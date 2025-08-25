<?php

namespace SearchWP\Summaries;

use SearchWP\Settings;
use SearchWP\Summaries\Data;
use SearchWP\Summaries\Template;

/**
 * Manages Email Summaries tasks.
 *
 * @since 4.3.16
 */
class Client {

	/**
	 * Initialize Email Summaries.
	 *
	 * @since 4.3.16
	 *
	 * @return void
	 */
	public static function init() {

		$is_disabled = self::is_disabled();

		// If Email Summaries are not disabled, register hooks.
		if ( ! $is_disabled ) {
			add_filter( 'cron_schedules', [ __CLASS__, 'add_cron_schedule' ] ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
			add_action( 'searchwp_email_summaries_cron', [ __CLASS__, 'cron' ] );
		}

		// If Email Summaries are not disabled and the cron event is not scheduled, schedule it.
		if ( ! $is_disabled && ! \wp_next_scheduled( 'searchwp_email_summaries_cron' ) ) {
			\wp_schedule_event( self::get_cron_date_gmt(), 'searchwp_email_summaries_weekly', 'searchwp_email_summaries_cron' );
		}

		// If Email Summaries are disabled and the cron event is scheduled, unschedule it.
		if ( $is_disabled && \wp_next_scheduled( 'searchwp_email_summaries_cron' ) ) {
			\wp_clear_scheduled_hook( 'searchwp_email_summaries_cron' );
		}
	}

	/**
	 * Cron callback for Email Summaries.
	 *
	 * @since 4.3.16
	 *
	 * @return void
	 */
	public static function cron() {

		$data = Data::get_summary_data();

		if ( empty( $data ) ) {
			return;
		}

		$parsed_home_url = wp_parse_url( home_url() );
		$site_domain     = $parsed_home_url['host'];

		if ( is_multisite() && isset( $parsed_home_url['path'] ) ) {
			$site_domain .= $parsed_home_url['path'];
		}

		$interval_label = self::get_cron_interval() === \WEEK_IN_SECONDS
			? __( 'Weekly', 'searchwp' )
			: __( 'Periodic', 'searchwp' );

		$subject = sprintf(
			/* translators: %1$s - interval label like 'Weekly' or 'Periodic', %2$s - site domain. */
			esc_html__( 'Your %1$s SearchWP Summary for %2$s', 'searchwp' ),
			$interval_label,
			$site_domain
		);

		/**
		 * Filters the email subject for summaries.
		 *
		 * @since 4.3.16
		 *
		 * @param string $subject Default email subject.
		 */
		$subject = apply_filters( 'searchwp/emails_summaries/email_subject', $subject );

		/**
		 * Filters the recipient email address for summaries.
		 *
		 * @since 4.3.16
		 *
		 * @param string $option Default recipient email address.
		 */
		$email_to = apply_filters( 'searchwp/email_summaries/email_to', get_option( 'admin_email' ) );

		\wp_mail(
			$email_to,
			$subject,
			Template::get_content( $data ),
			self::get_email_headers()
		);
	}

	/**
	 * Get email headers.
	 *
	 * @since 4.3.16
	 *
	 * @return string
	 */
	private static function get_email_headers() {

		$from_name    = \get_bloginfo( 'name' );
		$from_address = \get_option( 'admin_email' );
		$headers      = "From: {$from_name} <{$from_address}>\r\n";
		$headers     .= "Content-Type: text/html; charset=UTF-8\r\n";

		/**
		 * Filters the email headers for summaries.
		 *
		 * @since 4.3.16
		 *
		 * @param array $headers Default email headers.
		 * @param array $args    Email arguments.
		 */
		return apply_filters(
			'searchwp/emails_summaries/email_headers',
			$headers,
			[
				'from_name'    => $from_name,
				'from_address' => $from_address,
			]
		);
	}

	/**
	 * Add custom cron schedule for weekly summaries.
	 *
	 * @since 4.3.16
	 *
	 * @param array $schedules Current cron schedules.
	 *
	 * @return mixed
	 */
	public static function add_cron_schedule( $schedules ) {

		$schedules['searchwp_email_summaries_weekly'] = [
			'interval' => self::get_cron_interval(),
			'display'  => \esc_html__( 'Weekly SearchWP Email Summaries', 'searchwp' ),
		];

		return $schedules;
	}

	/**
	 * Get date for summaries cron event.
	 *
	 * @since 4.3.16
	 *
	 * @return int
	 */
	private static function get_cron_date_gmt() {

		$date = \absint( \strtotime( 'next monday 2pm' ) - ( \get_option( 'gmt_offset' ) * \HOUR_IN_SECONDS ) );

		return $date ? $date : \time();
	}

	/**
	 * Get interval for summaries cron event.
	 *
	 * @since 4.3.16
	 *
	 * @return int
	 */
	public static function get_cron_interval() {

		/**
		 * Filters the interval for Email Summaries. Default is 1 week.
		 *
		 * @since 4.3.16
		 *
		 * @param int $interval Default interval.
		 */
		$interval = apply_filters( 'searchwp/email_summaries/interval', \WEEK_IN_SECONDS );

		return $interval;
	}

	/**
	 * Check if Email Summaries are disabled.
	 *
	 * @since 4.3.16
	 *
	 * @return bool
	 */
	public static function is_disabled() {

		/**
		 * Filters whether Email Summaries are disabled.
		 *
		 * @since 4.3.16
		 *
		 * @param bool $disabled Whether Email Summaries are disabled.
		 */
		return (bool) apply_filters( 'searchwp/email_summaries/disabled', Settings::get( 'disable_email_summaries', 'boolean' ) );
	}
}
