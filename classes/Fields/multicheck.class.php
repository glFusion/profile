<?php
/**
 * Class to handle multi-check profile items.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2020 Lee Garner <lee@leegarner.com>
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
        if (!is_array($this->value)) $this->value = @unserialize($this->value);
        if (!is_array($this->value)) $this->value = array();
        $T = $this->_getTemplate();
        $T->set_block('template', 'optionRow', 'opt');
        foreach ($this->options['values'] as $id=>$value) {
            $T->set_var(array(
                'classes' => $this->_frmClass,
                'name' => $this->name,
                'id' => $id,
                'value' => $value,
                'opt_sel' => in_array($value, $this->value),
                'readonly' => $this->readonly,
            ) );
            $T->parse('opt', 'optionRow', true);
        }
        $T->parse('output', 'template');
        return $T->finish($T->get_var('output'));
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
     * Usage: Administrator editing the field.
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        $T = new \Template(PRF_PI_PATH . '/templates/admin/fields');
        $T->set_file('template', 'multicheck_values.thtml');

        $multichk_input = '';
        $defaults = $this->getOption('default', array());
        if (!is_array($defaults)) $defaults = array();
        $T->set_block('template', 'optionRow', 'opt');
        foreach ($this->options['values'] as $key=>$valdata) {
            $T->set_var(array(
                'opt_sel' => in_array($valdata, $defaults),
                'value' => $valdata,
            ) );
            $T->parse('opt', 'optionRow', true);
        }
        $T->parse('output', 'template');
        $multichk_input = $T->finish($T->get_var('output'));
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
        $v = isset($vals[$this->name]) ? $vals[$this->name] : array();
        return @serialize($v);
    }


    /**
     * Get field-specific options for the user search form
     *
     * @return  string      HTML input options
     */
    public function searchFormOpts()
    {
        global $LANG_PROFILE;

        $opts = $this->getOption('values', array());
        // Search options have multiple checkboxes for this field type
        $T = $this->_getTemplate('search', 'multicheck');
        $T->set_block('template', 'optionRow', 'opt');
        foreach ($opts as $valname) {
            $T->set_var(array(
                'fld_name' => $this->name,
                'opt_value' => htmlentities($valname, ENT_QUOTES),
                'opt_name' => $valname,
            ) );
            $T->parse('opt', 'optionRow', true);
        }
        $T->parse('output', 'template');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Actually gets the options array specific to this field.
     *
     * @param   array   $A      Form values
     * @return  object  $this
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
        return $this;
    }


    /**
     * Get the user search SQL for multicheck fields.
     * `$post` should contain an array of text values.
     *
     * @param   array   $post   Array of values from `$_POST`
     * @param   string  $tbl    Table prefix used in parent search
     * @return  string      SQL query portion
     */
    public function createSearchSQL($post, $tbl='data')
    {
        if (!isset($post[$this->name])) return '';

        if (isset($post['empty'][$this->name])) {
            $sql = "(`{$tbl}`.`{$this->name}` = '' OR
                     `{$tbl}`.`{$this->name}` IS NULL)";
        } else {
            $sql = array();
            foreach ($post[$this->name] as $val) {
                $value = DB_escapeString($val);
                $sql[]  = "`{$tbl}`.`{$this->name}` like '%{$value}%'";
            }
            if (!empty($sql)) {
                $sql = '(' . implode(' AND ', $sql) . ')';
            }
        }
        return $sql;
    }

}
