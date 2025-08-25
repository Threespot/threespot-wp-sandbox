<?php

namespace BreakdanceCustomElements;

use SearchWP\Integrations\PageBuilderBlocks;
use function Breakdance\Elements\c;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Breakdance\ElementStudio\registerElementForEditing(
	'BreakdanceCustomElements\\SearchWPResults',
	\Breakdance\Util\getdirectoryPathRelativeToPluginFolder( __DIR__ )
);

/**
 * Class SearchWPForm handles SearchWP forms integration with Breakdance.
 *
 * @since 4.5.0
 */
class SearchWPResults extends \Breakdance\Elements\Element {

	/**
	 * Define the UI icon for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function uiIcon() {

		return 'ListIcon';
	}

	/**
	 * Define the HTML tag for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function tag() {

		return 'div';
	}

	/**
	 * Define tag options for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function tagOptions() {

		return [];
	}

	/**
	 * Define the tag control path.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function tagControlPath() {

		return false;
	}

	/**
	 * Define the element name.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function name() {

		return PageBuilderBlocks::get_text( 'search_results_label' );
	}

	/**
	 * Define the element class name.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function className() {

		return '';
	}

	/**
	 * Define the element category.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function category() {

		return 'site';
	}

	/**
	 * Define the element badge.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function badge() {

		return false;
	}

	/**
	 * Define the element slug.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function slug() {

		return __CLASS__;
	}

	/**
	 * Define the element template.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function template() {

		return file_get_contents( __DIR__ . '/html.twig' );
	}

	/**
	 * Define the default CSS for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function defaultCss() {

		return file_get_contents( __DIR__ . '/default.css' );
	}

	/**
	 * Define the default properties for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function defaultProperties() {

		return [
			'content' => [
				'searchwp_results' => [
					'template_id' => '',
					'engine'      => '',
				],
			],
		];
	}

	/**
	 * Define the default children for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function defaultChildren() {

		return false;
	}

	/**
	 * Define the CSS template for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function cssTemplate() {

		return file_get_contents( __DIR__ . '/css.twig' );
	}

	/**
	 * Define the design controls for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function designControls() {

		return [];
	}

	/**
	 * Define the content controls for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function contentControls() {

		$template_items = PageBuilderBlocks::get_template_options();

		$template_items = array_map(
			function ( $item, $key ) {
				return [
					'text'  => $item,
					'value' => $key,
				];
			},
			$template_items,
			array_keys( $template_items )
		);

		$engine_items = PageBuilderBlocks::get_search_engines_options();

		$engine_items = array_map(
			function ( $item, $key ) {
				return [
					'text'  => $item,
					'value' => $key,
				];
			},
			$engine_items,
			array_keys( $engine_items )
		);

		return [
			c(
				'searchwp_results',
				PageBuilderBlocks::get_text( 'search_results_label' ),
				[
					c(
						'template_id',
						PageBuilderBlocks::get_text( 'template_label' ),
						[],
						[
							'type'   => 'dropdown',
							'layout' => 'inline',
							'items'  => $template_items,
						],
						false,
						false,
						[]
					),
					c(
						'engine',
						PageBuilderBlocks::get_text( 'search_engine_label' ),
						[],
						[
							'type'   => 'dropdown',
							'layout' => 'inline',
							'items'  => $engine_items,
						],
						false,
						false,
						[]
					),
					c(
						'searchwp_notice',
						'SearchWP Results Notice',
						[],
						[
							'type'            => 'alert_box',
							'layout'          => 'vertical',
							'alertBoxOptions' => [
								'style'   => 'warning',
								'content' => '<p>' . PageBuilderBlocks::get_text( 'search_settings_notice' ) . '</p>',
							],
						],
						false,
						false,
						[]
					),
				],
				[
					'type'   => 'section',
					'layout' => 'vertical',
				],
				false,
				false,
				[]
			),
		];
	}

	/**
	 * Define the settings controls for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function settingsControls() {

		return [];
	}

	/**
	 * Define the dependencies for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function dependencies() {

		return false;
	}

	/**
	 * Define the settings for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function settings() {

		return false;
	}

	/**
	 * Define the panel rules for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function addPanelRules() {

		return false;
	}

	/**
	 * Define the actions for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function actions() {

		return false;
	}

	/**
	 * Define the nesting rule for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function nestingRule() {

		return [
			'type' => 'final',
		];
	}

	/**
	 * Define the spacing bars for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function spacingBars() {

		return false;
	}

	/**
	 * Define the attributes for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function attributes() {

		return false;
	}

	/**
	 * Define experimental features for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function experimental() {

		return false;
	}

	/**
	 * Define the order for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return int
	 */
	public static function order() {

		return 11;
	}

	/**
	 * Define the dynamic property paths for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function dynamicPropertyPaths() {

		return [];
	}

	/**
	 * Define additional classes for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function additionalClasses() {

		return false;
	}

	/**
	 * Define project management settings for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return bool
	 */
	public static function projectManagement() {

		return false;
	}

	/**
	 * Define property paths to whitelist in flat props.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function propertyPathsToWhitelistInFlatProps() {

		return [
			'content.searchwp_results.template_id',
			'content.searchwp_results.engine',
		];
	}

	/**
	 * Define property paths to SSR element when value changes.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function propertyPathsToSsrElementWhenValueChanges() {

		return [
			'content.searchwp_results.template_id',
			'content.searchwp_results.engine',
			'content.searchwp_results',
		];
	}

	/**
	 * Define the available integrations for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return array
	 */
	public static function availableIn() {

		return [ 'oxygen', 'breakdance' ];
	}
}
