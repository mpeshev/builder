<?php 

PL_Js_Helper::init();

class PL_Js_Helper {

	public function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin') );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend') );
		add_action( 'admin_head', array(__CLASS__, 'admin_menu_url') );
		add_action( 'customize_controls_enqueue_scripts', array(__CLASS__, 'customizer') );
	}

	public function admin ($hook) {
		// Inject premium themes logic into the themes admin page when visiting from any site on the hosted env...
		if ($hook == 'themes.php' && defined('HOSTED_PLUGIN_KEY')) {
			self::register_enqueue_if_not('premium', trailingslashit(PL_JS_URL) . 'admin/premium.js', array('jquery'));
			// self::register_enqueue_if_not('jquery-ui', trailingslashit(PL_JS_LIB_URL) .  'jquery-ui/js/jquery-ui-1.8.17.custom.min.js', array( 'jquery'));
			self::register_enqueue_if_not('free-trial', trailingslashit(PL_JS_URL) .  'admin/free-trial.js', array( 'jquery-ui-core'));
			
			// Print global JS var containing premium theme list...
			global $PL_CUSTOMIZER_THEMES;
			ob_start();
			?> 
			  <script type="text/javascript"> 
			    var pl_premThemes = [];
			    <?php foreach ( $PL_CUSTOMIZER_THEMES['Premium'] as $name => $template ): ?>
			      pl_premThemes.push("<?php echo $template; ?>");
			    <?php endforeach; ?>
			  </script>
			<?php
			echo ob_get_clean();

			// Need this for trial activation...
			PL_Router::load_builder_partial('free-trial.php');

			// Launch dialog after theme is switched...
			if ( PL_Bootup::is_theme_switched() ) {
	    		self::register_enqueue_if_not('theme-switch', trailingslashit(PL_JS_URL) .  'admin/theme-switch.js', array( 'jquery-ui-core'));  
	    	}

	    	// Don't load any other scripts...
			return;
		} 

		// Handle plugin admin pages...
		$pages = array('placester_page_placester_properties', 
					   'placester_page_placester_property_add', 
					   'placester_page_placester_settings', 
					   'placester_page_placester_support', 
					   'placester_page_placester_theme_gallery', 
					   'placester_page_placester_integrations',
					   'placester_page_placester_settings_polygons', 
					   'placester_page_placester_settings_property_pages', 
					   'placester_page_placester_settings_international', 
					   'placester_page_placester_settings_neighborhood',
					   'placester_page_placester_settings_filtering', 
					   'placester_page_placester_settings_client',
					   'placester_page_placester_settings_template');

		if (!in_array($hook, $pages)) { return; }
		
// 		wp_enqueue_script('jquery-ui-core');
		//self::register_enqueue_if_not('jquery-ui', trailingslashit(PL_JS_LIB_URL) .  'jquery-ui/js/jquery-ui-1.8.17.custom.min.js', array( 'jquery'));
		self::register_enqueue_if_not('global', trailingslashit(PL_JS_URL) .  'admin/global.js', array( 'jquery-ui-core'));
		
		// ob_start();
		// pls_dump(PL_User::subscriptions());
		// error_log(ob_get_clean());

		// $sub = PL_User::subscriptions();
		// if ( empty($sub['plan']) && $sub['eligible_for_trial'] ) {
			self::register_enqueue_if_not('sign-up', trailingslashit(PL_JS_URL) .  'admin/sign-up.js', array( 'jquery-ui-dialog'));
			self::register_enqueue_if_not('free-trial', trailingslashit(PL_JS_URL) .  'admin/free-trial.js', array( 'jquery-ui-core'));
			self::register_enqueue_if_not('integration', trailingslashit(PL_JS_URL) .  'admin/integration.js', array( 'jquery-ui-core'));
			self::register_enqueue_if_not('demo-data', trailingslashit(PL_JS_URL) .  'admin/demo-data.js', array('jquery-ui-core'));
		// }
		
		if ($hook == 'placester_page_placester_properties') {
			self::register_enqueue_if_not('datatables', trailingslashit(PL_JS_LIB_URL) .  'datatables/jquery.dataTables.js', array( 'jquery'));			
			self::register_enqueue_if_not('my-listings', trailingslashit(PL_JS_URL) .  'admin/my-listings.js', array( 'jquery', 'jquery-ui-datepicker'));
		}

		if ($hook == 'placester_page_placester_property_add') {						
			self::register_enqueue_if_not('blueimp-iframe', trailingslashit(PL_JS_LIB_URL) .  'blueimp/js/jquery.iframe-transport.js', array( 'jquery'));			
			self::register_enqueue_if_not('blueimp-file-upload', trailingslashit(PL_JS_LIB_URL) .  'blueimp/js/jquery.fileupload.js', array( 'jquery'));			
			self::register_enqueue_if_not('add-listing', trailingslashit(PL_JS_URL) .  'admin/add-listing.js', array( 'jquery', 'jquery-ui-datepicker'));			
		}

		if ($hook == 'placester_page_placester_theme_gallery') {						
			self::register_enqueue_if_not('theme-gallery', trailingslashit(PL_JS_URL) .  'admin/theme-gallery.js', array( 'jquery'));			
		}

		// if ($hook == 'placester_page_placester_integrations') {						
		// 	self::register_enqueue_if_not('integration', trailingslashit(PL_JS_URL) .  'admin/integration.js', array( 'jquery'));			
		// }


		if ($hook == 'placester_page_placester_settings') {
			self::register_enqueue_if_not('settings', trailingslashit(PL_JS_URL) .  'admin/settings/general.js', array( 'jquery'));	
		}

		if ($hook == 'placester_page_placester_settings_polygons') {
			self::register_enqueue_if_not('settings', trailingslashit(PL_JS_URL) .  'admin/settings/polygon.js', array( 'jquery'));	
			self::register_enqueue_if_not('new-colorpicker', trailingslashit(PL_JS_URL) .  'lib/colorpicker/js/colorpicker.js', array( 'jquery'));	
			self::register_enqueue_if_not('google-maps', 'http://maps.googleapis.com/maps/api/js?sensor=false', array( 'jquery'));	
			self::register_enqueue_if_not('text-overlay', trailingslashit(PL_JS_URL) .  'lib/google-maps/text-overlay.js', array( 'jquery'));	
			self::register_enqueue_if_not('datatables', trailingslashit(PL_JS_LIB_URL) .  'datatables/jquery.dataTables.js', array( 'jquery'));	
		}

		if ($hook == 'placester_page_placester_settings_property_pages') {
			self::register_enqueue_if_not('settings-property', trailingslashit(PL_JS_URL) .  'admin/settings/property.js', array( 'jquery'));	
			self::register_enqueue_if_not('datatables', trailingslashit(PL_JS_LIB_URL) .  'datatables/jquery.dataTables.js', array( 'jquery'));	
		}
		
		if ($hook == 'placester_page_placester_settings_international') {
			self::register_enqueue_if_not('settings', trailingslashit(PL_JS_URL) .  'admin/settings/international.js', array( 'jquery'));	
			self::register_enqueue_if_not('settings', trailingslashit(PL_JS_URL) .  'admin/settings.js', array( 'jquery'));	
		}
		
		if ($hook == 'placester_page_placester_settings_neighborhood') {
			self::register_enqueue_if_not('settings', trailingslashit(PL_JS_URL) .  'admin/settings.js', array( 'jquery'));	
		}
		
		if ($hook == 'placester_page_placester_settings_filtering') {
			self::register_enqueue_if_not('settings', trailingslashit(PL_JS_URL) .  'admin/settings/filtering.js', array( 'jquery'));	
		}
		
		if ($hook == 'placester_page_placester_settings_client') {
			self::register_enqueue_if_not('settings-client', trailingslashit(PL_JS_URL) .  'admin/settings/client.js', array( 'jquery'));	
		}
		
		if ($hook == 'placester_page_placester_settings_template') {
			self::register_enqueue_if_not('settings-template', trailingslashit(PL_JS_URL) .  'admin/settings/template.js', array( 'jquery'));	
		}
	}

	public function admin_menu_url () {
		?>
			<script type="text/javascript">
				var adminurl = '<?php echo ADMIN_MENU_URL; ?>';
				var siteurl = '<?php echo site_url(); ?>';
			</script>
		<?php

	}

	public function frontend() {
		self::register_enqueue_if_not('datatables', trailingslashit(PL_JS_LIB_URL) .  'datatables/jquery.dataTables.js', array( 'jquery'));			
		self::register_enqueue_if_not('leads', trailingslashit(PL_JS_PUB_URL) .  'leads.js', array( 'jquery'));
		self::register_enqueue_if_not('membership', trailingslashit(PL_JS_PUB_URL) .  'membership.js', array( 'jquery'));
		self::register_enqueue_if_not('general', trailingslashit(PL_JS_PUB_URL) .  'general.js', array( 'jquery'));
		
		if ( PL_Option_Helper::get_demo_data_flag() && current_user_can('manage_options') ) {
			self::register_enqueue_if_not('infobar', trailingslashit(PL_JS_PUB_URL) .  'infobar.js', array( 'jquery'));
		}			
	}

	public function customizer() {
		self::register_enqueue_if_not('customizer', trailingslashit(PL_JS_PUB_URL) . 'customizer.js', array('jquery'));
		if ( PL_Customizer_Helper::is_onboarding() ) {
			self::register_enqueue_if_not('onboard', trailingslashit(PL_JS_PUB_URL) . 'onboard.js', array('jquery'));
		}

		// self::register_enqueue_if_not('jquery-ui', trailingslashit(PL_JS_LIB_URL) .  'jquery-ui/js/jquery-ui-1.8.17.custom.min.js', array( 'jquery'));
		self::register_enqueue_if_not('global', trailingslashit(PL_JS_URL) .  'admin/global.js', array( 'jquery-ui-core'));
		self::register_enqueue_if_not('free-trial', trailingslashit(PL_JS_URL) .  'admin/free-trial.js', array( 'jquery-ui-core'));
		self::register_enqueue_if_not('integration', trailingslashit(PL_JS_URL) .  'admin/integration.js', array( 'jquery-ui-core'));

	    if ( PL_Bootup::is_theme_switched() ) {
	    	self::register_enqueue_if_not('theme-switch', trailingslashit(PL_JS_URL) .  'admin/theme-switch.js', array( 'jquery-ui-core'));  
	    }
	}

	public static function register_enqueue_if_not($name, $path, $dependencies = array(), $version = null, $in_footer = false) {
		if (!wp_script_is($name, 'registered')) {
			wp_register_script($name, $path, $dependencies, $version, $in_footer);		
		}

		if ( !wp_script_is($name, 'queue') ) {
			wp_enqueue_script($name);		
		}	
	}

//end class
}