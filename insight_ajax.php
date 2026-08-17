<?php
/* insight_ajax.php -- async narrative fetch.
 *
 * The report page renders instantly with the deterministic analysis, then
 * calls this to swap in the model's version if one is configured and awake.
 * A slow or dead provider therefore costs the page nothing.
 *
 * Reads the context from the SESSION by nonce, never from the request body,
 * so no caller can hand it fabricated figures to narrate. */

session_start();
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$base = dirname(__FILE__);
if (!file_exists($base . '/iss_insight.php')) { echo ''; exit; }
require_once($base . '/iss_insight.php');
if (file_exists($base . '/iss_insight_analytics.php')) {
	require_once($base . '/iss_insight_analytics.php');
}
if (file_exists($base . '/iss_insight_audience.php')) {
	require_once($base . '/iss_insight_audience.php');
}

$k   = isset($_GET['k']) ? $_GET['k'] : '';
$ctx = iss_insight_unstash($k);
if ($ctx === null) { echo ''; exit; }

$showAudit = false;                       /* set true for admin users */
if (isset($_SESSION['ULev']) && $_SESSION['ULev'] == 1) { $showAudit = true; }

$out = iss_insight($ctx);

/* Must return the SAME shape the page rendered, toggle included -- otherwise
   the async swap silently downgrades a dual block to a technical-only one and
   the reader's Executive tab disappears mid-session. The findings are already
   on $out from iss_insight(); recomputed here only if that path was skipped. */
if (function_exists('iss_insight_html_dual')) {
	$c = iss_insight_normalize($ctx);
	$F = isset($out['findings_raw']) && is_array($out['findings_raw'])
	   ? $out['findings_raw']            /* base + advanced, set by iss_insight() */
	   : iss_insight_findings($c);
	echo iss_insight_html_dual($c, $F, $out, $showAudit);
} else {
	echo iss_insight_html($out, $showAudit);
}