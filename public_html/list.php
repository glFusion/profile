<?php
/**
*   Display and export the custom member lists for the Custom Profile plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2018 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/** Import core glFusion libraries */
require_once '../lib-common.php';

// Make sure the plugin is installed and enabled
if (!in_array('profile', $_PLUGINS)) {
    COM_404();
}

USES_profile_functions();

// Retrieve input variables.
COM_setArgNames(array('listid', 'action'));
$listid = COM_getArgument('listid');
$action = COM_getArgument('action');

switch ($action) {
case 'export':
    // Export member list to csv
    $PL = new \Profile\UserList($listid);
    $content = $PL->Export();
    //$content = PRF_memberList_export($listid);
    if (!empty($content)) {
        // If content received, write it out.  Otherwise fall through to the
        // list display
        header('Content-type: text/csv');
        header('Content-Disposition: attachment; filename="'.$listid.'.csv"');
        echo $content;
        exit;
    }
    break;

case 'pdf':
    $PL = new \Profile\pdfList($listid);
    $content = $PL->Render();
    if (!empty($content)) {
        exit;
    }
    break;

case 'html':
    $PL = new \Profile\htmlList($listid);
    $content = $PL->Render();
    if (!empty($content)) {
        echo $content;
        exit;
    }
    break;

default:
    // Display a member list
    $PL = new \Profile\UserList($listid);
    $PL->showExport();      // show the export link to qualified users
    $PL->showMenu();        // show the tabbed menu of lists
    $PL->hasExtras();       // show the search form & query limit selector
    $content = $PL->Render();
    break;
}

$output = PRF_siteHeader();
$output .= $content;
$output .= PRF_siteFooter();
echo $output;
exit;


?>
