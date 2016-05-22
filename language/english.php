<?php
/**
*   English language file for the Custom Profile plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2011 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$LANG_PROFILE = array(
'menu_title' => 'Custom Profiles',
'list_none_avail' => 'No profile lists available.',
'list_err_unserial' => 'An error occurred unserializing the fields.',
'admin_title' => 'Administer Custom Profiles',
'add_profile_item' => 'Add a new item',
'add_value' => 'Add a new value',
'list_profiles' => 'List profile items',
'block_title'   => 'Custom Profile Fields',
'yes'       => 'Yes',
'no'        => 'No',
'reset'     => 'Reset Form',
'reset_perms'   => 'Reset Permissions',
'search_users' => 'Search Users',
'undefined' => 'Undefined',
'empty' => 'Empty',
'contains'  => 'Contains',
'field'     => 'Field',
'none'      => 'None',
'q_searchable' => 'Searchable?',
'q_sortable'    => 'Sortable?',
'hdr_text'  => 'Header Text',
'include'   => 'Include',
'def_sort'  => 'Default Sort',

'hlp_fld_order' => 'The Order indicates where the item will appear on forms relative to other items, and may be changed later.',
'hlp_fld_value' => 'The Value has several meanings, depending upon the Field Type:<br><ul><li>For Text fields, this is the default value used in new entries</li><li>For Checkboxes, this is the value returned when the box is checked (usually 1).</li><li>For Radio Buttons and Dropdowns, this is a string of value:prompt pairs to be used.  This is in the format "value1:prompt1;value2:prompt2;..."</li></ul>',
'hlp_fld_mask' => 'Enter the data mask (e.g. "99999-9999" for a US zip code).',
'hlp_fld_enter_format' => 'Enter the format string',
'hlp_fld_enter_default' => 'Enter the default value for this field',
'hlp_fld_def_option' => 'Enter the option name to be used for the default',
'hlp_fld_def_option_name' => 'Enter the default option name',
'hlp_fld_chkbox_default' => 'Enter "1" for checked, "0" for unchecked.',

'orderby'   => 'Order',
'name'      => 'Name',
'type'      => 'Type',
'enabled'   => 'Enabled',
'show_in_profile' => 'Show in Profile',
'spancols'  => 'Span Columns',
'required'  => 'Required',
'msg_fld_missing' => '%s is required but not filled in.',
'hlp_required' => 'This item cannot be left blank',
'hlp_visible' => 'This field is visible in your public profile',
'hlp_invisible' => 'This field is NOT visible in your public profile',
'user_reg'  => 'Registration',
'readonly'  => 'Read-Only',
'invisible' => 'Invisible',
'select'    => '--Select--',
'usermenu'  => 'View Members',

'textprompt'    => 'Text Prompt',
'help_text'     => 'Help Text',
'fieldname'     => 'Field Name',
'fieldtype'     => 'Field Type',
'inputlen'      => 'Input Field Length',
'maxlen'        => 'Maximum Entry Length',
'value'         => 'Value',
'defvalue'      => 'Default Value',
'showtime'      => 'Show Time',
'hourformat'    => '12 or 24 hour format',
'hour12'        => '12-hour',
'hour24'        => '24-hour',
'format'        => 'Format',
'input_format'  => 'Input Format',
'autogen'       => 'Auto-Generate',
'mask'          => 'Field Mask',
'stripmask'     => 'Strip Mask Characters from Value',
'ent_registration' => 'Enter at Registration',
'pos_after'     => 'Position After',
'nochange'      => 'No Change',
'first'         => 'First',
'permissions'   => 'Permissions',
'defgroup'      => 'Default Group',
'month'         => 'Month',
'day'           => 'Day',
'year'          => 'Year',

'fld_types'     => array(
    'text' => 'Text',
    'textarea' => 'Textarea',
    'checkbox' => 'Checkbox',
    'multicheck' => 'Multi-Checkbox',
    'select' => 'Dropdown',
    'radio' => 'Radio Buttons',
    'date' => 'Date',
    'static' => 'Static',
    'account' => 'User Account',
),

'help'  => array(
    'list' => 'Create and edit custom profile fields',
    'searchusers' => 'Enter the values to be used for the user search.  Check the box under "Empty?" if the field should be empty, otherwise empty fields are ignored.',
    'lists' => 'Create and edit profile lists.  Copy the link to the List ID for placement in menus and other content.',
    'editform' => 'Create a new custom profile definition.  The Order indicates where the item will appear on forms relative to other items, and may be changed later.',
    'editlist' => 'Define a profile list view.  Select the fields to be included, change the order and column headers if desired.',
    'resetpermform' => 'Reset all field permissions to their default values.',
),


'fieldset1' => 'Additional Profile Settings',
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
'save_error_title' => 'Error Saving Custom Profile',

// begin membership fields
'del_selected' => 'Delete Selected',
'export'    => 'Export CSV',
'displayed' => 'Displayed',
'all_fields' => 'All Fields',
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

$LANG_MYACCOUNT['pe_profileprefs'] = 'Custom Profile';

$LANG_configsubgroups['profile'] = array(
    'sg_main' => 'Main Settings'
);

$LANG_fs['profile'] = array(
    'fs_main' => 'General Settings',
    'fs_lists' => 'List Settings',
    'fs_permissions' => 'Default Field Permissions',
);

$LANG_confignames['profile'] = array(
    'default_permissions' => 'Default Permissions',
    'defgroup' => 'Default Group',
    'showemptyvals' => 'Display empty values?',
    'grace_expired' => 'Expiration Grace Period (days)',
    'date_format'   => 'Date format',
    'list_incl_admin' => 'Include admin account in lists?',
);

// Note: entries 0, 1, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['profile'] = array(
    0 => array('True' => 1, 'False' => 0),
    1 => array('True' => TRUE, 'False' => FALSE),
    2 => array('As Submitted' => 'submitorder', 'By Votes' => 'voteorder'),
    3 => array('Yes' => 1, 'No' => 0),
    6 => array('Normal' => 'normal', 'Blocks' => 'blocks'),
    9 => array('Never' => 0, 'If Submission Queue' => 1, 'Always' => 2),
    10 => array('Never' => 0, 'Always' => 1, 'Accepted' => 2, 'Rejected' => 3),
    12 => array('No access' => 0, 'Read-Only' => 2, 'Read-Write' => 3),
    13 => array('Month Day Year' => 1, 'Day Month Year' => 2),
);


?>
