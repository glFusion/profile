<?php
/**
*   Class to handle text fields in profiles
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
*   Class for text input fields
*   The base class has all the functionality for text fields, this is a stub.
*   @package    profile
*/
class prfItem_text extends prfItem
{
    /**
    *   Provide a form field for editing this data item.
    *   This provides a text input field, to be overridden by child classes
    *
    *   @return string  HTML for data entry field
    */
    public function FormField()
    {
        $this->_FormFieldInit();
        $size = $this->getOption('size');
        $maxlength = $this->getOption('maxlength');
        $maxlength = (int)$maxlength > 0 ? "maxlength=\"$maxlength\"" : '';
        $fld = "<input $this->_frmClass name=\"{$this->name}\"
                    id=\"{$this->name}\" $maxlength
                    size=\"$size\"
                    type=\"text\" value=\"{$this->value}\" $this->_frmReadonly>\n";

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
        $this->options['maxlength'] = min((int)$A['maxlength'], 255);
        $this->options['size'] = min((int)$A['size'], 80);
        return $this->options;
    }


    /**
    *   Get the SQL field type for the "alter" statement
    *
    *   @return string      SQL field definition
    */
    public function getSqlType()
    {
        return 'VARCHAR(255)';
    }

}

?>
