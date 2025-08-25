/**
 * Form and Results Template Embed Wizard function.
 *
 * @since 4.5.0
 */

'use strict';

var SearchWPEmbedWizard = window.SearchWPEmbedWizard || ( function ( window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 4.5.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 4.5.0
		 */
		init: function () {
			$( window ).on(
				'load',
				() =>
				{
					if ( app.isGutenberg() ) {
						// Initialize tooltip in page editor.
						app.initTooltip();
					}
				}
			);
		},

		/**
		 * Init the embed page tooltip.
		 *
		 * @since 4.5.0
		 */
		initTooltip: function () {

			var $tooltip = $( '.searchwp-embed-wizard-tooltip' ),
				anchor   = '.block-editor .edit-post-header';

			// Create a dot element that will be the anchor for the tooltip.
			var $dot = $( '<span class="searchwp-embed-wizard-dot">&nbsp;</span>' );

			// Position the tooltip below the header.
			$tooltip.addClass( 'searchwp-embed-wizard-tooltip-gutenberg' );
			$( anchor ).after( $dot );

			// Show the tooltip.
			$tooltip.show();

			// Add an event listener for the "Done" button.
			$tooltip.find( '.searchwp-embed-wizard-done-btn' ).on(
				'click',
				function () {
					$tooltip.remove();
					$dot.remove();
				}
			);
		},

		/**
		 * Check if we're in the Gutenberg editor.
		 *
		 * @since 4.5.0
		 *
		 * @returns {boolean} Is Gutenberg or not.
		 */
		isGutenberg: function () {

			return typeof wp !== 'undefined' && Object.prototype.hasOwnProperty.call( wp, 'blocks' );
		},
	};

	// Provide access to public functions/properties.
	return app;

}( window, jQuery ) );

// Initialize.
SearchWPEmbedWizard.init();
