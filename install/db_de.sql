ALTER TABLE `ko_leute` CHANGE `anrede` `anrede` ENUM('', 'Herr', 'Frau');



UPDATE `ko_settings` SET `value` = 'Dienstplan <DIENSTNAME> f�r <MONAT> <JAHR>\n\nBitte Daten f�r folgende Anl�sse bis sp�testens <DEADLINE> erfassen resp. korrigieren:\n<ANLASSLISTE>\n\nVielen Dank und liebe Gr�sse' WHERE  CONVERT(`ko_settings`.`key` USING utf8) = 'dp_mailtext_1';

UPDATE `ko_settings` SET `value` = 'Hier bekommst du den Dienstplan f�r den n�chsten Monat.\nVielen Dank f�r deinen Einsatz!\n\nLiebe Gr�sse' WHERE  CONVERT(`ko_settings`.`key` USING utf8) = 'dp_mailtext_2';

UPDATE `ko_settings` SET `value` = 'Dienstplan' WHERE  CONVERT(`ko_settings`.`key` USING utf8) = 'dp_titel';

UPDATE `ko_settings` SET `value` = 'Guten Tag\n\n<ABSENDER> (<ABSENDEREMAIL>) hat Ihnen eine Datei geschickt und folgendes dazu geschrieben:\n---\n<TEXT>\n---\nDie Datei finden Sie unter: <LINK>' WHERE  CONVERT(`ko_settings`.`key` USING utf8) = 'fileshare_mailtext';


UPDATE `ko_settings` SET `value` = '41' WHERE `key` = 'sms_country_code';



UPDATE `ko_tapes_printlayout` SET `name` = 'Liste' WHERE  `ko_tapes_printlayout`.`id` = '1';
UPDATE `ko_tapes_printlayout` SET `name` = '6x2 Tapes' WHERE  `ko_tapes_printlayout`.`id` = '2';
UPDATE `ko_tapes_printlayout` SET `name` = '6x1 Tapes' WHERE  `ko_tapes_printlayout`.`id` = '3';



UPDATE `ko_pdf_layout` SET `name` = 'Layout 1' WHERE `ko_pdf_layout`.`id` = '1';
