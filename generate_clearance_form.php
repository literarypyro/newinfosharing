<?php
session_start();
require_once("phpexcel/Classes/PHPExcel.php");
require_once("phpexcel/Classes/PHPExcel/IOFactory.php");
require("excel_functions.php");
ini_set("date.timezone","Asia/Kuala_Lumpur");

/* =========================================================================
   generate_clearance_form.php

   Rebuilt on the same principle as generate_tar.php:

     1. GUARD every step that can fail silently. The old file called copy()
        and loadExistingWorkbook() without checking either, so a missing
        template or a non-writable printout/ produced a bare 500 with nothing
        on the page -- which is what made this so hard to diagnose before.
     2. Derive the row geometry from CONSTANTS that mirror the template,
        rather than re-deriving it with arithmetic inside the loop.
     3. Fill the header of EVERY page, not just page 1.
     4. When the data outruns the template's built-in pages, CLONE a page
        block rather than writing past the end of the formatting.

   ---- What the template actually is -------------------------------------
   forms/ClearanceForm.xls, sheet "CLEAR", carries EIGHT pre-built pages.
   Measured off the cell borders rather than assumed:

       page 1 : data rows  12 -  29   (18 entries)
       page 2 : data rows  42 -  58   (17)
       page 3 : data rows  71 -  87   (17)
       page 4 : data rows 100 - 116   (17)
       page 5 : data rows 129 - 145   (17)
       page 6 : data rows 158 - 174   (17)
       page 7 : data rows 187 - 203   (17)
       page 8 : data rows 216 - 232   (17)

   Total built-in capacity: 137 entries, NOT 144.

   ---- Three bugs this replaces -------------------------------------------
   (a) OFF BY ONE FROM PAGE 2 ONWARD. The old loop did $rowCount+=12 after
       every 18th entry, giving data tops 12, 41, 70, 99 ... but the template's
       are 12, 42, 71, 100 ... Each column-header band is TWO merged rows
       (10-11, 40-41, ...), so every page after the first wrote its first
       entry INTO the merged header -- where a merge hides all but the
       top-left cell, so that entry vanished -- and printed the remaining
       entries one row above their ruled boxes.

   (b) 18 ENTRIES PER PAGE ASSUMED THROUGHOUT. Only page 1 holds 18; pages
       2-8 hold 17. The old arithmetic drifted further out of register with
       every page.

   (c) PAGES 2+ PRINTED "0.0" FOR DAY AND DATE. Only B8/N8 -- page 1's header
       -- was ever filled. The other seven pages kept the 0.0 baked into the
       template.

   ---- Overflow ------------------------------------------------------------
   Past 137 entries the old code kept incrementing into rows 233+, which carry
   no borders, no merges and no column headers: the spill you described. Now
   a page block is cloned instead, so entry 138 starts a properly formatted
   page 9.
   ========================================================================= */

/* ---- geometry of forms/ClearanceForm.xls, sheet "CLEAR" ---- */
$CF_DATA_TOP  = array(12, 42, 71, 100, 129, 158, 187, 216);
$CF_PAGE_ROWS = array(18, 17,  17,  17,  17,  17,  17,  17);

/* Page 1 sits above the rest by one row (its header block is 30 rows, the
   others 29). Pages 2-8 are uniform, so page 7 is the block cloned for any
   page beyond the eighth. */
/* @rowheight -- Page 2's block, NOT page 7's. Pages 4-8 of the template were
   saved without explicit row heights, so they fall back to the sheet default;
   cloning one of those would have propagated the thin rows onto every new
   page. Page 2 carries the correct 24.9pt data rows. */
define("CF_CLONE_FIRST",  31);   /* first row of page 2's block            */
define("CF_CLONE_ROWS",   29);   /* rows in one page block, pages 2 onward */
define("CF_CLONE_DATATOP_OFFSET", 11); /* 42 - 31                          */
define("CF_CLONE_CAPACITY", 17);

/* Where the day / date go on each page. Page 1's are absolute; later pages
   are the same cells offset by that page's block start. */
define("CF_HDR_DAY_ROW",  8);
define("CF_HDR_DAY_COL",  "B");
define("CF_HDR_DATE_COL", "N");
define("CF_HDR_PAGE1_TO_PAGE2", 30);   /* row 8 -> row 38                  */

define("CF_DEBUG", false);

if(!isset($_GET['clearance_date']) || $_GET['clearance_date']===''){
	die("No clearance date given.");
}
$clearance_date = $_GET['clearance_date'];
if(strtotime($clearance_date)===false){
	die("Invalid clearance date.");
}

/* ---- guards, in the order things actually fail ---- */
$templateFile = "forms/ClearanceForm.xls";
if(!file_exists($templateFile)){
	die("Template not found: ".htmlspecialchars($templateFile));
}
if(!is_dir("printout")){
	die("The printout/ folder does not exist.");
}
if(!is_writable("printout")){
	die("printout/ is not writable -- check the folder permissions.");
}

$dateSlip    = date("Y-m-d His");
$newFilename = "printout/Clearance_".$dateSlip.".xls";

/* The old file ignored copy()'s return value. When printout/ was not
   writable the copy failed silently, and PHPExcel then tried to open a file
   that had never been created -- a 500 one step removed from its cause. */
if(!@copy($templateFile,$newFilename)){
	die("Could not copy the template into printout/.");
}

$excel = loadExistingWorkbook($newFilename);
if(!$excel){
	die("PHPExcel could not open the copied template.");
}

/* The sheet is named "CLEAR" in the file while the old code passed
   "CLEARANCE". createWorksheet(...,"openActive") opens the active sheet
   regardless, which is why that mismatch never surfaced -- but it means the
   name argument was decorative. Kept for signature compatibility. */
$workSheetName = "CLEARANCE";
$ExWs  = createWorksheet($excel,$workSheetName,"openActive");
$sheet = $excel->getActiveSheet();

$db = new mysqli("localhost","psssilva","!D40nkC2azXg$","is_transport");
if($db->connect_error){
	die("Database connection failed.");
}

$esc = $db->real_escape_string($clearance_date);
$sql = "select * from clearance where date like '".$esc."%%' order by clearance_no";
$rs  = $db->query($sql);
if(!$rs){
	die("Clearance query failed.");
}
$nm = $rs->num_rows;

/* ---- how many pages this day needs ---- */
$builtIn = 0;
foreach($CF_PAGE_ROWS as $cap){ $builtIn += $cap; }

$pagesNeeded = 0;
$left = $nm;
foreach($CF_PAGE_ROWS as $cap){
	if($left <= 0) break;
	$pagesNeeded++;
	$left -= $cap;
}
if($left > 0){
	$pagesNeeded += (int)ceil($left / CF_CLONE_CAPACITY);
}
if($pagesNeeded < 1){ $pagesNeeded = 1; }

/* ---- clone extra pages when the day outruns the template ----
   Same shape as generate_tar.php: append the block, copy values and style
   indexes cell by cell, carry the row heights, then re-apply the merges at
   the new offset. Merges are read from the source block rather than
   hard-coded, so a template edit does not silently break this. */
$cols = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V');

if($pagesNeeded > count($CF_DATA_TOP)){
	$extra = $pagesNeeded - count($CF_DATA_TOP);

	$blockMerges = array();
	foreach($sheet->getMergeCells() as $mergeRange){
		list($mStart,$mEnd) = explode(":", $mergeRange);
		$sC = preg_replace('/[0-9]/','',$mStart); $sR = (int)preg_replace('/[^0-9]/','',$mStart);
		$eC = preg_replace('/[0-9]/','',$mEnd);   $eR = (int)preg_replace('/[^0-9]/','',$mEnd);
		if($sR >= CF_CLONE_FIRST && $eR < CF_CLONE_FIRST + CF_CLONE_ROWS){
			$blockMerges[] = array($sC, $sR - CF_CLONE_FIRST, $eC, $eR - CF_CLONE_FIRST);
		}
	}

	$appendAt = $CF_DATA_TOP[count($CF_DATA_TOP)-1] + $CF_PAGE_ROWS[count($CF_PAGE_ROWS)-1];

	for($p=0;$p<$extra;$p++){
		$base = $appendAt + ($p * CF_CLONE_ROWS);
		for($r=0;$r<CF_CLONE_ROWS;$r++){
			$srcRow = CF_CLONE_FIRST + $r;
			$dstRow = $base + $r;

			$h = $sheet->getRowDimension($srcRow)->getRowHeight();
			if($h != -1){ $sheet->getRowDimension($dstRow)->setRowHeight($h); }

			foreach($cols as $c){
				$src = $c.$srcRow; $dst = $c.$dstRow;
				if(!$sheet->cellExists($src)){ continue; }
				$srcCell = $sheet->getCell($src);
				$sheet->getCell($dst)->setXfIndex($srcCell->getXfIndex());
				$v = $srcCell->getValue();
				/* A blank cell mistyped as numeric reads back as 0; letting
				   that through would stamp a 0 into every cloned page. */
				if($v !== null && $v !== "" && !($v === 0 || $v === "0")){
					if(is_numeric($v) && !is_string($v)){ $sheet->setCellValue($dst,$v); }
					else { $sheet->setCellValueExplicit($dst,(string)$v,PHPExcel_Cell_DataType::TYPE_STRING); }
				}
			}
		}
		foreach($blockMerges as $m){
			$sheet->mergeCells($m[0].($base + $m[1]).":".$m[2].($base + $m[3]));
		}

		$CF_DATA_TOP[]  = $base + CF_CLONE_DATATOP_OFFSET;
		$CF_PAGE_ROWS[] = CF_CLONE_CAPACITY;
	}
}

/* ---- normalise the data-row heights -------------------------------------
   @rowheight -- THE TEMPLATE IS INCONSISTENT, and this is why the rows get
   thinner partway through the printout:

       pages 1-3  data rows carry an explicit height of 24.9pt
       pages 4-8  carry no height at all, so they fall back to the sheet
                  default of 13.8pt -- a little over half

   Nothing in the generator caused it and nothing in the generator noticed:
   the rows were always thin from page 4 on, in the template itself. Rather
   than require the .xls to be re-saved, every data row is given page 1's
   height here, which also covers any page cloned above. Fixing the template
   too is worth doing, but this makes the output correct either way. */
$dataRowHeight = $sheet->getRowDimension($CF_DATA_TOP[0])->getRowHeight();
if($dataRowHeight == -1){ $dataRowHeight = 24.9; }   /* page 1's measured height */

for($p=0;$p<$pagesNeeded;$p++){
	$top = $CF_DATA_TOP[$p];
	$cap = $CF_PAGE_ROWS[$p];
	for($r=$top; $r<$top+$cap; $r++){
		$sheet->getRowDimension($r)->setRowHeight($dataRowHeight);
	}
}

/* ---- fill the day / date on EVERY page ---- */
$dayText  = date("l",       strtotime($clearance_date));
$dateText = date("F d, Y",  strtotime($clearance_date));

for($p=0;$p<$pagesNeeded;$p++){
	if($p == 0){ $hdrRow = CF_HDR_DAY_ROW; }
	else       { $hdrRow = CF_HDR_DAY_ROW + CF_HDR_PAGE1_TO_PAGE2 + (($p-1) * CF_CLONE_ROWS); }

	addContent(setRange(CF_HDR_DAY_COL.$hdrRow,  "C".$hdrRow), $excel, $dayText,  "true", $ExWs);
	addContent(setRange(CF_HDR_DATE_COL.$hdrRow, "O".$hdrRow), $excel, $dateText, "true", $ExWs);
}

/* ---- fill the entries ---- */
$page   = 0;
$inPage = 0;
$rowCount = $CF_DATA_TOP[0];

for($i=0;$i<$nm;$i++){
	$row = $rs->fetch_assoc();

	/* Page advance is driven by the per-page capacity table, so pages 2-8
	   correctly take 17 rather than the 18 the old loop assumed. */
	if($inPage >= $CF_PAGE_ROWS[$page]){
		$page++;
		if(!isset($CF_DATA_TOP[$page])){ break; }   /* cannot happen: pages were sized above */
		$inPage   = 0;
		$rowCount = $CF_DATA_TOP[$page];
	}

	$received_by = $row['received_by'];
	$rs2 = $db->query("select * from train_driver where id='".$db->real_escape_string($received_by)."' limit 1");
	if($rs2 && $rs2->num_rows > 0){
		$row2 = $rs2->fetch_assoc();
		$received_by = $row2['position']." ".substr($row2['firstName'],0,1).". ".$row2['lastName'];
	}

	$login  = ($row['login']  == "0000-00-00 00:00:00" || $row['login']  === null) ? "" : date("H:i",strtotime($row['login']));
	$logout = ($row['logout'] == "0000-00-00 00:00:00" || $row['logout'] === null) ? "" : date("H:i",strtotime($row['logout']));

	addContent(setRange("A".$rowCount,"A".$rowCount),$excel,$row['clearance_no'],"true",$ExWs);
	addContent(setRange("B".$rowCount,"B".$rowCount),$excel,$row['location'],"true",$ExWs);
	addContent(setRange("C".$rowCount,"F".$rowCount),$excel,$row['activity'],"true",$ExWs);
	addContent(setRange("G".$rowCount,"H".$rowCount),$excel,$row['person'],"true",$ExWs);
	addContent(setRange("I".$rowCount,"J".$rowCount),$excel,$row['position']." / ".$row['company'],"true",$ExWs);
	addContent(setRange("K".$rowCount,"L".$rowCount),$excel,$received_by,"true",$ExWs);
	addContent(setRange("M".$rowCount,"M".$rowCount),$excel,$login,"true",$ExWs);
	addContent(setRange("N".$rowCount,"N".$rowCount),$excel,$logout,"true",$ExWs);
	addContent(setRange("O".$rowCount,"P".$rowCount),$excel,$row['control_no'],"true",$ExWs);

	$rowCount++;
	$inPage++;
}

if(CF_DEBUG){
	echo "<pre>entries=".$nm." pages=".$pagesNeeded." built-in capacity=".$builtIn;
	echo "\ndata tops: ".implode(", ",$CF_DATA_TOP);
	echo "\ncapacity : ".implode(", ",$CF_PAGE_ROWS)."</pre>";
}

save($ExWb,$excel,$newFilename);

echo "<br>";
echo "Clearance Form has been generated (".$nm." entr".($nm==1?"y":"ies").
     " across ".$pagesNeeded." page".($pagesNeeded==1?"":"s").
     ").  Right-click and Save As: <a href='".htmlspecialchars($newFilename)."'>Here</a>";