<?php
/* excel_chain_test.php — throwaway diagnostic, safe to drop next to the other
   pages and load directly in a browser. Checks the shared PHPExcel /
   excel_functions.php chain that generate_ccdr.php, generate_nis.php, and
   generate_sccdr.php all depend on -- the one piece none of this session's
   edits have touched. Prints exactly which link breaks, with errors forced
   visible so nothing hides behind a blank 500. Delete once you're done with it.

   Note: unlike db_config_test.php, a genuine PHP-version incompatibility in
   PHPExcel itself CAN still fatal this script outright at the point it tries
   to load that library -- that's expected, and it IS the diagnostic result:
   whatever error text appears is the same one hiding behind generate_ccdr.php
   / generate_nis.php / generate_sccdr.php's blank/500 output today. Each
   check is flushed immediately so everything before that point stays visible
   even if a later check fatals. */

ini_set("display_errors", "1");
error_reporting(E_ALL);

function step($label){
	echo "\n".$label." ... ";
	@ob_flush(); @flush();
}
function ok($msg=""){ echo "OK".($msg!=""?" -- ".$msg:"")."\n"; @ob_flush(); @flush(); }
function bad($msg){ echo "FAIL -- ".$msg."\n"; @ob_flush(); @flush(); }

echo "<pre>";
echo "Excel-chain diagnostic -- ".date("Y-m-d H:i:s")."\n";
echo str_repeat("=", 60)."\n";

$dir = __DIR__;

step("phpexcel/Classes/PHPExcel.php exists");
$p1 = $dir."/phpexcel/Classes/PHPExcel.php";
file_exists($p1) ? ok($p1) : bad("not found at ".$p1);

step("phpexcel/Classes/PHPExcel/IOFactory.php exists");
$p2 = $dir."/phpexcel/Classes/PHPExcel/IOFactory.php";
file_exists($p2) ? ok($p2) : bad("not found at ".$p2);

step("excel_functions.php exists");
$p3 = $dir."/excel_functions.php";
file_exists($p3) ? ok($p3) : bad("not found at ".$p3);

echo "\n".str_repeat("-", 60)."\n";
echo "Template forms and output folder:\n";

foreach(array("CCDR.xls" => "generate_ccdr.php", "new INCIDENT format.xls" => "generate_nis.php", "SCCDR.xls" => "generate_sccdr.php") as $tpl => $usedBy){
	step("forms/".$tpl." exists (used by ".$usedBy.")");
	$tp = $dir."/forms/".$tpl;
	if(file_exists($tp)){ ok(is_readable($tp) ? "readable" : "EXISTS BUT NOT READABLE"); }
	else { bad("not found at ".$tp); }
}

step("printout/ directory exists and is writable");
$pd = $dir."/printout";
if(is_dir($pd)){ ok(is_writable($pd) ? "writable" : "EXISTS BUT NOT WRITABLE -- copy()/save() would fail here"); }
else { bad("not found at ".$pd); }

echo "\n".str_repeat("-", 60)."\n";
echo "Now actually loading the library (this is the step most likely to\n";
echo "reveal a PHP-version incompatibility -- if the page stops right after\n";
echo "this line with no FAIL message, THAT stopping point is the answer):\n";
@ob_flush(); @flush();

step("require_once PHPExcel.php");
if(file_exists($p1)){
	require_once($p1);
	ok("loaded without a fatal error");
} else {
	bad("skipped -- file missing (see above)");
}

step("require_once IOFactory.php");
if(file_exists($p2)){
	require_once($p2);
	ok("loaded without a fatal error");
} else {
	bad("skipped -- file missing (see above)");
}

step("require excel_functions.php");
if(file_exists($p3)){
	require($p3);
	ok("loaded without a fatal error");
} else {
	bad("skipped -- file missing (see above)");
}

step("expected functions defined after the above");
$need = array('loadExistingWorkbook','createWorksheet','addContent','setRange','save');
$missing = array();
foreach($need as $fn){ if(!function_exists($fn)) $missing[] = $fn; }
if(empty($missing)){ ok("all 5 present: ".implode(", ", $need)); }
else { bad("missing: ".implode(", ", $missing)); }

step("PHPExcel class itself is usable");
if(class_exists('PHPExcel')){
	try {
		$test = new PHPExcel();
		ok("new PHPExcel() succeeded");
	} catch (\Throwable $e) {
		bad("new PHPExcel() threw: ".$e->getMessage());
	}
} else {
	bad("PHPExcel class not defined after requiring PHPExcel.php");
}

echo "\n".str_repeat("=", 60)."\n";
echo "Done. If everything above says OK, the problem is likely further into\n";
echo "one specific file's own logic rather than this shared chain -- the\n";
echo "exact error text from actually opening generate_ccdr.php etc. (with\n";
echo "display_errors on, or from the error log) is the next thing needed.\n";
echo "Delete this file (excel_chain_test.php) once you're finished with it.\n";
echo "</pre>";