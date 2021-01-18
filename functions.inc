<?php
/**
 * Functions for the Profile plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2020 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** Check and see if we need to load the plugin configuration */
require_once $_CONF['path_system'] . 'classes/config.class.php';

$prf_config = config::get_instance();
$_PRF_CONF = $prf_config->get_config('profile');

/** Include static plugin config values */
require_once 'profile.php';

/** Define the absolute path to the plugin's directory */
define('PRF_PI_PATH', $_CONF['path'] . 'plugins/' . $_PRF_CONF['pi_dir'] . '/');
/** Define the base public url for the plugin */
define('PRF_PI_URL', $_CONF['site_url'] . '/' . $_PRF_CONF['pi_dir']);
/** Define the administration url for the plugin */
define('PRF_ADMIN_URL',
            $_CONF['site_admin_url'] . '/plugins/' . $_PRF_CONF['pi_dir']);
define('PRF_CHECKED', 'checked="checked"');
define('PRF_SELECTED', 'selected="selected"');

/** Determine the correct language file, falling back to English as default. */
$langpath = __DIR__ . '/language';
$langfile = $_CONF['language'] . '.php';
if (!file_exists("$langpath/$langfile")) {
    $langfile = 'english_utf-8.php';
}

/** Include plugin language file */
require_once "$langpath/$langfile";
if (file_exists("$langpath/custom/$langfile")) {
    /** Include custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

if (file_exists(__DIR__ . '/config.php')) {
/** Include local config values and overrides */
    include_once __DIR__ . '/config.php';
}

spl_autoload_register(function ($class)
{
    // project-specific namespace prefix
    $prefix = 'Profile\\';

    // base directory for the namespace prefix
    $base_dir = __DIR__ . '/classes/';

    // does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // get the relative class name
    $relative_class = substr($class, $len);

    // replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.class.php';

    // if the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Set the custom values into the $_USER array.  Check that this plugin
// is enabled as an extra safety check.
if (isset($_PLUGINS) && is_array($_PLUGINS) && in_array('profile', $_PLUGINS)) {
    PRF_setUserValues();
}

/** Include api functions for other plugins */
require_once __DIR__ . '/services.inc.php';


/**
 * Called by the plugin Editor to display the current plugin code version.
 * This may be different than the version installed and registered currently.
 * If newer then you may want to run the update.
 *
 * @return  string  Version number
 */
function plugin_chkVersion_profile()
{
    global $_PRF_CONF;
    return $_PRF_CONF['pi_version'];
}


/**
 * This will put an option for profile administration in the command and control area.
 *
 * @return  mixed   array(title, url, icon), or false when not allowed
 */
function plugin_cclabel_profile()
{
    global $LANG_PROFILE, $_PRF_CONF;

    if (SEC_hasRights($_PRF_CONF['pi_name'] . '.admin')) {
        return array ($LANG_PROFILE['menu_title'],
                PRF_ADMIN_URL . '/index.php',
                plugin_geticon_profile());
    } else {
        return false;
    }
}


/**
 * Put an option for profile management on the admin-only menu.
 *
 * @return   mixed   array(title, url, num. banner), or void when not allowed
 */
function plugin_getadminoption_profile()
{
    global $_CONF, $LANG_PROFILE;

    if (SEC_hasRights('profile.admin')) {
        return array ($LANG_PROFILE['menu_title'],
                PRF_ADMIN_URL . '/index.php',
                );
    } else {
        return false;
    }
}


/**
 * Delete profile data for users that are deleted.
 *
 * @param   int     $uid    User id of deleted user
 * @return  void
 */
function plugin_user_delete_profile($uid)
{
    global $_TABLES;

    DB_delete($_TABLES['profile_data'], 'puid', (int)$uid);
}


/**
 * Support merging local accounts into remote accounts
 *
 * @param   integer $origUID    Original (local) user ID
 * @param   integer $destUID    New (remote) user ID
 */
function plugin_user_move_profile($origUID, $destUID)
{
    global $_TABLES;

    $origUID = (int)$origUID;
    $destUID = (int)$destUID;

    DB_query("UPDATE {$_TABLES['profile_data']}
            SET puid = $destUID WHERE puid = $origUID", 1);
}


/**
 * Send a message to the user once at login if there's no email address.
 * This can happen if they've created their account via Facebook oauth.
 *
 * @param   integer $uid    User ID
 */
function plugin_user_login_profile($uid)
{
    global $LANG_PROFILE, $_USER;
    if (empty($_USER['email'])) {
        LGLIB_storeMessage($LANG_PROFILE['missing_email']);
    }
}


/**
 * Upgrade the Profile plugin.
 *
 * @uses    profile_do_upgrade()
 * @return  boolean     True on success, False on failure
 */
function plugin_upgrade_profile()
{
    require_once dirname(__FILE__) . '/upgrade.inc.php';
    return profile_do_upgrade();
}


/**
 * Returns the URL of the plugin's icon.
 *
 * @return  string      URL of the icon
 */
function plugin_geticon_profile()
{
    return PRF_PI_URL . '/images/profile.png';
}


/**
 * Automatic uninstall function for plugins.
 *
 * This code is automatically uninstalling the plugin.
 * It passes an array to the core code function that removes
 * tables, groups, features and php blocks from the tables.
 * Additionally, this code can perform special actions that cannot be
 * foreseen by the core code (interactions with other plugins for example)
 *
 * @return  array     Elements to be removed
 */
function plugin_autouninstall_profile ()
{
    global $_PRF_CONF;
    $out = array (
        // give the name of the tables, without $_TABLES[]
        'tables' => array('profile_def',
                'profile_data', 'profile_lists',
            ),
        // give the full name of the group, as in the db
        'groups' => array('profile Admin'),
        // give the full name of the feature, as in the db
        'features' => array('profile.admin', 'profile.export'),
        // give the full name of the block, including 'phpblock_', etc
        'php_blocks' => array(),
        // give all vars with their name
        'vars'=> array()
    );
    return $out;
}


/**
 * Get additional Javascript into the site header.
 *
 * @return  array List of paths to Javascript files
 */
function plugin_getheaderjs_profile()
{
    $pi_dir = PRF_PI_PATH . 'js/';

    $files = array(
        $pi_dir . 'toggleEnabled.js',
        //$pi_dir . 'iMask.js',
    );

    return $files;
}


/**
 * Get additional CSS into the site header.
 *
 * @return  array   List of paths to Javascript files
 */
function plugin_getheadercss_profile()
{
    global $_CONF, $_PRF_CONF;

    $styles = array();
    $files = array(
        'style.css',
    );

    // When looking for CSS files, we'll check in this order:
    //  1. layout/custom
    //  2. layout
    //  3. plugin/custom
    //  4. plugin (default distribution version)
    $pi_css = PRF_PI_PATH . 'css/';
    $layout_css = "{$_CONF['path_layout']}plugins/{$_PRF_CONF['pi_name']}/";
    foreach ($files as $file) {
        if (@file_exists($layout_css . 'custom/' . $file)) {
            $styles[] = $layout_css . 'custom/' . $file;
        } elseif (@file_exists($layout_css . $file)) {
            $styles[] = $layout_css . $file;
        } elseif (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } else {
            $styles[] = $pi_css . $file;
        }
    }
    return $styles;
}


/**
 * Create an edit form in the Account Settings section.
 * Used with glFusion 1.1.5 and above only.
 *
 * @param   integer $uid        User ID
 * @param   string  $panel      Panel being populated
 * @param   string  $fieldset   Name of fieldset being populated
 * @return  string              HTML for edit form. Never NULL.
 */
function plugin_profileedit_profile($uid, $panel, $fieldset)
{
    $retval = '';

    if ($panel == 'privacy' && $fieldset == 'privacy') {
        $P = Profile\Profile::getInstance($uid);
        $dir_fld = $P->getField('sys_directory');
        if ($dir_fld) {
            $T = new Template(__DIR__ . '/templates');
            $T->set_file('profile', 'profile_privacy.thtml');
            $T->set_var(array(
                'prf_prompt'    => $dir_fld->getPrompt(),
                'prf_field'     => $dir_fld->FormField(),
            ) );
            $T->parse('output', 'profile');
            $retval .= $T->finish($T->get_var('output'));
        }
    } elseif (
        $panel == 'useredit' ||
        ($panel == '' && $fieldset == '')
    ) {
        $P = Profile\Profile::getInstance($uid);
        $retval .= $P->Edit('edit', $uid);
    }
    return $retval;
}


/**
 * Displays custom profile variables in the user profile screen.
 *
 * @param   integer  $uid       User ID whose profile is being displayed
 * @return  string          HTML to include in profile page
 */
function plugin_profileblocksdisplay_profile($uid)
{
    global $LANG_PROFILE, $_TABLES, $_CONF, $_PRF_CONF, $_USER;

    $retval = '';

    // Make sure that a valid UID was passed in.
    if ($uid == '') {
        return $retval;;
    }

    $T = new Template(PRF_PI_PATH . 'templates/');
    $T->set_file(array(
        'block' => 'profileblock.thtml',
    ));
    $A = Profile\Profile::getInstance($uid)->getFields();
    if (empty($A)) {
        return $retval;
    }

    $showvisicons = $uid == $_USER['uid'] ? true : false;
    $items = 0;     // counter to check if any items are actually displayed
    $isAdmin = PRF_isManager();
    foreach ($A as $data) {
        // TODO: show variables to the user if owner perm set
        // Admin can see all fields, they'll be styled to indicate not public.
        if (!$data->canView() && !$isAdmin) {
            continue;
        }
        $value = $data->FormatValue();
        if ($_PRF_CONF['showemptyvals'] || !empty($value)) {
            $T->set_block('block', 'profile_row', 'prow');
            $T->set_var(array(
                'descrip'   => htmlspecialchars($data->getPrompt()),
                'value'     => $value,
                'showvisicons' => $showvisicons ? 'true' : '',
                'is_visible' => $showvisicons && $data->isPublic() ? 'true' : '',
                'pubprv'    => $data->isPublic() ? 'public' : 'private',
            ) );
            $T->parse('prow', 'profile_row', true);
            $items++;
        }
    }
    // Only return the block if it's not empty
    if ($items > 0) {
        $T->parse('output', 'block');
        $retval = $T->finish($T->get_var('output'));
    }
    return $retval;
}


/**
 * Save the profile information when edited via Account Settings.
 * Used with glFusion 1.1.5 and above
 *
 * @param   integer $uid    ID of user being saved
 */
function plugin_profilesave_profile($uid=0)
{
    global $_USER;

    if (COM_isAnonUser()) {
        return;
    }

    if ($uid == 0) {
        $uid = (int)$_USER['uid'];
    }

    $P = Profile\Profile::getInstance($uid);
    $P->Save($_POST);
}


/**
 * Handle a new user registration.
 * Called by PLG_createUser (glFusion 1.1.5) to handle a new user signup.
 *
 * @param   integer $uid    UID of new user.  Already set by glFusion
 */
function plugin_user_create_profile($uid = 0)
{
    global $_TABLES;

    // $uid must already be set to the new user's ID
    if ($uid == 0)
        return;

    $P = Profile\Profile::getInstance($uid);
    $P->Save($_POST, 'registration');
}


/**
 * Set the custom profile values into the $_USER array.
 * If a user ID is specified, then an array of values is returned for that
 * user.  If not, then the current user ID is used, the profile values
 * are merged into the $_USER array, and nothing is returned.
 *
 * @param   integer $uid    User ID, zero for current user
 * @return  mixed   Array of name=>string pairs, empty string if $_USER is set.
 */
function PRF_setUserValues($uid=0)
{
    global $_TABLES, $_USER;

    if ($uid == 0) {
        $_uid = $_USER['uid'];
    }
    $_uid = (int)$uid;
    $A = array();
    foreach (Profile\Profile::getInstance($_uid)->getFields() as $name => $data) {
        $A[$name] = $data->FormatValue();
    }
    if ($uid == 0) {
        if (is_array($_USER)) {
            $_USER = array_merge($_USER, $A);
        }
        return '';
    } else {
        return $A;
    }
}


/**
 * Returns a name=>value array of options broken out from the given string.
 * The string is expected to be a serialized array.
 *
 * @param   string  $str    Option string
 * @return  array           Associative array of option=>value pairs
 */
function PRF_getOpts($str)
{
    $A = @unserialize($str);
    if (!$A)
        return array();
    else
        return $A;
}


/**
 * Creates a name:value;name:value.. option string given the associative
 * array of name=>value options.
 * Options must already be validated for the type of record to be saved.
 *
 * @param   array   $A      Array of name=>value pairs
 * @return  string          Serialized string representing the array
 */
function PRF_setOpts($A)
{
    $str = '';
    if (is_array($A))
        $str = serialize($A);

    return $str;
}


/**
 * Sanitize all values in an array.  Used as a calback function to array_map.
 *
 * @param   string  $val    Value to sanitize
 * @return  string          SQL-safe version of $val
 */
function PRF_escape_string($val)
{
    return is_string($val) ? DB_escapeString($val) : $val;
}


/**
 * Verify that all required custom profile variables are set.
 *
 * @param   string  $type       Type of submission
 * @param   string  $content    Submission content
 * @return  string              Error message, or empty string if ok
 */
function plugin_itemPreSave_profile($type, $content)
{
    global $_USER;

    // Don't validate administrators, it doesn't work when creating
    // new users if any fields are required.
    if (SEC_hasRights('user.admin,profile.admin', 'OR')) {
        return '';
    }

    // Only check content types that we care about.
    // Profile editing calls this starting with 1.3.0
    if (
        $type == 'registration' ||
        $type == 'useredit'
    ) {
        $P = Profile\Profile::getInstance();
        if (!empty($P->Validate($_POST))) {
            return 97;
        } else {
            return 0;
        }
    } else {
        return '';
    }
}


/**
 * Check if the current user is a plugin admin (moderator).
 *
 * @return  boolean     True if an admin, False if not
 */
function plugin_ismoderator_profile()
{
    static $ismod = NULL;
    if ($ismod === NULL) {
        $ismod = SEC_hasRights('profile.admin');
    }
    return $ismod;
}


/**
 * Return a single value from the values table corresponding to
 * a specific user ID and item name.
 * Not used by this plugin, but intended to provide a basic API to
 * other plugins.
 *
 * @param   string  $key    Name of item to retrieve
 * @param   integer $uid    User ID, current user if zero
 * @return  array           array(value, value_text) for the requested item.
 */
function PRF_getValue($key, $uid=0)
{
    global $_TABLES, $_USER;

    if ($key == '') {
        return '';
    }
    if ($uid == 0) {
        $uid = $_USER['uid'];
    }
    $uid = (int)$uid;

    $P = \Profile\Profile::getInstance($uid);
    if (isset($P->fields[$key])) {
        return array($P->fields[$key]->getValue(), $P->fields[$key]->FormatValue());
    } else {
        return '';
    }
}


/**
 * Implements autotags for custom profile elements.
 *
 * @param  string  $op         Name of operation
 * @param  string  $content    Content of the autotag
 * @param  string  $autotag    Complete autotag
 * @return string              HTML replacement
 */
function plugin_autotags_profile($op, $content = '', $autotag = '')
{
    global $_USER;

    $HTML = '';
    $retval = $content;

    if ($op == 'tagname' ) {
        return array('profile', 'profile_list');
    } else if ($op == 'parse') {
        $item_id = COM_applyFilter($autotag['parm1']);
        $options = explode(' ', COM_applyFilter($autotag['parm2']));
        foreach ($options as $tmp) {
            $opt = explode(':', $tmp);
            // This is to allow for tags like 'case', which have no options
            // and would throw a PHP error
            if (isset($opt[1])) {
                $opts[$opt[0]] = $opt[1];
            } else {
                $opts[$opt[0]] = '';
            }
        }

        switch ($autotag['tag']) {
        case 'profile':
            // We always use a userID, so if one wasn't supplied then
            // use the current user. Anonymous users have no profile data
            // so just breat with $HTML empty.
            if (isset($opts['uid']))
                $uid = (int)$opts['uid'];
            else
                $uid = $_USER['uid'];
            if ($uid < 2) break;    // Have to have a valid user ID

            // Now get the specific profile value for the user
            list($value, $text) = PRF_getValue($item_id, $uid);

            // Set the $HTML variable to the value or formatted text
            $HTML = isset($opts['value']) ? $value : $text;

            // Switch the text case, if requested
            if (isset($opts['case'])) {
                switch ($opts['case']) {
                case 'l':
                    $HTML = strtolower($HTML);
                    break;
                case 'p':   // proper casing
                    $HTML = ucwords($HTML);
                    break;
                default:    // upper case
                    $HTML = strtoupper($HTML);
                    break;
                }
            }
            break;

        case 'profile_list':
            $item_id = COM_applyFilter($autotag['parm1']);
            $PL = new Profile\UserList($item_id);
            if (isset($opts['menu'])) $PL->showMenu();
            if (isset($opts['export'])) $PL->showExport();
            if (isset($opts['extras'])) $PL->hasExtras();
            $HTML = $PL->Render(true);
            break;
        }

        // Perform the text substitution with the final version of $HTML
        $retval = str_replace($autotag['tagstr'], $HTML, $content);
    }

    return $retval;
}


/**
 * Returns the items for this plugin that should appear on the main menu.
 *
 * NOTE: this MUST return the url/value pairs in the following format
 * $<arrayname>[<label>] = <url>
 *
 * @return  array   Associative array of menu items to be added to menu
 */
function plugin_getmenuitems_profile()
{
    global $_CONF, $LANG_PROFILE, $_PRF_CONF, $_CONF_MEMBERLIST;

    $menuitems = array();

    if (is_array($_CONF_MEMBERLIST)) {
        $menuitems[$LANG_PROFILE['usermenu']] =
            "{$_CONF['site_url']}/{$_PRF_CONF['pi_name']}/index.php";
    }

    return $menuitems;
}


/**
 * Return SQL expression to check for permissions.
 *
 * Taken from COM_getPermSQL with some modifications, since this
 * plugin doesn't use an owner_id field
 *
 * @since   v0.0.2
 * @param   string  $type   Part of the SQL expr. e.g. 'WHERE', 'AND'
 * @param   integer $u_id   User id or 0 = current user
 * @param   integer $access Access to check for (2=read, 3=r&write)
 * @param   string  $table  Table name if ambiguous (e.g. in JOINs)
 * @return  string          SQL expression string (may be empty)
 */
function PRF_getPermSQL($type = 'WHERE', $u_id = 0, $access = 2,
                        $table = '')
{
    global $_USER, $_GROUPS;

    // No restrictions on profile admins
    if (SEC_hasRights('profile.admin')) {
        return '';
    }

    if (!empty($table)) {
        $table .= '.';
    }

    if (COM_isAnonUser()) {
        $uid = 1;
    } else {
        $uid = $_USER['uid'];
    }
    if ($u_id <= 0) {
        $u_id = $uid;
    }
    $UserGroups = array();
    if (empty($_GROUPS)) {
        $_GROUPS = SEC_getUserGroups($uid);
    }
    $UserGroups = $_GROUPS;

    if (empty($UserGroups)) {
        // this shouldn't really happen, but if it does, handle user
        // like an anonymous user
        $uid = 1;
    }

    $sql = ' ' . $type ;

    if ($uid > 1) {
        if ($uid == $u_id) {
            // non-anon user viewing their own record, just check owner access
            $sql .= " {$table}perm_owner >= $access ";
        } else {
            // user checking another's record, check group & member access
            $sql .= "
            (
                (
                {$table}group_id IN (" . implode( ',', $UserGroups ) . ")
                AND
                {$table}perm_group >= $access
                )
            OR
                {$table}perm_members >= $access
            )";
        }
    } else {
        // anon user, just check anon access
        $sql .= " {$table}perm_anon >= $access ";
    }

    return $sql;
}


/**
 * Select default group for field permissions.
 *
 * @since   v0.0.2
 * @uses    PRF_getGroups()
 * @return  array   grp_name=>grp_id array of groups
 */
function plugin_configmanager_select_defgroup_profile()
{
    return PRF_getGroups('\'All Users\'', 'NOT');
}


/**
 * Return an an array of group names and IDs, including or excluding
 * the specified grplist.
 * No SQL validation is done on the passed parameters.
 *
 * @since   v0.0.2
 * @see     plugin_configmanager_select_defgroup_profile()
 * @param   string  $grplist    Comma-separated list of group names, SQL format
 * @param   string  $NOT        "NOT" to exclude $grplist, blank to include.
 */
function PRF_getGroups($grplist='', $NOT='')
{
    global $_TABLES;

    $A = array();

    $query = "SELECT grp_id, grp_name FROM {$_TABLES['groups']}";
    if ($NOT != '')
        $NOT = "NOT";

    // If a group list is specified, add it as a condition with the
    // specified keyword.
    if ($grplist != '') {
        $query .= " WHERE grp_name $NOT IN ($grplist)";
    }

    $result = DB_query($query);
    while ($row = DB_fetchArray($result)) {
        $A[$row['grp_name']] = $row['grp_id'];
    }

    return $A;
}


/**
 * Set the plugin's template variable value in the provided template.
 * The plugin's values are appended to the existing template variable.
 *
 * @since   v1.0.0
 * @param   string  $templatename   Name of template
 * @param   string  &$template      Actual template, by reference
 */
function plugin_templatesetvars_profile($templatename, &$template)
{
    if ($templatename == 'registration') {
        $existingPluginFields = $template->get_var('plugin_fields');
        $P = Profile\Profile::getInstance(1);
        $pluginFields = $existingPluginFields . $P->Edit('registration');
        $template->set_var('plugin_fields', $pluginFields);
    }
}


/**
 * Take action when a user's profile is changed.
 * Gets the data from the global $_POST array.
 *
 * @since   v1.0.0
 * @param   integer $uid    User ID being modified
 */
function Xplugin_user_changed_profile($uid)
{
    $P = \Profile\Profile::getInstance($uid);
    $P->Save($_POST);
}


/**
 * Get the URL to the localized configuration help file.
 *
 * @since   v1.0.0
 * @param   string  $option     Help option
 * @param   string  $doclang    Desired document language
 * @return  array   Array of (url, window_type)
 */
function plugin_getconfigelementhelp_profile($option, $doclang='english')
{
    $file = 'config.html';
    $doc_url = PRF_getDocURL($file, $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }

    return array($doc_url, 0);
}


/**
 * Export user data when requested.
 *
 * @param   integer $uid        User ID
 * @param   string  $email      User Email Address (not used)
 * @param   string  $username   User Name (not used)
 * @param   string  $ip         IP address (not used)
 * @return  string              XML-formatted data
 */
function plugin_privacy_export_profile($uid,$email='',$username='',$ip='')
{
    $retval = "<profile>\n";
    $tmp = \Profile\Profile::getInstance($uid)->fields;
    foreach ($tmp as $name => $data) {
        $d = addSlashes(htmlentities($data->FormatValue()));
        if (!empty($d)) {
            $p = str_replace(' ', '_', $data->prompt);
            $p = preg_replace("/[^A-Za-z0-9_]/", '', $p);
            $retval .= '<'.$p.'>'.$d.'</'.$p.">\n";
        }
    }
    $retval .= "</profile>\n";

    if ( function_exists('tidy_repair_string')) {
        $retval = tidy_repair_string($retval, array('input-xml' => 1));
    }
    return $retval;
}


/**
 * Return true to enable service functions.
 *
 * @return  boolean     True to enable web services
 */
function plugin_wsEnabled_profile()
{
    return true;
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * @see     plugin_getconfigelementhelp_subscription()
 * @param   string  $file       HTML file
 * @param   string  $doclang    Documentation language
 * @return  string              URL to document directory or file
 */
function PRF_getDocURL($file='', $doclang='english')
{
    global $_CONF, $_PRF_CONF;

    $doc_path = $_CONF['path_html'] . $_PRF_CONF['pi_name'] .
                "/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = PRF_PI_URL . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = PRF_PI_URL . "/docs/$doclang/";
    }

    return $doc_url;
}


/**
 * Reorder all items in a table.
 * Table name is abstracted to support both list and definition tables.
 *
 * @param  string  $table  Name of table
 * @param  string  $id_fld Name of "id" field
 */
function PRF_reorderDef($table, $id_fld='id')
{
    global $_TABLES;

    $id_fld = DB_escapeString($id_fld);

    $sql = "SELECT $id_fld, orderby
            FROM {$_TABLES[$table]}
            ORDER BY orderby ASC;";
    $result = DB_query($sql, 1);
    if (!$result) return;

    $order = 10;
    $stepNumber = 10;
    $changed = false;
    while ($A = DB_fetchArray($result, false)) {
        if ($A['orderby'] != $order) {  // only update incorrect ones
            $changed = true;
            $sql = "UPDATE {$_TABLES[$table]}
                SET orderby = '$order'
                WHERE $id_fld = '" . DB_escapeString($A[$id_fld]) . "'";
            DB_query($sql, 1);
        }
        $order += $stepNumber;
    }
    if ($changed) {
        \Profile\Cache::clear();
    }
}


/**
 * Replace quotes and apostrophes with html entities.
 * This is to avoid fouling up the html display of the text prompts
 * while still allowing other html to be used.
 *
 * @param   string  $str    String to convert
 * @return  string          Converted string
 */
function PRF_noquotes($str)
{
    return str_replace(array("'", '"'), array('&apos;', '&quot;'), $str);
}


/**
 * Check if the current user is allowed to manage memberships
 *
 * @return  boolean     True is user is a manager, False if not
 */
function PRF_isManager()
{
    global $_PRF_CONF;

    static $isManager = NULL;
    if ($isManager === NULL) {
        if (
            PRF_isAdmin() ||
            SEC_hasRights($_PRF_CONF['pi_name'] . '.manage')
        ) {
            $isManager = true;
        } else {
            $isManager = false;
        }
    }
    return $isManager;
}


/**
 * Check if the current user is allowed to administer the plugin
 *
 * @return  boolean     True is user is an admin, False if not
 */
function PRF_isAdmin()
{
    global $_PRF_CONF;

    static $isAdmin = NULL;
    if ($isAdmin === NULL) {
        if (SEC_hasRights($_PRF_CONF['pi_name'] . '.admin')) {
            $isAdmin = true;
        } else {
            $isAdmin = false;
        }
    }
    return $isAdmin;
}


/**
 * Get an array variable, first checking whether it's set.
 *
 * @since   v1.0.8
 * @param   array   $A          Array where the variable should be
 * @param   string  $key        Name of array element
 * @param   string  $type       Type of return value
 * @param   mixed   $default    Default value, if key is not set
 * @return     Array element cast to $type, or NULL if not set
 */
function PRF_getVar($A, $key, $type = 'string', $default = NULL)
{
    // If the key is not set, return the default
    if (!is_array($A) || !isset($A[$key])) {
        return $default;
    } else {
        $val = $A[$key];
    }

    // Cast the value according to the specified type
    switch ($type) {
    case 'int':
    case 'integer':
        $val = (int)$val;
        break;
    case 'bool':
        $val = (bool)$val;
        break;
    case 'float':
        $val = (float)$val;
        break;
    case 'array':
        // Cast value as an array only if it isn't already one
        if (!is_array($val)) {
            $val = array($val);
        }
        break;
    case 'string':
    default:
        $val = (string)$val;
        break;
    }
    return $val;
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
        $groupdd .= DB_getItem(
            $_TABLES['groups'],
            'grp_name',
            "grp_id = '".DB_escapeString($group_id)."'"
        ) .
        '<input type="hidden" name="group_id" value="' . $group_id . '" />';
    }
    return $groupdd;
}

?>
