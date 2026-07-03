<?php
/* =========================================================================
   db_connect.php — single shared MySQL connection + prepared-statement
   helpers for train_availability.php. (Item #2 of the train_availability
   pass.)

   2026-07 update: credentials no longer live here. They live in exactly
   one place system-wide -- db_config.php -- and this file just asks it
   for the 'transport' (is_transport) connection. Every other file that
   was touched in the same pass (processing.php, edit_ccdr.php, the
   generate_*.php / weekly_printout.php printouts) draws from the same
   db_config.php, so a credential rotation is now a one-file change.

   Adoption: any ISS page can switch to this by adding
       require_once("db_connect.php");
   and replacing  $db->query($sql)  call sites with db_query()/db_exec().
   ========================================================================= */

require_once("db_config.php");

$db = iss_db('transport');
if ($db === false) {
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