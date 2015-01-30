<?php
/**
 * AA-Team freamwork class
 * http://www.aa-team.com
 * =======================
 *
 * @package		WooZoneLight
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLight') != true) {
	class WooZoneLight {

		const VERSION = 1.0;

		// The time interval for the remote XML cache in the database (21600 seconds = 6 hours)
		const NOTIFIER_CACHE_INTERVAL = 21600;

		public $alias = 'WooZoneLight';

		public $localizationName = 'WooZoneLight';
		
		public $dev = '';
		public $debug = false;
		public $is_admin = false;

		/**
		 * configuration storage
		 *
		 * @var array
		 */
		public $cfg = array();

		/**
		 * plugin modules storage
		 *
		 * @var array
		 */
		public $modules = null;

		/**
		 * errors storage
		 *
		 * @var object
		 */
		private $errors = null;

		/**
		 * DB class storage
		 *
		 * @var object
		 */
		public $db = array();

		public $facebookInstance = null;
		public $fb_user_profile = null;
		public $fb_user_id = null;

		private $plugin_hash = null;
		private $v = null;
		
		public $amzHelper = null;
		
		public $jsFiles = array();
		
		public $wp_filesystem = null;
		
		private $opStatusMsg = array(
			'operation'			=> '',
			'msg'				=> ''
		);
		
		public $charset = '';
		
		public $pluginDepedencies = null;
		public $pluginName = 'Amazon Affiliates';
		
		public $feedback_url = "http://aa-team.com/feedback/index.php?app=%s&refferer_url=%s";


		/**
		 * The constructor
		 */
		function __construct($here = __FILE__)
		{
			$this->is_admin = is_admin() === true ? true : false;
			
			//$current_url = $_SERVER['HTTP_REFERER'];
			$current_url = $this->get_current_page_url();
			$this->feedback_url = sprintf($this->feedback_url, $this->alias, rawurlencode($current_url));
 
        	// load WP_Filesystem 
			include_once ABSPATH . 'wp-admin/includes/file.php';
		   	WP_Filesystem();
			global $wp_filesystem;
			$this->wp_filesystem = $wp_filesystem;

			$this->update_developer();

			$this->plugin_hash = get_option('WooZoneLight_hash');

			// set the freamwork alias
			$this->buildConfigParams('default', array( 'alias' => $this->alias ));

			// get the globals utils
			global $wpdb;

			// store database instance
			$this->db = $wpdb;

			// instance new WP_ERROR - http://codex.wordpress.org/Function_Reference/WP_Error
			$this->errors = new WP_Error();
			
			// charset
			$amazon_settings = $this->getAllSettings('array', 'amazon');
			if ( isset($amazon_settings['charset']) && !empty($amazon_settings['charset']) ) $this->charset = $amazon_settings['charset'];

			// plugin root paths
			$this->buildConfigParams('paths', array(
				// http://codex.wordpress.org/Function_Reference/plugin_dir_url
				'plugin_dir_url' => str_replace('aa-framework/', '', plugin_dir_url( (__FILE__)  )),

				// http://codex.wordpress.org/Function_Reference/plugin_dir_path
				'plugin_dir_path' => str_replace('aa-framework/', '', plugin_dir_path( (__FILE__) ))
			));

			// add plugin lib design paths and url
			$this->buildConfigParams('paths', array(
				'design_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'lib/design',
				'design_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'lib/design'
			));
   
			// add plugin scripts paths and url
			$this->buildConfigParams('paths', array(
				'scripts_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'lib/scripts',
				'scripts_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'lib/scripts'
			));

			// add plugin admin paths and url
			$this->buildConfigParams('paths', array(
				'freamwork_dir_url' => $this->cfg['paths']['plugin_dir_url'] . 'aa-framework/',
				'freamwork_dir_path' => $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/'
			));

			// add core-modules alias
			$this->buildConfigParams('core-modules', array(
				'dashboard',
				'modules_manager',
				'setup_backup',
				'remote_support',
				'server_status',
				'support',
				'assets_download',
				'stats_prod',
				'price_select'
			));

			// list of freamwork css files
			$this->buildConfigParams('freamwork-css-files', array(
				'core' => 'css/core.css',
				'panel' => 'css/panel.css',
				'form-structure' => 'css/form-structure.css',
				'form-elements' => 'css/form-elements.css',
				'form-message' => 'css/form-message.css',
				'button' => 'css/button.css',
				'table' => 'css/table.css',
				'tipsy' => 'css/tooltip.css',
				'admin' => 'css/admin-style.css'
			));

			// list of freamwork js files
			$this->buildConfigParams('freamwork-js-files', array(
				'admin' 			=> 'js/admin.js',
				'hashchange' 		=> 'js/hashchange.js',
				'ajaxupload' 		=> 'js/ajaxupload.js',
				'download_asset'	=> '../modules/assets_download/app.class.js'
			));
			
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/menu.php' );

			// Run the plugins section load method
			add_action('wp_ajax_WooZoneLightLoadSection', array( &$this, 'load_section' ));

			// Plugin Depedencies Verification!
			if (get_option('WooZoneLight_depedencies_is_valid', false)) {
				require_once( $this->cfg['paths']['scripts_dir_path'] . '/plugin-depedencies/plugin_depedencies.php' );
				$this->pluginDepedencies = new aaTeamPluginDepedencies( $this );

				// activation redirect to depedencies page
				if (get_option('WooZoneLight_depedencies_do_activation_redirect', false)) {
					add_action('admin_init', array($this->pluginDepedencies, 'depedencies_plugin_redirect'));
					return false;
				}
   
   				// verify plugin library depedencies
				$depedenciesStatus = $this->pluginDepedencies->verifyDepedencies();
				if ( $depedenciesStatus['status'] == 'valid' ) {
					// go to plugin license code activation!
					add_action('admin_init', array($this->pluginDepedencies, 'depedencies_plugin_redirect_valid'));
				} else {
					// create depedencies page
					add_action('init', array( $this->pluginDepedencies, 'initDepedenciesPage' ), 5);
					return false;
				}
			}
			
			// Run the plugins initialization method
			add_action('init', array( &$this, 'initThePlugin' ), 5);

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLightSaveOptions', array( &$this, 'save_options' ));

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLightModuleChangeStatus', array( &$this, 'module_change_status' ));

			// Run the plugins section options save method
			add_action('wp_ajax_WooZoneLightInstallDefaultOptions', array( &$this, 'install_default_options' ));

			// Amazon helper, import new product
			add_action('wp_ajax_WooZoneLightPriceUpdate', array( &$this, 'productPriceUpdate_frm' ));

			add_action('wp_ajax_WooZoneLightUpload', array( &$this, 'upload_file' ));
			add_action('wp_ajax_WooZoneLightDismissNotice', array( &$this, 'dismiss_notice' ));
			
			if(is_admin()){
				add_action('admin_head', array( &$this, 'createInstanceFreamwork' ));
				$this->check_if_table_exists();
			}

			add_action('admin_init', array($this, 'plugin_redirect'));
			
			if( $this->debug == true ){
				add_action('wp_footer', array($this, 'print_plugin_usages') );
				add_action('admin_footer', array($this, 'print_plugin_usages') );
			}
			
			add_action( 'admin_init', array($this, 'product_assets_verify') );

			if(!is_admin()){
				add_action( 'init' , array( $this, 'frontpage' ) );

				add_shortcode( 'amz_corss_sell', array($this, 'cross_sell_box') );
			}

			add_action( 'admin_bar_menu', array($this, 'update_notifier_bar_menu'), 1000 );
			add_action( 'admin_menu', array($this, 'update_plugin_notifier_menu'), 1000 );

			$this->check_amz_multiple_cart();
			
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/ajax-list-table.php' );
			new WooZoneLightAjaxListTable( $this );
			
			add_action( 'woocommerce_after_add_to_cart_button', array($this, 'woocommerce_external_add_to_cart'), 10 );
			
			$config = @unserialize( get_option( $this->alias . '_amazon' ) ); 
			$p_type = ((isset($config['onsite_cart']) && $config['onsite_cart'] == "no") ? 'external' : 'external');
			
			if( $p_type == 'simple' ) add_action( 'woocommerce_checkout_init', array($this, 'woocommerce_external_checkout'), 10 );
			
			if( isset($config['AccessKeyID']) &&  isset($config['SecretAccessKey']) && trim($config['AccessKeyID']) != "" && $config['SecretAccessKey'] != "" ){
				require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/amz.helper.class.php' );
				
				if( class_exists('WooZoneLightAmazonHelper') ){
					// $this->amzHelper = new WooZoneLightAmazonHelper( $this );
					$this->amzHelper = WooZoneLightAmazonHelper::getInstance( $this );
				}
			}

			// ajax download lightbox
			add_action('wp_ajax_WooZoneLightDownoadAssetLightbox', array( $this, 'download_asset_lightbox' ));
			
			// admin ajax action
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/utils/action_admin_ajax.php' );
			new WooZoneLight_ActionAdminAjax( $this );
			
			// load the plugin styles
			add_action('wp_ajax_WooZoneAdminStyles', array( &$this, 'admin_styles' ));
			
			// hook the assets cron job
			add_action('wp_ajax_WooZoneAssetsCron', array( &$this, 'assets_cron' ));
			add_action('wp_ajax_nopriv_WooZoneAssetsCron', array( &$this, 'assets_cron' ));
		}
		
		public function assets_cron()
		{
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'modules/assets_download/init.php' );

			@ini_set('max_execution_time', 0);
			@set_time_limit(0); // infinte
			WooZoneLightAssetDownload_cronjob();
			
			die('done!');
		}

		public function admin_styles()
		{
			require_once( $this->cfg['paths']['plugin_dir_path'] . 'aa-framework/load-styles.php' );
			die;
		}
		
		public function dismiss_notice()
		{
			update_option( $this->alias . "_dismiss_notice" , "true" );
			header( 'Location: ' . sprintf( admin_url('admin.php?page=%s'), $this->alias ) );
			die;
		}

		public function opStatusMsgInit() {
			$this->opStatusMsg = array(
				'operation'			=> '',
				'msg'				=> ''
			);
		}
		public function opStatusMsgGet() {
			return $this->opStatusMsg;
		}

		private function check_if_table_exists()
		{
			$amz_assets_table_name = $this->db->prefix . "amz_assets";
	        if ($this->db->get_var("show tables like '$amz_assets_table_name'") != $amz_assets_table_name) {
	            $sql = "CREATE TABLE " . $amz_assets_table_name . " (
					`id` BIGINT(15) UNSIGNED NOT NULL AUTO_INCREMENT,
					`post_id` INT(11) NOT NULL,
					`asset` VARCHAR(225) NULL DEFAULT NULL,
					`thumb` VARCHAR(225) NULL DEFAULT NULL,
					`download_status` ENUM('new','success','inprogress','error') NULL DEFAULT 'new',
					`hash` VARCHAR(32) NULL DEFAULT NULL,
					`media_id` INT(11) NULL DEFAULT '0',
					`msg` TEXT NULL,
					`date_added` DATETIME NULL DEFAULT NULL,
					`date_download` DATETIME NULL DEFAULT NULL,
					PRIMARY KEY (`id`),
					INDEX `post_id` (`post_id`),
					INDEX `hash` (`hash`),
					INDEX `media_id` (`media_id`),
					INDEX `download_status` (`download_status`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	
	            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	            dbDelta($sql);
	        }
			
			$amz_products_table_name = $this->db->prefix . "amz_products";
	        if ($this->db->get_var("show tables like '$amz_products_table_name'") != $amz_products_table_name) {
	            $sql = "CREATE TABLE " . $amz_products_table_name . " (
					`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
					`post_id` INT(11) NOT NULL,
					`post_parent` INT(11) NULL DEFAULT '0',
					`type` ENUM('post','variation') NULL DEFAULT 'post',
					`title` TEXT NULL,
					`nb_assets` INT(4) NULL DEFAULT '0',
					`nb_assets_done` INT(4) NULL DEFAULT '0',
					`status` ENUM('new','success') NULL DEFAULT 'new',
					PRIMARY KEY (`post_id`, `id`),
					UNIQUE INDEX `post_id` (`post_id`),
					INDEX `post_parent` (`post_parent`),
					INDEX `type` (`type`),
					INDEX `nb_assets` (`nb_assets`),
					INDEX `nb_assets_done` (`nb_assets_done`),
					INDEX `id` (`id`),
					INDEX `status` (`status`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
	
	            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	            dbDelta($sql);
	        }
		}
		
		public function update_developer()
		{
			if ( in_array($_SERVER['REMOTE_ADDR'], array('86.124.69.217', '86.124.76.250')) ) {
				$this->dev = 'andrei';
			}
			else{
				$this->dev = 'gimi';
			}
		}
		
		/**
		 * Output the external product add to cart area.
		 *
		 * @access public
		 * @subpackage	Product
		 * @return void
		 */

		public function woocommerce_external_add_to_cart()
		{ 
			echo '<script>jQuery(".single_add_to_cart_button").attr("target", "_blank");</script>'; 
		}
		
		public function check_amz_multiple_cart()
		{
			$amz_cross_sell = isset($_GET['amz_cross_sell']) ? $_GET['amz_cross_sell'] : false;
			if( $amz_cross_sell != false ){
				$asins = isset($_GET['asins']) ? $_GET['asins'] : '';

				if( trim($asins) != "" ){
					$asins = explode(',', $asins);
					if( count($asins) > 0 ){
						$amazon_settings = $this->getAllSettings('array', 'amazon');

						$GLOBALS['WooZoneLight'] = $this;
						// load the amazon webservices client class
						require_once( $this->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php');

						// create new amazon instance
						$aaAmazonWS = new aaAmazonWS(
							$amazon_settings['AccessKeyID'],
							$amazon_settings['SecretAccessKey'],
							$amazon_settings['country'],
							$this->main_aff_id()
						);

						$selectedItems = array();
						foreach ($asins as $key => $value){
							$selectedItems[] = array(
								'offerId' => $value,
								'quantity' => 1
							);
						}


						// debug only
						$aaAmazonWS->cartKill();

						$cart = $aaAmazonWS->responseGroup('Cart')->cartThem($selectedItems);

						$cart_items = isset($cart['CartItems']['CartItem']) ? $cart['CartItems']['CartItem'] : array();
						if( count($cart_items) > 0 ){
							header('Location: ' . $cart['PurchaseURL'] . "&tag=" . $amazon_settings['AffiliateId']);
							exit();
						}
					}
				}
			}
		}

		public function frontpage()
		{
			global $product;
			$amazon_settings = $this->getAllSettings('array', 'amazon');
			if( isset($amazon_settings['remove_gallery']) && $amazon_settings['remove_gallery'] == 'no' ){
				add_filter( 'the_content', array($this, 'remove_gallery'), 6);
			}
			add_filter( 'woocommerce_get_price_html', array($this, 'amz_disclaimer_price_html'), 100, 2 );
			
			
			if( !wp_script_is('WooZoneLight-frontend') ) {
				wp_enqueue_script( 'WooZoneLight-frontend' , $this->cfg['paths']['plugin_dir_url'] . '/lib/frontend/frontend.js', array( 'jquery' ) );
			}
			
			if( !wp_script_is('thickbox') ) {
				wp_enqueue_script('thickbox', null,  array('jquery'));
			}
			if( !wp_style_is('thickbox.css') ) {
				wp_enqueue_style('thickbox.css',  '/' . WPINC . '/js/thickbox/thickbox.css', null, '1.0');
			}

			$redirect_asin = (isset($_REQUEST['redirectAmzASIN']) && $_REQUEST['redirectAmzASIN']) != '' ? $_REQUEST['redirectAmzASIN'] : '';
			if( isset($redirect_asin) && strlen($redirect_asin) == 10 ) $this->redirect_amazon($redirect_asin);  

			$redirect_cart = (isset($_REQUEST['redirectCart']) && $_REQUEST['redirectCart']) != '' ? $_REQUEST['redirectCart'] : '';
			if( isset($redirect_cart) && $redirect_cart == 'true' ) $this->redirect_cart();
		}
		
		
		public function amz_disclaimer_price_html( $price, $product ){
			$post_id = isset($product->id) ? $product->id : 0;
			if ( $post_id <=0 ) return $price;

			global $woocommerce_loop;

			if ( !is_product() || isset( $woocommerce_loop ) || !$product->get_price() || !$this->verify_product_isamazon($post_id) ) return $price;

			$price_update_date = get_post_meta($post_id, "_price_update_date", true);
			$price_update_date = date('F j, Y, g:i a', $price_update_date);
  
			//<ins><span class="amount">£26.99</span></ins>
			$text = '&nbsp;<em class="WooZoneLight-price-info">' . sprintf( __('(as of %s)', $this->localizationName), $price_update_date) . '</em>';
			//$text .= $this->amz_product_free_shipping($post_id);
    		return str_replace( '</ins>', '</ins>' . $text, $price );
		}
		
		public function amz_availability( $availability, $product ) {
			//change text "In Stock' to 'available'
    		//if ( $_product->is_in_stock() )
			//	$availability['availability'] = __('available', 'woocommerce');
  
    		//change text "Out of Stock' to 'sold out'
    		//if ( !$_product->is_in_stock() )
			//	$availability['availability'] = __('sold out', 'woocommerce');

			$post_id = isset($product->id) ? $product->id : 0;
			if ( $post_id > 0 ) {
				$meta = get_post_meta($post_id, '_amzaff_availability', true);
				if ( !empty($meta) ) {
					$availability['availability'] = /*'<img src="shipping.png" width="24" height="18" alt="Shipping availability" />'*/'' . $meta;
					$availability['class'] = 'WooZoneLight-availability-icon';
				}
			}
			return $availability;
		}
		
		public function _get_current_amazon_aff() {
			/*$get_user_location = wp_remote_get( 'http://api.hostip.info/country.php?ip=' . $_SERVER["REMOTE_ADDR"] );
			if( isset($get_user_location->errors) ) {
				$main_aff_site = $this->main_aff_site();
				$user_country = $this->amzForUser( strtoupper(str_replace(".", '', $main_aff_site)) );
			}else{
				$user_country = $this->amzForUser($get_user_location['body']);
			}*/
			$user_country = $this->get_country_perip_external();
  
			// $config = @unserialize( get_option( $this->alias . '_amazon' ) );
			
			$ret = array(
				//'main_aff_site' 			=> $main_aff_site,
				'user_country'				=> $user_country,
			);
			return $ret;
		}


		public function main_aff_id()
		{
			$config = @unserialize( get_option( $this->alias . '_amazon' ) );
			if( isset($config['main_aff_id']) && isset($config['AffiliateID'][$config['main_aff_id']]) ) {
				return $config['AffiliateID'][$config['main_aff_id']];
			}

			return '';  
		}
		
		public function main_aff_site()
		{
			$config = @unserialize( get_option( $this->alias . '_amazon' ) );
			if( isset($config['main_aff_id']) && isset($config['AffiliateID'][$config['main_aff_id']]) ) {

				if( $config['main_aff_id'] == 'com' ){
					return '.com';
				}
				elseif( $config['main_aff_id'] == 'ca' ){
					return '.ca';
				}
				elseif( $config['main_aff_id'] == 'cn' ){
					return '.cn';
				}
				elseif( $config['main_aff_id'] == 'de' ){
					return '.de';
				}
				elseif( $config['main_aff_id'] == 'in' ){
					return '.in';
				}
				elseif( $config['main_aff_id'] == 'it' ){
					return '.it';
				}
				elseif( $config['main_aff_id'] == 'es' ){
					return '.es';
				}
				elseif( $config['main_aff_id'] == 'fr' ){
					return '.fr';
				}
				elseif( $config['main_aff_id'] == 'uk' ){
					return '.co.uk';
				}
				elseif( $config['main_aff_id'] == 'jp' ){
					return '.co.jp';
				}
			}

			return '';  
		}
		
		private function amzForUser( $userCountry='US' )
		{
			$config = @unserialize( get_option( $this->alias . '_amazon' ) );
			$affIds = $config['AffiliateID'];
			$main_aff_id = $this->main_aff_id();
			$main_aff_site = $this->main_aff_site(); 

			if( $userCountry == 'US' ){
				return array(
					'key'	=> 'com',
					'website' => isset($affIds['com']) && (trim($affIds['com']) != "") ? '.com' : $main_aff_site,
					'affID'	=> isset($affIds['com']) && (trim($affIds['com']) != "") ? $affIds['com'] : $main_aff_id
				);
			}
			 
			elseif( $userCountry == 'CA' ){
				return array(
					'key'	=> 'ca',
					'website' => isset($affIds['ca']) && (trim($affIds['ca']) != "") ? '.ca' : $main_aff_site,
					'affID'	=> isset($affIds['ca']) && (trim($affIds['ca']) != "") ? $affIds['ca'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'FR' ){
				return array(
					'key'	=> 'fr',
					'website' => isset($affIds['fr']) && (trim($affIds['fr']) != "") ? '.fr' : $main_aff_site,
					'affID'	=> isset($affIds['fr']) && (trim($affIds['fr']) != "") ? $affIds['fr'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'CN' ){
				return array(
					'key'	=> 'cn',
					'website' => isset($affIds['cn']) && (trim($affIds['cn']) != "") ? '.cn' : $main_aff_site,
					'affID'	=> isset($affIds['cn']) && (trim($affIds['cn']) != "") ? $affIds['cn'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'DE' ){
				return array(
					'key'	=> 'de',
					'website' => isset($affIds['de']) && (trim($affIds['de']) != "") ? '.de' : $main_aff_site,
					'affID'	=> isset($affIds['de']) && (trim($affIds['de']) != "") ? $affIds['de'] : $main_aff_id
				);
			}

			elseif( $userCountry == 'IN' ){
				return array(
					'key'	=> 'in',
					'website' => isset($affIds['in']) && (trim($affIds['in']) != "") ? '.in' : $main_aff_site,
					'affID'	=> isset($affIds['in']) && (trim($affIds['in']) != "") ? $affIds['in'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'IT' ){
				return array(
					'key'	=> 'it',
					'website' => isset($affIds['it']) && (trim($affIds['it']) != "") ? '.it' : $main_aff_site,
					'affID'	=> isset($affIds['it']) && (trim($affIds['it']) != "") ? $affIds['it'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'JP' ){
				return array(
					'key'	=> 'jp',
					'website' => isset($affIds['jp']) && (trim($affIds['jp']) != "") ? '.co.jp' : $main_aff_site,
					'affID'	=> isset($affIds['jp']) && (trim($affIds['jp']) != "") ? $affIds['jp'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'ES' ){
				return array(
					'key'	=> 'es',
					'website' => isset($affIds['es']) && (trim($affIds['es']) != "") ? '.es' : $main_aff_site,
					'affID'	=> isset($affIds['es']) && (trim($affIds['es']) != "") ? $affIds['es'] : $main_aff_id
				);
			}
			
			elseif( $userCountry == 'GB' ){
				return array(
					'key'	=> 'uk',
					'website' => isset($affIds['uk']) && (trim($affIds['uk']) != "") ? '.co.uk' : $main_aff_site,
					'affID'	=> isset($affIds['uk']) && (trim($affIds['uk']) != "") ? $affIds['uk'] : $main_aff_id
				);
			}
			else{
				
				$website = $config["main_aff_id"];
				if( $config["main_aff_id"] == 'uk' ) $website = 'co.uk';
				if( $config["main_aff_id"] == 'jp' ) $website = 'co.jp';
				
				return array(
					'key'			=> $config["main_aff_id"],
					'website' 		=> "." . $website,
					'affID'			=> $main_aff_id
				); 
			}
		}

		/**
		 * Output the external product add to cart area.
		 *
		 * @access public
		 * @subpackage	Product
		 * @return void
		 */

		public function woocommerce_external_checkout()
		{
			if( is_checkout() == true ){
				$this->redirect_cart();
			}
		}
		
		private function redirect_cart()
		{
			global $woocommerce;
			
			if( isset($woocommerce->cart->cart_contents_count) && (int) $woocommerce->cart->cart_contents_count > 0 ){
				$amz_products = array();
				$original_product_count = $woocommerce->cart->cart_contents_count;
				foreach ( $woocommerce->cart->cart_contents as $key => $value ) {
					
					$prod_id = isset($value['variation_id']) && (int)$value['variation_id'] > 0 ? $value['variation_id'] : $value['product_id']; 
					$amzASIN = get_post_meta( $prod_id, '_amzASIN', true );
					
					// check if is a valid ASIN code 
					if( isset($amzASIN) && strlen($amzASIN) == 10 ){
						$amz_products[] = array(
							'asin' 		=> $amzASIN,
							'quantity'	=> $value['quantity'],
							'key' => $key
						);
					}
				}

				// redirect back to checkout page
				if( count($amz_products) > 0 ){
 
					/*$get_user_location = wp_remote_get( 'http://api.hostip.info/country.php?ip=' . $_SERVER["REMOTE_ADDR"] ); 
					if( isset($get_user_location->errors) ) {
						$main_aff_site = $this->main_aff_site();
						$user_country = $this->amzForUser( strtoupper(str_replace(".", '', $main_aff_site)) );
					}else{
						$user_country = $this->amzForUser($get_user_location['body']);
					}*/
					$user_country = $this->get_country_perip_external();
					
					$config = @unserialize( get_option( $this->alias . '_amazon' ) );
					
					if( isset($config["redirect_checkout_msg"]) && trim($config["redirect_checkout_msg"]) != "" ){
						echo '<img src="' . ( $this->cfg['paths']['freamwork_dir_url'] . 'images/checkout_loading.gif'  ) . '" style="margin: 10px auto;">';
						echo "<h3>" . ( str_replace( '{amazon_website}', 'www.amazon' . $user_country['website'], $config["redirect_checkout_msg"]) ) . "</h3>";
					}
					
					$checkout_type =  isset($config['checkout_type']) && $config['checkout_type'] == '_blank' ? '_blank' : '_self';
					?>
						<form target="<?php echo $checkout_type;?>"  id="amzRedirect" method="POST" action="http://www.amazon<?php echo $user_country['website'];?>/gp/aws/cart/add.html">
							<input type="hidden" name="AssociateTag" value="<?php echo $user_country['affID'];?>"/> 
							<input type="hidden" name="SubscriptionId" value="<?php echo $config['AccessKeyID'];?>"/> 
					<?php 
					$cc = 1; 
					foreach ($amz_products as $key => $value){
					?>		
							<input type="hidden" name="ASIN.<?php echo $cc;?>" value="<?php echo $value['asin'];?>"/>
							<input type="hidden" name="Quantity.<?php echo $cc;?>" value="<?php echo $value['quantity'];?>"/>
					<?php
						$cc++;
					}   
					
					$redirect_in = isset($config['redirect_time']) && (int)$config['redirect_time'] > 0 ? ((int)$config['redirect_time'] * 1000) : 1;
					?>		 
						</form> 
						<script type="text/javascript">
						setTimeout(function() {
							document.getElementById("amzRedirect").submit();
						  	<?php 
						  	if( (int)$woocommerce->cart->cart_contents_count > 0 && $checkout_type == '_blank' ){
						  	?>
						  		setTimeout(function(){
						  			window.location.reload(true);
						  		}, 1);
						  	<?php	
						  	}
						  	?>
						}, <?php echo $redirect_in;?>);
						</script>
					<?php 
					// remove amazon products from client cart
					foreach ($amz_products as $key => $value) {
						
						if( isset($value['asin']) && trim($value['asin']) != "" ){
							$post_id = $this->get_post_id_by_meta_key_and_value('_amzASIN', $value['asin']);
							$redirect_to_amz = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon', true);
							update_post_meta($post_id, '_amzaff_redirect_to_amazon', (int)($redirect_to_amz+1));

							$woocommerce->cart->set_quantity( $value['key'], 0 );
						}
					}
					
					exit();
				}
			} 
		}
		
		private function redirect_amazon( $redirect_asin='' )
		{
			/*$get_user_location = wp_remote_get( 'http://api.hostip.info/country.php?ip=' . $_SERVER["REMOTE_ADDR"] );
			if( isset($get_user_location->errors) ) {
				$main_aff_site = $this->main_aff_site();
				$user_country = $this->amzForUser( strtoupper(str_replace(".", '', $main_aff_site)) );
			}else{
				$user_country = $this->amzForUser($get_user_location['body']);
			}*/
			$user_country = $this->get_country_perip_external();
			
			$config = @unserialize( get_option( $this->alias . '_amazon' ) );
			 
			if( isset($config["90day_cookie"]) && $config["90day_cookie"] == 'yes' ){
		?>
			<form id="amzRedirect" method="GET" action="http://www.amazon<?php echo $user_country['website'];?>/gp/aws/cart/add.html">
				<input type="hidden" name="AssociateTag" value="<?php echo $user_country['affID'];?>"/> 
				<input type="hidden" name="SubscriptionId" value="<?php echo $config['AccessKeyID'];?>"/> 
				<input type="hidden" name="ASIN.1" value="<?php echo $redirect_asin;?>"/>
				<input type="hidden" name="Quantity.1" value="1"/> 
			</form> 
		<?php 
			die('
				<script>
				setTimeout(function() {
				  	document.getElementById("amzRedirect").submit();
				}, 1);
				</script>
			');
			}else{
				if( isset($redirect_asin) && trim($redirect_asin) != "" ){
					$post_id = $this->get_post_id_by_meta_key_and_value('_amzASIN', $redirect_asin);
					$redirect_to_amz = (int) get_post_meta($post_id, '_amzaff_redirect_to_amazon', true);
					update_post_meta($post_id, '_amzaff_redirect_to_amazon', (int)($redirect_to_amz+1));
				}
				   
				$link = 'http://www.amazon' . ( $user_country['website'] ) . '/gp/product/' . ( $redirect_asin ) . '/?tag=' . ( $user_country['affID'] ) . '';
				
				die('<meta http-equiv="refresh" content="0; url=' . ( $link ) . '">');
			/* 
			<!--form id="amzRedirect" method="GET" action="<?php echo $link;?>">
			</form--> 
		    */
			}
			
		}

	function get_post_id_by_meta_key_and_value($key, $value) 
	{
		global $wpdb;
		$meta = $wpdb->get_results($wpdb->prepare("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key=%s AND meta_value=%s", $key, $value));
		
		if (is_array($meta) && !empty($meta) && isset($meta[0])) {
			$meta = $meta[0];
		}	
		if (is_object($meta)) {
			return $meta->post_id;
		}
		else {
			return false;
		}
	}

		public function plugin_redirect() {
			if (get_option('WooZoneLight_do_activation_redirect', false)) {
				
				$pullOutArray = @json_decode( file_get_contents( $this->cfg['paths']['plugin_dir_path'] . 'modules/setup_backup/default-setup.json' ), true );
				foreach ($pullOutArray as $key => $value){

					// prepare the data for DB update
					$saveIntoDb = $value != "true" ? serialize( $value ) : "true";
					// Use the function update_option() to update a named option/value pair to the options database table. The option_name value is escaped with $wpdb->escape before the INSERT statement.
					update_option( $key, $saveIntoDb );
				}

				$cross_sell_table_name = $this->db->prefix . "amz_cross_sell";
		        if ($this->db->get_var("show tables like '$cross_sell_table_name'") != $cross_sell_table_name) {

		            $sql = "CREATE TABLE " . $cross_sell_table_name . " (
						`ASIN` VARCHAR(10) NOT NULL,
						`products` TEXT NULL,
						`nr_products` INT(11) NULL DEFAULT NULL,
						`add_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						PRIMARY KEY (`ASIN`),
						UNIQUE INDEX `ASIN` (`ASIN`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

		            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		            dbDelta($sql);
		        }
				
				delete_option('WooZoneLight_do_activation_redirect');
				wp_redirect( get_admin_url() . 'admin.php?page=WooZoneLight' );
			}
		}

		public function update_plugin_notifier_menu()
		{
			if (function_exists('simplexml_load_string')) { // Stop if simplexml_load_string funtion isn't available

				// Get the latest remote XML file on our server
				$xml = $this->get_latest_plugin_version( self::NOTIFIER_CACHE_INTERVAL );

				$plugin_data = get_plugin_data( $this->cfg['paths']['plugin_dir_path'] . 'plugin.php' ); // Read plugin current version from the main plugin file

				if( isset($plugin_data) && count($plugin_data) > 0 ){
					if( (string)$xml->latest > (string)$plugin_data['Version']) { // Compare current plugin version with the remote XML version
						add_dashboard_page(
							$plugin_data['Name'] . ' Plugin Updates',
							'Amazon <span class="update-plugins count-1"><span class="update-count">New Updates</span></span>',
							'administrator',
							$this->alias . '-plugin-update-notifier',
							array( $this, 'update_notifier' )
						);
					}
				}
			}
		}

		public function update_notifier()
		{
			$xml = $this->get_latest_plugin_version( self::NOTIFIER_CACHE_INTERVAL );
			$plugin_data = get_plugin_data( $this->cfg['paths']['plugin_dir_path'] . 'plugin.php' ); // Read plugin current version from the main plugin file
		?>

			<style>
			.update-nag { display: none; }
			#instructions {max-width: 670px;}
			h3.title {margin: 30px 0 0 0; padding: 30px 0 0 0; border-top: 1px solid #ddd;}
			</style>

			<div class="wrap">

			<div id="icon-tools" class="icon32"></div>
			<h2><?php echo $plugin_data['Name'] ?> Plugin Updates</h2>
			<div id="message" class="updated below-h2"><p><strong>There is a new version of the <?php echo $plugin_data['Name'] ?> plugin available.</strong> You have version <?php echo $plugin_data['Version']; ?> installed. Update to version <?php echo $xml->latest; ?>.</p></div>
			<div id="instructions">
			<h3>Update Download and Instructions</h3>
			<p><strong>Please note:</strong> make a <strong>backup</strong> of the Plugin inside your WordPress installation folder <strong>/wp-content/plugins/<?php echo end(explode('wp-content/plugins/', $this->cfg['paths']['plugin_dir_path'])); ?></strong></p>
			<p>To update the Plugin, login to <a href="http://www.codecanyon.net/?ref=AA-Team">CodeCanyon</a>, head over to your <strong>downloads</strong> section and re-download the plugin like you did when you bought it.</p>
			<p>Extract the zip's contents, look for the extracted plugin folder, and after you have all the new files upload them using FTP to the <strong>/wp-content/plugins/<?php echo end(explode('wp-content/plugins/', $this->cfg['paths']['plugin_dir_path'])); ?></strong> folder overwriting the old ones (this is why it's important to backup any changes you've made to the plugin files).</p>
			<p>If you didn't make any changes to the plugin files, you are free to overwrite them with the new ones without the risk of losing any plugins settings, and backwards compatibility is guaranteed.</p>
			</div>
			<h3 class="title">Changelog</h3>
			<?php echo $xml->changelog; ?>

			</div>
		<?php
		}

		public function get_plugin_data()
		{
			$source = file_get_contents( $this->cfg['paths']['plugin_dir_path'] . "/plugin.php" );
			$tokens = token_get_all( $source );
		    $data = array();
			if( trim($tokens[1][1]) != "" ){
				$__ = explode("\n", $tokens[1][1]);
				foreach ($__ as $key => $value) {
					$___ = explode(": ", $value);
					if( count($___) == 2 ){
						$data[trim(strtolower(str_replace(" ", '_', $___[0])))] = trim($___[1]);
					}
				}				
			}
			
			$this->details = $data;
			return $data;  
		}

		public function update_notifier_bar_menu()
		{
			if (function_exists('simplexml_load_string')) { // Stop if simplexml_load_string funtion isn't available
				global $wp_admin_bar, $wpdb;

				// Don't display notification in admin bar if it's disabled or the current user isn't an administrator
				if ( !is_super_admin() || !is_admin_bar_showing() )
				return;

				// Get the latest remote XML file on our server
				// The time interval for the remote XML cache in the database (21600 seconds = 6 hours)
				$xml = $this->get_latest_plugin_version( self::NOTIFIER_CACHE_INTERVAL );

				if ( is_admin() )
					$plugin_data = get_plugin_data( $this->cfg['paths']['plugin_dir_path'] . 'plugin.php' ); // Read plugin current version from the main plugin file

					if( isset($plugin_data) && count($plugin_data) > 0 ){

						if( (string)$xml->latest > (string)$plugin_data['Version']) { // Compare current plugin version with the remote XML version

						$wp_admin_bar->add_menu(
							array(
								'id' => 'plugin_update_notifier',
								'title' => '<span>' . ( $plugin_data['Name'] ) . ' <span id="ab-updates">New Updates</span></span>',
								'href' => get_admin_url() . 'index.php?page=' . ( $this->alias ) . '-plugin-update-notifier'
							)
						);
					}
				}
			}
		}

		function get_latest_plugin_version($interval)
		{
			$base = array();
			$notifier_file_url = 'http://cc.aa-team.com/apps-versions/index.php?app=' . $this->alias;
			$db_cache_field = $this->alias . '_notifier-cache';
			$db_cache_field_last_updated = $this->alias . '_notifier-cache-last-updated';
			$last = get_option( $db_cache_field_last_updated );
			$now = time();

			// check the cache
			if ( !$last || (( $now - $last ) > $interval) ) {
				// cache doesn't exist, or is old, so refresh it
				if( function_exists('curl_init') ) { // if cURL is available, use it...
					$ch = curl_init($notifier_file_url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_setopt($ch, CURLOPT_TIMEOUT, 10);
					$cache = curl_exec($ch);
					curl_close($ch);
				} else {
					// ...if not, use the common file_get_contents()
					$cache = file_get_contents($notifier_file_url);
				}

				if ($cache) {
					// we got good results
					update_option( $db_cache_field, $cache );
					update_option( $db_cache_field_last_updated, time() );
				}

				// read from the cache file
				$notifier_data = get_option( $db_cache_field );
			}
			else {
				// cache file is fresh enough, so read from it
				$notifier_data = get_option( $db_cache_field );
			}

			// Let's see if the $xml data was returned as we expected it to.
			// If it didn't, use the default 1.0 as the latest version so that we don't have problems when the remote server hosting the XML file is down
			if( strpos((string)$notifier_data, '<notifier>') === false ) {
				$notifier_data = '<?xml version="1.0" encoding="UTF-8"?><notifier><latest>1.0</latest><changelog></changelog></notifier>';
			}

			// Load the remote XML data into a variable and return it
			$xml = simplexml_load_string($notifier_data);

			return $xml;
		}
		public function is_woocommerce_installed()
		{
			if ( in_array( 'envato-wordpress-toolkit/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) || is_multisite() )
			{
				return true;
			} else {
				return false;
			}
		}
		public function activate()
		{
			add_option('WooZoneLight_do_activation_redirect', true);
			add_option('WooZoneLight_depedencies_is_valid', true);
			add_option('WooZoneLight_depedencies_do_activation_redirect', true);
		}

		public function get_plugin_status ()
		{
			return 'valid_hash';
		}

		// add admin js init
		public function createInstanceFreamwork ()
		{
			echo "<script type='text/javascript'>jQuery(document).ready(function ($) {
					/*var WooZoneLight = new WooZoneLight;
					WooZoneLight.init();*/
				});</script>";
		}

		/**
		 * Create plugin init
		 *
		 *
		 * @no-return
		 */
		public function initThePlugin()
		{
			// If the user can manage options, let the fun begin!
			if(is_admin() && current_user_can( 'manage_options' ) ){
				if(is_admin() && (!isset($_REQUEST['page']) || strpos($_REQUEST['page'],'codestyling') === false)){
					// Adds actions to hook in the required css and javascript
					add_action( "admin_print_styles", array( &$this, 'admin_load_styles') );
					add_action( "admin_print_scripts", array( &$this, 'admin_load_scripts') );
				}

				// create dashboard page
				add_action( 'admin_menu', array( &$this, 'createDashboardPage' ) );

				// get fatal errors
				add_action ( 'admin_notices', array( &$this, 'fatal_errors'), 10 );

				// get fatal errors
				add_action ( 'admin_notices', array( &$this, 'admin_warnings'), 10 );
				
				$section = isset( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
				$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
				if($page == $this->alias || strpos($page, $this->alias) == true && trim($section) != "" ) {
					add_action('init', array( &$this, 'go_to_section' ));
				}
			}
			
			// keep the plugin modules into storage
			if(!isset($_REQUEST['page']) || strpos($_REQUEST['page'],'codestyling') === false){
				$this->load_modules();
			}
		}

		public function go_to_section()
		{
			$section = isset( $_REQUEST['section'] ) ? $_REQUEST['section'] : '';
			if( trim($section) != "" ) {	
				header('Location: ' . sprintf(admin_url('admin.php?page=%s#!/%s'), $this->alias, $section) );
				exit();
			}
		}
		
		private function update_products_type ( $what='all' )
		{
			
			$products = array();
			
			// update all products 
			if( $what == 'all' ){
				$args = array(
					'post_type' => 'product',
					'fields' => 'ids',
					'meta_key' => '_amzASIN',
					'posts_per_page' => '-1',
					'meta_query' => array(
				       array(
				           'key' => '_amzASIN',
				           'value'   => array(''),
        					'compare' => 'NOT IN'
				       )
				   )
				);
				$query = new WP_Query($args);
				//var_dump('<pre>',$query,'</pre>'); die; 
				if( count($query->posts) > 0 ){
					foreach ($query->posts as $key => $value) {
						$products[] = $value;
					}
				} 				
			}
			
			// custom product
			else{
				$products[] = $what;
			}
			
			
			$config = @unserialize( get_option( $this->alias . '_amazon' ) ); 
			if( count($products) > 0 ){
				foreach ($products as $key => $value) {
					$p_type = ((isset($config['onsite_cart']) && $config['onsite_cart'] == "no") ? 'external' : 'external');
					
					if( $p_type == 'simple' ){
						$args = array(
							'post_type' => 'product_variation',
							'posts_per_page' => '5',
							'post_parent' => $value
						);
						
						$query_variations = new WP_Query($args);
						
						if( $query_variations->post_count > 0 ){
							$p_type = 'variable';
						}
					}
					wp_set_object_terms( $value, $p_type, 'product_type');	
				}
			}
		}

		public function fixPlusParseStr ( $input=array(), $type='string' )
		{
			if($type == 'array'){
				if(count($input) > 0){
					$ret_arr = array();
					foreach ($input as $key => $value){
						$ret_arr[$key] = str_replace("###", '+', $value);
					}

					return $ret_arr;
				}

				return $input;
			}else{
				return str_replace('+', '###', $input);
			}
		}

		// saving the options
		public function save_options ()
		{
			// remove action from request
			unset($_REQUEST['action']);

			// unserialize the request options
			$serializedData = $this->fixPlusParseStr(urldecode($_REQUEST['options']));

			$savingOptionsArr = array();

			parse_str($serializedData, $savingOptionsArr);

			$savingOptionsArr = $this->fixPlusParseStr( $savingOptionsArr, 'array');

			// create save_id and remote the box_id from array
			$save_id = $savingOptionsArr['box_id'];
			unset($savingOptionsArr['box_id']); 

			// Verify that correct nonce was used with time limit.
			if( ! wp_verify_nonce( $savingOptionsArr['box_nonce'], $save_id . '-nonce')) die ('Busted!');
			unset($savingOptionsArr['box_nonce']);
			
			// remove the white space before asin
			if( $save_id == 'WooZoneLight_amazon' ){
				$_savingOptionsArr = $savingOptionsArr;
				$savingOptionsArr = array();
				foreach ($_savingOptionsArr as $key => $value) {
					if( !is_array($value) ){
						$savingOptionsArr[$key] = trim($value);
					}else{
						$savingOptionsArr[$key] = $value;
					}
				}
			}
			
			// prepare the data for DB update
			$saveIntoDb = serialize( $savingOptionsArr );
			
			// Use the function update_option() to update a named option/value pair to the options database table. The option_name value is escaped with $wpdb->escape before the INSERT statement.
			update_option( $save_id, $saveIntoDb ); 
			
			// check for onsite cart option 
			if( $save_id == $this->alias . '_amazon' ){
				$this->update_products_type( 'all' );
			}
			
			die(json_encode( array(
				'status' => 'ok',
				'html' 	 => 'Options updated successfully'
			)));
		}

		// saving the options
		public function install_default_options ()
		{
			// remove action from request
			unset($_REQUEST['action']);

			// unserialize the request options
			$serializedData = urldecode($_REQUEST['options']);


			$savingOptionsArr = array();
			parse_str($serializedData, $savingOptionsArr);

			// create save_id and remote the box_id from array
			$save_id = $savingOptionsArr['box_id'];
			unset($savingOptionsArr['box_id']);

			// Verify that correct nonce was used with time limit.
			if( ! wp_verify_nonce( $savingOptionsArr['box_nonce'], $save_id . '-nonce')) die ('Busted!');
			unset($savingOptionsArr['box_nonce']);

			// convert to array
			// convert to array
			$savingOptionsArr['install_box'] = str_replace('#!#', '&', $savingOptionsArr['install_box']);
			$savingOptionsArr['install_box'] = str_replace("'", "\'", $savingOptionsArr['install_box']); 
			//$savingOptionsArr['install_box'] = str_replace('\"', '"', $savingOptionsArr['install_box']);
			$pullOutArray = json_decode( $savingOptionsArr['install_box'], true );
			
			if(count($pullOutArray) == 0){
				die(json_encode( array(
					'status' => 'error',
					'html' 	 => "Invalid install default json string, can't parse it!"
				)));
			}else{

				foreach ($pullOutArray as $key => $value){

					// prepare the data for DB update
					$saveIntoDb = $value != "true" ? serialize( $value ) : $value;

					// Use the function update_option() to update a named option/value pair to the options database table. The option_name value is escaped with $wpdb->escape before the INSERT statement.
					update_option( $key, $saveIntoDb );
				}

				$cross_sell_table_name = $this->db->prefix . "amz_cross_sell";
		        if ($this->db->get_var("show tables like '$cross_sell_table_name'") != $cross_sell_table_name) {

		            $sql = "CREATE TABLE " . $cross_sell_table_name . " (
						`ASIN` VARCHAR(10) NOT NULL,
						`products` TEXT NULL,
						`nr_products` INT(11) NULL DEFAULT NULL,
						`add_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
						PRIMARY KEY (`ASIN`),
						UNIQUE INDEX `ASIN` (`ASIN`)
					) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

		            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		            dbDelta($sql);
		        }

				die(json_encode( array(
					'status' => 'ok',
					'html' 	 => 'Install default successful'
				)));
			}
		}

		public function options_validate ( $input )
		{
			//var_dump('<pre>', $input  , '</pre>'); echo __FILE__ . ":" . __LINE__;die . PHP_EOL;
		}

		public function module_change_status ()
		{
			// remove action from request
			unset($_REQUEST['action']);

			// update into DB the new status
			$db_alias = $this->alias . '_module_' . $_REQUEST['module'];
			update_option( $db_alias, $_REQUEST['the_status'] );

			die(json_encode(array(
				'status' => 'ok'
			)));
		}

		// loading the requested section
		public function load_section ()
		{
			$request = array(
				'section' => isset($_REQUEST['section']) ? strip_tags($_REQUEST['section']) : false
			);
     
			// get module if isset
			if(!in_array( $request['section'], $this->cfg['activate_modules'])) die(json_encode(array('status' => 'err', 'msg' => 'invalid section want to load!')));

			$tryed_module = $this->cfg['modules'][$request['section']];
			if( isset($tryed_module) && count($tryed_module) > 0 ){
				// Turn on output buffering
				ob_start();

				$opt_file_path = $tryed_module['folder_path'] . 'options.php';
				if( is_file($opt_file_path) ) {
					require_once( $opt_file_path  );
				}
				$options = ob_get_clean(); //copy current buffer contents into $message variable and delete current output buffer

				if(trim($options) != "") {
					$options = json_decode($options, true);

					// Derive the current path and load up aaInterfaceTemplates
					$plugin_path = dirname(__FILE__) . '/';
					if(class_exists('aaInterfaceTemplates') != true) {
						require_once($plugin_path . 'settings-template.class.php');

						// Initalize the your aaInterfaceTemplates
						$aaInterfaceTemplates = new aaInterfaceTemplates($this->cfg);

						// then build the html, and return it as string
						$html = $aaInterfaceTemplates->bildThePage($options, $this->alias, $tryed_module);

						// fix some URI
						$html = str_replace('{plugin_folder_uri}', $tryed_module['folder_uri'], $html);
						
						if(trim($html) != "") {
							$headline = '';
							if( isset($tryed_module[$request['section']]['in_dashboard']['icon']) ){
								$headline .= '<img src="' . ($tryed_module['folder_uri'] . $tryed_module[$request['section']]['in_dashboard']['icon'] ) . '" class="WooZoneLight-headline-icon">';
							}
							$headline .= $tryed_module[$request['section']]['menu']['title'] . "<span class='WooZoneLight-section-info'>" . ( $tryed_module[$request['section']]['description'] ) . "</span>";
							
							$has_help = isset($tryed_module[$request['section']]['help']) ? true : false;
							if( $has_help === true ){
								
								$help_type = isset($tryed_module[$request['section']]['help']['type']) && $tryed_module[$request['section']]['help']['type'] ? 'remote' : 'local';
								if( $help_type == 'remote' ){
									$headline .= '<a href="#load_docs" class="WooZoneLight-show-docs" data-helptype="' . ( $help_type ) . '" data-url="' . ( $tryed_module[$request['section']]['help']['url'] ) . '" data-operation="help">HELP</a>';
								} 
							}
							
							$headline .= '<a href="#load_docs" class="WooZoneLight-show-feedback" data-helptype="' . ( 'remote' ) . '" data-url="' . ( $this->feedback_url ) . '" data-operation="feedback">Feedback</a>';
 
							die( json_encode(array(
								'status' 	=> 'ok',
								'headline'	=> $headline,
								'html'		=> 	$html
							)) );
						}

						die(json_encode(array('status' => 'err', 'msg' => 'invalid html formatter!')));
					}
				}
			}
		}

		public function fatal_errors()
		{
			// print errors
			if(is_wp_error( $this->errors )) {
				$_errors = $this->errors->get_error_messages('fatal');

				if(count($_errors) > 0){
					foreach ($_errors as $key => $value){
						echo '<div class="error"> <p>' . ( $value ) . '</p> </div>';
					}
				}
			}
		}

		public function admin_warnings()
		{
			// print errors
			if(is_wp_error( $this->errors )) {
				$_errors = $this->errors->get_error_messages('warning');
				
				$theme_name = get_current_theme();
				$is_dissmised = get_option( $this->alias . "_dismiss_notice" );
				if( $theme_name != "Kingdom - Woocommerce Amazon Affiliates Theme" ){
					
					if( !isset($is_dissmised) || $is_dissmised == false ){
					
						$_errors = array('
							<p>
								<strong>
								For maximum usability and best experience with our Woocommerce Amazon Affiliates plugin we recommend using the custom made theme - <a href="http://codecanyon.net/item/kingdom-woocommerce-amazon-affiliates-theme/7919308?ref=AA-Team" target="_blank">Kingdom - Woocommerce Amazon Affiliates Theme</a> available on Codecanyon.
								</strong>
							</p>
							<p>
								<strong>
									<a href="http://codecanyon.net/item/kingdom-woocommerce-amazon-affiliates-theme/7919308?ref=AA-Team" target="_blank">Grab this theme</a> | <a class="dismiss-notice" href="' . ( admin_url( 'admin-ajax.php?action=WooZoneLightDismissNotice' ) ) . '" target="_parent">Dismiss this notice</a>
								</strong>
							</p>
						') ;
					}
				}
				if(count($_errors) > 0){
					foreach ($_errors as $key => $value){
						echo '<div class="updated"> <p>' . ( $value ) . '</p> </div>';
					}
				}
			}
		}

		/**
		 * Builds the config parameters
		 *
		 * @param string $function
		 * @param array	$params
		 *
		 * @return array
		 */
		protected function buildConfigParams($type, array $params)
		{
			// check if array exist
			if(isset($this->cfg[$type])){
				$params = array_merge( $this->cfg[$type], $params );
			}

			// now merge the arrays
			$this->cfg = array_merge(
				$this->cfg,
				array(	$type => array_merge( $params ) )
			);
		}

		/*
		* admin_load_styles()
		*
		* Loads admin-facing CSS
		*/
		public function admin_get_frm_style() {
			$css = array();

			if( isset($this->cfg['freamwork-css-files'])
				&& is_array($this->cfg['freamwork-css-files'])
				&& !empty($this->cfg['freamwork-css-files'])
			) {

				foreach ($this->cfg['freamwork-css-files'] as $key => $value){
					if( is_file($this->cfg['paths']['freamwork_dir_path'] . $value) ) {
						
						$cssId = $this->alias . '-' . $key;
						$css["$cssId"] = $this->cfg['paths']['freamwork_dir_path'] . $value;
						// wp_enqueue_style( $this->alias . '-' . $key, $this->cfg['paths']['freamwork_dir_url'] . $value );
					} else {
						$this->errors->add( 'warning', __('Invalid CSS path to file: <strong>' . $this->cfg['paths']['freamwork_dir_path'] . $value . '</strong>. Call in:' . __FILE__ . ":" . __LINE__ , $this->localizationName) );
					}
				}
			}
			return $css;
		}
		public function admin_load_styles()
		{
			global $wp_scripts;
			
			$javascript = $this->admin_get_scripts();
			
			wp_enqueue_style( 'WooZoneLight-aa-framework-styles', admin_url('admin-ajax.php?action=WooZoneAdminStyles') );
			
			if( in_array( 'thickbox', $javascript ) ) wp_enqueue_style('thickbox');
		}

		/*
		* admin_load_scripts()
		*
		* Loads admin-facing CSS
		*/
		public function admin_get_scripts() {
			$javascript = array();
			
			$current_url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
			$current_url = explode("wp-admin/", $current_url);
			if( count($current_url) > 1 ){ 
				$current_url = "/wp-admin/" . $current_url[1];
			}else{
				$current_url = "/wp-admin/" . $current_url[0];
			}
  
			if ( isset($this->cfg['modules'])
				&& is_array($this->cfg['modules']) && !empty($this->cfg['modules'])
			) {
			foreach( $this->cfg['modules'] as $alias => $module ){

				if( isset($module[$alias]["load_in"]['backend']) && is_array($module[$alias]["load_in"]['backend']) && count($module[$alias]["load_in"]['backend']) > 0 ){
					// search into module for current module base on request uri
					foreach ( $module[$alias]["load_in"]['backend'] as $page ) {
  
						$expPregQuote = ( is_array($page) ? false : true );
  						if ( is_array($page) ) $page = $page[0];

						$delimiterFound = strpos($page, '#');
						$page = substr($page, 0, ($delimiterFound!==false && $delimiterFound > 0 ? $delimiterFound : strlen($page)) );
						$urlfound = preg_match("%^/wp-admin/".($expPregQuote ? preg_quote($page) : $page)."%", $current_url);
						  
						if(
							// $current_url == '/wp-admin/' . $page
							( ( $page == '@all' ) || ( $current_url == '/wp-admin/admin.php?page=WooZoneLight' ) || ( !empty($page) && $urlfound > 0 ) )
							&& isset($module[$alias]['javascript']) ) {
  
							$javascript = array_merge($javascript, $module[$alias]['javascript']);
						}
					}
				}
			}
			} // end if
  
			$this->jsFiles = $javascript;
			return $javascript;
		}
		public function admin_load_scripts()
		{
			// very defaults scripts (in wordpress defaults)
			wp_enqueue_script( 'jquery' );
			
			$javascript = $this->admin_get_scripts();
			
			if( count($javascript) > 0 ){
				$javascript = @array_unique( $javascript );
  
				if( in_array( 'jquery-ui-core', $javascript ) ) wp_enqueue_script( 'jquery-ui-core' );
				if( in_array( 'jquery-ui-widget', $javascript ) ) wp_enqueue_script( 'jquery-ui-widget' );
				if( in_array( 'jquery-ui-mouse', $javascript ) ) wp_enqueue_script( 'jquery-ui-mouse' );
				if( in_array( 'jquery-ui-accordion', $javascript ) ) wp_enqueue_script( 'jquery-ui-accordion' );
				if( in_array( 'jquery-ui-autocomplete', $javascript ) ) wp_enqueue_script( 'jquery-ui-autocomplete' );
				if( in_array( 'jquery-ui-slider', $javascript ) ) wp_enqueue_script( 'jquery-ui-slider' );
				if( in_array( 'jquery-ui-tabs', $javascript ) ) wp_enqueue_script( 'jquery-ui-tabs' );
				if( in_array( 'jquery-ui-sortable', $javascript ) ) wp_enqueue_script( 'jquery-ui-sortable' );
				if( in_array( 'jquery-ui-draggable', $javascript ) ) wp_enqueue_script( 'jquery-ui-draggable' );
				if( in_array( 'jquery-ui-droppable', $javascript ) ) wp_enqueue_script( 'jquery-ui-droppable' );
				if( in_array( 'jquery-ui-datepicker', $javascript ) ) wp_enqueue_script( 'jquery-ui-datepicker' );
				if( in_array( 'jquery-ui-resize', $javascript ) ) wp_enqueue_script( 'jquery-ui-resize' );
				if( in_array( 'jquery-ui-dialog', $javascript ) ) wp_enqueue_script( 'jquery-ui-dialog' );
				if( in_array( 'jquery-ui-button', $javascript ) ) wp_enqueue_script( 'jquery-ui-button' );
				
				if( in_array( 'thickbox', $javascript ) ) wp_enqueue_script( 'thickbox' );
	
				// date & time picker
				if( !wp_script_is('jquery-timepicker') ) {
					if( in_array( 'jquery-timepicker', $javascript ) ) wp_enqueue_script( 'jquery-timepicker' , $this->cfg['paths']['freamwork_dir_url'] . 'js/jquery.timepicker.v1.1.1.min.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-slider' ) );
				}
			}
  
			if( count($this->cfg['freamwork-js-files']) > 0 ){
				foreach ($this->cfg['freamwork-js-files'] as $key => $value){

					if( is_file($this->cfg['paths']['freamwork_dir_path'] . $value) ){
						if( in_array( $key, $javascript ) ) wp_enqueue_script( $this->alias . '-' . $key, $this->cfg['paths']['freamwork_dir_url'] . $value );
					} else {
						$this->errors->add( 'warning', __('Invalid JS path to file: <strong>' . $this->cfg['paths']['freamwork_dir_path'] . $value . '</strong> . Call in:' . __FILE__ . ":" . __LINE__ , $this->localizationName) );
					}
				}
			}
		}

		/*
		 * Builds out the options panel.
		 *
		 * If we were using the Settings API as it was likely intended we would use
		 * do_settings_sections here. But as we don't want the settings wrapped in a table,
		 * we'll call our own custom wplanner_fields. See options-interface.php
		 * for specifics on how each individual field is generated.
		 *
		 * Nonces are provided using the settings_fields()
		 *
		 * @param array $params
		 * @param array $options (fields)
		 *
		 */
		public function createDashboardPage ()
		{
			add_menu_page(
				__( 'WooZone - Amazon Affiliates', $this->localizationName ),
				__( 'WooZone', $this->localizationName ),
				'manage_options',
				$this->alias,
				array( &$this, 'manage_options_template' ),
				$this->cfg['paths']['plugin_dir_url'] . 'icon_16.png'
			);
			
			add_submenu_page(
    			$this->alias,
    			$this->alias . " " . __('Amazon plugin configuration', $this->localizationName),
	            __('Amazon config', $this->localizationName),
	            'manage_options',
	            $this->alias . "&section=amazon",
	            array( $this, 'manage_options_template')
	        );
			
			add_submenu_page(
    			$this->alias,
    			$this->alias . " " . __('Amazon Advanced Search', $this->localizationName),
	            __('Amazon Search', $this->localizationName),
	            'manage_options',
	            $this->alias . "&section=advanced_search",
	            array( $this, 'manage_options_template')
	        );
		}

		public function manage_options_template()
		{
			// Derive the current path and load up aaInterfaceTemplates
			$plugin_path = dirname(__FILE__) . '/';
			if(class_exists('aaInterfaceTemplates') != true) {
				require_once($plugin_path . 'settings-template.class.php');

				// Initalize the your aaInterfaceTemplates
				$aaInterfaceTemplates = new aaInterfaceTemplates($this->cfg);

				// try to init the interface
				$aaInterfaceTemplates->printBaseInterface();
			}
		}

		/**
		 * Getter function, plugin config
		 *
		 * @return array
		 */
		public function getCfg()
		{
			return $this->cfg;
		}

		/**
		 * Getter function, plugin all settings
		 *
		 * @params $returnType
		 * @return array
		 */
		public function getAllSettings( $returnType='array', $only_box='', $this_call=false )
		{
			if( $this_call == true ){
				//var_dump('<pre>',$returnType, $only_box,'</pre>');  
			}
			$allSettingsQuery = "SELECT * FROM " . $this->db->prefix . "options where 1=1 and option_name REGEXP '" . ( $this->alias) . "_([a-z])'";
			if (trim($only_box) != "") {
				$allSettingsQuery = "SELECT * FROM " . $this->db->prefix . "options where 1=1 and option_name = '" . ( $this->alias . '_' . $only_box) . "' LIMIT 1;";
			}
			$results = $this->db->get_results( $allSettingsQuery, ARRAY_A);
			
			// prepare the return
			$return = array();
			if( count($results) > 0 ){
				foreach ($results as $key => $value){
					if($value['option_value'] == 'true'){
						$return[$value['option_name']] = true;
					}else{
						$return[$value['option_name']] = @unserialize(@unserialize($value['option_value']));
					}
				}
			}

			if(trim($only_box) != "" && isset($return[$this->alias . '_' . $only_box])){
				$return = $return[$this->alias . '_' . $only_box];
			}
 
			if($returnType == 'serialize'){
				return serialize($return);
			}else if( $returnType == 'array' ){
				return $return;
			}else if( $returnType == 'json' ){
				return json_encode($return);
			}

			return false;
		}

		/**
		 * Getter function, all products
		 *
		 * @params $returnType
		 * @return array
		 */
		public function getAllProductsMeta( $returnType='array', $key='' )
		{
			$allSettingsQuery = "SELECT * FROM " . $this->db->prefix . "postmeta where 1=1 and meta_key='" . ( $key ) . "'";
			$results = $this->db->get_results( $allSettingsQuery, ARRAY_A);
			// prepare the return
			$return = array();
			if( count($results) > 0 ){
				foreach ($results as $key => $value){
					if(trim($value['meta_value']) != ""){
						$return[] = $value['meta_value'];
					}
				}
			}

			if($returnType == 'serialize'){
				return serialize($return);
			}
			else if( $returnType == 'text' ){
				return implode("\n", $return);
			}
			else if( $returnType == 'array' ){
				return $return;
			}
			else if( $returnType == 'json' ){
				return json_encode($return);
			}

			return false;
		}

		/*
		* GET modules lists
		*/
		public function load_modules( $pluginPage='' )
		{
			$folder_path = $this->cfg['paths']['plugin_dir_path'] . 'modules/';
			$cfgFileName = 'config.php';

			// static usage, modules menu order
			$menu_order = array();

			$modules_list = glob($folder_path . '*/' . $cfgFileName);
			
			$nb_modules = count($modules_list);
			if ( $nb_modules > 0 ) {
				foreach ($modules_list as $key => $mod_path ) {

					$dashboard_isfound = preg_match("/modules\/dashboard\/config\.php$/", $mod_path);
					$depedencies_isfound = preg_match("/modules\/depedencies\/config\.php$/", $mod_path);
					
					if ( $pluginPage == 'depedencies' ) {
						if ( $depedencies_isfound!==false && $depedencies_isfound>0 ) ;
						else continue 1;
					} else {
						if ( $dashboard_isfound!==false && $dashboard_isfound>0 ) {
							unset($modules_list[$key]);
							$modules_list[$nb_modules] = $mod_path;
						}
					}
				}
			}
  
			foreach ($modules_list as $module_config ) {
				$module_folder = str_replace($cfgFileName, '', $module_config);

				// Turn on output buffering
				ob_start();

				if( is_file( $module_config ) ) {
					require_once( $module_config  );
				}
				$settings = ob_get_clean(); //copy current buffer contents into $message variable and delete current output buffer

				if(trim($settings) != "") {
					$settings = json_decode($settings, true);
					$settings_keys = array_keys($settings);
					$alias = (string)end($settings_keys);

					// create the module folder URI
					// fix for windows server
					$module_folder = str_replace( DIRECTORY_SEPARATOR, '/',  $module_folder );

					$__tmpUrlSplit = explode("/", $module_folder);
					$__tmpUrl = '';
					$nrChunk = count($__tmpUrlSplit);
					if($nrChunk > 0) {
						foreach ($__tmpUrlSplit as $key => $value){
							if( $key > ( $nrChunk - 4) && trim($value) != ""){
								$__tmpUrl .= $value . "/";
							}
						}
					}

					// get the module status. Check if it's activate or not
					$status = false;

					// default activate all core modules
					if ( $pluginPage == 'depedencies' ) {
						if ( $alias != 'depedencies' ) continue 1;
						else $status = true;
					} else {
						if ( $alias == 'depedencies' ) continue 1;
						
						if(in_array( $alias, $this->cfg['core-modules'] )) {
							$status = true;
						}else{
							// activate the modules from DB status
							$db_alias = $this->alias . '_module_' . $alias;
	
							if(get_option($db_alias) == 'true'){
								$status = true;
							}
						}
					}
  
					// push to modules array
					$this->cfg['modules'][$alias] = array_merge(array(
						'folder_path' 	=> $module_folder,
						'folder_uri' 	=> $this->cfg['paths']['plugin_dir_url'] . $__tmpUrl,
						'db_alias'		=> $this->alias . '_' . $alias,
						'alias' 		=> $alias,
						'status'		=> $status
					), $settings );

					// add to menu order array
					if(!isset($this->cfg['menu_order'][(int)$settings[$alias]['menu']['order']])){
						$this->cfg['menu_order'][(int)$settings[$alias]['menu']['order']] = $alias;
					}else{
						// add the menu to next free key
						$this->cfg['menu_order'][] = $alias;
					}

					// add module to activate modules array
					if($status == true){
						$this->cfg['activate_modules'][$alias] = true;
					}

					// load the init of current loop module
					$time_start = microtime(true);
					$start_memory_usage = (memory_get_usage());
					
					// in backend
					if( $this->is_admin === true && isset($settings[$alias]["load_in"]['backend']) ){
						
						$need_to_load = false;
						if( is_array($settings[$alias]["load_in"]['backend']) && count($settings[$alias]["load_in"]['backend']) > 0 ){
						
							$current_url = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : '';
							$current_url = explode("wp-admin/", $current_url);
							if( count($current_url) > 1 ){ 
								$current_url = "/wp-admin/" . $current_url[1];
							}else{
								$current_url = "/wp-admin/" . $current_url[0];
							}
							
							foreach ( $settings[$alias]["load_in"]['backend'] as $page ) {

								$expPregQuote = ( is_array($page) ? false : true );
  								if ( is_array($page) ) $page = $page[0];

								$delimiterFound = strpos($page, '#');
								$page = substr($page, 0, ($delimiterFound!==false && $delimiterFound > 0 ? $delimiterFound : strlen($page)) );
								$urlfound = preg_match("%^/wp-admin/".($expPregQuote ? preg_quote($page) : $page)."%", $current_url);
								
								$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
								$section = isset($_REQUEST['section']) ? $_REQUEST['section'] : '';
								if(
									// $current_url == '/wp-admin/' . $page ||
									( ( $page == '@all' ) || ( $current_url == '/wp-admin/admin.php?page=WooZoneLight' ) || ( !empty($page) && $urlfound > 0 ) )
									|| ( $action == 'WooZoneLightLoadSection' && $section == $alias )
									|| substr($action, 0, 3) == 'WooZoneLight'
								){
									$need_to_load = true;  
								}
							}
						}
  
						if( $need_to_load == false ){
							continue;
						}  
					}
					
					if( $this->is_admin === false && isset($settings[$alias]["load_in"]['frontend']) ){
						
						$need_to_load = false;
						if( $settings[$alias]["load_in"]['frontend'] === true ){
							$need_to_load = true;
						}
						if( $need_to_load == false ){
							continue;
						}  
					}

					// load the init of current loop module
					if( $status == true && isset( $settings[$alias]['module_init'] ) ){
						if( is_file($module_folder . $settings[$alias]['module_init']) ){
							//if( is_admin() ) {
								$current_module = array($alias => $this->cfg['modules'][$alias]);
								$GLOBALS['WooZoneLight_current_module'] = $current_module;
								 
								require_once( $module_folder . $settings[$alias]['module_init'] );

								$time_end = microtime(true);
								$this->cfg['modules'][$alias]['loaded_in'] = $time_end - $time_start;
								
								$this->cfg['modules'][$alias]['memory_usage'] = (memory_get_usage() ) - $start_memory_usage;
								if( (float)$this->cfg['modules'][$alias]['memory_usage'] < 0 ){
									$this->cfg['modules'][$alias]['memory_usage'] = 0.0;
								}
							//}
						}
					}
				}
			}
  
			// order menu_order ascendent
			ksort($this->cfg['menu_order']);
		}

		public function print_plugin_usages()
		{
			$html = array();
			
			$html[] = '<style>
				.WooZoneLight-bench-log {
					border: 1px solid #ccc; 
					width: 450px; 
					position: absolute; 
					top: 92px; 
					right: 2%;
					background: #95a5a6;
					color: #fff;
					font-size: 12px;
					z-index: 99999;
					
				}
					.WooZoneLight-bench-log th {
						font-weight: bold;
						background: #34495e;
					}
					.WooZoneLight-bench-log th,
					.WooZoneLight-bench-log td {
						padding: 4px 12px;
					}
				.WooZoneLight-bench-title {
					position: absolute; 
					top: 55px; 
					right: 2%;
					width: 425px; 
					margin: 0px 0px 0px 0px;
					font-size: 20px;
					background: #ec5e00;
					color: #fff;
					display: block;
					padding: 7px 12px;
					line-height: 24px;
					z-index: 99999;
				}
			</style>';
			
			$html[] = '<h1 class="WooZoneLight-bench-title">WooZoneLight: Benchmark performance</h1>';
			$html[] = '<table class="WooZoneLight-bench-log">';
			$html[] = 	'<thead>';
			$html[] = 		'<tr>';
			$html[] = 			'<th>Module</th>';
			$html[] = 			'<th>Loading time</th>';
			$html[] = 			'<th>Memory usage</th>';
			$html[] = 		'</tr>';
			$html[] = 	'</thead>';
			
			
			$html[] = 	'<tbody>';
			
			$total_time = 0;
			$total_size = 0;
			foreach ($this->cfg['modules'] as $key => $module ) {

				$html[] = 		'<tr>';
				$html[] = 			'<td>' . ( $key ) . '</td>';
				$html[] = 			'<td>' . ( number_format($module['loaded_in'], 4) ) . '(seconds)</td>';
				$html[] = 			'<td>' . (  $this->formatBytes($module['memory_usage']) ) . '</td>';
				$html[] = 		'</tr>';
			
				$total_time = $total_time + $module['loaded_in']; 
				$total_size = $total_size + $module['memory_usage']; 
			}

			$html[] = 		'<tr>';
			$html[] = 			'<td colspan="3">';
			$html[] = 				'Total time: <strong>' . ( $total_time ) . '(seconds)</strong><br />';			
			$html[] = 				'Total Memory: <strong>' . ( $this->formatBytes($total_size) ) . '</strong><br />';			
			$html[] = 			'</td>';
			$html[] = 		'</tr>';

			$html[] = 	'</tbody>';
			$html[] = '</table>';
			
			//echo '<script>jQuery("body").append(\'' . ( implode("\n", $html ) ) . '\')</script>';
			echo implode("\n", $html );
		}

		public function check_secure_connection ()
		{

			$secure_connection = false;
			if(isset($_SERVER['HTTPS']))
			{
				if ($_SERVER["HTTPS"] == "on")
				{
					$secure_connection = true;
				}
			}
			return $secure_connection;
		}


		/*
			helper function, image_resize
			// use timthumb
		*/
		public function image_resize ($src='', $w=100, $h=100, $zc=2)
		{
			// in no image source send, return no image
			if( trim($src) == "" ){
				$src = $this->cfg['paths']['freamwork_dir_url'] . '/images/no-product-img.jpg';
			}

			if( is_file($this->cfg['paths']['plugin_dir_path'] . 'timthumb.php') ) {
				return $this->cfg['paths']['plugin_dir_url'] . 'timthumb.php?src=' . $src . '&w=' . $w . '&h=' . $h . '&zc=' . $zc;
			}
		}

		/*
			helper function, upload_file
		*/
		public function upload_file ()
		{
			$slider_options = '';
			 // Acts as the name
            $clickedID = $_POST['clickedID'];
            // Upload
            if ($_POST['type'] == 'upload') {
                $override['action'] = 'wp_handle_upload';
                $override['test_form'] = false;
				$filename = $_FILES [$clickedID];

                $uploaded_file = wp_handle_upload($filename, $override);
                if (!empty($uploaded_file['error'])) {
                    echo json_encode(array("error" => "Upload Error: " . $uploaded_file['error']));
                } else {
                    echo json_encode(array(
							"url" => $uploaded_file['url'],
							"thumb" => ($this->image_resize( $uploaded_file['url'], $_POST['thumb_w'], $_POST['thumb_h'], $_POST['thumb_zc'] ))
						)
					);
                } // Is the Response
            }else{
				echo json_encode(array("error" => "Invalid action send" ));
			}

            die();
		}

		/**
		 * Getter function, shop config
		 *
		 * @params $returnType
		 * @return array
		 */
		public function getShopConfig( $section='', $key='', $returnAs='echo' )
		{
			if( count($this->app_settings) == 0 ){
				$this->app_settings = $this->getAllSettings();
			}

			if( isset($this->app_settings[$this->alias . "_" . $section])) {
				if( isset($this->app_settings[$this->alias . "_" . $section][$key])) {
					if( $returnAs == 'echo' ) echo $this->app_settings[$this->alias . "_" . $section][$key];

					if( $returnAs == 'return' ) return $this->app_settings[$this->alias . "_" . $section][$key];
				}
			}
		}

		public function download_image( $file_url='', $pid=0, $action='insert', $product_title='', $step=0 )
		{
			if(trim($file_url) != ""){
				$amazon_settings = $this->getAllSettings('array', 'amazon');
				
				if( $amazon_settings["rename_image"] == 'product_title' ){
					$image_name = sanitize_file_name($product_title);
					$image_name = preg_replace("/[^a-zA-Z0-9-]/", "", $image_name);
					$image_name = substr($image_name, 0, 200);
				}else{
					$image_name = uniqid();
				}
				
				// Find Upload dir path
				$uploads = wp_upload_dir();
				$uploads_path = $uploads['path'] . '';
				$uploads_url = $uploads['url'];

				$fileExt = @end(@explode(".", $file_url));
				$filename = $image_name . "-" . ( $step ) . "." . $fileExt;
				
				// Save image in uploads folder
				$response = wp_remote_get( $file_url );
  
				if( !is_wp_error( $response ) ){
					$image = $response['body'];
					
					$image_url = $uploads_url . '/' . $filename; // URL of the image on the disk
					$image_path = $uploads_path . '/' . $filename; // Path of the image on the disk
					$ii = 0;
					while ( $this->verifyFileExists($image_path) ) {
						$filename = $image_name . "-" . ( $step );
						$filename .= '-'.$ii;
						$filename .= "." . $fileExt;
						
						$image_url = $uploads_url . '/' . $filename; // URL of the image on the disk
						$image_path = $uploads_path . '/' . $filename; // Path of the image on the disk
						$ii++;
					}

					// verify image hash
					$hash = md5($image);
					$hashFound = $this->verifyProdImageHash( $hash );
					if ( !empty($hashFound) && isset($hashFound->media_id) ) { // image hash not found!
					
						$orig_attach_id = $hashFound->media_id;
						$image_path = $hashFound->image_path;
						
						return array(
							'attach_id' 		=> $orig_attach_id, // $attach_id,
							'image_path' 		=> $image_path,
							'hash'				=> $hash
						);
					}
					//write image if the wp method fails
					$has_wrote = $this->wp_filesystem->put_contents(
						$uploads_path . '/' . $filename, $image, FS_CHMOD_FILE
					);
					
					if( !$has_wrote ){
						file_put_contents( $uploads_path . '/' . $filename, $image );
					}

					// Add image in the media library - Step 3
					$wp_filetype = wp_check_filetype( basename( $image_path ), null );
					$attachment = array(
						// 'guid' 			=> $image_url,
						'post_mime_type' => $wp_filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $image_path ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
 
					$attach_id = wp_insert_attachment( $attachment, $image_path, $pid  ); 
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attach_id, $image_path );
					wp_update_attachment_metadata( $attach_id, $attach_data );
  
					return array(
						'attach_id' 		=> $attach_id,
						'image_path' 		=> $image_path,
						'hash'				=> $hash
					);
				}
				else{
					return array(
						'status' 	=> 'invalid',
						'msg' 		=> htmlspecialchars( implode(';', $response->get_error_messages()) )
					);
				}
			}
		}
		
		public function verifyProdImageHash( $hash ) {
			require( $this->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
			$WooZoneLightAssetDownloadCron = new WooZoneLightAssetDownload();
			
			return $WooZoneLightAssetDownloadCron->verifyProdImageHash( $hash );
		}

		public function productPriceUpdate_frm()
		{
			$asin = isset($_REQUEST['asin']) ? $_REQUEST['asin'] : '';
			if( strlen($asin) == 10 ){
				// get product id by ASIN
				$product = $this->db->get_row( "SELECT * from {$this->db->prefix}postmeta where 1=1 and meta_key='_amzASIN' and meta_value='$asin' ", ARRAY_A );
				
				$post_id = (int)$product['post_id'];
				if( $post_id > 0 ){
					
					$amzProduct = $this->amzHelper->getProductDataFromAmazon( true );
					
					// set the product price
					$this->amzHelper->productPriceUpdate( $amzProduct, $post_id, true );
				}
			}
			
			return 'invalid';
		}

		public function addNewProduct ( $retProd=array(), $import_images=true )
		{
			$this->opStatusMsgInit();

			if(count($retProd) == 0) {
				return false;
			}
			
			$shop_settings = @unserialize( get_option( $this->alias . '_amazon' ) );
			$default_import = (isset($shop_settings["default_import"]) && $shop_settings["default_import"] == 'publish' ? 'publish' : 'draft');
			$price_zero_import = (isset($shop_settings["import_price_zero_products"]) && $shop_settings["import_price_zero_products"] == 'yes' ? true : false);

			// verify if : amazon zero price product!
			if ( !$price_zero_import && $this->amzHelper->productAmazonPriceIsZero( $retProd ) ) {
				$this->opStatusMsg = array(
					'operation'			=> 'add_prod',
					'msg'				=> 'Add New Product/ Product (asin: ' . $retProd['ASIN'] . ') price is zero, so it is skipped!'
				);
				return false;
			}
   
			// first 3 paragraph
			$excerpt = @explode("\n", @strip_tags( implode("\n", $retProd['Feature']) ) );
			$excerpt = @implode("\n", @array_slice($excerpt, 0, 3));
		
			$args = array(
				'post_title' 	=> $retProd['Title'],
				'post_status' 	=> $default_import,
				'post_content' 	=> (count($retProd["images"]) > 0 ? "[gallery]" : "") . "\n" . $retProd['EditorialReviews'] . "\n" . (count($retProd['Feature']) > 0 &&  is_array($retProd['Feature']) == true ? implode("\n", $retProd['Feature']) : '') . "\n" . '[amz_corss_sell asin="' . ( $retProd['ASIN'] ) . '"]',
				'post_excerpt' 	=> $excerpt,
				'post_type' 	=> 'product',
				'menu_order' 	=> 0,
				'post_author' 	=> 1
			);

			$existProduct = amzStore_bulk_wp_exist_post_by_args($args);
			$metaPrefix = 'amzStore_product_';

			// check if post exists, if exist return array
			if( $existProduct === false){
				$lastId = wp_insert_post($args);
			}else{
				$lastId = $existProduct['ID'];
			}

			apply_filters( 'WooZoneLight_after_product_import', $lastId );
  
			// spin post/product content!
			$amz_settings = $this->getAllSettings('array', 'amazon');

			if( $import_images == true ){
				// get product images
				$this->amzHelper->set_product_images( $retProd, $lastId );
			}

			$_REQUEST['to-category'] = isset($_REQUEST['to-category']) ? $_REQUEST['to-category'] : 'amz';
			if($_REQUEST['to-category'] != 'amz'){
				// set the post category
				wp_set_object_terms( $lastId, array($_REQUEST['to-category']), 'product_cat', true);
			}else{
				// setup product categories
				$createdCats = $this->amzHelper->set_product_categories( $retProd['BrowseNodes'] );
				
				// Assign the post on the categories created
            	wp_set_post_terms( $lastId,  $createdCats, 'product_cat' );
			}
 
			// than update the metapost
			$this->amzHelper->set_product_meta_options( $retProd, $lastId, false );

			// Set the product type			
			$this->update_products_type( $lastId );
			
			// set the product price
			$this->amzHelper->productPriceUpdate( $retProd, $lastId, false );
			
			return $lastId;
		}

		public function updateWooProduct ( $retProd=array(), $rules=array(), $lastId=0 )
		{
			if(count($retProd) == 0) {
				return false;
			}
			$args_update = array();
			$args_update['ID'] = $lastId;
			if($rules['title'] == true) $args_update['post_title'] 	= $retProd['Title'];
			if($rules['content'] == true) $args_update['post_content'] = $retProd['EditorialReviews'] . "\n" . (count($retProd['Feature']) > 0 &&  is_array($retProd['Feature']) == true ? implode("\n", $retProd['Feature']) : '') . "\n" . '[amz_corss_sell asin="' . ( $retProd['ASIN'] ) . '"]';

			// update the post if needed
			if(count($args_update) > 1){
				wp_update_post( $args_update );
			}

			$tab_data = array();
			$tab_data[] = array(
				'id' => 'amzAff-customer-review',
				'content' => '<iframe src="' . ( isset($retProd['CustomerReviewsURL']) ? $retProd['CustomerReviewsURL'] : '' ) . '" width="100%" height="450" frameborder="0"></iframe>'
			); 
			
			// than update the metapost
			if($rules['sku'] == true) update_post_meta($lastId, '_sku', $retProd['SKU']);
			if($rules['url'] == true) update_post_meta($lastId, '_product_url', home_url('/?redirectAmzASIN=' . $retProd['ASIN'] ));
			if($rules['reviews'] == true) {
				if( isset($retProd['CustomerReviewsURL']) && @trim($retProd['CustomerReviewsURL']) != "" ) 
					update_post_meta($lastId, 'amzaff_woo_product_tabs', $tab_data);
			}
			if($rules['price'] == true){ 
				// set the product price
				$this->amzHelper->productPriceUpdate( $retProd, $lastId, false );
			}

			return $lastId;
		}

		public function getAmzSimilarityProducts ( $asin, $return_nr=3, $force_update=false )
		{
			// add 1 fake return products, current product
			$return_nr = $return_nr + 1;

			$cache_valid_for = (60 * 60 * 24); // 24 hours in seconds

			// check for cache of this ASIN
			$cache_request = $this->db->get_row( $this->db->prepare( "SELECT * FROM " . ( $this->db->prefix ) . "amz_cross_sell WHERE ASIN = %s", $asin), ARRAY_A );

			// if cache found for this product
			if( $cache_request != "" && count($cache_request) > 0 && $force_update === false){
				// if cache still valid, return from mysql cache
				if( isset($cache_request['add_date']) || (strtotime($cache_request['add_date']) > (time() + $cache_valid_for)) ){

					$ret = array();
					// get products from DB cache amz_cross_sell table
					$products = @unserialize($cache_request['products']);

					return array_slice( $products, 0, $return_nr);
				}
			}

			$amazon_settings = $this->getAllSettings('array', 'amazon');

			// load the amazon webservices client class
			require_once( $this->cfg['paths']['plugin_dir_path'] . '/lib/scripts/amazon/aaAmazonWS.class.php');

			// create new amazon instance
			$aaAmazonWS = new aaAmazonWS(
				$amazon_settings['AccessKeyID'],
				$amazon_settings['SecretAccessKey'],
				$amazon_settings['country'],
				$this->main_aff_id()
			);
			
			$retProd = array();
			$similarity = $aaAmazonWS->responseGroup('Medium,ItemAttributes,Offers')->optionalParameters(array(
				'MerchantId' => 'Amazon',
				'Condition' => 'New'
			))->similarityLookup($asin);
			
			$thisProd = $aaAmazonWS->responseGroup('Large,OfferFull,Offers')->optionalParameters(array(
				'MerchantId' => 'Amazon',
				'Condition' => 'New'
			))->lookup($asin);

			if($thisProd['Items']['Request']["IsValid"] == "True" && isset($thisProd['Items']['Item']) && count($thisProd['Items']['Item']) > 0){
				$thisProd = $thisProd['Items']['Item'];

				// product large image
				$retProd[$thisProd['ASIN']]['thumb'] = $thisProd['SmallImage']['URL'];

				$retProd[$thisProd['ASIN']]['ASIN'] = $thisProd['ASIN'];

				// product title
				$retProd[$thisProd['ASIN']]['Title'] = isset($thisProd['ItemAttributes']['Title']) ? $thisProd['ItemAttributes']['Title'] : '';

				// product Manufacturer
				$retProd[$thisProd['ASIN']]['Manufacturer'] = isset($thisProd['ItemAttributes']['Manufacturer']) ? $thisProd['ItemAttributes']['Manufacturer'] : '';

				$retProd[$thisProd['ASIN']]['price'] = isset($thisProd['OfferSummary']['LowestNewPrice']['FormattedPrice']) ? preg_replace( "/[^0-9,.]/", "", $thisProd['OfferSummary']['LowestNewPrice']['FormattedPrice'] ) : '';
			}

			if($similarity['Items']["Request"]["IsValid"] == "True" && isset($similarity['Items']['Item']) && count($similarity['Items']['Item']) > 1){
				foreach ($similarity['Items']['Item'] as $key => $value){
					$thisProd = $value;

					if(count($similarity['Items']['Item']) > 0 && count($value) > 0 && isset($thisProd['ASIN']) && strlen($thisProd['ASIN']) >= 10 ){
						// product large image
						$retProd[$thisProd['ASIN']]['thumb'] = $thisProd['SmallImage']['URL'];

						$retProd[$thisProd['ASIN']]['ASIN'] = $thisProd['ASIN'];

						// product title
						$retProd[$thisProd['ASIN']]['Title'] = isset($thisProd['ItemAttributes']['Title']) ? $thisProd['ItemAttributes']['Title'] : '';

						// product Manufacturer
						$retProd[$thisProd['ASIN']]['Manufacturer'] = isset($thisProd['ItemAttributes']['Manufacturer']) ? $thisProd['ItemAttributes']['Manufacturer'] : '';

						$retProd[$thisProd['ASIN']]['price'] = isset($thisProd['OfferSummary']['LowestNewPrice']['FormattedPrice']) ? preg_replace( "/[^0-9,.]/", "", $thisProd['OfferSummary']['LowestNewPrice']['FormattedPrice'] ) : '';

						// remove if don't have valid price
						if( !isset($retProd[$thisProd['ASIN']]['price']) || trim($retProd[$thisProd['ASIN']]['price']) == "" ){
							@unlink($retProd[$thisProd['ASIN']]);
						}
					}
				}
			}

			// if cache not found for this product
			if( $cache_request == "" && count($cache_request) == 0 ){
				$this->db->insert(
					$this->db->prefix . "amz_cross_sell",
					array(
						'ASIN' 			=> $asin,
						'products' 		=> serialize(array_slice( $retProd, 0, $return_nr)),
						'nr_products'	=> $return_nr
					),
					array(
						'%s',
						'%s',
						'%d'
					)
				);
			}
			else{
				$this->db->update(
					$this->db->prefix . "amz_cross_sell",
					array(
						'products' 		=> serialize(array_slice( $retProd, 0, $return_nr)),
						'nr_products'	=> $return_nr
					),
					array( 'ASIN' => $asin ),
					array(
						'%s',
						'%s',
						'%d'
					)
				);
			}

			return array_slice( $retProd, 0, $return_nr);
		}

		public function remove_gallery($content)
		{
		    return str_replace('[gallery]', '', $content);
		}

		public function cross_sell_box( $atts )
		{
			extract( shortcode_atts( array(
				'asin' => ''
			), $atts ) );

			$shop_settings = $this->getAllSettings('array', 'amazon', true);
			$cross_selling = false;
 			
			if( $cross_selling == false ) return '';

			global $product;

			// get product related items from Amazon
			$products = $this->getAmzSimilarityProducts( $asin );

			$backHtml = array();
			if( count($products) > 1 ){
				$amazon_settings = $this->getAllSettings('array', 'amazon');

				$backHtml[] = "<link rel='stylesheet' id='amz-cross-sell' href='" . ( $this->cfg['paths']['design_dir_url'] ) . "/cross-sell.css' type='text/css' media='all' />";

				$backHtml[] = '<div class="cross-sell">';
				$backHtml[] = 	'<h2>' . ( __('Frequently Bought Together', $this->localizationName ) ) . '</h2>';
				$backHtml[] = 	'<div style="margin-top: 0px;" class="separator"></div>';


				$backHtml[] = 	'<ul id="feq-products">';
				$cc = 0;
				$_total_price = 0;
				foreach ($products as $key => $value) {
					$value['price'] = str_replace(",", ".", $value['price']);
					$prod_link = home_url('/?redirectAmzASIN=' . $value['ASIN'] );
					$backHtml[] = 	'<li>';
					$backHtml[] = 	'<a target="_blank" rel="nofollow" href="' . ( $prod_link ) . '">';
					$backHtml[] = 		'<img class="cross-sell-thumb" id="cross-sell-thumb-' . ( $value['ASIN'] ) . '" src="' . ( $value['thumb'] ) . '" alt="' . ( htmlentities( str_replace('"', "'", $value['Title']) ) ) . '">';
					$backHtml[] = 	'</a>';
					if( $cc < (count($products) - 1) ){
						$backHtml[] = 		'<div class="plus-sign">+</div>';
					}

					$backHtml[] = 	'</li>';

					$cc++;

					$_total_price = $_total_price + $value['price'];
				}

				$backHtml[] = 		'<li class="cross-sell-buy-btn">';
				$backHtml[] = 			'<span id="cross-sell-bpt">Price for all:</span>';
				$backHtml[] = 			'<span id="cross-sell-buying-price" class="price">' . ( woocommerce_price( $_total_price ) ) . '</span>';
				$backHtml[] = 			'<div style="clear:both"></div><a href="' . home_url(). '" id="cross-sell-add-to-cart"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAH4AAAAYCAYAAAA8jknPAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAA2RpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuMC1jMDYwIDYxLjEzNDc3NywgMjAxMC8wMi8xMi0xNzozMjowMCAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wTU09Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9tbS8iIHhtbG5zOnN0UmVmPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvc1R5cGUvUmVzb3VyY2VSZWYjIiB4bWxuczp4bXA9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC8iIHhtcE1NOk9yaWdpbmFsRG9jdW1lbnRJRD0ieG1wLmRpZDo1NTg5QjA4OTlCQjRFMTExQkQ4Q0QxRDVGNERCQzdFMiIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDpCREE4OUY2OEI0OUQxMUUxQTZGQ0EwNjVCNUJCMERFRiIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDpCREE4OUY2N0I0OUQxMUUxQTZGQ0EwNjVCNUJCMERFRiIgeG1wOkNyZWF0b3JUb29sPSJBZG9iZSBQaG90b3Nob3AgQ1M1IFdpbmRvd3MiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDo1ODg5QjA4OTlCQjRFMTExQkQ4Q0QxRDVGNERCQzdFMiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDo1NTg5QjA4OTlCQjRFMTExQkQ4Q0QxRDVGNERCQzdFMiIvPiA8L3JkZjpEZXNjcmlwdGlv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" ></a>';
				$backHtml[] = 		'</li>';
				$backHtml[] = 	'</ul>';

				$backHtml[] = '<div class="cross-sell-buy-selectable">';
				$backHtml[] = 	'<ul class="cross-sell-items">';
				$cc = 0;
				foreach ($products as $key => $value) {

					if( $cc == 0 ){
						$backHtml[] = 		'<li>';
						$backHtml[] = 			'<input type="checkbox" checked="checked" value="' . ( $value['ASIN'] ) . '">';
						$backHtml[] = 			'<div class="cross-sell-product-title"><strong>' . __('This item:', $this->localizationName) . ' </strong>' . $value['Title'] . '</div>';
						$backHtml[] = 			'<div class="cross-sell-item-price">' . ( woocommerce_price( $value['price'] ) ) . '</div>';
						$backHtml[] = 		'</li>';
					}
					else{
						
						$prod_link = home_url('/?redirectAmzASIN=' . $value['ASIN'] );
						$backHtml[] = 		'<li>';
						$backHtml[] = 			'<input type="checkbox" checked="checked" value="' . ( $value['ASIN'] ) . '">';
						$backHtml[] = 			'<div class="cross-sell-product-title">' . ( '<a target="_blank" rel="nofollow" href="' . ( $prod_link ) . '">' . $value['Title'] .'</a>' ) . '</div>';
						$backHtml[] = 			'<div class="cross-sell-item-price">' . ( woocommerce_price( $value['price'] ) ) . '</div>';
						$backHtml[] = 		'</li>';
					}

					$cc++;
				}
				$backHtml[] = 	'</table>';

				$backHtml[] = '</div>';

				$backHtml[] = '</div>';

				$backHtml[] = '<div style="clear:both;"></div>';

				$backHtml[] = "<script type='text/javascript' src='" . ( $this->cfg['paths']['design_dir_url'] ) . "/cross-sell.js'></script>";
			}

			return ($_total_price > 0 ) ? implode(PHP_EOL, $backHtml) : '';
		}
		
		/**
	    * HTML escape given string
	    *
	    * @param string $text
	    * @return string
	    */
	    public function escape($text)
	    {
	        $text = (string) $text;
	        if ('' === $text) return '';

	        $result = @htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
	        if (empty($result)) {
	            $result = @htmlspecialchars(utf8_encode($text), ENT_COMPAT, 'UTF-8');
	        }

	        return $result;
	    }
		
		public function getBrowseNodes( $nodeid=0 )
		{
			if( !is_numeric($nodeid) ){
				return array(
					'status' 	=> 'invalid',
					'msg'		=> 'The $nodeid is not numeric: ' . $nodeid
				);
			}

			// try to get the option with this browsenode
			$nodes = get_option( $this->alias . '_node_children_' . $nodeid, false );
			
			// unable to find the node into cache, get live data
			if( !isset($nodes) || $nodes == false || count($nodes) == 0 ){
				$nodes = $this->amzHelper->browseNodeLookup( $nodeid );
				
				if( isset($nodes['BrowseNodes']) && count($nodes['BrowseNodes']) > 0 ){
					if( isset($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']) && count($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']) > 0 ){
	
						if( !isset($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'][1]['BrowseNodeId']) ){
							$nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'] = array(
								$nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']
							);
						}
						
						if( count($nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode']) > 0 ){
							$nodes = $nodes['BrowseNodes']['BrowseNode']['Children']['BrowseNode'];
							
							// store the cache into DB
							update_option( $this->alias . '_node_children_' . $nodeid, $nodes );
						}
					}
				}
			}
			
			
			return $nodes; 
		}

		public function multi_implode($array, $glue) 
		{
		    $ret = '';
		
		    foreach ($array as $item) {
		        if (is_array($item)) {
		            $ret .= $this->multi_implode($item, $glue) . $glue;
		        } else {
		            $ret .= $item . $glue;
		        }
		    }
		
		    $ret = substr($ret, 0, 0-strlen($glue));
		
		    return $ret;
		}

		public function download_asset_lightbox( $prod_id=0, $return='die' )
		{
			$prod_id = isset($_REQUEST['prod_id']) ? $_REQUEST['prod_id'] : $prod_id;
			
			$assets = $this->amzHelper->get_asset_by_postid( 'all', $prod_id, true );
			if ( count($assets) <= 0 ) {
				die( json_encode(array(
					'status' => 'invalid',
					'html'	=> __("this product has no assets to be dowloaded!", $this->localizationName )
				)));
			}
			
			$html = array();
			$html[] = '<div class="WooZoneLight-asset-download-lightbox">';
			$html[] = 	'<div class="WooZoneLight-donwload-in-progress-box">';
			$html[] = 		'<h1>' . __('Images download in progress ... ', $this->localizationName ) . '<a href="#" class="WooZoneLight-button red" id="WooZoneLight-close-btn">' . __('CLOSE', $this->localizationName ) . '</a></h1>';
			$html[] = 		'<p class="WooZoneLight-message WooZoneLight-info WooZoneLight-donwload-notice">';
			$html[] = 		__('Please be patient while the images are downloaded. 
			This can take a while if your server is slow (inexpensive hosting) or if you have many images. 
			Do not navigate away from this page until this script is done. 
			You will be notified via this box when the regenerating is completed.', $this->localizationName );
			$html[] = 		'</p>';
			
			$html[] = 		'<div class="WooZoneLight-process-progress-bar">';
			$html[] = 			'<div class="WooZoneLight-process-progress-marker"><span>0%</span></div>';
			$html[] = 		'</div>';
			
			$html[] = 		'<div class="WooZoneLight-images-tail">';
			$html[] = 			'<ul>';
			
			if( count($assets) > 0 ){
				foreach ($assets as $asset) {
					 
					$html[] = 		'<li data-id="' . ( $asset->id ) . '">';
					$html[] = 			'<img src="' . ( $asset->thumb ) . '">';
					$html[] = 		'</li>';	
				}
			} 
			
			$html[] = 			'</ul>';
			$html[] = 		'</div>';
			$html[] = 		'
			<script>
				jQuery(".WooZoneLight-images-tail ul").each(function(){
					
					var that = jQuery(this),
						lis = that.find("li"),
						size = lis.size();
					
					that.width( size *  86 );
				});
				jQuery(".WooZoneLight-images-tail ul").scrollLeft(0);
			</script>
			';
			
			$html[] = 		'<h2 class="WooZoneLight-process-headline">' . __('Debugging Information:', $this->localizationName ) . '</h2>';
			$html[] = 		'<table class="WooZoneLight-table WooZoneLight-debug-info">';
			$html[] = 			'<tr>';
			$html[] = 				'<td width="150">' . __('Total Images:', $this->localizationName ) . '</td>';
			$html[] = 				'<td>' . ( count($assets) ) . '</td>';
			$html[] = 			'</tr>';
			$html[] = 			'<tr>';
			$html[] = 				'<td>' . __('Images Downloaded:', $this->localizationName ) . '</td>';
			$html[] = 				'<td class="WooZoneLight-value-downloaded">0</td>';
			$html[] = 			'</tr>';
			$html[] = 			'<tr>';
			$html[] = 				'<td>' . __('Downloaded Failures:', $this->localizationName ) . '</td>';
			$html[] = 				'<td class="WooZoneLight-value-failures">0</td>';
			$html[] = 			'</tr>';
			$html[] = 		'</table>';
			
			$html[] = 		'<div class="WooZoneLight-downoad-log">';
			$html[] = 			'<ol>';
			//$html[] = 				'<li>"One-size-fits-most-Tube-DressCoverup-Field-Of-Flowers-White-0" (ID 214) failed to resize. The error message was: The originally uploaded image file cannot be found at <code>/home/aateam30/public_html/cc/wp-plugins/woo-Amazon-payments/wp-content/uploads/2014/03/One-size-fits-most-Tube-DressCoverup-Field-Of-Flowers-White-0.jpg</code></li>';
			$html[] = 			'</ol>';
			$html[] = 		'</div>';
			$html[] = 	'</div>';
			$html[] = '</div>';
			
			if( $return == 'die' ){
				die( json_encode(array(
					'status' => 'valid',
					'html'	=> implode("\n", $html)
				)));
			}
			
			return implode("\n", $html);
		}
		
		
		/**
		 * Delete product assets
		 */
		public function product_assets_verify() {
 			if ( current_user_can( 'delete_posts' ) )
				add_action( 'delete_post', array($this, 'product_assets_delete'), 10 );
		}
		
		public function product_assets_delete($prod_id) {
			// verify we are in woocommerce product
			if( function_exists('get_product') ){
				$product = get_product( $prod_id );
				if ( isset($product->id) && (int) $product->id > 0 ) {

					require( $this->cfg['paths']['plugin_dir_path'] . '/modules/assets_download/init.php' );
					$WooZoneLightAssetDownloadCron = new WooZoneLightAssetDownload();
					
					return $WooZoneLightAssetDownloadCron->product_assets_delete( $prod_id );
				}  
			}
		}


		/**
		 * Usefull
		 */
		
		//format right (for db insertion) php range function!
		public function doRange( $arr ) {
			$newarr = array();
			if ( is_array($arr) && count($arr)>0 ) {
				foreach ($arr as $k => $v) {
					$newarr[ $v ] = $v;
				}
			}
			return $newarr;
		}
		
		//verify if file exists!
		public function verifyFileExists($file, $type='file') {
			clearstatcache();
			if ($type=='file') {
				if (!file_exists($file) || !is_file($file) || !is_readable($file)) {
					return false;
				}
				return true;
			} else if ($type=='folder') {
				if (!is_dir($file) || !is_readable($file)) {
					return false;
				}
				return true;
			}
			// invalid type
			return 0;
		}
		
		public function formatBytes($bytes, $precision = 2) {
			$units = array('B', 'KB', 'MB', 'GB', 'TB');

			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);

			// Uncomment one of the following alternatives
			// $bytes /= pow(1024, $pow);
			$bytes /= (1 << (10 * $pow));

			return round($bytes, $precision) . ' ' . $units[$pow];
		}
		
		public function prepareForInList($v) {
			return "'".$v."'";
		}
		
		public function db_custom_insert($table, $fields, $ignore=false, $wp_way=false) {
			if ( $wp_way && !$ignore ) {
				$this->db->insert( 
					$table, 
					$fields['values'], 
					$fields['format']
				);
			} else {
			
				$formatVals = implode(', ', array_map(array('WooZoneLight', 'prepareForInList'), $fields['format']));
				$theVals = array();
				foreach ( $fields['values'] as $k => $v ) $theVals[] = $k;

				$q = "INSERT " . ($ignore ? "IGNORE" : "") . " INTO $table (" . implode(', ', $theVals) . ") VALUES (" . $formatVals . ");";
				foreach ($fields['values'] as $kk => $vv)
					$fields['values']["$kk"] = esc_sql($vv);
  
				$q = vsprintf($q, $fields['values']);
				$r = $this->db->query( $q );
			}
		}
		
		public function verify_product_isamazon($prod_id) {
			// verify we are in woocommerce product
			if( function_exists('get_product') ){
				$product = get_product( $prod_id );
				
				if ( isset($product->id) && (int) $product->id > 0 ) {
					
					// verify is amazon product!
					$asin = get_post_meta($prod_id, '_amzASIN', true);
					if ( $asin!==false && strlen($asin) > 0 ) {
						return true;
					}
				}
			}
			return false;
		}
		
		public function verify_product_isvariation($prod_id) {
			// verify we are in woocommerce product
			if( function_exists('get_product') ){
				$product = new WC_Product_Variable( $prod_id ); // WC_Product
  
				if ( isset($product->id) && (int) $product->id > 0 ) {
					if ( $product->has_child() ) // is product variation parent!
						return true;
				}
			}
			return false;
		}
		
		public function get_product_variations($prod_id) {
			// verify we are in woocommerce product
			if( function_exists('get_product') ){
				$product = new WC_Product_Variable( $prod_id ); // WC_Product
  
				if ( isset($product->id) && (int) $product->id > 0 ) {
					return $product->get_children();
				}
			}
			return array();
		}
		
		/**
		 * spin post/product content
		 */
		public function spin_content( $req=array() ) {
			return $ret;
		}

		/**
		 * 
		 */
		/**
		 * setup module messages
		 */
		public function print_module_error( $module=array(), $error_number, $title="" )
		{
			$html = array();
			if( count($module) == 0 ) return true;
  
			$html[] = '<div class="WooZoneLight-grid_4 WooZoneLight-error-using-module">';
			$html[] = 	'<div class="WooZoneLight-panel">';
			$html[] = 		'<div class="WooZoneLight-panel-header">';
			$html[] = 			'<span class="WooZoneLight-panel-title">';
			$html[] = 				__( $title, $this->localizationName );
			$html[] = 			'</span>';
			$html[] = 		'</div>';
			$html[] = 		'<div class="WooZoneLight-panel-content">';
			
			$error_msg = isset($module[$module['alias']]['errors'][$error_number]) ? $module[$module['alias']]['errors'][$error_number] : '';
			
			$html[] = 			'<div class="WooZoneLight-error-details">' . ( $error_msg ) . '</div>';
			$html[] = 		'</div>';
			$html[] = 	'</div>';
			$html[] = '</div>';
			
			return implode("\n", $html);
		}
		
		public function convert_to_button( $button_params=array() )
		{
			$button = array();
			$button[] = '<a';
			if(isset($button_params['url'])) 
				$button[] = ' href="' . ( $button_params['url'] ) . '"';
			
			if(isset($button_params['target'])) 
				$button[] = ' target="' . ( $button_params['target'] ) . '"';
			
			$button[] = ' class="WooZoneLight-button';
			
			if(isset($button_params['color'])) 
				$button[] = ' ' . ( $button_params['color'] ) . '';
				
			$button[] = '"';
			$button[] = '>';
			
			$button[] =  $button_params['title'];
		
			$button[] = '</a>';
			
			return implode("", $button);
		}

		public function load_terms($taxonomy){
    		global $wpdb;
			
			$query = "SELECT DISTINCT t.name FROM {$wpdb->terms} AS t INNER JOIN {$wpdb->term_taxonomy} as tt ON tt.term_id = t.term_id WHERE 1=1 AND tt.taxonomy = '".esc_sql($taxonomy)."'";
    		$result =  $wpdb->get_results($query , OBJECT);
    		return $result;                 
		}
		
		public function get_current_page_url() {
			$url = (!empty($_SERVER['HTTPS']))
				?
				"https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
				:
				"http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']
			;
			return $url;
		}
		
		public function get_country_perip_external( $return_field='country' ) {
			$config = @unserialize( get_option( $this->alias . '_amazon' ) );
			
			$paths = array(
				'api.hostip.info'			=> 'http://api.hostip.info/country.php?ip={ipaddress}',
				'www.geoplugin.net'			=> 'http://www.geoplugin.net/json.gp?ip={ipaddress}',
				'www.telize.com'			=> 'http://www.telize.com/geoip/{ipaddress}',
				'ipinfo.io'					=> 'http://ipinfo.io/{ipaddress}/geo',
			);
			
			$service_used = 'www.geoplugin.net';
			if ( isset($config['services_used_forip']) && !empty($config['services_used_forip']) ) {
				$service_used = $config['services_used_forip'];
			}
			
			$service_url = $paths["$service_used"];
			$service_url = str_replace('{ipaddress}', $_SERVER["REMOTE_ADDR"], $service_url);

			$get_user_location = wp_remote_get( $service_url );
			if ( isset($get_user_location->errors) ) {
				$main_aff_site = $this->main_aff_site();
				$country = strtoupper(str_replace(".", '', $main_aff_site));
			} else {
				$country = $get_user_location['body'];
				switch ($service_used) {
					case 'api.hostip.info':
						break;
						
					case 'www.geoplugin.net':
						$country = json_decode($country);
						$country = strtoupper( $country->geoplugin_countryCode );
						break;
						
					case 'www.telize.com':
						$country = json_decode($country);
						$country = strtoupper( $country->country_code );
						break;
						
					case 'ipinfo.io':
						$country = json_decode($country);
						$country = strtoupper( $country->country );
						break;
						
					default:
						break;
				}
			}
			
			if ( $return_field == 'country' ) {
				$user_country = $this->amzForUser($country);
				return $user_country;
			}
		}

		
		
		public function premium_message( $msg_type="row" )
		{
			$html = array();
			
			$html[] = '<div class="WooZoneLightPremiumMSG">';
			
			if( $msg_type == 'row-large' ){
				$html[] = "<h2 class='msg-title'>Like WooZone Light Version? This module is available only on the premium version. </h2>";
				$html[] = "<p class='msg-intro'>Visit <a href='" . ( $this->buy_url() ) . "' target='_blank'>WooZone Premium page</a> for details and full features lists.</p>";
				$html[] = "<a href='" . ( $this->buy_url() ) . "' class='WooZoneLightBuyNowBtn' target='_blank'></a>";
			}
			
			if( $msg_type == 'row-small' ){
				$html[] = "<h3 class='msg-small'>This feature is available only on Premium Version. <a href='" . ( $this->buy_url() ) . "' target='_blank'>Visit Premium Version Page. </a></h3>";
			}
			$html[] = '</div>';
			
			$html[] = '';
			
			return implode( "\n", $html );
		}
		
		public function buy_url()
		{
			return 'http://codecanyon.net/item/woocommerce-amazon-affiliates-wordpress-plugin/3057503?ref=AA-Team';
		}
	}
}