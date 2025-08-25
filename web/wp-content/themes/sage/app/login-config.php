<?php
namespace App;
use Illuminate\Support\Facades\Vite;

/**
 * Enqueue custom login page styles
 * Can also consider using https://github.com/log1x/modern-login
 */
add_action('login_enqueue_scripts', function() {
    echo Vite::withEntryPoints(['resources/styles/login.scss'])->toHtml();
});

// Change login page logo URL to homepage instead of https://wordpress.org
add_filter('login_headerurl', function() {
    return home_url();
});

// Set log-in page logo alt text to site name
add_filter('login_headertext', function() {
    return get_option('blogname', 'Site Admin');
});
