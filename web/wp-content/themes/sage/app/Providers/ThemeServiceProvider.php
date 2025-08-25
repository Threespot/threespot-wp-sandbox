<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Custom @html() Blade directives to safely escape HTML content
        // https://threespot.slack.com/archives/GSN56HNNA/p1633376245177600
        // https://discourse.roots.io/t/sage-10-custom-directive/21889
        // https://developer.wordpress.org/apis/security/escaping/
        // Test strings: https://github.com/minimaxir/big-list-of-naughty-strings/blob/master/blns.txt

        // Use wp_kses_data() to only allow the following elements:
        // <a>, <abbr>, <acronym>, <b>, <blockquote>, <cite>,
        // <code>, <datetime>, <del>, <em>, <href>, <i>, <q>,
        // <s>, <strike>, <strong>, <title>
        Blade::directive('html_inline', function ($text) {
            return "<?= wp_kses_data({$text}); ?>";
        });

        // Use wp_kses_post() to allow all safe HTML elements:
        // https://gist.github.com/tedw/3f8ab908b54bbc4ddf50cf0c87ba22a0
        Blade::directive('html', function ($text) {
            return "<?= wp_kses_post({$text}); ?>";
        });

        parent::boot();
    }
}
