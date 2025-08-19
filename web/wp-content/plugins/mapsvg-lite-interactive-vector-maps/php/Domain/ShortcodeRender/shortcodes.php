<?php
if (!class_exists('WP_EX_PAGE_ON_THE_FLY')) {
	class WP_EX_PAGE_ON_THE_FLY
	{

		public $slug = '';
		public $args = array();
		public $post_content = '';
		/**
		 * __construct
		 * @param array $args post to create on the fly
		 * @author Ohad Raz
		 *
		 */
		function __construct($args)
		{
			add_filter('the_posts', array($this, 'fly_page'));
			$this->args = $args;
			$this->slug = $args['slug'];
			$this->post_content = $args['post_content'];
		}

		/**
		 * function that catches the request and returns the page as if it was retrieved from the database
		 * @param  array $posts
		 * @return array
		 * @author Ohad Raz
		 */
		public function fly_page($posts)
		{
			global $wp, $wp_query;
			$page_slug = $this->slug;

			//check if user is requesting our fake page
			if (count($posts) == 0 && (strtolower($wp->request) == $page_slug || (isset($wp->query_vars['page_id']) && ($wp->query_vars['page_id'] == $page_slug)))) {

				//create a fake post
				$post = new stdClass;
				$post->post_author = 1;
				$post->post_name = $page_slug;
				$post->guid = get_bloginfo('wpurl' . '/' . $page_slug);
				$post->post_title = 'page title';
				//put your custom content here
				$post->post_content = $this->post_content; //"Fake Content";
				//just needs to be a number - negatives are fine
				$post->ID = -42;
				$post->post_status = 'static';
				$post->comment_status = 'closed';
				$post->ping_status = 'closed';
				$post->comment_count = 0;
				//dates may need to be overwritten if you have a "recent posts" widget or similar - set to whatever you want
				$post->post_date = current_time('mysql');
				$post->post_date_gmt = current_time('mysql', 1);

				$post = (object) array_merge((array) $post, (array) $this->args);
				$posts = NULL;
				$posts[] = $post;

				$wp_query->is_page = true;
				$wp_query->is_singular = true;
				$wp_query->is_home = false;
				$wp_query->is_archive = false;
				$wp_query->is_category = false;
				unset($wp_query->query["error"]);
				$wp_query->query_vars["error"] = "";
				$wp_query->is_404 = false;
			}

			return $posts;
		}
	} //end class
} //end if


// blank template


if (! function_exists('blank_slate_bootstrap')) {

	/**
	 * Initialize the plugin.
	 */
	function blank_slate_bootstrap()
	{

		load_plugin_textdomain('blank-slate', false, __DIR__ . '/languages');

		// Register the blank slate template
		blank_slate_add_template(
			'blank-slate-template.php',
			esc_html__('Blank Slate', 'mapsvg-lite')
			
		);

		// Add our template(s) to the dropdown in the admin
		add_filter(
			'theme_page_templates',
			function (array $templates) {
				return array_merge($templates, blank_slate_get_templates());
			}
		);

		// Ensure our template is loaded on the front end
		add_filter(
			'template_include',
			function ($template) {

				if (is_singular()) {

					$assigned_template = get_post_meta(get_the_ID(), '_wp_page_template', true);

					if (blank_slate_get_template($assigned_template)) {

						if (file_exists($assigned_template)) {
							return $assigned_template;
						}

						//$file = wp_normalize_path( plugin_dir_path( __FILE__ ) . '/templates/' . $assigned_template );
						$file = wp_normalize_path(plugin_dir_path(__FILE__) .  $assigned_template);

						if (file_exists($file)) {
							return $file;
						}
					}
				}

				return $template;
			}
		);
	}
}

if (! function_exists('blank_slate_get_templates')) {

	/**
	 * Get all registered templates.
	 *
	 * @return array
	 */
	function blank_slate_get_templates()
	{
		return (array) apply_filters('blank_slate_templates', array());
	}
}

if (! function_exists('blank_slate_get_template')) {

	/**
	 * Get a registered template.
	 *
	 * @param string $file Template file/path
	 *
	 * @return string|null
	 */
	function blank_slate_get_template($file)
	{
		$templates = blank_slate_get_templates();

		return isset($templates[$file]) ? $templates[$file] : null;
	}
}

if (! function_exists('blank_slate_add_template')) {

	/**
	 * Register a new template.
	 *
	 * @param string $file Template file/path
	 * @param string $label Label for the template
	 */
	function blank_slate_add_template($file, $label)
	{
		add_filter(
			'blank_slate_templates',
			function (array $templates) use ($file, $label) {
				$templates[$file] = $label;

				return $templates;
			}
		);
	}
}

add_action('plugins_loaded', 'blank_slate_bootstrap');


function mapsvg_blank_template()
{

	include("blank-template.php");
	exit;
}

function mapsvg_add_jquery()
{
	//	wp_enqueue_script('mapsvg', MAPSVG_PLUGIN_URL . 'js/mapsvg/globals.js', array('jquery'), (MAPSVG_RAND?rand():''));
	//wp_enqueue_script('mapsvg-resize', MAPSVG_PLUGIN_URL . 'js/mapsvg/resize.js', array('jquery'), (MAPSVG_RAND?rand():''));
}

if (isset($_REQUEST['mapsvg_shortcode_inline'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
	// Properly unslash and sanitize the shortcode
	$shortcode = sanitize_text_field(wp_unslash($_REQUEST['mapsvg_shortcode_inline']));  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

	// Optional: Additional validation if needed
	if (empty($shortcode)) {
		wp_die('Invalid shortcode parameter');
	}

	echo do_shortcode($shortcode);
	exit;
}

if (isset($_GET['mapsvg_shortcode'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

	add_action('wp_enqueue_scripts', 'mapsvg_add_jquery');
	add_action('template_redirect', 'mapsvg_blank_template');

	// Properly sanitize and unslash the shortcode parameter
	$shortcode = sanitize_text_field(wp_unslash($_GET['mapsvg_shortcode']));  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing

	// Optional: Add validation
	if (empty($shortcode)) {
		wp_die('Invalid shortcode parameter');
	}

	$args = array(
		'slug' => 'mapsvg_sc',
		'post_title' => '',
		'post_content' => $shortcode
	);

	// Add all CF7 parameters from shortodes
	add_filter('shortcode_atts_wpcf7', 'custom_shortcode_atts_wpcf7_filter', 10, 3);
	function custom_shortcode_atts_wpcf7_filter($out, $pairs, $atts)
	{
		//		$my_attr = 'field-one';
		//		if ( isset( $atts[$my_attr] ) ) {
		//			$out[$my_attr] = $atts[$my_attr];
		//		}
		//		$my_attr = 'field-two';
		//		if ( isset( $atts[$my_attr] ) ) {
		//			$out[$my_attr] = $atts[$my_attr];
		//		}
		foreach ($atts as $key => $val) {
			$out[$key] = $atts[$key];
		}
		return $out;
	}

	new WP_EX_PAGE_ON_THE_FLY($args);
}


if (isset($_GET['mapsvg_embed_post'])) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
	add_action('wp_enqueue_scripts', 'mapsvg_add_jquery');
	add_action('template_redirect', 'mapsvg_blank_template');

	$post_id = (int)$_GET['mapsvg_embed_post'];  // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing
	$post = get_post($post_id, ARRAY_A);
	$post['slug'] = 'mapsvg_sc';

	new WP_EX_PAGE_ON_THE_FLY($post);
}
