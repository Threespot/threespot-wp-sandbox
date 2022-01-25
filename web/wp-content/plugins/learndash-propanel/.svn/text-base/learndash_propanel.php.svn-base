<?php
/**
 * @package LearnDash Pro Panel
 * @version 1.5.4 3
 */
/*
Plugin Name: LearnDash Pro Panel
Plugin URI: http://www.learndash.com
Description: Easily manage and view your LearnDash LMS activity.
Version: 1.5.4
Author: LearnDash
Author URI: http://www.learndash.com
*/



if(!class_exists('LearnDash_Propanel')):
class LearnDash_Propanel{
	static function addColorScheme(){	
	 	wp_admin_css_color(
			'propanel', 
			'LearnDash Pro Panel #1',
			plugins_url('css/colorscheme.css', __FILE__),
			array('#485B79', '#FB9337', '#fffff', '#666666')
		);
		wp_admin_css_color(
  			 'propanel2',
   			__('LearnDash Pro Panel #2'),
   			plugins_url('css/colorscheme2.css', __FILE__),
   			array('#485B79', '#beeb20', '#f4f4f4', '#2683AE')
 		);
		wp_admin_css_color(
  			 'propanel3',
   			__('LearnDash Pro Panel #3'),
   			plugins_url('css/colorscheme3.css', __FILE__),
   			array('#737373', '#3381a7', '#f4f4f4', '#666666')
 		);
		
		$admin_role = get_role('administrator');
		$admin_role->add_cap('propanel_widgets');
		$group_leader = get_role('group_leader');
		if(!empty($group_leader))
		$group_leader->add_cap('propanel_widgets');
	}

	static function createDashboardWidgets(){
		if(current_user_can('propanel_widgets')){
			wp_add_dashboard_widget(
				'learndash_propanel_dashboard_widget', 
				__('LearnDash Dashboard',  'ld_propanel'), 
				array('LearnDash_Propanel', 'learndash_propanel_dashboard_overview')
			);
		}
	}

	/**
	 * Create the Activity dashboard markup
	 * @return [type] [description]
	 */
	static function activityPanel(){
		global $wpdb;
		$current_user = wp_get_current_user();
		if(!current_user_can('manage_options') && function_exists('is_group_leader') && is_group_leader($current_user->ID)) {
				$group_leader = true;
				$users_group_ids = learndash_get_administrators_group_ids($current_user->ID);
				global $wpdb;

				$user_ids = array();
				foreach ($users_group_ids as $user_group_id) {
					$user_ids_of_group = learndash_get_groups_user_ids($user_group_id);
					foreach ($user_ids_of_group as $user_id) {
						$user_ids[$user_id] = $user_id;
					}
				}
		}

		$table = $wpdb->usermeta;
		if(is_multisite())
		$sql = $wpdb->prepare("SELECT user_id, meta_value FROM $table WHERE meta_key = '_sfwd-quizzes' AND user_id IN (SELECT user_id FROM $table where meta_key = 'primary_blog'  AND meta_value='%d')", $GLOBALS['blog_id']);
		else
		$sql = "SELECT user_id, meta_value FROM $table WHERE meta_key = '_sfwd-quizzes'";

		$meta_list = $wpdb->get_results( $sql, ARRAY_A );

		if($group_leader) {
			$meta_list2 = array();
			foreach ($meta_list as $key => $value) {
				if(in_array($value["user_id"], $user_ids))
					$meta_list2[] = $value;
			}
			$meta_list = $meta_list2;
			unset($meta_list2);
		}

		$sorted_quizzes = self::sortQuizzes($meta_list);

		$activity = array();
		$users = array();

		foreach($sorted_quizzes as $time => $quizdata){
			$userid = $quizdata['user'];
			if($group_leader && !in_array($userid, $user_ids))
				continue;
			if(array_search($userid, $users) === FALSE && sizeof($activity) < 10){
				$users[] = $userid;
				$userData = get_userdata($userid);
				$quiz = get_post( $quizdata['quiz'] );

				$activity[] = array(
					'user' => $userData->user_nicename, 
					'userLink' => admin_url('user-edit.php?user_id=' . $userData->ID . '#submit'),
					'type' => 'quiz',
					'date' => date('l, F j, Y', $time),
					'quiz' => $quiz->post_title,
					'quizLink' => get_edit_post_link($quiz->ID),
					'score' => $quizdata['score'],
					'count' => $quizdata['count'],
					'pass' => $quizdata['pass']
				);

				$progress = get_user_meta($userid, '_sfwd-course_progress', true);

				if(!empty($progress)){
					$activity[] = self::extractProgress($userData, $progress);
				}
			}
		}

		if(sizeof($activity) < 20){

			$table = $wpdb->usermeta;
			if(is_multisite())
			$sql = $wpdb->prepare("SELECT user_id, meta_value FROM $table WHERE meta_key = '_sfwd-course_progress' AND user_id IN (SELECT user_id FROM $table where meta_key = 'primary_blog' AND meta_value='%d')", $GLOBALS['blog_id']);
			else
			$sql = "SELECT user_id, meta_value FROM $table WHERE meta_key = '_sfwd-course_progress'";


			$meta_list = $wpdb->get_results( $sql, ARRAY_A );
			if($group_leader) {
				$meta_list2 = array();
				foreach ($meta_list as $key => $value) {
					if(in_array($value["user_id"], $user_ids))
						$meta_list2[] = $value;
				}
				$meta_list = $meta_list2;
				unset($meta_list2);
			}

			for($i = sizeof($meta_list) - 1; $i>=0; $i--){
				if(sizeof($activity) >= 20)
					continue;

				$progress = $meta_list[$i];
				$userId = $progress['user_id'];
				if( array_search($userId, $users) === FALSE && !empty($progress) ){
					$activity[] = self::extractProgress(get_userdata($userId), unserialize($progress['meta_value']));
					$users[] = $userId;
				}
			}

		}

		include 'tpl/activity_panel.php';
	}

	/**
	 * Create the assigments dashboard widget markup
	 * @return [type] [description]
	 */
	static function assignmentsPanel(){
		global $wpdb;
		$current_user = wp_get_current_user();
		if(!current_user_can('manage_options') && function_exists('is_group_leader') && is_group_leader($current_user->ID)) {
				$group_leader = true;
				$users_group_ids = learndash_get_administrators_group_ids($current_user->ID);
				global $wpdb;

				$user_ids = array();
				foreach ($users_group_ids as $user_group_id) {
					$user_ids_of_group = learndash_get_groups_user_ids($user_group_id);
					foreach ($user_ids_of_group as $user_id) {
						$user_ids[$user_id] = $user_id;
					}
				}
		}

		if($group_leader) {
		$opt = array(
				'post_type'		=> 'sfwd-assignment',
				'posts_per_page'=> 20,
				'author__in'	=> $user_ids
			);
		}
		else
		$opt = array(
				'post_type'		=> 'sfwd-assignment',
				'posts_per_page'=> 20,
			);
		$assignments = get_posts($opt);
		
		$ordered = array();
		$users = array();
		$posts = array();
		foreach ($assignments as $assignment) {
			if(empty($users[$assignment->post_author]))
				$users[$assignment->post_author] = get_user_by("id", $assignment->post_author);

			if(empty($users[$assignment->post_author]))
				continue;
			$meta = get_post_meta($assignment->ID);
			$lesson_id  = @$meta["lesson_id"][0];
		
			if(empty($lesson_id))
				continue;
		
			if(empty($posts[$lesson_id]))
				$posts[$lesson_id] = get_post($lesson_id);

			if(empty($posts[$lesson_id]))
				continue;
		
			$lesson = $posts[$lesson_id];
			$user = $users[$assignment->post_author];
			$completed = learndash_is_assignment_approved_by_meta($assignment->ID); 
			$assData = self::extractAssignmentDataNew($assignment, $user, $lesson, $meta, $completed);
			if($completed)
				$ordered[] = $assData;
			else
				array_unshift($ordered, $assData);				
		}

		include 'tpl/assignments_panel.php';
	}
	static function extractAssignmentDataNew($assignment, $user, $lesson, $meta, $completed) {
		return array(
			'id' => $assignment->ID,
			'user' => $user->user_nicename,
			'userLink' => admin_url('user-edit.php?user_id=' . $user->ID . '#submit'),
			'userid' => $user->ID,
			'completed' => $completed,
			'lesson' => $lesson->post_title,
			'lessonLink' => get_permalink($lesson->ID),
			'lessonid' => $lesson->ID,
			'name' => @$meta['file_name'][0],
			'url' => @$meta['file_link'][0],
			'path' => @$meta['file_path'][0]
		);		
	}

	static function extractAssignmentData($ass, $user, $lesson, $completed){
		return array(
			'id' => $ass['id'],
			'user' => $user->user_nicename,
			'userLink' => admin_url('user-edit.php?user_id=' . $user->ID . '#submit'),
			'userid' => $user->ID,
			'completed' => $completed,
			'lesson' => $lesson->post_title,
			'lessonLink' => get_permalink($lesson->ID),
			'lessonid' => $lesson->ID,
			'name' => $ass['file_name'],
			'url' => $ass['file_link'],
			'path' => $ass['file_path']
		);
	}

	static function extractProgress($userData, $progress){
		$courseProgress = false;
		foreach($progress as $id => $course){
			$courseProgress = $course;
			$courseProgress['id'] = $id;
			continue;
		}
		$course = get_post($courseProgress['id']);
		return array(
			'user' => $userData->user_nicename,
			'userLink' => admin_url('user-edit.php?user_id=' . $userData->ID . '#submit'),
			'type' => 'course',
			'course' => $course->post_title,
			'courseLink' => get_edit_post_link($course->ID),
			'completed' => $courseProgress['completed'],
			'total' => $courseProgress['total']
		);
	}

	static function sortQuizzes($dbquiz){
		$sorted = array();
		foreach($dbquiz as $quiz){
			$quizzes = unserialize($quiz['meta_value']);
			$data = $quizzes[sizeof($quizzes) - 1];
			$data['user'] = $quiz['user_id'];
			$sorted[$data['time']] = $data;
		}
		krsort($sorted);
		return $sorted;
	}

	static function i18nize() {
		load_plugin_textdomain( 'ld_propanel', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 	
	}

	static function addResources(){
		wp_register_style( 'myPluginStylesheet', plugins_url('css/pistyle.css', __FILE__) );
	    wp_enqueue_style( 'myPluginStylesheet' );
	    wp_enqueue_style('pi-ui', plugins_url('css/jquery-ui-1.10.4.custom.css', __FILE__));
	    //wp_enqueue_style('pi-ui', '//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/smoothness/jquery-ui.css');
	    wp_enqueue_script( 'jquery' );
	    wp_enqueue_script( 'charts-js', plugins_url('js/chart.js', __FILE__), array('jquery') );
	    wp_enqueue_script( 'jquery-ui-tabs' );
	    
		wp_enqueue_style('propanel', plugins_url('css/propanel.css', __FILE__));

		if(is_admin() && get_current_screen()->id == "dashboard") {
			wp_enqueue_script('propaneljs', plugins_url('js/propanel.js', __FILE__), array('jquery','jquery-ui-tabs','charts-js' ));
			wp_localize_script( 'propaneljs', 'LearnDash_Propanel', array("loading" => __("Generating Report", "ld_propanel"),"next" => __("Next", "ld_propanel"),"prev" => __("Prev", "ld_propanel"),"profile_label" => __("%s's Profile", "ld_propanel"), "user_edit_url" => self_admin_url( 'user-edit.php' ) ) );
		}
	}

	static function setColorScheme(){
		$user = get_current_user_id();
		update_user_option($user, 'admin_color', 'propanel', true);
	  	update_user_option($user, 'admin_color', 'propanel2', true);
		update_user_option($user, 'admin_color', 'propanel3', true);
	}


	function learndash_propanel_dashboard_overview() {
		$email_message = LearnDash_Propanel::send_dashboard_emails();
		include 'tpl/dashboard.php';
	}
	static function overviewPanel() {
	    global $wpdb;

	  // $courses = get_pages("post_type=sfwd-courses");
	    $courses = get_posts("post_type=sfwd-courses&posts_per_page=-1");
	    $count_courses = count($courses);//wp_count_posts("sfwd-courses");
	    $count = $count_courses->publish;

	    /*if(method_exists('SFWD_LMS', 'course_progress_data'))
	    $course_progress_data = SFWD_LMS::course_progress_data();
		else
		return;

		$course_progress_data = LearnDash_Propanel::rearrange_course_progress_data($course_progress_data);
		*/
		$users = count_users();
		$total_users = $users["total_users"];
	    //Emails array use this one for username and all userdata pero course

		include 'tpl/overview_panel.php';		
	}
	static function send_dashboard_emails($course_progress_data = null) { 
		if(empty($_POST["submit_propanel_email"]) || empty($_POST["course_id"]) || empty($_POST[ "learndash_propanel_message"]) || empty($_POST[ "learndash_propanel_subject"]) )
			return; 

		$course_id = $_POST["course_id"];

		if(is_null($course_progress_data))
	    	$course_progress_data = SFWD_LMS::course_progress_data($course_id);

	    if(empty($course_progress_data))
	    	return;

		$course_id = $_POST["course_id"];
		if(empty($course_progress_data))
			return; 
		$users = $course_progress_data;
    	$email_to = array();
        // Check if the "from" input field is filled out      
        $message = stripslashes($_POST[ "learndash_propanel_message"]);
        $subject = stripslashes($_POST[ "learndash_propanel_subject"]);

        // message lines should not exceed 70 characters (PHP rule), so wrap it
        $message = wordwrap($message, 70);

        // send mail
        foreach ($users as $user) {
                //$email_to[] = $user['email'];
        	wp_mail($user['email'],$subject,$message);
        }

        return __("Message Sent", "ld_propanel");                            
	}
		static function rearrange_course_progress_data($data) {
			$course_progress_data = array();

			if(!empty($data))
			foreach ($data as $d) {
				$course_id = $d["course_id"];
				$user_id = $d["user_id"];

				if(empty($course_progress_data[$course_id]))
					$course_progress_data[$course_id] = array("course_title" => $d["course_title"], "users" => array(), "not_started" => 0, "progress" => 0, "completed" => 0);

				if(empty($course_progress_data[$course_id]["users"][$user_id])) {
					$d["percentage"] = LearnDash_Propanel::calculate_percentage_completion($d["completed_steps"], $d["total_steps"]);
					if(empty($d["percentage"]))
						$course_progress_data[$course_id]["not_started"]++;
					else if($d["percentage"] > 0 && $d["percentage"] < 100)
						$course_progress_data[$course_id]["progress"]++;
					else if($d["percentage"] >= 100)
						$course_progress_data[$course_id]["completed"]++;
					else
						$course_progress_data[$course_id]["not_started"]++;

					$course_progress_data[$course_id]["users"][$user_id] = $d;
				}
			}

			if(!empty($course_progress_data))
			foreach ($course_progress_data as $key => $value) {
				if($count = count($course_progress_data[$key]["users"])) {
					$course_progress_data[$key]["not_started"] = $course_progress_data[$key]["not_started"]*100/$count;
					$course_progress_data[$key]["progress"] = $course_progress_data[$key]["progress"]*100/$count;
					$course_progress_data[$key]["completed"] = $course_progress_data[$key]["completed"]*100/$count;
				}
			}
			return $course_progress_data;
		}

	static function learndash_propanel_csv_export() {
		if(empty($_REQUEST['learndash_propanel_csv_export']) || empty($_POST['learndash_propanel_course_id']))
			return;

	//	error_reporting(0);
		set_time_limit(0);

		$content = SFWD_LMS::course_progress_data($_POST['learndash_propanel_course_id']);

		if ( empty( $content ) ) {
			$content[] = Array( 'status' => __('No attempts', 'learndash'));
		}
		require_once( dirname(__FILE__) . '/parsecsv.lib.php' );
		$csv = new lmsParseCSV();
		$csv->output( true, 'courses.csv', $content, array_keys( reset( $content ) ) );
		die();
	}
	static function calculate_percentage_completion($completed, $total) {
		if(empty($completed))
			return 0;

		$percentage = intVal($completed*100/$total);
		$percentage = ($percentage > 100)? 100:$percentage;

		return $percentage;
	}
	function learndash_propanel_view_all_courses($limit = 300, $exit = true) {
		global $wpdb;
		if(empty($limit) || !is_integer($limit))
			$limit = 300;
		if(is_null($exit))
			$exit = true;
		$current_user = wp_get_current_user();
		if(!current_user_can('manage_options') && function_exists('is_group_leader') && is_group_leader($current_user->ID)) {
				$group_leader = true;
				$users_group_ids = learndash_get_administrators_group_ids($current_user->ID);
				global $wpdb;

				$user_ids = array();
				foreach ($users_group_ids as $user_group_id) {
					$user_ids_of_group = learndash_get_groups_user_ids($user_group_id);
					foreach ($user_ids_of_group as $user_id) {
						$user_ids[$user_id] = $user_id;
					}
				}
		}
		if($group_leader)
		{
			if(empty($user_ids))
			$results = array();
			else
			$results = $wpdb->get_results("SELECT meta_key, count(*) as count FROM $wpdb->usermeta where user_id IN ('".implode(",", $user_ids)."') AND meta_key like 'course_%_access_from' group by meta_key order by count desc LIMIT ".$limit);
		}
		else
		$results = $wpdb->get_results("SELECT meta_key, count(*) as count FROM $wpdb->usermeta where meta_key like 'course_%_access_from' group by meta_key order by count desc LIMIT ".$limit);

		$courses = array();

		$html = '';
		foreach ($results as $meta) {
			$course_id = str_replace(array("course_","_access_from"), "", $meta->meta_key);
			$course = get_post($course_id);
			if(!empty($course->ID))
			{	
				//$count = $meta->count;
				//$users = ($count == 1)? $count." ".__("user", "ld_propanel"):$count." ".__("users", "ld_propanel");
				$courses[] = $course;
				$html .= "<div>".$course->post_title. "</div>";
			}
		}
		
		if(is_string($exist) && $exit == "array") {
			return $courses;
		}

		echo $html;
		
		if($exit)
		exit;
	}
	static function learndash_propanel_ajax() {
		if(empty($_REQUEST["function"])) {
			return;
		}
		$current_user = wp_get_current_user();
		if(!current_user_can('manage_options') && function_exists('is_group_leader') && !is_group_leader($current_user->ID))
			return;

		switch ($_REQUEST["function"]) {
			case 'learndash_propanel_view_all_courses':
				LearnDash_Propanel::learndash_propanel_view_all_courses();
				exit;
				break;
			case 'activityPanel' :
				echo LearnDash_Propanel::activityPanel();
				exit;
			case 'assignmentsPanel' :
				echo LearnDash_Propanel::assignmentsPanel();
				exit;
			case 'overviewPanel' :
				echo LearnDash_Propanel::overviewPanel();
				exit;
			case 'course_selected' :
				$course_id = @$_REQUEST["data"]["course_id"];
				if(!empty($course_id))
				echo LearnDash_Propanel::course_selected($course_id);
				exit;
			default:
				exit;
				break;
		}
	}
	static function course_selected($course_id) {
	    $course_progress_data = SFWD_LMS::course_progress_data($course_id);
		$course_progress_data = LearnDash_Propanel::rearrange_course_progress_data($course_progress_data);
		$progress_data = $course_progress_data[$course_id];
		foreach ($progress_data["users"] as $user) {
			$progress_data_users[] = $user;
		}
		include 'tpl/course_progress.php';
	}
	static function sanitize_filename($file) {
		return preg_replace('/[^A-Za-z0-9\-\_]/', '', $file);
	}
}


add_action( 'admin_init', array('LearnDash_Propanel','learndash_propanel_csv_export'));
add_action( 'admin_init', array('LearnDash_Propanel','addColorScheme'));
add_action( 'wp_dashboard_setup', array('LearnDash_Propanel','createDashboardWidgets'));
add_action( 'admin_enqueue_scripts', array('LearnDash_Propanel','addResources'));
add_action( 'plugins_loaded', array('LearnDash_Propanel','i18nize'));
add_action( 'wp_ajax_learndash_propanel_ajax', array('LearnDash_Propanel','learndash_propanel_ajax'));


// Load the auto-update class
add_action('init', 'nss_plugin_updater_activate_learndash_propanel');
function nss_plugin_updater_activate_learndash_propanel()
{
	//if(!class_exists('nss_plugin_updater'))
    require_once (dirname(__FILE__).'/wp_autoupdate_propanel.php');
	
	$nss_plugin_updater_plugin_remote_path = 'http://support.learndash.com/';
    $nss_plugin_updater_plugin_slug = plugin_basename(__FILE__);

    new nss_plugin_updater_learndash_propanel ($nss_plugin_updater_plugin_remote_path, $nss_plugin_updater_plugin_slug);
}


function learndash_propanel_admin_tabs($admin_tabs) {
        $admin_tabs["propanel"] = array(
                                                                        "link"  =>      'admin.php?page=nss_plugin_license-learndash_propanel-settings',
                                                                        "name"  =>      __("ProPanel License","learndash_propanel"),
                                                                        "id"    =>      "admin_page_nss_plugin_license-learndash_propanel-settings",
                                                                        "menu_link"     =>      "edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses",
                                                                );
        return $admin_tabs;
}
add_filter("learndash_admin_tabs", "learndash_propanel_admin_tabs", 1, 1);

function learndash_propanel_learndash_admin_tabs_on_page($admin_tabs_on_page, $admin_tabs, $current_page_id) {

        if(empty($admin_tabs_on_page["admin_page_nss_plugin_license-learndash_propanel-settings"]) || !count($admin_tabs_on_page["admin_page_nss_plugin_license-learndash_propanel-settings"]))
                $admin_tabs_on_page["admin_page_nss_plugin_license-learndash_propanel-settings"] = array();

        $admin_tabs_on_page["admin_page_nss_plugin_license-learndash_propanel-settings"] = array_merge($admin_tabs_on_page["sfwd-courses_page_sfwd-lms_sfwd_lms_post_type_sfwd-courses"], (array) $admin_tabs_on_page["admin_page_nss_plugin_license-learndash_propanel-settings"]);

        foreach ($admin_tabs as $key => $value) {
                if($value["id"] == $current_page_id && $value["menu_link"] == "edit.php?post_type=sfwd-courses&page=sfwd-lms_sfwd_lms.php_post_type_sfwd-courses")
                {
                        $admin_tabs_on_page[$current_page_id][] = "propanel";
                        return $admin_tabs_on_page;
                }
        }
        return $admin_tabs_on_page;
}
add_filter("learndash_admin_tabs_on_page", "learndash_propanel_learndash_admin_tabs_on_page", 3, 3);

endif;
