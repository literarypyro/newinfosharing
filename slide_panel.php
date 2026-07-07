<?php /* =========================================================================
   slide_panel.php — portable slide-out panel, extracted from the incident
   report / edit CCDR panel in train_operations.php.

   WHAT THIS IS
     A single reusable "drawer" that loads any URL into an iframe and slides
     it in from the right, with a loading spinner and a timeout fallback
     (open-in-new-tab) if the target never loads. One panel, any number of
     trigger buttons on the page, each pointing it at a different URL.

   HOW TO USE IT
     1. Drop this file next to the page that wants a panel and include it
        once, right before </body>:
            <?php include 'slide_panel.php'; ?>
     2. Call it from any onclick, anywhere on the page:
            <a href="#" onclick="openSlidePanel('incident report.php?add_incident=5&embed=1','Add Incident')">Add Incident</a>
        Or from PHP-generated markup exactly like train_operations.php already does:
            onclick='openSlidePanel(\"edit_ccdr.php?ir=".$id."&embed=1\",\"Incident ".$no."\")'
     3. Optional third argument for a non-default width:
            openSlidePanel('some_form.php?embed=1','Title',{width:'640px'})

   WHAT THE TARGET PAGE NEEDS TO DO (same contract as incident_report.php /
   edit_ccdr.php already follow — copy that pattern for any new page):
     - Read $_GET['embed'] and suppress its own header/nav chrome when set,
       e.g.:
            $EMBED = isset($_GET['embed']);
            if($EMBED){ ob_start(); }
            require("Tmenu.php");
            if($EMBED){ ob_end_clean(); }
     - On successful save, if embedded, tell the parent instead of trying to
       redirect (a redirect would just navigate the iframe, not the page
       that opened it):
            if($EMBED){ echo "<script>parent.postMessage('sp:saved','*');</script>"; }
            else { echo "<script>window.opener.location='...';</script>"; }
       (train_operations.php's incident_report.php uses 'ir-saved' for this
       same message — both names are recognized below, so existing pages
       don't need to change. Use 'sp:saved' for anything new.)
     - If the target page's own form posts back to itself, carry embed=1
       through the form's action attribute so the response stays embedded.

   WHAT THIS DOES NOT ASSUME
     No dependency on train_operations.php's other (native-form) panel, no
     dependency on a specific filename to reload, no hardcoded page title.
     Colors fall back to the Line 3 rail-blue/gold palette but every value
     is a CSS var with a default, so any page can override them by defining
     --sp-rail / --sp-gold / etc. before this include, without editing this
     file.
   ========================================================================= */
?>
<style>
:root {
	--sp-rail:      var(--rail, #00529B);
	--sp-rail-dark: var(--rail-dark, #013E76);
	--sp-gold:      var(--gold, #FDB813);
	--sp-paper:     var(--paper, #F7F9FC);
	--sp-ink:       var(--ink, #16243B);
	--sp-mut:       var(--mut, #5A6678);
	--sp-line:      var(--line, #D2DDEA);
}
.sp-overlay        { position:fixed; top:0; right:0; bottom:0; left:0; background:rgba(10,25,50,.45); opacity:0; visibility:hidden; transition:opacity .2s; z-index:99998; }
.sp-overlay.active { opacity:1; visibility:visible; }

/* transform, not a hardcoded right offset -- correct for ANY panel width
   (the default, or a per-call opts.width override) without needing a
   matching "hidden position" constant tuned per width. */
.sp-panel          { position:fixed; top:0; right:0; width:820px; max-width:96vw; height:100vh; background:var(--sp-paper); box-shadow:-6px 0 24px rgba(0,30,80,.25); transition:transform .25s ease; z-index:99999; display:flex; flex-direction:column; font-family:"Segoe UI",system-ui,-apple-system,Roboto,Arial,sans-serif; transform:translateX(calc(100% + 30px)); }
.sp-panel.active   { transform:translateX(0); }

.sp-panel-head     { background:var(--sp-rail); border-bottom:3px solid var(--sp-gold); padding:12px 16px; display:flex; align-items:center; justify-content:space-between; flex:none; }
.sp-panel-head h3  { margin:0; color:#fff; font-size:13px; font-weight:600; letter-spacing:.3px; }
.sp-panel-close    { background:none; border:none; color:rgba(255,255,255,.7); font-size:19px; line-height:1; cursor:pointer; padding:0 2px; }
.sp-panel-close:hover { color:var(--sp-gold); }

.sp-panel-body     { flex:1; padding:0; overflow:hidden; position:relative; }
#spFrame           { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#spFrame.ready     { opacity:1; }

.sp-loading, .sp-fallback { position:absolute; top:0; right:0; bottom:0; left:0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:10px; background:var(--sp-paper); text-align:center; padding:0 30px; }
.sp-loading.hidden, .sp-fallback.hidden { display:none; }
.sp-spinner        { width:26px; height:26px; border:3px solid #C9D6E5; border-top-color:var(--sp-rail); border-radius:50%; animation:sp-spin .7s linear infinite; }
@keyframes sp-spin { to { transform:rotate(360deg); } }
.sp-loading span, .sp-fallback p { font-size:12px; color:var(--sp-mut); }
.sp-fallback strong { color:var(--sp-ink); font-size:13px; }
.sp-fallback a     { color:var(--sp-rail); font-weight:600; text-decoration:none; }
.sp-fallback a:hover { text-decoration:underline; }

@media (max-width:768px){ .sp-panel { width:100vw; max-width:100vw; } }
</style>

<div class="sp-overlay" id="spOverlay" onclick="closeSlidePanel()"></div>
<div class="sp-panel" id="spPanel" role="dialog" aria-modal="true" aria-labelledby="sp-panel-title">
	<div class="sp-panel-head">
		<h3 id="sp-panel-title">Details</h3>
		<button type="button" class="sp-panel-close" onclick="closeSlidePanel()" aria-label="Close">&times;</button>
	</div>
	<div class="sp-panel-body">
		<iframe id="spFrame" src="about:blank" title="Panel content" onload="spFrameLoaded()"></iframe>
		<div class="sp-loading" id="spLoading">
			<div class="sp-spinner"></div>
			<span>Loading&hellip;</span>
		</div>
		<div class="sp-fallback hidden" id="spFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The form may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="spFallbackLink" target="_blank" rel="noopener">Open in a new tab &rarr;</a>
		</div>
	</div>
</div>

<script>
/* ── Portable slide panel ─────────────────────────────────────────────
   openSlidePanel(url, title, opts)
     url   : full URL to load in the iframe, including any query string
             (e.g. "?embed=1"). Pass exactly what the target page needs.
     title : panel header text.
     opts  : optional. { width: '640px' }        -- per-call panel width
                        { standaloneUrl: '...' }  -- link used by the
                          timeout fallback and the "open in new tab"
                          affordance. Defaults to `url` with any
                          "embed=1" query param stripped, since that's
                          almost always what you want (the same page,
                          full chrome, opened directly instead of framed).
                        { timeout: 6000 }         -- ms before the
                          fallback message appears. Default 6s.

   closeSlidePanel() closes it and blanks the iframe.

   Target-page contract: see the comment block at the top of this file.
   On successful save, an embedded page should postMessage either
   'sp:saved' (preferred) or 'ir-saved' (kept for backward compatibility
   with pages already using that name) to trigger auto-close + reload.
   ── */
var spLoadTimer=null, spExpectingLoad=false;

function openSlidePanel(url, title, opts){
	opts = opts || {};
	var standaloneUrl = opts.standaloneUrl || url.replace(/([?&])embed=1(&|$)/,'$1').replace(/[?&]$/,'');
	var panel = document.getElementById('spPanel');

	panel.style.width = opts.width || '';
	document.getElementById('sp-panel-title').textContent = title || 'Details';
	document.getElementById('spFallbackLink').href = standaloneUrl;

	var frame = document.getElementById('spFrame');
	frame.classList.remove('ready');
	document.getElementById('spLoading').classList.remove('hidden');
	document.getElementById('spFallback').classList.add('hidden');
	clearTimeout(spLoadTimer);
	spExpectingLoad = true;
	frame.src = url;

	panel.classList.add('active');
	document.getElementById('spOverlay').classList.add('active');

	spLoadTimer = setTimeout(function(){
		if(spExpectingLoad) document.getElementById('spFallback').classList.remove('hidden');
	}, opts.timeout || 6000);
}

function spFrameLoaded(){
	if(!spExpectingLoad) return; /* ignore the about:blank reset fired by closeSlidePanel() / initial markup */
	spExpectingLoad = false;
	clearTimeout(spLoadTimer);
	document.getElementById('spLoading').classList.add('hidden');
	document.getElementById('spFallback').classList.add('hidden');
	document.getElementById('spFrame').classList.add('ready');
}

function closeSlidePanel(){
	var p = document.getElementById('spPanel');
	if(!p) return;
	p.classList.remove('active');
	clearTimeout(spLoadTimer);
	spExpectingLoad = false;
	document.getElementById('spFrame').src = 'about:blank'; /* drop any half-filled form */
	document.getElementById('spOverlay').classList.remove('active');
}

document.addEventListener('keydown', function(e){
	if(e.key === 'Escape') closeSlidePanel();
});

window.addEventListener('message', function(e){
	if(e.data === 'sp:saved' || e.data === 'ir-saved'){
		closeSlidePanel();
		location.reload();
	}
});
</script>