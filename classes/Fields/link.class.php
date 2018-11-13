<?php
/**
 * Class to handle links in profiles.
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
 * Class for link input fields.
 * @package profile
 */
class link extends \Profile\Field
{
    /**
     * Format the value for display.
     *
     * @param   string  $value  Optional value override
     * @return  string          HTML for value display
     */
    public function FormatValue($value = '')
    {
        global $_PRF_CONF, $_CONF;

        // Convert the sql date to a timestamp, then output it according
        // the field'd display format
        if (empty($value))
            $value = $this->value;

        if (empty($value))
            return '';

        return '<a href="' . $value . '" rel="nofollow">' .
            htmlspecialchars($value) . '</a>';
    }


    /**
     * Sanitize the field for storage
     *
     * @param   string  $val    Value to sanitize
     * @return  string          Sanitized version
     */
    public function Sanitize($val)
    {
        $val = parent::Sanitize($val);
        $val = COM_sanitizeURL($val);
        return $val;
    }

}

?>
