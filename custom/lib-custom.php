<?php
/**
 * Custom functions for including profile information into registration
 * and account settings pages.
 *
 * These functions should be included in private/system/lib-custom.php
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

/**
 * Generate content for a specific field.
 * This function generates a string for a field named "state".
 *
 * The function should be named "CUSTOM_profile_autogen_{fieldname}.
 * Note that the internal field name is "prf_state", which should be used
 * in the function name. The internal fields names are typically
 * "prf_{name}" and can be seen in the profile item list.
 *
 * @param   object  $Fld    Field Object
 * @return  string          Field Content
 */
/*function CUSTOM_profile_autogen_prf_state($Fld)
{
    return 'CA';
}*/

/**
 * Generate content for any field.
 * This function handles all field names and types that are not handled
 * by a field-specific function (see above).
 *
 * @param   object  $Fld    Field Object
 * @return  string          Field Content
 */
/*function CUSTOM_profile_autogen($Fld)
{
    switch ($Fld->name) {
        case 'prf_membershipid':
            // Generate a random Membership ID
            return 'member' . rand(1,99);
            break;
    }

    switch ($Fld->type) {
        case 'text':
            // Return a static text value
            return "I'm a text field";
            break;
        case 'date':
            // Return a static date value
            return '07/04/1776';
            break;
    }

    // Should always return something even if empty
    return '';
}*/

?>
