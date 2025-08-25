<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use App\Traits\MenuHelpers;

class Nav extends Composer
{
    use MenuHelpers { get_menu as private; }
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.footer',
        'partials.header',
        'partials.nav-primary',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
      return [
        'primary_navigation' => $this->get_menu('primary_navigation'),
        'secondary_navigation' => $this->get_menu('secondary_navigation'),
        'footer_navigation' => $this->get_menu('footer_navigation'),
      ];
    }
}
