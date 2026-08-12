<?php
/* ===========================================================================
   incident_report_data.php  —  DRAFT for review, not yet wired in

   The single copy of the incident-gathering "recipe" that incident_summary.php,
   weekly_printout.php and generate_nis2.php each currently re-write on their
   own. Nothing here changes what any page displays; it just gives the three a
   shared place to read from so the query, the date-window logic, and the
   per-incident car/defect lookups live once instead of three times.

   Read this top to bottom — each function is one step of the recipe you and I
   talked through. Once it looks right, the three pages get their duplicated
   gathering deleted and replaced with a call to these.
   =========================================================================== */


/* ---------------------------------------------------------------------------
   STEP 1 — normalise the date window.

   Both weekly_printout and generate_nis2 build this clause by hand, with
   different variable names, and NIS has a typo: it ends the day at 23:23:59
   instead of 23:59:59, so incidents in that 36-minute window are dropped from
   the Excel but not from the on-screen view. Doing it here fixes that once.

   Pass $to = '' for a single day; pass both for a range.
   --------------------------------------------------------------------------- */
function isDateClause($from, $to = ''){
	if($to === '' || $to === null){
		return " like '".$from."%%' ";
	}
	return " between '".$from." 00:00:00' and '".$to." 23:59:59' ";
}


/* ---------------------------------------------------------------------------
   STEP 2 — the week's weekly_report row.

   This holds the analysis/summary narrative you type on the weekly page. NIS
   reads the same row to print it. Only the weekly page should CREATE one, so
   this just reads; it returns null when none exists yet and the caller decides
   whether to insert.
   --------------------------------------------------------------------------- */
function isWeeklyReport($db, $from, $to = ''){
	$add = "";
	if($to !== '' && $to !== null){
		$add = " and to_date like '".$to."%%'";
	}
	$sql = "select * from weekly_report where from_date like '".$from."%%' ".$add." limit 1";
	$rs = $db->query($sql);
	if(!$rs || !$rs->num_rows) return null;
	return $rs->fetch_assoc();
}


/* ---------------------------------------------------------------------------
   STEP 3 — the incidents for the window, each with its cars attached.

   This is the heart of the duplication. All three pages run this same base
   query (incident_report joined to incident_description), then loop and fetch
   incident_cars per row; NIS and weekly also fetch defects. Here it happens
   once and hands back a ready-assembled list.

   Returns a list of incidents. Each is the incident_report/description row plus
   two extra keys the pages already compute by hand:
       'cars'       => array of car_no strings
       'cars_label' => "car_a, car_b, car_c"  (the joined string the pages print)

   IMPORTANT — defects are NOT gathered here, on purpose.
   The callers do NOT agree on defects:
     - weekly_printout reads incident_defects from a SEPARATE 'external'
       connection (iss_db('external')), not the transport db;
     - generate_nis2 does not read defects at all — the NIS template has no
       defects column.
   Baking defects into the shared core would be wrong for both: it would add a
   column NIS never had and read it from the wrong connection for the weekly
   page. So the core stops at incidents + cars — the part they genuinely share —
   and each caller does its own defect lookup against its own connection after.
   Sharing only the true overlap is the point.
   --------------------------------------------------------------------------- */
function isReportWindow($db, $from, $to = '', $orderClause = null){
	$dClause = isDateClause($from, $to);

	// Sort: weekly_printout already carries a fix here — position('' in ...)
	// always returns 1, so the old expression sorted by only the FIRST
	// character of the incident number. The corrected form finds the first
	// SPACE and sorts by the numeric part before it. The include uses the
	// fixed version so every caller inherits the correction.
	//
	// $orderClause lets a caller substitute its own tail (e.g. weekly's
	// level-filter sorts from $_POST['sort_by']); when null, the default
	// numeric-incident-number sort applies.
	if($orderClause === null){
		$orderClause = " order by substring(incident_no,1,position(' ' in incident_no)-1)*1 ";
	}

	$sql = "select * from incident_report
	          inner join incident_description on incident_report.id=incident_description.incident_id
	         where incident_date ".$dClause." ".$orderClause;
	$rs = $db->query($sql);

	$out = array();
	if(!$rs) return $out;

	while($row = $rs->fetch_assoc()){
		$cars = array();
		$carRS = $db->query("select * from incident_cars where incident_id='".$row['incident_id']."'");
		if($carRS){ while($c = $carRS->fetch_assoc()){ $cars[] = $c['car_no']; } }
		$row['cars']       = $cars;
		$row['cars_label'] = implode(", ", $cars);

		$out[] = $row;
	}
	return $out;
}


/* ---------------------------------------------------------------------------
   NOTE — what does NOT move here, and why.

   Beyond defects (above), NIS also gathers train composition
   (train_incident_report), duty personnel, signatories, timetable, engineering
   mods. Those are NIS-only — the weekly view never uses them — so folding them
   in would make the shared core carry weight its callers don't need. They stay
   in generate_nis2.php, which calls isReportWindow() for the shared part and
   adds its own extras on top.

   The performance shape is unchanged: same base query, plus one car lookup per
   incident, exactly as the pages do now. If that per-row car query is ever a
   bottleneck, THIS is the one place to batch it — a single
   "where incident_id in (...)" would replace the loop for every caller at once.
   That option only exists because the logic is shared.
   --------------------------------------------------------------------------- */