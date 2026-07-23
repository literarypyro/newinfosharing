<?php
/* ===========================================================================
   incident_equipment.php — the ONE place that knows how an incident links to
   equipment. Every report includes this instead of touching incident_report.equipt
   or incident_equipt directly.

   WHY THIS FILE EXISTS
   --------------------
   The console is mid-migration between two models:

     OLD   incident_report.equipt        one equipment per incident (1:1)
     NEW   incident_equipt(incident_id, equipt_id, subitem_id)   many per incident

   During the transition BOTH are live: legacy incidents carry the old column and
   have no junction rows; new incidents have junction rows. A report that reads
   only one of them silently loses half the data. ccsEquipmentLinkSql() below
   presents both as a single relation, junction-first with a legacy fallback, so
   reports never have to care which model a given incident uses.

   WHEN THE MIGRATION FINISHES and incident_report.equipt is retired, delete the
   second half of the UNION in ccsEquipmentLinkSql() — one edit, every report
   follows. That is the entire point of this file.

   THE COUNTING TRAP — read this before adapting anything
   ------------------------------------------------------
   With a junction table there are now THREE units, not two, and they multiply:

     incidents                one row per incident
     car-level failures       incident x affected car
     equipment-level failures incident x implicated equipment

   Joining incident_cars AND the equipment link in the same query produces a
   CROSS PRODUCT: an incident on 3 cars implicating 2 equipment items yields 6
   rows, not 3 and not 2. Under the old 1:1 model this could not happen, so
   every existing report uses count(*) and was correct. Under the new model
   count(*) is WRONG in any query that touches both.

   Use the ccsCount* helpers below instead. They spell out which pair is being
   made distinct, which is the thing that is easy to get wrong and impossible to
   spot afterwards in a chart.
   =========================================================================== */


/* ---------------------------------------------------------------------------
   CONFIG — adjust to match the real schema.
   The junction's subitem_id points at a lookup table this file has not seen.
   Set these three to the actual table and column names; if subitems are not
   needed yet, leave them and simply do not call ccsSubitemMap().
   --------------------------------------------------------------------------- */
if(!defined('CCS_SUBITEM_TABLE')) define('CCS_SUBITEM_TABLE', 'sub_item');
if(!defined('CCS_SUBITEM_ID'))    define('CCS_SUBITEM_ID',    'id');
// Label column is detected at runtime from the table itself (see
// ccsSubitemMap) — the lookup tables in this schema are inconsistent about it
// (equipment uses equipment_name, other_problem uses problem), so detecting
// beats hardcoding a guess that fails silently.
if(!defined('CCS_SUBITEM_NAME'))  define('CCS_SUBITEM_NAME',  '');

/* CCS_LEGACY_FALLBACK — set to false once every incident_report.equipt value
   has been backfilled into incident_equipt.

   This is not just tidiness. While it is true, ccsEquipmentLinkSql() returns a
   UNION, and MySQL always MATERIALISES a derived table containing a UNION into
   a temporary table with no indexes — once per query that uses it. The indexes
   on incident_equipt cannot help the join in that case, because the join is
   against the temp copy, not the table.

   Set this false and the subquery collapses to a single-table select, which
   MySQL can merge into the outer query and satisfy from the indexes. That is
   the difference between the reports scaling and not. */
if(!defined('CCS_LEGACY_FALLBACK')) define('CCS_LEGACY_FALLBACK', true);


/* ---------------------------------------------------------------------------
   ccsEquipmentLinkSql()

   Returns a parenthesised subquery exposing three columns —
   incident_id, equipt_id, subitem_id — for every incident/equipment link,
   whichever model the incident was recorded under.

   Join it wherever a report currently reads incident_report.equipt:

       from incident_report
       inner join ".ccsEquipmentLinkSql()." el on el.incident_id = incident_report.id
       where el.equipt_id = '123'

   $schema lets the same shape be used against the legacy database, e.g.
   ccsEquipmentLinkSql('is_transport_old'). Pass '' for the current one.

   ONLY is_transport HAS incident_equipt. is_transport_old is the pre-migration
   database, attached because of migration issues, and was never given the
   junction table — so for any schema other than the current one this returns
   the legacy column alone. Emitting the junction half there would reference a
   table that does not exist and fail the whole query.

   NOTE ON THE FALLBACK: an incident is treated as legacy only when it has NO
   junction rows at all. That means a partially-migrated incident — junction
   rows added but the old column not yet cleared — is read from the junction
   and its old column ignored, rather than counted twice. That is the safer
   failure mode, but it does mean a half-migrated incident silently loses the
   old value, so migrate an incident's links completely or not at all.
   --------------------------------------------------------------------------- */
function ccsEquipmentLinkSql($schema = ''){
	$p = ($schema !== '') ? $schema.'.' : '';

	// is_transport is the only database carrying incident_equipt.
	$hasJunction = ($schema === '' || $schema === 'is_transport');

	// Post-migration: no UNION, so the subquery merges into the outer query and
	// the incident_equipt indexes actually get used.
	if($hasJunction && !CCS_LEGACY_FALLBACK){
		return "(
			select ie.incident_id as incident_id,
			       ie.equipt_id   as equipt_id,
			       ie.subitem_id  as subitem_id
			  from {$p}incident_equipt ie
		)";
	}

	if(!$hasJunction){
		// Legacy database: the old 1:1 column is the only linkage there is.
		return "(
			select ir.id     as incident_id,
			       ir.equipt as equipt_id,
			       null      as subitem_id
			  from {$p}incident_report ir
			 where ir.equipt is not null and ir.equipt <> ''
		)";
	}

	return "(
		select ie.incident_id  as incident_id,
		       ie.equipt_id    as equipt_id,
		       ie.subitem_id   as subitem_id
		  from {$p}incident_equipt ie
		union all
		select ir.id           as incident_id,
		       ir.equipt       as equipt_id,
		       null            as subitem_id
		  from {$p}incident_report ir
		 where ir.equipt is not null and ir.equipt <> ''
		   and not exists (
		       select 1 from {$p}incident_equipt ie2 where ie2.incident_id = ir.id
		   )
	)";
}


/* ---------------------------------------------------------------------------
   Counting helpers.

   Each returns a SQL aggregate expression. They assume the equipment link is
   aliased "el" and, where cars are involved, incident_cars is aliased "ic".
   Use them instead of count(*) in any query that joins more than one of
   {incidents, cars, equipment} — see THE COUNTING TRAP above.
   --------------------------------------------------------------------------- */

// Distinct incidents, however many cars or equipment items they touch.
function ccsCountIncidents($alias = 'el'){
	return "count(distinct {$alias}.incident_id)";
}

// Incident x equipment. The unit for "which equipment fails most".
function ccsCountEquipmentFailures($alias = 'el'){
	return "count(distinct {$alias}.incident_id, {$alias}.equipt_id)";
}

// Incident x car. The unit the stats reports already use, and the one that
// keeps them reconciled with each other.
function ccsCountCarFailures($alias = 'ic'){
	return "count(distinct {$alias}.incident_id, {$alias}.car_no)";
}

// Incident x car x equipment — the full cross product. Rarely what anyone
// wants; provided so that if a report genuinely needs it, it says so out loud
// rather than arriving at it by accident through count(*).
function ccsCountCarEquipmentFailures($elAlias = 'el', $icAlias = 'ic'){
	return "count(distinct {$elAlias}.incident_id, {$icAlias}.car_no, {$elAlias}.equipt_id)";
}


/* ---------------------------------------------------------------------------
   Lookup maps — loaded once per page, not per row.
   --------------------------------------------------------------------------- */
function ccsEquipmentMap($db){
	$m = array();
	$rs = $db->query("select id, equipment_name from equipment");
	if($rs){ while($r = $rs->fetch_assoc()){
		if(trim($r['equipment_name']) !== '') $m[$r['id']] = $r['equipment_name'];
	} }
	return $m;
}

function ccsSubitemMap($db){
	$m = array();
	$tbl = CCS_SUBITEM_TABLE;

	// Read the table's own columns rather than assuming a label name. Every
	// failure here is tolerated rather than fatal: if sub_item is absent or
	// shaped unexpectedly, reports simply show equipment without subitems.
	$cols = array();
	$cr = @$db->query("show columns from ".$tbl);
	if(!$cr) return $m;
	while($c = $cr->fetch_assoc()){ $cols[strtolower($c['Field'])] = $c['Field']; }
	if(!count($cols)) return $m;

	$idCol = isset($cols[strtolower(CCS_SUBITEM_ID)]) ? $cols[strtolower(CCS_SUBITEM_ID)] : reset($cols);

	$nameCol = '';
	if(CCS_SUBITEM_NAME !== '' && isset($cols[strtolower(CCS_SUBITEM_NAME)])){
		$nameCol = $cols[strtolower(CCS_SUBITEM_NAME)];
	}
	else {
		foreach(array('sub_item_name','subitem_name','sub_item','item_name','equipment_name','problem','name','description') as $cand){
			if(isset($cols[$cand])){ $nameCol = $cols[$cand]; break; }
		}
	}
	// Last resort: the first column that is not the id.
	if($nameCol === ''){
		foreach($cols as $lc => $orig){ if($orig !== $idCol){ $nameCol = $orig; break; } }
	}
	if($nameCol === '') return $m;

	$rs = @$db->query("select `".$idCol."` as id, `".$nameCol."` as nm from ".$tbl);
	if($rs){ while($r = $rs->fetch_assoc()){
		if($r['nm'] !== null && trim($r['nm']) !== '') $m[$r['id']] = $r['nm'];
	} }
	return $m;
}


/* ---------------------------------------------------------------------------
   ccsIncidentEquipment()

   Display-side resolver for incident LOGS, where a row now needs to show
   several equipment items instead of one. Batched: one query for the whole
   page, not one per row.

   Returns [ incident_id => array( array('equipt_id'=>, 'equipment'=>,
                                         'subitem_id'=>, 'subitem'=>), ... ) ]

   Incidents with no link at all are simply absent from the result, so callers
   should treat a missing key as "unspecified" rather than assuming a row.
   --------------------------------------------------------------------------- */
function ccsIncidentEquipment($db, $incidentIds, $equipMap, $subMap = array(), $schema = ''){
	$out = array();
	if(!count($incidentIds)) return $out;

	$ids = array();
	foreach($incidentIds as $id){ $ids[] = "'".$db->real_escape_string($id)."'"; }
	$in = implode(',', $ids);

	$sql = "select el.incident_id, el.equipt_id, el.subitem_id
	          from ".ccsEquipmentLinkSql($schema)." el
	         where el.incident_id in (".$in.")";
	$rs = $db->query($sql);
	if(!$rs) return $out;

	// incident_equipt has no unique constraint on (incident_id, equipt_id), so
	// the same pair can repeat. The counting helpers use count(distinct ...)
	// and are immune, but this list would render the same equipment twice.
	$seen = array();
	while($r = $rs->fetch_assoc()){
		$iid = $r['incident_id'];
		$sig = $iid."|".$r['equipt_id']."|".$r['subitem_id'];
		if(isset($seen[$sig])) continue;
		$seen[$sig] = true;
		if(!isset($out[$iid])) $out[$iid] = array();
		$out[$iid][] = array(
			'equipt_id'  => $r['equipt_id'],
			'equipment'  => isset($equipMap[$r['equipt_id']]) ? $equipMap[$r['equipt_id']] : '',
			'subitem_id' => $r['subitem_id'],
			'subitem'    => ($r['subitem_id'] !== null && isset($subMap[$r['subitem_id']])) ? $subMap[$r['subitem_id']] : ''
		);
	}
	return $out;
}


/* ---------------------------------------------------------------------------
   ccsEquipmentLabel()

   Renders one incident's equipment list for a table cell. Keeps every report
   consistent about how multiple items and missing values look, so the display
   convention lives here rather than being re-invented per page.
   --------------------------------------------------------------------------- */
function ccsEquipmentLabel($items, $showSubitems = true){
	if(!is_array($items) || !count($items)){
		return "<span style='opacity:.55;'>Unspecified</span>";
	}
	$parts = array();
	foreach($items as $it){
		$name = ($it['equipment'] !== '') ? $it['equipment'] : 'Unknown';
		$txt  = htmlspecialchars($name);
		if($showSubitems && $it['subitem'] !== ''){
			$txt .= " <span style='opacity:.7;font-size:11px;'>&rsaquo; ".htmlspecialchars($it['subitem'])."</span>";
		}
		$parts[] = $txt;
	}
	// Several items on one incident is now normal, so show them all and make
	// the count visible rather than truncating to the first.
	$joined = implode("<br>", $parts);
	if(count($parts) > 1){
		$joined .= " <span style='opacity:.6;font-size:11px;'>(".count($parts)." items)</span>";
	}
	return $joined;
}