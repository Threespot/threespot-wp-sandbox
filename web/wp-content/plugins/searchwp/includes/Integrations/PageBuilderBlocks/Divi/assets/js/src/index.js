/* global searchwp_forms_divi_builder, searchwp_templates_divi_builder */

import React, { Component } from 'react';
import PropTypes from 'prop-types';

/**
 * SearchWPFormsSelector component.
 *
 * @since 4.5.0
 */
class SearchWPFormsSelector extends Component {

	/**
	 * Module slug.
	 *
	 * @since 4.5.0
	 *
	 * @type {string}
	 */
	static slug = 'searchwp_forms_selector';

	/**
	 * Constructor.
	 *
	 * @since 4.5.0
	 *
	 * @param {string} props List of properties.
	 */
	constructor( props ) {

		super( props );

		this.state = {
			error: null,
			isLoading: true,
			form: null,
		};
	}

	/**
	 * Set types for properties.
	 *
	 * @since 4.5.0
	 *
	 * @returns {object} Properties type.
	 */
	static get propTypes() {

		return {
			form_id: PropTypes.number,
		};
	}

	/**
	 * Check if form settings was updated.
	 *
	 * @since 4.5.0
	 *
	 * @param {object} prevProps List of previous properties.
	 */
	componentDidUpdate( prevProps ) {

		if ( prevProps.form_id !== this.props.form_id ) {
			this.componentDidMount();
		}
	}

	/**
	 * Ajax request for form HTML.
	 *
	 * @since 4.5.0
	 */
	componentDidMount() {

		var formData = new FormData();

		formData.append( 'nonce', searchwp_forms_divi_builder.nonce );
		formData.append( 'action', 'searchwp_forms_divi_preview' );
		formData.append( 'form_id', this.props.form_id );

		fetch(
			searchwp_forms_divi_builder.ajax_url,
			{
				method: 'POST',
				cache: 'no-cache',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
					'Cache-Control': 'no-cache',
				},
				body: new URLSearchParams( formData ),
			},
		)
			.then( res => res.json() )
			.then(
				( result ) => {

					this.setState( {
						isLoading: false,
						form: result.data,
					} );
				},
				( error ) => {

					this.setState( {
						isLoading: false,
						error,
					} );
				},
			);
	}

	/**
	 * Render module view.
	 *
	 * @since 4.5.0
	 *
	 * @returns {JSX.Element} View for module.
	 */
	render() {
		const { error, isLoading, form } = this.state,
			wrapperClasses = isLoading ? 'searchwp-divi-form-preview loading' : 'searchwp-divi-form-preview';

		if ( error || ! form ) {
			return (
				<div className="searchwp-divi-form-placeholder">
					<img src={ searchwp_forms_divi_builder.placeholder } alt="" />
					<p className="searchwp-divi-placeholder-text">
						{ searchwp_forms_divi_builder.placeholder_text }
					</p>
				</div>
			);
		}

		return (
			<div className={ wrapperClasses }>
				{ <div dangerouslySetInnerHTML={ { __html: form } } /> }
			</div>
		);
	}
}

/**
 * SearchWPTemplatesSelector component.
 *
 * @since 4.5.0
 */
class SearchWPTemplatesSelector extends Component {

	/**
	 * Module slug.
	 *
	 * @since 4.5.0
	 *
	 * @type {string}
	 */
	static slug = 'searchwp_templates_selector';

	/**
	 * Constructor.
	 *
	 * @since 4.5.0
	 *
	 * @param {string} props List of properties.
	 */
	constructor( props ) {

		super( props );

		this.state = {
			error: null,
			template: null,
		};
	}

	/**
	 * Set types for properties.
	 *
	 * @since 4.5.0
	 *
	 * @returns {object} Properties type.
	 */
	static get propTypes() {

		return {
			template_id: PropTypes.number,
			engine: PropTypes.string,
		};
	}

	/**
	 * Render module view.
	 *
	 * @since 4.5.0
	 *
	 * @returns {JSX.Element} View for module.
	 */
	render() {

		return (
			<div className="searchwp-divi-template-placeholder">
				<img src={ searchwp_templates_divi_builder.placeholder } alt="" />
				<p className="searchwp-divi-placeholder-text">{ searchwp_templates_divi_builder.placeholder_text_start }&nbsp;
				<a href={ searchwp_templates_divi_builder.guide_url } onClick={
					() => {
						window.open( searchwp_templates_divi_builder.placeholder_forms_url, '_blank' );
					}
				}
				>
					{ searchwp_templates_divi_builder.placeholder_forms_link }
				</a>
					&nbsp;{ searchwp_templates_divi_builder.placeholder_text_end }</p>
			</div>
		);
	}
}

jQuery( window ).on(
	'et_builder_api_ready',
	( event, API ) =>
	{
		// Register modules.
		API.registerModules( [ SearchWPFormsSelector, SearchWPTemplatesSelector ] );
	}
);
