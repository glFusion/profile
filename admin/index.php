<?php
/**
 * Entry point to administration functions for the Custom Profile plugin
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2019 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/** Import core glFusion libraries */
require_once '../../../lib-common.php';
/** Import authentication functions */
require_once '../../auth.inc.php';

// Make sure the plugin is installed and enabled
if (!in_array('profile', $_PLUGINS)) {
    COM_404();
}

// Only let admin users access this page
if (!SEC_hasRights('profile.admin')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the Profile Admin page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    COM_404();
}

// Import administration functions
USES_lib_admin();

$expected = array(
    'edit', 'savedef', 'deletedef', 'move', 'movelist', 'deletelist',
    'permreset', 'permresetconfirm', 'searchusers', 'dousersearch',
    'lists', 'editlist', 'savelist', 'cancellist',
    'mode',
);
$action = '';
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}
$view = isset($_GET['view']) ? $_GET['view'] : $action;
$id = isset($_POST['id']) ? $_POST['id'] :
        (isset($_GET['id']) ? $_GET['id'] : '');
$content = '';
$msg = '';
switch ($action) {
case 'permreset':
    $view = 'resetpermform';
    break;

case 'permresetconfirm':
    PRF_permReset();
    $content .= COM_showMessage('102', $_PRF_CONF['pi_name']);
    $view = 'listfields';
    break;

case 'move':
    // Reorder a profile definition
    $content .= PRF_moveRow('profile_def', 'id', $id, $actionval);
    break;

case 'movelist':
    $content .= PRF_moveRow('profile_lists', 'listid', $id, $actionval);
    $view = 'lists';
    break;

case 'edit':
    $view = 'editform';
    break;

case 'savedef':
    // Save or update a profile definition
    $F = Profile\Field::getInstance($_POST);
    $F->saveDef($_POST);
    $view = 'listfields';
    break;

case 'deletedef':
    // Delete a profile definition.  Also deletes user values.
    $id = (int)$id;
    list($name,$type) = DB_fetchArray(DB_query("SELECT name, type
            FROM {$_TABLES['profile_def']}
            WHERE id=$id"));
    if ($name != '') {
        // Static fields have no entry in the data table
        if ($type != 'static') {
            // In case defs get out of sync with data schema, don't fail here.
            DB_query("ALTER TABLE {$_TABLES['profile_data']} DROP `$name`", 1);
        }
    }

    // Check if the field is used in any lists.  Not an optimized query, to be
    // sure, but shouldn't happen too often.
    $res = DB_query("SELECT listid, fields FROM {$_TABLES['profile_lists']}
            WHERE fields like '%" . DB_escapeString($name) . "%'");
    while ($A = DB_fetchArray($res, false)) {
        $fields = @unserialize($A['fields']);
        if (is_array($fields)) {
            foreach ($fields as $fld_id => $fld_data) {
                if ($fld_data['dbfield'] == $name) {
                    unset($fields[$fld_id]);
                    $fields_str = @serialize($fields);
                    if ($fields_str) {
                        $sql = "UPDATE {$_TABLES['profile_lists']}
                            SET fields = '" . DB_escapeString($fields_str) .
                            "' WHERE listid = '{$A['listid']}'";
                        DB_query($sql);
                    }
                }
            }
        }
    }
    DB_delete($_TABLES['profile_def'], 'id', $id);
    break;

case 'deletelist':
    $listid = $actionval;
    if (!empty($listid)) {
        Profile\UserList::Delete($listid);
    }
    COM_refresh(PRF_ADMIN_URL . '/index.php?lists');
    break;

case 'savelist':
    if (isset($_POST['oldid']) && !empty($_POST['oldid'])) {
        $listid = $_POST['oldid'];
    } elseif (isset($_POST['listid']) && !empty($_POST['listid'])) {
        $listid = $_POST['listid'];
    }
    if (!empty($listid)) {
        $L = new Profile\UserList($listid);
        $L->Save($_POST);
    }
    COM_refresh(PRF_ADMIN_URL . '/index.php?lists');
    break;

case 'cancellist':
    $view = 'lists';
    break;

case 'dousersearch':
    $content .= Profile\Search::searchUsers($_POST);
    $view = 'none';     // We handled the content
    break;
}

// Select the page to display
switch ($view) {
case 'editform':
    // Edit a single definition
    $F = Profile\Field::getInstance($id);
    $content .= $F->Edit();
    break;

case 'lists':
    $content .= PRF_listLists();
    break;

case 'editlist':
    $L = new Profile\UserList($actionval);
    $content .= $L->Edit();
    break;

case 'resetpermform':
    $content .= PRF_permResetForm();
    break;

case 'none':
    // In case any modes create their own content
    break;

case 'searchusers':
    $content .= Profile\Search::searchUsersForm();
    break;

default:
    // Display the admin list
    $view = 'list';
    $content .= PRF_listFields();
    break;
}

$display = COM_siteHeader();
if ($msg != '') {
    $display .= COM_showMessage($msg, $_PRF_CONF['pi_name']);
}
$T = new Template(PRF_PI_PATH. 'templates/admin/');
$T->set_file('admin', 'index.thtml');
$T->set_var(array(
    'version'       => "{$LANG32[36]}: {$_PRF_CONF['pi_version']}",
    'menu'          => Profile\Menu::Admin($view),
    'page_content'  => $content,
    'pi_icon'       => plugin_geticon_profile(),
) );
$T->parse('output','admin');
$display .= $T->finish($T->get_var('output'));
$display .= COM_siteFooter();
echo $display;
exit;


/**
 * Uses lib-admin to list the list definitions.
 *
 * @return  string HTML for the list
 */
function PRF_listLists()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_PROFILE;

    $retval = '<p><a class="uk-button uk-button-success" href="' .
        PRF_ADMIN_URL . '/index.php?editlist=">' . $LANG_PROFILE['newlist'] .
        '</a></p>';

    $header_arr = array(
        array(
            'text' => $LANG_ADMIN['edit'],
            'field' => 'edit',
            'sort' => false,
            'align'=>'center',
        ),
        array(
            'text' => $LANG_PROFILE['orderby'],
            'field' => 'orderby',
            'sort' => false,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_PROFILE['listid'],
            'field' => 'listid',
            'sort' => true,
        ),
        array(
            'text' => $LANG_PROFILE['title'],
            'field' => 'title',
            'sort' => true,
        ),
        array(
            'text' => $LANG_ADMIN['delete'],
            'field' => 'delete',
            'sort' => false,
            'align' => 'center',
        ),
    );

    $defsort_arr = array(
        'field' => 'orderby',
        'direction' => 'asc',
    );

    $retval .= COM_startBlock(
        '', '',
        COM_getBlockTemplate('_admin_block', 'header')
    );

    $text_arr = array();
    $extra = array(
        'list_count' => DB_count($_TABLES['profile_lists']),
    );
    $query_arr = array(
        'table' => 'profile_lists',
        'sql' => "SELECT * FROM {$_TABLES['profile_lists']}",
        'query_fields' => array('title'),
        'default_filter' => ''
    );
    $form_arr = '';
    $retval .= ADMIN_list(
        'profile',
        'PRF_getField_list',
        $header_arr, $text_arr, $query_arr, $defsort_arr,
        '', $extra, '', $form_arr
    );
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));
    return $retval;
}


/**
 * Determine what to display in the admin list for each field.
 *
 * @param   string  $fieldname  Name of the field, from database
 * @param   mixed   $fieldvalue Value of the current field
 * @param   array   $A          Array of all name/field pairs
 * @param   array   $icon_arr   Array of system icons
 * @return  string              HTML for the field cell
 */
function PRF_getField_list($fieldname, $fieldvalue, $A, $icon_arr, $extra)
{
    global $_CONF, $LANG_ACCESS, $LANG_PROFILE, $LANG_ADMIN;

    $retval = '';

    switch($fieldname) {
    case 'edit':
        $retval =
            COM_createLink(
                '<i class="uk-icon uk-icon-edit"></i>',
                PRF_ADMIN_URL . '/index.php?editlist=' . $A['listid'],
                array(
                    'title' => $LANG_ADMIN['edit'],
                    'data-uk-tooltip' => '',
                )
            );
       break;

    case 'delete':
        $retval = COM_createLink(
                '<i class="uk-icon uk-icon-remove uk-text-danger"></i>',
                PRF_ADMIN_URL . '/index.php?deletelist=' . $A['listid'],
                array(
                    'title' => $LANG_ADMIN['delete'],
                    'data-uk-tooltip' => '',
                    'onclick' => "return confirm('{$LANG_PROFILE['q_conf_del']}');",
                )
            );
        break;

    case 'listid':
        $url = PRF_PI_URL . '/list.php?listid=' . $fieldvalue;
        $retval = COM_createLink($fieldvalue, $url,
            array('title'=>$url));
       break;

    case 'orderby':
        if ($fieldvalue > 10) {
            $retval = COM_createLink(
                '<i class="uk-icon uk-icon-arrow-up uk-icon-justify"></i>',
                PRF_ADMIN_URL . '/index.php?movelist=up&id=' . $A['listid']
            );
        } else {
            $retval = '<i class="uk-icon uk-icon-justify">&nbsp;</i>';
        }
        if ($fieldvalue < $extra['list_count'] * 10) {
            $retval .= COM_createLink(
                '<i class="uk-icon uk-icon-arrow-down uk-icon-justify"></i>',
                PRF_ADMIN_URL . '/index.php?movelist=down&id=' . $A['listid']
            );
        } else {
            $retval .= '<i class="uk-icon uk-icon-justify">&nbsp;</i>';
        }
        break;

    default:
        $retval = $fieldvalue;

    }

    return $retval;
}



/**
 * Uses lib-admin to list the profile definitions and allow updating.
 *
 * @return  string HTML for the list
 */
function PRF_listFields()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_PROFILE;

    $retval = '<p><a class="uk-button uk-button-success" href="' .
        PRF_ADMIN_URL . '/index.php?edit=x">' . $LANG_PROFILE['add_profile_item'] .
        '</a></p>';

    $header_arr = array(
        array(
            'text' => $LANG_ADMIN['edit'],
            'field' => 'edit',
            'sort' => false,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_PROFILE['orderby'],
            'field' => 'orderby',
            'sort' => true,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_PROFILE['name'],
            'field' => 'name',
            'sort' => true,
        ),
        array(
            'text' => $LANG_PROFILE['type'],
            'field' => 'type',
            'sort' => true,
        ),
        array(
            'text' => $LANG_PROFILE['enabled'],
            'field' => 'enabled',
            'sort' => true,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_PROFILE['public'],
            'field' => 'show_in_profile',
            'sort' => true,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_PROFILE['required'],
            'field' => 'required',
            'sort' => true,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_PROFILE['user_reg'],
            'field' => 'user_reg',
            'sort' => true,
            'align' => 'center',
        ),
        array(
            'text' => $LANG_ADMIN['delete'],
            'field' => 'delete',
            'sort' => false,
            'align' => 'center',
        ),
    );

    $defsort_arr = array(
        'field' => 'orderby',
        'direction' => 'asc',
    );

    $retval .= COM_startBlock(
        '', '',
        COM_getBlockTemplate('_admin_block', 'header')
    );

    $text_arr = array();
    $extra = array(
        'prf_count' => DB_count($_TABLES['profile_def']),
    );

    $query_arr = array(
        'table' => 'profile_def',
        'sql' => "SELECT * FROM {$_TABLES['profile_def']}",
        'query_fields' => array('name', 'type', 'value'),
        'default_filter' => '',
    );
    $form_arr = array();
    $retval .= ADMIN_list(
        'profile',
        'PRF_getField_profile',
        $header_arr, $text_arr, $query_arr, $defsort_arr,
        '', $extra, '', $form_arr
    );
    $retval .= COM_endBlock(COM_getBlockTemplate('_admin_block', 'footer'));

    return $retval;
}


/**
 * Determine what to display in the admin list for each field.
 *
 * @param   string  $fieldname  Name of the field, from database
 * @param   mixed   $fieldvalue Value of the current field
 * @param   array   $A          Array of all name/field pairs
 * @param   array   $icon_arr   Array of system icons
 * @return  string              HTML for the field cell
 */
function PRF_getField_profile($fieldname, $fieldvalue, $A, $icon_arr, $extra)
{
    global $_CONF, $LANG_ACCESS, $_PRF_CONF, $LANG_ADMIN, $LANG_PROFILE;

    $retval = '';

    switch($fieldname) {
    case 'username':
        $retval = COM_createLink(
            $fieldvalue,
            $_CONF['site_url'] . '/users.php?mode=profile&amp;uid=' . $A['uid']
        );
        break;

    case 'edit':
        $retval = COM_createLink(
            '<i class="uk-icon uk-icon-edit" data-uk-tooltip title="' . $LANG_ADMIN['edit'] . '"></i>',
            PRF_ADMIN_URL . '/index.php?edit=x&amp;id=' . $A['id']
        );
       break;

    case 'edituser':
        $retval = COM_createLink(
            '<i class="uk-icon uk-icon-edit" data-uk-tooltip title="' . $LANG_ADMIN['edit'] . '"></i>',
            "{$_CONF['site_url']}/admin/user.php?edit=x&amp;uid={$A['uid']}"
        );
        break;

    case 'delete':
        if (!$A['sys']) {
            $retval = COM_createLink(
                '<i class="uk-icon uk-icon-remove uk-text-danger"></i>',
                PRF_ADMIN_URL . '/index.php?deletedef=x&id=' . $A['id'],
                array(
                    'onclick' => "return confirm('{$LANG_PROFILE['q_conf_del']}');",
                    'title' => $LANG_ADMIN['delete'],
                    'data-uk-tooltip' => '',
                )
            );
        }
        break;

    case 'enabled':
    case 'required':
    case 'user_reg':
    case 'show_in_profile':
        if ($A[$fieldname] == 1) {
            $chk = PRF_CHECKED;
            $enabled = 1;
        } else {
            $chk = '';
            $enabled = 0;
        }
        $retval =
                "<input name=\"{$fieldname}_{$A['id']}\" " .
                "type=\"checkbox\" $chk " .
                'data-uk-tooltip title="' . $LANG_PROFILE['click_to_change'] . '" ' .
                "onclick='PRFtoggleEnabled(this, \"{$A['id']}\", \"{$fieldname}\");' ".
                ">\n";
    break;

    case 'public':
        if ($A['perm_members'] == 2 && $A['perm_anon'] == 2) {
            $chk = PRF_CHECKED;
        } else {
            $chk = '';
        }
        $retval =
                "<input name=\"{$fieldname}_{$A['id']}\" " .
                "type=\"checkbox\" $chk " .
                "onclick='PRFtoggleEnabled(this, \"{$A['id']}\", \"{$fieldname}\");' ".
                ">\n";
        break;

    case 'id':
        return '';
        break;

    case 'orderby':
        if ($fieldvalue > 10) {
            $retval = COM_createLink(
                '<i class="uk-icon uk-icon-arrow-up uk-icon-justify"></i>',
                PRF_ADMIN_URL . '/index.php?move=up&id=' . $A['id']
            );
        } else {
            $retval = '<i class="uk-icon uk-icon-justify">&nbsp;</i>';
        }
        if ($fieldvalue < $extra['prf_count'] * 10) {
            $retval .= COM_createLink(
                '<i class="uk-icon uk-icon-arrow-down uk-icon-justify"></i>',
                PRF_ADMIN_URL . '/index.php?move=down&id=' . $A['id']
            );
        } else {
            $retval .= '<i class="uk-icon uk-icon-justify">&nbsp;</i>';
        }
        break;

    default:
        $retval = $fieldvalue;
    }

    return $retval;
}


/**
 * Saves the current form entries as a new or existing record.
 *
 * @param   array   $A          Array of all values from the submitted form
 */
function PRF_saveDefs($A)
{
    global $_TABLES;

    // Sanitize the entry ID.  Zero = new entry
    $id = isset($A['id']) ? (int)$A['id'] : 0;

    // Sanitize the name, especially make sure there are no spaces and that
    // the name starts with "prf_", unless it's a system variable
    // These should be done by template JS, but just in case...
    if (strpos($A['name'], 'prf_') !== 0 && $A['is_sys'] != 1) {
        $A['name'] = 'prf_' . $A['name'];
    }
    $A['name'] = COM_sanitizeID($A['name'], false);
    if (empty($A['name']) || empty($A['type'])) {
        return '104';
    }

    // For an existing record, read from the db to make sure it exists.
    // Also, if it's a "system" record, then grab the fields that may not
    // be submitted with the form.
    if ($id > 0) {
        $sql = "SELECT name, type, sys
                FROM {$_TABLES['profile_def']}
                WHERE id = $id";
        $rec = DB_fetcharray(DB_query($sql, 1), false);
        if (empty($rec)) {
            return '103';
        }

        if ($rec['sys'] == 1) {
            // override any possible user-entered changes
            $A['type'] = $rec['type'];
            $A['name'] = $rec['name'];
        }
    } else {
        $cnt = DB_count($_TABLES['profile_def'], 'name', $A['name']);
        if ($cnt > 0) return '103';
    }

    // Put this field at the end of the line by default
    if (empty($A['orderby']))
        $A['orderby'] = 65535;

    // Set the size and maxlength to reasonable values
    $A['maxlength'] = min((int)$A['maxlength'], 255);
    $A['size'] = min((int)$A['size'], 80);

    // Get the permissions into db-storable values
    $perms = SEC_getPermissionValues($_POST['perm_owner'],
                $_POST['perm_group'], $_POST['perm_members'],
                $_POST['perm_anon']);

    // Set the options and default values according to the data type
    $A['options'] = '';
    $options = array(
        'default' => isset($A['defvalue']) ? trim($A['defvalue']) : '',
    );
    $sql_type = 'VARCHAR(255)';     // Default SQL column type

    // Mask and Visible Mask may exist for any field type, but are set
    // only if they are actually defined.
    if (isset($A['mask']) && $A['mask'] != '') {
        $options['mask'] = trim($A['mask']);
    }
    if (isset($A['vismask']) && $A['vismask'] != '') {
        $options['vismask'] = trim($A['vismask']);
    }
    if (isset($A['spancols']) && $A['spancols'] == '1') {
        $options['spancols'] = 1;
    }
    $user_reg = isset($A['user_reg']) && $A['user_reg'] == '1' ? 1 : 0;
    $show_in_profile = isset($A['show_in_profile']) &&
             $A['show_in_profile'] == '1' ? 1 : 0;
    $required = isset($A['required']) && $A['required'] == '1' ? 1 : 0;
    $enabled  = isset($A['enabled']) && $A['enabled'] == '1' ? 1 : 0;

    if (isset($A['help_text']) && $A['help_text'] != '') {
        $options['help_text'] = trim($A['help_text']);
    }

    // True if the data table should be updated.
    if ($A['oldtype'] != $A['type'] || $A['name'] != $A['oldname']) {
        $alter_table = true;
    } else {
        $alter_table = false;
    }

    switch ($A['type']) {
    case 'text':
        $options['size'] = (int)$_POST['size'];
        $options['maxlength'] = (int)$_POST['maxlength'];
        $options['autogen'] = isset($A['autogen']) && $A['autogen'] == '1' ?
            1 : 0;
        $sql_type = 'VARCHAR(255)';
        break;

    case 'textarea':
        $options['rows'] = (int)$_POST['rows'];
        $options['cols'] = (int)$_POST['cols'];
        $sql_type = 'TEXT';
        break;

    case 'checkbox':
        // For checkboxes, set the value to "1" automatically
        $options['value'] = '1';
        // Different default value for checkboxes
        $options['default'] = isset($A['chkdefvalue']) ? 1 : 0;
        $sql_type = 'TINYINT(1) UNSIGNED NOT NULL DEFAULT ' . $options['default'];
        break;

    case 'date':
        //$options['showtime'] =
        //        (isset($_POST['showtime']) && $_POST['showtime'] == 1 ? 1 : 0);
        switch ($_POST['timeformat']) {
        case '12':
        case '24':
            $options['timeformat'] = $_POST['timeformat'];
            $sql_type = 'DATETIME';
            break;
        default:
            // empty timeformat = date only
            $options['timeformat'] = '';
            $sql_type = 'DATE';
            break;
        }
        //$options['timeformat'] = $_POST['timeformat'] == '24' ? '24' : '12';
        $options['format'] = isset($_POST['format']) ? $_POST['format'] :
                    $_PRF_CONF['def_dt_format'];
        $options['input_format'] = isset($_POST['dt_input_format']) ?
                $_POST['dt_input_format'] : $_PRF_CONF['date_format'];
        //$sql_type = $options['showtime'] ? 'DATETIME' : 'DATE';
        break;

    case 'select':
    case 'radio':
        $newvals = array();
        if (!isset($A['select_values']) || !is_array($A['select_values'])) {
            $A['select_values'] = array();
        }
        foreach ($A['select_values'] as $val) {
            if (!empty($val)) {
                $newvals[] = $val;
            }
        }
        $options['default'] = '';
        if (isset($A['sel_default'])) {
            $default = (int)$A['sel_default'];
            if (isset($A['select_values'][$default])) {
                $options['default'] = $A['select_values'][$default];
            }
        }
        //$options['values'] = serialize($newvals);
        $options['values'] = $newvals;
        $sql_type = 'TEXT';
        break;

    case 'multicheck':
        $newvals = array();
        $defaults = array();
        if (!isset($A['multichk_values']) || !is_array($A['multichk_values'])) {
            $A['multichk_values'] = array();
        }
        if (!isset($A['multichk_default']) || !is_array($A['multichk_default'])) {
            $A['multichk_default'] = array();
        }
        foreach ($A['multichk_values'] as $key=>$val) {
            if (empty($val)) continue;  // empty value deletes selection
            $newvals[$key] = $val;
            if (in_array($val, $A['multichk_default'])) $defaults[$key] = $val;
        }
        // Set options as arrays; they'll be serialized later
        $options['values'] = $newvals;
        $options['default'] = $defaults;
        $sql_type = 'TEXT';
        break;

    case 'account':
        $sql_type = 'MEDIUMINT(8) DEFAULT 0';
        break;

    case 'static':
        $options['value'] = trim($A['static_val']);
        if ($alter_table && $id > 0) {
            // If we're changing the table type to static, we need to drop the
            // old column.  Otherwise, there's nothing to do for a static field.
            DB_query("ALTER TABLE {$_TABLES['profile_data']}
                    DROP `" . DB_escapeString($A['oldname']) . '`');
        }
        $alter_table = false;   // alteration already done
        break;

    }

    // Now alter the data table.  We do this first in case there's any SQL
    // error so we don't end up with a mismatch between the definitions and
    // the values.
    if ($alter_table) {
        $sql = "ALTER TABLE {$_TABLES['profile_data']} ";
        if ($id == 0) {
            $sql .= "ADD {$A['name']} $sql_type";
        } else {
            $sql .= "CHANGE {$A['oldname']} {$A['name']} $sql_type";
        }
        //echo $sql;die;
        DB_query($sql, 1);
        if (DB_error()) {
            return '103';
        }
    }

    // This serializes any options set
    $A['options'] = PRF_setOpts($options);

    // Make all entries SQL-safe
    $A = array_map('PRF_escape_string', $A);

    if ($id > 0) {
        // Existing record, perform update
        $sql1 = "UPDATE {$_TABLES['profile_def']} SET ";
        $sql3 = " WHERE id = $id";
    } else {
        // New record
        $sql1 = "INSERT INTO {$_TABLES['profile_def']} SET ";
        $sql3 = '';
    }
    $sql2 = "orderby = '" . (int)$A['orderby'] . "',
            name = '{$A['name']}',
            type = '{$A['type']}',
            enabled = '$enabled',
            required = '$required',
            user_reg = '$user_reg',
            show_in_profile= '$show_in_profile',
            prompt = '{$A['prompt']}',
            options = '{$A['options']}',
            group_id = '" . (int)$A['group_id'] . "',
            perm_owner = {$perms[0]},
            perm_group = {$perms[1]},
            perm_members = {$perms[2]},
            perm_anon = {$perms[3]}";
    $sql = $sql1 . $sql2 . $sql3;
    //echo $sql;die;
    DB_query($sql);
    if (DB_error()) {
        return '103';
    }
    PRF_reorderDef('profile_def');
    return '';
}


/**
 * Move a profile definition up or down the admin list.
 *
 * @param   string  $table      Name of table in $_TABLES array.
 * @param   string  $id_fld     Name of "ID" field.
 * @param   mixed   $id_val     Record ID to move.
 * @param   string  $where      Direction to move ('up' or 'down').
 */
function PRF_moveRow($table, $id_fld, $id_val, $where)
{
    global $_CONF, $_TABLES, $LANG21;

    $retval = '';

    $id_fld = DB_escapeString($id_fld);
    $id_val = DB_escapeString($id_val);

    // only if the id exists
    if (DB_count($_TABLES[$table], $id_fld, $id_val) != 1) {
        return '';
    }

    switch ($where) {
    case 'up':
        $sql = "UPDATE {$_TABLES[$table]}
                SET orderby = orderby-11
                WHERE $id_fld = '$id_val'";
        break;

    case 'down':
        $sql = "UPDATE {$_TABLES[$table]}
                SET orderby = orderby+11
                WHERE $id_fld = '$id_val'";
        break;
    }
    //echo $sql;die;
    DB_query($sql);
    PRF_reorderDef($table, $id_fld);
}


/**
 * Present a form verifying that all permissions should be reset.
 *
 * @since   version 1.0.2
 */
function PRF_permResetForm()
{
    $T = new Template(PRF_PI_PATH . 'templates/admin');
    $T->set_file('bulkperms', 'bulkperms.thtml');
    $T->parse('output', 'bulkperms');

    return $T->finish($T->get_var('output'));
}


/**
 * Reset all field permissions to the configured defaults.
 *
 * @since   version 1.0.2
 */
function PRF_permReset()
{
    global $_TABLES, $_PRF_CONF;

    $sql = "UPDATE {$_TABLES['profile_def']} SET
                perm_owner = {$_PRF_CONF['default_permissions'][0]},
                perm_group = {$_PRF_CONF['default_permissions'][1]},
                perm_members = {$_PRF_CONF['default_permissions'][2]},
                perm_anon = {$_PRF_CONF['default_permissions'][3]}";
    //echo $sql;die;
    DB_query($sql);
}

?>
