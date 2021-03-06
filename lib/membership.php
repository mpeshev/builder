<?php

/**
 * Class Designed to Handle the rigors of Membership, and membership options...
 * or something like that.
 * by matt, though much was taken from alex.
 */

PL_Membership::init();
class PL_Membership {

	static function init() {
		add_action( 'wp_ajax_nopriv_pl_register_lead', array( __CLASS__, 'ajax_create_lead' ));
		add_action( 'wp_ajax_nopriv_pl_login', array( __CLASS__, 'placester_ajax_login' ));
		// add_action( 'wp_ajax_nopriv_connect_wp_fb', array(__CLASS__, 'connect_fb_with_wp' ));
		// add_action( 'wp_ajax_nopriv_parse_signed_request', array(__CLASS__, 'fb_parse_signed_request' ));

		add_action( 'wp_ajax_add_favorite_property', array(__CLASS__,'ajax_add_favorite_property') );
		add_action( 'wp_ajax_nopriv_add_favorite_property', array(__CLASS__,'ajax_add_favorite_property') );
		add_action( 'wp_ajax_remove_favorite_property', array(__CLASS__,'ajax_remove_favorite_property') );

		add_action( 'wp_ajax_save_search', array(__CLASS__,'ajax_save_search') );

		add_shortcode( 'favorite_link_toggle', array(__CLASS__,'placester_favorite_link_toggle') );
		add_shortcode( 'lead_user_navigation', array(__CLASS__,'placester_lead_control_panel') );

		// Create the "Property lead" role
		$lead_role = add_role( 'placester_lead','Property Lead',array('add_roomates' => true,'read_roomates' => true,'delete_roomates' => true,'add_favorites' => true,'delete_roomates' => true,'level_0' => true,'read' => true) );
	}

	public static function get_favorite_ids () {
		$person = PL_People_Helper::person_details();
		$ids = array();
		if (isset($person['fav_listings'])) {
			foreach ( (array) $person['fav_listings'] as $fav_listings) {
				$ids[] = $fav_listings['id'];
			}
		}
		return $ids;
	}

	public static function ajax_save_search() {
		// irrelevant data to the search form filters
		$internal_params = array( 'action', 'submit', 'address_match' );
		foreach( $internal_params as $internal ) {
			if( isset( $_POST[$internal] ) ) unset($_POST[$internal]);
		}

		// add meta to user for searches
		if( ! empty( $_POST ) ) {
			$api_response = PL_People_Helper::add_member_saved_search( $_POST );
			echo json_encode( $api_response );
		} else {
			echo false;
		}

		die();
	}

	public static function ajax_add_favorite_property () {
		// Check to see if user is an admin (at this point, we know the user is logged in...)
		if (current_user_can('manage_options')) {
			echo json_encode(array('is_admin' => true));
		}
		else if ($_POST['property_id']) {
			$api_response = PL_People_Helper::associate_property($_POST['property_id']);
			echo json_encode($api_response);
		} else {
			echo false;
		}
		die();
	}

	public static function ajax_remove_favorite_property () {
		if ($_POST['property_id']) {
			$api_response = PL_People_Helper::unassociate_property($_POST['property_id']);
			echo json_encode($api_response);
			die();
		}
	}

	public static function get_client_area_url () {
		global $wpdb;
		$page_id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = 'client-profile'");
		return get_permalink($page_id);
	}

	public static function get_user () {
		$wp_user = wp_get_current_user();
		if ($wp_user->ID) {
			return $wp_user;
		}
		return false;
	}

	/**
	 *  Callback function for when the frontend
	 *  lead register form is submitted
	 *
	 *  JavaScript in "js/theme/placester.membership.js"
	 *
	 */
	public static function ajax_create_lead ()
	{
		//make sure it's from a form we created
		if ( !wp_verify_nonce($_POST['nonce'], 'placester_true_registration') ) {
			//malicious
			echo 'Sorry, your nonce didn\'t verify. Try using the form on the site.';
			die();
		}

		//all validation rules in a single place.
		$lead_object = self::validate_registration($_POST);

		//check for lead errors
		if ( !empty($lead_object['errors']) ) {
			$error_messages = self::process_registration_errors($lead_object['errors']);
			echo $error_messages;
			die(); // oops TODO: Fix the -1 random ass issue.
		} else {
			//create the lead!
			echo json_encode(self::create_lead($lead_object));
			die();
		}
	}

	// mother function for all lead creation.
	public static function create_lead($lead_object) {
		$wordpress_user_id = self::create_wordpress_user_lead($lead_object);
		if ( !is_wp_error($wordpress_user_id) ) {

			//force blog to be set immediately or MU throws errors.
			$blogs = get_blogs_of_user($wordpress_user_id);
			$first_blog = current($blogs);
			update_user_meta( $wordpress_user_id, 'primary_blog', $first_blog->userblog_id );

			$response = PL_People_Helper::add_person($lead_object);
			if (isset($response['code'])) {
				$lead_object['errors'][] = $response['message'];
				foreach ($response['validations'] as $key => $validation) {
					$lead_object['errors'][] = $response['human_names'][$key] . implode($validation, ' and ');
				}
				$lead_object['errors'][] = 'placester_create_failed';
			}

			// If the API call was successful, inform the user of his
			// password and set the password change nag
			if ( empty( $lead_object['errors'] ) ) {
				update_user_meta( $wordpress_user_id, 'placester_api_id', $response['id'] );
				wp_new_user_notification( $wordpress_user_id);
			}

			if (get_option('pls_send_client_option')) {
				wp_mail($lead_object['username'], 'Your new account on ' . site_url(), PL_Membership_Helper::parse_client_message($lead_object) );
			}

			//login user if successfully sign up.
			wp_set_auth_cookie($wordpress_user_id, true, is_ssl());
		} else {
			//failure
			$lead_object['errors'][] = 'wp_user_create_failed';
		}
		die();
	}


	/**
	*  Callback function for when the
	*  frontend login form is submitted
	*
	*  JavaScript in "js/theme/placester.membership.js"
	*
	*/
	public static function placester_ajax_login() {
		extract( $_POST );

		$errors = array();

		$sanitized_username = sanitize_user( $username );

		if ( empty( $sanitized_username ) ) {
			$errors['user_login'] = "An email address is required.";
		} elseif ( empty( $password )) {
			$errors['user_pass'] = "A password is required.";
		} else {
			$userdata = get_user_by( 'login', $sanitized_username );

			if ( $userdata ) {
				if ( !wp_check_password( $password, $userdata->user_pass, $userdata->ID ) )  {
					$errors['user_pass'] = "The password isn't correct.";
				}
			} else {
				$errors['user_login'] = "The email address is invalid.";
			}
		}

		if ( !empty($errors) ) {
			echo json_encode( $errors );
		} else {

			$rememberme = $remember == "forever" ? true : false;

			// Manually login user
			$creds['user_login'] = $sanitized_username;
			$creds['user_password'] = $password;
			$creds['remember'] = $rememberme;

			$user = wp_signon( $creds, true );

			wp_set_current_user($user->ID);

			$success = "You have successfully logged in.";
			echo json_encode( $success );

		}

		die;
	}

	//creates wordpress users given lead_objects
	private static function create_wordpress_user_lead($lead_object) {
		// Wordpress doesn't support phone.
		$userdata = array(
				'user_pass' => $lead_object['password'],
				'user_login' => $lead_object['username'],
				'user_email' => $lead_object['metadata']['email'],
				'role' => 'placester_lead',
		);

		$user_id = wp_insert_user( $userdata );

		//user creation failed.
		if ( !$user_id ) {
			return false;
		} else {
			return $user_id;
		}
	}

	// validates all registration data.
	private static function validate_registration($post_vars) {
		if ( is_array($post_vars)) {

			$lead_object['username'] = '';
			$lead_object['metadata']['email'] = '';
			$lead_object['password'] = '';
			$lead_object['name'] = '';
			$lead_object['phone'] = '';
			$lead_object['lead_type'] = get_bloginfo('url');
			$lead_object['errors'] = array();

			foreach ($post_vars as $key => $value) {
				switch ($key) {
					case 'username':
						$username['errors'] = array();
						$username['unvalidated'] = $value;
						$username['validated'] = '';

						//handles all random edge cases
						$username_validation = self::validate_username($username, $lead_object);

						//split verification array
						$username = $username_validation['username'];
						$lead_object = $username_validation['lead_object'];

						// if no errors, set username
						if( empty($username['errors']) ){
							$lead_object['username'] = $username['validated'];
						}

						break;

					case 'email':
						$email['errors'] = array();
						$email['unvalidated'] = $value;
						$email['validated'] = '';

						$email_validation = self::validate_email($email, $lead_object);

						//split verification array
						$email = $email_validation['email'];
						$lead_object = $email_validation['lead_object'];

						if ( empty($email['errors']) ) {
							$lead_object['metadata']['email'] = $email['validated'];
						}

						break;

					case 'password':
						$password['errors'] = array();
						$password['unvalidated'] = $value;
						$confirm_password = $post_vars['confirm'];
						$password['validated'] = '';

						$password_validation = self::validate_password($password, $confirm_password, $lead_object);

						//split verification array
						$password = $password_validation['password'];
						$lead_object = $password_validation['lead_object'];

						if ( empty($password['errors']) ) {
							$lead_object['password'] = $password['validated'];
						}
						break;

					case 'name':
						// we'll be fancy later.
						if ( !empty($value) ) {
							$lead_object['name'] = $value;
						}
						break;

					case 'phone':
						// we'll be fancy later.
						if ( !empty($value) ) {
							$lead_object['phone'] = $value;
						};
				}
			}
		}

		return $lead_object;

	}

	//rules for validating passwords
	private static function validate_password($password, $confirm_password, $lead_object) {
		//make sure we have password and confirm.
		if (!empty($password['unvalidated']) && !empty($confirm_password) ) {

			//make sure they are the same
			if ($password['unvalidated'] == $confirm_password ) {
				$password['validated'] = $password['unvalidated'];
			} else {
				//they aren't the same
				$lead_object['errors'][] = 'password_mismatch';
				$password['errors'] = true;
			}
		} else {
			// missing one.
			if ( empty($password['unvalidated']) ) {
				$lead_object['errors'][] = 'password_empty';
				$password['errors'] = true;
			}

			if ( empty($confirm_password)) {
				$lead_object['errors'][] = 'confirm_empty';
				$password['errors'] = true;
			}
		}

		return array('password' => $password, 'lead_object' => $lead_object);


	}

	//rules for validating email addresses
	private static function validate_email ($email, $lead_object)
	{
		if ( empty($email['unvalidated']) ) {
			$lead_object['errors'][] = 'email_required';
			$email['errors'] = true;
		} else {

			//something in email, is it valid?
			if ( is_email($email['unvalidated'] ) ) {
				if ( email_exists($email['unvalidated']) ) {
					$lead_object['errors'][] = 'email_taken';
					$email['errors'] = true;
				} else {
					$email['validated'] = $email['unvalidated'];
				}

			} else {
				$lead_object['errors'][] = 'email_invalid';
				$email['errors'] = true;
			}
		}

		return array('email' => $email, 'lead_object' => $lead_object);
	}

	// rules for validating the username
	private static function validate_username ($username, $lead_object)
	{

		//check for empty..
		if ( !empty($username['unvalidated']) ) {
			//check to see if it's valid
			$username['unvalidated'] = sanitize_user($username['unvalidated']);

		} else {
			//generate one from the email, because wordpress requries it
			$lead_object['errors'][] = 'username_empty';
			$username['errors'] = true;

		}

		// check if username exists.
		if ( username_exists($username['unvalidated']) ) {
			$lead_object['errors'][] = 'username_exists';
			$username['errors'] = true;
		} else {
			$username['validated'] = $username['unvalidated'];
		}

		return array('username' => $username, 'lead_object' => $lead_object);

	}

	// used for processing errors for the various forms.
	private static function process_registration_errors ($errors) {

		$error_messages = '';

		foreach ($errors as $error => $type) {

			switch ($type) {
				case 'username_exists':
					// $error_messages['username'][] .= 'That username already exists';
					$error_messages['user_email'] = 'That email already exists';
					break;

				case 'username_empty':
					// $error_messages['username'][] .= 'Username is required.';
					$error_messages['user_email'] = 'Email is required';
					break;

				case 'email_required':
					$error_messages['user_email'] = 'Email is required';
					break;

				case 'email_invalid':
					$error_messages['user_email'] = 'Your email is invalid';
					break;

				case 'email_taken':
					$error_messages['user_email'] = 'That email is already taken.';
					break;

				case 'password_empty':
					$error_messages['user_password'] = 'Password is required';
					break;

				case 'password_mismatch':
					$error_messages['user_confirm'] = 'Your passwords don\'t match';
					break;

				case 'confirm_empty':
					$error_messages['user_confirm'] = 'Confirm password is empty';
					break;

				default:
					$error_messages['user_email'] = 'There was an error, try again soon.';
					break;
			}
		}

		if ( !empty($error_messages) ) {
			echo json_encode( $error_messages );
		}
		// return $error_messages;
	}

	/**
	* Creates a registration form
	*
	* The paramater will be used as an action for the registration form and it
	* will be used in the ajax callback at submission
	*
	* @param string $role The Wordpress role
	*
	*/
	public static function generate_lead_reg_form ($role = 'placester_lead')
	{
		if ( ! is_user_logged_in() ) {
			ob_start();
			?>
			<div style="display:none;">
				<form method="post" action="#<?php echo $role; ?>" id="pl_lead_register_form" name="pl_lead_register_form" class="pl_login_reg_form pl_lead_register_form" autocomplete="off">

					<div style="display:none" class="success">You have been successfully signed up. This page will refresh momentarily.</div>

					<div id="pl_lead_register_form_inner_wrapper">

						<?php pls_do_atomic( 'register_form_before_title' ); ?>

						<h2>Sign Up</h2>

						<?php pls_do_atomic( 'register_form_before_email' ); ?>

						<p class="reg_form_email">
							<label for="user_email">Email</label>
							<input type="text" tabindex="25" size="20" required="required" class="input" id="reg_user_email" name="user_email" data-message="A valid email is needed." placeholder="Email">
						</p>

						<?php pls_do_atomic( 'register_form_before_password' ); ?>

						<p class="reg_form_pass">
							<label for="user_password">Password</label>
							<input type="password" tabindex="26" size="20" required="required" class="input" id="reg_user_password" name="user_password" data-message="Please enter a password." placeholder="Password">
						</p>

						<?php pls_do_atomic( 'register_form_before_confirm_password' ); ?>

						<p class="reg_form_confirm_pass">
							<label for="user_confirm">Confirm Password</label>
							<input type="password" tabindex="27" size="20" required="required" class="input" id="reg_user_confirm" name="user_confirm" data-message="Please confirm your password." placeholder="Confirm Password">
						</p>

						<?php pls_do_atomic( 'register_form_before_submit' ); ?>

						<p class="reg_form_submit">
							<input type="submit" tabindex="28" class="submit button" value="Register" id="pl_register" name="pl_register">
						</p>
						<?php echo wp_nonce_field( 'placester_true_registration', 'register_nonce_field' ); ?>
						<input type="hidden" tabindex="29" id="register_form_submit_button" name="_wp_http_referer" value="/listings/">

						<?php pls_do_atomic( 'register_form_after_submit' ); ?>

					</div>

				</form>
			</div>
			<?php
			$result = ob_get_clean();
		} else {
			ob_start();
			?>
				<div style="display:none">
					<div class="pl_error error" id="pl_lead_register_form">
					You cannot register a user if you are logged in. You shouldn't even see a "Register" link.
					</div>
				</div>
			<?php
			$result = ob_get_clean();
		}

		return $result;
	}


	/**
	 * Adds a "Add property to favorites" link
	 * if the user is not logged in, or if
	 * the property is not in the favorite list,
	 * and a "Remove property from favorites" otherwise
	 *
	 * TODO If logged in and not lead display something informing them
	 * of what they need to do to register a lead account
	 */
	public static function placester_favorite_link_toggle( $atts ) {
		$defaults = array(
			'add_text' => 'Add property to favorites',
			'remove_text' => 'Remove property from favorites',
			'spinner' => admin_url( 'images/wpspin_light.gif' ),
			'property_id' => false
			);

		$args = wp_parse_args( $atts, $defaults );
		extract( $args, EXTR_SKIP );

		$is_lead = current_user_can( 'placester_lead' );
		// if ( !$is_lead ) {
		//     return;
		// }

		// $add_link_attr = array('href' => "#{$property_id}",'id' => 'pl_add_favorite','class' => 'pl_prop_fav_link');
		// $remove_link_attr = array('href' => "#{$property_id}",'id' => 'pl_remove_favorite','class' => 'pl_prop_fav_link');

		// // Add extra classes if user not loggend in or doesn't have a lead account
		// if (  ) {
		//     // $add_link_attr['class'] .= 'guest';
		//     // $add_link_attr['href'] =
		//     // $add_link_attr['target'] = "_blank";
		// } else {

		//     // Return the remove link if favorite
		//     if ( $is_favorite )
		//         $add_link_attr['style'] = "display:none;";
		// }
		// if ( !isset($add_link_attr['style']) ) {
		//     $remove_link_attr['style'] = "display:none;";
		// }

		if ( is_user_logged_in() ) {
			$is_favorite = self::is_favorite_property($property_id);
		} else {
			$is_favorite = '';
		}
		ob_start();
		?>
			<div id="pl_add_remove_lead_favorites">

				<?php pls_do_atomic( 'before_add_to_fav' ); ?>

				<?php if (is_user_logged_in()): ?>
					<?php pls_do_atomic( 'before_add_to_fav_registered' ); ?>
					<a href="<?php echo "#" . $property_id ?>" id="pl_add_favorite" class="pl_prop_fav_link" <?php echo $is_favorite ? "style='display:none;'" : "" ?>><?php echo $add_text ?></a>
				<?php else: ?>
					<?php pls_do_atomic( 'before_add_to_fav_unregistered' ); ?>
					<a class="pl_register_lead_favorites_link" href="#pl_lead_register_form"><?php echo $add_text ?> </a>
				<?php endif ?>

				<a href="<?php echo "#" . $property_id ?>" id="pl_remove_favorite" class="pl_prop_fav_link" <?php echo !$is_favorite ? "style='display:none;'" : "" ?>><?php echo $remove_text ?></a>
				<img class="pl_spinner" src="<?php echo $spinner ?>" alt="ajax-spinner" style="display: none; margin-left: 5px;">

				<?php pls_do_atomic( 'after_add_to_fav' ); ?>

			</div>
		<?php
		return ob_get_clean();
	}


	public static function is_favorite_property ($property_id) {
		$person = PL_People_Helper::person_details();
		// pls_dump($property_id, $person['fav_listings']);
		if ( isset($person['fav_listings']) && is_array($person['fav_listings']) ) {
			foreach ($person['fav_listings'] as $fav_listing) {
				if ($fav_listing['id'] == $property_id) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Adds "Login | Register" if not logged in
	 * or "Logout | My account" if logged in
	 *
	 * TODO If logged in and not lead display something informing them
	 * of what they need to do to register a lead account
	 */
	public static function placester_lead_control_panel( $args ) {

	$fb_registered = false;
	// Capture users that just logged on w/ FB registration
	// if (isset($_POST['signed_request'])) {
	//   $fb_registered = true;
	//   $signed_request = self::fb_parse_signed_request($_POST['signed_request'], false);
	// }

	$defaults = array(
		'loginout' => true,
		'profile' => true,
		'register' => true,
		'container_tag' => false,
		'container_class' => false,
		'anchor_tag' => false,
		'anchor_class' => false,
		'separator' => ' | ',
		'inside_pre_tag' => false,
		'inside_post_tag' => false,
		'no_forms' => false // use this to return just the login/logout forms, no links. Do this for all calls to this function after the first on a page.
	);
	$args = wp_parse_args( $args, $defaults );
	extract( $args, EXTR_SKIP );

	// Register WP user w/ FB creds when FB registration has been triggered
	// if ($fb_registered) {
	//   self::connect_fb_with_wp($signed_request);
	// }


	$is_lead = current_user_can( 'placester_lead' );

	/** The login or logout link. */

	// user isn't logged into WP nor FB
	if ( !is_user_logged_in() && !$fb_registered ) {
		$loginout_link = '<a class="pl_login_link" href="#pl_login_form">Log in</a>';
	} else {
		$loginout_link = '<a href="' . esc_url( wp_logout_url(site_url()) ) . '" id="pl_logout_link">Log out</a>';
	}
	if ($anchor_tag) {
		$loginout_link = "<{$anchor_tag} class={$anchor_class}>" . $inside_pre_tag . $loginout_link . $inside_post_tag . "</{$anchor_tag}>";
	}


	/** The register link. */
	$register_link = '<a class="pl_register_lead_link" href="#pl_lead_register_form">Register</a>';
	if ($anchor_tag) {
		$register_link = "<{$anchor_tag} class={$anchor_class}>" . $inside_pre_tag . $register_link . $inside_post_tag . "</{$anchor_tag}>";
	}

	/** The profile link. */
	$profile_link = '<a id="pl_lead_profile_link" target="_blank" href="' . self::get_client_area_url() . '">My Account</a>';
	if ($anchor_tag) {
		$profile_link = "<{$anchor_tag} class={$anchor_class}>" . $inside_pre_tag . $profile_link . $inside_post_tag . "</{$anchor_tag}>";
	}
	// var_dump($profile_link);

	$loginout_link = ( $loginout ) ? $loginout_link : '';
	$register_link = ( $register ) ? ( empty($loginout_link) ? $register_link : $separator . $register_link ) : '';
	$profile_link = ( $profile ) ? ( empty($loginout_link) ? $profile_link : $separator . $profile_link ) : '';

	if ( ! is_user_logged_in() && ($no_forms != true) ) {

		// set the URL
	if (is_home()) {
		$url = home_url();
	} else {
		$url = get_permalink();
	}
	ob_start();
	?>
		<form name="pl_login_form" id="pl_login_form" action="<?php echo home_url(); ?>/wp-login.php" method="post" class="pl_login_reg_form">

			<?php pls_do_atomic( 'login_form_before_title' ); ?>

			<div id="pl_login_form_inner_wrapper">
				<h2>Login</h2>
				<!-- redirect-uri="<?php //echo $_SERVER["HTTP_REFERER"]; ?>" -->
				<!-- <fb:registration fields="name,location,email" width="260"></fb:registration> -->

				<?php pls_do_atomic( 'login_form_before_email' ); ?>

				<p class="login-username">
					<label for="user_login">Email</label>
					<input type="text" name="user_login" id="user_login" class="input" required="required" value="" tabindex="20" data-message="A valid email is needed" placeholder="Email" />
				</p>

				<?php pls_do_atomic( 'login_form_before_password' ); ?>

				<p class="login-password">
					<label for="user_pass">Password</label>
					<input type="password" name="user_pass" id="user_pass" class="input" required="required" value="" tabindex="21" data-message="A password is needed" placeholder="Password" />
				</p>

				<?php pls_do_atomic( 'login_form_before_remember' ); ?>

				<p class="login-remember">
					<label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="22" /> Remember Me</label>
				</p>

				<?php pls_do_atomic( 'login_form_before_submit' ); ?>

				<p class="login-submit">
					<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="Log In" tabindex="23" />
					<input type="hidden" name="redirect_to" value="<?php echo $url; ?>" />
				</p>

				<?php pls_do_atomic( 'before_login_title' ); ?>

			</div>

		</form>
		<?php
		$login_form = ob_get_clean();
		if ($container_tag && ($no_forms != true)) {
			return "<{$container_tag} class={$container_class}>" . $loginout_link . $register_link . "</{$container_tag}>" . self::generate_lead_reg_form() . "<div style='display:none;'>{$login_form}</div>";
		}
			return $loginout_link . $register_link . self::generate_lead_reg_form() . "<div style='display:none;'>{$login_form}</div>";
		} else {
			/** Remove the link to the profile if the current user is not a lead. */
			$extra = $is_lead ? $profile_link : "";
			if ($container_tag) {
				return "<{$container_tag} class={$container_class}>" . $loginout_link . $extra . "</{$container_tag}>";
			}
			return $loginout_link . $extra;
		}
	}


	public static function connect_fb_with_wp ($signed_request) {

		// json_decode signed_request into array
		$signed_request = json_decode($signed_request, true);

		$user_id = $signed_request['user_id'];
		$user_email = $signed_request['registration']['email'];
		$user_name = $signed_request['registration']['name'];
		$userdata = get_user_by( 'login', $user_id );

		// ob_start();
		//   pls_dump($userdata);
		// error_log(ob_get_clean());

		if ( $userdata ) {

			// user exists - manually log user in.
			// $creds['user_login'] = $user_id;
			// $creds['user_password'] = 'n8ph6QAs';
			// $creds['remember'] = true;
			// $user = wp_signon( $creds, true );

			wp_set_current_user($user_id);
			wp_set_auth_cookie($user_id, true);

			// $user = get_user_by('login', $user_id);

			// ob_start();
			//   pls_dump($user->ID);
			// error_log(ob_get_clean());


			// wp_set_current_user($user->ID);
			// wp_set_current_user('40');

		} else {

			// create random password
			$random_pass = self::random_password();

			// user doesn't exist, create user.
			$userdata = array(
				'user_pass' => $random_pass,
				'user_login' => $user_id,
				'user_url' => $_SERVER["SERVER_NAME"],
				'user_email' => $user_email,
				'user_nicename' => $user_name,
				'role' => 'placester_lead'
				);

			// add user to WP user table
			wp_insert_user( $userdata );

			$user = get_user_by('login', $user_id);

			// wp_set_auth_cookie($user->ID, true);
			// wp_set_current_user($user->ID);

			// $creds['user_login'] = $user_id;
			// $creds['user_password'] = $random_pass;
			// $creds['remember'] = true;
			// $created_user = wp_signon( $creds, false );

			// send user email w/ login and password
			wp_mail($user_email,
				'Your password for ' . $_SERVER["SERVER_NAME"],
				"to log into " . $_SERVER["SERVER_NAME"] . " your username is '" . $user_email . "', and your password is '" . $random_pass . "'. However, as long as you are signed into Facebook, you won't need to manually sign in."
				);

		}
	}


	// Parse Facebook Signed Request
	public static function fb_parse_signed_request($signed_request = '', $return = 'ajax') {

		if (empty($signed_request)) {
			extract($_POST);
		}

		list($encoded_sig, $payload) = explode('.', $signed_request, 2);

		// decode the data
		$sig = self::base64_url_decode($encoded_sig);
		$data = self::base64_url_decode($payload);

		if ($return == 'ajax') {
			echo $data;
		} else {
			return $data;
		}

	}

	public static function base64_url_decode($input) {
		return base64_decode(strtr($input, '-_', '+/'));
	}


	public static function random_password() {
		$alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
}