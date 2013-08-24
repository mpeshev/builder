<?php 

PL_WordPress_Helper::init();
class PL_WordPress_Helper {
	
	public static function init () {
		add_action('switch_theme', array(__CLASS__, 'report_theme'));
		add_action('wp_ajax_publish_post', array(__CLASS__, 'publish_post' ) );
	}

	public static function report_theme () {
		$theme = 'not sure';
		//I don't believe this function is registered at the time this is hooked in. 
		if (function_exists('wp_get_theme')) {
			$theme = strtolower(wp_get_theme()->get_template());
		}
		$response = PL_WordPress::set(array_merge(array('theme' => $theme), array('url' => site_url() ) ) );
		return $response;
	}

	public static function report_url () {
		$request = array('url' => site_url());
		$response = PL_WordPress::set($request);
	}

	public static function remote_filter_update ($args = array()) {
		$args = wp_parse_args($args);
		PL_Helper_User::set_global_filters($args);
	}

	public static function publish_post() {
		// Get current user's WP id...
		global $user_ID;

		// Format & sanitize title & content...
		$title = stripslashes($_POST['title']);
		$title = preg_replace('/<\?.*?(\?>|$)/', '', strip_tags($title));

		$content = stripslashes($_POST['content']);
		$content = preg_replace('/<\?.*?(\?>|$)/', '', strip_tags($content));

		$tags = stripslashes($_POST['tags']);
		$tags = preg_replace('/<\?.*?(\?>|$)/', '', strip_tags($tags));

		$new_post = array(
		    'post_title' => $title,
		    'post_content' => $content,
		    'post_status' => 'publish',
		    'post_date' => date('Y-m-d H:i:s'),
		    'post_author' => $user_ID,
		    'post_type' => 'post',
		    // 'post_category' => array(0),
		    'tags_input' => $tags
		);

		// Insert new post...
		$post_id = wp_insert_post($new_post);	

		echo json_encode( array('new_post_id' => $post_id) );
		die();
	}
}
