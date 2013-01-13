ALTER TABLE `movies` 
    CHANGE `type_of_movie` `type_of_movie` VARCHAR( 255 ) NOT NULL DEFAULT '',
    CHANGE `present_by` `present_by` VARCHAR( 255 ) NOT NULL DEFAULT '',
    CHANGE `group` `group` VARCHAR( 255 ) NOT NULL DEFAULT '';

ALTER TABLE `persones` CHANGE `info` `info` TEXT NULL;

ALTER TABLE `files` 
    CHANGE `md5_hash` `md5_hash` VARCHAR( 32 ) NOT NULL DEFAULT '',
    CHANGE `tth_hash` `tth_hash` VARCHAR( 40 ) NOT NULL DEFAULT '';

ALTER TABLE `movies` CHANGE `description` `description` TEXT NULL;

REPLACE INTO `registry` SET `key`='db_version', `value`=3;