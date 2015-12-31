<?php
/**
*   Provides automatic installation of the Profile plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2015 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.4
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** @global string $_DB_dbms */
global $_DB_dbms;
/** @global array $PRF_sampledata */
global $PRF_sampledata;

/** Include profile functions */
require_once $_CONF['path'].'plugins/profile/functions.inc';
/** Include database definitions */
require_once $_CONF['path'].'plugins/profile/sql/'. $_DB_dbms. '_install.php';

/** Plugin installation options
*   @global array $INSTALL_plugin['profile']
*/
$INSTALL_plugin['profile'] = array(
    'installer' => array('type' => 'installer', 
            'version' => '1', 
            'mode' => 'install'),

    'plugin' => array('type' => 'plugin', 
            'name'      => $_PRF_CONF['pi_name'],
            'ver'       => $_PRF_CONF['pi_version'], 
            'gl_ver'    => $_PRF_CONF['gl_version'],
            'url'       => $_PRF_CONF['pi_url'], 
            'display'   => $_PRF_CONF['pi_display_name']),

    array('type' => 'table', 
            'table'     => $_TABLES['profile_def'], 
            'sql'       => $_SQL['profile_def']),

    array('type' => 'table', 
            'table'     => $_TABLES['profile_data'], 
            'sql'       => $_SQL['profile_data']),

    array('type' => 'table', 
            'table'     => $_TABLES['profile_lists'], 
            'sql'       => $_SQL['profile_lists']),

    array('type' => 'group', 
            'group' => 'profile Admin', 
            'desc' => 'Users in this group can administer the Custom Profile plugin',
            'variable' => 'admin_group_id', 
            'admin' => true,
            'addroot' => true),

    array('type' => 'feature', 
            'feature' => 'profile.admin', 
            'desc' => 'Profile Administration access',
            'variable' => 'admin_feature_id'),

    array('type' => 'feature', 
            'feature' => 'profile.export', 
            'desc' => 'Access to export user lists',
            'variable' => 'export_feature_id'),

    array('type' => 'feature', 
            'feature'   => 'profile.viewall',
            'desc' => 'Access to view ALL user profiles, overriding user preferences.',
            'variable' => 'viewall_feature_id'),

    array('type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'admin_feature_id',
            'log' => 'Adding Admin feature to the admin group'),

    array('type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'export_feature_id',
            'log' => 'Adding Export feature to the admin group'),

    array('type' => 'mapping', 
            'group' => 'admin_group_id', 
            'feature' => 'viewall_feature_id',
            'log' => 'Adding View-All List feature to the admin group'),
);

/**
* Puts the datastructures for this plugin into the glFusion database
* Note: Corresponding uninstall routine is in functions.inc
* @return   boolean True if successful False otherwise
*/
function plugin_install_profile()
{
    global $INSTALL_plugin, $_PRF_CONF;

    COM_errorLog("Attempting to install the {$_PRF_CONF['pi_display_name']} plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$_PRF_CONF['pi_name']]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


/**
*   Perform post-installation functions
*/
function plugin_postinstall_profile()
{
    global $PRF_sampledata, $_TABLES, $_PRF_CONF;

    // Create data records for each user and populate first and
    // last name fields
    USES_lglib_class_nameparser();
    $sql = "SELECT uid, username, fullname FROM {$_TABLES['users']}";
    $res = DB_query($sql);
    while ($A = DB_fetchArray($res, false)) {
        // use username if fullname is empty
        // fullname may be empty, but username can't be
        if ($A['fullname'] == '') $A['fullname'] = $A['username'];
        $fname = DB_escapeString(NameParser::F($A['fullname']));
        $lname = DB_escapeString(NameParser::L($A['fullname']));
        $uid = (int)$A['uid'];
        $value_arr[] = "($uid, '$fname', '$lname')";
    }
    $values = implode(',', $value_arr);
    $sql = "INSERT INTO {$_TABLES['profile_data']}
                (puid, sys_fname, sys_lname)
            VALUES $values";
    DB_query($sql);
 
    // Install sample data
    if (is_array($PRF_sampledata)) {
        foreach ($PRF_sampledata as $sql) {
            DB_query($sql);
        }

        // Set the correct default Group ID
        $gid = (int)DB_getItem($_TABLES['groups'], 'grp_id', 
                "grp_name='{$_PRF_CONF['pi_name']} Admin'");
        if ($gid > 0) {
            DB_query("UPDATE {$_TABLES['profile_def']}
                SET group_id=$gid");
        }
    }

}



/**
*   Loads the configuration records for the Online Config Manager
*
*   @return   boolean     true = proceed with install, false = an error occured
*/
function plugin_load_configuration_profile()
{
    global $_CONF, $_PRF_CONF, $_TABLES;

    require_once PRF_PI_PATH . 'install_defaults.php';

    // Get the admin group ID that was saved previously.
    $group_id = (int)DB_getItem($_TABLES['groups'], 'grp_id', 
            "grp_name='{$_PRF_CONF['pi_name']} Admin'");

    return plugin_initconfig_profile($group_id);
}


?>
