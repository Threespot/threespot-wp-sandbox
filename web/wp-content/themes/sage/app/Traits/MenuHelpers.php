<?php

namespace App\Traits;

use Log1x\Navi\Navi;

/**
 * Helper methods that can be used on WordPress menus
 */

trait MenuHelpers
{
    /**
     * Navi menu helper
     * https://github.com/Log1x/navi
     * Checks if the menu has been assigned and builds a Navi object from the WP menu
     * @return {object} $navigation
     * @return {boolean} false
     */
    function get_menu($menu_name)
    {
        $navigation = false;

        if (has_nav_menu($menu_name)) {
            $navigation = (new Navi())->build($menu_name)->toArray();
        }

        return $navigation;
    }
}
