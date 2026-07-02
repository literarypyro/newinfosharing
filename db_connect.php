<?php
/* =========================================================================
   db_connect.php — single shared MySQL connection + prepared-statement
   helpers for the ISS pages. (Item #2 of the train_availability pass.)

   Adoption: any ISS page can switch to this by adding
       require_once("db_connect.php");
   and replacing  $db->query($sql)  call sites with db_query()/db_exec().

   CREDENTIALS — pick one, then rotate the password (it has lived inside
   page source for years; old copies/backups may still carry it):
     a) Preferred: move this file OUTSIDE the web root and require it as
        require_once(__DIR__."/../iss_private/db_connect.php");
     b) Or keep it beside the pages but deny direct HTTP access to it in
        the server config (Apache 2.4:  <Files "db_connect.php">
        Require all denied </Files>).
   ========================================================================= */

$DB_HOST = "localhost";
$DB_USER = "psssilva";
$DB_PASS = "!D40nkC2azXg$";   /* TODO: rotate in MySQL, then update here only */
$DB_NAME = "is_transport";

$db = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($db->connect_errno) {
	error_log("ISS db_connect (".$db->connect_errno."): ".$db->connect_error);
	die("Database connection failed.");
}

/* Deliberately NOT enabled yet — coordinate with the _ENYE_ handling in
   processing.php (item #6) first, because changing the connection charset
   changes how legacy rows read back:
$db->set_charset("utf8");
*/

/* ── Prepared-statement helpers ──────────────────────────────────────────
   db_exec($db,$sql,$params)  : prepare/bind/execute; returns mysqli_stmt
                                or false. Use for INSERT/UPDATE/DELETE.
                                $db->insert_id works right after, as before.
   db_query($db,$sql,$params) : same, then returns a buffered mysqli_result
                                — drop-in for old $db->query() SELECT call
                                sites (num_rows / fetch_assoc unchanged).
   Notes:
   - Every param is bound as a string ('s'); MySQL coerces for numeric
     columns exactly as the old quoted literals did.
   - null params are coerced to "" to match the old behavior, where an
     unset variable concatenated into SQL produced an empty string,
     never SQL NULL.
   - Uses argument unpacking (PHP 5.6+) and get_result() (mysqlnd, the
     default driver in stock PHP 7/8 builds) — both fine on the current
     deployment; neither runs on a true 5.4 box.
   ------------------------------------------------------------------------ */
function db_exec($db, $sql, $params = array()) {
	$stmt = $db->prepare($sql);
	if ($stmt === false) {
		error_log("ISS db_exec prepare failed: ".$db->error." -- ".$sql);
		return false;
	}
	if (count($params) > 0) {
		foreach ($params as $k => $v) {
			if ($v === null) { $params[$k] = ""; }
		}
		$stmt->bind_param(str_repeat("s", count($params)), ...$params);
	}
	if (!$stmt->execute()) {
		error_log("ISS db_exec execute failed: ".$stmt->error." -- ".$sql);
		return false;
	}
	return $stmt;
}

function db_query($db, $sql, $params = array()) {
	$stmt = db_exec($db, $sql, $params);
	if ($stmt === false) { return false; }
	return $stmt->get_result();
}