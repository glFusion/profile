<?php
/**
 * Class to handle profile lists
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2009-2020 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     1.3.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;
use glFusion\Database\Database;
use glFusion\Log\Log;
use glFusion\FieldList;

/** Import user library for USER_getPhoto() */
USES_lib_user();


/**
 * Class for profile lists.
 * @package profile
 */
class UserList
{
    /** List identifier string.
     * @var string */
    protected $listid = '';

    /** Title string.
     * @var string */
    protected $title = '';

    /** List order of apperarance.
     * @var integer */
    protected $orderby = 0;

    /** Array of Fields included in this list.
     * @var array */
    protected $fields = array();

    /** Group with View access to this list.
     * @var integer */
    protected $group_id = 0;

    /** User group included in this list.
     * @var integer */
    private $incl_grp = 0;

    /** User statuses included in list.
     * @var boolean */
    private $incl_user_stat = 0;

    /** Indicate whether this is a new list or an update.
     * @var boolean */
    private $isNew = true;

    /** Indicate whether this user is an admin.
     * @var boolean */
    protected $isAdmin = false;

    /** Indicate whether the menu should be shown.
     * @var boolean; */
    private $showMenu = false;

    /** Indicate whether to show the Export link.
     * @var boolean */
    private $show_export = false;

    /** Indicate whether to show the search form.
     * @var boolean */
    private $hasExtras = false;

    /** Respect the show_directory preference?
     * @var boolean */
    private $dir_optional = false;

    /** SQL clause to restrict access by group membership
     * @var string*/
    private $access_sql = '';

    /** Field to order by.
     * @var string */
    private $sortby = '';

    /** Directory to order list (ASC or DESC).
     * @var string */
    private $sortdir = 'ASC';

    /** Group-by field.
     * Not used in admin lists but needed for PDF lists.
     * @var string */
    protected $group_by = '';

    /** Fname Lname, 1 = "Lname, Fname".
     * @var integer */
    protected $name_format = 0;

    /** Query string obtained from plugins.
     * @var string */
    private $pi_query = '';

    /** Plugin-supplied search form items.
     * @var string */
    private $pi_filter = '';


    /**
     * Constructor.
     *
     * @param   string  $listid List ID to edit or display, empty for new list
     */
    public function __construct($listid = '')
    {
        global $_GROUPS, $_USER;

        $this->isAdmin = SEC_hasRights('profile.admin, profile.manage,user.edit', 'OR') ?
                true : false;

        // Get the group memberships to verify view access.  Admins see all.
        if ($this->isAdmin) {
            $this->access_sql = '';
        } else {
            if (!is_array($_GROUPS)) {
                $_GROUPS = SEC_getUserGroups($_USER['uid']);
            }
            $this->access_sql = 'WHERE group_id in (' .
                                implode(',', $_GROUPS) . ')';
        }

        $this->listid = COM_sanitizeID($listid, false);
        if ($this->listid != '') {
            if ($this->Read()) {
                $this->isNew = false;
            }
        } else {
            $this->listid = '';
            $this->orderby = '999';
            $this->title = '';
            $this->fields = array();
            $this->group_id = 13;   // Logged-in Users
            $this->incl_grp = 2;    // All Users
            $this->incl_user_stat = array(1,2,3,4); // All user types
            $this->sortby = '';
            $this->sortdir  = '';
        }
    }


    /**
     * Read a list record from the database.
     *
     * @param   string  $listid     Optional list ID, current ID if empty
     */
    public function Read($listid = '')
    {
        global $_TABLES, $LANG_PROFILE;

        if ($listid == '') {
            $listid = $this->listid;
        } else {
            $listid = COM_sanitizeID($listid, false);
        }

        $res = DB_query("SELECT * FROM {$_TABLES['profile_lists']}
                    WHERE listid='$listid'", 1);
        $A = DB_fetchArray($res, false);
        if (!empty($A)) {
            $this->listid = $A['listid'];
            $this->SetVars($A, true);
            if (isset($_GET['allfields'])) {
                // If this is an export of all data fields, override the
                // list's serialized array of field names
                $this->fields = $this->_getAllFields();
            }
            $this->isNew = false;

            // Check if users can elect to be excluded from the directory.
            // If this is disabled, then the sys_directory field isn't checked
            // for each user record.  Users with the "viewall" or "admin"
            // privilege can always see all profiles.
            if (!SEC_hasRights('profile.viewall, profile.admin', 'OR')) {
                $dir_option = DB_getItem(
                    $_TABLES['profile_def'],
                    'enabled',
                    "name='sys_directory'"
                );
                $this->dir_optional = $dir_option == '1' ? true : false;
            } else {
                $this->dir_optional = false;
            }
       }
    }


    /**
     * Set this objects variables from an array.
     * If $fromDB is true, the fields are unserialized from the stored string.
     *
     * @param   array   $A  Array of name=>value pairs
     * @param   boolean $fromDB True if $A is from the database
     */
    public function SetVars($A=array(), $fromDB=false)
    {
        if (empty($A)) return;

        $this->orderby = (int)$A['orderby'];
        $this->title = trim($A['title']);
        $this->group_id = (int)$A['group_id'];
        $this->incl_grp = (int)$A['incl_grp'];
        if (isset($A['incl_user_stat'])) {
            if (is_array($A['incl_user_stat'])) {
                $this->incl_user_stat = $A['incl_user_stat'];
            } else {
                $this->incl_user_stat = @unserialize($A['incl_user_stat']);
                if (!$this->incl_user_stat) $this->incl_user_stat = array();
            }
        }

        if ($fromDB) {
            $this->fields = @unserialize($A['fields']);
            if (!$this->fields) $this->fields = array();
        } elseif (!empty($A['field'])) {
            $this->fields = array();
            $fields = array();
            $sortorder = array();      // temp used for sorting
            if (isset($A['sortby'])) {
                list($sortby, $sortdir) = @explode(':', $A['sortby']);
            } else {
                $sortby = '';
            }

            // Create an array of field info from the submitted values.
            // Then sort the array in order of appearance and set the class
            // variable keyed by integers.
            foreach ($A['field'] as $key=>$value) {
                // $key contains the field names, $value is a dummy
                if (!isset($A['order'][$key]) || empty($A['order'][$key]))
                    $A['order'][$key] = '999';
                $fields[$key] = array(
                    'field'     => $key,
                    'dbfield'   => empty($A['fld_dbfield'][$key]) ? $key :
                                    $A['fld_dbfield'][$key],
                    'text'      => empty($A['fld_text'][$key]) ? $key :
                                    $A['fld_text'][$key],
                    'sort'      => isset($A['sort'][$key]) ? true : false,
                    'search'    => isset($A['search'][$key]) ? true : false,
                );
                if (isset($A['fld_opt'][$key])) {
                    $fields[$key]['opt'] = $A['fld_opt'][$key];
                }
                if ($sortby == $key) {
                    $fields[$key]['sortdir'] = $sortdir == 'D' ? 'DESC' : 'ASC';
                }
                $sortorder[$key] = $A['order'][$key];
            }
            array_multisort($sortorder, SORT_ASC, $fields);
            foreach ($fields AS $key=>$data) {
                $this->fields[] = $data;
            }
        }
    }


    /**
     * Get the query SQL for this list.
     * This function goes through all the plugins and incorporates their
     * profile SQL to create one query.
     *
     * @since   version 1.1.1
     * @uses    _usersInGroup()
     * @param   string  $query  Optional query, needed by Export()
     * @param   array   $extras Extras field info to be used by the list func.
     * @return  string      SQL query to find users in the list
     */
    protected function _getListSQL(string $query='', ?array &$extras=NULL) : string
    {
        global $_TABLES, $_PLUGINS, $_PRF_CONF;

        if (!is_array($extras)) {
            $extras = array('f_info'=>array());
        } elseif (!isset($extras['f_info'])) {
            $extras['f_info'] = array();
        }

        $pi_addjoin = array();  // additional JOIN clauses
        $pi_search = array();   // additional WHERE clauses
        $pi_tmp = array();      // output from service functions()
        $svc_msg = '';          // service message from service functions()
        $fieldnames = array();
        $pi_where = array();    // additional "where" clauses
        $args = array('post' => $_POST, 'get' => $_GET);

        foreach ($_PLUGINS as $pi_name) {
            $status = LGLIB_invokeService(
                $pi_name, 'profilefields', $args,
                $pi_tmp, $svc_msg
            );
            if ($status == PLG_RET_OK) {
                $fieldnames[] = $pi_tmp['query'];
                $pi_addjoin[] = $pi_tmp['join'];
                if (!empty($pi_tmp['where'])) {
                    $pi_where[] = $pi_tmp['where'];
                }
                if (isset($pi_tmp['search']) && is_array($pi_tmp['search'])) {
                    foreach ($pi_tmp['search'] as $srch_fld) {
                        $pi_search[] = $srch_fld;
                    }
                }
                if (is_array($extras['f_info']) && isset($pi_tmp['f_info']) &&
                        is_array($pi_tmp['f_info'])) {
                    $extras['f_info'] = array_merge($extras['f_info'], $pi_tmp['f_info']);
                }
            }
       }

       if (empty($fieldnames)) {
            $field_select = '';
        } else {
            $field_select = ',' . implode(',', $fieldnames);
        }
        $addjoin = (!empty($pi_addjoin)) ? implode(' ', $pi_addjoin) : '';
        $where_and = '';    // Additional "and" clauses

        if ($this->dir_optional && !$this->isAdmin) {
            // Let users, decide if they're to be shown
            $where_and .=
                ' AND (p.sys_directory = 1 OR p.sys_directory IS NULL) ';
        }

        // Pass the user's show-in-directory selection and whether the directory
        // is optional so the field rendering function can tell if they're
        // shown by choice or because the viewer is an admin.
        $field_select .= ', p.sys_directory, ' .
            ($this->isAdmin ? '1' : '0') . ' AS is_admin';

        // Set the uid level to be excluded.
        // uids > $noshow_uid will be included.
        if ($_PRF_CONF['list_incl_admin']) {
            $noshow_uid = 1;
        } else {
            $noshow_uid = 2;
        }

        // Only show users in the list's inclusion group, unless the group
        // is "All Users"
        if ($this->incl_grp != 2 && $this->incl_grp != 13) {
            $limit_group = $this->_usersInGroup($this->incl_grp);
            $where_and .= ' AND u.uid IN (' . $limit_group . ') ';
        } else {
            $limit_group = '';
        }
        if (!empty($this->incl_user_stat)) {
            $where_and .= ' AND u.status IN (' . implode(',', $this->incl_user_stat) . ')';
        }
        if (!empty($pi_where)) {
            $where_and .= ' AND ' . implode(' AND ', $pi_where);
        }

        if (!empty($query)) {
            $where_and .= ' ' . $query;
        }

        if ($this->name_format == 1) {
            $full_name = "IF (u.fullname = '' OR u.fullname IS NULL,
                    u.fullname,
                    CONCAT(SUBSTRING_INDEX(u.fullname,' ',-1), ', ',
                    SUBSTRING_INDEX(u.fullname,' ',1))) AS fullname,
                    SUBSTRING_INDEX(u.fullname,'',-1) AS lname,
                    SUBSTRING_INDEX(u.fullname,'',1) AS fname,
                    u.fullname AS realfullname";
            //USES_lglib_class_nameparser();
        } else {
            $full_name = "u.fullname AS fullname, 0 AS name_format";
        }
        $sql = "SELECT u.uid, u.username, u.email, u.homepage, u.photo,
                {$this->name_format} AS name_format,
                p.*, $full_name
                $field_select
                FROM {$_TABLES['users']} u
                LEFT JOIN {$_TABLES['profile_data']} p
                ON u.uid = p.puid
                $addjoin";
                //WHERE u.uid > $noshow_uid
                //$where_and ";
        $this->_filter_sql = "WHERE u.uid > $noshow_uid $where_and";
        /*if (!empty($this->group_by)) {
            $sql .= " GROUP BY {$this->group_by}";
        }*/
                //GROUP BY u.uid";
        return $sql;
    }


    protected function setGroupby($str)
    {
        if (!empty($str)) {
            $this->group_by = $str;
        }
    }


    /**
     * Set the fullname format if fullname is one of the fields.
     * Iterate through the fields and if the full name is present, set the
     * name format. $name_format is set to zero by default.
     *
     * @since   version 1.1.3
     */
    private function _setFullnameFormat()
    {
        foreach ($this->fields as $key=>$fld) {
            if ($fld['field'] == 'fullname' && isset($fld['opt']['disp'])) {
                $this->name_format = $fld['opt']['disp'];
            }
        }
    }


    /**
     * Set the query fields into an array.
     * This is sent by Render() to ADMIN_list(), but is also needed by
     * Export() which has to construct the query itself.
     *
     * @since   version 1.1.3
     * @return  array   Array of searchable fields
     */
    private function _setQueryFields()
    {
        $query_fields = array();
        foreach ($this->fields as $key=>$fld) {
            if (isset($fld['search']) && $fld['search'] == true) {
                $query_fields[] = $fld['dbfield'];
            }
            if (isset($fld['sortdir'])) {
                $this->sortby = $fld['field'];
                $this->sortdir = $fld['sortdir'];
                if ($fld['field'] == 'fullname') {
                    if (isset($fld['opt']['disp']) &&
                            $fld['opt']['disp'] == 1) {
//                        $this->sortby = 'lname, fname';
                    }
                }
            }
            /*if ($fld['field'] == 'fullname' && isset($fld['opt']['disp'])) {
                $this->name_format = $fld['opt']['disp'];
            }*/
        }
        return $query_fields;
    }


    /**
     * Render the list using the ADMIN_ list functions in lib-admin.php.
     * Need to have a function available outside the object to handle
     * the field display.
     * If $autotag is true, then nothing is returned if the user doesn't
     * have access to the list.  If false, an error block is returned.
     *
     * @uses    _getListSQL()
     * @uses    _getListMenu()
     * @uses    _setNameFormat()
     * @param   boolean $autotag    True if being displayed by an autotag
     * @return  string      HTML for the user list
     */
    public function Render($autotag=false)
    {
        global $_CONF, $_TABLES, $LANG_ADMIN, $LANG_PROFILE, $_PRF_CONF,
                $LANG_ADMIN;

        if ($this->listid == '') {
            // Get the first list if none specified, return empty string
            // if no list found.
            if (!$this->getFirst()) return '';
        }

        $db = Database::getInstance();
        $retval = '';

        // Verify that the current user is allowed to see this list, and
        // check again that we have a valid list ID. If showing the list
        // in an autotag, just display nothing.
        if (!$this->isAdmin &&
                ($this->listid == '' || !SEC_inGroup($this->group_id)) ) {
            if (!$autotag) {
                $retval .= '<div class="uk-alert uk-alert-danger">'.
                        $LANG_PROFILE['list_none_avail'] .
                        '</div>';
                if (COM_isAnonUser()) {
                    // If anonymous, assume that there might be a list if they
                    // log in
                    $retval .= SEC_loginForm();
                }
            }
            return $retval;
        }

        if (!is_array($this->fields)) return '';

        USES_lib_admin();

        $header_arr = $this->fields;
        $query_fields = $this->_setQueryFields();

        $form_arr = array();
        $this->_setFullnameFormat();

        if ($this->isAdmin) {
            // Add the "Edit" link on the left
            $header_arr = array_merge(
                array(
                    0 => array(
                        'text' => $LANG_ADMIN['edit'],
                        'field' => 'edit',
                        'sort' => false,
                    )
                ),
                $header_arr
            );

            // Add a footer explaining the highlighted entries
            $footer = $LANG_PROFILE['list_footer'];
        } else {
            $footer = '';
        }

        $this->_getPluginFilters();

        $extras = array(
            'f_info' => array(),
            'db' => $db,
        );
        $query_sql = $this->_getListSQL('', $extras);

        if (!empty($this->sortby)) {
            $defsort_arr = array(
                'field' => $this->sortby,
                'direction' => $this->sortdir == 'DESC' ? 'DESC' : 'ASC',
            );
        } else {
            $defsort_arr = array(
                'field' => NULL,
                'direction' => 'ASC',
            );
        }

        $query_fields = $this->_setQueryFields();
        $query_arr = array('table' => 'profile_lists',
            'sql' => $query_sql,
            'default_filter' => $this->_filter_sql,
            'query_fields' => $query_fields,
            //'group_by' => 'u.uid',
        );
        if (!empty($this->_group_by)) {
            $query_arr['group_by'] = $this->_group_by;
        }

        // Need this to get the listid into the column header sorting links
        $text_arr = array(
            'form_url'  => PRF_PI_URL . '/list.php?listid='.$this->listid,
            'has_limit' => true,
            'has_search' => true,
        );
        if (!empty($this->pi_query)) {
            $text_arr['form_url'] .= '&amp;' . $this->pi_query;
        }

        if (!empty($query_arr['query_fields']) && $this->hasExtras) {
            $text_arr['has_extras'] = true;
        }

        $exportlink = '';
        $pdflink = '';
        $htmllink = '';
        if ($this->show_export) {
            $query = urlencode($this->_getQuery());
            if (!empty($query)) {
                $query = "&amp;q=$query";
            }
            if (!empty($this->pi_query)) {
                $query .= '&amp;' . $this->pi_query;
            }
            $exportlink_disp = PRF_PI_URL . '/list.php?action=export' . $query .
                '&amp;listid=' . $this->listid;
            if (isset($_GET['order'])) {
                $exportlink_disp .= '&amp;orderby=' .
                        $header_arr[$_GET['order']]['field'] .
                        '&amp;direction=' . $_GET['direction'];
            }
            $exportlink_all = $exportlink_disp . '&amp;allfields';
            $csv_disp = COM_createLink(
                'CSV ' . $LANG_PROFILE['displayed'],
                $exportlink_disp
            );
            $csv_all = COM_createLink(
                'CSV ' . $LANG_PROFILE['all_fields'],
                $exportlink_all
            );
            $baselink = PRF_PI_URL . '/list.php?listid=' . $this->listid;
            if (!empty($this->pi_query)) {
                $baselink .= '&amp;' . $this->pi_query;
            }
            $pdflink = COM_createLink(
                'PDF',
                $baselink . '&action=pdf',
                array(
                    'target' => '_new',
                )
            );
            $htmllink = COM_createLink(
                'HTML',
                $baselink . '&action=html',
                array(
                    'target' => '_new',
                )
            );
        }

        // Add the menu of available lists, if requested
        $menu = $this->showMenu ? $this->_getListMenu() : '';
        $T = new \Template(PRF_PI_PATH . 'templates/');
        $T->set_file('list', 'memberlist.thtml');
        $T->set_var(array(
            'list_contents' => ADMIN_list(
                'profile_list_' . $this->listid,
                array(__CLASS__, 'getListField'),
                $header_arr, $text_arr, $query_arr, $defsort_arr,
                $this->pi_filter, $extras, '', $form_arr
            ),
            'show_export'   => $this->show_export,
            'csv_disp'      => $csv_disp,
            'csv_all'       => $csv_all,
            'pdf_link'      => $pdflink,
            'html_link'     => $htmllink,
            'menu'          => $menu,
            'footer'        => $footer,
        ) );
        $T->parse('output', 'list');
        $retval = $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Get the query string from $_POST or $_GET, preferring $_GET.
     *
     * @since   version 1.1.3
     * @return  string  User-supplied query
     */
    private function _getQuery()
    {
        if (isset($_GET['q'])) {
            $query = COM_applyFilter($_GET['q']);
        } elseif (isset($_POST['q'])) {
            $query = COM_applyFilter($_POST['q']);
        } else {
            $query = '';
        }

        return $query;
    }


    /**
     * Export a member list as a CSV file.
     *
     * @since   version 1.1.1
     * @uses    _getListSQL()
     * @uses    showExport()
     * @uses    _getQueryFields()
     * @return  string      CSV output, or false for error/no access
     */
    public function Export()
    {
        global $_TABLES;

        // Make sure the current user has access to export lists
        $this->showExport();
        if (!$this->show_export) {
            return false;
        }
        $this->_setFullnameFormat();
        $query = $this->_getQuery();

        if (!empty($query)) {
            $query_fields = $this->_setQueryFields();
            $filters = array();
            $filtersql .= " AND (";
            foreach ($query_fields as $q) {
                $filters[] = "$q LIKE '%" . DB_escapeString($query) . "%'";
            }
            if (!empty($filters)) {
                $filtersql .= implode(' OR ', $filters) . ')';
            }
        } else {
            $filtersql = '';
        }

        // If no list ID was specified, get the first available list that
        // the user can access. Return empty string if none found.
        if ($this->listid == '') {
            if (!$this->getFirst()) return '';
        }

        // Get the requested sort field & direction, or use the default
        if (isset($_GET['order'])) {
            $this->sortby = $_GET['order'];
            $this->sortdir = $_GET['direction'];
        } else {
            foreach ($this->fields as $key=>$fld) {
                if (isset($fld['sortdir'])) {
                    $this->sortby = $fld['field'];
                    $this->sortdir = $fld['sortdir'];
                    break;
                }
            }
        }

        // Get the user info.  If empty, return nothing
        $sql = $this->_getListSQL($filtersql);
        //echo $sql;die;
        /*if (!empty($this->sortby)) {
            $sql .= " ORDER BY {$this->sortby} {$this->sortdir}";
        }*/
        $result = DB_query($sql, 1);
        if (!$result || DB_numRows($result) < 1) {
            return false;
        }

        // Create the header row
        $fieldrow = array();
        foreach ($this->fields as $field) {
            $fieldrow[] = $field['text'];
        }
        $retval = '"' . implode('","', $fieldrow) . "\"\n";

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
                $classes[$fieldinfo['field']] = new Fields\text($fieldinfo['field']);
                break;
            default:
                $tmp[] = "'" . DB_escapeString($fieldinfo['field']) . "'";
                break;
            }
        }
        if (!empty($tmp)) {
            $tmp = implode(',', $tmp);
            $q = "SELECT name, type FROM {$_TABLES['profile_def']}
                    WHERE enabled = 1 AND name in ($tmp)";
            $r = DB_query($q);
            while ($z = DB_fetchArray($r, false)) {
                $classes[$z['name']] = Field::getInstance($z);
            }
        }

        // Create a row for each user record
        while ($A = DB_fetchArray($result, false)) {
            $fieldrow = array();
            foreach ($this->fields as $field) {
                $fldname = $field['field'];
                $classname = $classes[$field['field']];
                if ($classname !== NULL) {
                    $fieldrow[] = $classname->FormatValue($A[$fldname]);
                } else {
                    $fieldrow[] = $A[$fldname];
                }
            }
            $retval .= '"' . implode('","', $fieldrow) . "\"\n";
        }
        return $retval;
    }


    /**
     * Gets a menu of available lists and returns the tabbed menu.
     *
     * @return  string      HTML for menu list
     */
    private function _getListMenu()
    {
        global $_TABLES;

        USES_class_navbar();

        $menu = new \navbar();
        $sql = "SELECT * from {$_TABLES['profile_lists']}
                {$this->access_sql}
                ORDER BY orderby ASC";
        $r = DB_query($sql, 1);
        $query = urlencode($this->_getQuery());
        if (!empty($query))
            $query = "&amp;q=$query";
        else
            $query = '';

        // Add query parameters obtained from plugins
        if (!empty($this->pi_query)) {
            $query .= '&amp;' . $this->pi_query;
        }
        while ($A = DB_fetchArray($r, false)) {
            // not using COM_buildUrl here since there may be a query string
            $menu->add_menuitem($A['title'], PRF_PI_URL .
                    '/list.php?listid=' . $A['listid'] . $query);
        }
        $menu->set_selected($this->title);
        return $menu->generate();
    }


    /**
     * Edit a list definition
     *
     * @return  string      HTML for the edit form
     */
    public function Edit()
    {
        global $_CONF, $_TABLES, $LANG_PROFILE, $LANG_ACCESS, $_PLUGINS,
                $LANG28, $LANG04;

        $T = new \Template(PRF_PI_PATH . '/templates/admin');
        $T->set_file('editform', 'list.thtml');
        $T->set_var(array(
            'listid'        => $this->listid,
            'orderby'       => $this->orderby,
            'title'         => $this->title,
            'view_grp_select' => PRF_GroupDropdown($this->group_id, 3),
            'incl_grp_select' => PRF_GroupDropdown($this->incl_grp, 3),
            //'ustat_sel' . $this->incl_user_stat => 'selected="selected"',
            'lang_active'   => $LANG28[45],
            'lang_disabled' => $LANG28[42],
            'lang_verification' => $LANG28[107],
            'lang_approval' => $LANG28[44],
            'lang_activation' => $LANG04[116],
            'referrer'      => isset($_POST['referrer']) ?
                $_POST['referrer'] : PRF_ADMIN_URL . '/index.php?lists=x',
            'doc_url'   => PRF_getDocURL('list_def.html'),
            'canDelete'     => $this->isNew ? false : true,
        ) );

        for ($i = 0; $i < 5; $i++) {
            if (in_array($i, $this->incl_user_stat)) {
                $T->set_var('ustat_chk' . $i, PRF_CHECKED);
            }
        }

        // Add fields from the main users table
        $avail_fields = array(
            'username'  => array(
                'title' => $LANG_PROFILE['username'],
                'field' => 'username',
                'dbfield' => $_TABLES['users'] . '.username',
                'perm' => 2,
            ),
            'fullname'  => array(
                'title' => $LANG_PROFILE['fullname'],
                'field' => 'fullname',
                'dbfield' => $_TABLES['users'] . '.fullname',
                'perm' => 2,
            ),
            'email'     => array(
                'title' => $LANG_PROFILE['email_addr'],
                'field' => 'email',
                'dbfield' => $_TABLES['users'] . '.email',
                'perm' => 2,
            ),
            'avatar'    => array('title' => $LANG_PROFILE['avatar'],
                'dbfield' => $_TABLES['users'] . '.photo',
                'field' => 'avatar',
                'perm' => 2,
            ),
        );

        $sql = "SELECT name, prompt, perm_members+perm_anon as perm
                FROM {$_TABLES['profile_def']}";
        $res = DB_query($sql, 1);
        while ($A = DB_fetchArray($res, false)) {
            $avail_fields[$A['name']] = array(
                    'title' => $A['prompt'],
                    'field' => $A['name'],
                    'perm'  => (int)$A['perm']);
        }

        // Now get any fields that are provided by plugins.  These won't
        // have text strings associated with them.
        $tmp = array();         // storage for plugin info in our format
        $args = array(
            'post' => $_POST,
            'get' => $_GET,
        );
        $pi_tmp = array();      // output from LGLIB_invokeService()
        $svc_msg = '';          // service message from LGLIB_invokeService()
        foreach ($_PLUGINS as $pi_name) {
            $status = LGLIB_invokeService(
                 $pi_name,
                 'profilefields',
                 $args,
                 $pi_tmp,
                 $svc_msg
            );
            if (
                $status == PLG_RET_OK &&
                isset($pi_tmp['names']) &&
                is_array($pi_tmp['names'])
            ) {
                foreach($pi_tmp['names'] as $name=>$info) {
                    if (!isset($info['title']) || !isset($info['field'])) continue;
                    $tmp[$name] = array(
                        'title' => $info['title'],
                        'field' => $info['field'],
                        //'field' => $name,
                        'perm'  => 2,
                    );
                }
                $avail_fields = array_merge($avail_fields, $tmp);
            }
        }

        $T->set_block('editform', 'FieldRow', 'fRow');
        $i = 0;
        if (is_array($this->fields)) {
            // Show the currently-selected fields at the top of the list
            foreach ($this->fields as $key=>$field) {
                if ($field['field'] == 'fullname') {
                    $fld_opt = '<select name="fld_opt[' .
                            $field['field'] . '][disp]">' . LB;
                    $nf = isset($field['opt']['disp']) ?
                        (int)$field['opt']['disp'] : 0;
                    for ($i = 0; $i < 2; $i++) {
                        $fld_opt .= '<option value="' . $i . '" ' .
                            ($i == $nf ? 'selected="selected"' : '') . '>' .
                            $LANG_PROFILE['name_format' . $i] . '</option>' . LB;
                    }
                    $fld_opt .= '</select>' . LB;
                } elseif (!array_key_exists($field['field'], $avail_fields)) {
                    // Avoid errors if a plugin that's part of this list was removed.
                    continue;
                } else {
                    $fld_opt = '';
                }
                $T->set_var(array(
                    'fld_name'  => $field['field'],
                    'fld_opt'   => $fld_opt,
                    'fld_dbfield' => $field['dbfield'],
                    'fld_chk'   => PRF_CHECKED,
                    'fld_text'  => $field['text'],
                    'order'     => ++$i * 10,
                    'fld_text'  => $field['text'],
                    'sort_chk'  => isset($field['sort']) && $field['sort'] == true ? PRF_CHECKED : '',
                    'search_chk' => isset($field['search']) && $field['search'] == true ? PRF_CHECKED : '',
                    'public_field' =>
                    $avail_fields[$field['field']]['perm'] >= 2 ? 'true' : '',
                    'allow_sort' => $field['field'] != 'avatar' ? 'true' : '',
                    'ASC_sel'   => '',
                    'DESC_sel'  => '',
                ) );
                if (isset($field['sortdir'])) {
                    $T->set_var($field['sortdir'] . '_sel', PRF_CHECKED);
                }
                // Remove the field from the available fields
                unset($avail_fields[$field['field']]);
                $T->parse('fRow', 'FieldRow', true);
            }
        }

        // Now show the rest of the available, but unused, fields
        foreach ($avail_fields as $field => $data) {
            if ($field == 'fullname') {
                $fld_opt = '<select name="fld_opt[' .
                            $data['field'] . '][disp]">' . LB;
                $nf = 0;
                for ($i = 0; $i < 2; $i++) {
                    $fld_opt .= '<option value="' . $i . '" ' .
                        ($i == $nf ? 'selected="selected"' : '') . '>' .
                        $LANG_PROFILE['name_format' . $i] . '</option>' . LB;
                }
                $fld_opt .= '</select>' . LB;
            } else {
                $fld_opt = '';
            }
            $T->set_var(array(
                'fld_name'  => $field,
                'fld_dbfield' => $data['field'],
                'fld_chk'   => '',
                'order'     => '',
                'fld_text'  => $data['title'],
                'sort_chk'  => '',
                'search_chk' => '',
                'public_field' => $data['perm'] >= 2 ? 'true' : '',
                'allow_sort' => $data['field'] != 'avatar' ? 'true' : '',
                'ASC_sel'   => '',
                'DESC_sel'  => '',
                'fld_opt'   => $fld_opt,
            ) );
            if (isset($data['sortdir'])) {
                $T->set_var($field['sortdir'] . '_sel', PRF_CHECKED);
            }
            $T->parse('fRow', 'FieldRow', true);
        }
        $T->parse('output', 'editform');
        $retval = $T->finish($T->get_var('output'));
        return $retval;
    }


    /**
     * Save a list definition
     *
     * @param   array   $A  Variables, typically from $_POST
     */
    public function Save($A = array())
    {
        global $_TABLES;

        if (!empty($A)) $this->SetVars($A);

        if (isset($A['oldid']) && !empty($A['oldid'])) {
            $sql_action = "UPDATE {$_TABLES['profile_lists']} SET ";
            $sql_where = " WHERE listid='" . COM_sanitizeId($A['oldid']) ."'";
        } else {
            $sql_action = "INSERT INTO {$_TABLES['profile_lists']} SET ";
            $sql_where = '';
        }

        $sql_fields = "listid = '" . COM_sanitizeId($A['listid']) . "',
            orderby = '" . (int)$A['orderby'] . "',
            title = '" . DB_escapeString($A['title']) . "',
            group_id = '" . (int)$this->group_id . "',
            incl_grp = '" . (int)$this->incl_grp . "',
            incl_user_stat = '" . DB_escapeString(@serialize($this->incl_user_stat)) . "',
            fields = '" . DB_escapeString(serialize($this->fields)) . "'";
        $sql = $sql_action . $sql_fields . $sql_where;
        //echo $sql;die;
        DB_query($sql);
        self::reOrder();
    }


    /**
     * Reorder all items in a table.
     * Table name is abstracted to support both list and definition tables.
     *
     * @param  string  $table  Name of table
     * @param  string  $id_fld Name of "id" field
     */
    public static function reOrder()
    {
        global $_TABLES;

        $sql = "SELECT listid, orderby
            FROM {$_TABLES['profile_lists']}
            ORDER BY orderby ASC;";
        $result = DB_query($sql, 1);
        if (!$result) return;

        $order = 10;
        $stepNumber = 10;
        $changed = false;
        while ($A = DB_fetchArray($result, false)) {
            if ($A['orderby'] != $order) {  // only update incorrect ones
                $changed = true;
                $sql = "UPDATE {$_TABLES['profile_lists']}
                    SET orderby = '$order'
                    WHERE listid = '" . DB_escapeString($A['listid']) . "'";
                DB_query($sql);
            }
            $order += $stepNumber;
        }
        if ($changed) {
            Cache::clear();
        }
    }


    /**
     * Delete a list definition.
     *
     * @param   string  $listid ID of list to remove
     */
    public static function Delete($listid)
    {
        global $_TABLES;

        if (!empty($listid)) {
            DB_delete($_TABLES['profile_lists'], 'listid', DB_escapeString($listid));
            Cache::clear();
        }
    }


    /**
     * Set the showExport property.  This is "false" by default.
     * This property determines whether the export link is shown by Render().
     *
     * @param   boolean $value  New value to set, default = true
     * @return  object  $this
     */
    public function showExport($value = TRUE)
    {
        if (
            $value !== FALSE &&
            ($this->isAdmin || SEC_hasRights('profile.export'))
        ) {
            $value = TRUE;
        } else {
            $value = FALSE;
        }
        $this->show_export = $value;
        return $this;
    }


    /**
     * Set the showMenu property.  This is "false" by default.
     * This property determines whether the menu of lists is shown by Render().
     *
     * @param   boolean $value  New value to set, default = true
     * @return  object  $this
     */
    public function showMenu($value = true)
    {
        if ($value !== FALSE) $value = TRUE;
        $this->showMenu = $value;
        return $this;
    }


    /**
     * Set the hasExtras property.  This is "false" by default.
     * This property determines whether the search form is shown with the list.
     *
     * @param   boolean $value  New value to set, default = true
     * @return  object  $this
     */
    public function hasExtras($value = true)
    {
        if ($value !== FALSE) $value = TRUE;
        $this->hasExtras = $value;
        return $this;
    }


    /**
     * Get all the users in a specified group or sub-group.
     * Borrowed nearly verbatim from GROUP_UsersInGroup().
     *
     * @param   integer $group_id   Requested group ID
     * @return  string      Comma-separated list of user IDs
     */
    private function _usersInGroup(int $group_id) : string
    {
        global $_TABLES;

        $users = array(0);

        $db = Database::getInstance();

        // Quick check, if $group_id is "All Users" or "Logged-In Users" then
        // return all users except Anonymous. glFusion 2.0+ doesn't create
        // group records for logged-in users any more.
        if ($group_id == 13 || $group_id == 2) {
            try {
                $data = $db->conn->executeQuery(
                    "SELECT uid FROM {$_TABLES['users']} WHERE uid > 1"
                )->fetchAllAssociative();
            } catch (\Exception $e) {
                Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                $data = false;
            }
            if (is_array($data)) {
                foreach ($data as $user) {
                    $users[] = $user['uid'];
                }
            }
            return implode(',', $users);
        }

        // First, get this group and all the groups that belong to it.
        // Root always belongs to all groups, so it's excluded unless it was
        // specifically requested.
        $to_check = array ();
        $groups = array();
        array_push($to_check, $group_id);
        while (count($to_check) > 0) {
            $thisgroup = array_pop($to_check);
            if ($thisgroup > 0) {
                try {
                    $data = $db->conn->executeQuery(
                        "SELECT ug_grp_id FROM {$_TABLES['group_assignments']}
                        WHERE ug_main_grp_id = ?",
                        array($thisgroup),
                        array(Database::INTEGER)
                    )->fetchAllAssociative();
                } catch (\Exception $e) {
                    Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                    $data = false;
                }
                if (is_array($data)) {
                    foreach ($data as $A) {
                        // Don't include the Root group unless it was requested
                        if ($A['ug_grp_id'] == 1 && $group_id > 1) continue;
                        if (!in_array($A['ug_grp_id'], $groups)) {
                            if (!in_array($A['ug_grp_id'], $to_check)) {
                                array_push($to_check, $A['ug_grp_id']);
                            }
                        }
                    }
                    $groups[] = $thisgroup;
                }
            }
        }

        // Next, get all the users that belong to any of the groups
        $qb = $db->conn->createQueryBuilder();
        try {
            $data = $qb->select('DISTINCT u.uid')
               ->from($_TABLES['users'], 'u')
               ->leftJoin('u', $_TABLES['group_assignments'], 'ga', 'u.uid = ga.ug_uid')
               ->where('uid > 1')
               ->andWhere('ga.ug_main_grp_id IN (:groups)')
               ->orderBy('u.uid', 'ASC')
               ->setParameter('groups', $groups, Database::PARAM_INT_ARRAY)
               ->execute()->fetchAllAssociative();
        } catch (\Exception $e) {
            Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
            $data = false;
        }
        if (is_array($data) && !empty($data)) {
            foreach ($data as $A) {
                $users[] = $A['uid'];
            }
        }
        return implode(',', $users);
    }


    /**
     * Get the plugin filters for each plugin for searches.
     */
    protected function _getPluginFilters()
    {
        global $_PLUGINS;

        $this->pi_filter = '';
        $this->pi_query = '';
        $get_params = array();
        $args = array(
            'post' => $_POST,
            'get' => $_GET,
            'incl_user_stat' => $this->incl_user_stat,
            'incl_grp' => $this->incl_grp,
            'grp_access' => $this->group_id,
        );
        foreach ($_PLUGINS as $pi_name) {
            $status = LGLIB_invokeService(
                $pi_name,
                'profilefilter',
                $args,
                $pi_filter, $svc_msg
            );
            if ($status == PLG_RET_OK) {
                if (!empty($pi_filter['filter'])) {
                    $this->pi_filter .= $pi_filter['filter'];
                }
                if (!empty($pi_filter['get'])) {
                    $get_parms[] = $pi_filter['get'];
                }
            }
        }
        if (!empty($get_parms)) {
            $this->pi_query = implode('&amp;', $get_parms);
        }
    }


    /**
     * Get all fieldnames into a serialized array.
     * Used in list export to export all data fields
     *
     * @return  string  Serialized array of field names
     */
    protected function _getAllFields()
    {
        global $_TABLES;
        $sql = "SELECT name, prompt, type
                FROM {$_TABLES['profile_def']}
                WHERE enabled = 1
                ORDER BY orderby ASC";
        $res = DB_query($sql);
        $F = array(
            'username'  => array(
                'name'      => 'username',
                'prompt'    => 'username',
                'type'      => 'text',
            ),
            'fullname' => array(
                'name'      => 'fullname',
                'prompt'    => 'fullname',
                'type'      => 'text',
            ),
            'email'     => array(
                'name'      => 'email',
                'prompt'    => 'email',
                'type'      => 'text',
            ),
        );
        while ($A = DB_fetchArray($res, false)) {
            if ($A['type'] != 'static') $F[] = $A;
        }
        $fields = array();
        foreach ($F as $key=>$dbfield) {
            $found = false;
            foreach($this->fields as $key=>$data) {
                if ($data['field'] == $dbfield['name']) {
                    $fields[] = array(
                        'field'     => $data['field'],
                        'dbfield'   => $data['dbfield'],
                        'text'      => $data['text'],
                        'sort'      => $data['sort'],
                        'search'    => $data['search'],
                        'type'      => $dbfield['type'],
                    );
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $fields[] = array(
                    'field'     => $dbfield['name'],
                    'dbfield'   => $dbfield['name'],
                    'text'      => $dbfield['prompt'],
                    'sort'      => false,
                    'search'    => false,
                    'type'      => $dbfield['type'],
                );
            }
        }
        return $fields;
    }


    /**
     * Get the first list in the database. Used if there is no list specified.
     * No return, calls $this->Read() to load the list into object properties.
     *
     * @return  boolean     True if a list was loaded, False if not
     */
    protected function getFirst()
    {
        global $_TABLES;

        // Get the first available list
        $sql = "SELECT * FROM {$_TABLES['profile_lists']}
                {$this->access_sql}
                ORDER BY orderby ASC LIMIT 1";
        //echo $sql;die;
        $r = DB_query($sql, 1);
        if ($r && DB_numRows($r) == 1) {
            $A = DB_fetchArray($r, false);
            $this->Read($A['listid']);
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get the display value for a give list field.
     * Custom fields require a lookup to the profile_def table to find out
     * how to display the values.  Known fields, from the users table, can
     * be handled more simply.
     *
     * @param   string  $fieldname      Name of field, from the header array
     * @param   mixed   $fieldvalue     Value of current field
     * @param   array   $A              Array of all fieldnames & values
     * @param   array   $icon_arr       Array of system icons (not used)
     * @param   array   $extras         Extra data passed in verbatim
     * @return  string          Value to display for the current field
     */
    public static function getListField($fieldname, $fieldvalue, $A, $icon_arr, $extras)
    {
        global $_CONF, $LANG_ACCESS, $LANG_PROFILE, $_PRF_CONF, $_TABLES;

        $retval = '';
        $pi_admin_url = PRF_ADMIN_URL;
        static $custflds = array(); // static array of field info to save lookups

        if ($A['sys_directory'] === NULL || $A['sys_directory'] == 1) {
            $cls = 'profile_public';
        } else {
            $cls = 'profile_private';
        }
        $beg = '<span class="' . $cls . '">';
        $end = '</span>';

        switch($fieldname) {
        case 'edit':
            $retval = FieldList::edit(array(
                'url' => "{$_CONF['site_admin_url']}/user.php?edit=x&amp;uid={$A['uid']}",
            ) );
            break;

        case 'delete':
            $retval = FieldList::delete(array(
                'delete_url' => "{$pi_admin_url}/list.php?action=delete&id={$A['id']}",
                'attr' => array(
                    'onclick' => "return confirm('Do you really want to delete this item?');",
                ),
            ) );
           break;

        case 'fullname':
            if ($fieldvalue == '' || $fieldvalue == ', ') {
                $fieldvalue = $A['username'];
            }
            if ($A['name_format'] == 1) {
                // Fix single-word fullnames, like "Cher"
                if ($fieldvalue == $A['realfullname'] . ', ' . $A['realfullname']) {
                    $fieldvalue = $A['realfullname'];
                }
            }
            $retval = COM_createLink(
                $fieldvalue,
                $_CONF['site_url'] . '/users.php?mode=profile&uid=' . $A['uid']
            );
            break;

        case 'username':
            $retval = COM_createLink(
                $fieldvalue,
                $_CONF['site_url'] . '/users.php?mode=profile&uid=' . $A['uid']
            );
            break;

        case 'sys_directory':
            $retval = $fieldvalue == 1 ? $LANG_PROFILE['yes'] : $LANG_PROFILE['no'];
            break;

        case 'email':
            $retval = $fieldvalue;
            break;

        case 'avatar':
            $retval = USER_getPhoto($A['uid'], $A['photo'], $A['email']);
            break;

        default:
            if (isset($extras['f_info'][$fieldname]['disp_func'])) {
                // A field-specific callback function. A plugin has specified this
                // function, so we'll just call it without the protection of
                // invokeService()
                $function = $extras['f_info'][$fieldname]['disp_func'];
                $retval = $function($fieldname, $fieldvalue, $A, $icon_arr, $extras);
            } else {
                // An unknown field type, we have to look it up and figure out how
                // to display the value. This has to be done for every field in
                // every record, so use $custflds to minimize DB calls.
                if (!isset($custflds[$fieldname])) {
                    try {
                        $custflds = $db->conn->executeQuery(
                            "SELECT * FROM {$_TABLES['profile_def']}
                            WHERE name = ?",
                            array($fieldname),
                            array(Database::STRING)
                        )->fetchAssociative();
                    } catch (\Exception $e) {
                        Log::write('system', Log::ERROR, __METHOD__ . ': ' . $e->getMessage());
                        $custflds = false;
                    }
                    if (!is_array($custflds)) {
                        $custflds = array();
                    }
                }
                if (isset($custflds[$fieldname]) && $custflds[$fldname]) {
                    // A custom profile field was found.
                    $F = Field::getInstance($custflds[$fieldname], $fieldvalue, $A['uid']);
                    $retval = $F->FormatValue();
                } else {
                    // The field is probably from a plugin.
                    $retval = $fieldvalue;
                }
            }
            break;

        }

        return $beg . $retval . $end;
    }

}

