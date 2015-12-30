<?php
/**
*   Plugin-specific functions for the profile plugin
*   Load by calling USES_profile_functions()
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2015 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.4
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


/**
*   Displays a form for editing profile data.
*
*   @param  string  $type   'edit' or 'registration'
*   @param  integer $uid    User ID whose profile is being edited
*   @return string          HTML for the form
*/
function PRF_editForm($type = 'edit', $uid = 0, $form_id='profileform')
{
    global $_CONF, $_USER, $_TABLES, $LANG_PROFILE, $_SYSTEM;

    $T = new Template(PRF_PI_PATH . 'templates');

    // Choose the correct template file based on the glFusion version
    // and type of form needed
    switch ($type) {
    case 'inline':
        $template_file = 'profile_inlineform.thtml';
        $access_required = 2;
        break;
    case 'registration':
        $template_file = 'profile_registration.thtml';
        // Set no access level to profile defs since this is an anon user.
        // Otherwise, they're not allowed to even read the definitions.
        $access_required = 0;
        break;
    default:
        // For anything but new registrations, the current user needs
        // at least read privileges.
        $access_required = 2;
        $template_file = 'profile_usersettings.thtml';
        break;
    }

    $A = PRF_getDefs($uid, '', $access_required);
    $T->set_file('editform', $template_file);
    $T->set_var(array(
        'uid'       => $uid,
        'form_id'   => $form_id,
        'have_jquery' => $_SYSTEM['disable_jquery'] ? '' : 'true',
    ) );

    // Flag to make sure calendar javascript is added only once.  It's
    // only added if there's at least one calendar field.
    $T->set_block('editform', 'QueueRow', 'qrow');
    foreach ($A as $fldname => $data) {

        // Could do this in SQL, but why complicate PRF_getDefs()?
        // If the field is not required and is not to appear on the signup
        // form, then skip it.  If it is a registration field, override
        // the owner permission to make sure the user can edit it.
        if ($type == 'registration') {
            if (($data->user_reg == 0 && $data->required == 0))
                continue;
            $data->perm_owner = 3;
        }

        // If the field is required, set the fValidator class.
        // This doesn't work right for dates (yet).
        $fValidator_opts = array();     // Start with a clean array
        if ($data->required == 1) {
            if ($data->type != 'checkbox' && $data->type != 'multicheck') {
                // current fValidator doesn't work with checkboxes
                $fValidator_opts[] = 'required';
            }
            $T->set_var('is_required', 'true');
        } else {
            $T->clear_var('is_required');
        }

        // Set a flag to indicate that this is a static field.  No status
        // indicators need to be shown.
        if ($data->type == 'static') {
            $T->set_var('is_static', 'true');
        } else {
            $T->clear_var('is_static');
        }

        // If POSTed form data, set the user variable to that.  Otherwise,
        // set it to the default or leave it alone.
        if (isset($_POST[$data->name])) {
            $data->value = $_POST[$data->name];
        } elseif (is_null($data->value)) {
            $data->value = $data->options['default'];
        }

        if (isset($data->options['spancols'])) {
            $T->set_var('spancols', true);
        } else {
            $T->clear_var('spancols');
        }

        if (!empty($data->prompt)) {
            $T->set_var('prompt', PRF_noquotes($data->prompt));
        } else {
            $T->clear_var('prompt');
        }

        if (!empty($data->options['help_text'])) {
            $T->set_var('help_text', htmlspecialchars($data->options['help_text']));
        } else {
            $T->clear_var('help_text');
        }

        $T->set_var('is_visible', $data->isPublic() ? 'true' : '');
        $T->set_var('field', $data->FormField());
        $T->set_var('fld_class', isset($_POST['prf_errors'][$data->name]) ? 'profile_error' : '');
        $T->parse('qrow', 'QueueRow', true);
    }
    $T->parse('output', 'editform');
    $val = $T->finish($T->get_var('output'));
    return $val;
}


/**
*   Saves the user data to the database.
*
*   @param  array   $vals   Array of name=>value pairs, like $_POST.
*   @param  integer $uid    Optional user ID to save, current user default.
*   @param  string  $type   Type of operation.
*   @return boolean     True on success, False on failure.
*/
function PRF_saveData($vals, $uid = 0, $type = 'edit')
{
    global $_TABLES, $_USER, $LANG_PROFILE;

    if (!is_array($vals)) return;
    $uid = $uid < 1 ? (int)$_USER['uid'] : (int)$uid;

    if ($type == 'registration') {
        // for new user, get empty profile definitions
        $A = PRF_getDefs($uid, '', 0);
    } else {
        $isAdmin = SEC_hasRights('profile.admin');
        if ($uid != $_USER['uid'] && !$isAdmin) {
            // non-admin attempting to change another user's record
            return;
        }
        $A = PRF_getDefs($uid);
    }

    $fld_sql = array();
    $validation_errs = 0;
    $_POST['prf_errors'] = array();
    foreach ($A as $name => $data) {
        // Now make changes based on the type of data and applicable options.
        // Managers can override normal validations.
        if (!$data->validData($vals[$name], $vals) && !PRF_isManager()) {
            // We could just return false here, but instead continue checking so
            // we can show the user all the errors, not just the first.
            $msg = empty($data->options['help_text']) ?
                    sprintf($LANG_PROFILE['msg_fld_missing'], $data->prompt) :
                    $data->options['help_text'];
            LGLIB_storeMessage($msg, '', true);
            $_POST['prf_errors'][$name] = 'true';
            $validation_errs++;
        }
    }
    // If any validation errors found, return false now
    if ($validation_errs > 0) return false;

    if (isset($vals['fullname'])) {
        USES_lglib_class_nameparser();
        // Break the fullname into first and last names
        $fname = DB_escapeString(NameParser::F($vals['fullname']));
        $lname = DB_escapeString(NameParser::L($vals['fullname']));
        $fld_sql[] = "sys_fname = '$fname'";
        $fld_sql[] = "sys_lname = '$lname'";
    }

    foreach ($A as $name => $data) {
        switch ($data->type) {
        case 'date':
            $def_value = empty($data->value) ? $data->options['default'] :
                        $data->value;
            list($dt, $tm) = explode(' ', $def_value);
            if (empty($tm)) $tm = '00:00:00';
            $time = '';
            $date = '';
            if (isset($vals[$name . '_ampm'])) {
                $vals[$name . '_hour'] = $data->hour12to24($vals[$name . '_hour'], $vals[$name . '_ampm']);
            }
            foreach (array('_hour', '_minute') as $fld) {
                // Absense of time value is actually ok, just set to midnight
                if (!isset($vals[$name . $fld])) {
                    $time = $tm;
                    break;
                }
            }
            if (empty($time)) {
                $time = sprintf('%02d:%02d:%02d',
                        (int)$vals[$name . '_hour'],
                        (int)$vals[$name . '_minute'] , '00');
            }
            if (!isset($vals[$name.'_year']) ||
                !isset($vals[$name.'_year']) ||
                !isset($vals[$name.'_year'])) {
                $date = $dt;
            } else {
                /*$year = !empty($vals[$name . '_year']) ?
                        (int)$vals[$name . '_year'] : date('Y');*/
                $year = (int)$vals[$name . '_year'];
                $month = (int)$vals[$name . '_month'];
                $day = (int)$vals[$name . '_day'];
                $date = sprintf('%04d-%02d-%02d ', $year, $month, $day);
            }
            $newval = $date . ' ' . $time;
            break;

        case 'checkbox':
            $newval = $vals[$name] == '1' ? '1' : '0';
            break;
    
        case 'multicheck':
            $newval = @serialize($vals[$name]);
            break;

        case 'select':
            if (!isset($vals[$name]) && empty($data->value) &&
                isset($data->options['values']['default'])) {
                $vals[$name] = $data->options['values']['default'];
            }
            $newval = $vals[$name];
            break;

        case 'radio':
            if (!isset($vals[$name]) && empty($data->value) &&
                isset($data->options['values']['default'])) {
                $vals[$name] = $data->options['values']['default'];
            }
            $newval = $vals[$name];
            break;

        case 'static':
            // Nothing to save for static text fields, continue loop and
            // don't handle any $newval
            $newval = NULL;
            continue 2;
            break;

        case 'textarea':
        case 'account':
            $newval = $vals[$name];
            break;

        default:
            if (!isset($vals[$name]) && empty($data->value) &&
                isset($data->options['default'])) {
                $vals[$name] = $data->options['default'];
            }
            $newval = $vals[$name];
            break;
        }

        if ($type == 'registration') {
            // Automatically generate the data item, if needed
            if ($data->options['autogen'] == 1 && empty($newval)) {
                $newval = PRF_autogen($item, $uid);
            }
        }

        if ($data->perm_owner == 3 || $isAdmin) {
            // Perform field-specific sanitization and add to array of sql
            $newval = $data->Sanitize($newval);
            $fld_sql[] = $data->name . "='" . DB_escapeString($newval) . "'";
        }

    }   // end foreach data item loop

    if (!empty($fld_sql)) {
        $new_sql = implode(', ', $fld_sql);
        $c = DB_count($_TABLES['profile_data'], 'puid', $uid);
        if ($c == 0) {
            $sql = "INSERT INTO {$_TABLES['profile_data']} SET 
                    puid = $uid, $new_sql";
        } else {
            $sql = "UPDATE {$_TABLES['profile_data']} SET 
                    $new_sql 
                    WHERE puid = $uid";
        }
        DB_query($sql, 1);
        if (DB_error()) {
            COM_errorLog("PRF_saveData() - error execuring sql: $sql");
            return false;
        }
    }
    return true;
}


/**
*   Create the fValidator class string for input fields.  If no options
*   are supplied, then the fValidator is empty.
*
*   @since  version 0.0.2
*   @param  array   $opts   Options to include ('required', 'email', etc).
*   @param  array   $data   All field data, to get the mask
*   @return string          String for 'class="fValidate[] iMask...'
*/
function PRF_make_fValidator($opts, $data)
{
    $retval = 'class="fValidate[';

    if (is_array($opts)) {
        $opt = "'" . join("','", $opts) . "'";
    } else {
        $opt = '';
    }
    $retval .= $opt . ']';

    if (isset($data['options']['mask'])) {
        $stripmask = isset($data['options']['stripmask']) &&
                        $data['options']['stripmask'] == '1' ? 'true' : 'false';

        $retval .= " iMask\" alt=\"{
        type: 'fixed',
        mask: '{$data['options']['mask']}',
        stripMask: $stripmask }\"";
    } else {
        $retval .= '"';
    }

    return $retval;
}


/**
*   Convert a field mask (9999-AA-XX) to a visible mask (____-__-__)
*
*   @since  version 0.0.2
*   @param  string  $mask   Field mask
*   @return string          Field mask converted to visible mask
*/
function PRF_mask2vismask($mask)
{
    $old = array('9', 'X', 'A', 'a', 'x');
    $new = array('_', '_', '_', '_', '_');
    return str_replace($old, $new, $mask);
}


/**
*   Automatically generate a string value.
*
*   The site admin can effectively override this function by creating a
*   CUSTOM_profile_autogen() function which takes the field name & type
*   as arguments, or a CUSTOM_profile_autogen_{fieldname} function which
*   takes no arguments.  The second form takes precedence over the first.
*
*   All fields and values are passed to the auto-generation function so
*   it may use them in the creation of the new value.
*
*   @since  version 0.0.3
*   @param  array   $A      Field definition and values
*   @param  integer $uid    Optional user ID.  Zero is acceptable here.
*   @return string          Value to give the field
*/
function PRF_autogen($A, $uid=0)
{
    if (!is_array($A) || empty($A)) {
        return COM_makeSID();
    }

    $function = 'CUSTOM_profile_autogen_' . $A['name'];
    if (function_exists($function)) 
        return $function($A, $uid);
    elseif (function_exists('CUSTOM_profile_autogen')) 
        return CUSTOM_profile_autogen($A, $uid);
    else
        return COM_makeSID();
}


/**
*   Strips slashes if magic_quotes_gpc is on.
*
*   @param  mixed   $var    Value or array of values to strip.
*   @return mixed           Stripped value or array of values.
*/
function PRF_stripslashes($var)
{
    if (!empty($var) && get_magic_quotes_gpc()) {
        if (is_array($var)) {
            return array_map('PRF_stripslashes', $var);
        } else {
            return stripslashes($var);
        }
    } else {
        return $var;
    }
}


/**
*   Show the site header, with or without left blocks according to config.
*
*   @since  1.0.2
*   @see    COM_siteHeader()
*   @param  string  $subject    Text for page title (ad title, etc)
*   @param  string  $meta       Other meta info
*   @return string              HTML for site header
*/
function PRF_siteHeader($subject='', $meta='')
{
    global $_PRF_CONF, $LANG_PROFILE;

    $retval = '';

    switch($_PRF_CONF['displayblocks']) {
    case 2:     // right only
    case 0:     // none
        $retval .= COM_siteHeader('none', $subject, $meta);
        break;

    case 1:     // left only
    case 3:     // both
    default :
        $retval .= COM_siteHeader('menu', $subject, $meta);
        break;
    }

    return $retval;

}


/**
*   Show the site footer, with or without right blocks according to config.
*
*   @since  version 1.0.2
*   @see    COM_siteFooter()
*   @return string              HTML for site header
*/
function PRF_siteFooter()
{
    global $_PRF_CONF;

    $retval = '';

    switch($_PRF_CONF['displayblocks']) {
    case 2 : // right only
    case 3 : // left and right
        $retval .= COM_siteFooter(true);
        break;

    case 0: // none
    case 1: // left only
    default :
        $retval .= COM_siteFooter();
        break;
    }

    return $retval;

}


/**
*   Create a group selection dropdown, without the variable name.
*   The default SEC_getGroupDropdown function includes the "select" tags
*   with a hard-coded variable name ("group_id"), making it impossible to use
*   more than once on a form.
*
*   @since  version 1.1.1
*   @param  integer $group_id   Group ID selected by default
*   @param  integer $access     Access needed (2=read, 3=write)
*   @return string              HTML for the option selections
*/
function PRF_GroupDropdown($group_id, $access)
{
    global $_TABLES;

    $groupdd = '';

    if ($access == 3) {
        $usergroups = SEC_getUserGroups();

        foreach ($usergroups as $ug_name => $ug_id) {
            $groupdd .= '<option value="' . $ug_id . '"';
            if ($group_id == $ug_id) {
                $groupdd .= ' ' . PRF_SELECTED;
            }
            $groupdd .= '>' . $ug_name . '</option>' . LB;
        }
    } else {
        // They can't set the group then
        $groupdd .= DB_getItem($_TABLES['groups'], 'grp_name',
                                "grp_id = '".DB_escapeString($group_id)."'")
                 . '<input type="hidden" name="group_id" value="' . $group_id
                 . '" />';
    }

    return $groupdd;
}


/**
*   Invoke a service from another plugin.
*   This is our own version of PLG_invokeService() so web services doesn't
*   have to be enabled in the invoked plugin.
*
*   @param  string  $type   Plugin name
*   @param  string  $action Plugin function
*   @param  array   $args   Array of arguments to pass to the plugin
*   @param  mixed   &$output    Pointer to output values
*   @param  mixed   &$svc_msg   Service message (unused)
*   @return integer Return value, see lib-plugins.php
*/
function PRF_invokeService($type, $action, $args, &$output, &$svc_msg)
{
    global $_CONF;

    $retval = PLG_RET_ERROR;

    $output  = '';
    $svc_msg = '';

    // Check if the plugin type and action are valid
    $function = 'service_' . $action . '_' . $type;
    if (function_exists($function)) {
        if (!isset($args['gl_svc'])) {
            $args['gl_svc'] = false;
        }
        $retval = $function($args, $output, $svc_msg);
    }

    return $retval;
}

?>
