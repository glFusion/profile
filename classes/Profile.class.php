<?php
/**
 * Class to handle a user profile
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @since       v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;

/**
 * Class for a user's custom profile.
 * @package profile
 * @since   v1.2.0
 */
class Profile
{
    /** Profile fields, an array of objects.
     * @var array */
    private $fields = array();

    /** User ID.
     * @var integer */
    private $uid = 0;

    /**
     * Constructor.
     * Create profile object for the specified user ID, or the current
     * user if none specified.  If a key is requested, then just build
     * the forms for that key (requires a $uid).
     *
     * @param   integer $uid    Optional user ID
     */
    function __construct($uid=0)
    {
        global $_USER, $_PRF_CONF, $_TABLES;

        if ($uid == 0) {
            $uid = $_USER['uid'];
        }
        $this->uid = (int)$uid;
        if ($this->uid == 0) {
            $this->uid = 1;    // Anonymous
        }
        $this->Read();
    }


    /**
     * Get the profile object for the specified user.
     *
     * @param   integer $uid    User ID, default is current user
     * @return  object          Profile object for the user
     */
    public static function getInstance($uid=0)
    {
        global $_USER;

        if ($uid == 0) {
            $uid = $_USER['uid'];
        }
        $uid = (int)$uid;
        return new self($uid);
    }


    /**
     * Read the profile field definitions and values.
     *
     * @return  object  $this
     */
    private function Read()
    {
        global $_TABLES;

        // Get the field definitions, reading from the DB if not already done
        $key = 'proflle_defs_enabled';
        $defs = Cache::get($key);
        if ($defs === NULL) {
            $defs = array();
            $sql = "SELECT * FROM {$_TABLES['profile_def']}
                    WHERE enabled = 1
                    ORDER BY orderby ASC";
            $res = DB_query($sql);
            while ($A = DB_fetchArray($res, false)) {
                $defs[$A['name']] = $A;
            }
            Cache::set($key, $defs, 'defs');
        }

        // Now instantiate the field objects to get the field classes loaded.
        foreach ($defs as $name=>$data) {
            $this->fields[$name] = Field::getInstance($data);
        }

        // Now read the values for the current user.
        // Don't bother reading anonymous, just in case a record gets in there.
        if ($this->uid > 1) {
            $sql = "SELECT * FROM {$_TABLES['profile_data']}
                WHERE puid = '{$this->uid}'
                LIMIT 1";
            $res = DB_query($sql);
            $A = DB_fetchArray($res, false);
            if (!empty($A)) {
                foreach ($A as $name=>$value) {
                    if (isset($this->fields[$name])) {
                        $this->fields[$name]->setValue($value);
                    }
                }
            }
        }
        return $this;
    }


    /**
     * Get the field objects for the profile.
     *
     * @return  array       Array of Field objects
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Check if the profile contains a named field.
     *
     * @param   string  $name   Field name
     * @return  boolean     True if the field exists, False if not
     */
    public function hasField($name)
    {
        return array_key_exists($name, $this->fields);
    }


    /**
     * Get a single field from the profile.
     *
     * @param   string  $name   Field name
     * @return  object      Field object
     */
    public function getField($name)
    {
        if ($this->hasField($name)) {
            return $this->fields[$name];
        } else {
            return NULL;
        }
    }


    /**
     * Displays a form for editing profile data.
     *
     * @param   string  $type       'edit' or 'registration'
     * @param   integer $form_id    HTML Element ID to set for the form
     * @return  string          HTML for the form
     */
    public function Edit($type = 'edit', $form_id='profileform')
    {
        global $_CONF, $_USER, $_TABLES, $LANG_PROFILE, $_PRF_CONF, $_SYSTEM;

        // Choose the correct template file based on the glFusion version
        // and type of form needed
        switch ($type) {
        case 'inline':
            $template_name = 'profile_inlineform';
            $access_required = 2;
            break;
        case 'registration':
            $template_name = 'profile_registration';
            // Set no access level to profile defs since this is an anon user.
            // Otherwise, they're not allowed to even read the definitions.
            $access_required = 0;
            break;
        default:
            // For anything but new registrations, the current user needs
            // at least read privileges.
            $access_required = 2;
            $template_name = 'profile_usersettings';
            break;
        }

        $T = new \Template(PRF_PI_PATH . '/templates');
        $T->set_file('editform', $template_name . '.thtml');
        $T->set_var(array(
            'uid'       => $this->uid,
            'form_id'   => $form_id,
            'old_fullname' => $this->uid == 1 ? '' : $_USER['fullname'],
        ) );

        // Flag to make sure calendar javascript is added only once.  It's
        // only added if there's at least one calendar field.
        $T->set_block('editform', 'QueueRow', 'qrow');
        foreach ($this->fields as $fldname=>$Fld) {
            // If the field is not required and is not to appear on the signup
            // form, then skip it.  If it is a registration field, override
            // the owner permission to make sure the user can edit it.
            if ($type == 'registration') {
                if (!$Fld->getUserReg() && !$Fld->isRequired()) {
                    continue;
                }
                $Fld->setPerm('owner', 3);
            } elseif ($type == 'edit') {
                // In the account settings, the sys_directory checkbox
                // appears in the privacy panel so exclude here
                if ($fldname == 'sys_directory') continue;
            }

            // If the field is required, set the fValidator class.
            // This doesn't work right for dates (yet).
            if ($Fld->isRequired()) {
                $T->set_var('is_required', 'true');
            } else {
                $T->clear_var('is_required');
            }

            // Set a flag to indicate that this is a static field.  No status
            // indicators need to be shown.
            if ($Fld->getType() == 'static' || $Fld->getType() == 'system') {
                $T->set_var('is_static', 'true');
            } else {
                $T->clear_var('is_static');
            }

            // If POSTed form data, set the user variable to that.  Otherwise,
            // set it to the default or leave it alone.
            if (isset($_POST[$Fld->getName()])) {
                $Fld->setValue($_POST[$Fld->getName()]);
            } elseif (
                is_null($Fld->getValue()) &&
                !is_null($Fld->getOption('default'))
            ) {
                $Fld->setValue($Fld->getOption('default'));
            }

            $T->set_var(array(
                'is_visible'    => $Fld->isPublic() ? 'true' : '',
                'spancols'      => $Fld->getOption('spancols'),
                'help_text'     => htmlspecialchars($Fld->getOption('help_text')),
                'prompt'        => PRF_noquotes($Fld->getPrompt()),
                'field'         => $Fld->FormField(),
                'fld_class'     => isset($_POST['prf_errors'][$Fld->getName()]) ?
                    'profile_error' : '',
                'fld_name'      => $Fld->getName(),
            ) );
            $T->parse('qrow', 'QueueRow', true);
        }
        $T->parse('output', 'editform');
        $val = $T->finish($T->get_var('output'));
        return $val;
    }


    /**
     * Validate a profile submission.
     *
     * @see     plugin_itemPreSave_profile()
     * @see     self::Save()
     * @param   array|NULL  $vals   Values from $_POST, null to use current
     * @return  array   Array of error messages.
     */
    public function Validate($vals=NULL)
    {
        $errors = array();
        foreach ($this->fields as $name=>$Fld) {
            // Now make changes based on the type of data and applicable options.
            // Managers can override normal validations.
            if (!$Fld->validData($vals)) {
                // We could just return false here, but instead continue checking so
                // we can show the user all the errors, not just the first.
                $errors[$name] = sprintf(
                    $LANG_PROFILE['msg_fld_missing'],
                    $Fld->getPrompt()
                );
                $validation_errs++;
            }
        }
        return $errors;
    }


    /**
     * Saves the user data to the database.
     *
     * @param   array   $vals   Array of name=>value pairs, like $_POST.
     * @param   string  $type   Type of operation.
     * @return  boolean     True on success, False on failure.
     */
    public function Save($vals, $type = 'edit')
    {
        global $_TABLES, $_USER, $LANG_PROFILE;

        if (!is_array($vals)) {
            // Return error if values aren't provided
            return false;
        }
        if ($this->uid == 1) {
            // Fake success for anonymous, don't actually save
            return true;
        }
        $isAdmin = plugin_ismoderator_profile();
        if ($type != 'registration') {
            if ($this->uid != $_USER['uid'] && !PRF_isManager()) {
                COM_errorLog("Non admin user attempting to change another's profile");
                return false;
            }
        }

        $fld_sql = array();
        $validation_errs = 0;
        $_POST['prf_errors'] = array();
        $errors = $this->Validate($vals);
        $validation_errs = count($errors);
        foreach ($this->fields as $name=>$Fld) {
            if (isset($errors[$name])) {
                LGLIB_storeMessage($errors[$name], '', true);
                $_POST['prf_errors'][$name] = 'true';
                continue;
            }

            $newval = $Fld->prepareToSave($vals);
            if ($newval === NULL) {
                // special value to avoid saving, e.g. static fields
                continue;
            }

            // Auto-Generate a value during registration, or if the value is empty
            if (
                $Fld->getOption('autogen', 0) == 1 &&
                ($type == 'registration' || empty($newval))
            ) {
                $newval = $Fld->autogen();
            }

            // Require valid access, unless this is a registration submission
            // in which case fields need to be filled in regardless of access.
            if (
                $isAdmin ||
                $type == 'registration' ||
                $Fld->getPerm('owner') == 3
            ) {
                // Perform field-specific sanitization and add to array of sql
                $newval = $Fld->Sanitize($newval);
                $fld_sql[$Fld->getName()] = $Fld->getName() . "='" . DB_escapeString($newval) . "'";
            }
       }

        // If any validation errors found, return now for regular users
        // For managers, allow the data to be saved anyway but show the message.
        if ($validation_errs > 0) {
            COM_errorLog("$validation_errs errors saving the profile for user {$this->uid}");
            if (PRF_isManager()) {
                // Save but show message for managers
                LGLIB_storeMessage('Validation Errors overriden by Manager', '', true);
                $msg .= ' (Manager Override)';
            } else {
                // Abort for regular users
                return false;
            }
        }

        // If the fullname value is included but neither the
        // first nor last name fields were used, parse the fullname into first
        // and last.
        // Only update DB fields that are NOT included in the form, otherwise
        // there will be duplicate SQL fields during inserts.
        $fullname = PRF_getVar($vals, 'fullname');
        $old_fullname = PRF_getVar($vals, 'prf_old_fullname');
        $fname = PRF_getVar($vals, 'sys_fname');
        $lname = PRF_getVar($vals, 'sys_lname');
        if (
            !empty($fullname) && $fullname != $old_fullname
        ) {
            // The fullname has been changed or provided for the first time.
            $fname = DB_escapeString(\LGLib\NameParser::F($vals['fullname']));
            $lname = DB_escapeString(\LGLib\NameParser::L($vals['fullname']));
            $fld_sql['sys_fname'] = "sys_fname = '$fname'";
            $fld_sql['sys_lname'] = "sys_lname = '$lname'";
        } elseif (
            $fname != $this->fields['sys_fname']->getValue() ||
            $lname != $this->fields['sys_lname']->getValue()
        ) {
            // The first or last name was changed by the submitter, so
            // construct the fullname from them.
            $fullname = trim($fname . ' ' . $lname);
            $sql = "UPDATE {$_TABLES['users']}
                SET fullname = '" . DB_escapeString($fullname) . "'
                WHERE uid = {$this->uid}";
            DB_query($sql);
        }

        if (!empty($fld_sql)) {
            $new_sql = implode(', ', $fld_sql);
            $sql = "INSERT INTO {$_TABLES['profile_data']} SET
                        puid = '{$this->uid}', $new_sql
                    ON DUPLICATE KEY UPDATE $new_sql";
            DB_query($sql, 1);
            if (DB_error()) {
                COM_errorLog("Profile::Save() - error executing sql: $sql");
                return false;
            } else {
                Cache::delete('profile_user_' . $this->uid);
            }
        }
        return true;
    }

}
