<?php
/**
 * Class to handle date fields in profiles.
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
 * Class for date fields
 * @package profile
 */
class date extends \Profile\Field
{
    /**
     * Get the value formatted for display, depending on the field's format.
     *
     * @param   string  $value  Value to display, current value by default
     * @return  string  HTML for formatted field value
     */
    public function FormatValue($value = '')
    {
        global $_PRF_CONF, $_CONF;

        // Convert the sql date to a timestamp, then output it according
        // the field'd display format
        if (empty($value))
            $value = $this->value;

        if (empty($value) || $value == '0000-00-00')
            return '';

        // Explode parts from 'YYYY-MM-DD hh:mm:ss' format
        $dt_tm = explode(' ', $value);
        list($year, $month, $day) = explode('-', $dt_tm[0]);
        if (isset($dt_tm[1])) {
            list($hour, $minute, $second) = explode(':', $dt_tm[1]);
        } else {
            $hour = '00';
            $minute = '00';
            $second = '00';
        }

        if (!isset($this->options['input_format'])) {
            $this->options['input_format'] = 0;
        }
        switch ($this->options['input_format']) {
        case '2':
            $formatted = sprintf('%02d/%02d/%04d', $day, $month, $year);
            break;
        case '1':
            $formatted = sprintf('%02d/%02d/%04d', $month, $day, $year);
            break;
        default:
            // use sql date only: YYYY-MM-DD
            $formatted = $dt_tm[0];
            break;
        }

        $format = $this->getOption('timeformat', '24') == '12' ? '%I:%M %p' : '%H:%M';
        $formatted .= ' ' . strftime($format, mktime($hour, $minute, $second));
        /*if ($this->options['showtime'] == 1) {
            if ($_CONF['hour_mode'] == 12) {
                //$format = ' %I:%M:%S %p';
                $format = ' %I:%M %p';
            } else {
                //$format = ' %H:%M:%S';
                $format = ' %H:%S';
            }
            $ts = mktime($hour, $minute, $second);
            $formatted .= strftime($format, $ts);
        }*/

        return $formatted;
    }


    /**
     * Create the form entry field.
     *
     * @param   boolean $incl_time  True to include time in entry field
     * @return  string      HTML for month, day and year fields.
     */
    public function FormField($incl_time = true)
    {
        global $LANG_PROFILE, $_CONF, $_PRF_CONF;

        $this->_FormFieldInit();

        $timeformat = $this->getOption('timeformat');
        if ($timeformat && $incl_time) {
            $iFormat = '%Y-%m-%d %H:%M';
            if (strpos($this->value, ' ')) {
                list($date, $time) = explode(' ', $this->value);
            } else {
                $date = $this->value;
                $time = NULL;
            }
            if ($time == NULL) $time = '00:00:00';
            $dt = explode('-', $date);
            $tm = explode(':', $time);
            if (isset($_POST)) {
                if (isset($_POST[$this->name . '_hour']))
                    $tm[0] = $_POST[$this->name . '_hour'];
                if (isset($_POST[$this->name . '_minute']))
                    $tm[1] = $_POST[$this->name . '_minute'];
            }

            if ($this->options['timeformat'] == '12') {
                list($hour, $ampm_sel) = $this->hour24to12($tm[0]);
                $ampm_fld = '&nbsp;&nbsp;' .
                    self::ampmSelection($this->name . '_ampm', $ampm_sel);
            } else {
                $ampm_fld = '';
                $hour = $tm[0];
            }

            $hr_fld = '<select id="' . $this->name . '_hour" ' .
                    'name="' . $this->name . '_hour" ' .
                    $this->_frmReadOnly . '>' . LB .
                COM_getHourFormOptions($hour, $this->options['timeformat']) .
                '</select>' . LB;
            $min_fld = '<select id="' . $this->name . '_minute" ' .
                    'name="' . $this->name . '_minute" ' .
                    $this->_frmReadOnly . '>' . LB .
                COM_getMinuteFormOptions($tm[1]) .
                '</select>' . LB;
        } else {
            $iFormat = '%Y-%m-%d';
            $dt = explode('-', $this->value);
            $tm = NULL;
            $hr_fld = '';
            $min_fld = '';
            $ampm_fld = '';
        }

        if (isset($_POST)) {
            // Get the values from $_POST, if set.
            if (isset($_POST[$this->name . '_month']))
                $dt[1] = $_POST[$this->name . '_month'];
            if (isset($_POST[$this->name . '_day']))
                $dt[2] = $_POST[$this->name . '_day'];
            if (isset($_POST[$this->name . '_year']))
                $dt[0] = $_POST[$this->name . '_year'];
        }

        $m_fld = "<select id=\"{$this->name}_month\" name=\"{$this->name}_month\" {$this->_frmReadonly}>\n";
        $m_fld .= "<option value=\"0\">--{$LANG_PROFILE['month']}--</option>\n";
        $sel = isset($dt[1]) ? (int)$dt[1] : '';
        $m_fld .= COM_getMonthFormOptions($sel) . "</select>\n";

        $d_fld = "<select id=\"{$this->name}_day\" name=\"{$this->name}_day\" {$this->_frmReadonly}>\n";
        $d_fld .= "<option value=\"0\">--{$LANG_PROFILE['day']}--</option>\n";
        $sel = isset($dt[2]) ? (int)$dt[2] : '';
        $d_fld .= COM_getDayFormOptions($sel) . "</select>\n";

        $y_fld = $LANG_PROFILE['year'] .
            ': <input type="text" id="' . $this->name . '_year" name="' .
            $this->name . '_year" size="5" value="' . $dt[0] . '" ' .
            $this->_frmReadonly . '/>' . LB;

        // Hidden field to hold the date in its native format
        // Required for the date picker
        $datepick = '<input type="hidden" name="f_' . $this->name .
                '" id="f_' . $this->name . '" value="' . $this->value .
                '"/>' . LB;

        if (!$this->readonly) {
            // If not a readonly field, add the date picker image & js
            $datepick .= '<i class="uk-icon uk-icon--calendar tooltip" title="' .
                    $LANG_PROFILE['select_date'] . '" id="' .
                    $this->name . '_trigger"></i>';
            $timeformat = $this->getOption('timeformat', 0);
            if ($timeformat && $incl_time) {
                $showtime = 'true';
            } else {
                $showtime = 'false';
                $timeformat = 0;
            }
            $datepick .= LB . "<script type=\"text/javascript\">
Calendar.setup({
inputField :   \"f_{$this->name}\",
ifFormat   :   \"$iFormat\",
showsTime  :    $showtime,
timeFormat :    \"{$timeformat}\",
button     :   \"{$this->name}_trigger\",
onUpdate   :   {$this->name}_onUpdate
});
function {$this->name}_onUpdate(cal)
{
    var d = cal.date;

    if (cal.dateClicked && d) {
        PRF_updateDate(d, \"{$this->name}\", \"$timeformat\");
    }
    return true;
}
</script>" . LB;

        }

        // Place the date components according to m/d/y or d/m/y format
        if (!isset($this->options['input_format'])) {
            $this->options['input_format'] = 0;
        }
        switch ($this->options['input_format']) {
        case 2:
            $fld = $d_fld . ' ' . $m_fld . ' ' . $y_fld;
            break;
        case 1:
        default:
            $fld = $m_fld . ' ' . $d_fld . ' ' . $y_fld;
            break;
        }

        if ($timeformat && $incl_time) {
            $fld .= '&nbsp;&nbsp;' . $hr_fld . ':' . $min_fld . $ampm_fld;
        }

        $fld .= $datepick;
        return $fld;
    }


    /**
     * Get the available date formats
     *
     * @return  array   Array of date formats
     */
    public static function DateFormats()
    {
        global $LANG_PROFILE;
        $_formats = array(
            1 => $LANG_PROFILE['month'].' '.$LANG_PROFILE['day'].' '.$LANG_PROFILE['year'],
            2 => $LANG_PROFILE['day'].' '.$LANG_PROFILE['month'].' '.$LANG_PROFILE['year'],
        );
        return $_formats;
    }


    /**
     * Create the date format selector for use in defining the field.
     * Doesn't include the <select></select> tags, just the options.
     *
     * @param   integer $cur    Currently-selected format
     * @return  string          HTML for date format seleection options.
     */
    public static function DateFormatSelect($cur=0)
    {
        $retval = '';
        $_formats = self::DateFormats();
        foreach ($_formats as $key => $string) {
            $sel = $cur == $key ? PRF_SELECTED : '';
            $retval .= "<option value=\"$key\" $sel>$string</option>\n";
        }
        return $retval;
    }


    /**
     * Convert an hour from 24-hour to 12-hour format for display.
     *
     * @param   integer $hour   Hour to convert
     * @return  array       array(new_hour, ampm_indicator)
     */
    public function hour24to12($hour)
    {
        if ($hour >= 12) {
            $ampm = 'pm';
            if ($hour > 12) $hour -= 12;
        } else {
            $ampm = 'am';
            if ($hour == 0) $hour = 12;
        }
        return array($hour, $ampm);
    }


    /**
     * Convert an hour from 12-hour to 24-hour format.
     *
     * @param   integer $hour   Hour to convert
     * @param   boolean $pm     True if 'pm' is set
     * @return  integer     New hour
     */
    public function hour12to24($hour, $pm)
    {
        if ($pm) {
            if ($hour < 12) $hour += 12;
        } else {
            if ($hour == 12) $hour = 0;
        }
        return $hour;
    }


    /**
     * Get the AM/PM selection.
     * This is exactly like COM_getAmPmFormSelection(), but
     * adds the "id" to the selection so javascript can update it.
     *
     * @param   string  $name       Field name
     * @param   string  $selected   Which option is selected, am or pm?
     * @return  string      HTML for selection
     */
    private function ampmSelection($name, $selected)
    {
        global $_CONF;

        if (isset($_CONF['hour_mode'] ) && ( $_CONF['hour_mode'] == 24 )) {
            $retval = '';
        } else {
            $retval = '<select id="' . $name . '" name="' . $name . '">' .LB;
            $retval .= '<option value="0" ';
            if ($selected == '0' || $selected = 'am') {
                $retval .= PRF_SELECTED;
            }
            $retval .= '>am</option>' . LB . '<option value="1" ';
            if ($selected == '1' || $selected = 'pm') {
                $retval .= PRF_SELECTED;
            }
            $retval .= '>pm</option>' . LB . '</select>' . LB;
        }

        return $retval;
    }


    /**
     * Create the form elements for editing the value selections
     *
     * @return  array   Array of name=>value pairs for Template::set_var()
     */
    public function editValues()
    {
        $opts = array(
            'shtime_chk' => $this->getOption('showtime', 0) == 1 ? PRF_CHECKED : '',
        );
        switch ($this->getOption('timeformat')) {
        case '24':
            $opts['24h_sel'] = PRF_CHECKED;
            break;
        case '12':
            $opts['12h_sel'] = PRF_CHECKED;
            break;
        default:
            $opts['tm_none_sel'] = PRF_CHECKED;
            break;
        }
        return $opts;
    }


    /**
     * Check if a field has valid data.
     * Used in conjuction with the "required" flag.
     *
     * @param   array   $vals   Array of values
     * @return  boolean     True if data is valid, False if not
     */
    public function validData($vals = NULL)
    {
        // This function uses the field name and the $vals array to construct
        // month, day and year values. $value is unused

        if ($this->required) {
            if ($vals === NULL) {   // checking current value
                $parts = explode(' ', $this->value);
                $dt = explode('-', $parts[0]);
                $year = $dt[0];
                $month = isset($dt[1]) ? $dt[1] : 0;
                $day = isset($dt[2]) ? $dt[2] : 0;
            } elseif (is_array($vals)) {
                $month = isset($vals[$this->name . '_month']) ?
                    (int)$vals[$this->name . '_month'] : 0;
                $day = isset($vals[$this->name . '_day']) ?
                    (int)$vals[$this->name . '_day'] : 0;
                $year = isset($vals[$this->name . '_year']) ?
                    (int)$vals[$this->name . '_year'] : 0;
            } else {
                return false;
            }
            return checkdate($month, $day, $year);
        }
        return true;    // a value is not required
    }


    /**
     * Prepare to save a value to the DB.
     * This type uses the submitted values array to assemble the date.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  null            Null value
     */
    public function prepareToSave($vals)
    {
        $name = $this->name;
        $def_value = empty($this->value) ? $this->getOption('default') :
                        $this->value;
        if (strpos($def_value, ' ')) {
            list($dt, $tm) = explode(' ', $def_value);
        } else {
            $dt = $def_value;
            $tm = NULL;
        }
        if (empty($tm)) {
            $tm = '00:00:00';
        }
        $time = '';
        $date = '';
        if (isset($vals[$name . '_ampm'])) {
            $vals[$name . '_hour'] = $this->hour12to24(
                $vals[$name . '_hour'],
                $vals[$name . '_ampm']
            );
        }
        foreach (array('_hour', '_minute') as $fld) {
            // Absense of time value is actually ok, just set to midnight
            if (!isset($vals[$name . $fld])) {
                $time = $tm;
                break;
            }
        }
        if (empty($time)) {
            $time = sprintf(
                '%02d:%02d:%02d',
                (int)$vals[$name . '_hour'],
                (int)$vals[$name . '_minute'] ,
                '00'
            );
        }
        if (!isset($vals[$name.'_year']) ||
            !isset($vals[$name.'_year']) ||
            !isset($vals[$name.'_year'])
        ) {
            $date = $dt;
        } else {
            $year = (int)$vals[$name . '_year'];
            $month = (int)$vals[$name . '_month'];
            $day = (int)$vals[$name . '_day'];
            $date = sprintf('%04d-%02d-%02d ', $year, $month, $day);
        }
        $newval = $date . ' ' . $time;
        return $newval;
    }


     /**
     * Get field-specific options for the user search form
     *
     * @return  string      HTML input options
     */
    public function searchFormOpts()
    {
        $fld = '';
        $fld .= '<input type="radio" name="'.$this->name.'_mod" value="=">=&nbsp;&nbsp;';
        $fld .= '<input type="radio" name="'.$this->name.'_mod" value="<"><&nbsp;&nbsp;';
        $fld .= '<input type="radio" name="'.$this->name.'_mod" value="<="><=&nbsp;&nbsp;';
        $fld .= '<input type="radio" name="'.$this->name.'_mod" value=">">>&nbsp;&nbsp;';
        $fld .= '<input type="radio" name="'.$this->name.'_mod" value=">=">>=&nbsp;&nbsp;';
        $fld .= '<input type="radio" name="'.$this->name.'_mod" value="<>"><>&nbsp;';
        $fld .= $this->FormField(false);
        return $fld;
    }


    /**
     * Create the search sql for this field
     *
     * @param   array   $post   Values from $_POST
     * @param   string  $tbl    Table indicator
     * @return  string          SQL query fragment
     */
    public function createSearchSQL($post, $tbl='data')
    {
        if (isset($post['empty'][$this->name])) {
            $fld = "(`{$tbl}`.`{$this->name}` = '' OR
                    `{$tbl}`.`{$this->name}` IS NULL OR
                    `{$tbl}`.`{$this->name}` LIKE '0000-00-00%')";
        } else {
            $mods = array('<', '<=', '>', '>=', '=', '<>');
            $value = sprintf('%04d-%02d-%02d', $post[$this->name . '_year'],
                    $post[$this->name . '_month'], $post[$this->name . '_day']);
            if ($value == '0000-00-00') return '';
            $value = DB_escapeString($value);
            if (!isset($post[$this->name . '_mod'])) {
                $fld = "`{$tbl}`.`{$this->name}` LIKE '%$value%'";
            } else {
                $mod = in_array($post[$this->name . '_mod'], $mods) ? $post[$this->name . '_mod'] : '=';
                $fld = "`{$tbl}`.`{$this->name}` $mod '$value'";
            }
        }
    }


    /**
     * Actually gets the options array specific to this field.
     *
     * @param   array   $A      Form values
     * @return  object  $this
     */
    public function setOptions($A)
    {
        global $_PRF_CONF;

        switch ($A['timeformat']) {
        case '12':
        case '24':
            $this->options['timeformat'] = $A['timeformat'];
            break;
        default:
            // empty timeformat = date only
            $this->options['timeformat'] = '';
            break;
        }
        $this->options['format'] = isset($A['format']) ? $A['format'] :
                $_PRF_CONF['def_dt_format'];
        $this->options['input_format'] = isset($A['dt_input_format']) ?
                $A['dt_input_format'] : $_PRF_CONF['date_format'];
        return $this;
    }


    /**
     * Get the SQL field type for the "alter" statement
     *
     * @return  string      SQL field definition
     */
    public function getSqlType()
    {
        return $this->options['timeformat'] !== '' ? 'DATETIME' : 'DATE';
    }

}
