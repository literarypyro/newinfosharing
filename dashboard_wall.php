<?php
if(session_id()==""){ session_start(); }

/* =====================================================================
   dashboard_wall.php  --  Layout A, the OCC wall monitor.

   Same data layer as dashboard.php, different job: one screenful, big
   type, nothing clickable, refreshes itself.  Always shows TODAY --
   a wall board with a date picker is a wall board someone leaves on
   the wrong day.

   Query string:
     ?refresh=0   disable the auto-refresh (handy while styling)
     ?refresh=60  override the interval in seconds
   ===================================================================== */

if(file_exists(dirname(__FILE__)."/dash_data.php")){
	require_once(dirname(__FILE__)."/dash_data.php");
}

if(!function_exists('dash_trains')){
	echo "<p style='font:14px sans-serif;padding:20px'>Dashboard data layer not found. Upload <code>dash_data.php</code> alongside this page.</p>";
	exit;
}

$view_date = date("Y-m-d");

$refresh = 30;
if(isset($_GET['refresh']) && ctype_digit((string)$_GET['refresh'])){
	$refresh = (int)$_GET['refresh'];
	if($refresh>0 && $refresh<10){ $refresh=10; }   /* floor, so a typo can't hammer the DB */
}

$trains     = dash_trains($view_date);
$fleet      = dash_fleet_counts($view_date);
$inc        = dash_incident_counts($view_date);
$incidents  = dash_incidents($view_date);
$insertions = dash_recent_insertions($view_date,4);

/* The wall feed interleaves insertions and incidents into one
   chronological stream -- the controller wants "what just happened",
   not two separate lists to reconcile. */
$feed=array();
foreach($insertions as $i){
	$feed[]=array('ts'=>$i['ts'],'lvl'=>'','text'=>'Index '.$i['index_no'].' inserted - '.$i['point']);
}
foreach($incidents as $r){
	$t=dash_ts(isset($r['incident_date'])?$r['incident_date']:'');
	if(!$t){ continue; }
	$desc=trim(preg_replace('/\s+/',' ',strip_tags((string)(isset($r['description'])?$r['description']:''))));
	if($desc===""){ $desc=dash_type_label(isset($r['incident_type'])?$r['incident_type']:''); }
	if(strlen($desc)>64){ $desc=substr($desc,0,64)."..."; }
	if($r['open']){ $desc.=' - unresolved'; }
	$feed[]=array('ts'=>$t,'lvl'=>$r['lvl'],'text'=>$desc);
}
usort($feed,'dash_cmp_ts_desc');
$feed=array_slice($feed,0,6);

$avail_pct = dash_pct($fleet['online'],$fleet['target']);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Line 3 &mdash; Operations</title>
<?php dash_styles('wall'); ?>
</head>
<body>
<?php
/* The nav is deliberately NOT included on the wall board: nothing there is
   meant to be clicked, and a menu bar is wasted rows on a monitor nobody
   touches.  Set DASH_WALL_NAV to true if you want it. */
if(defined('DASH_WALL_NAV') && DASH_WALL_NAV){ require("Tmenu_2.php"); }
?>
<div class="ds-wrap">

	<div class="ds-bar">
		<div>
			<h1>Line 3 &middot; operations</h1>
			<div class="ds-sub"><?php echo dash_h(date("D d M Y")); ?> &middot; <?php echo dash_h(date("H:i")); ?><?php if($refresh>0){ echo " &middot; refreshes every ".(int)$refresh."s"; } ?></div>
		</div>
	</div>

<?php if(!dash_ready()){ ?>
	<div class="ds-alert">No database connection &mdash; figures below are not live.</div>
<?php } ?>

	<!-- ============================ TILES ============================ -->
	<div class="ds-tiles">

		<div class="ds-tile"><span class="ds-rail <?php echo $avail_pct>=90?'f-ok':($avail_pct>=75?'f-warn':'f-bad'); ?>"></span><div class="ds-tile-body">
			<div class="ds-tile-label">Trains on line</div>
			<div><span class="ds-val"><?php echo (int)$fleet['online']; ?></span><span class="ds-den">/ <?php echo (int)$fleet['target']; ?></span></div>
			<div class="ds-meter"><i class="<?php echo $avail_pct>=90?'f-ok':($avail_pct>=75?'f-warn':'f-bad'); ?>" style="width:<?php echo (int)$avail_pct; ?>%"></i></div>
		</div></div>

		<div class="ds-tile"><span class="ds-rail <?php echo $fleet['boundary']?'f-warn':'f-ok'; ?>"></span><div class="ds-tile-body">
			<div class="ds-tile-label">Waiting at boundary</div>
			<div><span class="ds-val"><?php echo (int)$fleet['boundary']; ?></span><span class="ds-den">/ <?php echo (int)$fleet['target']; ?></span></div>
			<div class="ds-meter"><i class="<?php echo $fleet['boundary']?'f-warn':'f-ok'; ?>" style="width:<?php echo dash_pct($fleet['boundary'],$fleet['target']); ?>%"></i></div>
		</div></div>

		<div class="ds-tile"><span class="ds-rail <?php echo $inc['open']?'f-bad':($inc['failures']?'f-warn':'f-ok'); ?>"></span><div class="ds-tile-body">
			<div class="ds-tile-label">Incidents today</div>
			<div><span class="ds-val"><?php echo (int)$inc['total']; ?></span><span class="ds-den"><?php echo (int)$inc['open']; ?> open</span></div>
			<div class="ds-meter"><i class="<?php echo $inc['open']?'f-bad':'f-warn'; ?>" style="width:<?php echo dash_pct($inc['failures'],$inc['total']>0?$inc['total']:1); ?>%"></i></div>
		</div></div>

		<div class="ds-tile"><span class="ds-rail <?php echo $fleet['cancelled']?'f-bad':'f-ok'; ?>"></span><div class="ds-tile-body">
			<div class="ds-tile-label">Cancelled trainsets</div>
			<div><span class="ds-val"><?php echo (int)$fleet['cancelled']; ?></span><span class="ds-den">/ <?php echo (int)$fleet['total']; ?></span></div>
			<div class="ds-meter"><i class="<?php echo $fleet['cancelled']?'f-bad':'f-ok'; ?>" style="width:<?php echo dash_pct($fleet['cancelled'],$fleet['total']>0?$fleet['total']:1); ?>%"></i></div>
		</div></div>

	</div>

	<!-- ========================= STATUS BAND ========================= -->
<?php dash_status_band($view_date,true); ?>

	<!-- ============================ FLEET ============================ -->
	<div class="ds-card">
<?php if(!count($trains)){ ?>
		<div class="ds-empty">No train availability recorded for today yet.</div>
<?php } else { ?>
		<div class="ds-fleet">
<?php	foreach($trains as $t){
			$meta = dash_state_meta($t['state']);
			$tone = $t['revenue'] ? $meta[1] : 'mute';
?>
			<span class="ds-chip t-<?php echo $tone; ?>"><?php echo dash_h($t['index_no']); ?></span>
<?php	} ?>
		</div>
		<div class="ds-legend">
			<span><i class="ds-key f-ok"></i>On line <?php echo (int)$fleet['online']; ?></span>
			<span><i class="ds-key f-warn"></i>At boundary <?php echo (int)$fleet['boundary']; ?></span>
			<span><i class="ds-key f-info"></i>Removed <?php echo (int)$fleet['removed']; ?></span>
			<span><i class="ds-key f-bad"></i>Cancelled <?php echo (int)$fleet['cancelled']; ?></span>
			<span><i class="ds-key f-mute"></i>Non-revenue <?php echo (int)$fleet['nonrevenue']; ?></span>
		</div>
<?php } ?>
	</div>

	<!-- ============================= FEED ============================ -->
	<div class="ds-card">
		<h2>Recent activity</h2>
<?php if(!count($feed)){ ?>
		<div class="ds-empty">Nothing recorded yet today.</div>
<?php } else { ?>
		<ul class="ds-feed">
<?php	foreach($feed as $f){ ?>
			<li><span class="ds-time"><?php echo dash_h(date("H:i",$f['ts'])); ?></span>
				<span><?php if($f['lvl']!==""){ ?><span class="ds-badge t-<?php echo dash_level_tone($f['lvl']); ?>"><?php echo dash_h($f['lvl']); ?></span><?php } ?><?php echo dash_h($f['text']); ?></span></li>
<?php	} ?>
		</ul>
<?php } ?>
	</div>

</div>

<?php if($refresh>0){ ?>
<script>
(function(){
	var secs=<?php echo (int)$refresh; ?>, left=secs;
	setInterval(function(){
		if(document.hidden){ left=secs; return; }   /* don't reload an unwatched board */
		if(--left<=0){ location.reload(); }
	},1000);
})();
</script>
<?php } ?>
</body>
</html>