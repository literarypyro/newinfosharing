<?php
/* Session before ANY output -- the console header calls session_start(). */
if(session_id()==""){ session_start(); }

/* Whether the console nav renders (see the require further down, in <body>). */
$NAV_SHOW = !(isset($_GET['embed']) && $_GET['embed']!='');

/* =====================================================================
   mobile_app.php  --  where staff get the ISS Android app.

   Distribution page, not a download endpoint: the file itself is served
   by app_download.php, which is deliberately NOT session-gated because
   the install happens on a phone that has no ISS session.  This page IS
   gated -- it is a console page like any other, and it carries the part
   that actually needs explaining: sideloading.

   The whole page is driven by whatever .apk files are sitting in the
   mobile/ folder.  Releasing a new build is "copy the file in"; nothing
   here needs editing.
   ===================================================================== */

if(file_exists(dirname(__FILE__)."/app_download.php")){
	require_once(dirname(__FILE__)."/app_download.php");
}

/* Guarded, in the house style: a missing helper degrades this page to a
   readable message instead of a fatal. */
$apk_ok    = function_exists('apk_builds');
$builds    = $apk_ok ? apk_builds() : array();
$latest    = $apk_ok ? apk_latest() : null;

/* Absolute URL, because the whole point is typing or scanning it on a
   device that is not this browser. */
$scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$host      = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$base_url  = $scheme.'://'.$host.rtrim(dirname($_SERVER['PHP_SELF']),'/\\').'/';
$dl_url    = $base_url.'app_download.php';

function ma_h($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }

/* The QR is drawn SERVER-SIDE as inline SVG. The first version of this
   page loaded a JavaScript library that was never uploaded, so the box
   came out empty -- and a CDN would have failed the same way, since the
   station PCs have no internet. qr_svg.php has no dependencies at all.
   Guarded in the house style: if it is missing, the URL below still
   renders and the QR panel simply hides. */
if(file_exists(dirname(__FILE__)."/qr_svg.php")){
	require_once(dirname(__FILE__)."/qr_svg.php");
}
$qr_markup = function_exists('qr_svg') ? qr_svg($dl_url,150,'M') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mobile App &mdash; Information Sharing System</title>
<style>
:root{
	--cf-blue:#00529B; --cf-blue-dk:#003C73; --cf-gold:#FDB813;
	--cf-ink:#1f2430; --cf-ink-2:#5a6472; --cf-ink-3:#8b93a1;
	--cf-line:#dfe3ea; --cf-surface:#fff; --cf-canvas:#f4f6fa;
	--cf-ok:#1f7a44; --cf-ok-bg:#e6f4ec;
	--cf-warn:#8a5a06; --cf-warn-bg:#fdf1d8;
}
.ma-wrap{max-width:1080px;margin:0 auto;padding:16px 18px 48px;
	font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:var(--cf-ink);background:var(--cf-canvas)}
.ma-wrap *{box-sizing:border-box}
.ma-bar{background:var(--cf-blue);color:#fff;border-radius:8px;padding:12px 18px;margin-bottom:14px}
.ma-bar h1{margin:0;font-size:18px;font-weight:600;letter-spacing:.2px}
.ma-bar p{margin:4px 0 0;font-size:13px;color:#cfe0f2}

.ma-hero{display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center;
	background:var(--cf-surface);border:1px solid var(--cf-line);border-radius:12px;padding:20px 22px;margin-bottom:14px}
.ma-ver{display:inline-block;font-size:12px;font-weight:700;letter-spacing:.4px;text-transform:uppercase;
	background:var(--cf-ok-bg);color:var(--cf-ok);padding:3px 9px;border-radius:6px;margin-bottom:9px}
.ma-hero h2{margin:0 0 6px;font-size:22px;font-weight:600}
.ma-meta{font-size:13px;color:var(--cf-ink-2);font-variant-numeric:tabular-nums}
.ma-btn{display:inline-block;margin-top:14px;background:var(--cf-gold);color:#3a2a00;text-decoration:none;
	font-size:15px;font-weight:700;padding:11px 22px;border-radius:8px;border:1px solid #e0a70f}
.ma-btn:hover{background:#ffc734}
.ma-url{margin-top:14px;font-size:12px;color:var(--cf-ink-2)}
.ma-url code{display:inline-block;font-family:Consolas,Menlo,monospace;font-size:14px;color:var(--cf-blue-dk);
	background:var(--cf-canvas);border:1px solid var(--cf-line);border-radius:6px;padding:6px 10px;margin-top:5px;
	-webkit-user-select:all;user-select:all}
.ma-qr{text-align:center;min-width:172px}
.ma-qr-box{width:160px;height:160px;display:flex;align-items:center;justify-content:center;
	background:#fff;border:1px solid var(--cf-line);border-radius:8px;padding:8px;margin:0 auto}
.ma-qr small{display:block;margin-top:7px;font-size:11px;color:var(--cf-ink-3)}

.ma-card{background:var(--cf-surface);border:1px solid var(--cf-line);border-radius:12px;padding:16px 20px;margin-bottom:14px}
.ma-card h3{margin:0 0 12px;font-size:15px;font-weight:600}
.ma-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px}

ol.ma-steps{margin:0;padding:0;list-style:none;counter-reset:s}
ol.ma-steps li{counter-increment:s;position:relative;padding:0 0 14px 38px;font-size:13.5px;line-height:1.55}
ol.ma-steps li:last-child{padding-bottom:0}
ol.ma-steps li::before{content:counter(s);position:absolute;left:0;top:0;width:25px;height:25px;
	background:var(--cf-blue);color:#fff;border-radius:50%;font-size:12px;font-weight:700;
	display:flex;align-items:center;justify-content:center}
ol.ma-steps b{color:var(--cf-ink)}
ol.ma-steps em{color:var(--cf-ink-2);font-style:normal;font-size:12.5px;display:block;margin-top:3px}

.ma-note{background:var(--cf-warn-bg);border:1px solid #e8cf9a;color:var(--cf-warn);
	border-radius:8px;padding:11px 14px;font-size:13px;line-height:1.5;margin-top:12px}
.ma-note b{display:block;margin-bottom:3px}

table.ma-tbl{width:100%;border-collapse:collapse;font-size:13px}
table.ma-tbl th{text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.4px;
	color:var(--cf-ink-3);font-weight:600;padding:0 10px 7px 0;border-bottom:1px solid var(--cf-line)}
table.ma-tbl td{padding:8px 10px 8px 0;border-bottom:1px solid var(--cf-line);font-variant-numeric:tabular-nums}
table.ma-tbl tr:last-child td{border-bottom:0}
table.ma-tbl a{color:var(--cf-blue);text-decoration:none;font-weight:600}
table.ma-tbl a:hover{text-decoration:underline}
.ma-cur{color:var(--cf-ok);font-weight:600}

.ma-empty{padding:26px 4px;text-align:center;color:var(--cf-ink-3);font-size:13.5px}
.ma-empty code{font-family:Consolas,Menlo,monospace;color:var(--cf-ink-2)}
@media (max-width:720px){ .ma-hero{grid-template-columns:1fr} .ma-qr{display:none} }
</style>
</head>
<body>
<?php
/* Console nav. Tmenu_2.php emits ONLY the header markup and the nav list --
   never <!DOCTYPE>/<html>/<head> -- so it belongs here inside <body>. */
if($NAV_SHOW && file_exists(dirname(__FILE__)."/Tmenu_2.php")){ require("Tmenu_2.php"); }
?>
<div class="ma-wrap">

	<div class="ma-bar">
		<h1>ISS Dashboard App</h1>
		<p>Android &middot; for Transport Division staff</p>
	</div>

<?php if(!$apk_ok){ ?>
	<div class="ma-card"><div class="ma-empty">
		Download helper not found. Upload <code>app_download.php</code> alongside this page.
	</div></div>
<?php } else if(!$latest){ ?>
	<div class="ma-card"><div class="ma-empty">
		<p style="margin:0 0 8px">No installer has been published yet.</p>
		<p style="margin:0">Copy the build into the <code>mobile/</code> folder, named
		<code><?php echo ma_h(APK_PREFIX); ?>1.0.0.apk</code>. This page picks it up automatically &mdash;
		nothing here needs editing when a version changes.</p>
	</div></div>
<?php } else { ?>

	<div class="ma-hero">
		<div>
			<span class="ma-ver">Version <?php echo ma_h($latest['version']); ?></span>
			<h2>Information Sharing System</h2>
			<div class="ma-meta">
				<?php echo ma_h(apk_human_size($latest['size'])); ?> &middot;
				published <?php echo ma_h(date('d M Y',$latest['modified'])); ?> &middot;
				Android 8.0 or newer
			</div>
			<a class="ma-btn" href="app_download.php">Download installer</a>
			<div class="ma-url">
				Or open this on the phone's browser:
				<br><code id="ma-dl-url"><?php echo ma_h($dl_url); ?></code>
			</div>
		</div>
<?php if($qr_markup!==''){ ?>
		<div class="ma-qr">
			<div class="ma-qr-box"><?php echo $qr_markup; ?></div>
			<small>Point the phone camera here</small>
		</div>
<?php } ?>
	</div>

	<div class="ma-grid">

		<div class="ma-card">
			<h3>Installing it</h3>
			<ol class="ma-steps">
				<li><b>Connect the phone to the MRT-3 network.</b>
					<em>Office Wi-Fi or VPN. The app talks to this server directly, so it will not work on mobile data alone.</em></li>
				<li><b>Open the link above on the phone</b> &mdash; scan the QR, or type the address into Chrome.</li>
				<li><b>Tap the downloaded file.</b>
					<em>Chrome shows it in the download bar, or find it under Files &rarr; Downloads.</em></li>
				<li><b>Allow installs from Chrome when prompted.</b>
					<em>Android asks once per browser: "Allow from this source". This is normal for apps that do not come from the Play Store.</em></li>
				<li><b>If Play Protect warns, choose "Install anyway".</b>
					<em>It flags every app it has not seen before, which includes any in-house app.</em></li>
<!--
				<li><b>Sign in with your usual ISS username and password.</b></li>
				-->
			</ol>
			<div class="ma-note">
				<b>Updating later</b>
				Download the newer version and install over the top &mdash; your login is kept.
				Do not uninstall first unless asked to.
			</div>
		</div>

		<div class="ma-card">
			<h3>Version history</h3>
			<table class="ma-tbl">
				<thead><tr><th>Version</th><th>Published</th><th>Size</th><th></th></tr></thead>
				<tbody>
<?php	$first=true; foreach($builds as $b){ ?>
					<tr>
						<td><?php echo ma_h($b['version']); ?><?php if($first){ ?> <span class="ma-cur">&middot; current</span><?php } ?></td>
						<td><?php echo ma_h(date('d M Y',$b['modified'])); ?></td>
						<td><?php echo ma_h(apk_human_size($b['size'])); ?></td>
						<td><a href="app_download.php?v=<?php echo ma_h($b['version']); ?>">Download</a></td>
					</tr>
<?php	$first=false; } ?>
				</tbody>
			</table>
			<div class="ma-note" style="background:var(--cf-canvas);border-color:var(--cf-line);color:var(--cf-ink-2)">
				<b style="color:var(--cf-ink)">Trouble installing?</b>
				If the download will not open, the phone may be blocking unknown apps entirely &mdash;
				Settings &rarr; Apps &rarr; Special access &rarr; Install unknown apps &rarr; Chrome &rarr; Allow.
				Contact Support Division if it still refuses.
			</div>
		</div>

	</div>

<?php } ?>

</div>

</body>
</html>