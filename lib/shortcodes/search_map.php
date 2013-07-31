<?php
/**
 * Post type/Shortcode to display Google maps
 *
 */

class PL_Map_CPT extends PL_SC_Base {

	protected $pl_post_type = 'pl_map';

	protected $shortcode = 'search_map';

	protected $title = 'Map';

	protected $options = array(
		'context'		=> array( 'type' => 'select', 'label' => 'Template', 'default' => ''),
		'width'			=> array( 'type' => 'numeric', 'label' => 'Width(px)', 'default' => 600 ),
		'height'		=> array( 'type' => 'numeric', 'label' => 'Height(px)', 'default' => 400 ),
		'widget_class'	=> array( 'type' => 'text', 'label' => 'Widget Class', 'default' => '' ),
//		'type'			=> array( 'type' => 'select', 'label' => 'Map Type',
//				'options' => array('listings' => 'listings', 'lifestyle' => 'lifestyle', 'lifestyle_polygon' => 'lifestyle_polygon' ),
//				'default' => '' ),
	);

	protected $default_tpls = array('twentyten', 'twentyeleven');
	
	protected $template = array(
		'css' => array(
			'type' => 'textarea',
			'label' => 'CSS',
			'css' => 'mime_css',
			'default' => '
/* sample div used to wrap the map plus any additional html */
.my-map {
	overflow: hidden;
	border: 1px solid #000;
}
/* format the map wrapper generated by the plugin */
.my-map .map_wrapper {
	padding: 2% !important;
	width: 96% !important;
	height: 96% !important;
}
.my-map .custom_google_map {
	width: 100% !important;
	height: 100% !important;
}',
			'description' => 'You can use any valid CSS in this field to customize your HTML, which will also inherit the CSS from the theme.'
		),

		'before_widget'	=> array(
			'type' => 'textarea',
			'label' => 'Add content before the map',
			'css' => 'mime_html',
			'default' => '<div class="my-map">',
			'description' => 'You can use any valid HTML in this field and it will appear before the map.
For example, you can wrap the whole map with a <div> element to apply borders, etc, by placing the opening <div> tag in this field and the closing </div> tag in the following field.'
		),

		'after_widget'	=> array(
			'type' => 'textarea',
			'label' => 'Add content after the map',
			'css' => 'mime_html',
			'default' => '</div>',
			'description' => 'You can use any valid HTML in this field and it will appear after the map.'
		),
	);




	public static function init() {
		parent::_init(__CLASS__);
	}
}

PL_Map_CPT::init();
