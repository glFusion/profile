<?php
/**
 * Plugin-specific functions for the profile plugin.
 * Load by calling USES_profile_functions()
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/**
 * Show the site header, with or without left blocks according to config.
 *
 * @since   1.0.2
 * @see     COM_siteHeader()
 * @param   string  $subject    Text for page title (ad title, etc)
 * @param   string  $meta       Other meta info
 * @return  string              HTML for site header
 */
function PRF_siteHeader($subject='', $meta='')
{
    global $_PRF_CONF, $LANG_PROFILE;

    $retval = '';
    if (!isset($_PRF_CONF['displayblocks'])) {
        $_PRF_CONF['displayblocks'] = 0;
    }
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
 * Show the site footer, with or without right blocks according to config.
 *
 * @since   version 1.0.2
 * @see     COM_siteFooter()
 * @return  string              HTML for site header
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
 * Create a group selection dropdown, without the variable name.
 * The default SEC_getGroupDropdown function includes the "select" tags
 * with a hard-coded variable name ("group_id"), making it impossible to use
 * more than once on a form.
 *
 * @since   version 1.1.1
 * @param   integer $group_id   Group ID selected by default
 * @param   integer $access     Access needed (2=read, 3=write)
 * @return  string              HTML for the option selections
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
 * Shows security control for an object.
 *
 * This will return the HTML needed to create the security control see on
 * screen for profile items.
 * Taken from SEC_getPermissionsHTML() to allow for no owner access
 *
 * @param   int     $perm_owner     Permissions the owner has 3 = read/write, 2 = read only, 0 = none
 * @param   int     $perm_group     Permission the group has
 * @param   int     $perm_members   Permissions logged in members have
 * @param   int     $perm_anon      Permissions anonymous users have
 * @return  string  needed HTML (table) in HTML $perm_owner = array of permissions [edit,read], etc edit = 1 if permission, read = 2 if permission
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
