/* global _SEARCHWP */

( function($) {

    'use strict';

    const app = {

		/**
		 * Init.
		 *
		 * @since 4.3.14
		 */
		init: () => {

			$( app.ready );
		},

		/**
		 * Document ready
		 *
		 * @since 4.3.14
		 */
		ready: () => {

			app.events();
		},

		/**
		 * Page events.
		 *
		 * @since 4.3.14
		 */
		events: () => {

			$( '.swp-onboarding-page-nav-btn' ).on( 'click', app.pageNavButtonClick );
			$( '#swp-onboarding-custom-sources .swp-toggle-checkbox' ).on( 'change', app.customSourcesCheckboxChange );

			$( '#swp-license-activate' ).on( 'click', app.activateLicense );
		},

		/**
		 * Callback for clicking page navigation button (e.g. "Next" or "Back").
		 *
		 * @since 4.3.14
		 */
		pageNavButtonClick: function(e) {

			e.preventDefault();

			$( '#swp-onboarding-error-msg' ).hide().empty();
			$( '.swp-content-container button' ).attr( 'disabled','disabled' );

			const button = $( this );
			button.addClass( 'swp-button--processing' );

			const inputs = {};
			$( '.swp-toggle-checkbox' ).each( function () {
				const checkbox = $( this );
				const inputGroup = checkbox.data( 'swp-input-group' );
				if ( inputGroup ) {
					inputs[ inputGroup ] = inputs[ inputGroup ] || {};
					inputs[ inputGroup ][ checkbox.attr( 'name' ) ] = checkbox.is( ':checked' );
				} else {
					inputs[ checkbox.attr( 'name' ) ] = checkbox.is( ':checked' );
				}
			} );

			const data = {
				'current_page': button.data( 'current-page' ),
				'action': button.data( 'action' ),
				'inputs': inputs,
			};

			$.post(
				ajaxurl,
				{
					_ajax_nonce: _SEARCHWP.nonce,
					action: _SEARCHWP.prefix + 'onboarding_load_page',
					data: JSON.stringify( data ),
				}
			)
			.always( app.pageNavButtonProcessResponse );
		},

		/**
		 * Process response for the "Next" button callback.
		 *
		 * @since 4.3.14
		 */
		pageNavButtonProcessResponse: ( response ) => {

			if ( response.data && response.data['target_url'] ) {
				location.href = response.data['target_url'];
				return;
			}

			$( '#swp-onboarding-error-msg' ).show().text( typeof response.data === 'string' ? response.data : 'There was an error processing your request.' );
			$( '.swp-content-container button' ).removeAttr( 'disabled' );
			$( '.swp-button' ).removeClass( 'swp-button--processing' );
		},

		/**
		 * Callback for changing the one of the Custom sources checkbox on the Engines page.
		 *
		 * @since 4.3.14
		 */
		customSourcesCheckboxChange: () => {

			let allCheckboxesUnchecked = true;
			$( '#swp-onboarding-custom-sources .swp-toggle-checkbox' ).each( function () {
				if ( $( this ).is( ':checked') ) {
					allCheckboxesUnchecked = false;
				}
			} );

			if ( allCheckboxesUnchecked ) {
				$( '#swp-onboarding-supplemental-engine-created-msg' ).hide();
				$( '#swp-onboarding-default-engine-only-msg' ).show();
			} else {
				$( '#swp-onboarding-supplemental-engine-created-msg' ).show();
				$( '#swp-onboarding-default-engine-only-msg' ).hide();
			}
		},

		/**
		 * Callback for clicking "Activate License" button.
		 *
		 * @since 4.3.14
		 */
		activateLicense: function(e) {

			e.preventDefault();

			$( '#swp-license-error-msg' ).hide().empty();

			$( '.swp-content-container button' ).attr( 'disabled','disabled' );
			$( '#swp-license-activate' ).addClass( 'swp-button--processing' );

			$.post(
				ajaxurl,
				{
					_ajax_nonce: _SEARCHWP.nonce,
					action: _SEARCHWP.prefix + 'license_activate',
					license_key: $( '#swp-license' ).val(),
				}
			)
			.always( app.activateLicenseProcessResponse );
		},

		/**
		 * Process response for the "Activate License" button callback.
		 *
		 * @since 4.3.14
		 */
		activateLicenseProcessResponse: ( response ) => {

			if ( response.data && response.data.status === 'valid' ) {
				location.reload();
				return;
			}

			$( '#swp-license-error-msg' ).show().text( typeof response.data === 'string' ? response.data : 'There was an error activating your license.' );
			$( '.swp-content-container button' ).removeAttr( 'disabled' );
			$( '#swp-license-activate' ).removeClass( 'swp-button--processing' );
		},
    };

    app.init();

    window.searchwp = window.searchwp || {};

    window.searchwp.AdminOnboardingWizardPage = app;

}( jQuery ) );
