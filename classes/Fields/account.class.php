<?php
/**
 * Class to handle account profile items.
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
 * Class for a user account selection.
 * This can be used to link the account to another.
 * @package profile
 */
class account extends \Profile\Field
{
    /**
     * Create the data entry field
     *
     * @return  string  HTML for selection dropdown.  Includes <select> tags.
     */
    public function FormField()
    {
        global $LANG_PROFILE, $_TABLES;

        $this->_FormFieldInit();
        // Borrow the "select" field type template
        $T = $this->_getTemplate('field', 'select');
        $T->set_var(array(
            'classes' => $this->_frmClass,
            'name' => $this->name,
            'readonly' => $this->readonly,
        ) );
        $T->set_block('template', 'optionRow', 'opt');
        $T->set_var(array(
            'opt_value' => 0,
            'opt_name' => '-- ' . $LANG_PROFILE['none'] . ' --',
            'opt_sel' => 0 == $this->value,
        ) );
        $T->parse('opt', 'optionRow', true);

        $sql = "SELECT uid, username, fullname
                FROM {$_TABLES['users']}
                ORDER BY username ASC";
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $T->set_var(array(
                'opt_value' => $A['uid'],
                'opt_name' => $A['username'] . ' - ' . $A['fullname'],
                'opt_sel' => $A['uid'] == $this->value,
            ) );
            $T->parse('opt', 'optionRow', true);
        }
        $T->parse('output', 'template');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Create an account selection list.
     *
     * @param   integer $selected   Currently-selected value
     * @return  string              HTML for selection options
     */
    public function XAccountSelection($selected = 0)
    {
        global $_TABLES;
        return "DEPRECATED";

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
     * Get the SQL field type for the "alter" statement.
     *
     * @return  string      SQL field definition
     */
    public function getSqlType() : string
    {
        return 'MEDIUMINT(8) DEFAULT 0';
    }


    /**
     * Sanitize the value.
     * This field type just returns an integer.
     *
     * @param   mixed   $val    Current field value
     * @return  integer     Sanitized value.
     */
    public function Sanitize($val)
    {
        return (int)$val;
    }

}
