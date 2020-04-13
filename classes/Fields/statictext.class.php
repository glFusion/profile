<?php
/**
 * Class to handle static profile items.
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
 * Class for static text fields.
 * @package     profile
 */
class statictext extends \Profile\Field
{
    /**
     * Call the parent constructor and initialize values for this object.
     *
     * @param   mixed   $item   Name of item, or array of info
     * @param   mixed   $value  Optional value to assign
     * @param   integer $uid    Optional user ID
     */
    public function __construct($item=NULL, $value='', $uid='')
    {
        parent::__construct($item, $value, $uid);
        $this->value = $this->getOption('value');
    }


    /**
     * Returns the static text
     *
     * @return  string  HTML for data entry field
     */
    public function FormField()
    {
        return $this->options['value'];
    }


    /**
     * Create the form elements for editing the value selections.
     * Returns the default value for single-value fields (text, textarea, etc)
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        return array('valuestr' => $this->options['value']);
    }


    /**
     * Prepare to save a value to the DB.
     * Static fields do not get saved, so return NULL as an indicator.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  null            Null value
     */
    public function prepareToSave($vals)
    {
        return NULL;
    }


    /**
     * Actually gets the options array specific to this field.
     *
     * @param   array   $A      Form values
     * @return  object  $this
     */
    public function setOptions($A)
    {
        $this->options['value'] = trim($A['static_val']);
        return $this;
    }


    /**
     * Get the field-modification part of the SQL statement, if any.
     * This field type is dropped if changing from another type to static,
     * otherwise no SQL is needed here.
     *
     * @param   array   $A      Array of form fields.
     * @return  string          SQL statement fragment.
     */
    private function getDataSql($A)
    {
        $sql = '';
        if ($A['oldtype'] != 'statictext') {
            // If we're changing the table type to static, we need to drop the
            // old column.  Otherwise, there's nothing to do for a static field.
            $sql = 'DROP `' . DB_escapeString($A['oldname']) . '`';
        }
        return $sql;
    }

}

?>
