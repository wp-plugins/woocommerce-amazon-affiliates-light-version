<?php 
/**
 *	Author: AA-Team
 *	Name: 	http://codecanyon.net/user/AA-Team/portfolio
 *	
**/
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLightAmazonHelper') != true) {
	class WooZoneLightAmazonHelper extends WooZoneLight 
	{
		private $the_plugin = null;
		public $aaAmazonWS = null;
		private $amz_settings = array();
		
		static protected $_instance;
		
		
		/* The class constructor
		=========================== */
		public function __construct( $the_plugin=array() ) 
		{
			$this->the_plugin = $the_plugin; 
			
			// get all amazon settings options
			$this->amz_settings = @unserialize( get_option( $this->alias . '_amazon' ) ); 
  
			// create a instance for amazon WS connections
			$this->setupAmazonWS();
			
			// ajax actions
			add_action('wp_ajax_WooZoneLightCheckAmzKeys', array( $this, 'check_amazon') );
			add_action('wp_ajax_WooZoneLightImportProduct', array( &$this, 'getProductDataFromAmazon' ));
			
			add_action('wp_ajax_WooZoneLightStressTest', array( &$this, 'stress_test' ));
		}
		
		/**
	    	* Singleton pattern
	    	*
	    	* @return pspGoogleAuthorship Singleton instance
	    	*/
		static public function getInstance( $the_plugin=array() )
		{
			if (!self::$_instance) {
				self::$_instance = new self( $the_plugin );
			}

			return self::$_instance;
		}
		
		public function stress_test()
		{
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';
			$return = array();
			
			$start = microtime(true);
			 
			//header('HTTP/1.1 500 Internal Server Error');
			//exit();
			
			if (!isset($_SESSION)) {
				 session_start(); 
			}
			
			if( $action == 'import_images' ){
				
				if( isset($_SESSION["WooZoneLight_test_product"]) && count($_SESSION["WooZoneLight_test_product"]) > 0 ){
					$product = $_SESSION["WooZoneLight_test_product"];

					$this->set_product_images( $product, $product['local_id'] );
					$return = array( 
						'status' => 'valid',
						'log' => "Images added for product: " . $product['local_id'],
						'execution_time' => number_format( microtime(true) - $start, 2),
					);
				}
				
				else{
					$return = array( 
						'status' => 'invalid',
						'log' => 'Unable to create the woocommerce product!'
					);
				}
			}
			
			if( $action == 'insert_product' ){
				if( isset($_SESSION["WooZoneLight_test_product"]) && count($_SESSION["WooZoneLight_test_product"]) > 0 ){
					$product = $_SESSION["WooZoneLight_test_product"];
					
					$insert_id = $this->the_plugin->addNewProduct( $product, false );
					if( (int) $insert_id > 0 ){
						
						$_SESSION["WooZoneLight_test_product"]['local_id'] = $insert_id;
						$return = array( 
							'status' => 'valid',
							'log' => "New product added: " . $insert_id,
							'execution_time' => number_format( microtime(true) - $start, 2),
						);
					}
				}
				
				else{
					$return = array( 
						'status' => 'invalid',
						'log' => 'Unable to create the woocommerce product!'
					);
				}
			}
			
			if( $action == 'get_product_data' ){
				
				$asin = isset($_REQUEST['ASIN']) ? $_REQUEST['ASIN'] : '';
				if( $asin != "" ){
					
					$retProd = array();
					$product = $this->aaAmazonWS->responseGroup('Large,ItemAttributes,Offers,Reviews')->optionalParameters(array('MerchantId' => 'All'))->lookup( $asin ); 
					if($product['Items']["Request"]["IsValid"] == "True"){
						$thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();
						
						if (isset($product['Items']['Item']) && count($product['Items']['Item']) > 0) {
							
							$number_of_images = ((int)$this->amz_settings["number_of_images"] > 0 ? $this->amz_settings["number_of_images"] : 'all');
							
							// product large image
							$retProd['images']['large'][] = $thisProd['LargeImage']['URL'];
							$retProd['images']['small'][] = $thisProd['SmallImage']['URL'];
		
							$retProd['ASIN'] = $thisProd['ASIN'];
		
							// get gallery images
							if(count($thisProd['ImageSets']) > 0){
								
								$count = 0;
								foreach ($thisProd['ImageSets']["ImageSet"] as $key => $value){
									
									if( isset($value['LargeImage']['URL']) ){
										$retProd['images']['large'][] = $value['LargeImage']['URL'];
										$retProd['images']['small'][] = $value['SmallImage']['URL'];
									}
									$count++;
								}
								$retProd['images']['large'] = @array_unique($retProd['images']['large']);
								
								if( isset($number_of_images) && (int) $number_of_images > 0 ){
									$retProd['images']['large'] = array_slice($retProd['images']['large'], 0, (int) $number_of_images);
								}
							}
							
							// CustomerReviews url
							if($thisProd['CustomerReviews']['HasReviews']){
								$retProd['CustomerReviewsURL'] = $thisProd['CustomerReviews']['IFrameURL'];
							}
		
							// DetailPageURL
							$retProd['DetailPageURL'] = $thisProd['DetailPageURL'];
		
							// product title
							$retProd['Title'] = isset($thisProd['ItemAttributes']['Title']) ? $thisProd['ItemAttributes']['Title'] : '';
		
							// SKU
							$retProd['SKU'] = isset($thisProd['ItemAttributes']['SKU']) ? $thisProd['ItemAttributes']['SKU'] : '';
		
							// Feature
							$retProd['Feature'] = isset($thisProd['ItemAttributes']['Feature']) ? $thisProd['ItemAttributes']['Feature'] : '';
		
							// EditorialReviews
							$retProd['EditorialReviews'] = isset($thisProd['EditorialReviews']['EditorialReview']['Content']) ? $thisProd['EditorialReviews']['EditorialReview']['Content'] : '';
							
							// The product BrowseNodes
							$retProd['BrowseNodes'] = isset($thisProd['BrowseNodes']) ? $thisProd['BrowseNodes'] : array(); 
							
							// The product Item attribues
							$retProd['ItemAttributes'] = isset($thisProd['ItemAttributes']) ? $thisProd['ItemAttributes'] : array(); 
							
							// The product Offers
							$retProd['Offers'] = isset($thisProd['Offers']) ? $thisProd['Offers'] : array();
							$retProd['OfferSummary'] = isset($thisProd['OfferSummary']) ? $thisProd['OfferSummary'] : array();
							
							$return = array( 
								'status' => 'valid',
								'log' => $retProd,
								'execution_time' => number_format( microtime(true) - $start, 2),
							);
							
							// save the product into session, for feature using of it
							$_SESSION['WooZoneLight_test_product'] = $retProd;
						}

						else{
							$return = array(
								'status' => 'invalid',
								'msg'	=> 'Please provide a valid ASIN code!',
								'log'	=> $product
							);
						}
					}

				} else {
					$return = array(
						'status' => 'invalid',
						'msg'	=> 'Please provide a valid ASIN code!'
					);
				}
			}
			
			die( json_encode($return) );   
		}
		
		public function check_amazon()
		{
			$status = 'valid';
			$msg = '';
	        try {
	            // Do a test connection
	        	$tryRequest = $this->aaAmazonWS->category('DVD')->responseGroup('Images')->search("Matrix");
	        } catch (Exception $e) {
	            // Check 
	            if (isset($e->faultcode)) {
	            	
					$msg = $e->faultcode . ": " . $e->faultstring; 
	                $status = 'invalid';
	            }
	        }
			
        	die(json_encode(array(
				'status' => $status,
				'msg' => $msg
			)));
		}
		
		private function convertMainAffIdInCountry( $main_add_id='' )
		{
			if( $main_add_id == 'com' ) return 'US';
			
			return strtoupper( $main_add_id );
		}
		
		public function getAmazonCategs()
		{
			$country = $this->convertMainAffIdInCountry( $this->amz_settings['main_aff_id'] );
			$csv = $categs = array();
		
			// try to read the plugin_root/assets/browsenodes.csv file
			$csv_file_content = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/browsenodes.csv' );
			
			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", $value);
					}
				}
			}
			
			// find current country in first row 
			$pos = 0;
			if( count($csv[0]) > 0 ){
				foreach ($csv[0] as $key => $value) {
					if( strtoupper($country) == strtoupper($value) ){
						$pos = $key;
					}
				}
			}
			
			if( $pos > 0 && count($csv) > 0 ){
				foreach ($csv as $key => $value) {
					// skip the header row	
					if( $key == 0 ) continue;
					
					if( isset($value[$pos]) && trim($value[$pos]) != "" ){
						$categs[$value[0]] = $value[$pos];
					}
				}
			}
			
			return $categs;  
		}

		public function getAmazonItemSearchParameters()
		{
			$country = $this->convertMainAffIdInCountry( $this->amz_settings['main_aff_id'] );
			$csv = $categs = array();
			
			
			// try to read the plugin_root/assets/searchindexParam-{country}.csv file
			// check if file exists
			if( !is_file( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' ) ){
				die( 'Unable to load file: ' . $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' );
			}
			
        	//$csv_file_content = $this->the_plugin->wp_filesystem->get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' );
        	$csv_file_content = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/searchindexParam-' . ( $country ) . '.csv' );
			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				 
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", trim($value));
					}
				}
			}
			
			if( count($csv) > 0 ){
				foreach ($csv as $key => $value) {
					$categs[$value[0]] = explode(":", trim($value[1]));
				}
			}
			
			return $categs;  
		}
		
		public function getAmazonSortValues()
		{
			$country = $this->convertMainAffIdInCountry( $this->amz_settings['main_aff_id'] );
			$csv = $categs = array();
			
			
			// try to read the plugin_root/assets/searchindexParam-{country}.csv file
			// check if file exists
			if( !is_file( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' ) ){
				die( 'Unable to load file: ' . $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' );
			}
			
        	//$csv_file_content = $this->the_plugin->wp_filesystem->get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' );
        	$csv_file_content = file_get_contents( $this->the_plugin->cfg['paths']['plugin_dir_path'] . 'assets/sortvalues-' . ( $country ) . '.csv' );
 
			if( trim($csv_file_content) != "" ){
				$rows = explode("\r", $csv_file_content);
				 
				if( count($rows) > 0 ){
					foreach ($rows as $key => $value) {
						$csv[] = explode(",", trim($value));
					}
				}
			}
			
			if( count($csv) > 0 ){
				foreach ($csv as $key => $value) {
					$categs[$value[0]] = explode(":", trim($value[1]));
				}
			}
			  
			return $categs;  
		}
		
		private function setupAmazonWS()
		{
			// load the amazon webservices client class
			require_once( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php' );
			
			// create new amazon instance
			$this->aaAmazonWS = new aaAmazonWS(
				$this->amz_settings['AccessKeyID'],
				$this->amz_settings['SecretAccessKey'],
				$this->amz_settings['country'],
				$this->the_plugin->main_aff_id()
			);
		}
		
		public function browseNodeLookup( $nodeid )
		{
			return $this->aaAmazonWS->responseGroup('BrowseNodeInfo')->browseNodeLookup( $nodeid );
		}
		
		public function updateProductReviews ( $post_id=0 )
		{
			
			// get product ASIN by post_id 
			$asin = get_post_meta( $post_id, '_amzASIN', true );
			
			$product = $this->aaAmazonWS->responseGroup('Reviews')->optionalParameters(array('MerchantId' => 'All'))->lookup( $asin ); 
			if($product['Items']["Request"]["IsValid"] == "True"){
				$thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();
				if (isset($product['Items']['Item']) && count($product['Items']['Item']) > 0){
					$reviewsURL = $thisProd['CustomerReviews']['IFrameURL'];
					if( trim($reviewsURL) != "" ){
						
						$tab_data = array();
						$tab_data[] = array(
							'id' => 'amzAff-customer-review',
							'content' => '<iframe src="' . ( $reviewsURL ) . '" width="100%" height="450" frameborder="0"></iframe>'
						); 
						
						update_post_meta( $post_id, 'amzaff_woo_product_tabs', $tab_data );
					}
				}
			}
			
			return $reviewsURL;  
		}
		
		public function getProductDataFromAmazon ( $return_amz_data=false )
		{
			// require_once( $this->the_plugin->cfg['paths']["scripts_dir_path"] . '/shutdown-scheduler/shutdown-scheduler.php' );
			// $scheduler = new aateamShutdownScheduler();

			$asin = isset($_REQUEST['asin']) ? htmlentities($_REQUEST['asin']) : '';
			$category = isset($_REQUEST['category']) ? htmlentities($_REQUEST['category']) : 'All';

			// check if product already imported 
			/*$your_products = $this->the_plugin->getAllProductsMeta('array', '_amzASIN');
			if( isset($your_products) && count($your_products) > 0 ){
				if( in_array($asin, $your_products) ){
					die( json_encode( array(
						'status' 	=> 'invalid',
						'msg'		=> 'Already imported!'
					) ) );
				}
			}*/
			
			$number_of_images = ((int)$this->amz_settings["number_of_images"] > 0 ? $this->amz_settings["number_of_images"] : 'all');
			$cross_selling = (isset($this->amz_settings["cross_selling"]) && $this->amz_settings["cross_selling"] == 'yes' ? true : false);

			if( trim($asin) == '' ){
				die( json_encode( array(
					'status' 	=> 'invalid',
					'msg'		=> 'Invalid ASIN code'
				) ) );
			}

			// create new amazon instance
			$aaAmazonWS = $this->aaAmazonWS;
 
			// create request by ASIN
			$product = $aaAmazonWS->responseGroup('Large,ItemAttributes,OfferFull,Variations,Reviews,PromotionSummary')->optionalParameters(array('MerchantId' => 'All'))->lookup($asin);
  			if($product['Items']["Request"]["IsValid"] == "True"){
				$thisProd = isset($product['Items']['Item']) ? $product['Items']['Item'] : array();
				if (isset($product['Items']['Item']) && count($product['Items']['Item']) > 0) {
					// start creating return array
					$retProd = $retProd['images'] = array();
					 
					// product large image
					$retProd['images']['large'][] = $thisProd['LargeImage']['URL'];
					$retProd['images']['small'][] = $thisProd['SmallImage']['URL'];

					$retProd['ASIN'] = $thisProd['ASIN'];

					// get gallery images
					if(count($thisProd['ImageSets']) > 0){
						
						$count = 0;
						foreach ($thisProd['ImageSets']["ImageSet"] as $key => $value){
							
							if( isset($value['LargeImage']['URL']) ){
								$retProd['images']['large'][] = $value['LargeImage']['URL'];
								$retProd['images']['small'][] = $value['SmallImage']['URL'];
							}
							$count++;
						}
						$retProd['images']['large'] = @array_unique($retProd['images']['large']);
						$retProd['images']['small'] = @array_unique($retProd['images']['small']);
						
						if( (int) $number_of_images > 0 ){
							$retProd['images']['large'] = array_slice($retProd['images']['large'], 0, (int) $number_of_images);
						}
					}
					
					// CustomerReviews url
					if($thisProd['CustomerReviews']['HasReviews']){
						$retProd['CustomerReviewsURL'] = $thisProd['CustomerReviews']['IFrameURL'];
					}

					// DetailPageURL
					$retProd['DetailPageURL'] = $thisProd['DetailPageURL'];

					// product title
					$retProd['Title'] = isset($thisProd['ItemAttributes']['Title']) ? $thisProd['ItemAttributes']['Title'] : '';

					// SKU
					$retProd['SKU'] = isset($thisProd['ItemAttributes']['SKU']) ? $thisProd['ItemAttributes']['SKU'] : '';

					// Feature
					$retProd['Feature'] = isset($thisProd['ItemAttributes']['Feature']) ? $thisProd['ItemAttributes']['Feature'] : '';

					// EditorialReviews
					$retProd['EditorialReviews'] = isset($thisProd['EditorialReviews']['EditorialReview']['Content']) ? $thisProd['EditorialReviews']['EditorialReview']['Content'] : '';
					
					// The product BrowseNodes
					$retProd['BrowseNodes'] = isset($thisProd['BrowseNodes']) ? $thisProd['BrowseNodes'] : array(); 
					
					// The product Item attribues
					$retProd['ItemAttributes'] = isset($thisProd['ItemAttributes']) ? $thisProd['ItemAttributes'] : array(); 
					
					// The product Offers
					$retProd['Offers'] = isset($thisProd['Offers']) ? $thisProd['Offers'] : array();
					$retProd['OfferSummary'] = isset($thisProd['OfferSummary']) ? $thisProd['OfferSummary'] : array();
					
					// The product Offers
					$retProd['Variations'] = isset($thisProd['Variations']) ? $thisProd['Variations'] : array();
					
					// The product VariationSummary
					$retProd['VariationSummary'] = isset($thisProd['VariationSummary']) ? $thisProd['VariationSummary'] : array();
					
					$requestData = array();
					$requestData['debug_level'] = isset($_REQUEST['debug_level']) ? (int)$_REQUEST['debug_level'] : 0;
					 
					if( $return_amz_data === true ){
						return $retProd;
					}
					
					// print some debug if requested
					if( $requestData['debug_level'] > 0 ) {
						if( $requestData['debug_level'] == 1) var_dump('<pre>', $retProd,'</pre>');
						if( $requestData['debug_level'] == 2) var_dump('<pre>', $product ,'</pre>');

						die;
					}
					
					$insert_id = $this->the_plugin->addNewProduct( $retProd );
  
					$__import_type = 'default';
					if ( isset($this->amz_settings['import_type'])
						&& $this->amz_settings['import_type']=='asynchronous' ) {
						$__import_type = $this->amz_settings['import_type' ];
					}
  
					if ( !empty($__import_type) && $__import_type=='default' && ( (int) $insert_id > 0) ) {
						$__statRet = array(
							'status' => 'valid',
							'show_download_lightbox' => true,
							'download_lightbox_html' => $this->the_plugin->download_asset_lightbox( $insert_id, 'html' )
						);
					} else {
						if ( (int) $insert_id > 0 ) {
							$__statRet = array(
								'status' => 'valid',
								'show_download_lightbox' => false
							);
						} else {
							$err = $this->the_plugin->opStatusMsgGet();
							$__statRet = array(
								'status' => 'invalid',
								'msg'	=> isset($err['msg']) && !empty($err['msg']) ? $err['msg'] : 'untraced error!'
							);
						}
					}
					// now return everythink as json
					die(json_encode($__statRet));
				}

				die( json_encode( array(
					'status' 	=> 'invalid',
					'msg'		=> $product['Items']['Request']['Errors']['Error']['Message']
				) ) );

			}else{
				die(json_encode(array(
					'status' => 'invalid',
					'msg' => "Can't get product by given ASIN: " . $asin
				)));
			}

			// $scheduler->registerShutdownEvent(array($scheduler, 'getLastError'), true);
		}

		/**
	     * Create the categories for the product
	     * @param array $browseNodes
	     */
	    public function set_product_categories( $browseNodes=array() )
	    {
	        // The woocommerce product taxonomy
	        $wooTaxonomy = "product_cat";
	        
	        // Categories for the product
	        $createdCategories = array();
	        
	        // Category container
	        $categories = array();
	        
	        // Count the top browsenodes
	        $topBrowseNodeCounter = 0;

	        // Check if we have multiple top browseNode
	        if( is_array( $browseNodes['BrowseNode'] ) )
	        {
	        	// check if is has only one key
	        	if( isset($browseNodes["BrowseNode"]["BrowseNodeId"]) && trim($browseNodes["BrowseNode"]["BrowseNodeId"]) != "" ){
	        		$_browseNodes = $browseNodes["BrowseNode"];
	        		$browseNodes = array();
					$browseNodes['BrowseNode'][0] = $_browseNodes;
					unset($_browseNodes);
	        	}
    
	            foreach( $browseNodes['BrowseNode'] as $browseNode )
	            {
	                // Create a clone
	                $currentNode = $browseNode;
	
	                // Track the child layer
	                $childLayer = 0;
	
	                // Inifinite loop, since we don't know how many ancestral levels
	                while( true )
	                {
	                    $validCat = true;
	                    
	                    // Replace html entities
	                    $dmCatName = str_replace( '&', 'and', $currentNode['Name'] );
	                    $dmCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
						
						$dmCatSlug_id = '';
						if ( is_object($currentNode) && isset($currentNode->BrowseNodeId) )
	                    	$dmCatSlug_id = ($currentNode->BrowseNodeId);
						else if ( is_array($currentNode) && isset($currentNode['BrowseNodeId']) )
							$dmCatSlug_id = ($currentNode['BrowseNodeId']);

						// $dmCatSlug = ( !empty($dmCatSlug_id) ? $dmCatSlug_id . '-' . $dmCatSlug : $dmCatSlug );

	                    $dmTempCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
	                    
	                    if( $dmTempCatSlug == 'departments' ) $validCat = false;
	                    if( $dmTempCatSlug == 'featured-categories' ) $validCat = false;
	                   	if( $dmTempCatSlug == 'categories' ) $validCat = false;
						if( $dmTempCatSlug == 'products' ) $validCat = false;
	                    if( $dmTempCatSlug == 'all-products') $validCat = false;
	
	                    // Check if we will make the cat
	                    if( $validCat ) {
	                        $categories[0][] = array(
	                            'name' => $dmCatName,
	                            'slug' => $dmCatSlug
	                        );
	                    }
	
	                    // Check if the current node has a parent
	                    if( isset($currentNode['Ancestors']['BrowseNode']['Name']) )
	                    {
	                        // Set the next Ancestor as the current node
	                        $currentNode = $currentNode['Ancestors']['BrowseNode'];
	                        $childLayer++;
	                        continue;
	                    }
	                    else
	                    {
	                        // There's no more ancestors beyond this
	                        break;
	                    }
	                } // end infinite while
	                
	                // Increment the tracker
	                $topBrowseNodeCounter++;
	            } // end foreach
	        }
	        else
	        {
	            // Handle single branch browsenode
	            
	            // Create a clone
	            $currentNode = $browseNodes['BrowseNode'];
	            
	            // Inifinite loop, since we don't know how many ancestral levels
	            while (true) 
	            {
	                // Always true unless proven
	                $validCat = true;
	                
	                // Replace html entities
	                $dmCatName = str_replace( '&', 'and', $currentNode['Name'] );
	                $dmCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
					$dmCatSlug_id = $currentNode['BrowseNodeId'];
	                // $dmCatSlug = ( !empty($dmCatSlug_id) ? $dmCatSlug_id . '-' . $dmCatSlug : $dmCatSlug );  
	                
	                $dmTempCatSlug = sanitize_title( str_replace( '&', 'and', $currentNode['Name'] ) );
	                
					if( $dmTempCatSlug == 'departments' ) $validCat = false;
                    if( $dmTempCatSlug == 'featured-categories' ) $validCat = false;
                   	if( $dmTempCatSlug == 'categories' ) $validCat = false;
					if( $dmTempCatSlug == 'products' ) $validCat = false;
                    if( $dmTempCatSlug == 'all-products') $validCat = false;
	                
	                // Check if we will make the cat
	                if( $validCat ) {
	                    $categories[0][] = array(
	                        'name' => $dmCatName,
	                        'slug' => $dmCatSlug
	                    );
	                }
	
	                // Check if the current node has a parent
	                if (isset($currentNode['Ancestors']['BrowseNode']['Name'])) 
	                {
	                    // Set the next Ancestor as the current node
	                    $currentNode = $currentNode['Ancestors']['BrowseNode'];
	                    continue;
	                } 
	                else 
	                {
	                    // There's no more ancestors beyond this
	                    break;
	                }
	            } // end infinite while
	                
	        } // end if browsenode is an array
	        
	        // Tracker
	        $catCounter = 0;
	        
	        // Make the parent at the top
	        foreach( $categories as $category )
	        {
	            $categories[$catCounter] = array_reverse( $category );
	            $catCounter++;
	        }
	        
	        // Current top browsenode
	        $categoryCounter = 0;
	
	        // Loop through each of the top browsenode
	        foreach( $categories as $category )
	        {
	            // The current node
	            $nodeCounter = 0;
	            // Loop through the array of the current browsenode
	            foreach( $category as $node )
	            {
	                // Check if we're at parent
	                if( $nodeCounter === 0 )
	                {                
	                    // Check if term exists
	                    $checkTerm = term_exists( str_replace( '&', 'and', $node['slug'] ), $wooTaxonomy );
	                    if( empty( $checkTerm ) )
	                    {
	                        // Create the new category
	                       $newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'] ) );
	                       
	                       // Add the created category in the createdCategories
	                       // Only run when the $newCat is an error
	                       if( gettype($newCat) != 'object' ) {
	                       		$createdCategories[] = $newCat['term_id'];
	                       }       
	                    }
	                    else
	                    {
	                        // if term already exists add it on the createdCats
	                        $createdCategories[] = $checkTerm['term_id'];
	                    }
	                }
	                else
	                {  
	                    // The parent of the current node
	                    $parentNode = $categories[$categoryCounter][$nodeCounter - 1];
	                    // Get the term id of the parent
	                    $parent = term_exists( str_replace( '&', 'and', $parentNode['slug'] ), $wooTaxonomy );
	                    
	                    // Check if the category exists on the parent
	                    $checkTerm = term_exists( str_replace( '&', 'and', $node['slug'] ), $wooTaxonomy );
	                    
	                    if( empty( $checkTerm ) )
	                    {
	                        $newCat = wp_insert_term( $node['name'], $wooTaxonomy, array( 'slug' => $node['slug'], 'parent' => $parent['term_id'] ) );
	                        
	                        // Add the created category in the createdCategories
	                        $createdCategories[] = $newCat['term_id'];
	                    }
	                    else
	                    {
	                        $createdCategories[] = $checkTerm['term_id'];
	                    }
	                }
	                
	                $nodeCounter++;
	            } 
	    
	            $categoryCounter++;
	        } // End top browsenode foreach
	        
	        // Delete the product_cat_children
	        // This is to force the creation of a fresh product_cat_children
	        delete_option( 'product_cat_children' );
	        
	        $returnCat = array_unique($createdCategories);
	     
	        // return an array of term id where the post will be assigned to
	        return $returnCat;
	    }

		public function set_woocommerce_attributes( $itemAttributes=array(), $post_id ) 
		{
	        global $wpdb;
	        global $woocommerce;
	 
	        // convert Amazon attributes into woocommerce attributes
	        $_product_attributes = array();
	        $position = 0;
			
			$allowedAttributes = 'all';
			$amazon_settings = $this->the_plugin->getAllSettings('array', 'amazon');
			if ( isset($amazon_settings['selected_attributes'])
				&& !empty($amazon_settings['selected_attributes'])
				&& is_array($amazon_settings['selected_attributes']) )
				$allowedAttributes = (array) $amazon_settings['selected_attributes'];
 
	        foreach( $itemAttributes as $key => $value )
	        { 
	            if (!is_object($value)) 
	            {
	            	if ( is_array($allowedAttributes) ) {
						if ( !in_array($key, $allowedAttributes) ) {
							continue 1;
						}
					}
					
	                // Apparel size hack
	                if($key === 'ClothingSize') {
	                    $key = 'Size';
	                }
					// don't add list price,Feature,Title into attributes
					if( in_array($key, array('ListPrice', 'Feature', 'Title') ) ) continue;
	                
	                // change dimension name as woocommerce attribute name
	                $attribute_name = wc_attribute_taxonomy_name(strtolower($key)); 
					
					// convert value into imploded array
					if( is_array($value) ) {
						$value = $this->the_plugin->multi_implode( $value, ', ' ); 
					}
					
					// Format Camel Case
					//$value = trim( preg_replace('/([A-Z])/', ' $1', $value) );
					 
					// if is empty attribute don't import
					if( trim($value) == "" ) continue;
					
	                $_product_attributes[$attribute_name] = array(
	                    'name' => $attribute_name,
	                    'value' => $value,
	                    'position' => $position++,
	                    'is_visible' => 1,
	                    'is_variation' => 0,
	                    'is_taxonomy' => 1
	                );
					
	                $this->add_attribute( $post_id, $key, $value );
	            }
	        }
	        
	        // update product attribute
	        update_post_meta($post_id, '_product_attributes', $_product_attributes);
			
			$this->attrclean_clean_all( 'array' ); // delete duplicate attributes
	    }
	
	    // add woocommrce attribute values
	    public function add_attribute($post_id, $key, $value) 
	    { 
	        global $wpdb;
	        global $woocommerce;
			 
			$amazon_settings = $this->the_plugin->getAllSettings('array', 'amazon');
			
	        // get attribute name, label
	        if ( isset($amazon_settings['attr_title_normalize']) && $amazon_settings['attr_title_normalize'] == 'yes' )
	        	$attribute_label = $key;
			else
				$attribute_label = $key;
	        $attribute_name = woocommerce_sanitize_taxonomy_name($key);

	        // set attribute type
	        $attribute_type = 'select';
	        
	        // check for duplicates
	        $attribute_taxonomies = $wpdb->get_var("SELECT * FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name = '".esc_sql($attribute_name)."'");
	        
	        if ($attribute_taxonomies) {
	            // update existing attribute
	            $wpdb->update(
                    $wpdb->prefix . 'woocommerce_attribute_taxonomies', array(
		                'attribute_label' => $attribute_label,
		                'attribute_name' => $attribute_name,
		                'attribute_type' => $attribute_type,
		                'attribute_orderby' => 'name'
                    ), array('attribute_name' => $attribute_name)
	            );
	        } else {
	            // add new attribute
	            $wpdb->insert(
	                $wpdb->prefix . 'woocommerce_attribute_taxonomies', array(
	                	'attribute_label' => $attribute_label,
	                	'attribute_name' => $attribute_name,
	                	'attribute_type' => $attribute_type,
	                	'attribute_orderby' => 'name'
	                )
	            );
	        }

	        // avoid object to be inserted in terms
	        if (is_object($value))
	            return;
	
	        // add attribute values if not exist
	        $taxonomy = wc_attribute_taxonomy_name($attribute_name);
			
	        if( is_array( $value ) )
	        {
	            $values = $value;
	        }
	        else
	        {
	            $values = array($value);
	        }
  
	        // check taxonomy
	        if( !taxonomy_exists( $taxonomy ) ) 
	        {
	            // add attribute value
	            foreach ($values as $attribute_value) {
	            	$attribute_value = (string) $attribute_value;
	                if(is_string($attribute_value)) {
	                    // add term
	                    $name = stripslashes($attribute_value);
	                    $slug = sanitize_title($name);
						
	                    if( !term_exists($name) ) {
	                        if( trim($slug) != '' && trim($name) != '' ) {
	                        	$this->the_plugin->db_custom_insert(
	                        		$wpdb->terms,
	                        		array(
	                        			'values' => array(
		                                	'name' => $name,
		                                	'slug' => $slug
										),
										'format' => array(
											'%s', '%s'
										)
	                        		),
	                        		true
	                        	);
	                            /*$wpdb->insert(
                                    $wpdb->terms, array(
		                                'name' => $name,
		                                'slug' => $slug
                                    )
	                            );*/
	
	                            // add term taxonomy
	                            $term_id = $wpdb->insert_id;
	                        	$this->the_plugin->db_custom_insert(
	                        		$wpdb->term_taxonomy,
	                        		array(
	                        			'values' => array(
		                                	'term_id' => $term_id,
		                                	'taxonomy' => $taxonomy
										),
										'format' => array(
											'%d', '%s'
										)
	                        		),
	                        		true
	                        	);
	                            /*$wpdb->insert(
                                    $wpdb->term_taxonomy, array(
		                                'term_id' => $term_id,
		                                'taxonomy' => $taxonomy
                                    )
	                            );*/
								$term_taxonomy_id = $wpdb->insert_id;
								$__dbg = compact('taxonomy', 'attribute_value', 'term_id', 'term_taxonomy_id');
								//var_dump('<pre>1: ',$__dbg,'</pre>');
	                        }
	                    } else {
	                        // add term taxonomy
	                        $term_id = $wpdb->get_var("SELECT term_id FROM {$wpdb->terms} WHERE name = '".esc_sql($name)."'");
	                        $this->the_plugin->db_custom_insert(
	                        	$wpdb->term_taxonomy,
	                        	array(
	                        		'values' => array(
		                           		'term_id' => $term_id,
		                           		'taxonomy' => $taxonomy
									),
									'format' => array(
										'%d', '%s'
									)
	                        	),
	                        	true
	                        );
	                        /*$wpdb->insert(
                           		$wpdb->term_taxonomy, array(
		                            'term_id' => $term_id,
		                            'taxonomy' => $taxonomy
                                )
	                        );*/
							$term_taxonomy_id = $wpdb->insert_id;
							$__dbg = compact('taxonomy', 'attribute_value', 'term_id', 'term_taxonomy_id');
							//var_dump('<pre>1c: ',$__dbg,'</pre>');
	                    }
	                }
	            }
	        }
	        else 
	        {
	            // get already existing attribute values
	            $attribute_values = array();
	            /*$terms = get_terms($taxonomy, array('hide_empty' => true));
				if( !is_wp_error( $terms ) ) {
	            	foreach ($terms as $term) {
	                	$attribute_values[] = $term->name;
	            	}
				} else {
					$error_string = $terms->get_error_message();
					var_dump('<pre>',$error_string,'</pre>');  
				}*/
				$terms = $this->the_plugin->load_terms($taxonomy);
	            foreach ($terms as $term) {
	               	$attribute_values[] = $term->name;
	            }
	            
	            // Check if $attribute_value is not empty
	            if( !empty( $attribute_values ) )
	            {
	                foreach( $values as $attribute_value ) 
	                {
	                	$attribute_value = (string) $attribute_value;
	                    if( !in_array( $attribute_value, $attribute_values ) ) 
	                    {
	                        // add new attribute value
	                        $__term_and_tax = wp_insert_term($attribute_value, $taxonomy);
							$__dbg = compact('taxonomy', 'attribute_value', '__term_and_tax');
							//var_dump('<pre>1b: ',$__dbg,'</pre>');
	                    }
	                }
	            }
	        }
	
	        // Add terms
	        if( is_array( $value ) )
	        {
	            foreach( $value as $dm_v )
	            {
	            	$dm_v = (string) $dm_v;
	                if( !is_array($dm_v) && is_string($dm_v)) {
	                    $__term_and_tax = wp_insert_term( $dm_v, $taxonomy );
						$__dbg = compact('taxonomy', 'dm_v', '__term_and_tax');
						//var_dump('<pre>2: ',$__dbg,'</pre>');
	                }
	            }
	        }
	        else
	        {
	        	$value = (string) $value;
	            if( !is_array($value) && is_string($value) ) {
	                $__term_and_tax = wp_insert_term( $value, $taxonomy );
					$__dbg = compact('taxonomy', 'value', '__term_and_tax');
					//var_dump('<pre>2b: ',$__dbg,'</pre>');
	            }
	        }
			
	        // link to woocommerce attribute values
	        if( !empty( $values ) )
	        {
	            foreach( $values as $term )
	            {
	            	
	                if( !is_array($term) && !is_object( $term ) )
	                { 
	                    $term = sanitize_title($term);
	                    
	                    $term_taxonomy_id = $wpdb->get_var( "SELECT tt.term_taxonomy_id FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} as tt ON tt.term_id = t.term_id WHERE t.slug = '".esc_sql($term)."' AND tt.taxonomy = '".esc_sql($taxonomy)."'" );
  
	                    if( $term_taxonomy_id ) 
	                    {
	                        $checkSql = "SELECT * FROM {$wpdb->term_relationships} WHERE object_id = {$post_id} AND term_taxonomy_id = {$term_taxonomy_id}";
	                        if( !$wpdb->get_var($checkSql) ) {
	                            $wpdb->insert(
	                                    $wpdb->term_relationships, array(
			                                'object_id' => $post_id,
			                                'term_taxonomy_id' => $term_taxonomy_id
	                                    )
	                            );
	                        }
	                    }
	                }
	            }
	        }
	    }

		/**
		 * Amazon product get price!
		 */
		public function productAmazonPriceIsZero( $thisProd ) {
			$prodprice = array('regular_price' => '');
  
			$price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
			//$offers_from = ( $price_setup == 'only_amazon' ? 'Amazon' : 'All' );
 
			// list price
			$offers = array(
				'ListPrice' => isset($thisProd['ItemAttributes']['ListPrice']['Amount']) ? ($thisProd['ItemAttributes']['ListPrice']['Amount'] * 0.01 ) : '',
				'LowestNewPrice' => isset($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount'] * 0.01) : '',
				'Offers'	=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array()
			);
  
			if( $price_setup == 'amazon_or_sellers' && isset($thisProd['OfferSummary']['LowestNewPrice']['Amount']) ) {
				$offers['LowestNewPrice'] = ($thisProd['OfferSummary']['LowestNewPrice']['Amount'] * 0.01);
			}

			$prodprice['regular_price'] = $offers['ListPrice'];

			// if regular price is empty setup offer price as regular price
			if( 
				(!isset($offers['ListPrice']) || (int)$offers['ListPrice'] == 0)
				|| (isset($offers['ListPrice']) && $offers['LowestNewPrice'] > $offers['ListPrice'])
			) {
				$prodprice['regular_price'] = $offers['LowestNewPrice'];
			}

			// if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
			if( !isset($prodprice['regular_price']) || (int)$prodprice['regular_price'] == 0 ) {
				$prodprice['regular_price'] = isset($thisProd['VariationSummary']['LowestPrice']['Amount']) ? ( $thisProd['VariationSummary']['LowestPrice']['Amount'] * 0.01 ) : '';
			}
  
			if ( empty($prodprice['regular_price']) || (int)$prodprice['regular_price'] <= 0 ) return true;
			return false;
		}

		public function productPriceUpdate( $thisProd, $post_id='', $return=true )
		{
			// if any of regular | sale price set to auto => no product price syncronization!
			$priceStatus = $this->productPriceGetRegularSaleStatus( $post_id );
			if ( $priceStatus['regular'] == 'selected' || $priceStatus['sale'] == 'selected' ) {
				if( $return == true ) {
					die(json_encode(array(
						'status' => 'valid',
						'data'		=> array(
							'_sales_price' => woocommerce_price( get_post_meta($post_id, '_regular_price', true) ),
							'_regular_price' => woocommerce_price( get_post_meta($post_id, '_sale_price', true) ),
							'_price_update_date' => date('F j, Y, g:i a', get_post_meta($post_id, '_price_update_date', true))
						)
					)));
				}
				return true;
			} // end priceStatus
			
			$price_setup = (isset($this->amz_settings["price_setup"]) && $this->amz_settings["price_setup"] == 'amazon_or_sellers' ? 'amazon_or_sellers' : 'only_amazon');
			//$offers_from = ( $price_setup == 'only_amazon' ? 'Amazon' : 'All' );

			// list price
			$offers = array(
				'ListPrice' 		=> isset($thisProd['ItemAttributes']['ListPrice']['Amount']) ? ($thisProd['ItemAttributes']['ListPrice']['Amount'] * 0.01 ) : '',
				'LowestNewPrice' 	=> isset($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount']) ? ($thisProd['Offers']['Offer']['OfferListing']['Price']['Amount'] * 0.01) : '',
				'LowestPrice' 		=> isset($thisProd['VariationSummary']['LowestSalePrice']['Amount']) ? ($thisProd['VariationSummary']['LowestSalePrice']['Amount'] * 0.01) : '',
				'Offers'			=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array()
			);
  
			if( $price_setup == 'amazon_or_sellers' && isset($thisProd['OfferSummary']['LowestNewPrice']['Amount']) ) {
				$offers['LowestNewPrice'] = ($thisProd['OfferSummary']['LowestNewPrice']['Amount'] * 0.01);
			}

			// get current product meta, update the values of prices and update it back
			$product_meta = get_post_meta( $post_id, '_product_meta', true );

			$product_meta['product']['regular_price'] = $offers['ListPrice'];

			// if regular price is empty setup offer price as regular price or lowest new price greater then list price
			if( 
				(!isset($offers['ListPrice']) || (int)$offers['ListPrice'] == 0)
				|| (isset($offers['ListPrice']) && $offers['LowestNewPrice'] > $offers['ListPrice'])
			) {
				$product_meta['product']['regular_price'] = $offers['LowestNewPrice'];
			}

			// if still don't have any regular price, try to get from VariationSummary (ex: Apparel category)
			if( !isset($product_meta['product']['regular_price']) || (int)$product_meta['product']['regular_price'] == 0 ) {
				$product_meta['product']['regular_price'] = isset($thisProd['VariationSummary']['LowestPrice']['Amount']) ? ( $thisProd['VariationSummary']['LowestPrice']['Amount'] * 0.01 ) : '';
			}

			if( isset($offers['LowestNewPrice']) ) {
				$product_meta['product']['sales_price'] = $offers['LowestNewPrice']; 
				// if offer price is higher than regular price, delete the offer
				if( $offers['LowestNewPrice'] >= $product_meta['product']['regular_price'] ){
					unset($product_meta['product']['sales_price']);
				}
			}
			
			if( isset($offers['LowestPrice']) && empty($product_meta['product']['sales_price']) ) {
				$product_meta['product']['sales_price'] = $offers['LowestPrice']; 
				// if offer price is higher than regular price, delete the offer
				if( $offers['LowestPrice'] >= $product_meta['product']['regular_price'] ){
					unset($product_meta['product']['sales_price']);
				}
			}

			// set product price metas!
			if ( isset($product_meta['product']['sales_price']) && !empty($product_meta['product']['sales_price']) ) {
				update_post_meta($post_id, '_sale_price', $product_meta['product']['sales_price']);
				$this->productPriceSetRegularSaleMeta($post_id, 'sale', array(
					'auto' => number_format($product_meta['product']['sales_price'], 2, '.', '')
				));
			} else { // new sale price is 0
				update_post_meta($post_id, '_sale_price', '');
				$this->productPriceSetRegularSaleMeta($post_id, 'sale', array(
					'auto' => ''
				));
			}
			update_post_meta($post_id, '_price_update_date', time());
			update_post_meta($post_id, '_regular_price', $product_meta['product']['regular_price']);
			$this->productPriceSetRegularSaleMeta($post_id, 'regular', array(
				'auto' => number_format($product_meta['product']['regular_price'], 2, '.', '')
			));
			update_post_meta($post_id, '_price', (isset($product_meta['product']['sales_price']) && trim($product_meta['product']['sales_price']) != "" ? $product_meta['product']['sales_price'] : $product_meta['product']['regular_price']));

			// set product price extra metas!
			$retExtra = $this->productPriceSetMeta( $thisProd, $post_id, 'return' );

			if( $return == true ) {
				die(json_encode(array(
					'status' => 'valid',
					'data'		=> array(
						'_sales_price' => isset($product_meta['product']['sales_price']) ? woocommerce_price($product_meta['product']['sales_price']) : '-',
						'_regular_price' => woocommerce_price($product_meta['product']['regular_price']),
						'_price_update_date' => date('F j, Y, g:i a', time())
					)
				)));
			}
		}
	
		public function set_woocommerce_variations( $retProd, $post_id, $variationNumber ) 
		{
	        global $woocommerce;
			
			$var_mode = '';
			$VariationDimensions = array();
			 
			// convert $variationNumber into number
			if( $variationNumber == 'yes_all' ){
				// 10 variation is enough
				$variationNumber = 100;
			}
			elseif( $variationNumber == 'no' ){
				$variationNumber = 0;
			}
			else{
				$variationNumber = end(explode( "_", $variationNumber ));
			}
 
	        if ( isset($retProd['Variations']['TotalVariations']) && $retProd['Variations']['TotalVariations'] > 0) {
				$offset = 0;
	            // its not a simple product, it is a variable product
	            wp_set_post_terms($post_id, 'variable', 'product_type', false);
				  
	            // initialize the variation dimensions array
	            if (count($retProd['Variations']['VariationDimensions']['VariationDimension']) == 1) {
	                $VariationDimensions[$retProd['Variations']['VariationDimensions']['VariationDimension']] = array();
	            } else {
	                // Check if VariationDimension is given
	                if(count($retProd['Variations']['VariationDimensions']['VariationDimension']) > 0 ) {
	                    foreach ($retProd['Variations']['VariationDimensions']['VariationDimension'] as $dim) {
	                        $VariationDimensions[$dim] = array();
	                    }
	                }
	            } 

	            // loop through the variations
	            if (count($retProd['Variations']['Item']) == 1) {
	                $variation_item = $retProd['Variations']['Item'];
	                $VariationDimensions = $this->variation_post( $retProd['Variations']['Item'], $variation_post, $post_options, $post_id, $VariationDimensions );
	                $offset ++;
	                $var_mode = 'create';
	            } else {
	            	
	                // if the variation still has items 
	                $var_mode = 'variation';
					
					$cc = 0;
					
	                // Loop through the variation
	                for( $cc = 1; $cc <= $variationNumber; $cc++ )
	                {
	                    // Check if there are still variations
	                    if( $offset > ((int)$retProd['Variations']['TotalVariations'] - 1) )
	                    {
	                        break;
	                    }
	                    elseif( $offset == ((int)$retProd['Variations']['TotalVariations'] - 1) )
	                    {
	                        $var_mode = 'create';
	                    }
	                    
	                    // Get the specifc variation 
	                    $variation_item = $retProd['Variations']['Item'][$offset];
  
	                    // Create the variation post
	                    $VariationDimensions = $this->variation_post( $variation_item, $post_id, $VariationDimensions );
	                    
	                    // Increase the offset
	                    $offset++;
	                }
	            }

	            // Set the offset
	            $this->var_offset = $offset;
	            
	            $tempProdAttr = get_post_meta( $post_id, '_product_attributes', true );
	            
	            foreach( $VariationDimensions as $name => $values )
	            {
	                if($name != '') {
	                	// convert value into imploded array
						if( is_array($values) ) {
							$values = $this->the_plugin->multi_implode( $values, ', ' ); 
						}
					
	                    $this->add_attribute( $post_id, $name, $values );
	                    $dimension_name = wc_attribute_taxonomy_name(strtolower($name));
	                    $tempProdAttr[$dimension_name] = array(
	                        'name' => $dimension_name,
	                        'value' => $values,
	                        'position' => 0,
	                        'is_visible' => 1,
	                        'is_variation' => 1,
	                        'is_taxonomy' => 1,
	                    );
	                }
	            }
	            
	            update_post_meta($post_id, '_product_attributes', serialize($tempProdAttr));
	        }
	    }
		
		public function variation_post( $variation_item, $post_id, $VariationDimensions ) 
		{
	        global $woocommerce, $wpdb;
			$number_of_images = ((int)$this->amz_settings["number_of_images_variation"] > 0 ? $this->amz_settings["number_of_images_variation"] : 'all');
			 
			$variation_post = get_post( $post_id, ARRAY_A );
	        $variation_post['post_title'] = isset($variation_item['ItemAttributes']['Title']) ? $variation_item['ItemAttributes']['Title'] : '';
	        $variation_post['post_type'] = 'product_variation';
	        $variation_post['post_parent'] = $post_id;
	        unset( $variation_post['ID'] );
			
	        $variation_post_id = wp_insert_post( $variation_post );
	
			$images = array();
			$images['Title'] = isset($variation_item['ItemAttributes']['Title']) ? $variation_item['ItemAttributes']['Title'] : uniqid();
			
	        // get gallery images
			if(count($variation_item['ImageSets']) > 0){
				
				// hack if have only 1 item
				if( isset($variation_item['ImageSets']['ImageSet']['SwatchImage']) ){
					$_tmp = $variation_item['ImageSets']["ImageSet"];
					$variation_item['ImageSets']["ImageSet"] = array();
					$variation_item['ImageSets']["ImageSet"][0] = $_tmp;  
				}
				
				foreach ($variation_item['ImageSets']["ImageSet"] as $key => $value){
					if( isset($value['LargeImage']['URL']) ){
						$images['images']['large'][] = $value['LargeImage']['URL'];
						$images['images']['small'][] = $value['SmallImage']['URL'];
					}
				}
				$images['images']['large'] = @array_unique($images['images']['large']);
				
				if( (int) $number_of_images > 0 ){
					$images['images']['large'] = array_slice($images['images']['large'], 0, (int) $number_of_images);
				} 
			}
			
			$this->set_product_images( $images, $variation_post_id, $post_id );
	        
			// set the product price
			$this->productPriceUpdate( $variation_item, $variation_post_id, false );
			
			// than update the metapost
			$this->set_product_meta_options( $variation_item, $variation_post_id, true );
			 
	        // Compile all the possible variation dimensions         
	        if(is_array($variation_item['VariationAttributes']['VariationAttribute']) && isset($variation_item['VariationAttributes']['VariationAttribute'][0]['Name'])) {
	        	
	            foreach ($variation_item['VariationAttributes']['VariationAttribute'] as $va) {
	            	
	                $this->add_attribute( $post_id, $va['Name'], $va['Value'] );

	                $curarr = $VariationDimensions[$va['Name']];
	                $curarr[$va['Value']] = $va['Value'];
					
	                $VariationDimensions[$va['Name']] = $curarr;
	        
	                $dimension_name = wc_attribute_taxonomy_name(strtolower($va['Name']));
	                update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($va['Value']));  
	            }
	        } else {
	            $dmName = $variation_item['VariationAttributes']['VariationAttribute']['Name'];
	            $dmValue = $variation_item['VariationAttributes']['VariationAttribute']['Value'];
	                
	            $this->add_attribute( $post_id, $dmName, $dmValue );
	                
	            $curarr = $VariationDimensions[$dmName];
	            $curarr[$dmValue] = $dmValue;
	            $VariationDimensions[$dmName] = $curarr;
	        
	            $dimension_name = wc_attribute_taxonomy_name(strtolower($dmName));
	            update_post_meta($variation_post_id, 'attribute_' . $dimension_name, sanitize_title($dmValue));
	        }
	            
	        // refresh attribute cache
	        $dmtransient_name = 'wc_attribute_taxonomies';
	        $dmattribute_taxonomies = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies");
	        set_transient($dmtransient_name, $dmattribute_taxonomies);
	
	        return $VariationDimensions;
	    }
		
		public function set_product_images( $retProd, $post_id, $parent_id=0 )
		{
			//var_dump('<pre>',$retProd, $post_id, $parent_id,'</pre>'); die;
			$productImages = array();
			
			// try to download the images
			$retProd["images"]['large'] = @array_filter($retProd["images"]['large']); // remove empty array elements!
			if( $retProd["images"]['large'] != false && count($retProd["images"]['large']) > 0) {
				$step = 0;
				
				// product variation
				if ( $parent_id > 0 ) {
					$retProd["images"]['large'] = array_slice($retProd["images"]['large'], 0, 1);
				}
				
				// insert the product into db if is not duplicate
				$amz_prod_status = $this->the_plugin->db_custom_insert(
	               	$this->the_plugin->db->prefix . 'amz_products',
	               	array(
	               		'values' => array(
							'post_id' => $post_id, 
							'post_parent' => $parent_id,
							'title' => isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
							'type' => (int) $parent_id > 0 ? 'variation' : 'post',
							'nb_assets' => count($retProd["images"]['large'])
						),
						'format' => array(
							'%d',
							'%d',
							'%s',
							'%s',
							'%d' 
						)
	                ),
	                true
	            );
				/*$amz_prod_status = $this->the_plugin->db->insert( 
					$this->the_plugin->db->prefix . 'amz_products', 
					array( 
						'post_id' => $post_id, 
						'post_parent' => $parent_id,
						'title' => isset($retProd["Title"]) ? $retProd["Title"] : 'untitled',
						'type' => (int) $parent_id > 0 ? 'variation' : 'post',
						'nb_assets' => count($retProd["images"]['large'])
					), 
					array( 
						'%d',
						'%d',
						'%s',
						'%s',
						'%d' 
					) 
				);*/
			
				foreach ($retProd["images"]['large'] as $key => $value){
					
					$this->the_plugin->db_custom_insert(
						$this->the_plugin->db->prefix . 'amz_assets',
						array(
							'values' => array(
								'post_id' => $post_id,
								'asset' => $value,
								'thumb' => $retProd["images"]['small'][$key],
								'date_added' => date( "Y-m-d H:i:s" )
							), 
							'format' => array( 
								'%d',
								'%s',
								'%s',
								'%s'
							)
						),
						true
					);
					/*$this->the_plugin->db->insert( 
						$this->the_plugin->db->prefix . 'amz_assets', 
						array(
							'post_id' => $post_id,
							'asset' => $value,
							'thumb' => $retProd["images"]['small'][$key],
							'date_added' => date( "Y-m-d H:i:s" )
						), 
						array( 
							'%d',
							'%s',
							'%s',
							'%s'
						) 
					);*/
					
					//$ret = $this->the_plugin->download_image($value, $post_id, 'insert', $retProd['Title'], $step);
					//if(count($ret) > 0){
					//	$productImages[] = $ret;
					//}
					$step++;
				}
			}

			// add gallery to product
			$productImages = array(); // remade in assets module!
			if(count($productImages) > 0){
				$the_ids = array();
				foreach ($productImages as $key => $value){
					$the_ids[] = $value['attach_id'];
				}
				
				// Add the media gallery image as a featured image for this post
				update_post_meta($post_id, "_thumbnail_id", $productImages[0]['attach_id']);
				update_post_meta($post_id, "_product_image_gallery", implode(',', $the_ids));
			}
		}
		
		public function set_product_meta_options( $retProd, $post_id, $is_variation=true )
		{
			if( $is_variation == false ){
				$tab_data = array();
				$tab_data[] = array(
					'id' => 'amzAff-customer-review',
					'content' => '<iframe src="' . ( isset($retProd['CustomerReviewsURL']) ? urldecode($retProd['CustomerReviewsURL']) : '' ) . '" width="100%" height="450" frameborder="0"></iframe>'
				);	
			}
			 
			// update the metapost
			update_post_meta($post_id, '_sku', $retProd['SKU']);
			update_post_meta($post_id, '_amzASIN', $retProd['ASIN']);
			update_post_meta($post_id, '_visibility', 'visible');
			update_post_meta($post_id, '_downloadable', 'no');
			update_post_meta($post_id, '_virtual', 'no');
			update_post_meta($post_id, '_stock_status', 'instock');
			update_post_meta($post_id, '_backorders', 'no');
			update_post_meta($post_id, '_manage_stock', 'no');
			update_post_meta($post_id, '_product_url', home_url('/?redirectAmzASIN=' . $retProd['ASIN'] ));
			
			
			if( $is_variation == false ){
				update_option('_transient_wc_product_type_' . $post_id, 'external');
				if( isset($retProd['CustomerReviewsURL']) && @trim($retProd['CustomerReviewsURL']) != "" ) 
					update_post_meta( $post_id, 'amzaff_woo_product_tabs', $tab_data );
			}
		}
		
		/**
		 * Assets download methods
		 */
		public function get_asset_by_id( $asset_id, $inprogress=false, $include_err=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLightAssetDownloadCron = new WooZoneLightAssetDownload();
			
			return $WooZoneLightAssetDownloadCron->get_asset_by_id( $asset_id, $inprogress, $include_err );
		}
		
		public function get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress=false, $include_err=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLightAssetDownloadCron = new WooZoneLightAssetDownload();
			
			return $WooZoneLightAssetDownloadCron->get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress, $include_err );
		}

		public function get_asset_multiple( $nb_dw='all', $inprogress=false, $include_err=false ) {
			require( $this->the_plugin->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLightAssetDownloadCron = new WooZoneLightAssetDownload();
			
			return $WooZoneLightAssetDownloadCron->get_asset_multiple( $nb_dw, $inprogress, $include_err );
		}
		
		
		/**
		 * Category Slug clean duplicate
		 */
		public function category_slug_clean_all( $retType = 'die' ) {
			global $wpdb;
			
			$q = "SELECT 
 a.term_id, a.name, a.slug, b.parent, b.count
 FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND b.taxonomy = 'product_cat'
;";
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = __('could not retrieve category slugs!', $this->the_plugin->localizationName);
				if ( $retType == 'die' ) die(json_encode($ret));
				else return $ret;
			}
			
			$upd = 0;
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$name = $value->name;
				$slug = $value->slug;

				$__arr = explode( "-" , $slug );
				$__arr = array_unique( $__arr );
				$slug = implode( "-" , $__arr );

				// execution/ update
				$q_upd = "UPDATE {$wpdb->terms} AS a SET a.slug = '%s' 
 WHERE 1=1 AND a.term_id = %s;";
 				$q_upd = sprintf( $q_upd, $slug, $term_id );
				$res_upd = $wpdb->query( $q_upd );

				if ( !empty($res_upd) ) $upd++;
			}
			
			$ret['status'] = 'valid';
			$ret['msg_html'] = $upd . __(' category slugs updated!', $this->the_plugin->localizationName);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}
		
		
		/**
		 * Attributes clean duplicate
		 */
		public function attrclean_getDuplicateList() {
			global $wpdb;

			// $q = "SELECT COUNT(a.term_id) AS nb, a.name, a.slug FROM {$wpdb->terms} AS a WHERE 1=1 GROUP BY a.name HAVING nb > 1;";
			$q = "SELECT COUNT(a.term_id) AS nb, a.name, a.slug, b.term_taxonomy_id, b.taxonomy, b.count FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND b.taxonomy REGEXP '^pa_' GROUP BY a.name, b.taxonomy HAVING nb > 1
 ORDER BY a.name ASC
;";
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			foreach ($res as $key => $value) {
				$name = $value->name;
				$taxonomy = $value->taxonomy;
				$ret["$name@@$taxonomy"] = $value;
			}
			return $ret;
		}
		
		public function attrclean_getTermPerDuplicate( $term_name, $taxonomy ) {
			global $wpdb;
			
			$q = "SELECT a.term_id, a.name, a.slug, b.term_taxonomy_id, b.taxonomy, b.count FROM {$wpdb->terms} AS a
 LEFT JOIN {$wpdb->term_taxonomy} AS b ON a.term_id = b.term_id
 WHERE 1=1 AND a.name=%s AND b.taxonomy=%s ORDER BY a.slug ASC;";
 			$q = $wpdb->prepare( $q, $term_name, $taxonomy );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$ret["$term_id"] = $value;
			}
			return $ret;
		}
		
		public function attrclean_removeDuplicate( $first_term, $terms=array(), $debug = false ) {
			if ( empty($terms) || !is_array($terms) ) return false;

			$term_id = array();
			$term_taxonomy_id = array();
			foreach ($terms as $k => $v) {
				$term_id[] = $v->term_id;
				$term_taxonomy_id[] = $v->term_taxonomy_id;
				$taxonomy = $v->taxonomy;
			}
			// var_dump('<pre>',$first_term, $term_id, $term_taxonomy_id, $taxonomy,'</pre>');  

			$ret = array();
			$ret['term_relationships'] = $this->attrclean_remove_term_relationships( $first_term, $term_taxonomy_id, $debug );
			$ret['terms'] = $this->attrclean_remove_terms( $term_id, $debug );
			$ret['term_taxonomy'] = $this->attrclean_remove_term_taxonomy( $term_taxonomy_id, $taxonomy, $debug );
			// var_dump('<pre>',$ret,'</pre>');  
			return $ret;
		}
		
		private function attrclean_remove_term_relationships( $first_term, $term_taxonomy_id, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_taxonomy_id) && count($term_taxonomy_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_taxonomy_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.object_id, a.term_taxonomy_id FROM {$wpdb->term_relationships} AS a
 WHERE 1=1 AND a.term_taxonomy_id IN (%s) ORDER BY a.object_id ASC, a.term_taxonomy_id;";
 			$q = sprintf( $q, $idList );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			$ret[] = 'object_id, term_taxonomy_id';
			foreach ($res as $key => $value) {
				$term_taxonomy_id = $value->term_taxonomy_id;
				$ret["$term_taxonomy_id"] = $value;
			}
			return $ret;
			}
			
			// execution/ update
			$q = "UPDATE {$wpdb->term_relationships} AS a SET a.term_taxonomy_id = '%s' 
 WHERE 1=1 AND a.term_taxonomy_id IN (%s);";
 			$q = sprintf( $q, $first_term, $idList );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		private function attrclean_remove_terms( $term_id, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_id) && count($term_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.term_id, a.name FROM {$wpdb->terms} AS a
 WHERE 1=1 AND a.term_id IN (%s) ORDER BY a.name ASC;";
 			$q = sprintf( $q, $idList );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;
			
			$ret = array();
			$ret[] = 'term_id, name';
			foreach ($res as $key => $value) {
				$term_id = $value->term_id;
				$ret["$term_id"] = $value;
			}
			return $ret;
			}
			
			// execution/ update
			$q = "DELETE FROM a USING {$wpdb->terms} as a WHERE 1=1 AND a.term_id IN (%s);";
 			$q = sprintf( $q, $idList );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}
		
		private function attrclean_remove_term_taxonomy( $term_taxonomy_id, $taxonomy, $debug = false ) {
			global $wpdb;
			
			$idList = (is_array($term_taxonomy_id) && count($term_taxonomy_id)>0 ? implode(', ', array_map(array($this->the_plugin, 'prepareForInList'), $term_taxonomy_id)) : 0);

			if ( $debug ) {
			$q = "SELECT a.term_id, a.taxonomy, a.term_taxonomy_id FROM {$wpdb->term_taxonomy} AS a
 WHERE 1=1 AND a.term_taxonomy_id IN (%s) AND a.taxonomy = '%s' ORDER BY a.term_taxonomy_id ASC;";
 			$q = sprintf( $q, $idList, esc_sql($taxonomy) );
			$res = $wpdb->get_results( $q );
			if ( !$res || !is_array($res) ) return false;

			$ret = array();
			$ret[] = 'term_id, taxonomy, term_taxonomy_id';
			foreach ($res as $key => $value) {
				$term_taxonomy_id = $value->term_taxonomy_id;
				$ret["$term_taxonomy_id"] = $value;
			}
			return $ret;
			}

			// execution/ update
			$q = "DELETE FROM a USING {$wpdb->term_taxonomy} as a WHERE 1=1 AND a.term_taxonomy_id IN (%s) AND a.taxonomy = '%s';";
 			$q = sprintf( $q, $idList, $taxonomy );
			$res = $wpdb->query( $q );
			$ret = $res;
			return $ret;
		}

		public function attrclean_clean_all( $retType = 'die' ) {
			// :: get duplicates list
			$duplicates = $this->attrclean_getDuplicateList();
  
			if ( empty($duplicates) || !is_array($duplicates) ) {
				$ret['status'] = 'valid';
				$ret['msg_html'] = __('no duplicate terms found!', $this->the_plugin->localizationName);
				if ( $retType == 'die' ) die(json_encode($ret));
				else return $ret;
			}
			// html message
			$__duplicates = array();
			$__duplicates[] = '0 : name, slug, term_taxonomy_id, taxonomy, count';
			foreach ($duplicates as $key => $value) {
				$__duplicates[] = $value->name . ' : ' . implode(', ', (array) $value);
			}
			$ret['status'] = 'valid';
			$ret['msg_html'] = implode('<br />', $__duplicates);
			// if ( $retType == 'die' ) die(json_encode($ret));
			// else return $ret;

			// :: get terms per duplicate
			$__removeStat = array();
			$__terms = array();
			$__terms[] = '0 : term_id, name, slug, term_taxonomy_id, taxonomy, count';
			foreach ($duplicates as $key => $value) {
				$terms = $this->attrclean_getTermPerDuplicate( $value->name, $value->taxonomy );
				if ( empty($terms) || !is_array($terms) || count($terms) < 2 ) continue 1;

				$first_term = array_shift($terms);

				// html message
				foreach ($terms as $k => $v) {
					$__terms[] = $key . ' : ' . implode(', ', (array) $v);
				}

				// :: remove duplicate term
				$removeStat = $this->attrclean_removeDuplicate($first_term->term_id, $terms, false);
				
				// html message
				$__removeStat[] = '-------------------------------------- ' . $key;
				$__removeStat[] = '---- term kept';
				$__removeStat[] = 'term_id, term_taxonomy_id';
				$__removeStat[] = $first_term->term_id . ', ' . $first_term->term_taxonomy_id;
				foreach ($removeStat as $k => $v) {
					$__removeStat[] = '---- ' . $k;
					if ( !empty($v) && is_array($v) ) {
						foreach ($v as $k2 => $v2) {
							$__removeStat[] = implode(', ', (array) $v2);
						}
					} else if ( !is_array($v) ) {
						$__removeStat[] = (int) $v;
					} else {
						$__removeStat[] = 'empty!';
					}
				}
			}

			$ret['status'] = 'valid';
			$ret['msg_html'] = implode('<br />', $__removeStat);
			if ( $retType == 'die' ) die(json_encode($ret));
			else return $ret;
		}

		public function attrclean_splitTitle($title) {
			$extra = array(
				'ASIN' => 'ASIN',
				'CEROAgeRating' => 'CERO Age Rating',
				'EAN' => 'EAN',
				'EANList' => 'EAN List',
				'EANListElement' => 'EAN List Element',
				'EISBN' => 'EISBN',
				'ESRBAgeRating' => 'ESRB Age Rating',
				'HMAC' => 'HMAC',
				'IFrameURL' => 'IFrame URL',
				'ISBN' => 'ISBN',
				'MPN' => 'MPN',
				'ParentASIN' => 'Parent ASIN',
				'PurchaseURL' => 'Purchase URL',
				'SKU' => 'SKU',
				'UPC' => 'UPC',
				'UPCList' => 'UPC List',
				'UPCListElement' => 'UPC List Element',
				'URL' => 'URL',
				'URLEncodedHMAC' => 'URL Encoded HMAC',
				'WEEETaxValue' => 'WEEE Tax Value'
			);
			
			if ( in_array($title, array_keys($extra)) ) {
				return $extra["$title"];
			}
			
			preg_match_all('/((?:^|[A-Z])[a-z]+)/', $title, $matches, PREG_PATTERN_ORDER);
			return implode(' ', $matches[1]);
		}
		
		/**
		 * Update november 2014
		 */
		public function productPriceSetMeta( $thisProd, $post_id='', $return=true ) {
			$ret = array();
			$o = array(
				'ItemAttributes'		=> isset($thisProd['ItemAttributes']['ListPrice']) ? array('ListPrice' => $thisProd['ItemAttributes']['ListPrice']) : array(),
				'Offers'				=> isset($thisProd['Offers']) ? $thisProd['Offers'] : array(),
				'OfferSummary'			=> isset($thisProd['OfferSummary']) ? $thisProd['OfferSummary'] : array(),
				'VariationSummary'		=> isset($thisProd['VariationSummary']) ? $thisProd['VariationSummary'] : array(),
			);
			update_post_meta($post_id, '_amzaff_amzRespPrice', $o);
			
			// Offers/Offer/OfferListing/IsEligibleForSuperSaverShipping
			if ( isset($o['Offers']['Offer']['OfferListing']['IsEligibleForSuperSaverShipping']) ) {
				$ret['isSuperSaverShipping'] = $o['Offers']['Offer']['OfferListing']['IsEligibleForSuperSaverShipping'] === true ? 1 : 0;
				update_post_meta($post_id, '_amzaff_isSuperSaverShipping', $ret['isSuperSaverShipping']);
			}
			
			// Offers/Offer/OfferListing/Availability
			if ( isset($o['Offers']['Offer']['OfferListing']['Availability']) ) {
				$ret['availability'] = (string) $o['Offers']['Offer']['OfferListing']['Availability'];
				update_post_meta($post_id, '_amzaff_availability', $ret['availability']);
			}
			
			return $ret;
		}

		public function productPriceSetRegularSaleMeta( $post_id, $type, $newMetas=array() ) {
			$_amzaff_price = $newMetas;
			$_amzaff_price_db = get_post_meta( $post_id, '_amzaff_'.$type.'_price', true );
			if ( !empty($_amzaff_price_db) && is_array($_amzaff_price_db) ) {
				$_amzaff_price = array_merge($_amzaff_price_db, $_amzaff_price);
			}
			update_post_meta($post_id, '_amzaff_'.$type.'_price', $_amzaff_price);
		}

		public function productPriceGetRegularSaleStatus( $post_id, $type='both' ) {
			$ret = array('regular' => 'auto', 'sale' => 'auto');
			
			foreach (array('regular', 'sale') as $priceType) {
				$meta = (array) get_post_meta( $post_id, '_amzaff_'.$priceType.'_price', true );
				if ( !empty($meta) && isset($meta["current"]) && !empty($meta["current"]) ) {
					$ret["$priceType"] = $meta["current"];
				}
			}
			if ( $type != 'both' && in_array($type, array('regular', 'sale')) ) {
				return $ret["$type"];
			}
			return $ret;
		}
	}
}