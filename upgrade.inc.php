<?php
/**
 * Upgrade routines for the Custom Profile plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2022 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// Required to get the config values
global $_CONF, $_PRF_CONF;
use glFusion\Database\Database;
use glFusion\Log\Log;


/**
 * Perform the upgrade starting at the current version.
 * If a version has no upgrade activity, e.g. only a code change,
 * then no upgrade section is required.  The version is bumped in
 * functions.inc.
 *
 * @param   boolean $dvlp   True to ignore errors (development update)
 * @return  integer Error code, 0 for success
 */
function profile_do_upgrade($dvlp = false)
{
    global $_PRF_CONF, $_PLUGIN_INFO, $_TABLES;

    $pi_name = $_PRF_CONF['pi_name'];   // used all over

    if (isset($_PLUGIN_INFO[$_PRF_CONF['pi_name']])) {
        if (is_array($_PLUGIN_INFO[$pi_name])) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[$pi_name]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$pi_name];
        }
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_profile();

    if (!COM_checkVersion($current_ver, '0.0.2')) {
        $current_ver = '0.0.2';
        if (!profile_upgrade_0_0_2()) return false;
    }


    if (!COM_checkVersion($current_ver, '1.0.2')) {
        $current_ver ='1.0.2';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_0_2()) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.0')) {
        $current_ver = '1.1.0';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_1_0()) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.1')) {
        $current_ver = '1.1.1';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_1_1()) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.2')) {
        $current_ver = '1.1.2';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_1_2()) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.3')) {
        $current_ver = '1.1.3';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_1_3($dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.1.4')) {
        $current_ver = '1.1.4';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_1_4($dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.2.0')) {
        $current_ver = '1.2.0';
        COM_errorLog("Updating Profile Plugin to $current_ver");
        if (!profile_upgrade_1_1_4(true)) return false;
        if (!profile_upgrade_1_2_0($dvlp)) return false;
    }

    if (!COM_checkVersion($current_ver, '1.2.1')) {
        $current_ver = '1.2.1';
        $sql = array(
            "DELETE FROM {$_TABLES['profile_data']} WHERE puid = 1",
        );
        if (_PRFtableHasColumn('profile_data', 'sys_membertype')) {
            $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
                CHANGE sys_membertype prf_membertype varchar(128)";
        }
        if (_PRFtableHasColumn('profile_data', 'sys_expires')) {
            $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
                CHANGE sys_expires prf_expires date";
        }
        if (_PRFtableHasColumn('profile_data', 'sys_parent')) {
            $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
                CHANGE sys_parent prf_parent mediumint(8) unsigned not null default 0";
        }
        if (_PRFtableHasColumn('profile_lists', 'incl_exp_stat')) {
            $sql[] = "ALTER TABLE {$_TABLES['profile_lists']} DROP incl_exp_stat";
        }
        // Catch up on creating sys_fname and sys_lname fields
        if (!profile_upgrade_1_1_4(true)) return false;
        if (!profile_do_upgrade_sql('1.2.1', $sql, $dvlp)) return false;
        if (!profile_do_set_version('1.2.1')) return false;
    }

    if (!COM_checkVersion($current_ver, '1.2.8')) {
        $current_ver = '1.2.8';

        $db = Database::getInstance();

        // Multiple date_format items got into the config table
        $count = $db->getCount(
            $_TABLES['conf_values'],
            array('name', 'group_name'),
            array('date_format', $pi_name),
            array(Database::STRING, Database::STRING)
        );
        if ($count > 1) {
            $count--;
            try {
                $db->conn->executeStatement(
                    "DELETE FROM {$_TABLES['conf_values']}
                    WHERE name = 'date_format' AND group_name = '$pi_name'
                    LIMIT $count",
                    array($pi_name, $count),
                    array(Database::STRING, Database::INTEGER)
                );
            } catch (\Throwable $d) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }
        //if (!profile_do_upgrade_sql($current_ver, $sql, $dvlp)) return false;
        if (!profile_do_set_version($current_ver)) return false;
    }

    // Remove deprecated files
    PRF_remove_old_files();

    // Update the plugin configuration
    USES_lib_install();
    global $profileConfigData;
    require_once __DIR__ . '/install_defaults.php';
    _update_config('profile', $profileConfigData);

    // Clear JS and CSS cache
    Profile\Cache::clear();
    if (function_exists('CACHE_clear')) {
        CACHE_clear();
    } else {
        CTL_clearCache();
        @unlink(COM_getStyleCacheLocation()[0]);
        @unlink(COM_getJSCacheLocation()[0]);
    }
    // Catch any final version update needed for code-only upgrades
    if ($current_ver != $installed_ver) {
        if (!profile_do_set_version($installed_ver)) return false;
    }
    return true;
}


/**
 * Actually perform any sql updates.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   array   $sql        Array of SQL statement(s) to execute
 * @param   boolean $dvlp       True to ignore SQL errors and continue
 * @return  boolean     True on success, False on error
 */
function profile_do_upgrade_sql($version, $sql='', $dvlp=false)
{
    global $_TABLES, $_PRF_CONF;

    // If no sql statements passed in, return success
    if (!is_array($sql) || empty($sql)) {
        return true;
    }

    $db = Database::getInstance();

    // Execute SQL now to perform the upgrade
    Log::write('system', Log::INFO, "--Updating glProfile to version $version");
    $errmsg = 'SQL Error during Profile plugin update';
    if ($dvlp) $errmsg .= ' (ignored)';
    foreach ($sql as $query) {
        try {
            $db->conn->executeStatement($query);
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            if (!$dvlp) return false;
        }
    }
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function profile_do_set_version($ver)
{
    global $_TABLES, $_PRF_CONF;

    try {
        Database::getInstance()->conn->update(
            $_TABLES['plugins'],
            array(
                'pi_version' => $_PRF_CONF['pi_version'],
                'pi_gl_version' => $_PRF_CONF['gl_version'],
                'pi_homepage' => $_PRF_CONF['pi_url'],
            ),
            array('pi_name' => $_PRF_CONF['pi_name']),
            array(
                Database::STRING,
                Database::STRING,
                Database::STRING,
                Database::STRING,
            )
        );
        return true;
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        return false;
    }
}


/** Upgrade to version 0.0.2 */
function profile_upgrade_0_0_2()
{
    global $_TABLES, $_CONF, $_PRF_CONF;

    $db = Database::getInstance();
    Log::write('system', Log::INFO, 'Upgrading the profile plugin to 0.0.2');
    $grp_id = (int)$db->getItem(
        $_TABLES['groups'],
        'grp_id',
        array('grp_name' => 'profile Admin')
    );

    try {
        $db->conn->executeStatement(
            "ALTER TABLE {$_TABLES['profile_def']}
            ADD `group_id` mediumint(8) unsigned NOT NULL default '$grp_id',
            ADD `perm_owner` tinyint(1) unsigned NOT NULL default '3',
            ADD `perm_group` tinyint(1) unsigned NOT NULL default '3',
            ADD `perm_members` tinyint(1) unsigned NOT NULL default '1',
            ADD `perm_anon` tinyint(1) unsigned NOT NULL default '1'",
            array($grp_id),
            array(Database::INTEGER)
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }

    // Implement the glFusion online config system.
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once PRF_PI_PATH . 'install_defaults.php';
    if (!plugin_initconfig_profile($grp_id)) {
        return false;
    }

    if (!profile_do_upgrade_sql('0.0.2', $sql)) return false;
    return profile_do_set_version('0.0.2');
}


/** Upgrade to version 1.0.2 */
function profile_upgrade_1_0_2()
{
    global $_TABLES, $_CONF, $_PRF_CONF;

    $db = Database::getInstance();
    try {
        $stmt = $db->conn->executeQuery(
            "SELECT name, options FROM {$_TABLES['profile_def']}
            WHERE type='date'"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $stmt = false;
    }

    if ($stmt) {
        while ($A = $stmt->fetchAssociative()) {
            $options = @unserialize($A['options']);
            if (!$options) {
                continue;
            }

            try {
                $B = $db->conn->executeQuery(
                    "SELECT * FROM {$_TABLES['profile_values']} WHERE name = ?",
                    array($A['name']),
                    array(Database::STRING)
                )->fetchAssociative();
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                $B = false;
            }
            if (!is_array($B) || empty($B['value'])) {
                continue;
            }

            $ts = strtotime($B['value']);
            if (!$ts) {
                Log::write('system', Log::ERROR, "Cannot convert date- uid:{$B['uid']}, name:{$B['name']}, value:{$B['value']}");
                continue;
            }
            $dt = date('Y-m-d H:i:s', $ts);
            try {
                $db->conn->update(
                    $_TABLES['profile_values'],
                    array('value' => $dt),
                    array('uid' => $B['uid'], 'name' => $B['name']),
                    array(Database::STRING, Database::INTEGER, Database::STRING)
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            }
        }
    }

    if (!profile_do_upgrade_sql('1.0.2', $sql)) return false;
    return profile_do_set_version('1.0.2');
}


/**
 * Upgrade to version 1.1.0.
 * Returns non-zero if an error is encountered at any point.
 *
 * @uses    PRF_dbname()
 * @return  integer     Zero for success, non-zero on error
 */
function profile_upgrade_1_1_0()
{
    global $_TABLES, $LANG_PROFILE, $_PRF_CONF;

    require_once PRF_PI_PATH . 'install_defaults.php';

    $db = Database::getInstance();
    $grp_id = (int)$db->getItem(
        $_TABLES['groups'],
        'grp_id',
        array('grp_name' => 'profile Admin')
    );

    // New configuration item(s)
    $c = config::get_instance();
    if ($c->group_exists($_PRF_CONF['pi_name'])) {
        $c->add('sg_main', NULL, 'subgroup', 0, 0, NULL, 0, true,
                $_PRF_CONF['pi_name']);
        $c->add('fs_main', NULL, 'fieldset', 0, 0, NULL, 0, true,
                $_PRF_CONF['pi_name']);
        $c->add('showemptyvals', 0, 'select', 0, 0, 3, 10, true,
                $_PRF_CONF['pi_name']);
        $c->add('grace_arrears', $_PRF_DEFAULT['grace_arrears'], 'text',
                0, 0, 0, 20, true, $_PRF_CONF['pi_name']);
        $c->add('grace_expired', $_PRF_DEFAULT['grace_expired'], 'text',
                0, 0, 0, 30, true, $_PRF_CONF['pi_name']);
        $c->add('date_format', $_PRF_DEFAULT['date_format'], 'select',
                0, 0, 13, 40, true, $_PRF_CONF['pi_name']);
    }

    // Add the new profile.export feature
    try {
        $db->conn->insert(
            $_TABLES['features'],
            array(
                'ft_name' => 'profile.export',
                'ft_descr' => 'Access to export user lists',
            ),
            array(Database::STRING, Database::STRING)
        );
        $feat_id = $db->conn->lastInsertId();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $feat_id = 0;
    }
    if ($grp_id > 0 && $feat_id > 0) {
        try {
            $db->conn->insert(
                $_TABLES['access'],
                array('acc_ft_id' => $feat_id, 'acc_grp_id' => $grp_id),
                array(Database::INTEGER, Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }
    }

    $createsql = array();
    $old_vals = array();    // to hold original values arrays.
    $fldDefs = array();     // field definitions, to create data schema

    // Update the table to move the "readonly" function into perm_owner
    // This is the first version to have the "invisible" function, so
    // perm_owner should always be 2 or 3
    try {
        $db->conn->executeStatement(
            "UPDATE {$_TABLES['profile_def']}
            SET perm_owner = 2
            WHERE perm_owner > 1 AND readonly = 1"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }

    // Convert values to new table layout
    try {
        $stmt = $db->conn->executeQuery(
            "SELECT id,name,type,options FROM {$_TABLES['profile_def']}"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $stmt = false;
    }
    if ($stmt) {
        while ($A = $stmt->fetchAssociative()) {
            $fldDefs[$A['name']] = $A;
        }
    }

    // Now make the SQL to create the fields as actual database columns.
    foreach ($fldDefs as $name=>$data) {
        $options = unserialize($data['options']);
        if (!$options) $options = array();
        $opt_str = '';
        $fldDefs[$name]['dbname'] = PRF_dbname($name);
        $default_val = isset($data['defvalue']) && !empty($data['defvalue']) ?
                "DEFAULT '" . $db->conn->quote($data['defvalue']) . "'" : '';
        switch ($data['type']) {
        case 'date':
            if (isset($options['showtime']) && $options['showtime'] == '1') {
                $sql_type = 'DATETIME';
            } else {
                $sql_type = 'DATE';
            }
            break;
        case 'checkbox':
            $sql_type = 'TINYINT(1) UNSIGNED NOT NULL ' . $default_val;
            break;
        case 'static':
            continue 2;
            break;
        case 'select':
        case 'radio':
            if (is_array($options['values'])) {
                $old_vals[$name] = $options['values'];    // save for later use
                $values = array();
                foreach ($options['values'] as $key=>$valname) {
                    $values[] = $name;
                    if (isset($options['default']) && $options['default'] == $key)
                        $options['default'] = $valname;
                }
            }
            $sql_type = 'VARCHAR(255)';
            break;
        default:
            $sql_type = 'VARCHAR(255)';
            break;
        }
        $createsql[] = $fldDefs[$name]['dbname'] . ' ' . $sql_type;
        // Update the field name with the sanitized version and fix
        // value optons if needed.
        $sql = "UPDATE {$_TABLES['profile_def']}
                SET name='{$fldDefs[$name]['dbname']}'";
        if (!empty($options)) {
            $opt_str = serialize($options);
            $sql .= ", options='$opt_str'";
        }
        $sql .= " WHERE id='{$fldDefs[$name]['id']}'";
        try {
            $db->conn->executeStatement($sql);
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            return false;
        }
    }

    if (!empty($createsql)) {
        // Concatenate all the field creation statements
        $createsql_str =  implode(',', $createsql) . ', ';
    } else {
        $createsql_str = '';
    }
    $sql = "CREATE TABLE {$_TABLES['profile_data']} (
            puid int(11),
            sys_membertype varchar(255) DEFAULT NULL,
            sys_expires date DEFAULT NULL,
            sys_directory tinyint(1) NOT NULL DEFAULT '1',
            $createsql_str
            PRIMARY KEY (`puid`)
        ) ENGINE=MyISAM";
    try {
        $db->conn->executeStatement($sql);
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        return false;
    }

    // Now get the existing values from the values table.  Go through each
    // user to create a single record containing all the values from the
    // old values table.
    try {
        $vRes = $db->conn->executeQuery(
            "SELECT DISTINCT uid FROM {$_TABLES['profile_values']}"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $vRes = false;
    }
    if ($vRes) {
        while ($A = $vRes->fetchAssociative()) {
            $uid = (int)$A['uid'];
            // Get the defined fields
            try {
                $stmt = $db->conn->executeQuery(
                    "SELECT * FROM {$_TABLES['profile_values']} WHERE uid = ?",
                    array($uid),
                    array(Database::INTEGER)
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                $stmt = false;
            }
            $colsql = array();
            if ($stmt) {
                while ($U = $stmt->fetchAssociative()) {
                    if (isset($fldDefs[$U['name']]) && $U['name'] != 'uid') {
                        if (is_array($old_vals[$U['name']])) {
                            if (isset($old_vals[$U['name']][$U['value']])) {
                                $U['value'] = $old_vals[$U['name']][$U['value']];
                            }
                        }
                        $value = $db->conn->quote($U['value']);
                        $colsql[] = $fldDefs[$U['name']]['dbname'] . "='$value'";
                    }
                }
            }
        }
        if (!empty($colsql)) {
            if ($uid == 2) {
                // Disable showing in lists for admin user by default
                $colsql[] = 'sys_directory = 0';
            }
            // It's ok to have missing rows in the data table if there's no
            // data for a user yet.
            $colsqlstr = implode(',', $colsql);
            try {
                $db->conn->executeStatement(
                    "INSERT INTO {$_TABLES['profile_data']} SET
                    puid = $uid, $colsqlstr"
                );
            } catch (\Throwable $e) {
                Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                return false;
            }
        }
    }

    // Make sure that we created a record for Admin, to disable showing
    // on the lists
    if ($db->getCount($_TABLES['profile_data'], 'puid', '2', Database::INTEGER) == 0) {
        try {
            $db->conn->insert(
                $_TABLES['profile_data'],
                array('puid' => 2, 'sys_directory' => 0)
                array(Database::INTEGER, Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }
    }

    // Now add the required fields to the definitions table
    $sql = array(
        "ALTER TABLE {$_TABLES['profile_def']}
            ADD `sys` tinyint(1) unsigned NOT NULL DEFAULT '0',
            ADD `plugin` varchar(255) DEFAULT '',
            DROP `readonly`,
            ADD KEY `idx_orderby` (`orderby`)",
        "INSERT INTO {$_TABLES['profile_def']} VALUES
            (0, 2, 'sys_membertype', 'text', 1, 0, 0,
            '{$LANG_PROFILE['membertype']}',
            'a:4:{s:7:\"default\";s:0:\"\";s:4:\"size\";i:40;s:9:\"maxlength\";i:255;s:7:\"autogen\";i:0;}',
            $grp_id, 0, 3, 0, 0, 1, ''),
            ( 0, 4, 'sys_expires', 'date', 1, 0, 0,
            '{$LANG_PROFILE['expiration']}',
            'a:5:{s:7:\"default\";s:0:\"\";s:8:\"showtime\";i:0;s:10:\"timeformat\";s:2:\"12\";s:6:\"format\";N;s:12:\"input_format\";s:1:\"1\";}',
            $grp_id, 0, 3, 0, 0, 1, ''),
            (0, 6, 'sys_directory', 'checkbox', 1, 0, 1,
            '{$LANG_PROFILE['list_member_dir']}',
            'a:1:{s:7:\"default\";i:1;}', $grp_id, 3, 3, 0, 0, 1, '')",
        "CREATE TABLE `{$_TABLES['profile_lists']}` (
            `listid` varchar(40) NOT NULL,
            `orderby` int(10) unsigned NOT NULL DEFAULT '0',
            `title` varchar(40) DEFAULT NULL,
            `fields` text,
            `group_id` int(11) unsigned DEFAULT '13',
            PRIMARY KEY (`listid`)
        ) ENGINE=MyISAM",
    );
    if (!profile_do_upgrade_sql('1.1.0', $sql)) return false;
    return profile_do_set_version('1.1.0');
}


/**
 * Upgrade to version 1.1.1.
 *
 * @return  boolean     True on success, False on error
 */
function profile_upgrade_1_1_1()
{
    global $_TABLES, $_PRF_CONF;

    $db = Database::getInstance();
    $grp_id = (int)$db->getItem(
        $_TABLES['groups'],
        'grp_id',
        array('grp_name' => $_PRF_CONF['pi_name'] . ' Admin')
    );

    // Add the new profile.export feature
    try {
        $db->conn->insert(
            $_TABLES['features'],
            array(
                'ft_name' => 'profile.viewall',
                'ft_descr' => 'Access to view ALL user profiles, overriding user preferences.',
            ),
            array(Database::STRING, Database::STRINg)
        );
        $feat_id = $db->conn->lastInsertId();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $feat_id = 0;
    }
    if ($grp_id > 0 && $feat_id > 0) {
        try {
            $db->conn->insert(
                $_TABLES['access'],
                array(
                    'acc_ft_id' => $feat_id,
                    'acc_grp_id' => $grp_id,
                ),
                array(Database::INTEGER, Database::INTEGER)
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }
    }

    try {
        $db->conn->executeStatement(
            "DROP TABLE IF EXISTS {$_TABLES['profile_values']}"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
    try {
        $db->conn->executeStatement(
            "ALTER TABLE {$_TABLES['profile_lists']}
            ADD incl_grp int(11) unsigned default 2"
        );
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
    }
    return profile_do_set_version('1.1.1');
}


/**
 * Upgrade to version 1.1.2.
 * Returns non-zero if an error is encountered at any point.
 *
 * @return  boolean     True on success, False on error
 */
function profile_upgrade_1_1_2()
{
    global $_TABLES, $_PRF_CONF, $LANG_PROFILE;

    require_once PRF_PI_PATH . 'install_defaults.php';
    $db = Database::getInstance();

    // New configuration item(s)
    $c = config::get_instance();
    $c->add('fs_lists', NULL, 'fieldset', 0, 2, NULL, 0, true,
                $_PRF_CONF['pi_name']);
    $c->add('list_incl_admin', 0, 'select', 0, 2, 3, 10, true,
                $_PRF_CONF['pi_name']);
    $c->del('grace_arrears', $_PRF_CONF['pi_name']);

    $grp_id = (int)$db->getItem(
        $_TABLES['groups'],
        'grp_id',
        array('grp_name' => $_PRF_CONF['pi_name'] . ' Admin')
    );
    if ($grp_id < 1) $grp_id = 1;

    $sql = array(
        "ALTER TABLE {$_TABLES['profile_data']}
            ADD `sys_parent` mediumint(8) unsigned NOT NULL DEFAULT '0'
                AFTER `sys_directory`",
        "INSERT INTO {$_TABLES['profile_def']} (
                orderby, name, type, enabled, required, user_reg,
                prompt, options, sys, group_id,
                perm_owner, perm_group, perm_members, perm_anon
            ) VALUES (
                999, 'sys_parent', 'account', 0, 0, 0,
                '{$LANG_PROFILE['parent_uid']}',
                'a:1:{s:7:\"default\";s:1:\"0\";}', 1, $grp_id,
                0, 3, 0, 0
            )",
        "UPDATE {$_TABLES['profile_def']}
                SET type='date' WHERE name='sys_expires'",
        "ALTER TABLE {$_TABLES['profile_lists']}
                ADD incl_user_stat tinyint(2) not null default -1,
                ADD incl_exp_stat tinyint(2) not null default 7",
    );
    if (!profile_do_upgrade_sql('1.1.2', $sql)) return false;
    return profile_do_set_version('1.1.2');
}


/**
 * Upgrade to version 1.1.3.
 * Returns non-zero if an error is encountered at any point.
 *
 * @return  boolean     True on success, False on error
 */
function profile_upgrade_1_1_3()
{
    global $_TABLES;

    $db = Database::getInstance();

    // Special handling for adding show_in_profile. This function is called
    // for the 1.1.3 and 1.1.4 update since the SQL wasn't include in new
    // installations for 1.1.3
    $sql = array();
    if (!_PRFtableHasColumn('profile_def', 'show_in_profile')) {
        $sql[] = "ALTER TABLE {$_TABLES['profile_def']}
            ADD show_in_profile tinyint(1) NOT NULL DEFAULT 1 AFTER user_reg";
    }

    $need_update = false;   // assume it's done and check if it's needed
    try {
        $A = $db->conn->executeQuery(
            "SHOW COLUMNS FROM {$_TABLES['profile_lists']} LIKE 'incl_user_stat'"
        )->fetchAssociative();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $A = false;
    }
    if (is_array($A) && $A['Type'] !== 'varchar(64)') {
        $sql[] = "ALTER TABLE {$_TABLES['profile_lists']}
            CHANGE incl_user_stat incl_user_stat varchar(64) NOT NULL
            DEFAULT 'a:4:{i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;}'";
        $need_update = true;    // later, to execute data conversion
    }
    if (!profile_do_upgrade_sql('1.1.3', $sql)) return false;

    if ($need_update) {     // updated profile_lists schema, now change data
        try {
            $stmt = $db->conn->executeQuery(
                "SELECT listid,incl_user_stat FROM {$_TABLES['profile_lists']}"
            );
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
            $stmt = false;
        }
        if ($stmt) {
            while ($A = $stmt->fetchAssociative()) {
                $listid = $A['listid'];
                $ustat = (int)$A['incl_user_stat'];
                if ($ustat == -1) $ustat = array(1,2,3,4);
                else $ustat = array($ustat);
                $nstat = @serialize($ustat);
                try {
                    $db->conn->update(
                        $_TABLES['profile_lists'],
                        array('incl_user_stat' => $nstat),
                        array('listid' => $listid)
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                    $stmt = false;
                }
            }
        }
    }
    return profile_do_set_version('1.1.3');
}


/**
 * Upgrade to version 1.1.4.
 * Adds first and last name fields to the profile data.
 *
 * @param   boolean $dvlp   True to ignore SQL errors
 * @return  boolean     True on success, False on error
 */
function profile_upgrade_1_1_4($dvlp=false)
{
    global $_TABLES, $LANG_PROFILE, $_PRF_CONF;

    COM_errorLog('Performing 1.1.3 SQL updates if needed');
    profile_upgrade_1_1_3(true);
    $db = Database::getInstance();

    $sql = array();
    $add_name_parts = false;
    if (!_PRFtableHasColumn('profile_data', 'sys_fname')) {
        $add_name_parts = true;
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            ADD sys_fname varchar(40) AFTER puid";
    }
    if ($db->getCount($_TABLES['profile_def'], 'name', 'sys_fname', Database::INTEGER) < 1) {
        $sql[] = "INSERT INTO {$_TABLES['profile_def']}
                (orderby, name, type, enabled, required, user_reg,
                prompt, options, sys, perm_owner)
            VALUES
                (41, 'sys_fname', 'fname', 0, 0, 0, '{$LANG_PROFILE['fname']}',
                    'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 1, 2)";
    }
    if (!_PRFtableHasColumn('profile_data', 'sys_lname')) {
        $add_name_parts = true;
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            ADD sys_lname varchar(40) AFTER sys_fname";
    }
    if ($db->getCount($_TABLES['profile_def'], 'name', 'sys_lname', Database::STRING) < 1) {
        $sql[] = "INSERT INTO {$_TABLES['profile_def']}
                (orderby, name, type, enabled, required, user_reg,
                prompt, options, sys, perm_owner)
            VALUES
                (42, 'sys_lname', 'lname', 0, 0, 0, '{$LANG_PROFILE['lname']}',
                    'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 1, 2)";
    }

    // Check if a "prf_phone" column exists in the data table but
    // missing from the definition table and add the definition if
    // needed.
    // The def was omitted when the sample data was originally added.
    if (_PRFtableHasColumn('profile_data', 'prf_phone')) {
        $r2 = $db->getCount($_TABLES['profile_def'], 'name', 'prf_phone', Database::STRING);
        if ($r2 == 0) {
            $sql[] = "INSERT INTO {$_TABLES['profile_def']}
                (orderby, name, type, enabled, required, user_reg,
                prompt, options, sys, perm_owner)
                VALUES
                    (95, 'prf_phone', 'text', 1, 0, 0, 'Phone Number',
                'a:5:{s:7:\"default\";s:0:\"\";s:9:\"help_text\";\
                s:23:\"enter ynur phone number\";s:4:\"size\";\
                i:40;s:9:\"maxlength\";i:255;s:7:\"autogen\";i:0;}', 0, 3)";
        }
    }

    if (!profile_do_upgrade_sql('1.1.4', $sql, $dvlp)) return false;

    if ($add_name_parts) {
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
                if ($A['fullname'] == '') $A['fullname'] = $A['username'];
                $fname = \LGLib\NameParser::F($A['fullname']);
                $lname = \LGLib\NameParser::L($A['fullname']);
                $uid = (int)$A['uid'];
                try {
                    $db->conn->update(
                        $_TABLES['profile_data'],
                        array('sys_fname' => $fname, 'sys_lname' => $lname),
                        array('puid' => $uid),
                        array(Database::STRING, Database::STRING, Database::INTEGER)
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                    if (!$dvlp) return false;
                }
            }
        }
    }
    return profile_do_set_version('1.1.4');
}


/**
 * Upgrade to version 1.2.0
 * Changes the prompt field to `text`.
 *
 * @param   boolean $dvlp   True to ignore SQL errors
 * @return  boolean     True on success, False on error
 */
function profile_upgrade_1_2_0($dvlp=false)
{
    global $_TABLES, $LANG_PROFILE, $_PRF_CONF;

    $db = Database::getInstance();
    $sql = array(
        "ALTER TABLE {$_TABLES['profile_def']}
            CHANGE `prompt` `prompt` text COLLATE utf8_unicode_ci",
        "UPDATE {$_TABLES['profile_def']} SET
            name='prf_membertype', sys=0
            WHERE name = 'sys_membertype'",
        "UPDATE {$_TABLES['profile_def']} SET
            name='prf_expires', sys=0
            WHERE name = 'sys_expires'",
        "UPDATE {$_TABLES['profile_def']} SET
            name='prf_parent', sys=0
            WHERE name = 'sys_parent'",
        "DELETE FROM {$_TABLES['profile_def']} WHERE puid = 1",
    );
    if (_PRFtableHasColumn('profile_data', 'sys_membertype')) {
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            CHANGE sys_membertype prf_membertype varchar(128)";
    }
    if (_PRFtableHasColumn('profile_data', 'sys_expires')) {
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            CHANGE sys_expires prf_expires date";
    }
    if (_PRFtableHasColumn('profile_data', 'sys_parent')) {
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            CHANGE sys_parent prf_parent mediumint(8) unsigned not null default 0";
    }
    if (_PRFtableHasColumn('profile_lists', 'incl_exp_stat')) {
        $sql[] = "ALTER TABLE {$_TABLES['profile_lists']} DROP incl_exp_stat";
    }

    if (!profile_do_upgrade_sql('1.2.0', $sql, $dvlp)) return false;

    // Add the profile.view permission to be used in place of "members"
    $ft_descr = $db->getItem(
        $_TABLES['features'],
        'ft_name',
        array('ft_name' => 'profile.view')
    );
    if (!$ft_descr) {
        try {
            $db->conn->insert(
                $_TABLES['features'],
                array(
                    'ft_name' => 'profile.view',
                    'ft_descr' => 'Access to view public profile fields for other members.',
                ),
                array(Database::STRING, Database::STRING)
            );
            $feat_id = $db->conn->lastInsertId();
        } catch (\Throwable $e) {
            Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        }
        if ($feat_id > 0) {
            $grp_id = (int)$db->getItem(
                $_TABLES['groups'],
                'grp_id',
                array('grp_name' => 'Logged-In Users')
            );
            if ($grp_id > 0) {
                try {
                    $db->conn->insert(
                        $_TABLES['access']
                        array(
                            'acc_ft_id' => $feat_id,
                            'acc_grp_id' => $grp_id,
                        ),
                        array(Database::INTEGER, Database::INTEGER)
                    );
                } catch (\Throwable $e) {
                    Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
                }
            }
        }
    }
    return profile_do_set_version('1.2.0');
}


/**
 * Create the database name for a field.
 * Used when upgrading to 1.1.0 to change "fieldname" to "prf_fieldname".
 *
 * @see     function profile_upgrade_1_1_0
 * @param   string  $oldname    Original field name
 * @return  string              New fieldname
 */
function PRF_dbname($oldname)
{
    // Empty name? Do nothing.
    if ($oldname == '') return $oldname;
    // Strip any leading "prf_" from the name, just in case
    $newname = preg_replace('/^prf_/', '', $oldname);
    // Now sanitize the name.  Only alphanumeric characters allowed
    $newname = preg_replace('/[^a-zA-Z0-9]+/','', $newname);
    // Finally prepend "prf_" to the sanitized name.
    $newname = 'prf_' . $newname;

    return $newname;
}


/**
 *   Remove deprecated files
 *   Errors in unlink() and rmdir() are ignored.
 */
function PRF_remove_old_files()
{
    global $_CONF;

    $paths = array(
        // private/plugins/profile
        __DIR__ => array(
            // 1.2.0 - Replaced with namespaced field classes
            'classes/prfItem_account.class.php',
            'classes/prfItem_checkbox.class.php',
            'classes/prfItem.class.php',
            'classes/prfItem_date.class.php',
            'classes/prfItem_link.class.php',
            'classes/prfItem_multicheck.class.php',
            'classes/prfItem_radio.class.php',
            'classes/prfItem_select.class.php',
            'classes/prfItem_static.class.php',
            'classes/prfItem_textarea.class.php',
            'classes/prfItem_text.class.php',
            'classes/prfList.class.php',
            // 1.2.1 - Remove old .uikit.thtml files
            'templates/profile_registration.uikit.thtml',
            'templates/profile_usersettings.uikit.thtml',
            // 1.2.8 - Some old files not removed previously
            'profile_functions.inc.php',
            'templates/admin/list.uikit.thtml',
            'templates/admin/profile.uikit.thtml',
            'templates/pdf/default.thtml',
            'css/calendar-blue.css',
            // 1.3.0
            'language/german_formal_utf-8.php',
        ),
        // public_html/profile
        $_CONF['path_html'] . 'profile' => array(
        ),
        // admin/plugins/profile
        $_CONF['path_html'] . 'admin/plugins/profile' => array(
        ),
    );
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        // Files that were renamed, changing case only.
        // Only delete these on non-windows systems.
        $paths[__DIR__][] = 'classes/pdflist.class.php';
    }

    foreach ($paths as $path=>$files) {
        foreach ($files as $file) {
            @unlink("$path/$file");
        }
    }
}


/**
 * Check if a column exists in a table
 *
 * @param   string  $table      Table Key, defined in paypal.php
 * @param   string  $col_name   Column name to check
 * @return  boolean     True if the column exists, False if not
 */
function _PRFtableHasColumn($table, $col_name)
{
    global $_TABLES;

    try {
        $stmt = Database::getInstance()->conn->executeQuery(
            "SHOW COLUMNS FROM {$_TABLES[$table]} LIKE ?",
            array($col_name),
            array(Database::STRING)
        );
        $rows = $stmt->rowCount();
    } catch (\Throwable $e) {
        Log::write('system', Log::ERROR, __FUNCTION__ . ': ' . $e->getMessage());
        $rows = 0;
    }
    return $rows;
}

