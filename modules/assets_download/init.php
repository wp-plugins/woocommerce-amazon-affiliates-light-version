<?php
/*
* Define class WooZoneLightAssetDownload
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;

if (class_exists('WooZoneLightAssetDownload') != true) {
    class WooZoneLightAssetDownload
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
		public $the_plugin = null;

		private $module_folder = '';
		private $module = '';

		static protected $_instance;
		
		private $settings;

        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct()
        {
        	global $WooZoneLight;

        	$this->the_plugin = $WooZoneLight;
			$this->module_folder = $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'modules/assets_download/';
			$this->module = $this->the_plugin->cfg['modules']['assets_download'];
			
			$this->settings = $WooZoneLight->getAllSettings('array', 'amazon');
  
			if (is_admin()) {
	            add_action('admin_menu', array( &$this, 'adminMenu' ));
			}
			
			add_action('wp_ajax_WooZoneLight_download_asset', array( &$this, 'ajax_download_asset' ));
			add_action('wp_ajax_WooZoneLightDeleteAssetsProducts', array( &$this, 'delete_products_asset' ));
			$this->__test_assets();
        }

		/**
	    * Singleton pattern
	    *
	    * @return WooZoneLightAssetDownload Singleton instance
	    */
	    static public function getInstance()
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self;
	        }

	        return self::$_instance;
	    }

		/**
	    * Hooks
	    */
	    static public function adminMenu()
	    {
	       self::getInstance()
	    		->_registerAdminPages();
	    }

	    /**
	    * Register plug-in module admin pages and menus
	    */
		protected function _registerAdminPages()
    	{ 
    		add_submenu_page(
    			$this->the_plugin->alias,
    			$this->the_plugin->alias . " " . __('Assets Download', $this->the_plugin->localizationName),
	            __('Assets Download', $this->the_plugin->localizationName),
	            'manage_options',
	            $this->the_plugin->alias . "_assets_download",
	            array($this, 'display_index_page')
	        );

			return $this;
		}

		public function display_index_page()
		{
			$this->printBaseInterface();
		}
		
		/*
		* printBaseInterface, method
		* --------------------------
		*
		* this will add the base DOM code for you options interface
		*/
		private function printBaseInterface()
		{
			global $wpdb;
?>
		<link rel='stylesheet' href='<?php echo $this->module_folder;?>app.css' type='text/css' media='all' />
		<div id="WooZoneLight-wrapper" class="fluid wrapper-WooZoneLight WooZoneLight-asset-download">
			
			<?php
			// show the top menu
			WooZoneLightAdminMenu::getInstance()->make_active('info|assets_download')->show_menu();
			?>

			<!-- Content -->
			<div id="WooZoneLight-content">
				
				<h1 class="WooZoneLight-section-headline">
					<?php 
					if( isset($this->module['assets_download']['in_dashboard']['icon']) ){
						echo '<img src="' . ( $this->module_folder . $this->module['assets_download']['in_dashboard']['icon'] ) . '" class="WooZoneLight-headline-icon">';
					}
					?>
					<?php echo $this->module['assets_download']['menu']['title'];?>
					<span class="WooZoneLight-section-info"><?php echo $this->module['assets_download']['description'];?></span>
					<?php
					$has_help = isset($this->module['assets_download']['help']) ? true : false;
					if( $has_help === true ){
						
						$help_type = isset($this->module['assets_download']['help']['type']) && $this->module['assets_download']['help']['type'] ? 'remote' : 'local';
						if( $help_type == 'remote' ){
							echo '<a href="#load_docs" class="WooZoneLight-show-docs" data-helptype="' . ( $help_type ) . '" data-url="' . ( $this->module['assets_download']['help']['url'] ) . '">HELP</a>';
						} 
					}
					echo '<a href="#load_docs" class="WooZoneLight-show-feedback" data-helptype="' . ( 'remote' ) . '" data-url="' . ( $this->the_plugin->feedback_url ) . '" data-operation="feedback">Feedback</a>';
					?>
				</h1>
				
				<!-- Main loading box -->
				<div id="WooZoneLight-main-loading">
					<div id="WooZoneLight-loading-overlay"></div>
					<div id="WooZoneLight-loading-box">
						<div class="WooZoneLight-loading-text"><?php _e('Loading', $this->the_plugin->localizationName);?></div>
						<div class="WooZoneLight-meter WooZoneLight-animate" style="width:86%; margin: 34px 0px 0px 7%;"><span style="width:100%"></span></div>
					</div>
				</div>

				<!-- Container -->
				<div class="WooZoneLight-container clearfix">

					<!-- Main Content Wrapper -->
					<div id="WooZoneLight-content-wrap" class="clearfix" style="padding-top: 5px;">

						<!-- Content Area -->
						<div id="WooZoneLight-content-area">
							<div class="WooZoneLight-grid_4">
	                        	<div class="WooZoneLight-panel">
	                        		<div class="WooZoneLight-panel-header">
										<span class="WooZoneLight-panel-title">
											<?php _e('Assets Download', $this->the_plugin->localizationName);?>
										</span>
									</div>
									<div class="WooZoneLight-panel-content">
										<form class="WooZoneLight-form" action="#save_with_ajax">
											<div class="WooZoneLight-form-row WooZoneLight-table-ajax-list" id="WooZoneLight-table-ajax-response">
											<?php
											WooZoneLightAjaxListTable::getInstance( $this->the_plugin )
												->setup(array(
													'id' 				=> 'WooZoneLightAssetDownload',
													'show_header' 		=> true,
													'search_box' 		=> false,
													'items_per_page' 	=> 5,
													'post_statuses' 	=> array(
														'publish'   => __('Published', $this->the_plugin->localizationName)
													),
													'custom_table'	=> 'amz_products',
													'columns'			=> array(

														'id'		=> array(
															'th'	=> __('Post', $this->the_plugin->localizationName),
															'td'	=> '%post_id%',
															'width' => '50'
														),
														'action'	=> array(
															'th'	=> __('Delete Asset', $this->the_plugin->localizationName),
															'td'	=> '%del_asset%',
															'width' => '50'
														),
														
														'assets'	=> array(
															'th'	=> __('Assets', $this->the_plugin->localizationName),
															'td'	=> '%post_assets%',
															'align' => 'left'
														)
													)
												))
												->print_html();
								            ?>
								            </div>
							            </form>
				            		</div>
								</div>
							</div>
							<div class="clear"></div>
							
							<div class="WooZoneLight-grid_4">
	                        	<div class="WooZoneLight-panel">
	                        		<div class="WooZoneLight-panel-header">
										<span class="WooZoneLight-panel-title">
											<?php _e('OR create cron job for doing the assets download jobs', $this->the_plugin->localizationName);?>
										</span>
									</div>
									<div class="WooZoneLight-panel-content">
										<div class="WooZoneLight-sync-details">
											<p>To configure a real cron job, you will need access to your cPanel or Admin panel (we will be using cPanel in this tutorial).</p>
											<p>1. Log into your cPanel.</p>
											<p>2. Scroll down the list of applications until you see the “<em>cron jobs</em>” link. Click on it.</p>
											<p><img width="510" height="192" class="aligncenter size-full wp-image-81" alt="wpcron-cpanel" src="<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'aa-framework';?>/images/wpcron-cpanel.png"></p>
											<p>3. Under the <em>Add New Cron Job</em> section, choose the interval that you want it to run the cron job. I have set it to run every 15minutes, but you can change it according to your liking.</p>
											<p><img width="470" height="331" class="aligncenter size-full wp-image-82" alt="wpcron-add-new-cron-job" src="<?php echo $this->the_plugin->cfg['paths']['plugin_dir_url'] . 'aa-framework/';?>/images/wpcron-add-new-cron-job.png"></p>
											<p>4. In the Command field, enter the following:</p>
										
											<div class="wp_syntax"><div class="code"><pre style="font-family:monospace;" class="bash"><span style="color: #c20cb9; font-weight: bold;">wget</span> <span style="color: #660033;">-q</span> <span style="color: #660033;">-O</span> - </span><?php echo admin_url('admin-ajax.php?action=WooZoneAssetsCron');?> <span style="color: #000000; font-weight: bold;">&gt;/</span>dev<span style="color: #000000; font-weight: bold;">/</span>null <span style="color: #000000;">2</span><span style="color: #000000; font-weight: bold;">&gt;&amp;</span><span style="color: #000000;">1</span></pre></div></div>
										
											<p>5. Click the “Add New Cron Job” button. You should now see a message like this:</p>
											<p>8. Save and upload (and replace) this file back to the server. This will disable WordPress internal cron job.</p>
											<p>That’s it.</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script type="text/javascript" src="<?php echo $this->module_folder;?>app.class.js" ></script>

<?php
		}

		/*
		* ajax_request, method
		* --------------------
		*
		* this will create requesto to 404 table
		*/
		public function ajax_request()
		{
			global $wpdb;
			$request = array(
				'id' 			=> isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0
			);
			
			$asin = get_post_meta($request['id'], '_amzASIN', true);
			
			$sync = new wwcAmazonSyncronize( $this->the_plugin );
			$sync->updateTheProduct( $asin );
		}

		
		public function delete_products_asset()
		{
			$request = array(
				'products' => isset($_REQUEST['products']) ? $_REQUEST['products'] : array()
			);
			
			if( count($request['products']) > 0 ){
				
				foreach ($request['products'] as $prod_id ) {
					$this->product_assets_delete( $prod_id );
				}
				
				die( json_encode(array(
					'status' => 'valid'
				)) ); 
			}
			
			die( json_encode(array(
				'status'		=> 'invalid',
				'msg'			=> 'Unable to delete products assets'
			)) ); 
		}
			
		/**
		 * download assets
		 */
		public function ajax_download_asset() 
		{
			$request = array(
				'id' 			=> isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0,
				'is_last_item'	=> isset($_REQUEST['is_last_item']) && $_REQUEST['is_last_item'] == 'yes' ? true : false,
				'is_first_item'	=> isset($_REQUEST['is_first_item']) && $_REQUEST['is_first_item'] == 'yes' ? true : false
			);
  
			$asset_id = $request['id'];
			if ( $asset_id == 0 ) {
				die( json_encode(array(
					'status'		=> 'invalid'
				)) );
			}
			
			$allowedRation = (int) $this->settings['ratio_prod_validate'];
			if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
			
			$importProdStatus = strtolower($this->settings['default_import']);
 
			$asset = $this->get_asset_by_id( $asset_id, true, true );
			$asset = array_shift( $asset );
			$updAssetStat = $this->upd_asset_db( $asset, $request['is_first_item'] );
			if ( $request['is_last_item'] ) {
				$updProdStat = $this->upd_prod_poststable($asset->post_id, $importProdStatus, $allowedRation);
			}

			$msg = ''; $msg_last = '';
			if ( $updAssetStat!== false ) {
				if ( isset($updAssetStat['status']) && $updAssetStat['status']=='valid' ) {
					$msg = '"' . $asset->asset . '" (ID ' . $asset->id . ') ' . __( 'was successfully downloaded and resized in', $this->the_plugin->localizationName ) . ' <code>{execution_time}</code>.';
				} else {
					$msg = '"' . $asset->asset . '" (ID ' . $asset->id . ') ' . __( 'could not be downloaded and resized - duration: ', $this->the_plugin->localizationName ) . ' <code>{execution_time}</code>.<br /><span style="color: red;">' . $updAssetStat['status'] . '</span>';
				}
			} else {
				$msg = '"' . $asset->asset . '" (ID ' . $asset->id . ') ' . __( 'could not be downloaded and resized - duration: ', $this->the_plugin->localizationName ) . ' <code>{execution_time}</code>.';
			}
			die( json_encode(array(
				'status'		=> 'valid',
				'msg' 			=> $msg, //'"img_3_large" (ID 202) was successfully downloaded and resized in <code>{execution_time}</code>.',
				'msg_last'		=> $msg_last,
				'data'			=> $request['id']
			)) );
		}
		
		public function get_asset_by_id( $asset_id, $inprogress=false, $include_err=false ) {
			return $this->get_asset_generic($asset_id, 1, 0, false, $inprogress, $include_err);
		}
		
		public function get_asset_by_postid( $nb_dw, $post_id, $include_variations, $inprogress=false, $include_err=false ) {
			return $this->get_asset_generic(0, $nb_dw, $post_id, $include_variations, $inprogress, $include_err);
		}
		
		public function get_asset_multiple( $nb_dw='all', $inprogress=false, $include_err=false ) {
			return $this->get_asset_generic(0, $nb_dw, 0, true, $inprogress, $include_err);
		}

		private function get_asset_generic( $asset_id=0, $nb_dw='all', $post_id=0, $include_variations=true, $inprogress=false, $include_err=false ) {
			global $wpdb;
			
			$asset_id = (int) $asset_id;
			$post_id = (int) $post_id;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			if ( $include_err ) $__q_dw = "and a.download_status in ('new', 'error')";
			else $__q_dw = "and a.download_status = 'new'";
			$q = "select a.id, a.post_id, a.asset, a.thumb, a.download_status, a.hash, a.media_id, b.title from " . $tables['assets'] . " as a left join " . $tables['products'] . " as b on a.post_id = b.post_id where 1=1 $__q_dw ";
			if ( is_int($asset_id) && $asset_id > 0 ) {
				$q .= "and a.id = '$asset_id' ";
			}
			if ( is_int($post_id) && $post_id > 0 ) {
				if ( $include_variations ) {
					$q .= "and ( a.post_id = '$post_id' or b.post_parent = '$post_id' ) ";
				} else {
					$q .= "and a.post_id = '$post_id' ";
				}
			}
			$q .= "order by a.id asc ";

			if ( $nb_dw == 'all' ) ;
			else {
				$nb_dw = (int) $nb_dw;
				$q .= "limit 0, $nb_dw";
			}
			$q .= ";";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$ret["{$v->id}"] = $v;
				}
			}
			
			// all selected assets have in progress status now!
			if ( $inprogress && !empty($ret) ) {
				$idList = implode(', ', array_map(array($this, 'prepareForInList'), array_keys($ret)));
				$qUpdStat = "update " . $tables['assets'] . " as a set a.download_status = 'inprogress' where 1=1 and a.id in ( $idList );";
				$statUpdStat = $wpdb->query($qUpdStat);
				if ($statUpdStat=== false) {
				}
			}
			return $ret;
		}

		private function upd_asset_db( $asset, $first_item=false ) {
			global $wpdb;
  
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			if ( !is_object($asset) && is_int($asset) && ($asset > 0) ) {
				
				$q = "select a.id, a.post_id, a.asset, a.download_status, a.hash, a.media_id, b.title from " . $tables['assets'] . " as a left join " . $tables['products'] . " as b on a.post_id = b.post_id where 1=1 and a.id = $asset;";
				$asset = $wpdb->get_row( $q, OBJECT );
			}
  
			if ( empty($asset) ) return false;
			
			$asset_id = $asset->id;
			$post_id = $asset->post_id;

			$dwimg = $this->the_plugin->download_image($asset->asset, $asset->post_id, 'insert', $asset->title, 0);
			$dwStatus = false;
  
			if ( isset($dwimg['attach_id']) && $dwimg['attach_id'] > 0 ) { // image was downloaded and inserted as media in wp posts
				$dwStatus = true;
			
				if ( $first_item ) {
					update_post_meta($post_id, "_thumbnail_id", $dwimg['attach_id']);
				} else {
					$current_thumb_id = get_post_meta($post_id, "_thumbnail_id", true);
					if ( empty($current_thumb_id) ) $current_thumb_id = 0;
					else $current_thumb_id = (int) $current_thumb_id;
					if ( $current_thumb_id == 0 || ( $current_thumb_id > $dwimg['attach_id'] ) )
						update_post_meta($post_id, "_thumbnail_id", $dwimg['attach_id']);
				}

				// build product gallery
				$current_prod_gallery = get_post_meta($post_id, "_product_image_gallery", true);
				if ( empty($current_prod_gallery) ) $__current_prod_gallery = array();
				else $__current_prod_gallery = explode(',', $current_prod_gallery);
				$__current_prod_gallery = array_merge( $__current_prod_gallery, array($dwimg['attach_id']) );
				$__current_prod_gallery = array_unique($__current_prod_gallery);
				update_post_meta($post_id, "_product_image_gallery", implode(',', $__current_prod_gallery));
			}
			
			$mediaValues = (object) array(
				'download_status'		=> ( $dwStatus ? 'success' : 'error' ),
				'hash'				=> ( $dwStatus ? $dwimg['hash'] : null ),
				'media_id'			=> ( $dwStatus ? $dwimg['attach_id'] : 0 ),
				'msg'				=> ( $dwStatus ? 'success' : $dwimg['msg'] )
			);
			
			// update row in assets table
			$statUpdAsset = $wpdb->update(
				$tables['assets'],
				array(
					'download_status'	=> $mediaValues->download_status,
					'hash'				=> $mediaValues->hash,
					'media_id'			=> $mediaValues->media_id,
					'date_download'		=> date("Y-m-d H:i:s"),
					'msg'				=> $mediaValues->msg
				),
				array( 'id' => $asset_id ),
				array(
					'%s', '%s', '%d', '%s', '%s'
				),
				array( '%d' )
			);
			if ($statUpdAsset=== false || !$dwStatus) {
				return array(
					'status'		=> 'invalid',
					'msg'			=> $mediaValues->msg
				);
			}
			
			// update row in products table
			/*$wpdb->update(
				$tables['products'],
				array(
					'nb_assets_done'		=> $nb_assets_done + 1
				),
				array( 'post_id' => $post_id ),
				array(
					'%d'
				),
				array( '%d' )
			);*/
			$qUpdProd = "update " . $tables['products'] . " as a set a.nb_assets_done = a.nb_assets_done + 1 where 1=1 and a.post_id = $post_id;";
			$statUpdProd = $wpdb->query($qUpdProd);
			if ($statUpdProd=== false) {
			}
			return array(
				'status'		=> 'valid'
			);
		}

		public function upd_prod_poststable( $post_id, $new_status='draft', $allowedRatio='75' ) {
			global $wpdb;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			$q = "select a.post_id, a.post_parent, (a.nb_assets_done / a.nb_assets) * 100 as ratio from " . $tables['products'] . " as a where 1=1 and ( a.post_id = $post_id or a.post_parent = $post_id );";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array(); $ratioTotal = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$key = empty($v->post_parent) ? $v->post_id : $v->post_parent;
					$ret["$key"] = $v;
					$ratioTotal[] = (int) $v->ratio;
				}
			}
			if ( empty($ret) ) return false;
			
			$ratioTotal = ( array_sum($ratioTotal) / count($ret) );
			$ratioTotal = number_format( $ratioTotal, 2 );

			// verify if ratio allow product update in wp posts table!
			if ( $ratioTotal < $allowedRatio ) {
				return false;
			}

			$idList = implode(', ', array_map(array($this, 'prepareForInList'), array_keys($ret)));

			$qUpdProd = "update " . $tables['products'] . " as a set a.status = 'success' where 1=1 and ( a.post_id in ( $idList ) or a.post_parent in ( $idList ) );";
			$statUpdProd = $wpdb->query($qUpdProd);
			if ($statUpdProd=== false) {
			}
			
			$qUpdStat = "update " . ($wpdb->prefix . 'posts') . " as a set a.post_status = '$new_status' where 1=1 and a.ID in ( $idList );";
			$statUpdStat = $wpdb->query($qUpdStat);
			if ($statUpdStat=== false) {
				return false;
			}
			return true;
		}
		
		public function verifyProdImageHash( $hash ) {
			global $wpdb;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			$q = "select a.id, a.post_id, a.media_id from " . $tables['assets'] . " as a where 1=1 and a.hash regexp '$hash' limit 1;";
			$res = $wpdb->get_row( $q, OBJECT );
			
			if ( !empty($res) && isset($res->media_id) ) {
				$attach = wp_get_attachment_metadata( $res->media_id );
				$file = is_array($attach) && !empty($attach) && isset($attach['file']) ? $attach['file'] : '';
				
				// Find Upload dir path
				$uploads = wp_upload_dir();
				$uploads_path = $uploads['path'] . '';

				$image_path = $uploads_path . '/' . basename($file);
				if ( !empty($file) && $this->the_plugin->verifyFileExists($image_path) ) {
					$res->image_path = $image_path;
					return $res;
				}
			}
			return false;
		}
		
		public function cronjob() {
			$cronNbImages = (int) $this->settings['cron_number_of_images'];
			$assetsList = $this->get_asset_multiple( $cronNbImages, true, false );
			if ( count($assetsList) <= 0 ) return false;
  
			$post_id_list = array();
			foreach( $assetsList as $k=>$asset ) {
				$this->upd_asset_db( $asset );
				$post_id_list[] = $asset->post_id;
			}
			var_dump('<pre>products: ',$post_id_list,'</pre>');  
  
			// update product status for the above assets products!
			$allowedRation = (int) $this->settings['ratio_prod_validate'];
			if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
			
			$importProdStatus = strtolower($this->settings['default_import']);

			if ( !empty($post_id_list) ) {
				foreach ($post_id_list as $key => $value) {
					$updProdStat = $this->upd_prod_poststable($value, $importProdStatus, $allowedRation);
					var_dump('<pre>',$value, $updProdStat,'</pre>'); 
				}
			}
		}
		
		public function update_products_status_all() {
			global $wpdb;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');
			
			$q = "select distinct(a.post_id) from " . $tables['products'] . " as a where 1=1 and status = 'new' order by a.post_id asc;";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$ret["{$v->post_id}"] = $v;
				}
			}
			$post_id_list = array_keys($ret);
			
			if ( empty($post_id_list) || !is_array($post_id_list) ) return false;
			
			$allowedRation = (int) $this->settings['ratio_prod_validate'];
			if ( $allowedRation <= 0 || $allowedRation > 100 ) $allowedRation = 90;
			
			$importProdStatus = strtolower($this->settings['default_import']);
			
			var_dump('<pre>', 'post_id', 'status','</pre>'); 
			foreach ($post_id_list as $key => $value) {
				// update product status for the above assets products!
				$updProdStat = $this->upd_prod_poststable($value, $importProdStatus, $allowedRation);
				var_dump('<pre>',$value, $updProdStat,'</pre>');  
			}
			echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}
		
		public function get_assets_bystatus($status) {
			global $wpdb;
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');
			
			$q = "select a.* from " . $tables['assets'] . " as a where 1=1 and a.download_status = '" . $status . "' order by a.id asc;";
			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$ret["{$v->id}"] = $v;
				}
			}
			var_dump('<pre>', $ret, '</pre>'); die('debug...'); 
		}
		
		public function product_assets_delete( $post_id ) {
			global $wpdb;
			
			$post_id = (int) $post_id;

			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');

			$q = "select a.post_id from " . $tables['products'] . " as a where 1=1 ";
			$q .= " and ( a.post_id = '$post_id' or a.post_parent = '$post_id' ) ";
			$q .= " order by a.id asc ";
			$q .= ";";

			$res = $wpdb->get_results( $q, OBJECT );

			$ret = array();
			if (is_array($res) && count($res)>0) {
				foreach ($res as $k=>$v) {
					$ret[] = $v->post_id;
				}
			}
			
			if ( !empty($ret) ) {
				$idList = implode(', ', array_map(array($this, 'prepareForInList'), array_values($ret)));

				$q = "delete from " . $tables['assets'] . " where 1=1 and post_id in ( $idList );";
				$res = $wpdb->query( $q );
				
				$q2 = "delete from " . $tables['products'] . " where 1=1 and post_id in ( $idList );";
				$res2 = $wpdb->query( $q2 );
			}
			return true;
		}

		public function __restore_assets_bystatus($status='error') {
			global $wpdb;
			
			$tables = array('assets' => $wpdb->prefix . 'amz_assets', 'products' => $wpdb->prefix . 'amz_products');
			
			$q = "update " . $tables['assets'] . " set download_status = 'new' where 1=1 and download_status = '" . $status . "';";
			$res = $wpdb->query( $q );
			var_dump('<pre>', $res, '</pre>'); die('debug...');  
		}
		
		public function __test_assets() {

			//$this->get_assets_bystatus('error');
			//$this->__restore_assets_bystatus('error');
			
			//$this->get_assets_bystatus('inprogress');
			//$this->__restore_assets_bystatus('inprogress');
			
			// $this->update_products_status_all();

			// $this->get_asset_by_id(6);
			$assets = $this->get_asset_by_postid('all', 43, true);
			if ( !empty($assets) ) {
				// array_shift($assets)
				foreach ($assets as $k=>$v) {
					// $this->upd_asset_db( $v );
				}
			}
			// $this->upd_prod_poststable(83, 'publish', 90);
		}

		
		/**
		 * Utils
		 */
		private function prepareForInList($v) {
			return "'".$v."'";
		}
    }
}

if ( !function_exists('WooZoneLightAssetDownload_cronjob') ) {
function WooZoneLightAssetDownload_cronjob() {
	// Initialize the WooZoneLightAssetDownload class
	$amzaffAssetDownload = new WooZoneLightAssetDownload();
	$amzaffAssetDownload->cronjob();
}
}

// Initialize the WooZoneLightAssetDownload class
$WooZoneLightAssetDownload = WooZoneLightAssetDownload::getInstance();