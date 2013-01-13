CREATE TABLE `bestsellers` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `movies` text NOT NULL,
  `rank` int(11) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `bookmarks` (
  `bookmark_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `movie_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bookmark_id`),
  UNIQUE KEY `user_id` (`user_id`,`movie_id`),
  KEY `UserID` (`user_id`),
  KEY `EntityID` (`movie_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `to_user_id` int(11) DEFAULT '0',
  `text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ip` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`comment_id`),
  KEY `UserID` (`user_id`),
  KEY `ToUserID` (`to_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `config` (
  `key` varchar(255) NOT NULL,
  `type` enum('scalar','array') NOT NULL,
  `value` text,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `countries` (
  `country_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`country_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `files` (
  `file_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `path` varchar(255) NOT NULL,
  `is_dir` tinyint(4) NOT NULL DEFAULT '0',
  `size` double NOT NULL DEFAULT '0',
  `metainfo` blob,
  `translation` varchar(255) NOT NULL DEFAULT '',
  `quality` varchar(100) NOT NULL DEFAULT '',
  `frames` blob,
  `tth_hash` varchar(40) NOT NULL DEFAULT '',
  `md5_hash` varchar(32) NOT NULL DEFAULT '',
  `tries` tinyint(4) NOT NULL DEFAULT '0',
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`file_id`),
  KEY `iPath` (`path`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `files_lost` (
  `file_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  PRIMARY KEY (`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `files_tasks` (
  `file_task_id` int(11) NOT NULL AUTO_INCREMENT,
  `from` varchar(255) NOT NULL,
  `to` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `tries` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`file_task_id`),
  KEY `from` (`from`),
  KEY `to` (`to`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `genres` (
  `genre_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`genre_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `hits` (
  `hit_id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(15) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`hit_id`),
  UNIQUE KEY `movie_id` (`movie_id`,`user_id`,`ip`),
  KEY `FilmID` (`movie_id`),
  KEY `UserID` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `incoming` (
  `incoming_id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(255) NOT NULL,
  `level` tinyint(4) NOT NULL DEFAULT '0',
  `is_dir` tinyint(4) NOT NULL,
  `size` double DEFAULT NULL,
  `expanded` tinyint(4) NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `files` longblob,
  `quality` blob,
  `translation` blob,
  `last_query` varchar(255) DEFAULT NULL,
  `search_results` longblob,
  `parsing_url` varchar(255) DEFAULT NULL,
  `parsed_info` blob,
  `info` blob,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  `hidden` tinyint(4) NOT NULL DEFAULT '0',
  `sort` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`incoming_id`),
  UNIQUE KEY `path` (`path`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `languages_proto` (
  `lang` enum('en','ru') NOT NULL,
  `combination` varchar(4) NOT NULL,
  `freq` double NOT NULL,
  PRIMARY KEY (`lang`,`combination`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL,
  `type` varchar(64) NOT NULL,
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `started_at` datetime NOT NULL,
  `ended_at` datetime DEFAULT NULL,
  `message` text,
  `report` longblob,
  PRIMARY KEY (`log_id`),
  KEY `type` (`type`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `movies` (
  `movie_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `international_name` varchar(255) NOT NULL,
  `description` text,
  `year` varchar(18) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `translation` text,
  `quality` varchar(100) NOT NULL DEFAULT '',
  `mpaa` varchar(255) DEFAULT NULL,
  `covers` text NOT NULL,
  `trailer` blob,
  `trailer_localized` tinyint(4) DEFAULT NULL,
  `hidden` tinyint(4) NOT NULL DEFAULT '0',
  `hit` int(11) NOT NULL DEFAULT '0',
  `type_of_movie` varchar(255) NOT NULL DEFAULT '',
  `created_by` int(11) DEFAULT NULL,
  `present_by` varchar(255) NOT NULL DEFAULT '',
  `group` varchar(255) NOT NULL DEFAULT '',
  `rank` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`movie_id`),
  KEY `iName` (`name`),
  KEY `iOriginalName` (`international_name`),
  KEY `iYear` (`year`),
  KEY `iHit` (`hit`),
  KEY `Rank` (`rank`),
  KEY `Hide` (`hidden`),
  KEY `iCreateDate` (`updated_at`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `movies_comments` (
  `movie_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  PRIMARY KEY (`movie_id`,`comment_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `movies_countries` (
  `movie_id` int(11) NOT NULL DEFAULT '0',
  `country_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`movie_id`,`country_id`),
  KEY `FilmID` (`movie_id`),
  KEY `CountryID` (`country_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `movies_files` (
  `movie_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`movie_id`,`file_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `movies_genres` (
  `movie_id` int(11) NOT NULL DEFAULT '0',
  `genre_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`genre_id`,`movie_id`),
  KEY `FilmID` (`movie_id`),
  KEY `GenreID` (`genre_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `movies_users_ratings` (
  `movie_user_rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL DEFAULT '0',
  `user_id` int(11) NOT NULL DEFAULT '0',
  `rating` tinyint(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`movie_user_rating_id`),
  UNIQUE KEY `movie_id` (`movie_id`,`user_id`),
  KEY `FilmID` (`movie_id`),
  KEY `UserID` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `participants` (
  `participant_id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL DEFAULT '0',
  `role_id` int(11) NOT NULL DEFAULT '0',
  `character` varchar(100) DEFAULT NULL,
  `person_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`participant_id`),
  UNIQUE KEY `movie_id_2` (`movie_id`,`role_id`,`person_id`),
  KEY `person_id` (`person_id`),
  KEY `role_id` (`role_id`),
  KEY `movie_id` (`movie_id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `persones` (
  `person_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `international_name` varchar(100) NOT NULL,
  `info` text,
  `photos` text,
  `url` varchar(100) DEFAULT NULL,
  `rank` float NOT NULL DEFAULT '0',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `tries` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`person_id`),
  KEY `Rank` (`rank`),
  KEY `updated_at` (`updated_at`),
  KEY `name` (`name`),
  KEY `international_name` (`international_name`),
  KEY `url` (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL AUTO_INCREMENT,
  `movie_id` int(11) NOT NULL,
  `system` enum('local','imdb','kinopoisk') NOT NULL,
  `system_uid` int(11) DEFAULT NULL,
  `count` int(11) DEFAULT NULL,
  `value` float DEFAULT NULL,
  `details` varbinary(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`rating_id`),
  UNIQUE KEY `movie_id` (`movie_id`,`system`),
  KEY `updated_at` (`updated_at`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `registry` (
  `key` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `name_hyphenated` varchar(100) DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT '5',
  PRIMARY KEY (`role_id`),
  KEY `Role` (`name`),
  KEY `SortOrder` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `search_trigrams` (
  `trigram` char(3) NOT NULL,
  `type` enum('movie','person') NOT NULL,
  `id` int(11) NOT NULL,
  KEY `trigram` (`trigram`,`type`),
  KEY `type` (`type`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `suggestion` (
  `suggestion_id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(255) NOT NULL,
  `type` enum('movie','person') NOT NULL,
  `id` int(11) NOT NULL,
  PRIMARY KEY (`suggestion_id`),
  UNIQUE KEY `word_2` (`word`,`type`,`id`),
  KEY `word` (`word`),
  KEY `type` (`type`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `suggestion_cache` (
  `query` varchar(255) NOT NULL,
  `result` text NOT NULL,
  PRIMARY KEY (`query`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
CREATE TABLE `users` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Login` varchar(32) NOT NULL DEFAULT '',
  `Password` varchar(32) NOT NULL DEFAULT '',
  `Email` varchar(255) NOT NULL DEFAULT '',
  `IP` text NOT NULL,
  `Balans` decimal(9,2) NOT NULL DEFAULT '1.00',
  `UserGroup` tinyint(4) NOT NULL DEFAULT '0',
  `ViewActivity` int(11) NOT NULL DEFAULT '0',
  `PlayActivity` int(11) NOT NULL DEFAULT '0',
  `RegisterDate` datetime DEFAULT NULL,
  `Mode` int(11) NOT NULL DEFAULT '1',
  `ipfw_rule` int(11) NOT NULL DEFAULT '0',
  `Enabled` tinyint(4) NOT NULL DEFAULT '1',
  `Preferences` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `iLogin` (`Login`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251;