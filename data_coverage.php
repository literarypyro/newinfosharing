<?php
/* ===========================================================================
   data_coverage.php — one place that knows which periods the console actually
   has records for.

   WHY THIS EXISTS
   ---------------
   A missing period and a quiet period are the same thing on a chart: a bar of
   height zero, or a table cell reading 0. They mean opposite things. Someone
   reading a printed report cannot tell "no failures were recorded because none
   happened" from "no failures were recorded because the database lost them",
   and the first reading is the flattering one, so it is the one that gets made.

   The console currently has one such hole: records trail off in May 2021 and
   resume in 2025, after is_transport was corrupted and the restore from
   is_transport_old only reached 2019.

   Rather than hardcode those dates into each report — where the next
   maintainer will not find them, and where the next gap will need the same
   edit again — the periods live in a data_coverage table and every report
   consults it through this file.

   FAILS SOFT: if the table does not exist, ccsLoadCoverage() returns an empty
   array and every month is treated as covered, i.e. exactly the behaviour the
   reports had before. Nothing breaks if the table has not been created yet.
   =========================================================================== */


/* ---------------------------------------------------------------------------
   ccsLoadCoverage() — call ONCE per page, alongside the other lookup maps.
   Returns a list of periods, each with start/end as YYYY-MM-DD, a status of
   'covered' | 'missing' | 'partial', and a note.
   --------------------------------------------------------------------------- */
function ccsLoadCoverage($db){
	$out = array();
	$rs = @$db->query("select period_start, period_end, status, note from data_coverage order by period_start");
	if(!$rs) return $out;   // table absent -> everything reads as covered
	while($r = $rs->fetch_assoc()){
		$out[] = array(
			'start'  => $r['period_start'],
			'end'    => $r['period_end'],
			'status' => strtolower($r['status']),
			'note'   => $r['note']
		);
	}
	return $out;
}


/* ---------------------------------------------------------------------------
   ccsMonthStatus() — status for one month, given as 'YYYY-MM'.

   A month counts as affected when it OVERLAPS a period at all, not only when
   it sits wholly inside one. A gap starting on 15 June still means June's
   figure is incomplete, and showing an incomplete month as if it were whole is
   the same error in a smaller form.
   --------------------------------------------------------------------------- */
function ccsMonthStatus($coverage, $ym){
	if(!is_array($coverage) || !count($coverage)) return 'covered';

	$mStart = $ym."-01";
	$mEnd   = date("Y-m-t", strtotime($mStart));

	$worst = 'covered';
	foreach($coverage as $p){
		if($p['status'] === 'covered') continue;
		if($mStart <= $p['end'] && $mEnd >= $p['start']){
			// 'missing' outranks 'partial' — report the more serious of any
			// overlapping periods rather than the first one matched.
			if($p['status'] === 'missing') return 'missing';
			$worst = 'partial';
		}
	}
	return $worst;
}

function ccsMonthIsMissing($coverage, $ym){ return ccsMonthStatus($coverage, $ym) === 'missing'; }


/* ---------------------------------------------------------------------------
   ccsCoverageCell() — how an uncovered month renders in a table.

   Grey ground and an em dash rather than a digit, so it cannot be mistaken for
   a count at a glance or misread as a zero in a printout. The title attribute
   carries the reason for anyone hovering on screen.
   --------------------------------------------------------------------------- */
function ccsCoverageCell($status, $note = ''){
	if($status === 'missing'){
		$t = $note !== '' ? $note : 'No data recorded for this period';
		return "<td align=center class='ccs-nodata' title=\"".htmlspecialchars($t, ENT_QUOTES)."\">&mdash;</td>";
	}
	return '';   // caller renders the normal cell
}


/* ---------------------------------------------------------------------------
   ccsCoverageCss() — the shared styling for those cells, on screen and in
   print. Kept here so the four reports cannot drift apart on it.
   --------------------------------------------------------------------------- */
function ccsCoverageCss(){
	return "
	.ccs-nodata{
		background:repeating-linear-gradient(45deg,#EFEDE6,#EFEDE6 4px,#E3E0D6 4px,#E3E0D6 8px);
		color:#8A857A; font-weight:normal;
	}
	";
}


/* ---------------------------------------------------------------------------
   ccsCoverageNote() — one sentence naming the gaps, for the legend on screen
   and the note under the figures in print.

   Returns '' when there is nothing to declare, so callers can print it
   unconditionally without producing an empty sentence.
   --------------------------------------------------------------------------- */
function ccsCoverageNote($coverage, $prefix = 'Data coverage: '){
	if(!is_array($coverage) || !count($coverage)) return '';
	$parts = array();
	foreach($coverage as $p){
		if($p['status'] === 'covered') continue;
		$label = ($p['status'] === 'missing') ? 'no records' : 'incomplete records';
		$parts[] = date("M Y", strtotime($p['start']))." to ".date("M Y", strtotime($p['end']))." — ".$label
		         . ($p['note'] !== '' && $p['note'] !== null ? " (".$p['note'].")" : "");
	}
	if(!count($parts)) return '';
	return $prefix.implode("; ", $parts).". Figures exclude these periods; blank cells mean no data, not zero failures.";
}


/* ---------------------------------------------------------------------------
   ccsCoverageForJs() — the uncovered months as a flat list of 'YYYY-MM'
   strings, for chart code.

   Charts should plot null rather than 0 for these, so no bar is drawn at all.
   A zero-height bar and a missing bar look identical, which is exactly the
   confusion this file exists to prevent — so the charts also carry a footnote
   naming the gap.
   --------------------------------------------------------------------------- */
function ccsUncoveredMonths($coverage, $fromYm, $toYm){
	$out = array();
	if(!is_array($coverage) || !count($coverage)) return $out;
	$cur = $fromYm."-01";
	$stop = $toYm."-01";
	$guard = 0;
	while($cur <= $stop && $guard++ < 600){
		$ym = substr($cur,0,7);
		if(ccsMonthStatus($coverage, $ym) !== 'covered') $out[] = $ym;
		$cur = date("Y-m-01", strtotime($cur." +1 month"));
	}
	return $out;
}