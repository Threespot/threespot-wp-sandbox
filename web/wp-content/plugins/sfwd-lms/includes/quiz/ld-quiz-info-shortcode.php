<?php
/**
 * Shortcodes for displaying Quiz and Course info
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */



/**
 * Shortcode that displays the requested quiz information
 *
 * @since 2.1.0
 *
 * @param  array $attr shortcode attributes
 * @return string      shortcode output
 */
function learndash_quizinfo( $attr ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			'show'     => '', // [score], [count], [pass], [rank], [timestamp], [pro_quizid], [points], [total_points], [percentage], [timespent]
			'user_id'  => '',
			'quiz'     => '',
			'time'     => '',
			'field_id' => '',
			'format'   => 'F j, Y, g:i a',
		),
		$attr
	);

	extract( $shortcode_atts );

	$time      = ( empty( $time ) && isset( $_REQUEST['time'] ) ) ? $_REQUEST['time'] : $time;
	$show      = ( empty( $show ) && isset( $_REQUEST['show'] ) ) ? $_REQUEST['show'] : $show;
	$quiz      = ( empty( $quiz ) && isset( $_REQUEST['quiz'] ) ) ? $_REQUEST['quiz'] : $quiz;
	$user_id   = ( empty( $user_id ) && isset( $_REQUEST['user_id'] ) ) ? $_REQUEST['user_id'] : $user_id;
	$course_id = ( empty( $course_id ) && isset( $_REQUEST['course_id'] ) ) ? $_REQUEST['course_id'] : null;
	$field_id  = ( empty( $field_id ) && isset( $_REQUEST['field_id'] ) ) ? $_REQUEST['field_id'] : $field_id;

	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();

		/**
		 * Added logic to allow admin and group_leader to view certificate from other users.
		 *
		 * @since 2.3
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( $post_type == 'sfwd-certificates' ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
				$user_id = intval( $_GET['user'] );
			}
		}
	}

	if ( empty( $quiz ) || empty( $user_id ) || empty( $show ) ) {
		return '';
	}

	$shortcode_atts['time']      = $time;
	$shortcode_atts['show']      = $show;
	$shortcode_atts['quiz']      = $quiz;
	$shortcode_atts['user_id']   = $user_id;
	$shortcode_atts['course_id'] = $course_id;
	$shortcode_atts['field_id']  = $field_id;

	$quizinfo = get_user_meta( $user_id, '_sfwd-quizzes', true );

	$selected_quizinfo  = '';
	$selected_quizinfo2 = '';

	foreach ( $quizinfo as $quiz_i ) {

		if ( isset( $quiz_i['time'] ) && $quiz_i['time'] == $time && $quiz_i['quiz'] == $quiz ) {
			$selected_quizinfo = $quiz_i;
			break;
		}

		if ( $quiz_i['quiz'] == $quiz ) {
			$selected_quizinfo2 = $quiz_i;
		}
	}

	$selected_quizinfo = empty( $selected_quizinfo ) ? $selected_quizinfo2 : $selected_quizinfo;

	switch ( $show ) {
		case 'timestamp':
			// date_default_timezone_set( get_option( 'timezone_string' ) );
			// $selected_quizinfo['timestamp'] = date_i18n( $format, $selected_quizinfo['time'] );
			$selected_quizinfo['timestamp'] = learndash_adjust_date_time_display( $selected_quizinfo['time'], $format );
			break;

		case 'percentage':
			if ( empty( $selected_quizinfo['percentage'] ) ) {
				$selected_quizinfo['percentage'] = empty( $selected_quizinfo['count'] ) ? 0 : $selected_quizinfo['score'] * 100 / $selected_quizinfo['count'];
			}

			break;

		case 'pass':
			$selected_quizinfo['pass'] = ! empty( $selected_quizinfo['pass'] ) ? esc_html__( 'Yes', 'learndash' ) : esc_html__( 'No', 'learndash' );
			break;

		case 'quiz_title':
			$quiz_post = get_post( $quiz );

			if ( ! empty( $quiz_post->post_title ) ) {
				$selected_quizinfo['quiz_title'] = $quiz_post->post_title;
			}

			break;

		case 'course_title':
			if ( ( isset( $selected_quizinfo['course'] ) ) && ( ! empty( $selected_quizinfo['course'] ) ) ) {
				$course_id = intval( $selected_quizinfo['course'] );
			} else {
				$course_id = learndash_get_setting( $quiz, 'course' );
			}
			if ( ! empty( $course_id ) ) {
				$course = get_post( $course_id );
				if ( ( is_a( $course, 'WP_Post' ) ) && ( ! empty( $course->post_title ) ) ) {
					$selected_quizinfo['course_title'] = $course->post_title;
				}
			}

			break;

		case 'timespent':
			$selected_quizinfo['timespent'] = isset( $selected_quizinfo['timespent'] ) ? learndash_seconds_to_time( $selected_quizinfo['timespent'] ) : '';
			break;

		case 'field':
			if ( ! empty( $field_id ) ) {
				if ( ( isset( $selected_quizinfo['pro_quizid'] ) ) && ( ! empty( $selected_quizinfo['pro_quizid'] ) ) ) {
					$formMapper         = new WpProQuiz_Model_FormMapper();
					$quiz_form_elements = $formMapper->fetch( $selected_quizinfo['pro_quizid'] );
					if ( ! empty( $quiz_form_elements ) ) {
						foreach ( $quiz_form_elements as $quiz_form_element ) {
							if ( absint( $field_id ) == absint( $quiz_form_element->getFormId() ) ) {
								$selected_quizinfo[ $show ] = '';

								if ( ( isset( $selected_quizinfo['statistic_ref_id'] ) ) && ( ! empty( $selected_quizinfo['statistic_ref_id'] ) ) ) {
									$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
									$statisticRefData   = $statisticRefMapper->fetchAllByRef( $selected_quizinfo['statistic_ref_id'] );
									if ( ( $statisticRefData ) && ( is_a( $statisticRefData, 'WpProQuiz_Model_StatisticRefModel' ) ) ) {
										$form_data = $statisticRefData->getFormData();
										if ( isset( $form_data[ $field_id ] ) ) {
											$selected_quizinfo[ $show ] = $quiz_form_element->getValue( $form_data[ $field_id ] );
										}
									}
								}
								break;
							}
						}
					}
				}
			}
			break;

	}

	/**
	 * Filter for quizinfo shortcode output.
	 *
	 * @since 2.1.0
	 * @since 3.1.4 Added $selected_quizinfo param.
	 *
	 * @param mixed Value of 'show' paramter.
	 * @param array $shortcode_atts Array of shortcode attributed.
	 * @param array $selected_quizinfo Quiz item array used for processing.
	 */
	if ( isset( $selected_quizinfo[ $show ] ) ) {
		return apply_filters( 'learndash_quizinfo', $selected_quizinfo[ $show ], $shortcode_atts, $selected_quizinfo );
	} else {
		return apply_filters( 'learndash_quizinfo', '', $shortcode_atts, $selected_quizinfo );
	}
}

add_shortcode( 'quizinfo', 'learndash_quizinfo' );



/**
 * Shortcode that displays the requested course information
 *
 * @since 2.1.0
 *
 * @param array $attr shortcode attributes.
 *
 * @return string shortcode output
 */
function learndash_courseinfo( $attr ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts(
		array(
			'show'           => 'course_title',
			'user_id'        => '',
			'course_id'      => '',
			'format'         => 'F j, Y, g:i a',
			'seconds_format' => 'time',
			'decimals'       => 2,
		),
		$attr
	);

	$shortcode_atts['course_id'] = ! empty( $shortcode_atts['course_id'] ) ? $shortcode_atts['course_id'] : '';
	if ( '' === $shortcode_atts['course_id'] ) {
		if ( ( isset( $_GET['course_id'] ) ) && ( ! empty( $_GET['course_id'] ) ) ) {
			$shortcode_atts['course_id'] = intval( $_GET['course_id'] );
		} else {
			$shortcode_atts['course_id'] = learndash_get_course_id();
		}
	}

	$shortcode_atts['user_id'] = ! empty( $shortcode_atts['user_id'] ) ? $shortcode_atts['user_id'] : '';
	if ( '' === $shortcode_atts['user_id'] ) {
		if ( ( isset( $_GET['user_id'] ) ) && ( ! empty( $_GET['user_id'] ) ) ) {
			$shortcode_atts['user_id'] = intval( $_GET['user_id'] );
		}
	}

	if ( empty( $shortcode_atts['user_id'] ) ) {
		$shortcode_atts['user_id'] = get_current_user_id();

		/**
		 * Added logic to allow admin and group_leader to view certificate from other users.
		 *
		 * @since 2.3
		 */
		$post_type = '';
		if ( get_query_var( 'post_type' ) ) {
			$post_type = get_query_var( 'post_type' );
		}

		if ( 'sfwd-certificates' == $post_type ) {
			if ( ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) && ( ( isset( $_GET['user'] ) ) && ( ! empty( $_GET['user'] ) ) ) ) {
				$shortcode_atts['user_id'] = intval( $_GET['user'] );
			}
		}
	}

	if ( empty( $shortcode_atts['course_id'] ) || empty( $shortcode_atts['user_id'] ) ) {
		return apply_filters( 'learndash_courseinfo', '', $shortcode_atts );
	}

	$shortcode_atts['show'] = strtolower( $shortcode_atts['show'] );

	switch ( $shortcode_atts['show'] ) {
		case 'course_title':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = $course->post_title;
			}
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			break;

		case 'course_url':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = get_permalink( $shortcode_atts['course_id'] );
			}
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			break;

		case 'course_price_type':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_setting( $shortcode_atts['course_id'], 'course_price_type' );
			}
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			break;

		case 'course_price':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course = get_post( $shortcode_atts['course_id'] );
			if ( ( $course ) && ( is_a( $course, 'WP_Post' ) ) ) {
				$shortcode_atts[ $shortcode_atts['show'] ] = learndash_get_setting( $shortcode_atts['course_id'], 'course_price' );
			}
			return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			break;

		case 'user_course_time':
			$shortcode_atts[ $shortcode_atts['show'] ] = 0;

			if ( ! empty( $shortcode_atts['user_id'] ) ) {
				$activity_query_args             = array(
					'post_types'     => learndash_get_post_type_slug( 'course' ),
					'activity_types' => 'course',
					'per_page'       => 1,
					'page'           => 1,
				);
				$activity_query_args['user_ids'] = $shortcode_atts['user_id'];
				$activity_query_args['post_ids'] = $shortcode_atts['course_id'];

				$user_courses_reports = learndash_reports_get_activity( $activity_query_args );
				if ( ! empty( $user_courses_reports['results'] ) ) {
					$activity_started   = 0;
					$activity_completed = 0;
					foreach ( $user_courses_reports['results'] as $course_activity ) {

						if ( ( property_exists( $course_activity, 'activity_started' ) ) && ( ! empty( $course_activity->activity_started ) ) ) {
							$activity_started = $course_activity->activity_started;
						}
						if ( ( property_exists( $course_activity, 'activity_completed' ) ) && ( ! empty( $course_activity->activity_completed ) ) ) {
							$activity_completed = $course_activity->activity_completed;
						} elseif ( ( property_exists( $course_activity, 'activity_updated' ) ) && ( ! empty( $course_activity->activity_updated ) ) ) {
							$activity_completed = $course_activity->activity_updated;
						}
						// There should only be one user+course entry. But just in case we break out of our loop here.
						break;
					}

					if ( ( ! empty( $activity_started ) ) && ( ! empty( $activity_completed ) ) ) {
						$shortcode_atts[ $shortcode_atts['show'] ] = absint( $activity_completed ) - absint( $activity_started );
					}
				}
			}

			if ( 'time' === $shortcode_atts['seconds_format'] ) {
				return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $shortcode_atts[ $shortcode_atts['show'] ] ), $shortcode_atts );
			} else {
				return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			}

			break;

		case 'cumulative_score':
		case 'cumulative_points':
		case 'cumulative_total_points':
		case 'cumulative_percentage':
		case 'cumulative_timespent':
		case 'cumulative_count':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$field    = str_replace( 'cumulative_', '', $shortcode_atts['show'] );
			$quizdata = get_user_meta( $shortcode_atts['user_id'], '_sfwd-quizzes', true );
			$quizzes  = learndash_course_get_steps_by_type( intval( $shortcode_atts['course_id'] ), 'sfwd-quiz' );
			if ( empty( $quizzes ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			/**
			 * Filters Quizzes to be included in calculations.
			 *
			 * @since 3.1.2
			 * @param array $quizzes        Array of Quiz IDs to be processed.
			 * @param array $shortcode_atts Array of shortcode attributes.
			 * @return array of Quiz IDs.
			*/
			$quizzes = apply_filters( 'learndash_courseinfo_quizzes', $quizzes, $shortcode_atts );

			$scores = array();

			if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
				foreach ( $quizdata as $data ) {
					if ( ( is_array( $quizzes ) ) && ( ( in_array( $data['quiz'], $quizzes ) ) ) ) {
						if ( ( ! isset( $data['course'] ) ) || ( intval( $data['course'] ) == intval( $shortcode_atts['course_id'] ) ) ) {
							if ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) {
								$scores[ $data['quiz'] ] = $data[ $field ];
							}
						}
					}
				}
			}

			if ( empty( $scores ) || ! count( $scores ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$sum = 0;

			foreach ( $scores as $score ) {
				$sum += $score;
			}

			$return = number_format( $sum / count( $scores ), $shortcode_atts['decimals'] );

			$shortcode_atts[ $shortcode_atts['show'] ] = $return;

			if ( 'timespent' == $field ) {
				if ( 'time' === $shortcode_atts['seconds_format'] ) {
					return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $shortcode_atts[ $shortcode_atts['show'] ] ), $shortcode_atts );
				} else {
					return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
				}
			} else {
				return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			}
			break;

		case 'aggregate_percentage':
		case 'aggregate_score':
		case 'aggregate_points':
		case 'aggregate_total_points':
		case 'aggregate_timespent':
		case 'aggregate_count':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$field    = substr_replace( $shortcode_atts['show'], '', 0, 10 );
			$quizdata = get_user_meta( $shortcode_atts['user_id'], '_sfwd-quizzes', true );
			$quizzes  = learndash_course_get_steps_by_type( intval( $shortcode_atts['course_id'] ), 'sfwd-quiz' );
			if ( empty( $quizzes ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			/**
			 * Filters Quizzes to be included in calculations.
			 *
			 * @since 3.1.2
			 * @param array $quizzes        Array of Quiz IDs to be processed.
			 * @param array $shortcode_atts Array of shortcode attributes.
			 * @return array of Quiz IDs.
			*/
			$quizzes = apply_filters( 'learndash_courseinfo_quizzes', $quizzes, $shortcode_atts );

			$scores = array();

			if ( ( ! empty( $quizdata ) ) && ( is_array( $quizdata ) ) ) {
				foreach ( $quizdata as $data ) {
					if ( ( is_array( $quizzes ) ) && ( ( in_array( $data['quiz'], $quizzes ) ) ) ) {
						if ( ( empty( $scores[ $data['quiz'] ] ) || $scores[ $data['quiz'] ] < $data[ $field ] ) ) {
							if ( ( ! isset( $data['course'] ) ) || ( intval( $data['course'] ) == intval( $shortcode_atts['course_id'] ) ) ) {
								$scores[ $data['quiz'] ] = $data[ $field ];
							}
						}
					}
				}
			}

			if ( empty( $scores ) || ! count( $scores ) ) {
				return apply_filters( 'learndash_courseinfo', 0, $shortcode_atts );
			}

			$sum = 0;

			foreach ( $scores as $score ) {
				$sum += $score;
			}

			$return                                    = number_format( $sum, $shortcode_atts['decimals'] );
			$shortcode_atts[ $shortcode_atts['show'] ] = $return;

			if ( 'timespent' == $field ) {
				if ( 'time' === $shortcode_atts['seconds_format'] ) {
					return apply_filters( 'learndash_courseinfo', learndash_seconds_to_time( $shortcode_atts[ $shortcode_atts['show'] ] ), $shortcode_atts['show'] );
				} else {
					return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts['show'] );
				}
			} else {
				return apply_filters( 'learndash_courseinfo', $shortcode_atts[ $shortcode_atts['show'] ], $shortcode_atts );
			}

		case 'completed_on':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$completed_on = get_user_meta( $shortcode_atts['user_id'], 'course_completed_' . $shortcode_atts['course_id'], true );

			if ( empty( $completed_on ) ) {
				$completed_on = learndash_user_get_course_completed_date( $shortcode_atts['user_id'], $shortcode_atts['course_id'] );
				if ( empty( $completed_on ) ) {
					return apply_filters( 'learndash_courseinfo', '-', $shortcode_atts );
				}
			}

			$shortcode_atts[ $shortcode_atts['show'] ] = $completed_on;
			return apply_filters( 'learndash_courseinfo', learndash_adjust_date_time_display( $completed_on, $shortcode_atts['format'] ), $shortcode_atts );
			break;

		case 'enrolled_on':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$enrolled_on = get_user_meta( $shortcode_atts['user_id'], 'course_' . $shortcode_atts['course_id'] . '_access_from', true );
			if ( empty( $enrolled_on ) ) {
				return apply_filters( 'learndash_courseinfo', '-', $shortcode_atts );
			}

			$shortcode_atts[ $shortcode_atts['show'] ] = $enrolled_on;
			return apply_filters( 'learndash_courseinfo', learndash_adjust_date_time_display( $enrolled_on, $shortcode_atts['format'] ), $shortcode_atts );
			break;

		case 'course_points':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$course_points                             = learndash_get_course_points( $shortcode_atts['course_id'], $shortcode_atts['decimals'] );
			$course_points                             = number_format( $course_points, $shortcode_atts['decimals'] );
			$shortcode_atts[ $shortcode_atts['show'] ] = $course_points;
			return apply_filters( 'learndash_courseinfo', $course_points, $shortcode_atts );

			break;

		case 'user_course_points':
			$shortcode_atts[ $shortcode_atts['show'] ] = '';

			$user_course_points                        = learndash_get_user_course_points( $shortcode_atts['user_id'] );
			$user_course_points                        = number_format( $user_course_points, $shortcode_atts['decimals'] );
			$shortcode_atts[ $shortcode_atts['show'] ] = $user_course_points;
			return apply_filters( 'learndash_courseinfo', $user_course_points, $shortcode_atts );

			break;

		default:
			return apply_filters( 'learndash_courseinfo', '', $shortcode_atts );
	}
}

add_shortcode( 'courseinfo', 'learndash_courseinfo' );
