<?php
/**
 * Theme setup.
 */
namespace App;
use function Env\env;
use Illuminate\Support\Facades\Vite;

/**
 * Get a full URL for a Vite asset for use with wp_enqueue_style()/wp_enqueue_script()
 * Since we’re not using Vite::withEntryPoints() we need to make sure we’re using the absolute URLs
 * for assets. In production, Vite::asset uses a relative URL which WP will prepend the `/wp` to.
 *
 * @param string $entry
 * @return string
 */
if (!function_exists('vite_asset_url')) {
    function vite_asset_url($entry)
    {
        $url = Vite::asset($entry);

        // Do nothing if the URL is already absolute
        if (preg_match('#^https?://#', $url) || strpos($url, '//') === 0) {
            return $url;
        }

        // Prepend home_url()
        return home_url($url);
    }
}

/**
 * Register the theme assets.
 *
 * @return void
 */
add_action('wp_enqueue_scripts', function () {
    // Optional: Only necessary if jQuery is used on the client side and not using WP’s local version
    // Locally host latest jQuery and load at end of the body instead of in the head
    // https://core.trac.wordpress.org/ticket/37110#comment:82
    if (!is_admin() && !is_customize_preview()) {
        wp_deregister_script('jquery'); // “jquery” is an alias that loads “jquery-core” and “jquery-migrate”
        wp_deregister_script('jquery-core');
        wp_deregister_script('jquery-migrate');

        // Register jQuery 3.x
        // Note: We don’t have to use Vite helpers since this file isn’t hashed
        wp_register_script('jquery-core', get_template_directory_uri() . '/public/build/jquery-3.7.1.min.js', null, '', true);

        // Register “jquery” handle to avoid breaking other scripts
        // https://wordpress.stackexchange.com/a/284532/
        // Note: We have to register the script before enqueueing it:
        // https://wordpress.stackexchange.com/a/82492/
        // https://stackoverflow.com/q/39653993/
        wp_register_script('jquery', false, ['jquery-core'], null, true);
    }

    // Disable oEmbed Discovery Links
    // Note: Yoast can also do this and much more
    //       https://yoast.com/help/yoast-seo-settings-crawl-optimization
    remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);

    // Keep default WP inline styles even though most of it is unused
    // - Docs: https://github.com/WordPress/gutenberg/blob/trunk/docs/explanations/architecture/styles.md#global-styles
    // - Example of inlined CSS: https://gist.github.com/tedw/4f150efc18e5208997c530e11ad3aaca
    // - Issue about removing default colors: https://github.com/WordPress/gutenberg/issues/40183
    // - How to remove styles: https://fullsiteediting.com/lessons/how-to-remove-default-block-styles/
    // wp_dequeue_style('global-styles');

    // Enqueue main CSS and JS files
    // NOTE: We’re using wp_enqueue_style() to have more control over where the tag is added.
    //       Sage recommends `echo Vite::withEntryPoints('resources/styles/main.scss')->toHtml();`
    wp_enqueue_style('main-css', vite_asset_url('resources/styles/main.scss'), null, '');

    // NOTE: We’re using wp_enqueue_script() to have more control over where the tag is added.
    //       Sage recommends `echo Vite::withEntryPoints('resources/styles/main.js')->toHtml();`
    wp_enqueue_script('main-js', vite_asset_url('resources/scripts/main.js'), null, '', true);
}, 100);

/**
 * Inline critical CSS inline in the <head> in production
 * Based on https://discourse.roots.io/t/critical-css-plugin/11855/3
 */
add_action('wp_head', function() {
    $css_path = Vite::asset('resources/styles/critical.scss');
    $css_path = get_template_directory().'/public'.explode('/public', $css_path)[1];

    // Note: The file won’t exist locally when the dev server is running so use Vite
    //       to add the `<link rel="stylesheet"> tag instead.
    if (!file_exists($css_path)) {
        echo Vite::withEntryPoints('resources/styles/critical.scss')->toHtml();
    } else {
        $css = file_get_contents($css_path);
        echo "<style id=\"critical\">{$css}</style>";
    }
}, 2);// use `2` so code is added right after Yoast’s meta tags

/**
 * Customize the <script> tags
 *
 * - Add type="module" to main.js to prevent old browsers (<=IE 11, <=Edge 15) from downloading it
 * - Add “defer” to all other scripts, except admin and some 3rd-party scripts
 * - Add “data-handle” attribute for debugging purposes
 * - See https://addyosmani.com/blog/script-priorities/ for script loading priority chart
 */
add_filter('script_loader_tag', function ($tag, $handle, $source) {
    // Add type="module" to main.js to prevent old browsers (<=IE 11, <=Edge 15) from downloading it
    // and ensure `vite-plugin-svg-icons` works locally (requires using `import`)
    // Note: Vite normally add type="module" to all scripts but since we’re using “wp_enqueue_script”
    //       we have to add it manually. See add_action('wp_enqueue_scripts') above for more info.
    // Note: Scripts with type="module" are also deferred by the browser
    if ($handle == 'main-js') {
        $tag = preg_replace('/><\/script>/', ' type="module" data-handle="' . $handle . '"></script>', $tag);
    }

    // Don’t alter scripts when logged into the admin, since there are additional
    // WordPress and 3rd-party scripts that are loaded and could break.
    // Also don’t alter them when in the WooCommerce cart or checkout flow.
    if (
        is_user_logged_in() ||
        (function_exists('\is_cart') && \is_cart()) ||
        (function_exists('\is_checkout') && \is_checkout())
    ) {
        return $tag;
    }

    // Add script handles that shouldn’t be deferred to $do_not_defer_handles array below
    // Don’t defer Google’s reCAPTCHA script (added by WP Forms as “wpforms-recaptcha”)
    // since WP Forms uses an inline <script> tag that breaks if reCAPTCHA is deferred.
    // Gravity Forms also adds some inline JS that requires the scripts execute in order.
    $do_not_defer_handles = [
        // Default WP scripts (only added when logged in)
        'admin-bar',
        'regenerator-runtime',
        'wp-a11y',
        'wp-dom-ready',
        'wp-hooks',
        'wp-i18n',
        'wp-polyfill',
        'wp-polyfill-inert',
        // Some plugins may break if jQuery is deferred (e.g. Gravity Forms)
        'jquery-core',
        // WP Forms
        'wpforms-recaptcha',
        // Gravity Forms
        'gform_gravityforms',
        'gform_gravityforms_theme',
        'gform_gravityforms_theme_vendors',
        'gform_gravityforms_utils',
        'gform_json',
        'gform_placeholder',
        'gform_textarea_counter',
        // WooCommerce
        'jquery-blockui',
        'jquery-mask',
        'jquery-payment',
        'js-cookie',
        'selectWoo',
        'sourcebuster-js',
        'stripe',
        'wc-add-to-cart',
        'wc-address-i18n',
        'wc-cart',
        'wc-cart-mr-giftcard',
        'wc-checkout',
        'wc-checkout-mr-giftcard',
        'wc-country-select',
        'wc-order-attribution',
        'wc_stripe_payment_request',
        'woo-tracks',
        'woocommerce',
        'woocommerce-tokenization-form',
        'woocommerce_stripe',
    ];

    // Don’t alter scripts in the admin, or any scripts listed in $do_not_defer_handles
    if (is_admin() || is_customize_preview() || in_array($handle, $do_not_defer_handles)) {
        $tag = $tag;
    }
    // Add “defer” to all other scripts
    elseif (!is_admin() && !is_customize_preview() ) {
        $tag = preg_replace('/><\/script>/', ' defer data-handle="' . $handle . '"></script>', $tag);
    }

    return $tag;
}, 20, 3);
