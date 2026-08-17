<?php
/* ============================================================================
   dash_datepicker.php  --  makes the dashboard's date control match the rest
   of the console.

   WHY THE COPY-PASTE DID NOT WORK
   Every other page renders a jQuery UI widget, so datepicker_theme.php has a
   `.ui-datepicker` div to skin. dashboard.php has no jQuery, no jQuery UI, and
   no such div -- its control is a NATIVE <input type="date">, and the calendar
   that drops out of it is drawn by the browser, outside the page's CSS. No
   stylesheet can reach it. Pasting the theme in styles an element that is
   never created.

   WHAT THIS DOES INSTEAD -- two layers, in this order:

   1. Skins the FIELD. Native or not, the input itself is ours to style, so it
      picks up the console's 30px height, border and focus ring, and the grey
      browser calendar glyph is tinted to the console blue. Costs nothing and
      needs no library.

   2. Upgrades the WIDGET, but only if it can. If jQuery UI is already on the
      page -- Tmenu_2.php may well pull it, and this page cannot know -- the
      input is switched to type=text and bound to $.datepicker with
      dateFormat "yy-mm-dd", which is byte-identical to what type=date submits,
      so dash_date() on the PHP side sees no difference either way. Then
      datepicker_theme.php has a real widget to skin and the dashboard matches
      statistics_report_modified exactly.

      If jQuery UI is NOT present, nothing is loaded and nothing breaks: the
      field keeps the native picker, which is a perfectly good control and on
      a phone is the better one.

   This is deliberately NOT a hard dependency. Adding jQuery 1.10 + jQuery UI
   to the dashboard for one field would put ~300KB and two globals onto the
   only console page that currently loads no library at all. That silence is
   worth more than an exactly-matching popup.

   HOW TO INCLUDE IT
   AFTER Tmenu_2.php, not in <head>: if the nav include links a jQuery UI
   stylesheet, anything emitted earlier loses to it on source order. <style>
   in <body> is valid HTML5.

       <?php require("Tmenu_2.php"); ?>
       <?php if(file_exists(dirname(__FILE__)."/dash_datepicker.php")) include(dirname(__FILE__)."/dash_datepicker.php"); ?>

   Pairs with datepicker_theme.php, which it pulls in itself (guarded). The
   theme's own double-include constant means a page can load both directly
   without emitting the skin twice.
   ========================================================================= */
if(!defined('CCS_DASH_DATEPICKER')){
	define('CCS_DASH_DATEPICKER', 1);

	/* Same trick as datepicker_theme.php: prove in View Source that this file
	   ran, and from where. Silence and "loaded but did nothing" look identical
	   from the outside otherwise. */
	echo "<!-- ccs-dash-datepicker LOADED from ".dirname(__FILE__)." -->\n";

	/* The widget skin, for the case where layer 2 fires. Guarded twice over:
	   file_exists here, CCS_DATEPICKER_THEME inside. */
	if(file_exists(dirname(__FILE__)."/datepicker_theme.php")){
		include_once(dirname(__FILE__)."/datepicker_theme.php");
	}
?>
<style>
/* @dashdate -- layer 1: the field. Applies to the native input and survives
   the type=date -> type=text swap below, since the class is what is selected
   on, not the type. Sized to the console's 30px controls so it lines up with
   the Show button beside it. */
.ds-bar form input.ds-date,
.ds-bar form input[type="date"],
.ds-bar form input[type="text"].ds-date{
	height:30px;box-sizing:border-box;
	padding:0 8px;
	font-family:inherit;font-size:12px;color:var(--cf-ink,#1A2238);
	background:var(--cf-surface,#FFFFFF);
	border:1px solid var(--cf-line,#C9CFDA);border-radius:4px;
	line-height:28px;
}
.ds-bar form input.ds-date:focus,
.ds-bar form input[type="date"]:focus{
	outline:none;border-color:#00529B;box-shadow:0 0 0 2px rgba(0,82,155,.18);
}
/* The browser's own calendar glyph ships mid-grey and reads as disabled next
   to the console's controls. This recolours the icon only -- the popup it
   opens is still the browser's and is not reachable from CSS. */
.ds-bar form input[type="date"]::-webkit-calendar-picker-indicator{
	cursor:pointer;opacity:.75;
	filter:invert(21%) sepia(96%) saturate(1650%) hue-rotate(196deg) brightness(94%) contrast(101%);
}
.ds-bar form input[type="date"]::-webkit-calendar-picker-indicator:hover{opacity:1;}
/* Firefox and the type=text fallback get no glyph at all, so the field alone
   has to read as clickable. */
.ds-bar form input[type="text"].ds-date{cursor:pointer;}
</style>
<script>
/* @dashdate -- layer 2, with the reason it bailed.
   The first cut returned silently at every check, which made "jQuery UI never
   arrived", "the field moved" and "this file is not on the server" all look
   the same from a screenshot. Each exit now names itself in the console under
   [ccs-dash-datepicker] and stamps data-ccs-dp on the field, so DevTools
   Elements answers it too. */
(function(){
	var TAG = '[ccs-dash-datepicker] ';
	function say(msg){
		if(window.console && console.info){ console.info(TAG + msg); }
		var el = document.querySelector ? document.querySelector('.ds-bar form input[name="d"]') : null;
		if(el && el.setAttribute){ el.setAttribute('data-ccs-dp', msg); }
	}

	var tries = 0;

	function upgrade(){
		var jq = window.jQuery;

		/* Split the two checks: "no jQuery at all" and "jQuery without UI" are
		   different fixes -- a missing script tag versus a 404 on jquery-ui.js. */
		if(!jq || !jq.fn){
			if(++tries < 30){ setTimeout(upgrade, 100); return; }   /* ~3s of grace */
			say('jQuery never loaded -- check the script tag path in dashboard.php resolves');
			return;
		}
		if(!jq.fn.datepicker){
			if(++tries < 30){ setTimeout(upgrade, 100); return; }
			say('jQuery ' + jq.fn.jquery + ' loaded but jquery-ui.js did not -- check that path');
			return;
		}

		/* @dashdate -- desktop only, on purpose. On a phone the native date
		   input opens the OS wheel picker, which beats a 17em grid of 11px
		   tap targets every time. The console skin is a workstation concern;
		   below 768px the field stays type=date and keeps the better control.
		   matchMedia is absent on very old browsers -- those get the upgrade,
		   which is the safe default for the workstations. */
		if(window.matchMedia && window.matchMedia("(max-width: 767px)").matches){
			say('narrow viewport -- keeping the native picker on purpose');
			return;
		}

		var $f = jq('.ds-bar form input[name="d"]');
		if(!$f.length){
			say('field not found at .ds-bar form input[name="d"] -- markup moved');
			return;
		}
		if($f.data('ccsBound')) return;

		/* type=date must go before binding. Two browsers' pickers on one field
		   is a real failure mode: the native popup opens over the jQuery one.
		   The value carries across unchanged -- both speak YYYY-MM-DD. */
		var val = $f.val();
		try{ $f.attr('type','text'); }catch(e){ say('browser refused the type swap -- keeping native'); return; }
		$f.addClass('ds-date').val(val).attr('autocomplete','off').data('ccsBound',1);

		$f.datepicker({
			dateFormat: 'yy-mm-dd',      /* what the form already submits */
			changeMonth: true,
			changeYear: true,
			maxDate: 0,                  /* no operating data ahead of today */
			showAnim: 'clip'
		});
		say('upgraded to the jQuery UI widget');
	}

	/* DOMContentLoaded rather than load: the head scripts have run by then, and
	   waiting for load meant every image on the dashboard had to finish first.
	   The retry above covers anything that arrives later than that. */
	if(document.addEventListener){
		document.addEventListener('DOMContentLoaded', upgrade, false);
		window.addEventListener('load', upgrade, false);
	} else if(window.attachEvent){
		window.attachEvent('onload', upgrade);
	}
})();
</script>
<?php
}