<?php
/**
 * Class to handle radio-button profile items.
 * Extends the select field type since it shares many characteristics.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2021 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.3
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

        $T = $this->_getTemplate();
        $T->set_block('template', 'optionRow', 'opt');
        foreach ($this->options['values'] as $id=>$value) {
            $T->set_var(array(
                'classes' => $this->_frmClass,
                'name' => $this->name,
                'value' => htmlentities($value, ENT_QUOTES),
                'selected' => $this->value == $value,
            ) );
            $T->parse('opt', 'optionRow', true);
        }
        $T->parse('output', 'template');
        return $T->finish($T->get_var('output'));
    }

}
