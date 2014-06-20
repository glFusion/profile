<?php
/**
*   Configuration defaults for the Custom Profile plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.2
*   @since      0.0.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
 *  Profile default settings
 *
 *  Initial Installation Defaults used when loading the online configuration
 *  records. These settings are only used during the initial installation
 *  and not referenced any more once the plugin is installed
 *
 *  @global array $_PRF_DEFAULT
 *
 */
global $_PRF_DEFAULT, $_PRF_CONF;
$_PRF_DEFAULT = array();

// Set the default permissions
$_PRF_DEFAULT['group_id'] = 1;      // last resort
$_PRF_DEFAULT['default_permissions'] =  array (3, 3, 0, 0);

// Show empty values (e.g. in user profile)?
$_PRF_DEFAULT['showemptyvals'] = 1;

// Period after dues are due that members will be in arrears, not expired
$_PRF_DEFAULT['grace_expired'] = 30;

$_PRF_DEFAULT['date_format'] = 1;

$_PRF_DEFAULT['list_incl_admin'] = 0;

/**
 *  Initialize Profile plugin configuration
 *
 *  Creates the database entries for the configuation if they don't already
 *  exist. Initial values will be taken from $_PRF_CONF if available (e.g. from
 *  an old config.php), uses $_PRF_DEFAULT otherwise.
 *
 *  @param  integer $group_id   Group ID to use as the plugin's admin group
 *  @return boolean             true: success; false: an error occurred
 */
function plugin_initconfig_profile($group_id = 0)
{
    global $_CONF, $_PRF_CONF, $_PRF_DEFAULT;

    if (is_array($_PRF_CONF) && (count($_PRF_CONF) > 1)) {
        $_PRF_DEFAULT = array_merge($_PRF_DEFAULT, $_PRF_CONF);
    }

    // Use configured default if a valid group ID wasn't presented
    if ($group_id == 0)
        $group_id = $_PRF_DEFAULT['group_id'];

    $c = config::get_instance();
    if (!$c->group_exists($_PRF_CONF['pi_name'])) {

        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true, 
                $_PRF_CONF['pi_name']);

        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true, 
                $_PRF_CONF['pi_name']);
        $c->add('showemptyvals', $_PRF_DEFAULT['showemptyvals'], 
                'select', 0, 0, 3, 10, true, $_PRF_CONF['pi_name']);
        $c->add('grace_expired', $_PRF_DEFAULT['grace_expired'],
                'text', 0, 0, 0, 30, true, $_PRF_CONF['pi_name']);
        $c->add('date_format', $_PRF_DEFAULT['date_format'],
                'select', 0, 0, 13, 30, true, $_PRF_CONF['pi_name']);

        $c->add('fs_lists', NULL, 'fieldset', 0, 2, NULL, 0, true, 
                $_PRF_CONF['pi_name']);
        $c->add('list_incl_admin', $_PRF_DEFAULT['list_incl_admin'], 
                'select', 0, 2, 3, 10, true, $_PRF_CONF['pi_name']);

        $c->add('fs_permissions', NULL, 'fieldset', 0, 4, NULL, 0, true, 
                $_PRF_CONF['pi_name']);
        $c->add('defgroup', $group_id,
                'select', 0, 4, 0, 90, true, $_PRF_CONF['pi_name']);
        $c->add('default_permissions', $_PRF_DEFAULT['default_permissions'],
                '@select', 0, 4, 12, 100, true, $_PRF_CONF['pi_name']);
    }

    return true;
}

?>
