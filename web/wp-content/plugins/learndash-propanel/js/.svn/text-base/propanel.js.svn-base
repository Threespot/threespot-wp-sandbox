var learndash_course_progress_filtered = new Array;

jQuery(function($){
	var panel = $('#ld_assignments_widget');
	if(panel.length){
		panel
			.on('click', 'a.assignment_complete', function(e){
				e.preventDefault();
				var a = $(e.target),
					item = a.closest('.activity_item'),
					lesson = a.attr('data-lesson'),
					user = a.attr('data-user')
				;
				item.find('.activity_item_actions').fadeOut('fast', function(){
					item.find('.activity_loading').fadeIn('fast');
				});
				$.post(item.find('.activity_lesson a').attr('href'), {userid: user, attachment_mark_complete: lesson}, function(){
					item.find('.assignment_status').html(panel.find('.activity_lang_completed').html());

					item.find('.activity_item_actions').show();
					item.find('.activity_loading').hide();

					item.removeClass('assignment_pendant');
				});
			})
			.on('click', 'a.assignment_delete', function(e){
				if(confirm(panel.find('.activity_lang_confirm').text())){
					e.preventDefault();
					var a = $(e.target),
						item = a.closest('.activity_item'),
						id = a.attr('data-id'),
						name = a.attr('data-name')
					;
					item.find('.activity_item_actions').fadeOut('fast', function(){
						item.find('.activity_deleting').fadeIn('fast');
					});
					$.get(
						item.find('.activity_lesson a').attr('href'), 
						{
							learndash_delete_attachment: id
						},
						function(){
							item.slideUp();
						}
					);
				}
			});
		;
	}
});


function learndash_propanel_course_progress_loaded() {
	//console.log("test");
    var wrap = jQuery( "#pi-courses option:selected" ).val();
   // console.log(wrap);
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

    learndash_course_progress_filtered = learndash_course_progress;
    learndash_course_progress_user_list(1, 50);

}
// console.log("learndash_course_progress_user_list");

function learndash_course_progress_user_list(page, limit) {
	var count = 0;
	var start = (page - 1) * limit;
	var learndash_propanel_user_table_body = jQuery("#learndash_propanel_user_table tbody");
	learndash_propanel_user_table_body.html(''); 

	for(var i = start; i < page * limit; i++) {
		//count++;
		if(typeof learndash_course_progress_filtered[i] == "object")
		learndash_propanel_append_to_list(learndash_course_progress_filtered[i], i+1);		
		else
		break;
	}
	var pagination = '';

	if(page > 1)
	pagination += '<a href="#" onClick="learndash_course_progress_user_list( ' + (page - 1) + ', ' + limit+ '); return false;">' + LearnDash_Propanel.prev + '</a>';

	if(page - 2 >= 1)	
	pagination += '<a href="#" onClick="learndash_course_progress_user_list( ' + (page - 2) + ', ' + limit+ '); return false;">' + (page - 2) + '</a>';
	if(page - 1 >= 1)	
	pagination += '<a href="#" onClick="learndash_course_progress_user_list( ' + (page - 1) + ', ' + limit+ '); return false;">' + (page - 1) + '</a>';

	if(!(page == 1 && typeof learndash_course_progress_filtered[i+1] != "object"))
	pagination += '<a class="current" href="#" onClick="return false;">' + (page) + '</a>';

	if(typeof learndash_course_progress_filtered[page*limit] == "object")
	pagination += '<a href="#" onClick="learndash_course_progress_user_list( ' + (page + 1) + ', ' + limit+ '); return false;">' + (page + 1) + '</a>';
	if(typeof learndash_course_progress_filtered[(page + 1) *limit] == "object")
	pagination += '<a href="#" onClick="learndash_course_progress_user_list( ' + (page + 2) + ', ' + limit+ '); return false;">' + (page + 2) + '</a>';

	if(typeof learndash_course_progress_filtered[i+1] == "object")
	pagination += '<a href="#"  onClick="learndash_course_progress_user_list( ' + (page + 1) + ', ' + limit+ '); return false;">' + LearnDash_Propanel.next + '</a>';

	jQuery("#learndash_propanel_user_table_pagination").html(pagination);
	/*
	jQuery.each(learndash_course_progress,function(i, v) {
	//	console.log("append_to_list: " + count);
		count++;
	});
	*/
}
function learndash_pro_panel_search() {
	var status = jQuery("#learndash_propanel_progress_status").val();
	var search_text = jQuery("#learndash_propanel_search").val().trim().toLowerCase();

	learndash_course_progress_filtered = new Array();

	var count = 0;
	jQuery.each(learndash_course_progress,function(i, user) {
		if(status != "")
		switch(status) {
			case "in_progress":
				if(user.percentage <= 0 || user.percentage >= 100)
					return true;

				break;
			case "not_started":
				if(user.percentage > 0)
					return true;

				break;
			case "complete":
				if(user.percentage < 100)
					return true;
				break;
			default:
				break;
		}

		if(search_text != "")
		{
			if( user.name.toLowerCase().search(search_text) < 0 &&  user.email.toLowerCase().search(search_text) < 0)
				return true;
		}

		learndash_course_progress_filtered[count] = user;
		count++;
	});
   learndash_course_progress_user_list(1, 50);
}
function learndash_propanel_append_to_list(user, count) {
	/*setTimeout(function() {*/
		var learndash_propanel_user_table_body = jQuery("#learndash_propanel_user_table tbody");
		var status = '';
		if(user.percentage >= 100)
			status = 'complete';
		else if(user.percentage <= 0)
			status = 'not_started';
		else
			status = 'in-progress';


		var user_row = '<tr><td>' + count + '</td><td class="pi-user"><p>' + user.name + '</p></td><td class="pi-email"><a href="mailto:' + user.email + '">' + user.email + '</a> </td><td class="pi-progress widget_ldcourseprogress"><dd class="course_progress '+ status + '" title=""><div class="course_progress_blue" style="width: ' + user.percentage + '%;"></div> </dd></td><td class="pi-edit"><a href="' + LearnDash_Propanel.user_edit_url + '?user_id=' + user.user_id + '">' + LearnDash_Propanel.profile_label.replace("%s", user.name) + '</a></td></tr>';

		learndash_propanel_user_table_body.append(user_row);

	//	console.log(count);
	/*}, 50*count);*/
}

jQuery(document).ready(function($){
	//jQuery('.pi-report').insertBefore('#dashboard-widgets-wrap');
jQuery('.pi-report').insertBefore('#postbox-container-1');

	jQuery(function() {
		jQuery( "#learndash_propanel_tabs" ).tabs({
			collapsible: false
		});
		jQuery("#learndash_propanel_tabs a").click(function() {
			if(jQuery( "#learndash_propanel_tabs" ).hasClass("closed"))
				jQuery( "#learndash_propanel_tabs .handlediv" ).click();
		});
	});

});
function learndash_propanel_view_all_courses() {
	jQuery.post(ajaxurl, {"action": "learndash_propanel_ajax", "function": "learndash_propanel_view_all_courses"}, function(data) {
		jQuery(".most-popular-courses").html(data);
	});
}
function learndash_propanel_ajax(fn, to, data, force, success_function) {
	var to_div = jQuery(to);
	if(to_div.text().length > 5 && typeof force == "undefined")
		return;
	else
	{
		learndash_propanel_show_loadingbar(to_div);
		jQuery.post(ajaxurl, {"action": "learndash_propanel_ajax", "function": fn, "data": data}, function(data) {
			learndash_propanel_timer(to_div, 100, data);
			if(typeof success_function == "function") {
				success_function(data);
			}

			//to_div.html(data);
		});	
	}
}
function learndash_propanel_course_selected(course) {
	var course_id = course.value;
	//console.log(course_id);
	if(parseInt(course_id) == 0) 
		jQuery("#learndash_propanel_course_progress").html('');
	else {
		learndash_propanel_ajax("course_selected", "#learndash_propanel_course_progress", {"course_id": course_id}, true, 
			function() {
				setTimeout(function() {
					learndash_propanel_course_progress_loaded();
				}, 3000);
			});
	}
}

function learndash_propanel_show_loadingbar(at) {
	var html = '<div class="learndash_propanel_loadingbar"><dd class="course_progress complete"><div class="course_progress_blue" data-percent="0" style="width: 0%;"></div><div class="loadingbar_message">' + LearnDash_Propanel.loading + '</div></dd></div>';
	jQuery(at).html(html);
	learndash_propanel_timer(at, 100);
}
var learndash_propanel_timer;
function learndash_propanel_timer(at, speed, completed_data) {
	var loadingbar = jQuery(at).find(".course_progress_blue");
	if(typeof learndash_propanel_timer != undefined) {
		clearInterval(learndash_propanel_timer);
		learndash_propanel_timer = undefined;
	}
	
	var learndash_propanel_timer = setInterval(function () {
		var percent = loadingbar.attr("data-percent");
		percent = parseInt(percent) + 5;
		loadingbar.attr("data-percent", percent);
		loadingbar.width(percent + "%");
		if(percent >= 80 && typeof completed_data == "undefined") {
			clearInterval(learndash_propanel_timer);
			learndash_propanel_timer = undefined;
		}

		if(percent >= 100) {
			setTimeout(function() {
				if(typeof completed_data != undefined) {
					jQuery(at).html(completed_data);
				}
				else
				{
					jQuery(at).html('');
				}
				clearInterval(learndash_propanel_timer);
				learndash_propanel_timer = undefined;
			}, 500);
		}
	}, speed);
}
