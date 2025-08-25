<?php
namespace App;
use function Env\env;

/**
 * Force absolute URLs for CSS files so they work in block pattern preview iframes.
 */
add_filter('style_loader_src', function($src, $handle) {
    // Convert relative URLs to absolute
    if (str_starts_with($src, '/')) {
        $src = WP_HOME . $src;
    }

	return $src;
}, 10, 2);

/**
 * Add custom block category
 * Note: Use the category slug in custom block metadata (e.g. `Category: threespotblock`)
 */
add_filter('block_categories_all', function($categories) {
    $new_category = [
        'slug' => 'threespotblock',
        'title' => __('Custom Blocks', 'sage'),
        'icon' => null,
    ];

    // Prepend new category to array of default categories
    array_unshift($categories, $new_category);

    return $categories;
}, 10);

/**
 * Add custom pattern category
 */
add_action('init', function() {
	if (class_exists('WP_Block_Patterns_Registry')) {
		register_block_pattern_category('threespotblock', [
    	    'label' => __('Custom Patterns', 'sage')
		]);
	}
}, 10);

/**
 * Remove empty <p> tags from body
 * Note: Although we can hide them with CSS using “p:empty { display: none; }” they’ll
 *       still affect first/last/nth-child selectors so it’s better to remove them.
 */
add_filter('the_content', function ($content) {
    $content = preg_replace('/(<p><\/p>)/', '', $content);

    return $content;
});

/**
 * Remove “auto” from “sizes” attribute on images (added in WP 6.7)
 * https://core.trac.wordpress.org/ticket/61847#comment:23
 * https://caniuse.com/mdn-html_elements_img_sizes_auto
 */
add_filter('wp_content_img_tag', function($image) {
    return str_replace(' sizes="auto, ', ' sizes="', $image);
});
add_filter('wp_get_attachment_image_attributes', function($attr) {
    if (isset($attr['sizes'])) {
        $attr['sizes'] = preg_replace('/^auto, /', '', $attr['sizes']);
    }
    return $attr;
});

/**
 * Replace YouTube and Vimeo iframes with <lite-youtube> and <lite-vimeo> custom elements
 *
 * @param string $html The oEmbed HTML returned by the provider.
 * @param string $url The URL of the content being embedded.
 * @param array  $attr An array of shortcode attributes (if applicable).
 * @param int    $post_id The post ID where the embed appears.
 * @return string The modified embed HTML with lite-youtube or lite-vimeo element.
 */
add_filter('embed_oembed_html', function($html, $url, $attr, $post_id) {
 // Process YouTube embeds
 if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
  return preg_replace_callback('/<iframe.*?<\/iframe>/is', function ($iframe_match) {
   $iframe = $iframe_match[0];

   if (preg_match('/\/embed\/([^?"\'\s]+)/', $iframe, $video_matches)) {
    $video_id = esc_attr($video_matches[1]);

    // Extract title if present
    $title = '';
    if (preg_match('/title="([^"]+)"/', $iframe, $title_matches)) {
     $title = esc_attr($title_matches[1]);
    }

    // Create the lite-youtube element with heredoc
    // PHP 8.2 supports complex expressions in string interpolation
    $title_text        = $title ? ": {$title}" : "";
    $lite_youtube_html = <<<HTML
<lite-youtube videoid="{$video_id}" params="autoplay=0&controls=1&rel=0&enablejsapi=1" js-api>
    <a href="https://youtube.com/watch?v={$video_id}" class="lty-playbtn">
        <span class="u-screenreader">Play Video{$title_text}</span>
    </a>
</lite-youtube>
HTML;

    return $lite_youtube_html;
   }

   return $iframe;
  }, $html);
 }

 // Process Vimeo embeds
 if (strpos($url, 'vimeo.com') !== false) {
  return preg_replace_callback('/<iframe.*?<\/iframe>/is', function ($iframe_match) {
   $iframe = $iframe_match[0];

   if (preg_match('/\/video\/([^?"\'\s]+)/', $iframe, $video_matches)) {
    $video_id = esc_attr($video_matches[1]);

    // Extract title if present
    $title = '';
    if (preg_match('/title="([^"]+)"/', $iframe, $title_matches)) {
     $title = esc_attr($title_matches[1]);
    }

    // Create the lite-vimeo element with heredoc
    $title_text       = $title ? ": {$title}" : "";
    $lite_vimeo_html = <<<HTML
<lite-vimeo videoid="{$video_id}">
    <a href="https://vimeo.com/{$video_id}" class="ltv-playbtn">
        <span class="u-screenreader">Play Video{$title_text}</span>
    </a>
</lite-vimeo>
HTML;

    return $lite_vimeo_html;
   }

   return $iframe;
  }, $html);
 }

 return $html;
}, 10, 4);

/**
 * Disable h1 heading level option
 * https://github.com/WordPress/gutenberg/pull/63535
 * NOTE: Requires Gutenberg 19+ (supported starting in WP 6.7)
 *       https://developer.wordpress.org/block-editor/contributors/versions-in-wordpress/
 */
add_filter('register_block_type_args', function($args, $block_type) {
    if ($block_type == 'core/heading') {
        $args['attributes']['levelOptions']['default'] = [2, 3, 4, 5, 6];
    }

    return $args;
}, 10, 2);

/**
 * Add custom classes to default blocks
 * Note: We’re adding “u-richtext” to column and group blocks
 *       in gutenberg.js (see “blocks.getBlockDefaultClassName”)
 *
 * https://developer.wordpress.org/reference/hooks/render_block/
 */
add_filter('render_block', function($block_content, $block) {
    // Heading block: Convert any <h1> to <h2> for accessibility
    //                (the page title should be the only <h1> on a page)
    // https://github.com/WordPress/gutenberg/issues/15160#issuecomment-908586929
    // https://www.a11yproject.com/posts/how-to-accessible-heading-structure/
    if ( $block['blockName'] === 'core/heading' ) {
        $block_content = str_replace('<h1', '<h2', $block_content);
        $block_content = str_replace('</h1', '</h2', $block_content);
    }

    // Cover block: Add “u-richtext” to inner container
    // NOTE: This won’t run in the admin so we have to add the richtext styles manually in gutenberg.scss
    if ($block['blockName'] === 'core/cover') {
        $block_content = str_replace('wp-block-cover__inner-container', 'wp-block-cover__inner-container u-richtext', $block_content);
    }

    // Media & Text block
    if ($block['blockName'] === 'core/media-text') {
        // Add “u-richtext” to text content wrapper
        // NOTE: This won’t run in the admin so we have to add the richtext styles manually in gutenberg.scss
        $block_content = str_replace('wp-block-media-text__content', 'wp-block-media-text__content u-richtext', $block_content);

        // Add loading="lazy" to images
        $block_content = str_replace('<img', '<img loading="lazy"', $block_content);
    }

    // Details block: Add “u-richtext” to wrapper
    if ($block['blockName'] === 'core/details') {
        $block_content = str_replace('wp-block-details ', 'wp-block-details u-richtext ', $block_content);
    }

    // File block: Hide inaccessible PDF viewer
    if ($block['blockName'] === 'core/file') {
        $block_content = str_replace('<object class="wp-block-file__embed"', '<object aria-hidden="true" class="wp-block-file__embed"', $block_content);
    }

    // Add loading="lazy" to iframe embeds
    // https://web.dev/articles/iframe-lazy-loading
    if ($block['blockName'] === 'core/embed') {
        $block_content = str_replace('<iframe ', '<iframe loading="lazy" ', $block_content);
    }

    return $block_content;
}, 10, 2 );

/**
 * Customize ACF oEmbed video iframe markup
 * @link https://www.advancedcustomfields.com/resources/oembed/
 * @param string $iframe
 * @return string
 */
function format_video_iframe($iframe) {
    // Use preg_match to find iframe src.
    preg_match('/src="(.+?)"/', $iframe, $matches);

    if (count($matches) === 0) {
        return false;
    }

    // Get iframe src
    $src = $matches[1];

    // Optional: Use “nocookie” embed for YouTube
    // NOTE: This will prevent the YouTube iframe JS API from working
    // if (str_contains($src, 'youtube.com')) {
    //     $src = str_replace('youtube.com', 'youtube-nocookie.com', $src);
    // }

    // Add custom URL params
    $params = array(
        // YouTube params
        // https://developers.google.com/youtube/player_parameters#Parameters
        'enablejsapi' => 1,
        'modestbranding' => 1,
        'rel' => 0,
        // Vimeo params
        // https://help.vimeo.com/hc/en-us/articles/12426260232977-Player-parameters-overview
        'color' => 'ff5100',
        'portrait' => 0,
        'title' => 0,
        // 'dnt' => 1,// Optional, will disable analytics
    );

    // Add custom query args to src
    $new_src = add_query_arg($params, $src);

    // Replace original src with updated one
    $iframe = str_replace($src, $new_src, $iframe);

    // Add extra attributes to iframe markup
    $iframe = str_replace('></iframe>', ' frameborder="0" loading="lazy"></iframe>', $iframe);

    return $iframe;
}

/**
 * Unregister unused default block styles and variations
 * @link https://github.com/WordPress/gutenberg/issues/25330#issuecomment-2143405764
 * @link https://discourse.roots.io/t/cant-unregister-core-block-style-with-sage-11/29304
 */
add_filter('block_type_metadata', function($metadata) {
    $disable_block_styles = [
        'core/button' => ['outline'],
        'core/image' => ['circle-mask', 'rounded'],
        'core/pullquote' => ['solid', 'solid-color'],
        'core/quote' => ['large', 'plain'],
        'core/separator' => ['dots', 'wide'],
    ];

    $disable_block_variations = [
        'core/group' => ['group-row', 'group-stack'],
    ];

    // Remove block styles
    if(array_key_exists($metadata['name'], $disable_block_styles)) {
        $styles = $metadata['styles'] ?? [];// $metadata['styles'] can be null

        $metadata['styles'] = array_filter(
            $styles,
            function ($style) use ($disable_block_styles, $metadata) {
                return !in_array($style['name'], $disable_block_styles[$metadata['name']], true);
            }
        );
    }

    // Remove block variations
    if(array_key_exists($metadata['name'], $disable_block_variations)) {
        $variations = $metadata['variations'] ?? [];// $metadata['styles'] can be null

        $metadata['variations'] = array_filter(
            $variations,
            function ($variation) use ($disable_block_variations, $metadata) {
                return !in_array($variation['name'], $disable_block_variations[$metadata['name']], true);
            }
        );
    }

    return $metadata;
});
