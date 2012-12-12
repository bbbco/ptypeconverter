<?php
/*
Plugin Name: pTypeConverter
Plugin URI: http://www.briandgoad.com/downloads/pTypeConverter
Version: 0.2.8.1
Author: Brian D. Goad
Author URI: http://www.briandgoad.com/
Description: This plugin, a complete reworking of my old plugin p2pConverter, allows you to 
			 easily convert any post type of a certain post to another in an easy to use interface. 
			 A pTypeConverter role capability prevents unwanted users from converting pages (i.e. 
			 only Administrators and Editors have this ability), which can be adjusted by using a 
			 Role Manager plugin. The user interface is located at the pTypeConverter submenu located 
			 under the Tools menu.
			 
*/

register_activation_hook(__FILE__,'pTC_install');
register_deactivation_hook(__FILE__,'pTC_uninstall');	

//Add p2p Capabilities to top two basic roles. Can be adjusted with Role Manager plugin.	
function pTC_install() {
	global $wpdb;
	add_action('admin_notices', 'pTC_show_error', 10, 2);
	if ( version_compare(get_bloginfo('version'), '3.2', '>=')) {
		$pTC_table = $wpdb->prefix . "pTC_logs";	
		add_option("pTC_log_db_version", "0.1");
		
		//Create logging table
		if($wpdb->get_var("show tables like '$pTC_table'") != $pTC_table) {
			$sql = "CREATE TABLE " . $pTC_table . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime NOT NULL,
				userid bigint(20) NOT NULL, 
				message text NOT NULL,
				priority smallint(1) NOT NULL,
				UNIQUE KEY id (id)
			);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');			dbDelta($sql);
		}
		
		//Begin logging		logMe("Begin Installation");
		$role = get_role('administrator');		$role->add_cap('pTypeConverter');
		logMe("Installed Admin Role", 2);
		$role2 = get_role('editor');
		$role2->add_cap('pTypeConverter');
		logMe("Installed Editor Role", 2);
		
		//Finished
		logMe("Finished Installation");
		
	} else if ( version_compare(get_bloginfo('version'), '3.0', '>=')) {
	
		wp_die('pTypeConverter 0.2 is not compatible with this version of Wordpress. Please either upgrade to Wordpress 3.2+, or use the previous version of pTypeConverter.');

	} else {

		wp_die('pTypeConverter is not compatible with this version of Wordpress. Please either upgrade to Wordpress 3.2+, or use my previous plugin p2pConverter.');
		
	}
	
}

//Removes p2p Capabilities from basic roles.
function pTC_uninstall() {
	global $wpdb;
	global $wp_roles;
	$pTC_table = $wpdb->prefix . "pTC_logs";
	
	logMe("Begin Uninstallation");

	// get a list of values, containing pairs of: $role_name => $display_name
	$pTC_roles = $wp_roles->get_names();
	logMe("Roles available before pType Uninstall: \n" . print_r($pTC_roles, TRUE), 3);
	
	foreach ($pTC_roles as $role) {
	
		$the_role = explode("|", $role);
		$the_role = get_role(strtolower($the_role[0]));

		if ( empty($the_role) )
			continue;
		$the_role->remove_cap(pTypeConverter) ;
		
	}

	$pTC_roles = $wp_roles->get_names();
	logMe("Roles now available (no pType): \n" . print_r($pTC_roles, TRUE), 3);
	logMe("Almost finished uninstalling ... last step: drop this table!");
	
	//Delete logging table!
	$wpdb->query("DROP TABLE IF EXISTS $pTC_table");
	
	
}

//Logging capability
function logMe($text,$prio=1) {
	global $wpdb;
	global $current_user;
	
	$pTC_table = $wpdb->prefix . "pTC_logs";
	$userid = $current_user->ID;
    $text = esc_attr($text);
    $time = date('Y-m-d H:i:s ',time());
    $wpdb->insert($pTC_table, 
		array(
			'time' => $time,
			'userid' => $userid,
			'message' => $text,
			'priority' => $prio
		),
		array(
			'%s',
			'%d',
			'%s',
			'%d'
		)
	) or do_action('admin_notices', "Cant add pTC log to db!", $sql);

    unset($sql);
    unset($userid);
    unset($text);
    unset($time);
    unset($pTC_table);
	unset($current_user);
	unset($wpdb);
}

function pTC_show_error($text, $sql) {

	echo '<div class="error"><p>' . $text . '</p><pre>' . $sql . '</pre></div>';

}

//Add menu item
if (stripos($_SERVER['REQUEST_URI'], '/wp-admin/') !== FALSE) {
	
	add_action('admin_menu', 'pTC_menu');
	
}

function pTC_menu() {

	global $current_user;
	
	//Don't show link to users without permissions
	if(current_user_can('pTypeConverter')) {
	
		logMe("User " . $current_user->display_name . " can view pTypeConverter page successfully", 3);
		$page = add_management_page('pTypeConverter', 'pTypeConverter', 'pTypeConverter', 'pTC', 'pTC_show_pTC');
		add_action('admin_head-' . $page, 'pTC_header');
		add_action('admin_print_scripts-' . $page, 'pTC_scripts');
		add_action('admin_print_styles-' . $page, 'pTC_styles');
		
		add_option('pTC_show_advanced_post_types', 'false');
		add_option('pTC_show_logging', 'false');
		
	} else {
	
		logMe("User " . $current_user->display_name . " is unable to view pTypeConverter link in admin because they lack the capability.", 0);
	
	}

}

//Functions
function pTC_scripts() {

	wp_enqueue_script('jquery');
	wp_enqueue_script('jquery-ui-core');
	wp_enqueue_script('jquery-ui-tabs');
	wp_register_script('jquery-tablesorter', plugins_url('js/jquery.tablesorter.min.js', __FILE__), array('jquery'));
	wp_enqueue_script('jquery-tablesorter');
	wp_register_script('jquery-tablesorter-widgets', plugins_url('js/jquery.tablesorter.widgets.js', __FILE__), array('jquery', 'jquery-tablesorter'));
	wp_enqueue_script('jquery-tablesorter-widgets');
	wp_register_script('jquery-ui-datepicker', plugins_url('js/jquery.ui.datepicker.js', __FILE__), array('jquery', 'jquery-ui-core'));
	wp_enqueue_script('jquery-ui-datepicker');
	
}

function pTC_styles() {

	wp_register_style('jquery-ui-smoothness', plugins_url('/css/smoothness/jquery-ui-1.8.7.custom.css', __FILE__));
	wp_enqueue_style('jquery-ui-smoothness');

}

function pTC_ajax() {
	
	global $wpdb;
	global $current_user;
	
	//Ensure Nonce Protection
	check_ajax_referer('pTC-ajax-check', 'security');

	//Don't allow bad users
	if(!current_user_can('pTypeConverter')) {
		logMe("User  " . $current_user->display_name . " attempted to execute the pTC_ajax, but was unable to do so because they lack the capability.", 0);
		wp_die(  __('You do not have sufficient permissions to access this page.') );
	}
	
	// Return logging
	if ($_POST['method'] == 'logging') { 
	
		if($_POST['data']) {
			
			$pTC_logging_level = $_POST['data'];
			
			$pTC_logs_table = $wpdb->prefix . "pTC_logs";
			$pTC_logs_query = "SELECT p.time, p.priority, u.user_login, p.message FROM " . $pTC_logs_table . " AS p, " . $wpdb->users . " AS u WHERE p.userid = u.ID AND p.priority <= " . $pTC_logging_level;
			logMe("Query: " . $pTC_logs_query, 2);
			$pTC_logs = $wpdb->get_results($pTC_logs_query, ARRAY_A);			
			if(!empty($pTC_logs)) {				$pTC_logs = json_encode($pTC_logs);				echo $pTC_logs;
			}
			
		}
	
	// Clear Logging Table
	} else if ($_POST['method'] == 'clearlogs') {
	
			$pTC_logs_table = $wpdb->prefix . "pTC_logs";
			$pTC_drop_logging_query = "TRUNCATE TABLE " . $pTC_logs_table;
			$wpdb->query($pTC_drop_logging_query);
			
			echo json_encode(array('message' => 'Logging cleared! Now go generate some new logs!'));
	
	// Return post types
	} else if ($_POST['method'] == 'showtypes') {
		
		if (get_option('pTC_show_advanced_post_types') == "false") {
			$pTC_types = get_post_types(array('public' => true));
		} else {
			$pTC_types = get_post_types();
		}
		foreach($pTC_types as $pTC_type) {
			$pTC_type_array[] = array('ID' => $pTC_type, 'value' => $pTC_type);
		}
		logMe("Possible PostTypes: \n" . print_r($pTC_type_array, TRUE), 3);
		echo json_encode($pTC_type_array);

		
	// Return authors
	} else if ($_POST['method'] == 'showauthors') {
		
		$pTC_users_args = array( 'who' => 'authors', 'orderby' => 'nicename', 'fields' => array ( 'ID', 'user_nicename' ) );
		$pTC_users = get_users($pTC_users_args);
		logMe("Possible Users: \n" . print_r($pTC_users, TRUE), 3);
		foreach($pTC_users as $pTC_user) {
			$pTC_user_array[] = array('ID' => $pTC_user->ID, 'value' => $pTC_user->user_nicename);
		}
		echo json_encode($pTC_user_array);
		
	// Return post listings	
	} else if ($_POST['method'] == 'showposts') {
	
		if ($_POST['data']) {
		
			parse_str($_POST['data']);
			$pTC_query_addition = "";
			
			if($pTC_filter_title != "") {
				$pTC_filter_title = esc_attr($pTC_filter_title);
				$pTC_query_addition .= " AND p.post_title LIKE '%%" . $pTC_filter_title . "%%'";
			} 
			
			if($pTC_filter_author != "") {
				$pTC_query_addition .= " AND u.ID = '" . esc_attr($pTC_filter_author) . "'";	
			} 
			
			if ($pTC_filter_start_date != "") {
				$pTC_filter_start_date = date_format(date_create(esc_attr($pTC_filter_start_date)), 'Y-m-d');
				if ($pTC_filter_start_date){
					$pTC_query_addition .= " AND p.post_date >= '" . $pTC_filter_start_date . "'";
				}
			} 
			
			if ($pTC_filter_end_date != "") {
				$pTC_filter_end_date = date_format(date_create(esc_attr($pTC_filter_end_date)), 'Y-m-d');
				if ($pTC_filter_end_date){
					$pTC_query_addition .= " AND p.post_date <= '" . $pTC_filter_end_date . "'";
				}
			} 
			
			if ($pTC_filter_type != "") {
			
				$pTC_query_addition .= " AND p.post_type = '" . esc_attr($pTC_filter_type) . "'";
			
			} else {
				
				if (get_option('pTC_show_advanced_post_types') == "false") {
					$pTC_types = get_post_types(array('public' => true));
				} else {
					$pTC_types = get_post_types();
				}
				foreach($pTC_types as $pTC_type) {
					$pTC_type_query .= " OR p.post_type = '" . $pTC_type . "'";
				}
				$pTC_query_addition .= " AND (p.post_type = '' " . $pTC_type_query . ")";			
			}
			
			if ($pTC_id != "") {
				foreach($pTC_id as $id) {
					$pTC_query_addition .= " OR p.id = '" . esc_attr($id) . "'";
				}
			}

			if ($pTC_filter_limit != "ALL") {
					$pTC_query_limit .= " LIMIT " . esc_attr($pTC_filter_limit);
			}
				
		} else {
		
			$pTC_query_addition = " AND 1 = 1";
			
		}
		
		$pTC_posts_query = "SELECT p.id, p.post_title, u.user_nicename, p.post_date, p.post_type FROM " . $wpdb->posts . " AS p, " . $wpdb->users . " AS u WHERE p.post_author = u.ID" . $pTC_query_addition . " ORDER BY p.post_date ASC" . $pTC_query_limit;
		logMe("Query: " . esc_attr($pTC_posts_query), 2);
		$pTC_posts = $wpdb->get_results($pTC_posts_query, ARRAY_A);
		logMe("Post Dump: \n " . print_r($pTC_posts, TRUE), 3);
		
		if ($pTC_posts) {
			echo json_encode($pTC_posts);
		} else {
			echo json_encode(array('message' => 'No matching posts found!'));
		}
		
	
	// Run convert sequence
	} else if ($_POST['method'] == 'convertposts') {
	
		if(@$_POST['data']) {
		
			global $wp_rewrite;
			
			$pTC_ids = @$_POST['data'];
			$pTC_type = attribute_escape(array_pop($pTC_ids));
			if($pTC_type) { 

				logMe("Prepare for post and type dump: \n Convert to Post Type:" . $pTC_type . "\n Post IDs: " . print_r($pTC_ids, TRUE), 2);
				
				$pTCquery = "UPDATE " . $wpdb->posts . " SET post_type = '" . $pTC_type . "' WHERE ";
				$results = array();
				
				if(is_array($pTC_ids) ) {
				
					foreach ($pTC_ids as $pTC_id) {
				
						$pTC_type_check = $wpdb->get_row("SELECT post_title,post_type FROM " . $wpdb->posts . " WHERE id=" . $pTC_id . "");
						logMe("PostId: " . $pTC_id . "<br />Original PostType: " . print_r($pTC_type_check->post_type . "<br/>Converting PostType: " . $pTC_type, TRUE));
						
						if ($pTC_type_check->post_type != $pTC_type) {
						
							$pTCqueryone = $pTCquery . "id=" . $pTC_id;
							
						} else {
						
							logMe("Conversion to " . $pTC_type . " failed because it is already a " . $pTC_type, 0);
							array_push($results, array('pTC_id' => $pTC_id, 'result' => 'failed', 'message' => 'The selected item is already a ' . $pTC_type)); 
							continue;
							
						}
						
						logMe("Query: " . $pTCqueryone, 2);
						$queryresult = $wpdb->query($pTCqueryone);
						$queryerror = $wpdb->print_error();
						logMe("Errors: " . $queryerror, 0);
						
						if ($queryresult) {
						
							logMe("Conversion to " . $pTC_type . " succeeded", 0);
							$pTC_type_check = $wpdb->get_row("SELECT post_title,post_type FROM " . $wpdb->posts . " WHERE id=" . $pTC_id . "");
							logMe("PostId: " . $pTC_id . "		Confirmed PostType after conversion: " . print_r($pTC_type_check->post_type, TRUE), 1);
							array_push($results, array('pTC_id' => $pTC_id, 'result' => 'succeeded', 'pTC_type' => $pTC_type_check->post_type, 'message' => 'Success!'));
							
						} else if ($queryerror) {
						
							logMe("Conversion to " . $pTC_type . " did not suceed because of SQL Error: " . $queryerror, 0);					
							array_push($results, array('pTC_id' => $pTC_id, 'result' => 'failed', 'message' => 'SQL Error: ' . $queryerror)); 						
							
						} else {

							logMe("Conversion to " . $pTC_type . " did not suceed because of unknown error.", 0);					
							array_push($results, array('pTC_id' => $pTC_id, 'result' => 'failed', 'message' => 'Unknown Error!')); 						
						
						}
				
					}

								
				}
								//Important! Rewrites permalinks for post/page files 
				$wp_rewrite->flush_rules();
								
				echo json_encode($results);
				
			} else {
				
				echo json_encode(array('result' => 'failed', 'message' => 'No post type selected! Please select a post type to convert to and try again.'));
					
			}	
		}
	} else if ($_POST['method'] == 'pTC_advanced_posts') {
	
		if($_POST['data']) {
			update_option('pTC_show_advanced_post_types', $_POST['data']);
			$message = "Advanced Post Types option saved to " . get_option('pTC_show_advanced_post_types');
		} else {
			$message = "Unknown error trying to save Advanced Post Type option.";
		}
		
		echo json_encode(array('message' => $message));
		
	} else if ($_POST['method'] == 'pTC_show_logging') {
	
		if($_POST['data']) {
			update_option('pTC_show_logging', $_POST['data']);
			$message = "Show Logging option saved to " . $_POST['data'];
		} else {
			$message = "Unknown error trying to save Show Logging option.";
		}
		
		echo json_encode(array('message' => $message));
		
	} else {
	
		//Unknown, and bad request
		logMe("Bad Request!", 0);
		wp_die( __('Bad Request!') ); 
	
	}
	
	die(); //this is required to return a proper result
	exit;
	
}
add_action('wp_ajax_pTC_ajax', 'pTC_ajax');

function pTC_header() {

	$pTC_ajax_nonce = wp_create_nonce("pTC-ajax-check");

?>

<script type="text/javascript"> 
	<!--
	
	jQuery(document).ready(function($){		

		$('#pTC_tabs').tabs();

		$('#pTC_checkall').click(function() {
			$("#pTC_table_posts input[type='checkbox']").attr('checked', $(this).is(':checked'));
		});
	

		$('#pTC_logging_level').change(function() {
			loadlogging(this.value);
			return false;
		});
		
		$('.pTC_filter').change(function() { 
			loadposts($('.pTC_filter').serialize());
			return false;
		});
			
		$('#pTC_filter_reset').click(function() {
			$('.pTC_filter').val('');
			loadposts($('.pTC_filter').serialize());
			return false;
		});
			
		$('#pTC_logging_clear').click(function() {
			var clear = confirm("Are you sure you want to clear all pTypeConverter Logging? This will remove all historical data, and you will start over from scratch.");
			if (clear) {
				postajax("clearlogs", "", clearlogs_confirmation, "#pTC_table_logging tbody");
			}
			return false;
		});

		$('#pTC_convert_button').click(function() {
			var post_type = $("#pTC_convert_type").val();
			var cbox_id = "";
			var post_title = "";
			var convert_posts = [];
			$(".pTC_cbox:checked").each(function(){
				convert_posts.push($(this).val());
				post_title += "\t- " + $(this).parent().next().text() + "\n";
			});
			
			var convert = confirm("Are you sure you want to convert these items: \n" + post_title + "Into a " + post_type + "?");
			if(convert) {
				convert_posts.push(post_type);
				postajax("convertposts", convert_posts, showConvertedPosts, "#pTC_table_posts tbody");
			}
			
			return false;
		
		});
		
					
		// Initiate tablesorter on the posts table only
		$('#pTC_table_posts').tablesorter( {widgets: ["zebra", "uitheme"], widgetZebra: { css: [ "ui-widget-content", "ui-state-default" ] }, widgetUitheme: { css: ["ui-icon-arrowthick-2-n-s", "ui-icon-arrowthick-1-s", "ui-icon-arrowthick-1-n"] }, sortList: [[3, 0]]} );
		
		// Initiate both datepickers 
		$('.pTC_date').datepicker({
			beforeShow: setRange,
			dateFormat: 'yy-mm-dd', 
			changeMonth: true, 
			changeYear: true, 
		});

		$("a[href='#convert']").click(function() {	
			$("#pTC_table_posts").trigger("update");
		});

		function loadeverything() {			
			loadtypes();			
			loadauthors();
			toggleLogging();
			loadlogging($('#pTC_logging_level').val());			
			loadposts($('.pTC_filter').serialize());
		}
		loadeverything();

		//Function that controls the available dates on the date pickers
		function setRange(input) {
			var dateMin = null, dateMax = new Date;
			
			if (input.id === "pTC_filter_start_date") {
				if ($("#pTC_filter_end_date").datepicker("getDate") != null){
					dateMax = $("#pTC_filter_end_date").datepicker("getDate");
				} 
			} else if (input.id === "pTC_filter_end_date") {
				if ($("#pTC_filter_start_date").datepicker("getDate") != null){
					dateMin = $("#pTC_filter_start_date").datepicker("getDate");
				} 
			}
			return { minDate: dateMin, maxDate: dateMax }
		}

		// jQuery datepicker CSS hack to allow datepicker to display properly
		$('#ui-datepicker-div').css('clip', 'auto');
		
		// Function to send POST via ajax
		function postajax(method, values, callback, object) {				
			$.post(ajaxurl, {action: 'pTC_ajax', security: '<?php echo $pTC_ajax_nonce; ?>', method: method, data: values}, function(data, status, xhr) {
				 callback(data, object);
			}, "json")
			.fail(function(e, xhr, settings, extn){
				alert('The ajax request failed:' + extn);
			});
		}
		function clearlogs_confirmation(data, object) {
		
			$(object).empty();
			alert(data.message);
		
		}
		function appendRows(json, table) {
			var html = "";			
			if(!json) {
			
				html += '<tr class="info"><td colspan=5>Nothing to display!</td></tr>\n';
			
			} else if(json.message) {
				html += '<tr class="info"><td colspan=5>' + json.message + '</td></tr>\n';

			} else {				
				$.each(json, function(i,row) {
					html += "<tr id=\"pTC_" + row.id + "\">";
					$.each(row, function(j,cell) {
						if(row.id == cell) {
							html += "<td><input class=\"pTC_cbox\" type=\"checkbox\" name=\"pTC_id[]\" value=\"" + cell + "\"/></td>";
						} else {
							html += "<td>" + cell + "</td>";
						}
					});
					html += "</tr>\n";
				});
			}			
			$(table).empty().append(html);
			//console.log("appended");
		}
		
		function rebindPostsTable() {
			//console.log("rebinding posts table");
			$("#pTC_table_posts").trigger("update"); 
			
			$('#pTC_table_posts tr').hover(function(){
				$(this).addClass('ui-state-hover');
			}, function(){ 
				$(this).removeClass('ui-state-hover');
			});
			
			$('table#pTC_table_posts tr').click(function(event){
				$(this).toggleClass('ui-state-highlight');
				if (event.target.type !== 'checkbox') {
					$(':checkbox', this).attr('checked', function() {
						return !this.checked;
					});
				}
			});

		}
		
		function appendOptions(json, element) {
			var html = "";
			$.each(json, function(i, select){
				html += "<option value='" + select.ID + "'>" + select.value + "</option>\n";
			});
			$(element).empty().append(html);
		}
		
		function showConvertedPosts(json, element) {

			$("#pTC_table_posts span.message").remove();
			$("#pTC_table_posts tr").removeClass("ui-state-error").removeClass("ui-state-highlight");
			if(json.result == "failed") {
				alert(json.message);
			} else {
								$.each(json, function(i, item) { 
					if(item.result == "succeeded") {
						$("#pTC_" + item.pTC_id).addClass("ui-state-highlight");
						$("#pTC_" + item.pTC_id + " td:last").html(item.pTC_type + "<span class=\"success message\">" + item.message + "</span>");
					} else {
						$("#pTC_" + item.pTC_id).addClass("ui-state-error");
						$("#pTC_" + item.pTC_id + " td:last").append("<span class=\"error message\">" + item.message + "</span>");
					}
				});

			}
			$("#pTC_table_posts input[type='checkbox']").attr("checked", false);
		
		}
		
		function loadlogging(logging_level) {
			if ($('#pTC_show_logging').val() == "true") {
				$("#pTC_table_logging tbody").empty().append("<tr><td colspan=3><h3>Loading ...</h3></td></tr>");
				postajax("logging", logging_level, appendRows, "#pTC_table_logging tbody");
			}
		}
		
		function loadposts(filter) {
			$("#pTC_table_posts tbody").empty().append("<tr><td colspan=3><h3>Loading ...</h3></td></tr>");
			postajax("showposts", filter, updateposts, '#pTC_table_posts tbody');
		}
		
		function updateposts(data, table) {
			//console.log("appending");
			appendRows(data, table);
			//console.log($(table).parent());
			rebindPostsTable();

		}
		
		function loadtypes(){
			postajax("showtypes", "", appendOptions, ".pTC_types");
		}
		
		function loadauthors(){
			postajax("showauthors", "", appendOptions, "#pTC_filter_author");
		}
		
		$("#pTC_advanced_posts").change(function(){		
			postajax("pTC_advanced_posts", $(this).val(), showAlert, loadeverything);
		});

		$("#pTC_show_logging").change(function(){		
			postajax("pTC_show_logging", $(this).val(), showAlert, toggleLogging);
		});
		
		function toggleLogging(){
			if ($('#pTC_show_logging').val() == "true") {
				$('div#pTC_tabs.ui-tabs ul.ui-tabs-nav li.ui-state-default:contains("Logging")').show();
			} else {
				$('div#pTC_tabs.ui-tabs ul.ui-tabs-nav li.ui-state-default:contains("Logging")').hide();
			}
		}
		
		function showAlert(data, callback) {
			alert(data.message);
			callback();
		}
		
	});
	
	-->
</script>

<style type="text/css">
.pTC_filter, .pTC_filter li {
	display: inline;
}
.pTC_date {
	width: 90px;
}
.fLeft {
	float: left;
}
.fRight {
	float: right;
}

span.error {
    border-radius: 3px 3px 3px 3px;
    border-style: solid;
    border-width: 1px;
	background-color: #FFEBE8;
    border-color: #CC0000;
	padding: 0 0.6em;
	margin: 0px 5px;
}

span.success {
    border-radius: 3px 3px 3px 3px;
    border-style: solid;
    border-width: 1px;
	background-color: #FBF9EE;
    border-color: #1155AA;
	padding: 0 0.6em;
	margin: 0px 5px;
}
	
/* jQuery UI Theme required css; as seen in css/ui/style.css file */ 
table.tablesorter { 
  font-family: arial; 
  margin: 10px 0pt 15px; 
  font-size: 8pt; 
  width: 100%; 
  text-align: left; 
  padding: 5px; 
} 
table.tablesorter thead tr th, table.tablesorter tfoot tr th { 
  border-collapse: collapse; 
  font-size: 8pt; 
  padding: 4px; 
} 
table.tablesorter thead tr .header { 
  background-repeat: no-repeat; 
  background-position: center right; 
  cursor: pointer; 
} 
table.tablesorter tbody td { 
  padding: 4px; 
  vertical-align: top; 
} 
table.tablesorter .header .ui-icon { 
  display: block; 
  float: right; 
} 
 
/* This allows you to use ui-state-default as the zebra stripe color */ 
table.tablesorter tr.ui-state-default { 
  background-image: url; 
} 
/* UI hover and active states make the font normal and the table resizes, this fixes it */ 
table.tablesorter th.header { 
  font-weight: bold; 
} 

</style>
<?php
}

//Menu page
function pTC_show_pTC() {

	//Don't allow bad users
	if(!current_user_can('pTypeConverter')) {
		logMe("User attempted to view the pTypeConverter page, but was unable to do so because they lack the capability.", 0);
		wp_die(  __('You do not have sufficient permissions to access this page.') );
	}
	
	
	?>
	
<br /><br />
<div id="pTC_tabs">
	<ul>
		<li><a href="#welcome">Welcome</a></li>
		<li><a href="#convert">Convert</a></li>
		<li><a href="#options">Options</a></li>
		<li><a href="#logging">Logging</a></li>
	</ul>

	<div id="welcome">

		<div class="wrap">
			<h1>pTypeConverter</h1>
			<p>Thanks for using pTypeConverter, a Wordpress plugin designed to help you convert posts to pages, or any other custom post types available, and vice versa.</p>
			<p>If you feel like this plugin has helped you, please consider making a donation:
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="donate">
				<input type="hidden" name="amount" value="">
				<input type="hidden" name="cmd" value="_xclick">
				<input type="hidden" name="business" value="bdgoad@gmail.com">
				<input type="hidden" name="item_name" value="brian d goad - Donation">
				<input type="hidden" name="no_shipping" value="1">
				<input type="hidden" name="return" value="http://www.briandgoad.com">
				<input type="hidden" name="cancel_return" value="http://www.briandgoad.com">
				<input type="hidden" name="currency_code" value="USD">
				<input type="hidden" name="page_style" value="">
				<input type="hidden" name="tax" value="0">
				<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" style="border: 0pt none ;" name="submit" alt="PayPal - The safer, easier way to pay online">
			</form></p>

		<hr />

		<h2>Instructions</h2>
		<h6>Please be advised that all use of pTypeConverter, of any version, is at the users risk. The developer of this plugin accepts no responsibility for the results of any actions taken by the use of pTypeConverter. It is a tool, and its misuse could damage your website severely. Be careful, and think before you convert!</h6>
		<p>If you are reading this page, then you already have complete access to the plugin. Users with Administrator and Editor roles have the ability to use this plugin, but this capability can be further restricted or adjusted using a Roles and Capabilities plugin.</p>
		<p>To get started, click the Convert tab.<p>
		<p>Here, you will see a screen with all of your posts listed. You can use the filter controls to search for and filter the posts that are displayed.</p>
		<p>Click the checkboxes next to the posts that you want to convert.</p>
		<p>Next, select from the drop-down which post type you want to convert to.</p>
		<p>Clicking the convert button will ask you to confirm the conversion.</p>
		<p>You should see a success or error message for each post that is converted.</p>
		<p>Now, use the Wordpress interface to confirm that the post(s) show up in the expected places.<p>

		<hr />
		<h2>Frequently Asked Questions</h2>
		<p><b>When I convert a page to a post, does it show up under the Posts section, or does it still remain in the Pages section?</b></p>
		<p>The Page becomes a Post, and will show up in the Posts section along with your other posts.</p>
		<br />
		<p><b>When I convert a post to a page, does all of the extra meta-data get converted as well?</b></p>
		<p>In a short answer, Yes.</p>
		<p>In a long answer, Yes, because posts and pages, etc are all considered the same thing in	Wordpress (i.e., they all are just records within a table). The main difference between them is that they have a field that determines what type of element they are. So any element that had post qualities, when 	turned into a page will have the same content, but now have the page qualities and ignore whatever fields apply to the post type. If the same page that was a post is converted back to a post, you will find that the same post qualities will still exist, even though they were not evident while it was a page.</p>
		<br />
		<p><b>Does this mean that if I convert a post with a bunch of comments that the comments will still be available on the page or other post type?</b></p>
		<p>Yes, all comments are stored in the database and will be kept in tact. You may have to check your default settings for displaying comments on the new post type, and also check to ensure that your theme supports showing comments on pages/post types.</p>
		<br />
		<p><b>I converted a page to a post, but the comments section is not displaying on the full post. What happened? What can I do to fix this?</b></p>
		<p>WordPress automatically defaults comments on pages to be turned off. When you convert the page to a post, the configurations remain the same, so the comments setting is turned off, even though it is now a post. Future versions of this plugin will probably incorporate controls to determine if you want comments enabled or disabled.</p>
		<p>To enable comments on the new post, please follow these steps:</p>
		<ul>
			<li>- Click to Posts > Posts</li>
			<li>- Scroll to the post that you want to enable comments on</li>
			<li>- Click the Edit link for that post</li>
			<li>- On the Edit Post section, in the upper right-hand section, click the yellow Screen Options button. A large dropdown section should appear</li>
			<li>- Click the checkbox for Discussion</li>
			<li>- Now look underneath your main body text editor (the large text area where you type in all of your article information). You should see checkbox options in regards to comments.</li>
			<li>- Click the checkbox to Allow comments</li>
			<li>- Click the Save button</li>
			<li>- Now visit your page, and comments should be enabled.</li>
		</ul>
		<br/>
		<p><b>I converted a post with a bunch of comments to a page, and now all of my comments are missing! What gives?</b></p>
		<p>Please follow the steps above to ensure that comments are enabled.</p>
		<p>Next, please ensure that the comments section is setup to display on your page template. You should look in the theme editor for the page_template.php file, and ensure this line is listed after The Loop: <pre><?php echo htmlentities('<?php comments_template(\'\', true); ?>'); ?></pre></p>
		<br />
		<p><b>When looking to find a post to convert, I see a bunch of extra listings called Revisions. Should I convert them as well?</b></p>
		<p>If you see these listings, you have the extra post types enabled, which is found in the Options tab. When working with these post types, please be careful as the extra post types are not meant to be publically accessible. This feature is made available for advanced users who might find it helpful to have this feature available for a number of reasons. One such example is if you have a post that was written over a span of time, and you need the ability to look at previous revisions to merge with the current one. You can convert past revisions to posts, leaving them unpublished, and have them available for reference.</p> 
		<br />
		<p><b>I have some posts that I would like to convert to pages, but since posts have categories and pages dont, wouldnt the permalinks break and any pages I have indexed in the search engines be broken?</b></p>
		<p>No. WordPress is smart about the way they create their pretty URLs. The pages and posts permalinks are retained when converting. As well as categories and such.</p>
		<br />
		<p><b>What about the URL? My users will be lost if the bookmarked the old one after I convert!</b></p>
		<p>No worries! The plugin rewrites the permalinks after conversion so that your old URL will automagically forward the user to the new URL. Wordpress takes care of most of the magic behind the scenes. If you are really concerned about URLs because of SEO, you can use a plugin like Platinum SEO to configure things more to your liking.</p>
		<br />
		<p><b>Will converting posts to pages affect my Menu structure?</b></p>
		<p>No, this should not affect your menu structure, unless you have it setup someway to automatically add new Pages to your menu.</p>

		<hr />
		<p>Have a question not listed? Feel free to <a href="http://www.briandgoad.com/downloads/ptypeconverter/">leave feedback</a>.</p>
		<p>Has this plugin helped you? Please consider donating to the developer:</p>
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" class="donate">
	        <input type="hidden" name="amount" value="">
	        <input type="hidden" name="cmd" value="_xclick">
	        <input type="hidden" name="business" value="bdgoad@gmail.com">
	        <input type="hidden" name="item_name" value="brian d goad - Donation">
	        <input type="hidden" name="no_shipping" value="1">
	        <input type="hidden" name="return" value="http://www.briandgoad.com">
	        <input type="hidden" name="cancel_return" value="http://www.briandgoad.com">
	        <input type="hidden" name="currency_code" value="USD">
	        <input type="hidden" name="page_style" value="">
	        <input type="hidden" name="tax" value="0">
	        <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" style="border: 0pt none ;" name="submit" alt="PayPal - The safer, easier way to pay online">
        </form>

   	  </div>

	</div>

	<div id="convert">
	
		<form id="pTC_form">
		<?php
			if ( function_exists('wp_nonce_field') ) {
				wp_nonce_field('pTC-convert_posts');
			}
		?>
			<div class="wrap">
				<h2>pTypeConverter</h2>
				<div class="fLeft">
					<h3>Filter By ...</h3>
						<ul class="pTC_filter">
							<li>Title: <input type="textbox" class="pTC_filter" id="pTC_filter_title" name="pTC_filter_title"></li> 
							<li>Author: <select class="pTC_filter" id="pTC_filter_author" name="pTC_filter_author"></select></li>
							<li>Earliest Date: <input type="text" class="pTC_date pTC_filter" id="pTC_filter_start_date" name="pTC_filter_start_date"></li>
							<li>Latest Date: <input type="text" class="pTC_date pTC_filter" id="pTC_filter_end_date" name="pTC_filter_end_date"></li>
							<li>Type: <select class="pTC_types pTC_filter" id="pTC_filter_type" name="pTC_filter_type"></select></li>
							<li>Limit: <select class="pTC_limit pTC_filter" id="pTC_filter_limit" name="pTC_filter_limit">
									<option>10</option>
									<option>25</option>
									<option selected="selected">50</option>
									<option>100</option>
									<option>ALL</option>
								</select>
							<li><input type="button" id="pTC_filter_reset" value="Reset">
						</ul>
				</div>
				<div class="fRight">
					<h3>Convert...</h3>
					<p>Selected items to:
					<select class="pTC_types" name="pTC_convert_type" id="pTC_convert_type">
					</select>
					<input id="pTC_convert_button" type="button" value="Convert!"/>
					</p>
				</div>
			</div>
			<div class="wrap">
				<table id="pTC_table_posts" class="widefat post fixed pTC_table tablesorter" cellspacing="0">
					<thead>
						<tr>
							<th class="sorter-false" width=30px><input type="checkbox" name="checkall" id="pTC_checkall"/></th>
							<th><?php _e('Title'); ?></th>
							<th><?php _e('Author'); ?></th>
							<th><?php _e('Date'); ?></th>
							<th><?php _e('Type'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
						</tr>
					</tbody>
				</table>
			</div>
		</form>
	</div>

	<div id="options">
		<div class="wrap">
			<h2>Options</h2>
			<ul>
				<li>
					<h4>Use Advanced Post Types</h4>
					<select id="pTC_advanced_posts" class="pTC_options">
						<option value="false" <?php echo (get_option('pTC_show_advanced_post_types') == "false") ? 'selected="selected"' : ''; ?>>False</option>
						<option value="true" <?php echo (get_option('pTC_show_advanced_post_types') == "true") ? 'selected="selected"' : ''; ?>>True</option>
					</select>
					<p>Enable this setting if you would like to enabled advanced post types that normally wouldn't be shown by default (i.e. are considered non-public, like "revision", or "nav_menu_item" types).</p>
				</li>
				<li>
					<h4>Show Logging</h4>
					<select id="pTC_show_logging" class="pTC_options">
						<option value="false" <?php echo (get_option('pTC_show_logging') == "false") ? 'selected="selected"' : ''; ?>>False</option>
						<option value="true" <?php echo (get_option('pTC_show_logging') == "true") ? 'selected="selected"' : ''; ?>>True</option>
					</select>
					<p>Enable this setting to display the Logging tab. Useful for debugging purposes.</p>
				</li>
			</ul>
		</div>
	</div>

	<div id="logging">
		<div class="wrap">
			<h2>Logging</h2>
			<div class="fLeft"><p>Show Logging Level:  <select id="pTC_logging_level"><option value="0">WARNING</option><option value="1" selected>INFO</option><option value="2">DEBUG</option><option value="3">MAXDEBUG</option></select></p></div>
			<div class="fRight"><input id="pTC_logging_clear" type="button" value="Clear Logging"></div>
			<table id="pTC_table_logging" class="widefat pTC_table" cellspacing="0">
				<thead>
					<tr>
						<th><?php _e('Timestamp'); ?></th>
						<th><?php _e('Level'); ?></th>
						<th><?php _e('User'); ?></th>
						<th><?php _e('Message'); ?></th>
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
		</div>
	</div>

</div>
<?php
	
	
}
?>
