<?php

namespace SearchWP;

abstract class BackgroundProcess {
	/**
	 * Process name.
	 *
	 * @since 4.1
	 * @var string
	 */
	protected $name = '';

	/**
	 * Process identifier.
	 *
	 * @since 4.1
	 * @var mixed
	 */
	protected $identifier;

	/**
	 * Process start time.
	 *
	 * @since 4.1
	 * @var int
	 */
	protected $start_time;

	/**
	 * Whether the process is locked.
	 *
	 * @since 4.1
	 * @var boolean
	 */
	protected $locked;

	/**
	 * Whether the process is enabled.
	 *
	 * @since 4.1
	 * @var boolean
	 */
	protected $enabled = true;

	/**
	 * Iniitalizer.
	 *
	 * @since 4.1
	 * @return void
	 */
	protected function init() {
		$this->locked = get_site_option( $this->identifier . '_process_lock' );

		// Bind async request callback, received after dispatch().
		if ( ! has_action( 'wp_ajax_' . $this->identifier, [ $this, 'async' ] ) ) {
			add_action( 'wp_ajax_' . $this->identifier, [ $this, 'async' ] );
		}

		// Add our WP_Cron health check schedule.
		add_filter( 'cron_schedules', function( $schedules ) {
			$interval = absint( apply_filters( 'searchwp\background_process\cron_interval', 5 ) );

			$schedules[ SEARCHWP_PREFIX . 'cron_interval' ] = array(
				'interval' => MINUTE_IN_SECONDS * $interval,
				'display'  => sprintf( __( 'Every %d Minutes', 'searchwp' ), $interval ),
			);

			return $schedules;
		} );

		// Schedule health check.
		$this->schedule();

		// Bind to health check WP_Cron event.
		if ( ! has_action( $this->identifier . '_cron', [ $this, 'health_check' ] ) ) {
			add_action( $this->identifier . '_cron', [ $this, 'health_check' ] );
		}
	}

	/**
	 * Callback for CRON request.
	 *
	 * @since 4.1
	 * @return void
	 */
	public function async() {
		// Don't lock up other requests while processing
		session_write_close();

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->trigger();

		wp_die();
	}

	/**
	 * Callback to check on the health of our process.
	 *
	 * @since 4.1
	 */
	public function health_check() {
		// Did we exceed our time limit?
		if ( $this->locked ) {
			// Did we exceed our time limit?
			if ( time() > $this->locked + absint( apply_filters( 'searchwp\background_process\process_time_limit', 60 ) ) ) {
				$this->time_limit_exceeded();
				$this->unlock_process();
				$this->trigger();
			}
		} else {
			// Trigger a recurring update to catch any edits that were made outside of observed hooks.
			do_action( 'searchwp\debug\log', 'Health check', $this->name . ':background' );
			$this->trigger();
		}
	}

	/**
	 * Schedule our WP_Cron event.
	 *
	 * @since 4.1
	 * @return void
	 */
	protected function schedule() {
		if ( ! wp_next_scheduled( $this->identifier . '_cron' ) ) {
			wp_schedule_event( time() + $this->interval_offset(), SEARCHWP_PREFIX . 'cron_interval', $this->identifier . '_cron' );
		}
	}

	/**
	 * Clears our WP_Cron event.
	 *
	 * @since 4.1
	 * @return void
	 */
	protected function clear_schedule() {
		$timestamp = wp_next_scheduled( $this->identifier . '_cron' );

		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $this->identifier . '_cron' );
		}
	}

	/**
	 * Uninstallation routine triggered in uninstall.php.
	 *
	 * @since 4.1
	 * @return void
	 */
	public function _uninstall() {
		$this->clear_schedule();
		$this->unlock_process();

		delete_site_option( $this->identifier . '_process_lock' );
	}

	/**
	 * Locks the process.
	 *
	 * @since 4.1
	 * @return void
	 */
	protected function lock_process() {
		global $wpdb;
		$this->start_time = time();

		update_site_option( $this->identifier . '_process_lock', $this->start_time );
	}

	/**
	 * Unlocks the process.
	 *
	 * @since 4.1
	 * @return void
	 */
	protected function unlock_process() {
		$result = delete_site_option( $this->identifier . '_process_lock' );
		$this->locked = false;
	}

	/**
	 * Dispatches loopback request.
	 *
	 * @since 4.1
	 */
	protected function dispatch() {
		$url = add_query_arg( [
			'action' => $this->identifier,
			'nonce'  => wp_create_nonce( $this->identifier ),
		], $this->get_query_url() );

		return wp_remote_post( esc_url_raw( $url ), $this->get_post_args() );
	}

	/**
	 * Trigger the index to cycle delta updates. Locks process, processes queue, unlocks process, dispatches if necessary.
	 *
	 * @since 4.1
	 */
	public function trigger() {
		if ( ! $this->enabled || $this->locked ) {
			if ( ! $this->enabled ) {
				do_action( 'searchwp\debug\log', 'Process is PAUSED', $this->name . ':background' );
			}

			if ( $this->locked ) {
				do_action( 'searchwp\debug\log', 'Process is LOCKED', $this->name . ':background' );
			}

			return false;
		}

		$this->lock_process();

		// Cycle a batch.
		$done = ! $this->cycle();

		// Maybe react to system load.
		$sleep = 0;
		if ( apply_filters( 'searchwp\background_process\load_monitoring', function_exists( 'sys_getloadavg' ) ) ) {
			$sleep = $this->_get_cpu_load_throttle();
		}

		sleep( $sleep );

		$this->unlock_process();

		if ( $done ) {
			$this->complete();
		} else {
			if ( 'alternate' !== $this->_method() ) {
				$this->dispatch();
			}
		}
	}

	/**
	 * Whether CPU load has been exceeded.
	 *
	 * @since 4.0
	 * @return boolean
	 */
	protected function _get_cpu_load_throttle() {
		$load      = sys_getloadavg();
		$threshold = abs( apply_filters( 'searchwp\background_process\load_maximum', 2 ) );

		if ( $load[0] < $threshold ) {
			return 0;
		}

		$throttle = absint( apply_filters( 'searchwp\background_process\load_throttle', [
			'load' => $load
		] ), 4 * floor( $load[0] ) );

		$ini_max = absint( ini_get( 'max_execution_time' ) ) - 5;

		if ( $ini_max < 10 ) {
			$ini_max = 10;
		}

		if ( $throttle > $ini_max ) {
			$throttle = $ini_max;
		}

		do_action(
			'searchwp\debug\log',
			'CPU load threshold (' . floatval( $threshold ) . ') breached: '
				. floatval( $load[0] ) . ' [waiting ' . $throttle . ']',
			$this->name . ':background'
		);

		return $throttle;
	}

	/**
	 * Retrieves loopback URL.
	 *
	 * @since 4.1
	 * @return string
	 */
	public function get_query_url() {
		return admin_url( 'admin-ajax.php' );
	}

	/**
	 * Returns loopback connection arguments.
	 *
	 * @since 4.1
	 * @return array Arguments for wp_remote_post().
	 */
	public function get_post_args() {
		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => '',
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);

		$basic_auth = apply_filters( 'searchwp\indexer\http_basic_auth_credentials', [] ); // Deprecated.
		$basic_auth = apply_filters( 'searchwp\background_process\http_basic_auth_credentials', [] );

		if ( $basic_auth ) {
			$args['headers'] = [
				'Authorization' => 'Basic ' . base64_encode( $basic_auth['username'] . ':' . $basic_auth['password'] ),
			];
		}

		$args = apply_filters( 'searchwp\indexer\loopback\args', $args ); // Deprecated.

		return apply_filters( 'searchwp\background_process\loopbackargs', $args );
	}

	/**
	 * Whether memory usage has been exceeded.
	 *
	 * @since 4.0
	 * @return boolean
	 */
	public function memory_exceeded() {
		$memory_limit   = \SearchWP\Utils::get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( 'searchwp\indexer\memory_exceeded', $return );
	}

	/**
	 * Returns communication method.
	 *
	 * @since 4.0
	 * @return string 'default', 'basicauth', or 'alternate'
	 */
	public function _method() {
		if ( apply_filters( 'searchwp\indexer\alternate', false ) ) {
			return 'alternate';
		}

		$args             = $this->get_post_args();
		$args['blocking'] = true;
		$args['timeout']  = 0.5;
		$args['body']     = 'SearchWP Indexer Communication Test';

		try	{
			$cache_key = md5( serialize( $args ) . esc_url_raw( $this->get_query_url() ) );
		} catch ( \Exception $e ) {
			// Something went wrong with the args so just skip the cache.
			$cache_key = false;
		}

		$response = ! empty( $cache_key ) ? wp_cache_get( $cache_key, '' ) : '';

		if ( empty( $response ) ) {
			$response = wp_remote_post( esc_url_raw( $this->get_query_url() ), $args );

			if ( ! empty( $cache_key ) ) {
				wp_cache_set( $cache_key, $response, '', 1 );
			}
		}

		if (
			is_wp_error( $response )
			&& isset( $response->errors['http_request_failed'] )
			&& isset( $response->errors['http_request_failed'][0] )
			&& false !== strpos( strtolower( $response->errors['http_request_failed'][0] ), 'could not resolve' )
		) {
			return 'alternate';
		} else if (
			! is_wp_error( $response)
			&& isset( $response['response']['code'] )
			&& 401 === (int) $response['response']['code']
		) {
			return 'basicauth';
		}

		return 'default';
	}

	/**
	 * Returns WP_Cron interval offset.
	 *
	 * @since 4.1
	 * @return int Offset in seconds.
	 */
	protected function interval_offset() {
		return 0;
	}

	/**
	 * Fires when background process is complete.
	 *
	 * @since 4.1
	 * @return void
	 */
	protected function complete() {}

	/**
	 * Cycles a batch for this process.
	 *
	 * @since 4.1
	 * @return mixed
	 */
	abstract protected function cycle();

	/**
	 * Fires when the time limit for this process has been exceeded.
	 *
	 * @since 4.1
	 * @return mixed
	 */
	abstract protected function time_limit_exceeded();
}