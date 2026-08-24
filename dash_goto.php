<?php
/* =====================================================================
   dash_goto.php  --  date-aware handoff from the dashboard to the rest
   of the console.

   WHY THIS EXISTS
   ---------------
   train_operations.php, incident summary.php and their siblings scope
   themselves to one operating date held in the SESSION, written when
   the user POSTs that page's own date box.  None of them read a date
   from the query string.  So a plain

       <a href="train_operations.php?d=2026-08-07">

   silently lands the user on whatever date their session was already
   holding -- which is exactly the "awkward stopgap" this replaces.

   This file has NO Tmenu include and emits NO output before the
   redirect, so header() works.  It validates the date, writes the one
   session key the target pages read, and forwards.

   If a target page later grows a real GET date parameter, delete its
   line from $targets and link it directly -- nothing else changes.
   ===================================================================== */

if(session_id()==""){ @session_start(); }

if(file_exists(dirname(__FILE__)."/dash_data.php")){
	require_once(dirname(__FILE__)."/dash_data.php");
}

/* Fallback validator, so this file still works if dash_data is absent. */
if(!function_exists('dash_date')){
	function dash_date($v){
		if(is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)){
			$p=explode("-",$v);
			if(checkdate((int)$p[1],(int)$p[2],(int)$p[0])){ return $v; }
		}
		return date("Y-m-d");
	}
}
if(!defined('DASH_DATE_SESSION_KEY')){    define('DASH_DATE_SESSION_KEY',   'search_date'); }
if(!defined('DASH_DATE_SESSION_FORMAT')){ define('DASH_DATE_SESSION_FORMAT','m/d/Y'); }

/* ---------------------------------------------------------------------
   Whitelist.  A redirect target is NEVER taken from the request string
   itself -- only a key into this map, so ?to= can't be pointed anywhere.

   'scope' is what kind of period the target page works in, because they
   are NOT all the same:
     'day'    daily pages -- write DASH_DATE_SESSION_KEY (search_date)
     'none'   the page keeps NO period in the session; write nothing
              rather than mutate a key it will never read. The statistics
              reports are this case: they are POST-only, driven by a
              search_date2/search_date range, and touching search_date on
              the way there would leave a stale operating date behind for
              the next daily page the user opened. They receive their
              range through the forwarded sd/ed/range params instead.

   Adjust the filenames and the two session key names below to match.
   --------------------------------------------------------------------- */
$targets = array(
	'ops'       => array('page'=>'train_operations_admin.php',            'scope'=>'day'),
	'ava'       => array('page'=>'train_availability.php',          'scope'=>'day'),
	'incidents' => array('page'=>'incident_summary.php',            'scope'=>'day'),
	'ccdr'      => array('page'=>'ccdr_summary.php',                'scope'=>'day'),
	'daily'     => array('page'=>'daily_report.php',                'scope'=>'day'),
	'depot'     => array('page'=>'depot_insertion.php',             'scope'=>'day'),
	'hourly'    => array('page'=>'train_hourly.php',                'scope'=>'day'),
	'clearance' => array('page'=>'clearance_form.php',              'scope'=>'day'),
	'stats'     => array('page'=>'statistics_report_modified.php',  'scope'=>'none'),
	'carstats'  => array('page'=>'car_statistics_report.php',       'scope'=>'none')
);

$key = isset($_GET['to']) ? (string)$_GET['to'] : '';
if(isset($targets[$key])){
	$page  = $targets[$key]['page'];
	$scope = $targets[$key]['scope'];
} else {
	$page  = 'dashboard.php';
	$scope = 'none';
}

$date = dash_date(isset($_GET['d']) ? $_GET['d'] : date("Y-m-d"));
$ts   = strtotime($date);

/* Write the period the way THIS target expects to read it -- and only
   the keys it actually reads.  Writing search_date on the way to a
   monthly report would leave a stale date behind for the next daily
   page the user opens, from a link that never meant to set one. */
if($scope==='day'){
	$_SESSION[DASH_DATE_SESSION_KEY] = date(DASH_DATE_SESSION_FORMAT, $ts);
}

/* ---------------------------------------------------------------------
   Forwarded query params, for targets that take their period in the URL
   rather than the session.  sd/ed/range are the names statistics_report_
   modified.php already uses on its Generate Printout link.  Whitelisted:
   nothing else is passed through, so this cannot be used to smuggle
   arbitrary query data into a target page.
   --------------------------------------------------------------------- */
$fwd = array();
foreach(array('sd','ed','range') as $k){
	if(isset($_GET[$k]) && $_GET[$k]!==''){
		$fwd[] = rawurlencode($k).'='.rawurlencode((string)$_GET[$k]);
	}
}
$query = count($fwd) ? (strpos($page,'?')===false ? '?' : '&').implode('&',$fwd) : '';

/* Filenames with spaces ("incident summary.php") are not valid in a
   Location header or an href as-is. */
$page = str_replace(' ','%20',$page);

/* Optional anchor, e.g. a=tr-482 -> #tr-482.
   NOTE: the anchor only lands if the target page emits a matching id.
   On train_operations.php that is a one-line addition to the row tag:
       <tr id="tr-<?php echo $row['id']; ?>" ...>
   Until then the link still opens the right page on the right date; it
   just doesn't scroll. */
$frag = '';
if(isset($_GET['a']) && $_GET['a']!==''){
	$a = preg_replace('/[^A-Za-z0-9_\-]/','', (string)$_GET['a']);
	if($a!==''){ $frag = '#'.$a; }
}

/* Relative Location is fine here and avoids hard-coding the host. */
header("Location: ".$page.$query.$frag);

/* Belt and braces: if headers were already sent by an include, fall back
   to a meta refresh rather than leaving a blank page. */
echo '<!DOCTYPE html><meta charset="utf-8">';
echo '<meta http-equiv="refresh" content="0;url='.htmlspecialchars($page.$query.$frag,ENT_QUOTES,'UTF-8').'">';
echo '<p style="font:14px sans-serif;padding:20px">Opening '.htmlspecialchars($page,ENT_QUOTES,'UTF-8').'&hellip; ';
echo '<a href="'.htmlspecialchars($page.$query.$frag,ENT_QUOTES,'UTF-8').'">continue</a></p>';
exit;