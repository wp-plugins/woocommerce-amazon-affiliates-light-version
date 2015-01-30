<?php
/*
* Define class WooZoneLight_ActionAdminAjax
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
!defined('ABSPATH') and exit;
if (class_exists('WooZoneLight_ActionAdminAjax') != true) {
    class WooZoneLight_ActionAdminAjax
    {
        /*
        * Some required plugin information
        */
        const VERSION = '1.0';

        /*
        * Store some helpers config
        */
		public $the_plugin = null;
		public $amzHelper = null;

		static protected $_instance;
		
	
		/*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent )
        {
			$this->the_plugin = $parent;
    
			$this->amzHelper = $this->the_plugin->amzHelper;
  
			add_action('wp_ajax_WooZoneLight_AttributesCleanDuplicate', array( $this, 'attributes_clean_duplicate' ));
			add_action('wp_ajax_WooZoneLight_CategorySlugCleanDuplicate', array( $this, 'category_slug_clean_duplicate' ));
        }
        
		/**
	    * Singleton pattern
	    *
	    * @return Singleton instance
	    */
	    static public function getInstance()
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self;
	        }
	        
	        return self::$_instance;
	    }
	    
	    
	    /**
	     * Clean Duplicate Attributes
	     *
	     */
		public function attributes_clean_duplicate( $retType = 'die' ) {
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';

			$ret = array(
				'status'			=> 'invalid',
				'msg_html'			=> ''
			);

			if ($action != 'attr_clean_duplicate' ) die(json_encode($ret));

			return $this->amzHelper->attrclean_clean_all();
		}
		
	    /**
	     * Clean Duplicate Category Slug
	     *
	     */
		public function category_slug_clean_duplicate( $retType = 'die' ) {
			$action = isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '';

			$ret = array(
				'status'			=> 'invalid',
				'msg_html'			=> ''
			);

			if ($action != 'category_slug_clean_duplicate' ) die(json_encode($ret));

			return $this->amzHelper->category_slug_clean_all();
		}
    }
}

// Initialize the WooZoneLight_ActionAdminAjax class
//$WooZoneLight_ActionAdminAjax = new WooZoneLight_ActionAdminAjax();
