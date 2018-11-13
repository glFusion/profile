<?php
/**
 * Entry point to administration functions for the Custom Profile plugin
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2012 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.2.0
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
USES_profile_functions();

$expected = array('edit', 'savedef', 'deletedef', 'move',
        'movelist', 'deletelist',
            'permreset', 'permresetconfirm',
            'searchusers', 'dousersearch',
            'lists', 'editlist', 'savelist', 'cancellist',
            'mode');
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

case 'deletelist':
    DB_delete($_TABLES['profile_lists'], 'listid', $actionval);
    $view = 'lists';
    break;

case 'savedef':
    // Save or update a profile definition
    $F = \Profile\Field::getInstance($_POST);
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

case 'savelist':
    if (isset($_POST['oldid']) && !empty($_POST['oldid'])) {
        $listid = $_POST['oldid'];
    } elseif (isset($_POST['listid']) && !empty($_POST['listid'])) {
        $listid = $_POST['listid'];
    }
    if (!empty($listid)) {
        $L = new \Profile\UserList($listid);
        $L->Save($_POST);
    }
    $view = 'lists';
    break;

case 'cancellist':
    $view = 'lists';
    break;

case 'dousersearch':
    //$content .= PRF_adminMenu('searchusers');
    $content .= PRF_searchUsers($_POST);
    $view = 'none';     // We handled the content
    break;
}

// Select the page to display
switch ($view) {
case 'editform':
    // Edit a single definition
    $F = \Profile\Field::getInstance($id);
    $content .= $F->Edit();
    break;

case 'lists':
    $content .= PRF_listLists();
    break;

case 'editlist':
    //$content .= PRF_adminMenu('list_edit_help');
    $L = new \Profile\UserList($actionval);
    $content .= $L->Edit();
    break;

case 'resetpermform':
    $content .= PRF_permResetForm();
    break;

case 'none':
    // In case any modes create their own content
    break;

case 'searchusers':
//    $content .= PRF_adminMenu('searchusers', 'hlp_search_users');
    $content .= PRF_searchUsersForm();
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
    'menu'          => PRF_adminMenu($view),
    'page_content'  => $content,
) );
$T->parse('output','admin');
//$display .= PRF_adminMenu($view);
//$display .= $content;
$display .= $T->finish($T->get_var('output'));
$display .= COM_siteFooter();
echo $display;
exit;


/**
 * Displays a form for editing a profile definition.
 *
 * @deprecated version 1.2.0
 * @param   integer $id     Database ID of item to edit, 0 for new item
 * @return  string          HTML for the form
 */
function X_PRF_adminForm($id)
{
    global $_TABLES, $_CONF, $LANG_PROFILE, $LANG_ADMIN, $_PRF_CONF;

    $retval = '';

    $id = (int)$id;
    $F = \Profile\Field::getInstance($id);
    $T = PRF_getTemplate('profile', 'editform', 'admin');
    /*if ($id > 0) {
        // Existing item, retrieve it
        $sql = "SELECT *
                FROM {$_TABLES['profile_def']}
                WHERE id='$id'";
        $result = DB_query($sql);
        if (DB_numRows($result) != 1) return '';
        $A = DB_fetchArray($result, false);

        $is_sys = (int)$A['sys'];

        // return nothing if this is a system item and can't be edited
        if ($is_sys) {
            $T->set_var('is_sys', 'true');
            //echo COM_refresh(PRF_ADMIN_URL . '/index.php?msg=101');
            //exit;
        }

        $T->set_var('editing', 'true');
    } else {
        // New item, create empty array
        $A = array(
            'group_id'      => $_PRF_CONF['defgroup'],
            'perm_owner'    => $_PRF_CONF['default_permissions'][0],
            'perm_group'    => $_PRF_CONF['default_permissions'][1],
            'perm_members'  => $_PRF_CONF['default_permissions'][2],
            'perm_anon'     => $_PRF_CONF['default_permissions'][3],
            'enabled'       => 1,
            'show_in_profile' => 1,
            'type'          => 'text',    // So there's a default value
        );
        $T->set_var('editing', '');
    }

    $F = \Profile\Field::getInstance($A);
    */

    // Instantiate a class to handle default values
    /*$classname = 'prf' . $A['type'];
    if (class_exists($classname)) {
        $F = new $classname($A['name'], $A['value']);
    } else {
        $F = new prfText($A['name'], $A['value']);
    }*/

    // Create the selection list for the "Position After" dropdown.
    // Include all options *except* the current one, and select the last one
    // for new items, the "no change" option for existing items.
    $sql = "SELECT orderby, name
            FROM {$_TABLES['profile_def']}
            WHERE id <> '$id'
            ORDER BY orderby ASC";
    $res1 = DB_query($sql);
    $orderby_options = '';
    $cnt = DB_numRows($res1);
    for ($i = 1; $i <= $cnt; $i++) {
        $B = DB_fetchArray($res1, false);
        $orderby = (int)$B['orderby'] + 1;
        if ($id == 0) {
            $sel = $i == $cnt ? PRF_SELECTED : '';
        } else {
            $sel = '';
        }
        $orderby_options .= "<option value=\"$orderby\" $sel>{$B['name']}</option>\n";
    }

    // Create the "Field Type" dropdown.  This is disabled for system items
    // in the template
    $type_options = '';
    foreach ($LANG_PROFILE['fld_types'] as $option => $opt_desc) {
        $sel = $A['type'] == $option ? PRF_SELECTED : '';
        $type_options .= "<option value=\"$option\" $sel>$opt_desc</option>\n";
    }

    // Populate the options specific to certain field types
    $opts = isset($A['options']) ? PRF_getOpts($A['options']) : array();

    // Set up the field-specific inputs for value selection or default value
    $T->set_var($F->editValues());

    if (!isset($opts['input_format'])) $opts['input_format'] = '';
    $T->set_var(array(
        'id'        => isset($A['id']) ? $A['id'] : 0,
        'name'      => isset($A['name']) ? $A['name'] : '',
        'type'      => isset($A['type']) ? $A['type'] : 'text',
        'oldtype'   => isset($A['type']) ? $A['type'] : 'text',
        'prompt'    => isset($A['prompt']) ? $A['prompt'] : '',
        'ena_chk'   => (isset($A['enabled']) && $A['enabled'] == 1) ? PRF_CHECKED : '',
        'user_reg_chk' => (isset($A['user_reg']) && $A['user_reg'] == 1) ? PRF_CHECKED : '',
        'req_chk'   => (isset($A['required']) && $A['required'] == 1) ? PRF_CHECKED : '',
        'in_prf_chk' => (isset($A['show_in_profile']) && $A['show_in_profile'] == 1) ? PRF_CHECKED : '',
        'spancols_chk' => (isset($opts['spancols']) && $opts['spancols'] == 1) ? PRF_CHECKED : '',
        'orderby'   => $A['orderby'],
        'format'    => isset($opts['format']) ? $opts['format'] : '',
        'input_format' => \Profile\Field\date::DateFormatSelect($opts['input_format']),
        'doc_url'   => PRF_getDocURL('profile_def.html'),
        'mask'      => isset($opts['mask']) ? $opts['mask'] : '',
        'vismask'   => isset($opts['vismask']) ? $opts['vismask'] : '',
        'autogen_chk' => (isset($opts['autogen']) && $opts['autogen'] == 1) ?
                        PRF_CHECKED : '',
        'stripmask_chk' => (isset($opts['stripmask']) && $opts['stripmask']  == 1) ?
                        PRF_CHECKED : '',
        'group_dropdown' => SEC_getGroupDropdown($A['group_id'], 3),
        'permissions' => PRF_getPermissionsHTML(
                $A['perm_owner'],$A['perm_group'],
                $A['perm_members'],$A['perm_anon']),
        'plugin_options' => COM_optionList($_TABLES['plugins'],
                        'pi_name,pi_name', $A['plugin'], 0, 'pi_enabled=1'),
        'help_text' => isset($opts['help_text']) ?
                htmlspecialchars($opts['help_text']) : '',
        'dt_input_format' => \Profile\Field\date::DateFormatSelect($opts['input_format']),
        'orderby_selection' => $orderby_options,
        'type_options' => $type_options,
    ) );
    $T->parse('output', 'editform');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}


/**
 * Uses lib-admin to list the list definitions.
 *
 * @return  string HTML for the list
 */
function PRF_listLists()
{
    global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_PROFILE;

    $retval = '';

    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'],
            'field' => 'edit',
            'sort' => false,
            'align'=>'center',
        ),
        array('text' => $LANG_PROFILE['orderby'],
            'field' => 'orderby',
            'sort' => false,
            'align' => 'center',
        ),
        array('text' => $LANG_PROFILE['listid'],
            'field' => 'listid',
            'sort' => true,
        ),
        array('text' => $LANG_PROFILE['title'],
            'field' => 'title',
            'sort' => true,
        ),
        array('text' => $LANG_ADMIN['delete'],
            'field' => 'delete',
            'sort' => false,
            'align' => 'center',
        ),
    );

    $defsort_arr = array('field' => 'orderby', 'direction' => 'asc');

    $retval .= COM_startBlock('', '',
                              COM_getBlockTemplate('_admin_block', 'header'));

    // Create the admin menu
    //$retval .= PRF_adminMenu('list_list_help');

    $text_arr = array();

    $query_arr = array('table' => 'profile_lists',
        'sql' => "SELECT *
               FROM
                    {$_TABLES['profile_lists']}",
        'query_fields' => array('title'),
        'default_filter' => ''
    );
    $form_arr = '';
    $retval .= ADMIN_list('profile', 'PRF_getField_list', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);
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
function PRF_getField_list($fieldname, $fieldvalue, $A, $icon_arr)
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
                '<i class="uk-icon uk-icon-trash-o prf-icon-danger"></i>',
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
        $retval = COM_createLink(
                '<img src="' . PRF_PI_URL .
                '/images/up.png" height="16" width="16" border="0" />',
                PRF_ADMIN_URL . '/index.php?movelist=up&id=' . $A['listid'],
                array(
                    'title' => $LANG_PROFILE['move_up'],
                    'data-uk-tooltip' => '',
                )
            ) .
            COM_createLink(
                '<img src="' . PRF_PI_URL .
                    '/images/down.png" height="16" width="16" border="0" />',
                PRF_ADMIN_URL . '/index.php?movelist=down&id=' . $A['listid'],
                array(
                    'title' => $LANG_PROFILE['move_dn'],
                    'data-uk-tooltip' => '',
                )
            );
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

    $retval = '';

    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'], 'field' => 'edit',
                'sort' => false, 'align' => 'center'),
        array('text' => $LANG_PROFILE['orderby'], 'field' => 'orderby',
                'sort' => true, 'align' => 'center'),
        array('text' => $LANG_PROFILE['name'], 'field' => 'name',
                'sort' => true),
        array('text' => $LANG_PROFILE['type'], 'field' => 'type',
                'sort' => true),
        array('text' => $LANG_PROFILE['enabled'], 'field' => 'enabled',
                'sort' => true, 'align' => 'center'),
        array('text' => $LANG_PROFILE['required'], 'field' => 'required',
                'sort' => true, 'align' => 'center'),
        array('text' => $LANG_PROFILE['user_reg'], 'field' => 'user_reg',
                'sort' => true, 'align' => 'center'),
        //array('text' => $LANG_PROFILE['readonly'], 'field' => 'readonly',
        //        'sort' => false),
        array('text' => $LANG_ADMIN['delete'], 'field' => 'delete',
                'sort' => false, 'align' => 'center'),
    );

    $defsort_arr = array('field' => 'orderby', 'direction' => 'asc');

    $retval .= COM_startBlock('', '',
                              COM_getBlockTemplate('_admin_block', 'header'));

    // Create the admin menu
    //$retval .= PRF_adminMenu();

    $text_arr = array();

    $query_arr = array('table' => 'profile_def',
        'sql' => "SELECT * FROM {$_TABLES['profile_def']}",
        'query_fields' => array('name', 'type', 'value'),
        'default_filter' => '',
    );
    $form_arr = array();
    $retval .= ADMIN_list('profile', 'PRF_getField_profile', $header_arr,
                    $text_arr, $query_arr, $defsort_arr, '', '', '', $form_arr);
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
function PRF_getField_profile($fieldname, $fieldvalue, $A, $icon_arr)
{
    global $_CONF, $LANG_ACCESS, $_PRF_CONF, $LANG_ADMIN, $LANG_PROFILE;

    $retval = '';

    switch($fieldname) {
    case 'username':
        $retval = COM_createLink($fieldvalue, $_CONF['site_url'] .
                '/users.php?mode=profile&amp;uid=' . $A['uid']);
        break;

    case 'edit':
        $retval = COM_createLink('<i class="' . $_PRF_CONF['_iconset'] .
                '-edit prf-icon-info" data-uk-tooltip title="' . $LANG_ADMIN['edit'] . '"></i>',
                PRF_ADMIN_URL . '/index.php?edit=x&amp;id=' . $A['id']);
       break;

    case 'edituser':
        $retval = COM_createLink($icon_arr['edit'],
                "{$_CONF['site_url']}/admin/user.php?edit=x&amp;uid={$A['uid']}");
        break;

    case 'delete':
        if (!$A['sys']) {
            $retval = COM_createLink(
                '<i class="' . $_PRF_CONF['_iconset'] . '-trash-o prf-icon-danger" ' .
                    "onclick=\"return confirm('{$LANG_PROFILE['q_conf_del']}');\"" .
                    'title="' . $LANG_ADMIN['delete'] . '" data-uk-tooltip></i>',
                PRF_ADMIN_URL . '/index.php?deletedef=x&id=' . $A['id']
            );
        }
        break;

    case 'enabled':
    case 'required':
    case 'user_reg':
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
        $retval = COM_createLink(
                '<img src="' . PRF_PI_URL .
                    '/images/up.png" height="16" width="16" border="0" ' .
                    'data-uk-tooltip title="' . $LANG_PROFILE['move_up'] . '"/>',
                PRF_ADMIN_URL . '/index.php?move=up&id=' . $A['id']
            ) .
            COM_createLink(
                '<img src="' . PRF_PI_URL .
                    '/images/down.png" height="16" width="16" border="0" ' .
                    'data-uk-tooltip title="' . $LANG_PROFILE['move_dn'] . '"/>',
                PRF_ADMIN_URL . '/index.php?move=down&id=' . $A['id']
            );
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
        if (!isset($A['selvalues']) || !is_array($A['selvalues'])) {
            $A['selvalues'] = array();
        }
        foreach ($A['selvalues'] as $val) {
            if (!empty($val)) {
                $newvals[] = $val;
            }
        }
        $options['default'] = '';
        if (isset($A['sel_default'])) {
            $default = (int)$A['sel_default'];
            if (isset($A['selvalues'][$default])) {
                $options['default'] = $A['selvalues'][$default];
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
 * Create the admin menu at the top of the list and form pages.
 *
 * @param   string  $page   Current page key
 * @return  string      HTML for admin menu section
 */
function PRF_adminMenu($page='list')
{
    global $_CONF, $LANG_PROFILE, $_PRF_CONF, $LANG01;

    $menu_arr = array ();
    if ($page == '')
        $page = 'list';

    $help_text = isset($LANG_PROFILE['help'][$page]) ? $LANG_PROFILE['help'][$page] : '';

    if ($page == 'list') {
        $menu_arr[] = array('url' => PRF_ADMIN_URL . '/index.php?edit=x',
            'text' => '<span class="prfNewAdminItem">' .
                $LANG_PROFILE['add_profile_item'] . '</span>');
    } else {
        $menu_arr[] = array('url' => PRF_ADMIN_URL . '/index.php',
            'text' => $LANG_PROFILE['list_profiles']);
    }

    if ($page == 'lists') {
        $menu_arr[] = array('url' => PRF_ADMIN_URL . '/index.php?editlist=',
            'text' => '<span class="prfNewAdminItem">' .
                $LANG_PROFILE['newlist'] . '</span>');
    } else {
        $menu_arr[] = array('url' => PRF_ADMIN_URL . '/index.php?lists=x',
            'text' => $LANG_PROFILE['lists']);
    }

    $menu_arr[] = array('url' => PRF_ADMIN_URL . '/index.php?permreset=x',
            'text' => $LANG_PROFILE['reset_perms']);

    $menu_arr[] = array('url' => PRF_ADMIN_URL . '/index.php?searchusers=x',
            'text' => $LANG_PROFILE['search_users']);

    $menu_arr[] = array('url' => $_CONF['site_admin_url'],
            'text' => $LANG01[53]);

    $retval = ADMIN_createMenu($menu_arr, $help_text,
                    plugin_geticon_profile());
    return $retval;
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

    //$retval = PRF_adminMenu('', 'hlp_reset_perms');
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


/**
 * Create a form to collect search parameters for users.
 *
 * @since   version 1.1.0
 * @return  string      HTML for form
 */
function PRF_searchUsersForm()
{
    global $_TABLES, $LANG_PROFILE;

    $T = new Template(PRF_PI_PATH . 'templates/admin');
    $T->set_file('searchform', 'search.thtml');

    $sql = "SELECT *
            FROM {$_TABLES['profile_def']}
            WHERE enabled = 1
            AND type <> 'static' " .
            COM_getPermSQL('AND', 0, 2) .
            ' ORDER BY orderby ASC';
    $res = DB_query($sql);
    while ($A = DB_fetchArray($res, false)) {
        $F = \Profile\Field::getInstance($A);
        $T->set_block('searchform', 'FldRow', 'frow');
        $T->set_var(array(
            'fld_prompt'    => $F->prompt,
            'fld_name'      => $F->name,
            'fld_input'     => $F->searchFormOpts(),
            'fld_empty'     => true,
        ) );
        $T->parse('frow', 'FldRow', true);
    }

    $T->parse('output', 'searchform');
    return $T->finish($T->get_var('output'));
}


/**
 * Search for users based on the parameters entered in PRF_searchUserForm().
 *
 * @since   version 1.1.0
 * @param   array   $vals   Search values (e.g. $_POST)
 * @return  string          HTML  for user list
 */
function PRF_searchUsers($vals)
{
    global $_TABLES, $LANG_ADMIN, $LANG_PROFILE;

    $sql = "SELECT * FROM {$_TABLES['profile_def']}
            WHERE enabled=1 " . COM_getPermSQL('AND', 0, 2);
    $res = DB_query($sql);
    $fields = array();
    while ($A = DB_fetchArray($res, false)) {
        if ($A['type'] != 'static') {
            $fields[$A['name']] = $A;
            $sql_flds[] = '`data`.`' . $A['name'] . '`';
        }
    }
    $sql_fldnames = implode(',', $sql_flds);

    $flds = array();
    foreach ($fields as $f_name=>$fld) {
        if (!isset($_POST[$f_name]) || $_POST[$f_name] == '-1') continue;   // signifies "any"
        $F = \Profile\Field::getInstance($fld);
        $f_name = DB_escapeString($f_name);
        switch($F->type) {
        case 'Xdate':
            if (isset($_POST['empty'][$f_name])) {
                $flds[] = "(`data`.`$f_name` = '' OR `data`.`$f_name` IS NULL OR `data`.`$f_name` LIKE '0000-00-00%')";
            } else {
                $mods = array('<', '<=', '>', '>=', '=', '<>');
                $value = sprintf('%04d-%02d-%02d', $_POST[$f_name . '_year'],
                    $_POST[$f_name . '_month'], $_POST[$f_name . '_day']);
                if ($value == '0000-00-00') continue;
                if (!isset($_POST[$f_name . '_mod'])) {
                    $flds[] = "`data`.`$f_name` LIKE '%$value%'";
                } else {
                    $mod = in_array($_POST[$f_name . '_mod'], $mods) ? $_POST[$f_name . '_mod'] : '=';
                    $flds[] = "`data`.`$f_name` $mod '$value'";
                }
            }
            break;
        default:
            $x = $F->createSearchSQL($_POST);
            if (!empty($x)) {
                $flds[] = $x;
            }
/*            $value = DB_escapeString($_POST[$f_name]);
            if (isset($_POST['empty'][$f_name])) {
                $flds[] = "(`data`.`$f_name` = '' OR `data`.`$f_name` IS NULL)";
            } elseif ($_POST[$f_name] !== '') {
                $flds[] = "`data`.`$f_name` like '%{$value}%'";
            }*/
            break;
        }
    }

    if (is_array($flds) && !empty($flds))
        $fld_sql = implode(' OR ', $flds);
    else
        $fld_sql = ' 1 = 1 ';

    $sql = "SELECT u.*, $sql_fldnames
            FROM {$_TABLES['users']} u
            LEFT JOIN {$_TABLES['profile_data']} data
            ON u.uid = data.puid
            WHERE ($fld_sql)";
    //echo $sql;die;
    $res = DB_query($sql);
    if (!$res || DB_error()) {
        return COM_showMessageText($LANG_PROFILE['query_error']);
    }

    $retval = '';
    $header_arr = array(
        array('text' => $LANG_ADMIN['edit'], 'field' => 'edituser', 'sort' => false),
        array('text' => 'Username', 'field' => 'username'),
        array('text' => 'Fullname', 'field' => 'fullname', 'sort' => true),
    );

    $data_arr = array();
    $text_arr = array();
    while ($A = DB_fetchArray($res, false)) {
        $data_arr[] = $A;
    }
    $retval = ADMIN_simpleList('PRF_getField_profile', $header_arr, $text_arr,
            $data_arr, '', '', '');
    return $retval;
}

?>
