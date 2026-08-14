<?php
/* =====================================================================
   app_download.php  --  serves the ISS mobile APK.

   DELIBERATELY NOT SESSION-GATED.  People install by scanning the QR or
   typing the URL on their PHONE, and the phone has no ISS session -- a
   session check here would make the normal install path impossible.  The
   INSTRUCTIONS page (mobile_app.php) is gated; the file is not.  On the
   station LAN the APK is not a secret.
      If that is ever unacceptable, the fix is NOT session_start() here --
   it is a short-lived one-time token minted by mobile_app.php, stored
   server-side (not in the session), and embedded in the QR.

   Why a gateway instead of a plain link to the .apk:
     1. The URL is STABLE across releases -- no QR to reprint, no wiki to
        edit, when 1.0.3 replaces 1.0.2.  Drop the new file in and the
        same link serves it.
     2. Correct Content-Type. Apache without an AddType for .apk serves
        application/octet-stream, and some Android browsers then refuse
        to hand the file to the package installer.
     3. Download logging, so you know whether a rollout has landed.
     4. The APK directory can be closed to direct browsing (.htaccess),
        so a half-uploaded file is never reachable mid-transfer.

   Usage:
     app_download.php                -> newest build
     app_download.php?v=1.0.2        -> that specific version
     app_download.php?manifest=1     -> JSON, for the app's update check
   ===================================================================== */

if(!defined('APK_DIR'))    { define('APK_DIR',    dirname(__FILE__).'/mobile'); }
if(!defined('APK_PREFIX')) { define('APK_PREFIX', 'iss-mobile-'); }
if(!defined('APK_LOG'))    { define('APK_LOG',    dirname(__FILE__).'/mobile/downloads.log'); }

/* ---------------------------------------------------------------------
   Discover the builds on disk.  Filenames are the single source of
   truth -- iss-mobile-1.0.2.apk -- so releasing is "copy one file in",
   with no database row and no config to edit in step with it.
   --------------------------------------------------------------------- */
function apk_builds(){
	static $cache=null;
	if($cache!==null){ return $cache; }
	$out=array();
	if(is_dir(APK_DIR)){
		$dh=@opendir(APK_DIR);
		if($dh){
			while(($f=readdir($dh))!==false){
				if(!preg_match('/^'.preg_quote(APK_PREFIX,'/').'([0-9]+(?:\.[0-9]+)*)\.apk$/i',$f,$m)){ continue; }
				$path=APK_DIR.'/'.$f;
				if(!is_file($path)){ continue; }
				$out[]=array(
					'version'  => $m[1],
					'file'     => $f,
					'path'     => $path,
					'size'     => filesize($path),
					'modified' => filemtime($path)
				);
			}
			closedir($dh);
		}
	}
	usort($out,'apk_cmp');
	return $cache=$out;
}

/* Newest first, by version number -- so 1.0.10 sorts above 1.0.9, which
   a plain string sort gets wrong. */
function apk_cmp($a,$b){ return version_compare($b['version'],$a['version']); }

function apk_latest(){
	$b=apk_builds();
	return count($b) ? $b[0] : null;
}

function apk_find($version){
	foreach(apk_builds() as $b){
		if($b['version']===$version){ return $b; }
	}
	return null;
}

function apk_human_size($bytes){
	if($bytes>=1048576){ return round($bytes/1048576,1).' MB'; }
	if($bytes>=1024){ return round($bytes/1024).' KB'; }
	return $bytes.' B';
}

/* Only run the delivery half when this file is the request target --
   mobile_app.php includes it purely for the helpers above. */
if(basename($_SERVER['SCRIPT_FILENAME'])!==basename(__FILE__)){ return; }

/* ---------------------------------------------------------------------
   Manifest -- what the installed app polls to learn an update exists.
   Unauthenticated on purpose: it discloses a version number and nothing
   else, and the app has no session either.
   --------------------------------------------------------------------- */
if(isset($_GET['manifest'])){
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-cache');
	$b=apk_latest();
	if(!$b){
		echo '{"available":false}';
		exit;
	}
	/* Build an absolute URL so the app doesn't have to reassemble one. */
	$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
	$host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
	$base   = $scheme.'://'.$host.rtrim(dirname($_SERVER['PHP_SELF']),'/\\').'/';
	echo json_encode(array(
		'available'    => true,
		'version'      => $b['version'],
		'size'         => $b['size'],
		'released'     => date('c',$b['modified']),
		'download_url' => $base.'app_download.php'
	));
	exit;
}

/* ---------------------------------------------------------------------
   Deliver the file.
   --------------------------------------------------------------------- */
$build = null;
if(isset($_GET['v']) && $_GET['v']!==''){
	/* Matched against the discovered list, never used to build a path --
	   so no traversal is possible regardless of what arrives here. */
	$build = apk_find(preg_replace('/[^0-9.]/','',(string)$_GET['v']));
} else {
	$build = apk_latest();
}

if(!$build){
	header('HTTP/1.1 404 Not Found');
	header('Content-Type: text/html; charset=utf-8');
	echo '<!DOCTYPE html><meta charset="utf-8">';
	echo '<p style="font:14px sans-serif;padding:20px">No installer is currently published. ';
	echo 'Ask the Transport Division IT staff to upload a build.</p>';
	exit;
}

/* Best-effort log.  Never let logging failure block a download. */
$who = 'anonymous';
if(session_id()!=='' || isset($_COOKIE[session_name()])){
	@session_start();
	if(isset($_SESSION['username'])){ $who=$_SESSION['username']; }
}
@file_put_contents(
	APK_LOG,
	date('Y-m-d H:i:s')."\t".$build['version']."\t".$who."\t".
	(isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'-')."\t".
	(isset($_SERVER['HTTP_USER_AGENT'])?str_replace("\t",' ',$_SERVER['HTTP_USER_AGENT']):'-')."\n",
	FILE_APPEND|LOCK_EX
);

/* Discard any buffered output -- a stray newline from an include
   corrupts a binary download and the APK fails to parse on the device. */
while(ob_get_level()>0){ @ob_end_clean(); }

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="'.$build['file'].'"');
header('Content-Length: '.$build['size']);
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: public');
header('X-APK-Version: '.$build['version']);

/* Chunked, so a 40 MB APK doesn't have to fit in memory_limit. */
$fp=@fopen($build['path'],'rb');
if($fp){
	while(!feof($fp)){
		echo fread($fp,262144);
		@flush();
	}
	fclose($fp);
} else {
	readfile($build['path']);
}
exit;