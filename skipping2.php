<?php 
require("Tmenu.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");
?>
<?php
/* =========================================================================
   train_operations.php  (reconciliation of train_availability.php + operations.php)

   ENGINE  : train_availability.php, verbatim.
             Same tables (train_availability, train_ava_time, train_switch,
             train_compo, train_incident_view, level, timetable_*), same POST
             field names, same processing.php AJAX endpoints, same helpers.
             All eight POST handlers below are copied unchanged.
   LAYOUT  : operations.php's structure -- two-row page header with date
             prev/today/next, timetable info strip, filter pills, refined
             table with one car per sub-row, and a right slide-panel that
             replaces the Bootstrap modal. Skinned in the Line 3 console
             palette (#00529B / #FDB813) already used across the ISS.
   DELIBERATE DIFFERENCES FROM train_availability.php (documented inline):
     - The 7 "Switch" columns become a switch trail (chips) inside the
       Index cell. Same data, same add/delete AJAX (processing.php), no cap
       of 7 lost columns of empty space.
     - L4 column KEPT (operations.php had dropped it).
     - Sub-row count follows the compo (3 cars = 3 rows) instead of a fixed 4.
   SKIPPING: ported from train_availability_skip.php (see the @skip comments).
             Same `skipping` table, same processing.php?skipLocation endpoint,
             same three POST handlers (insert / update / loading). The legacy
             page spent two table columns and a variable-height band of rows
             on this; here each train's skips are a stack of blocks in one
             Skip column, the way switches became chips. The forms live in the
             slide panel like every other form on this page.
   NOT PORTED FROM operations.php (no columns for them in the legacy schema):
     stabling / ready-for-insertion / planned-actual departure / loop,
     secondary driver, the Train Management board.
   INCIDENT FLOW: now a slide panel too — an iframe onto
     "incident report.php?cancel=|add_incident=...&embed=1" (embed discards
     Tmenu chrome, form still posts to itself; on save it postMessages
     'ir-saved' and this page reloads). Standalone/popup use is unchanged.
   Rename-safe: form action / reloads use $selfPage = basename(__FILE__).
   COMPARE (see the @compare comments):
     - The page is two sections, A and B, each with its own date picker and
       prev/today/next. A keeps the old field/session key (search_date); B
       posts search_date_b and is held under its own session key
       (skip_compare_date), following A (the day before) until picked.
     - Skips are no longer grouped by train. Each skip is its own row,
       ordered by departure time, latest first (ccsLoadSkipsForDate). The
       train's index, pill, switch trail and compo repeat on each of its rows.
     - Each section bar shows skips / trains / with-incident counts; B's carry
       the difference from A.
     - A and B sit side by side, each in a half-width pane with a compacted
       table; they stack only on phone-width screens.
     - Trimmed for width: no Car (compo) column, no status pill, no switch
       trail. Index is the number alone.
     - View only (@viewonly): the tables carry no Edit / + Skip / + Loading
       controls. The forms and POST handlers are still in the file, unused.
     - A line graph (@chart) between the peak tiles and the tables, across
       both halves: skips per hour of departure, A against B.
   ========================================================================= */
require_once("db_connect.php"); /* shared $db + db_exec()/db_query() prepared-statement helpers (item #2) */

/* ── Helper functions (verbatim from original) ── */
function getTrainDriver($id,$dbase){
	$rs=db_query($dbase,"select firstName,lastName,position from train_driver where id=? limit 1",array($id));
	if($rs===false || $rs->num_rows==0) return $id; /* item #2 micro-change: old code emitted PHP warnings + a mangled '. ' name for unknown ids */
	$row=$rs->fetch_assoc();
	return $row['position']." ".substr($row['firstName'],0,1).". ".$row['lastName'];
}
function getPHTrainDriver($id,$dbase){
	$rs=db_query($dbase,"select firstName,lastName from ph_trams where id=? limit 1",array($id));
	if($rs!==false && $rs->num_rows>0){
		$row=$rs->fetch_assoc();
		return substr($row['firstName'],0,1).". ".$row['lastName'];
	}
	return $id;
}
function getLevel($id,$dbase){
	/* === item #16 PORTED 2026-07 from train_availability.php (the one fix this
	   page's fork predated -- items #2, #1, and #3 were already carried/re-derived
	   in the redesign) ===
	   `level`.`order` is a MyISAM per-(date,level) AUTO_INCREMENT -- it numbers by
	   INSERTION ORDER, so late entries, corrections (edit_ccdr deletes+reinserts
	   the level row), and deletions desync it permanently. The ordinal is computed
	   live instead: this incident's chronological position (by incident_report.
	   incident_date, ties by id) among ALL same-day, same-level incidents -- the
	   same population the stored counter numbered. The `order` column keeps being
	   written by the engine as before; it's just no longer read here. Covers both
	   call sites on this page (active and cancelled branches). === END PORT === */
	$rs=db_query($dbase,"select l.date,l.level,ir.incident_date,ir.id as ir_id
		from level l join incident_report ir on ir.id=l.incident_id
		where l.incident_id=? limit 1",array($id));
	if($rs===false || $rs->num_rows==0) return "";
	$l0=$rs->fetch_assoc();
	$rs=db_query($dbase,"select count(*)+1 as rnk
		from level l join incident_report ir on ir.id=l.incident_id
		where l.date=? and l.level=?
		and (ir.incident_date<? or (ir.incident_date=? and ir.id<?))",
		array($l0['date'],$l0['level'],$l0['incident_date'],$l0['incident_date'],$l0['ir_id']));
	$row=$rs->fetch_assoc();
	return $row['rnk'];
}
function insertCompo($train_id,$car,$dbase){
	if($car=="") return;
	db_exec($dbase,"insert into train_compo(tar_id,car_no) values (?,?)",array($train_id,$car));
}
function ordinal_numbers($NUM){
	if(strlen($NUM)>1 && substr($NUM,-2,1)==1) return "th";
	$num=substr($NUM,-1);
	if($num==0) return "th";
	if($num==1) return "st";
	if($num==2) return "nd";
	if($num==3) return "rd";
	return "th";
}
function getOrdinal($number){
	$ends=array('th','st','nd','rd','th','th','th','th','th','th');
	if(($number%100)>=11 && ($number%100)<=13)
		return $number.'th';
	return $number.$ends[$number%10];
}

/* =========================================================================
   SKIPPING HELPERS — ported from train_availability_skip.php
   -------------------------------------------------------------------------
   A skip has three facts: the departure, the arrival, and the loading.
   Departure and arrival are encoded together, in one form.

   The loading is usually the arrival: the train arrives at a station and
   loads there, at that time. The loading_* columns exist for the case where
   it is NOT — the train arrives at one station and loads at another, or
   loads at a different time. They are filled in only then, which is why the
   legacy link reads "+ Loading (If Separate)" and why the legacy column was
   headed "Arrived/1st loading": the arrival IS the first loading by default.

   So EMPTY loading_* means "loaded on arrival", not "not encoded yet" and
   not "awaiting loading". There is no open/closed state on a skip and
   nothing here waits on the operator. An earlier comment in the legacy file
   read it the other way round; it was wrong, and so was the pending state
   this page first rendered from it.

   departure_location is an id into `departure_location` (the 13 stations in
   line order, then North Turnback 14, South Turnback 15, I & R area 16).
   arrived_location and loading_location are ids into `station` (1-12). That
   asymmetry is the legacy schema's, not a typo here — the departure picker
   is served by processing.php?skipLocation=Y, which reads the former.

   via_location is free text rather than a location id, because the pocket
   tracks are not rows in departure_location.
   ========================================================================= */

if(!function_exists('ccsSkipTableExists')){
function ccsSkipTableExists($dbase){
	static $exists=null;
	if($exists===null){
		$chk=@$dbase->query("show tables like 'skipping'");
		$exists=($chk && $chk->num_rows>0);
	}
	return $exists;
}
}

/* HH:MM, tolerating empty, null and zero dates */
if(!function_exists('ccsSkipTime')){
function ccsSkipTime($v){
	if($v===null || $v==="" || $v=="0000-00-00 00:00:00"){ return ""; }
	$t=strtotime($v);
	return ($t===false)?"":date("H:i",$t);
}
}

/* Null-safe name lookup. getTrainDriver() returns the raw id for an unknown
   row, which is right for a required driver column and wrong for the three
   nullable ones here, so skips get their own formatter. SUP -> STDO applied
   at the source, the way the legacy page applied it at every print site. */
if(!function_exists('ccsSkipPerson')){
function ccsSkipPerson($id,$dbase){
	$id=intval($id);
	if($id<=0){ return ""; }
	$rs=db_query($dbase,"select firstName,lastName,position from train_driver where id=? limit 1",array($id));
	if($rs===false || $rs->num_rows==0){ return ""; }
	$r=$rs->fetch_assoc();
	return str_replace("SUP","STDO",$r['position']." ".substr($r['firstName'],0,1).". ".$r['lastName']);
}
}

if(!function_exists('ccsPost')){
function ccsPost($k,$d=""){ return isset($_POST[$k]) ? $_POST[$k] : $d; }
}

/* @skip -- the month/day/year/hour/minute/amorpm -> "Y-m-d H:i" conversion the
   legacy page repeated inline eight times, once per handler. Two fixes folded in:

   1. The insert branch of the legacy skipping handler tested $_POST['hour']
      when it meant $_POST['hour2'], so a skip saved with a departure time but
      no arrival time yet built its arrival from a blank hour -- strtotime()
      returned false and date() stamped 1970-01-01 into arrived_time. Here the
      suffix decides which field is tested, so the two groups can't cross.
   2. A failed strtotime() returns "" rather than a 1970 date. */
if(!function_exists('ccsPostDateTime')){
function ccsPostDateTime($sfx=''){
	if(!isset($_POST['hour'.$sfx]) || $_POST['hour'.$sfx]===""){ return ""; }
	$hour   = $_POST['hour'.$sfx];
	$amorpm = ccsPost('amorpm'.$sfx);
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$t=strtotime(ccsPost('year'.$sfx)."-".ccsPost('month'.$sfx)."-".ccsPost('day'.$sfx)
		." ".$hour.":".ccsPost('minute'.$sfx));
	return ($t===false)?"":date("Y-m-d H:i",$t);
}
}

/* @compare -- the per-row shaping ccsLoadSkips() used to do inline, lifted
   out unchanged so the date-wide loader below shapes a skip exactly the same
   way. Nothing in the body differs from the loop it came from. */
if(!function_exists('ccsShapeSkip')){
function ccsShapeSkip($r,$dbase){
	$note="";
	if(isset($r['via_location']) && trim($r['via_location'])!=""){
		$note="via ".$r['via_location'];
		$va=ccsSkipTime(isset($r['via_arrival'])?$r['via_arrival']:"");
		$vd=ccsSkipTime(isset($r['via_departure'])?$r['via_departure']:"");
		if($va!="" || $vd!=""){ $note.=" (".$va." / ".$vd.")"; }
	}
	$depDrv  = ccsSkipPerson($r['departure_driver'],$dbase);
	$loadDrv = ccsSkipPerson($r['loading_driver'],$dbase);
	$loadTime= ccsSkipTime($r['loading_time']);
	/* @skip -- the legacy display read incident_id while the form wrote
	   incident_no, so a skip encoded through the form never showed its IN.
	   Prefer incident_id when the row has one, fall back to what the form
	   actually writes. */
	$incident="";
	if(isset($r['incident_id']) && $r['incident_id']!="" && $r['incident_id']!="0") $incident=$r['incident_id'];
	elseif(isset($r['incident_no']) && $r['incident_no']!="")                       $incident=$r['incident_no'];

	/* @skip -- "was the loading separate from the arrival?", which is the
	   only question the loading_* columns answer. A loading recorded at the
	   same station and the same minute as the arrival is the arrival, so it
	   collapses onto one line rather than being printed twice. */
	$loadWhere = trim($r['loading_name']." ".$r['loading_bound']);
	$arrWhere  = trim($r['arrived_name']);
	$arrTime   = ccsSkipTime($r['arrived_time']);
	$separateLoad = false;
	if($loadTime!="" || $loadWhere!=""){
		/* arrived_location carries no bound of its own, so the loading
		   station matches when its name is the same up to the bound. */
		$sameStation = ($arrWhere!="" && $loadWhere!="" && strpos($loadWhere,$arrWhere)===0);
		$sameTime    = ($arrTime!="" && $loadTime==$arrTime);
		$separateLoad= !($sameStation && $sameTime);
	}

	return array(
		'id'       =>$r['id'],
		'sep_load' =>$separateLoad,
		'from'     =>trim($r['departure_name']." ".$r['departure_bound']),
		'dep'      =>ccsSkipTime($r['departure_time']),
		'arr'      =>ccsSkipTime($r['arrived_time']),
		'arr_name' =>trim($r['arrived_name']),
		'to'       =>trim($r['loading_name']." ".$r['loading_bound']),
		'load'     =>$loadTime,
		'in'       =>$incident,
		'note'     =>$note,
		'depdrv'   =>$depDrv,
		/* only shown when it actually changed hands */
		'loaddrv'  =>($loadDrv!="" && $loadDrv!=$depDrv)?$loadDrv:"",
		'requested'=>$r['requested_by'],
		'reported' =>$r['reported_by'],
		/* raw ids/timestamps, handed to the edit form so it opens filled in
		   instead of blank -- see openSkipEdit() */
		'raw'      =>array(
			'id'        =>$r['id'],
			'dep'       =>$r['departure_time'],
			'arr'       =>$r['arrived_time'],
			'load'      =>$r['loading_time'],
			'dep_loc'   =>$r['departure_location'],
			'dep_bound' =>$r['departure_bound'],
			'arr_loc'   =>$r['arrived_location'],
			'load_loc'  =>$r['loading_location'],
			'load_bound'=>$r['loading_bound'],
			'drv'       =>$r['departure_driver'],
			'load_drv'  =>$r['loading_driver'],
			'req'       =>$r['requested_by'],
			'rep'       =>$r['reported_by'],
			'inc'       =>isset($r['incident_no'])?$r['incident_no']:""
		)
	);
}
}

/* Per-train loader, kept for any page that still includes these helpers.
   This page no longer calls it -- see ccsLoadSkipsForDate(). */
if(!function_exists('ccsLoadSkips')){
function ccsLoadSkips($dbase,$trainAvaId){
	$out=array();
	if(!ccsSkipTableExists($dbase)){ return $out; }
	/* @skip -- ordered ASCENDING, where the legacy page ordered DESC. In a
	   two-column band the newest-first order read fine; in a vertical stack
	   inside one cell it reads as a timeline, so it matches the switch trail
	   beside it (which is `order by date_change`). */
	$sql="select s.*, dl.name as departure_name, ll.station_name as loading_name, ss.station_name as arrived_name
		from skipping s
		left join departure_location dl on dl.id = s.departure_location
		left join station ll on ll.id = s.loading_location
		left join station ss on ss.id = s.arrived_location
		where s.tar_id=? order by s.departure_time, s.id";
	$q=db_query($dbase,$sql,array(intval($trainAvaId)));
	if($q===false){ return $out; }
	while($r=$q->fetch_assoc()){ $out[]=ccsShapeSkip($r,$dbase); }
	return $out;
}
}

/* @compare -- every skip on trains dated $dateCode, ONE ROW PER SKIP, newest
   departure first. Replaces the old train query, which inner-joined skipping
   and then `group by sk.tar_id` -- one row per train, with the train's skips
   stacked inside it, and the ORDER BY sk.departure_time applied to whichever
   skip row MySQL happened to keep for the group (not the latest one).

   Same population as before: the old page filtered trains by ta.date and only
   loaded skips for status='active' trains, so both conditions stay here.
   Ties (same minute) fall back to s.id desc, so the order is stable between
   reloads. */
if(!function_exists('ccsLoadSkipsForDate')){
function ccsLoadSkipsForDate($dbase,$dateCode){
	$out=array();
	if(!ccsSkipTableExists($dbase)){ return $out; }
	$sql="select s.*, dl.name as departure_name, ll.station_name as loading_name, ss.station_name as arrived_name
		from skipping s
		inner join train_availability ta on ta.id = s.tar_id
		left join departure_location dl on dl.id = s.departure_location
		left join station ll on ll.id = s.loading_location
		left join station ss on ss.id = s.arrived_location
		where ta.date between ? and ? and ta.status='active'
		order by s.departure_time desc, s.id desc";
	$q=db_query($dbase,$sql,array($dateCode." 00:00:00",$dateCode." 23:59:59"));
	if($q===false){ return $out; }
	while($r=$q->fetch_assoc()){
		$sp=ccsShapeSkip($r,$dbase);
		$sp['tar_id']=$r['tar_id'];
		$out[]=$sp;
	}
	return $out;
}
}

/* @compare -- everything about the TRAIN that a skip row shows: index, status
   pill, row tint, switch trail, compo. A train with four skips now prints four
   rows, so this is cached per train id to keep it to one set of queries per
   train, as before. Logic is the old per-train loop body, unchanged except
   where noted. */
if(!function_exists('ccsSkipTrainInfo')){
function ccsSkipTrainInfo($dbase,$taId){
	static $cache=array();
	$taId=intval($taId);
	if(isset($cache[$taId])) return $cache[$taId];

	$row=array('id'=>$taId,'index_no'=>'','status'=>'','type'=>'','date'=>'',
		'car_a'=>'','car_b'=>'','car_c'=>'','car_d'=>'',
		'boundary_time'=>'','insert_time'=>'','remove_time'=>'');
	$rs=db_query($dbase,"select ta.*, tat.boundary_time, tat.insert_time, tat.remove_time
		from train_availability ta
		left join train_ava_time tat on tat.train_ava_id = ta.id
		where ta.id=? limit 1",array($taId));
	if($rs!==false && $rs->num_rows>0){ $row=array_merge($row,$rs->fetch_assoc()); }

	/* ── Row status class + filter status (branches verbatim) ── */
	$inserted = $row['insert_time']!="";
	$boundary = $row['boundary_time']!="";
	$removed  = ($row['remove_time']!="" && $row['remove_time']!="0000-00-00 00:00:00");
	if($row['status']=="cancelled"){
		$rowClass="row--cancelled"; $dataStatus="cancelled";
	} elseif(!$removed && $inserted && $row['status']=="active"){
		$rowClass="row--service";   $dataStatus="service";
	} elseif(!$removed && !$inserted && $boundary && $row['status']=="active"){
		$rowClass="row--reserve";   $dataStatus="reserve";
	} else {
		$rowClass="row--removed";   $dataStatus="removed";
	}

	/* @compare -- the status pill, the switch trail and the compo (Car column)
	   were taken off this page to narrow the side-by-side tables, and their
	   queries with them (train_switch, train_compo): one query per train now.
	   The row tint and the coloured stripe on the Index cell above still
	   follow the lifecycle status; they cost no width. The pill / trail /
	   compo code is the same as train_availability.php's if it is wanted back. */

	$cache[$taId]=array(
		'id'=>$taId, 'index_no'=>$row['index_no'], 'status'=>$row['status'],
		'row_class'=>$rowClass, 'data_status'=>$dataStatus
	);
	return $cache[$taId];
}
}

/* @compare -- the table body for one date: one <tr> per skip, in the order
   ccsLoadSkipsForDate() returns them. The cell contents are the old per-skip
   blocks ($skipDep / $skipArr / $skipLoad / $skipRequested / $skipIncident)
   with one skip each, so they still use the fixed-height .skip-item--col
   blocks. $stats is filled for the section bar. */
if(!function_exists('ccsBuildSkipRows')){
function ccsBuildSkipRows($dbase,$dateCode,$SRemove,&$stats){
	$skips=ccsLoadSkipsForDate($dbase,$dateCode);
	$stats=array('skips'=>count($skips),'trains'=>0,'incidents'=>0,'am'=>0,'pm'=>0,
		'hours'=>array(),'untimed'=>0);
	/* @chart -- skips per hour of departure, for the A-vs-B line graph. Keyed
	   by hours since midnight of the section's date, so a departure logged
	   after midnight against the same operating day is hour 24, 25 ... rather
	   than wrapping round to the morning. A skip with no usable departure time
	   cannot be placed on the clock: it is counted in 'untimed' and the chart
	   says so, instead of quietly dropping it. */
	$dayStart=strtotime($dateCode." 00:00:00");
	/* @peak -- peak windows, judged on the full departure timestamp:
	     AM Peak  04:00 up to (not including) 09:00 on the section's date
	     PM Peak  15:00 on the section's date onward, to the end of revenue --
	              including any departure logged after midnight against the
	              same operating day (trains are filtered by ta.date, so a
	              00:20 departure on a late train still belongs here). */
	$amFrom=strtotime($dateCode." 04:00:00");
	$amTo  =strtotime($dateCode." 09:00:00");
	$pmFrom=strtotime($dateCode." 15:00:00");
	$seen=array();
	/* stands in for a skip with no arrival / no incident, so the blocks stay
	   level across the row */
	$skipBlank='<div class="skip-item skip-item--col"><span class="tc-none">&mdash;</span></div>';
	$html="";

	foreach($skips as $n=>$sp){
		$tid=$sp['tar_id'];
		$seen[$tid]=true;
		if($sp['in']!="") $stats['incidents']++;
		$depTs=($sp['raw']['dep']!="" && $sp['raw']['dep']!="0000-00-00 00:00:00") ? strtotime($sp['raw']['dep']) : false;
		if($depTs!==false){
			if($depTs>=$amFrom && $depTs<$amTo) $stats['am']++;
			elseif($depTs>=$pmFrom)             $stats['pm']++;
			$hIdx=(int)floor(($depTs-$dayStart)/3600);
			if($hIdx>=0 && $hIdx<48){
				$stats['hours'][$hIdx]=isset($stats['hours'][$hIdx]) ? $stats['hours'][$hIdx]+1 : 1;
			} else {
				$stats['untimed']++;   /* outside the operating day: cannot be plotted */
			}
		} else {
			$stats['untimed']++;
		}
		$t=ccsSkipTrainInfo($dbase,$tid);

		/* @viewonly -- this page is read-only: no Edit, no "+ Skip", no
		   "+ Loading". The forms, POST handlers and slide panel further up are
		   left in place but nothing on the page opens them any more. */

		/* Index: just the number */
		$idxCell='<td class="idx-cell">'
			.'<span class="idx-num">'.htmlspecialchars($t['index_no']).'</span>'
			.'</td>';

		/* Departure -- time and driver on the first line, station on the
		   second */
		$dep='<div class="skip-item skip-item--col">'
			.'<span class="skip-time">'.$sp['dep'].'</span>'
			.'<span class="skip-drv-inline">'.htmlspecialchars($sp['depdrv']).'</span>'
			.'<span class="skip-stn skip-line2">'.htmlspecialchars($sp['from']).'</span>'
			.'</div>';

		/* Arrived -- time on the first line, station on the second, so the
		   column only needs the width of the longest station name */
		if($sp['arr']!="" || $sp['arr_name']!=""){
			$arr='<div class="skip-item skip-item--col">'
				.'<span class="skip-time">'.$sp['arr'].'</span>'
				.'<span class="skip-stn skip-line2">'.htmlspecialchars($sp['arr_name']).'</span>'
				.'</div>';
		} else {
			$arr=$skipBlank;
		}

		/* Loaded -- laid out like Departure: time and driver (only when the
		   driver changed hands) on the first line, station on the second.
		   An empty loading means the train loaded on arrival, so that is what
		   the cell says rather than a dash, which would read as missing data. */
		if($sp['sep_load']){
			$load='<div class="skip-item skip-item--col">'
				.'<span class="skip-time">'.$sp['load'].'</span>'
				.'<span class="skip-drv-inline">'.htmlspecialchars($sp['loaddrv']).'</span>'
				.'<span class="skip-stn skip-line2">'.htmlspecialchars($sp['to']).'</span>'
				.'</div>';
		} else {
			$load='<div class="skip-item skip-item--col skip-item--none"><span class="tc-none">on arrival</span></div>';
		}

		/* Requested / Reported -- one per line of the block, each with a short
		   Req / Rep tag (the column header spells them out), so the column fits
		   a half-width pane */
		$req='<div class="skip-item skip-item--col">'
			.'<span class="skip-stn"><span class="skip-tag">Req</span> '.htmlspecialchars($sp['requested']).'</span>'
			.'<span class="skip-drv"><span class="skip-tag">Rep</span>&nbsp;'.htmlspecialchars($sp['reported']).'</span>'
			.'</div>';

		/* Incident */
		$inc=($sp['in']!="")
			? '<div class="skip-item skip-item--col skip-in">IN '.htmlspecialchars($sp['in']).'</div>'
			: $skipBlank;

		$rowClass=$t['row_class'];
		if($t['data_status']=="service" && ($n%2)) $rowClass.=" row--alt";

		$html.='<tr data-train-id="'.$tid.'" data-skip-id="'.intval($sp['id']).'" data-status="'.$t['data_status'].'" class="'.$rowClass.'">'
			.$idxCell
			.'<td class="ta-slot-cell">'.$dep.'</td>'
			.'<td class="ta-slot-cell">'.$arr.'</td>'
			.'<td class="ta-slot-cell">'.$load.'</td>'
			.'<td class="ta-slot-cell">'.$req.'</td>'
			.'<td class="ta-slot-cell">'.$inc.'</td>'
			.'</tr>';
	}
	$stats['trains']=count($seen);

	if(!count($skips)){
		$html='<tr class="row-empty"><td colspan="6">No skipping recorded on trains dated '
			.date("F d, Y",strtotime($dateCode)).'.</td></tr>';
	}
	return $html;
}
}

/* @compare -- one figure in a section bar; on B it carries the difference
   from A, so the comparison is stated rather than left to mental arithmetic. */
if(!function_exists('ccsStat')){
function ccsStat($n,$label,$vs=null){
	$d='';
	if($vs!==null){
		$diff=intval($n)-intval($vs);
		$d='<span class="cmp-delta">'.($diff>0 ? '+'.$diff : ($diff<0 ? '&minus;'.abs($diff) : '&plusmn;0')).' vs A</span>';
	}
	return '<span class="cmp-stat"><b>'.intval($n).'</b> '.$label.$d.'</span>';
}
}

/* @peak -- one peak tile: skip count in the window, its share of the day's
   skips, and on B the difference from A. */
if(!function_exists('ccsPeakTile')){
function ccsPeakTile($kind,$label,$window,$n,$total,$vs=null){
	$share=($total>0) ? round($n*100/$total).'% of '.intval($total).' skip'.(intval($total)==1?'':'s') : 'no skips that day';
	$d='';
	if($vs!==null){
		$diff=intval($n)-intval($vs);
		$d='<span class="cmp-delta">'.($diff>0 ? '+'.$diff : ($diff<0 ? '&minus;'.abs($diff) : '&plusmn;0')).' vs A</span>';
	}
	$icon=($kind=='am') ? 'ti-sunrise' : 'ti-sunset-2';
	return '<div class="peak-tile peak-tile--'.$kind.'">'
		.'<div class="peak-head"><i class="ti '.$icon.'" aria-hidden="true"></i>'
		.'<span class="peak-label">'.$label.'</span><span class="peak-window">'.$window.'</span></div>'
		.'<div class="peak-body"><span class="peak-num">'.intval($n).'</span>'
		.'<span class="peak-unit">skip'.(intval($n)==1?'':'s').'</span>'.$d.'</div>'
		.'<div class="peak-share">'.$share.'</div>'
		.'</div>';
}
}

/* =========================================================================
   @chart -- A vs B, skips per hour of departure, one line per day.

   Drawn as inline SVG by PHP, not by a chart library: this page has no chart
   library, the console is not guaranteed a route to a CDN, and an SVG made on
   the server is on screen the moment the page is -- nothing to load or fail.

   The hours run across the operating day: 04:00 to 23:00 always, widened
   only when a day has skips outside that (before 04:00, or after midnight on
   the same operating day, drawn past a "midnight" marker rather than wrapped
   to the left). The AM and PM peak bands are shaded with the same windows as
   the tiles above, so the area under a line inside a band IS that tile's
   number.

   TODAY'S line stops at the current hour. The hours still to come have no
   skips yet, and a line dropping to zero for them would read as a quiet
   afternoon -- the same reason the console shows a dash for a peak that has
   not started.

   $series: array of array('key','label','hours','untimed','is_today').
   ========================================================================= */
if(!function_exists('ccsSkipChart')){
function ccsSkipChart($series){
	$colors=array('a'=>array('#00529B',''), 'b'=>array('#C98F00','7 5'));
	$hMin=4; $hMax=23; $any=0; $untimed=0; $anyToday=false;
	foreach($series as $sr){
		foreach($sr['hours'] as $h=>$n){ if($n>0){ $any+=$n; if($h<$hMin) $hMin=$h; if($h>$hMax) $hMax=$h; } }
		$untimed+=$sr['untimed'];
		if($sr['is_today']) $anyToday=true;
	}
	$nowH=(int)floor((time()-strtotime(date("Y-m-d")." 00:00:00"))/3600);

	/* legend + notes are HTML, so they stay sharp and wrap on a narrow screen */
	$legend='';
	foreach($series as $sr){
		$c=$colors[$sr['key']];
		$tot=0; foreach($sr['hours'] as $n){ $tot+=$n; }
		$legend.='<span class="cmp-chart-key">'
			.'<svg width="26" height="10" aria-hidden="true"><line x1="1" y1="5" x2="25" y2="5" stroke="'.$c[0].'" stroke-width="2.5"'
			.($c[1]!==''?' stroke-dasharray="'.$c[1].'"':'').'/></svg>'
			.'<b>'.strtoupper($sr['key']).'</b> '.htmlspecialchars($sr['label'])
			.' <span class="cmp-chart-n">&middot; '.$tot.' skip'.($tot==1?'':'s').' plotted</span></span>';
	}
	$notes=array();
	if($anyToday) $notes[]="Today's line stops at the current hour.";
	if($untimed>0) $notes[]=$untimed.' skip'.($untimed==1?' has':'s have').' no departure time and '.($untimed==1?'is':'are').' not plotted.';

	$head='<div class="cmp-chart-head"><h3><i class="ti ti-chart-line" aria-hidden="true"></i> Skips per hour'
		.' <span class="cmp-chart-sub">by departure time</span></h3>'
		.'<div class="cmp-chart-legend">'.$legend.'</div></div>';

	if($any===0){
		return '<div class="cmp-chart">'.$head
			.'<div class="cmp-chart-empty">No skips with a departure time on either day.</div>'
			.($notes ? '<div class="cmp-chart-note">'.htmlspecialchars(implode(' ',$notes)).'</div>' : '')
			.'</div>';
	}

	/* geometry, in viewBox units */
	$W=1400; $H=200; $L=42; $R=18; $T=30; $B=32;
	$pw=$W-$L-$R; $ph=$H-$T-$B;
	$nB=$hMax-$hMin+1;
	$step=($nB>1) ? $pw/($nB-1) : $pw;
	$xOf=function($h) use($L,$hMin,$step){ return $L+($h-$hMin)*$step; };

	$maxN=1;
	foreach($series as $sr){ foreach($sr['hours'] as $n){ if($n>$maxN) $maxN=$n; } }
	$tick=($maxN<=5) ? 1 : (int)ceil($maxN/5);
	$yTop=(int)(ceil($maxN/$tick)*$tick);
	$yOf=function($n) use($T,$ph,$yTop){ return $T+$ph-($n/$yTop)*$ph; };

	$svg='<svg class="cmp-chart-svg" viewBox="0 0 '.$W.' '.$H.'" role="img" aria-label="Skips per hour of departure, A against B">';

	/* peak bands (clipped to the plotted range) */
	$half=$step/2;
	$bands=array(array(4,8,'AM peak','am'), array(15,$hMax,'PM peak','pm'));
	foreach($bands as $bd){
		$from=max($bd[0],$hMin); $to=min($bd[1],$hMax);
		if($to<$from) continue;
		$x1=max($L,$xOf($from)-$half); $x2=min($L+$pw,$xOf($to)+$half);
		$svg.='<rect class="cmp-band cmp-band--'.$bd[3].'" x="'.round($x1,1).'" y="'.$T.'" width="'.round($x2-$x1,1).'" height="'.$ph.'"/>'
			/* label ABOVE the plotting area, so a point near the top of the
			   band can never sit on it */
			.'<text class="cmp-band-lbl" x="'.round($x1+2,1).'" y="'.($T-9).'">'.$bd[2].'</text>';
	}

	/* horizontal gridlines + y labels */
	for($v=0;$v<=$yTop;$v+=$tick){
		$y=round($yOf($v),1);
		$svg.='<line class="cmp-grid-y" x1="'.$L.'" y1="'.$y.'" x2="'.($L+$pw).'" y2="'.$y.'"/>'
			.'<text class="cmp-ax" x="'.($L-8).'" y="'.($y+4).'" text-anchor="end">'.$v.'</text>';
	}

	/* x labels; a marker where the clock passes midnight */
	for($h=$hMin;$h<=$hMax;$h++){
		$x=round($xOf($h),1);
		$svg.='<text class="cmp-ax" x="'.$x.'" y="'.($T+$ph+18).'" text-anchor="middle">'.sprintf('%02d',$h%24).'</text>';
		if($h==24){
			$svg.='<line class="cmp-midnight" x1="'.round($x-$half,1).'" y1="'.$T.'" x2="'.round($x-$half,1).'" y2="'.($T+$ph).'"/>'
				.'<text class="cmp-ax cmp-ax--note" x="'.round($x-$half+4,1).'" y="'.($T+$ph-6).'">midnight</text>';
		}
	}

	/* the lines: A first, then B's dashed line over it. Where the two days
	   have the same count the dashes alternate gold and blue, so neither line
	   disappears under the other. */
	foreach($series as $sr){
		$c=$colors[$sr['key']];
		$last=$hMax;
		if($sr['is_today']) $last=min($hMax,$nowH);
		if($last<$hMin) continue;
		$pts=array(); $dots='';
		for($h=$hMin;$h<=$last;$h++){
			$n=isset($sr['hours'][$h]) ? $sr['hours'][$h] : 0;
			$x=round($xOf($h),1); $y=round($yOf($n),1);
			$pts[]=$x.','.$y;
			$dots.='<circle cx="'.$x.'" cy="'.$y.'" r="'.($n>0?4:2.5).'" fill="'.($n>0?$c[0]:'#fff').'" stroke="'.$c[0].'" stroke-width="1.5">'
				.'<title>'.strtoupper($sr['key']).' '.htmlspecialchars($sr['label']).' &middot; '
				.sprintf('%02d:00&ndash;%02d:59',$h%24,$h%24).' &middot; '.$n.' skip'.($n==1?'':'s').'</title></circle>';
		}
		$svg.='<polyline fill="none" stroke="'.$c[0].'" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round"'
			.($c[1]!==''?' stroke-dasharray="'.$c[1].'"':'').' points="'.implode(' ',$pts).'"/>'.$dots;
	}
	$svg.='</svg>';

	return '<div class="cmp-chart">'.$head.$svg
		.($notes ? '<div class="cmp-chart-note">'.htmlspecialchars(implode(' ',$notes)).'</div>' : '')
		.'</div>';
}
}

/* ── POST handlers (verbatim from original, whitespace tightened only) ── */
if(isset($_POST['index_no'])){
	$index_no=$_POST['index_no'];
	$lpam_id=$_POST['lpam_id'];
	$type=$_POST['type'];
	$car_a=$_POST['car_1']; $car_b=$_POST['car_2'];
	$car_c=$_POST['car_3']; $car_d=$_POST['car_4'];
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	db_exec($db,"insert into train_availability(index_no,date,car_a,car_b,car_c,car_d,lpam_id,status,type)
		values (?,?,?,?,?,?,?,'active',?)",
		array($index_no,$availability_date,$car_a,$car_b,$car_c,$car_d,$lpam_id,$type));
	$index_id=$db->insert_id;
	insertCompo($index_id,$car_a,$db); insertCompo($index_id,$car_b,$db);
	insertCompo($index_id,$car_c,$db); insertCompo($index_id,$car_d,$db);
	if(isset($_POST['cancel_departure'])){
		$availability_date="";
		db_exec($db,"update train_availability set status='cancelled' where id=?",array($index_id));
		echo "<script>window.addEventListener('load',function(){openIncidentPanel('cancel=".$index_id."','Cancel Train');});</script>"; /* was window.open popup; panel opens after load */
	}
	db_exec($db,"insert into train_ava_time(train_ava_id,boundary_time) values (?,?)",array($index_id,$availability_date));
}

if(isset($_POST['other_index_no'])){
	$index_no=$_POST['other_index_no'];
	$lpam_id=$_POST['lpam_id'];
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	$train_type=$_POST['train_type'];
	db_exec($db,"insert into train_availability(index_no,date,status,type) values (?,?,'active','unimog')",array($index_no,$availability_date));
	$index_id=$db->insert_id;
	if(isset($_POST['cancel_departure'])){
		db_exec($db,"update train_availability set status='cancelled' where id=?",array($index_id));
		$availability_date="";
		echo "<script>window.addEventListener('load',function(){openIncidentPanel('cancel=".$index_id."','Cancel Train');});</script>"; /* was window.open popup; panel opens after load */
	}
	db_exec($db,"insert into train_ava_time(train_ava_id,boundary_time) values (?,?)",array($index_id,$availability_date));
}

if(isset($_POST['insertion_id'])){
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=($_POST['hour']=="") ? "" :
		date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	if(isset($_POST['unimog_train_driver'])){
		$train_driver=($_POST['unimog_train_driver']=="other") ? $_POST['unimog_td_alternate'] : $_POST['unimog_train_driver'];
	} else {
		$train_driver=$_POST['train_driver'];
	}
	$rs=db_query($db,"select * from train_ava_time where train_ava_id=?",array($_POST['insertion_id']));
	if($rs->num_rows>0){
		/* item #2: the two consecutive UPDATEs on the same row were merged into one statement */
		db_exec($db,"update train_ava_time set insert_time=?,insert_driver=?,inserted_to=? where train_ava_id=?",
			array($availability_date,$train_driver,$_POST['inserted_to'],$_POST['insertion_id']));
	} else {
		db_exec($db,"insert into train_ava_time(train_ava_id,insert_time,insert_driver,inserted_to) values (?,?,?,?)",
			array($_POST['insertion_id'],$availability_date,$train_driver,$_POST['inserted_to']));
	}
	$changeRow=db_query($db,"select * from train_availability where id=?",array($_POST['insertion_id']))->fetch_assoc();
	$train_date=$changeRow['date'];
	$_POST['year']=date("Y",strtotime($train_date)); $_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));  $_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date)); $_POST['amorpm']=date("A",strtotime($train_date));
}

if(isset($_POST['remove_id'])){
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if(isset($_POST['unimog_train_driver'])){
		$train_driver=($_POST['unimog_train_driver']=="other") ? $_POST['unimog_td_alternate'] : $_POST['unimog_train_driver'];
	} else {
		$train_driver=$_POST['train_driver'];
	}
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	$availability_date=($_POST['hour']=="") ? "" :
		date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	$cancel_loop=$_POST['cancel_loop'];
	/* item #2: removal_remarks is now a bound parameter -- quotes/apostrophes in remarks no
	   longer break or corrupt this UPDATE. The two consecutive UPDATEs on the same row
	   (remove fields, then removed_from) were merged into one statement. */
	db_exec($db,"update train_ava_time set remove_time=?,remove_driver=?,removal_remarks=?,removed_from=? where train_ava_id=?",
		array($availability_date,$train_driver,$_POST['remarks'],$_POST['removed_from'],$_POST['remove_id']));
	$changeRow=db_query($db,"select * from train_availability where id=?",array($_POST['remove_id']))->fetch_assoc();
	$train_date=$changeRow['date'];
	$_POST['year']=date("Y",strtotime($train_date)); $_POST['month']=date("m",strtotime($train_date));
	$_POST['day']=date("d",strtotime($train_date));  $_POST['hour']=date("H",strtotime($train_date));
	$_POST['minute']=date("i",strtotime($train_date)); $_POST['amorpm']=date("A",strtotime($train_date));
	if(isset($_POST['cancel_loop'])){
		echo "<script>window.addEventListener('load',function(){openIncidentPanel('add_incident=".$_POST['remove_id']."','Add Incident');});</script>"; /* was window.open popup; panel opens after load */
	}
}

if(isset($_POST['remarks_id'])){
	$rs=db_query($db,"select * from train_ava_time where train_ava_id=?",array($_POST['remarks_id']));
	if($rs->num_rows>0){
		db_exec($db,"update train_ava_time set removal_remarks=? where train_ava_id=?",array($_POST['remarks'],$_POST['remarks_id']));
	} else {
		db_exec($db,"insert into train_ava_time(removal_remarks,train_ava_id) values (?,?)",array($_POST['remarks'],$_POST['remarks_id']));
	}
}

if(isset($_POST['switch_id'])){
	/* item #2 note: this postback path is superseded by the AJAX switch
	   (processing.php?ajaxSwitch) -- converted anyway; its removal is item #4. */
	$year=$_POST['year']; $month=$_POST['month']; $day=$_POST['day'];
	$hour=$_POST['hour']; $minute=$_POST['minute']; $amorpm=$_POST['amorpm'];
	if($amorpm=="pm"){ if($hour<12) $hour+=12; }
	else { if($hour=="12") $hour=0; }
	
	$switchDriver="";
	$switchDriver=$_POST['train_driver'];
	
	$availability_date=date("Y-m-d H:i",strtotime($year."-".$month."-".$day." ".$hour.":".$minute));
	db_exec($db,"insert into train_switch(train_ava_id,new_index,date_change,train_driver) values (?,?,?,?)",array($_POST['switch_id'],$_POST['new_index'],$availability_date),$switchDriver);
	$switchRow=db_query($db,"select * from train_availability where id=? limit 1",array($_POST['switch_id']))->fetch_assoc();
	if($switchRow['type']=="reserve"){
		db_exec($db,"update train_availability set type='revenue' where id=?",array($_POST['switch_id']));
	}
}

if(isset($_POST['edit_id'])){
	db_exec($db,"update train_availability set index_no=? where id=?",array($_POST['edit_index'],$_POST['edit_id']));
}

if(isset($_POST['edit_car'])){
	/* item #1 fix: re-inserts previously used $index_id, which is never set in this
	   branch -- the real compo rows were deleted, then replaced by orphan rows under a
	   blank tar_id. Now keyed to the edited train. (Edit modal also gained a Car 4 field,
	   so a 4-car set's car_d is no longer silently blanked.) */
	$edit_car_id=$_POST['edit_car'];
	db_exec($db,"delete from train_compo where tar_id=?",array($edit_car_id));
	db_exec($db,"update train_availability set car_a=?,car_b=?,car_c=?,car_d=? where id=?",
		array($_POST['car_1'],$_POST['car_2'],$_POST['car_3'],$_POST['car_4'],$edit_car_id));
	insertCompo($edit_car_id,$_POST['car_1'],$db); insertCompo($edit_car_id,$_POST['car_2'],$db);
	insertCompo($edit_car_id,$_POST['car_3'],$db); insertCompo($edit_car_id,$_POST['car_4'],$db);
}

/* ── SKIPPING POST handlers — ported from train_availability_skip.php ──
   All three are the legacy statements with their values bound rather than
   interpolated. That is not cosmetic here: requested_by and reported_by are
   free-typed operator names, and the legacy INSERT wrapped them in single
   quotes, so the first STDO with an apostrophe in their surname broke the
   statement outright. Remarks-style double-quote wrapping of the two
   timestamps goes the same way. */

if(isset($_POST['update_skipping'])){
	/* @skip -- departure_bound added to the UPDATE. The edit form has always
	   shown a Bound select, and the legacy UPDATE silently dropped it, so
	   correcting a skip's bound was impossible once it was saved. */
	db_exec($db,"update skipping set
			departure_time=?, departure_location=?, departure_bound=?, departure_driver=?,
			requested_by=?, reported_by=?, incident_no=?,
			arrived_time=?, arrived_location=?
		where id=?",
		array(
			ccsPostDateTime(''), ccsPost('skip_loc'), ccsPost('bound'), ccsPost('train_driver'),
			ccsPost('requested_by'), ccsPost('reported_by'), ccsPost('incident_no'),
			ccsPostDateTime('2'), ccsPost('arrived_location'),
			$_POST['update_skipping']
		));
}
elseif(isset($_POST['skip_loc'])){
	db_exec($db,"insert into skipping
			(departure_time,departure_location,departure_driver,departure_bound,tar_id,
			 requested_by,reported_by,incident_no,arrived_time,arrived_location)
		values (?,?,?,?,?,?,?,?,?,?)",
		array(
			ccsPostDateTime(''), ccsPost('skip_loc'), ccsPost('train_driver'), ccsPost('bound'),
			ccsPost('departure_id'), ccsPost('requested_by'), ccsPost('reported_by'),
			ccsPost('incident_no'), ccsPostDateTime('2'), ccsPost('arrived_location')
		));
}

if(isset($_POST['loading_location'])){
	/* legacy SELECTed the row first and then updated it by the id it had just
	   read back from the id it was given; dropped. */
	db_exec($db,"update skipping set loading_time=?, loading_location=?, loading_driver=?, loading_bound=?
		where id=?",
		array(ccsPostDateTime(''), $_POST['loading_location'], ccsPost('train_driver'),
		      ccsPost('bound'), ccsPost('loading_id')));
}
?>

<?php $selfPage = basename(__FILE__); /* form action / reload target — rename-safe */ ?>

<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css">
<?php
if(file_exists(dirname(__FILE__)."/datepicker_theme.php")){ include(dirname(__FILE__)."/datepicker_theme.php"); }
?>
<script src="jquery-ui-1.11.1/external/jquery/jquery.js"></script>
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>

<style type='text/css'>
/* =========================================================================
   LINE 3 OPERATIONS CONSOLE — operations.php layout, train_availability skin
   ========================================================================= */
:root {
	--ta-sans: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
	--ta-mono: ui-monospace, "Cascadia Mono", "Consolas", "Liberation Mono", monospace;
	--rail:      #00529B;
	--rail-dark: #013E76;
	--rail-wash: #EEF4FB;
	--gold:      #FDB813;
	--gold-ink:  #3A2D00;
	--ink:       #16243B;
	--mut:       #5A6678;
	--line:      #D2DDEA;
	--paper:     #F7F9FC;
	--c-service:   #1D9E75;
	--c-reserve:   #BA7517;
	--c-removed:   #378ADD;
	--c-cancelled: #E24B4A;
	--c-skipping:  #6D5BD0;
}



/* @font -- --ta-sans already matches the stack every other page uses, so the
   mismatch was never the value. Two scoping gaps:

   1. The rule was scoped to .ta-ops, so anything rendered OUTSIDE that
      container -- the template's own chrome, and the slide panels, which are
      position:fixed siblings -- fell back to whatever the admin template sets.
   2. Form controls do NOT inherit font-family. A <button>, <input>, <select>
      or <textarea> with no explicit rule renders in the browser's own UI font
      regardless of what its parent says. Several here had one (#search_date,
      .ops-nav-btn, .ops-pill) and several did not (.ops-go, the panel close
      button, anything added later), which is exactly the "some of the text"
      pattern -- it was always the controls that had been missed.

   Declaring it on body covers (1); the control reset covers (2) for
   everything present and everything added afterwards. */
body, .ta-ops { font-family:var(--ta-sans); }
.ta-ops { color:var(--ink); }
.ta-ops * { box-sizing:border-box; }
button, input, select, textarea,
.ta-ops button, .ta-ops input, .ta-ops select, .ta-ops textarea,
.ta-panel button, .ta-panel input, .ta-panel select, .ta-panel textarea {
	font-family:var(--ta-sans);
}
/* The monospace elements opt back in explicitly, so the reset above cannot
   flatten the figures the layout relies on being tabular. */
/* @font -- TIMES are not in this list any more. The insertion and removal
   times were monospace while the remarks beside them were sans, so two cells
   in the same row disagreed about what font the page uses -- and the times are
   the ones people read first.

   The reason they were monospace is real though: a proportional font gives
   digits different widths, so a column of times will not line up. That is what
   font-variant-numeric:tabular-nums is for -- it asks the sans face for its
   fixed-width digits, so the column still aligns without the typewriter look.
   Supported everywhere this console runs; where it is not, the only loss is
   the alignment it was solving for anyway.

   Identifiers stay monospace: an index number or a car number is a code to be
   read character by character, not prose. */
.idx-num, .sw-chip .sw-idx, .tc-car, td.lvl, .ops-info .val { font-family:var(--ta-mono); }
.hl-time, .ta-slot-time, .sw-chip .sw-time, .skip-time,
.ta-ops td .hl-time, .ta-ops td .ta-slot-time {
	font-family:var(--ta-sans);
	font-variant-numeric:tabular-nums;
	font-feature-settings:"tnum" 1;
}

/* ── Page header (operations.php two-row header, Line 3 skin) ── */
.ops-header       { background:var(--rail); border-bottom:3px solid var(--gold); padding:10px 16px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap; }
.ops-title h1     { margin:0; font-size:16px; font-weight:700; color:#fff; letter-spacing:.3px; line-height:1.2; }
.ops-title .sub   { font-size:10px; color:rgba(255,255,255,.55); letter-spacing:.5px; text-transform:uppercase; }
.ops-datebar      { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.ops-datebar form { margin:0; padding:0; display:inline-flex; align-items:center; gap:4px; }
/* @compare -- was #search_date; a class now, since each section has its own picker */
.ops-date-input   { height:27px; font-size:12px; font-family:var(--ta-sans); background:#fff; color:var(--ink); border:1px solid var(--line); border-radius:4px; padding:0 8px; width:118px; }
.ops-go           { height:27px; font-size:11px; font-weight:700; background:var(--gold); color:var(--gold-ink); border:none; border-radius:4px; padding:0 12px; cursor:pointer; }
.ops-nav-btn      { height:27px; min-width:27px; font-size:11px; font-weight:600; font-family:var(--ta-sans); color:#fff; background:transparent; border:1px solid rgba(255,255,255,.35); border-radius:4px; padding:0 8px; cursor:pointer; }
.ops-nav-btn:hover{ background:rgba(255,255,255,.12); }
.ops-actions      { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.ops-act          { display:inline-block; font-size:11px; font-weight:500; color:#fff; text-decoration:none; padding:5px 11px; border:1px solid rgba(255,255,255,.35); border-radius:4px; float:none !important; width:auto !important; cursor:pointer; }
.ops-act:hover    { background:rgba(255,255,255,.12); color:#fff; }
.ops-act--gold    { background:var(--gold); border-color:var(--gold); color:var(--gold-ink); font-weight:600; }
.ops-act--gold:hover { background:#E8A606; border-color:#E8A606; color:var(--gold-ink); }
.ops-act.disabled { color:rgba(255,255,255,.3); border-color:rgba(255,255,255,.15); pointer-events:none; }

/* ── Info strip (timetable code / date / day — operations.php bottom row) ── */
.ops-strip        { background:#fff; border-bottom:1px solid var(--line); padding:7px 16px; display:flex; align-items:center; gap:26px; flex-wrap:wrap; }
.ops-info         { display:flex; align-items:center; gap:8px; }
.ops-info i       { color:var(--rail); font-size:15px; }
.ops-info .lbl    { display:block; font-size:9px; text-transform:uppercase; letter-spacing:.6px; color:var(--mut); font-weight:600; }
.ops-info .val    { display:block; font-size:12.5px; font-weight:700; color:var(--ink); font-family:var(--ta-mono); }
.ops-info .val a  { font-size:10px; font-weight:400; font-family:var(--ta-sans); color:var(--rail); text-decoration:none; margin-left:6px; }
.ops-info .val a:hover { text-decoration:underline; }

/* ── Section header + filter pills (operations.php Operations Log row) ── */
.ops-section      { padding:10px 16px 2px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.ops-section h2   { margin:0; font-size:12px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--rail); }
.ops-pills        { display:flex; gap:5px; flex-wrap:wrap; }
.ops-pill         { font-size:10.5px; font-weight:600; font-family:var(--ta-sans); color:var(--mut); background:#fff; border:1px solid var(--line); border-radius:12px; padding:3px 11px; cursor:pointer; }
.ops-pill:hover   { border-color:var(--rail); color:var(--rail); }
.ops-pill.active  { background:var(--rail); border-color:var(--rail); color:#fff; }

/* ── Refined table (operations.php) — Line 3 skin ── */
.ops-table-wrap   { margin:8px 16px 14px; overflow-x:auto; border-radius:6px; box-shadow:0 1px 3px rgba(0,30,80,.12); background:#fff; }
table.train_ava   { width:100%; border-collapse:separate; border-spacing:0; min-width:980px; }
table.train_ava th{ background:var(--rail); color:#fff; padding:9px 10px; font-family:var(--ta-sans); font-weight:600; font-size:11px; letter-spacing:.4px; text-transform:uppercase; text-align:center; border-right:1px solid rgba(255,255,255,.18); border-bottom:3px solid var(--gold); }
table.train_ava td{ padding:8px 10px; vertical-align:top; border-right:1px solid #E6EDF5; border-bottom:1px solid #E6EDF5; font-family:var(--ta-sans); font-size:12.5px; }
table.train_ava tr.row-first td { border-top:2px solid var(--line); }

/* Row status tints (train_availability console) */
tr.row--service   td { background:#ffffff; }
tr.row--service.row--alt td { background:#FAFCFE; }
tr.row--removed   td { background:var(--rail-wash); }
tr.row--cancelled td { background:#FCF0EE; }
tr.row--reserve   td { background:#FFF8ED; }
tr.tr-hover       td { background:#E7F1FB !important; }

/* Index cell: stripe + number + pill + switch trail + actions */
td.idx-cell       { position:relative; padding-left:14px !important; vertical-align:top !important; min-width:150px; }
td.idx-cell::before { content:""; position:absolute; left:0; top:0; bottom:0; width:3px; }
tr.row--service   td.idx-cell::before { background:var(--c-service); }
tr.row--reserve   td.idx-cell::before { background:var(--c-reserve); }
tr.row--removed   td.idx-cell::before { background:var(--c-removed); }
tr.row--cancelled td.idx-cell::before { background:var(--c-cancelled); }
.idx-num          { display:inline-block; font-family:var(--ta-mono); font-weight:700; font-size:19px; color:var(--rail); line-height:1.1; }
.status-pill      { display:inline-flex; align-items:center; gap:5px; font-size:10px; font-weight:500; border-radius:10px; padding:1px 8px; margin-left:7px; vertical-align:3px; }
.status-pill .led { width:7px; height:7px; border-radius:50%; flex:none; }
.pill--service    { background:#E1F3EA; color:#0F6E4E; } .pill--service   .led { background:var(--c-service); }
.pill--reserve    { background:#FAEEDA; color:#854F0B; } .pill--reserve   .led { background:var(--c-reserve); }
.pill--removed    { background:#E6F1FB; color:#0C447C; } .pill--removed   .led { background:var(--c-removed); }
.pill--cancelled  { background:#FCEBEB; color:#A32D2D; } .pill--cancelled .led { background:var(--c-cancelled); }
.pill--pending    { background:#EEF0F3; color:#4A5464; } .pill--pending   .led { background:#8A93A0; }
/* @skipping -- a BADGE, not a status pill. It sits beside whichever lifecycle
   pill the train has, because a skipping train is also in service, or also
   removed; it is never skipping INSTEAD of those. Matches
   train_operations_admin.php so the operators' board and the management board
   cannot describe the same train differently. */
.pill--skipping   { background:#EDE9FB; color:#4B3A8F; } .pill--skipping  .led { background:var(--c-skipping); }
tr.is-skipping td.idx-cell::after{ content:""; position:absolute; left:0; bottom:0;
	width:3px; height:34%; background:var(--c-skipping); }

/* Switch trail — replaces the original's 7 Switch columns */
.sw-trail         { display:flex; flex-direction:column; align-items:flex-start; gap:3px; margin-top:6px; }
.sw-chip          { display:inline-flex; align-items:center; gap:6px; background:#fff; border:1px solid #C5D8EE; border-left:3px solid var(--gold); border-radius:4px; padding:2px 7px; }
.sw-chip .sw-idx  { font-family:var(--ta-mono); font-weight:700; font-size:12.5px; color:var(--rail); }
.sw-chip .sw-time { font-family:var(--ta-sans); font-variant-numeric:tabular-nums; font-size:10.5px; color:var(--mut); }
.sw-chip .sw-drv  { font-size:10px; color:var(--mut); }
.sw-chip .ta-del-sw { text-decoration:none; font-size:12px; color:#B23A33; line-height:1; }
.sw-chip .ta-del-sw.disabled { display:none; }
.idx-actions      { margin-top:6px; visibility:hidden; }
tr.tr-hover .idx-actions { visibility:visible; }

/* ── Skip column — ported from train_availability_skip.php's two Skip columns
      ("Skipped from" / "Arrived/1st loading") plus the variable-height band of
      rows they needed. Here each skip is one block in a stack, like the switch
      trail: no band arithmetic, no 2N+1 rowspan, and no cap. ── */
td.skip-cell      { min-width:205px; font-size:11.5px; vertical-align:top !important; }
.skip-item        { background:#FBFAFF; border:1px solid #E2DCF7; border-left:3px solid var(--c-skipping); border-radius:4px; padding:4px 7px; margin-bottom:5px; }
.skip-leg         { display:flex; align-items:baseline; gap:5px; flex-wrap:wrap; line-height:1.5; }
.skip-leg--sub    { color:var(--mut); }
.skip-time        { font-weight:700; color:var(--ink); }
.skip-leg--sub .skip-time { font-weight:600; color:var(--mut); }
.skip-stn         { font-weight:600; }
.skip-arrow       { color:var(--c-skipping); }
.skip-tag         { font-size:9px; text-transform:uppercase; letter-spacing:.5px; color:#99A2AE; }
.skip-meta        { color:var(--mut); font-size:10.5px; margin-top:2px; line-height:1.45; }
.skip-in          { font-size:10.5px; color:#19459B; }
.skip-note        { font-size:10.5px; color:#9A3412; }
.skip-add         { margin-top:2px; }
/* @skipcols -- the Departure / Arrived / Loaded / Requested columns each hold
   one block per skip, and the k-th block in every column is the same skip.
   Two things keep them level across the row: every column gets a block for
   every skip (a dash where there is nothing to show -- see $skipBlank), and
   each block is a fixed two lines tall: time and station on the first, the
   driver (.skip-drv) on the second. Blocks with nothing for the second line
   -- Arrived, Requested/Reported, Incident, the dash -- keep the same height,
   so the first lines still sit level across the row. nowrap is what makes
   the fixed height safe: a long name widens its column instead of wrapping
   to a third line. 46px = 2 x 18px lines + 2 x 4px padding + 2 x 1px border.
   Scoped to --col so the multi-line blocks in the Skip cell ($skipBody) are
   left alone. */
.skip-item--col   { box-sizing:border-box; height:46px; line-height:18px; white-space:nowrap; }
/* @skipcols -- the driver line is a row, so an action (Edit) can sit beside
   the name rather than under it. Its height is pinned to one 18px line, and
   the chip is slimmed to 16px inside it: at its normal 21px it would push
   the line, and the block, past the fixed 46px. The !important flags are
   only there because every .ta-act declaration already carries one. */
.skip-line2       { display:block; height:18px; }
.skip-drv         { display:flex; align-items:center; height:18px; font-size:11px; font-weight:400; color:var(--mut); }
.skip-drv .ta-act, .skip-row2 .ta-act { padding:0 6px !important; line-height:14px !important; margin:0 0 0 6px !important; }
/* Departure's second line: the station, at full ink, with Edit beside it */
.skip-row2        { display:flex; align-items:center; height:18px; }
.skip-drv-inline  { font-size:11px; color:var(--mut); margin-left:7px; }
.skip-item--none  { font-style:italic; font-size:11px; }
/* per-skip controls follow the page's hover convention (.idx-actions,
   .ta-slot-actions); "+ Skip" stays put, since with no skips yet it is the
   only thing in the cell to find. */
.skip-item .ta-act { visibility:hidden; }
tr.tr-hover .skip-item .ta-act { visibility:visible; }
/* @skipcols -- a block whose only content is an action ("+ Loading (if
   separate)" in the Loaded column). It is a --col block like every other,
   so it keeps the fixed 46px height and the same borders and left edge as
   its neighbours; the flex centring puts the button in the middle of that
   box rather than on the first text line, since there is no text for it to
   line up with. Visible without hover, for the reason above. */
.skip-item--action { display:flex; align-items:center; justify-content:center; }
.skip-item--action .ta-act { visibility:visible; margin-right:0 !important; }

/* Car column — one car per sub-row (operations.php), linked like the original */
td.tc-car-cell    { text-align:center; vertical-align:middle !important; min-width:64px; }
.tc-car           { display:inline-block; font-family:var(--ta-mono); font-size:13px; font-weight:700; color:var(--rail); text-decoration:none; background:var(--rail-wash); border:1px solid #C5D8EE; border-radius:4px; padding:2px 10px; min-width:44px; text-align:center; transition:background .12s,color .12s; float:none !important; width:auto !important; }
.tc-car:hover     { background:var(--rail); color:#fff; border-color:var(--rail); }
.tc-none          { color:#9AA6B6; }

/* Time / slot cells (train_availability console) */
.ta-slot-cell     { padding:8px 10px !important; vertical-align:top !important; min-width:110px; }
.hl-time          { display:inline-block; font-family:var(--ta-sans); font-variant-numeric:tabular-nums; font-size:13px; font-weight:700; color:#084298; background:#DCEBFB; border:1px solid #B7D3F2; border-radius:5px; padding:3px 10px; }
.ta-slot-time     { display:block; font-family:var(--ta-sans); font-variant-numeric:tabular-nums; font-size:13px; font-weight:700; color:var(--ink); line-height:1.35; }
.ta-slot-driver   { display:block; font-size:11px; color:var(--mut); line-height:1.35; margin-top:2px; }
.ta-slot-actions  { display:block; margin-top:5px; height:20px; visibility:hidden; }
td.td-hover .ta-slot-actions { visibility:visible; }

/* Action chips (train_availability console, verbatim look) */
.ta-act { display:inline-block !important; font-size:10px !important; font-weight:600 !important; text-decoration:none !important; padding:2px 7px !important; border-radius:3px !important; border:1px solid #B8B0A2 !important; background:#F1EEE3 !important; color:var(--rail) !important; line-height:1.5 !important; cursor:pointer !important; margin-right:3px !important; float:none !important; width:auto !important; }
.ta-act:hover        { background:var(--rail) !important; color:#fff !important; border-color:var(--rail) !important; }
.ta-act-cancel       { color:#B23A33 !important; border-color:#DDB5B3 !important; background:#FDF2F2 !important; }
.ta-act-cancel:hover { background:#B23A33 !important; color:#fff !important; border-color:#B23A33 !important; }
.ta-act-sep          { font-size:10px !important; color:#C4BBAE !important; margin:0 2px; }
.ta-act.disabled     { display:none !important; }

/* Remarks + levels */
.ta-remarks       { text-align:left; min-width:200px; }
.ta-remarks a     { color:#19459B; font-weight:500; text-decoration:none; }
.ta-cancelled-flag{ font-weight:700; letter-spacing:1px; color:#A32D2D; text-align:center; vertical-align:middle !important; }
td.lvl            { font-family:var(--ta-mono); color:#854F0B; text-align:center; min-width:44px; }
td.del-cell       { text-align:center; vertical-align:middle !important; }
td.del-cell a     { color:#B23A33; text-decoration:none; font-weight:700; }
td.del-cell a.disabled { display:none; }

/* ── Slide panel (operations.php) — hosts the original generated forms ── */
.ta-overlay       { position:fixed; inset:0; background:rgba(10,25,50,.45); opacity:0; visibility:hidden; transition:opacity .2s; z-index:99998; }
.ta-overlay.active{ opacity:1; visibility:visible; }
.ta-panel         { position:fixed; top:0; right:-900px; width:480px; max-width:96vw; height:100vh; background:var(--paper); box-shadow:-6px 0 24px rgba(0,30,80,.25); transition:right .25s ease; z-index:99999; display:flex; flex-direction:column; font-family:var(--ta-sans); }
.ta-panel.active  { right:0; }
.ta-panel-head    { background:var(--rail); border-bottom:3px solid var(--gold); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; flex:none; }
.ta-panel-head h3 { margin:0; color:#fff; font-size:13px; font-weight:600; letter-spacing:.3px; }
.ta-panel-close   { background:none; border:none; color:rgba(255,255,255,.7); font-size:19px; line-height:1; cursor:pointer; padding:0 2px; }
.ta-panel-close:hover { color:var(--gold); }
.ta-panel-body    { flex:1; overflow-y:auto; padding:16px 18px; }
.ta-panel-foot    { flex:none; background:#fff; border-top:1px solid var(--line); padding:10px 16px; display:flex; justify-content:flex-end; gap:8px; }
.ta-panel-foot .btn { font-size:12px; font-weight:500; padding:6px 16px; border-radius:4px; border:1px solid #C9D6E5; background:#fff; color:#41506A; text-decoration:none; cursor:pointer; }
.ta-panel-foot .btn:hover { background:var(--rail-wash); border-color:var(--rail); color:var(--rail); }
.ta-panel-foot .btn-primary { background:var(--rail); border-color:var(--rail); color:#fff; }
.ta-panel-foot .btn-primary:hover { background:var(--rail-dark); border-color:var(--rail-dark); }
.ta-panel-foot .hint { margin-right:auto; align-self:center; font-size:10px; color:var(--mut); }

/* Incident panel: wider variant hosting incident report.php in an iframe.
   #irPanel.ta-panel--ir (id+class) so this can't lose a specificity tie
   against the base .ta-panel width rule regardless of source order. */
#irPanel.ta-panel--ir { width:820px; }
.ta-panel-body--ir { padding:0; overflow:hidden; position:relative; }
#irFrame           { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#irFrame.ready     { opacity:1; }
.ir-loading, .ir-fallback { position:absolute; top:0; right:0; bottom:0; left:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:var(--paper); text-align:center; padding:0 30px; }
.ir-loading.hidden, .ir-fallback.hidden { display:none; }
.ir-spinner { width:26px; height:26px; border:3px solid #C9D6E5; border-top-color:var(--rail); border-radius:50%; animation:ir-spin .7s linear infinite; }
@keyframes ir-spin { to { transform:rotate(360deg); } }
.ir-loading span, .ir-fallback p { font-size:12px; color:var(--mut); }
.ir-fallback strong { color:var(--ink); font-size:13px; }
.ir-fallback a { color:var(--rail); font-weight:600; text-decoration:none; }
.ir-fallback a:hover { text-decoration:underline; }

/* Original modal-form table CSS, rescoped from #addModal to .ta-panel (verbatim rules) */
#add_form         { width:100%; border-collapse:collapse; font-size:12px; font-family:var(--ta-sans); }
#add_form th      { background:var(--rail); color:#fff; font-size:11px; font-weight:600; letter-spacing:.4px; padding:6px 10px; text-align:left; border-bottom:2px solid var(--gold); }
#add_form td:nth-child(odd)  { background:var(--rail-wash); color:#1A2238; font-weight:600; font-size:11px; padding:7px 10px; white-space:nowrap; width:130px; border-bottom:1px solid var(--line); vertical-align:middle; }
#add_form td:nth-child(even) { background:#fff; padding:5px 10px; border-bottom:1px solid var(--line); vertical-align:middle; }
#add_form td:last-child      { background:var(--paper); text-align:center; padding:10px; }
#add_form td[colspan="2"]    { background:var(--paper); text-align:center; padding:10px; border-bottom:none; }
#add_form td.submit          { background:var(--paper); text-align:center; padding:10px; }
.ta-panel input[type="text"], .ta-panel input[type="number"] { height:28px; font-size:12px; font-weight:400; font-family:var(--ta-sans); border:1px solid #C5D8EE; background:#fff; color:#1A2238; border-radius:4px; padding:0 8px; width:100%; box-sizing:border-box; }
.ta-panel input[type="text"]:focus, .ta-panel input[type="number"]:focus { border-color:var(--rail); outline:none; box-shadow:0 0 0 2px rgba(0,82,155,.12); }
.ta-panel select   { height:28px; font-size:12px; font-family:var(--ta-sans); border:1px solid #C5D8EE; background:#fff; color:#1A2238; border-radius:4px; padding:0 6px; width:100%; box-sizing:border-box; }
.ta-panel select:focus { border-color:var(--rail); outline:none; }
.ta-panel textarea { font-size:12px; font-family:var(--ta-sans); border:1px solid #C5D8EE; background:#fff; color:#1A2238; border-radius:4px; padding:6px 8px; width:100%; box-sizing:border-box; resize:vertical; min-height:70px; }
.ta-panel textarea:focus { border-color:var(--rail); outline:none; box-shadow:0 0 0 2px rgba(0,82,155,.12); }
.ta-panel input[type="submit"], .ta-panel button[type="button"]:not(.ta-panel-close) { height:30px; font-size:12px; font-weight:600; font-family:var(--ta-sans); background:var(--rail); color:#fff; border:1px solid var(--rail); border-radius:4px; padding:0 18px; cursor:pointer; }
.ta-panel input[type="submit"]:hover, .ta-panel button[type="button"]:not(.ta-panel-close):hover { background:var(--rail-dark); border-color:var(--rail-dark); }
.ta-panel input[type="checkbox"] { margin-right:5px; vertical-align:middle; }
.ta-panel select[name="month"], .ta-panel select[name="day"], .ta-panel select[name="year"],
.ta-panel select[name="hour"], .ta-panel select[name="minute"], .ta-panel select[name="amorpm"],
.ta-panel select[name="month2"], .ta-panel select[name="day2"], .ta-panel select[name="year2"],
.ta-panel select[name="hour2"], .ta-panel select[name="minute2"], .ta-panel select[name="amorpm2"] { width:auto; display:inline-block; margin-right:4px; }
/* bootstrap-2 typeahead dropdown, used by Requested By / Reported By */
.ta-panel .typeahead { width:100%; }
.typeahead.dropdown-menu { z-index:100001; font-size:12px; }

/* Legacy link classes still referenced by permission flags */
a.Llink:link { color:#FF0000; } a.Llink:visited { color:black; } a.Llink:hover { color:Orange; } a.Llink:active { color:#0000FF; }
a.LEdit:visited { color:blue; } a.LDel:visited { color:red; }
.alink a.disabled { color:#666; text-decoration:none; }

/* ── @compare -- two-date layout: A and B side by side, one pane each.
      Each pane is half the screen, so the table inside is compacted (see
      "compact table" below) to fit a 1920px screen without scrolling; on a
      narrower screen each pane scrolls sideways on its own. Only on a phone-
      width screen do the panes stack. ── */
.ops-section h2 .ops-section-note { font-size:10.5px; font-weight:500; letter-spacing:.2px; text-transform:none; color:var(--mut); margin-left:8px; }
/* The two bars share grid row 1 and the two tables share row 2, so the bars
   are always the same height and the tables always start level, whatever
   wraps in either bar. display:contents lets each section's bar and table be
   placed in the grid directly; the divider between A and B is a 1px line
   drawn down the middle of the grid. */
.cmp-grid         { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); grid-template-rows:auto auto auto auto; align-items:start; padding:8px 0 6px;
                    background:linear-gradient(var(--line),var(--line)) center top / 1px 100% no-repeat; }
.cmp-section      { display:contents; }
.cmp-section--a > * { grid-column:1; min-width:0; }
.cmp-section--b > * { grid-column:2; min-width:0; }
.cmp-bar          { grid-row:1; align-self:stretch; align-content:center; display:flex; align-items:center; gap:8px 14px; flex-wrap:wrap; margin:0 8px; padding:8px 10px; background:#fff; border:1px solid var(--line); border-left-width:4px; border-radius:6px; }
.cmp-grid .ops-table-wrap { grid-row:4; margin:8px 8px 14px; }
/* @chart -- row 3, across both columns: below the peak tiles, above the
   tables. Its white card covers the A | B divider drawn behind the grid. */
.cmp-chart        { grid-row:3; grid-column:1 / -1; position:relative; margin:8px 8px 0; padding:10px 14px 8px; background:#fff; border:1px solid var(--line); border-radius:6px; }
.cmp-chart-head   { display:flex; align-items:center; justify-content:space-between; gap:8px 18px; flex-wrap:wrap; margin-bottom:2px; }
.cmp-chart-head h3{ margin:0; font-size:12px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--rail); }
.cmp-chart-sub    { font-size:10.5px; font-weight:500; letter-spacing:.2px; text-transform:none; color:var(--mut); margin-left:6px; }
.cmp-chart-legend { display:flex; gap:6px 18px; flex-wrap:wrap; }
.cmp-chart-key    { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:var(--ink); white-space:nowrap; }
.cmp-chart-key b  { font-weight:700; }
.cmp-chart-n      { color:var(--mut); }
.cmp-chart-svg    { display:block; width:100%; height:auto; overflow:visible; }
.cmp-chart-svg .cmp-band--am { fill:#FDB813; fill-opacity:.10; }
.cmp-chart-svg .cmp-band--pm { fill:#6D5BD0; fill-opacity:.08; }
.cmp-chart-svg .cmp-band-lbl { font:600 11px var(--ta-sans); fill:var(--mut); letter-spacing:.5px; text-transform:uppercase; }
.cmp-chart-svg .cmp-grid-y   { stroke:#E6EDF5; stroke-width:1; }
.cmp-chart-svg .cmp-midnight { stroke:#9AA6B6; stroke-width:1; stroke-dasharray:3 4; }
.cmp-chart-svg .cmp-ax       { font:11px var(--ta-sans); fill:var(--mut); font-variant-numeric:tabular-nums; }
.cmp-chart-svg .cmp-ax--note { font-size:11px; font-style:italic; }
.cmp-chart-note   { font-size:10.5px; color:var(--mut); margin-top:2px; }
.cmp-chart-empty  { font-size:12px; font-style:italic; color:var(--mut); padding:26px 0; text-align:center; }
/* @peak -- the tiles take grid row 2, between the bars and the tables, so
   A's and B's tiles (and the tables under them) stay level too */
.peak-tiles       { grid-row:2; display:grid; grid-template-columns:1fr 1fr; gap:8px; margin:8px 8px 0; }
.peak-tile        { background:#fff; border:1px solid var(--line); border-top:3px solid var(--rail); border-radius:6px; padding:8px 12px; min-width:0; }
.peak-tile--am    { border-top-color:var(--gold); }
.peak-tile--pm    { border-top-color:var(--c-skipping); }
.peak-head        { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.peak-head i      { font-size:15px; color:var(--mut); }
.peak-tile--am .peak-head i { color:#B07F00; }
.peak-tile--pm .peak-head i { color:var(--c-skipping); }
.peak-label       { font-size:10.5px; font-weight:700; letter-spacing:.6px; text-transform:uppercase; color:var(--ink); }
.peak-window      { font-size:10.5px; color:var(--mut); font-variant-numeric:tabular-nums; }
.peak-body        { display:flex; align-items:baseline; gap:5px; margin-top:3px; flex-wrap:wrap; }
.peak-num         { font-size:24px; font-weight:700; line-height:1.1; color:var(--ink); font-variant-numeric:tabular-nums; }
.peak-unit        { font-size:11px; color:var(--mut); }
.peak-share       { font-size:10.5px; color:var(--mut); margin-top:1px; }

/* compact table -- half-width panes. Same cells and blocks as before, with
   tighter padding, a 12px base and no per-cell minimum widths. The Car
   column, status pill and switch trail are gone from this page entirely. */
.cmp-grid table.train_ava    { min-width:560px; }
.cmp-grid table.train_ava th { padding:7px 6px; font-size:10px; letter-spacing:.3px; line-height:1.25; }
.cmp-grid table.train_ava td { padding:6px 6px; font-size:12px; }
.cmp-grid td.idx-cell        { min-width:0; padding-left:10px !important; }
.cmp-grid .idx-num           { font-size:16px; }
.cmp-grid .ta-slot-cell      { min-width:0; padding:6px 6px !important; }
.cmp-grid .skip-item         { padding:4px 6px; }
/* "+ Loading (if separate)" keeps its legacy wording; it may wrap onto two
   lines inside its fixed-height block rather than widen the column */
.cmp-grid .skip-item--action { white-space:normal; text-align:center; }
.cmp-grid .skip-item--action .ta-act { line-height:1.3 !important; }
.cmp-section--a .cmp-bar { border-left-color:var(--rail); }
.cmp-section--b .cmp-bar { border-left-color:var(--gold); }
.cmp-tag          { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:4px; font-weight:700; font-size:13px; flex:none; }
.cmp-tag--a       { background:var(--rail); color:#fff; }
.cmp-tag--b       { background:var(--gold); color:var(--gold-ink); }
.cmp-when         { display:flex; flex-direction:column; line-height:1.25; min-width:132px; }
.cmp-date         { font-size:13.5px; font-weight:700; color:var(--ink); }
.cmp-day          { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:var(--mut); }
.cmp-nav          { display:flex; align-items:center; gap:4px; flex-wrap:wrap; }
.cmp-nav form     { margin:0; padding:0; display:inline-flex; align-items:center; gap:4px; }
.cmp-bar .ops-nav-btn       { color:var(--rail); background:#fff; border-color:var(--line); }
.cmp-bar .ops-nav-btn:hover { background:var(--rail-wash); border-color:var(--rail); }
.cmp-follow       { font-size:10.5px; font-weight:600; color:var(--rail); text-decoration:none; white-space:nowrap; }
.cmp-follow:hover { text-decoration:underline; }
.cmp-follow--on   { color:var(--mut); font-weight:500; font-style:italic; }
.cmp-follow--on:hover { text-decoration:none; }
/* counts always take their own line, in both bars, so the bars are the same
   height and the A and B tables start level (B's "vs A" figures would
   otherwise wrap its bar alone) */
.cmp-stats        { flex-basis:100%; display:flex; gap:14px; flex-wrap:wrap; padding-left:38px; }
.cmp-stat         { font-size:11px; color:var(--mut); white-space:nowrap; }
.cmp-stat b       { font-size:14px; color:var(--ink); font-variant-numeric:tabular-nums; margin-right:2px; }
.cmp-delta        { margin-left:5px; font-size:10px; font-weight:600; color:#6B5A00; background:#FFF4CC; border-radius:8px; padding:0 6px; }
/* one row per skip: the train's cars sit together in one cell */
table.train_ava td.tc-car-cell { vertical-align:top !important; }
.tc-cars          { display:flex; flex-wrap:wrap; gap:3px; justify-content:center; }
.tc-cars .tc-car  { font-size:12px; padding:1px 6px; min-width:0; }
.tc-cars .tc-car:hover { background:var(--rail-wash); color:var(--rail); border-color:#C5D8EE; }
/* hovering a skip lightly marks the same train's other skips in that table */
tr.tr-sib td      { background:#F3F8FD !important; }
tr.row-empty td   { text-align:center; color:var(--mut); font-style:italic; padding:22px 10px; background:#fff; }

@media (max-width:768px){
	.cmp-grid { display:block; background:none; }
	.cmp-section { display:block; margin-top:8px; }
	.cmp-chart { margin:8px 6px 0; }

	.ops-header, .ops-strip, .ops-section { padding-left:10px; padding-right:10px; }
	.ops-table-wrap { margin:8px 6px 12px; }
	.cmp-bar { margin:0 6px; }
	.cmp-stats { padding-left:0; }
	.ta-panel { width:100vw; max-width:100vw; }
}
</style>

<?php
/* @skip -- Requested By / Reported By suggestion lists, ported from the legacy
   page. Two changes: the lists are read through the page's existing $db rather
   than a second connection opened purely for this, and the two columns are
   queried separately. Legacy filtered on requested_by and then harvested
   reported_by from the same rows, so any STDO who had only ever reported a skip
   was missing from the Reported By list. */
$REQUESTED_BY=array(); $REPORTED_BY=array();
if(ccsSkipTableExists($db)){
	$q=$db->query("select distinct requested_by from skipping where requested_by is not null and requested_by<>'' order by requested_by");
	if($q){ while($r=$q->fetch_assoc()){ $REQUESTED_BY[]=$r['requested_by']; } }
	$q=$db->query("select distinct reported_by from skipping where reported_by is not null and reported_by<>'' order by reported_by");
	if($q){ while($r=$q->fetch_assoc()){ $REPORTED_BY[]=$r['reported_by']; } }
}
$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
if(defined('JSON_UNESCAPED_UNICODE')) $jsonFlags = $jsonFlags | JSON_UNESCAPED_UNICODE;
?>

<script language='javascript' src='ajax.js'></script>
<script language="javascript">
/* ── All JS from train_availability.php. Only the container changed:
      openPanel()/closePanel() replace $('#addModal').modal(), and the
      switch add/delete handlers write chips instead of <td> cells. ── */
var driverHTML="";

var REQUESTED_BY = <?= json_encode($REQUESTED_BY,$jsonFlags) ?>;
var REPORTED_BY  = <?= json_encode($REPORTED_BY,$jsonFlags) ?>;

function setHTML(){ makeajax("processing.php?trainDriver=Y","getDriver"); }
function setPH(){ makeajax("processing.php?ph_trams=Y","getPhTrams"); }
function setSchool(){ makeajax("processing.php?supDriver=Y","getSchoolDriver"); }
/* @skip -- same endpoint the legacy skip page used; nothing new is needed in
   processing.php for any of this. */
function setSkipping(){ makeajax("processing.php?skipLocation=Y","getSkipLocation"); }

function getDriver(ajaxHTML){
	if(ajaxHTML=="None available") return;
	driverHTML="<select name='train_driver' id='train_driver'>";
	var driverTerms=ajaxHTML.split("==>");
	var count=(driverTerms.length)*1-1;
	
	driverHTML+="<option></option>";	
	for(var n=0;n<count;n++){
		var parts=driverTerms[n].split(";");
		driverHTML+="<option value='"+parts[0]+"'>"+parts[1].replace("_ENYE_","?")+"</option>";
	}
	driverHTML+="</select>";
	var el=document.getElementById('td');
	if(el) el.innerHTML=driverHTML;
	/* @skip -- the legacy page carried a near-duplicate getDriver3() whose only
	   job was to mark one <option selected> when a skip's loading form reopened.
	   applyPrefill() does that for every control on every form, so the clone is
	   gone. */
	applyPrefill();
}

function getSchoolDriver(ajaxHTML){
	if(ajaxHTML=="None available") return;
	driverHTML="<select name='train_driver' id='train_driver'>";
	var driverTerms=ajaxHTML.split("==>");
	var count=(driverTerms.length)*1-1;
	for(var n=0;n<count;n++){
		var parts=driverTerms[n].split(";");
		driverHTML+="<option value='"+parts[0]+"'>"+parts[1].replace("_ENYE_","?")+"</option>";
	}
	driverHTML+="</select>";
	document.getElementById('school_tag').innerHTML=driverHTML;
}

function getPhTrams(ajaxHTML){
	if(ajaxHTML=="None available") return;
	driverHTML="<select name='unimog_train_driver' id='unimog_train_driver'>";
	var driverTerms=ajaxHTML.split("==>");
	var count=(driverTerms.length)*1-1;
	for(var n=0;n<count;n++){
		var parts=driverTerms[n].split(";");
		driverHTML+="<option value='"+parts[0]+"'>"+parts[1]+"</option>";
	}
	driverHTML+="<option value='other'>Other</option></select>";
	driverHTML+="<input style='border:1px solid gray' type=text name='unimog_td_alternate' />";
	document.getElementById('ph_trams_tag').innerHTML=driverHTML;
}

/* @skip -- departure locations (departure_location: stations + turnbacks +
   I & R area). Served by processing.php, so it is not the hardcoded 1-12 list
   the arrival/loading pickers use. */
function getSkipLocation(ajaxHTML){
	if(ajaxHTML=="None available") return;
	var h="<select name='skip_loc' id='skip_loc'>";
	var terms=ajaxHTML.split("==>");
	var count=(terms.length)*1-1;
	for(var n=0;n<count;n++){
		var parts=terms[n].split(";");
		h+="<option value='"+parts[0]+"'>"+parts[1]+"</option>";
	}
	h+="</select>";
	var el=document.getElementById('skip_location');
	if(el) el.innerHTML=h;
	applyPrefill();
}

/* @skip -- the 12-station list, which the legacy page spelled out twice (once
   for Arrived Location, once for Loading Location) on top of a third copy in
   the Inserted To picker. Values and labels are verbatim, "Ayale Ave." typo and
   all, so this page keeps agreeing with the other two copies; fix all three or
   none. */
var OPS_STATIONS=[[1,'North Ave.'],[2,'Quezon Ave.'],[3,'GMA Kamuning'],[4,'Araneta - Cubao'],
	[5,'Santolan'],[6,'Ortigas'],[7,'Shaw Boulevard'],[8,'Boni Avenue'],[9,'Guadalupe'],
	[10,'Ayale Ave.'],[11,'Magallanes'],[12,'Taft Ave.']];
function stationSelect(name){
	var h="<select name='"+name+"' id='"+name+"'>";
	for(var i=0;i<OPS_STATIONS.length;i++) h+="<option value='"+OPS_STATIONS[i][0]+"'>"+OPS_STATIONS[i][1]+"</option>";
	return h+"</select>";
}
function boundSelect(){
	return "<select name='bound' id='bound'><option value='NB'>Northbound</option><option value='SB'>Southbound</option></select>";
}

/* ── Prefill ──────────────────────────────────────────────────────────────
   @skip -- new, and the reason it exists: in the legacy page, Edit on an
   existing skip opened the SAME blank form as "+ Skip" and posted every field
   over the stored row, so correcting one time blanked the driver, the
   requesting STDO, the incident number and the arrival. formPrefill is a flat
   map of control name -> value; applyPrefill() is idempotent and is called
   again by each AJAX callback, because the driver and departure-location
   selects do not exist yet when the form is first written.               */
var formPrefill=null;

function applyPrefill(){
	if(!formPrefill) return;
	var form=document.getElementById('add_form');
	if(!form) return;
	for(var k in formPrefill){
		if(!formPrefill.hasOwnProperty(k)) continue;
		var v=formPrefill[k];
		if(v===null||v===undefined||v==="") continue;
		var el=form.querySelector('[name="'+k+'"]');
		if(el) el.value=v;
		/* a value with no matching <option> simply leaves the select unset,
		   which is the legacy behaviour anyway */
	}
}

/* "2026-11-04 06:56" -> the six select values for that group ('' or '2') */
function prefillDate(map,sfx,dt){
	if(!dt) return;
	var m=/^(\d{4})-(\d{2})-(\d{2})[ T](\d{1,2}):(\d{2})/.exec(dt);
	if(!m) return;
	var h24=parseInt(m[4],10);
	var ap=(h24>=12)?'pm':'am';
	var h=h24%12; if(h===0) h=12;
	map['year'+sfx]   = m[1];
	map['month'+sfx]  = String(parseInt(m[2],10));
	map['day'+sfx]    = String(parseInt(m[3],10));
	map['hour'+sfx]   = String(h);
	map['minute'+sfx] = String(parseInt(m[5],10));
	map['amorpm'+sfx] = ap;
}

function setDate(){
	var cell=document.getElementById('cell');
	if(!cell) return;   /* editIndex / editCar have no time field; the unguarded
	                       lookup threw there and killed the rest of this call */
	var d=new Date();
	var year=d.getFullYear(), mmonth=d.getMonth()*1+1, day=d.getDate();
	var tentativehour=d.getHours(), minute=d.getMinutes(), hour=0, amorpm="AM";
	if(tentativehour==0){ hour=12; amorpm="AM"; }
	else if(tentativehour>12){ hour=tentativehour-12; amorpm="PM"; }
	else { hour=tentativehour; amorpm="AM"; }
	var months=["January","February","March","April","May","June","July","August","September","October","November","December"];
	dateHTML="<select name='month' id='month'><option></option>";
	for(var i=1;i<=12;i++) dateHTML+="<option value='"+i+"'"+(mmonth==i?" selected":"")+">"+months[i-1]+"</option>";
	dateHTML+="</select>";
	dateHTML+="<select name='day' id='day'><option></option>";
	for(var i=1;i<=31;i++) dateHTML+="<option value='"+i+"'"+(day==i?" selected":"")+">"+i+"</option>";
	dateHTML+="</select>";
	yearLimit=year*1+16;
	dateHTML+="<select name='year' id='year'><option></option>";
	for(var i=1999;i<=yearLimit;i++) dateHTML+="<option value='"+i+"'"+(year==i?" selected":"")+">"+i+"</option>";
	dateHTML+="</select><br>";
	dateHTML+="<select name='hour'><option></option>";
	for(var i=1;i<=12;i++) dateHTML+="<option value='"+i+"'"+(hour==i?" selected":"")+">"+i+"</option>";
	dateHTML+="</select>";
	dateHTML+="<select name='minute'><option></option>";
	for(var i=0;i<=59;i++){ var label=(i<10)?"0"+i:i; dateHTML+="<option value='"+i+"'"+(minute==i?" selected":"")+">"+label+"</option>"; }
	dateHTML+="</select>";
	dateHTML+="<select name='amorpm'><option></option>";
	dateHTML+="<option value='am'"+(amorpm=="AM"?" selected":"")+">AM</option>";
	dateHTML+="<option value='pm'"+(amorpm=="PM"?" selected":"")+">PM</option>";
	dateHTML+="</select>";
	cell.innerHTML=dateHTML;
}

/* @skip -- second time widget (Arrived Time), the "2" group of field names.
   Ported from the dateHTML2 half of the legacy setDate(), where the minute
   loop wrote its `selected` into dateHTML instead of dateHTML2 -- so the
   arrival minute was never preselected and the departure markup got a stray
   token appended after it had already been written to the DOM. */
function setDate2(){
	var cell=document.getElementById('cell3');
	if(!cell) return;
	var d=new Date();
	var year=d.getFullYear(), mmonth=d.getMonth()*1+1, day=d.getDate();
	var t=d.getHours(), minute=d.getMinutes(), hour=0, amorpm="AM";
	if(t==0){ hour=12; amorpm="AM"; }
	else if(t>12){ hour=t-12; amorpm="PM"; }
	else { hour=t; amorpm="AM"; }
	var months=["January","February","March","April","May","June","July","August","September","October","November","December"];
	var h="<select name='month2' id='month2'><option></option>";
	for(var i=1;i<=12;i++) h+="<option value='"+i+"'"+(mmonth==i?" selected":"")+">"+months[i-1]+"</option>";
	h+="</select>";
	h+="<select name='day2' id='day2'><option></option>";
	for(var i=1;i<=31;i++) h+="<option value='"+i+"'"+(day==i?" selected":"")+">"+i+"</option>";
	h+="</select>";
	var yearLimit2=year*1+16;
	h+="<select name='year2' id='year2'><option></option>";
	for(var i=1999;i<=yearLimit2;i++) h+="<option value='"+i+"'"+(year==i?" selected":"")+">"+i+"</option>";
	h+="</select><br>";
	h+="<select name='hour2'><option></option>";
	for(var i=1;i<=12;i++) h+="<option value='"+i+"'"+(hour==i?" selected":"")+">"+i+"</option>";
	h+="</select>";
	h+="<select name='minute2'><option></option>";
	for(var i=0;i<=59;i++){ var label=(i<10)?"0"+i:i; h+="<option value='"+i+"'"+(minute==i?" selected":"")+">"+label+"</option>"; }
	h+="</select>";
	h+="<select name='amorpm2'><option></option>";
	h+="<option value='am'"+(amorpm=="AM"?" selected":"")+">AM</option>";
	h+="<option value='pm'"+(amorpm=="PM"?" selected":"")+">PM</option>";
	h+="</select>";
	cell.innerHTML=h;
}

/* @skip -- Requested By / Reported By. Free text with a suggestion list built
   from the names already in the table, so the same STDO is not spelled three
   ways. data-provide only fires at page load, so the plugin is initialised by
   hand; if bootstrap's typeahead is not on the page the field degrades to a
   plain text input instead of throwing (the legacy page assumed it). */
function buildTypeahead(containerId,fieldName,source){
	var c=document.getElementById(containerId);
	if(!c) return;
	var input=document.createElement('input');
	input.type='text';
	input.name=fieldName;
	input.className='span6 typeahead';
	input.autocomplete='off';
	c.innerHTML='';
	c.appendChild(input);
	if(window.jQuery && $.fn && $.fn.typeahead){ $(input).typeahead({source:source,items:4}); }
	applyPrefill();
}
function setRequest(){ buildTypeahead('request','requested_by',REQUESTED_BY); }
function setReport(){ buildTypeahead('report','reported_by',REPORTED_BY); }

/* Panel titles per form type (panel header replaces the modal's static "Edit Record") */
var panelTitles={ add_train:"Add / Prep Train", unimog:"Add / Prep UNIMOG Train",
	insertion:"Add Insertion", removal:"Add Removal", remarks:"Add / Edit Remarks",
	index_switch:"Switch Index No.", editIndex:"Edit Index No.", editCar:"Edit Train Compo",
	skipping:"Skipping Departure", loading:"Skipping Loading" };

/* @skip -- skipId drives whether the skipping form INSERTs or UPDATEs. In the
   legacy page it was a global that nothing ever reset, so once an operator had
   edited one skip, every later "+ Skip" on any train still carried the hidden
   update_skipping field and overwrote that first skip instead of adding a new
   one. Here changeForm() clears it on every call unless the caller is one of
   the two edit entry points, which set keepSkipContext immediately before. */
var skipId="";
var keepSkipContext=false;

function changeForm(form_type,form_id,form_extra){
	if(!keepSkipContext){ skipId=""; formPrefill=null; }
	keepSkipContext=false;

	var htmlCode="";
	if(form_type=="insertion"){
		htmlCode="<table><tr><th colspan=2>Add Insertion</th></tr>";
		htmlCode+="<tr><td>Insertion Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td>";
		if(form_extra=="unimog") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="test") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="schooling") htmlCode+="<td id='school_tag' name='school_tag'></td>";
		else if(form_extra=="reserve") htmlCode+="<td><input type=text name='unimog_train_driver' /></td>";
		else { htmlCode+="<td id='td' name='td'></td>"; setHTML(); }
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Inserted To</td><td><select name='inserted_to' id='inserted_to'>";
		htmlCode+="<option value='north'>North Ave.</option>";
		htmlCode+="<option value='quezon'>Quezon Ave.</option>";
		htmlCode+="<option value='gma'>GMA Kamuning</option>";
		htmlCode+="<option value='cubao'>Araneta - Cubao</option>";
		htmlCode+="<option value='santolan'>Santolan</option>";
		htmlCode+="<option value='ortigas'>Ortigas</option>";
		htmlCode+="<option value='shaw'>Shaw Boulevard</option>";
		htmlCode+="<option value='boni'>Boni Avenue</option>";
		htmlCode+="<option value='guadalupe'>Guadalupe</option>";
		htmlCode+="<option value='ayala'>Ayale Ave.</option>";
		htmlCode+="<option value='magallanes'>Magallanes</option>";
		htmlCode+="<option value='taft'>Taft Ave.</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='insertion_id' id='insertion_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}
	else if(form_type=="removal"){
		htmlCode="<table><tr><th colspan=2>Add Removal</th></tr>";
		htmlCode+="<tr><td>Removal Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td>";
		if(form_extra=="unimog") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="test") htmlCode+="<td id='ph_trams_tag' name='ph_trams_tag'></td>";
		else if(form_extra=="reserve") htmlCode+="<td><input type=text name='unimog_train_driver' /></td>";
		else if(form_extra=="schooling") htmlCode+="<td id='school_tag' name='school_tag'></td>";
		else htmlCode+="<td id='td' name='td'></td>";
		htmlCode+="</tr>";
		htmlCode+="<tr><td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'><textarea name='remarks' cols=50></textarea></span>";
		htmlCode+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td></tr>";
		htmlCode+="<tr><td>Removed From</td><td><select name='removed_from' id='removed_from'>";
		htmlCode+="<option value='north'>North Ave.</option><option value='quezon'>Quezon Ave.</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><td>Add Incident?</td><td>";
		htmlCode+="<input type='checkbox' name='cancel_loop' id='cancel_loop' />Open Incident Report</td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remove_id' id='remove_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}
	else if(form_type=="index_switch"){
		htmlCode="<table><tr><th colspan=2>Switch Index No.</th></tr>";
		htmlCode+="<tr><td>New Index No.</td><td><input type=text id='new_index_input' /></td></tr>";
		htmlCode+="<tr><td>Time of Switch</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td><td id='td' name='td'></td></tr>";
		htmlCode+="<tr><td colspan=2 align=center><button type='button' onclick='submitSwitch("+form_id+")'>Submit</button></td></tr></table>";
	}
	else if(form_type=="timetable"){
		htmlCode="<table><tr><th colspan=2>Set Timetable</th></tr>";
		htmlCode+="<tr><td>New Index No.</td><td><input type=text id='new_index_input' /></td></tr>";
		htmlCode+="<tr><td>Time of Switch</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Train Driver</td><td id='td' name='td'></td></tr>";
		htmlCode+="<tr><td colspan=2 align=center><button type='button' onclick='submitSwitch("+form_id+")'>Submit</button></td></tr></table>";
	}
	
	/* @skip -- Skipping departure. Same field names the legacy page posted, so
	   the handlers above are the legacy handlers. Reordered into the sequence
	   the encoder actually works in: when and where it left, who drove, who
	   asked and who reported, then the arrival. */
	else if(form_type=="skipping"){
		var editing=(skipId!=="");
		htmlCode="<table><tr><th colspan=2>"+(editing?"Edit Skipping Departure":"Add Skipping Departure")+"</th></tr>";
		htmlCode+="<tr><td>Time of Departure</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Departure Location</td><td id='skip_location'></td></tr>";
		htmlCode+="<tr><td>Bound</td><td>"+boundSelect()+"</td></tr>";
		htmlCode+="<tr><td>Train Driver</td><td id='td' name='td'></td></tr>";
		htmlCode+="<tr><td>Requested By</td><td id='request'></td></tr>";
		htmlCode+="<tr><td>Reported By</td><td id='report'></td></tr>";
		htmlCode+="<tr><td>Incident Number</td><td><input type='text' name='incident_no' autocomplete='off' /></td></tr>";
		htmlCode+="<tr><td>Arrived Time</td><td id='cell3' name='cell3'></td></tr>";
		htmlCode+="<tr><td>Arrived Location</td><td>"+stationSelect('arrived_location')+"</td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		if(editing) htmlCode+="<input type=hidden name='update_skipping' id='update_skipping' value='"+skipId+"' />";
		htmlCode+="<input type=hidden name='departure_id' id='departure_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='"+(editing?"Save":"Add")+"' /></td></tr></table>";
	}
	/* @skip -- Loading, the second sitting. form_id is the skipping row's id,
	   not a train id. */
	else if(form_type=="loading"){
		htmlCode="<table><tr><th colspan=2>Skipping Loading</th></tr>";
		htmlCode+="<tr><td>Time of Loading</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td>Loading Location</td><td>"+stationSelect('loading_location')+"</td></tr>";
		htmlCode+="<tr><td>Bound</td><td>"+boundSelect()+"</td></tr>";
		htmlCode+="<tr><td>Train Driver<br>during Loading</td><td id='td' name='td'></td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='loading_id' id='loading_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Save' /></td></tr></table>";
	}
	
	else if(form_type=="editIndex"){
		htmlCode="<table><tr><th colspan=2>Edit Index No.</th></tr>";
		htmlCode+="<tr><td>New Index No.</td><td><input type=text name='edit_index' /></td></tr>";
		htmlCode+="<tr><td colspan=2><input type='submit' class='submit' value='Submit' /></td></tr>";
		htmlCode+="<tr><input type=hidden name='edit_id' id='edit_id' value='"+form_id+"' /></tr></table>";
	}
	else if(form_type=="editCar"){
		htmlCode="<table><tr><th colspan=2>Edit Car</th></tr>";
		htmlCode+="<tr><td>Car 1</td><td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 2</td><td><input type=text name='car_2' id='car_2' autocomplete='off' onblur='fillCar(\"mid\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 3</td><td><input type=text name='car_3' id='car_3' autocomplete='off' onblur='fillCar(\"last\",this.value)' /></td></tr>";
		/* item #1 fix carried over: Car 4 present so a 4-car set's car_d isn't blanked */
		htmlCode+="<tr><td>Car 4</td><td><input type=text name='car_4' id='car_4' autocomplete='off' onblur='fillCar(\"last2\",this.value)' /></td></tr>";
		htmlCode+="<tr><td colspan=2><input type='submit' class='submit' value='Submit' /></td></tr>";
		htmlCode+="<tr><input type=hidden name='edit_car' id='edit_car' value='"+form_id+"' /></tr></table>";
	}
	else if(form_type=="add_train"){
		htmlCode="<table><tr><th colspan=2>Add/Prep Train</th></tr>";
		htmlCode+="<tr><td>Type</td><td><select name='type' id='type' onchange='setTrain(this.value)'>";
		htmlCode+="<option value='revenue'>Revenue Train</option><option value='reserve'>Reserve Train</option>";
		htmlCode+="<option value='schooling'>Schooling Train</option><option value='finance'>Finance Train</option>";
		htmlCode+="<option value='test'>Test Train</option></select></td></tr>";
		htmlCode+="<tr><td>Index No.</td><td id='index_tag' name='index_tag'><input type=text name='index_no' autocomplete='off' /></td></tr>";
		htmlCode+="<tr><td>Car 1</td><td><input type=text name='car_1' id='car_1' autocomplete='off' onblur='fillCar(\"first\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 2</td><td><input type=text name='car_2' id='car_2' autocomplete='off' onblur='fillCar(\"mid\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 3</td><td><input type=text name='car_3' id='car_3' autocomplete='off' onblur='fillCar(\"last\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>Car 4</td><td><input type=text name='car_4' id='car_4' autocomplete='off' onblur='fillCar(\"last2\",this.value)' /></td></tr>";
		htmlCode+="<tr><td>LPAM No.</td><td><input type=text name='lpam_id' autocomplete='off' /></td></tr>";
		htmlCode+="<tr><th colspan=2><input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled</th></tr>";
		htmlCode+="<tr><td>I336 Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td align=center class='submit' colspan=2><input type='submit' value='Add' /></td></tr></table>";
	}
	else if(form_type=="unimog"){
		htmlCode="<table><tr><th colspan=2>Add/Prep Unimog Train</th></tr>";
		htmlCode+="<tr><td>Type of Train</td><td><select name='train_type'><option value='unimog'>UNIMOG</option></select></td></tr>";
		htmlCode+="<tr><td>Index No.</td><td><select name='other_index_no'>";
		for(var n=80;n<=89;n++) htmlCode+="<option value='"+n+"'>"+n+"</option>";
		htmlCode+="</select></td></tr>";
		htmlCode+="<tr><th colspan=2><input type=checkbox name='cancel_departure' id='cancel_departure' />Cancelled</th></tr>";
		htmlCode+="<tr><td>I336 Time</td><td id='cell' name='cell'></td></tr>";
		htmlCode+="<tr><td align=center colspan=2><input type='submit' class='submit' value='Add' /></td></tr></table>";
	}
	else if(form_type=="remarks"){
		htmlCode="<table><tr><th colspan=2>Add/Edit Remarks</th></tr>";
		htmlCode+="<tr><td>Remarks/Cause of <br>Failure/Removal</td>";
		htmlCode+="<td><span name='remarks_space' id='remarks_space'><textarea name='remarks' cols=50>"+form_extra+"</textarea></span>";
		htmlCode+="<input type=checkbox name='remarks_check' id='remarks_check' onclick='setPreset(this)' />Preset Values</td></tr>";
		htmlCode+="<tr><td colspan=2 class='submit' align=center>";
		htmlCode+="<input type=hidden name='remarks_id' id='remarks_id' value='"+form_id+"' />";
		htmlCode+="<input type=submit value='Submit' /></td></tr></table>";
	}
	document.getElementById('add_form').innerHTML=htmlCode;
	openPanel(panelTitles[form_type]||"Edit Record");   /* was: $('#addModal').modal('show'); */
	setDate();
	if(form_type=="removal"||form_type=="insertion"||form_type=="index_switch"){
		if(form_extra=="test"||form_extra=="unimog") setPH();
		else if(form_extra=="schooling") setSchool();
		else setHTML();
	}
	if(form_type=="skipping"){ setDate2(); setSkipping(); setHTML(); setRequest(); setReport(); }
	if(form_type=="loading"){ setHTML(); }
	applyPrefill();   /* the synchronous controls; the AJAX ones call it again */
}

/* @skip -- the two edit entry points. Both read the row's stored values off a
   data-skip attribute rather than taking them as a dozen onclick arguments,
   so a surname with an apostrophe in it cannot break the markup. */
function openSkipEdit(el){
	var d;
	try { d=JSON.parse(el.getAttribute('data-skip')); } catch(e){ return; }
	skipId=String(d.id);
	formPrefill={
		skip_loc:d.dep_loc, bound:d.dep_bound, train_driver:d.drv,
		requested_by:d.req, reported_by:d.rep, incident_no:d.inc,
		arrived_location:d.arr_loc
	};
	prefillDate(formPrefill,'',d.dep);
	prefillDate(formPrefill,'2',d.arr);
	keepSkipContext=true;
	changeForm('skipping',d.train,'skipping');
	return false;
}
function openLoadingForm(el){
	var d;
	try { d=JSON.parse(el.getAttribute('data-skip')); } catch(e){ return; }
	skipId="";
	formPrefill={
		loading_location:d.load_loc,
		bound:(d.load_bound?d.load_bound:d.dep_bound),
		/* an unencoded loading keeps the departure driver preselected, which is
		   what the legacy getDriver3() existed to do */
		train_driver:(d.load_drv?d.load_drv:d.drv)
	};
	prefillDate(formPrefill,'',d.load);   /* blank -> setDate() leaves "now" */
	keepSkipContext=true;
	changeForm('loading',d.id,'loading');
	return false;
}

function setPreset(check){
	var remarksHTML="";
	if(check.checked){
		remarksHTML="<select name='remarks' id='remarks'>";
		remarksHTML+="<option>AM Off-Peak Removal</option><option>PM Off-Peak Removal</option>";
		remarksHTML+="<option>Normal Removal</option><option>Emergency Removal</option>";
		remarksHTML+="<option>Give Way for Test Train</option><option>Give Way for Schooling Train</option>";
		remarksHTML+="<option>Give Way for Dalian Train</option><option>No Good Train Available</option>";

		remarksHTML+="</select>";
	} else {
		remarksHTML="<textarea name='remarks' cols=50></textarea>";
	}
	document.getElementById('remarks_space').innerHTML=remarksHTML;
}

function cancelTrain(train_id){
	if(confirm("Cancel Train?")) openIncidentPanel("cancel="+train_id,"Cancel Train");
}

function setTrain(train){
	var trainHTML="";
	switch(train){
		case "revenue":  trainHTML="<input type=text name='index_no' />"; break;
		case "schooling": trainHTML="<input type=text name='index_no' />"; break;
		case "finance":  trainHTML="<input type=text name='index_no' value='90' />"; break;
		case "reserve":
			trainHTML="<select name='index_no' id='index_no'>";
			for(var i=50;i<=69;i++) trainHTML+="<option value='"+i+"'>"+i+"</option>";
			trainHTML+="</select>"; break;
		case "test":
			trainHTML="<select name='index_no' id='index_no'>";
			for(var i=70;i<=79;i++) trainHTML+="<option value='"+i+"'>"+i+"</option>";
			trainHTML+="</select>"; break;
	}
	document.getElementById('index_tag').innerHTML=trainHTML;
}

/* Switch delete: same processing.php?deleteSwitch endpoint; removes the chip
   instead of rebuilding a <td> (the 7 Switch columns no longer exist). */
function deleteSwitch(index){
	if(!confirm("Cancel Switch?")) return;
	$.get('processing.php',{deleteSwitch:index},function(data){
		$('.sw-chip[data-switch-id="'+index+'"]').remove();
	});
}

/* Switch add: same processing.php?ajaxSwitch endpoint; appends a chip to the
   train's switch trail instead of replacing a placeholder <td>. */
function submitSwitch(train_ava_id){
	var new_index=document.getElementById('new_index_input').value;
	if(new_index==''){alert('Please enter a new index number.');return;}
	var driverEl=document.getElementById('train_driver');       /* populated by setHTML() into the 'td' cell */
	var train_driver=driverEl?driverEl.value:'';
	var driverLabel=driverEl?driverEl.options[driverEl.selectedIndex].text:'';
	var month=document.getElementById('month').value;
	var day=document.getElementById('day').value;
	var year=document.getElementById('year').value;
	var hour=parseInt($('select[name="hour"]').val());
	var minute=parseInt($('select[name="minute"]').val());
	var amorpm=$('select[name="amorpm"]').val();
	if(amorpm=='pm'&&hour<12) hour+=12;
	if(amorpm=='am'&&hour==12) hour=0;
	var hh=(hour<10)?'0'+hour:''+hour;
	var mm=(minute<10)?'0'+minute:''+minute;
	var switch_time=year+'-'+month+'-'+day+' '+hh+':'+mm;
	/* NOTE: passes train_driver alongside the existing params. processing.php's
	   ajaxSwitch handler must accept it and write it to train_switch.train_driver
	   (the display code already reads that column) -- confirm/update that handler,
	   it wasn't part of the files provided here. */
	$.get('processing.php',{ajaxSwitch:1,train_ava_id:train_ava_id,new_index:new_index,switch_time:switch_time,train_driver:train_driver},function(data){
		var res=JSON.parse(data);
		if(res.status=='ok'){
			closePanel();
			var newChip='<span class="sw-chip" data-switch-id="'+res.switch_id+'">'
				+'<span class="sw-idx">'+new_index+'</span>'
				+'<span class="sw-time">'+hh+':'+mm+'</span>'
				+'<span class="sw-drv">'+driverLabel+'</span>'
				+'<a class="ta-del-sw" href="#" onclick=\'deleteSwitch("'+res.switch_id+'")\' aria-label="Delete switch">&times;</a>'
				+'</span>';
			$('.sw-trail[data-train-id="'+train_ava_id+'"]').append(newChip);
		}
	});
}

function deleteRow(index){
	if(confirm("Remove Record?")) makeajax("processing.php?removeRow="+index,"reloadPage");
}

function reloadPage(ajaxHTML){ self.location="<?php echo $selfPage; ?>"; }

function fillCar(position,car){
	/* item #1 fix carried over: null-safe reads -- a modal missing one of the four
	   fields used to throw here, killing the duplicate check. */
	function carVal(id){ var el=document.getElementById(id); return ((el)?el.value:"")*1; }
	var car_a=carVal('car_1');
	var car_b=carVal('car_2');
	var car_c=carVal('car_3');
	var car_d=carVal('car_4');
	var field="car_1", counter=0;
	if(position=="first"){ field="car_1"; if(car!=""){ if(car==car_b)counter++; if(car==car_c)counter++; if(car==car_d)counter++; } }
	else if(position=="mid")  { field="car_2"; if(car!=""){ if(car==car_a)counter++; if(car==car_c)counter++; if(car==car_d)counter++; } }
	else if(position=="last") { field="car_3"; if(car!=""){ if(car==car_a)counter++; if(car==car_b)counter++; if(car==car_d)counter++; } }
	else if(position=="last2"){ field="car_4"; if(car!=""){ if(car==car_a)counter++; if(car==car_b)counter++; if(car==car_c)counter++; } }
	if(counter>0){ alert("Car already in Compo of Train!"); document.getElementById(field).value=""; }
	else { makeajax("processing.php?checkCar="+car+"&car="+field,"confirmCar"); }
	/* was: $('#addModal').modal('show'); -- the panel stays open, no re-show needed */
}
function openCarPanel(year,car,title,month,equipt=null){
	/* @slidepanel
	   car_stats.php reads $_GET['car_id'] — this sent &car=, so the iframe
	   loaded with no car at all and rendered an empty report. That is why the
	   panel looked broken even once it was sliding correctly.
	   Values are encoded: the title carries a colon and spaces. */
	equipt = (equipt===undefined || equipt===null||equipt=='') ? '' : equipt;

	month = (month===undefined || month===null) ? '' : month;
	title = title || "Car with Most Failures";
	//var q = "year="       + encodeURIComponent(year)
	var q=  "&car_id="    + encodeURIComponent(car); 
	     // + "&equipt="     + encodeURIComponent(equipt);

	      //+ "&month="     + encodeURIComponent(month)
	      //+ "&title="     + encodeURIComponent(title);

	document.getElementById('ir-panel-title').textContent=title;
	document.getElementById('irFallbackLink').href="car_stats.php?"+q; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);   /* was commented out: reopening left the previous
	                                timer running, which could flash the timeout
	                                fallback over an already-loaded frame */
	irExpectingLoad=true;
	frame.src="car_stats.php?"+q+"&embed=1";
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('irOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}
function confirmCar(ajaxHTML){
	if(ajaxHTML!="No car"){ alert("Car already in Compo of another Train!"); document.getElementById(ajaxHTML).value=""; }
}

/* ── Slide panel (replaces the Bootstrap modal) ── */
function openPanel(title){
	document.getElementById('ta-panel-title').textContent=title||"Edit Record";
	document.getElementById('taPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
}
function closePanel(){
	document.getElementById('taPanel').classList.remove('active');
	document.getElementById('taOverlay').classList.remove('active');
}
document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ closePanel(); closeIncidentPanel(); } });

/* ── Incident panel: hosts "incident report.php" in an iframe (embed=1
      discards Tmenu chrome). The page posts to itself inside the iframe;
      on save its cancel/add_incident branch postMessages 'ir-saved' and
      we reload so the new incident/levels show on the row.
      Loading state: the iframe fades in on load; if nothing's loaded after
      6s (blocked by a security header, 404'd filename, etc.) a fallback
      offers a direct link instead of leaving a blank panel. ── */
var irLoadTimer=null, irExpectingLoad=false, irNeedsReload=false;
function openIncidentPanel(query,title){
	var url="incident report.php?"+query+"&embed=1";
	document.getElementById('ir-panel-title').textContent=title||"Incident Report";
	document.getElementById('irFallbackLink').href="incident report.php?"+query; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad=true;
	frame.src=url;
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}
function openEditIncidentPanel(query,title){
	var url="edit_ccdr.php?ir="+query+"&editable=1&embed=1";
	document.getElementById('ir-panel-title').textContent=title||"Incident Report Details";
	document.getElementById('irFallbackLink').href="edit_ccdr.php?ir="+query; /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad=true;
	frame.src=url;
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}


function irFrameLoaded(){
	if(!irExpectingLoad) return; /* ignore the about:blank resets from closeIncidentPanel/initial markup */
	irExpectingLoad=false;
	clearTimeout(irLoadTimer);
	document.getElementById('irLoading').classList.add('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	document.getElementById('irFrame').classList.add('ready');
}
function closeIncidentPanel(){
	var p=document.getElementById('irPanel');
	if(!p) return;
	p.classList.remove('active');
	clearTimeout(irLoadTimer);
	irExpectingLoad=false;
	document.getElementById('irFrame').src="about:blank"; /* drop any half-filled form */
	if(!document.getElementById('taPanel').classList.contains('active'))
		document.getElementById('taOverlay').classList.remove('active');
	if(irNeedsReload){ irNeedsReload=false; self.location="<?php echo $selfPage; ?>"; } /* pick up field edits saved inside the panel */
}
window.addEventListener('message',function(e){
	if(e.data==='ir-saved'){ closeIncidentPanel(); self.location="<?php echo $selfPage; ?>"; }
	/* edit_ccdr.php (embed=1) announces each saved field edit with 'sp:saved'.
	   Unlike 'ir-saved' the panel stays open -- editing a CCDR is usually a
	   run of several field edits -- we just remember the table behind is now
	   stale; closeIncidentPanel() reloads once when the user is done. */
	if(e.data==='sp:saved'){ irNeedsReload=true; }
});

/* ── Date prev/today/next, per section. @compare -- takes the section key
      ('a' or 'b'); each section's hidden form posts its own field
      (search_date for A, search_date_b for B), so moving one date never
      touches the other. ── */
function navDate(sec,offset){
	var f=document.getElementById('dateNavForm_'+sec);
	if(!f) return;
	var d;
	if(offset==='today'){ d=new Date(); }
	else { d=new Date(f.getAttribute('data-date')+"T00:00:00"); d.setDate(d.getDate()+offset); }
	var mm=d.getMonth()+1, dd=d.getDate();
	f.querySelector('input[type="hidden"]').value=d.getFullYear()+"-"+(mm<10?"0"+mm:mm)+"-"+(dd<10?"0"+dd:dd);
	f.submit();
}

/* ── Filter pills (operations.php) — client-side only, no backend change ── */
function filterTrains(status,btn){
	var pills=document.querySelectorAll('.ops-pill');
	for(var i=0;i<pills.length;i++) pills[i].classList.remove('active');
	btn.classList.add('active');
	var rows=document.querySelectorAll('tr[data-train-id]');
	for(var j=0;j<rows.length;j++){
		/* @skipping -- Skipping is an attribute, not a data-status, so it is
		   tested against its own flag while every other pill still tests the
		   lifecycle status. A skipping train that has been removed appears
		   under BOTH pills, which is the point. */
		var hit = (status==='all')
			|| (status==='skipping'
				? rows[j].getAttribute('data-skipping')==='1'
				: rows[j].getAttribute('data-status')===status);
		rows[j].style.display = hit ? '' : 'none';
	}
}

$(function(){ $(".ops-date-input").datepicker({changeMonth:true,changeYear:true,showAnim:"clip"}); });
</script>

<body>
<div style="clear:both;height:0;font-size:0;line-height:0"></div>

<?php
/* =========================================================================
   @compare -- DATE RESOLUTION, now for two dates.

   A is the date this page always had: same POST field (search_date), same
   session key, same session month/day/year side effects, so any other page
   reading $_SESSION['search_date'] still sees what it always saw.

   B is new and lives under its OWN session key (skip_compare_date), so
   picking a comparison date here never moves the date on
   train_availability.php. Until B is picked it follows A (the day before A);
   once picked it stays put until "Follow A" clears it.

   The old block resolved A twice (an if/if pair, then an if/elseif/else
   cascade doing the same thing again); folded into one pass. One fix: Go
   pressed with the box empty used to strtotime("") into 1970-01-01. An empty
   post is now ignored and the current date is kept.
   ========================================================================= */
$availability_date_code = date("Y-m-d");
if(isset($_POST['search_date']) && trim($_POST['search_date'])!==""){
	$_SESSION['search_date'] = $_POST['search_date'];
}
if(isset($_SESSION['search_date'])){
	$tA = strtotime($_SESSION['search_date']);
	if($tA!==false){
		$availability_date_code = date("Y-m-d",$tA);
		$_SESSION['month'] = date("m",$tA);
		$_SESSION['day']   = date("d",$tA);
		$_SESSION['year']  = date("Y",$tA);
	}
}
$availability_date = date("F d, Y", strtotime($availability_date_code));
$dayOfWeek         = date("l",      strtotime($availability_date_code));

if(isset($_POST['reset_date_b'])){
	unset($_SESSION['skip_compare_date']);
} elseif(isset($_POST['search_date_b']) && trim($_POST['search_date_b'])!==""){
	$_SESSION['skip_compare_date'] = $_POST['search_date_b'];
}
$compare_pinned    = false;
$compare_date_code = date("Y-m-d", strtotime($availability_date_code." -1 day"));
if(isset($_SESSION['skip_compare_date'])){
	$tB = strtotime($_SESSION['skip_compare_date']);
	if($tB!==false){ $compare_date_code = date("Y-m-d",$tB); $compare_pinned = true; }
}

/* ── Permission flags (verbatim, including the toolbar override quirk) ── */
if($ULev>=2){
	$SRemove="Llink"; $SRemove2="two pull-left"; $SRemove3="liR grow";
	$SRemove4="LEdit"; $SRemove5="LDel";
} else {
	$SRemove="disabled"; $SRemove2="disabled"; $SRemove3="disabled";
	$SRemove4="disabled"; $SRemove5="disabled";
}
$SRemove4="enabled"; /* toolbar always enables add/unimog (original behavior) */

/* ── Timetable (verbatim lookup; its display is still commented out) ── */
$timeTableRS=db_query($db,"select *,timetable_day.id as timeId from timetable_day
	inner join timetable_code on timetable_day.timetable_code=timetable_code.id
	where train_date=?",array($availability_date_code));
if($timeTableRS->num_rows>0){
	$ttRow=$timeTableRS->fetch_assoc();
	$ttCode=$ttRow['code'];
	$ttLink='<a href=\'#\' onclick=\'window.open("timetable_set.php?reset='.$ttRow['timeId'].'","code","height=300,width=350")\'>Set/Reset</a>';
} else {
	$ttCode="______";
	$ttLink='<a href=\'#\' onclick=\'window.open("timetable_set.php?set=1","code","height=300,width=350")\'>Set/Reset</a>';
}

/* ── Header action buttons (same onclicks/gating; operations styling) ── */
if($SRemove!="disabled"){
//	$addTrainBtn='<a href=\'#\' class="ops-act" onclick=\'changeForm("add_train","","")\'><i class="ti ti-plus" aria-hidden="true"></i> Add Train</a>';
//	$unimogBtn  ='<a href=\'#\' class="ops-act" onclick=\'changeForm("unimog","","")\'>UNIMOG</a>';
} else {
//	$addTrainBtn='<span class="ops-act disabled">+ Add Train</span>';
//	$unimogBtn  ='<span class="ops-act disabled">UNIMOG</span>';
}

/* ── @compare -- both datasets are built before anything is printed, so B's
      summary can be stated against A's. ── */
$statsA = array(); $statsB = array();
$rowsA  = ccsBuildSkipRows($db, $availability_date_code, $SRemove, $statsA);
$rowsB  = ccsBuildSkipRows($db, $compare_date_code,      $SRemove, $statsB);
$sections = array(
	array('key'=>'a','label'=>'A','field'=>'search_date',  'date'=>$availability_date_code,'rows'=>$rowsA,'stats'=>$statsA,'vs'=>null),
	array('key'=>'b','label'=>'B','field'=>'search_date_b','date'=>$compare_date_code,     'rows'=>$rowsB,'stats'=>$statsB,'vs'=>$statsA),
);
?>

<div class="ta-ops">

<!-- ── PAGE HEADER ── -->
<div class="ops-header">
	<div class="ops-title">
		<h1>Train Operations</h1>
		<div class="sub">Skipping Log &mdash; Line 3 &middot; two-date comparison</div>
	</div>
	<div class="ops-actions">
		<?php if(isset($addTrainBtn)) echo $addTrainBtn; if(isset($unimogBtn)) echo $unimogBtn; ?>
		&nbsp;
	</div>
</div>

<!-- ── SECTION HEADER + layout toggle ── -->
<div class="ops-section">
	<h2><i class="ti ti-clipboard-list" aria-hidden="true"></i> Skipping Log
		<span class="ops-section-note">one row per skip &middot; latest departure first</span></h2>
</div>

<!-- ── @compare -- two sections, A and B, identical apart from the date they
        read. Each has its own prev/today/next + picker, posting its own field. ── -->
<div class="cmp-grid" id="cmpGrid">
<?php foreach($sections as $sec){
	$sd  = $sec['date'];
	$st  = $sec['stats'];
	$key = $sec['key'];
?>
<section class="cmp-section cmp-section--<?php echo $key; ?>">
	<div class="cmp-bar">
		<span class="cmp-tag cmp-tag--<?php echo $key; ?>"><?php echo $sec['label']; ?></span>
		<div class="cmp-when">
			<span class="cmp-date"><?php echo date("F d, Y",strtotime($sd)); ?></span>
			<span class="cmp-day"><?php echo date("l",strtotime($sd)); ?></span>
		</div>
		<div class="cmp-nav">
			<button type="button" class="ops-nav-btn" onclick="navDate('<?php echo $key; ?>',-1)" title="Previous day">&lsaquo;</button>
			<button type="button" class="ops-nav-btn" onclick="navDate('<?php echo $key; ?>','today')" title="Go to today">Today</button>
			<button type="button" class="ops-nav-btn" onclick="navDate('<?php echo $key; ?>',1)" title="Next day">&rsaquo;</button>
			<form action="<?php echo $selfPage; ?>" method="post">
				<input type="text" name="<?php echo $sec['field']; ?>" class="ops-date-input" placeholder="<?php echo $sd; ?>" autocomplete="off">
				<input type="submit" value="Go" class="ops-go">
			</form>
		</div>
		<?php if($key=='b'){
			if($compare_pinned){ ?>
			<a href="#" class="cmp-follow" onclick="document.getElementById('dateResetFormB').submit();return false;"
			   title="Stop pinning B and go back to the day before A"><i class="ti ti-link" aria-hidden="true"></i> Follow A</a>
		<?php } else { ?>
			<span class="cmp-follow cmp-follow--on" title="B moves with A until you pick a date here">following A &middot; day before</span>
		<?php } } ?>
		<div class="cmp-stats">
			<?php
			echo ccsStat($st['skips'],     'skips',          $sec['vs']!==null ? $sec['vs']['skips']     : null);
			echo ccsStat($st['trains'],    'trains',         $sec['vs']!==null ? $sec['vs']['trains']    : null);
			echo ccsStat($st['incidents'], 'with incident',  $sec['vs']!==null ? $sec['vs']['incidents'] : null);
			?>
		</div>
	</div>

	<!-- @peak -- AM / PM peak tiles, between the date bar and the table -->
	<div class="peak-tiles">
		<?php
		echo ccsPeakTile('am','AM Peak','04:00 &ndash; 09:00',          $st['am'],$st['skips'], $sec['vs']!==null ? $sec['vs']['am'] : null);
		echo ccsPeakTile('pm','PM Peak','15:00 &ndash; end of revenue', $st['pm'],$st['skips'], $sec['vs']!==null ? $sec['vs']['pm'] : null);
		?>
	</div>

	<div class="ops-table-wrap">
	<table class='train_ava'>
	<colgroup>
		<col style="width:9%"><col style="width:26%"><col style="width:11%">
		<col style="width:19%"><col style="width:22%"><col style="width:13%">
	</colgroup>
	<thead>
	<tr>
		<th>Index No.</th>
		<th>Departure</th>
		<th>Arrived</th>
		<th>Loaded</th>
		<th>Requested by /<br>Reported by</th>
		<th>Incident No.</th>
	</tr>
	</thead>
	<tbody>
	<?php echo $sec['rows']; ?>
	</tbody>
	</table>
	</div><!-- /.ops-table-wrap -->

	<!-- Hidden form used by this section's prev/today/next buttons -->
	<form id="dateNavForm_<?php echo $key; ?>" action="<?php echo $selfPage; ?>" method="post" data-date="<?php echo $sd; ?>" style="display:none">
		<input type="hidden" name="<?php echo $sec['field']; ?>" value="">
	</form>
</section>
<?php } ?>
<?php /* @chart -- one graph for both days, spanning A and B, between the
         peak tiles and the tables (grid row 3; see .cmp-chart). */
$chartToday = date("Y-m-d");
echo ccsSkipChart(array(
	array('key'=>'a','label'=>date("D d M",strtotime($availability_date_code)),
	      'hours'=>$statsA['hours'],'untimed'=>$statsA['untimed'],'is_today'=>($availability_date_code===$chartToday)),
	array('key'=>'b','label'=>date("D d M",strtotime($compare_date_code)),
	      'hours'=>$statsB['hours'],'untimed'=>$statsB['untimed'],'is_today'=>($compare_date_code===$chartToday))
)); ?>
</div><!-- /.cmp-grid -->

<form id="dateResetFormB" action="<?php echo $selfPage; ?>" method="post" style="display:none">
	<input type="hidden" name="reset_date_b" value="1">
</form>

</div><!-- /.ta-ops -->

<!-- ── SLIDE PANEL (operations.php pattern) — hosts the same #add_form the
        POST handlers expect; footer Submit uses form='add_form' like the
        original modal footer did. ── -->
<div class="ta-overlay" id="taOverlay" onclick="closePanel();closeIncidentPanel()"></div>
<div class="ta-panel" id="taPanel" role="dialog" aria-modal="true" aria-labelledby="ta-panel-title">
	<div class="ta-panel-head">
		<h3 id="ta-panel-title">Edit Record</h3>
		<button type="button" class="ta-panel-close" onclick="closePanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body">
		<form name='add_form' id='add_form' action='<?php echo $selfPage; ?>' method='post'></form>
	</div>
	<div class="ta-panel-foot">
		<span class="hint">Esc closes</span>
		<a href="#" class="btn" onclick="closePanel();return false;">Close</a>
		<button type='submit' form='add_form' class="btn btn-primary" id='Suc' value='Submit'>Submit</button>
	</div>
</div>

<!-- ── INCIDENT PANEL — same slide-panel pattern, body is an iframe onto
        "incident report.php?...&embed=1". All incident logic (equipment
        picker, links, presets, POST handling) stays in that file. ── -->
<div class="ta-panel ta-panel--ir" id="irPanel" role="dialog" aria-modal="true" aria-labelledby="ir-panel-title">
	<div class="ta-panel-head">
		<h3 id="ir-panel-title">Incident Report</h3>
		<button type="button" class="ta-panel-close" onclick="closeIncidentPanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body ta-panel-body--ir">
		<iframe id="irFrame" src="about:blank" title="Incident Report" onload="irFrameLoaded()"></iframe>
		<div class="ir-loading" id="irLoading">
			<div class="ir-spinner"></div>
			<span>Loading incident form&hellip;</span>
		</div>
		<div class="ir-fallback hidden" id="irFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The form may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="irFallbackLink" target="_blank" rel="noopener">Open Incident Report in a new tab &rarr;</a>
		</div>
	</div>
</div>

<script>
/* Slot/row hover. @compare -- rows are skips now, not trains: the hovered
   row gets tr-hover (and its Edit / + Skip actions), and the same train's
   other skips in the same table get a lighter tr-sib tint, so a train's
   skips can still be picked out without being grouped. */
function initSlotHover(){
	$(document).off("mouseenter.slot mouseleave.slot")
	.on("mouseenter.slot",".ta-slot-cell",function(){ $(this).addClass("td-hover"); })
	.on("mouseleave.slot",".ta-slot-cell",function(){ $(this).removeClass("td-hover"); })
	.on("mouseenter.slot","tr[data-train-id]",function(){
		var id=$(this).data("train-id");
		$(this).addClass("tr-hover");
		$(this).closest("table").find("tr[data-train-id='"+id+"']").not(this).addClass("tr-sib");
	})
	.on("mouseleave.slot","tr[data-train-id]",function(){
		$(this).removeClass("tr-hover");
		$(this).closest("table").find("tr.tr-sib").removeClass("tr-sib");
	});
}
$(document).ready(initSlotHover);
$(window).on('load',initSlotHover);
</script>
</body>
<script src="js/jquery-migrate-1.2.1.min.js"></script>
<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
<script src="js/jquery.ui.touch-punch.js"></script>
<script src="js/modernizr.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/date.js"></script>
<script src='js/form.js'></script>