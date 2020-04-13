<?php
/**
 * Class to handle dropdown profile items.
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
 * Class for dropdown selections.
 * @package profile
 */
class select extends \Profile\Field
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
        if (!isset($this->options['values'])) {
            $this->options['values'] = array();
        } elseif (!is_array($this->options['values'])) {
            $this->options['values'] = @unserialize($this->options['values']);
            if (!$this->options['values']) $this->options['values'] = array();
        }
    }


    /**
     * Create the data entry field
     *
     * @return  string  HTML for selection dropdown.  Included <select> tags.
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
     * Create the form elements for editing the value selections
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        $listinput = '';
        if (!empty($this->options['values'])) {
            $i = 0;
            foreach ($this->options['values'] as $valname) {
                $listinput .= '<tr><td><input type="text" value="' . $valname . '" name="select_values[]" /></td>';
                $sel = $this->getOption('default') == $valname ? PRF_CHECKED : '';
                $listinput .= '<td><input type="radio" name="sel_default" value="' . $i . '" ' . $sel . '/></td>';
                $listinput .= '<td>' . $this->getRemoveRowIcon() . '</td>';
                $listinput .= "</tr>\n";
                $i++;
            }
        } else {
            $this->options['values'] = array();
            $listinput .= '<tr><td><input type="text" value="" name="select_values[]" /></td>' . "\n";
                $listinput .= '<td><input type="radio" name="sel_default" value="0" ' . $sel . '/></td>';
            $listinput .= '<td>' . $this->getRemoveRowIcon() . '</td>';
            $listinput .= "</tr>\n";
        }
        return array('list_input'=>$listinput);
    }


    /**
     * Prepare to save a value to the DB.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  string          Value to save
     */
    public function prepareToSave($vals)
    {
        $name = $this->name;
        if (!isset($vals[$name]) && empty($this->value) &&
                isset($data->options['values']['default'])) {
            $vals[$name] = $data->options['values']['default'];
        }
        return $vals[$name];
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
                '[]" value="' . htmlentities($valname, ENT_QUOTES) .
                '" />'.$valname.'&nbsp;';
        }
        return $fld;

        /*$fld = '';
        $opts = $this->getOption('values', array());
        foreach ($opts as $valname) {
            $fld .= '<input type="radio" name="'.$this->name .
                    '" value="'.htmlentities($valname, ENT_QUOTES) .
                    '" />'.$valname.'&nbsp;';
        }
        $fld .= '<input type="radio" name="'.$this->name .
                '" value="-1" ' . PRF_CHECKED . '/>' . $LANG_PROFILE['any'];
        return $fld;*/
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
                $sql = '(' . implode(' OR ', $sql) . ')';
            }
        }
        return $sql;
    }

}

?>
