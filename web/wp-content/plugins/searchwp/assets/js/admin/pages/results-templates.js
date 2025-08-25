/* global _SEARCHWP */

( function($) {

    'use strict';

    const app = {

        /**
         * Init.
         *
         * @since 4.3.6
         */
        init: () => {

            $( app.ready );
        },

        /**
         * Document ready
         *
         * @since 4.3.6
         */
        ready: () => {

			app.initExistingPageEmbedSelect();

            app.events();
        },

        /**
         * Page events.
         *
         * @since 4.3.6
         */
        events: () => {
			$( '#swp-template-save' ).on( 'click', app.saveSettings );

			$( '.swp-rt--edit-header--icon' ).on(
				'click',
				( e ) =>
				{
					const $title = $( e.currentTarget ).parent();
					$title.siblings( 'input' ).show();
					$title.hide();
				}
			);

			$( '[name="swp-rte-embed"]' ).on(
				'change',
				( e ) =>
				{
					$( '.swp-rte-embed--desc' ).hide();
					$( `#${e.target.value}` ).show();
				}
			);

			$( '.swp-results-template-embed-modal-go-btn' ).on( 'click', app.embedPageRedirect )

			app.UIEvents();
			app.licenseEvents();

        },

        /**
         * Save page settings.
         *
         * @since 4.3.6
         */
        saveSettings: () => {

			let swpPromotedAdsContent = '';

			if (
				typeof tinyMCE !== 'undefined' &&
				tinyMCE.get( 'swp-promoted-ads-content' ) &&
				! tinyMCE.get( 'swp-promoted-ads-content' ).isHidden()
			) {
				swpPromotedAdsContent = tinyMCE.get( 'swp-promoted-ads-content' ).getContent();
			} else {
				swpPromotedAdsContent = $( 'textarea[name=swp-promoted-ads-content]' ).val();
			}

            const settings = {
				'title': $( 'input[name=title]' ).val(),
                'swp-layout-theme': $( 'input[name=swp-layout-theme]:checked' ).val(),
                'swp-layout-style': $( 'input[name=swp-layout-style]:checked' ).val(),
                'swp-results-per-row': $( 'select[name=swp-results-per-row]' ).val(),
                'swp-image-size': $( 'select[name=swp-image-size]' ).val(),
                'swp-title-color': $( 'input[name=swp-title-color]' ).val(),
                'swp-title-font-size': $( 'input[name=swp-title-font-size]' ).val(),
                'swp-price-color': $( 'input[name=swp-price-color]' ).val(),
                'swp-price-font-size': $( 'input[name=swp-price-font-size]' ).val(),
                'swp-description-enabled': $( 'input[name=swp-description-enabled]' ).is( ':checked' ),
                'swp-button-enabled': $( 'input[name=swp-button-enabled]' ).is( ':checked' ),
                'swp-button-label': $( 'input[name=swp-button-label]' ).val(),
                'swp-button-bg-color': $( 'input[name=swp-button-bg-color]' ).val(),
                'swp-button-font-color': $( 'input[name=swp-button-font-color]' ).val(),
                'swp-button-font-size': $( 'input[name=swp-button-font-size]' ).val(),
                'swp-results-per-page': $( 'input[name=swp-results-per-page]' ).val(),
                'swp-pagination-style': $( 'input[name=swp-pagination-style]:checked' ).val(),
				'swp-pagination-prev': $( 'input[name=swp-pagination-prev]' ).val(),
				'swp-pagination-next': $( 'input[name=swp-pagination-next]' ).val(),
				'swp-load-more-enabled': $( 'input[name=swp-load-more-enabled]' ).is( ':checked' ),
				'swp-load-more-label': $( 'input[name=swp-load-more-label]' ).val(),
				'swp-load-more-bg-color': $( 'input[name=swp-load-more-bg-color]' ).val(),
				'swp-load-more-font-color': $( 'input[name=swp-load-more-font-color]' ).val(),
				'swp-load-more-font-size': $( 'input[name=swp-load-more-font-size]' ).val(),
				'swp-promoted-ads-enabled': $( 'input[name=swp-promoted-ads-enabled]' ).is( ':checked' ),
				'swp-promoted-ads-content': swpPromotedAdsContent,
				'swp-promoted-ads-position': $( 'input[name=swp-promoted-ads-position]' ).val(),
			};

			const $saveButton = $( '#swp-template-save' );

            const data = {
                _ajax_nonce: _SEARCHWP.nonce,
                action: _SEARCHWP.prefix + 'save_templates_page_settings',
				template_id: $saveButton.data( 'template-id' ),
                settings: JSON.stringify( settings ),
            };

            const $enabledInputs = $( '.swp-content-container button:not([disabled]), .swp-content-container input:not([disabled])' );

            $enabledInputs.prop( 'disabled', true );
            $saveButton.addClass( 'swp-button--processing' );

            $.post(
				ajaxurl,
				data,
				( response ) =>
				{
					$enabledInputs.prop( 'disabled', false );
					$saveButton.removeClass( 'swp-button--processing' );

					if ( response.success ) {
						$saveButton.addClass( 'swp-button--completed' );
						setTimeout( () => { $saveButton.removeClass( 'swp-button--completed' ) }, 1500 );
					}
				}
			);
        },

        /**
         * Page UI events.
         *
         * @since 4.3.6
         */
        UIEvents: () => {

            $( '[name="swp-layout-theme"]' ).on(
				'change',
				(e) =>
				{
					const theme = e.target.value;

					const $layout            = $( 'input[name=swp-layout-style]' );
					const imageSizeChoicesJs = document.querySelector( 'select[name=swp-image-size]' ).data.choicesjs;
					const perRowChoicesJs    = document.querySelector( 'select[name=swp-results-per-row]' ).data.choicesjs;

					const $preview     = $( '.swp-rt-theme-preview > div' );
					const $perRowBlock = $( '#swp-results-per-row-block' );

					if ( theme === 'alpha' ) {
						$layout.filter( '[value=list]' ).prop( 'checked',true );
						imageSizeChoicesJs.setChoiceByValue( '' );
						$preview
							.removeClass( 'swp-grid' )
							.addClass( 'swp-flex' );
						$preview
							.removeClass( 'swp-rp--img-sm swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-result-item--img--off' );
						$perRowBlock.hide();
					}

					if ( theme === 'beta' ) {
						$layout.filter( '[value=list]' ).prop( 'checked',true );
						imageSizeChoicesJs.setChoiceByValue( 'small' );
						$preview
							.removeClass( 'swp-grid' )
							.addClass( 'swp-flex' );
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-rp--img-sm' );
						$perRowBlock.hide();
					}

					if ( theme === 'gamma' ) {
						$layout.filter( '[value=grid]' ).prop( 'checked',true );
						imageSizeChoicesJs.setChoiceByValue( 'small' );
						perRowChoicesJs.setChoiceByValue( '3' );
						$preview
							.removeClass( 'swp-flex' )
							.addClass( 'swp-grid' );
						$preview
							.removeClass( 'swp-grid--cols-2 swp-grid--cols-4 swp-grid--cols-5 swp-grid--cols-6 swp-grid--cols-7' )
							.addClass( 'swp-grid--cols-3' );
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-rp--img-sm' );
						$perRowBlock.show();
					}

					if ( theme === 'epsilon' ) {
						$layout.filter( '[value=list]' ).prop( 'checked',true );
						imageSizeChoicesJs.setChoiceByValue( 'medium' );
						$preview
							.removeClass( 'swp-grid' )
							.addClass( 'swp-flex' );
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-rp--img-m' );
						$perRowBlock.hide();
					}

					if ( theme === 'zeta' ) {
						$layout.filter( '[value=grid]' ).prop( 'checked',true );
						imageSizeChoicesJs.setChoiceByValue( 'large' );
						perRowChoicesJs.setChoiceByValue( '3' );
						$preview
							.removeClass( 'swp-flex' )
							.addClass( 'swp-grid' );
						$preview
							.removeClass( 'swp-grid--cols-2 swp-grid--cols-4 swp-grid--cols-5 swp-grid--cols-6 swp-grid--cols-7' )
							.addClass( 'swp-grid--cols-3' );
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-m' )
							.addClass( 'swp-rp--img-l' );
						$perRowBlock.show();
					}

					if ( theme === 'combined' ) {
						$layout.filter( '[value=list]' ).prop( 'checked',true );
						imageSizeChoicesJs.setChoiceByValue( 'large' );
						$preview
							.removeClass( 'swp-grid' )
							.addClass( 'swp-flex' );
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-rp--img-l' );
						$perRowBlock.show();
					}
				}
			);

            $( '[name=swp-layout-style]' ).on(
				'change',
				(e) =>
				{
					const layout       = e.target.value;
					const $preview     = $( '.swp-rp-theme-preview > div' );
					const $perRowBlock = $( '#swp-results-per-row-block' );

					$( '[name="swp-layout-theme"][value="combined"]' ).prop( 'checked', true );

					if ( layout === 'list' ) {
						$preview
							.removeClass( 'swp-grid' )
							.addClass( 'swp-flex' );
						$perRowBlock.hide();
					}

					if ( layout === 'grid' ) {
						$preview
							.removeClass( 'swp-flex' )
							.addClass( 'swp-grid' );
						$perRowBlock.show();
					}
				}
			);

            $( '[name=swp-results-per-row]' ).on(
				'change',
				(e) =>
				{
					const $preview = $( '.swp-rp-theme-preview > div' );
					let perRow     = parseInt( e.target.value, 10 );

					if ( ! perRow ) {
						perRow = 3;
					}

					$( '[name="swp-layout-theme"][value="combined"]' ).prop( 'checked', true );

					$preview
						.removeClass( 'swp-grid--cols-2 swp-grid--cols-3 swp-grid--cols-4 swp-grid--cols-5 swp-grid--cols-6 swp-grid--cols-7' )
						.addClass( `swp-grid--cols-${perRow}` );
				}
			);

            $( '[name=swp-image-size]' ).on(
				'change',
				(e) =>
				{
					const imageSize = e.target.value;
					const $preview  = $( '.swp-rp-theme-preview > div' );

					$( '[name="swp-layout-theme"][value="combined"]' ).prop( 'checked', true );

					if ( ! imageSize ) {
						$preview
							.removeClass( 'swp-rp--img-sm swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-result-item--img--off' );
					}

					if ( imageSize === 'small' ) {
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-m swp-rp--img-l' )
							.addClass( 'swp-rp--img-sm' );
					}

					if ( imageSize === 'medium' ) {
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-l' )
							.addClass( 'swp-rp--img-m' );
					}

					if ( imageSize === 'large' ) {
						$preview
							.removeClass( 'swp-result-item--img--off swp-rp--img-sm swp-rp--img-m' )
							.addClass( 'swp-rp--img-l' );
					}
				}
			);

            $( '[name="swp-description-enabled"]' ).on(
				'change',
				(e) =>
				{
					const descriptionEnabled = e.target.checked;
					const descriptionPreview = $( '.swp-result-item--desc' );

					if ( descriptionEnabled ) {
						descriptionPreview.show();
					} else {
						descriptionPreview.hide();
					}
				}
			);

            $( '[name="swp-button-enabled"]' ).on(
				'change',
				(e) =>
				{
					const buttonEnabled = e.target.checked;
					const buttonPreview = $( '.swp-result-item--button' );

					if ( buttonEnabled ) {
						buttonPreview.show();
					} else {
						buttonPreview.hide();
					}
				}
			);

            $( '[name="swp-pagination-style"]' ).on(
				'change',
				(e) =>
				{
					const style     = e.target.value;
					const container = $( '.swp-results-pagination > ul' );

					if ( style === 'circular' ) {
						container
							.removeClass( 'swp-results-pagination--boxed swp-results-pagination--noboxed' )
							.addClass( 'swp-results-pagination--circular' );
					}

					if ( style === 'boxed' ) {
						container
							.removeClass( 'swp-results-pagination--circular swp-results-pagination--noboxed' )
							.addClass( 'swp-results-pagination--boxed' );
					}

					if ( style === 'noboxed' ) {
						container
							.removeClass( 'swp-results-pagination--boxed swp-results-pagination--circular' )
							.addClass( 'swp-results-pagination--noboxed' );
					}

					if ( style === 'circular' || style === 'boxed' ) {
						$( '.swp-pagination-label' ).hide();
					} else {
						$( '.swp-pagination-label' ).show();
					}
				}
			);

			$( '[name="swp-load-more-enabled"]' ).on(
				'change',
				(e) =>
				{
					const loadMoreEnabled   = e.target.checked;
					const $loadMoreStyle    = $( '#swp-load-more-styles' );
					const $paginationStyles = $( '#swp-pagination-styles' );

					if ( loadMoreEnabled ) {
						$loadMoreStyle.show();
						$paginationStyles.hide();
					} else {
						$loadMoreStyle.hide();
						$paginationStyles.show();
					}
				}
			);
        },

		/**
		 * Display "Loading" in ChoicesJS instance.
		 *
		 * @since 4.3.2
		 *
		 * @param {Choices} choicesJS ChoicesJS instance.
		 */
		displayLoading: function( choicesJS ) {

			const loadingText = 'Loading';

			choicesJS.setChoices(
				[
					{ value: '', label: `${loadingText}...`, disabled: true },
				],
				'value',
				'label',
				true
			);
		},

		/**
		 * Perform AJAX search request.
		 *
		 * @since 4.3.2
		 *
		 * @param {string} action     Action to be used when doing ajax request for search.
		 * @param {string} searchTerm Search term.
		 * @param {string} nonce      Nonce to be used when doing ajax request.
		 *
		 * @returns {Promise} jQuery ajax call promise.
		 */
		ajaxSearchPages: function( action, searchTerm, nonce ) {

			return $.get(
				ajaxurl,
				{
					action: action,
					search: searchTerm,
					_wpnonce: nonce,
				}
			).fail(
				function( err ) {
					console.error( err );
				}
			);
		},

		/**
		 * Perform search in ChoicesJS instance.
		 *
		 * @since 4.3.2
		 *
		 * @param {Choices} choicesJS  ChoicesJS instance.
		 * @param {string}  searchTerm Search term.
		 * @param {object}  ajaxArgs   Object containing `action` and `nonce` to perform AJAX search.
		 */
		performSearch: function( choicesJS, searchTerm, ajaxArgs ) {

			if ( ! ajaxArgs.action || ! ajaxArgs.nonce ) {
				return;
			}

			app.displayLoading( choicesJS );

			const requestSearchPages = app.ajaxSearchPages( ajaxArgs.action, searchTerm, ajaxArgs.nonce );

			requestSearchPages.done( function( response ) {
				choicesJS.setChoices( response.data, 'value', 'label', true );
			} );
		},

		/**
		 * Init "Existing Page" select inside the Embed modal.
		 *
		 * @since 4.3.2
		 */
		initExistingPageEmbedSelect: () => {

			const el = document.getElementById('swp-results-template-embed-existing-page-modal-select');

			if ( ! el ) {
				return;
			}

			const choices = new Choices( el, { allowHTML: false } );

			if ( ! el.dataset.useAjax ) {
				return;
			}

			const ajaxArgs = {
				action: 'searchwp_admin_template_embed_wizard_search_pages_choicesjs',
				nonce: _SEARCHWP.nonce,
			};

			/*
			 * ChoicesJS doesn't handle empty string search with it's `search` event handler,
			 * so we work around it by detecting empty string search with `keyup` event.
			 */
			choices.input.element.addEventListener( 'keyup', function( e ) {

				// Only capture backspace and delete keypress that results to empty string.
				if (
					( e.which !== 8 && e.which !== 46 ) ||
					e.target.value.length > 0
				) {
					return;
				}

				app.performSearch( choices, '', ajaxArgs );
			} );

			choices.passedElement.element.addEventListener( 'search', _.debounce( function( e ) {

				// Make sure that the search term is actually changed.
				if ( choices.input.element.value.length === 0 ) {
					return;
				}

				app.performSearch( choices, e.detail.value, ajaxArgs );
			}, 800 ) );
		},

		/**
		 * Redirect to template embed page.
		 *
		 * @since 4.4.0
		 */
		embedPageRedirect: function(e) {
			const $button = $( e.target );
			const $allInputs = $( '.swp-content-container button:not(.swp-rt--theme-preview button), .swp-content-container input:not(.swp-rt--theme-preview input)' );

			$allInputs.prop('disabled', true);
			$button.addClass('swp-button--processing');

			e.target.disabled = true;

			const data = {
				action: 'searchwp_admin_template_embed_wizard_embed_page_url',
				_wpnonce: _SEARCHWP.nonce,
				templateId: $( '#swp-template-save' ).data( 'template-id' ),
				pageId: 0,
				pageTitle: '',
			};

			if ( $button.data( 'action' ) === 'select-page' ) {
				data.pageId = $( '#swp-results-template-embed-existing-page-modal-select' ).val();
			}

			if ( $button.data( 'action' ) === 'create-page' ) {
				data.pageTitle = $( '#swp-results-template-embed-new-page-modal-page-title' ).val()
			}

			$.post( ajaxurl, data, function( response ) {
				if ( response.success ) {
					window.location = response.data;
				} else {
					console.error(response);
					$allInputs.prop('disabled', false);
					$button.removeClass('swp-button--processing');
					$button.after('<span class="swp-error-msg swp-text-red swp-b ">Error</span>');
					setTimeout(
						function () {
							$button.siblings('.swp-error-msg').remove();
						},
						1500
					);
				}
			} );
		},

		/**
		 * License related events.
		 *
		 * @since 4.4.0
		 */
		licenseEvents: function () {

			if ( _SEARCHWP.canUserCreateTemplates ) {
				return;
			}

			$( '#searchwp-create-results-template' ).on(
				'click',
				(e) =>
				{
					e.preventDefault();
					$( '#searchwp-license-fullscreen-notice' ).show();
				}
			);

			$( '#searchwp-license-fullscreen-notice .dismiss' ).on(
				'click',
				(e) =>
				{
					e.preventDefault();
					$( '#searchwp-license-fullscreen-notice' ).hide();
				}
			);
		},
    };

    app.init();

    window.searchwp = window.searchwp || {};

    window.searchwp.AdminSearchResultsPage = app;

}( jQuery ) );
