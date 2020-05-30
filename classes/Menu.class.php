<?php
/**
 * Class to provide admin and user-facing menus.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2019-2020 Lee Garner <lee@leegarner.com>
 * @package     profile
 * @version     v1.2.0
 * @since       v1.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Profile;

/**
 * Class to provide admin and user-facing menus.
 * @package profile
 */
class Menu
{
    /**
     * Create the administrator menu.
     *
     * @param   string  $view   View being shown, so set the help text
     * @return  string      Administrator menu
     */
    public static function Admin($view='')
    {
        global $_CONF, $LANG_PROFILE, $_PRF_CONF, $LANG01;

        USES_lib_admin();
        if ($view == '') {
            $view = 'list';
        }

        $help_text = isset($LANG_PROFILE['help'][$view]) ? $LANG_PROFILE['help'][$view] : '';

        $menu_arr = array(
            array(
                'url' => PRF_ADMIN_URL . '/index.php',
                'text' => $LANG_PROFILE['list_profiles'],
                'active' => $view == 'list' ? true : false,
            ),
            array(
                'url' => PRF_ADMIN_URL . '/index.php?lists=x',
                'text' => $LANG_PROFILE['lists'],
                'active' => $view == 'lists' ? true : false,
            ),
            array(
                'url' => PRF_ADMIN_URL . '/index.php?permreset=x',
                'text' => $LANG_PROFILE['reset_perms'],
                'active' => $view == 'resetpermform' ? true : false,
            ),
            array(
                'url' => PRF_ADMIN_URL . '/index.php?searchusers=x',
                'text' => $LANG_PROFILE['search_users'],
                'active' => $view == 'searchusers' ? true : false,
            ),
            array(
                'url' => $_CONF['site_admin_url'],
                'text' => $LANG01[53],
            ),
        );

        $retval = ADMIN_createMenu(
            $menu_arr, $help_text,
            plugin_geticon_profile()
        );
        return $retval;
    }


    /**
     * Show the site header, with or without left blocks according to config.
     *
     * @since   1.0.2
     * @see     COM_siteHeader()
     * @param   string  $subject    Text for page title (ad title, etc)
     * @param   string  $meta       Other meta info
     * @return  string              HTML for site header
     */
    public static function siteHeader($subject='', $meta='')
    {
        global $_PRF_CONF, $LANG_PROFILE;

        $retval = '';
        if (!isset($_PRF_CONF['displayblocks'])) {
            $_PRF_CONF['displayblocks'] = 0;
        }
        switch($_PRF_CONF['displayblocks']) {
        case 2:     // right only
        case 0:     // none
            $retval .= COM_siteHeader('none', $subject, $meta);
            break;
        case 1:     // left only
        case 3:     // both
        default :
            $retval .= COM_siteHeader('menu', $subject, $meta);
            break;
        }
        return $retval;
    }


    /**
     * Show the site footer, with or without right blocks according to config.
     *
     * @since   version 1.0.2
     * @see     COM_siteFooter()
     * @return  string              HTML for site header
     */
    public static function siteFooter()
    {
        global $_PRF_CONF;

        $retval = '';

        switch($_PRF_CONF['displayblocks']) {
        case 2 : // right only
        case 3 : // left and right
            $retval .= COM_siteFooter(true);
            break;
        case 0: // none
        case 1: // left only
        default :
            $retval .= COM_siteFooter();
            break;
        }
        return $retval;
    }

}

?>
