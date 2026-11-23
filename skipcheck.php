<?php
/* =========================================================================
   skipcheck.php -- one-off diagnostic for the app's AM / PM peak line.

   Put it in the SAME folder as the iss_api.php the app calls, open it in a
   browser, read the answers, then DELETE it. It changes nothing; it only
   reads. It prints no passwords or tokens, but it does show table and column
   names, so it should not stay on the server.

   Answers, in order:
     1. Is the iss_api.php beside it the new one (has the peak code)?
     2. Does the skip query the API runs actually work on this database?
        If not, MySQL's own error message.
     3. The same query with the new AM / PM columns.
     4. The skipping table's real column names, in case one is misnamed.
     5. The server's clock, which decides whether a peak has "started".
   ========================================================================= */
header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-store");
$dir = dirname(__FILE__);
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function ok($b){ return $b ? '<b style="color:#1D7A4E">YES</b>' : '<b style="color:#B3261E">NO</b>'; }
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Skip check</title>
<style>
body{font:15px/1.5 system-ui,sans-serif;margin:16px;color:#1A2233}
h2{font-size:16px;margin:22px 0 6px}
pre{background:#F2F4F8;padding:10px;border-radius:6px;white-space:pre-wrap;word-break:break-word;font-size:13px}
.note{color:#5A6275;font-size:13px}
</style></head><body>
<h1 style="font-size:19px">Skip check &middot; <?php echo h(date("Y-m-d H:i:s")); ?></h1>

<h2>1. Is iss_api.php the new version?</h2>
<?php
$api = $dir."/iss_api.php";
if(!file_exists($api)){
	echo "<p>".ok(false)." &mdash; no iss_api.php in this folder (".h($dir)."). Put this page next to the iss_api.php the app calls.</p>";
} else {
	$src = file_get_contents($api);
	$new = (strpos($src, 'iss_peak_value') !== false);
	echo "<p>Has the AM / PM peak code: ".ok($new)."</p>";
	echo "<p class='note'>File: ".h($api)."<br>Size: ".number_format(filesize($api))." bytes &middot; last changed ".h(date("Y-m-d H:i", filemtime($api)))."</p>";
	if(!$new){ echo "<p><b>This is the cause.</b> Replace this iss_api.php with the new one, then pull to refresh the app.</p>"; }
}
?>

<h2>2. Does the skip query work? (the one the tile's number needs)</h2>
<?php
$dd = $dir."/dash_data.php";
if(!file_exists($dd)){
	echo "<p>".ok(false)." &mdash; no dash_data.php in this folder, so the database cannot be reached from here.</p></body></html>";
	exit;
}
require_once($dd);
if(!function_exists('dash_db') || !function_exists('dash_ready') || !dash_ready()){
	echo "<p>".ok(false)." &mdash; dash_data.php could not connect to the database.</p></body></html>";
	exit;
}
$db = dash_db();
$d  = date("Y-m-d");
$e  = $db->real_escape_string($d);
$open = "(sk.loading_time is null or sk.loading_time='' or sk.loading_time='0000-00-00 00:00:00')";
$base = " from skipping sk inner join train_availability ta on ta.id = sk.tar_id"
      . " where date(ta.date)='".$e."' and ta.status='active'";

$q1 = "select count(*) as skips, count(distinct sk.tar_id) as trains,"
    . " sum(case when ".$open." then 1 else 0 end) as open_n".$base;
$r1 = @$db->query($q1);
if($r1 && ($row = $r1->fetch_assoc())){
	echo "<p>Works: ".ok(true)." &mdash; today ".(int)$row['skips']." skips on ".(int)$row['trains']." trains.</p>";
} else {
	echo "<p>Works: ".ok(false)." &mdash; <b>this is the cause.</b> The API falls back to a second count, which gives the tile its number but no AM / PM split. MySQL said:</p>";
	echo "<pre>".h($db->error ? $db->error : 'no row returned')."</pre>";
}
?>

<h2>3. The same query with the AM / PM columns</h2>
<?php
$q2 = "select count(*) as skips,"
    . " sum(case when ".$open." then 1 else 0 end) as open_n,"
    . " sum(case when sk.departure_time >= '".$e." 04:00:00' and sk.departure_time < '".$e." 09:00:00' then 1 else 0 end) as am_n,"
    . " sum(case when sk.departure_time >= '".$e." 15:00:00' then 1 else 0 end) as pm_n".$base;
$r2 = @$db->query($q2);
if($r2 && ($row = $r2->fetch_assoc())){
	echo "<p>Works: ".ok(true)." &mdash; AM peak ".(int)$row['am_n']." &middot; PM peak ".(int)$row['pm_n']." (so far today).</p>";
} else {
	echo "<p>Works: ".ok(false)." &mdash; MySQL said:</p><pre>".h($db->error ? $db->error : 'no row returned')."</pre>";
}
?>

<h2>4. Columns in the skipping table</h2>
<?php
$cols = array();
$rc = @$db->query("show columns from skipping");
if($rc){ while($c = $rc->fetch_assoc()){ $cols[] = $c['Field'].' ('.$c['Type'].')'; } }
echo $cols ? "<pre>".h(implode("\n", $cols))."</pre>" : "<p>Could not read them: ".h($db->error)."</p>";
$need = array('tar_id','departure_time','loading_time');
$names = array();
foreach($cols as $c){ $names[] = strtok($c, ' '); }
foreach($need as $n){ echo "<p class='note'>".h($n).": ".ok(in_array($n, $names))."</p>"; }
?>

<h2>5. Server clock</h2>
<p>PHP time now: <b><?php echo h(date("Y-m-d H:i")); ?></b> (timezone <?php echo h(date_default_timezone_get()); ?>).</p>
<p class="note">If this is not Manila time, a peak may show a dash when it has already started, or a count before it has.</p>

<p style="margin-top:26px"><b>Delete this file when you are done.</b></p>
</body></html>