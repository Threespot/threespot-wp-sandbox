<?php

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class SearchWP_Metrics_i18n {

	public $strings;
	public $options;

	function __construct() {

		$this->strings = array(
			'add'                                     => esc_html__( 'Add', 'searchwp-metrics' ),
			'add_as_partial_match'                    => esc_html__( 'Add as a partial match', 'searchwp-metrics' ),
			'add_as_exact_match'                      => esc_html__( 'Add as an exact match', 'searchwp-metrics' ),
			'all_engines_selected'                    => esc_html__( 'All engines selected', 'searchwp-metrics' ),
			'are_you_sure'                            => esc_html__( 'Are you sure?', 'searchwp-metrics' ),
			'average_click_rank'                      => esc_html__( 'Average Click Rank', 'searchwp-metrics' ),
			'average_rank'                            => esc_html__( 'Average Rank', 'searchwp-metrics' ),
			'cancel'                                  => esc_html__( 'Cancel', 'searchwp-metrics' ),
			'choose_engine'                           => esc_html__( 'Choose an engine', 'searchwp-metrics' ),
			'clear_data'                              => esc_html__( 'Clear Data', 'searchwp-metrics' ),
			'clear_metrics_data'                      => esc_html__( 'Clear Metrics Data', 'searchwp-metrics' ),
			'clear_metrics_data_at_intervals'         => esc_html__( 'Clear Data at Intervals', 'searchwp-metrics' ),
			'clear_metrics_interval_0'                => esc_html__( 'Never', 'searchwp-metrics' ),
			'clear_metrics_interval_30'               => esc_html__( '1 Month', 'searchwp-metrics' ),
			'clear_metrics_interval_90'               => esc_html__( '3 Months', 'searchwp-metrics' ),
			'clear_metrics_interval_180'              => esc_html__( '6 Months', 'searchwp-metrics' ),
			'clear_metrics_interval_365'              => esc_html__( '1 Year', 'searchwp-metrics' ),
			'clear_metrics_interval_custom'           => esc_html__( 'Custom Interval', 'searchwp-metrics' ),
			'clear_metrics_data_before'               => esc_html__( 'Clear Data Before', 'searchwp-metrics' ),
			'clear_metrics_data_note'                 => wp_kses(
				__( 'This will remove all the data Metrics has logged to date.', 'searchwp-metrics' ),
				[
					'strong' => [],
				]
			),
			'clear_metrics_data_before_note'          => wp_kses(
				__( 'This will remove all the data Metrics has logged <strong>before</strong> the date specified below.', 'searchwp-metrics' ),
				[
					'strong' => [],
				]
			),
			'clear_metrics_data_at_intervals_note'    => wp_kses(
				__( 'Automatically delete Metrics data older than the selected interval.', 'searchwp-metrics' ),
				[
					'strong' => [],
				]
			),
			'clear_metrics_data_interval_custom_note' => esc_html__( 'Set a custom number of days.', 'searchwp-metrics' ),
			'clear_metrics_data_note_details'         => wp_kses(
				__( 'Once cleared, this data <strong>cannot be restored!</strong>', 'searchwp-metrics' ),
				[
					'strong' => [],
				]
			),
			'clear_ignored_queries_note'              => wp_kses(
				__( 'Are you sure you want to clear all ignored queries? <strong>This cannot be undone!</strong>', 'searchwp-metrics' ),
				[
					'strong' => [],
				]
			),
			'clicked_result'                          => esc_html__( 'Clicked Result', 'searchwp-metrics' ),
			'clicks'                                  => esc_html__( 'Clicks', 'searchwp-metrics' ),
			'clicks_per_search'                       => esc_html__( 'Clicks Per Search', 'searchwp-metrics' ),
			'click_tracking_note'                     => wp_kses(
				sprintf(
					// Translators: first placeholder opens the link to the template customization docs, second placeholder closes it.
					__( 'Click tracking requires a small %1$stemplate customization%2$s to be in place', 'searchwp-metrics' ),
					'<a href="https://searchwp.com/extensions/metrics/#tracking" target="_blank">',
					'</a>'
				),
				[
					'a' => [
						'href'   => [],
						'target' => [],
					],
				]
			),
			'click_tracking_buoy'                     => esc_html__( 'Influence search results using click data', 'searchwp-metrics' ),
			'click_tracking_buoy_label_note'          => esc_html__( 'When enabled, Metrics will use its data to add weight to results based on conversion rate (clicks)', 'searchwp-metrics' ),
			'click_tracking_buoy_unavailable'         => esc_html__( 'To enable the ability to influence search results ranking, update to SearchWP 2.9.14 or higher.', 'searchwp-metrics' ),
			'close'                                   => esc_html__( 'Close', 'searchwp-metrics' ),
			'conversion_rate'                         => esc_html__( 'Conversion Rate', 'searchwp-metrics' ),
			'date'                                    => esc_html__( 'Date', 'searchwp-metrics' ),
			'date_range'                              => esc_html__( 'Date Range', 'searchwp-metrics' ),
			// Translators: abbreviated day names separated by underscore.
			'days_abbr'                               => esc_html__( 'Su_Mo_Tu_We_Th_Fr_Sa', 'searchwp-metrics' ),
			'details'                                 => esc_html__( 'Details', 'searchwp-metrics' ),
			'engine_details_for_timeline'             => sprintf(
				// Translators: placeholder is the engine label.
				esc_html__( '%s Engine - Details for this Date Range', 'searchwp-metrics' ),
				'{{ engine.label }}'
			),
			'engine_statistics'                       => esc_html__( 'Engine Statistics', 'searchwp-metrics' ),
			'engines_to_display'                      => esc_html__( 'Engines to display', 'searchwp-metrics' ),
			'entry'                                   => esc_html__( 'Entry', 'searchwp-metrics' ),
			'export_engine_statistics'                => esc_html__( 'Export Engine Statistics', 'searchwp-metrics' ),
			'export_popular_searches'                 => esc_html__( 'Export Popular Searches', 'searchwp-metrics' ),
			'export_searches_over_time'               => esc_html__( 'Export Searches Over Time', 'searchwp-metrics' ),
			'ignored'                                 => esc_html__( 'Ignored', 'searchwp-metrics' ),
			'ignored_message'                         => esc_html__( 'You have ignored the following queries; they are excluded from Metrics. Ignored queries are unique to you, other users retain their own ignored queries.', 'searchwp-metrics' ),
			'ignored_searches'                        => esc_html__( 'Ignored Searches', 'searchwp-metrics' ),
			'ignored_search_query'                    => esc_html__( 'Ignored Search Query', 'searchwp-metrics' ),
			'ignore_query_prompt'                     => esc_html__( 'Enter search to ignore', 'searchwp-metrics' ),
			'insight_analysis'                        => wp_kses(
				sprintf(
					// Translators: first is a search query, second is a click count for that search query, third is the number of posts that were clicked, fourth is the search query.
					__( 'Searches for %1$s generated %2$s clicks to %3$s different results. Content related to %4$s may need to be more targeted.', 'searchwp-metrics' ),
					'<strong>{{ query }}</strong>',
					'<strong>{{ clickCount }}</strong>',
					'<strong>{{ postCount }}</strong>',
					'<strong>{{ query }}</strong>'
				),
				[
					'strong' => [],
				]
			),
			'insight_popular_plural'                  => wp_kses(
				sprintf(
					// Translators: first placeholder is the number of popular entries.
					__( 'There are %1$s popular entries with a relatively high conversion rate. Should these entries be more prominent on the site?', 'searchwp-metrics' ),
					'<strong>{{ postCount }}</strong>'
				),
				[
					'strong' => [],
				]
			),
			'insight_popular_singular'                => wp_kses(
				sprintf(
					// Translators: first placeholder is the number of popular entries.
					__( 'There is %1$s popular entry that is viewed more than the average. Should this entry be more prominent on the site?', 'searchwp-metrics' ),
					'<strong>1</strong>'
				),
				[
					'strong' => [],
				]
			),
			'insight_underdog_plural'                 => sprintf(
				// Translators: first placeholder is the number of popular entries.
				__( 'There are %1$s entries with a relatively high conversion rate, but a low search result rank. Can any be optimized to rank higher?', 'searchwp-metrics' ),
				'<strong>{{ postCount }}</strong>'
			),
			'insight_underdog_singular'               => wp_kses(
				sprintf(
					// Translators: first placeholder is the number of popular entries.
					__( 'There is %1$s entry that has a high conversion rate, but a low search result rank. Can it be optimized to rank higher?', 'searchwp-metrics' ),
					'<strong>1</strong>'
				),
				[
					'strong' => [],
				]
			),
			'insights'                                => esc_html__( 'Insights', 'searchwp-metrics' ),
			'insights_engine'                         => sprintf(
				// Translators: placeholder is the engine label.
				esc_html__( 'Insights (%s engine)', 'searchwp-metrics' ),
				'{{ engine.label }}'
			),
			'ip_blocklist'                            => esc_html__( 'IP Blocklist', 'searchwp-metrics' ),
			'ip_blocklist_note'                       => esc_html__( 'One per line. Searches from these IPs will be ignored.', 'searchwp-metrics' ),
			'last_30_days'                            => esc_html__( 'Last 30 Days', 'searchwp-metrics' ),
			'last_7_days'                             => esc_html__( 'Last 7 Days', 'searchwp-metrics' ),
			'logging_rules'                           => esc_html__( 'Logging Rules', 'searchwp-metrics' ),
			'logging_rules_note'                      => esc_html__( 'Logging can be prevented by customizing the IP Blocklist and User ID/Role Blocklist below. Searches that meet the criteria of either Blocklist will not be logged.', 'searchwp-metrics' ),
			'logging_rules_note_details'              => wp_kses(
				__( '<strong>Note:</strong> these rules are <strong>not</strong> applied retroactively', 'searchwp-metrics' ),
				[
					'strong' => [],
				]
			),
			'limit_metrics_to_queries'                => esc_html__( 'Limit Metrics to Queries', 'searchwp-metrics' ),
			'modify_logging_rules'                    => esc_html__( 'Modify Logging Rules', 'searchwp-metrics' ),
			// Translators: month names separated by underscore.
			'months'                                  => esc_html__( 'January_February_March_April_May_June_July_August_September_October_November_December', 'searchwp-metrics' ),
			// Translators: abbreviated month names separated by underscore.
			'months_abbr'                             => esc_html__( 'Jan_Feb_Mar_Apr_May_Jun_Jul_Aug_Sep_Oct_Nov_Dec', 'searchwp-metrics' ),
			'no_clicks'                               => esc_html__( 'There were no clicks for this search during this timeframe', 'searchwp-metrics' ),
			'no_failed_searches'                      => esc_html__( 'There are no searches that yielded zero results', 'searchwp-metrics' ),
			'no_ignored_queries'                      => esc_html__( 'There are no queries that have been ignored', 'searchwp-metrics' ),
			'no_insights'                             => esc_html__( 'There are no Insights to display yet, please allow time for more data to be collected', 'searchwp-metrics' ),
			'no_results_searches'                     => esc_html__( 'No Results Searches', 'searchwp-metrics' ),
			'no_results_searches_engine'              => sprintf(
				// Translators: placeholder is the engine label.
				esc_html__( 'No Results Searches (%s engine)', 'searchwp-metrics' ),
				'{{ engine.label }}'
			),
			'no_results_searches_engine_note'         => wp_kses(
				sprintf(
					// Translators: placeholder is a link to Term Synonyms.
					__( '<strong>Tip:</strong> Because these searches found no results you may want to set up %s that better match your content.', 'searchwp-metrics' ),
					'<a href="https://searchwp.com/documentation/setup/global-rules/#synonyms" target="_blank">Term Synonyms</a>'
				),
				[
					'a' => [
						'href' => [],
					],
				]
			),
			'not_enough_data'                         => esc_html__( 'Not enough data...', 'searchwp-metrics' ),
			'of_all_searches'                         => esc_html__( 'of all searches', 'searchwp-metrics' ),
			'popular_search_details_engine'           => sprintf(
				// Translators: placeholder is the engine label.
				esc_html__( 'Popular Search Details (%s engine)', 'searchwp-metrics' ),
				'{{ engine.label }}'
			),
			'popular_search_details_note'             => esc_html__( 'Gain more insight into your popular searches by analyzing the clicks for popular search terms.', 'searchwp-metrics' ),
			'popular_searches'                        => esc_html__( 'Popular Searches', 'searchwp-metrics' ),
			'remove'                                  => esc_html__( 'remove', 'searchwp-metrics' ),
			'remove_all_ignored_queries'              => esc_html__( 'Remove All Ignored Queries', 'searchwp-metrics' ),
			'remove_on_uninstallation'                => esc_html__( 'Remove all Metrics data on uninstallation', 'searchwp-metrics' ),
			'remove_on_uninstallation_label_note'     => esc_html__( 'When enabled, Metrics will permanently remove all of its data upon uninstallation (cannot be undone)', 'searchwp-metrics' ),
			'save_close'                              => esc_html__( 'Save and Close', 'searchwp-metrics' ),
			// 'search_metrics' => esc_html__( 'Search Metrics', 'searchwp-metrics' ),
			'search_query'                            => esc_html__( 'Search Query', 'searchwp-metrics' ),
			'search_query_controls'                   => esc_html__( 'Search Query Controls', 'searchwp-metrics' ),
			'searches'                                => esc_html__( 'Searches', 'searchwp-metrics' ),
			'searches_per_user'                       => esc_html__( 'Searches Per User', 'searchwp-metrics' ),
			'searches_per_user_note'                  => wp_kses(
				__( 'Average number of searches by<br>users who have searched', 'searchwp-metrics' ),
				[
					'br' => [],
				]
			),
			'select_hour'                             => esc_html__( 'Select Hour', 'searchwp-metrics' ),
			'select_minute'                           => esc_html__( 'Select Minute', 'searchwp-metrics' ),
			'select_second'                           => esc_html__( 'Select Second', 'searchwp-metrics' ),
			'settings'                                => esc_html__( 'General Settings', 'searchwp-metrics' ),
			'statistic'                               => esc_html__( 'Statistic', 'searchwp-metrics' ),
			'stop_ignoring_query'                     => esc_html__( 'Stop ignoring this query', 'searchwp-metrics' ),
			'this_month'                              => esc_html__( 'This Month', 'searchwp-metrics' ),
			'this_week'                               => esc_html__( 'This Week', 'searchwp-metrics' ),
			// Translators: date range separator.
			'to'                                      => esc_html__( 'to', 'searchwp-metrics' ),
			'total_results_viewed'                    => esc_html__( 'Total Results Viewed', 'searchwp-metrics' ),
			'total_searches'                          => esc_html__( 'Total Searches', 'searchwp-metrics' ),
			'type_search_query'                       => esc_html__( 'Type search query', 'searchwp-metrics' ),
			'update'                                  => esc_html__( 'Update', 'searchwp-metrics' ),
			'user_id_role_blocklist'                  => esc_html__( 'User ID/Role Blocklist', 'searchwp-metrics' ),
			'user_id_role_blocklist_note'             => esc_html__( 'One per line. Searches from these IDs/Roles will be ignored.', 'searchwp-metrics' ),
			'value'                                   => esc_html__( 'Value', 'searchwp-metrics' ),
			'view_all'                                => esc_html__( 'View All', 'searchwp-metrics' ),
			'view_more'                               => esc_html__( 'View More', 'searchwp-metrics' ),
			'view_no_results_searches'                => esc_html__( 'View No Results Searches', 'searchwp-metrics' ),
		);

		/**
		 * Default start date for the date range picker.
		 *
		 * @since 1.0.0
		 *
		 * @param string $searchwp_metrics_default_start_date Default start date for the date range picker.
		 */
		$searchwp_metrics_default_start_date = apply_filters( 'searchwp_metrics_default_start_date', '29 days ago' );

		/**
		 * Default end date for the date range picker.
		 *
		 * @since 1.0.0
		 *
		 * @param string $searchwp_metrics_default_end_date Default end date for the date range picker.
		 */
		$searchwp_metrics_default_end_date = apply_filters( 'searchwp_metrics_default_end_date', 'now' );

		/**
		 * Default date to clear data before.
		 *
		 * @since 1.0.0
		 *
		 * @param string $searchwp_metrics_default_clear_data_before_date Default date to clear data before.
		 */
		$searchwp_metrics_default_clear_data_before_date = apply_filters( 'searchwp_metrics_default_clear_data_before_date', '3 months ago' );

		/**
		 * First day of the week.
		 *
		 * @since 1.0.0
		 *
		 * @param int $searchwp_metrics_first_day_of_week First day of the week.
		 */
		$searchwp_metrics_first_day_of_week = apply_filters( 'searchwp_metrics_first_day_of_week', 0 );

		/**
		 * Year suffix.
		 *
		 * @since 1.0.0
		 *
		 * @param string $searchwp_metrics_year_suffix Year suffix.
		 */
		$searchwp_metrics_year_suffix = apply_filters( 'searchwp_metrics_year_suffix', '' );

		$this->options = [
			'default_start'                  => gmdate( 'Y-m-d', strtotime( $searchwp_metrics_default_start_date ) ),
			'default_end'                    => gmdate( 'Y-m-d', strtotime( $searchwp_metrics_default_end_date ) ),
			'default_clear_data_before_date' => gmdate( 'Y-m-d', strtotime( $searchwp_metrics_default_clear_data_before_date ) ),
			'first_day_of_week'              => absint( $searchwp_metrics_first_day_of_week ),
			'year_suffix'                    => $searchwp_metrics_year_suffix,
		];
	}

	function init() {
		add_action( 'plugins_loaded', array( $this, 'textdomain' ) );
	}

	function textdomain() {

		$locale = apply_filters( 'plugin_locale', get_locale(), 'searchwp-metrics' );
		$mofile = WP_LANG_DIR . '/searchwp-metrics/searchwp-metrics-' . $locale . '.mo';

		if ( file_exists( $mofile ) ) {
			load_textdomain( 'searchwp-metrics', $mofile );
		} else {
			load_plugin_textdomain( 'searchwp-metrics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}
	}
}

new SearchWP_Metrics_i18n();
