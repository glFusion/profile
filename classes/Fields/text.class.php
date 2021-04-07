<?php
/**
 * Class to handle text fields in profiles.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.2.3
 * @since       1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile\Fields;

/**
* Class for text input fields.
* @package  profile
*/
class text extends \Profile\Field
{
    /**
     * Provide a form field for editing this data item.
     * This provides a text input field, to be overridden by child classes
     *
     * @return  string  HTML for data entry field
     */
    public function FormField()
    {
        $this->_FormFieldInit();
        $size = $this->getOption('size');
        $maxlength = $this->getOption('maxlength');
        $maxlength = (int)$maxlength > 0 ? "maxlength=\"$maxlength\"" : '';
        $T = $this->_getTemplate();
        $T->set_var(array(
            'classes' => $this->_frmClass,
            'name' => $this->name,
            'maxlen' => $maxlength,
            'size' => $size,
            'type' => 'text',
            'value' => $this->value,
            'readonly' => $this->readonly,
        ) );
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
        $this->options['maxlength'] = min((int)$A['maxlength'], 255);
        $this->options['size'] = min((int)$A['size'], 80);
        return $this;
    }


    /**
     * Get the SQL field type for the "alter" statement
     *
     * @return  string      SQL field definition
     */
    public function getSqlType()
    {
        return 'VARCHAR(255)';
    }

}
