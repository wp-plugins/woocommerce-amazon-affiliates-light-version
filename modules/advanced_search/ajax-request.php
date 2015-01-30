<?php
add_action('wp_ajax_WooZoneLightCategParameters', 'WooZoneLightCategParameters');
function WooZoneLightCategParameters() {

	global $WooZoneLight;
	
	// retrive the item search parameters
	$ItemSearchParameters = $WooZoneLight->amzHelper->getAmazonItemSearchParameters();
	
	// retrive the item search parameters
	$ItemSortValues = $WooZoneLight->amzHelper->getAmazonSortValues();
	
	$html = array();
	$request = array(
		'categ' => isset($_REQUEST['categ']) ? $_REQUEST['categ'] : '',
		'nodeid' => isset($_REQUEST['nodeid']) ? $_REQUEST['nodeid'] : ''
	);

	$sort = array();

	$sort['relevancerank'] = 'Items ranked according to the following criteria: how often the keyword appears in the description, where the keyword appears (for example, the ranking is higher when keywords are found in titles), how closely they occur in descriptions (if there are multiple keywords), and how often customers purchased the products they found using the keyword.';
	$sort['salesrank'] = "Bestselling";
	$sort['pricerank'] = "Price: low to high";
	$sort['inverseprice'] = "Price: high to low";
	$sort['launch-date'] = "Newest arrivals";
	$sort['-launch-date'] = "Newest arrivals";
	$sort['sale-flag'] = "On sale";
	$sort['pmrank'] = "Featured items";
	$sort['price'] = "Price: low to high";
	$sort['-price'] = "Price: high to low";
	$sort['reviewrank'] = "Average customer review: high to low";
	$sort['titlerank'] = "Alphabetical: A to Z";
	$sort['-titlerank'] = "Alphabetical: Z to A";
	$sort['pricerank'] = "Price: low to high";
	$sort['inverse-pricerank'] = "Price: high to low";
	$sort['daterank'] = "Publication date: newer to older";
	$sort['psrank'] = "Bestseller ranking taking into consideration projected sales.The lower the value, the better the sales.";
	$sort['orig-rel-date'] = "Release date: newer to older";
	$sort['-orig-rel-date'] = "Release date: older to newer";
	$sort['releasedate'] = "Release date: newer to older";
	$sort['-releasedate'] = "Release date: older to newer";
	$sort['songtitlerank'] = "Most popular";
	$sort['uploaddaterank'] = "Date added";
	$sort['-video-release-date'] = "Release date: newer to older";
	$sort['-edition-sales-velocity'] = "Quickest to slowest selling products.";
	$sort['subslot-salesrank'] = "Bestselling";
	$sort['release-date'] = "Sorts by the latest release date from newer to older. See orig-rel-date, which sorts by the original release date.";
	$sort['-age-min'] = "Age: high to low";

	// print the title
	$html[] = '<h2>' . ( $request['categ'] ) . ' Search</h2>';

	// store categ into input, use in search FORM
	$html[] = '<input type="hidden" name="WooZoneLightParameter[categ]" value="' . ( $request['categ'] ) . '" />';

	// Keywords
	$html[] = '<div class="WooZoneLightParameterSection">';
	$html[] = 	'<label>' . __('Keywords', $WooZoneLight->localizationName) .'</label>';
	$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[Keywords]">';
	$html[] = '</div>';

	// Keywords
	$args = array(
		'orderby' 	=> 'menu_order',
		'order' 	=> 'ASC',
		'hide_empty' => 0,
		'post_per_page' => '-1'
	);
	$categories = get_terms('product_cat', $args);

	$html[] = '<div class="WooZoneLightParameterSection">';
	$html[] = 	'<label>' . __('Import in:', $WooZoneLight->localizationName) .'</label>';
	$categories = get_terms('product_cat', $args);
	$html[] = 	'<select name="amzStore-to-category" id="amzStore-to-category" style="width: 200px;">';
	$html[] = 		'<option value="amz">Use category from Amazon</option>';
	if(count($categories) > 0){
		foreach ($categories as $key => $value){
			$html[] = '<option value="' . ( $value->name ) . '">' . ( $value->name ) . '</option>';
		}
	}
	$html[] = '</select>';
	$html[] = '</div>';


	// BrowseNode
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'BrowseNode', $ItemSearchParameters[$request['categ']] ) ){
		
		$nodes = $WooZoneLight->getBrowseNodes( $request['nodeid'] );
		
		//var_dump('<pre>',$nodes,'</pre>'); die;  

		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('BrowseNode', $WooZoneLight->localizationName) .'</label>';

		$html[] = 	'<div id="WooZoneLightGetChildrens">';
		$html[] = 	'<select name="WooZoneLightParameter[node]">';
		$html[] = '<option value="">' . __('All', $WooZoneLight->localizationName) .'</option>';
		foreach ($nodes as $key => $value){
			$html[] = '<option value="' . ( $value['BrowseNodeId'] ) . '">' . ( $value['Name'] ) . '</option>';
		}
		$html[] = 	'</select>';
		$html[] = '</div>';
		//$html[] = 	'<input type="button" class="WooZoneLight-button blue WooZoneLightGetChildNodes" value="' . __('Get Child Nodes', $WooZoneLight->localizationName) .'" style="width: 100px; float: left;position: relative; bottom: -3px;" />';

		$html[] = 	'<div id="WooZoneLightGetChildrens"></div>';
		$html[] = 	'<p>Browse nodes are identify items categories</p>';
		$html[] = '</div>';
	}

	// Brand
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'Brand', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Brand', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[Brand]">';
		$html[] = 	'<p>Name of a brand associated with the item. You can enter all or part of the name. For example, Timex, Seiko, Rolex. </p>';
		$html[] = '</div>';
	}

	// Condition
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'Condition', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Condition', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<select name="WooZoneLightParameter[Condition]">';
		$html[] = 		'<option value="">All Conditions</option>';
		$html[] = 		'<option value="New">New</option>';
		$html[] = 		'<option value="Used">Used</option>';
		$html[] = 		'<option value="Collectible">Collectible</option>';
		$html[] = 		'<option value="Refurbished">Refurbished</option>';
		$html[] = 	'</select>';
		$html[] = 	'<p>Use the Condition parameter to filter the offers returned in the product list by condition type. By default, Condition equals "New". If you do not get results, consider changing the value to "All. When the Availability parameter is set to "Available," the Condition parameter cannot be set to "New."</p>';
		$html[] = '</div>';
	}

	// Manufacturer
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'Manufacturer', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Manufacturer', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[Manufacturer]">';
		$html[] = 	'<p>Name of a manufacturer associated with the item. You can enter all or part of the name.</p>';
		$html[] = '</div>';
	}

	// MaximumPrice
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'MaximumPrice', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Maximum Price', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[MaximumPrice]">';
		$html[] = 	'<p>Specifies the maximum price of the items in the response. Prices are in terms of the lowest currency denomination, for example, pennies. For example, 3241 represents $32.41.</p>';
		$html[] = '</div>';
	}

	// MinimumPrice
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'MinimumPrice', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Minimum Price', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[MinimumPrice]">';
		$html[] = 	'<p>Specifies the minimum price of the items to return. Prices are in terms of the lowest currency denomination, for example, pennies, for example, 3241 represents $32.41.</p>';
		$html[] = '</div>';
	}

	// MerchantId
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'MerchantId', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Merchant Id', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[MerchantId]">';
		$html[] = 	'<p>An optional parameter you can use to filter search results and offer listings to only include items sold by Amazon. By default, Product Advertising API returns items sold by various merchants including Amazon. Use the Amazon to limit the response to only items sold by Amazon.</p>';
		$html[] = '</div>';
	}

	// MinPercentageOff
	if( isset($ItemSearchParameters[$request['categ']]) && in_array( 'MinPercentageOff', $ItemSearchParameters[$request['categ']] ) ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Min Percentage Off', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<input type="text" size="22" name="WooZoneLightParameter[MinPercentageOff]">';
		$html[] = 	'<p>Specifies the minimum percentage off for the items to return.</p>';
		$html[] = '</div>';
	}

	// Sort
	if( $request['categ'] != "All" ){
		$html[] = '<div class="WooZoneLightParameterSection">';
		$html[] = 	'<label>' . __('Sort', $WooZoneLight->localizationName) .'</label>';
		$html[] = 	'<select name="WooZoneLightParameter[Sort]" class="WooZoneLightParameter-sort">';

		$curr_sort = array();
		if(isset($ItemSortValues[$request['categ']])){
			$curr_sort = $ItemSortValues[$request['categ']];
		}

		$first_sort_key = '';
		$first_sort_desc = '';
		$cc = 0; 
		foreach ( $sort as $key => $value ){
			if( isset($curr_sort) && in_array( $key, $curr_sort) ){
				if( $cc == 0 ){
					$first_sort_key = $key;
					$first_sort_desc = $value;
				}

				$html[] = '<option value="'. ( $key ) .'" data-desc="'. ( str_replace('"', "'", $value) ) .'">'. ( $key ) .'</option>';

				$cc++;
			}
		}

		$html[] = 	'</select>';
		$html[] = 	'<p id="WooZoneLightOrderDesc" style="width: 100%;">' . ( "<strong>" . ( $first_sort_key ) . ":</strong> " . $first_sort_desc ) . '</p>';
		$html[] = 	'<p>Means by which the items in the response are ordered.</p>';
		$html[] = '</div>';
	}

	// button
	$html[] = '<input type="submit" value="' . __('Search for items', 'Search for products') . '" class="WooZoneLight-button blue" >';

	die(json_encode(array(
		'status' 	=> 'valid',
		'html'		=> implode("\n", $html)
	)));
}


add_action('wp_ajax_WooZoneLightLaunchSearch', 'WooZoneLightLaunchSearch_callback');
function WooZoneLightLaunchSearch_callback() {
	global $WooZoneLight;

	$plugin_uri = $WooZoneLight->cfg['paths']['plugin_dir_url'] . 'modules/bulk_products_import/';

	$requestData = array(
		'params' => isset($_REQUEST['params']) ? $_REQUEST['params'] : '',
		'page' => isset($_REQUEST['page']) ? (int)($_REQUEST['page']) : '',
		'node' => isset($_REQUEST['node']) ? $_REQUEST['node'] : '',
	);

	$your_products = $WooZoneLight->getAllProductsMeta('array', '_amzASIN');

	$parameters = array();
	parse_str( ( $requestData['params'] ), $parameters);

	if( isset($parameters['WooZoneLightParameter'])) {
		$parameters = $parameters['WooZoneLightParameter'];
	}

	$aaAmazonWS = $WooZoneLight->amzHelper->aaAmazonWS;

	// changing the category to {$requestData['category']} and the response to only images and looking for some matrix stuff.
	$aaAmazonWS
		->category( $parameters['categ'] )
		->page( $requestData['page'] )
		->responseGroup( 'Large' . ( $parameters['categ'] == 'Apparel' ? ',Variations' : '') );
		//->responseGroup( 'Large' );


	// option parameters
	$optionalParameters = $parameters;
	// remove from optional parameters any other unecesarry keys
	$notValidOptional = array('categ', 'Keywords', 'node');
	if( count($optionalParameters) > 0 ){
		foreach ($optionalParameters as $key => $value){
			if( in_array( $key, $notValidOptional) ) unset($optionalParameters[$key]);
		}
	}

	// clear the empty array
	$optionalParameters = array_filter($optionalParameters);

	if( count($optionalParameters) > 0 ){
		$_optionalParameters = array();
		foreach ($optionalParameters as $key => $value){
			$_optionalParameters[$key] = $value;
		}

		// if node is send, chain to request
		if( isset($requestData['node']) && trim($requestData['node']) != "" ){
			$_optionalParameters['BrowseNode'] = $requestData['node'];
		}

		// set the page
		$_optionalParameters['ItemPage'] = $requestData['page'];

		// add optional parameter to query
		$aaAmazonWS->optionalParameters( $_optionalParameters );
	}

	//var_dump('<pre>',$aaAmazonWS,'</pre>'); die;

	// add the search keywords
	$response = $aaAmazonWS->search( $parameters['Keywords'] );

	$requestData['debug_level'] = isset($_REQUEST['debug_level']) ? (int)$_REQUEST['debug_level'] : 0;
	// print some debug if requested
	if( $requestData['debug_level'] > 0 ) {
		if( $requestData['debug_level'] == 1) var_dump('<pre>', $response['Items']['Request'],'</pre>');
		if( $requestData['debug_level'] == 2) var_dump('<pre>', $requestData, $response ,'</pre>');
	}

	if($response['Items']['Request']['IsValid'] == 'False') {

		die('<div class="error" style="float: left;margin: 10px;padding: 6px;">Amazon error id: <bold>' . ( $response['Items']['Request']['Errors']['Error']['Code'] ) . '</bold>: <br /> ' . ( $response['Items']['Request']['Errors']['Error']['Message'] ) . '</div>');
	}
	elseif(count($response['Items']) > 0){

		if (isset($response['Items']['TotalResults']) && $response['Items']['TotalResults'] > 1) {
			$totalPages = ( $parameters['categ'] == 'All' ? 5 : 10 );
	?>
			<div class="WooZoneLight-execution-queue">
				<table class="WooZoneLight-queue-table" width="100%">
					<tbody>
						<tr>
							<td width="100">
								<?php _e('Execution Queue:', $WooZoneLight->localizationName);?>
							</td>
							<td id="WooZoneLight-execution-queue-list"><?php _e('No item(s) yet', $WooZoneLight->localizationName);?></td>
							<td align="right" width="150">
								<a class="WooZoneLight-button green" id="WooZoneLight-advance-import-btn" target="_blank" href="#">Import product(s)</a>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="resultsTopBar">
				<h2>
					Showing <?php echo $requestData['page'];?> - <?php echo $response['Items']["TotalPages"];?> of <span id="WooZoneLight-totalPages"><?php echo $response['Items']["TotalResults"];?></span> Results <em>(The limit from Amazon is <code><?php echo $totalPages;?></code> pages for your search)</em>
				</h2>

				<div class="WooZoneLight-pagination">
					<span>View page:</span>
					<select id="WooZoneLight-page">
						<?php
						for( $p = 1; $p <= $totalPages; $p++ ){
							echo '<option value="' . ( $p ) . '" ' . ( $p == $requestData['page'] ? 'selected' : '' ) . '> ' . ( $p ) . ' </option>';
						}
						?>
					</select>
				</div>
			</div>

		<?php
		}	// don't show paging if total results it's not bigget than 1
			if (isset($response['Items']['Item']) && count($response['Items']['Item']) > 0){
		?>

		<table class="WooZoneLight-items-list" border="0" cellspacing="0" cellpadding="0">
			<thead>
				<tr>
					<th width="30"><input type="checkbox" id="WooZoneLight-items-select-all" /></th>
					<th align="left"><?php _e('Product name', $WooZoneLight->localizationName);?></th>
					<th width="20"><?php _e('Image', $WooZoneLight->localizationName);?></th>
					<th width="80"><?php _e('Price', $WooZoneLight->localizationName);?></th>
					<th width="100"><?php _e('View', $WooZoneLight->localizationName);?></th>
				</tr>
			</thead>
			<tbody>

			<?php
				$cc = 0;
				foreach ($response['Items']['Item'] as $key => $value){

					if($response['Items']['TotalResults'] == 1) {
						$value = $response['Items']['Item'];
					}
					if(($cc + 1) > $response['Items']['TotalResults']) continue;

					$thumb = isset($value['SmallImage']['URL']) ? $value['SmallImage']['URL'] : '';
					if(trim($thumb) == ""){
						// try to find image as first image from image sets
						$thumb = $value['ImageSets']['ImageSet'][0]['SmallImage']['URL'];
					}
					
					$full_img = isset($value['LargeImage']['URL']) ? $value['LargeImage']['URL'] : '';
					if(trim($full_img) == ""){
						// try to find image as first image from image sets
						$full_img = $value['ImageSets']['ImageSet'][0]['LargeImage']['URL'];
					}
					
					$orig_thumb = $thumb;
					//$thumb = $WooZoneLight->image_resize( $thumb, 50, 50, 2);

					$blocked = '';
					if( isset($your_products) && count($your_products) > 0 ){
						if( in_array($value['ASIN'], $your_products) ){
							$blocked = 'blocked"';
						}
					}
		?>

					<tr id="WooZoneLight-item-row-<?php echo $value['ASIN'];?>" class="<?php echo $blocked;?>">
						<td align="center">
							<?php
							if( trim($blocked) == "" ) {
							?>
								<input type="checkbox" class="WooZoneLight-items-select" value="<?php echo $value['ASIN'];?>" />
							<?php
							}else{
								echo '<i style="font-size: 12px;">' . __('Already Imported', $WooZoneLight->localizationName) . '</i>';
							}
							?>
							</td>
						<td><?php echo $value['ItemAttributes']['Title'];?></td>
						<td align="center"><a class="WooZoneLight-tooltip" href="#" data-img="<?php echo $full_img;?>"><img id="WooZoneLight-item-img-<?php echo $value['ASIN'];?>" src="<?php echo $thumb;?>" height="30"></a></td>
						<td align="center">
							<div class="WooZoneLight-item-price-block">
								<?php
									if($parameters['categ'] == 'Apparel'){
										echo isset($value['VariationSummary']['LowestPrice']['FormattedPrice']) ? $value['VariationSummary']['LowestPrice']['FormattedPrice'] : '';
									}else{
										echo isset($value['Offers']['Offer']['OfferListing']['Price']['FormattedPrice']) ? $value['Offers']['Offer']['OfferListing']['Price']['FormattedPrice'] : '';
									}
								?>
							</div>
						</td>
						<td align="center"><a href="<?php echo $value['DetailPageURL'];?>" target="_blank" class="WooZoneLight-button blue"><?php _e('View details', $WooZoneLight->localizationName);?></a></td>
					</tr>
		<?php
				} // end foreach
				echo '</tbody></table>'; // close the table
		} // end if have products

		else{

			if( isset($response['Items']['Request']['Errors']['Error']['Message']) ){
				echo '<div class="WooZoneLight-message error">';
				echo 	$response['Items']['Request']['Errors']['Error']['Message'];
				echo '</div>';
			}
		}
	}
	die(); // this is required to return a proper result
}


add_action('wp_ajax_WooZoneLightGetChildNodes', 'WooZoneLightGetChildNodes');
function WooZoneLightGetChildNodes() {
	global $WooZoneLight;

	$request = array(
		'nodeid' => isset($_REQUEST['ascensor']) ? $_REQUEST['ascensor'] : ''
	);

	$nodes = $WooZoneLight->getBrowseNodes( $request['nodeid'] );

	// Apparel & Accessories

 	$html = array();
	$has_nodes = false;
	//$html[] = '<div class="WooZoneLightParameterSection">';
	$html[] = 	'<select name="WooZoneLightParameter[node]" style="margin: 10px 0px 0px 0px;">';
	$html[] = '<option value="">' . __('All', $WooZoneLight->localizationName) .'</option>';
	foreach ($nodes as $key => $value){
		if( isset($value['BrowseNodeId']) && trim($value['BrowseNodeId']) != "" )
			$has_nodes = true;
			
		$html[] = '<option value="' . ( $value['BrowseNodeId'] ) . '">' . ( $value['Name'] ) . '</option>';
	}
	$html[] = 	'</select>';
	//$html[] = 	'<input type="button" class="WooZoneLight-button blue WooZoneLightGetChildNodes" value="' . __('Get Child Nodes', $WooZoneLight->localizationName) .'" style="width: 100px; float: left;position: relative; bottom: -3px;" />';
	//$html[] = '</div>';
	
	if( $has_nodes == false ){
		$html = array();
	}
	die(json_encode(array(
		'status' 	=> 'valid',
		'html'		=> implode("\n", $html)
	)));
}