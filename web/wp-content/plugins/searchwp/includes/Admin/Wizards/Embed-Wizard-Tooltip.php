<?php
/**
 * Search Form and Results template Embed Wizard Tooltip.
 * Embed page tooltip HTML template.
 *
 * @since 4.5.0
 */

if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="searchwp-embed-wizard-tooltip">
	<div id="searchwp-embed-wizard-tooltip-content">
			<h3><?php esc_html_e( 'Add a Block', 'searchwp' ); ?></h3>
			<p>
				<?php
				printf(
					wp_kses(
						$tooltip_text,
						[
							'a'  => [
								'href'   => [],
								'rel'    => [],
								'target' => [],
							],
							'br' => [],
						]
					),
					esc_url( $learn_more_url )
				);
				?>
			</p>
			<i class="searchwp-embed-wizard-tooltip-arrow"></i>
		<button type="button" class="searchwp-embed-wizard-done-btn"><?php esc_html_e( 'Done', 'searchwp' ); ?></button>
	</div>
</div>
