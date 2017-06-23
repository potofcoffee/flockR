CREATE TABLE `ko_event` (
  `my_vmfds_events_categories` varchar(255) default NULL,
  `my_vmfds_events_picture` varchar(255) default NULL,
)
CREATE TABLE `ko_eventgruppen` (
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
