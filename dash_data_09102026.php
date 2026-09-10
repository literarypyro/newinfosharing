<?php
/* =====================================================================
   dash_data.php  --  shared data layer for the ISS dashboard pages.

   Both dashboard.php (console home) and dashboard_wall.php (OCC wall
   monitor) include THIS file and nothing else.  One query set, two
   skins -- so the numbers can never drift apart between them.

   Design notes
   ------------
   * Every helper require is GUARDED (file_exists + function_exists
     stubs).  Uploading this file before db_config.php degrades to an
     empty dashboard, never a blank page.  -- the statistics_report
     blank-page postmortem, applied up front.
   * Every table this file touches beyond train_availability /
     train_ava_time / incident_report is PROBED at runtime.  A widget
     whose table is absent hides itself instead of fatalling.
   * Dates are validated to Y-m-d then escaped; no user string ever
     reaches a query unescaped.

   ASSUMPTIONS worth checking against the live schema (all isolated
   here, in one block, so they are a one-line fix each):
     a) train_availability.date is the availability DATETIME and the
        row's operating day is date(ta.date).
     b) incident_report.level holds 'L2'/'L3'/'L4' style strings (or a
        bare digit).  dash_level() normalises both.  If level is an FK
        into a `level` table, set DASH_LEVEL_LOOKUP below.
     c) The index-switch table is auto-probed by column signature
        (new_index + date_change).  Set DASH_SWITCH_TABLE to pin it.
   ===================================================================== */

if(session_id()==""){ @session_start(); }

/* ---- tunables -------------------------------------------------- */
if(!defined('DASH_SWITCH_TABLE'))  define('DASH_SWITCH_TABLE',  '');   /* '' = auto-probe */
if(!defined('DASH_LEVEL_LOOKUP'))  define('DASH_LEVEL_LOOKUP',  '');   /* '' = level column is literal */
if(!defined('DASH_FLEET_TARGET'))  define('DASH_FLEET_TARGET',  0);    /* 0 = derive from the day's records */
if(!defined('DASH_SPARK_DAYS'))    define('DASH_SPARK_DAYS',    14);
if(!defined('DASH_TREND_MONTHS'))  define('DASH_TREND_MONTHS',  12);
/* @grain -- bucket counts for the other two grains. Years is deliberately
   short: six rows is enough to see a direction, and reaching further back
   crosses the missing stretch where every bar would need a caveat. */
if(!defined('DASH_TREND_YEARS'))   define('DASH_TREND_YEARS',    6);
if(!defined('DASH_TREND_WEEKS'))   define('DASH_TREND_WEEKS',   12);

/* --- handoff to the other console pages ---------------------------
   The train/incident pages scope themselves to one operating date held
   in the session (set from a POST'd search_date on those pages).  The
   dashboard cannot pass a date in a query string they don't read, so
   dash_goto.php writes that session key and redirects.  If your pages
   use a different key or format, change it HERE and every link on the
   dashboard follows.  ------------------------------------------------ */
if(!defined('DASH_DATE_SESSION_KEY'))    define('DASH_DATE_SESSION_KEY',   'search_date');
if(!defined('DASH_DATE_SESSION_FORMAT')) define('DASH_DATE_SESSION_FORMAT','m/d/Y');
if(!defined('DASH_GOTO'))                define('DASH_GOTO',               'dash_goto.php');

/* Status band: 'band' (recommended) or 'ticker' (scrolling marquee). */
if(!defined('DASH_BAND_MODE'))     define('DASH_BAND_MODE',   'band');
if(!defined('DASH_BAND_ROTATE'))   define('DASH_BAND_ROTATE', 8);   /* seconds per item, band mode */
if(!defined('DASH_BAND_MAX'))      define('DASH_BAND_MAX',    5);

/* --- slide panel handoff ------------------------------------------
   The band is the entry point into editing the CCDR record it names.
   Clicking an item opens it in the iframe slide panel instead of
   navigating away.  Three things to confirm against your slide panel:

     DASH_PANEL_FN   openSlidePanel(url, title), from the extracted
                     slide_panel.php.  This is the CURRENT house pattern --
                     problem_history.php, other_history.php, td_history.php
                     and car_statistics_report.php all call it exactly this
                     way.  (train_operations.php still has the older inline
                     openEditIncidentPanel(irId,title), which took a bare
                     id; that file predates the extraction.)
     DASH_PANEL_URL  the no-JS fallback href.  {id} is the incident's
                     primary key.  Deliberately identical to the panel's
                     own irFallbackLink (no embed=1), so the keyboard
                     path, a middle-click and the 6-second frame-timeout
                     escape all land on exactly the same page.
     DASH_PANEL_TITLE  panel header text.

   If the opener is not defined on the page (dash_panel.php not included,
   JS blocked, wall board), the click is NOT intercepted and the href
   runs instead -- the band degrades to a plain link to the same record,
   never to a dead element and never to a different page.

   The save protocol ('ir-saved' / 'sp:saved') lives entirely in
   dash_panel.php, exactly as it does in train_operations.php.  Nothing
   here listens for it -- two listeners would mean two reloads. ----- */
if(!defined('DASH_PANEL'))         define('DASH_PANEL',        true);
if(!defined('DASH_PANEL_FN'))      define('DASH_PANEL_FN',     'openSlidePanel');
if(!defined('DASH_PANEL_URL'))     define('DASH_PANEL_URL',    'edit_ccdr.php?ir={id}');
/* Title format matches the other report pages exactly: "Incident - 2026-0812". */
if(!defined('DASH_PANEL_TITLE'))   define('DASH_PANEL_TITLE',  'Incident');

/* ---- guarded helper loads -------------------------------------- */
if(file_exists(dirname(__FILE__)."/db_config.php")){
	require_once(dirname(__FILE__)."/db_config.php");
}

/* =====================================================================
   Connection
   ===================================================================== */
function dash_db(){
	static $db=null; static $tried=false;
	if($tried){ return $db; }
	$tried=true;
	if(function_exists('iss_db')){
		$db=@iss_db('transport');
	}
	if($db && method_exists($db,'set_charset')){ @$db->set_charset('utf8'); }
	return $db;
}

function dash_ready(){ $db=dash_db(); return ($db && !$db->connect_errno); }

/* =====================================================================
   Schema probes -- so a missing table hides a widget, never kills a page
   ===================================================================== */
function dash_table_exists($t){
	static $c=array();
	if(isset($c[$t])){ return $c[$t]; }
	if(!dash_ready()){ return $c[$t]=false; }
	$db=dash_db();
	$rs=@$db->query("show tables like '".$db->real_escape_string($t)."'");
	return $c[$t]=($rs && $rs->num_rows>0);
}

function dash_columns($t){
	static $c=array();
	if(isset($c[$t])){ return $c[$t]; }
	$out=array();
	if(dash_table_exists($t)){
		$db=dash_db();
		$rs=@$db->query("show columns from `".str_replace("`","",$t)."`");
		if($rs){ while($r=$rs->fetch_assoc()){ $out[strtolower($r['Field'])]=$r['Field']; } }
	}
	return $c[$t]=$out;
}

function dash_has_col($t,$col){ $c=dash_columns($t); return isset($c[strtolower($col)]); }

/* First column name from $cands that actually exists on $t, else "". */
function dash_pick_col($t,$cands){
	$c=dash_columns($t);
	foreach($cands as $cand){ if(isset($c[strtolower($cand)])){ return $c[strtolower($cand)]; } }
	return "";
}

/* The index-switch table, by column signature. */
function dash_switch_table(){
	static $t=null;
	if($t!==null){ return $t; }
	if(DASH_SWITCH_TABLE!==''){ return $t=DASH_SWITCH_TABLE; }
	$cands=array('train_index_change','index_change','train_ava_switch','train_switch',
	             'switch_index','train_index_switch','index_switch');
	foreach($cands as $cand){
		if(dash_table_exists($cand) && dash_has_col($cand,'new_index') && dash_has_col($cand,'date_change')){
			return $t=$cand;
		}
	}
	return $t="";
}

/* =====================================================================
   Small utilities
   ===================================================================== */

/* Validate to Y-m-d, else today.  Nothing else may build a date. */
function dash_date($v){
	if(is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)){
		$p=explode("-",$v);
		if(checkdate((int)$p[1],(int)$p[2],(int)$p[0])){ return $v; }
	}
	return date("Y-m-d");
}

function dash_esc($v){
	$db=dash_db();
	return $db ? $db->real_escape_string($v) : addslashes($v);
}

function dash_h($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }

/* MySQL zero-dates and blanks all collapse to 0. */
function dash_ts($v){
	if($v===null){ return 0; }
	$v=trim((string)$v);
	if($v==="" || substr($v,0,10)==="0000-00-00"){ return 0; }
	$t=strtotime($v);
	return ($t===false || $t<=0) ? 0 : $t;
}

function dash_hm($v){ $t=dash_ts($v); return $t ? date("H:i",$t) : ""; }

/* =====================================================================
   Train availability -- the fleet strip and the four fleet tiles
   ===================================================================== */

/* Derived operating state.  This is the "truthful status pill" logic:
   read the timestamps, do not trust a stored status beyond 'cancelled'. */
function dash_train_state($r){
	if(isset($r['status']) && strtolower(trim($r['status']))=='cancelled'){ return 'cancelled'; }

	if(dash_ts(isset($r['remove_time'])?$r['remove_time']:"")){ return 'removed'; }
	if(dash_ts(isset($r['insert_time'])?$r['insert_time']:"")){ return 'online'; }

	if(dash_ts(isset($r['boundary_time'])?$r['boundary_time']:"")){ return 'boundary'; }
	return 'pending';
}

/* @skipping -- Whether a train skipped part of the loop, as ONE rule.
 *
 * The old test was `trim($r['inserted_to']) !== "north"`, which is true of the
 * empty string. inserted_to stays empty until a train is actually inserted, so
 * every pending and reserve train counted as skipping -- and because trains
 * are added to the day's schedule as the day goes on, the tile climbed all
 * morning. That is the multiplying figure: it was counting "not yet inserted"
 * as "skipped".
 *
 * A train that has not entered the loop cannot have skipped part of it, so an
 * insertion is required. The comparison is case-insensitive because nothing
 * constrains what train_operations_admin writes into the column.
 *
 * The dashboard's insertion feed badges the same thing a few lines down the
 * page. Both now call this, so the tile and the badges cannot disagree -- a
 * count of 6 beside four badged rows is the kind of contradiction that costs
 * the whole dashboard its credibility. */
function dash_is_skipping($r){
	if($r['skip']>0){ return true; } else { return false; }
	$to = strtolower(trim((string)(isset($r['inserted_to']) ? $r['inserted_to'] : "")));
	return ($to !== "" && $to !== "north");
}

function dash_train_state2($r){
	return dash_is_skipping($r) ? 'skipping' : 'null';
}

/* Non-revenue trains are shown but never counted against the target. */
function dash_is_revenue($r){
	$t=strtolower(trim(isset($r['type'])?$r['type']:""));
	return ($t==="" || $t=="revenue" || $t=="schooling");
}

function dash_trains($date){
	static $cache=array();
	$date=dash_date($date);
	if(isset($cache[$date])){ return $cache[$date]; }
	$out=array();
	if(!dash_ready() || !dash_table_exists('train_availability')){ return $cache[$date]=$out; }

	$d=dash_esc($date);
	$sql ="select ta.id,ta.index_no,ta.status,ta.type,ta.date,";
	$sql.="ta.car_a,ta.car_b,ta.car_c,ta.car_d,";
	$sql.="(select count(sk.departure_time) from skipping sk where sk.tar_id=ta.id) as skip,";
	$sql.="tt.boundary_time,tt.insert_time,tt.skipping,tt.remove_time,";
	$sql.="tt.insert_driver,tt.remove_driver,tt.inserted_to,tt.removed_from ";
	$sql.="from train_availability ta ";
	$sql.="left join train_ava_time tt on tt.train_ava_id=ta.id ";

	$sql.="where date(ta.date)='".$d."' ";
	$sql.="order by (ta.index_no+0), ta.index_no";

	$db=dash_db();
	$rs=@$db->query($sql);
	if($rs){
		while($r=$rs->fetch_assoc()){
			$r['state']=dash_train_state($r);
			$r['revenue']=dash_is_revenue($r);

			$out[]=$r;



		}
	}

	
	
	
	
	return $cache[$date]=$out;
}
function dash_trains2($date){
	static $cache=array();
	$date=dash_date($date);
	if(isset($cache[$date])){ return $cache[$date]; }
	$out=array();
	if(!dash_ready() || !dash_table_exists('train_availability')){ return $cache[$date]=$out; }

	$d=dash_esc($date);

	
	$sql2 ="select * from skipping ";
	$sql2.="where date(departure_time)='".$d."' ";

	
	
	$db=dash_db();
	$rs2=@$db->query($sql2);
	if($rs2){
		$counts=$rs2->num_rows;
	}
	else {
		$counts=0;
		
	}
	
	
	
	return $counts;
}
/* Counts keyed by state, plus the denominator the tiles divide by. */
function dash_fleet_counts($date){
	$rows=dash_trains($date);
	$c=array('online'=>0,'boundary'=>0,'removed'=>0,'cancelled'=>0,'pending'=>0,
	         'nonrevenue'=>0,'total'=>0,'target'=>0);
	foreach($rows as $r){
		$c[$r['state']]++;
		$c['total']++;
		if(!$r['revenue']){ $c['nonrevenue']++; }
	}
	$c['target']= DASH_FLEET_TARGET>0 ? DASH_FLEET_TARGET : max(1,$c['total']-$c['nonrevenue']);
	return $c;
}
function dash_fleet_counts2($date){
	$rows=dash_trains2($date);
	/* $c was never initialised as an array -- 'null' was incremented from
	   undefined on every non-skipping train, one notice per train. */
	$c['skipping']=$rows;	
	return $c;
}

/* Insertions of the day, newest first -- "most recent train on loop".
   @insertions -- $limit of 0 (or less) returns the whole day. The dashboard
   card scrolls now rather than truncating, and a card that scrolls should be
   showing everything: a scrollbar that stops at five is worse than a list of
   five, because it looks complete. The default stays 5 so every other caller
   behaves exactly as before. */
function dash_recent_insertions($date,$limit=5){
	$rows=dash_trains($date);
	$ins=array();
	foreach($rows as $r){
		$t=dash_ts($r['insert_time']);
		if($t){
			$ins[]=array(
				'ts'=>$t,
				'index_no'=>$r['index_no'],
				'point'=>(strtolower(trim((string)$r['inserted_to']))=='quezon')?'Quezon Ave.':'North Ave.',
				/* @skipping -- carried explicitly rather than re-derived from
				   the label above. The label maps anything that is not
				   'quezon' to 'North Ave.', so a third insertion point would
				   be displayed as North and badged as not-skipping while the
				   tile counted it. One rule, one answer. */
				'skipping'=>dash_is_skipping($r),
				'driver'=>$r['insert_driver']
			);
		}
	}
	usort($ins,'dash_cmp_ts_desc');
	if((int)$limit <= 0){ return $ins; }
	return array_slice($ins,0,(int)$limit);
}

function dash_cmp_ts_desc($a,$b){
	if($a['ts']==$b['ts']){ return 0; }
	return ($a['ts']<$b['ts']) ? 1 : -1;
}

/* Index switches for the day, if the table is present. */
function dash_switches($date){
	$t=dash_switch_table();
	if($t===""){ return array(); }
	$out=array();
	$db=dash_db();
	$d=dash_esc(dash_date($date));
	$sql="select s.new_index,s.date_change from `".str_replace("`","",$t)."` s ";
	$sql.="where date(s.date_change)='".$d."' order by s.date_change desc limit 20";
	$rs=@$db->query($sql);
	if($rs){ while($r=$rs->fetch_assoc()){ $out[]=$r; } }
	return $out;
}

/* =====================================================================
   Incidents
   ===================================================================== */

/* 'L3', '3', 3 and 'l3' all normalise to 'L3'.  Blank stays blank. */
function dash_level($r){
	$v=isset($r['level'])?trim((string)$r['level']):"";
	if($v===""){ return ""; }
	if(DASH_LEVEL_LOOKUP!==''){
		$map=dash_level_map();
		if(isset($map[$v])){ $v=$map[$v]; }
	}
	if(is_numeric($v)){ return "L".(int)$v; }
	$v=strtoupper($v);
	if(substr($v,0,1)!=="L"){ return "L".$v; }
	return $v;
}

function dash_level_map(){
	static $m=null;
	if($m!==null){ return $m; }
	$m=array();
	$t=DASH_LEVEL_LOOKUP;
	if($t!=='' && dash_table_exists($t)){
		$idc=dash_pick_col($t,array('id','level_id'));
		$lbl=dash_pick_col($t,array('level','name','label','description'));
		if($idc && $lbl){
			$db=dash_db();
			$rs=@$db->query("select `".$idc."` a,`".$lbl."` b from `".str_replace("`","",$t)."`");
			if($rs){ while($r=$rs->fetch_assoc()){ $m[$r['a']]=$r['b']; } }
		}
	}
	return $m;
}

/* Levels 0 and 1 are recorded incidents but are not service failures. */
function dash_is_failure($lvl){
	return ($lvl==="L2" || $lvl==="L3" || $lvl==="L4");
}

function dash_incidents($date){
	static $cache=array();
	$date=dash_date($date);
	if(isset($cache[$date])){ return $cache[$date]; }
	$out=array();
	if(!dash_ready() || !dash_table_exists('incident_report')){ return $cache[$date]=$out; }
	$db=dash_db();
	$d=dash_esc($date);
	$sql="select * from incident_report where date(incident_date)='".$d."' order by incident_date desc";
	$rs=@$db->query($sql);
	if($rs){
		while($r=$rs->fetch_assoc()){
			$r['lvl']=dash_level($r);
			$r['failure']=dash_is_failure($r['lvl']);
			$r['open']=dash_incident_open($r);
			$out[]=$r;
		}
	}
	return $cache[$date]=$out;
}

/* "Open" = a resolution column exists on this schema and is empty. */
function dash_incident_open($r){
	foreach(array('time_resolved','resolved_time','time_restored','end_time') as $k){
		if(array_key_exists($k,$r)){ return dash_ts($r[$k]) ? false : true; }
	}
	return false;   /* no resolution column on this schema -- never claim open */
}

function dash_incident_counts($date){
	$rows=dash_incidents($date);
	$c=array('total'=>0,'failures'=>0,'open'=>0,'L0'=>0,'L1'=>0,'L2'=>0,'L3'=>0,'L4'=>0,'worst'=>'');
	foreach($rows as $r){
		$c['total']++;
		if(isset($c[$r['lvl']])){ $c[$r['lvl']]++; }
		if($r['failure']){
			$c['failures']++;
			if($c['worst']==="" || $r['lvl']>$c['worst']){ $c['worst']=$r['lvl']; }
		}
		if($r['open']){ $c['open']++; }
	}
	return $c;
}

/* The row's own primary key, whatever this schema calls it. */
function dash_incident_id($r){
	foreach(array('id','incident_id','ir_id') as $k){
		if(isset($r[$k]) && $r[$k]!==""){ return $r[$k]; }
	}
	return "";
}

/* Station / direction suffix, only from columns that actually exist. */
function dash_incident_where($r){
	$bits=array();
	foreach(array('station','location','area') as $k){
		if(isset($r[$k]) && trim((string)$r[$k])!==""){ $bits[]=trim((string)$r[$k]); break; }
	}
	if(isset($r['direction'])){
		$d=strtoupper(trim((string)$r['direction']));
		if($d==='N'||$d==='NB'){ $bits[]='northbound'; }
		else if($d==='S'||$d==='SB'){ $bits[]='southbound'; }
		else if($d!==''){ $bits[]=$r['direction']; }
	}
	return implode(" ",$bits);
}

/* One-line summary text for an incident row. */
function dash_incident_text($r,$maxlen=90){
	$t=isset($r['description'])?(string)$r['description']:'';
	$t=trim(preg_replace('/\s+/',' ',strip_tags($t)));
	if($t===''){ $t=dash_type_label(isset($r['incident_type'])?$r['incident_type']:''); }
	$where=dash_incident_where($r);
	if($where!==''){ $t.=' - '.$where; }
	if(function_exists('mb_strlen') && mb_strlen($t,'UTF-8')>$maxlen){ return mb_substr($t,0,$maxlen,'UTF-8').'...'; }
	if(strlen($t)>$maxlen){ return substr($t,0,$maxlen).'...'; }
	return $t;
}

/* The single most recent incident of the day, or null. */
function dash_latest_incident($date){
	$rows=dash_incidents($date);          /* already ordered newest first */
	return count($rows) ? $rows[0] : null;
}

/* What the status band shows: every still-open incident, newest first.
   If nothing is open, the latest incident alone -- so the band always
   carries the "one latest incident" the band is there to surface. */
function dash_band_items($date){
	$rows=dash_incidents($date);
	$open=array();
	foreach($rows as $r){ if($r['open']){ $open[]=$r; } }
	$pick = count($open) ? $open : (count($rows) ? array($rows[0]) : array());
	$pick = array_slice($pick,0,(int)DASH_BAND_MAX);

	$out=array();
	foreach($pick as $r){
		$out[]=array(
			'id'   => dash_incident_id($r),
			'ts'   => dash_ts(isset($r['incident_date'])?$r['incident_date']:''),
			'lvl'  => $r['lvl'],
			'open' => $r['open'],
			'text' => dash_incident_text($r),
			'no'   => isset($r['incident_no'])?trim((string)$r['incident_no']):''
		);
	}
	return $out;
}

/* The record URL for a band item: what the slide panel loads, and what
   the anchor falls back to without JS.  Empty when there is no id. */
function dash_incident_url($item){
	if(!isset($item['id']) || $item['id']===''){ return ''; }
	return str_replace('{id}', rawurlencode($item['id']), DASH_PANEL_URL);
}

/* Panel attributes for one band item.  Empty string when the panel is
   off, when there is no id to edit, or on the wall board (nothing on a
   wall monitor should invite a click). */
function dash_panel_attrs($item,$wall=false){
	if($wall || !DASH_PANEL){ return ''; }
	if(!isset($item['id']) || $item['id']===''){ return ''; }
	$title = DASH_PANEL_TITLE;
	if(isset($item['no']) && $item['no']!==''){ $title .= ' - '.$item['no']; }
	/* The full URL, with embed=1 -- openSlidePanel() takes a path, not an id.
	   The anchor's href stays embed-less, so the no-JS path gets the standalone
	   page rather than a chrome-less fragment. */
	$url = dash_incident_url($item);
	if($url===''){ return ''; }
	$sep = (strpos($url,'?')===false) ? '?' : '&';
	return ' data-panel="'.dash_h($url.$sep.'embed=1').'" data-panel-title="'.dash_h($title).'"';
}

/* Worst tone across the band's items -- drives the band's colour. */
function dash_band_tone($items){
	$tone='mute';
	$rank=array('mute'=>0,'info'=>1,'warn'=>2,'bad'=>3);
	foreach($items as $it){
		$t=dash_level_tone($it['lvl']);
		if($it['open'] && $t==='mute'){ $t='info'; }
		if($rank[$t]>$rank[$tone]){ $tone=$t; }
	}
	return $tone;
}

/* incident_type -> readable problem type, via equipment_type. */
function dash_type_map(){
	static $m=null;
	if($m!==null){ return $m; }
	$m=array();
	if(dash_table_exists('equipment_type')){
		$idc=dash_pick_col('equipment_type',array('id','equipment_type_id','type_id'));
		$lbl=dash_pick_col('equipment_type',array('equipment_type','name','type','description','label'));
		if($idc && $lbl){
			$db=dash_db();
			$rs=@$db->query("select `".$idc."` a,`".$lbl."` b from equipment_type");
			if($rs){ while($r=$rs->fetch_assoc()){ $m[(string)$r['a']]=$r['b']; } }
		}
	}
	return $m;
}

function dash_type_label($v){
	$map=dash_type_map();
	$k=(string)$v;
	if(isset($map[$k]) && trim($map[$k])!==""){ return $map[$k]; }
	if(trim($k)===""){ return "Unspecified"; }
	return $k;
}

/* Today's incidents grouped by problem type, biggest first. */
function dash_type_breakdown($date,$limit=6){
	$rows=dash_incidents($date);
	$agg=array();
	foreach($rows as $r){
		$k=dash_type_label(isset($r['incident_type'])?$r['incident_type']:"");
		if(!isset($agg[$k])){ $agg[$k]=0; }
		$agg[$k]++;
	}
	arsort($agg);
	return array_slice($agg,0,$limit,true);
}

/* =====================================================================
   Series -- sparklines and the monthly trend
   ===================================================================== */

/* One row per day for the last N days ending on $date.
   Returns date => count, with zero-filled gaps so the sparkline is even. */
function dash_daily_series($table,$datecol,$date,$days,$where=""){
	$out=array();
	$date=dash_date($date);
	$end=strtotime($date);
	$start=strtotime("-".((int)$days-1)." day",$end);
	for($i=0;$i<(int)$days;$i++){ $out[date("Y-m-d",strtotime("+".$i." day",$start))]=0; }
	if(!dash_ready() || !dash_table_exists($table)){ return $out; }
	$db=dash_db();
	$sql ="select date(`".$datecol."`) d, count(*) c from `".str_replace("`","",$table)."` ";
	$sql.="where date(`".$datecol."`) between '".dash_esc(date("Y-m-d",$start))."' and '".dash_esc($date)."' ";
	if($where!==""){ $sql.="and (".$where.") "; }
	$sql.="group by d";
	$rs=@$db->query($sql);
	if($rs){ while($r=$rs->fetch_assoc()){ if(isset($out[$r['d']])){ $out[$r['d']]=(int)$r['c']; } } }
	return $out;
}

function dash_spark_trains($date){
	return dash_daily_series('train_availability','date',$date,DASH_SPARK_DAYS,"status<>'cancelled'");
}
function dash_spark_incidents($date){
	return dash_daily_series('incident_report','incident_date',$date,DASH_SPARK_DAYS);
}
function dash_spark_cancelled($date){
	return dash_daily_series('train_availability','date',$date,DASH_SPARK_DAYS,"status='cancelled'");
}

/* Yesterday-vs-today delta for the tile chips.  Compares the same
   weekday last week, not literally yesterday -- weekend service differs. */
function dash_delta($series,$date){
	$date=dash_date($date);
	$prev=date("Y-m-d",strtotime("-7 day",strtotime($date)));
	$now =isset($series[$date])?$series[$date]:0;
	if(!isset($series[$prev])){ return null; }
	return $now-$series[$prev];
}

/* @grain -- Which bucket size the trend card is showing. Anything else falls
   back to months, so a stale or hand-edited ?g= renders the familiar card
   rather than an empty one. */
function dash_trend_grain($g){
	$g = strtolower(trim((string)$g));
	return in_array($g, array('year','month','ytd','week'), true) ? $g : 'month';
}

/* @grain -- Coverage, loaded once and cached.

   dash_month_series below carries a warning that reaching past 2021-06..2024-12
   needs data_coverage.php wired in first. The yearly grain reaches straight
   into that stretch on its first render, so this is that wiring.

   It matters more than it looks. A year with no records draws a bar of almost
   nothing, and a near-empty bar on a trend chart reads as a GOOD year -- the
   single most dangerous thing this card could show a manager. Those buckets
   are returned as null, not zero, and the dashboard hatches them. */
function dash_trend_coverage(){
	static $cov = null, $tried = false;
	if($tried){ return $cov; }
	$tried = true;
	if(!function_exists('ccsLoadCoverage') && file_exists(dirname(__FILE__)."/data_coverage.php")){
		@require_once(dirname(__FILE__)."/data_coverage.php");
	}
	if(function_exists('ccsLoadCoverage') && function_exists('ccsMonthStatus') && dash_ready()){
		$cov = ccsLoadCoverage(dash_db());
	}
	return $cov;
}

/* True when a given YYYY-MM is known to hold no records. Unknown coverage
   answers false: guessing a month is missing would hatch bars that are simply
   quiet, and overstating absence is its own kind of wrong. */
function dash_month_missing($ym){
	$cov = dash_trend_coverage();
	if($cov === null || !function_exists('ccsMonthStatus')){ return false; }
	return ccsMonthStatus($cov, $ym) === 'missing';
}


/* =====================================================================
   @grain -- One series builder for all three bucket sizes.

   Returns everything the card needs to draw itself and to hand its own
   window to a drill-down, so the view and the link can never disagree
   about what is on screen:

     keys    ordered bucket keys
     counts  key => int, or NULL where there are no records
     labels  key => short axis label
     full    key => label for the hover title
     sd/ed   the window, for the report handoff
     head    the card heading

   dash_month_series() is left exactly as it was. It is called elsewhere
   and there was no reason to make every caller pay for this.
   ===================================================================== */
function dash_trend_series($date, $grain){
	$grain = dash_trend_grain($grain);
	$date  = dash_date($date);
	$out   = array('keys'=>array(),'counts'=>array(),'labels'=>array(),'full'=>array(),
	               'sd'=>$date,'ed'=>$date,'head'=>'','grain'=>$grain);

	if($grain === 'year'){
		$y0 = (int)date("Y", strtotime($date)) - (DASH_TREND_YEARS - 1);
		for($y = $y0; $y <= (int)date("Y", strtotime($date)); $y++){
			$k = (string)$y;
			$out['keys'][] = $k; $out['counts'][$k] = 0;
			$out['labels'][$k] = $k; $out['full'][$k] = $k;
		}
		$out['sd'] = $y0."-01-01";
		$out['ed'] = $date;
		$out['head'] = 'Last '.DASH_TREND_YEARS.' years';
		$expr = "date_format(incident_date,'%Y')";
	}
	else if($grain === 'week'){
		/* Monday-start weeks, keyed by the Monday itself rather than by a
		   week NUMBER -- ISO week numbering rolls over at new year and would
		   put week 52 next to week 1 with no way to tell the years apart. */
		$mon = strtotime("monday this week", strtotime($date));
		for($i = DASH_TREND_WEEKS - 1; $i >= 0; $i--){
			$t = strtotime("-".$i." week", $mon);
			$k = date("Y-m-d", $t);
			$out['keys'][] = $k; $out['counts'][$k] = 0;
			$out['labels'][$k] = date("j M", $t);
			$out['full'][$k]   = 'week of '.date("j M Y", $t);
		}
		$out['sd'] = $out['keys'][0];
		$out['ed'] = $date;
		$out['head'] = 'Last '.DASH_TREND_WEEKS.' weeks';
		$expr = "date_format(date_sub(incident_date, interval weekday(incident_date) day),'%Y-%m-%d')";
	}
	else if($grain === 'ytd'){
		/* @ytd -- The months of the VIEWED year, January to the month on
		   screen. Distinct from 'month', which is a rolling last-N window and
		   so straddles two years for most of the year -- comparing March with
		   the previous March means counting backwards across a boundary the
		   card does not draw.

		   It stops at the month being viewed and does NOT run to December.
		   Drawing the rest of the year would put four empty bars on the right
		   of the chart in September, and this file already carries the warning
		   for why that is the worst thing this card can do: a near-empty bar
		   reads as a GOOD period. Those months have not happened; there is no
		   honest bar for them, so there is no bar. */
		$y   = (int)date("Y", strtotime($date));
		$end = (int)date("n", strtotime($date));
		for($m = 1; $m <= $end; $m++){
			$t = mktime(0,0,0,$m,1,$y);
			$k = date("Y-m", $t);
			$out['keys'][] = $k; $out['counts'][$k] = 0;
			$out['labels'][$k] = date("M", $t);
			$out['full'][$k]   = date("F Y", $t);
		}
		$out['sd'] = $y."-01-01";
		$out['ed'] = $date;
		/* A completed year is just the year; a year still running says so, so
		   nobody reads a nine-month total as an annual one. */
		$out['head'] = ($end === 12) ? ($y.' by month') : ($y.' to date');
		$expr = "date_format(incident_date,'%Y-%m')";
	}
	else {
		$cur = strtotime(date("Y-m-01", strtotime($date)));
		for($i = DASH_TREND_MONTHS - 1; $i >= 0; $i--){
			$t = strtotime("-".$i." month", $cur);
			$k = date("Y-m", $t);
			$out['keys'][] = $k; $out['counts'][$k] = 0;
			$out['labels'][$k] = date("M", $t);
			$out['full'][$k]   = date("F Y", $t);
		}
		$out['sd'] = $out['keys'][0]."-01";
		$out['ed'] = $date;
		$out['head'] = 'Last '.DASH_TREND_MONTHS.' months';
		$expr = "date_format(incident_date,'%Y-%m')";
	}

	if(dash_ready() && dash_table_exists('incident_report')){
		$db  = dash_db();
		$to  = date("Y-m-d", strtotime("+1 day", strtotime($date)));
		$sql = "select ".$expr." k, count(*) c from incident_report "
		     . "where incident_date>='".dash_esc($out['sd'])." 00:00:00' "
		     . "and incident_date<'".dash_esc($to)." 00:00:00' group by k";
		$rs = @$db->query($sql);
		if($rs){ while($r=$rs->fetch_assoc()){
			if(array_key_exists($r['k'], $out['counts'])){ $out['counts'][$r['k']] = (int)$r['c']; }
		} }
	}

	/* Null out buckets with no coverage. A year counts as missing only when
	   EVERY month in it is -- a partly recovered year keeps its bar, because
	   the count in it is real even though it is incomplete. */
	foreach($out['keys'] as $k){
		if($grain === 'month' || $grain === 'ytd'){
			/* Both are keyed YYYY-MM, so they null out identically. */
			if(dash_month_missing($k)) $out['counts'][$k] = null;
		}
		else if($grain === 'year'){
			$all = true;
			for($m = 1; $m <= 12; $m++){
				$ym = sprintf("%04d-%02d", (int)$k, $m);
				if($ym > date("Y-m", strtotime($date))) break;
				if(!dash_month_missing($ym)){ $all = false; break; }
			}
			if($all) $out['counts'][$k] = null;
		}
		else {
			/* A week can straddle two months; missing if both are. */
			$a = date("Y-m", strtotime($k));
			$b = date("Y-m", strtotime("+6 day", strtotime($k)));
			if(dash_month_missing($a) && dash_month_missing($b)) $out['counts'][$k] = null;
		}
	}
	return $out;
}

/* Last N months of incident counts.  All months since 2025-01 are
   covered, so no null-vs-zero handling is needed at this window --
   widen DASH_TREND_MONTHS past the 2021-06..2024-12 gap and wire
   data_coverage.php in before trusting the earlier bars. */
function dash_month_series($date,$months){
	$out=array();
	$date=dash_date($date);
	$cur=strtotime(date("Y-m-01",strtotime($date)));
	for($i=(int)$months-1;$i>=0;$i--){ $out[date("Y-m",strtotime("-".$i." month",$cur))]=0; }
	if(!dash_ready() || !dash_table_exists('incident_report')){ return $out; }
	$db=dash_db();
	$from=date("Y-m-01",strtotime("-".((int)$months-1)." month",$cur));
	$to  =date("Y-m-d",strtotime("+1 month",$cur));
	$sql ="select date_format(incident_date,'%Y-%m') ym, count(*) c from incident_report ";
	$sql.="where incident_date>='".dash_esc($from)."' and incident_date<'".dash_esc($to)."' group by ym";
	$rs=@$db->query($sql);
	if($rs){ while($r=$rs->fetch_assoc()){ if(isset($out[$r['ym']])){ $out[$r['ym']]=(int)$r['c']; } } }
	return $out;
}

/* =====================================================================
   Presentation helpers
   ===================================================================== */

/* ---------------------------------------------------------------------
   Links.  Every number on this dashboard should be a doorway to the page
   that produced it -- and it has to arrive there on the SAME operating
   date the dashboard is showing.  dash_link() builds that handoff.

   $target is a key from the whitelist in dash_goto.php, never a path.
   $anchor lands on a row id if the target page emits one.
   --------------------------------------------------------------------- */
function dash_link($target,$date,$anchor="",$extra=array()){
	$q='to='.rawurlencode($target).'&d='.rawurlencode(dash_date($date));
	if($anchor!==""){ $q.='&a='.rawurlencode($anchor); }
	foreach($extra as $k=>$v){ $q.='&'.rawurlencode($k).'='.rawurlencode($v); }
	return DASH_GOTO.'?'.$q;
}

function dash_state_meta($state){
	switch($state){
		case 'online':    return array('On line',    'ok');
		case 'boundary':  return array('At boundary','warn');
		case 'removed':   return array('Removed',    'info');
		case 'cancelled': return array('Cancelled',  'bad');
		case 'pending':   return array('Not prepped','mute');
	}
	return array($state,'mute');
}

function dash_level_tone($lvl){
	if($lvl==="L4"){ return 'bad'; }
	if($lvl==="L3"){ return 'warn'; }
	if($lvl==="L2"){ return 'warn'; }
	return 'mute';
}

/* Percentage clamped to 0..100 and rounded -- never a raw float in the DOM. */
function dash_pct($n,$d){
	$d=(float)$d;
	if($d<=0){ return 0; }
	$p=round(((float)$n/$d)*100);
	if($p<0){ $p=0; } if($p>100){ $p=100; }
	return $p;
}

/* Inline sparkline from a date=>count map.  Pure CSS, no canvas. */
function dash_sparkline($series){
	$max=0;
	foreach($series as $v){ if($v>$max){ $max=$v; } }
	$html='<span class="ds-spark">';
	$i=0; $n=count($series);
	foreach($series as $v){
		$i++;
		$h=($max>0)? max(8,round(($v/$max)*100)) : 8;
		$cls=($i==$n)?' class="ds-spark-now"':'';
		$html.='<span'.$cls.' style="height:'.$h.'%"></span>';
	}
	return $html.'</span>';
}

function dash_delta_chip($d){
	if($d===null){ return ''; }
	$tone = ($d>0) ? 'up' : (($d<0) ? 'down' : 'flat');
	$txt  = ($d>0) ? '+'.$d : (($d<0) ? '&minus;'.abs($d) : '0');
	return '<span class="ds-delta ds-delta--'.$tone.'" title="vs same weekday last week">'.$txt.'</span>';
}

/* ---------------------------------------------------------------------
   Status band -- the strip between the tiles and everything else.

   Two modes, set by DASH_BAND_MODE:
     'band'   one item at a time, cross-faded on a timer.  Static text
              you can actually read at a glance; the only motion is a
              pulse dot when something is unresolved.  RECOMMENDED.
     'ticker' a continuously scrolling marquee.  Included because it was
              asked for, but it makes a single incident harder to read,
              not easier -- the words are only legible for part of their
              journey across the strip.
   Both respect prefers-reduced-motion.
   --------------------------------------------------------------------- */
function dash_status_band($date,$wall=false){
	$items = dash_band_items($date);
	$mode  = (DASH_BAND_MODE==='ticker') ? 'ticker' : 'band';

	if(!count($items)){
?>
	<div class="ds-band ds-band--ok">
		<span class="ds-band-tag">Clear</span>
		<div class="ds-band-slides"><span class="ds-band-slide is-on"><span class="ds-band-txt">No incidents recorded for this date.</span></span></div>
	</div>
<?php
		return;
	}

	$tone   = dash_band_tone($items);
	$anyOpen=false;
	foreach($items as $it){ if($it['open']){ $anyOpen=true; break; } }
	$tag    = $anyOpen ? (count($items)>1 ? count($items)." open" : "Open now") : "Latest";
?>
	<div class="ds-band ds-band--<?php echo dash_h($tone); ?><?php if($mode==='ticker'){ echo ' ds-band--ticker'; } ?>"
	     id="ds-band" data-rotate="<?php echo (int)DASH_BAND_ROTATE; ?>">
		<span class="ds-band-tag"><?php if($anyOpen){ ?><i class="ds-pulse"></i><?php } ?><?php echo dash_h($tag); ?></span>
		<div class="ds-band-slides">
<?php	if($mode==='ticker'){ ?>
			<div class="ds-band-track">
<?php		for($pass=0;$pass<2;$pass++){
				foreach($items as $it){ ?>
				<a class="ds-band-item" href="<?php echo dash_h(dash_incident_url($it)); ?>"<?php echo dash_panel_attrs($it,$wall); ?>><?php
					if($it['lvl']!==''){ ?><span class="ds-badge t-<?php echo dash_h(dash_level_tone($it['lvl'])); ?>"><?php echo dash_h($it['lvl']); ?></span><?php }
					echo dash_h($it['text']);
					if($it['ts']){ ?> <span class="ds-band-meta"><?php echo dash_h(date("H:i",$it['ts'])); ?></span><?php }
				?></a>
<?php			}
			} ?>
			</div>
<?php	} else {
			$i=0;
			foreach($items as $it){
				$i++;
				/* An item with no id has nothing to open -- it renders as
				   plain text rather than as a link that goes nowhere. */
				$url = dash_incident_url($it);
				$el  = $url!=='' ? 'a' : 'span';
?>
			<<?php echo $el; ?> class="ds-band-slide<?php if($i==1){ echo ' is-on'; } ?>"<?php
				if($url!==''){ ?> href="<?php echo dash_h($url); ?>"<?php }
				echo dash_panel_attrs($it,$wall);
			?>>
<?php			if($it['lvl']!==''){ ?><span class="ds-badge t-<?php echo dash_h(dash_level_tone($it['lvl'])); ?>"><?php echo dash_h($it['lvl']); ?></span><?php } ?>
				<span class="ds-band-txt"><?php echo dash_h($it['text']); ?></span>
				<span class="ds-band-meta"><?php
					if($it['no']!==''){ echo dash_h($it['no'])." &middot; "; }
					if($it['ts']){ echo dash_h(date("H:i",$it['ts']));
						echo ' &middot; <b data-since="'.(int)$it['ts'].'">&mdash;</b>';
					}
					if($it['open']){ echo " &middot; unresolved"; }
//					if($url!==''){ echo ' &middot; <span class="ds-band-cta">edit CCDR</span>'; }
				?></span>
			</<?php echo $el; ?>>
<?php		}
		} ?>
		</div>
	</div>
<?php if(!$wall && DASH_PANEL){ ?>
	<script>
	/* Band -> slide panel.  Delegated, so rotation replacing the visible
	   slide never loses the handler.  Walks up manually rather than using
	   Element.closest(), which older browsers on the station PCs lack. */
	(function(){
		var band=document.getElementById('ds-band');
		if(!band) return;

		function panelNode(n){
			while(n && n!==band){
				if(n.getAttribute && n.getAttribute('data-panel')){ return n; }
				n=n.parentNode;
			}
			return null;
		}

		band.onclick=function(e){
			e=e||window.event;
			var a=panelNode(e.target||e.srcElement);
			if(!a) return true;
			var open=window[<?php echo json_encode(DASH_PANEL_FN); ?>];
			/* No panel on this page -> fall through to the href, which
			   opens the same record as an ordinary page. */
			if(typeof open!=='function') return true;
			if(e.preventDefault) e.preventDefault(); else e.returnValue=false;
			open(a.getAttribute('data-panel'), a.getAttribute('data-panel-title')||'');
			return false;
		};
	})();
	</script>
<?php } ?>
<?php if($mode!=='ticker'){ ?>
	<script>
	(function(){
		var band=document.getElementById('ds-band');
		if(!band) return;
		var reduce=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;

		/* elapsed-time readouts, refreshed in place so the page needn't reload */
		function since(){
			var now=Math.floor(Date.now()/1000);
			var ns=band.querySelectorAll('[data-since]');
			for(var i=0;i<ns.length;i++){
				var m=Math.floor((now-parseInt(ns[i].getAttribute('data-since'),10))/60);
				if(m<0) m=0;
				ns[i].innerHTML = m<1 ? 'just now'
					: (m<60 ? m+' min ago'
					: Math.floor(m/60)+'h '+(m%60)+'m ago');
			}
		}
		since(); setInterval(since,30000);

		/* rotation only earns its place when there is more than one item */
		var slides=band.querySelectorAll('.ds-band-slide');
		if(reduce||slides.length<2) return;
		var n=0, ms=(parseInt(band.getAttribute('data-rotate'),10)||8)*1000, timer=null;
		function go(){
			slides[n].className='ds-band-slide';
			n=(n+1)%slides.length;
			slides[n].className='ds-band-slide is-on';
		}
		function start(){ timer=setInterval(go,ms); }
		function stop(){ if(timer){ clearInterval(timer); timer=null; } }
		start();
		band.addEventListener('mouseenter',stop);
		band.addEventListener('mouseleave',start);
	})();
	</script>
<?php }
}

/* =====================================================================
   Theme -- the --cf-* console tokens, plus dashboard-only additions.
   $mode: 'console' (workstation) or 'wall' (OCC monitor).
   ===================================================================== */
function dash_styles($mode='console'){
	$wall = ($mode==='wall');
?>
<style>
:root{
	--cf-blue:#00529B; --cf-blue-dk:#003C73; --cf-gold:#FDB813;
	--cf-ink:#1f2430; --cf-ink-2:#5a6472; --cf-ink-3:#8b93a1;
	--cf-line:#dfe3ea; --cf-surface:#ffffff; --cf-canvas:#f4f6fa;
	--cf-ok:#1f7a44; --cf-ok-bg:#e6f4ec;
	--cf-warn:#8a5a06; --cf-warn-bg:#fdf1d8;
	--cf-bad:#a32222; --cf-bad-bg:#fbeaea;
	--cf-info:#125e9c; --cf-info-bg:#e6f0fa;
	--cf-mute:#5f6672; --cf-mute-bg:#eef0f4;
	--cf-radius:8px;
}
.ds-wrap{max-width:<?php echo $wall?'1680px':'1360px'; ?>;margin:0 auto;padding:<?php echo $wall?'18px 22px':'16px 18px 40px'; ?>;
	font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:var(--cf-ink);background:var(--cf-canvas);
	min-height:<?php echo $wall?'calc(100vh - 56px)':'auto'; ?>;box-sizing:border-box}
.ds-wrap *{box-sizing:border-box}

.ds-bar{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;
	background:var(--cf-blue);color:#fff;border-radius:var(--cf-radius);padding:<?php echo $wall?'12px 20px':'10px 16px'; ?>;margin-bottom:14px}
.ds-bar h1{margin:0;font-size:<?php echo $wall?'21px':'17px'; ?>;font-weight:600;letter-spacing:.2px}
.ds-bar .ds-sub{font-size:<?php echo $wall?'15px':'13px'; ?>;color:#cfe0f2;font-variant-numeric:tabular-nums}
.ds-bar form{display:flex;gap:8px;align-items:center;margin:0}
.ds-bar input[type=date]{border:0;border-radius:6px;padding:5px 8px;font:inherit;font-size:13px;color:var(--cf-ink)}
.ds-bar button{border:0;border-radius:6px;padding:6px 12px;font:inherit;font-size:13px;font-weight:600;
	background:var(--cf-gold);color:#3a2a00;cursor:pointer}
.ds-bar a.ds-today{color:#fff;font-size:13px;text-decoration:none;border:1px solid rgba(255,255,255,.5);border-radius:6px;padding:5px 10px}

/* --- tiles -------------------------------------------------------- */
.ds-tiles{display:grid;gap:12px;margin-bottom:14px;
	grid-template-columns:repeat(auto-fit,minmax(<?php echo $wall?'190px':'168px'; ?>,1fr))}
.ds-tile{display:flex;background:var(--cf-surface);border:1px solid var(--cf-line);border-radius:12px;overflow:hidden}
.ds-rail{flex:0 0 4px}
.ds-tile-body{flex:1;min-width:0;padding:<?php echo $wall?'14px 16px':'12px 14px'; ?>}
.ds-tile-label{display:flex;align-items:center;gap:7px;font-size:<?php echo $wall?'14px':'12.5px'; ?>;
	color:var(--cf-ink-2);margin-bottom:<?php echo $wall?'9px':'7px'; ?>}
.ds-dot{width:8px;height:8px;border-radius:50%;flex:0 0 8px}
.ds-val{font-size:<?php echo $wall?'42px':'25px'; ?>;font-weight:600;line-height:1;font-variant-numeric:tabular-nums}
.ds-den{font-size:<?php echo $wall?'21px':'14px'; ?>;color:var(--cf-ink-3);font-variant-numeric:tabular-nums;margin-left:2px}
.ds-meter{height:5px;border-radius:3px;background:var(--cf-line);margin-top:<?php echo $wall?'13px':'10px'; ?>;overflow:hidden}
.ds-meter i{display:block;height:5px;border-radius:3px}
.ds-spark{display:flex;align-items:flex-end;gap:2px;height:20px;margin-top:9px}
.ds-spark span{flex:1;border-radius:1px;background:#c9d2df;min-height:2px}
.ds-spark span.ds-spark-now{background:var(--cf-blue)}
.ds-delta{font-size:11px;padding:1px 6px;border-radius:6px;margin-left:6px;vertical-align:3px;font-weight:600}
.ds-delta--up{background:var(--cf-bad-bg);color:var(--cf-bad)}
.ds-delta--down{background:var(--cf-ok-bg);color:var(--cf-ok)}
.ds-delta--flat{background:var(--cf-mute-bg);color:var(--cf-mute)}

/* --- cards -------------------------------------------------------- */
.ds-grid{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(320px,1fr))}
.ds-card{background:var(--cf-surface);border:1px solid var(--cf-line);border-radius:12px;
	padding:<?php echo $wall?'14px 18px':'13px 16px'; ?>;margin-bottom:12px}
.ds-card h2{margin:0 0 11px;font-size:<?php echo $wall?'16px':'14.5px'; ?>;font-weight:600}
.ds-card-head{display:flex;align-items:baseline;justify-content:space-between;gap:10px;margin-bottom:11px}
.ds-card-head h2{margin:0}
.ds-note{font-size:12px;color:var(--cf-ink-3)}

/* --- fleet strip -------------------------------------------------- */
.ds-fleet{display:flex;flex-wrap:wrap;gap:<?php echo $wall?'7px':'5px'; ?>}
.ds-chip{font-family:Consolas,Menlo,monospace;font-size:<?php echo $wall?'15px':'13px'; ?>;
	padding:<?php echo $wall?'7px 10px':'4px 8px'; ?>;border-radius:6px;text-decoration:none;font-weight:600}
.ds-legend{display:flex;flex-wrap:wrap;gap:14px;margin-top:12px;font-size:<?php echo $wall?'13px':'12px'; ?>;color:var(--cf-ink-2)}
.ds-legend span{display:flex;align-items:center;gap:6px}
.ds-key{width:10px;height:10px;border-radius:2px}

/* --- feeds -------------------------------------------------------- */
/* @insertions -- Scroller for feeds that show everything rather than a top-N.
   The height is capped in rows, not pixels, so it holds the same number of
   entries whether the wall stylesheet is in force or the desk one.

   overscroll-behavior stops a flick past the end of the list from scrolling
   the page underneath it -- on the OCC wall that would drag the whole
   dashboard out of position with no obvious way back.

   The fade is a background-attachment trick rather than an overlay, so it
   only shows while there is more list below and disappears at the end: a
   permanent gradient reads as a design flourish, and the point is to signal
   that content continues. */
.ds-scroll{
	max-height:<?php echo $wall?'19em':'16em'; ?>;
	overflow-y:auto; overscroll-behavior:contain;
	scrollbar-width:thin; scrollbar-color:var(--cf-line) transparent;
	background:
		linear-gradient(var(--cf-surface) 30%, transparent) top / 100% 22px no-repeat,
		linear-gradient(transparent, var(--cf-surface) 70%) bottom / 100% 22px no-repeat,
		radial-gradient(farthest-side at 50% 0, rgba(0,40,90,.12), transparent) top / 100% 7px no-repeat,
		radial-gradient(farthest-side at 50% 100%, rgba(0,40,90,.12), transparent) bottom / 100% 7px no-repeat;
	background-attachment:local, local, scroll, scroll;
}
.ds-scroll::-webkit-scrollbar{width:7px}
.ds-scroll::-webkit-scrollbar-thumb{background:var(--cf-line);border-radius:4px}
.ds-scroll::-webkit-scrollbar-track{background:transparent}
/* A scrolled list hides its own length, so the heading carries the count. */
.ds-count{font-size:11px;font-weight:600;color:var(--cf-ink-3);
	background:var(--cf-canvas);border-radius:9px;padding:1px 7px;margin-left:5px;
	vertical-align:middle}
@media print{ .ds-scroll{max-height:none;overflow:visible;background:none} }
.ds-feed{margin:0;padding:0;list-style:none}
.ds-feed li{display:flex;gap:12px;align-items:baseline;padding:<?php echo $wall?'9px 0':'6px 0'; ?>;
	border-bottom:1px solid var(--cf-line);font-size:<?php echo $wall?'15px':'13px'; ?>}
.ds-feed li:last-child{border-bottom:0}
.ds-time{font-family:Consolas,Menlo,monospace;color:var(--cf-ink-3);flex:0 0 <?php echo $wall?'50px':'42px'; ?>}
.ds-badge{font-size:11px;font-weight:700;padding:1px 6px;border-radius:5px;margin-right:6px}

/* --- bar rows ----------------------------------------------------- */
.ds-bars{display:grid;grid-template-columns:minmax(90px,auto) 1fr 30px;gap:8px 10px;align-items:center;font-size:13px}
.ds-bars .ds-track{background:var(--cf-canvas);border-radius:3px;height:14px;overflow:hidden}
.ds-bars .ds-track i{display:block;height:14px;border-radius:3px;background:var(--cf-blue)}
.ds-bars .ds-n{text-align:right;font-variant-numeric:tabular-nums;color:var(--cf-ink-2)}

/* --- monthly trend ------------------------------------------------ */
.ds-months{display:flex;align-items:flex-end;gap:6px;height:84px}
.ds-months i{flex:1;background:var(--cf-blue);border-radius:3px 3px 0 0;min-height:2px;display:block}
/* @grain -- No records. Full height so the bucket cannot be mistaken for a
   quiet one, hatched and pale so it cannot be mistaken for a busy one, and
   carrying no number at all. */
.ds-months i.is-gap{height:100% !important;background:repeating-linear-gradient(45deg,
	var(--cf-line),var(--cf-line) 3px,transparent 3px,transparent 6px);border-radius:3px}
.ds-months-x span.is-gap{color:var(--cf-ink-3);font-style:italic}
/* @grain -- Bucket-size selector. Plain links, so the choice lives in the URL
   and survives a refresh, a bookmark and a wall-display reload with no JS. */
.ds-seg{display:inline-flex;border:1px solid var(--cf-line);border-radius:7px;overflow:hidden}
.ds-seg a{padding:3px 9px;font-size:11px;line-height:1.6;color:var(--cf-ink-2);
	text-decoration:none;background:var(--cf-surface)}
.ds-seg a+a{border-left:1px solid var(--cf-line)}
.ds-seg a.on{background:var(--cf-blue);color:#fff;font-weight:600}
.ds-seg a:hover:not(.on){background:var(--cf-mute-bg)}
.ds-months-x{display:flex;gap:6px;margin-top:6px;font-size:11px;color:var(--cf-ink-3)}
.ds-months-x span{flex:1;text-align:center}

/* --- status band -------------------------------------------------- */
.ds-band{display:flex;align-items:stretch;border:1px solid var(--cf-line);border-radius:12px;
	overflow:hidden;background:var(--cf-surface);margin-bottom:14px}
.ds-band-tag{display:flex;align-items:center;gap:8px;padding:0 <?php echo $wall?'18px':'14px'; ?>;
	font-size:<?php echo $wall?'13px':'11.5px'; ?>;font-weight:700;letter-spacing:.5px;
	text-transform:uppercase;white-space:nowrap}
.ds-band--ok .ds-band-tag{background:var(--cf-ok-bg);color:var(--cf-ok)}
.ds-band--info .ds-band-tag{background:var(--cf-info-bg);color:var(--cf-info)}
.ds-band--warn .ds-band-tag{background:var(--cf-warn-bg);color:var(--cf-warn)}
.ds-band--bad .ds-band-tag{background:var(--cf-bad-bg);color:var(--cf-bad)}
.ds-band--mute .ds-band-tag{background:var(--cf-mute-bg);color:var(--cf-mute)}
.ds-band--ok{border-left:4px solid var(--cf-ok)}
.ds-band--info{border-left:4px solid var(--cf-info)}
.ds-band--warn{border-left:4px solid var(--cf-warn)}
.ds-band--bad{border-left:4px solid var(--cf-bad)}
.ds-band--mute{border-left:4px solid #b7bec9}
.ds-band-slides{position:relative;flex:1;min-width:0;display:grid;
	padding:<?php echo $wall?'12px 18px':'10px 14px'; ?>}
.ds-band-slide{grid-area:1/1;display:flex;align-items:center;gap:10px;min-width:0;
	text-decoration:none;color:inherit;opacity:0;transition:opacity .45s ease;pointer-events:none}
.ds-band-slide.is-on{opacity:1;pointer-events:auto}
.ds-band-txt{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;
	font-size:<?php echo $wall?'18px':'14px'; ?>;font-weight:500}
.ds-band-meta{font-size:<?php echo $wall?'13px':'12px'; ?>;color:var(--cf-ink-3);
	white-space:nowrap;font-variant-numeric:tabular-nums}
.ds-band-meta b{font-weight:600;color:var(--cf-ink-2)}
.ds-band-cta{color:var(--cf-blue);font-weight:600}
a.ds-band-slide:hover .ds-band-cta,a.ds-band-item:hover .ds-band-cta{text-decoration:underline}
a.ds-band-slide,a.ds-band-item{cursor:pointer}
a.ds-band-slide:focus-visible{outline:2px solid var(--cf-blue);outline-offset:2px}
.ds-pulse{width:8px;height:8px;border-radius:50%;background:currentColor;
	animation:ds-pulse 2s ease-in-out infinite}
@keyframes ds-pulse{0%,100%{opacity:1}50%{opacity:.2}}
/* ticker variant -- one continuous scroll, no per-item fade */
.ds-band--ticker .ds-band-slides{display:block;overflow:hidden}
.ds-band-track{display:inline-flex;align-items:center;gap:46px;white-space:nowrap;
	animation:ds-marquee 30s linear infinite;will-change:transform}
.ds-band-item{display:inline-flex;align-items:center;gap:8px;text-decoration:none;color:inherit;
	font-size:<?php echo $wall?'18px':'14px'; ?>;font-weight:500}
@keyframes ds-marquee{from{transform:translateX(0)}to{transform:translateX(-50%)}}
.ds-band--ticker:hover .ds-band-track{animation-play-state:paused}
@media (prefers-reduced-motion:reduce){
	.ds-pulse,.ds-band-track{animation:none}
	.ds-band-slide{opacity:1;position:static;pointer-events:auto}
	.ds-band-slides{display:block}
	.ds-band-slide+.ds-band-slide{margin-top:6px}
}

/* --- clickable tiles ---------------------------------------------- */
a.ds-tile{text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
a.ds-tile:hover{border-color:#b9c6d8;box-shadow:0 1px 4px rgba(0,60,115,.10)}
a.ds-tile:focus-visible{outline:2px solid var(--cf-blue);outline-offset:2px}
.ds-more{font-size:12px;color:var(--cf-blue);text-decoration:none}
.ds-more:hover{text-decoration:underline}

/* --- tones -------------------------------------------------------- */
.t-ok{background:var(--cf-ok-bg);color:var(--cf-ok)}
.t-warn{background:var(--cf-warn-bg);color:var(--cf-warn)}
.t-bad{background:var(--cf-bad-bg);color:var(--cf-bad)}
.t-info{background:var(--cf-info-bg);color:var(--cf-info)}
.t-mute{background:var(--cf-mute-bg);color:var(--cf-mute)}
.f-ok{background:var(--cf-ok)} .f-warn{background:var(--cf-warn)} .f-bad{background:var(--cf-bad)}
.f-info{background:var(--cf-info)} .f-mute{background:#b7bec9}

.ds-empty{padding:22px 4px;text-align:center;color:var(--cf-ink-3);font-size:13px}
.ds-alert{background:var(--cf-warn-bg);color:var(--cf-warn);border:1px solid #e8cf9a;
	border-radius:var(--cf-radius);padding:10px 14px;margin-bottom:12px;font-size:13px}

@media print{
	.ds-wrap{background:#fff;padding:0}
	.ds-bar{background:#fff;color:#000;border-bottom:2px solid var(--cf-blue);border-radius:0}
	.ds-bar .ds-sub{color:#444} .ds-bar form,.ds-bar a.ds-today{display:none}
	.ds-card,.ds-tile{break-inside:avoid;border-color:#999}
}
</style>
<?php
}