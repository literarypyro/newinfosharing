<?php
/* ============================================================================
 * type_scale.php — single-point fluid type scale for the MRT-3 ISS console.
 *
 * WIRING (one line per page, immediately AFTER the Tmenu_2.php require):
 *
 *     require("Tmenu_2.php");
 *     if (file_exists(dirname(__FILE__)."/type_scale.php")) require_once(dirname(__FILE__)."/type_scale.php");
 *
 * ORDER RULE (same one datepicker_theme.php follows): this file MUST be
 * emitted after every stylesheet and <style> block on the page. Almost every
 * rule below ties on specificity with the page's own rule and wins only on
 * source order. Putting this in <head> will do nothing for pages that set
 * td{font-size:12px} in their own head <style>.
 *
 * WHAT IT DOES
 *   1. Sets a fluid root font-size, so every rem/em on the page scales with
 *      the viewport.
 *   2. Re-declares the legacy element-level px sizes as fluid values, so the
 *      hardcoded 11/12/13px rules in the old pages scale too.
 *   3. Adds an optional A- / A / A+ control that multiplies the whole scale
 *      and remembers the choice per browser.
 *
 * WHAT IT DELIBERATELY DOES NOT DO
 *   - It does not touch inline style="font-size:..." attributes unless
 *     CCS_TYPE_SCALE_FORCE is defined (see below). Those need !important and
 *     that is a blunt instrument on a legacy codebase.
 *   - It does not affect the print popups. Those are separate documents built
 *     in JS and never see this file. On-page printing is reset to pt below.
 *
 * TUNING KNOBS — define any of these BEFORE the include to change behaviour:
 *   CCS_TYPE_SCALE_CONTROL  false  drop the A- / A+ widget, keep the scaling
 *   CCS_TYPE_SCALE_LEGACY   false  skip the element sweep (section 2)
 *   CCS_TYPE_SCALE_FORCE    true   add !important to the sweep, beats inline
 * ========================================================================== */

if (!defined('CCS_TYPE_SCALE')) {
	define('CCS_TYPE_SCALE', '1.0');

	if (!defined('CCS_TYPE_SCALE_CONTROL')) define('CCS_TYPE_SCALE_CONTROL', true);
	if (!defined('CCS_TYPE_SCALE_LEGACY'))  define('CCS_TYPE_SCALE_LEGACY',  true);
	if (!defined('CCS_TYPE_SCALE_FORCE'))   define('CCS_TYPE_SCALE_FORCE',   false);

	$ccsTsBang = CCS_TYPE_SCALE_FORCE ? ' !important' : '';

	echo "\n<!-- ccs-type-scale LOADED from ".basename(dirname(__FILE__))." -->\n";
?>
<style id="ccs-type-scale">
/* ---------------------------------------------------------------------------
 * 1. The scale itself.
 *
 * Each step is clamp(floor, slope*vw + intercept, ceiling). The slope is what
 * makes it responsive; the floor and ceiling stop a 1366 laptop from getting
 * illegible text and a 4K wall panel from getting comic-book text.
 *
 * Reference sizes for --ccs-fs-md: 360px -> 13px (floor), 768 -> 14.0px,
 * 1024 -> 15.6px, 1366 -> 17.7px, 1600+ -> 19px (ceiling).
 * ------------------------------------------------------------------------- */
:root{
	--ccs-ui-scale: 1;                                   /* A- / A+ multiplier */

	--ccs-fs-xs:   clamp(11px, 0.30vw +  9.3px, 14px);   /* was 10-11px: captions, footnotes */
	--ccs-fs-sm:   clamp(12px, 0.46vw +  9.6px, 16px);   /* was 11-12px: table body, labels  */
	--ccs-fs-md:   clamp(13px, 0.62vw +  9.2px, 19px);   /* was 12-13px: base UI text        */
	--ccs-fs-lg:   clamp(15px, 0.85vw + 10.0px, 23px);   /* was 14-16px: section heads       */
	--ccs-fs-xl:   clamp(18px, 1.25vw + 11.0px, 30px);   /* was 18-20px: page title          */
	--ccs-fs-2xl:  clamp(22px, 1.90vw + 12.0px, 40px);   /* KPI tile numbers                 */

	--ccs-lh-tight: 1.25;
	--ccs-lh-body:  1.45;
}

html{
	font-size: calc(var(--ccs-fs-md) * var(--ccs-ui-scale));
	-webkit-text-size-adjust: 100%;
	text-size-adjust: 100%;
}

/* Everything that never had an explicit size now inherits the fluid base. */
body{
	font-size: 1rem;
	line-height: var(--ccs-lh-body);
}

/* ---------------------------------------------------------------------------
 * 1b. Wall-monitor tier.
 *
 * dashboard_wall.php is read from across the OCC, not from a chair. Add
 * class="ds-wall" to its <body> (or <html>) and the whole scale steps up
 * without a second stylesheet.
 * ------------------------------------------------------------------------- */
.ds-wall, .ds-wall :root, html.ds-wall{
	--ccs-fs-xs:   clamp(14px, 0.55vw + 10.0px, 20px);
	--ccs-fs-sm:   clamp(16px, 0.75vw + 11.0px, 24px);
	--ccs-fs-md:   clamp(18px, 1.00vw + 11.5px, 28px);
	--ccs-fs-lg:   clamp(22px, 1.45vw + 12.5px, 36px);
	--ccs-fs-xl:   clamp(28px, 2.20vw + 13.0px, 52px);
	--ccs-fs-2xl:  clamp(36px, 3.40vw + 13.5px, 80px);
}
html.ds-wall{ font-size: calc(var(--ccs-fs-md) * var(--ccs-ui-scale)); }

<?php if (CCS_TYPE_SCALE_LEGACY): ?>
/* ---------------------------------------------------------------------------
 * 2. Legacy sweep.
 *
 * The old pages set px sizes on bare element selectors. Because this block is
 * emitted last, these single-element selectors beat the page's identical ones
 * on source order. Anything the page styles by CLASS still wins (correctly —
 * that is the page making a deliberate choice), which is why the console's own
 * components are listed separately in section 2b.
 * ------------------------------------------------------------------------- */
p, li, dd, dt, div, span,
td, th, caption,
label, legend,
a{ font-size: inherit<?php echo $ccsTsBang; ?>; }

table{ font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>; }
td, th{ line-height: var(--ccs-lh-body)<?php echo $ccsTsBang; ?>; }

/* Form controls do not inherit font by default in any browser. */
input, select, textarea, button, optgroup, option{
	font-family: inherit<?php echo $ccsTsBang; ?>;
	font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>;
}

small, sub, sup, .note, .cap, .hint, .footnote{ font-size: var(--ccs-fs-xs)<?php echo $ccsTsBang; ?>; }

h1{ font-size: var(--ccs-fs-xl)<?php echo $ccsTsBang; ?>;  line-height: var(--ccs-lh-tight); }
h2{ font-size: var(--ccs-fs-lg)<?php echo $ccsTsBang; ?>;  line-height: var(--ccs-lh-tight); }
h3{ font-size: var(--ccs-fs-md)<?php echo $ccsTsBang; ?>;  line-height: var(--ccs-lh-tight); }
h4, h5, h6{ font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>; line-height: var(--ccs-lh-tight); }

/* ---------------------------------------------------------------------------
 * 2b. Console components.
 *
 * These are class selectors, so they need to be named explicitly — element
 * rules above cannot reach them. Add a line here when a component turns up
 * that still looks small; this is the one place to do it.
 * ------------------------------------------------------------------------- */
#navMenu, #navMenu a{ font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>; }

.ph-filters, .ph-filters label, .ph-filters input,
.ph-filters select, .ph-filters button{ font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>; }

.ins-head{ font-size: var(--ccs-fs-lg)<?php echo $ccsTsBang; ?>; }
.ins-body, .ins-exec, .ins-tech{ font-size: var(--ccs-fs-md)<?php echo $ccsTsBang; ?>; }

.dataTables_wrapper, .dataTables_filter, .dataTables_length,
.dataTables_info, .dataTables_paginate{ font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>; }

.ui-datepicker{ font-size: var(--ccs-fs-sm)<?php echo $ccsTsBang; ?>; }
<?php endif; ?>

/* ---------------------------------------------------------------------------
 * 3. Print reset.
 *
 * vw units resolve against the paper width when printing, which is not what
 * anyone wants. Freeze the on-page print path to points.
 * ------------------------------------------------------------------------- */
@media print{
	:root{ --ccs-ui-scale: 1; }
	html{ font-size: 10pt; }
	body{ font-size: 10pt; }
	table, td, th, input, select{ font-size: 9pt !important; }
	h1{ font-size: 15pt !important; }
	h2{ font-size: 12pt !important; }
	h3{ font-size: 10.5pt !important; }
	#ccs-ts-control{ display: none !important; }
}

/* ---------------------------------------------------------------------------
 * 4. The A- / A / A+ control.
 * ------------------------------------------------------------------------- */
#ccs-ts-control{
	position: fixed;
	right: 12px;
	bottom: 12px;
	z-index: 9500;                    /* below .ui-datepicker's 10000 */
	display: flex;
	align-items: center;
	gap: 2px;
	padding: 3px;
	border: 1px solid #C9CFDA;
	border-radius: 999px;
	background: #FFFFFF;
	box-shadow: 0 2px 8px rgba(0,0,0,.16);
	font-family: inherit;
	line-height: 1;
}
#ccs-ts-control button{
	appearance: none;
	border: 0;
	background: transparent;
	color: #00529B;
	cursor: pointer;
	padding: 0;
	width: 28px;
	height: 28px;
	border-radius: 999px;
	font-weight: 700;
	font-size: 13px;
	line-height: 1;
}
#ccs-ts-control button:hover{ background: #EAF1F8; }
#ccs-ts-control button:focus-visible{ outline: 2px solid #FDB813; outline-offset: 1px; }
#ccs-ts-control button[data-step="down"]{ font-size: 11px; }
#ccs-ts-control button[data-step="up"]{ font-size: 16px; }
#ccs-ts-control button[data-step="reset"]{ font-size: 13px; color: #55606E; }
#ccs-ts-control[data-scale="1"] button[data-step="reset"]{ opacity: .45; }

@media (max-width: 767px){
	#ccs-ts-control{ right: 8px; bottom: 8px; }
}
</style>
<?php if (CCS_TYPE_SCALE_CONTROL): ?>
<script>
/* ccs-type-scale: user multiplier on top of the responsive scale.
   Runs immediately (not on DOMContentLoaded) for the apply step so there is no
   flash of unscaled text; the widget itself waits for the body. */
(function(){
	if (window.ccsTypeScaleReady) { return; }
	window.ccsTypeScaleReady = true;

	var KEY   = 'ccsUiScale';
	var STEPS = [0.85, 0.925, 1, 1.1, 1.2, 1.35, 1.5];
	var root  = document.documentElement;

	function read(){
		var v;
		try { v = parseFloat(window.localStorage.getItem(KEY)); } catch(e) { v = NaN; }
		return (isFinite(v) && v >= 0.85 && v <= 1.5) ? v : 1;
	}
	function apply(v){
		root.style.setProperty('--ccs-ui-scale', String(v));
		var box = document.getElementById('ccs-ts-control');
		if (box) { box.setAttribute('data-scale', String(v)); }
		try { window.localStorage.setItem(KEY, String(v)); } catch(e) {}
	}
	function nudge(dir){
		var cur = read(), i = 0, best = 0, d = 99;
		for (i = 0; i < STEPS.length; i++) {
			if (Math.abs(STEPS[i] - cur) < d) { d = Math.abs(STEPS[i] - cur); best = i; }
		}
		best = Math.max(0, Math.min(STEPS.length - 1, best + dir));
		apply(STEPS[best]);
	}

	apply(read());
	window.ccsSetUiScale = apply;      /* console/other pages can call this */

	/* No widget inside the slide panel or any other iframe — it would stack
	   on top of the host page's copy. */
	var embedded = false;
	try { embedded = (window.self !== window.top); } catch(e) { embedded = true; }
	if (embedded) { return; }

	function build(){
		if (document.getElementById('ccs-ts-control')) { return; }
		var box = document.createElement('div');
		box.id = 'ccs-ts-control';
		box.setAttribute('role', 'group');
		box.setAttribute('aria-label', 'Text size');
		box.setAttribute('data-scale', String(read()));
		box.innerHTML =
			'<button type="button" data-step="down"  title="Smaller text" aria-label="Smaller text">A</button>' +
			'<button type="button" data-step="reset" title="Reset text size" aria-label="Reset text size">A</button>' +
			'<button type="button" data-step="up"    title="Larger text" aria-label="Larger text">A</button>';
		box.onclick = function(ev){
			var t = ev.target;
			if (!t || t.tagName !== 'BUTTON') { return; }
			var step = t.getAttribute('data-step');
			if (step === 'up')    { nudge(1); }
			if (step === 'down')  { nudge(-1); }
			if (step === 'reset') { apply(1); }
		};
		document.body.appendChild(box);
	}

	if (document.body) { build(); }
	else if (document.addEventListener) { document.addEventListener('DOMContentLoaded', build, false); }
	else { window.onload = build; }
})();
</script>
<?php endif; ?>
<?php
} /* end CCS_TYPE_SCALE guard */
?>