CREATE TABLE ko_event_publishers (
	`id` INT(11) NOT NULL auto_increment PRIMARY KEY,
	`title` VARCHAR(255),
	`recipients` VARCHAR(255),
	`recipient_groups` VARCHAR(255),
	`frequency` SMALLINT DEFAULT 0,
	`weekday` SMALLINT DEFAULT 0,
	`sendtime` TIME,
	`offset` VARCHAR(50),
	`daterange` VARCHAR(50),
	`categories` VARCHAR(50),
	`lastsent` DATETIME,
	`groupbyday` SMALLINT DEFAULT 0,
	`template` MEDIUMTEXT,
	`reply_to` VARCHAR(255),
);