<?php
/*
Plugin Name: G3 Schedules 
Description: Create schedule items that can be shown via shortcodes.
Version: 0.1.0
Author: Andrew Montgomery-Hurrell
Author URI: http://darkliquid.co.uk
*/

define( 'G3_SCHEDULES_PATH', WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)) );

// init the plugin and post types it provides
function g3_schedules_init() {

	$labels = array(
		'name' => _x('G3 Schedules', 'post type general name'),
		'singular_name' => _x('G3 Schedule', 'post type singular name'),
		'add_new' => _x('Add New', 'schedule'),
		'add_new_item' => __('Add New Schedule'),
		'edit_item' => __('Edit Schedule'),
		'new_item' => __('New Schedule'),
		'view_item' => __('View Schedule'),
		'search_items' => __('Search Schedule'),
		'not_found' =>  __('No schedules found'),
		'not_found_in_trash' => __('No schedules found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'G3 Schedules'
	);

	register_post_type('schedules', array(
		'publicly_queryable' => true,
		'show_in_nav_menus' => false,
		'show_ui' => true,
		'exclude_from_search' => true,
		'taxonomies' => array(),
		'description' => 'G3 Schedules',
		'label' => 'G3 Schedules',
		'labels' => $labels,
		'supports' => array('title', 'page-attributes')
	));


};
add_action('init', 'g3_schedules_init');

function g3_schedules_styles() {
	wp_register_style( 'g3-schedule-styles', plugin_dir_url(__FILE__) . 'styles.css' );
	wp_enqueue_style('g3-schedule-styles');
}
add_action( 'wp_print_styles', 'g3_schedules_styles' );

// init the admin area for schedules
function g3_schedules_admin_init() {
	add_meta_box(
    		'g3_schedules_id', 
	    	'G3 Schedules options', 
    		'g3_schedules_meta_callback', 
	    	'schedules', 
	    	'normal'
	);
}
add_action( 'admin_init', 'g3_schedules_admin_init');

// make forms support uploading
function g3_schedules_enable_post_multipart_encoding() {
	echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'g3_schedules_enable_post_multipart_encoding');

// our magical meta box
function g3_schedules_meta_callback($post) {
	// Use nonce for verification
	wp_nonce_field( plugin_basename(__FILE__), 'g3_schedules_nonce' );

	$meta = get_post_custom($post->ID);
	
	// link
	echo '<p><label for="g3_schedules_link">Schedule Link</label> ';
  	echo '<input type="text" size="64" id="g3_schedules_link" name="g3_schedules_link" value="'.esc_attr($meta['g3_schedules_link'][0]).'" /></p>';	

	// uploader
	echo '<p><label for="g3_schedules_image">Schedule Image <small>(Please upload image that are 475x105)</small></label><br/>';
	$img_id = get_post_meta($post->ID, 'g3_schedules_image_id', true);
	if(is_numeric($img_id)) {
		$img = wp_get_attachment_image_src($img_id, 'schedules-banner');
		if($img) {
			echo '<br /><img src="'.$img[0].'" /><input id="g3_schedules_image_delete" name="g3_schedules_image_delete" type="checkbox" /><label for="g3_schedules_image_delete">Don\'t use an image</label><br />';
		} else {
			echo '<br /><img src="/images/nophoto.png" /><br />';
		}
	} else {
		echo '<br /><img src="/images/nophoto.png" /><br />';
	}
	echo '<input id="g3_schedules_image" name="g3_schedules_image" type="file" /> '.get_post_meta($post->ID, 'g3_schedules_image_status', true).'</p>';

	// day
	echo '<p><label for="g3_schedules_day">Schedule Day</label> ';
	echo '<select id="g3_schedules_day" name="g3_schedules_day">';
	$cur_day = get_post_meta($post->ID, 'g3_schedules_day', true);
	$days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
	foreach($days as $day) {
		echo '<option value="'.$day.'"'.($cur_day == $day ? ' selected="selected"' : '').'>'.$day.'</option>';
	}
	echo '</select></p>';

	// start
	echo '<p><label for="g3_schedules_start">Schedule Start</label> ';
	echo '<select id="g3_schedules_start" name="g3_schedules_start">';
	$cur_start = get_post_meta($post->ID, 'g3_schedules_start', true);
	for($i = 0; $i < 24; $i++) {	
		echo '<option value="'.$i.'"'.(intval($cur_start) == $i ? ' selected="selected"' : '').'>'.sprintf('%02d',$i).':00</option>';
	}
	echo '</select></p>';

	// end 
	echo '<p><label for="g3_schedules_end">Schedule End</label> ';
	echo '<select id="g3_schedules_end" name="g3_schedules_end">';
	$cur_end = get_post_meta($post->ID, 'g3_schedules_end', true);
	for($i = 0; $i < 24; $i++) {
		echo '<option value="'.$i.'"'.(intval($cur_end) == $i ? ' selected="selected"' : '').'>'.sprintf('%02d',$i).':00</option>';
	}
	echo '</select></p>';

}

// actually save this junk
function g3_schedules_save_postdata( $post_id ) {

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times

	if ( !wp_verify_nonce( $_POST['g3_schedules_nonce'], plugin_basename(__FILE__) )) {
		return $post_id;
	}

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
		return $post_id;
	}

  
	// Check permissions
	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return $post_id;
	}

	// OK, we're authenticated: we need to find and save the data

	// do we have an upload?
	if(isset($_FILES['g3_schedules_image']) && ($_FILES['g3_schedules_image']['size'] > 0)) {
		$arr_file_type = wp_check_filetype(basename($_FILES['g3_schedules_image']['name']));
		$uploaded_file_type = $arr_file_type['type'];

		// Set an array containing a list of acceptable formats
		$allowed_file_types = array('image/jpg','image/jpeg','image/gif','image/png');

		// If the uploaded file is the right format
		if(in_array($uploaded_file_type, $allowed_file_types)) {

			// Options array for the wp_handle_upload function. 'test_upload' => false
			$upload_overrides = array( 'test_form' => false ); 

			// Handle the upload using WP's wp_handle_upload function. Takes the posted file and an options array
			$uploaded_file = wp_handle_upload($_FILES['g3_schedules_image'], $upload_overrides);

			// If the wp_handle_upload call returned a local path for the image
			if(isset($uploaded_file['file'])) {

				// The wp_insert_attachment function needs the literal system path, which was passed back from wp_handle_upload
				$file_name_and_location = $uploaded_file['file'];

				// Generate a title for the image that'll be used in the media library
				$file_title_for_media_library = get_the_title($post_id) . '(' . basename($file_name_and_location) . ')';

				// Set up options array to add this file as an attachment
				$attachment = array(
					'post_mime_type' => $uploaded_file_type,
					'post_title' => 'G3 Schedule Image: ' . addslashes($file_title_for_media_library),
					'post_content' => '',
					'post_status' => 'inherit'
				);

				// Run the wp_insert_attachment function. This adds the file to the media library and generates the thumbnails. 
				// If you wanted to attch this image to a post, you could pass the post id as a third param and it'd magically happen.
				$attach_id = wp_insert_attachment( $attachment, $file_name_and_location );
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				$attach_data = wp_generate_attachment_metadata( $attach_id, $file_name_and_location );
				wp_update_attachment_metadata($attach_id,  $attach_data);

				// Before we update the post meta, trash any previously uploaded image for this post.
				$existing_uploaded_image = (int) get_post_meta($post_id,'g3_schedules_image_id', true);
				if(is_numeric($existing_uploaded_image)) {
					wp_delete_attachment($existing_uploaded_image);
				}

				// Now, update the post meta to associate the new image with the post
				update_post_meta($post_id,'g3_schedules_image_id',$attach_id);

				// Set the feedback flag to false, since the upload was successful
				$upload_feedback = false;


			} else { // wp_handle_upload returned some kind of error. the return does contain error details, so you can use it here if you want.

				$upload_feedback = 'There was a problem with your upload.';
				update_post_meta($post_id,'g3_schedules_image_id',$attach_id);

			}

		} else { // wrong file type

			$upload_feedback = 'Please upload only image files (jpg, gif or png).';
			update_post_meta($post_id,'g3_schedules_image_id',$attach_id);

		}

	}

	$data = array();
	if($attachid) {
		$data['g3_schedules_image_id'] = $attach_id;
	}
	if($_POST['g3_schedules_image_delete']) {
		delete_post_meta($post_id,'g3_schedules_image_id');
	}
	$data['g3_schedules_image_status'] = $upload_feedback;
	update_post_meta($post_id,'g3_schedules_image_status',$upload_feedback);
	$data['g3_schedules_link'] = $_POST['g3_schedules_link'];
	update_post_meta($post_id, 'g3_schedules_link', $data['g3_schedules_link']);
	$data['g3_schedules_day'] = $_POST['g3_schedules_day'];
	update_post_meta($post_id, 'g3_schedules_day', $data['g3_schedules_day']);
	$data['g3_schedules_start'] = $_POST['g3_schedules_start'];
	update_post_meta($post_id, 'g3_schedules_start', $data['g3_schedules_start']);
	$data['g3_schedules_end'] = $_POST['g3_schedules_end'];
	update_post_meta($post_id, 'g3_schedules_end', $data['g3_schedules_end']);
	return $data;
}
add_action('save_post', 'g3_schedules_save_postdata');

// render schedules
function g3_schedules() {
	$query = array(
		'post_type' => 'schedules',
		'posts_per_page' => -1,
		'orderby' => 'menu_order'
	);	

	$schedules = get_posts($query);

	$output = "";

	// create the list
	ob_start();
	//include the specified file
	include(G3_SCHEDULES_PATH . "/schedules-list.php");
	//assign the file output to $output variable and clean buffer
	$output .= ob_get_clean();	
	return $output;
}

// lets add some contextual help in for all these fancy things
add_action('load-post-new.php', 'g3_schedules_shortcode_help');
add_action('load-post.php', 'g3_schedules_shortcode_help');

function g3_schedules_shortcode_help() {
   add_filter('contextual_help','load_g3_schedules_edit_post_help');
}

function load_g3_schedules_edit_post_help($help) {
    ob_start();
?>
<h2>G3 Schedules Shortcodes</h2>
<p>If you want to display a list of schedules with images and blurb:</p>
<dl>
    <dt><pre>[g3_schedules /]</pre></dt>
    <dd>Shows the list of schedules</dd>
</dl>

<?php
	$out = ob_get_clean();

    get_current_screen()->add_help_tab( array(
        'id'        => 'g3-schedules-shortcodes',
        'title'     => __('G3 Schedules Shortcodes'),
        'content'   => $out
    ) );
}

function g3_schedules_shortcode($atts, $content = null) {
	echo g3_schedules();
}
add_shortcode('g3_schedules', 'g3_schedules_shortcode');

function g3_schedules_theme_editor_help($help) {
    ob_start();
?>
<h2>G3 Schedules functions</h2>
<p>If you want to display a list of schedules with images and blurb:</p>
<dl>
    <dt><pre>echo g3_schedules();</pre></dt>
    <dd>Shows the schedules.</dd>
</dl>
<?php
	$out = ob_get_clean();

    get_current_screen()->add_help_tab( array(
        'id'        => 'g3-schedules-phpcodes',
        'title'     => __('G3 Schedules PHP Codes'),
        'content'   => $out
    ) );
}

// some more help for the theme editor
function g3_schedules_theme_menu_help() {
   add_filter('contextual_help','g3_schedules_theme_editor_help');
}
add_action('load-theme-editor.php', 'g3_schedules_theme_menu_help');
?>
