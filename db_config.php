<?php
/* =========================================================================
   db_config.php — the ONE place every ISS page gets its MySQL credentials
   and database names from. Change a hostname, username, password, or a
   database name here and every file that calls iss_db() picks it up
   automatically -- nothing else needs to be touched.

   Background (2026-07): several connections across this codebase were
   still pointed at pre-migration database names (transport, external,
   user_transport) using root with a blank password -- leftovers from
   before the databases were renamed with an "is_" prefix for production.
   Those are fixed by routing through here. See:
     - processing.php: clearIncidentRecords(), search_preencoded
     - edit_ccdr.php: the incident_no rename handler
   Confirmed by Pat (2026-07): production now uses the is_-prefixed names
   exclusively, root is not the app-layer user, and a real password is
   set -- matching the psssilva credentials already used by every OTHER
   (non-broken) connection throughout this codebase, which is what this
   file centralizes.

   Usage:
       require_once("db_config.php");
       $db = iss_db('transport');          // is_transport
       $db2 = iss_db('external');          // is_external
       $db3 = iss_db('timetable');         // is_timetable
       $db4 = iss_db('user_transport');    // is_user_transport
   Each key is cached per-request: calling iss_db() twice for the same
   key returns the SAME connection object, no matter how many times or
   from how many functions it's called -- so replacing a stray extra
   `new mysqli(...)` with iss_db() is always safe, never opens a
   redundant connection.
   ========================================================================= */

$ISS_DB_HOST = "localhost";
$ISS_DB_USER = "psssilva";
$ISS_DB_PASS = "!D40nkC2azXg$";

/* logical name => actual (current, is_-prefixed) database name */
$ISS_DB_NAMES = array(
	'transport'      => 'is_transport',
	'external'       => 'is_external',
	'timetable'      => 'is_timetable',
	'user_transport' => 'is_user_transport',
);

$ISS_DB_CACHE = array();

function iss_db($which='transport'){
	global $ISS_DB_HOST, $ISS_DB_USER, $ISS_DB_PASS, $ISS_DB_NAMES, $ISS_DB_CACHE;

	if(isset($ISS_DB_CACHE[$which]) && $ISS_DB_CACHE[$which] instanceof mysqli){
		return $ISS_DB_CACHE[$which];
	}
	if(!isset($ISS_DB_NAMES[$which])){
		error_log("iss_db(): unknown database key '".$which."'");
		return false;
	}

	/* PHP 8.1+ makes mysqli throw mysqli_sql_exception on connect failure by
	   default, instead of just setting connect_errno -- an uncaught connect
	   failure would otherwise be a hard fatal error for the whole page rather
	   than the graceful `return false` every caller here expects. The
	   try/catch makes this safe on any PHP version: on < 8.1, where no
	   exception is thrown, it's a no-op and connect_errno is still checked
	   below as before. */
	try {
		$conn = new mysqli($ISS_DB_HOST, $ISS_DB_USER, $ISS_DB_PASS, $ISS_DB_NAMES[$which]);
	} catch (\Throwable $e) {
		error_log("iss_db('".$which."') connect threw: ".$e->getMessage());
		return false;
	}
	if($conn->connect_errno){
		error_log("iss_db('".$which."') connect failed (".$conn->connect_errno."): ".$conn->connect_error);
		return false;
	}
	$ISS_DB_CACHE[$which] = $conn;
	return $conn;
}