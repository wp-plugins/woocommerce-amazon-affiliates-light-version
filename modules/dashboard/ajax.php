<?php
/*
* Define class WooZoneLightDashboardAjax
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLightDashboardAjax') != true) {
    class WooZoneLightDashboardAjax extends WooZoneLightDashboard
    {
    	public $the_plugin = null;
		private $module_folder = null;
		
		/*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $the_plugin=array() )
        {
        	$this->the_plugin = $the_plugin;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/dashboard/';
			
			// ajax  helper
			add_action('wp_ajax_WooZoneLightDashboardRequest', array( &$this, 'ajax_request' ));
		}
		
		/*
		* ajax_request, method
		* --------------------
		*
		* this will create requests to 404 table
		*/
		public function ajax_request()
		{
			$return = array();
			
			$actions = isset($_REQUEST['sub_actions']) ? explode(",", $_REQUEST['sub_actions']) : '';

			if( in_array( 'products_performances', $actions) ){
				
				$prod_per_page = isset($_REQUEST['prod_per_page']) ? $_REQUEST['prod_per_page'] : 12;
				$products_response = $this->getPublishProductsWidthStatus( $prod_per_page );
				
				if( count($products_response['products']) == 0 ){
					$html[] = '<div class="WooZoneLight-message blue">You need to import some Amazon products first!</div>';
				}
				else{
					
					$html[] = '<div class="WooZoneLight-products-summary">';
					$html[] = '<div class="the-item-stat">
			                        <span style="background-color:#5A1977;" class="WooZoneLight-summary-icon">
			                        	<img src="' . ( $this->module_folder . 'assets/' ) . 'total_products.png">
			                        </span>
			                        <span class="WooZoneLight-summary-text">
			                            <span>' . ( $products_response['stats']['nb_products'] ) . '</span>
			                            <span>Total Number of products</span>
			                        </span>
			                    </div>';
								
					$html[] = '<div class="the-item-stat">
			                        <span style="background-color:#5A1977;" class="WooZoneLight-summary-icon">
			                        	<img src="' . ( $this->module_folder . 'assets/' ) . 'view.png">
			                        </span>
			                        <span class="WooZoneLight-summary-text">
			                            <span>' . ( $products_response['stats']['total_hits'] ) . '</span>
			                            <span>Total products views</span>
			                        </span>
			                    </div>';
					
					$html[] = '<div class="the-item-stat">
			                        <span style="background-color:#5A1977;" class="WooZoneLight-summary-icon">
			                        	<img src="' . ( $this->module_folder . 'assets/' ) . 'redirect_amazon.png">
			                        </span>
			                        <span class="WooZoneLight-summary-text">
			                            <span>' . ( $products_response['stats']['total_redirect_to_amazon'] ) . '</span>
			                            <span>Total redirected to Amazon</span>
			                        </span>
			                    </div>';
					
								
					$html[] = '</div>';
	
					$html[] = 	'<ul class="WooZoneLight-top-products">';
					
					if( count($products_response['products']) > 0 ){
						$pos = 0;
	 
						foreach ($products_response['products'] as $product ) {
							$html[] = 		'<li>';
							$html[] = 			'<div class="WooZoneLight-prod-position"><span>#' . ( ++$pos ) . '</span></div>';
							$html[] = 			'<a href="' . ( admin_url('post.php?post=' . ( $product['id'] ) . '&action=edit') ) . '" target="_blank" class="WooZoneLight-the-product">';
							$html[] = 				'<div class="WooZoneLight-the-product-image">' . ( get_the_post_thumbnail( $product['id'], array(75, 75) ) ) . '</div>';
							$html[] = 				'<span>Views: <strong>' . ( $product['hits'] ) . '</strong></span>';
							$html[] = 				'<span>Redirect to Amazon: <strong>' . ( $product['redirect_to_amazon'] ) . '</strong></span>';
							$html[] = 			'</a>';
							$html[] = 		'</li>';
						}
					}
					$html[] = 	'</ul>';
				}
				
				$return['products_performances'] = array(
					'status' => 'valid',
					'html' => implode("\n", $html)
				);
			}
			

			die(json_encode($return));
		}

		private function getPublishProductsWidthStatus( $limit=0 )
		{
		
			$ret = array();
			
			$args = array();
			$ret['products'] = array();
			$ret['stats']['nb_products'] = 0;
			$ret['stats']['total_hits'] = 0;
			$ret['stats']['total_redirect_to_amazon'] = 0;
				
			$args['post_type'] = 'product';
	
			$args['meta_key'] = '_amzASIN';
			$args['meta_value'] = '';
			$args['meta_compare'] = '!=';
	
			// show all posts
			$args['fields'] = 'ids';
			$args['posts_per_page'] = '-1';
			
			$loop = new WP_Query( $args );
			$cc = 0;
			 
			if( count($loop->posts) > 0 ){
				
				$stats_query = "SELECT post_id, meta_key, meta_value FROM " . (  $this->the_plugin->db->prefix ) . "postmeta WHERE 1=1 AND post_id IN (" . ( implode(",", $loop->posts) ) . ")";
				$stats_query .= " AND ( meta_key='_amzaff_redirect_to_amazon' ";
				$stats_query .= " OR meta_key='_amzaff_hits' )";  
				
				$stats_results = $this->the_plugin->db->get_results( $stats_query, ARRAY_A );
				
				$products_status = array();
				// reodering here
				if( count($stats_results) > 0 ){
					foreach ($stats_results as $row ) {
						$products_status[$row['post_id']][$row['meta_key']] = $row['meta_value'];
					}
				}
				
				foreach ($loop->posts as $post) {
					
					$redirect_to_amazon = ( isset($products_status[$post]['_amzaff_redirect_to_amazon']) ? (int) $products_status[$post]['_amzaff_redirect_to_amazon'] : 0 );
					$hits = ( isset($products_status[$post]['_amzaff_hits']) ? (int) $products_status[$post]['_amzaff_hits'] : 0 );
					$score = ($redirect_to_amazon * 3) + ($hits * 1);
					
					$ret['products'][$post] = array(
						'id' => $post,
						'score' => $score,
						'redirect_to_amazon' => $redirect_to_amazon,
						'hits' => $hits
					);
					
					$ret['stats']['nb_products'] = $ret['stats']['nb_products'] + 1;
					$ret['stats']['total_hits'] = $ret['stats']['total_hits'] + $hits;
					$ret['stats']['total_redirect_to_amazon'] = $ret['stats']['total_redirect_to_amazon'] + $redirect_to_amazon;
				} 
			}
			
			if( count($ret['products']) > 0 ){
				// reorder the products as a top
				$ret['products'] = $this->sort_hight_to_low( $ret['products'], 'score' );
				
				// limit the return, if request
				if( (int) $limit != 0 ){
					$ret['products'] = array_slice($ret['products'], 0, $limit);
				}
			}
			 
			return $ret;
		}
		
		function sort_hight_to_low( $a, $subkey ) 
		{
		    foreach($a as $k=>$v) {
		        $b[$k] = strtolower($v[$subkey]);
		    }
		    arsort($b);
		    foreach($b as $key=>$val) {
		        $c[$key] = $a[$key];
		    }
		    return $c;
		}
		
		
	
		/**
		 * $cache_lifetime in minutes
		 */
		private function getRemote( $the_url, $cache_lifetime=60 )
		{
			// try to get from cache
			$request_alias = 'WooZoneLight_' . md5($the_url);
			$from_cache = get_option( $request_alias );
			
			if( $from_cache != false ){
				if( time() < ( $from_cache['when'] + ($cache_lifetime * 60) )){
					return $from_cache['data'];
				}
			}
			$response = wp_remote_get( $the_url, array('user-agent' => "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0", 'timeout' => 10) ); 
			
			// If there's error
            if ( is_wp_error( $response ) ){
            	return array(
					'status' => 'invalid'
				);
            }
        	$body = wp_remote_retrieve_body( $response );
			
			$response_data = json_decode( $body, true );
			
			// overwrite the cache data 
			update_option( $request_alias, array(
				'when' => time(),
				'data' => $response_data
			) );
				
	        return $response_data;
		}
    }
}