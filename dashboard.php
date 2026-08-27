<?php
/* Session before any output -- the console header calls session_start().
   Tmenu_2.php itself is required further down, inside <body>: it emits the
   header markup and the nav list ONLY, never <!DOCTYPE>/<html>/<head>, so
   this page supplies its own document scaffolding. */
if(session_id()==""){ session_start(); }

/* =====================================================================
   dashboard.php  --  Layout B, the console home page.

   Workstation density: compact tiles with sparklines and week-on-week
   delta chips, the fleet strip, two feed cards, and a monthly trend
   band.  Date-parameterised, so it doubles as a historical day view.

   Depends only on dash_data.php (guarded).  Drop both files next to
   the other console pages and link it from Tmenu_2.php.
   ===================================================================== */

if(file_exists(dirname(__FILE__)."/dash_data.php")){
	require_once(dirname(__FILE__)."/dash_data.php");
}

/* Hard stop with a readable message rather than a blank page. */
if(!function_exists('dash_trains')){
	echo "<p style='font:14px sans-serif;padding:20px'>Dashboard data layer not found. Upload <code>dash_data.php</code> alongside this page.</p>";
	exit;
}

/* ---- selected operating date ------------------------------------- */
$view_date = dash_date(isset($_GET['d']) ? $_GET['d'] : (isset($_POST['d']) ? $_POST['d'] : date("Y-m-d")));
$is_today  = ($view_date === date("Y-m-d"));

/* ==========================================================================
   @dashlink -- Force the operating date onto the incident-summary links.

   incident_summary.php now honours ?d=<date> and clears any previously chosen
   range when it sees one. It only fires if the parameter actually arrives, and
   what dash_link() emits for the 'incidents' key is decided in dash_data.php,
   not here. Rather than depend on that, the date is appended at the call site.

   Safe if dash_link() already sends d=: PHP takes the LAST occurrence of a
   repeated query parameter, and both copies carry the same value. Safe if it
   sends some other name too -- that one is simply ignored downstream.

   The fragment has to be preserved. dash_link()'s third argument is an anchor
   (the per-incident links pass 'ir-<id>'), and naively concatenating would
   produce "#ir-12&d=..." -- the date swallowed into the fragment, never sent
   to the server. So the hash is split off, the parameter appended to the path
   and query, and the hash put back.

   Guarded on its own name so a later dash_data.php that defines the same
   helper wins without a redeclare fatal. */
if(!function_exists('dash_force_date')){
	function dash_force_date($url, $d){
		$url  = (string)$url;
		$hash = '';
		$h    = strpos($url, '#');
		if($h !== false){ $hash = substr($url, $h); $url = substr($url, 0, $h); }
		$url .= (strpos($url, '?') === false ? '?' : '&').'d='.rawurlencode($d);
		return $url.$hash;
	}
}

/* ---- data -------------------------------------------------------- */
$trains      = dash_trains($view_date);
$fleet       = dash_fleet_counts($view_date);
$inc         = dash_incident_counts($view_date);
$incidents   = dash_incidents($view_date);
/* @insertions -- No limit. The card scrolls instead of truncating, so the
   figure it shows is the day's whole insertion log rather than its first
   five. A 0 limit means "all" in dash_recent_insertions(). */
$insertions  = dash_recent_insertions($view_date,0);
$types       = dash_type_breakdown($view_date,6);
$months      = dash_month_series($view_date,DASH_TREND_MONTHS);

$sp_trains   = dash_spark_trains($view_date);
$sp_inc      = dash_spark_incidents($view_date);
$sp_cancel   = dash_spark_cancelled($view_date);

$d_trains    = dash_delta($sp_trains,$view_date);
$d_inc       = dash_delta($sp_inc,$view_date);
$d_cancel    = dash_delta($sp_cancel,$view_date);

$max_month   = 0;
foreach($months as $v){ if($v>$max_month){ $max_month=$v; } }

/* @grain -- Bucket size for the trend card, from ?g=.

   GUARDED on dash_trend_series() rather than assumed. Those helpers live in
   dash_data.php, and this console has already been bitten once by a page
   being newer than the include it depends on -- the lfilter hunt was exactly
   that. If dash_data.php is the older copy, $trend stays null and the card
   below falls back to the twelve-month version it has always drawn. No
   fatal, no blank card, no selector until the include catches up. */
$trend_grain = function_exists('dash_trend_grain')
	? dash_trend_grain(isset($_GET['g']) ? $_GET['g'] : 'month') : 'month';
$trend       = function_exists('dash_trend_series')
	? dash_trend_series($view_date,$trend_grain) : null;

/* Scaled over real counts only. A null bucket -- a period with no records at
   all -- has no height to contribute and must not drag the top of the scale
   down with it. */
$max_trend   = 0;
if($trend){ foreach($trend['counts'] as $v){ if($v!==null && $v>$max_trend){ $max_trend=$v; } } }

/* @tt -- The auth token, for URLs that do NOT pass through dash_goto.php.

   dash_goto.php appends tt to everything it redirects to, which is why the
   tiles and the fleet chips work. The slide panel is different: its iframe src
   is used verbatim, so nothing was adding the token and Tmenu.php's guard on
   train_detail.php bounced it to login. (edit_ccdr.php escapes this because
   its Tmenu require is commented out -- it is not that panels are exempt.)

   Taken from THIS request first. The dashboard is itself behind the guard, so
   whatever token got us here is by definition a working one, and a session
   that rotates its token keeps working with no edit. The literal is only a
   fallback for entry points that carry none -- it is the same value
   dash_goto.php already hardcodes, and the two should collapse into one
   constant when there is a natural home for it. */
$dsTT = (isset($_GET['tt']) && $_GET['tt'] !== '')
	? (string)$_GET['tt']
	: '2a7b85131d93ffbaacc73f7ff024b55a';
$max_type    = 0;
foreach($types as $v){ if($v>$max_type){ $max_type=$v; } }
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard &mdash; Line 3 Operations Console</title>
<?php dash_styles('console'); ?>
<!-- @dashdate -- structural CSS for the date widget. Not optional: jQuery UI
     relies on its stylesheet for the datepicker's display:none and width, so
     without this link the calendar renders inline and permanently open at the
     top of the page. datepicker_theme.php repaints it in console colours and
     is pulled in further down by dash_datepicker.php -- after this link, which
     is the order that makes the repaint win.

     The matching SCRIPT tags are NOT here. See the note below Tmenu_2.php. -->
<link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />

</head>
<body>
<?php require("Tmenu_2.php"); ?>
<?php

/* @dashdate -- console skin for the date control. Deliberately AFTER the nav

   include: if Tmenu_2.php links a jQuery UI stylesheet, anything emitted

   earlier loses to it on source order. Guarded so a station that has not

   received the file yet keeps the plain native field. */

if(file_exists(dirname(__FILE__)."/dash_datepicker.php")){ include(dirname(__FILE__)."/dash_datepicker.php"); }
?>
<!-- @dashdate -- jQuery UI loads HERE, after Tmenu_2.php, and this ordering is
     the whole fix. Tmenu_2.php emits its own <script> for jQuery 1.10.2. In the
     first attempt these two tags sat up in <head>: jquery-ui.js ran, attached
     .datepicker to the jQuery instance that existed at that moment, and then
     Tmenu_2's tag replaced window.jQuery with a fresh 1.10.2 that had never
     heard of it. The console said it plainly -- "jQuery 1.10.2 loaded but
     jquery-ui.js did not" -- which was true from where the check was standing.

     So: no jquery.js tag of our own. The page already has jQuery, loading a
     second copy is what caused this, and jQuery UI 1.11.1 is happy on 1.6+.
     Anything that loads jQuery again after this point will break it the same
     way, so this include stays last. -->
<script src="jquery-ui-1.11.1/jquery-ui.js"></script>
<?php

?>


<div class="ds-wrap">

	<div class="ds-bar">
		<div>
			<h1>Operations dashboard</h1>
		<div class="ds-sub"><?php echo dash_h(date("l, d F Y",strtotime($view_date))); ?><?php if($is_today){ echo " &middot; ".dash_h(date("H:i")); } ?></div>

		</div>
		<?php /* @selfpage -- Transplanted from the test build. Both of these named
		         dashboard.php outright, so a renamed copy submitted INTO the live
		         console and Today jumped away from it -- which made a side-by-side
		         test build untestable in the one way that matters. Resolved from
		         the actual filename now, the same pattern as $selfPage in
		         statistics_report_modified.php and $ohSelf in other_history.php. */
		   $dsSelf = basename(__FILE__);
		   /* @grain -- The trend card's bucket size, when this file has one. Guarded
		      with isset() so the same markup is correct before and after the trend
		      selector is merged: without it the form submits d alone, $_GET['g'] is
		      dropped and the card snaps back to Monthly on every date change. */
		   $dsGrain = ($trend && isset($trend_grain)) ? $trend_grain : ''; ?>
		<form method="get" action="<?php echo dash_h($dsSelf); ?>">
		<input class="ds-date" type="date" name="d" value="<?php echo dash_h($view_date); ?>">
			<?php if($dsGrain!==''){ ?><input type="hidden" name="g" value="<?php echo dash_h($dsGrain); ?>"><?php } ?>

			<button type="submit">Show</button>
			<?php /* Today means "same view, today", not "reset everything". */ ?>
			<?php if(!$is_today){ ?><a class="ds-today" href="<?php echo dash_h($dsSelf).($dsGrain!==''?'?g='.dash_h($dsGrain):''); ?>">Today</a><?php } ?>
		</form>
	</div>

<?php if(!dash_ready()){ ?>
	<div class="ds-alert">No database connection. Check <code>db_config.php</code> &mdash; every panel below will read empty until it resolves.</div>
<?php } ?>

	<!-- ============================ TILES ============================ -->
	<div class="ds-tiles">

		<a class="ds-tile" href="<?php echo dash_h(dash_link('opsline',$view_date,'',array('lfilter'=>'service'))); ?>" title="Open train operations for this date"><span class="ds-rail f-ok"></span><div class="ds-tile-body">
			<div class="ds-tile-label"><span class="ds-dot f-ok"></span>Inserted</div>
			<?php /* @merge -- denominator and day-on-day chip, restored from the
			         pre-integration build. $d_trains is still computed above; the
			         markup that displayed it had been dropped. A bare count with
			         nothing to compare it against is the one thing a dashboard
			         tile should never show. */ ?>
			<div><span class="ds-val"><?php echo (int)$fleet['online']; ?></span>
			</div>
			<?php echo dash_sparkline($sp_trains); ?>
		</div></a>

		<a class="ds-tile" href="<?php echo dash_h(dash_link('opsline',$view_date,'',array('lfilter'=>'reserve'))); ?>" title="Open train operations for this date"><span class="ds-rail f-warn"></span><div class="ds-tile-body">
			<div class="ds-tile-label"><span class="ds-dot f-warn"></span>Reserve</div>
			<div><span class="ds-val"><?php echo (int)$fleet['boundary']; ?></span>
			</div>
			<?php 

			/**
			<span class="ds-den">prepped</span>
			*/
			?>
			<div class="ds-meter"><i class="f-warn" style="width:<?php echo dash_pct($fleet['boundary'],$fleet['target']); ?>%"></i></div>
		</div></a>

		<a class="ds-tile" href="<?php echo dash_h(dash_link('opsline',$view_date,'',array('lfilter'=>'removed'))); ?>" title="Open train operations for this date"><span class="ds-rail f-info"></span><div class="ds-tile-body">
			<div class="ds-tile-label"><span class="ds-dot f-info"></span>Removed</div>
			<div><span class="ds-val"><?php echo (int)$fleet['removed']; ?></span>
			
			
			</div>
			<div class="ds-meter"><i class="f-info" style="width:<?php echo dash_pct($fleet['removed'],$fleet['target']); ?>%"></i></div>
		</div></a>

		<a class="ds-tile" href="<?php echo dash_h(dash_force_date(dash_link('incidents',$view_date),$view_date)); ?>" title="Open the incident summary for this date"><span class="ds-rail <?php echo $inc['failures']?'f-bad':'f-ok'; ?>"></span><div class="ds-tile-body">
			<div class="ds-tile-label"><span class="ds-dot <?php echo $inc['failures']?'f-bad':'f-ok'; ?>"></span>Incidents</div>
			<div><span class="ds-val"><?php echo (int)$inc['total']; ?></span>
			
			</div>
			<?php echo dash_sparkline($sp_inc); ?>
		</div></a>

		<a class="ds-tile" href="<?php echo dash_h(dash_link('opsline',$view_date,'',array('lfilter'=>'cancelled'))); ?>" title="Open train operations for this date"><span class="ds-rail <?php echo $fleet['cancelled']?'f-bad':'f-ok'; ?>"></span><div class="ds-tile-body">
			<div class="ds-tile-label"><span class="ds-dot <?php echo $fleet['cancelled']?'f-bad':'f-ok'; ?>"></span>Cancelled</div>
			<div><span class="ds-val"><?php echo (int)$fleet['cancelled']; ?></span>
			
			</div>
			<?php echo dash_sparkline($sp_cancel); ?>
		</div></a>

	</div>

	<!-- ========================= STATUS BAND ========================= -->
<?php
/* The band's items open edit_ccdr.php in the same iframe slide panel
   train_operations.php uses.  Loaded HERE rather than in dash_data.php
   so the wall board never pulls it in.  Guarded: without it the band's
   anchors behave as ordinary links to the same record.

   Prefer a real extracted slide_panel.php once it exists; dash_panel.php
   is the stand-in port and can then be deleted. */
if(file_exists(dirname(__FILE__)."/slide_panel.php")){
	include_once(dirname(__FILE__)."/slide_panel.php");
} else if(file_exists(dirname(__FILE__)."/dash_panel.php")){
	include_once(dirname(__FILE__)."/dash_panel.php");
}
dash_status_band($view_date,false);
?>

	<!-- ============================ FLEET ============================ -->
	<div class="ds-card">
		<div class="ds-card-head">
			<h2><a class="ds-more" style="font-size:inherit;color:inherit" href="<?php echo dash_h(dash_link('ops',$view_date)); ?>">Fleet</a></h2>
			<span class="ds-note"><?php echo (int)$fleet['total']; ?> record<?php echo $fleet['total']==1?'':'s'; ?><?php if($fleet['nonrevenue']){ echo " &middot; ".(int)$fleet['nonrevenue']." non-revenue"; } ?></span>
		</div>
<?php if(!count($trains)){ ?>
		<div class="ds-empty">No train availability recorded for this date.</div>
<?php } else { ?>
		<?php /* @traindetail -- id on the container so the click handler below can
		         delegate to it, the same way the incident feed does. */ ?>
		<div class="ds-fleet" id="ds-fleet">
<?php	foreach($trains as $t){
			$meta = dash_state_meta($t['state']);
			$tone = $t['revenue'] ? $meta[1] : 'mute';
			$tip  = $meta[0];
			if(dash_hm($t['insert_time'])!=""){ $tip.=" &middot; in ".dash_hm($t['insert_time']); }
			if(dash_hm($t['remove_time'])!=""){ $tip.=" &middot; out ".dash_hm($t['remove_time']); }
			if(!$t['revenue']){ $tip.=" &middot; ".$t['type']; }
?>
			<?php /* @traindetail -- href unchanged: it still points at the real
			         operations page, so middle-click, Ctrl-click, "open in new tab"
			         and a browser with JS off all behave exactly as before. The
			         panel is an ENHANCEMENT layered on top via data-panel, which is
			         the same contract the incident feed already uses -- if the
			         opener function is not present the click just follows the link. */ ?>
			<a class="ds-chip t-<?php echo $tone; ?>" title="<?php echo dash_h(strip_tags(str_replace('&middot;','-',$tip))); ?>"
			   href="<?php echo dash_h(dash_link('ops',$view_date,'tr-'.$t['id'])); ?>"
			   data-panel="train_detail.php?ta=<?php echo (int)$t['id']; ?>&amp;embed=1&amp;tt=<?php echo dash_h($dsTT); ?>"
			   data-panel-title="Index <?php echo dash_h($t['index_no']); ?> &mdash; <?php echo dash_h($meta[0]); ?>"><?php echo dash_h($t['index_no']); ?></a>
<?php	} ?>
		</div>
		<script>
		/* @traindetail -- Delegated to the container, not bound per chip, so it
		   survives the fleet strip being re-rendered and costs one listener
		   instead of forty. Lifted from the incident feed handler below, including
		   how it resolves the opener name: DASH_PANEL_FN when the console defines
		   one, openSlidePanel otherwise.

		   Every early return falls through to the href rather than swallowing the
		   click. A chip that does nothing is worse than a chip that navigates. */
		(function(){
			var strip=document.getElementById('ds-fleet');
			if(!strip||!strip.addEventListener) return;
			var fn=<?php echo json_encode(defined('DASH_PANEL_FN') ? DASH_PANEL_FN : 'openSlidePanel'); ?>;
			strip.addEventListener('click',function(e){
				/* Let the browser handle the ways a user asks for a new tab. */
				if(e.metaKey||e.ctrlKey||e.shiftKey||e.altKey||(e.button&&e.button!==0)) return true;
				var a=e.target||e.srcElement;
				while(a && a!==strip && !(a.getAttribute && a.getAttribute('data-panel'))) a=a.parentNode;
				if(!a || a===strip) return true;
				var open=window[fn];
				if(typeof open!=='function') return true;   /* no panel -> follow the href */
				if(e.preventDefault) e.preventDefault(); else e.returnValue=false;
				open(a.getAttribute('data-panel'), a.getAttribute('data-panel-title')||'');
				return false;
			},false);
		})();
		</script>
		<div class="ds-legend">
			<span><i class="ds-key f-ok"></i>Inserted <?php echo (int)$fleet['online']; ?></span>
			<span><i class="ds-key f-warn"></i>Reserve <?php echo (int)$fleet['boundary']; ?></span>
			<span><i class="ds-key f-info"></i>Removed <?php echo (int)$fleet['removed']; ?></span>
			<span><i class="ds-key f-bad"></i>Cancelled <?php echo (int)$fleet['cancelled']; ?></span>
			<span><i class="ds-key f-mute"></i>Non-revenue <?php echo (int)$fleet['nonrevenue']; ?></span>
		</div>
<?php } ?>
	</div>

	<!-- ======================== TWO-COLUMN ROW ======================== -->
	<div class="ds-grid">

		<div class="ds-card">
			<div class="ds-card-head">
				<h2>Insertions<?php if(count($insertions)){ echo ' <span class="ds-count">'.count($insertions).'</span>'; } ?></h2>
<?php 
/**
				<a class="ds-more" href="<?php echo dash_h(dash_link('depot',$view_date)); ?>">Depot insertion &rarr;</a>
				*/
				?>
			</div>
<?php if(!count($insertions)){ ?>
			<div class="ds-empty">No insertions recorded yet.</div>
<?php } else { ?>
			<?php /* @insertions -- Every insertion for the day, in a scroller rather
			         than a truncated list. The count sits in the heading because a
			         scrolled list hides its own length: without it there is no way
			         to tell eleven insertions from forty without dragging. */ ?>
			<?php /* @skiptag -- "Skipping" was a third &middot;-separated fragment
			         with the same weight as the station, tacked onto a line whose
			         length already varied by index and station name -- so it landed
			         in a different place on every row and read as part of the
			         station rather than as a property of the insertion. It was also
			         missing the space before its separator, so it ran straight into
			         "Ave.".

			         It is a flag, not a continuation of the sentence, so it is now a
			         badge pushed to the right edge. Right-aligned it forms a clean
			         column down the card: which insertions skipped is answerable by
			         scanning one edge instead of reading every line to its end.

			         Reuses .ds-badge, so it inherits the size, weight and radius of
			         the level badges in the incident feed rather than inventing a
			         second badge style. margin-right is cleared because that class
			         is built to sit BEFORE text; this one sits after everything. */ ?>
			<style>
.ds-feed .ds-skip{margin-right:0;flex:none;
	text-transform:uppercase;letter-spacing:.05em;font-size:10px}
			</style>
			<div class="ds-scroll">
			<ul class="ds-feed">
<?php	foreach($insertions as $i){ ?>
				<?php /* @skiptag -- compare the raw value, not the escaped one. The
				         old test ran dash_h() over a string that cannot contain
				         anything needing escaping, which worked but read as though
				         the escaping mattered to the comparison. */ ?>
				<li><span class="ds-time"><?php echo dash_h(date("H:i",$i['ts'])); ?></span>
					<span>Index <?php echo dash_h($i['index_no']); ?> &middot; <?php echo dash_h($i['point']); ?></span><?php
					if($i['point'] !== "North Ave."){ ?><span class="ds-badge t-warn ds-skip" title="Inserted at Quezon Ave. &mdash; skips the North Ave. segment">Skipping</span><?php } ?></li>
<?php	} ?>
			</ul>
			</div>
<?php } ?>
		</div>

		<div class="ds-card">
			<div class="ds-card-head">
				<h2>Incidents today</h2>
				<a class="ds-more" href="<?php echo dash_h(dash_force_date(dash_link('incidents',$view_date),$view_date)); ?>"><?php if($inc['worst']!==""){ echo "highest ".dash_h($inc['worst'])." &rarr;"; } else { echo "no service failures &rarr;"; } ?></a>
			</div>
<?php if(!count($incidents)){ ?>
			<div class="ds-empty">No incidents recorded for this date.</div>
<?php } else { ?>
			<ul class="ds-feed" id="ds-inc-feed">
<?php	$shown=0;
		foreach($incidents as $r){
			if($shown>=6){ break; }
			$shown++;
			$lvl = $r['lvl'];
			$desc= isset($r['description']) ? $r['description'] : '';
			$desc= trim(preg_replace('/\s+/',' ',strip_tags((string)$desc)));
			if(strlen($desc)>72){ $desc=substr($desc,0,72)."&hellip;"; }
			if($desc===""){ $desc=dash_type_label(isset($r['incident_type'])?$r['incident_type']:''); }
			/* @feedpanel -- the band titles its panel "Incident - <no>"; match it,
			   falling back to the row id when the register has no number yet. */
			$incId = dash_incident_id($r);
			$incNo = (isset($r['incident_no']) && trim((string)$r['incident_no'])!=='')
			       ? trim((string)$r['incident_no']) : $incId;
?>
				<li><span class="ds-time"><?php echo dash_h(dash_hm($r['incident_date'])); ?></span>
					<span><?php if($lvl!==""){ ?><span class="ds-badge t-<?php echo dash_level_tone($lvl); ?>"><?php echo dash_h($lvl); ?></span><?php } ?><?php
					/* @feedpanel -- same contract as the status band: the anchor keeps
					   its REAL href to the register, and data-panel carries the embed
					   URL. The handler below intercepts the click only after confirming
					   the opener exists, so with slide_panel.php absent, JS blocked, or
					   a middle-click, the link still goes somewhere useful.
					   The previous edit called openEditIncidentPanel(): that function
					   lives in train_operations_parallel.php and is not loaded here, so
					   even past the parse error it would have thrown ReferenceError. */
					?><a class="ds-more" style="font-size:inherit;color:inherit" href="<?php echo dash_h(dash_force_date(dash_link('incidents',$view_date,$incId?('ir-'.$incId):''),$view_date)); ?>"<?php if($incId!==""){ ?> data-panel="edit_ccdr.php?ir=<?php echo dash_h($incId); ?>&amp;embed=1" data-panel-title="Incident <?php echo dash_h($incNo); ?>"<?php } ?>><?php echo dash_h($desc); ?></a><?php if($r['open']){ ?> <em style="color:var(--cf-bad)">open</em><?php } ?></span></li>
<?php	} ?>
			</ul>
			<script>
			/* @feedpanel -- delegated to the list rather than bound per anchor, so it
			   survives the feed being re-rendered. The opener name comes from
			   DASH_PANEL_FN when defined, exactly as the band resolves it. */
			(function(){
				var list=document.getElementById('ds-inc-feed');
				if(!list||!list.addEventListener) return;
				var fn=<?php echo json_encode(defined('DASH_PANEL_FN') ? DASH_PANEL_FN : 'openSlidePanel'); ?>;
				list.addEventListener('click',function(e){
					var a=e.target||e.srcElement;
					while(a && a!==list && !(a.getAttribute && a.getAttribute('data-panel'))) a=a.parentNode;
					if(!a || a===list) return true;
					var open=window[fn];
					if(typeof open!=='function') return true;   /* no panel -> follow the href */
					if(e.preventDefault) e.preventDefault(); else e.returnValue=false;
					open(a.getAttribute('data-panel'), a.getAttribute('data-panel-title')||'');
					return false;
				},false);
			})();
			</script>
			<div class="ds-note" style="margin-top:9px"><a class="ds-more" href="<?php echo dash_h(dash_force_date(dash_link('incidents',$view_date),$view_date)); ?>">Full register</a> &mdash; showing <?php echo (int)$shown; ?> of <?php echo (int)$inc['total']; ?>.</div>
<?php } ?>
		</div>

	</div>

	<!-- ========================= BREAKDOWNS ========================== -->
	<div class="ds-grid">

		<div class="ds-card">
			<h2>Today by problem type</h2>
<?php if(!count($types)){ ?>
			<div class="ds-empty">Nothing to break down.</div>
<?php } else { ?>
			<div class="ds-bars">
<?php	foreach($types as $label=>$n){ ?>
				<span style="color:var(--cf-ink-2)"><?php echo dash_h($label); ?></span>
				<span class="ds-track"><i style="width:<?php echo dash_pct($n,$max_type); ?>%"></i></span>
				<span class="ds-n"><?php echo (int)$n; ?></span>
<?php	} ?>
			</div>
<?php } ?>
		</div>

		<div class="ds-card">
			<div class="ds-card-head">
				<h2><?php echo $trend ? dash_h($trend['head']) : 'Last '.(int)DASH_TREND_MONTHS.' months'; ?></h2>
<?php	if($trend){ ?>
				<?php /* @grain -- Bucket-size selector. Anchors rather than a <select>,
				         so the state lives in the URL: the wall display reloads into the
				         same view, and the choice can be bookmarked. $view_date rides
				         along so switching grain never silently jumps to today. */ ?>
				<span class="ds-seg">
<?php		foreach(array('year'=>'Yearly','month'=>'Monthly','week'=>'Weekly') as $gk=>$glabel){ ?>
					<a class="<?php echo $trend_grain===$gk?'on':''; ?>" href="?d=<?php echo dash_h($view_date); ?>&amp;g=<?php echo $gk; ?>"><?php echo $glabel; ?></a>
<?php		} ?>
				</span>
<?php	} else { ?>
				<?php /* @grain -- dash_data.php predates dash_trend_series(): no selector,
				         and the card draws the twelve-month series it always did. */ ?>
				<a class="ds-more" href="<?php
					$ym_keys = array_keys($months);
					echo dash_h(dash_link('stats',$view_date,'',array(
						'sd'    => (count($ym_keys) ? $ym_keys[0]."-01" : $view_date),
						'ed'    => $view_date,
						'range' => 'custom'
					)));
				?>">Statistics report &rarr;</a>
<?php	} ?>
			</div>
			<div class="ds-months">
<?php	if($trend){
			foreach($trend['keys'] as $k){
				$n = $trend['counts'][$k];
				if($n === null){ ?>
				<?php /* No records at all. Full height and hatched, because a
				         near-empty bar on a trend chart reads as a GOOD period --
				         the most dangerous thing this card could show. */ ?>
				<i class="is-gap" title="<?php echo dash_h($trend['full'][$k]); ?>: no records"></i>
<?php			} else { ?>
				<i style="height:<?php echo dash_pct($n,$max_trend>0?$max_trend:1); ?>%" title="<?php echo dash_h($trend['full'][$k].": ".$n); ?>"></i>
<?php			}
			}
		} else {
			foreach($months as $ym=>$n){ ?>
				<i style="height:<?php echo dash_pct($n,$max_month>0?$max_month:1); ?>%" title="<?php echo dash_h($ym.": ".$n); ?>"></i>
<?php		}
		} ?>
			</div>
			<div class="ds-months-x">
<?php	if($trend){
			foreach($trend['keys'] as $k){ ?>
				<span class="<?php echo $trend['counts'][$k]===null?'is-gap':''; ?>"><?php echo dash_h($trend['labels'][$k]); ?></span>
<?php		}
		} else {
			foreach($months as $ym=>$n){ ?>
				<span><?php echo dash_h(date("M",strtotime($ym."-01"))); ?></span>
<?php		}
		} ?>
			</div>
<?php	if($trend){ ?>
			<div class="ds-card-head" style="margin:9px 0 0">
				<span style="font-size:11px;color:var(--cf-ink-3)"><?php
					/* @grain -- Absence, counted. A hatched bar is easy to miss in a
					   glance across a room; a number is not. */
					$ngap = 0; foreach($trend['counts'] as $v){ if($v===null) $ngap++; }
					echo $ngap ? dash_h($ngap.' bucket'.($ngap==1?'':'s').' have no records') : '&nbsp;';
				?></span>
				<a class="ds-more" href="<?php
					/* @grain -- Yearly goes to year_stats.php instead. A six-year span
					   pushed into the statistics report as sd/ed would render one
					   enormous flat table; year_stats is the page built for that
					   question, and it derives its own range anyway. */
					if($trend_grain === 'year'){
						echo dash_h(dash_link('years',$view_date));
					} else {
						echo dash_h(dash_link('stats',$view_date,'',array(
							'sd'    => $trend['sd'],
							'ed'    => $trend['ed'],
							'range' => 'custom'
						)));
					}
				?>"><?php echo $trend_grain==='year' ? 'Year comparison &rarr;' : 'Statistics report &rarr;'; ?></a>
			</div>
<?php	} ?>
		</div>

	</div>

</div>
</body>
</html>