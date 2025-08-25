<?php

namespace App;
use Illuminate\Support\Facades\Vite;

/**
 * Add SVG to supported MIME types
 */
add_filter('upload_mimes', function ($mimes) {
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
});

/**
 * Returns raw SVG markup via file in /resources/images or image ID
 *
 * Note: Tried to make this a custom Blade directive but the arguments have to be plain strings
 * Source: https://discourse.roots.io/t/best-practice-svg-wordpress-like-a-boss/6280/15
 * https://stackoverflow.com/questions/30058556/including-svg-contents-in-laravel-5-blade-template/43117258
 * https://laracasts.com/discuss/channels/laravel/custom-blade-directive-which-should-accept-an-array-of-objects
 * Could consider forking https://github.com/adamwathan/blade-svg
 * Could also consider https://github.com/Log1x/sage-svg but it lacks the same functionality as our helper.
 *
 * @param array $params {
 *     Array of parameters.
 *
 *     @type string $file       The filename of the SVG located in the theme’s /resources/images folder.
 *     @type int    $file_id    The ID of the SVG uploaded to the Media Library.
 *     @type string $class      Optional class(es) to apply to the SVG element.
 *     @type int    $width      Optional width of the SVG.
 *     @type int    $height     Optional height of the SVG.
 *     @type bool   $sprite     Whether to reference the file in the SVG sprite.
 *     @type bool   $unique_ids Whether to add a random hash to each ID to avoid conflicts.
 *     @type bool   $focusable  Whether the SVG should be accessible to screen readers and IE/Edge.
 * }
 * @return string The raw SVG markup or an error message.
 */
function svg($params) {
	$defaults = [
		'file' => '',// for SVGs located in the theme’s /resources/images folder
		'file_id' => null,// for SVGs uploaded to the Media Library
		'class' => null,// optional class(es) to apply to the SVG element
		'width' => null,
		'height' => null,
		'sprite' => false,# whether to reference the file in the SVG sprite (e.g. <use xlink:href="#sprite-icon_name">)
		'unique_ids' => true,# adds random hash to each ID to avoid conflicts with other SVGs
		'focusable' => false,# whether SVG should be accessible to screenreaders and IE/Edge
		// These options haven’t been integrated yet, use <span class="u-screenreader"> text instead
		// 'title' => null,
		// 'desc' => null
	];

	$params = array_merge($defaults, $params);

	// Ensure a filename or file ID is provided
	if (empty($params['file']) && empty($params['file_id'])) {
		return 'Error: No SVG file specified.';
	}

	// Get SVG path
	// https://discourse.roots.io/t/best-practice-svg-wordpress-like-a-boss/6280/15
	if (!empty($params['file'])) {
        $svg_path = get_theme_file_path("resources/images/{$params['file']}.svg");
	} else {
        // Get file by ID
		$svg_path = get_attached_file($params['file_id']);

        // Make sure it’s an SVG
        if (!empty($svg_path)) {
          $file_type = wp_check_filetype(basename($svg_path));
          if ($file_type['ext'] !== 'svg') {
            return 'Error: Uploaded file is not an SVG.';
          }
        }
	}

    // Ensure the file exists
    if (!file_exists($svg_path)) {
        return 'Error: SVG file not found at ' . $svg_path;
    }

    // Ensure file is readable
    if (!is_readable($svg_path)) {
        return 'Error: SVG file is not readable ' . $svg_path;
    }

    // Don’t load files larger than 500KB
    if (filesize($svg_path) > 500000) {
        return 'Error: SVG file exceeds 500KB max allowed size. Consider using <img> tag instead.';
    }

    // First read the file contents
    $svg_content = file_get_contents($svg_path);
    if ($svg_content === false) {
        return 'Error: Could not read SVG file.';
    }

    // Load SVG content with libxml security options
    libxml_use_internal_errors(true);
    $svg = new \DOMDocument();

    // Load SVG with LIBXML_NONET flag to prevent network access
    $svg->load($svg_path, LIBXML_NONET);

    // Check for any errors
    $errors = libxml_get_errors();

    if (!empty($errors)) {
        return 'Error: Invalid SVG markup in ' . $svg_path . ': ' . print_r($errors, true);
        libxml_clear_errors();
    }

    // Validate SVG has markup
    if (empty($svg->documentElement)) {
        return 'Error: Invalid SVG markup for ' . $svg_path;
    }

	// Remove unnecessary attributes for inline SVGs
	$svg->documentElement->removeAttribute('baseProfile');
	$svg->documentElement->removeAttribute('version');

	if (!$params['sprite']) {
		$svg->documentElement->removeAttribute('xmlns');
	}

	if (!$params['focusable']) {
		// Prevent SVG from gaining focus in IE 10+
		$svg->documentElement->setAttribute('focusable', 'false');

		// Hide SVG from screen readers (update once “title” and “desc” options are integrated)
		$svg->documentElement->setAttribute('aria-hidden', 'true');
	}

	// Get viewbox dimensions
	$dimensions = explode(' ', $svg->documentElement->getAttribute('viewBox'));

	$boxWidth = (float) $dimensions[2];
	$boxHeight = (float) $dimensions[3];

	// Set height attribute
	if (!empty($params['height'])) {
		$svg->documentElement->setAttribute('height', $params['height']);

		// Automatically calculate the width if not set
		if (empty($params['width'])) {
			$params['width'] = (float) $params['height'] * ($boxWidth / $boxHeight);
			$svg->documentElement->setAttribute('width', $params['width']);
		}
	}

	// Set width attribute
	if (!empty($params['width'])) {
		$svg->documentElement->setAttribute('width', $params['width']);

		// Automatically calculate the height if not set
		if (empty($params['height'])) {
			$params['height'] = (float) $params['width'] * ($boxHeight / $boxWidth);
			$svg->documentElement->setAttribute('height', $params['height']);
		}

	}

	// Set class attribute
	if (!empty($params['class'])) {
		$svg->documentElement->setAttribute('class', $params['class']);
	}

	// Handle sprite usage
    // Note: Automatically use sprite if the file path contains “/sprite/”
    // Note: Sprites won’t work in the admin since WP uses an iframe for the editor content
	if (
         ($params['sprite'] || str_contains($svg_path, '/sprite/')) &&
         !is_admin()
    ) {
		// Remove child nodes from original SVG
		while ($svg->documentElement->hasChildNodes()) {
			$svg->documentElement->removeChild($svg->documentElement->firstChild);
		}

		// Create <use> element
		// Note: Using createDocumentFragment() resulted in PHP warning about an undefined namespace
		// https://bugs.php.net/bug.php?id=44773
		// https://stackoverflow.com/a/59299852/673457
		$use = $svg->createElement('use');

		// Get filename by removing subfolders from path
		$filename = basename($params['file']);

		// All SVG sprite symbols should be prefixed with “sprite” (see svg-sprite.blade.php)
		$use->setAttribute('href', "#sprite-{$filename}");

		$svg->documentElement->appendChild($use);
	}

	$svg_markup = $svg->saveXML($svg->documentElement);

	// For inline SVGs (i.e. not using a sprite), append a random number to IDs to prevent conflicts
	if (!$params['sprite'] && $params['unique_ids']) {
		// Generate random number
		// https://www.php.net/manual/en/function.random-bytes.php
		$guid = '_'.bin2hex(random_bytes(3));

		// Find IDs, save to $id_matches array
		// NOTE: $id_matches[0] includes the markup (e.g. id="foo")
		//       $id_matches[1] only includes the ID (e.g. foo)
		preg_match_all('/\bid="(\S+)"/', $svg_markup, $id_matches);

		// Use array_filter() to remove empty and falsey nested arrays
		// https://www.php.net/manual/en/function.array-filter.php
		if (!empty(array_filter($id_matches))) {
			// Append $guid to IDs (e.g. id="icon_123456")
			foreach ($id_matches[0] as $id_match) {
				// $id_match includes the attribute and quotes, e.g. id="chev",
				// so we need to remove the closing quote using substr(), append
				// the guid, then add back the closing quote.
				$svg_markup = str_replace($id_match, substr($id_match, 0, -1).$guid.'"', $svg_markup);
			}

			// Append $guid to ID references (e.g. xlink:href="#foo", filter="url(#foo)")
			foreach ($id_matches[1] as $id_match) {
				// Capture group 1: Either `(#` or `"#`
				// Capture group 2: ID string
				// Capture group 1: Either `"` or `)`
				$svg_markup = preg_replace('/([("]#)('.$id_match.')([")])/', '$1$2'.$guid.'$3', $svg_markup);
			}
		}
	}

	return $svg_markup;
}


/**
 * Appends an SVG icon to the last word of a given text, wrapping the last word in a <span> to prevent orphans.
 *
 * @param array $params {
 *     Array of parameters.
 *
 *     @type string $text  The text to which the SVG icon will be appended.
 *     @type string $class The class to apply to the <span> wrapping the last word.
 *     @type array  $svg   The parameters for the svg() function to generate the SVG markup.
 * }
 * @return string The text with the appended SVG icon or an error message.
 */
function append_icon($params) {
	$defaults = [
		'text' => '',
		'class' => 'u-nowrap',
		'svg' => [
			// Options below are from svg() function
			'file' => '',
			'class' => null,
			'width' => null,
			'height' => null,
			'sprite' => false,
		]
	];

	$params = array_merge($defaults, $params);

	// Ensure text and an SVG filename are provided
	if (empty($params['text']) || empty($params['svg']['file'])) {
		return 'Error: Text or SVG file not specified.';
	}

	// Generate SVG markup
	$svg = svg($params['svg']);

	$words = explode(' ', $params['text']);

	if (count($words) == 1) {
		return '<span class="'.$params['class'].'">'.$params['text'].$svg.'</span>';
	}

	// Save last word, removing it from $words array
	$last_word = array_pop($words);

	// Convert remaining words back to string
	$text = implode(' ', $words);

	return $text.' <span class="'.$params['class'].'">'.$last_word.$svg.'</span>';
}
