<?php
/**
 * This file contains the wrapper for a custom alert message
 *
 * @since 3.0
 *
 * @package LearnDash
 */

$type = apply_filters(
	'ld-alert-type',
	( ( isset( $type ) ) && ( ! empty( $type ) ) ? $type : '' )
);

$icon = apply_filters(
	'ld-alert-icon',
	'ld-alert-icon ld-icon' . ( ( isset( $icon ) ) && ( ! empty( $icon ) ) ? ' ld-icon-' . $icon : '' ),
	( ! empty( $type ) ? $type : '' ),
	( ( isset( $icon ) ) && ( ! empty( $icon ) ) ? $icon : '' )
);

$class = apply_filters(
	'ld-alert-class',
	'ld-alert ' . ( ! empty( $type ) ? 'ld-alert-' . $type : '' ),
	( ! empty( $type ) ? $type : '' ),
	( ! empty( $icon ) ? $icon : '' )
);

$message = apply_filters(
	'learndash_alert_message',
	$message,
	( ! empty( $type ) ? $type : '' ),
	( ! empty( $icon ) ? $icon : '' )
);

if ( ( isset( $message ) ) && ( ! empty( $message ) ) ) :

	/**
	 * Add content between before an alert
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-alert-before', $class, $icon, $message, $type ); ?>

	<div class="<?php echo esc_attr( $class ); ?>">
		<div class="ld-alert-content">

			<?php
			/**
			 * Add content between before an alert icon
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-alert-icon-before', $class, $icon, $message, $type );

			if ( ! empty( $icon ) ) :
				?>
				<div class="<?php echo esc_attr( $icon ); ?>"></div>
				<?php
			endif;

			/**
			 * Add content after an alert icon
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-alert-icon-after', $class, $icon, $message, $type );

			?>
			<div class="ld-alert-messages">
			<?php
			echo wp_kses_post( $message );
			?>
			</div>
			<?php

			/**
			 * Add content after an alert message
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-alert-message-after', $class, $icon, $message, $type );
			?>
		</div>

		<?php
		/**
		 * Add content between alert message and button
		 *
		 * @since 3.0
		 */
		do_action( 'learndash-alert-between-message-button', $class, $icon, $message, $type );

		$button = apply_filters(
			'ld-alert-button',
			( ( isset( $button ) ) && ( ! empty( $button ) ) ? $button : array() )
		);

		if ( is_array( $button ) && ! empty( $button ) ) :

			$button_target = ( ( isset( $button['target'] ) ) && ( ! empty( $button['target'] ) ) ? 'target="' . esc_attr( $button['target'] ) . '"' : '' );
			$button_class  = 'class="ld-button' . ( ( isset( $button['class'] ) ) && ( ! empty( $button['class'] ) ) ? ' ' . esc_attr( $button['class'] ) : '' ) . '"';
			$button_url    = ( ( isset( $button['url'] ) ) && ( ! empty( $button['url'] ) ) ? 'href="' . esc_url( $button['url'] ) . '"' : '' );
			$button_label  = ( ( isset( $button['label'] ) ) && ( ! empty( $button['label'] ) ) ? esc_html( $button['label'] ) : '' );
			$button_icon   = ( ( isset( $button['icon'] ) ) && ( ! empty( $button['icon'] ) ) ? '<span class="ld-icon ld-icon-' . esc_attr( $button['icon'] ) . '"></span>' : '' );

			// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above
			?>
			<a <?php echo $button_class; ?> <?php echo $button_url; ?> <?php echo $button_target; ?>>
				<?php echo $button_icon; ?>
				<?php echo $button_label; ?>
			</a>
			<?php
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		endif;

		/**
		 * Add content after an alert button
		 *
		 * @since 3.0
		 */
		do_action( 'learndash-alert-content-after', $class, $icon, $message, $type );
		?>
	</div>

	<?php
	/**
	 * Add content after an alert
	 *
	 * @since 3.0
	 */
	do_action( 'learndash-alert-after', $class, $icon, $message, $type );

endif; ?>
