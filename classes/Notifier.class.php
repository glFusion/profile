<?php
/**
 * Class to handle notifications.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2021 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.5
 * @since       v1.2.5
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;


/**
 * Notification class.
 * @package profile
 */
class Notifier
{
    /** User ID of the recipient.
     * @var integer */
    private $uid = 0;


    /**
     * Instantiate the object, set the user ID property.
     *
     * @param   integer $uid    User ID
     */
    public function __construct($uid)
    {
        $this->uid = (int)$uid;
    }


    /**
     * Send a notification.
     */
    public function send()
    {
        global $_TABLES, $_CONF;

        // Get the current profile data.
        $P = new Profile($this->uid);
        $T = new \Template(PRF_PI_PATH . '/templates/notify');
        $T->set_file('email', 'user_updates.thtml');
        $T->set_block('email', 'Fields', 'Fld');
        foreach ($P->getFields() as $Field) {
            if ($Field->getPerm('owner') >= 2) {
                $T->set_var(array(
                    'fld_name' => $Field->getPrompt(),
                    'fld_val' => $Field->FormatValue(),
                ) );
                $T->parse('Fld', 'Fields', true);
            }
        }
        $T->parse('output', 'email');
        $html = $T->finish($T->get_var('output'));

        $email_addr = DB_getItem($_TABLES['users'], 'email', "uid = {$this->uid}");
        COM_emailNotification(array(
            'to' => array($email_addr),
            'from' => $_CONF['noreply_mail'],
            'htmlmessage' => $html,
            'subject' => $_CONF['site_name'] . ' - Profile Update',
        ) );
    }

}
