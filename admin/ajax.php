<?php
/**
 *  Common AJAX functions
 *
 *  @author     Lee Garner <lee@leegarner.com>
 *  @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
 *  @package    profile
 *  @version    0.0.2
 *  @license    http://opensource.org/licenses/gpl-2.0.php 
 *  GNU Public License v2 or later
 *  @filesource
 */

/**
 *  Include required glFusion common functions
 */
require_once '../../../lib-common.php';

// This is for administrators only
if (!SEC_hasRights('profile.admin')) {
    COM_accessLog("User {$_USER['username']} tried to illegally access the profile AJAX functions.");
    exit;
}

$base_url = $_CONF['site_url'];

switch ($_GET['action']) {
case 'toggleEnabled':
    $newval = $_REQUEST['newval'] == 1 ? 1 : 0;
    $type = trim($_GET['type']);
    $id = (int)$_REQUEST['id'];

    switch ($type) {
    case 'required':
    case 'enabled':
    case 'user_reg':
        // Toggle the is_origin flag between 0 and 1
        DB_query("UPDATE {$_TABLES['profile_def']}
                SET $type = '$newval'
                WHERE id='$id'", 1);
        break;

    case 'readonly':
        // Change the perm_owner to 2 if readonly is 1
        $db_val = $newval == 1 ? 2 : 3;
        DB_query("UPDATE {$_TABLES['profile_def']}
                SET perm_owner = $db_val
                WHERE id = '$id'", 1);
        break;

    case 'public':
        // Change the perm_owner to 2 if readonly is 1
        $db_val = $newval == 1 ? 2 : 0;
        DB_query("UPDATE {$_TABLES['profile_def']}
                SET perm_members = $db_val, perm_anon = $db_val
                WHERE id = '$id'", 1);
        break;

     default:
        exit;
    }
    
    header('Content-Type: text/xml');
    header("Cache-Control: no-cache, must-revalidate");
    //A date in the past
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

    echo '<?xml version="1.0" encoding="ISO-8859-1"?>
    <info>'. "\n";
    echo "<newval>$newval</newval>\n";
    echo "<id>{$id}</id>\n";
    echo "<type>{$type}</type>\n";
    echo "<baseurl>{$base_url}</baseurl>\n";
    echo "</info>\n";
    break;

default:
    exit;
}

?>
