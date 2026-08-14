<?php
/* =====================================================================
   qr_svg.php  --  QR code generator, pure PHP, no dependencies.

   WHY THIS EXISTS
   ---------------
   The first cut of mobile_app.php loaded a JavaScript QR library and
   drew the code in the browser. That library was never uploaded, so the
   box rendered empty. Pointing at a CDN is not an option either -- the
   station PCs have no internet, so it would fail exactly the same way
   with no explanation.

   So the QR is built on the server and emitted as inline SVG:
     * nothing to download, nothing to copy station to station
     * works with JavaScript disabled
     * prints correctly (it is vector, not a canvas)
     * cannot break because a CDN moved or a file was missed in a rollout

   Byte mode, ECC levels L/M/Q/H, versions 1-10 (up to 271 bytes at L),
   which is far more than any intranet URL needs.

   USE
     require_once("qr_svg.php");
     echo qr_svg("http://10.0.0.5/iss/app_download.php", 160);

   Verified by round trip: the generated matrix was rendered and read
   back with a real decoder (OpenCV), not merely eyeballed.
   ===================================================================== */

if(defined('QR_SVG_LOADED')){ return; }
define('QR_SVG_LOADED',true);

/* ---------------------------------------------------------------------
   GF(256) arithmetic for Reed-Solomon, primitive polynomial 0x11D.
   --------------------------------------------------------------------- */
function qr_gf(){
	static $t=null;
	if($t!==null){ return $t; }
	$exp=array_fill(0,512,0); $log=array_fill(0,256,0);
	$x=1;
	for($i=0;$i<255;$i++){
		$exp[$i]=$x;
		$log[$x]=$i;
		$x<<=1;
		if($x&0x100){ $x^=0x11D; }
	}
	for($i=255;$i<512;$i++){ $exp[$i]=$exp[$i-255]; }
	return $t=array('exp'=>$exp,'log'=>$log);
}

function qr_gf_mul($a,$b){
	if($a==0||$b==0){ return 0; }
	$t=qr_gf();
	return $t['exp'][$t['log'][$a]+$t['log'][$b]];
}

/* Generator polynomial for $degree error-correction codewords. */
function qr_rs_gen($degree){
	static $cache=array();
	if(isset($cache[$degree])){ return $cache[$degree]; }
	$t=qr_gf();
	$poly=array(1);
	for($i=0;$i<$degree;$i++){
		$next=array_fill(0,count($poly)+1,0);
		foreach($poly as $j=>$c){
			$next[$j]   ^= qr_gf_mul($c,$t['exp'][$i]);
			$next[$j+1] ^= $c;
		}
		$poly=$next;
	}
	/* The loop above accumulates with index 0 as the CONSTANT term. The
	   division in qr_rs_encode wants index 0 to be the LEADING coefficient
	   (the x^degree term), so reverse once here rather than indexing
	   backwards at every step. Getting this backwards is silent: the
	   codewords still come out the right length and only a real decoder
	   notices, which is why this file is round-trip tested. */
	$poly=array_reverse($poly);
	return $cache[$degree]=$poly;
}

/* EC codewords for one data block. */
function qr_rs_encode($data,$ecLen){
	$gen=qr_rs_gen($ecLen);
	$rem=array_fill(0,$ecLen,0);
	foreach($data as $b){
		$factor = $b ^ $rem[0];
		array_shift($rem);
		$rem[]=0;
		for($i=0;$i<$ecLen;$i++){
			$rem[$i] ^= qr_gf_mul($gen[$i+1],$factor);
		}
	}
	return $rem;
}

/* ---------------------------------------------------------------------
   Block structure per version and EC level:
     ecPerBlock, group1Blocks, group1Data, group2Blocks, group2Data
   Versions 1-10 only -- deliberately, since the payload here is a URL.
   --------------------------------------------------------------------- */
function qr_blocks($ver,$ec){
	static $T=array(
	 1=>array('L'=>array(7,1,19,0,0),  'M'=>array(10,1,16,0,0), 'Q'=>array(13,1,13,0,0), 'H'=>array(17,1,9,0,0)),
	 2=>array('L'=>array(10,1,34,0,0), 'M'=>array(16,1,28,0,0), 'Q'=>array(22,1,22,0,0), 'H'=>array(28,1,16,0,0)),
	 3=>array('L'=>array(15,1,55,0,0), 'M'=>array(26,1,44,0,0), 'Q'=>array(18,2,17,0,0), 'H'=>array(22,2,13,0,0)),
	 4=>array('L'=>array(20,1,80,0,0), 'M'=>array(18,2,32,0,0), 'Q'=>array(26,2,24,0,0), 'H'=>array(16,4,9,0,0)),
	 5=>array('L'=>array(26,1,108,0,0),'M'=>array(24,2,43,0,0), 'Q'=>array(18,2,15,2,16),'H'=>array(22,2,11,2,12)),
	 6=>array('L'=>array(18,2,68,0,0), 'M'=>array(16,4,27,0,0), 'Q'=>array(24,4,19,0,0), 'H'=>array(28,4,15,0,0)),
	 7=>array('L'=>array(20,2,78,0,0), 'M'=>array(18,4,31,0,0), 'Q'=>array(18,2,14,4,15),'H'=>array(26,4,13,1,14)),
	 8=>array('L'=>array(24,2,97,0,0), 'M'=>array(22,2,38,2,39),'Q'=>array(22,4,18,2,19),'H'=>array(26,4,14,2,15)),
	 9=>array('L'=>array(30,2,116,0,0),'M'=>array(22,3,36,2,37),'Q'=>array(20,4,16,4,17),'H'=>array(24,4,12,4,13)),
	10=>array('L'=>array(18,2,68,2,69),'M'=>array(26,4,43,1,44),'Q'=>array(24,6,19,2,20),'H'=>array(28,6,15,2,16))
	);
	return isset($T[$ver][$ec]) ? $T[$ver][$ec] : null;
}

/* Alignment pattern centre coordinates. */
function qr_align_centers($ver){
	static $T=array(1=>array(),2=>array(6,18),3=>array(6,22),4=>array(6,26),5=>array(6,30),
	                6=>array(6,34),7=>array(6,22,38),8=>array(6,24,42),9=>array(6,26,46),10=>array(6,28,50));
	return isset($T[$ver]) ? $T[$ver] : array();
}

/* Bits of padding after the interleaved codewords. */
function qr_remainder_bits($ver){
	if($ver==1){ return 0; }
	if($ver<=6){ return 7; }
	return 0;   /* versions 7-13 */
}

function qr_data_capacity($ver,$ec){
	$b=qr_blocks($ver,$ec);
	if(!$b){ return 0; }
	return $b[1]*$b[2] + $b[3]*$b[4];
}

/* Smallest version that fits $len bytes, or 0. */
function qr_pick_version($len,$ec){
	for($v=1;$v<=10;$v++){
		$cap=qr_data_capacity($v,$ec);
		if(!$cap){ continue; }
		$countBits = ($v<=9) ? 8 : 16;
		if(4 + $countBits + 8*$len <= $cap*8){ return $v; }
	}
	return 0;
}

/* ---------------------------------------------------------------------
   Byte-mode bitstream -> padded, blocked, interleaved codewords.
   --------------------------------------------------------------------- */
function qr_codewords($text,$ver,$ec){
	$len=strlen($text);
	$countBits = ($ver<=9) ? 8 : 16;

	$bits='';
	$bits .= '0100';                                        /* byte mode */
	$bits .= str_pad(decbin($len),$countBits,'0',STR_PAD_LEFT);
	for($i=0;$i<$len;$i++){
		$bits .= str_pad(decbin(ord($text[$i])),8,'0',STR_PAD_LEFT);
	}

	$cap = qr_data_capacity($ver,$ec)*8;
	/* Terminator: up to four zero bits, but never past capacity. */
	$bits .= str_repeat('0', min(4, $cap-strlen($bits)));
	/* Pad to a byte boundary, then alternate the two standard pad bytes. */
	if(strlen($bits)%8){ $bits .= str_repeat('0', 8-(strlen($bits)%8)); }
	$pads=array('11101100','00010001'); $p=0;
	while(strlen($bits) < $cap){ $bits .= $pads[$p%2]; $p++; }

	$data=array();
	for($i=0;$i<strlen($bits);$i+=8){ $data[]=bindec(substr($bits,$i,8)); }

	/* Split into blocks per the version/EC spec. */
	$spec=qr_blocks($ver,$ec);
	$ecLen=$spec[0];
	$blocks=array(); $ecblocks=array(); $pos=0;
	for($i=0;$i<$spec[1];$i++){
		$b=array_slice($data,$pos,$spec[2]); $pos+=$spec[2];
		$blocks[]=$b; $ecblocks[]=qr_rs_encode($b,$ecLen);
	}
	for($i=0;$i<$spec[3];$i++){
		$b=array_slice($data,$pos,$spec[4]); $pos+=$spec[4];
		$blocks[]=$b; $ecblocks[]=qr_rs_encode($b,$ecLen);
	}

	/* Interleave: column-wise across blocks, data first then EC. */
	$out=array();
	$maxData=max($spec[2],$spec[4]);
	for($i=0;$i<$maxData;$i++){
		foreach($blocks as $b){ if(isset($b[$i])){ $out[]=$b[$i]; } }
	}
	for($i=0;$i<$ecLen;$i++){
		foreach($ecblocks as $b){ if(isset($b[$i])){ $out[]=$b[$i]; } }
	}
	return $out;
}

/* ---------------------------------------------------------------------
   Matrix construction.
   --------------------------------------------------------------------- */
function qr_build($ver,$codewords){
	$size = 17 + 4*$ver;
	$m    = array(); $fn = array();
	for($r=0;$r<$size;$r++){
		$m[$r]=array_fill(0,$size,0);
		$fn[$r]=array_fill(0,$size,0);
	}

	$set=function($r,$c,$v) use (&$m,&$fn,$size){
		if($r<0||$c<0||$r>=$size||$c>=$size){ return; }
		$m[$r][$c]=$v?1:0; $fn[$r][$c]=1;
	};

	/* Finder patterns + separators. */
	$finder=function($fr,$fc) use ($set,$size){
		for($r=-1;$r<=7;$r++){
			for($c=-1;$c<=7;$c++){
				$rr=$fr+$r; $cc=$fc+$c;
				if($rr<0||$cc<0||$rr>=$size||$cc>=$size){ continue; }
				$d=max(abs($r-3),abs($c-3));
				$set($rr,$cc,($d!=2 && $d<=3));
			}
		}
	};
	$finder(0,0); $finder(0,$size-7); $finder($size-7,0);

	/* Timing patterns. */
	for($i=0;$i<$size;$i++){
		if(!$fn[6][$i]){ $set(6,$i,($i%2)==0); }
		if(!$fn[$i][6]){ $set($i,6,($i%2)==0); }
	}

	/* Alignment patterns, skipping the three finder corners. */
	$cs=qr_align_centers($ver);
	$n=count($cs);
	for($i=0;$i<$n;$i++){
		for($j=0;$j<$n;$j++){
			if(($i==0&&$j==0)||($i==0&&$j==$n-1)||($i==$n-1&&$j==0)){ continue; }
			$ar=$cs[$i]; $ac=$cs[$j];
			for($r=-2;$r<=2;$r++){
				for($c=-2;$c<=2;$c++){
					$set($ar+$r,$ac+$c, max(abs($r),abs($c))!=1);
				}
			}
		}
	}

	/* Format-info area reserved now, written after the mask is chosen. */
	for($i=0;$i<9;$i++){
		if(!$fn[$i][8]){ $set($i,8,0); }
		if(!$fn[8][$i]){ $set(8,$i,0); }
	}
	for($i=0;$i<8;$i++){
		if(!$fn[8][$size-1-$i]){ $set(8,$size-1-$i,0); }
		if(!$fn[$size-1-$i][8]){ $set($size-1-$i,8,0); }
	}
	$set($size-8,8,1);   /* the always-dark module */

	/* Version info, versions 7 and up. */
	if($ver>=7){
		$rem=$ver;
		for($i=0;$i<12;$i++){ $rem = ($rem<<1) ^ ((($rem>>11)&1) * 0x1F25); }
		$bits = ($ver<<12) | $rem;
		for($i=0;$i<18;$i++){
			$bit = ($bits>>$i)&1;
			$a = $size-11 + ($i%3);
			$b = intval($i/3);
			$set($b,$a,$bit);
			$set($a,$b,$bit);
		}
	}

	/* Data, zigzagging up and down two columns at a time. */
	$bitIdx=0;
	$total=count($codewords)*8;
	for($right=$size-1; $right>=1; $right-=2){
		if($right==6){ $right=5; }
		for($vert=0;$vert<$size;$vert++){
			for($j=0;$j<2;$j++){
				$c = $right-$j;
				$upward = ((($right+1)&2)==0);
				$r = $upward ? ($size-1-$vert) : $vert;
				if($fn[$r][$c]){ continue; }
				if($bitIdx<$total){
					$byte=$codewords[$bitIdx>>3];
					$m[$r][$c]= ($byte >> (7-($bitIdx&7))) & 1;
					$bitIdx++;
				}
				/* Past the data, modules stay light -- the remainder bits. */
			}
		}
	}
	return array($m,$fn,$size);
}

function qr_mask_bit($mask,$r,$c){
	switch($mask){
		case 0: return (($r+$c)%2)==0;
		case 1: return ($r%2)==0;
		case 2: return ($c%3)==0;
		case 3: return (($r+$c)%3)==0;
		case 4: return ((intval($c/3)+intval($r/2))%2)==0;
		case 5: return ((($r*$c)%2)+(($r*$c)%3))==0;
		case 6: return (((($r*$c)%2)+(($r*$c)%3))%2)==0;
		case 7: return (((($r+$c)%2)+(($r*$c)%3))%2)==0;
	}
	return false;
}

function qr_write_format($m,$fn,$size,$ec,$mask){
	static $ecBits=array('L'=>1,'M'=>0,'Q'=>3,'H'=>2);
	$data = ($ecBits[$ec]<<3) | $mask;
	$rem=$data;
	for($i=0;$i<10;$i++){ $rem = ($rem<<1) ^ ((($rem>>9)&1) * 0x537); }
	$bits = (($data<<10) | $rem) ^ 0x5412;

	for($i=0;$i<=5;$i++){ $m[$i][8] = ($bits>>$i)&1; }
	$m[7][8] = ($bits>>6)&1;
	$m[8][8] = ($bits>>7)&1;
	$m[8][7] = ($bits>>8)&1;
	for($i=9;$i<15;$i++){ $m[8][14-$i] = ($bits>>$i)&1; }

	for($i=0;$i<8;$i++){ $m[8][$size-1-$i] = ($bits>>$i)&1; }
	for($i=8;$i<15;$i++){ $m[$size-15+$i][8] = ($bits>>$i)&1; }
	return $m;
}

/* The four penalty rules from the spec; lowest total wins. */
function qr_penalty($m,$size){
	$score=0;

	/* Rule 1 -- runs of five or more identical modules. */
	for($r=0;$r<$size;$r++){
		$run=1;
		for($c=1;$c<$size;$c++){
			if($m[$r][$c]==$m[$r][$c-1]){ $run++; }
			else { if($run>=5){ $score += 3+($run-5); } $run=1; }
		}
		if($run>=5){ $score += 3+($run-5); }
	}
	for($c=0;$c<$size;$c++){
		$run=1;
		for($r=1;$r<$size;$r++){
			if($m[$r][$c]==$m[$r-1][$c]){ $run++; }
			else { if($run>=5){ $score += 3+($run-5); } $run=1; }
		}
		if($run>=5){ $score += 3+($run-5); }
	}

	/* Rule 2 -- 2x2 blocks of one colour. */
	for($r=0;$r<$size-1;$r++){
		for($c=0;$c<$size-1;$c++){
			$v=$m[$r][$c];
			if($v==$m[$r][$c+1] && $v==$m[$r+1][$c] && $v==$m[$r+1][$c+1]){ $score += 3; }
		}
	}

	/* Rule 3 -- finder-like 1:1:3:1:1 sequences with four light modules. */
	$p1=array(1,0,1,1,1,0,1,0,0,0,0);
	$p2=array(0,0,0,0,1,0,1,1,1,0,1);
	for($r=0;$r<$size;$r++){
		for($c=0;$c<=$size-11;$c++){
			$a=true; $b=true;
			for($k=0;$k<11;$k++){
				if($m[$r][$c+$k]!=$p1[$k]){ $a=false; }
				if($m[$r][$c+$k]!=$p2[$k]){ $b=false; }
				if(!$a && !$b){ break; }
			}
			if($a){ $score += 40; }
			if($b){ $score += 40; }
		}
	}
	for($c=0;$c<$size;$c++){
		for($r=0;$r<=$size-11;$r++){
			$a=true; $b=true;
			for($k=0;$k<11;$k++){
				if($m[$r+$k][$c]!=$p1[$k]){ $a=false; }
				if($m[$r+$k][$c]!=$p2[$k]){ $b=false; }
				if(!$a && !$b){ break; }
			}
			if($a){ $score += 40; }
			if($b){ $score += 40; }
		}
	}

	/* Rule 4 -- deviation from an even dark/light split. */
	$dark=0;
	for($r=0;$r<$size;$r++){ $dark += array_sum($m[$r]); }
	$pct = ($dark*100.0)/($size*$size);
	$k = intval(abs($pct-50)/5);
	$score += $k*10;

	return $score;
}

/* Returns array(matrix, size) -- matrix[row][col], 1 = dark. */
/* $forceMask is for testing only -- it pins the mask so a generated matrix
   can be compared module-for-module against a reference implementation.
   Leave it null in production so the penalty rules choose the mask. */
function qr_matrix($text,$ec='M',$forceMask=null){
	$ec=strtoupper($ec);
	if(!in_array($ec,array('L','M','Q','H'))){ $ec='M'; }
	$ver=qr_pick_version(strlen($text),$ec);
	if(!$ver){ return null; }   /* too long for version 10 */

	$cw = qr_codewords($text,$ver,$ec);
	list($base,$fn,$size) = qr_build($ver,$cw);

	$best=null; $bestScore=null;
	for($mask=0;$mask<8;$mask++){
		if($forceMask!==null && $mask!=$forceMask){ continue; }
		$m=$base;
		for($r=0;$r<$size;$r++){
			for($c=0;$c<$size;$c++){
				if($fn[$r][$c]){ continue; }
				if(qr_mask_bit($mask,$r,$c)){ $m[$r][$c] ^= 1; }
			}
		}
		$m=qr_write_format($m,$fn,$size,$ec,$mask);
		$s=qr_penalty($m,$size);
		if($bestScore===null || $s<$bestScore){ $bestScore=$s; $best=$m; }
	}
	return array($best,$size);
}

/* ---------------------------------------------------------------------
   SVG output. One <path> for every dark module, so the file stays small
   and scales cleanly for print.
   --------------------------------------------------------------------- */
function qr_svg($text,$px=160,$ec='M',$dark='#00529B',$light='#ffffff',$quiet=4){
	$res=qr_matrix($text,$ec);
	if(!$res){ return ''; }
	list($m,$size)=$res;
	$total=$size + 2*$quiet;

	$d='';
	for($r=0;$r<$size;$r++){
		for($c=0;$c<$size;$c++){
			if($m[$r][$c]){ $d .= 'M'.($c+$quiet).' '.($r+$quiet).'h1v1h-1z'; }
		}
	}

	$s  = '<svg xmlns="http://www.w3.org/2000/svg" width="'.(int)$px.'" height="'.(int)$px.'" ';
	$s .= 'viewBox="0 0 '.$total.' '.$total.'" shape-rendering="crispEdges" role="img" ';
	$s .= 'aria-label="QR code linking to the app installer">';
	$s .= '<rect width="'.$total.'" height="'.$total.'" fill="'.htmlspecialchars($light,ENT_QUOTES,'UTF-8').'"/>';
	$s .= '<path d="'.$d.'" fill="'.htmlspecialchars($dark,ENT_QUOTES,'UTF-8').'"/>';
	$s .= '</svg>';
	return $s;
}