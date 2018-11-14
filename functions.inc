<?php
/**
 * Functions for the Profile plugin for glFusion.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.1.4
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/**  Check and see if we need to load the plugin configuration */
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
$langpath = PRF_PI_PATH . 'language';
$langfile = $_CONF['language'] . '.php';
if (!file_exists("$langpath/$langfile")) {
    $langfile = 'english.php';
}

/** Include plugin language file */
require_once "$langpath/$langfile";
if (file_exists("$langpath/custom/$langfile")) {
    /** Include custom language file, if any */
    include_once "$langpath/custom/$langfile";
}

if (file_exists(PRF_PI_PATH . 'config.php')) {
/** Include local config values and overrides */
    include_once PRF_PI_PATH . 'config.php';
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

$_PRF_CONF['now'] = date('Y-m-d H:i:s');
$_PRF_CONF['_is_uikit'] = $_SYSTEM['framework'] == 'uikit' ? true : false;
$_PRF_CONF['_iconset'] = $_PRF_CONF['_is_uikit'] ? 'uk-icon' : 'fa fa';

/** Import plugin-specific functions */
function USES_profile_functions()
{   require_once PRF_PI_PATH . 'profile_functions.inc.php';     }

/** Include api functions for other plugins */
require_once PRF_PI_PATH . 'services.inc.php';


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
        $T = PRF_getTemplate('profile_privacy', 'profile');
        $P = \Profile\Profile::getInstance($uid);
        if (isset($P->fields['sys_directory'])) {
            $T->set_var(array(
                'prf_prompt'    => $P->fields['sys_directory']->prompt,
                'prf_field'     => $P->fields['sys_directory']->FormField(),
            ) );
            $T->parse('output', 'profile');
            $retval .= $T->finish($T->get_var('output'));
        }
    } elseif ( ($panel == 'useredit') || ($panel == '' && $fieldset == '') ) {
        $P = \Profile\Profile::getInstance($uid);
        $retval .= $P->Edit('edit', $uid);
    }
    return $retval;
}


/**
 * Displays custom profile variables in the user profile screen.
 *
 * @param   integer  $uid       User ID whose profile is being displayed
 * @param   object   $template  Pointer to template object
 * @return  string          HTML to include in profile page
 */
function plugin_profilevariablesdisplay_profile($uid, &$template)
{
    global $LANG_PROFILE, $_TABLES, $_CONF, $_PRF_CONF, $_USER;

    $retval = '';

    // Make sure that a valid UID was passed in.
    if ($uid == '') {
        return '';
    }

    USES_profile_functions();

    $T = new Template(PRF_PI_PATH . 'templates/');
    $T->set_file(array(
        'block' => 'profileblock.thtml',
    ));
    $T->set_var('is_uikit', $_PRF_CONF['_is_uikit']);
    //$A = PRF_getDefs($uid);
    $A = \Profile\Profile::getInstance($uid)->fields;
    if (empty($A)) {
        return '';
    }

    $showvisicons = $uid == $_USER['uid'] ? true : false;
    $items = 0;     // counter to check if any items are actually displayed
    $isAdmin = PRF_isManager();
    foreach ($A as $data) {
// TODO: show variables to the user if owner perm set
        // Admin can see all fields, they'll be styled to indicate not public.
        if (!$data->isPublic() && !$isAdmin) continue;
        $value = $data->FormatValue();
        if ($_PRF_CONF['showemptyvals'] || !empty($value)) {
            $T->set_block('block', 'profile_row', 'prow');
            $T->set_var(array(
                'descrip'   => htmlspecialchars($data->prompt),
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
        $existingFields = $template->get_var('plugin_fields');
        $template->set_var('plugin_fields',
            $existingFields . $T->finish($T->get_var('output')));
    } else {
        return '';
    }
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

    //USES_profile_functions();
    //PRF_saveData($_POST, $uid);
    //USES_profile_class_profile();
    //$P = new Profile();
    //$P->Save($_POST);
    $P = \Profile\Profile::getInstance($uid);
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

    //USES_profile_functions();
    //PRF_saveData($_POST, $uid, 'registration');
    //USES_profile_class_profile();
    //$P = new Profile($uid);
    $P = \Profile\Profile::getInstance($uid);
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

    //USES_profile_class_profile();

    if ($uid == 0) $uid = $_USER['uid'];
    $uid = (int)$uid;
    $A = array();
    //$P = new Profile($uid);

    //$tmp = PRF_getDefs($uid);
    $P = \Profile\Profile::getInstance($uid);
    foreach ($P->fields as $name => $data) {
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
 * Retrieve all the profile definitions and data items for the current or supplied user ID.
 * An empty array is returned in case of a database error.  The typical
 * cause would be when the code is updated and the plugin upgrade function
 * hasn't yet been run to update the database.
 *
 * @param   integer $uid    Optional user ID, current user if empty
 * @param   string  $key    Specific key to retrieve, all if blank
 * @param   integer $access Access level needed.  Used to allow admin access
 * @return  array           Array of profile objects
 */
function PRF_getDefs($uid = 0, $key = '', $access = 2)
{
    global $_TABLES, $_USER, $LANG_PROFILE, $_PRF_CONF;

    return \Profile\Profile::getInstance($uid);

    //USES_profile_class_item();

    // Default to current user if nothing else provided.  Zero is valid
    // if this is a new user registration, though return will be empty.
    $uid = $uid == 0 ? (int)$_USER['uid'] : (int)$uid;

    $sql = "SELECT * FROM {$_TABLES['profile_def']} pd
            WHERE pd.enabled=1 ";
    if ($key != '') {
        $sql .= " AND pd.name = '" . DB_escapeString($key) . "' ";
    }

    // Make sure the current user has access to this user's field:
    if ($uid > 0 && $access > 0) {
        $sql .= PRF_getPermSQL('AND', $uid, $access, 'pd');
    }
    $sql .= " ORDER BY pd.orderby";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("PRF_getDefs() error: $sql");
        return array();
    }

    // Get the data record for this field
    $sql = "SELECT * FROM {$_TABLES['profile_data']} WHERE puid='$uid'";
    $res1 = DB_query($sql, 1);
    if (DB_error()) return array();
    $vals = DB_fetchArray($res1, false);

    $F = array();
    while ($def = DB_fetchArray($res, false)) {
        //$classname = 'prf' . $tmp['type'];
        /*if (isset($vals[$def['name']])) {
            $def['value'] = $vals[$def['name']];
        } else {
            $def['value'] = NULL;
        }*/
        $value = isset($vals[$def['name']]) ? $vals[$def['name']] : NULL;
        $F[$def['name']] = \Profile\Field::getInstance($def, $value, $uid);
    }
    return $F;
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
 * This is not truly necessary since fValidator won't allow incomplete
 * submissions, but serves as a fallback in case Javascript is disabled.
 * Read-only fields are excluded from the check when $type == 'registration'.
 *
 * @param   string  $type       Type of submission
 * @param   string  $content    Submission content
 * @return  string              Error message, or empty string if ok
 */
function plugin_itemPreSave_profile($type, $content)
{
    // Don't validate administrators, it doesn't work when creating
    // new users if any fields are required.
    if (SEC_hasRights('user.admin,profile.admin', 'OR'))
        return '';

    // Only check content types that we care about.
    // Profile editing calls this starting with 1.3.0
    if ($type == 'registration') {
        $uid = 0;
    } elseif ($type == 'useredit') {
        $uid = $_POST['uid'];
    } else {
        return '';
    }

    $P = \Profile\Profile::getInstance($uid);
    if (!$P->Save($_POST)) {
        return 97;
    } else {
        return 0;
    }
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

    if ($key == '')
        return '';
    $uid = (int)$uid;
    if ($uid == 0) $uid = $_USER['uid'];

    $A = PRF_getDefs($uid, $key);
    return array($A[$key]->value, $A[$key]->FormatValue());
    //USES_profile_class_profile();
    //$P = new Profile($uid, $key);
    //return array($P->items[$key]->value, $P->items[$key]->value_text);
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
            $PL = new \Profile\UserList($item_id);
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
    global $_PRF_CONF;

    if ($templatename == 'registration') {
        //USES_profile_functions();
        $existingPluginFields = $template->get_var('plugin_fields');
        //$pluginFields = $existingPluginFields . PRF_editForm('registration');
        $P = \Profile\Profile::getInstance(1);
        $pluginFields = $existingPluginFields . $P->Edit('registration');
        $template->set_var('plugin_fields', $pluginFields);
    }
}


/**
*   Take action when a user's profile is changed.
*   Gets the data from the global $_POST array.
*
*   @since  version 1.0.0
*   @param  integer $uid    User ID being modified
*/
/*function plugin_user_changed_profile($uid)
{
   USES_profile_functions();
   PRF_saveData($_POST, $uid);
}*/


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
    if (version_compare(GVERSION, '1.6.0', '<')) {
        $file = 'config.legacy.html';
    } else {
        $file = 'config.html';
    }
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
    $tmp = PRF_getDefs($uid);

    $retval = "<profile>\n";
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
 * @deprecated
 * @param  string  $table  Name of table
 * @param  string  $id_fld Name of "id" field
 */
function X_PRF_reorderDef($table, $id_fld='id')
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

    while ($A = DB_fetchArray($result, false)) {

        if ($A['orderby'] != $order) {  // only update incorrect ones
            $sql = "UPDATE {$_TABLES[$table]}
                SET orderby = '$order'
                WHERE $id_fld = '" . DB_escapeString($A[$id_fld]) . "'";
            DB_query($sql, 1);
        }
        $order += $stepNumber;
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
        if (PRF_isAdmin() ||
                SEC_hasRights($_PRF_CONF['pi_name'] . '.manage')) {
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
 * Helper function to get a template object.
 *
 * @param   string  $name   Base name of template, e.g. "editor"
 * @param   string  $id     ID of template, e.g. "editform"
 * @param   string  $path   Optional path if not "templates/"
 * @return  string      Full filename, e.g. "editor.uikit.thtml"
 */
function PRF_getTemplate($name, $id, $path='')
{
    global $_PRF_CONF;

    // May use "switch" later, for now there are only two options
    $tpltype = $_PRF_CONF['_is_uikit'] ? '.uikit' : '';
    $filename = "$name$tpltype.thtml";
    if (substr($path, 0, 9) != 'templates') {
        $path = 'templates/' . $path;
    }
    if (!file_exists(PRF_PI_PATH . "$path/$filename")) {
        $filename = "$name.thtml";
    }
    $T = new Template(PRF_PI_PATH . $path);
    $T->set_file($id, $filename);
    return $T;
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

?>
