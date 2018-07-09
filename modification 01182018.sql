ALTER TABLE  `incident_report` ADD  `action_type` TEXT NULL AFTER  `incident_date` ,
ADD  `recommending_approval` TEXT NULL AFTER  `action_type` ,
ADD  `approving_person` TEXT NULL AFTER  `recommending_approval`