<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>MRT-3 Train Availability — redesign options</title>

<!-- Tabler outline icons (used for the small action affordances). Degrades to text labels if offline. -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.11.0/dist/tabler-icons.min.css">

<style>
/* =========================================================================
   PREVIEW CHROME  —  evaluation harness only.
   None of this block ships to train_availability.php. It is the gray page,
   the sticky toggle bar, and the legend used to compare the three themes.
   ========================================================================= */
:root{
  --ta-sans: "Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif;
  --ta-mono: ui-monospace, "Cascadia Mono", "Consolas", "Liberation Mono", monospace;
}
*{ box-sizing:border-box; }
body{ margin:0; background:#EAEEF3; color:#16243B; font-family:var(--ta-sans); }
.harness{ max-width:1280px; margin:0 auto; padding:0 16px 48px; }
.harness-bar{
  position:sticky; top:0; z-index:50; background:#fff; border-bottom:1px solid #D8DFE8;
  display:flex; align-items:center; gap:16px; flex-wrap:wrap; padding:11px 16px; margin:0 -16px 16px;
}
.harness-bar h1{ font-size:14px; font-weight:500; margin:0; color:#00529B; }
.harness-bar .seg{ display:inline-flex; border:1px solid #C9D3DF; border-radius:8px; overflow:hidden; }
.harness-bar .seg button{
  font:inherit; font-size:12px; font-weight:500; border:none; background:#fff; color:#41506A;
  padding:6px 13px; cursor:pointer; border-right:1px solid #E2E8F0;
}
.harness-bar .seg button:last-child{ border-right:none; }
.harness-bar .seg button[aria-pressed="true"]{ background:#00529B; color:#fff; }
.harness-bar .seg button:focus-visible{ outline:2px solid #FDB813; outline-offset:-2px; }
.harness-note{ font-size:12px; color:#5E708A; line-height:1.5; margin:0 0 14px; }
.harness-note code{ font-family:var(--ta-mono); font-size:11px; background:#fff; border:1px solid #DCE3EC; border-radius:4px; padding:1px 5px; color:#19459B; }
.legend{ display:flex; gap:14px; flex-wrap:wrap; align-items:center; font-size:12px; color:#41506A; margin:0 0 16px; }
.legend .lg{ display:inline-flex; align-items:center; gap:6px; }
.legend .dot{ width:9px; height:9px; border-radius:50%; }
.ta-scroll{ overflow-x:auto; border-radius:12px; box-shadow:0 1px 3px rgba(20,40,80,.08); }

/* =========================================================================
   SHARED BASE  —  ships to train_availability.php
   Structural rules common to all three themes. Class names map onto markup
   that already exists in the page (noted inline) so the CSS is drop-in.
   Scope everything under .ta-grid so it outweighs the global admin template.
   ========================================================================= */
.ta-grid{ font-family:var(--ta-sans); color:#16243B; background:#fff; border-radius:12px; overflow:hidden; }

/* toolbar (replaces the inline-styled toolbar <table> at the top of the page) */
.ta-grid .ta-toolbar{ display:flex; align-items:center; gap:13px; flex-wrap:wrap; }
.ta-grid .ta-toolbar .tb-date{ font-weight:500; }
.ta-grid .ta-toolbar .tb-day{ font-size:11px; }
.ta-grid .ta-toolbar .tb-spacer{ flex:1; }
.ta-grid .ta-toolbar input[type=text]{ font:inherit; font-size:12px; height:28px; border-radius:5px; padding:0 8px; width:130px; }
.ta-grid .ta-toolbar .tb-act{ display:inline-flex; align-items:center; gap:5px; font-size:11px; text-decoration:none; padding:5px 11px; border-radius:5px; cursor:pointer; }

/* data table (maps to table.train_ava) */
.ta-grid table.ta{ border-collapse:collapse; width:100%; min-width:1080px; }
.ta-grid .ta th,.ta-grid .ta td{ padding:6px 8px; text-align:center; vertical-align:middle; }
.ta-grid .ta thead th{ font-weight:500; font-size:11px; }            /* maps to tr.rowHeading th */
.ta-grid .ta-num{ font-weight:500; line-height:1; }                  /* index numerals */
.ta-grid .ta-num--sw{ font-size:13px; }                              /* switched-to index */
.ta-grid .ta-time{ display:block; }                                  /* maps to .ta-slot-time / .switch-time */
.ta-grid .ta-driver{ display:block; font-size:11px; margin-top:1px; }/* maps to .ta-slot-driver / .switch-driver */
.ta-grid .ta-idx{ position:relative; }
.ta-grid .ta-status{ display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:500; margin-top:4px; }
.ta-grid .ta-status .led{ width:8px; height:8px; border-radius:50%; flex:none; }
.ta-grid .ta-cars{ display:flex; flex-direction:column; gap:3px; align-items:center; } /* maps to .tc-compo */
.ta-grid .ta-car{ font-size:11px; font-weight:500; text-decoration:none; border-radius:4px; padding:2px 9px; min-width:46px; } /* maps to .tc-car */
.ta-grid .ta-edit{ display:inline-flex; align-items:center; gap:4px; font-size:11px; text-decoration:none; border-radius:4px; padding:3px 8px; margin-top:3px; cursor:pointer; } /* maps to .tc-edit-btn */
.ta-grid .ta-remarks{ text-align:left; }
.ta-grid .ta-incident{ font-weight:500; text-decoration:none; }       /* the IN #### link */
.ta-grid .ta-act{ display:inline-flex; align-items:center; gap:4px; font-size:11px; text-decoration:none; border-radius:4px; padding:3px 8px; margin:3px 3px 0 0; cursor:pointer; } /* maps to .ta-act */
.ta-grid .ta-level{ font-weight:500; }                                /* L2 / L3 / L4 ordinals */
.ta-grid .ta-cancel{ font-weight:500; letter-spacing:.5px; }
.ta-grid .ta-del{ text-decoration:none; font-size:14px; line-height:1; } /* the row delete X */
.ta-grid .ta-del-sw{ text-decoration:none; font-size:12px; margin-left:4px; } /* per-switch delete */
.ta-grid .muted{ color:#9AA6B6; }
@media (prefers-reduced-motion: reduce){ *{ transition:none !important; } }

/* =========================================================================
   THEME 1 — OPERATIONS CONSOLE   (apply class ta-console on .ta-grid)
   Live-ops feel: solid rail-blue header band, monospaced indices/times,
   tight gridlines, refined (desaturated) row tint + status pill + left rail.
   ========================================================================= */
.ta-grid.ta-console{ border:1px solid #D2DDEA; }
.ta-grid.ta-console .ta-toolbar{ background:#00529B; padding:9px 13px; border-bottom:3px solid #FDB813; }
.ta-grid.ta-console .tb-wm{ font-weight:500; letter-spacing:.4px; background:#FDB813; color:#3A2D00; padding:2px 8px; border-radius:4px; font-size:11px; }
.ta-grid.ta-console .tb-date{ color:#fff; } .ta-grid.ta-console .tb-day{ color:rgba(255,255,255,.6); }
.ta-grid.ta-console .ta-toolbar input[type=text]{ border:1px solid rgba(255,255,255,.5); background:#fff; color:#16243B; }
.ta-grid.ta-console .tb-act{ color:#fff; border:1px solid rgba(255,255,255,.4); }
.ta-grid.ta-console .tb-act--print{ background:#FDB813; color:#3A2D00; border-color:#FDB813; font-weight:500; }
.ta-grid.ta-console .tb-tt{ color:rgba(255,255,255,.65); border:none; } .ta-grid.ta-console .tb-tt b{ color:#FDB813; font-weight:500; }
.ta-grid.ta-console .ta th,.ta-grid.ta-console .ta td{ border:1px solid #D2DDEA; }
.ta-grid.ta-console .ta thead th{ background:#00529B; color:#fff; border-color:#0A639E; }
.ta-grid.ta-console .ta thead tr:nth-child(2) th{ background:#0A5FA8; }
.ta-grid.ta-console .ta-num{ font-family:var(--ta-mono); color:#00529B; font-size:14px; }
.ta-grid.ta-console .ta-time{ font-family:var(--ta-mono); font-size:12px; }
.ta-grid.ta-console .ta-driver{ color:#5A6678; }
.ta-grid.ta-console .ta-idx::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:3px; }
.ta-grid.ta-console .row--service .ta-idx::before{ background:#1D9E75; }
.ta-grid.ta-console .row--reserve .ta-idx::before{ background:#BA7517; }
.ta-grid.ta-console .row--removed .ta-idx::before{ background:#378ADD; }
.ta-grid.ta-console .row--cancelled .ta-idx::before{ background:#E24B4A; }
.ta-grid.ta-console .row--removed td{ background:#EEF4FB; }
.ta-grid.ta-console .row--cancelled td{ background:#FCF0EE; }
.ta-grid.ta-console .ta-status{ border-radius:10px; padding:1px 8px; }
.ta-grid.ta-console .is-service{ background:#E1F3EA; color:#0F6E4E; } .ta-grid.ta-console .is-service .led{ background:#1D9E75; }
.ta-grid.ta-console .is-reserve{ background:#FAEEDA; color:#854F0B; } .ta-grid.ta-console .is-reserve .led{ background:#BA7517; }
.ta-grid.ta-console .is-removed{ background:#E6F1FB; color:#0C447C; } .ta-grid.ta-console .is-removed .led{ background:#378ADD; }
.ta-grid.ta-console .is-cancelled{ background:#FCEBEB; color:#A32D2D; } .ta-grid.ta-console .is-cancelled .led{ background:#E24B4A; }
.ta-grid.ta-console .ta-car{ font-family:var(--ta-mono); color:#00529B; background:#EEF4FB; border:1px solid #C5D8EE; }
.ta-grid.ta-console .ta-car:hover{ background:#00529B; color:#fff; }
.ta-grid.ta-console .ta-edit{ color:#5A6678; border:1px solid #D8D2C2; background:transparent; }
.ta-grid.ta-console .ta-edit:hover{ color:#00529B; border-color:#00529B; background:#EEF4FB; }
.ta-grid.ta-console .ta-act{ color:#00529B; border:1px solid #C9D6E5; background:#F4F8FC; }
.ta-grid.ta-console .ta-act:hover{ background:#00529B; color:#fff; border-color:#00529B; }
.ta-grid.ta-console .ta-incident{ color:#19459B; }
.ta-grid.ta-console .ta-level{ font-family:var(--ta-mono); color:#854F0B; }
.ta-grid.ta-console .ta-cancel{ color:#A32D2D; }
.ta-grid.ta-console .ta-del,.ta-grid.ta-console .ta-del-sw{ color:#B23A33; }

/* =========================================================================
   THEME 2 — CIVIC CLEAN   (apply class ta-civic on .ta-grid)
   Light, airy govtech: soft header, hairline borders, roomy rows, NO row
   flood — status lives entirely in pill chips so the grid stays calm.
   ========================================================================= */
.ta-grid.ta-civic{ border:1px solid #E6ECF3; }
.ta-grid.ta-civic .ta-toolbar{ background:#fff; padding:11px 15px; border-bottom:1px solid #E6ECF3; }
.ta-grid.ta-civic .tb-wm{ font-weight:500; color:#00529B; font-size:13px; border-left:3px solid #FDB813; padding-left:8px; }
.ta-grid.ta-civic .tb-date{ color:#1F2A3D; } .ta-grid.ta-civic .tb-day{ color:#8A95A6; }
.ta-grid.ta-civic .ta-toolbar input[type=text]{ border:1px solid #D5DEEA; background:#fff; color:#1F2A3D; }
.ta-grid.ta-civic .tb-act{ color:#00529B; border:1px solid #D5DEEA; }
.ta-grid.ta-civic .tb-act--print{ background:#FDB813; color:#3A2D00; border-color:#FDB813; font-weight:500; }
.ta-grid.ta-civic .tb-tt{ color:#8A95A6; border:none; } .ta-grid.ta-civic .tb-tt b{ color:#00529B; font-weight:500; }
.ta-grid.ta-civic .ta th,.ta-grid.ta-civic .ta td{ padding:9px 10px; border-bottom:1px solid #EEF2F7; border-right:1px solid #F2F5F9; }
.ta-grid.ta-civic .ta thead th{ background:#F5F8FC; color:#00529B; border-bottom:2px solid #CDDDEF; border-right:1px solid #E4ECF5; }
.ta-grid.ta-civic .ta thead tr:nth-child(2) th{ color:#5E708A; font-weight:400; }
.ta-grid.ta-civic .ta-num{ color:#1F2A3D; font-size:15px; }
.ta-grid.ta-civic .ta-time{ color:#34405A; font-size:12px; }
.ta-grid.ta-civic .ta-driver{ color:#8A95A6; }
.ta-grid.ta-civic .ta-status{ border-radius:11px; padding:2px 9px; }
.ta-grid.ta-civic .is-service{ background:#E8F5EE; color:#0F6E4E; } .ta-grid.ta-civic .is-service .led{ background:#1D9E75; }
.ta-grid.ta-civic .is-reserve{ background:#FBF0DC; color:#8A6314; } .ta-grid.ta-civic .is-reserve .led{ background:#BA7517; }
.ta-grid.ta-civic .is-removed{ background:#EAF2FB; color:#0C447C; } .ta-grid.ta-civic .is-removed .led{ background:#378ADD; }
.ta-grid.ta-civic .is-cancelled{ background:#FCEDED; color:#A32D2D; } .ta-grid.ta-civic .is-cancelled .led{ background:#E24B4A; }
.ta-grid.ta-civic .ta-car{ color:#00529B; background:#fff; border:1px solid #C5D8EE; border-radius:6px; }
.ta-grid.ta-civic .ta-car:hover{ background:#EEF4FB; }
.ta-grid.ta-civic .ta-edit{ color:#5E708A; border:1px solid #E0E6EE; background:#fff; border-radius:6px; }
.ta-grid.ta-civic .ta-edit:hover{ color:#00529B; border-color:#00529B; }
.ta-grid.ta-civic .ta-act{ color:#5E708A; border:1px solid #E0E6EE; background:#fff; border-radius:6px; }
.ta-grid.ta-civic .ta-act:hover{ color:#00529B; border-color:#00529B; }
.ta-grid.ta-civic .ta-incident{ color:#19459B; }
.ta-grid.ta-civic .ta-level{ color:#8A6314; }
.ta-grid.ta-civic .ta-cancel{ color:#A32D2D; background:#FDF4F4; border-radius:6px; font-weight:500; letter-spacing:0; }
.ta-grid.ta-civic .ta-del,.ta-grid.ta-civic .ta-del-sw{ color:#C2607A; }

/* =========================================================================
   THEME 3 — TRANSIT SIGNAGE   (apply class ta-signage on .ta-grid)
   Brand-forward station board: yellow-stripe masthead, heavier blue group
   headers, oversized index numerals, board-style status indicators.
   ========================================================================= */
.ta-grid.ta-signage{ border:1px solid #C9D6E5; }
.ta-grid.ta-signage .ta-toolbar{ background:#00529B; padding:0 13px 0 0; border-bottom:4px solid #FDB813; gap:13px; }
.ta-grid.ta-signage .tb-wm{ align-self:stretch; display:flex; align-items:center; font-weight:500; letter-spacing:1px; background:#FDB813; color:#3A2D00; padding:11px 13px; font-size:13px; }
.ta-grid.ta-signage .tb-date{ color:#fff; font-size:14px; } .ta-grid.ta-signage .tb-day{ color:rgba(255,255,255,.6); }
.ta-grid.ta-signage .ta-toolbar input[type=text]{ border:1px solid rgba(255,255,255,.45); background:#fff; color:#16243B; }
.ta-grid.ta-signage .tb-act{ color:#fff; border:1px solid rgba(255,255,255,.45); }
.ta-grid.ta-signage .tb-act--print{ background:#FDB813; color:#3A2D00; border-color:#FDB813; font-weight:500; }
.ta-grid.ta-signage .tb-tt{ color:rgba(255,255,255,.65); border:none; } .ta-grid.ta-signage .tb-tt b{ color:#FDB813; font-weight:500; }
.ta-grid.ta-signage .ta th,.ta-grid.ta-signage .ta td{ border:1px solid #D2DDEA; }
.ta-grid.ta-signage .ta thead th{ background:#00529B; color:#fff; border-color:#0E66AD; }
.ta-grid.ta-signage .ta thead .ta-grp{ background:#013E76; letter-spacing:.5px; border-bottom:3px solid #FDB813; }
.ta-grid.ta-signage .ta thead tr:nth-child(2) th{ background:#0A5FA8; }
.ta-grid.ta-signage .ta-num{ color:#00529B; font-size:20px; }
.ta-grid.ta-signage .ta-num--sw{ font-size:14px; }
.ta-grid.ta-signage .ta-time{ font-weight:500; font-size:13px; }
.ta-grid.ta-signage .ta-driver{ color:#5A6678; }
.ta-grid.ta-signage .row--removed td{ background:#EDF3FB; }
.ta-grid.ta-signage .row--cancelled td{ background:#FCF0EE; }
.ta-grid.ta-signage .ta-status{ gap:6px; }
.ta-grid.ta-signage .is-service{ color:#0F6E4E; } .ta-grid.ta-signage .is-service .led{ background:#1D9E75; }
.ta-grid.ta-signage .is-reserve{ color:#854F0B; } .ta-grid.ta-signage .is-reserve .led{ background:#BA7517; }
.ta-grid.ta-signage .is-removed{ color:#0C447C; } .ta-grid.ta-signage .is-removed .led{ background:#378ADD; box-shadow:inset -3px 0 0 #fff; }
.ta-grid.ta-signage .is-cancelled{ color:#A32D2D; } .ta-grid.ta-signage .is-cancelled .led{ background:#E24B4A; }
.ta-grid.ta-signage .ta-car{ color:#fff; background:#00529B; border:1px solid #00529B; }
.ta-grid.ta-signage .ta-car:hover{ background:#013E76; }
.ta-grid.ta-signage .ta-edit{ color:#00529B; border:1px solid #BFD2EA; background:#EEF4FB; }
.ta-grid.ta-signage .ta-edit:hover{ background:#00529B; color:#fff; }
.ta-grid.ta-signage .ta-act{ color:#00529B; border:1px solid #BFD2EA; background:#EEF4FB; }
.ta-grid.ta-signage .ta-act:hover{ background:#00529B; color:#fff; }
.ta-grid.ta-signage .ta-incident{ color:#19459B; border-bottom:2px solid #FDB813; }
.ta-grid.ta-signage .ta-level{ color:#8A5410; font-size:13px; }
.ta-grid.ta-signage .ta-cancel{ color:#A32D2D; letter-spacing:1px; }
.ta-grid.ta-signage .ta-del,.ta-grid.ta-signage .ta-del-sw{ color:#B23A33; }

/* focus visibility (quality floor) */
.ta-grid a:focus-visible,.ta-grid input:focus-visible{ outline:2px solid #FDB813; outline-offset:1px; }
</style>
</head>

<body>
<div class="harness">

  <div class="harness-bar">
    <h1>MRT-3 Train Availability — redesign options</h1>
    <div class="seg" role="group" aria-label="Choose a theme">
      <button data-theme="ta-console" aria-pressed="true">Operations console</button>
      <button data-theme="ta-civic" aria-pressed="false">Civic clean</button>
      <button data-theme="ta-signage" aria-pressed="false">Transit signage</button>
    </div>
  </div>

  <p class="harness-note">
    Same data and columns across all three — only the skin changes. The live page reserves
    <strong>7 switch slots</strong>; shown here as 3 for width. Class names map onto markup already in
    <code>train_availability.php</code> (e.g. <code>.ta-car</code> → <code>.tc-car</code>,
    <code>.ta-act</code> → <code>.ta-act</code>, <code>.ta-time</code> → <code>.ta-slot-time</code>).
    The drop-in CSS is everything under <code>.ta-grid</code>; the gray page and this bar are preview-only.
  </p>

  <div class="legend">
    <span class="lg"><span class="dot" style="background:#1D9E75"></span>In service</span>
    <span class="lg"><span class="dot" style="background:#BA7517"></span>Reserve / standby</span>
    <span class="lg"><span class="dot" style="background:#378ADD"></span>Removed</span>
    <span class="lg"><span class="dot" style="background:#E24B4A"></span>Cancelled</span>
  </div>

  <div class="ta-scroll">
  <div class="ta-grid ta-console" id="grid">

    <!-- ===== TOOLBAR ===== -->
    <div class="ta-toolbar">
      <span class="tb-wm">LINE 3</span>
      <span class="tb-date">June 29, 2026</span><span class="tb-day">Monday</span>
      <input type="text" placeholder="2026-06-29" aria-label="Search date">
      <a class="tb-act tb-act--go" href="#">Go</a>
      <span class="tb-spacer"></span>
      <a class="tb-act" href="#"><i class="ti ti-plus" aria-hidden="true"></i>Add train</a>
      <a class="tb-act" href="#">UNIMOG</a>
      <a class="tb-act tb-act--print" href="#"><i class="ti ti-printer" aria-hidden="true"></i>Generate printout</a>
      <span class="tb-act tb-tt">Timetable&nbsp;<b>A1</b></span>
    </div>

    <!-- ===== DATA TABLE ===== -->
    <table class="ta">
      <thead>
        <tr>
          <th rowspan="2">Index No.</th>
          <th colspan="3" class="ta-grp">Switch <span style="opacity:.7">(7 slots)</span></th>
          <th rowspan="2">Train Compo</th>
          <th rowspan="2">Time on I336</th>
          <th rowspan="2">Inserted</th>
          <th rowspan="2">Removed</th>
          <th rowspan="2">Remarks / Cause of failure / removal</th>
          <th colspan="3" class="ta-grp">Removal</th>
          <th rowspan="2"></th>
        </tr>
        <tr>
          <th>Index No.</th><th>Index No.</th><th>Index No.</th>
          <th>L2</th><th>L3</th><th>L4</th>
        </tr>
      </thead>
      <tbody>

        <!-- 23 — in service, one switch -->
        <tr class="row--service">
          <td class="ta-idx"><span class="ta-num">23</span><br><span class="ta-status is-service"><span class="led"></span>In service</span></td>
          <td><span class="ta-num ta-num--sw">07</span><span class="ta-time">14:32</span><span class="ta-driver">D. Reyes</span><a class="ta-del-sw" href="#" aria-label="Delete switch">&times;</a></td>
          <td><a class="ta-act" href="#"><i class="ti ti-arrows-exchange" aria-hidden="true"></i>Switch</a></td>
          <td></td>
          <td><div class="ta-cars"><a class="ta-car" href="#">3015</a><a class="ta-car" href="#">3016</a><a class="ta-car" href="#">3052</a><a class="ta-car" href="#">3100</a></div><a class="ta-edit" href="#"><i class="ti ti-pencil" aria-hidden="true"></i>Edit</a></td>
          <td><span class="ta-time">05:42</span></td>
          <td><span class="ta-time">05:50</span><span class="ta-driver">TO J. Santos</span></td>
          <td class="muted">&mdash;</td>
          <td class="ta-remarks"><span class="muted">&mdash;</span><br><a class="ta-act" href="#">Add remarks</a><a class="ta-act" href="#">Add incident</a></td>
          <td class="muted">&mdash;</td><td class="muted">&mdash;</td><td class="muted">&mdash;</td>
          <td><a class="ta-del" href="#" aria-label="Delete row">&times;</a></td>
        </tr>

        <!-- 05 — in service, two switches -->
        <tr class="row--service">
          <td class="ta-idx"><span class="ta-num">05</span><br><span class="ta-status is-service"><span class="led"></span>In service</span></td>
          <td><span class="ta-num ta-num--sw">12</span><span class="ta-time">09:15</span><span class="ta-driver">E. Lim</span><a class="ta-del-sw" href="#" aria-label="Delete switch">&times;</a></td>
          <td><span class="ta-num ta-num--sw">19</span><span class="ta-time">13:40</span><span class="ta-driver">G. Tan</span><a class="ta-del-sw" href="#" aria-label="Delete switch">&times;</a></td>
          <td><a class="ta-act" href="#"><i class="ti ti-arrows-exchange" aria-hidden="true"></i>Switch</a></td>
          <td><div class="ta-cars"><a class="ta-car" href="#">3034</a><a class="ta-car" href="#">3035</a><a class="ta-car" href="#">3070</a><a class="ta-car" href="#">3071</a></div><a class="ta-edit" href="#"><i class="ti ti-pencil" aria-hidden="true"></i>Edit</a></td>
          <td><span class="ta-time">05:30</span></td>
          <td><span class="ta-time">05:38</span><span class="ta-driver">TO A. Bautista</span></td>
          <td class="muted">&mdash;</td>
          <td class="ta-remarks"><span class="muted">&mdash;</span><br><a class="ta-act" href="#">Add remarks</a><a class="ta-act" href="#">Add incident</a></td>
          <td class="muted">&mdash;</td><td class="muted">&mdash;</td><td class="muted">&mdash;</td>
          <td><a class="ta-del" href="#" aria-label="Delete row">&times;</a></td>
        </tr>

        <!-- 31 — removed (door fault) -->
        <tr class="row--removed">
          <td class="ta-idx"><span class="ta-num">31</span><br><span class="ta-status is-removed"><span class="led"></span>Removed</span></td>
          <td><a class="ta-act" href="#"><i class="ti ti-arrows-exchange" aria-hidden="true"></i>Switch</a></td>
          <td></td>
          <td></td>
          <td><div class="ta-cars"><a class="ta-car" href="#">3028</a><a class="ta-car" href="#">3029</a><a class="ta-car" href="#">3061</a></div><a class="ta-edit" href="#"><i class="ti ti-pencil" aria-hidden="true"></i>Edit</a></td>
          <td><span class="ta-time">06:10</span></td>
          <td><span class="ta-time">06:18</span><span class="ta-driver">TO M. Cruz</span></td>
          <td><span class="ta-time">11:45</span><span class="ta-driver">TO R. Dela Cruz</span></td>
          <td class="ta-remarks">Door fault &mdash; Car 3061 pulled for inspection<br><a class="ta-incident" href="#">IN 2451</a><br><a class="ta-act" href="#">Add remarks</a><a class="ta-act" href="#">Add incident</a></td>
          <td class="ta-level">1st</td><td class="muted">&mdash;</td><td class="muted">&mdash;</td>
          <td><a class="ta-del" href="#" aria-label="Delete row">&times;</a></td>
        </tr>

        <!-- 09 — removed (ATP) -->
        <tr class="row--removed">
          <td class="ta-idx"><span class="ta-num">09</span><br><span class="ta-status is-removed"><span class="led"></span>Removed</span></td>
          <td><a class="ta-act" href="#"><i class="ti ti-arrows-exchange" aria-hidden="true"></i>Switch</a></td>
          <td></td>
          <td></td>
          <td><div class="ta-cars"><a class="ta-car" href="#">3041</a><a class="ta-car" href="#">3042</a><a class="ta-car" href="#">3055</a><a class="ta-car" href="#">3056</a></div><a class="ta-edit" href="#"><i class="ti ti-pencil" aria-hidden="true"></i>Edit</a></td>
          <td><span class="ta-time">06:25</span></td>
          <td><span class="ta-time">06:30</span><span class="ta-driver">TO L. Aquino</span></td>
          <td><span class="ta-time">09:12</span><span class="ta-driver">TO P. Mendoza</span></td>
          <td class="ta-remarks">ATP intervention &mdash; repeated brake demand<br><a class="ta-incident" href="#">IN 2453</a><br><a class="ta-act" href="#">Add remarks</a><a class="ta-act" href="#">Add incident</a></td>
          <td class="ta-level">2nd</td><td class="muted">&mdash;</td><td class="muted">&mdash;</td>
          <td><a class="ta-del" href="#" aria-label="Delete row">&times;</a></td>
        </tr>

        <!-- 18 — cancelled -->
        <tr class="row--cancelled">
          <td class="ta-idx"><span class="ta-num">18</span><br><span class="ta-status is-cancelled"><span class="led"></span>Cancelled</span></td>
          <td><a class="ta-act" href="#"><i class="ti ti-arrows-exchange" aria-hidden="true"></i>Switch</a></td>
          <td></td>
          <td></td>
          <td class="muted">n/a</td>
          <td><span class="ta-time">04:55</span></td>
          <td colspan="2" class="ta-cancel">CANCELLED</td>
          <td class="ta-remarks">Departure cancelled &mdash; traction power dip<br><a class="ta-incident" href="#">IN 2448</a><br><a class="ta-act" href="#">Add remarks</a><a class="ta-act" href="#">Add incident</a></td>
          <td class="muted">&mdash;</td><td class="ta-level">2nd</td><td class="muted">&mdash;</td>
          <td><a class="ta-del" href="#" aria-label="Delete row">&times;</a></td>
        </tr>

        <!-- 44 — reserve / standby (compo prepped, not yet inserted) -->
        <tr class="row--reserve">
          <td class="ta-idx"><span class="ta-num">44</span><br><span class="ta-status is-reserve"><span class="led"></span>Reserve</span></td>
          <td><a class="ta-act" href="#"><i class="ti ti-arrows-exchange" aria-hidden="true"></i>Switch</a></td>
          <td></td>
          <td></td>
          <td><div class="ta-cars"><a class="ta-car" href="#">3088</a><a class="ta-car" href="#">3089</a><a class="ta-car" href="#">3090</a></div><a class="ta-edit" href="#"><i class="ti ti-pencil" aria-hidden="true"></i>Edit</a></td>
          <td class="muted">&mdash;</td>
          <td class="muted">&mdash;</td>
          <td class="muted">&mdash;</td>
          <td class="ta-remarks"><span class="muted">Standby at depot</span><br><a class="ta-act" href="#">Add remarks</a><a class="ta-act" href="#">Add incident</a></td>
          <td class="muted">&mdash;</td><td class="muted">&mdash;</td><td class="muted">&mdash;</td>
          <td><a class="ta-del" href="#" aria-label="Delete row">&times;</a></td>
        </tr>

      </tbody>
    </table>
  </div>
  </div>

</div>

<script>
/* Preview-only: swap the theme class on the grid. Not part of the drop-in. */
(function(){
  var grid = document.getElementById('grid');
  var btns = document.querySelectorAll('.seg button');
  btns.forEach(function(b){
    b.addEventListener('click', function(){
      grid.className = 'ta-grid ' + b.dataset.theme;
      btns.forEach(function(x){ x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
    });
  });
})();
</script>
</body>
</html>