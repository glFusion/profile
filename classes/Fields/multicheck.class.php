<?php
/**
 * Class to handle multi-check profile items.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.2.0
 * @since       1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile\Fields;

/**
 * Class for multiple checkboxes
 * @package profile
 */
class multicheck extends \Profile\Field
{
    /**
     * Constructor.
     * Set up the values array
     *
     * @param   mixed   $item   Name of item, or array of info
     * @param   mixed   $value  Optional value to assign
     * @param   integer $uid    Optional user ID, current user by default
     */
    public function __construct($item=NULL, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
        // One more unserialization needed for multicheck values
        //$this->options['values'] = @unserialize($this->options['values']);
        if (!empty($this->value) && is_string($this->value))
            $this->value = @unserialize($this->value);
        if (is_null($this->value)) $this->value = $this->getOption('default');
        /*if (!is_array($this->options['values'])) {
            $this->options['values'] = @unserialize($value->options['values']);
            if (!$this->options['values']) $this->options['values'] = array();
        }*/
    }


    /**
     * Create the data entry field.
     *
     * @return  string  HTML for radio buttons & prompts
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
            $this->value = $this->getOption('values');
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
     * Return the formatted string value, or an empty strin on error.
     *
     * @param   integer $value  Not used, just for consistency
     * @return  string          String corresponding to value.
     */
    public function FormatValue($value = NULL)
    {
        // If a value is not supplied, use the current value
        if ($value === NULL) {
            $value = $this->value;
        }
        // If the value is a string, unserialize it into an array
        if (is_string($value)) $value = @unserialize($value);

        // Check again that it is an array (unserialize may have failed)
        if (is_array($value)) {
            $formatted = implode(', ', $value);
        } else {
            $formatted = '';
        }
        return $formatted;
    }


    /**
     * Set the value selections ino the $values property.
     *
     * @param   array   $vals   Array of values (key=>value)
     */
    public function setVals($vals)
    {
        $this->values = array();
        foreach ($this->options['values'] as $key=>$option) {
            if (isset($vals[$key]) && $vals[$key]== 1) {
                $this->values[] = $option['value'];
            }
        }
    }


    /**
     * Create the form elements for editing the value selections.
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        if (!empty($this->options['values'])) {
            $multichk_input = '';
            $defaults = $this->getOption('default', array());
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


    /**
     * Prepare to save a value to the DB.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  string          Value to save
     */
    public function prepareToSave($vals)
    {
        return @serialize($vals[$this->name]);
    }


    /**
     * Get field-specific options for the user search form
     *
     * @return  string      HTML input options
     */
    public function searchFormOpts()
    {
        global $LANG_PROFILE;

        $fld = '';
        $opts = $this->getOption('values', array());
        foreach ($opts as $valname) {
            $fld .= '<input type="checkbox" name="'.$this->name .
                        '[]" value="' . htmlendities($valname, ENT_QUOTES) .
                        '" />'.$valname.'&nbsp;';
        }
        return $fld;
    }


    /**
     * Actually gets the options array specific to this field.
     *
     * @param   array   $A      Form values
     * @return  array           Array of options to save
     */
    public function setOptions($A)
    {
        $newvals = array();
        $defaults = array();
        if (!isset($A['multichk_values']) || !is_array($A['multichk_values'])) {
            $A['multichk_values'] = array();
        }
        if (!isset($A['multichk_default']) || !is_array($A['multichk_default'])) {
            $A['multichk_default'] = array();
        }
        foreach ($A['multichk_values'] as $key=>$val) {
            if (empty($val)) continue;  // empty value deletes selection
            $newvals[$key] = $val;
            if (in_array($val, $A['multichk_default'])) $defaults[$key] = $val;
        }
        // Set options as arrays; they'll be serialized later
        $this->options['values'] = $newvals;
        $this->options['default'] = $defaults;
        return $this->options;
    }
 
}

?>
