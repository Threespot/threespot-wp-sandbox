<?php
namespace App;

/**
 * Convert bytes to human-friendly notation
 * @param string $size
 * @return string
 */
function bytes_to_human_size($size) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  $power = $size > 0 ? floor(log($size, 1024)) : 0;
  $formatted = number_format($size / pow(1024, $power), 1, '.', ',') . $units[$power];
  return $formatted;
}

/**
 * Strip <p> tags
 * Used when ACF wysiwyg fields are required for a heading tag
 * @param string $markup
 * @return string
 */
function strip_p_tags($markup) {
    return preg_replace('/<\/?p>/', '', $markup);
}

/**
 * Check if URL is external
 * https://stackoverflow.com/a/22964930/673457
 * @param string $url
 * @return boolean
 */
function is_external($url) {
  $home_url = parse_url(home_url());
  $test_url = parse_url($url);

  // Ignore relative URLs
  if (empty($test_url['host'])) {
    return false;
  }

  // Check if hosts are equal
  if (strcasecmp($test_url['host'], $home_url['host']) === 0) {
    return false;
  }

  // Check if the url host is a subdomain
  return strrpos(strtolower($test_url['host']), $home_url['host']) !== strlen($test_url['host']) - strlen($home_url['host']);
}

/**
 * Append non-breaking space between last two words to prevent orphans
 * https://css-tricks.com/snippets/php/append-non-breaking-space-between-last-two-words/
 * @param string $text
 * @param int $minWords
 * @param int $maxCharLength
 * @return string
 */
function nowrap($text, $minWords = 2, $maxCharLength = 12) {
    // Remove extra spaces since they would get added to the word array
    $text = preg_replace('/\s+/', ' ', trim($text));

    $words = explode(' ', $text);

    // Make sure there are enough words
    if (count($words) < $minWords) {
        return $text;
    }

    // Second-to-last word
    $penultimate_word = $words[count($words) - 2];

    $last_word = $words[count($words) - 1];
    // Ignore “</p>” tags from last word (could be added by ACF textarea field)
    $last_word = str_replace('</p>', '', $last_word);
    // Ignore ending periods as well
    $last_word = rtrim($last_word, '.');

    // Do nothing if last two words are longer than $maxCharLength
    if (strlen($penultimate_word.$last_word) > $maxCharLength) {
        return $text;
    }

    // Append “&nbsp;” and last word to second-to-last word
    $words[count($words) - 2] .= '&nbsp;' . $words[count($words) - 1];

    // Remove last word from array now that we’ve added it to the second-to-last word
    array_pop($words);

    // Convert array back into a string
    return implode(' ', $words);
}

/**
 * Trim excerpt using custom word count
 * @param string $excerpt
 * @param int $word_count
 * @return string
 */
function trim_excerpt($excerpt, $word_count = 25) {
    if (!is_string($excerpt) || empty($excerpt)) {
        return '';
    }

    // Trim whitespace from the beginnging and end of string
    $excerpt = trim($excerpt);

    // Remove “…” from the end of the string if present
    $excerpt = rtrim($excerpt, '…');

    // Check if excerpt requires truncating
    // Note: The `0` in str_word_count() returns the number of words found
    //       https://www.php.net/manual/en/function.str-word-count.php
    if (str_word_count($excerpt, 0) > $word_count) {
        // Split the string into words, limiting to $word_count
        $words = preg_split('/\s+/', $excerpt, $word_count + 1);
        // Remove the last item since it contains all remaining words
        array_pop($words);
        // Convert array back to string
        $excerpt = implode(' ', $words);
    }

    // Append “…” if excerpt ends mid-sentence
    return preg_match('/[.!?”]$/', $excerpt) ? $excerpt : $excerpt . '…';
}

/**
 * Clean up HTML markup from WYSIWYG editors
 *
 * Removes TinyMCE artifacts, unwanted span tags, and inline styles
 * Works with multi-paragraph and multiline content
 *
 * @param string $content The HTML content to clean
 * @return string Cleaned HTML content
 */
function clean_wysiwyg_markup($content) {
    // Make sure we're working with the content as a single string with preserved newlines
    $content = trim($content);

    // Step 1: Remove spans with data-mce-* attributes INCLUDING their contents
    $content = preg_replace('/<span[^>]*data-mce-[^>]*>.*?<\/span>/s', '', $content);

    // Step 2: Remove all other span tags but KEEP their contents
    $content = preg_replace('/<span[^>]*>(.*?)<\/span>/s', '$1', $content);

    // Step 3: Remove all inline style attributes from any remaining tags
    $content = preg_replace('/\s+style="[^"]*"/i', '', $content);

    // Step 4: Remove empty tags
    $content = preg_replace('/<([a-z][a-z0-9]*)\b[^>]*>\s*<\/\1>/i', '', $content);

    // Return trimmed content
    return trim($content);
}

/**
 * Strip opening and closing quotes from text or HTML.
 * This is helpful for avoiding duplicate quotes when we’re applying
 * them via code, just in case the author also adds them in the CMS.
 *
 * Handles all common forms of quotes:
 * - Straight quotes (")
 * - Smart/curly quotes ("")
 * - HTML entities (&quot;, &ldquo;, &rdquo;)
 * - Numeric entities (&#8220;, &#8221;)
 * - Hex entities (&#x201C;, &#x201D;)
 *
 * @param string $text The HTML content to process
 * @return string Text with opening and closing quotes removed
 */
function strip_quotes($text) {
    $text = clean_wysiwyg_markup($text);

    // Handle potential regex errors
    $result = @preg_replace([
        // Start pattern: Match opening HTML tag(s) followed by opening quote
        '/^(\s*(?:<[^>]*>\s*)*)(?:"|“|&quot;|&ldquo;|&#8220;|&#x201C;)/us',

        // End pattern: Match closing quote followed by closing HTML tag(s)
        '/(?:"|”|&quot;|&rdquo;|&#8221;|&#x201D;)(\s*(?:<\/[^>]*>\s*)*)$/us'
    ], ['\1', '\1'], $text);

    // Return original text if regex fails
    return $result !== null ? $result : $text;
}

/**
 * Convert characters to HTML numeric string references (e.g. 'a' => '&#97;')
 * This should be used to obfuscate all email addressed (e.g. mailto links)
 * @link https://stackoverflow.com/a/32997549/
 * @link https://www.php.net/manual/en/function.mb-encode-numericentity.php
 * @param string $string
 * @return string
 */
function obfuscate($string) {
    if (empty($string)) {
        return $string;
    }

    return mb_encode_numericentity($string, array(0x000000, 0x10ffff, 0, 0xffffff), 'UTF-8');

    // NOTE: Here’s an alternate method that seems to be as performant
    // https://stackoverflow.com/a/42124616/
    // return preg_replace_callback('/./', function($char) {
    //     return '&#'.ord($char[0]).';';
    // }, $string);
}

/**
 * Return list of post types, including pages, posts, and any CPTs
 * @return array
 */
function get_custom_post_types() {
    // Get array of custom post type names, excluding built-in public post types
    // (e.g. page, post, attachment, revision, nav_menu_item)
    $post_types = get_post_types(['public' => true, '_builtin' => false]);

    // Add back pages and posts
    $post_types[] = 'page';
    $post_types[] = 'post';

    // Remove if-so trigger post type
    unset($post_types['ifso_triggers']);

    return $post_types;
}
