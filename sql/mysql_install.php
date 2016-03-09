<?php
/**
*   Table definitions for the Profile plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2015 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.4
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

/** @global array $_TABLES */
global $_TABLES;

$_SQL['profile_def'] = "CREATE TABLE {$_TABLES['profile_def']} (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `orderby` int(11) unsigned DEFAULT NULL,
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `required` tinyint(1) NOT NULL DEFAULT '1',
  `user_reg` tinyint(1) NOT NULL DEFAULT '0',
  `show_in_profile` tinyint(1) NOT NULL DEFAULT '1',
  `prompt` varchar(80) COLLATE utf8_unicode_ci DEFAULT '',
  `options` text COLLATE utf8_unicode_ci,
  `group_id` mediumint(8) unsigned NOT NULL DEFAULT '1',
  `perm_owner` tinyint(1) unsigned NOT NULL DEFAULT '3',
  `perm_group` tinyint(1) unsigned NOT NULL DEFAULT '3',
  `perm_members` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `perm_anon` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `sys` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `plugin` varchar(255) COLLATE utf8_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_orderby` (`orderby`)
)";

$_SQL['profile_data'] = "CREATE TABLE {$_TABLES['profile_data']} (
  `puid` int(11) unsigned NOT NULL,
  `sys_membertype` varchar(255) default NULL,
  `sys_expires` date default NULL,
  `sys_directory` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `sys_parent` mediumint(8) unsigned NOT NULL DEFAULT '0',
  `sys_fname` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sys_lname` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY  (`puid`),
  KEY `lname` (`sys_lname`,`sys_fname`)
)";

$_SQL['profile_lists'] = "CREATE TABLE `{$_TABLES['profile_lists']}` (
  `listid` varchar(40) NOT NULL,
  `orderby` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(40) DEFAULT NULL,
  `fields` text,
  `group_id` int(11) unsigned DEFAULT '13',
  `incl_grp` int(11) unsigned DEFAULT '2',
  `incl_user_stat` varchar(64) NOT NULL DEFAULT 'a:4:{i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;}',
  `incl_exp_stat` tinyint(2) not null default 7,
  PRIMARY KEY (`listid`)
)";

global $PRF_sampledata;
$PRF_sampledata = array(
"INSERT INTO {$_TABLES['profile_def']}
        (orderby, name, type, enabled, required, user_reg,
        prompt, options, sys, perm_owner)
     VALUES
        (10, 'sys_membertype', 'text', 1, 0, 0,
            '{$LANG_PROFILE['membertype']}', 
            'a:4:{s:7:\"default\";s:0:\"\";s:4:\"size\";i:40;s:9:\"maxlength\";i:255;s:7:\"autogen\";i:0;}', 1, 1),
        (20, 'sys_expires', 'date', 1, 0, 0, '{$LANG_PROFILE['expiration']}', 
            'a:5:{s:7:\"default\";s:0:\"\";s:8:\"showtime\";i:0;s:10:\"timeformat\";s:2:\"12\";s:6:\"format\";N;s:12:\"input_format\";s:1:\"1\";}', 1, 1),
        (30, 'sys_directory', 'checkbox', 1, 0, 1, 
            '{$LANG_PROFILE['list_member_dir']}', 
            'a:1:{s:7:\"default\";i:1;}', 1, 1),
        (40, 'sys_parent', 'account', 0, 0, 0, '{$LANG_PROFILE['parent_uid']}', 
            'a:1:{s:7:\"default\";s:1:\"0\";}', 1, 1),
        (42, 'sys_fname', 'text', 1, 0, 0, '{$LANG_PROFILE['fname']}', 
            'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 1, 0),
        (44, 'sys_lname', 'text', 1, 0, 0, '{$LANG_PROFILE['lname']}', 
            'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 1, 0),
        (50, 'prf_address1', 'text', 1, 1, 1, 'Address Line 1', 
            'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 0, 3),
        (60, 'prf_address2', 'text', 1, 0, 1, 'Address Line 2', 
            'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 0, 3),
        (70, 'prf_city', 'text', 1, 1, 1, 'City', 
            'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 0, 3),
        (80, 'prf_state', 'text', 1, 1, 1, 'State', 
            'a:2:{s:4:\"size\";i:2;s:9:\"maxlength\";i:2;}', 0, 3),
        (90, 'prf_zip', 'text', 1, 1, 1, 'Zip Code', 
            'a:2:{s:4:\"size\";i:10;s:9:\"maxlength\";i:10;}', 0, 3),
        (95, 'prf_phone', 'text', 1, 0, 0, 'Phone Number', 
            'a:5:{s:7:\"default\";s:0:\"\";s:9:\"help_text\";s:23:\"Enter your phone number\";s:4:\"size\";i:40;s:9:\"maxlength\";i:255;s:7:\"autogen\";i:0;}', 0, 3),
        (100, 'prf_favcolor', 'radio', 1, 1, 1, 'Favorite color', 
            'a:2:{s:6:\"values\";a:3:{i:1;s:3:\"Red\";i:2;s:4:\"Blue\";i:3;s:6:\"Yellow\";}s:7:\"default\";s:1:\"2\";}', 0, 3),
        (110, 'prf_birthdate', 'date', 1, 1, 1, 'BirthDate', 
            'a:3:{s:8:\"showtime\";i:0;s:10:\"timeformat\";s:1:\"0\";s:6:\"format\";s:8:\"%m/%d/%Y\";}', 0, 3)",
"ALTER TABLE {$_TABLES['profile_data']}
    ADD `prf_address1` varchar(255) default NULL,
    ADD `prf_address2` varchar(255) default NULL,
    ADD `prf_city` varchar(255) default NULL,
    ADD `prf_state` varchar(255) default NULL,
    ADD `prf_zip` varchar(255) default NULL,
    ADD `prf_phone` varchar(255) default NULL,
    ADD `prf_favcolor` varchar(255) default NULL,
    ADD `prf_birthdate` datetime default NULL",
);

?>
