<?php
/**
 * Class to handle individual profile items.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2018 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;

/**
 * Base class for profile fields.
 * @package profile
 * @since   v1.1.0
 */
class Field
{
    /** Options for this item.
     * Public, but should be accessed via getOption().
     * @var array */
    protected $options = array();

    /** CSS class to use on the entry form.
     * @var string */
    protected $_frmClass = '';

    /** Set to "DISABLED" if this item is read-only, to disable frm entry.
     * @var string */
    protected $_frmReadonly = '';

    /** Easily indicate a hidden field.
     * @var boolean */
    protected $hidden = 0;

    /** Flag indicating the field is enabled for use.
     * @var boolean */
    protected $enabled = 1;

    /** Flag indicating the field is shown in the user's profile.
     * @var boolean */
    protected $show_in_profile = 1;

    /** Show the field on the registration form?
     * @var boolean */
    protected $user_reg = 0;

    /** Is this a system (not modifiable) field?
     * @var boolean */
    protected $sys = 0;

    /** Is this a read-only field?
     * @var boolean */
    protected $readonly = 0;

    /** Is this a required field?
     * @var boolean */
    protected $required = 0;

    /** Type of field (text, numeric, date, etc.)
     * @var string */
    protected $type = 'text';

    /** Short name of the field.
     * @var string */
    protected $name = '';

    /** Field prompt.
     * @var string */
    protected $prompt = '';

    /** Submitted value for the field.
     * @var string */
    protected $value = '';

    /** Field record ID.
     * @var integer */
    protected $id = 0;

    /** User ID.
     * @var integer */
    protected $uid = 0;

    /** Field display order.
     * @var integer */
    protected $orderby = 9999;

    /** Group ID for permissions.
     * @var integer */
    protected $group_id = 2;

    /** Owner (user) permission to edit their own values.
     * @var integer */
    protected $perm_owner = 3;

    /** Permisson for the group to edit values.
     * @var integer */
    protected $perm_group = 3;

    /** Permission for regular members to the value.
     * @var integer */
    protected $perm_members = 2;

    /** Permission for anonymous users to the value.
     * @var integer */
    protected $perm_anon = 0;


    /**
     * Constructor.  Sets the local properties using the array $item.
     *
     * @param   mixed   $item   Name of item, or array of info
     * @param   mixed   $value  Optional value to assign
     * @param   integer $uid    Optional user ID, current user by default
     */
    public function __construct($item=NULL, $value='', $uid = '')
    {
        global $_USER, $_TABLES, $_PRF_CONF;

        if (empty($uid)) {
            $uid = $_USER['uid'];
        }
        $this->uid = (int)$uid;
        $A = array();

        // If the item info is an array, then assume that it's been read from
        // the database and simply populate our variables; no need to re-read.
        // Otherwise, read the item from the DB.
        if (is_array($item)) {
            $A = $item;
        } elseif ($item !== NULL) {
            $sql = "SELECT * FROM {$_TABLES['profile_def']}
                    WHERE name='" . DB_escapeString($item) . "'";
            $res = DB_query($sql);
            $A = DB_fetchArray($res, false);
        }
        if (!empty($A)) {
            $this->setVars($A, true);
        } else {
            $this->group_id = (int)$_PRF_CONF['defgroup'];
            $this->perm_owner = (int)$_PRF_CONF['default_permissions'][0];
            $this->perm_group = (int)$_PRF_CONF['default_permissions'][1];
            $this->perm_members = (int)$_PRF_CONF['default_permissions'][2];
            $this->perm_anon = (int)$_PRF_CONF['default_permissions'][3];
            $this->enabled = 1;
        }

        // Override the item's value if one is provided.
        if (!empty($value)) {
            $this->value = $value;
        }

        // If the unserialization failed, provide an empty array
        if (!$this->options) {
            $this->options = array();
        }
    }


    /**
     * Set property values from supplied variables.
     *
     * @param   array   $A      $_POST array or DB record
     * @param   boolean $from_db    True if this is from the DB
     */
    private function setVars($A, $from_db = true)
    {
        global $_PRF_CONF;

        $this->id = PRF_getVar($A, 'id', 'integer', 0);
        $this->name = PRF_getVar($A, 'name');
        $this->value = PRF_getVar($A, 'value');
        $this->orderby = PRF_getVar($A, 'orderby', 'integer', 9999);
        $this->type = PRF_getVar($A, 'type', 'string', 'text');
        $this->prompt = PRF_getVar($A, 'prompt');
        $this->group_id = PRF_getVar($A, 'group_id', 'integer', $_PRF_CONF['defgroup']);
        $this->sys = PRF_getVar($A, 'sys', 'integer', 0);
        $this->user_reg = PRF_getVar($A, 'user_reg', 'integer', 0);
        $this->enabled = PRF_getVar($A, 'enabled', 'integer', 0);
        $this->required = PRF_getVar($A, 'required', 'integer', 0);
        $this->show_in_profile = PRF_getVar($A, 'show_in_profile', 'integer', 0);

        // Load permissions differently if read from DB vs. from a form
        if ($from_db) {
            $this->perm_owner = PRF_getVar($A, 'perm_owner', 'integer', $_PRF_CONF['default_permissions'][0]);
            $this->perm_group = PRF_getVar($A, 'perm_group', 'integer', $_PRF_CONF['default_permissions'][1]);
            $this->perm_members = PRF_getVar($A, 'perm_members', 'integer', $_PRF_CONF['default_permissions'][2]);
            $this->perm_anon = PRF_getVar($A, 'perm_anon', 'integer', $_PRF_CONF['default_permissions'][3]);
            $this->options = @unserialize(PRF_getVar($A, 'options'));
        } else {
            if (isset($A['perm_owner'])) {
                $perms = SEC_getPermissionValues(
                    $A['perm_owner'], $A['perm_group'],
                    $A['perm_members'], $A['perm_anon']
                );
                $this->perm_owner   = (int)$perms[0];
                $this->perm_group   = (int)$perms[1];
                $this->perm_members = (int)$perms[2];
                $this->perm_anon    = (int)$perms[3];
            }
            $this->options = array();
        }
    }


    /**
     * Return a formatted value.
     * This default function simply returns the current value unchanged.
     *
     * @param   mixed   $value  Raw value
     * @return  string  Value formatted for display
     */
    public function FormatValue($value = '')
    {
        if ($value == '') {
            $value = $this->value;
        }
        return htmlspecialchars($value);
    }


    /**
     * Initialize common form field requirements.
     * This can be called by child classes in their FormField() function
     */
    protected function _FormFieldInit()
    {
        global $_SYSTEM;

        // collect CSS classes to apply
        $classes = array(
            'prfInput_' . $this->type,
        );

        // Check for required status.  May need to excluded dates from this...
        // Setting 'required' on the profile page causes issues for uikit
        // since it's not checked until submission and the user may never
        // see the error. In that case, don't set the field as required and
        // rely on post-submission validation.
        if ($this->required == 1 && !PRF_isManager()) {
            $classes[] = 'required error';
        }
        $this->_frmClass = 'class="' . implode(' ', $classes) . '"';

        // If POSTed form data, set the user variable to that.  Otherwise,
        // set it to the default or leave it alone.
        if (isset($_POST[$this->name])) {
            $this->value = $_POST[$this->name];
        } elseif (is_null($this->value)) {
            $this->value = $this->getOption('default');
        }

        // Check for read-only status on the field.  Admins can always
        // edit user values.
        $this->_frmReadonly = '';
        //$this->readonly = false;
        $this->hidden = false;
        if (
            $this->perm_owner < 3 &&
            !SEC_hasRights('profile.admin, profile.manage', 'OR')
        ) {
            $this->_frmReadonly = ' disabled="disabled" ';
            //    $this->readonly = true;
            $this->hidden = $this->perm_owner < 2 ? true : false;
        }
    }


    /**
     * Provide a form field for editing this data item.
     * This provides a text input field, to be overridden by child classes
     *
     * @return  string  HTML for data entry field
     */
    public function FormField()
    {
        $this->_FormFieldInit();

        $size = $this->getOption('size', 40);
        $maxlength = $this->getOption('maxlength', 40);
        $maxlength = (int)$maxlength > 0 ? "maxlength=\"$maxlength\"" : '';
        $fld = "<input $this->_frmClass name=\"{$this->name}\"
                    id=\"{$this->name}\" $maxlength
                    size=\"$size\"
                    type=\"text\" value=\"{$this->value}\" $this->_frmReadonly>\n";

        return $fld;
    }


    /**
     * Get the available options for this field.
     *
     * @return  array   Field options
     */
    public function XOptions()
    {
        return $this->options;
    }


    /**
     * Sanitize the value.
     * Does not make it database-safe, just strips invalid or objectionable
     * material.
     * This will depend largely upon the type of field.
     *
     * @param   mixed   $val    Current field value
     * @return  mixed           Sanitized value.
     */
    public function Sanitize($val)
    {
        //$val = COM_checkWords(COM_checkHTML($val));
        return $val;
    }


    /**
     * Check if this item should be publicly displayed in the user's
     * profile.
     *
     * @return  boolean     True if publicly visible, False if not
     */
    public function isPublic()
    {
        return $this->show_in_profile ? true : false;
        return ($this->perm_members > 1 || $this->perm_anon > 1) ? TRUE : FALSE;
    }


    /**
     * Prepare this item's values to be saved in the database
     *
     * @return  string      DB-safe version of the value(s)
     */
    public function prepareForDB()
    {
        return DB_escapeString($this->value);
    }


    /**
     * Create the form elements for editing the value selections
     * Returns the default value for single-value fields (text, textarea, etc)
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        return array(
            'defvalue'  => $this->getOption('default'),
            'maxlength' => $this->getOption('maxlength', 40),
            'size'      => $this->getOption('size', 40),
        );
    }


    /**
     * Get a value from the options array, returning a default if not set.
     * This is just a helper function to avoid "invalid index" errors for
     * options that aren't set.
     *
     * @param   string  $name       Name of option
     * @param   mixed   $default    Default return value
     * @return  mixed       Option value, or default if not set
     */
    public function getOption($name, $default='')
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }


    /**
     * Check if a field has valid data.
     * Used in conjuction with the "required" flag.
     *
     * @param   array   $vals   Array of name->value pairs
     * @return  boolean         True if data is valid, False if not
     */
    public function validData($vals = NULL)
    {
        if ($vals === NULL) {   // check current value
            $val = $this->value;
        } elseif (isset($vals[$this->name])) {
            $val = $vals[$this->name] !== NULL ? $vals[$this->name] : $this->value;
        } else {
            $val = '';
        }
        if ($this->required && empty($val)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Get all defined field objects, only enabled by default.
     * First gets an array of field records, from cache if possible,
     * then puts them into objects for return.
     *
     * @param   boolean $enabled    True to include only enabled fields
     * @return  array   Array of field objects
     */
    public static function getAll($enabled = true)
    {
        global $_TABLES;

        $key = $enabled ? 1 : 0;
        static $retval = array();

        if (isset($retval[$key])) return $retval[$key];

        $retval[$key] = array();
        $cache_key = 'allfields_' . $key;
        $A = Cache::get($cache_key);
        if ($A === NULL) {
            $sql = "SELECT * FROM {$_TABLES['profile_def']}";
            if ($enabled) $sql .= ' WHERE enabled = 1';
            $sql .= ' ORDER BY orderby ASC';
            $res = DB_query($sql);
            if ($res) {
                while ($f = DB_fetchArray($res, false)) {
                    $A[$f['id']] = $f;
                }
            }
            Cache::set($cache_key, $A);
        }
        if (is_array($A)) {
            foreach ($A as $f) {
                $retval[$key][$f['id']] = self::getInstance($f);
            }
        }
        return $retval[$key];
    }


    /**
     * Get an instance of the class based on the type of field definition.
     * If the requested class doesn't exist, return a text field object.
     * If an array is passed in, get the type from it. Otherwise read the
     * definition record to get the correct type.
     *
     * @param   array   $A      Row from profile_def table
     * @param   mixed   $value  Data value to pass to class
     * @param   integer $uid    User ID to pass to class
     * @return  object          Class instsnce
     */
    public static function getInstance($A, $value='', $uid=0)
    {
        global $_TABLES;
        static $defs = array();

        if (!is_array($A)) {
            // Need to retrieve the field to find the type
            $id = (int)$A;
            $sql = "SELECT * FROM {$_TABLES['profile_def']}
                    WHERE id = '$id'";
            $res = DB_query($sql);
            $A = DB_fetchArray($res, false);
        }
        if (is_array($A)) {
            $id = (int)$A['id'];
            $type = $A['type'];
        } else {
            $id = 0;
            $type = 'text';
        }
        $classname = __NAMESPACE__ . '\\Fields\\' . $type;

        // For a new field being created, don't cache anything.
        // Just construct the appropriate class and return it.
        if ($id == 0) {
            return new $classname();
        }

        if (!isset($defs[$id])) {
            if (is_array($A)) {     // should be array at this point
                $classname = __NAMESPACE__ . '\\Fields\\' . $type;
                if (class_exists($classname)) {
                    $cls = new $classname($A, '', $uid);
                } else {
                    // so there's a default if the type isn't defined
                    $cls = new Fields\text($A, '', $uid);
                }
            } else {
                // Failsafe if no field is found, e.g. when creating a new one
                $cls = new Fields\text('', '', $uid);
            }
            $defs[$id] = $cls;  // save for subsequent requests, if not new
        }
        // Always set the value if defined, allows for repeated calls
        // for different users, e.g. in a profile list.
        $defs[$id]->value = $value;
        $defs[$id]->uid = $uid;
        return $defs[$id];
    }


    /**
     * Displays a form for editing a profile definition.
     *
     * @param   integer $id     Database ID of item to edit, 0 for new item
     * @return  string          HTML for the form
     */
    public function Edit()
    {
        global $_TABLES, $_CONF, $LANG_PROFILE, $LANG_ADMIN, $_PRF_CONF;

        $T = new \Template(PRF_PI_PATH . '/templates/admin');
        $T->set_file('editform', 'profile.thtml');

        $fields = self::getAll(false);
        /*$sql = "SELECT id, orderby, name
                FROM {$_TABLES['profile_def']}
                ORDER BY orderby ASC";
        $res1 = DB_query($sql);
        $orderby_options = '';
        $current = $this->orderby - 10;
        while ($B = DB_fetchArray($res1, false)) {*/
        $orderby_options = '';
        $current = $this->orderby - 10;
        foreach ($fields as $fld) {
            if ($fld->id == $this->id) continue;
            $orderby = $fld->orderby + 1;
            if ($this->id > 0) {
                $sel = $fld->orderby == $current ? PRF_SELECTED : '';
            } else {
                $sel = '';
            }
            $orderby_options .= "<option value=\"$orderby\" $sel>{$fld->name}</option>\n";
        }

            //if ($B['id'] == $this->id) continue;    // skip this item
/*            $orderby = (int)$B['orderby'] + 1;
            if ($this->id > 0) {
                $sel = $B['orderby'] == $current ? PRF_SELECTED : '';
            } else {
                $sel = '';
            }
            $orderby_options .= "<option value=\"$orderby\" $sel>{$B['name']}</option>\n";
        }*/

        // Create the "Field Type" dropdown.  This is disabled for system items
        // in the template
        $type_options = '';
        foreach ($LANG_PROFILE['fld_types'] as $option => $opt_desc) {
            $sel = $this->type == $option ? PRF_SELECTED : '';
            $type_options .= "<option value=\"$option\" $sel>$opt_desc</option>\n";
        }

        // Set up the field-specific inputs for value selection or default value
        $T->set_var($this->editValues());

        if (!isset($opts['input_format'])) $opts['input_format'] = '';
        $T->set_var(array(
            'is_sys'    => $this->sys,
            'id'        => $this->id,
            'name'      => $this->name,
            'type'      => $this->type,
            'oldtype'   => $this->type,
            'prompt'    => $this->prompt,
            'ena_chk'   => $this->enabled == 1 ? PRF_CHECKED : '',
            'user_reg_chk' => $this->user_reg == 1 ? PRF_CHECKED : '',
            'req_chk'   => $this->required == 1 ? PRF_CHECKED : '',
            'in_prf_chk' => $this->show_in_profile == 1 ? PRF_CHECKED : '',
            'spancols_chk' => $this->getOption('spancols', 0),
            'orderby'   => $this->orderby,
            'format'    => $this->getOption('format'),
            //'input_format' => Fields\date::DateFormatSelect($this->getOption('input_format')),
            'doc_url'   => PRF_getDocURL('profile_def.html'),
            'mask'      => $this->getOption('mask'),
            'vismask'   => $this->getOption('vismask'),
            'autogen_chk' => $this->getOption('autogen', 0) == 1 ? PRF_CHECKED : '',
            'stripmask_chk' => $this->getOption('stripmask', 0) == 1 ? PRF_CHECKED : '',
            'group_dropdown' => SEC_getGroupDropdown($this->group_id, 3),
            'permissions' => PRF_getPermissionsHTML(
                $this->perm_owner, $this->perm_group,
                $this->perm_members, $this->perm_anon),
            'plugin_options' => COM_optionList($_TABLES['plugins'],
                        'pi_name,pi_name', $this->plugin, 0, 'pi_enabled=1'),
            'help_text' => htmlspecialchars($this->getOption('help_text')),
            'dt_input_format' => Fields\date::DateFormatSelect($this->getOption('input_format', 0)),
            'orderby_selection' => $orderby_options,
            'type_options' => $type_options,
            'removeRowIcon' => $this->getRemoveRowIcon(),   // to place in javascript
        ) );
        $T->parse('output', 'editform');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Saves the current form entries as a new or existing record.
     *
     * @param   array   $A          Array of all values from the submitted form
     */
    public function saveDef($A)
    {
        global $_TABLES;

        // Sanitize the entry ID.  Zero = new entry
        $id = $this->id;

        // Sanitize the name, especially make sure there are no spaces and that
        // the name starts with "prf_", unless it's a system variable
        // These should be done by template JS, but just in case...
        if (strpos($A['name'], 'prf_') !== 0 && $A['sys'] != 1) {
            $A['name'] = 'prf_' . $A['name'];
        }
        $A['name'] = COM_sanitizeID($A['name'], false);
        if (empty($A['name']) || empty($A['type'])) {
            return '104';
        }

        $this->setVars($A, false);

        // For a new record, make sure the field name is unique
        if ($this->id == 0) {
            // for existing records, make sure it's not a duplicate name
            $cnt = DB_count($_TABLES['profile_def'], 'name', $A['name']);
            if ($cnt > 0) return '103';
        }

        // Put this field at the end of the line by default
        //if (empty($A['orderby'])) $A['orderby'] = 65535;
        if ($this->orderby == 0) $this->orderby = 65535;

        // Set the options and default values according to the data type
        // Only a couple of field types will override this.
        $this->options['default'] = isset($A['defvalue']) ? trim($A['defvalue']) : '';

        // Mask and Visible Mask may exist for any field type, but are set
        // only if they are actually defined.
        if (isset($A['mask']) && $A['mask'] != '') {
            $this->options['mask'] = trim($A['mask']);
        }
        if (isset($A['vismask']) && $A['vismask'] != '') {
            $this->options['vismask'] = trim($A['vismask']);
        }
        if (isset($A['spancols']) && $A['spancols'] == '1') {
            $this->options['spancols'] = 1;
        }
        if (isset($A['help_text']) && $A['help_text'] != '') {
            $this->options['help_text'] = trim($A['help_text']);
        }

        // Now alter the data table.  We do this first in case there's any SQL
        // error so we don't end up with a mismatch between the definitions and
        // the values.
        $sql = $this->getDataSql($A);
        if (!empty($sql)) {
            $sql = "ALTER TABLE {$_TABLES['profile_data']} $sql";
            //echo $sql;die;
            DB_query($sql, 1);
            if (DB_error()) {
                return '103';
            }
        }

        // After all default options are set, allow the field types to
        // provide their own.
        $this->setOptions($A);

        // This serializes any options set
        //$A['options'] = PRF_setOpts($options);
        $options = DB_escapeString(@serialize($this->options));

        // Make all entries SQL-safe
        //$A = array_map('PRF_escape_string', $A);

        if ($id > 0) {
            // Existing record, perform update
            $sql1 = "UPDATE {$_TABLES['profile_def']} SET ";
            $sql3 = " WHERE id = {$this->id}";
        } else {
            // New record
            $sql1 = "INSERT INTO {$_TABLES['profile_def']} SET ";
            $sql3 = '';
        }
        $sql2 = "orderby = '{$this->orderby}',
                name = '{$this->name}',
                type = '{$this->type}',
                enabled = '{$this->enabled}',
                required = '{$this->required}',
                user_reg = '{$this->user_reg}',
                show_in_profile= '{$this->show_in_profile}',
                prompt = '{$this->prompt}',
                options = '{$options}',
                group_id = '{$this->group_id}',
                perm_owner = {$this->perm_owner},
                perm_group = {$this->perm_group},
                perm_members = {$this->perm_members},
                perm_anon = {$this->perm_anon}";
        $sql = $sql1 . $sql2 . $sql3;
        //echo $sql;die;
        DB_query($sql);
        if (DB_error()) {
            return '103';
        }
        // Values array may be affected by changes to definition, so clear both
        Cache::clear();
        self::reOrder();
        return '';
    }


    /**
     * Get the field-modification part of the SQL statement, if any.
     *
     * @param   array   $A      Array of form fields.
     * @return  string          SQL statement fragment.
     */
    private function getDataSql($A)
    {
        $sql = '';
        if ($A['oldtype'] != $A['type'] || $A['name'] != $A['oldname']) {
            // Only need to update the data table if name or type changed
            $sql_type = $this->getSqlType($A);
            if ($this->id == 0) {
                $sql .= "ADD {$A['name']} $sql_type";
            } else {
                $sql .= "CHANGE {$A['oldname']} {$A['name']} $sql_type";
            }
        }
        return $sql;
    }


    /**
     * Reorder all items in a table.
     */
    private static function reOrder()
    {
        global $_TABLES;

        $sql = "SELECT id, orderby
                FROM {$_TABLES['profile_def']}
                ORDER BY orderby ASC;";
        $result = DB_query($sql, 1);
        if (!$result) return;

        $order = 10;
        $stepNumber = 10;

        while ($A = DB_fetchArray($result, false)) {
            if ($A['orderby'] != $order) {  // only update incorrect ones
                $sql = "UPDATE {$_TABLES['profile_def']}
                        SET orderby = '$order'
                        WHERE id = '" . (int)$A['id'] . "'";
                DB_query($sql, 1);
            }
            $order += $stepNumber;
        }
    }


    /**
     * Prepare to save a value to the DB.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  mixed           String to be saved for this item
     */
    public function prepareToSave($vals)
    {
        if (isset($vals[$this->name])) {
            // new value provided in array
            $newval = $vals[$this->name];
        } elseif (!empty($this->value)) {
            // value already set in this object
            $newval = $this->value;
        } else {
            // no value provided, get the default or blank
            $newval = $this->getOption('default');
        }
        return $newval;
    }


    /**
     * Get field-specific options for the user search form.
     *
     * @return  string      HTML input options
     */
    public function searchFormOpts()
    {
        return '<input type="text" size="50" name="'.$this->name.'" value="" />';
    }


    /**
     * Create the search sql for this field.
     * This is the default function where there is a single value for a field,
     * e.g. text, textarea, radio
     *
     * @param   array   $post   Values from $_POST
     * @param   string  $tbl    Table indicator
     * @return  string          SQL query fragment
     */
    public function createSearchSQL($post, $tbl='data')
    {
        if (!isset($post[$this->name])) return '';

        $sql = '';
        if (isset($post['empty'][$this->name])) {
            $sql = "(`{$tbl}`.`{$this->name}` = '' OR
                     `{$tbl}`.`{$this->name}` IS NULL)";
        } elseif (isset($post[$this->name]) && $post[$this->name] !== '') {
            $value = DB_escapeString($post[$this->name]);
            $sql = "`{$tbl}`.`{$this->name}` like '%{$value}%'";
        }
        return $sql;
    }


    /**
     * Actually gets the options array specific to this field.
     * Default- do nothing, return options array.
     *
     * @param   array   $A      Form values
     * @return  array           Array of options to save
     */
    public function setOptions($A)
    {
        return $this;
    }


    /**
     * Get the SQL field type for the "alter" statement.
     * Default type is TEXT.
     *
     * @return  string      SQL field definition
     */
    public function getSqlType()
    {
        return 'TEXT';
    }


    /**
     * Automatically generate a field value.
     *
     * The site admin can effectively override this function by creating a
     * CUSTOM_profile_autogen() function which takes the field name & type
     * as arguments, or a CUSTOM_profile_autogen_{fieldname} function.
     * The second form takes precedence over the first.
     *
     * @since   version 0.0.3
     * @param   array   $A      Field definition and values
     * @param   integer $uid    Optional user ID.  Zero is acceptable here.
     * @return  string          Value to give the field
     */
     public function AutoGen()
     {
        if (!is_object($A) || empty($A)) {
            return COM_makeSID();
        }
        $function = 'CUSTOM_profile_autogen_' . $this->name;
        if (function_exists($function)) {
            return $function($this, $this->uid);
        } elseif (function_exists('CUSTOM_profile_autogen')) {
            return CUSTOM_profile_autogen($this, $this->uid);
        } else {
            return COM_makeSID();
        }
    }


    /**
     * Get the icon and javascript call to remove a row from the option list.
     * Common for multi-option field types like radio, select and multi-checkbox.
     *
     * @return  string  HTML for row removal icon
     */
    protected function getRemoveRowIcon()
    {
        return '<i class="uk-icon uk-icon-remove uk-text-danger" ' .
            'data-uk-tooltip title="Remove Row" ' .
            'onclick="javascript:return removeRow(this);"></i>';
    }


    /**
     * Get the field prompt to display.
     *
     * @return  string      Prompt string
     */
    public function getPrompt()
    {
        return $this->prompt;
    }


    /**
     * Set the value into the Field object.
     *
     * @param   mixed   $val    Field value
     * @return  object  $this
     */
    public function setValue($val)
    {
        $this->value = $val;
        return $this;
    }


    /**
     * See if this field is required.
     *
     * @return  boolean     True if required, False if not
     */
    public function isRequired()
    {
        return $this->required ? 1 : 0;
    }


    /**
     * Get the type of field.
     *
     * @return  string      Field type
     */
    public function getType()
    {
        return $this->type;
    }


    /**
     * Get the name of this field.
     *
     * @return  string      Field name
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Get the field value.
     *
     * @return  mixed       Field value
     */
    public function getValue()
    {
        return $this->value;
    }


    /**
     * Get one or all field permissions.
     *
     * @param   string  $type   Perm type (owner, group) or empty for all
     * @return  integer|array   Single or all permissions
     */
    public function getPerm($type=NULL)
    {
        if ($type != NULL) {
            $type = 'perm_' . $type;
            return (int)$this->$type;
        } else {
            return array(
                'owner' => $this->perm_owner,
                'group' => $this->perm_group,
                'members' => $this->perm_members,
                'anon' => $this->perm_anon,
            );
        }
    }


    /**
     * Get the at-registration setting for the field.
     *
     * @return  boolean     Status of user_reg flag
     */
    public function getUserReg()
    {
        return $this->user_reg ? 1 : 0;
    }

}   // class Field

?>
