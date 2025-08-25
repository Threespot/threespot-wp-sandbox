<?php

namespace App;

/**
 * Return base64 encoded string of a 1px transparent GIF
 * Useful when we want to emulate <picture> using two <img> tags with “srcset”
 * with CSS to hide one of the two <img> tags based on the viewport width.
 * This is preferable to <picture> since it allows the browser to select the best source.
 * https://css-tricks.com/snippets/html/base64-encode-of-1x1px-transparent-gif/
 * https://stackoverflow.com/a/13139830/673457
 *
 * @return string
 */
function blank_gif() {
  return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
}

/**
 * Get the file extension of an image attachment
 *
 * @param int $attachment_id
 * @return string|false File extension, lowercase (e.g. jpg, png) or false if not found
 */
function get_image_file_type($attachment_id) {
    $metadata = wp_get_attachment_metadata($attachment_id);

    // Verify metadata exists and has file path
    if (!$metadata || !isset($metadata['file'])) {
        return false;
    }

    // Extract and return lowercase file extension
    $file_type = pathinfo($metadata['file'], PATHINFO_EXTENSION);

    return strtolower($file_type);
}

/**
 * Get array of Fly image size names from base name (i.e. before the “__”, see image-sizes.php)
 * Used by the fly_srcset() helper function below
 *
 * @param int $img_id
 * @param array|string $sizes
 * @return array
 */
function fly_get_sizes($img_id, $sizes) {
    if (empty($img_id) || empty($sizes) || !is_string($sizes)) {
        return '';
    }

    // Get all Fly image sizes
    // https://github.com/junaidbhura/fly-dynamic-image-resizer/blob/fd90db22d3e83e60c903244a7a42f7ec12805177/inc/helpers.php#L64-L72
    $fly_images = \JB\FlyImages\Core::get_instance();
    $fly_sizes = $fly_images->get_all_image_sizes();

    // Search array keys for matching sizes
    // https://www.php.net/manual/en/function.preg-grep.php#111673
    // Note: Use double underscores in Fly image size names to prevent
    //       false positives (e.g. “square” vs. “square_scaled”).
    $sizes = preg_grep("/^{$sizes}__/", array_keys($fly_sizes));

    if (empty($sizes)) {
        return false;
    }

    return $sizes;
}

/**
 * Generate “srcset” attribute using array of image sizes (see image-sizes.php)
 * Used by the img_tag() helper function below
 *
 * @param int $img_id
 * @param array|string $sizes
 * @return string
 */
function fly_srcset($img_id, $sizes) {
    if (empty($img_id) || empty($sizes)) {
        return '';
    }

    // If $sizes is a single string, lookup sizes and build array
    if (is_string($sizes)) {
        $sizes = fly_get_sizes($img_id, $sizes);
    }

    $srcset = [];

    foreach ($sizes as $size) {
        $img = fly_get_attachment_image_src($img_id, $size);

        if (!empty($img)) {
            $srcset[] = "{$img['src']} {$img['width']}w";
        }
    }

    return implode(',', $srcset);
}

/**
 * Convert an associative array to HTML attribute formatting
 * e.g. ['alt' => 'example'] ——> alt="example"
 * Based on https://github.com/Log1x/sage-svg/blob/master/src/SageSvg.php
 * @param array $attrs
 * @return string
 */
function buildAttributes(array $attrs = []) {
    if (empty($attrs)) {
        return '';
    }

    return ' ' . collect($attrs)->map(function ($value, $attr) {
        if (is_int($attr)) {
            return $value;
        }

        return sprintf('%s="%s"', $attr, $value);
    })->implode(' ');
}

/**
 * Generate image tag markup using array of Fly image sizes (see image-sizes.php)
 * @param int $img_id
 * @param array $attrs
 * @return string
 */
function img_tag($img_id, array $attrs = []) {
    // Check for required params
    if (empty($img_id) || empty($attrs)) {
        return '';
    }

    // Support “crop” attribute as alias for “srcset”
    if (empty($attrs['srcset']) && !empty($attrs['crop'])) {
        $attrs['srcset'] = $attrs['crop'];
    }

    if (empty($attrs['srcset'])) {
        // Unset to avoid adding an empty “srcset” attribute
        unset($attrs['srcset']);

        // If “src” also wasn’t defined we can’t build the image
        if (empty($attrs['src'])) {
            return '';
        }
    }
    else {
        // If “src” wasn’t defined, use the first “srcset” image size
        if (!array_key_exists('src', $attrs) || empty($attrs['src'])) {
            // Unless “blank_gif” was true, in which case use a blank gif
            if (array_key_exists('blank_src', $attrs) && $attrs['blank_src'] == true) {
                $attrs['src'] = blank_gif();
            }
            else {
                if (is_string($attrs['srcset'])) {
                    $img_size = current(fly_get_sizes($img_id, $attrs['srcset']));
                }
                elseif (is_array($attrs['srcset']))  {
                    $img_size = $attrs['srcset'][0];
                }
                else {
                    return '';
                }

                $fly_src = fly_get_attachment_image_src($img_id, $img_size);

                // Check for data in case the image is missing
                // (could happen locally or after content migration)
                if (empty($fly_src)) {
                    return '';
                }

                $attrs['src'] = $fly_src['src'];
            }
        }

        if (!empty($attrs['alt']) && substr($attrs['alt'], -1) != '.') {
            // Check if an author added `alt="` to the alt text and remove it
            $attrs['alt'] = str_replace('alt="', '', $attrs['alt']);

            // Escape quotes and other attributes
            $attrs['alt'] = esc_attr($attrs['alt']);

            // Add period to end of alt text to improve screen reader experience
            // https://axesslab.com/alt-texts/
            $attrs['alt'] .= '.';
        }

        // Convert “srcset” array of image sizes to an HTML string
        $attrs['srcset'] = fly_srcset($img_id, $attrs['srcset']);
    }

    // Prepend blank gif to “srcset” if “blank_src” is true
    if (array_key_exists('blank_src', $attrs) && $attrs['blank_src'] == true) {
        $attrs['srcset'] = blank_gif() . ' 1w,' . $attrs['srcset'];
        // Remove “blank_src” attribute so it doesn’t get added to the <img>
        unset($attrs['blank_src']);
    }

    // For images that will be lazy loaded via JS, set `"lazy_load" => true`
    // to replace “src” with “data-src” and “srcset” with “data-srcset”
    if (array_key_exists('lazy_load', $attrs) && $attrs['lazy_load'] == true) {
        $attrs['data-src'] = $attrs['src'];
        $attrs['data-srcset'] = $attrs['srcset'];
        unset($attrs['src']);
        unset($attrs['srcset']);
        unset($attrs['lazy_load']);
    }

    // Remove “crop” attribute so it’s not added to the <img> tag
    unset($attrs['crop']);

    // Merge attrs with defaults
    $attrs = array_merge([
        'alt' => get_img_alt($img_id) ?? '',
    ], $attrs);

    // Add attributes to image tag
    return sprintf('<img%s>', buildAttributes($attrs));
}


/**
 * Get image alt text
 * @param int $img_id
 * @return string
 */
function get_img_alt($img_id) {
    if (empty($img_id)) {
        return false;
    }

    return get_post_meta($img_id, '_wp_attachment_image_alt', TRUE) ?? '';
}
