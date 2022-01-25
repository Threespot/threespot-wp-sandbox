	                    
	    <div class="pi-report learndash-theme meta-box-sortables">
	        <div id="learndash_propanel_tabs" class="postbox closed">
	        	<div class="handlediv"><br></div>
	            <ul>
	                <li><a href="#learndash_propanel_tabs-1" ><?php _e("Overview", "ld_propanel"); ?></a></li>
	                <li><a href="#learndash_propanel_tabs-2" ><?php _e("Activity Stream", "ld_propanel"); ?></a></li>
	                <li><a href="#learndash_propanel_tabs-3" ><?php _e("Assignments", "ld_propanel"); ?></a></li>
	            </ul>
	            <div class="inside">
	            <div id="learndash_propanel_tabs-1" class="cf learndash_propanel_tabs_panel">
	            	<?php LearnDash_ProPanel::overviewPanel();  ?>
	            </div>
	            <div id="learndash_propanel_tabs-2" class="learndash_propanel_tabs_panel">
	            	<?php LearnDash_ProPanel::activityPanel();  ?>
	            </div>
	            <div id="learndash_propanel_tabs-3" class="learndash_propanel_tabs_panel">
	            	<?php LearnDash_ProPanel::assignmentsPanel();  ?>
	            </div>
	        	<?php 
	        	if(!empty($email_message)) {
	                    	echo "<div class='updated' onclick='jQuery(this).hide();'>".$email_message."</div>";
	                    }
	            	?>
	       	    </div>
	        </div>
	    </div>
