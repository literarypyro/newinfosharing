<?php
/* ============================================================================
 * iss_insight_print.php  --  the analysis panel, on paper.
 * ----------------------------------------------------------------------------
 * The four report pages each build their printout by writing a whole new
 * document into a popup with win.document.write(). That new document does NOT
 * inherit this page's stylesheets, so anything the panel's own CSS provides is
 * gone the moment it crosses over. Every print block therefore re-declares its
 * own CSS from scratch.
 *
 * Rather than paste a copy of the panel CSS into four print blocks and watch
 * them drift, both halves live here:
 *
 *   iss_insight_print_css()  -- a CSS fragment (no <style> tags) to be
 *                               concatenated into the print window's style
 *                               string, alongside the rules already there.
 *   iss_insight_print_js()   -- a <script> block defining the two globals the
 *                               print blocks call.
 *
 * WHY A DOM CLONE AND NOT $issPanelHtml.
 * $issPanelHtml is the OFFLINE text, captured server-side into a buffer while
 * the page rendered. When a model provider is configured, iss_insight_stash()
 * plus the XHR at the bottom of the panel block replaces #issInsight's
 * innerHTML after load with the model's wording. Printing from the PHP buffer
 * would silently paper over that and hand out a document that does not match
 * what the operator is looking at. Reading the DOM at the moment the button is
 * pressed is the only capture that cannot disagree with the screen -- the same
 * reason ccsFullTableHtml() and tbl.outerHTML read the live table rather than
 * a server-side copy.
 *
 * Cloning also sidesteps escaping: the HTML arrives as a JS value, never as a
 * string literal inside the concatenation, so there is nothing to quote.
 *
 * GUARDS. Every function is guarded on its OWN name, not a sibling's -- the
 * version-skew rule. A station running an older iss_insight_audience.php next
 * to a newer copy of this file must not fatal; it just prints without the
 * panel, exactly as it does today. Callers test function_exists() before
 * calling, and the JS side tests typeof, so a station that has not received
 * this file at all prints byte-identically to before.
 * ==========================================================================*/

if(!function_exists('iss_insight_print_css')){
/**
 * CSS fragment for the print window. Scoped entirely under .rpt-insight so it
 * cannot touch the report's own rules, and returned WITHOUT <style> tags
 * because the caller concatenates it into an existing style string.
 *
 * The width of the block is deliberately NOT set here: the landscape summary
 * reports float it beside the two figures, the portrait drill-downs run it
 * full width above them. Each page sets that itself.
 */
function iss_insight_print_css(){
	return
	  '.rpt-insight{ border:1px solid #d1d5db; border-left:3px solid #BA7517;'
	. ' padding:8px 10px; page-break-inside:avoid; break-inside:avoid;'
	. ' font-size:9px; line-height:1.45; color:#1a1a1a; text-align:left; }'

	/* The switch is a control, and paper has no state. Buttons and any script
	   the panel carried are dropped by the JS clone as well -- belt and braces,
	   because a future panel revision could introduce a control this selector
	   list does not know about but which still renders as a box on the page. */
	. '.rpt-insight .ins-switch, .rpt-insight .ins-tab, .rpt-insight button,'
	. ' .rpt-insight script, .rpt-insight .ins-audit{ display:none !important; }'

	. '.rpt-insight .ins-bar{ margin:0 0 6px; }'
	. '.rpt-insight .ins-title{ font-size:8px; letter-spacing:.09em;'
	. ' text-transform:uppercase; color:#1f4e79; font-weight:600; }'

	. '.rpt-insight .ins-head{ font-size:10.5px; font-weight:600; color:#1a1a1a;'
	. ' margin:0 0 5px; line-height:1.4; }'
	. '.rpt-insight .ins-summary{ font-size:9px; color:#374151; margin:0 0 5px; }'
	. '.rpt-insight p{ margin:0 0 5px; font-size:9px; line-height:1.45; }'
	. '.rpt-insight ul, .rpt-insight ol{ margin:0 0 5px; padding-left:13px; }'
	. '.rpt-insight li{ font-size:9px; line-height:1.45; margin:0 0 3px; }'

	/* Caveats are the whole reason the executive register is safe to hand to
	   someone who will never see the table. They are the last thing that may
	   be dropped for space, so they are styled to survive rather than to be
	   inconspicuous. */
	. '.rpt-insight .ins-caveat{ font-size:8.5px; color:#5A6275; font-style:italic;'
	. ' margin:4px 0 0; line-height:1.4; }'

	/* Which register prints. The on-screen toggle sets style.display INLINE on
	   the two views, and an inline style beats a plain rule -- hence
	   !important on the reveal. The hide rules are also !important and more
	   specific, so they win over it in turn. Switching is then a matter of the
	   data-view attribute the JS stamps on the wrapper, which is exactly the
	   shape of the table.ccs-rows-with tr.ccs-zero rule already in these print
	   blocks: one attribute on the clone, no conditional CSS to assemble. */
	. '.rpt-insight .ins-view, .rpt-insight .v{ display:block !important; }'
	. '.rpt-insight[data-view="exec"] .ins-tech,'
	. ' .rpt-insight[data-view="exec"] .v-tech{ display:none !important; }'
	. '.rpt-insight[data-view="tech"] .ins-exec,'
	. ' .rpt-insight[data-view="tech"] .v-exec{ display:none !important; }'

	/* The one sentence that prints regardless of register, up in the masthead.
	   A printout is circulated past whoever printed it; two lines is a cheap
	   price for the reader who receives the paper and not the screen. */
	. '.rpt-lead{ font-size:10.5px; font-weight:600; color:#1a1a1a;'
	. ' margin:5px 0 0; line-height:1.4; }';
}}

if(!function_exists('iss_insight_print_js')){
/**
 * Emits the clone helpers once per page. Idempotent via a static flag so a
 * page may call it next to each panel without defining the globals twice.
 *
 * issInsightPrintHtml(id) -> null, or
 *   { html: '<inner markup>', view: 'exec'|'tech', lead: '<plain sentence>' }
 * issInsightPrintBlock(id) -> '' or a ready-to-concatenate .rpt-insight div.
 */
function iss_insight_print_js(){
	static $done = false;
	if($done) return '';
	$done = true;
	return <<<'ISSPRINTJS'
<script>
/* @insight -- capture the panel as it stands at the moment Print is pressed. */
(function(){
	if(window.issInsightPrintHtml) return;

	window.issInsightPrintHtml = function(id){
		var b = document.getElementById(id);
		if(!b) return null;

		/* data-view lives on .ins-block, which the dual renderer emits inside
		   the anchor div. The single-register path (iss_insight_html, used when
		   iss_insight_audience.php is absent) has no such element and no views
		   to choose between -- defaulting to exec is harmless there because
		   neither .ins-exec nor .ins-tech exists to be hidden. */
		var host = b.querySelector ? (b.querySelector('.ins-block') || b) : b;
		var view = (host.getAttribute && host.getAttribute('data-view')) || 'exec';

		var c = b.cloneNode(true);
		if(c.removeAttribute) c.removeAttribute('id');

		if(c.querySelectorAll){
			var drop = c.querySelectorAll('script, .ins-switch, .ins-tab, button');
			for(var i = drop.length - 1; i >= 0; i--){
				if(drop[i].parentNode) drop[i].parentNode.removeChild(drop[i]);
			}
			/* Nested ids would collide with the report's own markup once both
			   are in the same popup document. */
			var ids = c.querySelectorAll('[id]');
			for(var j = 0; j < ids.length; j++){ ids[j].removeAttribute('id'); }
		}

		var lead = '';
		if(c.querySelector){
			var h = c.querySelector('.ins-exec .ins-head') || c.querySelector('.ins-head');
			if(h){
				lead = (h.textContent || h.innerText || '')
					.replace(/\s+/g, ' ')
					.replace(/^ | $/g, '');
			}
		}

		return {
			html: c.innerHTML,
			view: (view === 'tech' ? 'tech' : 'exec'),
			lead: lead
		};
	};

	window.issInsightPrintBlock = function(id){
		var d = window.issInsightPrintHtml(id);
		if(!d || !d.html) return '';
		return '<div class="rpt-insight" data-view="' + d.view + '">' + d.html + '</div>';
	};

	window.issInsightPrintLead = function(id){
		var d = window.issInsightPrintHtml(id);
		return (d && d.lead) ? d.lead : '';
	};
})();
</script>
ISSPRINTJS;
}}