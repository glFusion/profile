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
    global $_CONF, $_USER, $_TABLES, $LANG_PROFILE, $_PRF_CONF, $_SYSTEM;

    // Choose the correct template file based on the glFusion version
    // and type of form needed
    switch ($type) {
    case 'inline':
        $template_name = 'profile_inlineform';
        $access_required = 2;
        break;
    case 'registration':
        $template_name = 'profile_registration';
        // Set no access level to profile defs since this is an anon user.
        // Otherwise, they're not allowed to even read the definitions.
        $access_required = 0;
        break;
    default:
        // For anything but new registrations, the current user needs
        // at least read privileges.
        $access_required = 2;
        $template_name = 'profile_usersettings';
        break;
    }

    $A = PRF_getDefs($uid, '', $access_required);
    $T = PRF_getTemplate($template_name, 'editform');
    $T->set_var(array(
        'uid'       => $uid,
        'form_id'   => $form_id,
        'have_jquery' => isset($_SYSTEM['disable_jquery']) && $_SYSTEM['disable_jquery'] ? '' : 'true',
        'iconset'   => $_PRF_CONF['_iconset'],
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
        if ($data->type == 'static' || $data->type == 'system') {
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

        $T->set_var(array(
            'is_visible'    => $data->isPublic() ? 'true' : '',
            'field'         => $data->FormField(),
            'fld_class'     => isset($_POST['prf_errors'][$data->name]) ?
                    'profile_error' : '',
            'fld_name'      => $data->name,
        ) );
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
        if (!isset($vals[$name])) continue;
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

    // If the "fullname" value is included, break it into first and last names.
    // Only update DB fields that are NOT included in the form, otherwise
    // there will be duplicate SQL fields during inserts.
    if (isset($vals['fullname'])) {
        USES_lglib_class_nameparser();
        $fname = DB_escapeString(NameParser::F($vals['fullname']));
        $lname = DB_escapeString(NameParser::L($vals['fullname']));
        if (!isset($vals['sys_fname'])) $fld_sql[] = "sys_fname = '$fname'";
        if (!isset($vals['sys_lname'])) $fld_sql[] = "sys_lname = '$lname'";
    }

    foreach ($A as $name => $data) {
        switch ($data->type) {
        case 'date':
            $def_value = empty($data->value) ? $data->options['default'] :
                        $data->value;
            if (strpos($def_value, ' ')) {
                list($dt, $tm) = explode(' ', $def_value);
            } else {
                $dt = $def_value;
                $tm = NULL;
            }
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
            $newval = isset($vals[$name]) ? $vals[$name] : '';
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

        // Auto-Generate a value during registration, or if the value is empty
        if (isset($data->options['autogen']) &&
                $data->options['autogen'] == 1 &&
                ($type == 'registration' || empty($newval))) {
            $newval = PRF_autogen($data, $uid);
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
            COM_errorLog("PRF_saveData() - error executing sql: $sql");
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
*   Automatically generate a field value.
*
*   The site admin can effectively override this function by creating a
*   CUSTOM_profile_autogen() function which takes the field name & type
*   as arguments, or a CUSTOM_profile_autogen_{fieldname} function.
*   The second form takes precedence over the first.
*
*   @since  version 0.0.3
*   @param  array   $A      Field definition and values
*   @param  integer $uid    Optional user ID.  Zero is acceptable here.
*   @return string          Value to give the field
*/
function PRF_autogen($A, $uid=0)
{
    if (!is_object($A) || empty($A)) {
        return COM_makeSID();
    }
    $function = 'CUSTOM_profile_autogen_' . $A->name;
    if (function_exists($function))
        return $function($A, $uid);
    elseif (function_exists('CUSTOM_profile_autogen'))
        return CUSTOM_profile_autogen($A, $uid);
    else
        return COM_makeSID();
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
/*        $usergroups = SEC_getUserGroups();

        foreach ($usergroups as $ug_name => $ug_id) {
            $groupdd .= '<option value="' . $ug_id . '"';
            if ($group_id == $ug_id) {
                $groupdd .= ' ' . PRF_SELECTED;
            }
            $groupdd .= '>' . $ug_name . '</option>' . LB;
        }*/
        $groupdd = COM_optionList($_TABLES['groups'], 'grp_id,grp_name', $group_id);
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
*   Shows security control for an object
*
*   This will return the HTML needed to create the security control see on
*   screen for profile items.
*   Taken from SEC_getPermissionsHTML() to allow for no owner access
*
*   @param  int     $perm_owner     Permissions the owner has 3 = read/write, 2 = read only, 0 = none
*   @param  int     $perm_group     Permission the group has
*   @param  int     $perm_members   Permissions logged in members have
*   @param  int     $perm_anon      Permissions anonymous users have
*   @return string  needed HTML (table) in HTML $perm_owner = array of permissions [edit,read], etc edit = 1 if permission, read = 2 if permission
*/
function PRF_getPermissionsHTML($perm_owner,$perm_group,$perm_members,$perm_anon)
{
    $retval = '';

    // Convert "no access" permission values. GL < 1.5 used 1,2,3; 1.5+ used 0,2,3
    if ($perm_owner == 1) $perm_owner = 0;
    if ($perm_group == 1) $perm_group = 0;
    if ($perm_members == 1) $perm_members = 0;
    if ($perm_anon == 1) $perm_anon = 0;

    $T = new Template(PRF_PI_PATH . 'templates/admin');
    $T->set_file(array('editor'=>'edit_permissions.thtml'));

    $T->set_var(array(
        'owner_chk_' . $perm_owner  => PRF_CHECKED,
        'grp_chk_' . $perm_group    => PRF_CHECKED,
        'members_chk_' . $perm_members => PRF_CHECKED,
        'anon_chk_' . $perm_anon => PRF_CHECKED,
    ) );
    $T->parse('output','editor');
    $retval .= $T->finish($T->get_var('output'));

    return $retval;
}

?>
