<?php
/**
 * Web service functions for the Profile plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2011-2021 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own!');
}

/**
 * Set system values in a user's profile.
 * If profile data doesn't exist for the user, then a record is
 * created.
 *
 * The $args array must contain:
 *      'uid' => user_id
 *      'field' => field_name
 *      'value' => new_value
 *
 * @param   array   $args       Array of UID, Field and Value.
 * @param   mixed   $output    Unused.
 * @param   string  $svc_msg   Unused.
 * @return  integer             Status of update
 */
function service_setvalue_profile($args, &$output, &$svc_msg)
{
    global $_TABLES;

    if (
        !isset($args['uid']) ||
        $args['uid'] < 2 ||
        !isset($args['field']) ||
        empty($args['field'])
    ) {
        return PLG_RET_ERROR;
    }
    $uid = (int)$args['uid'];
    $field = DB_escapeString($args['field']);

    if (!isset($args['value'])) {
        $value='';
    } else {
        $value = DB_escapeString($args['value']);
    }

    $where = "name='$field'";
    if (isset($args['plugin)'])) {
        $where .= " AND plugin='" . DB_escapeString($plugin) . "'";
    }

    $type = DB_getItem($_TABLES['profile_def'], 'type', $where);
    if (!$type) {
        return PLG_RET_ERROR;
    }

    // Verify that the column exists
    $res = DB_query("SHOW COLUMNS FROM {$_TABLES['profile_data']}
            WHERE Field = '{$field}'");
    if (DB_numRows($res) != 1) {
        return PLG_RET_ERROR;
    }

    // See if there is a record for this user.  Create one if not.
    $puid = DB_getItem(
        $_TABLES['profile_data'],
        'puid',
        "puid = $uid"
    );
    if (empty($puid)) {
        $sql = "INSERT INTO {$_TABLES['profile_data']}
            SET puid = $uid, $field = '$value'";
    } else {
        $sql = "UPDATE {$_TABLES['profile_data']}
            SET $field = '$value'
            WHERE puid = $uid";
    }
    DB_query($sql, 1);
    if (!DB_error()) {
        return PLG_RET_SUCCESS;
    } else {
        return PLG_RET_ERROR;
    }
}


/**
 * Return one or more value from the data table corresponding to
 * a specific user ID and item name(s).
 *
 * @param   array       $args       mixed 'item' and optional 'uid'
 * @param   mixed       $output     Output array
 * @param   mixed       $svc_msg    Service messages
 * @return  integer     Result code
 */
function service_getValues_profile($args, &$output, &$svc_msg)
{
    global $_TABLES, $_USER;

    $output = array();

    $uid = isset($args['uid']) ? (int)$args['uid'] : (int)$_USER['uid'];
    if ($uid < 2) {
        return PLG_RET_ERROR;
    }

    // Get all the profile items. We don't consider a failure here to be
    // an error, there will just be nothing returned.
    $A = Profile\Profile::getInstance($uid)->getFields();

    if (isset($args['item'])) {
        $items = $args['item'];
        if ($items == '*') {
            // Getting the formatted value for all items
            foreach ($A as $key=>$item) {
                $output[$key] = $A[$item]->FormatValue();
            }
        } else {
            // Getting the formatted value for one or more specific items
            if (!is_array($items)) {
                $items = array($items);
            }
            foreach ($items as $item) {
                if (isset($A[$item])) {
                    $output[$item] = $A[$item]->FormatValue();
                }
            }
        }
    } else {
        // Just getting the item objects for all items
        foreach ($A as $key=>$item) {
            $output[$key] = $item;
        }
    }
    return PLG_RET_OK;
}


/**
 * Create an edit form similar to the Account Settings section.
 * Does not include the <form> tags, leaving the action up to the caller.
 *
 * @param   array       $args       mixed 'item' and optional 'uid'
 * @param   mixed       $output     Output array
 * @param   mixed       $svc_msg    Service messages
 * @return  integer     Result code
 */
function service_renderForm_profile($args, &$output, &$svc_msg)
{
    global $_USER;

    $output = '';

    if (COM_isAnonUser()) {
        return PLG_RET_ERROR;
    }
    $uid = isset($args['uid']) ? $args['uid'] : $_USER['uid'];
    $form_id = isset($args['form_id']) ? $args['form_id'] : '';
    $P = Profile\Profile::getInstance($uid);
    $output = $P->Edit('inline', $form_id);
    return PLG_RET_OK;
}


/**
 * Allow other plugins to pass profile data to be saved.
 * The form should be created with service_renderForm_profile() to ensure
 * that all the variables are present and named correctly.
 *
 * @param   array       $args       mixed 'item' and optional 'uid'
 * @param   mixed       $output     Output array
 * @param   mixed       $svc_msg    Service messages
 * @return  integer     Result code
 */
function service_saveData_profile($args, &$output, &$svc_msg)
{
    global $_USER;

    // Make sure that if we're saving a different user's profile that
    // we'are allowed to do so.
    if (!SEC_hasRights('profile.admin') && $args['uid'] != $_USER['uid']) {
        return PLG_RET_ERROR;
    }

    $P = Profile\Profile::getInstance($args['uid']);
    $status = $P->Save($args['data']);
    return $status ? PLG_RET_OK : PLG_RET_ERROR;
}


/**
 * Verify that a user's profile is valid.
 * Checks each field using the field's validData function and sets $output
 * to an array of field names with invalid values.
 *
 * @param   array       $args       Must include `uid` element
 * @param   mixed       $output     Output array - gets error field names
 * @param   mixed       $svc_msg    Service messages (not used)
 * @return  integer     Result code
 */
function service_validate_profile($args, &$output, &$svc_msg)
{
    $uid = $args['uid'];
    $Prf = Profile\Profile::getInstance($uid);
    $output = array();
    foreach ($Prf->getFields() as $name=>$Fld) {
        if (!$Fld->validData()) {
            $output[] = $name;
        }
    }
    return empty($output) ? PLG_RET_OK : PLG_RET_ERROR;
}
