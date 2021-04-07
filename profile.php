<?php
/**
 * Table definitions and other static config variables.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2021 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.3
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/**
 * Global array of table names from glFusion.
 * @global array $_TABLES
 */
global $_TABLES;

/**
 * Global table name prefix.
 * @global string $_DB_table_prefix
 */
global $_DB_table_prefix;

$_TABLES['profile_def']     = $_DB_table_prefix . 'profile_def';
$_TABLES['profile_data']    = $_DB_table_prefix . 'profile_data';
$_TABLES['profile_lists']   = $_DB_table_prefix . 'profile_lists';

// Deprecated tables, listed here only so they can be dropped:
$_TABLES['profile_values']  = $_DB_table_prefix . 'profile_values';

/**
 * Global configuration array.
 * @global array $_PRF_CONF
 */
global $_PRF_CONF;
$_PRF_CONF['pi_name']           = 'profile';
$_PRF_CONF['pi_version']        = '1.2.3.1';
$_PRF_CONF['gl_version']        = '1.7.8';
$_PRF_CONF['pi_url']            = 'http://www.leegarner.com';
$_PRF_CONF['pi_display_name']   = 'Custom Profile';

$_PRF_CONF['def_dt_format'] = '%Y-%M-%d %H:%m';
$_PRF_CONF['def_dt_input_format'] = 1;
$_PRF_CONF['pi_dir']    = $_PRF_CONF['pi_name'];

?>
