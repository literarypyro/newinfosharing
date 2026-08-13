<?php
/* =========================================================================
   period_filter.php — the Year/Month vs Date-range filter, in one place.

   Written for problem_history.php, other_history.php and td_history.php.
   Three copies of this logic would drift: the label wording, the mode
   gating, the datepicker options and the chart-fill rule all have to agree,
   and they only stay agreeing if there is one of them.

   Usage:

       require_once("period_filter.php");
       $ph = phResolvePeriod($_GET);              // or $_POST
       ... " where x='y' ".$ph['clause']." order by ..."
       phFilterCss();                             // once, inside <head>/<style>
       phFilterFields($ph);                       // inside your <form>
       phDatepickerJs();                          // once, after jQuery UI

   Four modes. The mode is explicit rather than inferred from which
   parameters happen to be present, because both sets of controls on screen
   with one silently winning is the confusing part:

       mode=period, y + m   -> one month of one year
       mode=period, y       -> one whole year
       mode=period, m       -> that month across EVERY year ("all Marches")
       mode=range,  sd + ed -> exact date range
       (nothing)            -> all time

   Links without &mode= still work; the mode is inferred from what arrived.
   ========================================================================= */

function phResolvePeriod($src, $col = 'incident_date'){
	$mode = isset($src['mode']) ? $src['mode'] : '';
	if($mode !== 'period' && $mode !== 'range'){
		$mode = (isset($src['sd']) && $src['sd'] !== '') ? 'range' : 'period';
	}

	$year  = isset($src['y']) && $src['y'] !== '' ? (int)$src['y'] : 0;
	$month = isset($src['m']) && $src['m'] !== '' ? (int)$src['m'] : 0;
	if($month < 1 || $month > 12) $month = 0;

	$sd = isset($src['sd']) && $src['sd'] !== '' ? strtotime($src['sd']) : false;
	$ed = isset($src['ed']) && $src['ed'] !== '' ? strtotime($src['ed']) : false;

	$isRange = ($mode === 'range' && $sd !== false && $ed !== false);
	// Swap rather than clamp: a mis-entered From/To still answers the question.
	if($isRange && $ed < $sd){ $t=$sd; $sd=$ed; $ed=$t; }
	if($mode === 'range'){ $year = 0; $month = 0; }   // one mode at a time

	$clause    = "";
	$label     = "All time";
	$monthOnly = false;

	if($isRange){
		$clause = " and ".$col." between '".date("Y-m-d",$sd)." 00:00:00' and '".date("Y-m-d",$ed)." 23:59:59' ";
		$label  = date("d M Y",$sd)." to ".date("d M Y",$ed);
	}
	else if($year && $month){
		$clause = " and ".$col." like '".sprintf("%04d-%02d",$year,$month)."-%' ";
		$label  = date("F Y", strtotime(sprintf("%04d-%02d-01",$year,$month)));
	}
	else if($year){
		$clause = " and ".$col." like '".$year."-%' ";
		$label  = "Year ".$year;
	}
	else if($month){
		// month() rather than LIKE because the year varies. This is the only
		// mode that is not a contiguous window — see 'monthOnly' below.
		$clause    = " and month(".$col.") = ".$month." ";
		$label     = date("F", strtotime(sprintf("2000-%02d-01",$month)))." (all years)";
		$monthOnly = true;
	}

	$qs = "&mode=".$mode;
	if($isRange){ $qs .= "&sd=".urlencode(date("Y-m-d",$sd))."&ed=".urlencode(date("Y-m-d",$ed)); }
	else {
		if($year)  $qs .= "&y=".$year;
		if($month) $qs .= "&m=".$month;
	}

	return array(
		'mode'      => $mode,
		'year'      => $year,
		'month'     => $month,
		'sd'        => $sd,
		'ed'        => $ed,
		'isRange'   => $isRange,
		'clause'    => $clause,
		'label'     => $label,
		/* True for the month-across-all-years mode. Any chart that fills gaps
		   between its first and last bucket must NOT fill when this is set:
		   the span between two Marches is mostly months the filter excluded,
		   and zero bars there would say "no incidents" when they mean "not
		   asked for". */
		'monthOnly' => $monthOnly,
		'qs'        => $qs,
		'active'    => ($clause !== '')
	);
}

function phFilterCss(){ ?>
/* Controls: one height for everything in the row. Selects and inputs take
   different default padding, which is what makes an unstyled filter row look
   ragged. */
/* superseded by the grid rule below -- see @toolbaralign */
/* @toolbaralign -- Reverting the shape, not patching the alignment again.

   Every one of my four attempts tried to make a TWO-ROW bar line up: labels
   on one line, controls beneath. Flex-end, a spacer label, a fixed-height
   grid, then a real two-row grid -- each fixed the case I could reason about
   and broke another, and the last two clipped the labels because row 1 kept
   being sized wrong for this page's ancestors.

   The two-row shape is the problem, and I introduced it on 06 Aug when I
   added visible labels to a bar that previously had none. A single row of
   equal-height controls has no cross-axis question to get wrong: there is one
   line, everything is centred on it, and no ancestor height can push one item
   out of step with another.

   So the labels stay -- they were worth adding -- but they sit INLINE before
   their control instead of above it. */
/* @toolbaralign -- SPECIFICITY, which is what I should have checked first.
   This page's own <style> block is emitted BEFORE history_theme.php is
   included, so any rule the theme carries for button / input / select at the
   same specificity WINS -- later wins at equal weight. That is consistent with
   what we have seen for five rounds: the markup is right (button and selects
   are siblings inside form.ph-filters, confirmed against the parsed DOM), the
   rules read correctly in the file, and none of them take effect on the
   control that a shared theme is most likely to style -- the button.

   The alignment-critical declarations are therefore given a tag-qualified
   selector AND !important. Not a preference, a necessity: without seeing
   history_theme.php I cannot know its specificity, and this is the only way
   to be sure these win regardless. */
/* @toolbaralign -- The !important flags that were here are gone. The real
   cause turned out to be in history_theme.php: it set the fields to 28px and
   the submit to 30px, and it links bootstrap.min.css, whose
   "input, select { margin-bottom:9px }" applies to the fields but not to a
   <button>. Both are fixed at the root now, so these rules only have to
   describe the layout. */
form.ph-filters{
	display:flex;flex-wrap:wrap;align-items:center;gap:6px 14px;
}
form.ph-filters div.ph-field{display:flex;align-items:center;gap:6px;}
/* The theme also zeroes the margin on these (see @toolbaralign there); the
   height stays 30px here because this bar is its own thing and is not mixed
   with .stat-toolbar controls on any page. */
form.ph-filters select,
form.ph-filters input[type=text],
form.ph-filters button,
form.ph-filters input[type=submit]{
	height:30px;box-sizing:border-box;margin:0;vertical-align:middle;
}
/* @toolbaralign -- The labels read a touch high. align-items:center centres
   the label's BOX, not its glyphs, and at line-height:1 the box hugs the em
   square -- which reserves descender space that all-caps text never uses, so
   the letters sit above the box's optical centre.

   Matching the label's line-height to the control height (30px) makes both
   boxes the same and lets the text centre inside its own line box, which is
   where the glyphs actually settle correctly. */
.ph-field > label{white-space:nowrap;line-height:30px;font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#5A6275;font-weight:600;}
.ph-field { position:relative; top:10px; }

.ph-clear { position:relative; top:10px; }

.ph-inline{display:flex;align-items:center;gap:6px;}
.ph-sep{font-size:11px;color:#5A6275;}
.ph-filters select,
.ph-filters input[type=text]{
	height:30px;box-sizing:border-box;padding:4px 8px;
	border:1px solid #D8D2C2;border-radius:4px;
	font-size:12px;font-family:inherit;background:#FFFFFF;color:#1A2238;
}
/* readonly is what turns a date field grey: browsers give readonly inputs
   their DISABLED styling, which leaves the value barely legible. The picker
   still owns the field, but it should not look switched off. WebKit needs
   -webkit-text-fill-color separately or it greys the text regardless. */
.ph-filters input[readonly]{
	background:#FFFFFF;color:#1A2238;cursor:pointer;opacity:1;
	-webkit-text-fill-color:#1A2238;
}
.ph-filters input[readonly]:hover{border-color:#00529B;}
.ph-filters input[readonly]:focus{outline:2px solid #00529B;outline-offset:-1px;}
.ph-filters input::placeholder{color:#8A93A6;opacity:1;}
/* @toolbaralign -- Normalising, not a demonstrated fix. The button and the
   fields are both 30px here and the row is flex with align-items:flex-end, so
   on paper the bottoms already line up -- unlike the .stat-toolbar pages,
   where the offset has a provable cause. These declarations remove the ways a
   drift could still creep in: box-sizing so 30px means the same thing on a
   bordered field and a borderless button, align-self so an inherited
   align-items cannot override the row's, and margin:0 against UA defaults. */
/* @toolbaralign -- The Apply button sat ~13px below the fields in Firefox,
   and align-self:flex-end did not help. 13px is the height of a label line,
   which is the tell: every OTHER control is inside a .ph-field whose first
   line box is its <label>, and the button had no label at all. Whenever the
   row falls back to baseline alignment -- which it does if the flex rule is
   not in force, and Firefox reaches it more readily here -- a flex/inline
   item's baseline is the baseline of its FIRST line box. The fields' first
   line is the label; the button's is its own caption. So the button's caption
   was being lined up with the labels, dropping the whole button by one label
   height.

   Fixed structurally rather than with more alignment properties: the button
   now sits in a .ph-field with a spacer label, so it has the same two-line
   box as every other control. That lines up under flex-end AND under
   baseline, so it no longer depends on which one wins. */
/* same 16px/30px template as every other field -- nothing special needed */
/* @toolbaralign -- one row now, so the action needs no spacer label. */
.ph-filters .ph-field--action > label{display:none;}
/* @toolbaralign -- one shared height for everything on the line. */
.ph-filters button{
	height:30px;box-sizing:border-box;margin:0;vertical-align:middle;
	background:#FDB813;color:#3A2D00;border:none;border-radius:4px;
	padding:0 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;
}
/* Some callers submit with an <input> rather than a <button>; it was picking
   up none of the rules above. */
.ph-filters input[type=submit]{
	height:30px;box-sizing:border-box;margin:0;vertical-align:middle;
	background:#FDB813;color:#3A2D00;border:none;border-radius:4px;
	padding:0 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;
}
.ph-filters input[type=submit]:hover{background:#E5A50F;}
.ph-filters button:hover{background:#E5A50F;}
/* @toolbaralign -- the Clear link is a bare child of the row for the same
   reason the button was; align-self:center + a padding fudge was guesswork.
   Bottom-aligned to the control line instead, matching the button's height so
   its text sits on the same line. */
/* @clearbtn -- Clear was bare underlined text next to a filled gold button,
   which read as an afterthought rather than a control. It is given the same
   30px pill geometry as everything else on the line, but OUTLINED rather than
   filled: Apply is the primary action and should stay the only solid block of
   colour in the row. Turning Clear gold too would make the row ask which of
   two equal buttons you meant.

   The x is drawn from ::before rather than typed into the markup, so the glyph
   cannot be selected with the label text or read out twice by a screen reader. */
.ph-clear{
	display:inline-flex;align-items:center;gap:5px;
	height:30px;padding:0 12px;box-sizing:border-box;
	border:1px solid #D8D2C2;border-radius:4px;background:#FFFFFF;
	color:#5A6275;font-size:11px;font-weight:600;font-family:inherit;
	text-decoration:none;white-space:nowrap;cursor:pointer;
	transition:border-color .12s,color .12s,background .12s;
}
.ph-clear::before{content:"\00D7";font-size:15px;line-height:1;margin-top:-1px;}
/* Red only on hover: a permanently red control in a filter bar reads as a
   warning, when this just widens the view again. */
.ph-clear:hover{border-color:#C98B8B;color:#7A1F1F;background:#FBF3F3;text-decoration:none;}
.ph-clear:focus-visible{outline:2px solid #00529B;outline-offset:1px;}

/* jQuery UI ships z-index 1 on .ui-datepicker and the console theme puts the
   table header above that, so the calendar draws UNDER the table. It is
   appended to <body>, so it has to beat everything on the page. The rest
   re-skins the stock smoothness theme, which is grey-on-grey. */
.ui-datepicker{
	z-index:10000 !important;
	font-family:"Segoe UI",system-ui,Arial,sans-serif;font-size:12px;
	background:#FFFFFF;border:1px solid #C9CFDA;border-radius:6px;
	box-shadow:0 6px 20px rgba(0,30,80,.18);padding:6px;
}
.ui-datepicker .ui-datepicker-header{background:#00529B;border:none;border-radius:4px;color:#FFFFFF;padding:5px 4px;}
.ui-datepicker .ui-datepicker-title{color:#FFFFFF;font-weight:600;}
.ui-datepicker .ui-datepicker-title select{
	background:#FFFFFF;color:#1A2238;border:1px solid #00529B;border-radius:3px;
	font-size:12px;padding:1px 3px;margin:0 2px;
}
.ui-datepicker .ui-datepicker-prev,
.ui-datepicker .ui-datepicker-next{background:none;border:none;cursor:pointer;top:6px;}
.ui-datepicker .ui-datepicker-prev span,
.ui-datepicker .ui-datepicker-next span{filter:brightness(0) invert(1);}
.ui-datepicker th{background:none;color:#5A6275;font-size:10px;text-transform:uppercase;font-weight:600;border:none;padding:4px 0;}
.ui-datepicker td{border:none;padding:1px;}
.ui-datepicker td a,
.ui-datepicker td span{display:block;text-align:center;padding:5px 0;border:none;border-radius:3px;background:none;color:#1A2238;text-decoration:none;}
.ui-datepicker td a:hover{background:#E8F0F9;color:#00529B;}
.ui-datepicker td .ui-state-active{background:#00529B !important;color:#FFFFFF !important;}
.ui-datepicker td .ui-state-highlight{background:#FFF1CC;color:#3A2D00;}
.ui-datepicker td.ui-datepicker-unselectable span{color:#B8BEC9;}
<?php }

/* $db is needed only for the year list. $clearUrl is where "Clear" goes —
   pass the page's own URL with whatever non-period parameters it needs kept. */
/* $clearMode: 'link' for GET pages, 'reset' for POST pages. On a POST page a
   plain link to the page URL would also throw away the other POST fields (the
   personnel name on td_history.php), so there Clear blanks the period inputs
   and resubmits instead. */
/* $submitLabel: the page's own verb. td_history.php's button has always said
   "Retrieve" -- it submits the personnel search as well as the period, and
   renaming it to "Apply" changed what the page looked like it did. */
function phFilterFields($ph, $db, $clearUrl, $clearMode = 'link', $submitLabel = 'Apply'){ ?>
	<div class="ph-field">
	<label for="phMode">Filter by</label>
	<select name="mode" id="phMode" onchange="phSetMode(this.value)">
		<option value="period"<?php echo $ph['mode']==='period'?' selected':''; ?>>Year / month</option>
		<option value="range"<?php echo $ph['mode']==='range'?' selected':''; ?>>Date range</option>
	</select>
	</div>

	<div class="ph-field" id="phPeriodFields"<?php echo $ph['mode']==='range'?' style="display:none"':''; ?>>
	<label>Year / month</label>
	<div class="ph-inline">
	<select name="y" title="Year">
		<option value="">All years</option>
		<?php
		/* Years that actually hold records, so the list cannot offer an empty one. */
		$yrRS=$db->query("select distinct year(incident_date) as y from incident_report where incident_date is not null order by y desc");
		if($yrRS){ while($yr=$yrRS->fetch_assoc()){
			$yv=(int)$yr['y']; if($yv<=0) continue;
			echo "<option value='".$yv."'".($ph['year']==$yv?" selected":"").">".$yv."</option>";
		} }
		?>
	</select>
	<select name="m" title="Month">
		<option value="">All months</option>
		<?php for($mi=1;$mi<=12;$mi++){
			echo "<option value='".$mi."'".($ph['month']==$mi?" selected":"").">"
			   . date("F", strtotime(sprintf("2000-%02d-01",$mi)))."</option>";
		} ?>
	</select>
	</div>
	</div>

	<div class="ph-field" id="phRangeFields"<?php echo $ph['mode']==='range'?'':' style="display:none"'; ?>>
	<label for="ph_sd">Date range</label>
	<div class="ph-inline">
	<input type="text" name="sd" id="ph_sd" value="<?php echo $ph['isRange'] ? date("Y-m-d",$ph['sd']) : ''; ?>" placeholder="From" size="10" readonly>
	<span class="ph-sep">&rarr;</span>
	<input type="text" name="ed" id="ph_ed" value="<?php echo $ph['isRange'] ? date("Y-m-d",$ph['ed']) : ''; ?>" placeholder="To" size="10" readonly>
	</div>
	</div>

	<div class="ph-field ph-field--action">
	<?php /* @toolbaralign -- spacer label: aria-hidden so it is not announced,
	         visibility:hidden so it still occupies its line box. Without it the
	         button has one line box where every other field has two, and the
	         row cannot align. */ ?>
	<label aria-hidden="true">&nbsp;</label>
	<button type="submit"><?php echo htmlspecialchars($submitLabel); ?></button>
	</div>
	<?php if($ph['active']){
		if($clearMode === 'reset'){ ?>
		<a href="#" class="ph-clear" onclick="phClearPeriod(this.form); return false;">Clear</a>
	<?php } else { ?>
		<a href="<?php echo htmlspecialchars($clearUrl); ?>" class="ph-clear">Clear</a>
	<?php } } ?>
<?php }

function phDatepickerJs(){ ?>
<script>
/* Declared outside the jQuery block so the inline onchange can reach it
   regardless of script load order. '' restores the stylesheet's display
   rather than forcing a hard-coded value. */
function phClearPeriod(f){
	if(!f) return;
	if(f.y)  f.y.value  = '';
	if(f.m)  f.m.value  = '';
	if(f.sd) f.sd.value = '';
	if(f.ed) f.ed.value = '';
	f.submit();
}
function phSetMode(v){
	var p=document.getElementById('phPeriodFields');
	var r=document.getElementById('phRangeFields');
	if(p) p.style.display = (v==='range') ? 'none' : '';
	if(r) r.style.display = (v==='range') ? '' : 'none';
}
</script>
<script>
/* dateFormat must be yy-mm-dd: the PHP side parses with strtotime and stores
   Y-m-d. The two pickers bound to each other so a backwards range cannot be
   built by hand. */
if(window.jQuery) jQuery(function($){
	if(!$.fn.datepicker) return;
	var o = { dateFormat:'yy-mm-dd', changeMonth:true, changeYear:true, yearRange:'c-10:c+1' };
	var $sd = $('#ph_sd'), $ed = $('#ph_ed');
	if(!$sd.length) return;
	$sd.datepicker($.extend({}, o, { onSelect:function(d){ $ed.datepicker('option','minDate',d); } }));
	$ed.datepicker($.extend({}, o, { onSelect:function(d){ $sd.datepicker('option','maxDate',d); } }));
	if($sd.val()) $ed.datepicker('option','minDate',$sd.val());
	if($ed.val()) $sd.datepicker('option','maxDate',$ed.val());
});
</script>
<?php }