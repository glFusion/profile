<?php
/**
 * Class to handle checkbox profile items.
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
 * Class for Checkbox items.
 * @package profile
 */
class checkbox extends \Profile\Field
{
    /**
     * Constructor.
     *
     * @param   mixed   $item   Name of item, or array of info
     * @param   mixed   $value  Optional value to assign
     * @param   integer $uid    Optional user ID, current user by default
     */
    public function __construct($item=NULL, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
    }


    /**
     * Return the formatted string value.
     *
     * @param   integer $value  Optional value.  "1" = on, "0" = off
     * @return  string          String corresponding to value.
     */
    public function FormatValue($value = 0)
    {
        global $LANG_PROFILE;

        if (empty($value)) {
            $value = $this->value;
        }
        $formatted = $value == 1 ? $LANG_PROFILE['yes'] : $LANG_PROFILE['no'];
        return $formatted;
    }


    /**
     * Create the data entry field
     *
     * @return  string  HTML for checkbox field, with prompt string
     */
    public function FormField()
    {
        $this->_FormFieldInit();

        if (is_null($this->value)) {
            $chk = ($this->getOption('default', 0) == 1);
        } else {
            $chk = $this->value == 1;
        }
        $T = $this->_getTemplate();
        $T->set_var(array(
            'classes' => $this->_frmClass,
            'name' => $this->name,
            'id' => $this->name,
            'checked' => $chk,
            'readonly' => $this->_frmReadonly,
        ) );
        $T->parse('output', 'template');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Sanitize this field for storage
     *
     * @param   integer $val    Starting value
     * @return  integer         Sanitized value, either 1 or 0
     */
    public function Sanitize($val)
    {
        $val = $val == 1 ? 1 : 0;
        return $val;
    }


    /**
     * Special handling for "required" setting for checkboxes.
     */
    protected function X_FormFieldInit()
    {
        return "DEPRECATED";
        parent::_FormFieldInit();
        $this->_frmClass = '';
    }


    /**
     * Create the form elements for editing the value selections.
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        return array(
            'chkdefault_chk' =>
                $this->getOption('default') == 1 ? PRF_CHECKED : '',
        );
    }


    /**
     * Check if a field has valid data.  Used in conjuction with the "required" flag.
     *
     * @param   array   $vals   Values submitted from form
     * @return  booldan         True if data is valid, False if not
     */
    public function validData($vals = NULL)
    {
        if ($vals === NULL) {   // check current value
            $val = $this->value;
        } elseif (is_array($vals) && isset($vals[$this->name])) {
            $val = $vals[$this->name] !== NULL ? (int)$vals[$this->name] : $this->value;
        } else {
            $val = '';
        }
        if ($this->required && $val != 1) {
           return false;
        } else {
            return true;
        }
    }


    /**
     * Prepare to save a value to the DB.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  string          Value to save
     */
    public function prepareToSave($vals)
    {
        return isset($vals[$this->name]) && $vals[$this->name] == '1' ? '1' : '0';
    }


    /**
     * Get field-specific options for the user search form
     *
     * @return  string      HTML input options
     */
    public function searchFormOpts()
    {
        global $LANG_PROFILE;

        $T = $this->_getTemplate('search', 'radio');
        $T->set_block('template', 'optionRow', 'opt');
        foreach (array($LANG_PROFILE['unchecked'], $LANG_PROFILE['checked']) as $val=>$dscp) {
            $T->set_var(array(
                'fld_name' => $this->name,
                'opt_value' => $val,
                'opt_name' => $dscp,
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
     * @return  array           Array of options to save
     */
    public function setOptions($A)
    {
        $this->options['value'] = 1;
        $this->options['default'] = isset($A['chkdefvalue']) ? 1 : 0;
        return $this;
    }


    /**
     * Get the SQL field type for the "alter" statement
     *
     * @return  string      SQL field definition
     */
    public function getSqlType() : string
    {
        return 'TINYINT(1) UNSIGNED NOT NULL DEFAULT ' . (int)$this->getOption('default', 0);
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
    public function createSearchSQL(array $post, string $tbl='data') : string
    {
        if (!isset($post[$this->name])) return '';

        $sql = '';
        if (isset($post['empty'][$this->name])) {
            $sql = "(`{$tbl}`.`{$this->name}` = '' OR
                     `{$tbl}`.`{$this->name}` IS NULL)";
        } elseif (isset($post[$this->name]) && is_array($post[$this->name])) {
            $value = DB_escapeString($post[$this->name][0]);
            $sql = "`{$tbl}`.`{$this->name}` like '%{$value}%'";
        }
        return $sql;
    }

}

