// External dependencies
import React, { Component, Fragment } from 'react';

// WS Form component
class Component_WS_Form extends Component {

	// Slug
	static slug = 'ws_form_divi';

	constructor(props) {

		super(props);

		// State variables
		this.state = {

			isLoaded: false,
			ajaxHTML: null,
			formIDCurrent: null,
			instanceID: 1
		};
	}

	componentDidMount() {

		// Set instance ID to window instance ID variable (global)
		this.setState({

			instanceID: window.ws_form_divi_instance_id
		});

		// Increment window instance ID variable
		window.ws_form_divi_instance_id++;
	}

	componentDidUpdate() {

		// Get state variables as constants
		const { formIDCurrent, isLoaded, instanceID } = this.state;

		// If this form AJAX has not been loaded (or form changes), load it via AJAX
		if(
			(this.props.form_id > 0) &&
			(formIDCurrent !== this.props.form_id)
		) {

			// Set initial states
			this.setState({

				isLoaded: false,
				ajaxHTML: null,
				formIDCurrent: this.props.form_id
			});

			// Build request body
			var body = new FormData();
			body.append('action', 'ws_form_divi_form');
			body.append('et_admin_load_nonce ', window.et_fb_options.et_admin_load_nonce);
			body.append('form_id', this.props.form_id);
			body.append('instance_id', instanceID);

			// Fetch
			fetch(

				window.et_fb_options.ajaxurl, 
				{
					body: body,
					method: 'POST',        
				}
			)
			.then(res => res.text())
			.then(

				(text) => {

					this.setState({

						isLoaded: true,
						ajaxHTML: text
					});
				}
			)
		}

		// If this component is loaded, initialize it using frontend.js
		if(isLoaded) {

			window.ws_form_divi_init(this.props.form_id);
		}
	}

	render() {

		// Get isLoaded state
		const { isLoaded } = this.state;

		// Returns
		if(!parseInt(this.props.form_id)) {

			// Select form
			return(<Fragment><div class="ws_form_divi_no_form_id"><h2>WS Form</h2><p>Select the form that you would like to use for this module.</p></div></Fragment>);

		} else if (!isLoaded) {

			// Show Divi loader
			return (<div class="et-fb-loader" style={{'position': 'relative', 'margin-bottom': '40px;'}}></div>);

		} else {

			// Render shortcode markup
			return (<Fragment><div class="ws_form_divi_ajax" dangerouslySetInnerHTML={this.createMarkup()} /></Fragment>);
		}
	}

	createMarkup() {

		// Get HTML markup
		const { ajaxHTML } = this.state;

		return {__html: ajaxHTML};
	}
}

export default Component_WS_Form;
