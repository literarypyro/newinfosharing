<?php
session_start();
?>
<?php
require_once("phpexcel/Classes/PHPExcel.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
/* excel_functions.php no longer needed -- this version talks to PHPExcel directly */
?>
<?php
require_once("db_config.php"); /* centralized credentials -- see db_config.php */
?>
<?php
ini_set("date.timezone","Asia/Manila"); /* was Asia/Kuala_Lumpur -- confirmed incorrect */
?>
<?php
/* ============================================================================
   generate_nis.php -- NIS / Control Center Daily Report printout (2026-07 rewrite)

   The old version copied a BLANK "new INCIDENT format.xls" (only column widths)
   and then rebuilt the whole form cell-by-cell in code. This version is
   template-driven: forms/NIS_template.xlsx is a real, fully formatted Excel
   form (letterhead, boxed headers, legend, signature block, print setup).
   The script only:
     1. copies the template into printout/,
     2. finds the {{REPORT_DATE}} / {{DATA}} / {{PRINTED_BY}} placeholder cells,
     3. inserts one styled row per incident below the prototype row
        (PHPExcel's insertNewRowBefore copies the prototype row's style), and
     4. fills in the values.
   Everything visual now lives in the template -- edit it in Excel, not here.
   Page breaks / repeating headers are handled by Excel print titles (rows 1:9
   repeat at the top of every printed page), replacing the old
   page_counter==24/28 re-drawing logic.

   2026-07-24 (b): the first cut of this rewrite shipped an .xls template that
   Excel refused with "We found a problem with some content". Cause: the
   template was built as .xlsx and converted to .xls by LibreOffice, and that
   conversion emitted TWO Print_Titles defined names (a built-in one plus a
   literal "_xlnm.Print_Titles", both -> $A$1:$IV$9). Excel treats a duplicated
   built-in name as a corrupt workbook. The template is now a native .xlsx with
   exactly one correctly-scoped defined name, and this script reads and writes
   Excel2007. Do NOT re-introduce an .xls template produced by conversion.

   2026-07-24 (c): "Template is missing the {{DATA}} marker row." The template
   was fine -- the scan was wrong. openpyxl 3.1 writes text as OOXML INLINE
   strings (t="inlineStr", no sharedStrings.xml part), and PHPExcel returns a
   PHPExcel_RichText OBJECT for those, so the scan's is_string() test skipped
   every text cell. Fixed two ways: nis_cell_text() flattens string / RichText /
   null, and the template is now written with shared strings, which is what
   Excel itself emits. The scan also falls back to NIS_FALLBACK_* row constants
   instead of dying, and NIS_DEBUG dumps what the reader actually returned.

   2026-07-24 (d): output opened as a completely black sheet -- not a dark-mode
   display issue, the file really was painted black. openpyxl writes the default
   empty fill as <patternFill/> with no patternType attribute (and its API gives
   no way to force one); PHPExcel cannot read the missing attribute and on save
   emitted <patternFill patternType="solid"> with fgColor FF000000, referenced
   by EVERY cellXf. Fixed in the template (patternType="none" written
   explicitly) and again here via the FILL_NONE pass below.

   Deliberate changes from the old file (2026-07-24):
   a. Range end time fixed: 23:23:59 -> 23:59:59 (incident_summary.php already
      uses 23:59:59, so printout and on-screen list now agree).
   b. Engineering RA / AP / IRO are reset per incident. The old loop kept the
      previous incident's values when engineering_mod had no row, so those
      columns could show the WRONG incident's signatories.
   c. GET dates are validated + escaped before entering SQL.
   d. All incident_cars rows are listed (old code capped the car list at 4).
   e. Missing template / failed copy now stops with a clear message instead of
      a blank or broken file (see the blank-page postmortem on the stats pages).
   f. Date/time cells are written as explicit text -- no more leading apostrophe
      showing up in the Date column.

   Unchanged on purpose:
   - GET interface (ccdr, ccdr2) -- incident_summary.php needs no changes.
   - Column D "Type of Action" stays blank. The old code wrote $action_type,
     which was never defined anywhere, so the column has always printed empty
     and is filled in by hand. Wire it up here if a source field ever exists.
   - Time format H:iA and date format m/d/Y, as on the summary page.
   ============================================================================ */

/* Output format. Leave false (.xlsx) unless something downstream truly needs
   legacy .xls -- see the NIS_OUTPUT_XLS note in the save block below. */
define("NIS_OUTPUT_XLS", false);

/* Geometry of forms/NIS_template.xlsx, used only if a placeholder marker is
   missing (e.g. someone edited the template and cleared a cell). */
define("NIS_FALLBACK_DATA_ROW", 10);
define("NIS_FALLBACK_DATE_ROW", 7);
define("NIS_FALLBACK_PRINTED_ROW", 30);

/* Set true to print what the placeholder scan actually read from the template. */
define("NIS_DEBUG", false);

if(isset($_GET['ccdr'])){
	$ccdr_date=$_GET['ccdr'];
	$ccdr_date2=isset($_GET['ccdr2'])?$_GET['ccdr2']:"";

	/* change (c): the old file interpolated $_GET straight into SQL */
	if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$ccdr_date)){
		die("Invalid date.");
	}
	if($ccdr_date2!="" && !preg_match('/^\d{4}-\d{2}-\d{2}$/',$ccdr_date2)){
		$ccdr_date2="";
	}

	$templateFile="forms/NIS_template.xlsx";
	if(!file_exists($templateFile)){
		/* change (e): fail loudly, not with a broken workbook */
		die("Template not found: ".htmlspecialchars($templateFile).". Upload NIS_template.xlsx into forms/.");
	}
	/* The Excel2007 reader and writer both need the zip extension. Without it
	   PHPExcel fails inside the writer and can leave a truncated file, which
	   Excel then reports as damaged -- check it up front instead. */
	if(!class_exists('ZipArchive')){
		die("PHP's zip extension is not enabled, which PHPExcel needs for .xlsx. Enable php_zip, or set NIS_OUTPUT_XLS to true.");
	}

	$dateSlip=date("Y-m-d His");
	$ext=NIS_OUTPUT_XLS?".xls":".xlsx";
	$newFilename="printout/NIS_".$dateSlip.$ext;

	$workFile="printout/NIS_".$dateSlip.".xlsx";
	if(!@copy($templateFile,$workFile)){
		die("Could not write into printout/ -- check that the folder exists and is writable.");
	}

	$reader=PHPExcel_IOFactory::createReader('Excel2007');
	$excel=$reader->load($workFile);
	$sheet=$excel->getSheetByName("NIS");
	if($sheet===null){ $sheet=$excel->getActiveSheet(); }
	$excel->setActiveSheetIndex($excel->getIndex($sheet));

	/* ---- locate the placeholder cells (survives future template edits) ----
	   nis_cell_text() exists because getValue() does NOT always return a string.
	   A cell stored as an OOXML inline string (t="inlineStr") comes back from
	   PHPExcel as a PHPExcel_RichText OBJECT, and a plain is_string() test then
	   silently skips every text cell in the template -- which is what made this
	   scan report "missing the {{DATA}} marker row" even though the marker was
	   in A10. openpyxl 3.1 writes inline strings by default; Excel writes shared
	   strings. Handle both rather than depending on which tool last saved the
	   template. */
	$cols=array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O');

	$dataRow=0; $dateRow=0; $dateCol="A"; $printedRow=0; $printedCol="A";
	$highestRow=$sheet->getHighestRow();
	$scanned=array(); /* kept for the diagnostic below */
	for($r=1;$r<=$highestRow;$r++){
		foreach($cols as $c){
			if(!$sheet->cellExists($c.$r)){ continue; }
			$v=nis_cell_text($sheet->getCell($c.$r)->getValue());
			if($v===""){ continue; }
			if(strpos($v,"{{DATA}}")!==false){ $dataRow=$r; }
			else if(strpos($v,"{{REPORT_DATE}}")!==false){ $dateRow=$r; $dateCol=$c; }
			else if(strpos($v,"{{PRINTED_BY}}")!==false){ $printedRow=$r; $printedCol=$c; }
			if($c=="A" && count($scanned)<40){ $scanned[]=$r.": ".$v; }
		}
	}

	/* Fall back to the template's known geometry rather than dying outright, so
	   a future template edit that drops a marker degrades instead of failing.
	   Set these to match the template if you move things around. */
	if($dataRow==0){ $dataRow=NIS_FALLBACK_DATA_ROW; $usedFallback=true; } else { $usedFallback=false; }
	if($dateRow==0){ $dateRow=NIS_FALLBACK_DATE_ROW; $dateCol="A"; }
	if($printedRow==0){ $printedRow=NIS_FALLBACK_PRINTED_ROW; $printedCol="A"; }

	if($usedFallback && NIS_DEBUG){
		/* Turn NIS_DEBUG on to see exactly what the reader gave back. */
		echo "<pre>Placeholder scan found no {{DATA}} marker; using row ".$dataRow.".\n";
		echo "Sheet: ".$sheet->getTitle()."  highestRow: ".$highestRow."  highestColumn: ".$sheet->getHighestColumn()."\n";
		echo "Column A as read:\n".htmlspecialchars(implode("\n",$scanned))."</pre>";
	}

	$db=iss_db('transport');

	$ccdr_esc=$db->real_escape_string($ccdr_date);
	if($ccdr_date2==""){
		$dClause=" like '".$ccdr_esc."%%' ";
	}
	else {
		/* change (a): was 23:23:59 -- incidents between 23:24 and midnight
		   were silently dropped from ranged printouts */
		$dClause=" between '".$ccdr_esc." 00:00:00' and '".$db->real_escape_string($ccdr_date2)." 23:59:59' ";
	}

	/* item #3 fix retained: sort by the numeric prefix of incident_no.
	   The original position('' in ...) variant sorted by the first character
	   only -- kept here for the record:
	$sql="select * from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$dClause." order by substring(incident_no,1,position('' in incident_no))*1 ";
	*/
	$sql="select * from incident_report inner join incident_description on incident_report.id=incident_description.incident_id where incident_date ".$dClause." order by substring(incident_no,1,position(' ' in incident_no)-1)*1 ";
	$rs=$db->query($sql);
	if(!$rs){ die("Incident query failed."); }
	$nm=$rs->num_rows;

	/* ---- report date line ---- */
	if($dateRow>0){
		if($ccdr_date2==""){
			$reportDate=date("F d, Y (l)",strtotime($ccdr_date));
		}
		else {
			$reportDate=date("F d, Y",strtotime($ccdr_date))." to ".date("F d, Y",strtotime($ccdr_date2));
		}
		$sheet->setCellValue($dateCol.$dateRow,$reportDate);
	}

	/* ---- grow the table: one styled row per incident ----
	   insertNewRowBefore(dataRow+1, n-1) pushes the legend/signature block
	   down and gives every inserted row the prototype row's fonts, borders
	   and alignment. Nothing below the table needs re-drawing. */
	if($nm>1){
		$sheet->insertNewRowBefore($dataRow+1,$nm-1);
		if($printedRow>$dataRow){ $printedRow+=($nm-1); }
	}
	if($nm==0){
		$sheet->setCellValue("A".$dataRow,""); /* clear the {{DATA}} marker */
	}

	/*
	   Preserved from the old generate_nis.php -- these blocks fetched shift
	   personnel and signatories, but every line that wrote them into the sheet
	   was already commented out there. Re-enable if the form should pull the
	   Verified By name from the signatories table instead of the one baked
	   into the template ("OLIVER S. CASILI"):

	$db2=iss_db('user_transport');
	$psql="select * from duty_personnel where personnel_date like '".$ccdr_esc."%%' and shift='3'";
	$prs=$db2->query($psql);
	if($prs && $prs->num_rows>0){
		$prow=$prs->fetch_assoc();
		$recording=getTrainDriver($db,$prow['recording']);
		$clerk=getTrainDriver($db,$prow['clerk']);
		$duty_manager=getTrainDriver($db,$prow['duty_manager']);
	}

	$signatoryRS=$db2->query("select * from signatories order by signatory_date DESC");
	if($signatoryRS && $signatoryRS->num_rows>0){
		$signatoryRow=$signatoryRS->fetch_assoc();
		if(strtotime($ccdr_date)<strtotime($signatoryRow['signatory_date'])){
			$sig2RS=$db2->query("select * from signatories where signatory_date>'".$ccdr_esc."' order by signatory_date asc");
			if($sig2RS && $sig2RS->num_rows>0){ $signatoryRow=$sig2RS->fetch_assoc(); }
		}
		$chief=$signatoryRow['chief_transport'];
		// then locate the name cell and overwrite it:
		// for($r=$dataRow;$r<=$sheet->getHighestRow();$r++){
		// 	if($sheet->cellExists("L".$r) && $sheet->getCell("L".$r)->getValue()=="OLIVER S. CASILI"){
		// 		$sheet->setCellValue("L".$r,$chief); break;
		// 	}
		// }
	}
	*/

	/* ---- data rows ---- */
	for($i=0;$i<$nm;$i++){
		$row=$rs->fetch_assoc();
		$excelRow=$dataRow+$i;

		/* cars -- change (d): list every incident_cars row, not just four */
		$carList=array();
		$carRS=$db->query("select * from incident_cars where incident_id='".$db->real_escape_string($row['incident_id'])."'");
		if($carRS){
			while($carRow=$carRS->fetch_assoc()){
				$carList[]=$carRow['car_no'];
			}
		}
		$carClause=implode(", ",$carList);
		$newCarClause=$carClause; /* raw list for the Car No. column */

		/* train composition (ICN column) */
		$train_compo="";
		$compoRS=$db->query("select * from train_incident_report inner join train_availability on train_availability.id=train_incident_report.train_ava_id where train_incident_report.incident_id='".$db->real_escape_string($row['incident_id'])."'");
		if($compoRS && $compoRS->num_rows>0){
			$compoRow=$compoRS->fetch_assoc();
			$train_compo=$compoRow['index_no']." (".$compoRow['car_a'].", ".$compoRow['car_b'].", ".$compoRow['car_c'].")";
		}

		$incident_type=$row['incident_type'];
		$hourStamp=date("H:iA",strtotime($row['incident_date']));
		$incident_no=$row['incident_no'];
		$description="";
		$location=$row['location'];
		$reported_by=$row['reported_by'];
		$received_by=$row['received_by'];

		if($incident_type=="rolling"){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }

			$direction=$row['direction'];
			/* item #7 fix: SB/NB never got spelled out the way D/ML do below, so the
			   description used to end with a raw code ("...  SB," / "...  NB,"). S means
			   "station" (not a direction), so it is blanked rather than spelled out --
			   matching how edit_ccdr.php already treats S on its own display. Confirmed
			   2026-07: S=Station, SB=Southbound, NB=Northbound, D=Depot, ML=Mainline. */
			if($direction=="S"){ $location="Stn. ".$location; $direction=""; }
			else if($direction=="SB"){ $location="Stn. ".$location; $direction="Southbound"; }
			else if($direction=="NB"){ $location="Stn. ".$location; $direction="Northbound"; }
			else if($direction=="D"){ $direction="Depot"; }
			else if($direction=="ML"){ $direction="Mainline"; }
			/* item #7 fix: omit the "  " separator when $direction was blanked (S), so the
			   description reads "Stn. Ayala, ..." instead of "Stn. Ayala  , ...". */
			$description="Index #".$row['index_no'].",".$carClause.$location.($direction!=""?"  ".$direction:"").", ".$row['description'].", Reported By ".$reported_by.", ";
		}
		else if(($incident_type=="unload")||($incident_type=='nload')){
			if($carClause==""){ } else { $carClause=" Car(s) ".$carClause.", "; }

			$description="Index #".$row['index_no'].",".$carClause.", ".$row['description'].", Reported By ".$reported_by.", ";
		}
		else {
			$description.=$row['description'].", Reported By ".$reported_by;
		}

		$action_maintenance=$row['action_maintenance'];

		/* CTC: person who received the report */
		$ctc="";
		$CCRErs=$db->query("select * from train_driver where id='".$db->real_escape_string($received_by)."'");
		if($CCRErs && $CCRErs->num_rows>0){
			$CCRErow=$CCRErs->fetch_assoc();
			$ctc=substr($CCRErow['firstName'],0,1).". ".$CCRErow['lastName'];
		}

		/* change (b): reset per incident -- the old loop leaked the previous
		   incident's engineering signatories into rows that had none */
		$recommend_eng="";
		$approving_eng="";
		$iro_eng="";
		$engRS=$db->query("select * from engineering_mod where incident_id='".$db->real_escape_string($row['incident_id'])."'");
		if($engRS && $engRS->num_rows>0){
			$engRow=$engRS->fetch_assoc();
			$recommend_eng=$engRow['recommend_approval'];
			$approving_eng=$engRow['approving_officer'];
			$iro_eng=$engRow['iro'];
		}

		/* one incident = one row; the template row supplies all formatting */
		$sheet->setCellValueExplicit("A".$excelRow,$incident_no,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("B".$excelRow,date("m/d/Y",strtotime($row['incident_date'])),PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("C".$excelRow,$hourStamp,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("D".$excelRow,"",PHPExcel_Cell_DataType::TYPE_STRING); /* Type of Action -- see header note */
		$sheet->setCellValueExplicit("E".$excelRow,$newCarClause,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("F".$excelRow,$train_compo,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("G".$excelRow,$reported_by,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("H".$excelRow,$ctc,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("I".$excelRow,$row['recommending_approval'],PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("J".$excelRow,$row['approving_person'],PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("K".$excelRow,$description,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("L".$excelRow,$action_maintenance,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("M".$excelRow,$recommend_eng,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("N".$excelRow,$approving_eng,PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet->setCellValueExplicit("O".$excelRow,$iro_eng,PHPExcel_Cell_DataType::TYPE_STRING);

		/* wrapped descriptions grow the row when the file is opened */
		$sheet->getRowDimension($excelRow)->setRowHeight(-1);
	}

	/* ---- neutralise any fill PHPExcel may have invented ----
	   openpyxl writes the default "no fill" as <patternFill/> with NO
	   patternType attribute. PHPExcel cannot interpret that and, on save,
	   emits a SOLID fill using its internal default start colour (black) and
	   points every cellXf at it -- producing a workbook in which every cell is
	   painted black, so black text is invisible. The template now declares
	   patternType="none" explicitly, which fixes it at the source; this is the
	   belt-and-braces pass so an older or hand-edited template can't
	   reintroduce it. Remove only if you ever want real cell shading. */
	$sheet->getStyle("A1:O".$sheet->getHighestRow())
	      ->getFill()->setFillType(PHPExcel_Style_Fill::FILL_NONE);

	/* ---- footer line ---- */
	if($printedRow>0){
		$user_fullname=isset($_SESSION['user_fullname'])?$_SESSION['user_fullname']:"";
		$sheet->setCellValue($printedCol.$printedRow,"Printed: ".$user_fullname);
	}

	/* ---- print setup ----
	   Orientation / paper / fit are re-asserted harmlessly. Print titles are
	   NOT: rows 1:9 already repeat because the TEMPLATE carries the
	   _xlnm.Print_Titles defined name, and calling
	   setRowsToRepeatAtTopByStartAndEnd() on a workbook that already has that
	   name is the second way this file ends up corrupt -- it can leave a
	   duplicate built-in name behind, which is exactly what Excel rejects.
	   Uncomment ONLY if headers stop repeating, then confirm the result opens
	   cleanly in Excel, not just in LibreOffice:
	     $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1,9);
	*/
	$sheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
	$sheet->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_LEGAL);
	$sheet->getPageSetup()->setFitToWidth(1);
	$sheet->getPageSetup()->setFitToHeight(0);

	/* ---- save ---- */
	if(NIS_OUTPUT_XLS){
		/* Legacy path. PHPExcel's Excel5 writer plus print-title defined names
		   is the combination that produced the corrupt file, so the defined
		   name is dropped here -- headers will NOT repeat across printed pages
		   in .xls output. Prefer .xlsx. */
		foreach($excel->getWorksheetIterator() as $ws5){
			$ws5->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(0,0);
		}
		$writer=PHPExcel_IOFactory::createWriter($excel,'Excel5');
		$writer->save($newFilename);
		@unlink($workFile); /* remove the working .xlsx copy */
	}
	else {
		$writer=PHPExcel_IOFactory::createWriter($excel,'Excel2007');
		$writer->save($newFilename);
		if($newFilename!==$workFile){ @unlink($workFile); }
	}

	echo "NIS has been generated (".$nm." incident".($nm==1?"":"s")."). Press right click and Save As: <a href='".htmlspecialchars($newFilename)."'>Here</a>";
}

function nis_cell_text($v){
	/* PHPExcel hands back a plain string for a shared-string cell, but a
	   PHPExcel_RichText object for an inline-string cell, and null for an empty
	   one. Flatten all three to a string. */
	if($v===null){ return ""; }
	if(is_object($v)){
		if(method_exists($v,'getPlainText')){ return $v->getPlainText(); }
		return (string)$v;
	}
	if(is_string($v)){ return $v; }
	return (string)$v;
}

function getTrainDriver($db,$td_id){
	/* used by the preserved duty_personnel block above when re-enabled */
	$sql="select * from train_driver where id='".$db->real_escape_string($td_id)."' limit 1";
	$rs=$db->query($sql);
	if(!$rs || $rs->num_rows==0){ return ""; }
	$row=$rs->fetch_assoc();
	$name=$row['firstName']." ".substr($row['midName'],0,1).". ".$row['lastName'];
	return $name;
}
?>