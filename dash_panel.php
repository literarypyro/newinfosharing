<?php
/* =====================================================================
   dash_panel.php  --  the incident slide panel, ported from
   train_operations.php so the dashboard band opens edit_ccdr.php
   exactly the way the operations table does.

   PORTED VERBATIM (do not "improve" these independently -- they are a
   protocol shared with edit_ccdr.php and "incident report.php"):
     * openEditIncidentPanel(irId, title)  -> edit_ccdr.php?ir=<id>&embed=1
     * openIncidentPanel(query, title)     -> incident report.php?<query>&embed=1
     * irFrameLoaded() / closeIncidentPanel()
     * the 6-second fallback when the frame never loads
     * the postMessage contract:
          'ir-saved'  close the panel and reload the host page
          'sp:saved'  a field edit landed; panel STAYS open, host page is
                      marked stale and reloads once on close
     * element ids irPanel / irFrame / irLoading / irFallback /
       irFallbackLink / ir-panel-title / taOverlay

   Keeping the ids and function names identical means that when you
   finish extracting slide_panel.php, this file is deleted and the
   include swapped -- the dashboard needs no other change.

   Self-contained: the CSS uses literal hex rather than train_operations'
   --rail / --paper / --mut tokens, which are not defined on the
   dashboard.  Values are the same console blue and gold.

   Guarded against double-inclusion.
   ===================================================================== */

if(defined('DASH_PANEL_LOADED')){ return; }
define('DASH_PANEL_LOADED',true);

/* Where the host page reloads to after a save inside the panel.  The
   dashboard is a GET page, so preserving the query string keeps the
   user on the operating date they were looking at. */
$dashPanelSelf = htmlspecialchars(
	isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'dashboard.php',
	ENT_QUOTES,'UTF-8'
);
?>
<style>
/* ── overlay + panel shell (ported from train_operations.php) ── */
#taOverlay        { position:fixed; inset:0; background:rgba(10,25,50,.45); opacity:0; visibility:hidden; transition:opacity .2s; z-index:99998; }
#taOverlay.active { opacity:1; visibility:visible; }
#irPanel          { position:fixed; top:0; right:-900px; width:820px; max-width:96vw; height:100vh; background:#fff;
                    box-shadow:-6px 0 24px rgba(0,30,80,.25); transition:right .25s ease; z-index:99999;
                    display:flex; flex-direction:column; font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif; }
#irPanel.active   { right:0; }
#irPanel .ta-panel-head  { background:#00529B; border-bottom:3px solid #FDB813; padding:12px 16px;
                           display:flex; align-items:center; justify-content:space-between; flex:none; }
#irPanel .ta-panel-head h3 { margin:0; color:#fff; font-size:13px; font-weight:600; letter-spacing:.3px; }
#irPanel .ta-panel-close { background:none; border:none; color:rgba(255,255,255,.7); font-size:19px; line-height:1; cursor:pointer; padding:0 2px; }
#irPanel .ta-panel-close:hover { color:#FDB813; }
#irPanel .ta-panel-body--ir { flex:1; padding:0; overflow:hidden; position:relative; }

#irFrame          { display:block; width:100%; height:100%; border:0; background:#fff; opacity:0; transition:opacity .15s; }
#irFrame.ready    { opacity:1; }
.ir-loading, .ir-fallback { position:absolute; top:0; right:0; bottom:0; left:0; display:flex; flex-direction:column;
                    align-items:center; justify-content:center; gap:10px; background:#fff; text-align:center; padding:0 30px; }
.ir-loading.hidden, .ir-fallback.hidden { display:none; }
.ir-spinner       { width:26px; height:26px; border:3px solid #C9D6E5; border-top-color:#00529B; border-radius:50%; animation:ir-spin .7s linear infinite; }
@keyframes ir-spin { to { transform:rotate(360deg); } }
.ir-loading span, .ir-fallback p { font-size:12px; color:#8b93a1; }
.ir-fallback strong { color:#1f2430; font-size:13px; }
.ir-fallback a    { color:#00529B; font-weight:600; text-decoration:none; }
.ir-fallback a:hover { text-decoration:underline; }
@media (max-width:900px){ #irPanel { width:100vw; max-width:100vw; } }
@media (prefers-reduced-motion:reduce){ #irPanel { transition:none; } .ir-spinner { animation:none; } }
</style>

<div class="ta-overlay" id="taOverlay" onclick="closeIncidentPanel()"></div>

<div class="ta-panel ta-panel--ir" id="irPanel" role="dialog" aria-modal="true" aria-labelledby="ir-panel-title">
	<div class="ta-panel-head">
		<h3 id="ir-panel-title">Incident Report</h3>
		<button type="button" class="ta-panel-close" onclick="closeIncidentPanel()" aria-label="Close">&times;</button>
	</div>
	<div class="ta-panel-body ta-panel-body--ir">
		<iframe id="irFrame" src="about:blank" title="Incident Report" onload="irFrameLoaded()"></iframe>
		<div class="ir-loading" id="irLoading">
			<div class="ir-spinner"></div>
			<span>Loading incident form&hellip;</span>
		</div>
		<div class="ir-fallback hidden" id="irFallback">
			<strong>This is taking longer than expected.</strong>
			<p>The form may be blocked from loading inside this panel.<br>You can open it directly instead:</p>
			<a href="#" id="irFallbackLink" target="_blank" rel="noopener">Open Incident Report in a new tab &rarr;</a>
		</div>
	</div>
</div>

<script>
/* ── Incident panel, ported from train_operations.php ───────────────── */
var irLoadTimer=null, irExpectingLoad=false, irNeedsReload=false;
var dashPanelSelf="<?php echo $dashPanelSelf; ?>";

/* Shared body of both openers: point the frame at $url, show the loading
   state, arm the 6s fallback, slide the panel in. */
function irOpenFrame(url,fallbackUrl,title,defaultTitle){
	document.getElementById('ir-panel-title').textContent=title||defaultTitle;
	document.getElementById('irFallbackLink').href=fallbackUrl;   /* no embed=1: full standalone page */
	var frame=document.getElementById('irFrame');
	frame.classList.remove('ready');
	document.getElementById('irLoading').classList.remove('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	clearTimeout(irLoadTimer);
	irExpectingLoad=true;
	frame.src=url;
	document.getElementById('irPanel').classList.add('active');
	document.getElementById('taOverlay').classList.add('active');
	irLoadTimer=setTimeout(function(){
		if(irExpectingLoad) document.getElementById('irFallback').classList.remove('hidden');
	},6000);
}

/* Signature matches train_operations.php exactly: the BARE incident id,
   not a URL.  The dashboard band calls this. */
function openEditIncidentPanel(query,title){
	irOpenFrame("edit_ccdr.php?ir="+query+"&embed=1",
	            "edit_ccdr.php?ir="+query,
	            title,"Incident Report Details");
}

/* Kept for parity, so a future "add incident" action on the dashboard
   has the same door available. */
function openIncidentPanel(query,title){
	irOpenFrame("incident report.php?"+query+"&embed=1",
	            "incident report.php?"+query,
	            title,"Incident Report");
}

/* Alias matching the extracted slide_panel.php, which is what the dashboard
   band actually calls.  If the real slide_panel.php is present this whole file
   is skipped, so this only matters as a fallback. */
function openSlidePanel(url,title){
	var bare = url.replace(/[?&]embed=1/,'');
	irOpenFrame(url, bare, title, "Incident Report");
}

function irFrameLoaded(){
	if(!irExpectingLoad) return;   /* ignore the about:blank resets */
	irExpectingLoad=false;
	clearTimeout(irLoadTimer);
	document.getElementById('irLoading').classList.add('hidden');
	document.getElementById('irFallback').classList.add('hidden');
	document.getElementById('irFrame').classList.add('ready');
}

function closeIncidentPanel(){
	var p=document.getElementById('irPanel');
	if(!p) return;
	p.classList.remove('active');
	clearTimeout(irLoadTimer);
	irExpectingLoad=false;
	document.getElementById('irFrame').src="about:blank";   /* drop any half-filled form */
	document.getElementById('taOverlay').classList.remove('active');
	/* Pick up field edits saved inside the panel.  Reloading only on close
	   -- not on every 'sp:saved' -- is deliberate: editing a CCDR is a run
	   of several field edits, and reloading mid-run would slam the panel
	   shut under the user. */
	if(irNeedsReload){ irNeedsReload=false; self.location=dashPanelSelf; }
}

document.addEventListener('keydown',function(e){ if(e.key==='Escape'){ closeIncidentPanel(); } });

window.addEventListener('message',function(e){
	if(e.origin && e.origin!==window.location.origin) return;
	if(e.data==='ir-saved'){ closeIncidentPanel(); self.location=dashPanelSelf; }
	if(e.data==='sp:saved'){ irNeedsReload=true; }
});
</script>