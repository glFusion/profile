<?php
/**
 * Provides automatic installation of the Profile plugin.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2009-2021 Lee Garner <lee@leegarner.com>
 * @package    profile
 * @version    v1.2.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

use glFusion\Database\Database;
use glFusion\Log\Log;

/** @global string $_DB_dbms */
global $_DB_dbms;
/** @global array $PRF_sampledata */
global $PRF_sampledata;

/** Include profile functions */
require_once __DIR__ . '/functions.inc';
/** Include database definitions */
require_once __DIR__ . '/sql/'. $_DB_dbms. '_install.php';

/** Plugin installation options
*   @global array $INSTALL_plugin['profile']
*/
$INSTALL_plugin['profile'] = array(
    'installer' => array(
        'type' => 'installer',
        'version' => '1',
        'mode' => 'install',
    ),
    'plugin' => array(
        'type' => 'plugin',
        'name'      => $_PRF_CONF['pi_name'],
        'ver'       => $_PRF_CONF['pi_version'],
        'gl_ver'    => $_PRF_CONF['gl_version'],
        'url'       => $_PRF_CONF['pi_url'],
        'display'   => $_PRF_CONF['pi_display_name'],
    ),
    array(
        'type' => 'table',
        'table'     => $_TABLES['profile_def'],
        'sql'       => $_SQL['profile_def'],
    ),
    array(
        'type' => 'table',
        'table'     => $_TABLES['profile_data'],
        'sql'       => $_SQL['profile_data'],
    ),
    array(
        'type' => 'table',
        'table'     => $_TABLES['profile_lists'],
        'sql'       => $_SQL['profile_lists'],
    ),
    array(
        'type' => 'group',
        'group' => 'profile Admin',
        'desc' => 'Users in this group can administer the Custom Profile plugin',
        'variable' => 'admin_group_id',
        'admin' => true,
        'addroot' => true,
    ),
    array(
        'type' => 'feature',
        'feature' => 'profile.admin',
        'desc' => 'Profile Administration access',
        'variable' => 'admin_feature_id',
    ),
    array(
        'type' => 'feature',
        'feature' => 'profile.export',
        'desc' => 'Access to export user lists',
        'variable' => 'export_feature_id',
    ),
    array(
        'type' => 'feature',
        'feature'   => 'profile.viewall',
        'desc' => 'Access to view ALL user profiles, overriding user preferences.',
        'variable' => 'viewall_feature_id',
    ),
    array(
        'type' => 'feature',
        'feature'   => 'profile.view',
        'desc' => 'Access to view public profile fields for other members.',
        'variable' => 'view_feature_id',
    ),
    array(
        'type' => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'admin_feature_id',
        'log' => 'Adding Admin feature to the admin group',
    ),
    array(
        'type' => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'export_feature_id',
        'log' => 'Adding Export feature to the admin group',
    ),
    array(
        'type' => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'viewall_feature_id',
        'log' => 'Adding View-All List feature to the admin group',
    ),
    array(
        'type' => 'mapping',
        'findgroup' => 'Logged-In Users',
        'feature' => 'view_feature_id',
        'log' => 'Adding View feature to the logged-in user group',
    ),
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

    $db = Database::getInstance();

    // Create data records for each user and populate first and
    // last name fields
    try {
        $stmt = $db->conn->executeQuery(
            "SELECT uid, username, fullname FROM {$_TABLES['users']}"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $stmt = false;
    }
    if ($stmt) {
        while ($A = $stmt->fetchAssociative()) {
            // use username if fullname is empty
            // fullname may be empty, but username can't be
            if ($A['fullname'] == '') {
                $A['fullname'] = $A['username'];
            }
            try {
                $db->conn->insert(
                    $_TABLES['profile_data'],
                    array(
                        'puid' => $uid,
                        'sys_fname' => LGLib\NameParser::F($A['fullname']),
                        'sys_lname' => LGLib\NameParser::L($A['fullname']),
                    ),
                    array(
                        Database::INTEGER,
                        Database::STRING,
                        Database::STRING,
                    )
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }
    }

    // Install sample data
    if (is_array($PRF_sampledata)) {
        foreach ($PRF_sampledata as $sql) {
            try {
                $db->conn->executeStatement($sql);
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }

        // Set the correct default Group ID
        $gid = (int)$db->getItem(
            $_TABLES['groups'],
            'grp_id',
            array('grp_name' => $_PRF_CONF['pi_name'] . ' Admin')
        );
        if ($gid > 0) {
            try {
                $db->conn->executeStatement(
                    "UPDATE {$_TABLES['profile_def']} SET group_id = ?",
                    array($gid),
                    array(Database::INTEGER)
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }
    }

    Profile\Cache::clear();
    if (function_exists('CACHE_clear')) {
        // glFusion 2.00+
        CACHE_clear();
    } else {
        // These are removed in CACHE_clear() starting with glFusion 2.0.0
        CTL_clearCache();
        @unlink(COM_getStyleCacheLocation()[0]);
        @unlink(COM_getJSCacheLocation()[0]);
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

    require_once __DIR__ . '/install_defaults.php';

    // Get the admin group ID that was saved previously.
    $group_id = (int)$db->getItem(
        $_TABLES['groups'],
        'grp_id',
        array('grp_name' => $_PRF_CONF['pi_name'] . ' Admin')
    );
    return plugin_initconfig_profile($group_id);
}
