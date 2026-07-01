<?php
/* Tmenu.php retired as a standalone file -- its nav markup was a
   byte-for-byte duplicate of Tmenu_2.php (same menu links, same DB
   loops), which is exactly the kind of drift that caused the earlier
   left:40px vs left:0 / width:55% vs width:100% mismatches noted in
   trans_menu_2.php's own history. Rather than maintain two copies of
   the same menu that can silently diverge again, every page that
   requires("Tmenu.php") -- train_availability.php, incident_report.php,
   and any others -- now transparently gets Tmenu_2.php instead. Any
   future nav/header fix only needs to touch Tmenu_2.php once. */
require("Tmenu_2.php");