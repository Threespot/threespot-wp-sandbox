<div class="pi-left-panel">
    <ul>
    	<?php if(current_user_can('manage_options')) { ?>
        <li class="pi-box">
            <h4><?php _e("Total Courses", "ld_propanel"); ?></h4>
            <p><?php echo $count_courses; ?></p>
        </li>
        <li class="pi-box">
            <h4><?php _e("Total Students", "ld_propanel"); ?></h4>
            <p><?php echo $total_users; ?></p>
        </li>
        <?php } else { ?>
        <li class="pi-box">
            <p><a href="<?php echo admin_url('admin.php?page=group_admin_page'); ?>" class="ld-btn"><?php _e("Manage Groups", "ld_propanel"); ?></a></p>
        </li>
    	<?php } ?>
        <li class="pi-box relative">
            <a class="viewall" href="javascript:;" onclick="learndash_propanel_view_all_courses();"><?php _e("View All", "ld_propanel"); ?></a>
            <h4><?php _e("Most Popular Courses", "ld_propanel"); ?></h4>
            <div class="most-popular-courses">
            <?php
            	LearnDash_Propanel::learndash_propanel_view_all_courses(3, false);
            ?>                   
        	</div>
        </li>  
    </ul>
</div>

<!-- Courses Dropdown -->
<div class="pi-right-panel pi-box">
    <h3><?php _e("Course Reports", "ld_propanel"); ?></h3>

    <form>
    	<select id="pi-courses" name="post_id" onChange="learndash_propanel_course_selected(this)">
    		<option value='0'><?php _e("-- Select --", "ld_propanel"); ?></option>
    	   <?php 
    	    foreach ($courses as $course)
            	echo "<option value='".$course->ID."'>".$course->post_title."</option>";
        	?>
	    </select>
    </form>
    <div id="learndash_propanel_course_progress">
    </div>
</div>