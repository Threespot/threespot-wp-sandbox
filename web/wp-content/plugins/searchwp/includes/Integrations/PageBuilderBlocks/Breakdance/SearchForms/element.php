<?php

namespace BreakdanceCustomElements;

use SearchWP\Integrations\PageBuilderBlocks;
use function Breakdance\Elements\c;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\Breakdance\ElementStudio\registerElementForEditing(
	'BreakdanceCustomElements\\SearchWPForm',
	\Breakdance\Util\getdirectoryPathRelativeToPluginFolder( __DIR__ )
);

/**
 * Class SearchWPForm handles SearchWP forms integration with Breakdance.
 *
 * @since 4.5.0
 */
class SearchWPForm extends \Breakdance\Elements\Element {

	/**
	 * Define the UI icon for the element.
	 *
	 * @since 4.5.0
	 *
	 * @return string
	 */
	public static function uiIcon() {

		return 'SearchIcon';
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

		return PageBuilderBlocks::get_text( 'search_form_title' );
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

		if ( ! isset( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'breakdance_load_document' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return file_get_contents( __DIR__ . '/default.css' );
		}

		return file_get_contents( SEARCHWP_PLUGIN_DIR . '/assets/css/frontend/search-forms.css' );
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
				'searchwp_form' => [
					'form_id' => '0',
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

		$items = PageBuilderBlocks::get_form_options();

		$items = array_map(
			function ( $item, $key ) {
				return [
					'text'  => $item,
					'value' => $key,
				];
			},
			$items,
			array_keys( $items )
		);

		return [
			c(
				'searchwp_form',
				PageBuilderBlocks::get_text( 'search_form_title' ),
				[
					c(
						'form_id',
						'Form',
						[],
						[
							'type'   => 'dropdown',
							'layout' => 'inline',
							'items'  => $items,
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

		return 10;
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

		return [ 'content.searchwp_form.form_id' ];
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
			'content.searchwp_form.form_id',
			'content.searchwp_form',
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
