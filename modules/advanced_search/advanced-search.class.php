<?php
/*
* Define class Modules Manager List
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLightAdvancedSearch') != true) {
    class WooZoneLightAdvancedSearch
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';
		
        /*
        * Store some helpers config             
        */
        public $cfg = array();
        public $module = array();
        public $networks = array();
		private $amz_setup = null;
		public $the_plugin = null;
		
        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct($cfg, $module)
        {
        	global $WooZoneLight;
			
        	$this->the_plugin = $WooZoneLight;
            $this->cfg    = $cfg;
            $this->module = $module;
			$this->amz_setup = $WooZoneLight->getAllSettings('array', 'amazon');
        }
		
		public function moduleValidation() {
			$ret = array(
				'status'			=> false,
				'html'				=> ''
			);
			
			// AccessKeyID, SecretAccessKey, AffiliateId, main_aff_id
			
			// find if user makes the setup
			$module_settings = $this->the_plugin->getAllSettings('array', 'amazon');

			$module_mandatoryFields = array(
				'AccessKeyID'			=> false,
				'SecretAccessKey'		=> false,
				'main_aff_id'			=> false
			);
			if ( isset($module_settings['AccessKeyID']) && !empty($module_settings['AccessKeyID']) ) {
				$module_mandatoryFields['AccessKeyID'] = true;
			}
			if ( isset($module_settings['SecretAccessKey']) && !empty($module_settings['SecretAccessKey']) ) {
				$module_mandatoryFields['SecretAccessKey'] = true;
			}
			if ( isset($module_settings['main_aff_id']) && !empty($module_settings['main_aff_id']) ) {
				$module_mandatoryFields['main_aff_id'] = true;
			}
			$mandatoryValid = true;
			foreach ($module_mandatoryFields as $k=>$v) {
				if ( !$v ) {
					$mandatoryValid = false;
					break;
				}
			}
			
			if ( !$mandatoryValid ) {
				$error_number = 1; // from config.php / errors key
				
				$ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use Advanced Search module, yet!' );
				return $ret;
			}
			
			if( !$this->the_plugin->is_woocommerce_installed() ) {  
				$error_number = 2; // from config.php / errors key
				
				$ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use Advanced Search module, yet!' );
				return $ret;
			}
			
			if( !extension_loaded('soap') ) {
				if( !(extension_loaded("curl") && function_exists('curl_init')) ) {
					$error_number = 3; // from config.php / errors key
				
					$ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use Advanced Search module, yet!' );
					return $ret;	
				}	
			}

			if( !(extension_loaded("curl") && function_exists('curl_init')) ) {  
				$error_number = 4; // from config.php / errors key
				
				$ret['html'] = $this->the_plugin->print_module_error( $this->module, $error_number, 'Error: Unable to use Advanced Search module, yet!' );
				return $ret;
			}
			
			$ret['status'] = true;
			return $ret;
		}
		
        public function printSearchInterface()
        {
			// find if user makes the setup
			$moduleValidateStat = $this->moduleValidation();
			if ( !$moduleValidateStat['status'] || !is_object($this->the_plugin->amzHelper) || is_null($this->the_plugin->amzHelper) )
				echo $moduleValidateStat['html'];
			else{ 
							
        	/*if ( !is_object($this->the_plugin->amzHelper) || is_null($this->the_plugin->amzHelper) ) {
        		$html = array();
        		$html[] = '<div class="WooZoneLight-message blue">You need to set the Access Key ID, Secret Access Key and Your Affiliate IDs first!</div>';
				echo implode('\n', $html);
        	} else {*/
?>
<style>#WooZoneLight-advanced-search {display: none;}</style>
<link rel='stylesheet' href='<?php echo $this->module['folder_uri'];?>extra-style.css' type='text/css' media='all' />
<script type="text/javascript" src="<?php echo $this->module['folder_uri'];?>advanced-search.class.js" ></script>

<div id="WooZoneLight-advanced-search">
	<!-- Main loading box -->
	<div id="main-loading">
		<div id="loading-overlay"></div>
		<div id="loading-box">
			<div class="loading-text">Loading</div>
			<div class="meter animate" style="width:86%; margin: 34px 0px 0px 7%;"><span style="width:100%"></span></div>
		</div>
	</div>
	
	<table id="WooZoneLight-layout-table" border="0" width="100%" cellspacing="0" cellpadding="15">
		<tr>
			<td class="col1" width="200" style="vertical-align: top">
				<ul class="WooZoneLight-categories-list">
					<li class="on"><a href="#All" data-categ="All" data-nodeid="All"><?php _e('All', $this->the_plugin->localizationName);?></a></li>
					<?php 
					$categs = $this->the_plugin->amzHelper->getAmazonCategs();
					if( count($categs) > 0 ){
						foreach ($categs as $key => $value){
					?>
							<li><a href="#<?php echo $key;?>" data-categ="<?php echo $key;?>" data-nodeid="<?php echo $value;?>"><?php echo preg_replace('/([A-Z])/', ' $1', $key);?></a></li>
					<?php
						}	
					}
					?>
					
				</ul>
			</td>
			<td class="col2" width="500"  style="vertical-align: top">
				<div class="WooZoneLight-parameters-list" id="WooZoneLight-parameters-container"> <p>loading ...</p></div>
			</td>
			<td class="col3" style="vertical-align: top">
				<div class="WooZoneLight-product-list"><!-- dinamyc content here --></div>
			</td>
		</tr>
	</table>
</div>
<?php
       		}
        }
    }
}
// Initalize the your WooZoneLightAdvancedSearch
$WooZoneLightAdvancedSearch = new WooZoneLightAdvancedSearch($this->cfg, $module);