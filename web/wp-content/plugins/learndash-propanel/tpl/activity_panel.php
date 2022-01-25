<div id="ldp-activity-stream" >
<div id="ld_activity_widget">
<div id="ld_activity_actions" class="sidebar-name">

	<a class="ld-btn" href="<?php echo admin_url('?quiz_export_submit=1&nonce-sfwd=' . wp_create_nonce('sfwd-nonce') ) ?>" class="activity_export_quiz"><?php echo __('Export Quiz Data', 'ld_propanel') ?></a> 

	<a class="ld-btn" href="<?php echo admin_url('?courses_export_submit=1&nonce-sfwd=' . wp_create_nonce('sfwd-nonce') ) ?>" class="activity_export_course"><?php echo __('Export Course Data', 'ld_propanel') ?></a>

</div>
	<?php if(sizeof($activity)):  foreach($activity as $item): ?>

		<div class="activity_item activity_<?php echo $item['type'] ?>">

			<div class="activity_item_header">

				<span class="activity_user"><a href="<?php echo $item['userLink'] ?>" title="<?php echo __('See user progress', 'ld_propanel') ?>"><?php echo $item['user'] ?></a></span> 

				<?php if($item['type'] == 'quiz'): ?>

				<abbr class="activity_date"><?php echo $item['date'] ?></abbr>	

				<?php endif; ?>

			</div>

			<div class="activity_item_content">

			<?php if ($item['type'] == 'quiz'): ?>

				<?php echo __('Quiz', 'ld_propanel') ?> <b><a href="<?php echo $item['quizLink'] ?>" class="activity_quiz_link "><?php echo $item['quiz'] ?></a></b> <?php echo __('completed', 'ld_propanel') ?>: 

				<b><?php echo $item['pass'] ? __('Passed', 'ld_propanel') : __('Not Passed', 'ld_propanel') ?></b>. 

				<?php echo __('Score', 'ld_propanel') ?> <?php echo $item['score'] ?> <?php echo __('out of', 'ld_propanel') ?> <?php echo $item['count'] ?>.

			<?php else: ?>

				<?php echo __('Course', 'ld_propanel') ?> <b><a href="<?php echo $item['courseLink'] ?>" class="activity_course_link "><?php echo $item['course'] ?></a></b> <?php echo __('updated', 'ld_propanel') ?>: 

				<?php echo __('Completed', 'ld_propanel'), ' ', $item['completed'], ' ',  __('out of', 'ld_propanel'), ' ', $item['total'], ' ', __('steps', 'ld_propanel') ?>.

			<?php endif; ?>

			</div>

		</div>

	<?php endforeach; else: ?>

		<div class="activity_item"><?php _e('No activity found.', 'ld_propanel') ?></div>

	<?php endif; ?>

</div>
</div>



