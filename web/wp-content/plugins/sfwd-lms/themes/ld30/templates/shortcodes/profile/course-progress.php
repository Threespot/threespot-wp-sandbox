<div class="ld-progress">
	<div class="ld-progress-heading">
		<div class="ld-progress-label">
		<?php
		printf(
			// translators: Course Progress Overview Label
			esc_html_x( '%s Progress', 'Course Progress Overview Label', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
		);
		?>
		</div>
		<div class="ld-progress-stats">
			<div class="ld-progress-percentage ld-secondary-color">
				<?php
				printf(
					// translators: Percentage of course completion
					esc_html_x( '%s%% Complete', 'Percentage of course completion', 'learndash' ),
					esc_html( $progress['percentage'] )
				);
				?>
			</div> <!--/.ld-course-progress-percentage-->
			<div class="ld-progress-steps">
			<?php
			echo sprintf(
				// translators: placeholder: completed steps, total steps'
				esc_html_x( '%1$d/%2$d Steps', 'placeholder: completed steps, total steps', 'learndash' ),
				esc_html( $progress['completed'] ),
				esc_html( $progress['total'] )
			);
			?>
			</div>
		</div> <!--/.ld-course-progress-stats-->
	</div> <!--/.ld-course-progress-heading-->

	<div class="ld-progress-bar">
		<div class="ld-progress-bar-percentage ld-secondary-background" style="width: <?php echo esc_attr( $progress['percentage'] ); ?>%;"></div>
	</div> <!--/.ld-course-progress-bar-->
</div> <!--/.ld-course-progress-->
