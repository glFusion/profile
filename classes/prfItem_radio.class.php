<?php
/**
*   Class to handle radio-button profile items.
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
*   Class for radio button items.
*   @package    profile
*/
class prfItem_radio extends prfItem
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


    /**
    *   Prepare to save a value to the DB
    *
    *   @param  array   $vals   Array of all submitted values
    *   @return mixed           String to be saved for this item
    */
    public function prepareToSave($vals)
    {
        $name = $this->name;
        if (!isset($vals[$name]) && empty($this->value) &&
                isset($this->options['values']['default'])) {
                $vals[$name] = $data->options['values']['default'];
        }
        $newval = isset($vals[$name]) ? $vals[$name] : '';
        return $newval;
    }


    /**
    *   Get field-specific options for the user search form
    *
    *   @return string      HTML input options
    */
    public function searchFormOpts()
    {
        global $LANG_PROFILE;

        $fld = '';
        $opts = $this->getOption('values', array());
        foreach ($opts as $valname) {
            $fld .= '<input type="radio" name="'.$this->name .
                    '" value="'.htmlentities($valname, ENT_QUOTES) .
                    '" />'.$valname.'&nbsp;';
        }
        return $fld;
    }


    /**
    *   Actually gets the options array specific to this field.
    *
    *   @param  array   $A      Form values
    *   @return array           Array of options to save
    */
    public function setOptions($A)
    {
        $newvals = array();
        if (!isset($A['selvalues']) || !is_array($A['selvalues'])) {
            $A['selvalues'] = array();
        }
        foreach ($A['selvalues'] as $val) {
            if (!empty($val)) {
                $newvals[] = $val;
            }
        }
        $this->options['default'] = '';
        $this->options['values'] = $newvals;
        return $this->options;
    }


    /**
    *   Get the SQL field type for the "alter" statement
    *
    *   @return string      SQL field definition
    */
    public function getSqlType()
    {
        return 'TEXT';
    }
 
}

?>
