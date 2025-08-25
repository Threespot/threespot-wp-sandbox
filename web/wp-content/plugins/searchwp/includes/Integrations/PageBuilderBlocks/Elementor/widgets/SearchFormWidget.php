<?php

namespace SearchWP\Integrations\PageBuilderBlocks\Elementor\Widgets;

use SearchWP\Integrations\PageBuilderBlocks;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Class SearchFormWidget handles the SearchWP form widget for Elementor.
 *
 * @since 4.5.0
 */
class SearchFormWidget extends \Elementor\Widget_Base {

	/**
	 * Get the name of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return string Widget name.
	 */
	public function get_name() {

		return 'searchwp_search_form';
	}

	/**
	 * Get the title of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return string Widget title.
	 */
	public function get_title() {

		return PageBuilderBlocks::get_text( 'search_form_title' );
	}

	/**
	 * Get the icon of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {

		return 'eicon-search';
	}

	/**
	 * Get the categories of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {

		return [ 'searchwp' ];
	}

	/**
	 * Get the keywords of the widget.
	 *
	 * @since 4.5.0
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {

		return [ 'searchwp', 'search', 'form' ];
	}

	/**
	 * Get the script dependencies of the widget.
	 *
	 * @since 4.5.0
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => PageBuilderBlocks::get_text( 'form_settings_label' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$form_options = PageBuilderBlocks::get_form_options();

		$this->add_control(
			'form_id',
			[
				'label'   => PageBuilderBlocks::get_text( 'form_label' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => array_key_first( $form_options ),
				'options' => $form_options,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the widget output on the frontend.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	protected function render() {

		$settings = $this->get_settings_for_display();
		$form_id  = absint( $settings['form_id'] ?? 0 );
		$form     = \SearchWP\Forms\Storage::get( $form_id );

		if ( empty( $form ) ) {
			return;
		}

		echo \SearchWP\Forms\Frontend::render( [ 'id' => $form_id ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the widget output in the editor.
	 *
	 * @since 4.5.0
	 *
	 * @return void
	 */
	protected function content_template() {
		?>
		<# if ( ! settings.form_id || settings.form_id === '0' ) { #>
			<?php echo PageBuilderBlocks::get_component_preview( PageBuilderBlocks::get_text( 'select_form_from_settings' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<# } else { #>
			<div class="elementor-shortcode">[searchwp_form id="{{ settings.form_id }}"]</div>
		<# } #>
		<?php
	}
}
