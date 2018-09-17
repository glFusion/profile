<?php
/**
*   Dutch language file for the Custom Profile plugin
*   @author     Eric Starkenburg <Zilverballs@home.nl>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

$LANG_PROFILE = array(
'menu_title' => 'Aangepaste Profielen',
'list_none_avail' => 'No profile lists available.',
'list_err_unserial' => 'An error occurred unserializing the fields.',
'admin_title' => 'Beheer Aangepaste Profielen',
'add_profile_item' => 'Voeg een nieuw veld toe',
'list_profiles' => 'Toon profielvelden',
'block_title'   => 'Aangepaste Profielvelden',
'yes'       => 'Ja',
'no'        => 'Nee',
'reset'     => 'Reset Formulier',
'reset_perms'   => 'Reset Permissions',
'search_users' => 'Search Users',
'undefined' => 'Undefined',
'empty' => 'Empty or Any',
'contains'  => 'Contains',
'field'     => 'Field',
'none'      => 'None',
'q_searchable' => 'Searchable?',
'q_sortable'    => 'Sortable?',
'hdr_text'  => 'Header Text',
'include'   => 'Include',
'def_sort'  => 'Default Sort',

'hlp_fld_order' => 'De volgorde geeft aan waar het veld op het formulier verschijnt t.o.v. andere velden en kunnen later worden gewijzigd.',
'hlp_fld_value' => 'De waarde heeft meerdere betekenissen, afhankelijk van het veldtype:<br><ul><li>Voor tekstvelden is dit de standaard waarde bij nieuwe invoer</li><li>Voor vinkboxen is dit de waarde indien aangevinkt (meestal 1).</li><li>Voor Radio Buttons en Dropdowns is dit een tekenreeks:prompt paren voor gebruik.  Dit is in het formaat "waarde1:prompt1;waarde2:prompt2;..."</li></ul>',
'hlp_fld_mask' => 'Vul masker in (bijv."99999-9999" voor een US postcode).',
'hlp_fld_enter_format' => 'Enter the format string',
'hlp_fld_enter_default' => 'Enter the default value for this field',
'hlp_fld_def_option' => 'Enter the option name to be used for the default',
'hlp_fld_def_option_name' => 'Enter the default option name',
'hlp_fld_chkbox_default' => 'Enter "1" for checked, "0" for unchecked.',

'help'  => array(
    'list' => 'Maak en wijzig profielvelden',
    'editform' => 'Maak een nieuwe profieldefinitie.  De volgorde geeft aan waar de velden of het formulier verschijnen relatief t.o.v. andere velden en kunnen later nog worden gewijzigd.',
    'searchusers' => 'Enter the values to be used for the user search.  Check the box under "Empty?" if the field should be empty, otherwise empty fields are ignored.',
    'lists' => 'Create and edit profile lists.  Copy the link to the List ID for placemet in menus and other content.',
    'editlist' => 'Define a profile list view.  Select the fields to be included, change the order and column headers if desired.',
    'resetpermform' => 'Reset all field permissions to their default values.',
),

'orderby'   => 'Volgorde',
'name'      => 'Naam',
'type'      => 'Type',
'enabled'   => 'Ingeschakeld',
'spancols'  => 'Span Columns',
'required'  => 'Verplicht',
'msg_fld_missing' => '%s is required but not filled in.',
'msg_mgr_override' => 'Validation Errors overriden by Manager.',
'hlp_required' => 'This item cannot be left blank',
'hlp_visible' => 'This field is visible in your public profile',
'hlp_invisible' => 'This field is NOT visible in your public profile',
'user_reg'  => 'Registratie',
'readonly'  => 'Alleen-Lezen',
'invisible' => 'Invisible',
'select'    => '--Selecteer--',
'usermenu'  => 'Bekijk Leden',

'textprompt'    => 'Tekst Aanwijzer',
'help_text'     => 'Help Text',
'fieldname'     => 'Veldnaam',
'fieldtype'     => 'Veldtype',
'inputlen'      => 'Invoer Veldlengte',
'maxlen'        => 'Maximum Invoerlengte',
'value'         => 'Waarde',
'defvalue'      => 'Standaard Waarde',
'showtime'      => 'Toon de Tijd',
'hourformat'    => '12 of 24 uur Formaat',
'hour12'        => '12-uur',
'hour24'        => '24-uur',
'format'        => 'Formaat',
'input_format'  => 'Input Format',
'autogen'       => 'Auto-Genereren',
'mask'          => 'Veld Masker',
'stripmask'     => 'Verwijder Masker Karakters',
'ent_registration' => 'Invullen bij Registratie',
'pos_after'     => 'Positioneer Na',
'nochange'      => 'Geen Wijziging',
'first'         => 'Eerste',
'permissions'   => 'Rechten',
'defgroup'      => 'Standaard Groep',
'month'         => 'Month',
'day'           => 'Day',
'year'          => 'Year',

'fld_types'     => array(
    'text' => 'Text',
    'textarea' => 'Textarea',
    'checkbox' => 'Checkbox',
    'select' => 'Dropdown',
    'radio' => 'Radio Buttons',
    'date' => 'Date',
    'statictext' => 'Static',
    'account' => 'User Account',
),

'fieldset1' => 'Additionele Profiel Instellingen',
'list_view_grp' => 'List viewable by',
'list_incl_grp' => 'Group to include',
'listid'        => 'List ID',
'title'         => 'Title',
'lists'         => 'Lists',
'newlist'       => 'New List',
'rows'          => 'Rows',
'columns'       => 'Columns',
'avatar'        => 'Avatar',
'checked'       => 'Checked',
'unchecked'     => 'Un-Checked',
'any'           => 'Any',
'incl_user_stat' => 'Include user status',
'incl_exp_stat' => 'Include account status',
'name_format0'  => 'First Last',
'name_format1'  => 'Last, First',
'list_footer'   => '<span class="profile_private">Highlighted</span> entries indicate members who have not elected to be listed in the member listing.',
'missing_email' => 'Your email address is not included in your account profile. This may happen if you log in using the Facebook link. Please visit <a href="' . $_CONF['site_url'] . '/usersettings.php?mode=edit">your account settings</a> to update your email address so you will receive notifications and subscriptions.',
'select_date' => 'Click for a date picker',
'toggle_success' => 'Item has been updated.',
'toggle_failure' => 'Error updating item.',
'q_conf_del' => 'Do you really want to delete this item?',
'move_up'   => 'Move Up',
'move_dn'   => 'Move Down',
'click_to_change' => 'Click to change',

// begin membership fields
'del_selected' => 'Delete Selected',
'export'    => 'Export CSV',
'submitter' => 'Submitter',
'submitted' => 'Submitted',
'orderby'   => 'Order',
'name'      => 'Name',
'fullname'  => 'Full Name',
'fname'     => 'First Name',
'lname'     => 'Last Name',
'address1'   => 'Address',
'city'      => 'City',
'state'     => 'State',
'zip'       => 'Zip',
'phone'     => 'Phone',
'cell'      => 'Cell',
'email'     => 'Email',
'type'      => 'Type',
'status'    => 'Status',
'current'   => 'Current',
'arrears'   => 'In Arrears',
'expired'   => 'Expired',
'expires'   => 'Expires',
'position'  => 'Position',
'division'  => 'Division',
'joined'    => 'Joined',
'select'    => 'Select',
'move'      => 'Move',
'usermenu'  => 'View Members',
'membertype'    => 'Member Type',
'expiration'    => 'Expiration',
'list_member_dir' => 'List in Member Directory?',
'username'  => 'Username',
'email_addr' => 'E-Mail Address',
'parent_uid' => 'Parent Account',
'child_accounts' => 'Child Accounts',
// END MEMBERSHIP FIELDS
'add_value' => 'Add a new value',
'show_in_profile' => 'Show in Profile',
'save_error_title' => 'Error Saving Custom Profile',
'displayed' => 'Displayed',
'all_fields' => 'All Fields',
);

$PLG_profile_MESSAGE03  = 'There was an error getting the plugin version.';
$PLG_profile_MESSAGE04  = 'There was an error upgrading the plugin.';
$PLG_profile_MESSAGE05  = 'There was an error updating the plugin version number.';
$PLG_profile_MESSAGE06  = 'The plugin is already up to date.';
$PLG_profile_MESSAGE100 = 'Error Saving Profile';
$PLG_profile_MESSAGE101 = 'System items cannot be edited.';
$PLG_profile_MESSAGE102 = 'Permissions have been reset on all items.';
$PLG_profile_MESSAGE103 = 'Error saving profile definition.  Possibly a duplicate item name.';
$PLG_profile_MESSAGE104 = 'Invalid field name provided.';

$LANG_MYACCOUNT['pe_profileprefs'] = 'Aangepast Profiel';

/** Language strings for the plugin configuration section */
$LANG_configsections['profile'] = array(
    'label' => 'Profile',
    'title' => 'Custom Profile Configuration'
);

$LANG_configsubgroups['profile'] = array(
    'sg_main' => 'Hoofd Instellingen'
);

$LANG_fs['profile'] = array(
    'fs_main' => 'Algemene Instellingen',
    'fs_lists' => 'List Settings',
    'fs_permissions' => 'Standaard Veld Rechten',
);

$LANG_confignames['profile'] = array(
    'default_permissions' => 'Standaard Rechten',
    'defgroup' => 'Standaard Groep',
    'showemptyvals' => 'Display empty values?',
    'grace_expired' => 'Expiration Grace Period (days)',
    'date_format'   => 'Date format',
    'list_incl_admin' => 'Include admin account in lists?',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['profile'] = array(
    0 => array('Ja' => 1, 'Nee' => 0),
    1 => array('Ja' => TRUE, 'Nee' => FALSE),
    2 => array('Zoals Opgeslagen' => 'submitorder', 'O.b.v. Stemmen' => 'voteorder'),
    3 => array('Ja' => 1, 'Nee' => 0),
    6 => array('Normaal' => 'normal', 'Blokken' => 'blocks'),
    9 => array('Nooit' => 0, 'Indien Wachtrij' => 1, 'Altijd' => 2),
    10 => array('Nooit' => 0, 'Altijd' => 1, 'Geaccepteerd' => 2, 'Geweigerd' => 3),
    12 => array('Geen Toegang' => 0, 'Alleen-Lezen' => 2, 'Lezen-Schrijven' => 3),
    13 => array('Month Day Year' => 1, 'Day Month Year' => 2),
);


?>
