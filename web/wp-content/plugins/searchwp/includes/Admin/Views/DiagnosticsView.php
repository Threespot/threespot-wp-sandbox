<?php
/**
 * SearchWP DiagnosticsView.
 *
 * @since 4.4.0
 */

namespace SearchWP\Admin\Views;

use SearchWP\Utils;
use SearchWP\Admin\NavTab;

/**
 * Class DiagnosticsView is responsible for providing the UI for Diagnostics.
 *
 * @since 4.4.0
 */
class DiagnosticsView {

	/**
	 * The slug for this view.
	 *
	 * @since 4.4.0
	 *
	 * @var string
	 */
	private static $slug = 'diagnostics';

	/**
	 * DiagnosticsView constructor.
	 *
	 * @since 4.4.0
	 */
	public function __construct() {

		if ( Utils::is_swp_admin_page( 'tools' ) ) {
			new NavTab(
				[
					'page'  => 'tools',
					'tab'   => self::$slug,
					'label' => __( 'Diagnostics', 'searchwp' ),
				]
			);
		}

		if ( Utils::is_swp_admin_page( 'tools', self::$slug ) ) {
			add_action( 'searchwp\settings\view',  [ __CLASS__, 'render' ] );
			add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
		}

		add_action( 'wp_ajax_' . SEARCHWP_PREFIX . 'diagnostics_get_indexed_tokens', [ __CLASS__, 'get_indexed_tokens' ] );
		add_action( 'wp_ajax_' . SEARCHWP_PREFIX . 'diagnostics_get_unindexed_entries', [ __CLASS__, 'get_unindexed_entries' ] );

		add_filter( 'searchwp\extensions', [ __CLASS__, 'unregister_deprecated_extension' ], 99 );
	}

	/**
	 * Outputs the assets needed for the Diagnostics UI.
	 *
	 * @since 4.4.0
	 */
	public static function assets() {

		$handle = SEARCHWP_PREFIX . self::$slug;

		array_map(
			'wp_enqueue_style',
			[
				Utils::$slug . 'collapse-layout',
				Utils::$slug . 'input',
				Utils::$slug . 'style',
			]
		);

		wp_enqueue_style(
			$handle,
			SEARCHWP_PLUGIN_URL . 'assets/css/admin/pages/diagnostics.css',
			[],
			SEARCHWP_VERSION
		);

		wp_enqueue_script(
			$handle,
			SEARCHWP_PLUGIN_URL . 'assets/js/admin/pages/diagnostics.js',
			[
				Utils::$slug . 'collapse',
			],
			SEARCHWP_VERSION,
			true
		);

		Utils::localize_script( $handle );
	}

	/**
	 * Callback for the render of this view.
	 *
	 * @since 4.4.0
	 */
	public static function render() {

		if ( ! class_exists( 'SearchWP\Index\Controller' ) || ! class_exists( 'SearchWP\Entry' ) ) {
			wp_die( 'SearchWP 4.0+ must be active' );
		}

		$index = new \SearchWP\Index\Controller();
		?>
        <div class="swp-content-container">
            <div id="diagnostics">
                <div class="swp-collapse swp-opened">
                    <div class="swp-collapse--header">
                        <h2 class="swp-h2">
                            <?php esc_html_e( 'View Indexed Tokens for Source Entry', 'searchwp' ); ?>
                        </h2>
                        <button class="swp-expand--button">
                            <svg class="swp-arrow" width="17" height="11" viewBox="0 0 17 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.2915 0.814362L8.09717 6.95819L1.90283 0.814362L0 2.7058L8.09717 10.7545L16.1943 2.7058L14.2915 0.814362Z" fill="#0E2121" fill-opacity="0.8"/>
                            </svg>
                        </button>
                    </div>
                    <div class="swp-collapse--content">
                        <div class="swp-row">
                            <div class="swp-flex--row">
                                <p class="swp-p swp-margin-b30">
                                    <?php esc_html_e( 'View indexed tokens for a specific source entry.', 'searchwp' ); ?>
                                </p>
                            </div>
                            <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">
                                <div class="swp-col swp-col--title-width">
                                    <h3 class="swp-h3">
                                        <?php esc_html_e( 'Source Entry', 'searchwp' ); ?>
                                    </h3>
                                </div>
                                <div class="swp-col sm:swp-w-full">
                                    <form class="searchwp-diagnostics-indexed-tokens" action="">
                                        <div class="swp-flex--row swp-flex--gap20 swp-margin-b20">
                                            <select name="searchwp_source" class="swp-select">
                                                <?php foreach ( $index->get_sources() as $source ) : ?>
                                                    <option value="<?php echo esc_attr( $source->get_name() ); ?>">
                                                        <?php echo esc_html( $source->get_label( 'singular' ) ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="swp-flex--row swp-flex--gap10 swp-flex--align-c">
                                                <label for="searchwp_source_entry_id" class="swp-label"><?php esc_html_e( 'ID', 'searchwp' ); ?></label>
                                                <input name="searchwp_source_entry_id" id="searchwp_source_entry_id" type="text" class="swp-input" value="">
                                            </div>
                                            <button type="submit" class="swp-button swp-button--green"><?php esc_html_e( 'View Tokens', 'searchwp' ); ?></button>
                                        </div>
                                    </form>
                                    <div class="searchwp-diagnostics-results-display searchwp-diagnostics-indexed-tokens-display">
                                        <p class="swp-p"><?php esc_html_e( 'Please choose the applicable Source and enter an Entry ID', 'searchwp' ); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="swp-collapse swp-opened">
                    <div class="swp-collapse--header">
                        <h2 class="swp-h2">
                            <?php esc_html_e( 'List Unindexed Entries', 'searchwp' ); ?>
                        </h2>
                        <button class="swp-expand--button">
                            <svg class="swp-arrow" width="17" height="11" viewBox="0 0 17 11" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.2915 0.814362L8.09717 6.95819L1.90283 0.814362L0 2.7058L8.09717 10.7545L16.1943 2.7058L14.2915 0.814362Z" fill="#0E2121" fill-opacity="0.8"/>
                            </svg>
                        </button>
                    </div>
                    <div class="swp-collapse--content">
                        <div class="swp-row">
                            <div class="swp-flex--row">
                                <p class="swp-p swp-margin-b30">
                                    <?php esc_html_e( 'View unindexed entries for a specific source.', 'searchwp' ); ?>
                                </p>
                            </div>
                            <div class="swp-flex--row sm:swp-flex--col sm:swp-flex--gap30">
                                <div class="swp-col swp-col--title-width">
                                    <h3 class="swp-h3">
                                        <?php esc_html_e( 'Source', 'searchwp' ); ?>
                                    </h3>
                                </div>
                                <div class="swp-col sm:swp-w-full">
                                    <form class="searchwp-diagnostics-unindexed-entries" action="">
                                        <div class="swp-flex--row swp-flex--gap20 swp-margin-b20">
                                            <select name="searchwp_source" class="swp-select">
                                                <?php foreach ( $index->get_sources() as $source ) : ?>
                                                    <option value="<?php echo esc_attr( $source->get_name() ); ?>">
                                                        <?php echo esc_html( $source->get_label() ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="swp-button swp-button--green"><?php esc_html_e( 'View List', 'searchwp' ); ?></button>
                                        </div>
                                    </form>
                                    <div class="searchwp-diagnostics-results-display searchwp-diagnostics-unindexed-entries-display">
                                        <p class="swp-p"><?php esc_html_e( 'Please choose the applicable Source. Results limited to 100.', 'searchwp' ); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * AJAX callback to retrieve indexed tokens for the submitted Source/ID pair.
	 *
	 * @since 4.4.0
	 */
	public static function get_indexed_tokens() {

		Utils::check_ajax_permissions();

		$source = isset( $_REQUEST['source'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id     = isset( $_REQUEST['id'] ) ? absint( $_REQUEST['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $source || ! $id ) {
			wp_send_json_error( __( 'Invalid source or ID.', 'searchwp' ) );
		}

		$index = new \SearchWP\Index\Controller();
		$entry = new \SearchWP\Entry( $index->get_source_by_name( $source ), $id );

		wp_send_json_success( $index->get_tokens_for_entry( $entry ) );
	}

	/**
	 * AJAX callback to retrieve unindexed entries for the submitted Source.
	 *
	 * @since 4.4.0
	 */
	public static function get_unindexed_entries() {

		Utils::check_ajax_permissions();

		$source = isset( $_REQUEST['source'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['source'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! $source ) {
			wp_send_json_error( __( 'Invalid source.', 'searchwp' ) );
		}

		$index   = new \SearchWP\Index\Controller();
		$source  = $index->get_source_by_name( $source );
		$entries = $source->get_unindexed_entries( 100 );

		$entry_ids = [];
		foreach ( $entries->get() as $entry ) {
			$entry_ids[] = $entry->get_id();
		}

		wp_send_json_success( $entry_ids );
	}

	/**
	 * Unregisters the deprecated Diagnostics extension.
	 *
	 * @since 4.4.0
	 *
	 * @param array $extensions The extensions.
	 *
	 * @return array
	 */
	public static function unregister_deprecated_extension( $extensions ) {

		// Unset the Diagnostics extension.
		unset( $extensions['Diagnostics'] );

		return $extensions;
	}
}
