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
   @autorefresh -- Keeping the board current without reloading the page.

   NOT a meta refresh and not location.reload(). Both throw the whole document
   away every few seconds, which on a control-room terminal means: any open
   slide panel slams shut mid-read, the scroll position jumps, a half-typed
   date in the picker is wiped, and the browser re-fetches the menu, the CSS
   and jQuery on every tick. The page also becomes impossible to read aloud
   from, because it can vanish underneath whoever is reading it.

   Instead the page fetches ITSELF with partial=1 and swaps in just the data
   region. One source of truth -- there is no second endpoint to keep in step
   with this file, which is the failure mode a hand-written JSON API invites.

   Four things it deliberately does NOT do:
     - poll a historical date. A past day cannot change; polling it is pure
       load on the database for a number that is already final.
     - poll while the tab is hidden. An unattended terminal left open overnight
       would otherwise run every dashboard query some thousands of times.
     - poll while a slide panel is open. The panel is read from the page behind
       it; re-rendering that page under an open panel is how a click lands on
       the wrong incident.
     - overlap. If a tick is still in flight when the next is due, it is
       skipped rather than queued.
   ========================================================================== */
if(!defined('DASH_REFRESH_SECS')){ define('DASH_REFRESH_SECS', 20); }

/* A partial request renders the live region only -- see the ob_clean() below. */
$dsPartial = (isset($_GET['partial']) && $_GET['partial'] === '1');
if($dsPartial){ ob_start(); }

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
$fleet2       = dash_fleet_counts2($view_date);

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
<?php /* @autorefresh -- the menu is chrome; a partial tick has no use for it,
         and skipping it also skips whatever queries it runs. */
if(!$dsPartial){ require("Tmenu_2.php"); } ?>
<?php

/* @dashdate -- console skin for the date control. Deliberately AFTER the nav

   include: if Tmenu_2.php links a jQuery UI stylesheet, anything emitted

   earlier loses to it on source order. Guarded so a station that has not

   received the file yet keeps the plain native field. */

if(!$dsPartial && file_exists(dirname(__FILE__)."/dash_datepicker.php")){ include(dirname(__FILE__)."/dash_datepicker.php"); }
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
		<?php /* @autorefresh -- OUTSIDE #dsLive on purpose, so the swap cannot
		         replace the very element that reports whether the swap is
		         happening. A dashboard that has quietly stopped updating looks
		         exactly like a quiet shift; this is the line that tells them
		         apart, so it must not depend on the thing it is reporting on. */ ?>
		<?php if($is_today){ ?>
		<div class="ds-live" id="dsLiveStatus" aria-live="polite">
			<label class="ds-live-toggle"><input type="checkbox" id="dsLiveOn" checked> Live</label>
			<span class="ds-live-dot" id="dsLiveDot"></span>
			<span id="dsLiveText">starting&hellip;</span>
		</div>
		<?php } ?>
	</div>

<?php
/* @autorefresh -- Everything below is replaced on a tick. The bar above is
   not: it holds the date form (a half-typed value must survive) and the status
   readout.

   ob_clean() throws away everything rendered so far -- doctype, head, menu,
   the bar -- so a partial response is the live region and nothing else. The
   page above still RUNS on a tick, which is deliberate: the data those lines
   compute is shared, and splitting the file into a page half and a fragment
   half is what would let the two drift apart. */
if($dsPartial){ ob_clean(); } else { echo '<div id="dsLive">'; }
/* The wrapper itself is NOT part of a tick response. The browser assigns the
   response to region.innerHTML, so shipping the <div id="dsLive"> too would
   nest a second one inside the first on every tick -- ids duplicating, and the
   nesting growing one level deeper every twenty seconds until something that
   looks up an element by id finds the wrong one. */
?>

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
		<a class="ds-tile" href="<?php echo dash_h(dash_link('opsline',$view_date,'',array('lfilter'=>'skipping'))); ?>" title="Open train operations for this date"><span class="ds-rail f-ok"></span><div class="ds-tile-body">
			<div class="ds-tile-label"><span class="ds-dot f-ok"></span>Skipping Trains</div>
			<?php /* @merge -- denominator and day-on-day chip, restored from the
			         pre-integration build. $d_trains is still computed above; the
			         markup that displayed it had been dropped. A bare count with
			         nothing to compare it against is the one thing a dashboard
			         tile should never show. */ ?>
			<div><span class="ds-val"><?php echo (int)$fleet2['skipping']; ?></span>
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
	font-weight:600;font-size:11px}
			</style>
			<div class="ds-scroll">
			<ul class="ds-feed">
<?php	foreach($insertions as $i){ ?>
				<?php /* @skiptag -- badge off the flag dash_recent_insertions now
				         carries, not off the display label. Inferring it from the
				         label meant the badge and the Skipping tile were deciding
				         the same question by two different rules, and the label
				         collapses every insertion point that is not 'quezon' into
				         "North Ave." -- so a third point would print as North and
				         go unbadged while the tile counted it. */ ?>
				<li><span class="ds-time"><?php echo dash_h(date("H:i",$i['ts'])); ?></span>
					<span>Index <?php echo dash_h($i['index_no']); ?> &middot; <?php echo dash_h($i['point']); ?></span><?php
					if(!empty($i['skipping'])){ ?><span class="ds-badge t-warn ds-skip">Skipping</span><?php } ?></li>
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
				<?php /* @ytd -- The fourth option is labelled with the YEAR rather
				         than "This year", because the card follows the date being
				         viewed: on a 2019 board "This year" would be a lie, while
				         "2019" is simply what it shows. Placed next to Monthly --
				         both are months, one rolling and one calendar. */
				$dsSeg = array(
					'year'  => 'Yearly',
					'month' => 'Monthly',
					'ytd'   => date("Y", strtotime($view_date)),
					'week'  => 'Weekly',
				); ?>
				<span class="ds-seg">
<?php		foreach($dsSeg as $gk=>$glabel){ ?>
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

<?php
/* @autorefresh -- A partial request ends HERE, before the closing tag, so the
   response is the region's contents and nothing else. The buffer opened at the
   top is flushed; everything the browser already has -- doctype, head, menu,
   stylesheets, jQuery -- is not sent again on a tick. */
if($dsPartial){ ob_end_flush(); exit; }
?>
</div><!-- /#dsLive -->

</div>
<?php /* @autorefresh -- Emitted for EVERY date, not just today.
         Polling is today-only; swapping the region is not. The range selector
         on the trend card switches grain by loading a new URL, and that is
         worth doing in place whichever date is on screen -- a 2019 board has
         nothing to poll for, but its Yearly/Monthly/Weekly buttons should
         still not throw the page away. */ ?>
<style>
.ds-live{display:flex;align-items:center;gap:7px;font-size:11.5px;color:var(--cf-ink-3,#5A6275);
	margin-left:14px;white-space:nowrap;}
.ds-live-toggle{display:flex;align-items:center;gap:4px;cursor:pointer;}
.ds-live-toggle input{margin:0;cursor:pointer;}
.ds-live-dot{width:7px;height:7px;border-radius:50%;background:#9AA5B1;flex:none;}
.ds-live.is-live   .ds-live-dot{background:#1D9E75;}
.ds-live.is-busy   .ds-live-dot{background:#BA7517;}
/* Stale is the state that matters, so it is the only one that is loud. */
.ds-live.is-stale  .ds-live-dot{background:#E24B4A;}
.ds-live.is-stale  #dsLiveText{color:#A32D2D;font-weight:600;}
.ds-live.is-paused .ds-live-dot{background:#9AA5B1;}
/* @grain -- the region dims while a range switch is in flight, so a slow
   query reads as "loading" rather than as "the button did nothing". */
#dsLive.is-loading{opacity:.55;transition:opacity .12s;}
</style>
<script>
/* @autorefresh + @grain -- see the block comment at the top of this file for
   what the polling deliberately does not do. */
(function(){
	var SECS     = <?php echo (int)DASH_REFRESH_SECS; ?>;
	var IS_TODAY = <?php echo $is_today ? 'true' : 'false'; ?>;
	var region   = document.getElementById('dsLive');
	var live     = document.getElementById('dsLiveStatus');   /* today only */
	var toggle   = document.getElementById('dsLiveOn');
	var text     = document.getElementById('dsLiveText');
	if(!region || !window.XMLHttpRequest) return;

	var busy = false, lastOk = new Date(), failures = 0;

	function two(n){ return (n<10?'0':'')+n; }
	function clock(d){ return two(d.getHours())+':'+two(d.getMinutes())+':'+two(d.getSeconds()); }

	/* The status readout exists only on today's board. Everything below calls
	   state() unconditionally, so it has to tolerate not being there rather
	   than each caller testing first. */
	function state(cls, msg){
		if(!live || !text) return;
		live.className = 'ds-live ' + cls;
		text.innerHTML = '';
		text.appendChild(document.createTextNode(msg));
	}

	/* A panel is open when the console's panel element carries its active
	   class. Several ids are checked because the console has more than one
	   panel implementation in play; an unknown one simply means no pause,
	   which is the safe direction -- a missed pause is one redraw, a false
	   pause is a board that stops updating while saying it is live. */
	function panelOpen(){
		var ids = ['irPanel','taPanel','slidePanel','dashPanel'];
		for(var i=0;i<ids.length;i++){
			var el = document.getElementById(ids[i]);
			if(el && el.className && el.className.indexOf('active') !== -1) return true;
		}
		return false;
	}

	/* innerHTML does not execute <script>. The fleet strip and the incident
	   feed each bind a delegated listener to the container they live in, and
	   those containers are replaced by the swap -- so without re-running them
	   the chips and feed rows stop opening panels, silently, while everything
	   still LOOKS right. Cloned into fresh nodes because a script element that
	   has already run will not run again. */
	function runScripts(root){
		var found = root.getElementsByTagName('script'), list = [];
		for(var i=0;i<found.length;i++){ list.push(found[i]); }
		for(var j=0;j<list.length;j++){
			var old = list[j], fresh = document.createElement('script');
			if(old.src){ fresh.src = old.src; } else { fresh.text = old.text || old.innerHTML; }
			old.parentNode.replaceChild(fresh, old);
		}
	}

	function partialUrl(href){
		var u = href.split('#')[0];
		u += (u.indexOf('?') === -1 ? '?' : '&') + 'partial=1';
		/* Defeats the proxy and the browser cache alike; without it a tick can
		   return the response from twenty seconds ago and look like a shift in
		   which nothing happened. */
		return u + '&_=' + (new Date()).getTime();
	}

	/* @grain -- The date form and the Today link live in the bar, OUTSIDE the
	   swapped region, so a range switch does not touch them. Both carry the
	   grain: the form as a hidden input, the link in its query string. Left
	   alone, submitting the date form after switching to Weekly would quietly
	   send you back to Monthly -- the board would look like it had ignored the
	   click, one step later and with no obvious cause. */
	function syncBar(href){
		var m = /[?&]g=([a-z]+)/i.exec(href);
		var g = m ? m[1] : '';
		var hidden = document.querySelector('.ds-bar input[name="g"]');
		if(hidden && g){ hidden.value = g; }
		var today = document.querySelector('.ds-bar a.ds-today');
		if(today && g){
			today.href = today.href.split('?')[0] + '?g=' + g;
		}
	}

	/* One loader for both callers. The tick reloads the current URL; a range
	   button loads a different one and pushes it. Sharing it is the point:
	   two copies of "fetch, swap, re-run scripts" would drift, and the swap is
	   the part with the non-obvious step in it. */
	function load(href, push, onDone){
		if(busy) return;
		busy = true;
		if(push){ region.className = 'is-loading'; }

		var xhr = new XMLHttpRequest();
		xhr.open('GET', partialUrl(href), true);
		xhr.setRequestHeader('X-Requested-With','XMLHttpRequest');
		xhr.onreadystatechange = function(){
			if(xhr.readyState !== 4) return;
			busy = false;
			region.className = '';
			var ok = (xhr.status >= 200 && xhr.status < 300 && xhr.responseText);
			if(ok){
				region.innerHTML = xhr.responseText;
				runScripts(region);
				if(push && window.history && window.history.pushState){
					window.history.pushState({ds:1}, '', href);
				}
				syncBar(push ? href : location.href);
				lastOk = new Date(); failures = 0;
			}
			if(onDone) onDone(ok);
		};
		try { xhr.send(null); } catch(e){ busy = false; region.className = ''; if(onDone) onDone(false); }
	}

	/* ---- polling: today only ---------------------------------------------- */
	function tick(){
		if(busy) return;                                   /* never overlap */
		if(toggle && !toggle.checked){ state('is-paused','paused'); return; }
		if(document.hidden){ return; }                     /* nobody is looking */
		if(panelOpen()){ state('is-paused','paused - panel open'); return; }

		state('is-busy','updating' + (failures ? ' - retry ' + failures : '') + '\u2026');
		load(location.href, false, function(ok){
			if(ok){ state('is-live','updated ' + clock(lastOk)); return; }
			failures++;
			/* One blip is a blip. Sustained failure is reported as STALE with
			   the age of the data, because the alternative is a board showing
			   an hour-old number as though it were now. */
			if(failures < 3){ state('is-live','updated ' + clock(lastOk)); }
			else {
				var mins = Math.round((new Date() - lastOk) / 60000);
				state('is-stale','STALE - last updated ' + clock(lastOk)
					+ (mins >= 1 ? ' (' + mins + ' min ago)' : ''));
			}
		});
	}

	/* ---- range switching: every date ---------------------------------------
	   Delegated to the region rather than bound to the anchors, because the
	   anchors are replaced by every swap -- including the swap the range
	   button itself triggers. A direct binding would work once and then stop,
	   which is the sort of failure that gets reported as "it works sometimes". */
	if(region.addEventListener && window.history && window.history.pushState){
		region.addEventListener('click', function(e){
			var t = e.target;
			while(t && t !== region && !(t.tagName && t.tagName.toLowerCase() === 'a')){ t = t.parentNode; }
			if(!t || t === region || !t.href) return;
			var seg = t.parentNode;
			if(!seg || !seg.className || seg.className.indexOf('ds-seg') === -1) return;

			/* Let the browser have the click when a new tab or window was
			   asked for -- comparing two grains side by side is a reasonable
			   thing to want. */
			if(e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;

			e.preventDefault();
			load(t.href, true);
		}, false);

		/* Back and Forward move between grains without a server round trip of
		   the whole page. The state test keeps this off history entries this
		   script did not create. */
		window.addEventListener('popstate', function(ev){
			if(ev.state && ev.state.ds){ load(location.href, false); }
		}, false);
	}

	/* ---- start ------------------------------------------------------------ */
	if(IS_TODAY){
		if(toggle){
			toggle.onchange = function(){
				if(toggle.checked){ state('is-live','resuming\u2026'); tick(); }
				else { state('is-paused','paused'); }
			};
		}
		/* Refresh on return rather than waiting out the rest of the interval:
		   the first thing someone does on coming back to a terminal is read it. */
		if(typeof document.addEventListener === 'function'){
			document.addEventListener('visibilitychange', function(){
				if(!document.hidden) tick();
			});
		}
		state('is-live','updated ' + clock(lastOk));
		setInterval(tick, SECS * 1000);
	}
})();
</script>
</body>
</html>