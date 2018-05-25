<?php
/**
*   Swedish language file for the Custom Profile plugin
*   @author     Daniel Toivonen <ero@festihouse.com>
*   @copyright  Copyright (c) 2009 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*   GNU Public License v2 or later
*   @filesource
*/

$LANG_PROFILE = array(
'menu_title' => 'Anpassade profiler',
'list_none_avail' => 'No profile lists available.',
'list_err_unserial' => 'An error occurred unserializing the fields.',
'admin_title' => 'Administrera Anpassade profiler',
'add_profile_item' => 'Läggg till nytt objekt',
'list_profiles' => 'Lista profilobjekt',
'block_title'   => 'Anpassade profilfält',
'yes'       => 'Ja',
'no'        => 'Nej',
'reset'     => 'Återställ',

'hlp_fld_order' => 'Ordningen visar var objektet kommer visas i formuläret i förhållande till andra objekt, och kan ändras senare.',
'hlp_fld_value' => 'Värdet har flera betydelser, beroende om fälttypen:<br><ul><li>För Textfält, detta är standardvärdet som används i nya poster</li><li>För Checkboxar, detta är värdet som returneras när en box är markerad (vanligtvis 1).</li><li>För knappar och menyer, detta är ett strängvärde:visar par som ska användas.  Detta är formatet "värde1:visar1;värde2:visar2;..."</li></ul>',
'hlp_fld_mask' => 'Skriv in en datavärde (t.ex. "99999-9999" för en SE postkod).',
'hlp_fld_enter_format' => 'Enter the format string',
'hlp_fld_enter_default' => 'Enter the default value for this field',
'hlp_fld_def_option' => 'Enter the option name to be used for the default',
'hlp_fld_def_option_name' => 'Enter the default option name',
'hlp_fld_chkbox_default' => 'Enter "1" for checked, "0" for unchecked.',

'orderby'   => 'Ordning',
'name'      => 'Namn',
'type'      => 'Typ',
'enabled'   => 'Aktiverad',
'required'  => 'Nödvändig',
'msg_fld_missing' => '%s is required but not filled in.',
'msg_mgr_override' => 'Validation Errors overriden by Manager.',
'user_reg'  => 'Registrering',
'readonly'  => 'Läs-endast',
'select'    => '--Välj--',
'usermenu'  => 'Visa medlemmar',

'spancols'  => 'Span Columns',
'hlp_required' => 'This item cannot be left blank',
'hlp_visible' => 'This field is visible in your public profile',
'hlp_invisible' => 'This field is NOT visible in your public profile',
'invisible' => 'Invisible',

'help_text'     => 'Help Text',
'input_format'  => 'Input Format',
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
    'static' => 'Static',
    'account' => 'User Account',
),


'help'  => array(
    'list' => 'Skapa eller ändra ett anpassad profilfält',
    'editform' => 'Skapa en ny anpassad profildefinition. Ordningen visar var objektet kommer visas i formuläret i förhållande till andra objekt, och kan ändras senare.<br />Hjälp',
    'searchusers' => 'Enter the values to be used for the user search.  Check the box under "Empty?" if the field should be empty, otherwise empty fields are ignored.',
    'lists' => 'Create and edit profile lists.  Copy the link to the List ID for placemet in menus and other content.',
    'editlist' => 'Define a profile list view.  Select the fields to be included, change the order and column headers if desired.',
    'resetpermform' => 'Reset all field permissions to their default values.',
),

'orderby'   => 'Ordning',
'name'      => 'Namn',
'type'      => 'Typ',
'enabled'   => 'Aktiverad',
'required'  => 'Nödvändig',
'user_reg'  => 'Registrering',
'readonly'  => 'Läs-endast',
'select'    => '--Välj--',
'usermenu'  => 'Visa medlemmar',

'textprompt'    => 'Textprompt',
'fieldname'     => 'Fältnamn',
'fieldtype'     => 'Fälttyp',
'inputlen'      => 'Fältlängd för inmatning',
'maxlen'        => 'Max teckenlängd',
'value'         => 'Värde',
'defvalue'      => 'Standard värde',
'showtime'      => 'Visa tid',
'hourformat'    => '12 eller 24 timmarsformat',
'hour12'        => '12-timmar',
'hour24'        => '24-timmar',
'format'        => 'Format',
'autogen'       => 'Auto-Generera',
'mask'          => 'Fältmaskering',
'stripmask'     => 'Skippa maskerat tecken från värde',
'ent_registration' => 'Skrivs in vid registrering',
'pos_after'     => 'Possition efter',
'nochange'      => 'Ingen ändring',
'first'         => 'Första',
'permissions'   => 'Tillstånd',
'defgroup'      => 'Standard grupp',

'fieldset1' => 'Ytterligare profilinställningar',
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
'reset_perms' => 'Reset Permissions',
'search_users' => 'Search Users',
'undefined' => 'Undefined',
'empty' => 'Tom eller något',
'contains' => 'Contains',
'field' => 'Field',
'none' => 'None',
'q_searchable' => 'Searchable?',
'q_sortable' => 'Sortable?',
'hdr_text' => 'Header Text',
'include' => 'Include',
'def_sort' => 'Default Sort',
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

$LANG_MYACCOUNT['pe_profileprefs'] = 'Anpassad profil';

/** Language strings for the plugin configuration section */
$LANG_configsections['profile'] = array(
    'label' => 'Profile',
    'title' => 'Custom Profile Configuration'
);

$LANG_configsubgroups['profile'] = array(
    'sg_main' => 'Huvudinställningar'
);

$LANG_fs['profile'] = array(
    'fs_main' => 'Generella inställingar',
    'fs_lists' => 'List Settings',
    'fs_permissions' => 'Standardfältstillstånd',
);

$LANG_confignames['profile'] = array(
    'default_permissions' => 'Standardtillstånd',
    'defgroup' => 'Standardgrupp',
    'showemptyvals' => 'Display empty values?',
    'grace_expired' => 'Expiration Grace Period (days)',
    'date_format'   => 'Date format',
    'list_incl_admin' => 'Include admin account in lists?',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['profile'] = array(
    0 => array('Sant' => 1, 'Falskt' => 0),
    1 => array('Sant' => TRUE, 'Falskt' => FALSE),
    2 => array('Efter inslämnade' => 'submitorder', 'Efter röster' => 'voteorder'),
    3 => array('Ja' => 1, 'Nej' => 0),
    6 => array('Normal' => 'normal', 'Block' => 'blocks'),
    9 => array('Aldrig' => 0, 'Om inlämningskö' => 1, 'Alltid' => 2),
    10 => array('Aldrig' => 0, 'Alltid' => 1, 'Godkända' => 2, 'Avvisad' => 3),
    12 => array('Ingen åtkomst' => 0, 'Läsa-endast' => 2, 'Läsa-Skriva' => 3),
    13 => array('Month Day Year' => 1, 'Day Month Year' => 2),
);


?>
