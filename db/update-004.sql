CREATE TABLE  `config` (
 `key` VARCHAR( 255 ) NOT NULL ,
 `type` ENUM(  'scalar',  'array' ) NOT NULL ,
 `value` TEXT,
 `active` TINYINT( 4 ) NOT NULL DEFAULT  '1',
PRIMARY KEY (  `key` )
) ENGINE = MYISAM DEFAULT CHARSET = cp1251;

ALTER TABLE  `movies` ADD  `trailer_localized` TINYINT NULL AFTER  `trailer`;
ALTER TABLE  `movies` CHANGE  `translation`  `translation` TEXT NULL;
ALTER TABLE  `files` MODIFY COLUMN  `md5_hash` VARCHAR( 32 ) NOT NULL DEFAULT  '' AFTER  `tth_hash`;

ALTER TABLE  `movies` DROP INDEX  `iCreateDate`, ADD INDEX  `iCreateDate` (`updated_at`);