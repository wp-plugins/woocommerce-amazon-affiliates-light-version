<?php
if(!function_exists('amzStore_bulk_wp_exist_post_by_args')) {
	function amzStore_bulk_wp_exist_post_by_args($args) {
		global $wpdb;
		//$result = $wpdb->get_row("SELECT * FROM " . ( $wpdb->prefix ) . "posts WHERE 1=1 and post_status = '" . ( $args['post_status'] ) . "' and post_title = '" .  ( $args['post_title'] )  . "'", 'ARRAY_A');
		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . ( $wpdb->prefix ) . "posts WHERE 1=1 and post_status = '" . ( $args['post_status'] ) . "' and post_title = %s", $args['post_title'] ), 'ARRAY_A' );
		if(count($result) > 0){
			return $result;
		}
		return false;
	}
}
add_action('wp_ajax_WooZoneLight_load_product', 'WooZoneLight_load_product_callback');
function WooZoneLight_load_product_callback() {
	global $WooZoneLight;

	$amazon_settings = $WooZoneLight->getAllSettings('array', 'amazon');
	$plugin_uri = $WooZoneLight->cfg['paths']['plugin_dir_url'] . 'modules/amazon/';

	$requestData = array(
		'ASIN' => isset($_REQUEST['ASIN']) ? htmlentities($_REQUEST['ASIN']) : '',
		'to-category' => isset($_REQUEST['to-category']) ? htmlentities($_REQUEST['to-category']) : 'amz'
	);

	// load the amazon webservices client class
	require_once( $WooZoneLight->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php');

	// create new amazon instance
	$aaAmazonWS = new aaAmazonWS(
		$amazon_settings['AccessKeyID'],
		$amazon_settings['SecretAccessKey'],
		$amazon_settings['country'],
		$amazon_settings['AffiliateId']
	);

	// create request by ASIN
	$product = $aaAmazonWS->responseGroup('Large')->optionalParameters(array('MerchantId' => 'All'))->lookup($requestData['ASIN']);
	if($product['Items']["Request"]["IsValid"] == "True"){
		$thisProd = $product['Items']['Item'];
		if(count($product['Items']['Item']) > 0){
			// start creating return array
			$retProd = $retProd['images'] = array();

			// product large image
			$retProd['images'][] = $thisProd['LargeImage']['URL'];

			$retProd['ASIN'] = $thisProd['ASIN'];

			// get gallery images
			if(count($thisProd['ImageSets']) > 0){
				$count = 0;
				foreach ($thisProd['ImageSets']["ImageSet"] as $key => $value){
					if($count > 5) continue;
					if( isset($value['LargeImage']['URL']) && $count > 0 ){
						$retProd['images'][] = $value['LargeImage']['URL'];
					}
					$count++;
				}
			}

			// set other ItemAttributes

			// CustomerReviews url
			if($thisProd['CustomerReviews']['HasReviews']){
				$retProd['CustomerReviewsURL'] = $thisProd['CustomerReviews']['IFrameURL'];
			}

			// DetailPageURL
			$retProd['DetailPageURL'] = $thisProd['DetailPageURL'];

			// ItemLinks
			$retProd['ItemLinks'] = $thisProd['ItemLinks'];

			// product title
			$retProd['Title'] = $thisProd['ItemAttributes']['Title'];

			// Binding
			$retProd['Binding'] = $thisProd['ItemAttributes']['Binding'];

			// ProductGroup
			$retProd['ProductGroup'] = $thisProd['ItemAttributes']['ProductGroup'];

			// SKU
			$retProd['SKU'] = $thisProd['ItemAttributes']['SKU'];

			// Feature
			$retProd['Feature'] = $thisProd['ItemAttributes']['Feature'];

			// price (OfferSummary) //['Offers']
			$retProd['price'] = array(
				'Amount' => $thisProd['Offers']['Offer']['OfferListing']['Price']['Amount'],
				'FormattedPrice' => preg_replace( "/[^0-9,.]/", "", $thisProd['Offers']['Offer']['OfferListing']['Price']['FormattedPrice'] )
			);

			// EditorialReviews
			$retProd['EditorialReviews'] = $thisProd['EditorialReviews']['EditorialReview']['Content'];

			if($_REQUEST['dump'] == '1'){
				var_dump('<pre>', $retProd, $thisProd ,'</pre>'); die;
			}

			$prod_id = $WooZoneLight->addNewWooProduct($retProd);

			// now return everythink as json
			die(json_encode(array(
				'status' 		=> 'valid',
				'prod_id'		=> $prod_id,
				'redirect_url'	=> sprintf(admin_url('/post.php?post=%s&action=edit'), $prod_id)
			)));
		}
	}else{
		die(json_encode(array(
			'status' => 'invalid',
			'msg' => "Can't get product by given ASIN: " . $requestData['ASIN']
		)));
	}
}

add_action('wp_ajax_amazon_request', 'WooZoneLightamazon_request_callback');
function WooZoneLightamazon_request_callback() {
	global $WooZoneLight;

	$amazon_settings = $WooZoneLight->getAllSettings('array', 'amazon');
	$plugin_uri = $WooZoneLight->cfg['paths']['plugin_dir_url'] . 'modules/amazon/';

	$requestData = array(
		'search' => isset($_REQUEST['search']) ? htmlentities($_REQUEST['search']) : '',
		'category' => isset($_REQUEST['category']) ? htmlentities($_REQUEST['category']) : '',
		'page' => isset($_REQUEST['page']) ? (int)($_REQUEST['page']) : ''
	);

	// load the amazon webservices client class
	require_once( $WooZoneLight->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php');

	// create new amazon instance
	$aaAmazonWS = new aaAmazonWS(
		$amazon_settings['AccessKeyID'],
		$amazon_settings['SecretAccessKey'],
		$amazon_settings['country'],
		$amazon_settings['AffiliateId']
	);

	// changing the category to {$requestData['category']} and the response to only images and looking for some matrix stuff.
	$response = $aaAmazonWS->category($requestData['category'])->page($requestData['page'])->responseGroup('Large')->search($requestData['search']);

	// print some debug if requested
	if($_GET['dump'] == 1 && is_admin()) {
		var_dump('<pre>', $requestData, $response ,'</pre>'); die;
	}

	if($response['Items']['Request']['IsValid'] == 'False') {

		die('<div class="error" style="float: left;margin: 10px;padding: 6px;">Amazon error id: <bold>' . ( $response['Items']['Request']['Errors']['Error']['Code'] ) . '</bold>: <br /> ' . ( $response['Items']['Request']['Errors']['Error']['Message'] ) . '</div>');
	}
	elseif(count($response['Items']) > 0){

		if($response['Items']['TotalResults'] > 1) {
	?>
			<div class="resultsTopBar">
				<h2>
					Showing <?php echo $requestData['page'];?> - <?php echo $response['Items']["TotalPages"];?> of <span id="WooZoneLight-totalPages"><?php echo $response['Items']["TotalResults"];?></span> Results
				</h2>

				<div class="WooZoneLight-pagination">
					<span>View page:</span>
					<select id="WooZoneLight-page">
						<?php
						for( $p = 1; $p <= 5; $p++ ){
							echo '<option value="' . ( $p ) . '" ' . ( $p == $requestData['page'] ? 'selected' : '' ) . '> ' . ( $p ) . ' </option>';
						}
						?>
					</select>
				</div>
			</div>

		<?php
		}	// don't show paging if total results it's not bigget than 1
			if(count($response['Items']['Item']) > 0){
				echo '
				<div class="WooZoneLight-product-box">
					<table class="product">
				';
				$cc = 0;

				foreach ($response['Items']['Item'] as $key => $value){

					if($response['Items']['TotalResults'] == 1) {
						$value = $response['Items']['Item'];
						if($_REQUEST['dump'] == 1){
							var_dump('<pre>',$value ,'</pre>'); die;
						}
					}
					if(($cc + 1) > $response['Items']['TotalResults']) continue;

					$thumb = $value['SmallImage']['URL'];
					if(trim($thumb) == ""){
						// try to find image as first image from image sets
						$thumb = $value['ImageSets']['ImageSet'][0]['SmallImage']['URL'];
					}
		?>
					<tr>
						<td class="product-number"><?php echo ++$cc;?>.</td>
						<td class="product-image">
							<a href="<?php echo $value['DetailPageURL'];?>" target="_blank">
								<img class="productImage" src="<?php echo $thumb;?>">
							</a>
						</td>
						<td class="product-data">
							<h4 class="product-title">
								<a href="<?php echo $value['DetailPageURL'];?>" target="_blank"><?php echo $value['ItemAttributes']['Title'];?></a>
							</h4>
							<div class="newPrice">
								<span class="price"><?php echo $value['Offers']['Offer']['OfferListing']['Price']['FormattedPrice'];?></span>
							</div>
							<div class="product-description"><?php echo $value['EditorialReviews']['EditorialReview']['Content'];?></div>
						</td>
						<td class="product-options">
							<?php
								if($value['CustomerReviews']['HasReviews'] == true){
									echo '<a class="thickbox WooZoneLight-option-btn" href="' . ( $value['CustomerReviews']['IFrameURL'] ) . '&TB_iframe=true"> <img src="'. ( $plugin_uri ) .'assets/comments.png" /> Customer Reviews</a>';
								}
							?>
							<a class="WooZoneLight-option-btn WooZoneLight-load-product" href="#" rel="<?php echo $value['ASIN'];?>"><img src="<?php echo $plugin_uri;?>assets/update.png" /> Load this product </a>
						<td>
					</tr>
		<?php
				} // end foreach
				echo '</table></div>'; // close the table
		} // end if have products
	}
	die(); // this is required to return a proper result
}