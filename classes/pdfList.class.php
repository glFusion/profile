<?php
/**
 * Handle the printing of PDF reports using FPDF
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
 * Class for creating a PDF member list.
 * @package profile
 * @since   1.1.4
 */
class pdfList extends htmlList
{
    /**
     * Call the parent constructor.
     *
     * @param   integer $listid     ID of list to generate
     */
    public function __construct($listid='')
    {
        parent::__construct($listid);
    }


    /**
     * Create the report
     *
     * @param   string  $filename   Filename to save to disk, empty to show in browser
     */
    public function Render($filename = '')
    {
        if (!empty($filename)) {
            $cache_dir = $_CONF['path'] . 'data/profile';
            if (!is_dir($cache_dir)) {
                mkdir($cache_dir, 0777, true);
            }
        }

        // Verify that the current user is allowed to see this list, and
        // check again that we have a valid list ID. If showing the list
        // in an autotag, just display nothing.
        if (!$this->isAdmin) {
            if (!empty($filename)) {
                // Only admins can create files for later publication
                COM_404();
            }
        }

        // Render the list as HTML, then use Html2Pdf to create the PDF.
        $content = parent::Render($filename);

        USES_lglib_class_html2pdf();
        try {
            $html2pdf = new \Spipu\Html2Pdf\Html2Pdf('P', 'A4', 'en');
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

}   // class pdfList

?>
