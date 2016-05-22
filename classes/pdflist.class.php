<?php
/**
*   Handle the printing of PDF reports using FPDF
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2016 Lee Garner <lee@leegarner.com>
*   @package    profile
*   @version    1.1.4
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

USES_profile_class_list();

/**
*   Class for creating a PDF catalog
*   @package    profile
*   @since      1.1.4
*/
class prfPdfList extends prfList
{
    public function __construct($listid='')
    {
        parent::__construct($listid);
    }

 
    /**
    *   Create the report
    *
    *   @param  $filename   Filename to save to disk, empty to show in browser
    */
    public function Render($filename = '')
    {
        global $_CONF, $_TABLES;

        if ($this->listid == '') {
            // Get the first available list
            $sql = "SELECT * FROM {$_TABLES['profile_lists']}
                    {$this->access_sql}
                    ORDER BY orderby ASC LIMIT 1";
            //echo $sql;die;
            $r = DB_query($sql, 1);
            if ($r && DB_numRows($r) == 1) {
                $A = DB_fetchArray($r, false);
                $this->Read($A['listid']);
            }
        }

        if (!empty($filename)) {
            $cache_dir = $_CONF['path'] . 'data/profile';
            if (!is_dir($cache_dir)) {
                mkdir($cache_dir, 0777, true);
            }
        }
        $retval = '';

        // Verify that the current user is allowed to see this list, and
        // check again that we have a valid list ID. If showing the list
        // in an autotag, just display nothing.
        if (!$this->isAdmin) {
            if (!empty($filename)) {
                // Only admins can create files for later publication
                COM_404();
            }
            if ($this->listid == '' || !SEC_inGroup($this->group_id)) {
                COM_404();
            }
        }

        if (!is_array($this->fields)) return '';
        $sql = $this->_getListSQL();
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
                $classes[$fieldinfo['field']] = new prfText($fieldinfo['field']);
                break;
            default:
                $tmp[] = "'" . DB_escapeString($fieldinfo['field']) . "'";
                break;
            }
        }
        if (!empty($tmp)) {
            $tmp = implode(',', $tmp);
            $q = "SELECT name, type FROM {$_TABLES['profile_def']}
                    WHERE name in ($tmp)";
            $r = DB_query($q);
            while ($z = DB_fetchArray($r, false)) {
                $classname = 'prf' . $z['type'];
                if (class_exists($classname)) {
                    $classes[$z['name']] = new $classname($z['name']);
                } else {
                    $classes[$z['name']] = new prfText($z['name']);
                }
            }
        }

        // Open template
        $T = new Template(PRF_PI_PATH . 'templates/pdf');
        if (file_exists(PRF_PI_PATH . "templates/pdf/{$this->listid}.thtml")) {
            $T->set_file('list', $this->listid . '.thtml');
        } else {
            $T->set_file('list', 'default.thtml');
        }
        $T->set_block('list', 'UserRow', 'row');

        // Create a row for each user record
        while ($A = DB_fetchArray($result, false)) {
            foreach ($this->fields as $field) {
                $fldname = $field['field'];
                $classname = $classes[$field['field']];
                if ($classname !== NULL) {
                    $data = $classname->FormatValue($A[$fldname]);
                } else {
                    $data = $A[$fldname];
                }
                $T->set_var(array(
                    $fldname    => $data,
                ) );
            }
            $T->parse('row', 'UserRow');
        }

        USES_class_date();
        $dt = new Date('now', $_CONF['timezone']);
        $T->set_var(array(
            'title'         => $this->title,
            'report_date'   => $dt->format($_CONF['date'], true),
        ) );
        $T->parse('output', 'list');
        $content = $T->finish($T->get_var('output'));
 
        USES_lglib_class_html2pdf();
        try {
            $html2pdf = new HTML2PDF('P', 'A4', 'en');
          //$html2pdf->setModeDebug();
            $html2pdf->setDefaultFont('Arial');
            $html2pdf->writeHTML($content);
            // Save the file if a filename is given, otherwise download
            if ($filename !== '')  {
                $html2pdf->Output($this->cache_dir . '/' . $filename, 'F');
            } else {
                $html2pdf->Output('memberlist.pdf', 'I');
            }
        } catch(HTML2PDF_exception $e) {
            COM_errorLog($e);
            return 2;
        }

    }   // function Render()

}   // class PhotoCatalog

?>
