# Custom Profile Plugin - Change Log

## Version 1.2.8
Release 2022-02-16
- Fix #11, no default allowed for text columns in recent MySQL versions
- Log profile errors when overriden by admin saving user

## Version 1.2.7
Release 2021-06-29
- Fix bug calling undefined function during user profile update

## Version 1.2.6
Release 2021-06-20
- Use correct tag to mark multicheck boxes as checked
- Remove field caching, interferes with changing field type

## Version 1.2.5
Release 2021-06-18
- Move field rendering to templates
- Enable user confirmation emails when profile is changed
- Hide time display if timeformat is "none"
- Pass group IDs to plugins from profile lists

## Version 1.2.4
Release 2021-04-07
- Fix checkbox field validation.

## Version 1.2.3
Release 2021-04-06
- Return text error message from plugin_itemPreSave_profile() instead of string code.

## Version 1.2.2
Release 2021-01-24
- Check if first/last name fields are enabled before creating fullname from them.

## Version 1.2.1
Release 2021-01-20
- Fix saving first and last names during registration.

## Version 1.2.0
Release 2021-01-18
- Remove support for non-uikit themes
- Enable web services to allow `PLG_invokeService()` to be used
- Fix namespace usage
- Implement caching for glFusion 2.0.0+
- Refactor profile field classes by type
- Add `privacy_export()` function
- Require LGLib 1.0.7+ for NameParser class
- Ensure that `sys_fname` and `sys_lname` fields are created during update if missing.
- Remove realtime field validation if using uikit
- Update README to indicate reliance on LGLib plugin and `lglib_messages` header var.
- Add PDF membership list
- Shows a reminder message at login to users who have no email address.
- UIKit template updates
- Change toggle AJAX from XML to JSON
- Fix user search for multi-option items.
- Modernize using UIkit icons.
- Add dvlpupdate.php development update utility.
- SQL Strict Mode fixes.
- Create first/last name from fullname (or vice-versa) at registration.
- Deprecate non-UTF-8 language files.
- Deprecate unused API functions.
- Change unused system fields to regular fields.
- Deprecate expiration status in lists, will be provided by Membership plugin.

## Version 1.1.3
Released 2013-06-13

## Version 1.1.2
Released 2011-11-04
- 0000478: [UI] HTML characters being encoded twice (lee) - resolved.
- 0000481: [Administration] Deleting a field does not remove it from the lists (lee) - resolved.
- 0000486: [Configuration] Add an Account-type field (lee) - resolved.
- 0000484: [Administration] Remove grace period for arrears (lee) - resolved.
- 0000474: [Lists] Add a selection to the list definition for user status (lee) - resolved.
- 0000475: [Lists] Searching a list containing values from other plugins causes a SQL error (lee) - resolved.
- 0000467: [Administration] Hard-coded table names in `PRF_saveData()` (lee) - resolved.
[7 issues]

## Version 1.1.1
Released 2011-08-26
- 0000464: [UI] Selection values are not extracted properly for dropdown lists (lee) - resolved.
- 0000461: [UI] Add a Textarea-type field (lee) - resolved.
- 0000462: [UI] Allow lists to show the user's; image (lee) - resolved.
- 0000463: [glFusion Integration] Admins can't add users if any profile fields are required (lee) - resolved.
[4 issues]

## Version 1.1.0
Released 2011-07-23
- 0000412: [UI] Default checkbox values not being honored (lee) - resolved.
- 0000335: [UI] Add Memberlist function (lee) - resolved.
[2 issues]

## Version 1.0.2
Released 2010-05-25
- 0000393: [Administration] Change date fields to be stored in MySQL DATETIME format (lee) - resolved.
[1 issue]

## Version 1.0.1
Minor bug fixes and enhancements from 1.0.0
- 0000385: [UI] PHP error when using IE (lee) - resolved.
- 0000381: [UI] Read-only fields are empty after users edit their own profile (lee) - resolved.
- 0000380: [Administration] Checkbox values aren't saved (lee) - resolved.
- 0000375: [UI] Prompts are left-aligned in user edit screen (lee) - resolved.
- 0000374: [Language] Multiple languages not working (lee) - resolved.

## Version 1.0.0
- 0000347: [glFusion Integration] Implement plugin_xxxx_profile functions for compatibility with glFusion 1.1.7 (lee) - resolved.
- 0000339: [Configuration] Add capability to auto-generate field content (lee) - resolved.

## Version  0.0.2
Released 2009-10-14
Adds support for glFusion permissions applied to individual fields. Also begin testing input validation via iMask.
- 0000337: [UI] Allow admins to restrict access to users viewing others' profiles (lee) - resolved.
- 0000336: [UI] plugin_itemPresave_profile function rejects contact emails (lee) - resolved.
- 0000333: [Administration] Sample CUSTOM_userEdit function doesn't check if plugin is enabled (lee) - resolved.
