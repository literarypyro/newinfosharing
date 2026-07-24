<?php
session_start();
?>
<?php
require_once("phpexcel/Classes/PHPExcel.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
/* excel_functions.php no longer needed -- this version talks to PHPExcel directly */
?>
<?php
require_once("db_config.php"); /* centralized credentials -- see db_config.php.
   The old file carried a hardcoded mysqli username/password in source, which is
   exactly what db_config.php was created to eliminate. */
?>
<?php
ini_set("date.timezone","Asia/Manila"); /* was Asia/Kuala_Lumpur -- same fix as generate_nis.php */
?>
<?php
/* ============================================================================
   generate_tar.php -- Train Availability Report printout (2026-07 rewrite)

   WHAT CHANGED STRUCTURALLY
   The old TAR.xls contained SIX pre-drawn ledger blocks (7 trains each, 43
   rows per block) and the code filled them positionally; anything beyond the
   pre-drawn blocks simply could not be printed, and the blocks had drifted
   from hand-copying (block 4's letterhead still said "...AND COMMUNICATIONS").
   The new forms/TAR_template.xlsx contains exactly ONE canonical ledger block
   (rows 1-43: header rows 1-13, seven 4-row train slots at rows 14-41, two
   spacer rows) plus the footer (rows 44-54: notes, signature block). This
   script CLONES the block as many times as the day's data needs:

     pages = ceil(trains / 7)
     1. insert 43*(pages-1) rows before the footer
     2. copy block rows 1-43 into each new page: values, styles (by xf index),
        row heights, and merges (collected once, re-applied at an offset)
     3. fill the header of EVERY page (Day/Date/Code + signatories -- the old
        code only ever filled page 1's header, so printed pages 2+ showed the
        stale names baked into the template)
     4. fill train slots; a slot is 4 rows, all merges pre-exist in the template
     5. trim the unused slots of the last page and let the footer follow the
        data directly (same look as the old removeRow slide-up, but computed
        from known geometry instead of merge-then-unmerge guesswork)
     6. one explicit page break after every full block, so a block IS a page
        regardless of printer-driver rounding (the old file relied on natural
        pagination at 100% scale, which is exactly what drifts)

   Geometry constants below mirror the template. If you edit the template's
   layout, update them together.

   DELIBERATE FIXES from the old file (2026-07-24), beyond the structure:
   a. $personnel_date was assigned from $ccdr_date, a variable that does not
      exist in this file (copy-paste from generate_nis), so the signatory
      date comparison always failed and the TOP header always printed the
      OLDEST signatories row. Now uses the report date, so the GM / Director
      names match what was in effect on that date.
   b. $availability_date (used to decide whether an insert/remove time from a
      different day gets its date printed above it) was also undefined, so the
      date prefix NEVER appeared. Now the report date.
   c. Stale-value leak: $inserted_to (and friends) were only reset inside some
      branches, so a train with no insert time could print the PREVIOUS
      train's "Quezon Ave." prefix. All per-train variables reset every loop.
   d. The removed-from check compared an undefined variable ($removed_from)
      instead of the DB column, and used an HTML "<br/>" inside an Excel cell.
      Now reads $row2['removed_from'] (guarded -- stays blank if the column
      does not exist) and uses a real newline, mirroring the insert side.
   e. GET date validated + escaped before entering SQL; helper lookups guarded
      against missing rows instead of fataling.
   f. Timezone Asia/Kuala_Lumpur -> Asia/Manila (same offset, correct zone).

   PRESERVED ON PURPOSE
   - getLevel() live-ordinal computation and its full explanatory comment.
   - The 4-switch cap and its 2026-07 review comment (with a short addendum:
     the template confirms columns B-E are the only switch columns).
   - Query shapes, name formatting, CANCELLED banner rules, ordinal clauses.
   ============================================================================ */

/* ---- geometry of forms/TAR_template.xlsx ---- */
define("TAR_BLOCK_ROWS", 43);        /* rows per ledger block / printed page  */
define("TAR_SLOTS_PER_PAGE", 7);     /* train slots per block                 */
define("TAR_SLOT_ROWS", 4);          /* rows per train slot                   */
define("TAR_DATA_TOP", 14);          /* first slot's top row within a block   */
define("TAR_FOOTER_FIRST", 44);      /* footer's first row in the template    */
define("TAR_FOOTER_NAMES_OFFSET", 9);/* names row = footer first + this       */

/* Trim the unused slots of the last page so the footer follows the data
   (the old behaviour). Set false to keep full blank ruled slots instead. */
define("TAR_TRIM_LAST_PAGE", true);

/* Set true to print layout numbers (pages, rows, footer position). */
define("TAR_DEBUG", false);

function getTrainDriver($id,$dbase){
	$sql="select * from train_driver where id='".$dbase->real_escape_string($id)."'";
	$rs=$dbase->query($sql);
	if(!$rs || $rs->num_rows==0){ return ""; }
	$row=$rs->fetch_assoc();
	$name=$row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'];
	return $name;
}

function getTrainDriver2($db,$td_id){
	$sql="select * from train_driver where id='".$db->real_escape_string($td_id)."' limit 1";
	$rs=$db->query($sql);
	if(!$rs || $rs->num_rows==0){ return ""; }
	$row=$rs->fetch_assoc();
	$name=$row['firstName']." ".substr($row['midName'],0,1).". ".$row['lastName'];
	return $name;
}

function getPHTrainDriver($id,$dbase){
	$sql="select firstName,lastName from ph_trams where id='".$dbase->real_escape_string($id)."' limit 1";
	$rs=$dbase->query($sql);
	if($rs && $rs->num_rows>0){
		$row=$rs->fetch_assoc();
		$name=substr($row['firstName'],0,1).". ".$row['lastName'];
	}
	else {
		$name=$id;
	}
	return $name;
}

function getLevel($id,$dbase){
	/* === FIX APPLIED 2026-07 (same fix as item #16 in train_availability.php and
	   edit_ccdr.php's getLevelRank(), never previously applied here) ===
	   `level`.`order` is a MyISAM per-(date,level) AUTO_INCREMENT -- it numbers by
	   INSERTION ORDER, so a late-entered incident, a correction (edit_ccdr's level
	   handler deletes+reinserts the row), or a deletion permanently desyncs it from
	   true chronological order. This was the exact bug fixed on-screen in
	   train_availability.php and edit_ccdr.php, but this file -- which generates
	   the actual PRINTED TAR report handed to management -- was never included in
	   that fix, so the paper document could show different 1st/2nd/3rd ordinals
	   than the screen. Now computes the ordinal live instead: this incident's
	   chronological position (by incident_report.incident_date, ties by id) among
	   all same-day, same-level incidents -- the same population the stored counter
	   numbered. Self-contained prepared statements since this file has no db_exec()/
	   db_query() helpers (matches the approach already used in edit_ccdr.php). The
	   `order` column itself is untouched -- the MyISAM engine keeps writing it
	   exactly as before, it's just no longer read here. === END FIX === */
	$stmt=$dbase->prepare("select l.date,l.level,ir.incident_date,ir.id as ir_id
		from level l join incident_report ir on ir.id=l.incident_id
		where l.incident_id=? limit 1");
	if($stmt===false) return "";
	$stmt->bind_param("s",$id);
	$stmt->execute();
	$rs=$stmt->get_result();
	if($rs===false || $rs->num_rows==0) return "";
	$l0=$rs->fetch_assoc();
	$stmt=$dbase->prepare("select count(*)+1 as rnk
		from level l join incident_report ir on ir.id=l.incident_id
		where l.date=? and l.level=?
		and (ir.incident_date<? or (ir.incident_date=? and ir.id<?))");
	if($stmt===false) return "";
	$stmt->bind_param("sssss",$l0['date'],$l0['level'],$l0['incident_date'],$l0['incident_date'],$l0['ir_id']);
	$stmt->execute();
	$row=$stmt->get_result()->fetch_assoc();
	return $row['rnk'];
}

function getOrdinal($number){
	$ends = array('th','st','nd','rd','th','th','th','th','th','th');
	if (($number %100) >= 11 && ($number%100) <= 13)
	   $abbreviation = $number. 'th';
	else
	   $abbreviation = $number. $ends[$number % 10];
	return $abbreviation;
}

function tarCellText($v){
	/* PHPExcel returns a PHPExcel_RichText object for inline-string cells --
	   see the {{DATA}} postmortem in generate_nis.php. Flatten everything. */
	if($v===null){ return ""; }
	if(is_object($v)){
		if(method_exists($v,'getPlainText')){ return $v->getPlainText(); }
		return (string)$v;
	}
	return (string)$v;
}

function tarSetCell($sheet,$coord,$v){
	/* Plain digit runs (train index, car numbers) are written numeric so Excel
	   does not flag them "number stored as text"; anything else (times with
	   colons, names, multi-line remarks) is explicit text. */
	$v=(string)$v;
	if($v===""){ return; }
	if(ctype_digit($v) && strlen($v)<15 && (strlen($v)==1 || $v[0]!=="0")){
		$sheet->setCellValue($coord,(int)$v);
	}
	else {
		$sheet->setCellValueExplicit($coord,$v,PHPExcel_Cell_DataType::TYPE_STRING);
	}
}

function tarParseRange($range){
	/* "L14:O17" -> array("L",14,"O",17); avoids PHPExcel's mixed 0-based /
	   1-based column-index helpers entirely. */
	if(preg_match('/^([A-Z]+)(\d+):([A-Z]+)(\d+)$/',$range,$m)){
		return array($m[1],(int)$m[2],$m[3],(int)$m[4]);
	}
	if(preg_match('/^([A-Z]+)(\d+)$/',$range,$m)){
		return array($m[1],(int)$m[2],$m[1],(int)$m[2]);
	}
	return null;
}

if(isset($_GET['tar'])){
	$tar_date=$_GET['tar'];

	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$tar_date)){
		die("Invalid date.");
	}

	$templateFile="forms/TAR_template.xlsx";
	if(!file_exists($templateFile)){
		die("Template not found: ".htmlspecialchars($templateFile).". Upload TAR_template.xlsx into forms/.");
	}
	if(!class_exists('ZipArchive')){
		die("PHP's zip extension is not enabled, which PHPExcel needs for .xlsx.");
	}

	$dateSlip=date("Y-m-d His");
	$newFilename="printout/TAR_".$dateSlip.".xlsx";
	if(!@copy($templateFile,$newFilename)){
		die("Could not write into printout/ -- check that the folder exists and is writable.");
	}

	$reader=PHPExcel_IOFactory::createReader('Excel2007');
	$excel=$reader->load($newFilename);
	$sheet=$excel->getSheetByName("TAR");
	if($sheet===null){ $sheet=$excel->getActiveSheet(); }
	$excel->setActiveSheetIndex($excel->getIndex($sheet));

	$cols=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U');

	$db=iss_db('transport');
	$db2=iss_db('user_transport');
	$tar_esc=$db->real_escape_string($tar_date);

	/* fixes (a) and (b): both of these were undefined in the old file */
	$personnel_date=$tar_date;
	$availability_date=$tar_date;

	/* ---- day / date / timetable code ---- */
	$header_day=date("l",strtotime($tar_date));
	$header_date=date("F d, Y",strtotime($tar_date));
	$header_code="";
	$timeTableRS=$db->query("select *,timetable_day.id as timeId from timetable_day inner join timetable_code on timetable_day.timetable_code=timetable_code.id where train_date like '".$tar_esc."%%'");
	if($timeTableRS && $timeTableRS->num_rows>0){
		$timeTableRow=$timeTableRS->fetch_assoc();
		$header_code=$timeTableRow['code'];
	}

	/* ---- signatories in effect on the report date (fix (a) makes this work) ---- */
	$gm=""; $gm_office=""; $director=""; $chief="";
	$signatoryRS=$db2->query("select * from signatories order by signatory_date DESC");
	if($signatoryRS && $signatoryRS->num_rows>0){
		$signatoryRow=$signatoryRS->fetch_assoc();
		if(strtotime($personnel_date)>=strtotime($signatoryRow['signatory_date'])){
			$gm=$signatoryRow['general_manager'];
			$gm_office=$signatoryRow['gm_office'];
			$director=$signatoryRow['director_ops'];
			$chief=$signatoryRow['chief_transport'];
		}
		else {
			$sigRS=$db2->query("select * from signatories where signatory_date>'".$db2->real_escape_string($personnel_date)."' order by signatory_date asc");
			if($sigRS && $sigRS->num_rows>0){
				$sigRow=$sigRS->fetch_assoc();
				$gm=$sigRow['general_manager'];
				$gm_office=$sigRow['gm_office'];
				$director=$sigRow['director_ops'];
				$chief=$sigRow['chief_transport'];
			}
		}
	}

	/* ---- shift-3 duty personnel for the footer ---- */
	$recording=""; $clerk=""; $duty_manager="";
	$prs=$db2->query("select * from duty_personnel where personnel_date like '".$db2->real_escape_string($personnel_date)."%%' and shift='3'");
	if($prs && $prs->num_rows>0){
		$prow=$prs->fetch_assoc();
		$recording=getTrainDriver2($db,$prow['recording']);
		$clerk=getTrainDriver2($db,$prow['clerk']);
		$duty_manager=getTrainDriver2($db,$prow['duty_manager']);
	}

	/* ---- the day's trains, buffered so the page count is known up front ---- */
	$trains=array();
	$rs=$db->query("select * from train_availability where date like '".$tar_esc."%%' order by date");
	if(!$rs){ die("Train availability query failed."); }
	while($r=$rs->fetch_assoc()){ $trains[]=$r; }
	$nm=count($trains);

	$pages=max(1,(int)ceil($nm/TAR_SLOTS_PER_PAGE));

	/* ================= 1-3. grow the workbook to the page count ================= */

	/* collect the block's merges once, as column-letter offsets */
	$blockMerges=array();
	foreach($sheet->getMergeCells() as $range=>$xx){
		$p=tarParseRange($range);
		if($p!==null && $p[3]<=TAR_BLOCK_ROWS){ $blockMerges[]=$p; }
	}

	if($pages>1){
		$sheet->insertNewRowBefore(TAR_FOOTER_FIRST, TAR_BLOCK_ROWS*($pages-1));

		for($p=1;$p<$pages;$p++){
			$base=TAR_BLOCK_ROWS*$p;
			for($r=1;$r<=TAR_BLOCK_ROWS;$r++){
				$h=$sheet->getRowDimension($r)->getRowHeight();
				if($h!=-1){ $sheet->getRowDimension($base+$r)->setRowHeight($h); }
				foreach($cols as $c){
					$src=$c.$r; $dst=$c.($base+$r);
					if(!$sheet->cellExists($src)){ continue; }
					$srcCell=$sheet->getCell($src);
					$sheet->getCell($dst)->setXfIndex($srcCell->getXfIndex());
					$v=$srcCell->getValue();
					if($v!==null && $v!==""){
						$t=tarCellText($v);
						if(is_numeric($v) && !is_string($v)){
							$sheet->setCellValue($dst,$v);
						}
						else {
							$sheet->setCellValueExplicit($dst,$t,PHPExcel_Cell_DataType::TYPE_STRING);
						}
					}
				}
			}
			foreach($blockMerges as $m){
				$sheet->mergeCells($m[0].($m[1]+$base).":".$m[2].($m[3]+$base));
			}
		}
	}

	$footerFirst=TAR_FOOTER_FIRST + TAR_BLOCK_ROWS*($pages-1);

	/* ================= 4. header of every page ================= */
	for($p=0;$p<$pages;$p++){
		$base=TAR_BLOCK_ROWS*$p;
		$sheet->setCellValueExplicit("O".($base+8),$header_day,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("O".($base+9),$header_date,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("O".($base+10),$header_code,PHPExcel_Cell_DataType::TYPE_STRING);
		if($gm!=""){ $sheet->setCellValueExplicit("C".($base+8),$gm,PHPExcel_Cell_DataType::TYPE_STRING); }
		if($gm_office!=""){ $sheet->setCellValueExplicit("C".($base+9),$gm_office,PHPExcel_Cell_DataType::TYPE_STRING); }
		if($director!=""){ $sheet->setCellValueExplicit("J".($base+8),$director,PHPExcel_Cell_DataType::TYPE_STRING); }
	}

	/* ================= 5. fill the train slots ================= */
	for($i=0;$i<$nm;$i++){
		$row=$trains[$i];

		$page=(int)floor($i/TAR_SLOTS_PER_PAGE);
		$slot=$i%TAR_SLOTS_PER_PAGE;
		$start=TAR_BLOCK_ROWS*$page + TAR_DATA_TOP + TAR_SLOT_ROWS*$slot;
		$end=$start+TAR_SLOT_ROWS-1;

		/* fix (c): reset EVERYTHING per train -- the old loop leaked
		   $inserted_to (and could leak dates/drivers) between trains */
		$boundary_time=""; $insert_time=""; $insert_driver=""; $inserted_to="";
		$remove_time=""; $remove_driver=""; $removed_from=""; $remove_remarks="";
		$insert_date=""; $remove_date="";

		$train_index=$row['index_no'];
		tarSetCell($sheet,"A".$start,$train_index);

		$rs3=$db->query("select * from train_switch where train_ava_id='".$db->real_escape_string($row['id'])."' order by date_change");
		$nm3=$rs3?$rs3->num_rows:0;

		/* === REVIEWED 2026-07 -- NOT changed, and here's precisely why ===
		   The screen (train_availability.php) allows up to 7 switches; this print
		   loop caps at 4. Raising this to 7 to match is NOT safe to do blindly:
		   the loop below writes one switch per column starting at chr(66)='B' and
		   incrementing (B, C, D, E for n=0..3). The very next column, F, is where
		   car_a is unconditionally written a few lines down, G is boundary_time,
		   H is insert data -- ALL written after this loop runs, so they'd
		   immediately overwrite whatever a 5th+ switch wrote there. Raising the
		   cap wouldn't actually surface switches 5-7 on the printed page; it
		   would silently make them vanish (overwritten a few lines later)
		   instead of the current honest, deliberate cap. B-through-E is exactly
		   the 4-column gap between index_no (A) and cars (F) -- this was very
		   likely sized to the template on purpose, not a bug.
		   ADDENDUM 2026-07-24: template in hand -- TAR.xls's data slots have
		   switch sub-cells in columns B-E only (two stacked 2-row merges per
		   column), so the 4-column cap matches the physical form exactly and
		   stands. === END REVIEW === */
		if($nm3>4){
			$nm3=4;
		}

		$col=66;
		for($n=0;$n<$nm3;$n++){
			$row3=$rs3->fetch_assoc();
			tarSetCell($sheet,chr($col).$start,date("H:i",strtotime($row3['date_change'])));
			tarSetCell($sheet,chr($col).($end-1),$row3['new_index']);
			$col++;
		}

		tarSetCell($sheet,"F".$start,$row['car_a']);
		tarSetCell($sheet,"F".($start+1),$row['car_b']);
		tarSetCell($sheet,"F".$end,$row['car_c']);

		$rs2=$db->query("select * from train_ava_time where train_ava_id='".$db->real_escape_string($row['id'])."'");
		$row2=($rs2 && $rs2->num_rows>0)?$rs2->fetch_assoc():array();

		if(isset($row2['boundary_time']) && $row2['boundary_time']!=""){
			$boundary_time=date("H:i",strtotime($row2['boundary_time']));
		}

		if(isset($row2['insert_time']) && $row2['insert_time']!=""){
			if($row2['insert_time']=="0000-00-00 00:00:00"){
				$insert_date="";
				$insert_time="";
			}
			else {
				$insert_time=date("H:i",strtotime($row2['insert_time']));
				$insert_date=date("Y-m-d",strtotime($row2['insert_time']));
				/* fix (b): with $availability_date defined, a carried-over
				   insert from a previous day now shows its date again */
				if(strtotime($availability_date)>strtotime($insert_date)){
					$insert_time=$insert_date."\n".$insert_time;
				}
			}

			$inserted_to=isset($row2['inserted_to'])?$row2['inserted_to']:"";

			if($row['type']=="unimog"){
				$insert_driver=getPHTrainDriver($row2['insert_driver'],$db)."\nMAINTENANCE PROVIDER";
			}
			else if($row['type']=="test"){
				$insert_driver=getPHTrainDriver($row2['insert_driver'],$db)."\nMAINTENANCE PROVIDER";
			}
			else if($row['type']=="reserve"){
				$insert_driver=$row2['insert_driver'];
			}
			else {
				$insert_driver=getTrainDriver($row2['insert_driver'],$db);
			}
			if($inserted_to=="quezon"){ $inserted_to="Quezon Ave.\n"; }
			else { $inserted_to=""; }
		}

		if(isset($row2['remove_time']) && $row2['remove_time']!=""){
			if($row2['remove_time']=="0000-00-00 00:00:00"){
				$remove_time="";
				$remove_date="";
			}
			else {
				$remove_date=date("Y-m-d",strtotime($row2['remove_time']));
				$remove_time=date("H:i",strtotime($row2['remove_time']));
				if(strtotime($availability_date)>strtotime($remove_date)){
					$remove_time=$remove_date."\n".$remove_time;
				}
			}
			if($row['type']=="unimog"){
				$remove_driver=getPHTrainDriver($row2['remove_driver'],$db)."\nMAINTENANCE PROVIDER";
			}
			else if($row['type']=="test"){
				$remove_driver=getPHTrainDriver($row2['remove_driver'],$db)."\nMAINTENANCE PROVIDER";
			}
			else if($row['type']=="reserve"){
				$remove_driver=$row2['remove_driver'];
			}
			else {
				$remove_driver=getTrainDriver($row2['remove_driver'],$db);
			}
			/* fix (d): compare the DB column (guarded), not an undefined
			   variable, and prefix with a newline, not HTML */
			$removed_from=isset($row2['removed_from'])?$row2['removed_from']:"";
			if($removed_from=="quezon"){ $removed_from="Quezon Ave.\n"; }
			else { $removed_from=""; }
			$remove_remarks=isset($row2['removal_remarks'])?$row2['removal_remarks']:"";
		}

		if($boundary_time!=""){
			tarSetCell($sheet,"G".$start,$boundary_time);
		}

		/* incident references + L2/L3 ordinal clauses */
		$incidentClause="";
		$level2Clause="";
		$level3Clause="";
		$l2Count=0;
		$l3Count=0;
		$cancelRS=$db->query("select * from train_incident_view where train_ava_id='".$db->real_escape_string($row['id'])."'");
		$cancelNM=$cancelRS?$cancelRS->num_rows:0;
		for($m=0;$m<$cancelNM;$m++){
			$cancelRow=$cancelRS->fetch_assoc();
			$level=$cancelRow['level'];
			$order=getLevel($cancelRow['incident_id'],$db);

			if($m==0){
				$incidentClause.="SEE IN ".$cancelRow['incident_no'];
			}
			else {
				$incidentClause.=",\n";
				$incidentClause.="IN ".$cancelRow['incident_no'];
			}

			if($level==2){
				if($l2Count==0){ $level2Clause.=getOrdinal($order); }
				else { $level2Clause.=",\n".getOrdinal($order); }
				$l2Count++;
			}
			else if($level==3){
				if($l3Count==0){ $level3Clause.=getOrdinal($order); }
				else { $level3Clause.=",\n".getOrdinal($order); }
				$l3Count++;
			}
		}

		if($row['status']=="active"){
			tarSetCell($sheet,"H".$start,$inserted_to.$insert_time);
			tarSetCell($sheet,"I".$start,$insert_driver);
			tarSetCell($sheet,"J".$start,$removed_from.$remove_time);
			tarSetCell($sheet,"K".$start,$remove_driver);
		}
		else {
			/* CANCELLED banner spans the time/driver columns. The slots are
			   pre-merged per column in the template, so widen deliberately:
			   unmerge the involved columns, then merge the span. */
			$spanCols=($boundary_time=="")?array("G","H","I","J","K"):array("H","I","J","K");
			foreach($spanCols as $sc){
				$rng=$sc.$start.":".$sc.$end;
				$merged=$sheet->getMergeCells();
				if(isset($merged[$rng])){ $sheet->unmergeCells($rng); }
			}
			$firstCol=$spanCols[0];
			$sheet->mergeCells($firstCol.$start.":K".$end);
			tarSetCell($sheet,$firstCol.$start,"CANCELLED");
		}

		$remarksOut=$remove_remarks;
		if($incidentClause!=""){
			$remarksOut=($remarksOut!="")?$remarksOut."\n".$incidentClause:$incidentClause;
		}
		tarSetCell($sheet,"L".$start,$remarksOut);
		tarSetCell($sheet,"P".$start,$level2Clause);
		tarSetCell($sheet,"Q".$start,$level3Clause);
	}

	/* ================= 6. trim the last page's unused slots ================= */
	$usedLast=$nm - TAR_SLOTS_PER_PAGE*($pages-1);
	$unused=TAR_SLOTS_PER_PAGE-$usedLast;
	if(TAR_TRIM_LAST_PAGE && $unused>0){
		$base=TAR_BLOCK_ROWS*($pages-1);
		$firstDead=$base + TAR_DATA_TOP + TAR_SLOT_ROWS*$usedLast;
		$lastDead=$base + TAR_DATA_TOP + TAR_SLOT_ROWS*TAR_SLOTS_PER_PAGE - 1;

		foreach(array_keys($sheet->getMergeCells()) as $range){
			$p=tarParseRange($range);
			if($p!==null && $p[1]>=$firstDead && $p[3]<=$lastDead){
				$sheet->unmergeCells($range);
			}
		}
		$sheet->removeRow($firstDead, $lastDead-$firstDead+1);
		$footerFirst -= ($lastDead-$firstDead+1);
	}

	/* ================= 7. one block = one printed page ================= */
	/* The template ships with its own break after row 43. removeRow() above
	   SHIFTS inherited breaks, so after trimming (and especially on a one-page
	   day) that break would land mid-footer and split it across pages. Clear
	   everything and set the block breaks fresh on final coordinates. */
	foreach(array_keys($sheet->getBreaks()) as $brkCell){
		$sheet->setBreak($brkCell, PHPExcel_Worksheet::BREAK_NONE);
	}
	for($p=1;$p<$pages;$p++){
		$sheet->setBreak("A".(TAR_BLOCK_ROWS*$p), PHPExcel_Worksheet::BREAK_ROW);
	}

	/* ================= 8. footer signature names ================= */
	$namesRow=$footerFirst + TAR_FOOTER_NAMES_OFFSET;
	if($clerk!=""){ $sheet->setCellValueExplicit("B".$namesRow,$clerk,PHPExcel_Cell_DataType::TYPE_STRING); }
	if($recording!=""){ $sheet->setCellValueExplicit("G".$namesRow,$recording,PHPExcel_Cell_DataType::TYPE_STRING); }
	if($duty_manager!=""){ $sheet->setCellValueExplicit("K".$namesRow,$duty_manager,PHPExcel_Cell_DataType::TYPE_STRING); }
	if($chief!=""){ $sheet->setCellValueExplicit("N".$namesRow,$chief,PHPExcel_Cell_DataType::TYPE_STRING); }

	/* ---- black-fill guard: see the fill postmortem in generate_nis.php ---- */
	$sheet->getStyle("A1:U".$sheet->getHighestRow())
	      ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_NONE);

	if(TAR_DEBUG){
		echo "<pre>trains=".$nm." pages=".$pages." usedLast=".$usedLast
			." footerFirst=".$footerFirst." namesRow=".$namesRow
			." highestRow=".$sheet->getHighestRow()."</pre>";
	}

	/* ---- save ---- */
	$writer=PHPExcel_IOFactory::createWriter($excel,'Excel2007');
	$writer->save($newFilename);

	echo "Train Availability Report has been generated (".$nm." train".($nm==1?"":"s").", ".$pages." page".($pages==1?"":"s")."). Press right click and Save As: <a href='".htmlspecialchars($newFilename)."'>Here</a>";
}
?>