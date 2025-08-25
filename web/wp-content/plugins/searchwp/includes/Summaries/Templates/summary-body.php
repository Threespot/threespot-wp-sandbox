<?php
/**
 * Email Summary body template.
 *
 * @since 4.3.16
 *
 * @var array $summary Contains all the data to include in the template.
 * {
 *     @type int   $total_users          Total users.
 *     @type int   $total_searches       Total searches.
 *     @type int   $failed_searches      Failed searches.
 *     @type int   $total_results_viewed Total results viewed.
 *     @type int   $clicks_per_search    Average clicks per search.
 *     @type float $searches_per_user    Average searches per user.
 *     @type array $popular_searches     Popular searches data to loop through.
 *     @type array $engines              Search engines data to loop through.
 *     @type array $icons                Icons used for design purposes.
 *     @type array $notification         Notification block shown at the end of the email.
 * }
 */

use SearchWP\Summaries\Client;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<table class="summary-container" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
	<tbody>
	<tr>
		<td class="summary-content" bgcolor="#ffffff">
			<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
				<tbody>
				<tr>
					<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
					<td class="summary-content-inner" align="center" valign="top" width="600">
						<div class="summary-header" width="100%">
							<h6 class="greeting"><?php esc_html_e( 'Hi there!', 'searchwp' ); ?></h6>
							<p>
								<?php
								$interval_label = Client::get_cron_interval() === \WEEK_IN_SECONDS ? __( 'week', 'searchwp' ) : __( 'period', 'searchwp' );

								printf(
									/* translators: placeholder is an interval label like 'week' or 'period'. */
									esc_html__( 'Let’s see how your site search performed in the past %s.', 'searchwp' ),
									esc_html( $interval_label )
								);
								?>
							</p>
						</div>
						<div class="email-summaries-overview-wrapper" width="100%">

							<table class="email-summaries-overview" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation" bgcolor="#f8f8f8">
								<tbody>
								<tr>
									<td class="overview-icon" valign="top">
										<?php if ( ! empty( $summary['icons']['overview'] ) ) : ?>
										<img src="<?php echo esc_url( $summary['icons']['overview'] ); ?>" width="52" height="52" alt="<?php esc_attr_e( 'Overview', 'searchwp' ); ?>" />
										<?php endif; ?>
									</td>
									<td class="overview-stats" valign="top">
										<?php if ( isset( $summary['total_searches'] ) && ! empty( $summary['total_searches'] ) ) : ?>
											<table>
												<?php if ( ! empty( $summary['total_users'] ) ) : ?>
												<tr>
													<td>
														<h5><?php echo absint( $summary['total_users'] ); ?></h5>
													</td>
													<td>
														<p><?php echo wp_kses( _n( 'User', 'Users', absint( $summary['total_users'] ), 'searchwp' ), [] ); ?></p>
													</td>
												</tr>
												<?php endif; ?>

												<?php if ( ! empty( $summary['total_searches'] ) ) : ?>
												<tr>
													<td>
														<h5><?php echo absint( $summary['total_searches'] ); ?></h5>
													</td>
													<td>
														<p><?php echo wp_kses( _n( 'Search', 'Total Searches', absint( $summary['total_searches'] ), 'searchwp' ), [] ); ?></p>
													</td>
												</tr>
												<?php endif; ?>

												<?php if ( ! empty( $summary['failed_searches'] ) || $summary['failed_searches'] === 0 ) : ?>
												<tr>
													<td>
													<h5><?php echo absint( $summary['failed_searches'] ); ?></h5>
													</td>
													<td>
													<p><?php esc_html_e( 'Searches with no results', 'searchwp' ); ?></p>
													</td>
												</tr>
												<?php endif; ?>

												<?php if ( ! empty( $summary['total_results_viewed'] ) || $summary['total_results_viewed'] === 0 ) : ?>
												<tr>
													<td>
													<h5><?php echo esc_html( $summary['total_results_viewed'] ); ?></h5>
													</td>
													<td>
													<p><?php echo wp_kses( _n( 'Result Viewed', 'Results Viewed', absint( $summary['total_results_viewed'] ), 'searchwp' ), [] ); ?></p>
													</td>
												</tr>
												<?php endif; ?>

												<?php if ( ! empty( $summary['searches_per_user'] ) ) : ?>
												<tr>
													<td>
													<h5><?php echo esc_html( $summary['searches_per_user'] ); ?></h5>
													</td>
													<td>
													<p><?php esc_html_e( 'Average Searches per user', 'searchwp' ); ?></p>
													</td>
												</tr>
												<?php endif; ?>

												<?php if ( ! empty( $summary['clicks_per_search'] ) || $summary['clicks_per_search'] === 0 ) : ?>
												<tr>
													<td>
													<h5><?php echo esc_html( $summary['clicks_per_search'] ); ?></h5>
													</td>
													<td>
													<p><?php esc_html_e( 'Average Clicks per search', 'searchwp' ); ?></p>
													</td>
												</tr>
												<?php endif; ?>
											</table>
										<?php else : ?>
											<p><?php esc_html_e( 'There where no searches in the past week.', 'searchwp' ); ?></p>
										<?php endif; ?>
									</td>
								</tr>
								</tbody>
							</table>
						</div>
						<div class="email-summaries-popular-searches-wrapper" width="100%">
							<?php if ( ! empty( $summary['engines'] ) ) : ?>
							<h5>
								<?php
								echo count( $summary['engines'] ) > 1 ? esc_html__( 'Popular searches across all engines', 'searchwp' ) : esc_html__( 'Popular searches', 'searchwp' );
								?>
							</h5>
							<?php endif; ?>
							<table class="email-summaries" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
								<thead>
								<tr>
									<th align="<?php echo is_rtl() ? 'right' : 'left'; ?>" valign="top"><?php esc_html_e( 'Query', 'searchwp' ); ?></th>
									<th class="entries-column" align="<?php echo is_rtl() ? 'right' : 'left'; ?>" valign="top" colspan="2"><?php esc_html_e( 'Searches', 'searchwp' ); ?></th>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $summary['popular_searches'] as $row ) : ?>
									<tr>
										<td class="search-query" valign="middle"><?php echo isset( $row['query'] ) ? esc_html( $row['query'] ) : ''; ?></td>
										<td class="entry-count" align="center" valign="middle">
											<span><?php echo isset( $row['searches'] ) ? absint( $row['searches'] ) : ''; ?></span>
										</td>
									</tr>
								<?php endforeach; ?>

								<?php if ( empty( $summary['popular_searches'] ) ) : ?>
									<tr>
										<td colspan="3">
											<?php esc_html_e( 'It appears you do not have any popular search yet.', 'searchwp' ); ?>
										</td>
									</tr>
								<?php endif; ?>
								</tbody>
							</table>
						</div>
					</td>
					<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	<?php if ( ! empty( $summary['notification'] ) ) : ?>
		<tr class="summary-notice" align="center">
			<td class="summary-notification-block" bgcolor="#edf3f7">
				<table class="summary-notification-table summary-notification-table" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
					<tbody>
					<?php if ( ! empty( $summary['icons']['notification_block'] ) ) : ?>
						<tr>
							<td class="summary-notice-icon" align="center" valign="middle">
								<img src="<?php echo esc_url( $summary['icons']['notification_block'] ); ?>" width="52" height="52" alt="<?php esc_attr_e( 'Notification', 'searchwp' ); ?>" />
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( ! empty( $summary['notification']['title'] ) || ! empty( $summary['notification']['content'] ) ) : ?>
						<tr>
							<td class="summary-notice-content" align="center" valign="middle">
								<?php if ( ! empty( $summary['notification']['title'] ) ) : ?>
									<h4><?php echo esc_html( $summary['notification']['title'] ); ?></h4>
								<?php endif; ?>
								<?php if ( ! empty( $summary['notification']['content'] ) ) : ?>
									<p><?php echo wp_kses_post( $summary['notification']['content'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( ! empty( $summary['notification']['button'] ) ) : ?>
						<tr>
							<td class="button-container" align="center" valign="middle">
								<table class="button-wrapper" cellspacing="24">
									<tr>
										<?php if ( ! empty( $summary['notification']['button']['url'] ) && ! empty( $summary['notification']['button']['text'] ) ) : ?>
											<td class="button button-blue" align="center" border="1" valign="middle">
												<a href="<?php echo esc_url( $summary['notification']['button']['url'] ); ?>" class="button-link" rel="noopener noreferrer" target="_blank" bgcolor="#036aab">
													<?php echo esc_html( $summary['notification']['button']['text'] ); ?>
												</a>
											</td>
										<?php endif; ?>
									</tr>
								</table>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
			</td>
		</tr>
	<?php endif; ?>
	<?php if ( ! empty( $summary['info_block'] ) && empty( $summary['notification'] ) ) : ?>
		<tr class="summary-notice" align="center">
			<td class="summary-info-block" bgcolor="#f7f0ed">
				<table class="summary-info-table summary-notice-table" border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
					<tbody>
					<?php if ( ! empty( $summary['icons']['info_block'] ) ) : ?>
						<tr>
							<td class="summary-notice-icon" align="center" valign="middle">
								<img src="<?php echo esc_url( $summary['icons']['info_block'] ); ?>" width="52" height="52" alt="<?php esc_attr_e( 'Info', 'searchwp' ); ?>" />
							</td>
						</tr>
					<?php endif; ?>
					<?php if ( ! empty( $summary['info_block']['title'] ) || ! empty( $summary['info_block']['content'] ) ) : ?>
						<tr>
							<td class="summary-notice-content" align="center" valign="middle">
								<?php if ( ! empty( $summary['info_block']['title'] ) ) : ?>
									<h4><?php echo esc_html( $summary['info_block']['title'] ); ?></h4>
								<?php endif; ?>
								<?php if ( ! empty( $summary['info_block']['content'] ) ) : ?>
									<p><?php echo wp_kses_post( $summary['info_block']['content'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( ! empty( $summary['info_block']['url'] ) && ! empty( $summary['info_block']['button'] ) ) : ?>
						<tr>
							<td class="button-container" align="center" valign="middle">
								<table class="button-wrapper" cellspacing="24">
									<tr>
										<td class="button button-orange" align="center" border="1" valign="middle">
											<a href="<?php echo esc_url( $summary['info_block']['url'] ); ?>" class="button-link" rel="noopener noreferrer" target="_blank" bgcolor="#e27730">
												<?php echo esc_html( $summary['info_block']['button'] ); ?>
											</a>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					<?php endif; ?>
					</tbody>
				</table>
			</td>
		</tr>
	<?php endif; ?>
	</tbody>
</table>
