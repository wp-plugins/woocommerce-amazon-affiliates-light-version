<?php
/**
 * Init Amazon
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		0.1
 */

// load metabox
if(	is_admin() ) {
	require_once( 'ajax-request.php' );

	/* Use the admin_menu action to define the custom box */
    add_action('admin_menu', 'WooZoneLight_api_search_metabox');

    /* Adds a custom section to the "side" of the product edit screen */
    function WooZoneLight_api_search_metabox() {
		//add_meta_box('WooZoneLight_api_search', 'Search product(s) on Amazon', 'WooZoneLight_api_search_custom_box', 'product', 'normal', 'high');
    }

	/* The code for api search custom metabox */
	function WooZoneLight_api_search_custom_box() {
		global $WooZoneLight;

		$amazon_settings = $WooZoneLight->getAllSettings('array', 'amazon');
		$plugin_uri = $WooZoneLight->cfg['paths']['plugin_dir_url'] . 'modules/amazon/';
	?>
		<link rel='stylesheet' id='WooZoneLight-metabox-css' href='<?php echo $plugin_uri . 'meta-box.css';?>' type='text/css' media='all' />

		<script type='text/javascript' src='<?php echo $plugin_uri . 'meta-box.js';?>'></script>

		</form> <!-- closing the top form -->
			<form id="WooZoneLight-search-form" action="/" method="POST">
			<div style="bottom: 0px; top: 0px;" class="WooZoneLight-shadow"></div>
			<div id="WooZoneLight-search-bar">
				<div class="WooZoneLight-search-content">
					<div class="WooZoneLight-search-block">
						<label for="WooZoneLight-search">Search by Keywords or ASIN:</label>
						<input type="text" name="WooZoneLight-search" id="WooZoneLight-search" value="" />
					</div>

					<div class="WooZoneLight-search-block" style="width: 220px">
						<span class="caption">Category:</span>
						<select name="WooZoneLight-category" id="WooZoneLight-category">
						<?php
							foreach ($WooZoneLight->amazonCategs() as $key => $value){
								echo '<option value="' . ( $value ) . '">' . ( $value ) . '</option>';
							}
						?>
						</select>
					</div>

					<div class="WooZoneLight-search-block" style="width: 320px">
						<span>Import to category:</span>
						<?php
						$args = array(
							'orderby' 	=> 'menu_order',
							'order' 	=> 'ASC',
							'hide_empty' => 0
						);
						$categories = get_terms('product_cat', $args);
						echo '<select name="WooZoneLight-to-category" id="WooZoneLight-to-category" style="width: 200px;">';
						echo '<option value="amz">Use category from Amazon</option>';
						if(count($categories) > 0){
							foreach ($categories as $key => $value){
								echo '<option value="' . ( $value->name ) . '">' . ( $value->name ) . '</option>';
							}
						}
						echo '</select>';
						?>
					</div>

					<input type="submit" class="button-primary" id="WooZoneLight-search-link" value="Search" />
				</form>
				<div id="WooZoneLight-ajax-loader"><img src="<?php echo $plugin_uri;?>assets/ajax-loader.gif" /> searching on <strong>Amazon.<?php echo $amazon_settings['country'];?></strong> </div>
			</div>
		</div>
		<div id="WooZoneLight-results">
			<div id="WooZoneLight-ajax-results"><!-- dynamic content here --></div>
			<div style="clear:both;"></div>
		</div>

		<?php
		if($_REQUEST['action'] == 'edit'){
			echo '<style>#amzStore_shop_products_price, #amzStore_shop_products_markers { display: block; }</style>';
		}
		?>
	<?php
	}
}
require_once( 'product-tabs.php' );