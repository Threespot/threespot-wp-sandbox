<?php
/**
 * Shortcode for ld_certificate
 * 
 * @since 3.2
 * 
 * @package LearnDash\Shortcodes
 */

function ld_certificate_shortcode( $atts = array(), $content = '' ) {
	global $learndash_shortcode_used;
	
	if ( ! is_array( $atts ) ) {
		$atts = array();
	}

	$defaults = array(
		'course_id'  => 0,
		'quiz_id'	 => 0,
		'user_id'    => get_current_user_id(),
		'label'      => esc_html__( 'Certificate', 'learndash' ),
		//'id'         => '',
		'class'      => 'button',
		//'target'     => '', //'_blank',
		//'aria-label' => esc_html__( 'link text - new window', 'learndash' ),
		'context'    => '',	// User defined value.
		'callback'   => '',	// User defined value.
	);
	$atts = shortcode_atts( $defaults, $atts );

	$atts['course_id'] = absint( $atts['course_id'] );
	$atts['quiz_id']   = absint( $atts['quiz_id'] );
	$atts['user_id']   = absint( $atts['user_id'] );

	if ( empty( $atts['course_id'] ) ) {
		$atts['course_id'] = learndash_get_course_id();
	}
	if ( empty( $atts['quiz_id'] ) ) {
		$atts['quiz_id'] = learndash_get_quiz_id();
	}

	//if ( ( '_blank' === $atts['target'] ) && ( empty( $atts['aria-label'] ) ) ) {
	//	$atts['aria-label'] = esc_html__( 'link text - new window', 'learndash' );
	//}

	/**
	 * Allow filtering of the shortcode attributes.
	 *
	 * @since 3.2
	 * @param array $atts Array of shortcode attributes.
	 */
	$atts = apply_filters( 'ld_certificate_shortcode_values', $atts );
	
	$atts['cert_url']  = '';

	if ( ! empty( $atts['user_id'] ) ) {
		if ( ( ! empty( $atts['course_id'] ) ) || ( ! empty( $atts['quiz_id'] ) ) ) {
			$learndash_shortcode_used = true;
			$cert_button_html = '';
			if ( ! empty( $atts['quiz_id'] ) ) {
				// Ensure the user passed the Quiz.
				if ( learndash_is_quiz_complete( $atts['user_id'], $atts['quiz_id'], $atts['course_id'] ) ) {
					$cert_details = learndash_certificate_details( $atts['quiz_id'], $atts['user_id'] );
					if ( ( isset( $cert_details['certificateLink'] ) ) && ( ! empty( $cert_details['certificateLink'] ) ) ) {
						$atts['cert_url'] = $cert_details['certificateLink'];
					}
				}
			} else if ( ! empty( $atts['course_id'] ) ) {
				// Ensure the user completed the Course.
				if ( 'completed' === learndash_course_status( $atts['course_id'], $atts['user_id'], true ) ) {
					$atts['cert_url'] = learndash_get_course_certificate_link( $atts['course_id'], $atts['user_id'] );
				}
			}

			if ( ! empty( $atts['cert_url'] ) ) {
				/**
				 * Allow filtering of the cert url
				 *
				 * @since 3.2
				 * @param URL cert_url URL for Certificate.
				 */
				$atts['cert_url'] = apply_filters( 'ld_certificate_shortcode_cert_url', $atts['cert_url'] );

				if ( ( ! empty( $atts['callback'] ) ) && ( is_callable( $atts['callback'] ) ) ) {
					$cert_button_html = call_user_func( $atts['callback'], $atts );
				} else {
					$cert_button_html = '<a href="'. esc_url( $atts['cert_url'] ) . '"' . 
					( ! empty( $atts['class'] ) ? ' class="' . esc_attr( $atts['class'] ) : '' ) . '"' .
					( ! empty( $atts['id'] ) ? ' id="' . esc_attr( $atts['id'] ) . '"' : '' ) .
					//( ! empty( $atts['target'] ) ? ' target="' . esc_attr( $atts['target'] ) . '"' : '' ) . 
					//( ! empty( $atts['aria-label'] ) ? ' aria-label="' . esc_html( $atts['aria-label'] ) . '"' : '' ) . 
					'>';

					if ( ! empty( $atts['label'] ) ) {
						$cert_button_html .= do_shortcode( $atts['label'] );
					}

					$cert_button_html .= '</a>';
				}
			}

			/**
     		 * Filter to allow override of shortcode button HTML before added to content
     		 *
    		 * @since 3.2
			 * @param html $cert_button_html HTML of generated button element.
			 * @param array $atts Array of shortcode attributes used to generate $cert_button_html element.
			 * @param string $content Shortcode additional content passed into handler function.
     		 */
			$cert_button_html = apply_filters( 'learndash_ld_certificate_html', $cert_button_html, $atts, $content );
			if ( ! empty( $cert_button_html ) ) {
				$content .= $cert_button_html;
			}
		}
	}

	return $content;
}
add_shortcode( 'ld_certificate', 'ld_certificate_shortcode', 10, 2 );
