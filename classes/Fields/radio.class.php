<?php
/**
 * Class to handle radio-button profile items.
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
namespace Profile\Fields;

/**
 * Class for radio button fields.
 * Radio buttons are essentially the same as Select options
 * except for the rendered field.
 * @package profile
 */
class radio extends select
{
    /**
     * Constructor.
     * Set up the array of value options
     *
     * @param   mixed   $item   Name of item, or array of info
     * @param   string  $value  Optional value to assign, serialized array
     * @param   integer $uid    Optional user ID, current user by default
     */
    public function XX__construct($item=NULL, $value='', $uid = '')
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
     * Create the data entry field.
     * Creates radio buttons in a line.
     *
     * @return  string  HTML for radio buttons & prompts
     */
    public function FormField()
    {
        $this->_FormFieldInit();

        if (empty($this->getOption('values'))) {
            // Have to have some values for radio buttons
            return '';
        }

        // If no current value, use the defined default
        if (is_null($this->value)) {
            $this->value = $this->getOption('default');
        }

        $fld = '';
        foreach ($this->options['values'] as $id=>$value) {
            $sel = $this->value == $value ? PRF_CHECKED : '';
            $fld .= "<input $this->_frmClass type=\"radio\"
                name=\"{$this->name}\"
                id=\"{$this->name}_{$value}\"
                value=\"" . htmlentities($value, ENT_QUOTES) .
                "\" $sel {$this->_frmReadonly}/>$value&nbsp;\n";
        }
        return $fld;
    }


    /**
     * Create the form elements for editing the value selections
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function XeditValues()
    {
        $listinput = '';
        $i = 0;
        if (!empty($this->options['values'])) {
            foreach ($this->options['values'] as $valname) {
                $listinput .= '<tr><td><input type="text" value="' . $valname . '" name="select_values[]" /></td>';
                $sel = $this->getOption('default') == $valname ?
                        PRF_CHECKED : '';
                $listinput .= '<td><input type="radio" name="sel_default" value="' . $i . '" ' . $sel . '/></td>';
                $listinput .= '<td>' . $this->getRemoveRowIcon() . '</td>';
                $listinput .= "</tr>\n";
                $i++;
            }
        } else {
            $thie->options['values'] = array();
            $listinput = '<tr><td><input type="text" value="" name="select_values[]" /></td>';
            $listinput .= '<td><input type="radio" name="sel_default" value="0" ' . PRF_CHECKED . '/></td>';
            $listinput .= '<td>' . $this->getRemoveRowIcon() . '</td>';
            $listinput .= "</tr>\n";
        }
        return array('list_input'=>$listinput);
    }


    /**
     * Prepare to save a value to the DB
     *
     * @param   array   $vals   Array of all submitted values
     * @return  mixed           String to be saved for this item
     */
    public function XprepareToSave($vals)
    {
        if (!isset($vals[$this->name])) {
            if (empty($this->value)) {
                $vals[$this->name] = $this->getOption('default');
            } else {
                $vals[$this->name] = $this->value;
            }
        }
        return $vals[$this->name];
    }


    /**
     * Get field-specific options for the user search form
     *
     * @return  string      HTML input options
     */
    public function XsearchFormOpts()
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
     * Actually gets the options array specific to this field.
     *
     * @param  array   $A      Form values
     * @return array           Array of options to save
     */
    public function XsetOptions($A)
    {
        $newvals = array();
        if (!isset($A['select_values']) || !is_array($A['select_values'])) {
            $A['select_values'] = array();
        }
        foreach ($A['select_values'] as $val) {
            if (!empty($val)) {
                $newvals[] = $val;
            }
        }
        $def_idx = PRF_getVar($A, 'sel_default', 'integer', 0);
        $this->options['default'] = PRF_getVar($newvals, $def_idx);
        $this->options['values'] = $newvals;
        return $this->options;
    }

}

?>
