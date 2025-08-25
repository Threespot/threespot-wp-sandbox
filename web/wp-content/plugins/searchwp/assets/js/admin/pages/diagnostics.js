/* global _SEARCHWP */

( function ( $ ) {
    'use strict';

    const app = {
        init: function () {
            this.events();
        },

        events: function () {
            $( document ).on( 'submit', '.searchwp-diagnostics-indexed-tokens', this.handleIndexedTokens );
            $( document ).on( 'submit', '.searchwp-diagnostics-unindexed-entries', this.handleUnindexedEntries );
        },

        handleIndexedTokens: function (e) {

            e.preventDefault();
            const $form    = $( this );
            const $display = $( '.searchwp-diagnostics-indexed-tokens-display' );
            const source   = $form.find( 'select[name="searchwp_source"]' ).val();
            const id       = $form.find( 'input[name="searchwp_source_entry_id"]' ).val();

			$.ajax(
				{
					url: ajaxurl,
					type: 'POST',
					data: {
						action: _SEARCHWP.prefix + 'diagnostics_get_indexed_tokens',
						source: source,
						id: id,
						_ajax_nonce: _SEARCHWP.nonce
					},
					success: function (response) {
						if ( response.success ) {
							let dataLength = response.data.length;
							let results    = '<p class="description">Found tokens: ' + parseInt( dataLength, 10 ) + '</p>';

							if ( dataLength ) {
								results += '<ul>';
								for ( var i = 0; i < dataLength; i++ ) {
									results += '<li>' + response.data[i] + '</li>';
								}
								results += '</ul>';
								$display.html( results );
							} else {
								$display.html( '<p class="swp-p">No tokens found.</p>' );
							}
						} else {
							$display.html( '<p class="swp-p">Error retrieving tokens.</p>' );
						}
					},
					error: function () {
						$display.html( '<p class="swp-p">Error retrieving tokens.</p>' );
					}
				}
			);
		},

        handleUnindexedEntries: function (e) {

            e.preventDefault();
            const $form    = $( this );
            const $display = $( '.searchwp-diagnostics-unindexed-entries-display' );
            const source   = $form.find( 'select[name="searchwp_source"]' ).val();

            $.ajax(
				{
					url: ajaxurl,
					type: 'POST',
					data: {
						action: _SEARCHWP.prefix + 'diagnostics_get_unindexed_entries',
						source: source,
						_ajax_nonce: _SEARCHWP.nonce
                	},
					success: function ( response ) {
						if ( response.success ) {
							const entries = response.data;
							let results   = '<p class="description">Unindexed IDs: ' + parseInt( response.data.length, 10 ) + '</p>';

							if ( entries.length ) {
								results += '<ul>';
								entries.forEach(
									function ( id ) {
										results += '<li>' + id + '</li>';
									}
								);
								results += '</ul>';
								$display.html( results );
							} else {
								$display.html( '<p class="swp-p">No unindexed entries found.</p>' );
							}
						} else {
							$display.html( '<p class="swp-p">Error retrieving unindexed entries.</p>' );
						}
					},
					error: function () {
						$display.html( '<p class="swp-p">Error retrieving unindexed entries.</p>' );
					}
            	}
			);
        }
    };

    $( app.init.bind( app ) );
})( jQuery );
