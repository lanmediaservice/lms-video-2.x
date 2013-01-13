ALTER TABLE `persones` ADD `Photos` TEXT NULL DEFAULT '' AFTER `Images`;
ALTER TABLE `films` ADD `Rank` FLOAT NOT NULL DEFAULT '0' AFTER `ImdbRatingDetail` , ADD INDEX (`Rank`);
ALTER TABLE `persones` ADD `Rank` FLOAT NOT NULL DEFAULT '0' AFTER `OzonUrl` , ADD INDEX (`Rank`);

CREATE TABLE `bestsellers` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `films` text NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

CREATE TABLE `search_trigrams` (
  `trigram` char(3) NOT NULL,
  `type` enum('film','person') NOT NULL,
  `id` int(11) NOT NULL,
  KEY `trigram` (`trigram`,`type`),
  KEY `type` (`type`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

CREATE TABLE `suggestion` (
  `suggestion_id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL,
  `type` enum('film','person') NOT NULL,
  `id` int(11) NOT NULL,
  PRIMARY KEY (`suggestion_id`),
  UNIQUE KEY `word_2` (`word`,`type`,`id`),
  KEY `word` (`word`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;

CREATE TABLE `suggestion_cache` (
  `query` varchar(255) NOT NULL,
  `result` text NOT NULL,
  PRIMARY KEY (`query`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;


ALTER TABLE `filmgenres` ADD INDEX (`FilmID`);
ALTER TABLE `filmgenres` ADD INDEX (`GenreID`);
ALTER TABLE `filmcountries` ADD INDEX (`FilmID`);
ALTER TABLE `filmcountries` ADD INDEX (`CountryID`);
ALTER TABLE `films` ADD INDEX (`Hide`);
ALTER TABLE `roles` ADD INDEX (`Role`);
ALTER TABLE `roles` ADD INDEX (`SortOrder`);
