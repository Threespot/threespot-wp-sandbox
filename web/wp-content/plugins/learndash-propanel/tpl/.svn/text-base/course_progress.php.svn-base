<input type="hidden" class="learndash_propanel_course_details" value="<?php echo $course->ID; ?>">
<div class="box" id="course-dashboard">
	<form method="post">
		<h4 class="learndash_propanel_send_message_label"><?php echo sprintf(__("Send message to all %s users", "ld_propanel"), "<i>".$progress_data["course_title"]."</i>"); ?></h4>
		<label><?php _e("Subject:", "ld_propanel"); ?> </label>
		<input type="text" name="learndash_propanel_subject" style="margin-bottom: 15px;"><br>
		<div class="learndash_propanel_message_label"><label for="learndash_propanel_message"><?php _e("Message:", "ld_propanel"); ?> </label></div><textarea rows="10" cols="40" id="learndash_propanel_message" name="learndash_propanel_message"></textarea><br>
		<input type="submit" name="submit_propanel_email" value="<?php _e("Send Message", "ld_propanel"); ?>">
		<input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
	</form>

	<div class="pi-graph cf">
	    <input type="hidden" class="course_count" value="<?php echo count($progress_data["users"]) ;?>">
	        <div class="pi-legend-container">
	            <div class="not-started">
	               <span class="pi-legend"></span><?php _e("Not Started:", "ld_propanel"); ?> 
	                <?php echo (int)$progress_data['not_started'] ;?>%
	                <input type="hidden" id="not_started_<?php echo $course_id ;?>" value="<?php echo $progress_data['not_started'] ;?>">
	            </div>
	            <div class="in-progress">
	               <span class="pi-legend"></span> <?php _e("In Progress:", "ld_propanel"); ?> 
	                <?php echo (int)$progress_data['progress'] ;?>%
	                <input type="hidden" id="progress_<?php echo $course_id ;?>" value="<?php echo $progress_data['progress'] ;?>">

	            </div>
	            <div class="complete">
	               <span class="pi-legend"></span> <?php _e("Complete:", "ld_propanel"); ?>
	                <?php echo (int)$progress_data['completed'] ;?>%
	                <input type="hidden" id="complete_<?php echo $course_id;?>" value="<?php echo $progress_data['completed'] ;?>">
	            </div>
	        </div>  
	        <div class="pie-chart">
	            <div class="canvas-wrap">
	                <canvas id="canvas<?php echo $course_id;?>" width="250" height="250"></canvas>
	            </div>
	        </div>
	</div>
    <div class="wrap">
        <h3><?php _e("Export User Information", "ld_propanel"); ?></h3>
        <div class="learndash_propanel_filter">
            <?php _e("Filter", "ld_propanel"); ?> 
                <select name="learndash_propanel_progress_status"  id="learndash_propanel_progress_status"  onChange="learndash_pro_panel_search()">
                    <option value=""> <?php _e("All", "ld_propanel"); ?> </option>
                    <option value="not_started"> <?php _e("Not Started", "ld_propanel"); ?> </option>
                    <option value="in_progress"> <?php _e("In Progress", "ld_propanel"); ?> </option>
                    <option value="complete"> <?php _e("Complete", "ld_propanel"); ?> </option>
                </select> 
            <?php _e("Search", "ld_propanel"); ?> <input type="text" name="learndash_propanel_search"  id="learndash_propanel_search" onChange="learndash_pro_panel_search()">
        </div>
        <form method="post" class="pi-form-table" name="learndash_propanel_user_export_<?php echo $course_id?>" action="<?php echo admin_url( '/?learndash_propanel_csv_export=1'); ?>">
            <table id="learndash_propanel_user_table">
                <thead>
                    <tr>
                        <th><?php _e("S. No.", "ld_propanel"); ?></th>
                        <th><?php _e("Username", "ld_propanel"); ?></th>
                        <th><?php _e("Email", "ld_propanel"); ?></th>
                        <th><?php _e("Progress", "ld_propanel"); ?></th>
                        <th><?php _e("Edit Profile", "ld_propanel"); ?></th>
                    </tr>
                </thead>
                <tbody>
            <?php
            /*
				foreach ($progress_data["users"] as $progress_user) {
					
					$completed_class = empty($progress_user["percentage"])? "not-started":(($progress_user["percentage"] >= 100)? "complete":"progress");
                    ?>
                    <tr>
                        <td class="pi-user">
                            <p><?php echo $progress_user['name'] ;?></p>
                        </td>
                        <td class="pi-email">
                            <a href="mailto:<?php echo $progress_user['email'] ;?>"><?php echo $progress_user['email'] ;?></a> 
                        </td>
                        <td class="pi-progress widget_ldcourseprogress">
                            <dd class="course_progress <?php echo $completed_class; ?>" title="<?php echo sprintf(__("%s out of %s steps completed", "learndash"),$progress_user['completed_steps'], $progress_user['total_steps']); ?>">
                                <div class="course_progress_blue" style="width: <?php echo $progress_user['percentage']; ?>%;"></div> 
                            </dd>
                        </td>
                        <td class="pi-edit">
                            <a href="<?php echo admin_url( 'user-edit.php?user_id=' . $progress_user['user_id'], 'http' ); ?>"><?php echo sprintf(__("%s's Profile", "ld_propanel"), $progress_user["name"] ); ?></a>
                        </td>
                    </tr>
                    <?php
                } */
            ?>
                </tbody>  
            </table>
            <div id="learndash_propanel_user_table_pagination"></div>
            <p><?php _e("Press the button below to export user information.", "ld_propanel"); ?></p>
            <input type="submit" class="ld-btn" name="learndash_propanel_submit_<?php echo $course_id; ?>" value="<?php _e("Export User Info", "ld_propanel"); ?>" />
            <input type="hidden" name="learndash_propanel_course_id" value="<?php echo $course_id; ?>">
        </form>
    </div>
</div> 	
<script type="text/javascript">
var learndash_course_progress = <?php echo json_encode((array) $progress_data_users); ?>;
/*
jQuery(document).ready(function($){ 
		console.log("test");
        var wrap = jQuery( "#pi-courses option:selected" ).val();

		var notStarted = parseInt(jQuery('#not_started_' + wrap).val()) ;
		var progress   = parseInt(jQuery('#progress_' + wrap).val()) ;        
		var complete   = parseInt(jQuery('#complete_' + wrap).val()) ;        

       	var pieData = [
		{
		    value: notStarted,
		    color: "#a5a5a5" 
		},
		{
		    value : progress,
		    color : "#c3dd5a"
		},
		{
		    value : complete,
		    color : "#5CB85C"
		}

		];
        var myPie = new Chart(document.getElementById("canvas"+wrap).getContext("2d")).Pie(pieData);

       // console.log("learndash_course_progress_user_list");
     //   learndash_course_progress_user_list();
        
        function learndash_course_progress_user_list() {
        	var count = 0;
        	jQuery.each(learndash_course_progress,function(i, v) {
        		console.log("append_to_list: " + count);
        		count++;
        		append_to_list(v, count);
        	});
        }
        function append_to_list(user, count) {
        	setTimeout(function() {
        		console.log(count);
        	}, 2000*count);
        }
}); */
</script>
