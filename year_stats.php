<?php
session_start();
ini_set("date.timezone","Asia/Kuala_Lumpur");

/* =========================================================================
   year_stats.php -- car-level failures as a year x month grid.

   WHY THIS PAGE EXISTS SEPARATELY
   -------------------------------
   Every other report on the console is scoped to a filtered window. A
   year-against-year comparison has to deliberately ignore that window, and a
   chart that pointedly disobeys the From/To boxes sitting above it is exactly
   the confusion equipt_stats.php and month_stats.php produced -- two "By
   month" tables answering different questions with nothing saying which was
   which. So this is its own page: it covers everything, and covering
   everything IS what it is.

   WHY A GRID AND NOT A YEAR-OVER-YEAR LINE
   ----------------------------------------
   The instinct is months on the x-axis, one line per year. That would lie
   here. The console has months with no records at all, and a line draws
   straight through them -- the reader sees a gentle slope where there is no
   data. A grid can show absence AS absence.

   DESIGNED FOR DATA THAT IS STILL ARRIVING
   ----------------------------------------
   Records are being recovered, so nothing about the shape of this page is
   fixed in code:

     - The year range comes from MIN/MAX(incident_date) at runtime, unioned
       with whatever the coverage table knows about. Recover 2022 and a 2022
       row appears on the next page load. There is no hardcoded floor year to
       forget to update.
     - The shading scale is recomputed from the cells actually present, so a
       restored month rescales the grid rather than sitting off the top of a
       stale range.
     - Three cell states, never two: covered with failures, covered with
       none, and NOT COVERED. A recovered month flips from hatched to shaded
       on its own. Collapsing the last two into a single blank is the one
       thing that would make this page actively misleading -- "we had no
       failures that March" and "we have no records for that March" are
       opposite claims.
     - Row and column summaries carry their own coverage denominator, and the
       month comparison is offered as a RATE as well as a total. Totalling
       March across years while some Marches are missing is the specific trap
       this page invites, so the honest figure sits beside the raw one.

   COUNTING BASIS
   --------------
   Car-level failures -- one row per incident per car affected -- matching
   statistics_report_modified.php, car_statistics_report.php and month_stats.php
   so the figures reconcile. The incident history logs count one row per
   incident and will always show a smaller number.
   ========================================================================= */

/* embed=1 -> hosted inside a slide-panel iframe. Tmenu.php still runs (it
   provides $db / session / auth side effects) but its printed chrome is
   captured and discarded. Opened standalone, nothing changes. */
$IR_EMBED = isset($_GET['embed']);
if($IR_EMBED){ ob_start(); }
require("Tmenu.php");
if($IR_EMBED){ ob_end_clean(); }

/* Which months the console actually has records for. Loaded defensively: if
   data_coverage.php has not been uploaded, the stubs report every month as
   covered and the page still renders -- it just cannot tell a genuine zero
   from a gap, which the legend says out loud rather than hiding. */
if(file_exists(dirname(__FILE__)."/data_coverage.php")){
	require_once(dirname(__FILE__)."/data_coverage.php");
}
$ysHasCoverage = function_exists('ccsMonthStatus');
if(!$ysHasCoverage){
	function ccsLoadCoverage($db){ return array(); }
	function ccsMonthStatus($coverage,$ym){ return 'covered'; }
	function ccsMonthIsMissing($coverage,$ym){ return false; }
	function ccsCoverageCss(){ return ''; }
}

/* Same connection pattern as statistics_report_modified.php: centralized
   credentials when the file is there, the original line when it is not. */
if(file_exists(dirname(__FILE__)."/db_config.php")){ require_once(dirname(__FILE__)."/db_config.php"); }
if(!isset($db) || !$db){ $db=new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport"); }

$coverage = ccsLoadCoverage($db);

/* ---- Inputs -------------------------------------------------------------
   The same narrowing the sibling panels accept, so this page can be reached
   from any of them without widening silently. No date window: the period is
   the subject here, and bounding it would defeat the point. */
$car    = isset($_GET['car'])    && $_GET['car']    !== '' ? (int)$_GET['car']    : 0;
$equipt = isset($_GET['equipt']) && $_GET['equipt'] !== '' ? (int)$_GET['equipt'] : 0;
$level  = isset($_GET['level'])  && $_GET['level']  !== '' ? (int)$_GET['level']  : 0;

/* @roster -- optional equipment population, same contract as month_stats.php.
   Ids cast to int and rebuilt so nothing from the query string reaches the
   SQL as text; an empty or all-junk list is treated as absent rather than as
   "match nothing", which would render a blank grid with no explanation. */
$equipts = array();
if(isset($_GET['equipts']) && $_GET['equipts'] !== ''){
	foreach(explode(',', (string)$_GET['equipts']) as $v){
		$v = (int)trim($v);
		if($v > 0 && !in_array($v, $equipts, true)) $equipts[] = $v;
	}
}

/* Optional display bound. NOT a data filter -- it only trims which rows are
   drawn, for when the recovered archive eventually makes the grid taller than
   a screen. Left empty by default so new years appear on their own. */
$yFrom = isset($_GET['y1']) && $_GET['y1'] !== '' ? (int)$_GET['y1'] : 0;
$yTo   = isset($_GET['y2']) && $_GET['y2'] !== '' ? (int)$_GET['y2'] : 0;

$where = "1=1";
if($car)             $where .= " and incident_cars.car_no*1 = ".$car." ";
if($equipt)          $where .= " and incident_report.equipt = ".$equipt." ";
if(count($equipts))  $where .= " and incident_report.equipt in (".implode(',', $equipts).") ";
if($level)           $where .= " and incident_report.level = ".$level." ";

/* ---- The grid -----------------------------------------------------------
   One pass, grouped by year and month. Car-level failures, so incident_cars
   is joined and NOT de-duplicated: an incident affecting three cars counts
   once against each, which is the basis every other statistics page uses. */
$grid = array();          // [year][month] => count
/* @total -- NOT accumulated here. This loop sees every row the filter matched,
   including months later masked as 'gap' (coverage says no records, yet rows
   exist), months in the future, and years trimmed away by the y1/y2 bound.
   None of those reach the grid, so a total summed here counts cells the table
   does not draw: it read 20767 beside twelve columns summing to 20710, and
   divided that by the MASKED denominator of 120 covered months. It is derived
   from $yearTotal instead, below, which makes it the sum of the All row by
   construction rather than by coincidence. */
$total = 0;
$sql = "select year(incident_report.incident_date) as yr,
               month(incident_report.incident_date) as mo,
               count(1) as c
          from incident_report
          inner join incident_cars on incident_report.id = incident_cars.incident_id
         where ".$where."
           and incident_report.incident_date is not null
         group by yr, mo";
$rs = $db->query($sql);
if($rs){
	while($r = $rs->fetch_assoc()){
		$y = (int)$r['yr']; $m = (int)$r['mo']; $c = (int)$r['c'];
		if($y <= 0 || $m < 1 || $m > 12) continue;   /* junk dates stay out of the axis */
		if(!isset($grid[$y])) $grid[$y] = array();
		$grid[$y][$m] = $c;
	}
}

/* ---- Which years to draw ------------------------------------------------
   Derived, never hardcoded. Two sources unioned:

     1. Years with rows, from the grid above.
     2. Years the coverage table knows about -- so a year that is KNOWN to be
        missing still gets a row of hatching rather than vanishing. A year
        that silently disappears from the axis is indistinguishable from a
        year that never existed, and while the archive is being restored that
        distinction is the whole point of the page.

   Recover a year and its row appears next load, with no edit here. */
$years = array_keys($grid);
if($ysHasCoverage){
	$cy = $db->query("select distinct year(incident_date) as yr from incident_report where incident_date is not null");
	if($cy){ while($cr = $cy->fetch_assoc()){ $years[] = (int)$cr['yr']; } }
	foreach(array_keys((array)$coverage) as $ym){
		if(preg_match('/^(\d{4})-\d{2}$/', (string)$ym, $mm)) $years[] = (int)$mm[1];
	}
}
$years = array_values(array_unique(array_filter($years, 'is_int')));
sort($years);

/* The display bound, applied after derivation so it trims rather than defines. */
if($yFrom || $yTo){
	$keep = array();
	foreach($years as $y){
		if($yFrom && $y < $yFrom) continue;
		if($yTo   && $y > $yTo)   continue;
		$keep[] = $y;
	}
	$years = $keep;
}

/* Never draw months that have not happened yet -- an unreached December is
   not a gap in the record, and hatching it would overstate what is missing. */
$nowY = (int)date("Y"); $nowM = (int)date("n");

/* ---- Cell states --------------------------------------------------------
   'gap'      no coverage. Not a zero, and never shaded like one.
   'future'   not yet reached. Drawn as nothing at all.
   'zero'     covered, no failures recorded. A real and good result.
   'value'    covered, with failures.

   The scale is built from 'value' cells only, so gaps and future months
   cannot drag the range, and it is rebuilt on every load -- restore a heavy
   month and everything below it rescales rather than pinning to a stale max. */
$state = array(); $vals = array();
$coveredCells = array(); $coveredByYear = array(); $coveredByMonth = array();
foreach($years as $y){
	$state[$y] = array();
	$coveredByYear[$y] = 0;
	for($m = 1; $m <= 12; $m++){
		$ym = sprintf("%04d-%02d", $y, $m);
		if($y > $nowY || ($y === $nowY && $m > $nowM)){ $state[$y][$m] = 'future'; continue; }
		if($ysHasCoverage && ccsMonthStatus($coverage, $ym) === 'missing'){ $state[$y][$m] = 'gap'; continue; }
		$c = isset($grid[$y][$m]) ? (int)$grid[$y][$m] : 0;
		$state[$y][$m] = $c > 0 ? 'value' : 'zero';
		$coveredByYear[$y]++;
		if(!isset($coveredByMonth[$m])) $coveredByMonth[$m] = 0;
		$coveredByMonth[$m]++;
		$coveredCells[] = $c;
		if($c > 0) $vals[] = $c;
	}
}
$vLo = count($vals) ? min($vals) : 0;
$vHi = count($vals) ? max($vals) : 0;

/* ---- Summaries ----------------------------------------------------------
   Every total carries its own coverage denominator. A year with four months
   restored will always total less than a complete one, and without the
   denominator beside it that reads as a good year. The per-covered-month rate
   is the figure that survives an uneven archive -- it is the one to compare
   while records are still coming back, and the raw total is the one to
   compare once they are not. */
$yearTotal = array(); $monthTotal = array();
foreach($years as $y){
	$yearTotal[$y] = 0;
	for($m = 1; $m <= 12; $m++){
		if($state[$y][$m] === 'gap' || $state[$y][$m] === 'future') continue;
		$c = isset($grid[$y][$m]) ? (int)$grid[$y][$m] : 0;
		$yearTotal[$y] += $c;
		if(!isset($monthTotal[$m])) $monthTotal[$m] = 0;
		$monthTotal[$m] += $c;
	}
}
/* The grand total is the sum of the row totals -- the same figure the table's
   own corner cell prints, because it is now the same variable. */
foreach($yearTotal as $t){ $total += $t; }

function ysRate($sum, $months){ return $months > 0 ? round($sum / $months, 1) : null; }

/* Worst month overall, by rate rather than by total, for the same reason. */
$peakM = 0; $peakRate = -1;
foreach($monthTotal as $m => $sum){
	$r = ysRate($sum, isset($coveredByMonth[$m]) ? $coveredByMonth[$m] : 0);
	if($r !== null && $r > $peakRate){ $peakRate = $r; $peakM = $m; }
}
$peakY = 0; $peakYRate = -1;
foreach($yearTotal as $y => $sum){
	$r = ysRate($sum, $coveredByYear[$y]);
	if($r !== null && $r > $peakYRate){ $peakYRate = $r; $peakY = $y; }
}

$gapCells = 0;
foreach($years as $y){ for($m=1;$m<=12;$m++){ if($state[$y][$m]==='gap') $gapCells++; } }

$mAbbr = array(1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'May',6=>'Jun',
               7=>'Jul',8=>'Aug',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec');
$mFull = array(1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',
               7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December');

/* ---- Narrowing, stated --------------------------------------------------
   Same rule as the sibling panels: every filter is named, AND so is the
   unfiltered case. The absence of a filter line was what let two differently
   scoped tables read as a contradiction. */
$ysNarrow = array();
if($car) $ysNarrow[] = 'Car '.$car;
if($equipt){
	$enm=''; $eq=$db->query("select equipment_name from equipment where id='".$equipt."'");
	if($eq && ($er=$eq->fetch_assoc())) $enm=(string)$er['equipment_name'];
	$ysNarrow[] = ($enm !== '' ? $enm : 'Equipment #'.$equipt);
}
if(count($equipts) && !$equipt) $ysNarrow[] = count($equipts).' equipment types';
if($level) $ysNarrow[] = 'Level '.$level;
$ysScope = count($ysNarrow)
	? 'Filtered to '.implode(' &middot; ', array_map('htmlspecialchars', $ysNarrow))
	: 'All equipment, all cars, all levels';

/* Drill-down target: one cell is one month, which is exactly what
   month_stats.php already renders. The narrowing rides along so the panel
   opens on the same population the grid was showing. */
/* @tt -- The access token every console page expects on an inbound URL. It was
   missing here, so a cell click landed on month_stats.php without it; the
   sibling drill-downs in car_statistics_report.php all append it. Held as a
   constant rather than repeated as a literal, because this page now needs it
   in two places -- the href and the panel's iframe src -- and two hand-copied
   literals is how one of them ends up stale. */
define('YS_TT', '2a7b85131d93ffbaacc73f7ff024b55a');

function ysCellUrl($y, $m, $car, $equipt, $level, $equipts){
	$q = 'by=equipt&year='.$y.'&months='.$m;
	if($car)            $q .= '&car='.$car;
	if($equipt)         $q .= '&equipt='.$equipt;
	if($level)          $q .= '&level='.$level;
	if(count($equipts)) $q .= '&equipts='.rawurlencode(implode(',', $equipts));
	$q .= '&title='.rawurlencode(ysCellTitle($y, $m));
	$q .= '&tt='.YS_TT;
	return 'month_stats.php?'.$q;
}

/* Used for both the panel heading and month_stats.php's own title= parameter,
   so the two cannot disagree. */
function ysCellTitle($y, $m){
	$f = array(1=>'January','February','March','April','May','June','July',
	           'August','September','October','November','December');
	return $f[$m].' '.$y;
}
?>
<style>
.rowHeading {background:#00529B; color:#FFFFFF; font-size:15px; font-weight:600;}
.rowClass {background-color:#F5F2E8;}
select { border:1px solid #D8D2C2; color:#1A2238; background-color:#FFFFFF; border-radius:4px; }
<?php echo ccsCoverageCss(); ?>


/* =========================================================================
   Shared console furniture, carried over verbatim from month_stats.php and
   equipt_stats.php. These classes are declared per page rather than in
   history_theme.php, so a new page that uses the markup without copying the
   rules gets the structure and none of the styling -- which is exactly what
   this page did on its first render: a .kpi-strip flexbox containing four
   completely unstyled .kpi-tile divs, and a .stat-scope bar with no blue.

   Kept byte-identical to the siblings rather than tidied. The moment these
   drift, two panels opened side by side stop looking like the same system,
   and that is a harder thing to notice than a page that is plainly unstyled.
   ========================================================================= */
body { margin:24px 30px; background:#FAFAF6; color:#1A2238; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }

h2 { color:#1A2238; font-size:20px; }

.stat-legend {
	display:flex; align-items:center; gap:16px; flex-wrap:wrap;
	background:#F1EEE3; border:1px solid #E5DECC; border-top:none;
	padding:8px 16px; font-size:12px; color:#5A6275;
}
.stat-legend .swatch { display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:5px; vertical-align:middle; }

.stat-scope {
	background:#00529B; border-bottom:3px solid #FDB813;
	border-radius:6px 6px 0 0; padding:10px 16px;
	color:#FFFFFF; font-size:13px; font-weight:600;
	display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
}
.stat-scope .muted { color:rgba(255,255,255,.75); font-weight:400; margin-left:8px; }
.stat-scope .scope-left { min-width:0; }
.scope-btn {
	display:inline-block; flex:none;
	background:#FDB813; color:#3A2D00 !important;
	font-size:12px; font-weight:700; line-height:1;
	padding:8px 14px; border-radius:4px; border:none;
	text-decoration:none !important; cursor:pointer; white-space:nowrap;
}
.scope-btn:hover, .scope-btn:focus { background:#E5A50F; color:#3A2D00 !important; text-decoration:none !important; }
.scope-btn:focus-visible { outline:2px solid #FFFFFF; outline-offset:2px; }
.scope-actions { display:flex; align-items:center; gap:8px; flex:none; }
.scope-btn--ghost {
	background:transparent; color:#FFFFFF !important;
	border:1px solid rgba(255,255,255,.65);
	font-family:inherit;
}
.scope-btn--ghost:hover, .scope-btn--ghost:focus {
	background:rgba(255,255,255,.14); color:#FFFFFF !important; border-color:#FFFFFF;
}
@media print { .scope-actions { display:none; } }

.train_ava { border-collapse:collapse; }
.train_ava td, .train_ava th { border:1px solid #E5DECC; padding:6px 8px; }

a.two { color:#00529B; font-weight:600; text-decoration:none; }
a.two:visited { color:#00529B; }
a.two:hover, a.two:active { color:#003E76; text-decoration:underline; }

/* KPI tiles -- same geometry as month_stats.php's strip. The strip alone was
   declared here before; the tiles inside it were not. */
.kpi-strip { display:flex; flex-wrap:wrap; gap:10px; margin:14px 0; }
.kpi-tile {
	flex:1; min-width:150px; border:1px solid #E5DECC; border-radius:6px;
	padding:10px 12px; background:#FBFAF6;
}
.kpi-tile .k-label { font-size:11px; color:#5A6275; text-transform:uppercase; letter-spacing:.06em; }
.kpi-tile .k-value { font-size:22px; font-weight:600; color:#00529B; }
.kpi-tile .k-value--name { font-size:15px; line-height:1.3; margin-top:3px; color:#7A1F1F; }
.kpi-tile .k-sub   { font-size:11px; color:#5A6275; }

.brk-head { font-size:12px; text-transform:uppercase; letter-spacing:.07em; color:#00529B;
	border-bottom:1px solid #E5DECC; padding-bottom:5px; margin:22px 0 8px; font-weight:600; }

/* The grid. Fixed layout: twelve month columns must stay equal, or a single
   four-digit cell widens its column and the eye reads the wobble as meaning. */
.ys-grid { table-layout:fixed; width:100%; border-collapse:separate; border-spacing:2px; }
.ys-grid th, .ys-grid td { padding:0; text-align:center; font-size:12px; }
.ys-grid col.c-yr  { width:52px; }
.ys-grid col.c-tot { width:74px; }
.ys-grid thead th { font-weight:600; color:#00529B; font-size:11px;
	text-transform:uppercase; letter-spacing:.05em; padding-bottom:4px; }
.ys-grid tbody th.yr { text-align:left; font-weight:600; color:#1A2238; padding-left:2px; }

.ys-cell { display:block; height:26px; line-height:26px; border-radius:2px;
	text-decoration:none; color:#1A2238; }
a.ys-cell:hover, a.ys-cell:focus { outline:2px solid #FDB813; outline-offset:-2px; }

/* @cellcolour -- Every link state pinned explicitly, and that is the whole
   point rather than tidiness. `.ys-cell` and `.ys-s4` are both one class, so
   they tie on specificity and source order decides; but a console-wide
   `a:hover { color:... }` in history_theme.php is an element PLUS a
   pseudo-class, which outranks both. Hovering the darkest cell therefore
   repainted its text link-blue on a #00529B background and the number
   disappeared -- worst on exactly the cell most worth reading. Anchors only:
   the totals and the All row are not links and keep the base colour. */
a.ys-cell, a.ys-cell:link, a.ys-cell:visited,
a.ys-cell:hover, a.ys-cell:active, a.ys-cell:focus { color:#1A2238; }
a.ys-cell.ys-s3, a.ys-cell.ys-s3:link, a.ys-cell.ys-s3:visited,
a.ys-cell.ys-s3:hover, a.ys-cell.ys-s3:active, a.ys-cell.ys-s3:focus,
a.ys-cell.ys-s4, a.ys-cell.ys-s4:link, a.ys-cell.ys-s4:visited,
a.ys-cell.ys-s4:hover, a.ys-cell.ys-s4:active, a.ys-cell.ys-s4:focus { color:#FFFFFF; }
a.ys-cell.ys-zero, a.ys-cell.ys-zero:link, a.ys-cell.ys-zero:visited,
a.ys-cell.ys-zero:hover, a.ys-cell.ys-zero:active, a.ys-cell.ys-zero:focus { color:#9A968A; }

/* Sequential single hue. Magnitude is one thing, so it gets one colour --
   a categorical palette here would invite reading the shades as kinds. */
.ys-s0 { background:#EDF3FA; }
.ys-s1 { background:#CFE0F2; }
.ys-s2 { background:#9DC0E4; }
.ys-s3 { background:#5C93CE; color:#FFFFFF; }
.ys-s4 { background:#00529B; color:#FFFFFF; font-weight:600; }

/* Covered, no failures. Deliberately NOT the same as a gap. */
.ys-zero { background:#FBFAF6; color:#9A968A; }

/* @peakmark -- The ring the two peak tiles refer to. Without it "October" sat
   beside a grid whose darkest cell is a July, and the two looked like they
   disagreed: the tiles rank a month-of-year by its AVERAGE across covered
   years (the All row), while the shading ranks single cells by raw count.
   Both are right; nothing on the page said they were answering different
   questions. Marking the cell each tile names is cheaper than explaining it. */
.ys-peak { outline:2px solid #FDB813; outline-offset:-2px; border-radius:2px; }

/* No coverage. Hatched, and carrying an em dash rather than a digit, so it
   cannot be read as a count at a glance or in a photocopy. */
.ys-gap { background:repeating-linear-gradient(45deg,#E5DECC,#E5DECC 3px,#F5F2E8 3px,#F5F2E8 6px);
	color:#8A8272; }
.ys-future { background:transparent; }

.ys-tot { background:#F5F2E8; font-weight:600; color:#1A2238; }
.ys-rate { font-size:11px; color:#6B6B6B; font-weight:400; }
.ys-part { color:#7A1F1F; }

.ys-foot th, .ys-foot td { border-top:2px solid #E5DECC; padding-top:3px; }
.ys-note { font-size:12px; color:#6B6B6B; margin:10px 0 0; line-height:1.55; }
/* Emitted by the hidden-rows/coverage warnings; the siblings use .ccs-note
   from history_theme.php, matched here so the two read the same. */
.ccs-note { font-size:12px; color:#6B6B6B; margin:10px 0 0; line-height:1.55; }
.ys-key { display:flex; flex-wrap:wrap; gap:14px; align-items:center; font-size:12px;
	color:#4A4A4A; margin:0 0 10px; }
.ys-key i { display:inline-block; width:14px; height:12px; border-radius:2px;
	vertical-align:-1px; margin-right:5px; }

/* --- Slide panel ------------------------------------------------ @monthpanel
   Copied byte-identical from car_statistics_report.php, for the reason its own
   comment gives a few rules up: the moment these drift, two panels opened side
   by side stop looking like the same system, and that is harder to notice than
   a page that is plainly unstyled. var() fallbacks because the ta- custom
   properties live in train_operations.php's :root and do not reach here. */
.ta-overlay       { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(10,25,50,.45); opacity:0; visibility:hidden; transition:opacity .2s; z-index:99998; }
.ta-overlay.active{ opacity:1; visibility:visible; }
.ta-panel         { position:fixed; top:0; right:-900px; width:480px; max-width:96vw; height:100vh; background:var(--paper,#F7F9FC); box-shadow:-6px 0 24px rgba(0,30,80,.25); transition:right .25s ease; z-index:99999; display:flex; flex-direction:column; font-family:"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif; }
.ta-panel.active  { right:0; }
.ta-panel-head    { background:var(--rail,#00529B); border-bottom:3px solid var(--gold,#FDB813); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; flex:none; }
.ta-panel-head h3 { margin:0; color:#fff; font-size:13px; font-weight:600; letter-spacing:.3px; }
.ta-panel-close   { background:none; border:none; color:rgba(255,255,255,.7); font-size:19px; line-height:1; cursor:pointer; padding:0 2px; }
.ta-panel-close:hover { color:var(--gold,#FDB813); }
.ta-panel-body    { flex:1; overflow-y:auto; padding:16px 18px; }
#irPanel.ta-panel--ir { width:820px; }
.ta-panel-body--ir { padding:0; overflow:hidden; position:relative; }
#irFrame           { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#irFrame.ready     { opacity:1; }
.ir-loading, .ir-fallback { position:absolute; top:0; right:0; bottom:0; left:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:var(--paper,#F7F9FC); text-align:center; padding:0 30px; }
.ir-loading.hidden, .ir-fallback.hidden { display:none; }
.ir-spinner { width:26px; height:26px; border:3px solid #C9D6E5; border-top-color:var(--rail,#00529B); border-radius:50%; animation:ir-spin .7s linear infinite; }
@keyframes ir-spin { to { transform:rotate(360deg); } }
.ir-loading span, .ir-fallback p { font-size:12px; color:var(--mut,#5A6678); }
.ir-fallback strong { color:var(--ink,#16243B); font-size:13px; }
.ir-fallback a { color:var(--rail,#00529B); font-weight:600; text-decoration:none; }
.ir-fallback a:hover { text-decoration:underline; }
</style>
<?php include("history_theme.php"); ?>

<body>
<div class="ccs-page">

<div class="ccs-header">
<h1>Failures by year and month</h1>
<div class='sub' style="color:<?php echo count($ysNarrow) ? '#FDB813' : 'rgba(255,255,255,.72)'; ?>;"><?php echo $ysScope; ?></div>
<?php if(count($years)){ ?>
<div class='sub'><?php echo (int)$years[0]; ?> to <?php echo (int)$years[count($years)-1]; ?> &middot; derived from the records present, not a fixed range</div>
<?php } ?>
</div>

<div class="ccs-panel">
<div class="ccs-panel-head">
<div class="stat-scope">
	<div class="scope-left">Car-level failures per month</div>
	<div class="scope-actions">
		<button type="button" class="scope-btn scope-btn--ghost" onclick="ysPrintReport()">Generate printout</button>
	</div>
</div>
<div class="stat-legend">
	<span><span class="swatch" style="background:#00529B;"></span>Counts are car-level failures <?php
		echo htmlspecialchars(
			$car ? 'for this car'
			     : ($equipt ? 'for this equipment'
			     : (count($equipts) ? 'across '.count($equipts).' equipment types' : 'across all equipment')));
	?></span>
	<span><span class="swatch" style="background:#E5DECC;"></span>Hatched = no records for that month, which is not the same as none occurring</span>
</div>
</div>
<div class='ccs-panel-body'>

<div class="kpi-strip">
	<div class="kpi-tile">
		<div class="k-label">Car-level failures</div>
		<div class="k-value"><?php echo (int)$total; ?></div>
		<div class="k-sub">across <?php echo count($coveredCells); ?> covered month<?php echo count($coveredCells)==1?'':'s'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label">Years on record</div>
		<div class="k-value"><?php echo count($years); ?></div>
		<div class="k-sub"><?php echo $gapCells ? $gapCells.' month'.($gapCells==1?'':'s').' still missing' : 'no gaps'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label">Heaviest month, on average</div>
		<div class="k-value" style="color:#7A1F1F;"><?php echo $peakM ? htmlspecialchars($mFull[$peakM]) : '&mdash;'; ?></div>
		<div class="k-sub"><?php echo $peakM
			? $peakRate.' per covered month across '.(int)$coveredByMonth[$peakM].' year'.($coveredByMonth[$peakM]==1?'':'s').' &mdash; ringed in the All row'
			: 'not enough data'; ?></div>
	</div>
	<div class="kpi-tile">
		<div class="k-label">Heaviest year, on average</div>
		<div class="k-value"><?php echo $peakY ? (int)$peakY : '&mdash;'; ?></div>
		<div class="k-sub"><?php echo $peakY
			? $peakYRate.' per covered month across '.(int)$coveredByYear[$peakY].' month'.($coveredByYear[$peakY]==1?'':'s').' &mdash; ringed in the Total column'
			: 'not enough data'; ?></div>
	</div>
</div>

<?php if(!count($years)){ ?>
<p class="ys-note">No incidents match this scope yet.</p>
<?php } else { ?>

<div class="ys-key">
	<span><i style="background:#EDF3FA"></i><i style="background:#9DC0E4"></i><i style="background:#00529B"></i>fewer &rarr; more failures</span>
	<span><i class="ys-zero" style="border:1px solid #E5DECC"></i>covered, none recorded</span>
	<span><i class="ys-gap"></i>no records</span>
</div>

<table class="ys-grid">
<colgroup>
	<col class="c-yr">
	<?php for($m=1;$m<=12;$m++){ echo '<col>'; } ?>
	<col class="c-tot">
</colgroup>
<thead>
<tr>
	<th></th>
	<?php for($m=1;$m<=12;$m++){ ?><th title="<?php echo htmlspecialchars($mFull[$m]); ?>"><?php echo htmlspecialchars($mAbbr[$m]); ?></th><?php } ?>
	<th>Total</th>
</tr>
</thead>
<tbody>
<?php foreach($years as $y){ ?>
<tr>
	<th class="yr"><?php echo (int)$y; ?></th>
	<?php for($m=1;$m<=12;$m++){
		$st = $state[$y][$m];
		if($st === 'future'){ echo '<td><span class="ys-cell ys-future"></span></td>'; continue; }
		if($st === 'gap'){
			echo '<td><span class="ys-cell ys-gap" title="'.htmlspecialchars($mFull[$m].' '.$y).' &mdash; no records">&mdash;</span></td>';
			continue;
		}
		$c = isset($grid[$y][$m]) ? (int)$grid[$y][$m] : 0;
		$url = htmlspecialchars(ysCellUrl($y,$m,$car,$equipt,$level,$equipts));
		$ttl = htmlspecialchars($mFull[$m].' '.$y).' &mdash; '.$c.' car-level failure'.($c==1?'':'s');
		/* @monthpanel -- The href is the real target and stays real: it keeps
		   middle-click, ctrl-click and a no-JS load working, and it is what the
		   panel's own fallback link points at when the iframe will not load.
		   The onclick intercepts a plain left click and opens the panel
		   instead, matching openMonthPanel() in car_statistics_report.php.

		   Not attached under embed=1: this page is itself inside a panel
		   iframe then, and a second panel nested in an 820px frame is worse
		   than navigating the frame, which is what the bare href does. */
		$oc = $IR_EMBED ? '' :
		      ' onclick="return ysCellClick(event,this,\''
		      . htmlspecialchars(addslashes(ysCellTitle($y,$m)), ENT_QUOTES) . '\')"';
		if($c === 0){
			echo '<td><a class="ys-cell ys-zero" href="'.$url.'" title="'.$ttl.'"'.$oc.'>0</a></td>';
			continue;
		}
		/* Five steps, rebuilt every load from the values present. */
		$band = ($vHi > $vLo) ? (int)floor((($c - $vLo) / ($vHi - $vLo)) * 5) : 2;
		if($band > 4) $band = 4;
		echo '<td><a class="ys-cell ys-s'.$band.'" href="'.$url.'" title="'.$ttl.'"'.$oc.'>'.$c.'</a></td>';
	} ?>
	<?php
	/* The row total sits OUTSIDE the shading scale on purpose: shaded on the
	   same ramp it would be the darkest cell in every row and the eye would
	   read twelve months of pale against one dark block as a trend. The
	   coverage denominator rides with it -- a four-month year totalling less
	   than a twelve-month one is arithmetic, not improvement. */
	$cm = $coveredByYear[$y];
	$partial = ($cm > 0 && $cm < 12 && !($y === $nowY));
	?>
	<td class="ys-tot<?php echo ($peakY && $y === $peakY) ? ' ys-peak' : ''; ?>"><?php echo (int)$yearTotal[$y]; ?><br>
		<span class="ys-rate<?php echo $partial ? ' ys-part' : ''; ?>"><?php
			echo $cm ? ($cm.'/12 mo') : 'no data';
		?></span></td>
</tr>
<?php } ?>
</tbody>
<tfoot>
<tr class="ys-foot">
	<th class="yr">All</th>
	<?php for($m=1;$m<=12;$m++){
		$sum = isset($monthTotal[$m]) ? (int)$monthTotal[$m] : 0;
		$cm  = isset($coveredByMonth[$m]) ? (int)$coveredByMonth[$m] : 0;
		$rate = ysRate($sum, $cm);
		/* The rate leads and the total follows. While the archive is uneven,
		   comparing March's total against July's compares how much of each
		   has been recovered, not how bad each was. */
		$pk = ($peakM && $m === $peakM) ? ' ys-peak' : '';
		echo '<td class="ys-tot'.$pk.'">'.($rate === null ? '&mdash;' : $rate).'<br>'
		   . '<span class="ys-rate">'.($cm ? $sum.' in '.$cm.'y' : 'no data').'</span></td>';
	} ?>
	<td class="ys-tot"><?php echo (int)$total; ?><br><span class="ys-rate">all</span></td>
</tr>
</tfoot>
</table>

<p class="ys-note">
	The bottom row is failures <b>per covered year</b> for that month, with the raw total beneath it.
	Rates rather than totals, because a month recovered for three years and one recovered for eight
	are not comparable on totals alone &mdash; that gap closes as records come back, and until it does
	the rate is the figure to read.
	Figures count <b>car-level failures</b>: an incident affecting three cars counts once against each.
	This is the basis the equipment and per-car reports use, so they reconcile; the incident history
	logs count one row per incident and show a smaller figure.
	<?php if(!$IR_EMBED){ ?>
	Any cell click opens that month in the period panel.
	<?php } ?>
</p>

<?php if(!$ysHasCoverage){ ?>
<p class="ys-note" style="color:#7A1F1F;">
	<b>Coverage table unavailable.</b> Without data_coverage.php this page cannot tell a month with no
	failures from a month with no records, so every such cell is drawn as a zero. Treat quiet stretches
	with suspicion until it is in place.
</p>
<?php } ?>

<?php } ?>

</div><!-- /.ccs-panel-body -->
</div><!-- /.ccs-panel -->
</div><!-- /.ccs-page -->

<script>
/* Printout. The grid is already a table, so unlike the chart-bearing reports
   there is nothing to rasterize -- the markup is cloned straight across and
   restyled for paper. Landscape: thirteen columns do not fit portrait. */
function ysPrintReport(){
	var tbl = document.querySelector('.ys-grid');
	if(!tbl){ return; }
	var win = window.open('', '_blank');
	if(!win){ alert('Allow pop-ups to generate the printout.'); return; }

	win.document.write(
		'<html><head><title>Failures by year and month</title><style>' +
			'@page{ size:A4 landscape; margin:12mm 10mm 13mm; }' +
			'body{ font-family:Arial,Helvetica,sans-serif; color:#1a1a1a; font-size:9px; margin:0; }' +
			'.rpt-head{ border-bottom:2px solid #1f4e79; padding-bottom:6px; margin-bottom:8px; }' +
			'.rpt-title{ font-size:15px; font-weight:bold; color:#1f4e79; margin:0; }' +
			'.rpt-subject{ font-size:10px; color:#374151; margin:3px 0 0; }' +
			'.rpt-meta{ font-size:8.5px; color:#4b5563; margin:4px 0 0; }' +
			'table{ width:100%; border-collapse:collapse; table-layout:fixed; }' +
			'th,td{ border:1px solid #d1d5db; padding:3px 2px; text-align:center; font-size:8.5px; }' +
			'thead th{ background:#1f4e79; color:#fff; }' +
			'tbody th{ background:#f3f4f6; text-align:left; padding-left:4px; }' +
			'tfoot td, tfoot th{ background:#f3f4f6; font-weight:bold; }' +
			/* Screen shading does not survive to paper reliably, and a grey
			   ramp photocopies into mush. Paper leans on the numbers, and
			   marks only the two states a number cannot express. */
			'.ys-cell{ display:block; }' +
			'.ys-gap{ background:#e5e7eb; }' +
			'.ys-rate{ font-size:7.5px; color:#6b7280; display:block; }' +
			'.rpt-note{ font-size:8px; color:#6b7280; margin-top:8px; line-height:1.45; }' +
			'.rpt-foot{ margin-top:10px; border-top:1px solid #d1d5db; padding-top:5px; font-size:8px; color:#6b7280; }' +
		'</style></head><body>' +
			'<div class="rpt-head">' +
				'<p class="rpt-title">MRT-3 &mdash; Failures by Year and Month</p>' +
				'<p class="rpt-subject"><?php echo addslashes(strip_tags($ysScope)); ?></p>' +
				'<p class="rpt-meta">Car-level failures<?php
					echo addslashes($gapCells ? ' &middot; '.$gapCells.' month'.($gapCells==1?'':'s').' have no records and are shaded grey' : '');
				?></p>' +
			'</div>' +
			tbl.outerHTML +
			'<p class="rpt-note">Bottom row is failures per covered year for that month, raw total beneath. ' +
				'Grey cells have no records, which is not the same as no failures. ' +
				'An incident affecting three cars counts once against each.</p>' +
			'<div class="rpt-foot">MRT-3 Information Sharing System &middot; generated <?php echo date("d M Y, H:i"); ?> &middot; for internal operational use</div>' +
		'</body></html>'
	);
	win.document.close();
	win.onload = function(){ setTimeout(function(){ win.print(); }, 250); };
}
</script>

<?php if(!$IR_EMBED){ ?>
<!-- @monthpanel -- Its own backdrop; taOverlay belongs to train_operations.php.
     Emitted only when this page is NOT itself inside a panel iframe: nesting a
     second 820px panel inside one is worse than letting the cell's href
     navigate the frame it is already in. -->
<div class="ta-overlay" id="irOverlay" onclick="ysClosePanel()"></div>
<div class="ta-panel ta-panel--ir" id="irPanel" role="dialog" aria-modal="true" aria-labelledby="ir-panel-title">
	<div class="ta-panel-head">
		<h3 id="ir-panel-title">Period breakdown</h3>
		<button type="button" class="ta-panel-close" onclick="ysClosePanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body ta-panel-body--ir">
		<iframe id="irFrame" src="about:blank" title="Period breakdown" onload="ysFrameLoaded()"></iframe>
		<div class="ir-loading" id="irLoading">
			<div class="ir-spinner"></div>
			<span>Loading failure table&hellip;</span>
		</div>
		<div class="ir-fallback hidden" id="irFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The page may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="irFallbackLink" target="_blank" rel="noopener">Open the month in a new tab &rarr;</a>
		</div>
	</div>
</div>
<script>
/* @monthpanel -- Same shape as openMonthPanel() in car_statistics_report.php.
   The URL is taken from the anchor rather than rebuilt in JS, so the panel and
   the fallback link cannot target different months, and the token and filters
   are carried by whatever ysCellUrl() already put in the href. */
var irLoadTimer = null, irExpectingLoad = false;

function ysCellClick(e, a, title){
	/* Let the browser have the click when the operator asked for a new tab or
	   window -- a panel that hijacks ctrl-click makes a grid of 100+ cells
	   impossible to compare side by side. */
	if(e && (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1)){ return true; }
	if(!document.getElementById('irPanel')){ return true; }   /* navigate instead */
	ysOpenPanel(a.href, title);
	return false;
}

function ysOpenPanel(url, title){
	document.getElementById('ir-panel-title').textContent = title;
	document.getElementById('irFallbackLink').href = url;
	var frame = document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad = true;
	frame.src = url + '&embed=1';
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('irOverlay').classList.add('active');
	irLoadTimer = setTimeout(function(){
		if(irExpectingLoad){ document.getElementById('irFallback').classList.remove('hidden'); }
	}, 6000);
}

function ysClosePanel(){
	var p = document.getElementById('irPanel');
	if(!p){ return; }
	p.classList.remove('active');
	clearTimeout(irLoadTimer);
	irExpectingLoad = false;
	document.getElementById('irFrame').src = 'about:blank';   /* release the framed page */
	var ov = document.getElementById('irOverlay');
	if(ov){ ov.classList.remove('active'); }
}

function ysFrameLoaded(){
	if(!irExpectingLoad){ return; }   /* ignore the about:blank resets */
	irExpectingLoad = false;
	clearTimeout(irLoadTimer);
	document.getElementById('irLoading').classList.add('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	document.getElementById('irFrame').classList.add('ready');
}

document.addEventListener('keydown', function(e){
	if(e.key === 'Escape'){ ysClosePanel(); }
});
</script>
<?php } ?>
</body>