glProfile - Change Log

Version 1.1.4
=============
- Ensure that sys_fname and sys_lname fields are created during update if missing.
- Remove realtime field validation if using uikit
- Update README to indicate reliance on LGLib plugin and lglib_messages header var.
- Add PDF membership list
- Shows a reminder message at login to users who have no email address.

glProfile - 1.1.2 (Released 2011-11-04) [ View Issues ]
=======================================
- 0000478: [UI] HTML characters being encoded twice (lee) - resolved.
- 0000481: [Administration] Deleting a field doesn't remove it from the lists (lee) - resolved.
- 0000486: [Configuration] Add an Account-type field (lee) - resolved.
- 0000484: [Administration] Remove grace period for arrears (lee) - resolved.
- 0000474: [Lists] Add a selection to the list definition for user status (lee) - resolved.
- 0000475: [Lists] Searching a list containing values from other plugins causes a SQL error (lee) - resolved.
- 0000467: [Administration] Hard-coded table names in PRF_saveData() (lee) - resolved.
[7 issues]

glProfile - 1.1.1 (Released 2011-08-26) [ View Issues ]
=======================================
- 0000464: [UI] Selection values aren't extracted properly for dropdown lists (lee) - resolved.
- 0000461: [UI] Add a Textarea-type field (lee) - resolved.
- 0000462: [UI] Allow lists to show the user's image (lee) - resolved.
- 0000463: [glFusion Integration] Admins can't add users if any profile fields are required (lee) - resolved.
[4 issues]

glProfile - 1.1.0 (Released 2011-07-23) [ View Issues ]
=======================================
- 0000412: [UI] Default checkbox values not being honored (lee) - resolved.
- 0000335: [UI] Add Memberlist function (lee) - resolved.
[2 issues]

glProfile - 1.0.2 (Released 2010-05-25) [ View Issues ]
=======================================

Released 2010-05-25

- 0000393: [Administration] Change date fields to be stored in MySQL DATETIME format (lee) - resolved.
[1 issue]

glProfile - 1.0.1
=================

Minor bug fixes and enhancements from 1.0.0

- 0000385: [UI] PHP error when using IE (lee) - resolved.
- 0000381: [UI] Read-only fields are empty after users edit their own profile (lee) - resolved.
- 0000380: [Administration] Checkbox values aren' t saved (lee) - resolved.
- 0000375: [UI] Prompts are left-aligned in user edit screen (lee) - resolved.
- 0000374: [Language] Multiple languages not working (lee) - resolved.


glProfile - 1.0.0
=================

Version 1.0.0

- 0000347: [glFusion Integration] Implement plugin_xxxx_profile functions for compatibility with glFusion 1.1.7 (lee) - resolved.
- 0000339: [Configuration] Add capability to auto-generate field content (lee) - resolved.


glProfile - 0.0.2
=================

Version 0.0.2 (14 Oct 2009)
Adds support for glFusion permissions applied to individual fields. Also begin testing input validation via iMask.

- 0000337: [UI] Allow admins to restrict access to users viewing others' profiles (lee) - resolved.
- 0000336: [UI] plugin_itemPresave_profile function rejects contact emails (lee) - resolved.
- 0000333: [Administration] Sample CUSTOM_userEdit function doesn't check if plugin is enabled (lee) - resolved.


