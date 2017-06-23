CREATE TABLE `ko_event` (
  `my_vmfds_events_categories` varchar(255) default NULL,
  `my_vmfds_events_picture` varchar(255) default NULL,
  `my_vmfds_events_registration_link` varchar(255) default NULL,
  `my_vmfds_events_tickets_link` varchar(255) default NULL,
  `my_vmfds_events_prices` mediumtext default NULL,
  `my_vmfds_events_longdescription` mediumtext default NULL,
  `my_vmfds_events_teaser_start` date default NULL,
  `my_vmfds_events_fblink` varchar(255) default NULL,
  `my_vmfds_events_location` varchar(255) default NULL,
  `my_vmfds_events_nav_address` varchar(255) default NULL,
  `my_vmfds_events_nav_address_lat` DECIMAL(10, 8)NOT NULL DEFAULT '0',
  `my_vmfds_events_nav_address_lon` DECIMAL(10, 8) NOT NULL DEFAULT '0',
  `my_vmfds_events_itinerary` mediumtext default NULL,
  `my_vmfds_events_gi_image` varchar(255) default NULL,
  `my_vmfds_events_announcement_image` varchar(255) default NULL,
  `my_vmfds_events_announcement_start` date default NULL,
  `my_vmfds_events_announcement_title` varchar(255) default NULL,
  `my_vmfds_events_announcement_note` varchar(255) default NULL,
  `my_vmfds_events_has_reservations` varchar(255) default NULL,
  `my_vmfds_events_reservation_notes` mediumtext default NULL,
  `my_vmfds_events_max_reservations` varchar(255) default NULL,
  `my_vmfds_events_is_soldout` varchar(255) default NULL,
  `my_vmfds_events_mitteilungsblatt_title` varchar(255) default NULL,
  `my_vmfds_events_mitteilungsblatt_text` mediumtext default NULL,
  `my_vmfds_events_series_title` varchar(255) default NULL,
  `my_vmfds_events_reference` varchar(255) default NULL,
)
CREATE TABLE `ko_eventgruppen` (
  `my_vmfds_events_skip_listtitle` tinyint(4) default 0 NOT NULL,
  `my_vmfds_events_picture` varchar(255) default NULL,
)
CREATE TABLE `ko_event_mod` (
  `my_vmfds_events_categories` varchar(255) default NULL,
  `my_vmfds_events_picture` varchar(255) default NULL,
)

CREATE TABLE `ko_event_categories` (
   `id` INT(11) NOT NULL auto_increment PRIMARY KEY,
   `title` VARCHAR(255)
)
