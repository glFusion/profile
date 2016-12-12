<?php
/**
*   Display and export the custom member lists for the Custom Profile plugin.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2010-2011 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.1
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
USES_profile_class_list();

// Retrieve input variables.
COM_setArgNames(array('listid', 'action'));
$listid = COM_getArgument('listid');
$action = COM_getArgument('action');

switch ($action) {
case 'export':
    // Export member list to csv
    $PL = new prfList($listid);
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
    USES_profile_class_pdflist();
    $PL = new prfPdfList($listid);
    $content = $PL->Render();
    if (!empty($content)) {
        exit;
    }
    break;

case 'html':
    USES_profile_class_htmlList();
    $PL = new prfHtmlList($listid);
    $content = $PL->Render();
    if (!empty($content)) {
        echo $content;
        exit;
    }
    break;

default:
    // Display a member list
    $PL = new prfList($listid);
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
