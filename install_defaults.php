<?php
/**
 * Configuration defaults for the Custom Profile plugin
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2020 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @since       v0.0.2
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined('GVERSION')) {
    die('This file can not be used on its own!');
}

/** @var global config data */
global $profileConfigData;
$profileConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'showemptyvals',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 3,
        'sort' => 10,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'grace_expired',
        'default_value' => '30',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'date_format',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 13,
        'sort' => 30,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'notify_change',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 14,
        'sort' => 40,
        'set' => true,
        'group' => 'profile',
    ),

    array(
        'name' => 'fs_lists',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'date_format',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 13,
        'sort' => 30,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'list_incl_admin',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 3,
        'sort' => 10,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'list_allow_pdf',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 13,
        'sort' => 20,
        'set' => true,
        'group' => 'profile',
    ),


    array(
        'name' => 'fs_permissions',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'defgroup',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 20,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'profile',
    ),
    array(
        'name' => 'default_permissions',
        'default_value' => array(3, 3, 0, 0),
        'type' => '@select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 12,
        'sort' => 30,
        'set' => true,
        'group' => 'profile',
    ),
);


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
    global $profileConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('profile')) {
        USES_lib_install();
        foreach ($profileConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    } else {
        COM_errorLog('initconfig error: Profile config group already exists');
    }
    return true;
}
