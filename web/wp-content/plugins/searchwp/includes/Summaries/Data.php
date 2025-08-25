<?php

namespace SearchWP\Summaries;

use SearchWP\Settings;
use SearchWP\Statistics;
use SearchWP\Admin\Notifications\Notifications;
use SearchWP\Admin\Extensions\Extensions;
use SearchWP_Metrics\QuerySearchesOverTime as Metrics_Searches_Over_Time;
use SearchWP_Metrics\QueryPopularQueriesOverTime as Metrics_Popular_Queries_Over_Time;
use SearchWP_Metrics\QueryFailedSearchesOverTime as Metrics_Failed_Searches_Over_Time;
use SearchWP_Metrics\QueryAverageSearchesPerUser as Metrics_Average_Searches_Per_User;
use SearchWP_Metrics\QueryTotalClicks as Metrics_Total_Clicks;
use SearchWP_Metrics\QueryAverageClicksPerSearch as Metrics_Average_Clicks_Per_Search;

/**
 * Fetches Email Summaries data.
 *
 * @since 4.3.16
 */
class Data {

	/**
	 * Get summary data.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	public static function get_summary_data() {

		if ( class_exists( '\\SearchWP_Metrics' ) ) {
			$data = self::get_metrics_data();
		} else {
			$data = self::get_statistics_data();
		}

		if ( empty( $data ) ) {
			return [];
		}

		$data['icons'] = self::get_summary_icons();

		$data['notification'] = self::get_notification();

		$data['info_block'] = self::get_info_block();

		return $data;
	}

	/**
	 * Get Statistics data.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_statistics_data() {

		$engines = self::get_engines();

		$total_searches = self::get_statistics_total_searches( $engines );

		if ( empty( $total_searches ) ) {
			return [];
		}

		return [
			'engines'          => $engines,
			'total_searches'   => $total_searches,
			'popular_searches' => self::get_statistics_popular_searches( $engines ),
			'failed_searches'  => self::get_statistics_failed_searches( $engines ),
		];
	}

	/**
	 * Get Statistics popular searches.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return array
	 */
	private static function get_statistics_popular_searches( $engines ) {

		$popular_searches = [];

		foreach ( $engines as $engine ) {

			$popular_searches = array_merge(
				$popular_searches,
				Statistics::get_popular_searches(
					[
						'engine' => $engine->get_name(),
						'limit'  => self::get_popular_searches_limit(),
						'days'   => self::get_data_range_in_days(),
					]
				)
			);
		}

		// Order the popular searches by searches count.
		usort(
			$popular_searches,
			function ( $a, $b ) {
				return $b->searches - $a->searches;
			}
		);

		$reduced_popular_searches = [];

		// Reduce duplicated queries to the ones with most searches.
		foreach ( $popular_searches as $popular_search ) {
			if (
				! isset( $reduced_popular_searches[ $popular_search->query ] )
				|| $popular_search->searches > $reduced_popular_searches[ $popular_search->query ]['searches']
			) {
				$reduced_popular_searches[ $popular_search->query ] = [
					'query'    => $popular_search->query,
					'searches' => $popular_search->searches,
				];
			}
		}

		// Reduce the popular searches to the top nth entries. Default is 5.
		$popular_searches = array_slice( $reduced_popular_searches, 0, self::get_popular_searches_limit() );

		return $popular_searches;
	}

	/**
	 * Get Statistics total searches.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return int
	 */
	private static function get_statistics_total_searches( $engines ) {

		$total_searches = [];

		foreach ( $engines as $engine ) {
			$total_searches[] = array_sum(
				wp_list_pluck(
					Statistics::get_searches_over_time(
						[
							'engine' => $engine->get_name(),
							'days'   => self::get_data_range_in_days(),
						]
					),
					'searches'
				)
			);
		}

		$total_searches = array_sum( $total_searches );

		return $total_searches;
	}

	/**
	 * Get Statistics failed searches.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return int
	 */
	private static function get_statistics_failed_searches( $engines ) {

		$ignored = Settings::get( 'ignored_queries', 'array' );
		$ignored = array_map( 'strtolower', $ignored );

		$failed_searches = [];

		foreach ( $engines as $engine ) {

			$failed_searches = array_merge(
				$failed_searches,
				Statistics::get_popular_searches(
					[
						'engine'   => $engine->get_name(),
						'days'     => self::get_data_range_in_days(),
						'min_hits' => 0,
						'max_hits' => 0,
						'excluded' => $ignored,
					]
				)
			);
		}

		return count( $failed_searches );
	}

	/**
	 * Get Metrics data.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_metrics_data() {

		$engines = self::get_engines();

		$total_searches = self::get_metrics_total_searches( $engines );

		if ( empty( $total_searches ) ) {
			return [];
		}

		$users_data = self::get_metrics_searches_per_user( $engines );

		return [
			'engines'              => $engines,
			'total_users'          => $users_data['total_users'],
			'total_searches'       => self::get_metrics_total_searches( $engines ),
			'searches_per_user'    => $users_data['average_searches_per_user'],
			'failed_searches'      => self::get_metrics_failed_searches( $engines ),
			'total_results_viewed' => self::get_metrics_total_results_viewed( $engines ),
			'clicks_per_search'    => self::get_metrics_clicks_per_search( $engines ),
			'popular_searches'     => self::get_metrics_popular_searches( $engines ),
		];
	}

	/**
	 * Get Metrics total searches.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return int
	 */
	private static function get_metrics_total_searches( $engines ) {

		$total_searches = [];

		foreach ( $engines as $engine ) {
			$total_searches[] = array_sum(
				wp_list_pluck(
					( new Metrics_Searches_Over_Time(
						[
							'engine' => $engine->get_name(),
							'after'  => self::get_data_range_in_days() . ' days ago',
							'before' => 'now',
						]
					) )->get_results(),
					'searches'
				)
			);
		}

		$total_searches = array_sum( $total_searches );

		return $total_searches;
	}

	/**
	 * Get Metrics popular searches.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return array
	 */
	private static function get_metrics_popular_searches( $engines ) {

		$popular_searches = [];

		foreach ( $engines as $engine ) {

			$popular_searches = array_merge(
				$popular_searches,
				( new Metrics_Popular_Queries_Over_Time(
					[
						'engine' => $engine->get_name(),
						'after'  => self::get_data_range_in_days() . ' days ago',
						'before' => 'now',
						'limit'  => self::get_popular_searches_limit(),
					]
				) )->get_results()
			);
		}

		// Order the popular searches by searches count.
		usort(
			$popular_searches,
			function ( $a, $b ) {
				return $b->searchcount - $a->searchcount;
			}
		);

		$reduced_popular_searches = [];

		// Reduce duplicated queries to the ones with most searches.
		foreach ( $popular_searches as $popular_search ) {
			if (
				! isset( $reduced_popular_searches[ $popular_search->query ] )
				|| $popular_search->searchcount > $reduced_popular_searches[ $popular_search->query ]['searches']
			) {
				$reduced_popular_searches[ $popular_search->query ] = [
					'query'    => $popular_search->query,
					'searches' => $popular_search->searchcount,
				];
			}
		}

		// Reduce the popular searches to the top 5.
		$popular_searches = array_slice( $reduced_popular_searches, 0, self::get_popular_searches_limit() );

		return $popular_searches;
	}

	/**
	 * Get Metrics failed searches.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return int
	 */
	private static function get_metrics_failed_searches( $engines ) {

		$failed_searches = [];

		foreach ( $engines as $engine ) {
			$failed_searches[] = array_sum(
				wp_list_pluck(
					( new Metrics_Failed_Searches_Over_Time(
						[
							'engine' => $engine->get_name(),
							'after'  => self::get_data_range_in_days() . ' days ago',
							'before' => 'now',
						]
					) )->get_results(),
					'failcount'
				)
			);
		}

		$failed_searches = array_sum( $failed_searches );

		return $failed_searches;
	}

	/**
	 * Get Metrics total results viewed.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return int
	 */
	private static function get_metrics_total_results_viewed( $engines ) {

		global $wpdb;

		$total_results_viewed = [];

		foreach ( $engines as $engine ) {

			( new Metrics_Total_Clicks(
				[
					'engine' => $engine->get_name(),
					'after'  => self::get_data_range_in_days() . ' days ago',
					'before' => 'now',
				]
			) )->get_results();

			$total_results_viewed[] = $wpdb->num_rows;
		}

		return array_sum( $total_results_viewed );
	}

	/**
	 * Get Metrics clicks per search.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return string
	 */
	private static function get_metrics_clicks_per_search( $engines ) {

		$average_clicks_per_search = 0;
		$clicks_per_search         = [];

		foreach ( $engines as $engine ) {

			$clicks_per_search = array_merge(
				$clicks_per_search,
				( new Metrics_Average_Clicks_Per_Search(
					[
						'engine' => $engine->get_name(),
						'after'  => self::get_data_range_in_days() . ' days ago',
						'before' => 'now',
					]
				) )->get_results()
			);
		}

		if ( ! empty( $clicks_per_search ) ) {
			$total_clicks = array_sum( wp_list_pluck( $clicks_per_search, 'clicks' ) );

			if ( empty( $total_clicks ) ) {
				$average_clicks_per_search = 0;
			} else {
				$average_clicks_per_search = $total_clicks / count( $clicks_per_search );
			}
		}

		return ! empty( $average_clicks_per_search ) ? number_format_i18n( (float) $average_clicks_per_search, 2 ) : 0;
	}

	/**
	 * Get Metrics average searches per user.
	 *
	 * @since 4.3.16
	 *
	 * @param array $engines Search engines.
	 *
	 * @return array
	 */
	public static function get_metrics_searches_per_user( $engines ) {

		$users_count               = 0;
		$average_searches_per_user = 0;
		$searches_per_user         = [];

		foreach ( $engines as $engine ) {
			$searches_per_user = array_merge(
				$searches_per_user,
				( new Metrics_Average_Searches_Per_User(
					[
						'engine' => $engine->get_name(),
						'after'  => self::get_data_range_in_days() . ' days ago',
						'before' => 'now',
					]
				) )->get_results()
			);
		}

		if ( ! empty( $searches_per_user ) ) {
			$uids           = wp_list_pluck( $searches_per_user, 'uid' );
			$uid_counts     = array_count_values( $uids );
			$total_searches = array_sum( $uid_counts );

			if ( ! empty( $uid_counts ) ) {
				$users_count               = count( $uid_counts );
				$average_searches_per_user = $total_searches / $users_count;
			}
		}

		return [
			'total_users'               => $users_count,
			'average_searches_per_user' => number_format_i18n( (float) $average_searches_per_user, 2 ),
		];
	}

	/**
	 * Get the popular searches limit.
	 *
	 * @since 4.3.16
	 *
	 * @return int
	 */
	private static function get_popular_searches_limit() {

		/**
		 * Filters the number of popular searches to include in the email summaries.
		 *
		 * @since 4.3.16
		 *
		 * @param int $popular_searches_limit Number of popular searches to include in the email summaries. Default is 5.
		 */
		return absint( apply_filters( 'searchwp/emails_summaries/popular_searches_limit', 5 ) );
	}

	/**
	 * Get the number of days to look back for statistics.
	 *
	 * @since 4.3.16
	 *
	 * @return int
	 */
	private static function get_data_range_in_days() {

		/**
		 * Filters the number of days to look back for statistics.
		 *
		 * @since 4.3.16
		 *
		 * @param int $data_range_in_days Number of days to look back for statistics. Default is 7.
		 */
		return absint( apply_filters( 'searchwp/emails_summaries/data_range_in_days', 7 ) );
	}

	/**
	 * Get search engines.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_engines() {

		return Settings::get_engines();
	}

	/**
	 * Get summary icons.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_summary_icons() {

		$base_url = SEARCHWP_PLUGIN_URL . '/assets/images/';

		return [
			'overview'           => $base_url . 'overview-icon.png',
			'info_block'         => $base_url . 'info-block-icon.png',
			'notification_block' => $base_url . 'notification-block-icon.png',
		];
	}

	/**
	 * Get notification.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_notification() {

		$notifications = Notifications::get();

		$notification = ! empty( $notifications ) ? $notifications[0] : null;

		if ( ! empty( $notification ) ) {

			$action_text = ! empty( $notification['actions'][0] ) ? $notification['actions'][0]['text'] : '';
			$action_url  = ! empty( $notification['actions'][0] ) ? $notification['actions'][0]['url'] : '';

			// Strip any parameter from the URL.
			$action_url = strtok( $action_url, '?' );

			// Add email summaries parameters to the URL.
			$action_url = add_query_arg(
				[
					'utm_source'   => 'WordPress',
					'utm_medium'   => 'Email+Summaries+Notification+Button',
					'utm_campaign' => 'SearchWP',
					'utm_content'  => str_replace( ' ', '+', $action_text ),
				],
				$action_url
			);

			$notification = [
				'title'   => $notification['title'],
				'content' => $notification['content'],
				'button'  => [
					'text' => $action_text,
					'url'  => $action_url,
				],
			];
		}

		return $notification;
	}

	/**
	 * Get the info block.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_info_block() {

		$info_block = self::get_info_block_extensions();

		// If there is no extensions info block, show the documentation info block.
		if ( empty( $info_block ) ) {
			$info_block = self::get_info_block_docs();
		}

		return $info_block;
	}

	/**
	 * Get the extensions info block.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_info_block_extensions() {

		$block_data = [];

		// Extensions.
		$pro_extensions = [
			'searchwp-metrics',
			'searchwp-custom-results-order',
			'searchwp-related',
			'searchwp-redirects',
		];

		// Get a list of inactive SearchWP extensions.
		$inactive_extensions = array_filter(
			array_map(
				function ( $extension ) {
					$extension = Extensions::get( $extension );

					return ! $extension['plugin_active'] ? $extension : false;
				},
				$pro_extensions
			)
		);

		if ( ! empty( $inactive_extensions ) ) {

			// Get a random non-active Pro extension if present.
			$random_extension_index = ! empty( $inactive_extensions ) ? array_rand( $inactive_extensions ) : false;

			switch ( $inactive_extensions[ $random_extension_index ]['slug'] ) {
				case 'searchwp-metrics':
					$title   = 'Boost Conversions with Search Metrics';
					$content = 'Curious about what your visitors are searching for? Unleash the power of SearchWP Metrics! Discover real user search queries, identify opportunities for new content, and optimize your site for what matters – conversions.';
					break;

				case 'searchwp-custom-results-order':
					$title   = 'Promote Key Content with Custom Results Order';
					$content = 'Take control of your search results with Custom Results Order! Easily promote essential content to the top of search results, guiding users to the information you want them to see.';
					break;

				case 'searchwp-related':
					$title   = 'Keep Visitors Engaged: Power Up Your Site with Related Content';
					$content = 'Visitors who find relevant content are more likely to stay engaged and explore your site. SearchWP Related Content makes it easy to Boost User Engagement, Increase Page Views, and Improve SEO Performance';
					break;

				case 'searchwp-redirects':
					$title   = 'Smart Search, Seamless Journeys';
					$content = 'Want to take control of where your visitors land after a specific search? SearchWP Redirects lets you create custom redirects based on search queries, ensuring your visitors find what they’re looking for, every time.';
					break;
			}

			$block_data = [
				'title'   => $title,
				'content' => $content,
				'url'     => $inactive_extensions[ $random_extension_index ]['url'] . '?utm_source=WordPress&utm_medium=Email+Summaries+DYK+Button&utm_campaign=SearchWP&utm_content=Learn+More',
				'button'  => 'Learn More',
			];
		}

		return $block_data;
	}

	/**
	 * Get the documentation info block.
	 *
	 * @since 4.3.16
	 *
	 * @return array
	 */
	private static function get_info_block_docs() {

		$docs = [
			[
				'title'   => 'Fine-Tune Your Search with SearchWP Global Rules',
				'content' => 'Frustrated with irrelevant search results? SearchWP Global Rules are your secret weapon!  Refine your searches by eliminating common words ("the", "and") and setting up synonyms, ensuring visitors always find exactly what they\'re looking for.  Boost your user experience and keep them engaged with laser-focused search results.',
				'url'     => 'https://searchwp.com/documentation/setup/global-rules/',
			],
			[
				'title'   => 'Supercharge your site Search with Search Forms',
				'content' => 'Empower your visitors to find exactly what they need. SearchWP Search Forms go beyond the basic search bar.  Build beautiful, customizable forms with advanced filtering options like categories, tags, and post types.  Simplify the search experience, boost engagement, and watch those conversions soar.',
				'url'     => 'https://searchwp.com/documentation/setup/search-forms/',
			],
			[
				'title'   => 'Unleash the Power of Hidden Content',
				'content' => 'Don\'t let valuable content get buried! SearchWP unlocks the power of your media library. Search PDFs, Office documents, and text files directly alongside your website content. Users can finally find exactly what they need, boosting engagement and user satisfaction. Let SearchWP turn your media library into a goldmine of discoverable information.',
				'url'     => 'https://searchwp.com/wordpress-search-pdf-office-text-documents/',
			],
		];

		// Get a random documentation index.
		$random_doc_index = array_rand( $docs );

		$block_data = [
			'title'   => $docs[ $random_doc_index ]['title'],
			'content' => $docs[ $random_doc_index ]['content'],
			'url'     => $docs[ $random_doc_index ]['url'] . '?utm_source=WordPress&utm_medium=Email+Summaries+DYK+Button&utm_campaign=SearchWP&utm_content=Learn+More',
			'button'  => 'Learn More',
		];

		return $block_data;
	}
}
