<?php
/**
*   Class to handle checkbox profile items.
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
namespace Profile\Fields;

/**
*   Class for Checkbox items
*/
class checkbox extends \Profile\Field
{
    /**
    *   Constructor.
    *   Set up the options array
    *
    *   @param  mixed   $item   Name of item, or array of info
    *   @param  mixed   $value  Optional value to assign
    *   @param  integer $uid    Optional user ID, current user by default
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
            $chk = $this->getOption('default', 0) == 1 ? PRF_CHECKED : '';
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
                $this->getOption('default') == 1 ? PRF_CHECKED : '',
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


    /**
    *   Prepare to save a value to the DB.
    *
    *   @param  array   $vals   Array of all submitted values
    *   @return string          Value to save
    */
    public function prepareToSave($vals)
    {
        return isset($vals[$this->name]) && $vals[$this->name] == '1' ? '1' : '0';
    }


    /**
    *   Get field-specific options for the user search form
    *
    *   @return string      HTML input options
    */
    public function searchFormOpts()
    {
        global $LANG_PROFILE;

        return '<input type="radio" name="'. $this->name.'" value="1">' .
                $LANG_PROFILE['checked'] . '&nbsp;' .
                '<input type="radio" name="'. $this->name.'" value="0" />' .
                $LANG_PROFILE['unchecked'];
    }


    /**
    *   Actually gets the options array specific to this field.
    *
    *   @param  array   $A      Form values
    *   @return array           Array of options to save
    */
    public function setOptions($A)
    {
        $this->options['value'] = 1;
        $this->options['default'] = isset($A['chkdefvalue']) ? 1 : 0;
        return $this->options;
    }


    /**
    *   Get the SQL field type for the "alter" statement
    *
    *   @return string      SQL field definition
    */
    public function getSqlType($defvalue)
    {
        return 'TINYINT(1) UNSIGNED NOT NULL DEFAULT ' . (int)$this->getOption('default');
    }

}

?>
