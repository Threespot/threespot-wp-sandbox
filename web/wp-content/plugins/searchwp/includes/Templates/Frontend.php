<?php

namespace SearchWP\Templates;

use SearchWP\Forms\Frontend as SearchFormsFrontend;
use SearchWP\Forms\Storage as SearchWPFormsStorage;
use SearchWP\Settings;
use SearchWP\Templates\Storage;
use SearchWP\Support\Arr;
use SearchWP\Utils;

/**
 * Display search results page on the frontend.
 *
 * @since 4.3.6
 */
class Frontend {

    /**
     * Init.
     *
     * @since 4.3.6
     */
    public function init() {

        $this->hooks();
    }

    /**
     * Hooks.
     *
     * @since 4.3.6
     */
    public function hooks() {

        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'assets' ] );

        // Add shortcode for [searchwp_template].
        add_shortcode( 'searchwp_template', [ __CLASS__, 'render_shortcode' ] );

		add_action( 'wp_ajax_swp_template_load_more', [ __CLASS__, 'ajax_load_more' ] );
		add_action( 'wp_ajax_nopriv_swp_template_load_more', [ __CLASS__, 'ajax_load_more' ] );

        // Register Gutenberg block.
        self::register_gutenberg_block();

        if ( version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ) {
            add_filter( 'block_categories_all', [ __CLASS__, 'register_gutenberg_block_category' ] );
        } else {
            add_filter( 'block_categories', [ __CLASS__, 'register_gutenberg_block_category' ] );
        }

        $form       = self::get_form_from_request();
        $input_name = isset( $form['input_name'] ) ? $form['input_name'] : 's';
        if ( $input_name !== 'swps' ) {
            return;
        }

        if ( empty( $form['target-page'] ) ) {
            add_filter( 'template_include', [ __CLASS__, 'render_swp_template' ], PHP_INT_MAX );
        }
    }

    /**
     * Load frontend assets.
     *
     * @since 4.3.6
     */
    public static function assets() {

	    if ( ! isset( $_GET['swps'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		    return;
	    }

		// If WP is in script debug, or we pass ?script_debug in a URL - set debug to true.
		$debug = Utils::get_debug_assets_suffix();

        wp_enqueue_style(
            'searchwp-results-page',
            SEARCHWP_PLUGIN_URL . "assets/css/frontend/results-page{$debug}.css",
            [],
            SEARCHWP_VERSION
        );

		wp_enqueue_script(
			'searchwp-templates',
			SEARCHWP_PLUGIN_URL . "assets/js/frontend/results-templates{$debug}.js",
			[ 'jquery' ],
			SEARCHWP_VERSION,
			true
		);

		wp_localize_script(
			'searchwp-templates',
			'searchwpTemplates',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			]
		);
	}

	/**
	 * Shortcode callback for [searchwp_template].
	 *
	 * @since 4.4.0
	 *
	 * @param array $atts Shortcode attributes.
	 *
	 * @return string
	 */
	public static function render_shortcode( $atts ) {

		$form = self::get_form_from_request();

		if ( empty( $form ) ) {
			return '';
		}

		$atts = shortcode_atts(
			[
				'id'     => '',
				'engine' => '',
			],
			$atts,
			'searchwp_template'
		);

		return self::render(
			[
				'id'     => ! empty( $atts['id'] ) ? absint( $atts['id'] ) : '',
				'engine' => ! empty( $atts['engine'] ) ? sanitize_text_field( $atts['engine'] ) : '',
			]
		);
	}

	/**
	 * Renders the template used in the SearchWP Default Results Page.
	 *
	 * @since 4.3.6
	 *
	 * @updated 4.4.0
	 *
	 * @param string $template The path of the template to include.
	 *
	 * @return string
	 */
	public static function render_swp_template( $template ) {

		if ( ! isset( $_GET['swps'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return $template;
		}

		echo self::render( [ 'is_page' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		return '';
	}

    /**
     * Render page.
     *
     * @since 4.3.6
	 *
	 * @updated 4.4.0
     *
     * @param array $args Optional. Array of arguments.
	 *
     * @return string The rendered content.
     */
    public static function render( $args = [] ) {

		// Make sure the form contents don't get indexed.
		if ( did_action( 'searchwp\indexer\batch' ) ) {
			return '';
		}

		static $is_rendering = false;

		// Prevent nested renderings.
		// The page with the template(s) can also be included in the search results.
		// When the excerpt is generated for the results, it will call this function again causing an infinite loop.
		if ( $is_rendering ) {
			return '';
		}

        $args = wp_parse_args(
            $args,
            [
                'id'      => '',
                'engine'  => '',
                'is_page' => false,
            ]
        );

        $form = self::get_form_from_request();

		if ( empty( $form ) ) {
			return '';
		}

        // Get template ID - priority: direct args > form settings > default.
        $template_id = self::get_template_id( $args, $form );

        // Get engine - priority: direct args > form settings > default.
        $engine = self::get_engine_name( $args, $form );

		// Retrieve the settings for the template.
        $settings = Storage::get_template( $template_id );

		if ( empty( $settings ) ) {
			return '';
		}

        // Retrieve applicable query parameters.
        $search_query = isset( $_GET['swps'] ) ? sanitize_text_field( wp_unslash( $_GET['swps'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $search_query === null ) {
			return '';
		}

		$search_page = isset( $_GET['swppg'] ) ? absint( $_GET['swppg'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$is_rendering = true;

        ob_start();

		// Render the template.
		self::render_page_header( $form, $search_query, $args );

        self::render_content( $settings, $engine, $search_query, $search_page );

        self::render_page_footer( $args );

		$is_rendering = false;

        return ob_get_clean();
    }

    /**
     * Render the search results content.
     *
     * @since 4.3.6
     *
	 * @param array  $settings     Search Results Page settings.
     * @param string $engine       The search engine to use.
     * @param string $search_query The search query.
     * @param int    $search_page  The current page number.
     */
    private static function render_content( $settings, $engine, $search_query, $search_page ) {

		// Generate a unique ID for the search results container.
		$search_results_container_id = 'swp-search-results-' . uniqid();

        // Perform the search.
        $per_page = absint( $settings['swp-results-per-page'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$search_args = [
			'engine' => $engine, // The Engine name.
			'fields' => 'all',   // Load proper native objects of each result.
			'page'   => $search_page,
		];

		if ( ! empty( $per_page ) ) {
			$search_args['per_page'] = $per_page;
		}

		$searchwp_query = new \SearchWP\Query( $search_query, $search_args );

		$search_results = $searchwp_query->get_results();

        if ( ! empty( $search_results ) ) :
			echo self::get_display_styles( $settings['id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			?>
			<div class="<?php echo esc_attr( self::get_container_classes( $settings ) ); ?>" id="<?php echo esc_attr( $search_results_container_id ); ?>">
				<?php

				echo self::get_results( $search_results, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

				?>
			</div><!-- End of .swp-search-results -->
			<?php

			if ( $settings['swp-load-more-enabled'] && $search_page < $searchwp_query->max_num_pages ) {

				self::render_load_more_button( $search_query, $search_page, $engine, $settings, $search_results_container_id );
			} else {

				self::render_pagination( $searchwp_query, $settings, $search_page );
			}
        else :
	?>
            <p><?php esc_html_e( 'No results found, please search again.', 'searchwp' ); ?></p>
			<?php self::render_promoted_ad( $settings ); ?>
        <?php
		endif;
    }

	/**
	 * Generates the search results items.
	 *
	 * @since 4.4.0
	 *
	 * @param array $search_results Search results.
	 * @param array $settings       Search Results Template settings.
	 *
	 * @return string
	 */
	private static function get_results( $search_results, $settings ) {

		ob_start();

		/**
		 * Initiate Metrics click tracking.
		 */
		do_action( 'searchwp_metrics_click_tracking_start' );

		$ads_position = absint( $settings['swp-promoted-ads-position'] );

		// Insert the promoted ad at the specified position or after the last result.
		array_splice( $search_results, $ads_position, 0, [ 'swp_promoted_ads' ] );

		foreach ( $search_results as $search_result ) :

			if ( $search_result === 'swp_promoted_ads' ) {
				self::render_promoted_ad( $settings );
			} else {
				self::render_result( $search_result, $settings );
			}

		endforeach;

		/**
		 * Stop Metrics click tracking.
		 */
		do_action( 'searchwp_metrics_click_tracking_stop' );

		return ob_get_clean();
	}

	/**
	 * Render a single search result item.
	 *
	 * @since 4.5.0
	 *
	 * @param array $search_result The search result data to be rendered.
	 * @param array $settings      Search Results Template settings.
	 *
	 * @return void
	 */
	private static function render_result( $search_result, $settings ) {

		$display_data = self::get_display_data( $search_result );
		?>
		<article class="swp-result-item post-<?php echo absint( $display_data['id'] ); ?> post type-<?php echo esc_attr( $display_data['type'] ); ?> status-publish format-standard hentry category-uncategorized entry">
			<?php if ( ! empty( $display_data['image_html'] ) && ! empty( $settings['swp-image-size'] ) && $settings['swp-image-size'] !== 'none' ) : ?>
				<div class="swp-result-item--img-container">
					<div class="swp-result-item--img">
						<?php echo wp_kses_post( $display_data['image_html'] ); ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="swp-result-item--info-container">
				<h2 class="entry-title">
					<a href="<?php echo esc_url( $display_data['permalink'] ); ?>">
						<?php echo wp_kses_post( $display_data['title'] ); ?>
					</a>
				</h2>
				<?php if ( ! empty( $settings['swp-description-enabled'] ) ) : ?>
					<p class="swp-result-item--desc">
						<?php echo wp_kses_post( $display_data['content'] ); ?>
					</p>
				<?php endif; ?>

				<?php if ( in_array( $display_data['type'], [ 'product', 'download' ], true ) ) : ?>
					<p class="swp-result-item--price">
						<?php echo wp_kses_post( $display_data['type'] === 'product' ? self::get_product_price_html( $display_data['id'] ) : self::get_download_price_html( $display_data['id'] ) ); ?>
					</p>
				<?php endif; ?>

				<?php if ( ! empty( $settings['swp-button-enabled'] ) ) : ?>
					<a href="<?php echo esc_url( $display_data['permalink'] ); ?>" class="swp-result-item--button">
						<?php echo ! empty( $settings['swp-button-label'] ) ? esc_html( $settings['swp-button-label'] ) : esc_html__( 'Read More', 'searchwp' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}

	/**
	 * Render the promoted ad.
	 *
	 * @since 4.5.0
	 *
	 * @param array $settings Search Results Template settings.
	 *
	 * @return void
	 */
	private static function render_promoted_ad( $settings ) {

		if ( empty( $settings['swp-promoted-ads-enabled'] ) || empty( $settings['swp-promoted-ads-content'] ) ) {
			return;
		}
		?>
		<div class="swp-promoted-ad swp-result-item">
			<?php echo wp_kses_post( $settings['swp-promoted-ads-content'] ); ?>
		</div>
		<?php
	}

	/**
	 * Render search results pagination.
	 *
	 * @since 4.4.0
	 *
	 * @param \SearchWP\Query $searchwp_query    SearchWP query object.
	 * @param array           $settings          Search Results Page settings.
	 * @param string          $search_pagination Search pagination HTML.
	 */
	private static function render_pagination( $searchwp_query, $settings, $search_page ) {

		$display_labels = empty( $settings['swp-pagination-style'] );

		$pagination_args = [
			'format'    => '?swppg=%#%',
			'current'   => $search_page,
			'total'     => $searchwp_query->max_num_pages,
			'prev_text' => ! empty( $settings['swp-pagination-prev'] ) && $display_labels ? $settings['swp-pagination-prev'] : '&larr;',
			'next_text' => ! empty( $settings['swp-pagination-next'] ) && $display_labels ? $settings['swp-pagination-next'] : '&rarr;',
		];

		$search_pagination = paginate_links( $pagination_args );

		if ( $searchwp_query->max_num_pages > 1 ) :
			?>
			<div class="navigation pagination" role="navigation">
				<h2 class="screen-reader-text"><?php esc_html_e( 'Results navigation', 'searchwp' ); ?></h2>
				<div class="<?php echo esc_attr( self::get_pagination_classes( $settings ) ); ?>"><?php echo wp_kses_post( $search_pagination ); ?></div>
			</div>
			<?php
		endif;
	}

	/**
	 * Render load more button.
	 *
	 * @since 4.4.0
	 *
	 * @param string $search_query                The search query.
	 * @param int    $search_page                 Current search page number.
	 * @param string $engine                      The search engine to use.
	 * @param array  $settings                    Search Results Page settings.
	 * @param string $search_results_container_id The search results container ID.
	 */
	private static function render_load_more_button( $search_query, $search_page, $engine, $settings, $search_results_container_id ) {

		$button_label      = ! empty( $settings['swp-load-more-label'] ) ? $settings['swp-load-more-label'] : __( 'Load More', 'searchwp' );
		$button_bg_color   = ! empty( $settings['swp-load-more-bg-color'] ) ? $settings['swp-load-more-bg-color'] : '';
		$button_font_color = ! empty( $settings['swp-load-more-font-color'] ) ? $settings['swp-load-more-font-color'] : '';
		$button_font_size  = ! empty( $settings['swp-load-more-font-size'] ) ? $settings['swp-load-more-font-size'] : '';

		$css_array = [];
		if ( ! empty( $button_bg_color ) ) {
			Arr::set( $css_array, 'background-color', esc_html( $button_bg_color ), '/' );
		}
		if ( ! empty( $button_font_color ) ) {
			Arr::set( $css_array, 'color', esc_html( $button_font_color ), '/' );
		}
		if ( ! empty( $button_font_size ) ) {
			Arr::set( $css_array, 'font-size', absint( $button_font_size ) . 'px', '/' );
		}
		$button_styles = self::css_array_to_css( $css_array );
		?>
		<div class="swp-load-more">
			<button
				class="swp-button swp-load-more-button"
				data-query="<?php echo esc_attr( $search_query ); ?>"
				data-page="<?php echo absint( $search_page + 1 ); ?>"
				data-engine="<?php echo esc_attr( $engine ); ?>"
				data-template="<?php echo esc_attr( $settings['id'] ); ?>"
				data-container-id="<?php echo esc_attr( $search_results_container_id ); ?>"
				style="<?php echo esc_attr( $button_styles ); ?>"
			>
				<span class="swp-load-more-spinner swp-hidden">
					<span class="loading-spinner"></span>
				</span>
				<span class="swp-load-more-label"><?php echo esc_html( $button_label ); ?></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Handle AJAX request for loading more results.
	 *
	 * @since 4.4.0
	 *
	 * @return void
	 */
	public static function ajax_load_more() {

		// Check if required parameters exist.
		if ( ! isset( $_POST['query'] ) || ! isset( $_POST['page'] ) || ! isset( $_POST['engine'] ) || ! isset( $_POST['template'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			wp_send_json_error( [ 'message' => __( 'Missing required parameters', 'searchwp' ) ] );

			return;
		}

		// Sanitize and validate parameters.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$search_query = sanitize_text_field( wp_unslash( $_POST['query'] ) );
		$search_page  = absint( $_POST['page'] );
		$engine       = sanitize_text_field( wp_unslash( $_POST['engine'] ) );
		$template_id  = absint( $_POST['template'] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate template ID.
		if ( empty( $template_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid template ID', 'searchwp' ) ] );

			return;
		}

		$settings = Storage::get_template( $template_id );

		// Check if template exists.
		if ( empty( $settings ) ) {
			wp_send_json_error( [ 'message' => __( 'Template not found', 'searchwp' ) ] );

			return;
		}

		$searchwp_query = new \SearchWP\Query(
			$search_query,
			[
				'engine'   => $engine,
				'page'     => $search_page,
				'fields'   => 'all',
				'per_page' => $settings['swp-results-per-page'],
			]
		);

		$search_results = $searchwp_query->get_results();

		$html = self::get_results( $search_results, $settings );

		// Return JSON response with the expected structure.
		wp_send_json_success(
			[
				'html'      => $html,
				'page'      => $search_page + 1,
				'max_pages' => $searchwp_query->max_num_pages,
			]
		);
	}

	/**
	 * Get the page header.
	 *
	 * @since 4.4.0
	 *
	 * @param array  $form         Search form settings.
	 * @param string $search_query Search query.
	 * @param array  $args         Render arguments.
	 */
	private static function render_page_header( $form, $search_query, $args ) {

		if ( empty( $args['is_page'] ) ) {
			return;
		}

		if ( wp_is_block_theme() ) {
			?>
			<!DOCTYPE html>
			<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>" />
				<?php wp_head(); ?>
			</head>
			<body <?php body_class(); ?>>
			<?php wp_body_open(); ?>
			<div class="wp-site-blocks">
			<?php block_template_part( 'header' ); ?>
			<main class="wp-block-group swp-rp-main">
			<header class="swp-rp-page-header">
				<?php
				// Add a search form if enabled in setting.
				if ( ! empty( $form['id'] ) && ( ! isset( $form['template-include-search-form'] ) || $form['template-include-search-form'] ) ) {
					echo SearchFormsFrontend::render( [ 'id' => $form['id'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<h1 class="page-title">
					<?php
					echo esc_html(
						sprintf(
						/* translators: %s is the search term. */
							__( 'Search results for: %s', 'searchwp' ),
							$search_query
						)
					);
					?>
				</h1>
			</header>
			<?php

		} else {
			get_header( 'searchwp' );
			?>
			<div id="content" class="site-content">
			<div id="primary" class="content-area">
			<main id="main" class="site-main swp-rp-main">
			<header class="swp-rp-page-header">
				<?php
				// Add a search form if enabled in setting.
				if ( ! empty( $form['id'] ) && ( ! isset( $form['template-include-search-form'] ) || $form['template-include-search-form'] ) ) {
					echo SearchFormsFrontend::render( [ 'id' => $form['id'] ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>

				<h1 class="page-title">
					<?php
					echo esc_html(
						sprintf(
						/* translators: %s is the search term. */
							__( 'Search results for: %s', 'searchwp' ),
							$search_query
						)
					);
					?>
				</h1>
			</header>
			<?php
		}
	}

	/**
	 * Render the page footer.
	 *
	 * @param array $args Render arguments.
	 *
	 * @since 4.4.0
	 */
	private static function render_page_footer( $args ) {

		if ( empty( $args['is_page'] ) ) {
			return;
		}

		if ( wp_is_block_theme() ) {
			?>
				</main>
					<footer class="wp-block-template-part">
						<?php block_template_part( 'footer' ); ?>
					</footer>
				<div><!-- End of .wp-site-blocks -->

				<?php wp_footer(); ?>
				</body>
			</html>
			<?php
		} else {
			?>
					</main>
				</div><!-- End of #primary -->
			</div><!-- End of #content -->
			<?php
			get_footer( 'searchwp' );
		}
	}

    /**
     * Get the result data to display in the template.
     *
     * @since 4.3.6
     *
     * @param \WP_Post|\WP_User|\WP_Term|mixed $result Result object.
     *
     * @return array
     */
	private static function get_display_data( $result ) {

		// During a multisite search, results can be from multiple blogs.
		// If the result is from a different blog than the current one, we need to switch to that blog before fetching the result's data.
		$switched_blog = self::maybe_switch_blog( $result );

		// Get the native entry for the result.
		$result = self::maybe_get_native_entry( $result );

		if ( $result instanceof \WP_Post ) {
			$data = [
				'id'         => absint( $result->ID ),
				'type'       => get_post_type( $result ),
				'title'      => get_the_title( $result ),
				'permalink'  => get_the_permalink( $result ),
				'image_html' => get_the_post_thumbnail( $result ),
				'content'    => get_the_excerpt( $result ),
			];
		}

		if ( $result instanceof \WP_User ) {
			$data = [
				'id'         => absint( $result->ID ),
				'type'       => 'user',
				'title'      => $result->data->display_name,
				'permalink'  => get_author_posts_url( $result->data->ID ),
				'image_html' => get_avatar( $result->data->ID ),
				'content'    => get_the_author_meta( 'description', $result->data->ID ),
			];
		}

		if ( $result instanceof \WP_Term ) {
			$data = [
				'id'         => absint( $result->term_id ),
				'type'       => 'taxonomy-term',
				'title'      => $result->name,
				'permalink'  => get_term_link( $result->term_id, $result->taxonomy ),
				'image_html' => '',
				'content'    => $result->description,
			];
		}

		$defaults = [
			'id'         => 0,
			'type'       => 'unknown',
			'title'      => '',
			'permalink'  => '',
			'image_html' => '',
			'content'    => '',
		];

		/**
		 * Filter the data to display in the search results template.
		 *
		 * @since 4.3.6
		 *
		 * @param array $data   The data to display in the search results template.
		 * @param mixed $result The result object.
		 */
		$data = apply_filters( 'searchwp\results\entry\data', empty( $data ) ? $defaults : $data, $result );

		if ( $switched_blog ) {
			restore_current_blog();
		}

		// Make sure that default array structure is preserved.
		return is_array( $data ) ? array_merge( $defaults, $data ) : $defaults;
	}

	/**
	 * Switch to the blog of the result.
	 *
	 * @since 4.3.16
	 *
	 * @param mixed $result Result object.
	 */
	private static function maybe_switch_blog( $result ) {

		if (
			$result instanceof \stdClass &&
			property_exists( $result, 'site' ) &&
			absint( $result->site ) !== get_current_blog_id()
		) {
			switch_to_blog( absint( $result->site ) );

			return true;
		}

		return false;
	}

	/**
	 * Get the native entry of the result.
	 *
	 * @since 4.3.16
	 *
	 * @param mixed $result Result object.
	 *
	 * @return \WP_Post|\WP_User|\WP_Term|mixed
	 */
	private static function maybe_get_native_entry( $result ) {

		if ( $result instanceof \stdClass && property_exists( $result, 'source' ) ) {

			$id = absint( $result->id );

			if ( strpos( $result->source, 'post' . SEARCHWP_SEPARATOR ) === 0 ) {
				$result = get_post( $id );
			} elseif ( strpos( $result->source, 'taxonomy' . SEARCHWP_SEPARATOR ) === 0 ) {
				$result = get_term( $id );
			} elseif ( $result->source === 'user' ) {
				$result = get_user_by( 'ID', $id );
			}
		} elseif ( $result instanceof \SearchWP\Entry ) {

			$result = $result->native();
		}

		return $result;
	}

	/**
	 * Get dynamic inline styles based on current settings.
	 *
	 * @param int $template_id The template ID.
	 *
	 * @since 4.3.6
	 *
	 * @updated 4.4.0
	 */
	private static function get_display_styles( $template_id = 0 ) {

		$settings = Storage::get_template( $template_id );

		$el = ".swp-results-template-{$template_id} ";

		$css_array = [];

		$title_selector = $el . '.swp-result-item .entry-title';
		if ( ! empty( $settings['swp-title-color'] ) ) {
			Arr::set( $css_array, "{$title_selector}/color", esc_html( $settings['swp-title-color'] ), '/' );
		}
		if ( ! empty( $settings['swp-title-font-size'] ) ) {
			Arr::set( $css_array, "{$title_selector}/font-size", absint( $settings['swp-title-font-size'] ) . 'px', '/' );
		}

		$price_selector = $el . '.swp-result-item .swp-result-item--price, .swp-result-item .swp-result-item--price *';
		if ( ! empty( $settings['swp-price-color'] ) ) {
			Arr::set( $css_array, "{$price_selector}/color", esc_html( $settings['swp-price-color'] ), '/' );
		}
		if ( ! empty( $settings['swp-price-font-size'] ) ) {
			Arr::set( $css_array, "{$price_selector}/font-size", absint( $settings['swp-price-font-size'] ) . 'px', '/' );
		}

		$button_selector = $el . '.swp-result-item a.swp-result-item--button';
		if ( ! empty( $settings['swp-button-font-color'] ) ) {
			Arr::set( $css_array, "{$button_selector}/color", esc_html( $settings['swp-button-font-color'] ), '/' );
		}
		if ( ! empty( $settings['swp-button-bg-color'] ) ) {
			Arr::set( $css_array, "{$button_selector}/background-color", esc_html( $settings['swp-button-bg-color'] ), '/' );
		}
		if ( ! empty( $settings['swp-button-font-size'] ) ) {
			Arr::set( $css_array, "{$button_selector}/font-size", absint( $settings['swp-button-font-size'] ) . 'px', '/' );
		}

		// Return the style tag directly with escaped CSS content.
		return '<style>' . esc_html( self::css_array_to_css( $css_array ) ) . '</style>';
	}

    /**
     * Get search results page container classes.
     *
     * @since 4.3.6
     *
     * @param array $settings Search Results Page settings.
     *
     * @return string
     */
    private static function get_container_classes( $settings ) {

        $classes = [
            'swp-search-results',
			'swp-results-template-' . $settings['id'],
        ];

        if ( $settings['swp-layout-style'] === 'grid' ) {
            $classes[] = 'swp-grid';
            $per_row   = absint( $settings['swp-results-per-row'] );
            if ( ! empty( $per_row ) ) {
                $classes[] = 'swp-grid--cols-' . $per_row;
            }
        }

        if ( $settings['swp-layout-style'] === 'list' ) {
            $classes[] = 'swp-flex';
        }

        $image_size = $settings['swp-image-size'];
        if ( empty( $image_size ) || $image_size === 'none' ) {
            $classes[] = 'swp-rp--img-none';
        }
        if ( $image_size === 'small' ) {
            $classes[] = 'swp-rp--img-sm';
        }
        if ( $image_size === 'medium' ) {
            $classes[] = 'swp-rp--img-m';
        }
        if ( $image_size === 'large' ) {
            $classes[] = 'swp-rp--img-l';
        }

        return implode( ' ', $classes );
    }

    /**
     * Get search results page pagination classes.
     *
     * @since 4.3.6
     *
     * @param array $settings Search Results Page settings.
     *
     * @return string
     */
    private static function get_pagination_classes( $settings ) {

        $classes = [
            'nav-links',
        ];

        if ( $settings['swp-pagination-style'] === 'circular' ) {
            $classes[] = 'swp-results-pagination';
            $classes[] = 'swp-results-pagination--circular';
        }

        if ( $settings['swp-pagination-style'] === 'boxed' ) {
            $classes[] = 'swp-results-pagination';
            $classes[] = 'swp-results-pagination--boxed';
        }

        return implode( ' ', $classes );
    }

    /**
     * Get WooCommerce product price HTML.
     *
     * @since 4.3.6
     *
     * @param int $product_id WooCommerce product id.
     *
     * @return string
     */
    private static function get_product_price_html( $product_id ) {

        if ( ! function_exists( 'wc_get_product' ) ) {
            return '';
        }

        $product = wc_get_product( $product_id );

        if ( empty( $product ) ) {
            return '';
        }

        return $product->get_price_html();
    }

    /**
     * Get EDD product price HTML.
     *
     * @since 4.3.6
     *
     * @param int $download_id EDD download id.
     *
     * @return string
     */
    private static function get_download_price_html( $download_id ) {

        if ( ! function_exists( 'edd_price' ) ) {
            return '';
        }

        return edd_price( $download_id, false );
    }

    /**
     * Recursive function that generates from a multidimensional array of CSS rules, a valid CSS string.
     *
     * @since 4.3.6
     *
     * @param array $rules  CSS rules array.
     *   An array of CSS rules in the form of:
     *   array('selector'=>array('property' => 'value')). Also supports selector
     *   nesting, e.g.,
     *   array('selector' => array('selector'=>array('property' => 'value'))).
     * @param int   $indent Indentation level.
     *
     * @return string A CSS string of rules. This is not wrapped in <style> tags.
     * @source http://matthewgrasmick.com/article/convert-nested-php-array-css-string
     */
    private static function css_array_to_css( $rules, $indent = 0 ) {

        $css    = '';
        $prefix = str_repeat( '  ', $indent );

        foreach ( $rules as $key => $value ) {
            if ( is_array( $value ) ) {
                $selector   = $key;
                $properties = $value;

                $css .= $prefix . "$selector {\n";
                $css .= $prefix . self::css_array_to_css( $properties, $indent + 1 );
                $css .= $prefix . "}\n";
            } else {
                $property = $key;
                $css     .= $prefix . "$property: $value;\n";
            }
        }

        return $css;
    }

	/**
	 * Get the form settings.
	 *
	 * @since 4.4.0
	 *
	 * @return array|bool
	 */
	private static function get_form_from_request() {

		$form_id = isset( $_GET['swp_form']['form_id'] ) ? absint( $_GET['swp_form']['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! empty( $form_id ) ) {
			return SearchWPFormsStorage::get( $form_id );
		}

		return false;
	}

	/**
	 * Get the template ID.
	 *
	 * @since 4.4.0
	 *
	 * @param array $args Renderer args.
	 * @param array $form Search form settings.
	 *
	 * @return int
	 */
	private static function get_template_id( $args, $form ) {

		if ( ! empty( $args['id'] ) ) {
			return absint( $args['id'] );
		}

		if ( ! empty( $form['results-template'] ) ) {
			return absint( $form['results-template'] );
		}

		return 1;
	}

	/**
	 * Resolve the engine name.
	 *
	 * @since 4.5.0
	 *
	 * @param array $args Renderer args.
	 * @param array $form Search form settings.
	 *
	 * @return string
	 */
	private static function get_engine_name( $args, $form ) {

		$available_engines = Settings::get_engines();

		// Check if args engine exists and is valid.
		if ( ! empty( $args['engine'] ) ) {
			$selected_engine = sanitize_text_field( $args['engine'] );

			// If args engine exists in available engines, return it.
			if ( array_key_exists( $selected_engine, $available_engines ) ) {
				return $selected_engine;
			}
		}

		// If args engine doesn't exist or is invalid, check form engine.
		if ( ! empty( $form['engine'] ) ) {
			$selected_engine = $form['engine'];

			// If form engine exists in available engines, return it.
			if ( array_key_exists( $selected_engine, $available_engines ) ) {
				return $selected_engine;
			}
		}

		// If neither args engine nor form engine is valid, return default.
		return 'default';
	}

    /**
     * Register Results Template Gutenberg block.
     *
     * @since 4.4.0
     */
    private static function register_gutenberg_block() {

        register_block_type(
            SEARCHWP_PLUGIN_DIR . '/assets/gutenberg/build/results-template',
            [
                'render_callback' => [ __CLASS__, 'render' ],
                'attributes'      => [
                    'id'     => [
                        'type' => 'integer',
                    ],
                    'engine' => [
                        'type' => 'string',
                    ],
                ],
            ]
        );

        // Pass templates and engines data to the block editor.
        add_action(
			'admin_enqueue_scripts',
			function () {
				if ( ! function_exists( 'get_current_screen' ) ) {
					return;
				}

				$screen = get_current_screen();
				if ( ! $screen || ! method_exists( $screen, 'is_block_editor' ) || ! $screen->is_block_editor() ) {
					return;
				}

				wp_localize_script(
					'searchwp-results-template-editor-script',
					'searchwpTemplates',
					Storage::get_templates()
				);

				wp_localize_script(
					'searchwp-results-template-editor-script',
					'searchwpEngines',
					\SearchWP\Settings::get_engines()
				);

				// Add custom text for the results template block.
				wp_localize_script(
					'searchwp-results-template-editor-script',
					'searchwpResultsTemplateText',
					[
						'noticeHtml' => sprintf(
							/* translators: %s is the URL to the Search Forms page. */
							__( 'Search results will appear here, based on the settings of the <a href="%s" target="_blank" rel="noopener noreferrer">Search Form</a> pointing to this page (see the Search Form\'s "Target Page" setting).', 'searchwp' ),
							admin_url( 'admin.php?page=searchwp-forms' )
						),
					]
				);
			}
		);
    }

    /**
     * Register block category for SearchWP if it doesn't exist already.
     *
     * @since 4.4.0
     *
     * @param array $categories Array of block categories.
     *
     * @return array
     */
    public static function register_gutenberg_block_category( $categories ) {

        $category_slugs = wp_list_pluck( $categories, 'slug' );

        return in_array( 'searchwp', $category_slugs, true ) ? $categories : array_merge(
            $categories,
            [
                [
                    'slug'  => 'searchwp',
                    'title' => 'SearchWP',
                    'icon'  => null,
                ],
            ]
        );
    }
}
