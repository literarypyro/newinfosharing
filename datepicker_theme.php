<?php
/* ============================================================================
   datepicker_theme.php -- console re-skin for the jQuery UI datepicker.

   WHY THIS FILE EXISTS
   problem_history.php had this block inline and its calendar looks like part
   of the console; every other page falls back to the stock jQuery UI
   "smoothness" theme, which is grey-on-grey and looks like a different app.
   Rather than paste the same 35 lines onto each page, they now include this.

   HOW TO USE IT -- ORDER MATTERS
   Include it AFTER whatever jQuery UI stylesheet the page links:

       <link rel="stylesheet" href="jquery-ui-themes-1.11.1/themes/smoothness/jquery-ui.css" />
       <?php if(file_exists(dirname(__FILE__)."/datepicker_theme.php")) include(dirname(__FILE__)."/datepicker_theme.php"); ?>

   Most of these rules tie with smoothness on specificity, so they win on
   source order alone. Put the include before the <link> and smoothness wins
   instead -- the calendar goes grey again with nothing in the console to say
   why. The file_exists guard is the standing rule for shared includes here:
   a page that reaches a server before this file does degrades to the old
   look rather than to a blank page.

   WHAT IT DELIBERATELY DOES NOT SET
   No width, position, or display. Those come from the page's base jQuery UI
   stylesheet and the widget breaks without them. This file is a skin laid
   over that base, never a replacement for it.

   Guarded against double inclusion so a page can include it and still pull in
   a partial that does the same.
   ========================================================================= */
if(!defined('CCS_DATEPICKER_THEME')){
	define('CCS_DATEPICKER_THEME', 1);
?>
<!-- ccs-datepicker-theme v2 LOADED from <?php echo dirname(__FILE__); ?> -->
<style>
/* @filterui -- The calendar was drawing UNDER the table: jQuery UI ships
   z-index 1 on .ui-datepicker and the console theme puts the table header
   above that. It is appended to <body>, so it needs to beat everything on the
   page, not just its neighbours. The rest re-skins the widget to the console
   palette -- the stock smoothness theme is grey-on-grey and does not belong
   next to this table. */
.ui-datepicker{
	z-index:10000 !important;
	font-family:"Segoe UI",system-ui,Arial,sans-serif;font-size:12px;
	background:#FFFFFF;border:1px solid #C9CFDA;border-radius:6px;
	box-shadow:0 6px 20px rgba(0,30,80,.18);padding:6px;
}
.ui-datepicker .ui-datepicker-header{
	background:#00529B;border:none;border-radius:4px;color:#FFFFFF;padding:5px 4px;
}
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
.ui-datepicker td span{
	display:block;text-align:center;padding:5px 0;border:none;border-radius:3px;
	background:none;color:#1A2238;text-decoration:none;
}
.ui-datepicker td a:hover{background:#E8F0F9;color:#00529B;}
.ui-datepicker td .ui-state-active{background:#00529B !important;color:#FFFFFF !important;}
.ui-datepicker td .ui-state-highlight{background:#FFF1CC;color:#3A2D00;}
.ui-datepicker td.ui-datepicker-unselectable span{color:#B8BEC9;}

/* @filterui -- smoothness paints a gradient IMAGE on the header and on every
   day cell, which survives a background-color change and shows through as a
   grey wash. problem_history never hit this because it links no jQuery UI
   theme at all; every page that does needs the images killed explicitly. */
.ui-datepicker .ui-datepicker-header,
.ui-datepicker td a,
.ui-datepicker td span{background-image:none;}

/* @filterui -- and the month/year dropdowns: smoothness pins them to 45%
   each, which with the padding above overflows the 17em widget and clips the
   year. Narrower, side by side, with room for the four-digit year. */
.ui-datepicker select.ui-datepicker-month{width:47%;}
.ui-datepicker select.ui-datepicker-year{width:47%;}
</style>
<script>
/* @dppos -- PLACEMENT FIX.
   Symptom: on a short window the calendar jumps to the very top of the page
   and lands on the logo instead of opening under the field.

   Cause is jQuery UI's own _checkOffset, not this theme's CSS:

       offset.top -= Math.min(offset.top,
           (offset.top + dpHeight > viewHeight && viewHeight > dpHeight)
               ? Math.abs(dpHeight + inputHeight) : 0);

   When the widget would run off the bottom it tries to flip above the input,
   but Math.min() clamps the shift to offset.top -- so if there is not enough
   room above (there almost never is, for a filter bar near the top of the
   page) it subtracts the WHOLE offset and pins top to exactly 0. That is the
   logo overlap: not a near-miss, an exact zero.

   Re-skinning made this bite more often rather than causing it. The console
   padding adds roughly 50px of height, which is enough to push
   input_bottom + height past the fold on a 1366x768 workstation where the
   stock widget just fit.

   Replaces the method rather than repositioning in beforeShow. beforeShow
   fires BEFORE jQuery UI writes top/left, and showAnim:"clip" then wraps the
   div in .ui-effects-wrapper which takes those coordinates with it -- so a
   later css() on the div moves nothing until the animation ends and the
   calendar visibly jumps. Correcting the number at source means the wrapper
   is built in the right place to begin with.

   isFixed is handed back to the original: that path subtracts scroll offsets
   for inputs inside position:fixed containers, which nothing here uses and
   which is not worth reimplementing blind. */
(function(){
	function patch(){
		var $ = window.jQuery;
		if(!$ || !$.datepicker || !$.datepicker._checkOffset) return false;
		if($.datepicker._ccsOffsetPatched) return true;
		$.datepicker._ccsOffsetPatched = true;

		var original = $.datepicker._checkOffset;

		$.datepicker._checkOffset = function(inst, offset, isFixed){
			if(isFixed){ return original.apply(this, arguments); }

			var dpW = inst.dpDiv.outerWidth(),
			    dpH = inst.dpDiv.outerHeight(),
			    inH = (inst.input && inst.input.length) ? inst.input.outerHeight() : 0,
			    /* innerWidth/Height first: this page has no DOCTYPE, and in quirks
			       mode documentElement.clientHeight is not the viewport. */
			    vw  = window.innerWidth  || document.documentElement.clientWidth  || document.body.clientWidth,
			    vh  = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight,
			    sl  = window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0,
			    st  = window.pageYOffset || document.documentElement.scrollTop  || document.body.scrollTop  || 0;

			/* Horizontal: nudge in from whichever edge it overhangs. */
			offset.left = Math.min(offset.left, sl + vw - dpW - 2);
			offset.left = Math.max(offset.left, sl + 2);

			/* offset.top arrives as the input's bottom edge -- the below-field
			   position, which is what we want whenever it fits. */
			if(offset.top + dpH > st + vh){
				var above = offset.top - inH - dpH;
				if(above >= st + 2){
					offset.top = above;                          /* real room above: flip */
				} else {
					/* Neither side fits. Sit as low as the viewport allows and let
					   the field stay visible above it, rather than jumping to 0. */
					offset.top = Math.max(st + 2, st + vh - dpH - 2);
				}
			}
			return offset;
		};
		return true;
	}

	/* This block is emitted in <head>, before the jQuery UI script tags on
	   most pages, so it cannot patch anything yet. DOMContentLoaded is early
	   enough -- head scripts have run by then -- with load as the fallback for
	   pages that defer them. */
	if(!patch()){
		if(document.addEventListener){
			document.addEventListener('DOMContentLoaded', patch, false);
			window.addEventListener('load', patch, false);
		} else if(window.attachEvent){
			window.attachEvent('onload', patch);
		}
	}
})();
</script>
<?php
}