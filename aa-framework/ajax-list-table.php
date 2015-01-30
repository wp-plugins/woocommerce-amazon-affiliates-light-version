<?php
/**
 * AA-Team - http://www.aa-team.com
 * ================================
 *
 * @package		WooZoneLightAjaxListTable
 * @author		Andrei Dinca
 * @version		1.0
 */
! defined( 'ABSPATH' ) and exit;

if(class_exists('WooZoneLightAjaxListTable') != true) {
	class WooZoneLightAjaxListTable {

		/*
        * Some required plugin information
        */
        const VERSION = '1.0';

		/*
        * Singleton pattern
        */
		static protected $_instance;

		/*
        * Store some helpers
        */
		public $the_plugin = null;

		/*
        * Store some default options
        */
		public $default_options = array(
			'id' => '', /* string, uniq list ID. Use for SESSION filtering / sorting actions */
			'debug_query' => false, /* default is false */
			'show_header' => true, /* boolean, true or false */
			'list_post_types' => 'all', /* array('post', 'pages' ... etc) or 'all' */
			'items_per_page' => 15, /* number. How many items per page */
			'post_statuses' => 'all',
			'search_box' => true, /* boolean, true or false */
			'show_statuses_filter' => true, /* boolean, true or false */
			'show_pagination' => true, /* boolean, true or false */
			'show_category_filter' => true, /* boolean, true or false */
			'show_parent_products' => true,
			'columns' => array(),
			'custom_table' => ''
		);
		private $items;
		private $items_nr;
		private $args;

		public $opt = array();

        /*
        * Required __construct() function that initalizes the AA-Team Framework
        */
        public function __construct( $parent )
        {
        	$this->the_plugin = $parent;
			add_action('wp_ajax_WooZoneLightAjaxList', array( $this, 'request' ));

			if(session_id() == '') {
			    // session isn't started
			    session_start();
			}
        }

		/**
	    * Singleton pattern
	    *
	    * @return class Singleton instance
	    */
	    static public function getInstance( $parent )
	    {
	        if (!self::$_instance) {
	            self::$_instance = new self($parent);
	        }

	        return self::$_instance;
	    }

		/**
	    * Setup
	    *
	    * @return class
	    */
		public function setup( $options=array() )
		{
			$this->opt = array_merge( $this->default_options, $options );

			//unset($_SESSION['WooZoneLightListTable']); // debug

			// check if set, if not, reset
			$_SESSION['WooZoneLightListTable'][$this->opt['id']] = $options;

			return $this;
		}

		/**
	    * Singleton pattern
	    *
	    * @return class Singleton instance
	    */
		public function request()
		{
			$request = array(
				'sub_action' 	=> isset($_REQUEST['sub_action']) ? $_REQUEST['sub_action'] : '',
				'ajax_id' 		=> isset($_REQUEST['ajax_id']) ? $_REQUEST['ajax_id'] : '',
				'params' 		=> isset($_REQUEST['params']) ? $_REQUEST['params'] : '',
			);
  
			if( $request['sub_action'] == 'post_per_page' ){
				$new_post_per_page = $request['params']['post_per_page'];

				if( $new_post_per_page == 'all' ){
					$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['posts_per_page'] = '-1';
				}
				elseif( (int)$new_post_per_page == 0 ){
					$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['posts_per_page'] = $this->opt['items_per_page'];
				}
				else{
					$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['posts_per_page'] = $new_post_per_page;
				}

				// reset the paged as well
				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}
  
			if( $request['sub_action'] == 'paged' ){
				$new_paged = $request['params']['paged'];
				if( $new_paged < 1 ){
					$new_paged = 1;
				}

				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['paged'] = $new_paged;
			}

			if( $request['sub_action'] == 'post_type' ){
				$new_post_type = $request['params']['post_type'];
				if( $new_post_type == "" ){
					$new_post_type = "";
				}

				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['post_type'] = $new_post_type;

				// reset the paged as well
				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}

			if( $request['sub_action'] == 'post_parent' ){
				$new_post_parent = $request['params']['post_parent'];
				if( $new_post_parent == "" ){
					$new_post_parent = "";
				}

				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['post_parent'] = $new_post_parent;

				// reset the paged as well
				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}

			if( $request['sub_action'] == 'post_status' ){
				$new_post_status = $request['params']['post_status'];
				if( $new_post_status == "all" ){
					$new_post_status = "";
				}

				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['post_status'] = $new_post_status;

				// reset the paged as well
				$_SESSION['WooZoneLightListTable'][$request['ajax_id']]['params']['paged'] = 1;
			}

			// create return html
			ob_start();

			$this->setup( $_SESSION['WooZoneLightListTable'][$request['ajax_id']] );
			$this->print_html();
			$html = ob_get_contents();
			ob_clean();

			$return = array(
				'status' 	=> 'valid',
				'html'		=> $html
			);
			
			die( json_encode( array_map(utf8_encode, $return) ) );
		}

		/**
	    * Helper function
	    *
	    * @return object
	    */
		public function get_items()
		{
			global $wpdb;

			$ses = isset($_SESSION['WooZoneLightListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLightListTable'][$this->opt['id']]['params'] : array();
			//var_dump('<pre>',$ses,'</pre>'); die;

			$this->args = array(
				'posts_per_page'  	=> ( isset($ses['posts_per_page']) ? $ses['posts_per_page'] : $this->opt['items_per_page'] ),
				'paged'				=> ( isset($ses['paged']) ? $ses['paged'] : 1 ),
				'category'        	=> ( isset($ses['category']) ? $ses['category'] : '' ),
				'orderby'         	=> 'post_date',
				'order'          	=> 'DESC',
				'post_type'       	=> ( isset($ses['post_type']) && trim($ses['post_type']) != "all" ? $ses['post_type'] : array_keys($this->get_list_postTypes()) ),
				'post_status'     	=> ( isset($ses['post_status']) ? $ses['post_status'] : '' ),
				'suppress_filters' 	=> true
			);
			
			if ( isset($ses['post_parent']) && trim($ses['post_parent']) != "all" ) {
				$this->args = array_merge($this->args, array(
					'post_parent'       	=> $ses['post_parent']
				));
			}

			// if custom table, make request in the custom table not in wp_posts
			if( trim($this->opt["custom_table"]) == "amz_products"){
				$pages = array();

			    // select all pages and post from DB
			    $myQuery = "SELECT * FROM " . $wpdb->prefix  . ( $this->opt["custom_table"] ) . " WHERE type='post' and status='new' AND 1=1 ";
				
			    $__limitClause = $this->args['posts_per_page']>0 ? " 1=1 limit " . (($this->args['paged'] - 1) * $this->args['posts_per_page']) . ", " . $this->args['posts_per_page'] : '1=1 ';
				$result_query = str_replace("1=1 ", $__limitClause, $myQuery);
				
			    $query = $wpdb->get_results( $result_query, ARRAY_A);
  
			    foreach ($query as $key => $myrow){
					$pages[$myrow['post_id']] = array(
						'post_id' => $myrow['post_id'],
						'post_parent' => $myrow['post_parent'],
						'type' => $myrow['type'],
						'title' => $myrow['title'],
						'nb_assets' => $myrow['nb_assets'],
						'nb_assets_done' => $myrow['nb_assets_done'],
					);
			    }

				if( $this->opt['debug_query'] == true ){
					echo '<script>console.log("' . $result_query . '");</script>';
				}

				$this->items = $pages;
				$this->items_nr = $wpdb->get_var( str_replace("*", "count(post_id) as nbRow", $myQuery) );
				
			}else{

				// remove empty array
				$this->args = array_filter($this->args);

				$this->items = get_posts( $this->args );

				// get all post count
				$nb_args = $this->args;
				$nb_args['posts_per_page'] = '-1';
				$nb_args['fields'] = 'ids';
				$this->items_nr = (int) count(get_posts( $nb_args ));

				if( $this->opt['debug_query'] == true ){
					$query = new WP_Query( $this->args );
					echo '<script>console.log("' . $query->request . '");</script>';
				}
			}

			return $this;
		}

		private function getAvailablePostStatus()
		{
			$ses = isset($_SESSION['WooZoneLightListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLightListTable'][$this->opt['id']]['params'] : array();

			$post_type = isset($ses['post_type']) && trim($ses['post_type']) != "" ? $ses['post_type'] : '';
			$post_type = trim( $post_type );
			$qClause = '';
			if ( $post_type!='' && $post_type!='all' )
				$qClause .= " AND post_type = '" . ( esc_sql($post_type) ) . "' ";
			else
				$qClause .= " AND post_type IN ( " . implode( ',', array_map( array($this->the_plugin, 'prepareForInList'), array_keys($this->get_list_postTypes()) ) ) . " ) ";
			
			$post_parent = isset($ses['post_parent']) && trim($ses['post_parent']) != "" ? $ses['post_parent'] : '';
			$post_parent = trim( $post_parent );
			//$qClause = ' AND post_parent > 0 ';
			if ( $post_parent!='' && $post_parent!='all' )
				$qClause .= " AND post_parent = '" . ( esc_sql($post_parent) ) . "' ";

			$sql = "SELECT count(id) as nbRow, post_status, post_type FROM " . ( $this->the_plugin->db->prefix ) . "posts WHERE 1 = 1 ".$qClause." group by post_status";
			$sql = preg_replace('~[\r\n]+~', "", $sql);

			return $this->the_plugin->db->get_results( $sql, ARRAY_A );
		}

		private function get_list_postTypes()
		{
			// overwrite wrong post-type value
			if( !isset($this->opt['list_post_types']) ) $this->opt['list_post_types'] = 'all';
			
			// custom array case
			if( is_array($this->opt['list_post_types']) && count($this->opt['list_post_types']) > 0 ) {
				//return $this->opt['list_post_types'];
				$__ = array();
				foreach ($this->opt['list_post_types'] as $key => $value) {
					$__[$value] = get_post_type_object( $value );
				} 
				return $__;
			}

			// all case
			//return get_post_types(array('show_ui' => TRUE, 'show_in_nav_menus' => TRUE), 'objects');
			$_builtin = get_post_types(array('show_ui' => TRUE, 'show_in_nav_menus' => TRUE, '_builtin' => TRUE), 'objects');
			if ( !is_array($_builtin) || count($_builtin)<0 )
				$_builtin = array();

			$_notBuiltin = get_post_types(array('show_ui' => TRUE, 'show_in_nav_menus' => TRUE, '_builtin' => FALSE), 'objects');
			if ( !is_array($_notBuiltin) || count($_notBuiltin)<0 )
				$_notBuiltin = array();
				
			$exclude = array();
			$ret = array_merge($_builtin, $_notBuiltin);
			if (!empty($exclude)) foreach ( $exclude as $exc) if ( isset($ret["$exc"]) ) unset($ret["$exc"]);
  
			return $ret;
		}

		private function get_list_parentProducts()
		{
			$ses = isset($_SESSION['WooZoneLightListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLightListTable'][$this->opt['id']]['params'] : array();
			
			$qClause = '';
			$qClause .= " AND a.post_status IN ('publish') ";

			$post_parent = isset($ses['post_parent']) && trim($ses['post_parent']) != "" ? $ses['post_parent'] : '';
			$post_parent = trim( $post_parent );
			$qClause .= ' AND a.post_parent > 0 ';
			//if ( $post_parent!='' && $post_parent!='all' )
			//	$qClause .= " AND a.post_parent = '" . ( esc_sql($post_parent) ) . "' ";
			
			$qClause .= " AND a.post_type IN ( " . implode( ',', array_map( array($this->the_plugin, 'prepareForInList'), array_keys($this->get_list_postTypes()) ) ) . " ) ";

			$table_posts = $this->the_plugin->db->prefix . "posts";
			//$sql = "SELECT count(id) as nbRow, post_parent FROM " . ( $this->the_plugin->db->prefix ) . "posts WHERE 1 = 1 ".$qClause." group by post_parent;";
			//$qClause = "AND a.post_status IN ('publish')  AND a.post_parent > 0  AND a.post_type IN ( 'product','product_variation' )";
			$sql = "SELECT COUNT(a.id) AS nbRow, a.post_parent as _ID, b.post_title as _title
 FROM $table_posts AS a RIGHT JOIN $table_posts AS b ON a.post_parent = b.ID
 WHERE 1=1
 AND ( !ISNULL(b.ID) AND b.ID > 0 AND b.post_status IN ('publish') )
 ".$qClause."
 GROUP BY a.post_parent
 ORDER BY _title ASC
;";
			$sql = preg_replace('~[\r\n]+~', "", $sql);
    
			$ret = array();
			$res = $this->the_plugin->db->get_results( $sql, ARRAY_A );
			if ( !empty($res) ) {
				foreach ( $res as $key => $val ) {
					$_id = $val['_ID'];
					$ret["$_id"] = $val;
					$ret["$_id"]['_title'] = $ret["$_id"]['_title'] . ' (' . $ret["$_id"]['nbRow'] . ')';
				}
  
				/*$args = array(
					'post_type' 	=> 'product',
					'post__in' 		=> (array) array_keys($ret)
				);
				$parentPosts = get_posts( $args );
  
				foreach ( $parentPosts as $key2 => $val2 ) {
					$_id = $val2->ID;
					$ret["$_id"]['_title'] = $val2->post_title . ' (' . $ret["$_id"]['nbRow'] . ')';
				}*/
			}
			return $ret;
		}

		private function get_pagination()
		{
			$html = array();

			$ses = isset($_SESSION['WooZoneLightListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLightListTable'][$this->opt['id']]['params'] : array();

			$posts_per_page = ( isset($ses['posts_per_page']) ? $ses['posts_per_page'] : $this->opt['items_per_page'] );
			$paged = ( isset($ses['paged']) ? $ses['paged'] : 1 );
			$total_pages = ceil( $this->items_nr / $posts_per_page );
			
			if( $this->opt['show_pagination'] ){
				$html[] = 	'<div class="WooZoneLight-list-table-right-col">';

				$html[] = 		'<div class="WooZoneLight-box-show-per-pages">';
				$html[] = 			'<select name="WooZoneLight-post-per-page" id="WooZoneLight-post-per-page" class="WooZoneLight-post-per-page">';


				foreach( range(5, 50, 5) as $nr => $val ){
					$html[] = 			'<option val="' . ( $val ) . '" ' . ( $posts_per_page == $val ? 'selected' : '' ). '>' . ( $val ) . '</option>';
				}
				foreach( range(100, 500, 100) as $nr => $val ){
					$html[] = 			'<option val="' . ( $val ) . '" ' . ( $posts_per_page == $val ? 'selected' : '' ). '>' . ( $val ) . '</option>';
				}

				$html[] = 				'<option value="all">';
				$html[] =				__('Show All', $this->the_plugin->localizationName);
				$html[] = 				'</option>';
				$html[] =			'</select>';
				$html[] = 			'<label for="WooZoneLight-post-per-page" style="width:57px">' . __('per pages', $this->the_plugin->localizationName) . '</label>';
				$html[] = 		'</div>';

				$html[] = 		'<div class="WooZoneLight-list-table-pagination tablenav">';

				$html[] = 			'<div class="tablenav-pages">';
				$html[] = 				'<span class="displaying-num">' . ( $this->items_nr ) . ' items</span>';
				if( $total_pages > 1 ){
					$html[] = 				'<span class="pagination-links"><a class="first-page ' . ( $paged <= 1 ? 'disabled' : '' ) . ' WooZoneLight-jump-page" title="Go to the first page" href="#paged=1">&laquo;</a>';
					$html[] = 				'<a class="prev-page ' . ( $paged <= 1 ? 'disabled' : '' ) . ' WooZoneLight-jump-page" title="Go to the previous page" href="#paged=' . ( $paged > 2 ? ($paged - 1) : '' ) . '">&lsaquo;</a>';
					$html[] = 				'<span class="paging-input"><input class="current-page" title="Current page" type="text" name="paged" value="' . ( $paged ) . '" size="2" style="width: 45px;"> of <span class="total-pages">' . ( ceil( $this->items_nr / $this->args['posts_per_page'] ) ) . '</span></span>';
					$html[] = 				'<a class="next-page ' . ( $paged >= ($total_pages - 1) ? 'disabled' : '' ) . ' WooZoneLight-jump-page" title="Go to the next page" href="#paged=' . ( $paged >= ($total_pages - 1) ? $total_pages : $paged + 1 ) . '">&rsaquo;</a>';
					$html[] = 				'<a class="last-page ' . ( $paged >=  ($total_pages - 1) ? 'disabled' : '' ) . ' WooZoneLight-jump-page" title="Go to the last page" href="#paged=' . ( $total_pages ) . '">&raquo;</a></span>';
				}
				$html[] = 			'</div>';
				$html[] = 		'</div>';

				$html[] = 	'</div>';
			}

			return implode("\n", $html);
		}

		public function print_header()
		{
			$html = array();
			$ses = isset($_SESSION['WooZoneLightListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLightListTable'][$this->opt['id']]['params'] : array();

			$post_type = isset($ses['post_type']) && trim($ses['post_type']) != "" ? $ses['post_type'] : '';
			$post_parent = isset($ses['post_parent']) && trim($ses['post_parent']) != "" ? $ses['post_parent'] : '';

			$html[] = '<div id="WooZoneLight-list-table-header">';

			if( trim($this->opt["custom_table"]) == ""){
				$list_postTypes = $this->get_list_postTypes();

				$html[] = '<div class="WooZoneLight-list-table-left-col">';
				$html[] = 		'<select name="WooZoneLight-filter-post_type" class="WooZoneLight-filter-post_type">';
				if( count($list_postTypes) >= 2 ){
					$html[] = 		'<option value="all" >';
					$html[] =			__('Show All', $this->the_plugin->localizationName);
					$html[] = 		'</option>';	
				}

	            foreach ( $list_postTypes as $name => $postType ){
					$html[] = 		'<option ' . ( $name == $post_type ? 'selected' : '' ) . ' value="' . ( $this->the_plugin->escape($name) ) . '">';
					$html[] = 			( is_object($postType) ? ucfirst($this->the_plugin->escape($name)) : ucfirst($name) );
					$html[] = 		'</option>';
	            }
				$html[] = 		'</select>';

				if( $this->opt['show_parent_products'] ){
					$list_parentProducts = $this->get_list_parentProducts();
					
					$html[] = 	'<select name="WooZoneLight-filter-post_parent" class="WooZoneLight-filter-post_parent">';
					$html[] = 		'<option value="all" >';
					$html[] =		__('Show All', $this->the_plugin->localizationName);
					$html[] = 		'</option>';

		            foreach ( $list_parentProducts as $id => $postParent ){
						$html[] = 		'<option ' . ( $id == $post_parent ? 'selected' : '' ) . ' value="' . ( $id ) . '">';
						$html[] = 			( $postParent['_title'] );
						$html[] = 		'</option>';
		            }

					$html[] =	'</select>';
				}
				
				if( $this->opt['show_statuses_filter'] ){
					$html[] = $this->post_statuses_filter();
				}
				$html[] = 		'</div>';

				if( $this->opt['search_box'] ){
					$html[] = 	'<div class="WooZoneLight-list-table-right-col">';
					$html[] = 		'<div class="WooZoneLight-list-table-search-box">';
					$html[] = 			'<input type="text" name="s" value="" >';
					$html[] = 			'<input type="button" name="" class="button" value="Search Posts">';
					$html[] = 		'</div>';
					$html[] = 	'</div>';
				}

				if( $this->opt['show_category_filter']  && 3==4 ){
					$html[] = '<div class="WooZoneLight-list-table-left-col" >';
					$html[] = 	'<select name="WooZoneLight-filter-post_type" class="WooZoneLight-filter-post_type">';
					$html[] = 		'<option value="all" >';
					$html[] =		__('Show All', $this->the_plugin->localizationName);
					$html[] = 		'</option>';
					$html[] =	'</select>';
					$html[] = '</div>';
				}
			}else{
				$html[] = '<div class="WooZoneLight-list-table-left-col">&nbsp;</div>';
			}

			$html[] = $this->get_pagination();

			$html[] = '</div>';

            echo implode("\n", $html);

			return $this;
		}

		public function print_main_table( $items=array() )
		{
			$html = array();

			if( $this->opt['id'] == 'WooZoneLightSyncMonitor' ) {
				$last_updated_product = (int)get_option( 'WooZoneLight_last_updated_product', true);
				if( $last_updated_product > 0 ){
					$last_sync_date = get_post_meta($last_updated_product, '_last_sync_date', true);
					
					$html[] = 	'<div class="WooZoneLight-last-updated-product WooZoneLight-message WooZoneLight-info">';
					$html[] =		__('The last product synchronized was:', $this->the_plugin->localizationName);
					$html[] =		'<strong>' . $last_updated_product . '</strong>. ';
					$html[] =		__('This was synchronized at:', $this->the_plugin->localizationName);
					$html[] =		'<i>' . ( $last_sync_date ) . '</i>';
					$html[] = 	'</div>';
				}
			}
 
			$html[] = '<div id="WooZoneLight-list-table-posts">';	
			$html[] = 	'<table class="WooZoneLight-table" id="' . ( $this->opt["id"] ) . '" style="border: none;border-bottom: 1px solid #f2f2f2;">';
			$html[] = 		'<thead>';
			$html[] = 			'<tr>';
			foreach ($this->opt['columns'] as $key => $value){
				if( $value['th'] == 'checkbox' ){
					$html[] = '<th class="checkbox-column" width="20"><input type="checkbox" id="WooZoneLight-item-check-all" checked></th>';
				}
				else{
					$html[] = '<th' . ( isset($value['width']) && (int)$value['width'] > 0 ? ' width="' . ( $value['width'] ) . '"' : '' ) . '' . ( isset($value['align']) && $value['align'] != "" ? ' align="' . ( $value['align'] ) . '"' : '' ) . '>' . ( $value['th'] ) . '</th>';
				}
			}

			$html[] = 			'</tr>';
			$html[] = 		'</thead>';

			$html[] = 		'<tbody>';
			
			if( trim($this->opt["custom_table"]) == "amz_products" && count($this->items) == 0 ){
				$html[] = '<td colspan="' . ( count($this->opt['columns']) ) . '" style="text-align:left">
					<div class="WooZoneLight-message WooZoneLight-success">Good news, all products assets has been downloaded successfully!</div>
				</td>';
			}
			
			foreach ($this->items as $post){
				$post_id = 0;
				if ( isset($post->ID) ) $post_id = $post->ID;
				else if ( isset($post['post_id']) ) $post_id = $post['post_id'];
				
				if ( $post_id > 0 )
					$item_data = array(
						'score' 	=> get_post_meta( $post_id, 'WooZoneLight_score', true )
					);

				$html[] = 			'<tr data-itemid="' . ( $post_id ) . '">';
				foreach ($this->opt['columns'] as $key => $value){

					$html[] = '<td style="'
						. ( isset($value['align']) && $value['align'] != "" ? 'text-align:' . ( $value['align'] ) . ';' : '' ) . ''
						. ( isset($value['valign']) && $value['valign'] != "" ? 'vertical-align:' . ( $value['valign'] ) . ';' : '' ) . ''
						. ( isset($value['css']) && count($value['css']) > 0 ? $this->print_css_as_style($value['css']) : '' ) . '">';

					if( $value['td'] == 'checkbox' ){
						$html[] = '<input type="checkbox" class="WooZoneLight-item-checkbox" name="WooZoneLight-item-checkbox-' . ( $post->ID ) . '" checked>';
					}
					elseif( $value['td'] == '%ID%' ){
						$html[] = ( $post->ID );
					}
					elseif( $value['td'] == '%parent_id%' ){
						$html[] = ( $post->post_parent );
					}
					elseif( $value['td'] == '%title%' ){
						$html[] = '<input type="hidden" id="WooZoneLight-item-title-' . ( $post->ID ) . '" value="' . ( str_replace('"', "'", $post->post_title) ) . '" />';
						$html[] = '<a href="' . ( sprintf( admin_url('post.php?post=%s&action=edit'), $post->ID)) . '">';
						$html[] = 	( $post->post_title . ( $post->post_status != 'publish' ? ' <span class="item-state">- ' . ucfirst($post->post_status) : '</span>') );
						$html[] = '</a>';
					}
					elseif( $value['td'] == '%button%' ){
						$value['option']['color'] = isset($value['option']['color']) ? $value['option']['color'] : 'gray';
						$html[] = 	'<input type="button" value="' . ( $value['option']['value'] ) . '" class="WooZoneLight-button ' . ( $value['option']['color'] ) . ' WooZoneLight-' . ( $value['option']['action'] ) . '">';
					}
					elseif( $value['td'] == '%date%' ){
						$html[] = '<i>' . ( $post->post_date ) . '</i>';
					}
					elseif( $value['td'] == '%thumb%' ){
						
						$html[] = get_the_post_thumbnail( $post->ID, array(50, 50) );
					}
					elseif( $value['td'] == '%date%' ){
						$html[] = '<i>' . ( $post->post_date ) . '</i>';
					}
					elseif( $value['td'] == '%hits%' ){
						$hits = (int) get_post_meta($post->ID, '_amzaff_hits', true);
						$html[] = '<i class="WooZoneLight-prod-stats-number">' . ( $hits ) . '</i>';
					}
					elseif( $value['td'] == '%added_to_cart%' ){
						$addtocart = (int) get_post_meta($post->ID, '_amzaff_addtocart', true);
						$html[] = '<i class="WooZoneLight-prod-stats-number">' . ( $addtocart ) . '</i>';
					}
					elseif( $value['td'] == '%redirected_to_amazon%' ){
						$amzaff_woo_product_tabs = (int) get_post_meta($post->ID, '_amzaff_redirect_to_amazon', true);
						$html[] = '<i class="WooZoneLight-prod-stats-number">' . ( $amzaff_woo_product_tabs ) . '</i>';
					}
					elseif( $value['td'] == '%bad_url%' ){
						$html[] = '<i>' . ( $post['url'] ) . '</i>';
					}
					elseif( $value['td'] == '%asin%' ){
						$asin = get_post_meta($post->ID, '_amzASIN', true);
						$html[] = '<strong>' . ( $asin ) . '</strong>';
					}
					elseif( $value['td'] == '%last_sync_date%' ){
						$last_sync_date = get_post_meta($post->ID, '_last_sync_date', true);
						$html[] = '<i class="WooZoneLight-data-last_sync_date">' . ( $last_sync_date ) . '</i>';
					}
					elseif( $value['td'] == '%price%' ){
						$html[] = '<div class="WooZoneLight-data-price">';
						
						$localID = $post->ID;
						
						$product_meta['product'] = array();
						$product_meta['product']['price_update_date'] = get_post_meta($localID, "_price_update_date", true);
						$product_meta['product']['sales_price'] = get_post_meta($localID, "_sale_price", true);
						$product_meta['product']['regular_price'] = get_post_meta($localID, "_regular_price", true);
						$product_meta['product']['price'] = get_post_meta($localID, "_price", true);
						
						if ( empty($product_meta['product']['sales_price']) && empty($product_meta['product']['regular_price']) ) {
							$product_meta['product']['variation_price'] = array('min' => get_post_meta($localID, "_min_variation_price", true), 'max' => get_post_meta($localID, "_max_variation_price", true));
						}

						if ( empty($product_meta['product']['sales_price']) && empty($product_meta['product']['regular_price']) ) {
							
							$html[] = 	'From price: ' . (isset($product_meta['product']['variation_price']['min']) && (float)$product_meta['product']['variation_price']['min'] > 0 ? '<strong id="_regular_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['variation_price']['min'] ) ) . '</strong>' : '&#8211;');
							$html[] = 	'<br />';
							$html[] = 	'To price: ' . (isset($product_meta['product']['variation_price']['max']) && (float)$product_meta['product']['variation_price']['max'] > 0 ? '<strong id="_sales_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['variation_price']['max'] ) ) . '</strong>' : '&#8211;');
						} else {
							
							$html[] = 	'Regular price: ' . (isset($product_meta['product']['regular_price']) && (float)$product_meta['product']['regular_price'] > 0 ? '<strong id="_regular_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['regular_price'] ) ) . '</strong>' : '&#8211;');
							$html[] = 	'<br />';
							$html[] = 	'Sales price (offer): ' . (isset($product_meta['product']['sales_price']) && (float)$product_meta['product']['sales_price'] > 0 ? '<strong id="_sales_price-' . ( isset($product_meta['product']['asin']) ? $product_meta['product']['asin'] : '0' ) . '">' . ( woocommerce_price( $product_meta['product']['sales_price'] ) ) . '</strong>' : '&#8211;');
						}
						
						// &#8211; = unicode EN DASH
						$html[] = '</div>';
					}
					elseif( $value['td'] == '%last_date%' ){
						$html[] = '<i>' . ( $post['data'] ) . '</i>';
					}
					elseif( $value['td'] == '%preview%' ){
						$asin = get_post_meta($post->ID, '_amzASIN', true); 
						$html[] = "<div class='WooZoneLight-product-preview'>";
						$html[] = 	get_the_post_thumbnail( $post->ID, array(150, 150) );
						$html[] = 	"<div class='WooZoneLight-product-label'><strong>" . ( $post->post_title ) . "</strong></div>";
						$html[] = 	"<div class='WooZoneLight-product-label'>ASIN: <strong>" . ( $asin ) . "</strong></div>";
						$html[] = 	"<div class='WooZoneLight-product-label'>";
						$html[] = 		'<a href="' . ( get_permalink( $post->ID ) ) . '" class="WooZoneLight-button gray">' . __('View product', $this->the_plugin->localizationName) . '</a>';
						$html[] = 		'<a href="' . ( admin_url( 'post.php?post=' . ( $post->ID ) . '&action=edit' ) ) . '" class="WooZoneLight-button blue">' . __('Edit product', $this->the_plugin->localizationName) . '</a>';
						$html[] = 	"</div>";
						$html[] = "</div>";
					}
					
					elseif( $value['td'] == '%spinn_content%' ){
						
						// first check if you have the original content saved into DB
						$post_content = get_post_meta( $post->ID, 'WooZoneLight_old_content', true );
						
						// if not, retrive from DB
						if( $post_content == false ){
							$live_post = get_post( $post->ID, ARRAY_A );
							$post_content = $live_post['post_content'];
						}
						
						$post_content = htmlentities( wpautop( $post_content ) );
						
						$finded_replacements = get_post_meta( $post->ID, 'WooZoneLight_finded_replacements', true );
						if( $finded_replacements && count($finded_replacements) > 0 ){
							
							foreach ($finded_replacements as $word) {
								$post_content = str_replace($word, "<span class='WooZoneLight-word-" . ( sanitize_title($word) ) . "'>" . ( $word ) . "</span>", $post_content);
							}
						}
						$reorder_content = get_post_meta( $post->ID, 'WooZoneLight_reorder_content', true );
						
						$html[] = "<div class='WooZoneLight-spinn-container'>";
						$html[] = "<table class='WooZoneLight-spinn-content'>";
						$html[] = 	"<tr>";
						$html[] = 		"<td width='49%' class='WooZoneLight-spinn-border-right'>";
						$html[] = 			"<h2>" . ( __('Fresh (spin) Content', $this->the_plugin->localizationName) ) . "</h2>";
						$html[] = 		"</td>";
						$html[] = 		"<td>";
						$html[] =			"<h2>" . ( __('Old (original) Content', $this->the_plugin->localizationName) ) . "</h2>";
						$html[] = 		"</td>";
						$html[] = 	"</tr>";
						$html[] = 	"<tr>";
						$html[] = 		"<td width='49%' class='WooZoneLight-spinn-border-right'>";
						$html[] = 		"<div class='WooZoneLight-spin-editor-container'>";
						$html[] = 			"<div id='WooZoneLight-spin-editor-" . ( $post->ID ) . "' class='WooZoneLight-spin-content-editor WooZoneLight-spinner-container'>";
						$html[] = 			htmlentities( wpautop( $reorder_content ), ENT_QUOTES, "UTF-8" );
						$html[] = 			"</div>";
						
						if( trim($reorder_content) != "" ){
							$html[] = 			"<script>WooZoneLightContentSpinner.spin_order_interface( jQuery('#WooZoneLight-spin-editor-" . ( $post->ID ) . "') );</script>";
						}
						
						$html[] = 			"<div class='WooZoneLight-spin-replacement-box'>";
						$html[] = 				"<a href='#' class='close'>&times;</a>";
						$html[] = 				"<div class='WooZoneLight-spin-box-suggest'>
													<ul class='WooZoneLight-spin-box-suggest-select'></ul>
												</div>
												
												<div class='WooZoneLight-spin-box-suggest-options'>
													<a href='#prev' class='WooZoneLight-button gray WooZoneLight-skip-to-prev'> < prev snip word </a>
													<a href='#next' class='WooZoneLight-button gray WooZoneLight-skip-to-next'> next spin word > </a>
												</div>
						";
						$html[] = 			"</div>";
						
						$html[] = 			"<div class='WooZoneLight-spin-options'>";
						$html[] = 				'<a href="#" class="WooZoneLight-button green WooZoneLight-spin-content-btn" data-prodid="' . ( $post->ID ) . '">' . __('SPIN Content now!', $this->the_plugin->localizationName) . '</a>';
						$html[] = 				'
							<select class="WooZoneLight-spin-replacements" name="WooZoneLight-spin-replacements">
								<option value="10">10 replacements</option>
								<option value="30">30 replacements</option>
								<option value="60">60 replacements</option>
								<option value="80">80 replacements</option>
								<option value="100">100 replacements</option>
								<option value="0">All possible replacements</option>
							</select>
						';
						$html[] = 			"</div>";
						$html[] = 		"</div>";
						$html[] = 		"</td>";
						$html[] = 		"<td>";
						$html[] = 			"<div class='WooZoneLight-spin-content-editor WooZoneLight-spin-original-content'>";
						$html[] = 			$post_content;
						$html[] = 			"</div>";
						$html[] = 			"<div class='WooZoneLight-spin-options'>";
						$html[] = 				'<a href="#" class="WooZoneLight-button blue WooZoneLight-save-content-btn" data-prodid="' . ( $post->ID ) . '">' . __('SAVE Content', $this->the_plugin->localizationName) . '</a><a href="#" class="WooZoneLight-button blue WooZoneLight-rollback-content-btn" data-prodid="' . ( $post->ID ) . '" style="margin-left: 5px;">' . __('Rollback Content', $this->the_plugin->localizationName) . '</a>';
						$html[] =			"</div>";
						$html[] = 		"</td>";
						$html[] = 	"</tr>";
						$html[] = "</table>";
						$html[] = "</div>";
					}
					
					if( trim($this->opt["custom_table"]) == "amz_products"){
						if( $value['td'] == '%post_id%' ){
							$html[] = '<span class="WooZoneLight-post_id">' . ( $post['post_id'] ) . '</span>';
						}
						elseif( $value['td'] == '%del_asset%' ) {
							$html[] = '<input type="checkbox" name="delete_asset" value="' . ( $post['post_id'] ) . '">';
						}
						elseif( $value['td'] == '%post_assets%' ){
							
							$in_ids = array();
							$in_ids[] = $post['post_id']; // add curent post into in array
							
							$nb_assets = array('total' => 0, 'done' => 0);
							$nb_assets['total'] = $post['nb_assets'];
							$nb_assets['done'] = $post['nb_assets_done'];
							
							// get variations 
							$variations = $this->the_plugin->db->get_results( "SELECT * FROM " . $this->the_plugin->db->prefix  . ( $this->opt["custom_table"] ) . " WHERE 1=1 AND post_parent='" . ( $post['post_id'] ) . "' AND type='variation'", ARRAY_A);
							if( $variations && count( $variations ) > 0 ){
								foreach ($variations as $_the_post ) {
									$in_ids[] = $_the_post['post_id'];
									$nb_assets['total'] += (int) $_the_post['nb_assets'];
									$nb_assets['done'] += (int) $_the_post['nb_assets_done'];
								}
							}
							
							// get the assets 
							$assets = $this->the_plugin->db->get_results( "SELECT * FROM " . $this->the_plugin->db->prefix . "amz_assets WHERE 1=1 AND post_id IN (" . ( implode(",", $in_ids) ) . ")", ARRAY_A);
							//var_dump('<pre>',$assets, $this->the_plugin->db,'</pre>'); die;  
 
							$html[] = '<table class="WooZoneLight-table assets-download-list" data-itemid="' . ($post_id) . '">';
							$html[] = 	'<tr>';
							$html[] = 		'<td width="540" style="vertical-align: top;height: 180px;">';
							$html[] = 			'<div class="WooZoneLight-post-title">';
							$html[] = 				'<h3 title="' . ( $post['title'] ) . '">' . ( $post['title'] ) . '</h3>';
							$html[] = 				'<table class="WooZoneLight-post-info">';
							$html[] = 					'<tr>';
							$html[] = 						'<td>' . __('Number of variation:', $this->the_plugin->localizationName) . '</td>';
							$html[] = 						'<td>' . count( $variations ) . '</td>';
							$html[] = 					'</tr>';
							$html[] = 					'<tr>';
							$html[] = 						'<td>' . __('Assets:', $this->the_plugin->localizationName) . '</td>';
							$html[] = 						'<td>' . $nb_assets['total'] . ' (' . __('new', $this->the_plugin->localizationName) . ') | ' . $nb_assets['done'] . ' (' . __('done', $this->the_plugin->localizationName) . ')</td>';
							$html[] = 					'</tr>';
							/*
							$html[] = 					'<tr>';
							$html[] = 						'<td>Product status:</td>';
							$html[] = 						'<td>' . ( get_post_field( 'post_status', $post['post_id'] ) ) . '</td>';
							$html[] = 					'</tr>';
							*/
							$html[] = 					'<tr>';
							$html[] = 						'<td colspan="2">';
							$html[] = 							'<a href="#" class="WooZoneLight-button green WooZoneLight-download-assets-btn" data-prodid="' . ( $post['post_id'] ) . '">' . __('Download assets NOW!', $this->the_plugin->localizationName) . '</a>';
							$html[] = 							'<a href="' . ( admin_url('post.php?post=' . ( $post['post_id'] ) . '&action=edit') ) . '" class="WooZoneLight-button blue">' . __('Edit product', $this->the_plugin->localizationName) . '</a>';
							$html[] = 							'<a href="' . ( get_permalink( $post['post_id']) ) . '" class="WooZoneLight-button gray">' . __('View product', $this->the_plugin->localizationName) . '</a>';
							$html[] = 						'</td>';
							$html[] = 					'</tr>';
							$html[] = 				'</table>';
							$html[] = 			'</div>';
							$html[] = 		'</td>';
							$html[] = 		'<td>';
							
							
							// the post assets
							$html[] = 			'<div class="WooZoneLight-post-asset">';
							$html[] = 				'<div class="WooZoneLight-post-asset-left">';
							// loop the assets
							if( $assets && count($assets) > 0 ){
								foreach ($assets as $asset) {
									
									if( $post['post_id'] == $asset['post_id'] ){  
										$html[] = 	'<div class="WooZoneLight-post-asset-preview">';
										$html[] = 		'<img src="' . ( $asset['thumb'] ) . '">';
										$html[] = 	'</div>';
									}
								}
							}
							
							$html[] = 				'</div>';
							$html[] = 			'</div>';
							
							
							// the variatios assets
							if( $variations && count( $variations ) > 0 ){
								
								$html[] = 	'<a href="#" class="WooZoneLight-show-variations">Show <em>(' . ( count( $variations ) ). ')</em> variations</a>';
								$html[] = 	'<div class="WooZoneLight-variations-list">';
								
								$html[] = 			'<div class="WooZoneLight-post-asset">';
								$html[] = 				'<h4><strong>' . __('Variations:', $this->the_plugin->localizationName) . '</strong></h4>';
								$html[] = 					'<div class="WooZoneLight-post-asset-left">';
								foreach ($variations as $variation) {
								
									// loop the assets
									if( $assets && count($assets) > 0 ){
										foreach ($assets as $asset) {
											
											if( $variation['post_id'] == $asset['post_id'] ){  
												$html[] = 	'<div class="WooZoneLight-post-asset-preview">';
												$html[] = 		'<img src="' . ( $asset['thumb'] ) . '">';
												$html[] = 	'</div>';
											}
										}
									}
									
								}
								$html[] = 				'</div>';
								$html[] = 			'</div>';
								
								$html[] = 	'</div>';
							}
							
							$html[] = 		'</td>';

							$html[] = 	'</tr>';
							$html[] = '</table>';  
						}
						
					}

					$html[] = '</td>';
				}

				$html[] = 			'</tr>';
			}

			$html[] = 		'</tbody>';

			$html[] = 	'';

			$html[] = 	'</table>';

			if( trim($this->opt["custom_table"]) == ""){

				if( isset($this->opt['mass_actions']) && count($this->opt['mass_actions']) > 0 ){
					$html[] = '<div class="WooZoneLight-list-table-left-col" style="padding-top: 5px;">&nbsp;';

					foreach ($this->opt['mass_actions'] as $key => $value){
						$html[] = 	'<input type="button" value="' . ( $value['value'] ) . '" id="WooZoneLight-' . ( $value['action'] ) . '" class="WooZoneLight-button ' . ( $value['color'] ) . '">';
					}
					$html[] = '</div>';
				}else{
					
					$html[] = '<div class="WooZoneLight-list-table-left-col" style="padding-top: 5px;">&nbsp;';
					
					/*$html[] = 	'<input type="button" value="Auto detect focus keyword for All" id="WooZoneLight-all-auto-detect-kw" class="WooZoneLight-button blue">';
					$html[] = 	'<input type="button" value="Optimize All" id="WooZoneLight-all-optimize" class="WooZoneLight-button blue">';*/
					$html[] = '</div>';
				}
			}
			else{
				$html[] = '<div class="WooZoneLight-list-table-left-col" style="padding-top: 5px;">&nbsp;';
				if( trim($this->opt["custom_table"]) == "amz_products"){
					$html[] = '<a class="WooZoneLight-button orange WooZoneLight-download-all-assets-btn" href="#">Download ALL products assets NOW!</a>';
					$html[] = '<a class="WooZoneLight-button red WooZoneLight-delete-all-assets-btn" href="#">Delete selected products assets</a>';
				}
				$html[] = '</div>';
			}

			$html[] = $this->get_pagination();

			$html[] = '</div>';

            echo implode("\n", $html);

			return $this;
		}

		public function post_statuses_filter()
		{
			$html = array();

			$availablePostStatus = $this->getAvailablePostStatus();

			$ses = isset($_SESSION['WooZoneLightListTable'][$this->opt['id']]['params']) ? $_SESSION['WooZoneLightListTable'][$this->opt['id']]['params'] : array();

			$curr_post_status = isset($ses['post_status']) && trim($ses['post_status']) != "" ? $ses['post_status'] : 'all';

			if( $this->opt['post_statuses'] == 'all' ){
				$postStatuses = array(
				    'all'   	=> __('All', $this->the_plugin->localizationName),
				    'publish'   => __('Published', $this->the_plugin->localizationName),
				    'future'    => __('Scheduled', $this->the_plugin->localizationName),
				    'private'   => __('Private', $this->the_plugin->localizationName),
				    'pending'   => __('Pending Review', $this->the_plugin->localizationName),
				    'draft'     => __('Draft', $this->the_plugin->localizationName),
				);
			}
			else{
				$postStatuses = $this->opt['post_statuses'];
				//die('invalid value of <i>post_statuses</i>. Only implemented value is: <i>all</i>!');
			}

			$html[] = 		'<ul class="subsubsub WooZoneLight-post_status-list">';
			$cc = 0;
			// add into _postStatus array only if have equivalent into query results
			$_postStatus = array();
			$totals = 0;
			foreach ($availablePostStatus as $key => $value){
				if( in_array($value['post_status'], array_keys($postStatuses))){
					$_postStatus[$value['post_status']] = $value['nbRow'];
					$totals = $totals + $value['nbRow'];
				}
			}

			foreach ($postStatuses as $key => $value){
				$cc++;

				if( $key == 'all' || in_array($key, array_keys($_postStatus)) ){
					$html[] = 		'<li class="ocs_post_status">';
					$html[] = 			'<a href="#post_status=' . ( $key ) . '" class="' . ( $curr_post_status == $key ? 'current' : '' ) . '" data-post_status="' . ( $key ) . '">';
					$html[] = 				$value . ' <span class="count">(' . ( ( $key == 'all' ? $totals : $_postStatus[$key] ) ) . ')</span>';
					$html[] = 			'</a>' . ( count($_postStatus) > ($cc) ? ' |' : '');
					$html[] = 		'</li>';
				}
			}

			$html[] = 		'</ul>';

			return implode("\n", $html);
		}

		public function print_html()
		{
			$html = array();

			$this->get_items();
			$items = $this->items;
  
			$html[] = '<input type="hidden" class="WooZoneLight-ajax-list-table-id" value="' . ( $this->opt['id'] ) . '" />';

			// header
			if( $this->opt['show_header'] === true ) $this->print_header();

			// main table
			$this->print_main_table( $items );
   
			echo implode("\n", $html);
   
			return $this;
		}

		private function print_css_as_style( $css=array() )
		{
			$style_css = array();
			if( isset($css) && count($css) > 0 ){
				foreach ($css as $key => $value) {
					$style_css[] = $key . ": " . $value;
				}
			}

			return ( count($style_css) > 0 ? implode(";", $style_css) : '' );
		}

	}
}