<?php
/**
*   Home page for the Custom Profile plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2010 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.0.2
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

require_once '../lib-common.php';
if (!in_array('profile', $_PLUGINS) || !is_array($_PRF_CONF)) {
    COM_404();
}

USES_lib_user();

// some vars that will be moved to user-selectable values:
// Default to Logged-in Users
$group = (int)PRF_getParam('group', $_PRF_CONF['default_grp']);
$srchval = PRF_getParam('srchval');
$conf_grplist = isset($_PRF_CONF['groups']) ? $_PRF_CONF['groups'] : '';

// Redirect to an error page if login is required and this user isn't
if ($_CONF['loginrequired']) $_PRF_CONF['login_required'] = 1;
if ($_PRF_CONF['login_required'] && COM_isAnonUser()) {
    $retval = COM_siteHeader('menu', $LANG_LOGIN[1]);
    $retval .= SEC_loginRequiredForm();
    $retval .= COM_siteFooter();
    echo $retval;
    exit;
}

echo COM_siteHeader();
$T = new Template(PRF_PI_PATH . 'templates/');
$T ->set_file(array(
    'header' => 'header.thtml',
    'oneuser' => 'oneuser.thtml',
    'footer' => 'footer.thtml',
));

$T->set_var('myself', $_SERVER['PHP_SELF']);
$T->set_var('srchval', $srchval == '' ? 'Search' : $srchval);

// Create the SQL to locate site members.
//$grplist = PRF_GetGroups($group);
$group = (int)$group;
if ($group < 1) $group = 13;    // Avoid nasty surprises

// If we're excluding rather than including groups, then make 
// the query negative.
$grplist = DB_escapeString($conf_grplist);
if (empty($grplist)) {
    echo COM_siteFooter();
    exit;
}

$exclude = isset($_PRF_CONF['exclude_groups']) && 
                $_PRF_CONF['exclude_groups'] == true ? 'NOT' : '';
$sel_list = COM_optionList($_TABLES['groups'], 'grp_id, grp_name', $group, 1,
    "grp_id $NOT IN ($grplist)");
$T->set_var('grp_select', $sel_list);

$min_uid = isset($_PRF_CONF['include_admin']) &&
            $_PRF_CONF['include_admin'] == true ? 1 : 2;
$sql = "SELECT u.uid, u.fullname, u.email, u.homepage, u.photo
        FROM {$_TABLES['users']} u
        RIGHT JOIN {$_TABLES['userinfo']} i 
            ON i.uid = u.uid
        LEFT JOIN {$_TABLES['group_assignments']} ga
            ON u.uid = ga.ug_uid
        WHERE u.uid > $min_uid 
        AND ga.ug_main_grp_id = $group ";

// Add the search parameters if any were supplied
if ($srchval != '' && !empty($_PRF_CONF['search_fields'])) {
    $srchvals = split(' ', $srchval);
    $phrases = array();
    if (!isset($_PRF_CONF['search_fields']) ||
            !is_array($_PRF_CONF['search_fields'])) {
        $_PRF_CONF['search_fields'] = array();
    }
    foreach ($_PRF_CONF['search_fields'] as $field) {
        $phrase = array();
        foreach ($srchvals as $value) {
            $value = DB_escapeString($value);
            $phrase[] = "$field LIKE '%{$value}%'";
        }
        $phrases[] = '(' . join(' AND ', $phrase) . ')';
    }
    $search_phrase = join(' OR ', $phrases);
    $sql .= " AND ($search_phrase)";
}
//echo $sql;

$result = DB_query($sql);

// From the full query just executed, get the total number of items to
// be displayed.  Figure out the page number, and re-execute the query
// with the appropriate LIMIT clause.
$totalEntries = DB_numRows($result);
$maxList = isset($_PRF_CONF['perpage']) ? (int)$_PRF_CONF['perpage'] : 20;
if ($maxList < 1) $maxList = 20;
if ($totalEntries <= $maxList)
    $totalPages = 1;
elseif ($totalEntries % $maxList == 0)
    $totalPages = $totalEntries / $maxList;
else
    $totalPages = ceil($totalEntries / $maxList);

$page = COM_applyFilter(PRF_getParam('start'), true);
if ($page < 1 || $page > $totalPages)
    $page = 1;

if ($totalEntries == 0)
    $startEntry = 0;
else
    $startEntry = $maxList * $page - $maxList + 1;

if ($page == $totalPages)
    $endEntry = $totalEntries;
else
    $endEntry = $maxList * $page;

$prePage = $page - 1;
$nextPage = $page + 1;
$initEntry = $maxList * $page - $maxList;

// Create the page menu string for display if there is more
// than one page
$pageMenu = '';
if ($totalPages > 1) {
    $baseURL = "{$_SERVER['PHP_SELF']}?page=entries&eventID={$dummy}";
    $pageMenu = COM_printPageNavigation($baseURL, $page, $totalPages, "start=");
}
$T->set_var('pagemenu', $pageMenu);
$T->parse('output', 'header');
echo $T->finish($T->get_var('output'));

// Append the limit clause to the query and re-execute it
$sql .= " ORDER BY SUBSTRING_INDEX(fullname,' ',1) ASC
        LIMIT $initEntry,$maxList";
//echo $sql;
$result = DB_query($sql);

// Determine whether a link to the users' profiles should be shown
$show_profile_link = false;
if (isset($_PRF_CONF['groups_link_profile']) && 
        is_array($_PRF_CONF['groups_link_profile'])) {
    foreach ($_PRF_CONF['groups_link_profile'] as $grp_id) {
        if (in_array($grp_id, $_GROUPS)) {
            $show_profile_link = true;
            break;
        }
    }
}
// Set the image width to a sensible default if it's not configured
$img_width = isset($_PRF_CONF['img_width']) && (int)$_PRF_CONF['img_width'] > 0 ? 
            (int)$_PRF_CONF['img_width'] : 100;

while ($row = DB_fetchArray($result)) {
        $photo = USER_getPhoto ($row['uid'], $row['photo'], $row['email'], 
                                $img_width, 0);
        $caption = "{$row['fullname']}<br />{$row['homepage']}";
        if (isset($_PRF_CONF['show_email']) && $_PRF_CONF['show_email'] == true) {
            $caption .= '<br />' . $row['email'];
        }

        $T->set_var('fullname', htmlspecialchars($row['fullname']));
        $T->set_var('caption', htmlspecialchars($caption));
        $T->set_var('img_width', $img_width);

        if ($show_profile_link == true || 
            (isset($_PRF_CONF['link_own_profile']) && 
                $_PRF_CONF['link_own_profile'] && 
            $row['uid'] == $_USER['uid'])) {
            $T->set_var('profile_url', 
                "{$_CONF['site_url']}/users.php?mode=profile&uid={$row['uid']}");
        } else {
            $T->set_var('profile_url', '');
        }

        //$T->set_var('photo_url', PHOTOURL . $photo);
        $T->set_var('photo_url', $photo);
        
        $T->parse('output', 'oneuser', false);
        echo $T->finish($T->get_var('output'));
}

$T->parse('output', 'footer');
echo $T->finish($T->get_var('output'));

echo COM_siteFooter();


/**
 *  Retrieve the groups to show in the selection dropdown.
 *  Takes the list from the configuration, converts it to an array 
 *  and gets the corresponding names from the database.  If a name 
 *  is given in the configuraton for a group, that name is used instead.
 *
 *  @param  string  &$group Pointer to the group to use as selected
 *  @return array   Array of id=>groupname values
 */
function X_PRF_GetGroups(&$group)
{
    global $_TABLES, $_PRF_CONF;

    $A = array();

    // If "no group" is an option, prepend it and use 0 as the value.
    /*if ($nogroup) {
        $A['None'] = 0;
    }*/

    // If we're excluding rather than including groups, then make 
    // the query negative.
    $grplist = DB_escapeString($_PRF_CONF['groups']);
    $exclude = $_PRF_CONF['exclude_groups'] == true ? 'NOT' : '';

    $sql = "SELECT grp_id, grp_name FROM {$_TABLES['groups']}";

    // If a group list is specified, add it as a condition with the
    // specified keyword.
    if ($grplist!= '') {
        $sql .= " WHERE grp_id $NOT IN ($grplist)";
    }

    $sql .= " ORDER BY grp_name ASC";
    //echo $sql;

    $result = DB_query($sql);
    if ($result && DB_numRows($result) > 0) {
        $firstgroup = 0;
        while ($row = DB_fetchArray($result)) {

            if ($firstgroup == 0)
                $firstgroup = $row['grp_id'];

            if (isset($_PRF_CONF['group_names'][$row['grp_id']])) {
                $row['grp_name'] = 
                    $_PRF_CONF['group_names'][$row['grp_id']];
            }

            $A[$row['grp_id']] = htmlspecialchars($row['grp_name']);

        }

        if (!isset($A[$group]))
            $group = $firstgroup;
    }

    return $A;

}

function PRF_getParam($name, $defvalue = '')
{
    if (isset($_POST[$name]))
        return $_POST[$name];
    elseif (isset($_GET[$name]))
        return $_GET[$name];
    else
        return $defvalue;
}

?>
