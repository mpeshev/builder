<div class="wrap">
	<?php echo PL_Helper_Header::pl_settings_subpages(); ?>
	<div>
		<div class="header-wrapper">
			<h2>Custom Drawn Areas</h2>
			<div class="ajax_message" id="neighborhood_messages"></div>
		</div>
		<div class="clear"></div>
		<p>Use the map below to outline neighborhoods. Once you've outlined and saved a custom drawn area you can allow your visitors to use it to search or associated it with an area page.</p>
		<div class="polygon_wrapper">
			<div class="show_neighborhood_areas">
				<span>Show Already Drawn Areas:</span>
				<?php echo PL_Taxonomy_Helper::taxonomies_as_checkboxes(); ?>
				<a id="clear_created_neighborhoods"href="#">Hide All</a>
			</div>
			<div class="create_new_wrapper">
				<a href="#" id="create_new_polygon" class="button">Create New Custom Drawn Area</a>
			</div>
			<div class="ajax_message" id="polygon_ajax_messages"></div>
			<div class="clear"></div>
			<div id="polygon_map"></div>
			<div class="map_address">
				<label for="map_address_input">Address</label>
				<input type="text" id="map_address_input">
				<a href="#" id="start_map_address_search" class="button">Search</a>
			</div>
			<div class="polygon_list">
				<?php echo PL_Router::load_builder_partial('settings-polygon-create.php'); ?>				
				<div style="display:none" class="create_prevent_overlay" id="create_prevent_overlay">
					<h2>Click on the Map to Start Drawing</h2>
					<p>Click on the map to start tracing the outline of your custom area.</p>
					<a href="#" id="close_create_overlay" class="button">Cancel</a>
				</div>	
				
					

				<div class="polygons" id="list_of_polygons">
					<table id="polygon_listings_list" class="widefat post fixed placester_properties" cellspacing="0">
					    <thead>
					      <tr>
					        <th><span>Name</span></th>
					        <th><span>Type</span></th>
					        <th><span>Neighborhood</span></th>
					        <th><span></span></th>
					        <th><span></span></th>
					      </tr>
					    </thead>
					    <tbody></tbody>
					    <tfoot>
					      <tr>
					        <th></th>
					        <th></th>
					        <th></th>
					        <th></th>
					        <th></th>
					      </tr>
					    </tfoot>
					  </table>
				</div>
			</div>
		</div>
	</div>
</div>