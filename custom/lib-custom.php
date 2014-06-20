<?php
/**
*   Custom functions for including profile information into registration
*   and account settings pages.
*
*   These functions may be merged or appended to lib-custom.php
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2011 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


/*
*   Sample autogen functions to automatically generate field values.
*   Un-comment the functions to use them.  Otherwise, the default
*   PRF_autogen() function is used.
*/

/**
*   Generate content for a specific field.
*   This function generates a string for a field named "state".
*   The function should be named "CUSTOM_profile_autogen_{fieldname}
*
*   @return string  Field Content
*/
/*function CUSTOM_profile_autogen_state()
{
    return 'CA';
}
*/

/**
*   Generate content for any field.
*   This function handles all field names and types, and it completely
*   overrides the default PRF_autogen() function (which is to return
*   COM_makeSID()).
*
*   @param  string  $name   Field Name
*   @param  string  $type   Field Type ('text', 'date', 'checkbox', etc).
*   @return string          Field Content
*/
/*function CUSTOM_profile_autogen($name='', $type='')
{
    switch ($name) {
        case 'membershipid':
            // Generate a random Membership ID
            return 'member' . rand(1,99);
            break;
    }

    switch ($type) {
        case 'text':
            // Return a static text value
            return "I'm a text field";
            break;
        case 'date':
            // Return a static date value
            return '07/04/1776';
            break;
    }
}
*/

?>
