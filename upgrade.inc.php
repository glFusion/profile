<?php
/**
 * Upgrade routines for the Custom Profile plugin
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.1.4
 * @license     http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

// Required to get the config values
global $_CONF, $_PRF_CONF;


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
    global $_PRF_CONF, $_PLUGIN_INFO;
    
    if (isset($_PLUGIN_INFO[$_PRF_CONF['pi_name']])) {
        if (is_array($_PLUGIN_INFO[$_PRF_CONF['pi_name']])) {
            // glFusion > 1.6.5
            $current_ver = $_PLUGIN_INFO[$_PRF_CONF['pi_name']]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$_PRF_CONF['pi_name']];
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

    // Update the plugin configuration
    USES_lib_install();
    global $profileConfigData;
    require_once __DIR__ . '/install_defaults.php';
    _update_config('profile', $profileConfigData);

    // Catch any final version update needed for code-only upgrades
    if (!COM_checkVersion($current_ver, $installed_ver)) {
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
 * @#return boolean     True on success, False on error
 */
function profile_do_upgrade_sql($version, $sql='', $dvlp=false)
{
    global $_TABLES, $_PRF_CONF;

    // If no sql statements passed in, return success
    if (!is_array($sql)) {
        return true;
    }

    // Execute SQL now to perform the upgrade
    COM_errorLOG("--Updating glProfile to version $version");
    $errmsg = 'SQL Error during Profile plugin update';
    if ($dvlp) $errmsg .= ' (ignored)';
    foreach ($sql as $query) {
        //COM_errorLOG("Profile Plugin $version update: Executing SQL => $query");
        DB_query($query, 1);
        if (DB_error()) {
            COM_errorLog("$errmsg: $query",1);
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

    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '{$_PRF_CONF['pi_version']}',
            pi_gl_version = '{$_PRF_CONF['gl_version']}',
            pi_homepage = '{$_PRF_CONF['pi_url']}'
        WHERE pi_name = '{$_PRF_CONF['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_PRF_CONF['pi_display_name']} Plugin version",1);
        return false;
    } else {
        return true;
    }
}


/** Upgrade to version 0.0.2 */
function profile_upgrade_0_0_2()
{
    global $_TABLES, $_CONF, $_PRF_CONF;

    COM_errorLog('Upgrading the profile plugin to 0.0.2');
    $grp_id = (int)DB_getItem($_TABLES['groups'], 'grp_id',
                "grp_name='profile Admin'");

    $sql[] = "ALTER TABLE {$_TABLES['profile_def']}
        ADD `group_id` mediumint(8) unsigned NOT NULL default '$grp_id',
        ADD `perm_owner` tinyint(1) unsigned NOT NULL default '3',
        ADD `perm_group` tinyint(1) unsigned NOT NULL default '3',
        ADD `perm_members` tinyint(1) unsigned NOT NULL default '1',
        ADD `perm_anon` tinyint(1) unsigned NOT NULL default '1'";

    // Implement the glFusion online config system.
    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once PRF_PI_PATH . 'install_defaults.php';
    if (!plugin_initconfig_profile($grp_id))
        return false;

    if (!profile_do_upgrade_sql('0.0.2', $sql)) return false;
    return profile_do_set_version('0.0.2');
}


/** Upgrade to version 1.0.2 */
function profile_upgrade_1_0_2()
{
    global $_TABLES, $_CONF, $_PRF_CONF;

    $sql1 = "SELECT name, options FROM {$_TABLES['profile_def']}
            WHERE type='date'";
    $res1 = DB_query($sql1);
    while ($A = DB_fetchArray($res1, false)) {
        $options = unserialize($A['options']);
        if (!$options)
            continue;

        $sql2 = "SELECT * FROM {$_TABLES['profile_values']}
            WHERE name='{$A['name']}'";
        $res2 = DB_query($sql2);
        while ($B = DB_fetchArray($res2, false)) {
            if (empty($B['value']))
                continue;

            $ts = strtotime($B['value']);
            if (!$ts) {
                COM_errorLog("Cannot convert date- uid:{$B['uid']}, name:{$B['name']}, value:{$B['value']}");
                continue;
            }
            $dt = date('Y-m-d H:i:s', $ts);
            $sql3 = "UPDATE {$_TABLES['profile_values']}
                    SET value='$dt'
                    WHERE uid='{$B['uid']}'
                    AND name='{$B['name']}'";
            DB_query($sql3);
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

    $grp_id = (int)DB_getItem($_TABLES['groups'], 'grp_id',
                    "grp_name='profile Admin'");

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
    DB_query("INSERT INTO {$_TABLES['features']}
            (ft_name, ft_descr)
        VALUES (
            'profile.export',
            'Access to export user lists'
        )", 1);
    if (!DB_error()) {
        $feat_id = DB_insertId();
        if ($grp_id > 0 && $feat_id > 0) {
            DB_query("INSERT INTO {$_TABLES['access']}
                    (acc_ft_id, acc_grp_id)
                VALUES
                    ($feat_id, $grp_id)", 1);
        }
    }

    $createsql = array();
    $old_vals = array();    // to hold original values arrays.
    $fldDefs = array();     // field definitions, to create data schema

    // Update the table to move the "readonly" function into perm_owner
    // This is the first version to have the "invisible" function, so
    // perm_owner should always be 2 or 3
    DB_query("UPDATE {$_TABLES['profile_def']}
            SET perm_owner = 2
            WHERE perm_owner > 1 AND readonly = 1", 1);

    // Convert values to new table layout
    $rDef = DB_query("SELECT id,name,type,options
                FROM {$_TABLES['profile_def']}");
    while ($A = DB_fetchArray($rDef, false)) {
        $fldDefs[$A['name']] = $A;
    }

    // Now make the SQL to create the fields as actual database columns.
    foreach ($fldDefs as $name=>$data) {
        $options = unserialize($data['options']);
        if (!$options) $options = array();
        $opt_str = '';
        $fldDefs[$name]['dbname'] = PRF_dbname($name);
        $default_val = isset($data['defvalue']) && !empty($data['defvalue']) ?
                "DEFAULT '" . DB_escapeString($data['defvalue']) . "'" : '';
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
            continue;
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
        DB_query($sql, 1);
        if (DB_error()) return false;
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
        //echo $sql;
        DB_query($sql, 1);
        if (DB_error()) return false;

    // Now get the existing values from the values table.  Go through each
    // user to create a single record containing all the values from the
    // old values table.
    $vRes = DB_query("SELECT DISTINCT uid FROM {$_TABLES['profile_values']}");
    while ($A = DB_fetchArray($vRes, false)) {
        $uid = (int)$A['uid'];
        // Get the defined fields
        $sql = "SELECT *
            FROM {$_TABLES['profile_values']}
            WHERE uid = $uid";
        $res = DB_query($sql);
        $colsql = array();
        while ($U = DB_fetchArray($res, false)) {
            if (isset($fldDefs[$U['name']]) && $U['name'] != 'uid') {
                if (is_array($old_vals[$U['name']])) {
                    if (isset($old_vals[$U['name']][$U['value']])) {
                        $U['value'] = $old_vals[$U['name']][$U['value']];
                    }
                }
                $value = DB_escapeString($U['value']);
                $colsql[] = $fldDefs[$U['name']]['dbname'] . "='$value'";
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
            $dsql = "INSERT INTO {$_TABLES['profile_data']} SET
                    puid = $uid, $colsqlstr";
            DB_query($dsql, 1);
            if (DB_error()) {
                COM_errorLog("Error executing $dsql");
                 return false;
            }
        }
    }

    // Make sure that we created a record for Admin, to disable showing
    // on the lists
    if (DB_count($_TABLES['profile_data'], 'puid', '2') == 0) {
        DB_query("INSERT INTO {$_TABLES['profile_data']}
                SET puid = 2, sys_directory = 0", 1);
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

    $grp_id = (int)DB_getItem($_TABLES['groups'], 'grp_id',
                    "grp_name='{$_PRF_CONF['pi_name']} Admin'");

    // Add the new profile.export feature
    DB_query("INSERT INTO {$_TABLES['features']}
            (ft_name, ft_descr)
        VALUES (
            'profile.viewall',
            'Access to view ALL user profiles, overriding user preferences.'
        )", 1);
    if (!DB_error()) {
        $feat_id = DB_insertId();
        if ($grp_id > 0 && $feat_id > 0) {
            DB_query("INSERT INTO {$_TABLES['access']}
                    (acc_ft_id, acc_grp_id)
                VALUES
                    ($feat_id, $grp_id)", 1);
        }
    }

    DB_query("DROP TABLE IF EXISTS {$_TABLES['profile_values']}");
    DB_query("ALTER TABLE {$_TABLES['profile_lists']}
            ADD incl_grp int(11) unsigned default 2");
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

    // New configuration item(s)
    $c = config::get_instance();
    $c->add('fs_lists', NULL, 'fieldset', 0, 2, NULL, 0, true,
                $_PRF_CONF['pi_name']);
    $c->add('list_incl_admin', 0, 'select', 0, 2, 3, 10, true,
                $_PRF_CONF['pi_name']);
    $c->del('grace_arrears', $_PRF_CONF['pi_name']);

    $grp_id = (int)DB_getItem($_TABLES['groups'], 'grp_id',
            "grp_name='{$_PRF_CONF['pi_name']} Admin'");
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

    // Special handling for adding show_in_profile. This function is called
    // for the 1.1.3 and 1.1.4 update since the SQL wasn't include in new
    // installations for 1.1.3
    $sql = array();
    if (!_PRFtableHasColumn('profile_def', 'show_in_profile')) {
        $sql[] = "ALTER TABLE {$_TABLES['profile_def']}
            ADD show_in_profile tinyint(1) NOT NULL DEFAULT 1 AFTER user_reg";
    }

    $need_update = false;   // assume it's done and check if it's needed
    $r = DB_query("SHOW COLUMNS FROM {$_TABLES['profile_lists']} LIKE 'incl_user_state'");
    $A = DB_fetchArray($r, false);
    if ($A['Type'] !== 'varchar(64)') {
        $sql[] = "ALTER TABLE {$_TABLES['profile_lists']}
            CHANGE incl_user_stat incl_user_stat varchar(64) NOT NULL
            DEFAULT 'a:4:{i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;}'";
        $need_update = true;    // later, to execute data conversion
    }
    if (!profile_do_upgrade_sql('1.1.3', $sql)) return false;

    if ($need_update) {     // updated profile_lists schema, now change data
        $sql = "SELECT listid,incl_user_stat FROM {$_TABLES['profile_lists']}";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $listid = DB_escapeString($A['listid']);
            $ustat = (int)$A['incl_user_stat'];
            if ($ustat == -1) $ustat = array(1,2,3,4);
            else $ustat = array($ustat);
            $nstat = DB_escapeString(@serialize($ustat));
            DB_query("UPDATE {$_TABLES['profile_lists']} SET 
                    incl_user_stat = '$nstat' WHERE listid='$listid'");
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
    profile_upgrade_1_1_3($dvlp);

    $sql = array();
    $add_name_parts = false;
    if (!_PRFtableHasColumn('profile_def', 'sys_fname')) {
        $add_name_parts = true;
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            ADD sys_fname varchar(40) AFTER sys_parent";
        $sql[] = "INSERT INTO {$_TABLES['profile_def']}
                (orderby, name, type, enabled, required, user_reg,
                prompt, options, sys, perm_owner)
            VALUES
                (41, 'sys_fname', 'fname', 0, 0, 0, '{$LANG_PROFILE['fname']}',
                    'a:2:{s:4:\"size\";i:40;s:9:\"maxlength\";i:80;}', 1, 2)";
    }
    if (!_PRFtableHasColumn('profile_def', 'sys_lname')) {
        $add_name_parts = true;
        $sql[] = "ALTER TABLE {$_TABLES['profile_data']}
            ADD sys_lname varchar(40) AFTER sys_fname";
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
        $r2 = DB_count($_TABLES['profile_def'], 'name', 'prf_phone');
        if ($r2 == 0) {
            $sql[] = "INSERT INTO {$_TABLES['profile_def']}
                (orderby, name, type, enabled, required, user_reg,
                prompt, options, sys, perm_owner)
                VALUES
                    (95, 'prf_phone', 'text', 1, 0, 0, 'Phone Number', 
                'a:5:{s:7:\"default\";s:0:\"\";s:9:\"help_text\";\
                s:23:\"Enter your phone number\";s:4:\"size\";\
                i:40;s:9:\"maxlength\";i:255;s:7:\"autogen\";i:0;}', 0, 3)";
        }
    }

    if (!profile_do_upgrade_sql('1.1.4', $sql, $dvlp)) return false;

    if ($add_name_parts) {
        $sql = "SELECT uid, fullname FROM {$_TABLES['users']}";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            // use username if fullname is empty
            // fullname may be empty, but username can't be
            if ($A['fullname'] == '') $A['fullname'] = $A['username'];
            $fname = DB_escapeString(\LGLib\NameParser::F($A['fullname']));
            $lname = DB_escapeString(\LGLib\NameParser::L($A['fullname']));
            $uid = (int)$A['uid'];
            $sql = "UPDATE {$_TABLES['profile_data']} SET
                    sys_fname='$fname', sys_lname='$lname'
                    WHERE puid=$uid";
            DB_query($sql, 1);
            if (DB_error()) {
                COM_errorLog("Error executing sql in profile 1.1.4 update: $sql");
                if (!$dvlp) return false;
            }
        }
    }
    return profile_do_set_version('1.1.4');
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
        ),
        // public_html/profile
        $_CONF['path_html'] . 'profile' => array(
        ),
        // admin/plugins/profile
        $_CONF['path_html'] . 'admin/plugins/profile' => array(
        ),
    );

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

    $col_name = DB_escapeString($col_name);
    $res = DB_query("SHOW COLUMNS FROM {$_TABLES[$table]} LIKE '$col_name'");
    return DB_numRows($res) == 0 ? false : true;
}

?>
