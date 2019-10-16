<?php
/**
 * Class to search for users based on custom profile data.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @since       v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;


/**
 * Class to search for users based on custom profile data.
 * @package profile
 */
class Search
{
    /**
     * Determine what to display in the admin list for each field.
     *
     * @param   string  $fieldname  Name of the field, from database
     * @param   mixed   $fieldvalue Value of the current field
     * @param   array   $A          Array of all name/field pairs
     * @param   array   $icon_arr   Array of system icons
     * @return  string              HTML for the field cell
     */
    public static function getField($fieldname, $fieldvalue, $A, $icon_arr)
    {
        global $_CONF, $LANG_ACCESS, $_PRF_CONF, $LANG_ADMIN, $LANG_PROFILE;

        $retval = '';

        switch($fieldname) {
        case 'username':
            $retval = COM_createLink(
                $fieldvalue,
                $_CONF['site_url'] . '/users.php?mode=profile&amp;uid=' . $A['uid']
            );
            break;

        case 'edituser':
            $retval = COM_createLink(
                '<i class="uk-icon uk-icon-edit" data-uk-tooltip title="' . $LANG_ADMIN['edit'] . '"></i>',
                "{$_CONF['site_url']}/admin/user.php?edit=x&amp;uid={$A['uid']}"
            );
            break;

        default:
            $retval = $fieldvalue;
        }

        return $retval;
    }

    
    /**
     * Create a form to collect search parameters for users.
     *
     * @since   version 1.1.0
     * @return  string      HTML for form
     */
    public static function searchUsersForm()
    {
        global $_TABLES, $LANG_PROFILE;

        $T = new \Template(PRF_PI_PATH . 'templates/admin');
        $T->set_file('searchform', 'search.thtml');

        $sql = "SELECT *
            FROM {$_TABLES['profile_def']}
            WHERE enabled = 1
            AND type <> 'static' " .
            COM_getPermSQL('AND', 0, 2) .
            ' ORDER BY orderby ASC';
        $res = DB_query($sql);
        while ($A = DB_fetchArray($res, false)) {
            $F = Field::getInstance($A);
            $T->set_block('searchform', 'FldRow', 'frow');
            $T->set_var(array(
                'fld_prompt'    => $F->prompt,
                'fld_name'      => $F->name,
                'fld_input'     => $F->searchFormOpts(),
                'fld_empty'     => true,
            ) );
            $T->parse('frow', 'FldRow', true);
        }

        $T->parse('output', 'searchform');
        return $T->finish($T->get_var('output'));
    }


    /**
     * Search for users based on the parameters entered in PRF_searchUserForm().
     *
     * @since   version 1.1.0
     * @param   array   $vals   Search values (e.g. $_POST)
     * @return  string          HTML  for user list
     */
    public static function searchUsers($vals)
    {
        global $_TABLES, $LANG_ADMIN, $LANG_PROFILE;

        $sql = "SELECT * FROM {$_TABLES['profile_def']}
            WHERE enabled=1 " . COM_getPermSQL('AND', 0, 2);
        $res = DB_query($sql);
        $fields = array();
        while ($A = DB_fetchArray($res, false)) {
            if ($A['type'] != 'static') {
                $fields[$A['name']] = $A;
                $sql_flds[] = '`data`.`' . $A['name'] . '`';
            }
        }
        $sql_fldnames = implode(',', $sql_flds);

        $flds = array();
        foreach ($fields as $f_name=>$fld) {
            if (!isset($_POST[$f_name]) || $_POST[$f_name] == '-1') {
                continue;   // signifies "any"
            }
            $F = Field::getInstance($fld);
            $f_name = DB_escapeString($f_name);
            $x = $F->createSearchSQL($_POST);
            if (!empty($x)) {
                $flds[] = $x;
            }
        }

        if (is_array($flds) && !empty($flds)) {
            $fld_sql = implode(' OR ', $flds);
        } else {
            $fld_sql = ' 1 = 1 ';
        }

        $sql = "SELECT u.*, $sql_fldnames
            FROM {$_TABLES['users']} u
            LEFT JOIN {$_TABLES['profile_data']} data
            ON u.uid = data.puid
            WHERE ($fld_sql)";
        //echo $sql;die;
        $res = DB_query($sql);
        if (!$res || DB_error()) {
            return COM_showMessageText($LANG_PROFILE['query_error']);
        }

        $retval = '';
        $header_arr = array(
            array(
                'text' => $LANG_ADMIN['edit'],
                'field' => 'edituser',
                'sort' => false,
            ),
            array(
                'text' => 'Username',
                'field' => 'username',
            ),
            array(
                'text' => 'Fullname',
                'field' => 'fullname',
                'sort' => true,
            ),
        );

        $data_arr = array();
        $text_arr = array();

        while ($A = DB_fetchArray($res, false)) {
            $data_arr[] = $A;
        }
        $retval = ADMIN_simpleList(
            array(__CLASS__, 'getField'),
            $header_arr, $text_arr,
            $data_arr, '', '', $extra 
        );
        return $retval;
    }

}

?>
