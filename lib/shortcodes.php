<?php

/****** 
	This class defines the shortcodes that can be used to access blueprint 
	functionality from any page or post.

	It handles both the implementation of each code in addition to hooking 
	them into the proper events and filters.
******/

PL_Shortcodes::init();
class PL_Shortcodes
{
	public static $codes = array(
				'search_form', 
				'search_listings',
				'static_listings',
				'search_map',
				'listing_slideshow',
				//
				'prop_details',
				'featured_listings',
				'post_listing',
				'advanced_slideshow',
				'pl_neighborhood'
			);

	public static $p_codes = array(
				'search_form' => 'Search Form Shortcode',
				'search_listings' => 'Search Listings Shortcode',
				'search_map' => 'Listings Map Template',
				'listing_slideshow' => 'Listing Slideshow Template',
				'static_listings' => 'Slideshow Template',
				//
				'prop_details' => 'Property Details Template',
				'featured_listings' => 'Slideshow Template',
				'advanced_slideshow' => 'Advanced Slideshow Template',
				'post_listing' => 'Post Listing Template',
				'pl_neighborhood' => 'Neighborhood Template'
			);
	
	// TODO: Construct these lists dynamically by examining the doc hierarchy...
	public static $defaults = array(
				'search_form' => array('twentyten', 'twentyeleven'),
        'search_listings' => array('twentyten', 'twentyeleven'),
        'search_map' => array('twentyten', 'twentyeleven'),
        'listing_slideshow'	=> array('twentyten', 'twentyeleven'),
        'static_listings' => array('twentyten', 'twentyeleven','responsive_basic'),
 				// 
 				'listings' => array('twentyten', 'twentyeleven'),
 				'prop_details' => array('twentyten', 'twentyeleven'),
 				'featured_listings' => array('twentyten', 'twentyeleven'),
 				'advanced_slideshow' => array('twentyten', 'twentyeleven'),
 				'post_listing' => array('twentyten', 'twentyeleven'),
 				'pl_neighborhood' => array('twentyten', 'twentyeleven')
 			);

	public static $subcodes = array(
				'search_form'  =>  array(
							'bedrooms',
	            'min_beds',
	            'max_beds',
	            'bathrooms',
	            'min_baths',
	            'max_baths',
	            'price',
	            'half_baths',
	            'property_type',
	            'listing_types',
	            'zoning_types',
	            'purchase_types',
	            'available_on',
	            'cities',
	            'states',
	            'zips',
	            'neighborhood',
	            'county',
	            'min_price',
	            'max_price',
	            'min_price_rental',
							'max_price_rental'
				),
				'listing'	  =>  array(
							'price',
							'sqft',
							'beds',
							'baths',
							'half_baths',
							'avail_on',
							'url',
							'address',
							'locality',
							'region',
							'postal',
							'neighborhood',
							'county',
							'country',
							'coords',
							'unit',
							'full_address',
							'email',
							'phone',
							'desc',
							'image',
							'mls_id',
							'map',
							'listing_type',
							'img_gallery',
							'amenities',
							'price_unit',
							//'compliance'
        ),
				'listing_slideshow' => array(
							'ls_index',
							'ls_url',
							'ls_address',
							'ls_beds',
							'ls_baths',
				),
				//
				'neighborhood' => array(
							'nb_title',
							'nb_featured_image',
							'nb_description',
							'nb_link',
							'nb_map'
				),

			);

	// TODO: These are a temporary solution, come up with a better convention...
	public static $form_html = false;
	public static $listing = false;
	public static $prop_details_enabled_key = 'pls_prop_details_enabled';

	public function init() 
	{

		//pulls in all the macro shortcodes, static list defined above
		foreach (self::$codes as $shortcode) {
			add_shortcode($shortcode, array(__CLASS__, $shortcode . '_shortcode_handler'));			
		}

		// For any shortcodes that use subcodes, register them to a single handler that bears the shortcode's name
		foreach (self::$subcodes as $code => $subs) {
		  foreach ($subs as $sub) {
			add_shortcode($sub, array(__CLASS__, $code . '_sub_shortcode_handler'));
		  }	
		}

		// register several helpful shortcodes for filters, for metadata, locaiton and common
		add_shortcode('pl_filter', array(__CLASS__, 'pl_filter_shortcode_handler'));
		
		// Register hooks to customize the html for the wrapper functions
		add_filter('pls_listings_search_form_outer_shortcode', array(__CLASS__, 'searchform_shortcode_context'), 10, 6);
		add_filter('pls_listings_list_ajax_item_html_shortcode', array(__CLASS__, 'listings_shortcode_context'), 10, 3);
		add_filter('property_details_filter', array(__CLASS__, 'prop_details_shortcode_context'), 10, 2);


		// Ensure all of shortcodes are set to some snippet...
		foreach (self::$codes as $code) {
			add_option( ('pls_' . $code), self::$defaults[$code][0] );
		}
		
		// Separately register the Compliance shortcode as it's not completely relevant
		// to the widget types
		add_shortcode( 'compliance', array( __CLASS__, 'compliance_shortcode_handler' ) );

		// Handle the special case of turning property details functionality on/off...
		add_option( self::$prop_details_enabled_key, 'false' ); 

		//basically initializes the bootloader object if it's been defined because a
		//shortcode has been called
		add_action('wp_footer', array(__CLASS__, 'init_bootloader'));
		// add_action('after_switch_theme', array(__CLASS__, 'update_shortcode_js'));
	}


	/*** Shortcode Handlers ***/	
	
	public static function compliance_shortcode_handler( $atts ) {
		$content = PL_Component_Entity::compliance_entity( $atts );
		
		return PL_Shortcode_Wrapper::create( 'compliance', $content );
		
	} 
	
	public static function search_form_shortcode_handler($atts) {
		$content = PL_Component_Entity::search_form_entity( $atts );
		
		return PL_Shortcode_Wrapper::create( 'search_form', $content );
	}
	
	public static function neighborhood_shortcode_handler($atts) {
		//$content = PL_Component_Entity::search_form_entity( $atts );
		$content = '';
		
		return PL_Shortcode_Wrapper::create( 'neighborhood', $content );
	}


	public static function listing_slideshow_shortcode_handler ($atts) {
		$content = PL_Component_Entity::listing_slideshow( $atts );
		
		return PL_Shortcode_Wrapper::create( 'listing_slideshow', $content );
		
	}
	public static function advanced_slideshow_shortcode_handler ($atts) {
		$content = PL_Component_Entity::advanced_slideshow_entity( $atts );
		
		return PL_Shortcode_Wrapper::create( 'advanced_slideshow', $content );
	}
	
	// Handle featured listings and filters
	public static function featured_listings_shortcode_handler ($atts, $content = '') {
				
		$content = PL_Component_Entity::featured_listings_entity( $atts );
		
		return PL_Shortcode_Wrapper::create( 'featured_listings', $content );	
	}
	
	public static function static_listings_shortcode_handler ( $atts, $content = '' ) {
		add_filter('pl_filter_wrap_filter', array( __CLASS__, 'pl_filter_wrap_default_filters' ));
		$filters = '';
		
		// call do_shortcode for all pl_filter shortcodes
		// Note: don't leave whitespace or other non-valuable symbols
		if( ! empty( $content ) ) {
			$filters = do_shortcode( strip_tags( $content ) );
		}
		$filters = str_replace('&nbsp;', '', $filters);
				
		$content = PL_Component_Entity::static_listings_entity( $atts, $filters );
		
		return PL_Shortcode_Wrapper::create( 'static_listings', $content );
	}
	
	public static function post_listing_shortcode_handler ( $atts ) {
		// $shortcode = 'listings';
		// self::$listing = $listing;

		// // ob_start();
		// //   echo pls_dump($listing);
		// // return ob_get_clean();

	 //  	$snippet_body = self::get_active_snippet_body($shortcode);
	 //  	return do_shortcode($snippet_body);
	}


	public static function search_listings_shortcode_handler( $atts, $content )
	{		
		add_filter('pl_filter_wrap_filter', array( __CLASS__, 'pl_filter_wrap_default_filters' ));
		$filters = '';
		
		// call do_shortcode for all pl_filter shortcodes
		// Note: don't leave whitespace or other non-valuable symbols
		if( ! empty( $content ) ) {
			$filters = do_shortcode( strip_tags( $content ) );
		}
		
		$filters = str_replace('&nbsp;', '', $filters);
		
		// Handle attributes using shortcode_atts...
		// These attributes will hand the look and feel of the listing form container, as 
		// the context func applies to each individual listing.
		$content = PL_Component_Entity::search_listings_entity( $atts, $filters );
		
		return PL_Shortcode_Wrapper::create( 'search_listings', $content );
	}

	public static function search_map_shortcode_handler( $atts ) {
		$content = PL_Component_Entity::search_map_entity( $atts );
		
		return PL_Shortcode_Wrapper::create( 'search_map', $content );
	}
	

	public static function pl_neighborhood_shortcode_handler( $atts ) {
		$content = PL_Component_Entity::pl_neighborhood_entity( $atts );
	
		return PL_Shortcode_Wrapper::create( 'pl_neighborhood', $content );
	}

/*** Context Filter Handlers ***/	

	public static function searchform_shortcode_context($form, $form_html, $form_options, $section_title, $form_data)
	{
		$shortcode = 'search_form';
		self::$form_html = $form_html;

		$snippet_body = self::get_active_snippet_body($shortcode);
		return do_shortcode($snippet_body);
	}

	// It's important to note that this is called for every individual listing...
	public static function listings_shortcode_context($item_html, $listing) 
	{
		$shortcode = 'listings';
		self::$listing = $listing;

	  	$snippet_body = self::get_active_snippet_body($shortcode);
	  	return do_shortcode($snippet_body);
	}

	public static function prop_details_shortcode_context($html, $listing_data) 
	{
		// Check to see if this functionality is enabled...
		$enabled = get_option( self::$prop_details_enabled_key, 'false' );
		
		if ($enabled == 'true') 
		{
			$shortcode = 'prop_details';
			self::$listing = $listing_data;

		  	$snippet_body = self::get_active_snippet_body($shortcode);
		  	return do_shortcode($snippet_body);
	  	}
	  	else 
	  	{
	  		// Simply pass on what was originally sent the filter...
	  		return $html;
	  	}
	}


/*** Sub-Shortcode Handlers ***/

	public static function search_form_sub_shortcode_handler ($atts, $content, $tag) 
	{ 
		return isset( self::$form_html[$tag] ) ? self::$form_html[$tag] : '';
	}

	public static function listing_sub_shortcode_handler ($atts, $content, $tag) {
		$content = PL_Component_Entity::listing_sub_entity( $atts, $content, $tag );
		
		return PL_Shortcode_Wrapper::create( 'listing_sub', $content );
	}
	
	public static function listing_slideshow_sub_shortcode_handler ($atts, $content, $tag) {
		$content = PL_Component_Entity::listing_slideshow_sub_entity( $atts, $content, $tag );
	
		return PL_Shortcode_Wrapper::create( 'listing_slideshow_sub', $content );
	}
	
	public static function neighborhood_sub_shortcode_handler ($atts, $content, $tag) {
		$content = PL_Component_Entity::neighborhood_sub_entity( $atts, $content, $tag );
	
		return PL_Shortcode_Wrapper::create( 'neighborhood_sub', $content );
	}
	
	/** Helpcode shortcode handler **/

	/**
	 * Handle filters for listing
	 * 
	 * Expected attributes:
	 * 
	 * group - group="metadata" or group="location", for wrapping filter calls by group
	 * filter - filter="listing_types", filter="zoning_types" and used together with a group call
	 * value - the value of the filter
	 * 
	 * @param unknown_type $atts
	 * @param unknown_type $content
	 */
	public static function pl_filter_shortcode_handler( $atts, $content = '' ) {
		$out = '';
		
		if( !isset( $atts['filter'] ) || ! isset( $atts['value'] ) ) {
			return "";
		}
		
		extract($atts);
		
		if( isset( $group ) ) {
			$filter = $group . '[' . $filter . ']';
		}
		
		return apply_filters('pl_filter_wrap_filter', '{ "name": "' . $filter . '", "value" : "' . $value . '"} ');
	}	
	
	public static function pl_filter_wrap_default_filters( $filter ) {
		return "listings.default_filters.push(" . trim( strip_tags( $filter ) ) . "); ";
	}

	/*** Helper Functions ***/

	public static function get_active_snippet_body($shortcode, $template_name = '')
	{
		// Get snippet ID currently associated with this shortcode...
		$option_key = ('pls_' . $shortcode);
		$snippet_name = get_option($option_key, self::$defaults[$shortcode][0]);

		// Determine if snippet is custom (in DB) or default (stored in flat-file)
		$snippet_DB_key = ('pls_' . $shortcode . '_' . $snippet_name);
		$type = ( get_option($snippet_DB_key) ? 'custom' : 'default' );

		// assign a template as a shortcode arg
		if( ! empty( $template_name ) ) {
			$snippet_name = $template_name;
			if( isset( self::$defaults[$shortcode] ) && in_array( $template_name, self::$defaults[$shortcode] ) ) {
				$type = 'default';
			} else {
				$type = 'custom';
			}
		}
		$snippet_body = PL_Router::load_snippet($shortcode, $snippet_name, $type);
		return $snippet_body;
	}


	public static function init_bootloader () {
		ob_start();
		?>
			<script type="text/javascript">
			jQuery(document).ready(function( $ ) {
				if (typeof bootloader === 'object') {
		  			bootloader.init();
			  	}	
			});
			</script>
		<?php
		echo ob_get_clean();
	}

	function update_shortcode_templates($new_theme) {
			global $did_i_just_change_theme;
	
	}

	function update_shortcode_js ($new_theme) {

		$current_themes = get_themes();
	    $new_theme_info = $current_themes[$new_theme];

		ob_start();
	    ?>
	    	<script type="text/javascript">
	    		console.log(<?php echo json_encode($new_theme_info) ?>);
	    	</script>
	    <?php
	    echo ob_get_clean();		    
	}
	
	/*** Admin Functions ***/
	
	public static function admin_buffer_op($page_hook) {
		add_action('load-'.$page_hook, array(__CLASS__, 'admin_header'));
		add_action('admin_footer-'.$page_hook, array(__CLASS__, 'admin_footer'));
	}	

	public static function admin_header() {
		ob_start();
	}	
	
	public static function admin_footer() {
		ob_end_flush();
	}
	
	public static function debug($var, $lines = 1) {
		echo '<pre>';
		$traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		for ($trace=1; $trace<=$lines; $trace++) {
			echo $traces[$trace]['file'].':'.(!empty($traces[$trace]['class'])?$traces[$trace]['class'].':':'').$traces[$trace]['function'].':'.(!empty($traces[$trace]['line'])?$traces[$trace]['line'].':':'')."\n";
		}
		var_dump($var);
		echo '</pre>';
	}
}

?>