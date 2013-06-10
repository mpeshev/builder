<?php
/**
 * The custom shortcodes are stored as a custom post type of pl_general_widget.
 *
 */
class PL_General_Widget_CPT extends PL_Post_Base {

	private static $allowable_tags = "<a><p><script><div><span><section><label><br><h1><h2><h3><h4><h5><h6><scr'+'ipt><style><article><ul><ol><li><strong><em><button><aside><blockquote><footer><header><form><nav><input><textarea><select>";

	// Leverage the PL_Form class and it's fields format (and implement below)
	// map short codes to title
	// IMPORTANT: shortcode name cannot be a substr of another shortcode name
	public static $codes = array(
			'search_map' => 'Search Map',
			'search_form' => 'Search Form',
			'search_listings' => 'Search Listings',
			'listing_slideshow' => 'Listings Slideshow',
			'featured_listings' => 'Featured Listings',
			'static_listings' => 'List of Listings',
			// 'pl_neighborhood' => 'Neighborhood'
		);
	// map post sub type to title
	public static $post_types =  array(
			'pl_map' => 'Map',
			'pl_form' => 'Search Form',
			'pl_search_listings' => 'Search Listings',
			'pl_slideshow' => 'Slideshow',
			'static_listings' => 'List of Listings',
			// 'pl_neighborhood' => 'Neighborhood'
		);
	
	// default
	public static $default_post_type = 'pl_map';

	public static $fields = array(
			'width' => array( 'type' => 'text', 'label' => 'Width', 'css' => 'pl_map pl_form pl_search_listings pl_slideshow pl_neighborhood featured_listings static_listings' ),
			'height' => array( 'type' => 'text', 'label' => 'Height', 'css' => 'pl_map pl_form pl_search_listings pl_slideshow pl_neighborhood featured_listings static_listings' ),
			'animation' => array( 'type' => 'select', 'label' => 'Animation', 'options' => array(
					'fade' => 'fade',
					'horizontal-slide' => 'horizontal-slide',
					'vertical-slide' => 'vertical-slide',
					'horizontal-push' => 'horizontal-push',
				), 'css' => 'pl_slideshow' ),
			'animationSpeed' => array( 'type' => 'text', 'label' => 'Animation Speed', 'css' => 'pl_slideshow' ),
			'timer' => array( 'type' => 'checkbox', 'label' => 'Timer', 'css' => 'pl_slideshow' ),
			'pauseOnHover' => array( 'type' => 'checkbox', 'label' => 'Pause on hover', 'css' => 'pl_slideshow' ),
			'hide_sort_by' => array( 'type' => 'checkbox', 'label' => 'Hide Sort By dropdown', 'css' => 'pl_static_listings' ),
			'form_action_url' => array( 'type' => 'text', 'label' => 'Form Address', 'css' => 'pl_form' ),
			'hide_sort_direction' => array( 'type' => 'checkbox', 'label' => 'Hide Sort Direction', 'css' => 'pl_static_listings' ),
			'hide_num_results' => array( 'type' => 'checkbox', 'label' => 'Hide Show Number of Results', 'css' => 'pl_static_listings' ),
 			'num_results_shown' => array( 'type' => 'text', 'label' => 'Number of Results Displayed', 'css' => 'pl_static_listings' ),
			'widget_class' => array( 'type' => 'text', 'label' => 'Widget Class', 'css' => 'pl_map pl_form pl_search_listings pl_slideshow pl_neighborhood featured_listings static_listings' ),
		);

	public function register_post_type() {
		$args = array(
			'labels' => array(
				'name' => __( 'Placester Widget', 'pls' ),
				'singular_name' => __( 'pl_map', 'pls' ),
				'add_new_item' => __('Add New Placester Widget', 'pls'),
				'edit_item' => __('Edit Placester Widget', 'pls'),
				'new_item' => __('New Placester Widget', 'pls'),
				'all_items' => __('All Placester Widgets', 'pls'),
				'view_item' => __('View Placester Widgets', 'pls'),
				'search_items' => __('Search Placester Widgets', 'pls'),
				'not_found' =>  __('No widgets found', 'pls'),
				'not_found_in_trash' => __('No widgets found in Trash', 'pls')),
			'menu_icon' => trailingslashit(PL_IMG_URL) . 'logo_16.png',
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array('title'),
		);

		register_post_type('pl_general_widget', $args );
	}

	public function __construct() {
		parent::__construct();

		add_action( 'save_post', array( $this, 'meta_box_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_head', array( $this, 'admin_head_plugin_path' ) );
		add_filter( 'manage_edit-pl_general_widget_columns' , array( $this, 'widget_edit_columns' ) );
 		add_filter( 'manage_pl_general_widget_posts_custom_column', array( $this, 'widget_custom_columns' ) );
		add_action( 'wp_ajax_autosave', array( $this, 'autosave_refresh_iframe' ), 1 );
		add_action( 'wp_ajax_autosave_widget', array( $this, 'autosave_save_post_for_iframe' ) );
		add_action( 'wp_ajax_autosave_widget_template', array( $this, 'autosave_save_template' ) );
		add_action( 'wp_ajax_handle_widget_script', array( $this, 'handle_iframe_cross_domain' ) );
		add_action( 'wp_ajax_nopriv_handle_widget_script', array( $this, 'handle_iframe_cross_domain' ) );
		// add_filter( 'pl_form_section_after', array( $this, 'filter_form_section_after' ), 10, 3 );
		add_filter('post_row_actions', array( $this, 'remove_quick_edit_view'), 10, 1 );
		add_action( 'restrict_manage_posts', array( $this, 'listing_posts_add_filter_widget_type' ) );
		add_filter( 'parse_query', array( $this, 'widget_type_posts_filter' ) );
		add_filter( 'get_edit_post_link', array( $this, 'shortcode_edit_link' ), 10, 3);
	}

	/**
	 * Handle cross-domain script insertion and pass back to the embedded script for the widget
	 */
	public function handle_iframe_cross_domain() {
		// don't process if widget ID is missing
 		if( ! isset( $_GET['id'] ) ) {
 			die();
 		}

 		// defaults
 		$args['width'] = '250';
 		$args['height'] = '250';

 		// get the post and the meta
 		$post_id = $_GET['id'];
		$meta = get_post_custom( $post_id );

		// default GET should have at least id, callback and action
		$ignore_array = array(
			'pl_static_listings_option',
			'pl_featured_listings_option',
		);

		foreach( $meta as $key => $value ) {
			// ignore several options that we don't need to pass
			if( ! in_array( $key, $ignore_array ) ) {
				// ignore underscored private meta keys from WP
				if( strpos( $key, '_', 0 ) !== 0 && is_array( $value ) && ! empty( $value[0] ) ) {
					$args[$key] = $value[0];
				}
			}
		}

		$args['width'] = ! empty( $_GET['width'] ) ? $_GET['width'] : $args['width'];
		$args['height'] = ! empty( $_GET['height'] ) ? $_GET['height'] : $args['height'];
		$args['widget_class'] = ! empty( $meta['widget_class'] ) && is_array( $meta['widget_class'] ) ? $meta['widget_class'][0] : '';

		unset( $args['action'] );
		unset( $args['callback'] );

		$args['post_id'] = $_GET['id'];

		if( isset( $args['widget_original_src'] ) ) {
			$args['widget_url'] =  $args['widget_original_src'] . '/?p=' . $_GET['id'];
			unset( $args['widget_original_src'] );
		} else {
			$args['widget_url'] =  home_url() . '/?p=' . $_GET['id'];
		}

		header("content-type: application/javascript");
		echo $_GET['callback'] . '(' . json_encode( $args ) . ');';
	}

	public function meta_box_save( $post_id ) {
		// Avoid autosaves
 		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		// Verify nonces for ineffective calls
		if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['meta_box_nonce'], 'pl_cpt_meta_box_nonce' ) ) return;

		// if our current user can't edit this post, bail
		// if( !current_user_can( 'edit_post' ) ) return;

		$pl_post_type = $_POST['pl_post_type'];

		// This should be a determined widget type already.
		if( $pl_post_type === 'pl_general_widget' ) {
			return;
		}

		// Fetch the context template
		$context_template = self::get_context_template( $pl_post_type );
		if( isset( $_POST['pl_template_' . $context_template ] ) ) {
			update_post_meta( $post_id, 'pl_cpt_template', $_POST['pl_template_' . $context_template] );
		} else if( isset( $_POST['pl_cpt_template'] ) && ! empty( $_POST['pl_cpt_template'] ) ) {
			update_post_meta( $post_id, 'pl_cpt_template', $_POST['pl_cpt_template'] );
		}

		// Send the before/after snippets for the template
		if( ! empty( $_POST['pl_template_before_block'] ) ) {
	 		update_post_meta( $post_id, 'pl_template_before_block', $_POST['pl_template_before_block'] );
		}
		if( ! empty( $_POST['pl_template_after_block'] ) ) {
			update_post_meta( $post_id, 'pl_template_after_block', $_POST['pl_template_after_block'] );
		}

		if( $pl_post_type === 'featured_listings' ||  $pl_post_type === 'static_listings') {
			pl_featured_listings_meta_box_save( $post_id );
		}

		if( $pl_post_type === 'pl_slideshow') {
			if( isset( $_POST['pl_featured_listing_meta'] ) ) {
				update_post_meta( $post_id, 'pl_featured_listing_meta',  $_POST['pl_featured_listing_meta'] );
			}
		}

		update_post_meta( $post_id, 'pl_post_type', $pl_post_type );

		foreach( self::$fields as $field => $values ) {
			if( $values['type'] === 'checkbox' && ! isset( $_POST[$field] ) ) {
				update_post_meta( $post_id, $field, false );
			} else if( isset( $_POST[$field] ) ) {
				if( $field != 'pl_cpt_template' ) {
					update_post_meta( $post_id, $field, $_POST[$field] );
				}
			}
		}

		if( isset( $_POST['radio-type'] ) ) {
			$radio_type = $_POST['radio-type'];
			$select_type = 'nb-id-select-' . $radio_type;
			if( isset( $_POST[$select_type] ) ) {
				// persist radio box storage based on what is saved
				update_post_meta( $post_id, 'radio-type', $_POST['radio-type'] );
				update_post_meta( $post_id, 'nb-select-' . $radio_type, $_POST[ $select_type ] );
			}
		}

		if( isset( $_POST['pl_featured_listing_meta'] ) ) {
			update_post_meta( $post_id, 'pl_featured_listing_meta',  $_POST['pl_featured_listing_meta'] );
		}
	}

	public function post_type_templating( $single ) {

		$post = get_queried_object();

		if( empty( $post ) || ! isset( $post->post_type ) ) {
			return $single;
		}

		if( ! in_array( $post->post_type, PL_Post_Type_Manager::$post_types )
				&& 'pl_general_widget' !== $post->post_type ) {
			return $single;
		}

		if( ! empty( $post ) ) {
			// map the post type from the meta key (as we use a single widget here)
			$post_type = get_post_meta($post->ID, 'pl_post_type', true);
			$post->post_type = $post_type;
		}
		$skipdb = false;
		// if( !empty ( $_GET['skipdb'] ) && $_GET['skipdb'] == 'true' ) {
		if( isset( $_GET['action'] ) && isset( $_GET['id'] ) && count( $_GET ) > 3 ) {
			$skipdb = true;
		}

		if( ! empty( $post ) ) {
			// TODO: make a more thoughtful loop here, interfaces or so
			if( $post->post_type == 'pl_map' ) {
				PL_Map_CPT::post_type_templating( $single, $skipdb );
			} else if( $post->post_type == 'pl_form' ) {
				PL_Form_CPT::post_type_templating( $single, $skipdb );
			} else if( $post->post_type == 'pl_slideshow' ) {
				PL_Slideshow_CPT::post_type_templating( $single, $skipdb );
			} else if( $post->post_type == 'pl_search_listings' ) {
				PL_Search_Listing_CPT::post_type_templating( $single, $skipdb );
			} else if( $post->post_type == 'pl_neighborhood' ) {
				PL_Neighborhood_CPT::post_type_templating( $single, $skipdb );
			} else if( $post->post_type == 'featured_listings' ) {
				$this->prepare_featured_template( $single, $skipdb );
			} else if( $post->post_type == 'static_listings' ) {
				$this->prepare_static_template( $single, $skipdb );
			}
		}
		// Silence is gold.
	}

	public function admin_styles( $hook ) {
	}

	public function admin_head_plugin_path( ) {
	?>
		<script type="text/javascript">
			var placester_plugin_path = '<?php echo PL_PARENT_URL; ?>';
		</script>
	<?php
	}

	public function widget_edit_columns( $columns ) {
		$new_columns = array();
		$new_columns['cb'] = $columns['cb'];
		$new_columns['title'] = $columns['title'];
		$new_columns['type'] = "Widget";
		$new_columns['date'] = $columns['date'];

		return $new_columns;
	}

	public function widget_custom_columns( $column ) {
		global $post;
		$widget_type = get_post_meta( $post->ID, 'pl_post_type', true );

		switch ($column) {
			case "type":
				if( ! empty( $widget_type ) ) {
					echo PL_Post_Type_Manager::get_post_type_title_helper( $widget_type );
				}
				break;
		}
	}

 	public function autosave_refresh_iframe( ) {
		if ( isset($_POST['pl_post_type']) ) {
			$id = isset( $_POST['post_ID'] ) ? (int) $_POST['post_ID'] : 0;

			if ( ! $id )
				wp_die( -1 );

			if( ! headers_sent() ):
				?>
					<script type="text/javascript">
						jQuery('#post').trigger('submit');
					</script>
				<?php
			endif;
		}
	}

	public static function autosave_save_template()
	{
		if ($_POST['shortcode'] && $_POST['title'] && $_POST['snippets']) {

			// Format & sanitize snippet_body...
			$snippets = array();
			parse_str($_POST['snippets'], $snippets);
			foreach($snippets as $snippet=>&$snippet_body) {
				$snippet_body = preg_replace('/<\?.*?(\?>|$)/', '', strip_tags($snippet_body, self::$allowable_tags));
				$snippet_body = htmlentities($snippet_body, ENT_QUOTES);
			}

			$id = self::save_shortcode_template($_POST['shortcode'], $_POST['title'], $snippets);
			echo json_encode(array('unique_id' => $id));

			// Blow-out the cache so the changes to the snippet can take effect...
			PL_Cache::clear();
		} else {
			echo array();
		}

		die();
	}

	// Helper function for featured listings
	// They are already available via other UI
	private function prepare_featured_template( $single ) {
		global $post;

		if( ! empty( $post ) && $post->post_type === 'featured_listings' ) {
			$meta = get_post_meta( $post->ID );
			$args = '';

			if( ! empty( $meta['pl_cpt_template'] ) ) {
				$args .= 'template="' . $meta['pl_cpt_template'][0] . '"';
			}

			$shortcode = '[featured_listings id="' . $post->ID . '" '. $args . ']';
			include PL_LIB_DIR . '/post_types/pl_post_types_template.php';

			die();
		}
	}

	// Helper function for static listings
	// They are already available via other UI
	private function prepare_static_template( $single ) {
		global $post;

		if( ! empty( $post ) && $post->post_type === 'static_listings' ) {

			$meta = get_post_meta( $post->ID );
			$args = '';
				
			if( ! empty( $meta['pl_cpt_template'] ) ) {
				$args .= 'template="static_listings_' . $meta['pl_cpt_template'][0] . '"';
			}
			if( ! empty( $meta['num_results_shown'] ) && $meta['num_results_shown'][0]) {
				$args .= sprintf( ' query_limit="%s"', $meta['num_results_shown'][0] );
			}
			if( ! empty( $meta['hide_num_results'] ) ) {
				$args .= sprintf( ' hide_num_results="%s"', $meta['hide_num_results'][0] );
			}
			if( ! empty( $meta['hide_sort_by'] ) ) {
				$args .= sprintf( ' hide_sort_by="%s"', $meta['hide_sort_by'][0] );
			}
			if( ! empty( $meta['hide_sort_direction'] ) ) {
				$args .= sprintf( ' hide_sort_direction="%s"', $meta['hide_sort_direction'][0] );
			}

			$shortcode = '[static_listings id="' . $post->ID . '" ' . $args . ']';

			include PL_LIB_DIR . '/post_types/pl_post_types_template.php';

			die();
		}
	}

	// Autosave function when any of the input fields is called
	public function autosave_save_post_for_iframe( ) {
		if( ! empty ($_POST['post_id'] ) ) {
			$post_id = (int) $_POST['post_id'];
			$pl_post_type = ! empty( $_POST['pl_post_type'] ) ? $_POST['pl_post_type'] : self::$default_post_type;

			if( $pl_post_type === 'featured_listings'
				|| $pl_post_type === 'static_listings'
				|| $pl_post_type === 'pl_static_listings'
				|| $pl_post_type === 'pl_search_listings') {
				pl_featured_listings_meta_box_save( $post_id );
			}
// 			if( $pl_post_type === 'pl_neighborhood' ) {
//				$this->meta_box_save( $post_id );
// 			}

			if ( isset( $_POST['post_title'] )) {
				wp_insert_post( array( 'ID'=>$post_id, 'post_type'=>'pl_general_widget', 'post_title'=>$_POST['post_title'] ) );
			}

			update_post_meta($post_id, 'pl_post_type', $pl_post_type);
			//TODO: update template list in view
			$shortcode = self::get_context_template($pl_post_type);
			echo json_encode(array('snippet_list'=>self::template_list($shortcode)));
		}

 		die();
	}

	public function update_template_block_styles( ) {
		ob_start();
	?>
	<style type="text/css">
		.snippet_container {
			width: 400px;
			margin-top: 0px;
		}
		.shortcode_container {
			width: 100%;
		}
	</style>
	<?php
		echo ob_get_clean();
	}

	public static function get_context_template( $post_type ) {
		switch( $post_type ) {
			case 'pl_search_listings':		return 'search_listings';
			case 'pl_map':					return 'search_map';
			case 'pl_form':					return 'search_form';
			case 'pl_slideshow':			return 'listing_slideshow';
			case 'pl_static_listings':		return 'static_listings';

			// for all the others with the same name
			default:
				return $post_type;
		}
	}

	public function filter_form_section_after( $form, $index, $count ) {
		if( $index < $count ) {
			return $form . '<div class="section-after"></div>';
		}
		return $form;
	}

	/**
	 * Remove quick edit and view
	 */
	public function remove_quick_edit_view( $actions ) {
		global $post;

		if( $post->post_type === 'pl_general_widget' ) {
			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['view'] );
		}
		return $actions;
	}

	/**
	 * Display widget types filter
	 */
	public function listing_posts_add_filter_widget_type($arg = '') {
		$type = 'pl_general_widget';
		if ($type != $arg && (! isset( $_GET['post_type'] ) || $_GET['post_type'] != 'pl_general_widget')) {
			return;
		}

		$values = array_flip( self::$post_types );
		?>
        <select name="pl_widget_type">
        <option value="">All widget types</option>
        <?php
            $current_v = isset($_GET['pl_widget_type'])? $_GET['pl_widget_type']:'';
            foreach ($values as $label => $value) {
                printf
                    (
                        '<option value="%s"%s>%s</option>',
                        $value,
                        $value == $current_v? ' selected="selected"':'',
                        $label
                    );
                }
        ?>
        </select>
        <?php
	}

	/**
	 * Filter by widget types
	 */
	public function widget_type_posts_filter( $query ) {
		global $pagenow;
		$type = 'pl_general_widget';

		if ( is_admin() && ! empty( $_GET['pl_widget_type'] ) ) {
			$query->query_vars['meta_key'] = 'pl_post_type';
			$query->query_vars['meta_value'] = $_GET['pl_widget_type'];
		}
	}

	public function shortcode_edit_link($url, $ID, $context) {
		global $pagenow;
		if (get_post_type($ID) == 'pl_general_widget') {
			if ($pagenow == 'admin.php') {
				return admin_url('admin.php?page=placester_shortcodes_shortcode_edit&post='.$ID);
			}
			elseif ($pagenow == 'post.php') {
				return admin_url('admin.php?page=placester_shortcodes');
			}
		}
		return $url;
	}


	/* Template storage functions */


	public static function save_shortcode_template($shortcode, $title, $data) {
		if (empty(self::$codes[$shortcode])) {
			return '';
		}
		$snippet_DB_key = ('pls_' . $shortcode . '__' . $title);
		$data = array_merge(array('before_widget'=>'','after_widget'=>'','snippet_body'=>'','widget_css'=>''), (array)$data);
		update_option($snippet_DB_key, $data);

		// Add to the list of custom snippet IDs for this shortcode...
		$snippet_list_DB_key = ('pls_' . $shortcode . '_list');
		$snip_arr = get_option($snippet_list_DB_key, array()); // If it doesn't exist, create a blank array to append...
		$snip_arr[] = $title;
		$snip_arr = array_unique($snip_arr);

		// Update (or add) list in (to) DB...
		update_option($snippet_list_DB_key, $snip_arr);

		return $snippet_DB_key;
	}

	public static function delete_shortcode_template($id) {
		$parts = explode('-', $id, 2);
		if (count($parts) != 2 || empty(self::$codes[$parts[0]])) {
			return '';
		}
		$snippet_DB_key = ('pls_' . $parts[0] . '__' . $parts[1]);
		delete_option($snippet_DB_key);

		// Add to the list of custom snippet IDs for this shortcode...
		$snippet_list_DB_key = ('pls_' . $parts[0] . '_list');
		$snip_arr = get_option($snippet_list_DB_key, array()); // If it doesn't exist, create a blank array to append...
		unset($snip_arr[$parts[1]]);

		// Update (or add) list in (to) DB...
		update_option($snippet_list_DB_key, $snip_arr);
	}

	public static function load_shortcode_template($shortcode, $title) {
		$snippet_DB_key = 'pls_' . $shortcode . '__' . $title;
		$vals = array('before_widget'=>'','after_widget'=>'','snippet_body'=>'Cannot find custom snippet...','widget_css'=>'');
		return get_option($snippet_DB_key, $vals);
	}

	public static function template_list($shortcode) {
		// Get list of custom snippet ids for this shortcode...
		$snippet_list_DB_key = ('pls_' . $shortcode . '_list');
		$snip_arr = get_option($snippet_list_DB_key, array());

		$default_snippets = !empty(PL_Shortcodes::$defaults[$shortcode]) ? PL_Shortcodes::$defaults[$shortcode] : array();

		$snippet_type_map = array();

		foreach ($default_snippets as $snippet) {
			$snippet_type_map[$snippet] = 'default';
		}

		// Add Custom snippets..
		foreach ($snip_arr as $snippet) {
			$snippet_type_map[$snippet] = 'custom';
		}

		return $snippet_type_map;
	}
}

new PL_General_Widget_CPT();
