<?php
/**
 *   Class to handle account profile items.
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
 * Class for a user account selection.
 * This can be used to link the account to another.
 * @package profile
 */
class account extends \Profile\Field
{
    /**
     * Create the data entry field
     *
     * @uses    AccountSelection();
     * @return  string  HTML for selection dropdown.  Includes <select> tags.
     */
    public function FormField()
    {
        global $LANG_PROFILE;

        $this->_FormFieldInit();

        $fld = "<select $this->_frmClass name=\"{$this->name}\"
                    id=\"{$this->name}\" $this->_frmReadonly>\n";
        $fld .= $this->AccountSelection($this->value);
        //$fld .= "<option value=\"0\">--{$LANG_PROFILE['select']}--</option>\n";
        /*foreach ($this->options['values'] as $id=>$value) {
            $sel = $this->value == $value ? 'selected="selected"' : '';
            $fld .= "<option value=\"$value\" $sel>$value</option>\n";
        }*/
        $fld .= "</select>\n";
        return $fld;
    }


    /**
     * Create an account selection list.
     *
     * @param   integer $selected   Currently-selected value
     * @return  string              HTML for selection options
     */
    public function AccountSelection($selected = 0)
    {
        global $_TABLES;

        $sel = 0 == $selected ? PRF_SELECTED : '';
        $retval = '<option value="0" ' . $sel . '>--None--</option>' . LB;

        $sql = "SELECT uid, username, fullname
                FROM {$_TABLES['users']}
                ORDER BY username ASC";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $sel = $A['uid'] == $selected ? PRF_SELECTED : '';
            $retval .= '<option value="' . $A['uid'] . '" ' . $sel . '>' .
                    $A['username'] . ' - ' . $A['fullname'] . '</option>' . LB;
        }
        return $retval;
    }


    /**
     * Format the value for dispaly.
     *
     * @param   integer $value  Optional value override
     * @return  string          HTML for value display
     */
    public function FormatValue($value = 0)
    {
        global $_CONF, $_TABLES, $LANG_PROFILE;

        if ($value == 0) $value = $this->value;
        if ($value > 0) {
            return COM_createLink(COM_getDisplayName($value) . " ($value)",
                $_CONF['site_url'] . '/users.php?mode=profile&amp;uid=' . $value);
        } else {
            return '';
        }

        /*if ($value == 0) {
            return '';
        }*/

        $linebreak = '';
        if ($value > 0) {
            $parent = COM_createLink(COM_getDisplayName($value) . " ($value)",
                $_CONF['site_url'] . '/users.php?mode=profile&amp;uid=' . $value);
        } else {
            $parent = '';
        }
        $children = array();
        $accounts = $parent;

        $sql = "SELECT puid FROM {$_TABLES['profile_data']}
                WHERE sys_parent = '{$this->uid}'";
        $res = DB_query($sql, 1);
        while ($A = DB_fetchArray($res, false)) {
            $children[] = COM_createLink(COM_getDisplayName($A['puid']) .
                    ' (' . $A['puid'] . ')',
                $_CONF['site_url'].'/users.php?mode=profile&amp;uid='.$A['puid']);
        }
        if (!empty($children)) {
            $children = implode(', ', $children);
            if (!empty($parent)) {
                $accounts .= $linebreak;
            }
            $accounts .= $LANG_PROFILE['child_accounts'] . ': ' . $children;
        }

        return $accounts;
    }


    /**
     * Prepare to save a value to the DB.
     * This type returns the submitted value without modification.
     *
     * @param   array   $vals   Array of all submitted values
     * @return  string          Value to save
     */
    public function prepareToSave($vals)
    {
        return $vals[$this->name];
    }


    /**
     * Get the SQL field type for the "alter" statement.
     *
     * @return  string      SQL field definition
     */
    public function getSqlType()
    {
        return 'MEDIUMINT(8) DEFAULT 0';
    }

}

?>
