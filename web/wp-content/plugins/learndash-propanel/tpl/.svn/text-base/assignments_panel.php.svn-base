<div id="pi-assignment-tab">
<div id="ld_assignments_widget">
<div id="ld_activity_actions" class="sidebar-name">
	<a class="ld-btn" href="<?php echo admin_url('edit.php?post_type=sfwd-assignment') ?>"><?php echo __('Assignment Management', 'ld_propanel') ?></a> 
</div>

	<?php if(sizeof($ordered)): foreach($ordered as $item): ?>

		<div class="activity_item assignment_<?php echo $item['completed'] ? 'done' : 'pendant' ?>">

			<div class="activity_item_header">

				<span class="activity_user"><a href="<?php echo $item['userLink'] ?>" title="<?php echo __('See user progress', 'ld_propanel') ?>"><?php echo $item['user'] ?></a></span> 

				<?php _e('in', 'ld_propanel') ?> 

				<span class="activity_lesson"><a href="<?php echo $item['lessonLink'] ?>" title="<?php echo __('See lesson assignments', 'ld_propanel') ?>"><?php echo $item['lesson'] ?></a></span> 

				<?php _e('uploaded', 'ld_propanel') ?> 

				<a href="<?php echo $item['url'] ?>"><?php echo $item['name'] ?></a>.

			</div>

			<div class="activity_item_actions">

				<a href="<?php echo $item['url'] ?>"><?php _e('Download', 'ld_propanel') ?></a> - 				

				<span class="assignment_status">

					<?php if ($item['completed']): ?>

					<b><?php _e('Completed', 'ld_propanel') ?></b>

					<?php else: ?>

					<a class="assignment_complete" href="#" data-user="<?php echo $item['userid'] ?>" data-lesson="<?php echo $item['lessonid'] ?>"><?php _e('Mark as completed', 'ld_propanel') ?></a>

					<?php endif; ?>

				</span> - 

				<a class="assignment_delete" href="#" data-id="<?php echo $item['id'] ?>" data-name="<?php echo $item['name'] ?>"><?php _e('Delete', 'ld_propanel') ?></a>

			</div>	

			<div class="activity_loading"><?php _e('Marking as completed ...', 'ld_propanel') ?></div>

			<div class="activity_deleting"><?php _e('Deleting ...', 'ld_propanel') ?></div>

		</div>

	<?php endforeach; else: ?>

		<div class="activity_item"><?php _e('No assigments found.', 'ld_propanel') ?></div>

	<?php endif; ?>

	<div class="activity_lang activity_lang_completed"><b><?php _e('Completed', 'ld_propanel') ?></b></div>

	<div class="activity_lang activity_lang_confirm"><?php _e('Are you sure you want to delete this assignment?', 'ld_propanel') ?></div>

</div>
</div>

