<?php
/**
*   Class to handle individual profile items.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2009-2016 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.4
*   @license    http://opensource.org/licenses/gpl-2.0.php
*               GNU Public License v2 or later
*   @filesource
*/

/**
*   Class for profile items
*   @package    profile
*   @since      1.1.0
*/
class prfItem
{
    /** User ID
        @var integer */
    var $uid;

    /** Variable Name
        @var string */
    var $name;

    /** Variable Value
        @var mixed */
    var $value;

    /** Formatted Value, for dates and numbers
        @var string */
    var $formattedvalue;

    /** Options for this item
        @var array */
    var $options = array();

    /** Required flag
        @var boolean */
    var $required;

    /** Does this item appear on the registration form?
        @var boolean */
    var $user_reg;

    /** CSS class to use on the entry form
        @var string */
    protected $_frmClass = '';

    /** Set to "DISABLED" if this item is read-only, to disable frm entry
        @var string */
    protected $_frmReadonly = '';

    /** This indicates a readonly field, just for convenience where it
        might be checked.
        @var boolean */
    protected $readonly;

    /** Easily indicate a hidden field.
        @var boolean */
    protected $hidden;


    /**
    *   Constructor.  Sets the local properties using the array $item.
    *
    *   @param  mixed   $item   Name of item, or array of info
    *   @param  mixed   $value  Optional value to assign
    *   @param  integer $uid    Optional user ID, current user by default
    */
    public function __construct($item, $value='', $uid = '')
    {
        global $_USER, $_TABLES;

        if (empty($uid)) $uid = $_USER['uid'];
        $this->uid = (int)$uid;

        $A = array();

        // If the item info is an array, then assume that it's been read from
        // the database and simply populate our variables; no need to re-read.
        // Otherwise, read the item from the DB.
        if (is_array($item)) {
            $A = $item;
        } else {
            $sql = "SELECT *
                    FROM {$_TABLES['profile_def']}
                    WHERE name='" . DB_escapeString($item) . "'";
            $res = DB_query($sql);
            $A = DB_fetchArray($res, false);
        }
        if (!empty($A)) {
            $this->name = $A['name'];
            $this->value = $A['value'];
            $this->options = @unserialize($A['options']);
            $this->user_reg = $A['user_reg'] == 1 ? 1 : 0;
            $this->orderby = (int)$A['orderby'];
            $this->type = $A['type'];
            $this->enabled = $A['enabled'] == 1 ? 1 : 0;
            $this->required = $A['required'] == 1 ? 1 : 0;
            $this->show_in_profile = $A['show_in_profile'] == 1 ? 1 : 0;
            $this->prompt = $A['prompt'];
            $this->group_id = (int)$A['group_id'];
            $this->perm_owner = (int)$A['perm_owner'];
            $this->perm_group = (int)$A['perm_group'];
            $this->perm_members = (int)$A['perm_members'];
            $this->perm_anon = (int)$A['perm_anon'];
        }

        // Override the item's value if one is provided.
        if (!empty($value)) $this->value = $value;

        // If the unserialization failed, provide an empty array
        if (!$this->options) $this->options = array();
    }


    /**
    *   Return a formatted value.
    *   This default function simply returns the current value unchanged.
    *
    *   @return string  Value formatted for display
    */
    public function FormatValue($value = '')
    {
        if ($value == '') $value = $this->value;
        return htmlspecialchars($value);
    }


    /**
    *   Initialize common form field requirements.
    */
    protected function _FormFieldInit()
    {
        global $_SYSTEM;

        // Check for required status.  May need to excluded dates from this...
        // Setting 'required' on the profile page causes issues for uikit
        // since it's not checked until submission and the user may never
        // see the error. In that case, don't set the field as required and
        // rely on post-submission validation.
        if ($this->required == 1 && $_SYSTEM['framework'] == 'legacy') {
            $this->_frmClass = "class=\"fValidate['required']\" ";
        } else {
            $this->_frmClass = 'class="prfInputText"';
        }

        // If POSTed form data, set the user variable to that.  Otherwise,
        // set it to the default or leave it alone.
        if (isset($_POST[$this->name])) {
            $this->value = $_POST[$this->name];
        } elseif (is_null($this->value)) {
            $this->value = $this->options['default'];
        }

        // Check for read-only status on the field.  Admins can always
        // edit user values.
        $this->_frmReadonly = '';
        $this->readonly = false;
        $this->hidden = false;
        if ($this->perm_owner < 3 &&
                !SEC_hasRights('profile.admin, profile.manage', 'OR')) {
            $this->_frmReadonly = ' disabled="disabled" ';
            $this->readonly = true;
            $this->hidden = $this->perm_owner < 2 ? true : false;
        }
    }


    /**
    *   Provide a form field for editing this data item.
    *   This provides a text input field, to be overridden by child classes
    *
    *   @return string  HTML for data entry field
    */
    public function FormField()
    {
        $this->_FormFieldInit();

        $size = $this->options['size'];
        $maxlength = $this->options['maxlength'];
        $maxlength = (int)$maxlength > 0 ? "maxlength=\"$maxlength\"" : '';
        $fld = "<input $this->_frmClass name=\"{$this->name}\"
                    id=\"{$this->name}\" $maxlength
                    size=\"$size\"
                    type=\"text\" value=\"{$this->value}\" $this->_frmReadonly>\n";

        return $fld;
    }


    /**
    *   Get the available options for this field.
    *
    *   @return array   Field options
    */
    public function Options()
    {
        return $this->options;
    }


    /**
    *   Sanitize the value.
    *   Does not make it database-safe, just strips invalid or objectionable
    *   material.
    *   This will depend largely upon the type of field.
    *
    *   @param  mixed   $val    Current field value
    *   @return mixed           Sanitized value.
    */
    public function Sanitize($val)
    {
        //$val = COM_checkWords(COM_checkHTML($val));
        return $val;
    }


    /**
    *   Check if this item should be publicly displayed in the user's
    *   profile.
    *
    *   @return boolean     True if publicly visible, False if not
    */
    public function isPublic()
    {
        return ($this->perm_members > 1 || $this->perm_anon > 1) ? TRUE : FALSE;
    }


    /**
    *   Prepare this item's values to be saved in the database
    *
    *   @return string      DB-safe version of the value(s)
    */
    public function prepareForDB()
    {
        return DB_escapeString($this->value);
    }


    /**
    *   Create the form elements for editing the value selections
    *   Returns the default value for single-value fields (text, textarea, etc)
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        return array(
            'defvalue'  =>$this->options['default'],
            'maxlength' => $this->options['maxlength'],
            'size'      => $this->options['size'],
        );
    }


    /**
    *   Check if a field has valid data.  Used in conjuction with the "required" flag.
    *
    *   @param  boolean     $required   Return false only if invalid AND required
    *   @return booldan         True if data is valid, False if not
    */
    public function validData($value = NULL)
    {
        $val = $value !== NULL ? $value : $this->value;
        if ($this->required && empty($val)) return false;
        else return true;
    }


    /**
    *   Get a class based on the type of field definition.
    *   If the requested class doesn't exist, return prfText() object.
    *
    *   @param  array   $A      Row from profile_def table
    *   @param  mixed   $value  Data value to pass to class
    *   @param  integer $uid    User ID to pass to class
    *   @return object          Class instsnce
    */
    public static function getClass($A, $value='', $uid=0)
    {
        $classname = 'prf' . $A['type'];
        if (class_exists($classname)) {
            return new $classname($A, $value, $uid);
        } else {
            return new prfText($A, $value, $uid);
        }
    }

}   // class prfItem


/**
*   Class for text input fields
*   The base class has all the functionality for text fields, this is a stub.
*   @package    profile
*/
class prfText extends prfItem
{
}


/**
*   Class for textarea input fields
*
*   @package    profile
*/
class prfTextarea extends prfItem
{
    public function FormField()
    {
        global $LANG_PROFILE, $_CONF;

        $this->_FormFieldInit();

        // Textareas get width attributes from the layout CSS, so counteract
        // by setting the width
        $fld = '<textarea style="width:90%;" ' .
                "{$this->_frmClass} name=\"{$this->name}\" " .
                "rows=\"{$this->options['rows']}\" " .
                "cols=\"{$this->options['cols']}\" " .
                "id=\"{$this->name}\" {$this->_frmReadonly}>" .
                $this->value . "</textarea>\n";
        return $fld;
    }


    /**
    *   Return the value formatted for display.
    *
    *   @param  string  $value  Optional value, current value if empty
    *   @return         Formatted value for display.
    */
    public function FormatValue($value = '')
    {
        if (empty($value))
            $value = $this->value;

        return nl2br(htmlspecialchars($value));
    }


    /**
    *   Sanitize multi-line input.
    *   Don't call COM_checkHTML() since that will remove newlines.
    *   Our FormatValue() function escapes all HTML anyway.
    *
    *   @param  string  $val    Original value
    *   @return string          Sanitized version.
    */
    public function Sanitize($val)
    {
        return COM_checkWords($val);
    }

    public function editValues()
    {
        return array(
            'default'   => $this->options['default'],
            'rows'      => $this->options['rows'],
            'cols'      => $this->options['cols'],
            'help_txt'  => $this->options['help_text'],
        );
    }

}


/**
*   Class for link input fields
*   @package    profile
*/
class prfLink extends prfItem
{
    public function FormatValue($value = '')
    {
        global $_PRF_CONF, $_CONF;

        // Convert the sql date to a timestamp, then output it according
        // the field'd display format
        if (empty($value))
            $value = $this->value;

        if (empty($value))
            return '';

        return '<a href="' . $value . '" rel="nofollow">' .
            htmlspecialchars($value) . '</a>';
    }

    public function Sanitize($val)
    {
        $val = parent::Sanitize($val);
        $val = COM_sanitizeURL($val);
        return $val;
    }
}


/**
*   Class for static text fields
*   @package    profile
*/
class prfStatic extends prfItem
{
    public function __construct($item, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
        $this->value = $this->options['value'];
    }


    /**
    *   Returns the static text
    *
    *   @return string  HTML for data entry field
    */
    public function FormField()
    {
        return $this->options['value'];
    }


    /**
    *   Create the form elements for editing the value selections
    *   Returns the default value for single-value fields (text, textarea, etc)
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        return array('valuestr' => $this->options['value']);
    }
}


/**
*   Class for date fields
*   @package    profile
*/
class prfDate extends prfItem
{
    /**
    *   Get the value formatted for display, depending on the field's format.
    *
    *   @param  string  $value  Value to display, current value by default
    *   @return string  HTML for formatted field value
    */
    public function FormatValue($value = '')
    {
        global $_PRF_CONF, $_CONF;

        // Convert the sql date to a timestamp, then output it according
        // the field'd display format
        if (empty($value))
            $value = $this->value;

        if (empty($value) || $value == '0000-00-00')
            return '';

        // Explode parts from 'YYYY-MM-DD hh:mm:ss' format
        $dt_tm = explode(' ', $value);
        list($year, $month, $day) = explode('-', $dt_tm[0]);
        if (isset($dt_tm[1])) {
            list($hour, $minute, $second) = explode(':', $dt_tm[1]);
        } else {
            $hour = '00';
            $minute = '00';
            $second = '00';
        }

        switch ($this->options['input_format']) {
        case '2':
            $formatted = sprintf('%02d/%02d/%04d', $day, $month, $year);
            break;
        case '1':
            $formatted = sprintf('%02d/%02d/%04d', $month, $day, $year);
            break;
        default:
            // use sql date only: YYYY-MM-DD
            $formatted = $dt_tm[0];
            break;
        }

        if ($this->options['timeformat'] != '') {
            $format = $this->options['timeformat'] == '12' ? '%I:%M %p' : '%H:%M';
            $formatted .= ' ' .
                    strftime($format, mktime($hour, $minute, $second));
        }
        /*if ($this->options['showtime'] == 1) {
            if ($_CONF['hour_mode'] == 12) {
                //$format = ' %I:%M:%S %p';
                $format = ' %I:%M %p';
            } else {
                //$format = ' %H:%M:%S';
                $format = ' %H:%S';
            }
            $ts = mktime($hour, $minute, $second);
            $formatted .= strftime($format, $ts);
        }*/

        return $formatted;
    }


    /**
    *   Create the form entry field.
    *
    *   @return string      HTML for month, day and year fields.
    */
    public function FormField($incl_time = true)
    {
        global $LANG_PROFILE, $_CONF, $_PRF_CONF;

        $this->_FormFieldInit();

        if ($this->options['timeformat'] && $incl_time) {
            $iFormat = '%Y-%m-%d %H:%M';
            list($date, $time) = explode(' ', $this->value);
            if ($time == NULL) $time = '00:00:00';
            $dt = explode('-', $date);
            $tm = explode(':', $time);
            if (isset($_POST)) {
                if (isset($_POST[$this->name . '_hour']))
                    $tm[0] = $_POST[$this->name . '_hour'];
                if (isset($_POST[$this->name . '_minute']))
                    $tm[1] = $_POST[$this->name . '_minute'];
            }

            if ($this->options['timeformat'] == '12') {
                list($hour, $ampm_sel) = $this->hour24to12($tm[0]);
                $ampm_fld = '&nbsp;&nbsp;' .
                    self::ampmSelection($this->name . '_ampm', $ampm_sel);
            } else {
                $ampm_fld = '';
                $hour = $tm[0];
            }

            $hr_fld = '<select id="' . $this->name . '_hour" ' .
                    'name="' . $this->name . '_hour" ' .
                    $this->_frmReadOnly . '>' . LB .
                COM_getHourFormOptions($hour, $this->options['timeformat']) .
                '</select>' . LB;
            $min_fld = '<select id="' . $this->name . '_minute" ' .
                    'name="' . $this->name . '_minute" ' .
                    $this->_frmReadOnly . '>' . LB .
                COM_getMinuteFormOptions($tm[1]) .
                '</select>' . LB;
            /*$hr_fld = '<input type="text" id="' . $this->name .
                '_hour" size="2" maxlength="2" name="' . $this->name .
                '_hour" value="' . $hour . '" ' . $this->_frmReadonly .
                '/>' . LB;
            $min_fld = '<input type="text" id="' . $this->name .
                '_minute" size="2" maxlength="2" name="' . $this->name .
                '_minute" value="' . $tm[1] . '" ' . $this->_frmReadonly .
                '/>' . LB;*/
        } else {
            $iFormat = '%Y-%m-%d';
            $dt = explode('-', $this->value);
            $tm = NULL;
            $hr_fld = '';
            $min_fld = '';
        }

        if (isset($_POST)) {
            // Get the values from $_POST, if set.
            if (isset($_POST[$this->name . '_month']))
                $dt[1] = $_POST[$this->name . '_month'];
            if (isset($_POST[$this->name . '_day']))
                $dt[2] = $_POST[$this->name . '_day'];
            if (isset($_POST[$this->name . '_year']))
                $dt[0] = $_POST[$this->name . '_year'];
        }

        $m_fld = "<select id=\"{$this->name}_month\" name=\"{$this->name}_month\" {$this->_frmReadonly}>\n";
        $m_fld .= "<option value=\"0\">--{$LANG_PROFILE['month']}--</option>\n";
        $m_fld .= COM_getMonthFormOptions((int)$dt[1]) . "</select>\n";

        $d_fld = "<select id=\"{$this->name}_day\" name=\"{$this->name}_day\" {$this->_frmReadonly}>\n";
        $d_fld .= "<option value=\"0\">--{$LANG_PROFILE['day']}--</option>\n";
        $d_fld .= COM_getDayFormOptions((int)$dt[2]) . "</select>\n";

        $y_fld = $LANG_PROFILE['year'] .
            ': <input type="text" id="' . $this->name . '_year" name="' .
            $this->name . '_year" size="5" value="' . $dt[0] . '" ' .
            $this->_frmReadonly . '/>' . LB;

        // Hidden field to hold the date in its native format
        // Required for the date picker
        $datepick = '<input type="hidden" name="f_' . $this->name .
                '" id="f_' . $this->name . '" value="' . $this->value .
                '"/>' . LB;

        if (!$this->readonly) {
            // If not a readonly field, add the date picker image & js
            $datepick .= '<i class="' . $_PRF_CONF['_iconset'] .
                    '-calendar tooltip" title="' .
                    $LANG_PROFILE['select_date'] . '" id="' .
                    $this->name . '_trigger"></i>';
            if ($this->options['timeformat']) {
                $showtime = 'true';
                $timeformat = $this->options['timeformat'];
            } else {
                $showtime = 'false';
                $timeformat = 0;
            }
            $datepick .= LB . "<script type=\"text/javascript\">
Calendar.setup({
inputField :   \"f_{$this->name}\",
ifFormat   :   \"$iFormat\",
showsTime  :    $showtime,
timeFormat :    \"{$timeformat}\",
button     :   \"{$this->name}_trigger\",
onUpdate   :   {$this->name}_onUpdate
});
function {$this->name}_onUpdate(cal)
{
    var d = cal.date;

    if (cal.dateClicked && d) {
        PRF_updateDate(d, \"{$this->name}\", \"$timeformat\");
    }
    return true;
}
</script>" . LB;

        }

        // Place the date components according to m/d/y or d/m/y format
        switch ($this->options['input_format']) {
        case 2:
            $fld = $d_fld . ' ' . $m_fld . ' ' . $y_fld;
            break;
        case 1:
        default:
            $fld = $m_fld . ' ' . $d_fld . ' ' . $y_fld;
            break;
        }

        //if ($this->options['showtime']) {
        if ($this->options['timeformat']) {
            $fld .= '&nbsp;&nbsp;' . $hr_fld . ':' . $min_fld . $ampm_fld;
        }

        $fld .= $datepick;

        return $fld;
    }


    /**
    *   Get the available date formats
    *
    *   @return array   Array of date formats
    */
    public function DateFormats()
    {
        global $LANG_PROFILE;
        $_formats = array(
            1 => $LANG_PROFILE['month'].' '.$LANG_PROFILE['day'].' '.$LANG_PROFILE['year'],
            2 => $LANG_PROFILE['day'].' '.$LANG_PROFILE['month'].' '.$LANG_PROFILE['year'],
        );
        return $_formats;
    }


    /**
    *   Create the date format selector for use in defining the field
    *   Doesn't include the <select></select> tags, just the options.
    *
    *   @param  integer $cur    Currently-selected format
    *   @return string          HTML for date format seleection options.
    */
    public function DateFormatSelect($cur=0)
    {
        $retval = '';
        $_formats = prfDate::DateFormats();
        foreach ($_formats as $key => $string) {
            $sel = $cur == $key ? PRF_SELECTED : '';
            $retval .= "<option value=\"$key\" $sel>$string</option>\n";
        }
        return $retval;
    }


    /**
    *   Convert an hour from 24-hour to 12-hour format for display.
    *
    *   @param  integer $hour   Hour to convert
    *   @return array       array(new_hour, ampm_indicator)
    */
    public function hour24to12($hour)
    {
        if ($hour >= 12) {
            $ampm = 'pm';
            if ($hour > 12) $hour -= 12;
        } else {
            $ampm = 'am';
            if ($hour == 0) $hour = 12;
        }
        return array($hour, $ampm);
    }


    /**
    *   Convert an hour from 12-hour to 24-hour format.
    *
    *   @param  integer $hour   Hour to convert
    *   @param  boolean $pm     True if 'pm' is set
    *   @return integer     New hour
    */
    public function hour12to24($hour, $pm)
    {
        if ($pm) {
            if ($hour < 12) $hour += 12;
        } else {
            if ($hour == 12) $hour = 0;
        }
        return $hour;
    }


    /**
    *   Get the AM/PM selection.
    *   This is exactly like COM_getAmPmFormSelection(), but
    *   adds the "id" to the selection so javascript can update it.
    *
    *   @param  string  $name       Field name
    *   @param  string  $selected   Which option is selected, am or pm?
    *   @return string      HTML for selection
    */
    private function ampmSelection($name, $selected)
    {
        global $_CONF;

        if (isset($_CONF['hour_mode'] ) && ( $_CONF['hour_mode'] == 24 )) {
            $retval = '';
        } else {
            $retval .= '<select id="' . $name . '" name="' . $name . '">' .LB;
            $retval .= '<option value="0" ';
            if ($selected == '0' || $selected = 'am') {
                $retval .= PRF_SELECTED;
            }
            $retval .= '>am</option>' . LB . '<option value="1" ';
            if ($selected == '1' || $selected = 'pm') {
                $retval .= PRF_SELECTED;
            }
            $retval .= '>pm</option>' . LB . '</select>' . LB;
        }

        return $retval;
    }


    /**
    *   Create the form elements for editing the value selections
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        $opts = array(
            'shtime_chk' => $this->options['showtime'] == 1 ? PRF_CHECKED : '',
        );
        switch ($this->options['timeformat']) {
        case '24':
            $opts['24h_sel'] = PRF_CHECKED;
            break;
        case '12':
            $opts['12h_sel'] = PRF_CHECKED;
            break;
        default:
            $opts['tm_none_sel'] = PRF_CHECKED;
            break;
        }
        return $opts;
    }

    /**
    *   Check if a field has valid data.  Used in conjuction with the
    *   "required" flag.
    *
    *   @param  string  $value  Unused, required for inheritance
    *   @param  array   $vals   Array of values
    *   @return boolean     True if data is valid, False if not
    */
    public function validData($value = NULL, $vals = NULL)
    {
        // This function uses the field name and the $vals array to construct
        // month, day and year values. $value is unused

        if (!is_array($vals)) {
            // Can't be valid data
            return false;
        }

        if ($this->required) {
            $month = isset($vals[$this->name . '_month']) ?
                    (int)$vals[$this->name . '_month'] : 0;
            $day = isset($vals[$this->name . '_day']) ?
                    (int)$vals[$this->name . '_day'] : 0;
            $year = isset($vals[$this->name . '_year']) ?
                    (int)$vals[$this->name . '_year'] : 0;
            USES_lglib_class_datecalc();
            if (!Date_Calc::isValidDate($month, $day, $year)) return false;
        }
        return true;
    }

}


/**
*   Class for Checkbox items
*/
class prfCheckbox extends prfItem
{
    /**
    *   Constructor.
    *   Set up the options array
    */
    public function __construct($item, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
    }


    /**
    *   Return the formatted string value.
    *
    *   @param  integer $value  Optional value.  "1" = on, "0" = off
    *   @return string          String corresponding to value.
    */
    public function FormatValue($value = 0)
    {
        global $LANG_PROFILE;

        if (empty($value))
            $value = $this->value;

        $formatted = $value == 1 ? $LANG_PROFILE['yes'] : $LANG_PROFILE['no'];

        return $formatted;
    }


    /**
    *   Create the data entry field
    *
    *   @return string  HTML for checkbox field, with prompt string
    */
    public function FormField()
    {
        $this->_FormFieldInit();

        if (is_null($this->value)) {
            $chk = $this->options['default'] == 1 ? PRF_CHECKED : '';
        } elseif ($this->value == 1) {
            $chk = PRF_CHECKED;
        } else {
            $chk = '';
        }

        // _frmClass is ignored since fValidator doesn't work for checkboxes
        $fld = "<input {$this->_frmClass} name=\"{$this->name}\"
                    id=\"{$this->name}\"
                    type=\"checkbox\" value=\"1\" $chk
                    {$this->_frmReadonly}>\n";
        return $fld;
    }


    /**
    *   Sanitize this field for storage
    *
    *   @param  integer $val    Starting value
    *   @return integer         Sanitized value, either 1 or 0
    */
    public function Sanitize($val)
    {
        $val = $val == 1 ? 1 : 0;
        return $val;
    }


    /**
    *   Special handling for "required" setting for checkboxes.
    *   FValidator doesn't work right, so don't use it and allow
    *   the submission handling to deal with empty checkboxes.
    */
    protected function _FormFieldInit()
    {
        parent::_FormFieldInit();
        $this->_frmClass = '';
    }


    /**
    *   Create the form elements for editing the value selections
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        return array(
            'chkdefault_chk' =>
                $this->options['default'] == 1 ? PRF_CHECKED : '',
        );
    }


    /**
    *   Check if a field has valid data.  Used in conjuction with the "required" flag.
    *
    *   @param  boolean     $required   Return false only if invalid AND required
    *   @return booldan         True if data is valid, False if not
    */
    public function validData($value = NULL)
    {
        $val = $value !== NULL ? $value : $this->value;
        if ($this->required && $val != 1) return false;
        else return true;
    }

}


/**
*   Class for radio button items.
*   @package    profile
*/
class prfRadio extends prfItem
{
    /**
    *   Constructor
    *   Set up the array of value options
    *
    *   @param  mixed   $item   Name of item, or array of info
    *   @param  string  $value  Optional value to assign, serialized array
    *   @param  integer $uid    Optional user ID, current user by default
    */
    public function __construct($item, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
        if (!isset($this->options['values'])) {
            $this->options['values'] = array();
        } elseif (!is_array($this->options['values'])) {
            $this->options['values'] = @unserialize($this->options['values']);
            if (!$this->options['values']) $this->options['values'] = array();
        }
    }


    /**
    *   Create the data entry field.
    *   Creates radio buttons in a line.
    *
    *   @return string  HTML for radio buttons & prompts
    */
    public function FormField()
    {
        $this->_FormFieldInit();

        if (empty($this->options['values'])) {
            // Have to have some values for radio buttons
            return '';
        }

        // If no current value, use the defined default
        if (is_null($this->value)) {
            $this->value = $this->options['default'];
        }

        $fld = '';
        foreach ($this->options['values'] as $id=>$value) {
            $sel = $this->value == $value ? PRF_CHECKED : '';
            $fld .= "<input $this->_frmClass type=\"radio\"
                name=\"{$this->name}\"
                id=\"{$this->name}\"
                value=\"" . htmlentities($value, ENT_QUOTES) .
                "\" $sel {$this->_frmReadonly}/>$value&nbsp;\n";
        }
        return $fld;
    }


    /**
    *   Create the form elements for editing the value selections
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        $listinput = '';
        $i = 0;
        if (!empty($this->options['values'])) {
            foreach ($this->options['values'] as $valname) {
                $listinput .= '<li><input type="text" id="vName' . $i .
                        '" value="' . $valname . '" name="selvalues[]" />';
                $sel = $this->options['default'] == $valname ?
                        PRF_CHECKED : '';
                $listinput .= "<input type=\"radio\" name=\"sel_default\"
                        value=\"$i\" $sel />";
                $listinput .= '</li>' . "\n";
                $i++;
            }
        } else {
            $thie->options['values'] = array();
            $listinput = '<li><input type="text" id="vName0" value=""
                name="selvalues[]" /></li>' . "\n";
        }
        return array('list_input'=>$listinput);
    }

}


/**
*   Class for dropdown selections
*   @package    profile
*/
class prfSelect extends prfItem
{
    /**
    *   Constructor.
    *   Set up the values array
    *
    *   @param  mixed   $item   Name of item, or array of info
    *   @param  mixed   $value  Optional value to assign
    *   @param  integer $uid    Optional user ID, current user by default
    */
    public function __construct($item, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
        if (!isset($this->options['values'])) {
            $this->options['values'] = array();
        } elseif (!is_array($this->options['values'])) {
            $this->options['values'] = @unserialize($this->options['values']);
            if (!$this->options['values']) $this->options['values'] = array();
        }
    }


    /**
    *   Create the data entry field
    *
    *   @return string  HTML for selection dropdown.  Included <select> tags.
    */
    public function FormField()
    {
        global $LANG_PROFILE;

        $this->_FormFieldInit();

        $fld = "<select $this->_frmClass name=\"{$this->name}\"
                    id=\"{$this->name}\" $this->_frmReadonly>\n";
        $fld .= "<option value=\"\">--{$LANG_PROFILE['select']}--</option>\n";
        foreach ($this->options['values'] as $id=>$value) {
            $sel = $this->value == $value ? PRF_SELECTED : '';
            $fld .= "<option value=\"$value\" $sel>$value</option>\n";
        }
        $fld .= "</select>\n";
        return $fld;
    }


    /**
    *   Create the form elements for editing the value selections
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        $listinput = '';
        $i = 0;
        if (!empty($this->options['values'])) {
            foreach ($this->options['values'] as $valname) {
                $listinput .= '<li><input type="text" id="vName' . $i .
                        '" value="' . $valname . '" name="selvalues[]" />';
                $sel = $this->options['default'] == $valname ?
                        PRF_CHECKED : '';
                $listinput .= "<input type=\"radio\" name=\"sel_default\"
                        value=\"$i\" $sel />";
                $listinput .= '</li>' . "\n";
                $i++;
            }
        } else {
            $thie->options['values'] = array();
            $listinput = '<li><input type="text" id="vName0" value=""
                name="selvalues[]" /></li>' . "\n";
        }
        return array('list_input'=>$listinput);
    }
}


/**
*   Class for multiple checkboxes
*   @package    profile
*/
class prfMultiCheck extends prfItem
{
    /**
    *   Constructor.
    *   Set up the values array
    *
    *   @param  mixed   $item   Name of item, or array of info
    *   @param  mixed   $value  Optional value to assign
    *   @param  integer $uid    Optional user ID, current user by default
    */
    public function __construct($item, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
        // One more unserialization needed for multicheck values
        //$this->options['values'] = @unserialize($this->options['values']);
        if (!empty($this->value) && is_string($this->value))
            $this->value = @unserialize($this->value);
        if (is_null($this->value)) $this->value = $this->options['default'];
        /*if (!is_array($this->options['values'])) {
            $this->options['values'] = @unserialize($value->options['values']);
            if (!$this->options['values']) $this->options['values'] = array();
        }*/
    }


    /**
    *   Create the data entry field
    *
    *   @return string  HTML for radio buttons & prompts
    */
    public function FormField()
    {
        $this->_FormFieldInit();

        if (empty($this->options['values'])) {
            // Have to have some values for checkboxes
            return '';
        }
        // If no current value, use the defined default
        if (is_null($this->value)) {
            $this->value = $this->options['values'];
        }

        $fld = '';
        $i = 0;
        if (!is_array($this->value)) $this->value = @unserialize($this->value);
        if (!is_array($this->value)) $this->value = array();
        foreach ($this->options['values'] as $id=>$value) {
            $sel = in_array($value, $this->value) ? PRF_CHECKED : '';
            $fld .= '<span style="white-space:nowrap">' .
                    "<input {$this->_frmClass} type=\"checkbox\" " .
                    "name=\"{$this->name}[$id]\" id=\"{$this->name}_$id\" " .
                    "value=\"$value\" $sel {$this->_frmReadonly} />&nbsp;" .
                    $value . '</span>' . LB;
            $i++;
        }
        return $fld;
    }


    /**
    *   Return the formatted string value.
    *
    *   @param  integer $value  Not used, just for consistency
    *   @return string          String corresponding to value.
    */
    public function FormatValue($value = '')
    {
        if (is_string($value)) $value = @unserialize($value);
        if (!is_array($value)) $value = $this->value;
        if (is_array($value)) $formatted = implode(', ', $value);
        else $formatted = '';
        return $formatted;
    }


    public function setVals($vals)
    {
        $this->values = array();
        foreach ($this->options['values'] as $key=>$option) {
            if (isset($vals[$key]) && $vals[$key]== 1) {
                $this->values[] = $option['value'];
            }
        }
    }


    public function prepareForDB()
    {
        return serialize($this->values);
    }


    /**
    *   Create the form elements for editing the value selections
    *
    *   @return array   Array of name=>value pairs for Template::set_var()
    */
    public function editValues()
    {
        if (!empty($this->options['values'])) {
            $multichk_input = '';
            $defaults = $this->options['default'];
            if (!is_array($defaults)) $defaults = array();
            foreach ($this->options['values'] as $key=>$valdata) {
                $multichk_input .= '<li><input type="text" id="multiName' . $key .
                        '" value="' . $valdata. '" name="multichk_values['.$key.']" />';
                $sel = in_array($valdata, $defaults) ? PRF_CHECKED : '';
                $multichk_input .= "<input type=\"checkbox\" name=\"multichk_default[$key]\"
                        value=\"$valdata\" $sel />";
                $multichk_input .= '</li>' . "\n";
                $i++;
            }
        } else {
            $values = array();
            $multichk_input = '<li><input type="text" id="multiName0" value=""
                    name="multichk_values[0]" /></li>' . "\n";
        }
        return array('multichk_input'=>$multichk_input);
    }
}


/**
*   Class for a user account selection
*   This can be used to link the account to another
*   @package    profile
*/
class prfAccount extends prfItem
{
    /**
    *   Create the data entry field
    *
    *   @uses   AccountSelection();
    *   @return string  HTML for selection dropdown.  Includes <select> tags.
    */
    public function FormField()
    {
        global $LANG_PROFILE;

        $this->_FormFieldInit();

        $fld = "<select $this->_frmClass name=\"{$this->name}\"
                    id=\"{$this->name}\" $this->_frmReadonly>\n";
        $fld .= $this->AccountSelection($this->value);
        //$fld .= "<option value=\"0\">--{$LANG_PROFILE['select']}--</option>\n";
        /*foreach ($this->options['values'] as $id=>$value) {
            $sel = $this->value == $value ? 'selected="selected"' : '';
            $fld .= "<option value=\"$value\" $sel>$value</option>\n";
        }*/
        $fld .= "</select>\n";
        return $fld;
    }


    /**
    *   Create an account selection list.
    *
    *   @param  integer $selected   Currently-selected value
    *   @return string              HTML for selection options
    */
    public function AccountSelection($selected = 0)
    {
        global $_TABLES;

        $sel = 0 == $selected ? PRF_SELECTED : '';
        $retval = '<option value="0" ' . $sel . '>--None--</option>' . LB;

        $sql = "SELECT uid, username, fullname
                FROM {$_TABLES['users']}
                ORDER BY username ASC";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $sel = $A['uid'] == $selected ? PRF_SELECTED : '';
            $retval .= '<option value="' . $A['uid'] . '" ' . $sel . '>' .
                    $A['username'] . ' - ' . $A['fullname'] . '</option>' . LB;
        }
        return $retval;
    }


    public function FormatValue($value = 0)
    {
        global $_CONF, $_TABLES, $LANG_PROFILE;

        if ($value == 0) $value = $this->value;
        /*if ($value == 0) {
            return '';
        }*/

        $linebreak = '';
        if ($value > 0) {
            $parent = COM_createLink(COM_getDisplayName($value) . " ($value)",
                $_CONF['site_url'] . '/users.php?mode=profile&amp;uid=' . $value);
        } else {
            $parent = '';
        }
        $children = array();
        $accounts = $parent;

        $sql = "SELECT puid FROM {$_TABLES['profile_data']}
                WHERE sys_parent = '{$this->uid}'";

        $res = DB_query($sql, 1);
        while ($A = DB_fetchArray($res, false)) {
            $children[] = COM_createLink(COM_getDisplayName($A['puid']) .
                    ' (' . $A['puid'] . ')',
                $_CONF['site_url'].'/users.php?mode=profile&amp;uid='.$A['puid']);
        }
        if (!empty($children)) {
            $children = implode(', ', $children);
            if (!empty($parent)) {
                $accounts .= $linebreak;
            }
            $accounts .= $LANG_PROFILE['child_accounts'] . ': ' . $children;
        }

        return $accounts;
    }

}

?>
