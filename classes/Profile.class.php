<?php
/**
*   Class to handle a user profile
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2018 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.2.0
*   @since      1.2.0
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/
namespace Profile;

/**
*   Class for a user's custom forms.
*   @package    profile
*   @since      1.2.0
*/
class Profile
{
    /** Local properties
    *   @var array */
    var $properties = array();

    /** Profile fields, an array of objects
    *   @var array */
    var $fields = array();

    /** User ID
    *   @var integer */
    var $uid;

    /**
    *   Constructor.  Create a forms object for the specified user ID,
    *   or the current user if none specified.  If a key is requested,
    *   then just build the forms for that key (requires a $uid).
    *
    *   @param  integer $uid    Optional user ID
    *   @param  string  $key    Optional key to retrieve
    */
    function __construct($uid=0)
    {
        global $_USER, $_PRF_CONF, $_TABLES;

        if ($uid == 0) $uid = $_USER['uid'];
        $this->uid = (int)$uid;
        if ($this->uid == 0) $this->uid = 1;    // Anonymous

        $this->Read();
    }


    /**
    *   Get the profile object for the specified user
    *
    *   @param  integer $uid    User ID, default is current user
    *   @return object          Profile object for the user
    */
    public static function getInstance($uid=0)
    {
        global $_USER;
        static $_profiles = array();

        if ($uid == 0) $uid = $_USER['uid'];
        $uid = (int)$uid;
        $cache_key = 'user_' . $uid;
        if (!array_key_exists($uid, $_profiles)) {
            $_profiles[$uid] = Cache::get($cache_key);
            if ($_profiles[$uid] === NULL) {
                $_profiles[$uid] = new self($uid);
                Cache::set($cache_key, $_profiles[$uid], array('defs','vals'));
            }
        }
        return $_profiles[$uid];
    }


    /**
    *   Read the profile field definitions and values.
    *   Stores definitions in a static array for re-use.
    */
    private function Read()
    {
        global $_TABLES;

        // Get the field definitions, reading from the DB if not already done
        $key = 'defs_enabled';
        $defs = Cache::get($key);
        if ($defs === NULL) {
            $defs = array();
            $sql = "SELECT * FROM {$_TABLES['profile_def']}
                    WHERE enabled = 1";
            $res = DB_query($sql);
            while ($A = DB_fetchArray($res, false)) {
                $defs[$A['name']] = prfItem::getInstance($A);
            }
            Cache::set($key, $defs, 'defs');
        }
        $this->fields = $defs;

        // Now read the values for the current user
        $sql = "SELECT * FROM {$_TABLES['profile_data']}
                    WHERE puid = '{$this->uid}'";
        $res = DB_query($sql);
        $A = DB_fetchArray($res, false);
        foreach ($A as $name=>$value) {
            if (isset($this->fields[$name])) {
                $this->fields[$name]->value = $value;
            }
        }
    }


    /**
    *   Displays a form for editing profile data.
    *
    *   @param  string  $type       'edit' or 'registration'
    *   @param  integer $form_id    HTML Element ID to set for the form
    *   @return string          HTML for the form
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

        $T = PRF_getTemplate($template_name, 'editform');
        $T->set_var(array(
            'uid'       => $this->uid,
            'form_id'   => $form_id,
            'have_jquery' => isset($_SYSTEM['disable_jquery']) && $_SYSTEM['disable_jquery'] ? '' : 'true',
            'iconset'   => $_PRF_CONF['_iconset'],
        ) );

        // Flag to make sure calendar javascript is added only once.  It's
        // only added if there's at least one calendar field.
        $T->set_block('editform', 'QueueRow', 'qrow');
        foreach ($this->fields as $fldname=>$data) {
            // If the field is not required and is not to appear on the signup
            // form, then skip it.  If it is a registration field, override
            // the owner permission to make sure the user can edit it.
            if ($type == 'registration') {
                if (($data->user_reg == 0 && $data->required == 0))
                    continue;
                $data->perm_owner = 3;
            }

            // If the field is required, set the fValidator class.
            // This doesn't work right for dates (yet).
            $fValidator_opts = array();     // Start with a clean array
            if ($data->required == 1) {
                if ($data->type != 'checkbox' && $data->type != 'multicheck') {
                    // current fValidator doesn't work with checkboxes
                    $fValidator_opts[] = 'required';
                }
                $T->set_var('is_required', 'true');
            } else {
                $T->clear_var('is_required');
            }

            // Set a flag to indicate that this is a static field.  No status
            // indicators need to be shown.
            if ($data->type == 'static' || $data->type == 'system') {
                $T->set_var('is_static', 'true');
            } else {
                $T->clear_var('is_static');
            }

            // If POSTed form data, set the user variable to that.  Otherwise,
            // set it to the default or leave it alone.
            if (isset($_POST[$data->name])) {
                $data->value = $_POST[$data->name];
            } elseif (is_null($data->value) && isset($data->options['default'])) {
                $data->value = $data->options['default'];
            }

            $T->set_var(array(
                'is_visible'    => $data->isPublic() ? 'true' : '',
                'spancols'      => $data->getOption('spancols'),
                'help_text'     => htmlspecialchars($data->getOption('help_text')),
                'prompt'        => PRF_noquotes($data->prompt),
                'field'         => $data->FormField(),
                'fld_class'     => isset($_POST['prf_errors'][$data->name]) ?
                    'profile_error' : '',
                'fld_name'      => $data->name,
            ) );
            $T->parse('qrow', 'QueueRow', true);
        }
        $T->parse('output', 'editform');
        $val = $T->finish($T->get_var('output'));
        return $val;
    }


    /**
    *   Saves the user data to the database.
    *
    *   @param  array   $vals   Array of name=>value pairs, like $_POST.
    *   @param  string  $type   Type of operation.
    *   @return boolean     True on success, False on failure.
    */
    public function Save($vals, $type = 'edit')
    {
        global $_TABLES, $_USER, $LANG_PROFILE;

        if (!is_array($vals)) return;
        if ($this->uid == 1) return;    // never actually save anonymous

        if ($type != 'registration') {
            $isAdmin = SEC_hasRights('profile.admin');
            if ($this->uid != $_USER['uid'] && !$isAdmin) {
                // non-admin attempting to change another user's record
                return;
            }
        }

        $fld_sql = array();
        $validation_errs = 0;
        $_POST['prf_errors'] = array();
        foreach ($this->fields as $name=>$data) {
            // Now make changes based on the type of data and applicable options.
            // Managers can override normal validations.
            if (!$data->validData($vals)) {
                // We could just return false here, but instead continue checking so
                // we can show the user all the errors, not just the first.
                $msg = sprintf($LANG_PROFILE['msg_fld_missing'], $data->prompt);
                LGLIB_storeMessage($msg, '', true);
                // Add a value to $_POST so the field will be highlighted when it
                // is redisplayed.
                $_POST['prf_errors'][$name] = 'true';
                $validation_errs++;
                continue;   // skip remainder of loop for invalid records
            }

            $newval = $data->prepareToSave($vals);
            if ($newval === NULL) continue;     // special value to avoid saving

            // Auto-Generate a value during registration, or if the value is empty
            if ($data->getOption('autogen', 0) == 1 &&
                    ($type == 'registration' || empty($newval))) {
                $newval = PRF_autogen($data, $this->uid);
            }

            if ($data->perm_owner == 3 || $isAdmin) {
                // Perform field-specific sanitization and add to array of sql
                $newval = $data->Sanitize($newval);
                $fld_sql[] = $data->name . "='" . DB_escapeString($newval) . "'";
            }
        }

        // If any validation errors found, return now for regular users
        // For managers, allow the data to be saved anyway but show the message.
        if ($validation_errs > 0) {
            if (PRF_isManager()) {
                // Save but show message for managers
                LGLIB_storeMessage('Validation Errors overriden by Manager', '', true);
                $msg .= ' (Manager Override)';
            } else {
                // Abort for regular users
                return false;
            }
        }

        // If the "fullname" value is included, break it into first and last names.
        // Only update DB fields that are NOT included in the form, otherwise
        // there will be duplicate SQL fields during inserts.
        if (isset($vals['fullname'])) {
            $fname = DB_escapeString(\LGLib\NameParser::F($vals['fullname']));
            $lname = DB_escapeString(\LGLib\NameParser::L($vals['fullname']));
            if (!isset($vals['sys_fname'])) $fld_sql[] = "sys_fname = '$fname'";
            if (!isset($vals['sys_lname'])) $fld_sql[] = "sys_lname = '$lname'";
        }

        if (!empty($fld_sql)) {
            $new_sql = implode(', ', $fld_sql);
            $sql = "INSERT INTO {$_TABLES['profile_data']} SET
                        puid = '{$this->uid}', $new_sql
                    ON DUPLICATE KEY UPDATE $new_sql";
            //echo $sql;die;
            DB_query($sql, 1);
            if (DB_error()) {
                COM_errorLog("Profile::Save() - error executing sql: $sql");
                return false;
            } else {
                Cache::delete('user_' . $this->uid);
            }
        }
        return true;
    }

}

?>
