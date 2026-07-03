<?php
/* db_config_test.php — throwaway diagnostic, safe to drop next to the other pages
   and load directly in a browser. Read-only: does not touch, modify, or delete
   anything. Tests each of the 4 centralized connections one at a time and prints
   the exact result for each, so a failure is visible on the page itself instead
   of turning into a blank 500. Delete this file once the problem is found --
   it's not meant to stay on the server long-term. */

ini_set("display_errors", "1");
error_reporting(E_ALL);

echo "<pre>";
echo "db_config.php diagnostic -- ".date("Y-m-d H:i:s")."\n";
echo str_repeat("=", 60)."\n\n";

if(!file_exists(__DIR__."/db_config.php")){
	echo "FAIL: db_config.php not found in this directory (".__DIR__.").\n";
	echo "</pre>";
	exit;
}
echo "OK: db_config.php found at ".__DIR__."/db_config.php\n\n";

require_once("db_config.php");

if(!function_exists('iss_db')){
	echo "FAIL: db_config.php loaded, but iss_db() is not defined.\n";
	echo "This usually means db_config.php itself has a PHP error -- check its\n";
	echo "own syntax with:  php -l db_config.php\n";
	echo "</pre>";
	exit;
}
echo "OK: iss_db() is defined.\n\n";

foreach(array('transport','external','timetable','user_transport') as $key){
	echo "Testing iss_db('".$key."') ... ";
	$conn = iss_db($key);
	if($conn === false){
		echo "FAIL\n";
		echo "  iss_db() returned false. The real reason was written to the PHP\n";
		echo "  error log (search it for a line starting with \"iss_db('".$key."')\").\n";
		echo "  Most likely cause: the username/password/database name for this\n";
		echo "  key in db_config.php doesn't match what's actually on the server.\n\n";
	} else {
		$dbName = $conn->query("select database() d")->fetch_assoc()['d'];
		echo "OK -- connected to database: ".$dbName."\n\n";
	}
}

echo str_repeat("=", 60)."\n";
echo "Done. Delete this file (db_config_test.php) once you're finished with it.\n";
echo "</pre>";
