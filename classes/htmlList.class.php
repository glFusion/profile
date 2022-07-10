<?php
/**
 * Create HTML lists for export.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2016-2018 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;

/**
 * Class for creating a HTML uesr list
 * @package     profile
 * @since       1.1.4
 */
class htmlList extends UserList
{
    /**
     * Constructor. Simply calls the parent constructor.
     *
     * @param   string  $listid     Optional list ID to load
     */
    public function __construct($listid='')
    {
        parent::__construct($listid);
    }


    /**
     * Create the report.
     *
     * @param   $filename   Filename to save to disk, empty to show in browser
     */
    public function Render($filename = '')
    {
        global $_CONF, $_TABLES;

        if ($this->listid == '') {
            if (!$this->getFirst()) return '';
        }

        // Verify that the current user is allowed to see this list, and
        // check again that we have a valid list ID. If showing the list
        // in an autotag, just display nothing.
        if (!$this->isAdmin) {
            if ($this->listid == '' || !SEC_inGroup($this->group_id)) {
                COM_404();
            }
        }

        if (!is_array($this->fields)) return '';
        $this->setGroupby('u.uid');
        $sql = $this->_getListSQL();
        if ($this->name_format == 1) {
            $sql .= ' ORDER BY sys_lname ASC, sys_fname ASC';
        } else {
            $sql .= ' ORDER BY fullname ASC';
        }
        //echo $sql;die;

        $result = DB_query($sql, 1);
        if (!$result || DB_numRows($result) < 1) {
            return false;
        }

        // Field types aren't included in the query like they are for
        // displayed lists, so we need to find out the type of each field.
        // Here we load up an array of field type classes so we don't have to
        // instantiate one and read from the database for every field.
        $tmp = array();
        $classes = array();
        foreach ($this->fields as $key=>$fieldinfo) {
            if (strstr($fieldinfo['dbfield'], '.')) {
                // A plugin field where dbfield is "table.fieldname"
                // Set a NULL value and the field value will be returned as-is
                $classes[$fieldinfo['field']] = NULL;
                continue;
            }
            switch ($fieldinfo['field']) {
            case 'username':
            case 'email':
            case 'fullname':
                $classes[$fieldinfo['field']] = new Fields\text();
                break;
            default:
                $tmp[] = "'" . DB_escapeString($fieldinfo['field']) . "'";
                break;
            }
        }
        if (!empty($tmp)) {
            $tmp = implode(',', $tmp);
            $q = "SELECT * FROM {$_TABLES['profile_def']}
                    WHERE name in ($tmp)";
            $r = DB_query($q);
            while ($z = DB_fetchArray($r, false)) {
                $classes[$z['name']] = Field::getInstance($z);
            }
        }

        // Open template
        $T = new \Template(PRF_PI_PATH . 'templates/pdf');
        if (file_exists(PRF_PI_PATH . "templates/pdf/custom/{$this->listid}.thtml")) {
            $T->set_file('list', $this->listid . '.thtml');
            $def_tpl = false;
        } else {
            $T->set_file('list', '_default.thtml');
            $def_tpl = true;
        }

        $T->set_block('list', 'HeaderRow', 'hrow');
        foreach ($this->fields as $field) {
            if ($def_tpl) {
                if (!empty($field['text'])) {
                    $text = $field['text'];
                } else {
                    $fldname = $field['field'];
                    if (
                        strpos($fldname, 'sys_') === 0 ||
                        strpos($fldname, 'prf_') === 0
                    ) {
                        $fldname = substr($fldname, 4);
                    }
                    if (isset($LANG_PROFILE[$fldname])) {
                        $text = $LANG_PROFILE[$fldname];
                    } else {
                        $text = ucfirst($fldname);
                    }
                }
                $T->set_var('fld_hdr', $text);
            }
            $T->parse('hrow', 'HeaderRow', true);
        }

        // Create a row for each user record
        $T->set_block('list', 'UserRow', 'row');
        while ($A = DB_fetchArray($result, false)) {
            if ($def_tpl) {
                $T->set_block('list', 'Fields', 'flds');
            }
            foreach ($this->fields as $field) {
                $fldname = $field['field'];
                switch ($fldname) {
                case 'avatar':
                    $data = USER_getPhoto($A['uid'], $A['photo'], $A['email']);
                    break;
                default:
                    $fldClass = $classes[$field['field']];
                    if ($fldClass !== NULL) {
                        $data = $fldClass->FormatValue($A[$fldname]);
                    } else {
                        $data = $A[$fldname];
                    }
                    break;
                }
                if ($def_tpl) {
                    $T->set_var('fld_val', $data);
                    $T->parse('flds', 'Fields', true);
                } else {
                    $T->set_var($fldname, $data);
                }
            }
            $T->parse('row', 'UserRow', true);
            $T->clear_var('flds');
        }

        $dt = new \Date('now', $_CONF['timezone']);
        $T->set_var(array(
            'title'         => $this->title,
            'report_date'   => $dt->format($_CONF['date'], true),
        ) );
        $T->parse('output', 'list');
        $content = $T->finish($T->get_var('output'));
        return $content;
    }

}
