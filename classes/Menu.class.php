<?php
/**
 * Class to provide admin and user-facing menus.
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

}

?>


