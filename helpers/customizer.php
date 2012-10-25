<?php

/*****************************************************/
/* Initializer the Heavily Modified Theme Customizer */
/*****************************************************/

PL_Customizer_Helper::init();
class PL_Customizer_Helper 
{
	public static function init() {
		add_action ('admin_menu', array(__CLASS__, 'themedemo_admin') );
		add_action( 'customize_register', array(__CLASS__, 'PL_customize_register'), 1 );
		add_action( 'customize_controls_print_footer_scripts', array(__CLASS__, 'load_partials') );

		add_action( 'wp_ajax_load_custom_styles', array(__CLASS__, 'load_custom_styles') );
		add_action( 'wp_ajax_load_theme_info', array(__CLASS__, 'load_theme_info') );
		add_action( 'wp_ajax_change_theme', array(__CLASS__, 'change_theme') );
	}

	public static function is_onboarding() {
		// NOTE: For now, always set onboarding to true--change this when releasing...
		return true; // ( isset($_GET['onboard']) && strtolower($_GET['onboard']) == 'true' );
	}

	public static function themedemo_admin() 
	{
	    // add the Customize link to the admin menu
	    add_theme_page( 'Customize', 'Customize', 'edit_theme_options', 'customize.php' );
	}

	public static function PL_customize_register( $wp_customize ) 
	{
		// A simple check to ensure function was called properly...
		if ( !isset($wp_customize) ) { return; }

		// This is a global function, as PHP does not allow nested class declaration...
		define_custom_controls();

		/* NOTE: For now, always load customizer with onboard settings... */
		$onboard = true; // ( isset($_GET['onboard']) && strtolower($_GET['onboard']) == 'true' );
		PL_Customizer::register_components( $wp_customize, $onboard );

		// Prevent default control from being created
		remove_action( 'customize_register', array(  $wp_customize, 'register_controls' ) );

		// No infobar in theme previews...
		remove_action( 'wp_head', 'placester_info_bar' );

		// Register function to inject script to make postMessage settings work properly
		if ( $wp_customize->is_preview() && ! is_admin() ) { 
			add_action( 'wp_footer', array(__CLASS__, 'inject_postMessage_hooks'), 21); 
		}
	}

	public static function inject_postMessage_hooks() {
	  // Gets the theme that the customizer is currently set to display/preview...
	  global $wp_customize;
	  $theme_opts_key = $wp_customize->get_stylesheet();
	  // error_log($theme_opts_key);

	  $postMessage_settings = array(
	  								 'pls-site-title' => 'header h1 a', 
	  								 'pls-site-subtitle' => 'header h2, #slogan', 
	  								 'pls-user-email' => 'section.e-mail a, #contact .email a, header .phone a, section.email a, header p.h-email a, .widget-pls-agent .email', 
	  								 'pls-user-phone' => 'section.contact-info .phone, header p.h-phone, header div.phone, header section.phone .phone-bg-mid, .widget-pls-agent .phone'
	  								);

	  ?>
	    <script type="text/javascript">
	    ( function( $ ){
	    <?php foreach ($postMessage_settings as $id => $selector): ?>
	      wp.customize('<?php echo "{$theme_opts_key}[{$id}]"; ?>', function( value ) {
	        value.bind(function(to) {
	          //if (to) {	
	            $('<?php echo $selector; ?>').text(to);
	          //}
	        });
	      });
	    <?php endforeach; ?>  
	    } )( jQuery )
	    </script>
	  <?php
	}

	public static function load_partials() {
	  ?>
	    <!-- Spinner for Theme Preview overlay -->
	    <img id="preview_load_spinner" src="<?php echo plugins_url('/placester/images/preview_load_spin.gif'); ?>" alt="Theme Preview is Loading..." />
	  
	  <?php if ( self::is_onboarding() ): ?>
	    <!-- Tooltip box -->
	    <div id="tooltip" class="tip">
	      <a class="close" href="#"></a>    
	    	<h4>Welcome!</h4>
	      <p class="desc">Great!  You're making all the right moves.  We're going to take you into the main admin panel now so you can further customize your web site.<br />
	      <br />You can always return to this customization wizard by clicking Appearance in the main menu, then clicking "Customize."</p>
	      <p class="link"><a href="#">Let's Get Started</a></p>
	    </div>

	    <script type="text/javascript">
	      var wizard_global = {
	      	states: {
	      		welcome: {
	      			header: 'Welcome!',
	      			content: 'Great!  You\'re making all the right moves.  We\'re going to take you into the main admin panel now so you can further customize your web site.<br />'
	      					  + '<br />You can always return to this customization wizard by clicking Appearance in the main menu, then clicking "Customize."',
	      			link: 'Let\'s Get Started',
	      			pane_id: '',
	      			left: '39%',
	      			top: '36%',
	      			next_state: 'theme'
	      		},
	      		theme: {
	      			header: '1. Theme Selection',
	      			content: '',
	      			link_text: 'Select a Theme',
	      			pane_id: 'theme_content',
	      			left: '75px',
	      			top: '50px',
	      			next_state: 'title'
	      		},
	      		title: {
	      			header: '2. Slogan & Title',
	      			content: 'Add a Title',
	      			link_text: '',
	      			pane_id: 'title_content',
	      			left: '75px',
	      			top: '100px',
	      			next_state: 'colors'
	      		},
	      		colors: {
	      			header: '3. Colors & Style',
	      			content: '',
	      			link_text: 'Customize your Theme',
	      			pane_id: 'colors_content',
	      			left: '75px',
	      			top: '150px',
	      			next_state: 'brand'
	      		},
	      		brand: {
	      			header: '4. Upload Logo',
	      			content: '',
	      			link_text: 'Upload my Logo',
	      			pane_id: 'brand_content',
	      			left: '75px',
	      			top: '200px',
	      			next_state: 'mls'
	      		},
	      		mls:  {
	      			header: '5. MLS Integration',
	      			content: '',
	      			link_text: 'Integrate with your MLS',
	      			pane_id: 'mls_content',
	      			left: '75px',
	      			top: '250px',
	      			next_state: 'listing'
	      		},
	      		listing: {
	      			header: '6. Post a Listing',
	      			content: '',
	      			link_text: 'Post my First Listing',
	      			pane_id: 'listing_content',
	      			left: '75px',
	      			top: '300px',
	      			next_state: 'post'
	      		},
	      		post: {
	      			header: '7. Make a Blog Post',
	      			content: '',
	      			link_text: 'Make a Post',
	      			pane_id: 'post_content',
	      			left: '75px',
	      			top: '350px',
	      			next_state: 'analytics'
	      		},
	      		analytics: {
	      			header: '8. Analytics',
	      			content: '',
	      			link_text: 'Integrate with Google',
	      			pane_id: 'analytics_content',
	      			left: '75px',
	      			top: '400px',
	      			next_state: 'confirm'
	      		},
	      		confirm: {
	      			header: '',
	      			content: '',
	      			link_text: '',
	      			pane_id: 'confirm_content',
	      			left: '75px',
	      			top: '450px',
	      			next_state: ''
	      		}
	      	},
	      	active_state: 'welcome' // Initially set to this value for page_load...
	      }
	    </script>
	  <?php endif; ?>

	  <?php
	}

	public static function load_custom_styles() {
		if ( isset($_POST['template']) && isset($_POST['color']) )  {
		  	// This needs to be defined (ref'd by the template file we're about to load...)
		  	$color = $_POST['color'];

		  	ob_start();
				include(trailingslashit(PL_PARENT_DIR) . 'config/customizer/theme-skins/' . $_POST['template'] . '.php');
			$styles = ob_get_clean();			

			echo json_encode( array( 'styles' => $styles ) );
		}

		die();
	}

	public static function load_theme_info() {
		if ( isset($_POST['theme']) ) {
			$theme_name = $_POST['theme'];
			// switch_theme( $theme_name, $theme_name);

			$theme_obj = wp_get_theme( $theme_name );

			ob_start();
			?>
	            <div class="theme-screenshot">
	              <img src="<?php echo esc_url( $theme_obj->get_screenshot() ); ?>" />
	      	    </div>

	            <h2>Theme Description</h2>
	            <p><?php echo $theme_obj->display('Description'); ?></p>
	        <?php
	        $new_html = ob_get_clean();
	       	    
			echo json_encode(array('theme_info' => $new_html));
		}

		die();
	}

	public static function change_theme() {
		if ( isset ($_POST['new_theme']) ) {
			$new_theme = $_POST['new_theme'];

			// Assume stylesheet and template name are the same for now...
			switch_theme( $new_theme, $new_theme );

			echo json_encode(array('success' => 'true'));
		}

		die();
	}

}

?>