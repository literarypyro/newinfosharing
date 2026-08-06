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
.ph-filters{display:flex;flex-wrap:wrap;align-items:flex-end;gap:10px;}
.ph-field{display:flex;flex-direction:column;gap:3px;}
.ph-field > label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:#5A6275;font-weight:600;}
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
.ph-filters button{
	height:30px;background:#FDB813;color:#3A2D00;border:none;border-radius:4px;
	padding:0 16px;font-size:12px;font-weight:700;cursor:pointer;font-family:inherit;
}
.ph-filters button:hover{background:#E5A50F;}
.ph-clear{font-size:11px;color:#5A6275;text-decoration:none;align-self:center;padding-bottom:7px;}
.ph-clear:hover{color:#7A1F1F;text-decoration:underline;}

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

	<button type="submit"><?php echo htmlspecialchars($submitLabel); ?></button>
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