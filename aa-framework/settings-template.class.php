<?php
/*
* Define class aaInterfaceTemplates
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
! defined( 'ABSPATH' ) and exit;

if(class_exists('aaInterfaceTemplates') != true) {

	class aaInterfaceTemplates {

		/*
		* Some required plugin information
		*/
		const VERSION = '1.0';
		
		/*
		* Store some helpers config
		* 
		*/
		public $cfg	= array();

		/*
		* Required __construct() function that initalizes the AA-Team Framework
		*/
		public function __construct($cfg) 
		{
			$this->cfg = $cfg;   
		}
		
		
		/*
		* bildThePage, method
		* -------------------
		*
		* @params $options = array (requiered)
		* @params $alias = string (requiered)
		* this will create you interface via options array elements
		*/
		public function bildThePage ( $options = array(), $alias='', $module=array(), $showForm=true ) 
		{
			global $WooZoneLight;
			
			// reset as array, this will stock all the html content, and at the end return it
			$html = array();
  
			if(count($options) == 0) {
				return 'Please fill whit some options content first!';
			}
			
			$noRowElements = array('message', 'html', 'app');
			
			foreach ( $options as $theBoxs ) {
				
				// loop the all the boxs
				foreach ( $theBoxs as $box_id => $box ){
					
					$box_id = $alias . "_" . $box_id;
					$settings = array();
					
					// get the values from DB
					$dbValues = get_option($box_id);
					
					// check if isset and string have content
					if(isset($dbValues) && trim($dbValues) != ""){
						$settings = unserialize($dbValues);
					}
					
					// create defalt setup for each header, prevent php notices
					if(!isset($box['header'])) $box['header']= false;
					if(!isset($box['toggler'])) $box['toggler']= false;
					if(!isset($box['buttons'])) $box['buttons']= false;
					if(!isset($box['premium'])) $box['premium']= false;
					if(!isset($box['style'])) $box['style']= 'panel';
					
					$box_show_wrappers = true;
					if ( !isset($box['panel_setup_verification']) )
						$box['panel_setup_verification'] = false;
					
					if ( $box['panel_setup_verification'] ) {

						$tryLoadInterface = str_replace("{plugin_folder_path}", $module["folder_path"], $box['elements'][0]['path']);
									
						if(is_file($tryLoadInterface)) {
							// Turn on output buffering
							ob_start();
										
							require( $tryLoadInterface  );
  
							if ( isset($__module_is_setup_valid) && $__module_is_setup_valid !==true ) {
								$box_show_wrappers = false;
							}
									
							//copy current buffer contents into $message variable and delete current output buffer
							$__error_msg_panel = ob_get_clean();
						}
					}
  
					if ( $box_show_wrappers ) {
					// container setup
					$html[] = '<div class="WooZoneLight-' . ( $box['size'] ) . '">
                        	<div class="WooZoneLight-' . ( $box['style'] ) . '">';
							
					// hide panel header only if it's requested
					if( $box['header'] == true ) {
						$html[] = '<div class="WooZoneLight-panel-header">
							<span class="WooZoneLight-panel-title">
								' . ( isset($box['icon']) ? '<img src="' . ( $box['icon'] ) . '" />' : '' ) . '
								' . ( $box['title'] ) . '
							</span>
							 ' . ( $box['toggler'] == true ? '<span class="WooZoneLight-panel-toggler"></span>' : '' ) . '
						</div>';
					}
						
					$html[] = '<div class="WooZoneLight-panel-content">';
					if($showForm){
						$html[] = '<form class="WooZoneLight-form" id="' . ( $box_id ) . '" action="#save_with_ajax">';
					}
					
					// create a hidden input for sending the prefix
					$html[] = '<input type="hidden" id="box_id" name="box_id" value="' . ( $box_id ) . '" />';
					
					$html[] = '<input type="hidden" id="box_nonce" name="box_nonce" value="' . ( wp_create_nonce( $box_id . '-nonce') ) . '" />';
					} // end if show box wrappers

					$html[] = $this->tabsHeader($box); // tabs html header

					// loop the box elements
					if(count($box['elements']) > 0){
					
						// loop the box elements now
						foreach ( $box['elements'] as $elm_id => $value ){
							
							if( isset($value['premium']) && $value['premium'] == true ){
								continue;
							}
						
							// some helpers. Reset an each loop, prevent collision
							$val = '';
							$select_value = '';
							$checked = '';
							$option_name = isset($option_name) ? $option_name : '';
							
							// Set default value to $val
							if ( isset( $value['std']) ) {
								$val = $value['std'];
							}
							
							// If the option is already saved, ovveride $val
							if ( ( $value['type'] != 'info' ) ) {
								if ( isset($settings[($elm_id)] ) ) {
										$val = $settings[( $elm_id )];
										
										// Striping slashes of non-array options
										if ( !is_array($val) ) {
											$val = stripslashes( $val );
											if($val == '') $val = true;
										}
								}
							}
							
							// If there is a description save it for labels
							$explain_value = '';
							if ( isset( $value['desc'] ) ) {
								$explain_value = $value['desc'];
							}
							
							if(!in_array( $value['type'], $noRowElements)){
								// the row and the label 
								$html[] = '<div class="WooZoneLight-form-row' . ($this->tabsElements($box, $elm_id)) . '" style="position: relative;">';
								
								if( isset($value['premium']) && $value['premium'] == true ){
									//$html[] = $WooZoneLight->premium_message('row-small');
								}
								$html[] = 	'<label for="' . ( $elm_id ) . '">' . ( isset($value['title']) ? $value['title'] : '' ) . '</label>
									   <div class="WooZoneLight-form-item'. ( isset($value['size']) ? " " . $value['size'] : '' ) .'">';
							}
							
							// the element description
							if(isset($value['desc'])) $html[]	= '<span class="formNote">' . ( $value['desc'] ) . '</span>';
							
							switch ( $value['type'] ) {
								
								// Basic text input
								case 'text':
									$html[] = '<input ' . ( isset($value['force_width']) ? "style='width:" . ( $value['force_width'] ) . "px;'" : '' ) . ' id="' . esc_attr( $elm_id ) . '" name="' . esc_attr( $option_name . $elm_id ) . '" type="text" value="' . esc_attr( $val ) . '" />';
								break;
								
								// Basic checkbox input
								case 'checkbox':
									$html[] = '<input ' . ( isset($value['force_width']) ? "style='width:" . ( $value['force_width'] ) . "px;'" : '' ) . ' ' . ( $val == true ? 'checked' : '' ). ' id="' . esc_attr( $elm_id ) . '" name="' . esc_attr( $option_name . $elm_id ) . '" type="checkbox" value="" />';
								break;
								
								// Basic upload_image
								case 'upload_image':
									$html[] = '<table border="0">';
									$html[] = '<tr>';
									$html[] = 	'<td>';
									$html[] = 		'<input class="upload-input-text" name="' . ( $elm_id ) . '" id="' . ( $elm_id ) . '_upload" type="text" value="' . ( $val ) . '" />';
									
									$html[] = 		'<script type="text/javascript">
										jQuery("#' . ( $elm_id ) . '_upload").data({
											"w": ' . ( $value['thumbSize']['w'] ) . ',
											"h": ' . ( $value['thumbSize']['h'] ) . ',
											"zc": ' . ( $value['thumbSize']['zc'] ) . '
										});
									</script>';
									
									$html[] = 	'</td>';
									$html[] = '<td>';
									$html[] = 		'<a href="#" class="button upload_button" id="' . ( $elm_id ) . '">' . ( $value['value'] ) . '</a> ';
									$html[] = 		'<a href="#" class="button reset_button ' . $hide . '" id="reset_' . ( $elm_id ) . '" title="' . ( $elm_id ) . '">Remove</a> ';
									$html[] = '</td>';
									$html[] = '</tr>';
									$html[] = '</table>';
									
									$html[] = '<a class="thickbox" id="uploaded_image_' . ( $elm_id ) . '" href="' . ( $val ) . '" target="_blank">';
									
									if(!empty($val)){
										$imgSrc = $WooZoneLight->image_resize( $val, $value['thumbSize']['w'], $value['thumbSize']['h'], $value['thumbSize']['zc'] );
										$html[] = '<img style="border: 1px solid #dadada;" id="image_' . ( $elm_id ) . '" src="' . ( $imgSrc ) . '" />';
									}
									$html[] = '</a>';
									
								break;
								
								// Basic textarea
								case 'textarea':
									$cols = "120";
									if(isset($value['cols'])) {
										$cols = $value['cols'];
									}
									$height = "style='height:120px;'";
									if(isset($value['height'])) {
										$height = "style='height:{$value['height']};'";
									}
									
									$html[] = '<textarea id="' . esc_attr( $elm_id ) . '" ' . $height . ' cols="' . ( $cols ) . '" name="' . esc_attr( $option_name . $elm_id ) . '">' . esc_attr( $val ) . '</textarea>';
								break;
								
								// Basic html/text message
								case 'message':
									$html[] = '<div class="WooZoneLight-message WooZoneLight-' . ( $value['status'] ) . ' ' . ($this->tabsElements($box, $elm_id)) . '">' . ( $value['html'] ) . '</div>';
								break;
								
								// buttons
								case 'buttons':
								
									// buttons for each box
									
									if(count($value['options']) > 0){
										foreach ($value['options'] as $key => $value){
											$html[] = '<input 
												type="' . ( $value['type'] ) . '" 
												style="width:' . ( $value['width'] ) . '" 
												value="' . ( $value['value'] ) . '" 
												class="WooZoneLight-button ' . ( $value['color'] ) . ' ' . ( isset($value['pos']) ? $value['pos'] : '' ) . ' ' . ( $value['action'] ) . '" 
											/>';
										}
									}
									
								break;
								
								
								// Basic html/text message
								case 'html':
									$html[] = $value['html'];
								break;
								
								// Basic app, load the path of this file
								case 'app':
									
									$tryLoadInterface = str_replace("{plugin_folder_path}", $module["folder_path"], $value['path']);
									
									if(is_file($tryLoadInterface)) {
										// Turn on output buffering
										ob_start();
										
										require( $tryLoadInterface  );
										
										//copy current buffer contents into $message variable and delete current output buffer
										$html[] = ob_get_clean();
									}
								break;
								
								// Select Box
								case 'select':
									$html[] = '<select ' . ( isset($value['force_width']) ? "style='width:" . ( $value['force_width'] ) . "px;'" : '' ) . ' name="' . esc_attr( $elm_id ) . '" id="' . esc_attr( $elm_id ) . '">';
									
									foreach ($value['options'] as $key => $option ) {
										$selected = '';
										if( $val != '' ) {
											if ( $val == $key ) { $selected = ' selected="selected"';} 
										}
										$html[] = '<option'. $selected .' value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
									 } 
									$html[] = '</select>';
								break;
								
								// multiselect Box
								case 'multiselect':
									$html[] = '<select multiple="multiple" size="3" name="' . esc_attr( $elm_id ) . '[]" id="' . esc_attr( $elm_id ) . '">';
									
									if(count($option) > 1){
										foreach ($value['options'] as $key => $option ) {
											$selected = '';
											if( $val != '' ) {
												if ( in_array($key, $val) ) { $selected = ' selected="selected"';} 
											}
											$html[] = '<option'. $selected .' value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
										} 
									}
									$html[] = '</select>';
								break;
								
								// multiselect Box
								case 'multiselect_left2right':

									$available = array(); $selected = array();
									foreach ($value['options'] as $key => $option ) {
										if( $val != '' ) {
											if ( in_array($key, $val) ) { $selected[] = $key; } 
										}
									}
									$available = array_diff(array_keys($value['options']), $selected);
									
									$html[] = '<div class="WooZoneLight-multiselect-half WooZoneLight-multiselect-available" style="margin-right: 2%;">';
									if( isset($value['info']['left']) ){
										$html[] = '<h5>' . ( $value['info']['left'] ) . '</h5>';
									}
									$html[] = '<select multiple="multiple" size="' . (isset($value['rows_visible']) ? $value['rows_visible'] : 5) . '" name="' . esc_attr( $elm_id ) . '-available[]" id="' . esc_attr( $elm_id ) . '-available" class="multisel_l2r_available">';
									
									if(count($available) > 0){
										foreach ($value['options'] as $key => $option ) {
											if ( !in_array($key, $available) ) continue 1;
											$html[] = '<option value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
										} 
									}
									$html[] = '</select>';
									
									$html[] = '</div>';
									
									$html[] = '<div class="WooZoneLight-multiselect-half WooZoneLight-multiselect-selected">';
									if( isset($value['info']['right']) ){
										$html[] = '<h5>' . ( $value['info']['right'] ) . '</h5>';
									}
									$html[] = '<select multiple="multiple" size="' . (isset($value['rows_visible']) ? $value['rows_visible'] : 5) . '" name="' . esc_attr( $elm_id ) . '[]" id="' . esc_attr( $elm_id ) . '" class="multisel_l2r_selected">';
									
									if(count($selected) > 0){
										foreach ($value['options'] as $key => $option ) {
											if ( !in_array($key, $selected) ) continue 1;
											$isselected = ' selected="selected"'; 
											$html[] = '<option'. $isselected .' value="' . esc_attr( $key ) . '">' . esc_html( $option ) . '</option>';
										} 
									}
									$html[] = '</select>';
									$html[] = '</div>';
									$html[] = '<div style="clear:both"></div>';
									$html[] = '<div class="multisel_l2r_btn" style="">';
									$html[] = '<span style="display: inline-block; width: 24.1%; text-align: center;"><input id="' . esc_attr( $elm_id ) . '-moveright" type="button" value="Move Right" class="moveright WooZoneLight-button gray"></span>';
									$html[] = '<span style="display: inline-block; width: 24.1%; text-align: center;"><input id="' . esc_attr( $elm_id ) . '-moverightall" type="button" value="Move Right All" class="moverightall WooZoneLight-button gray"></span>';
									$html[] = '<span style="display: inline-block; width: 24.1%; text-align: center;"><input id="' . esc_attr( $elm_id ) . '-moveleft" type="button" value="Move Left" class="moveleft WooZoneLight-button gray"></span>';
									$html[] = '<span style="display: inline-block; width: 24.1%; text-align: center;"><input id="' . esc_attr( $elm_id ) . '-moveleftall" type="button" value="Move Left All" class="moveleftall WooZoneLight-button gray"></span>';
									$html[] = '</div>';
								break;
								
							}
							
							if(!in_array( $value['type'], $noRowElements)){
								// close: .WooZoneLight-form-row
								$html[] = '</div>';
								
								// close: .WooZoneLight-form-item
								$html[] = '</div>';
							}
							
						}
					}
					
					// WooZoneLight-message use for status message, default it's hidden
					$html[] = '<div class="WooZoneLight-message" id="WooZoneLight-status-box" style="display:none;"></div>';
					
					if( $box['premium'] == true ){
						$html[] = $WooZoneLight->premium_message('row-large');
					}
					
					if( $box['buttons'] == true && !is_array($box['buttons']) ) {
						// buttons for each box
						$html[] = '<div class="WooZoneLight-button-row">
							<input type="reset" value="Reset to default value" class="WooZoneLight-button gray left" />
							<input type="submit" value="Save the settings" class="WooZoneLight-button green WooZoneLight-saveOptions" />
						</div>';
					}
					elseif( is_array($box['buttons']) ){
						// buttons for each box
						$html[] = '<div class="WooZoneLight-button-row">';
						
						foreach ( $box['buttons'] as $key => $value ){
							$html[] = '<input type="submit" value="' . ( $value['value'] ) . '" class="WooZoneLight-button ' . ( $value['color'] ) . ' ' . ( $value['action'] ) . '" />';
						}
						
						$html[] = '</div>';
					}
					
					if ( $box_show_wrappers ) {
						
					if($showForm){
						// close: form
						$html[] = '</form>';
					}
					
					// close: .WooZoneLight-panel-content
					$html[] = '</div>';
					
					// close: box style  div (.WooZoneLight-panel)
					$html[] = '</div>';
					
					// close: box size div
					$html[] = '</div>';
					
					} // end if show box wrappers
				}
			}
			
			// return the $html
			return implode("\n", $html);
		}

		/*
		* printBaseInterface, method
		* --------------------------
		*
		* this will add the base DOM code for you options interface
		*/
		public function printBaseInterface( $pluginPage='' ) 
		{
?>
		<div id="WooZoneLight-wrapper" class="fluid wrapper-WooZoneLight">
    
			<!-- Header -->
			<?php
			// show the top menu
			WooZoneLightAdminMenu::getInstance()->show_menu( $pluginPage );
			?>
		
			<!-- Content -->
			<div id="WooZoneLight-content">
				
				<h1 class="WooZoneLight-section-headline">
				</h1>
				
				<!-- Container -->
				<div class="WooZoneLight-container clearfix">
				
					<!-- Main Content Wrapper -->
					<div id="WooZoneLight-content-wrap" class="clearfix">
					
						<!-- Content Area -->
						<div id="WooZoneLight-content-area">
							<!-- Content Area -->
							<div id="WooZoneLight-ajax-response"></div>
							
							<div class="clear"></div>
						</div>
					</div>
				</div>
			</div>
		</div>

<?php
		}
		
		//make Tabs!
		private function tabsHeader($box) {
			$html = array();

			// get tabs
			$__tabs = isset($box['tabs']) ? $box['tabs'] : array();

			$__ret = '';
			if (is_array($__tabs) && count($__tabs)>0) {
				$html[] = '<ul class="tabsHeader">';
				$html[] = '<li style="display:none;" id="tabsCurrent" title=""></li>'; //fake li with the current tab value!
				foreach ($__tabs as $tabClass=>$tabElements) {
					$html[] = '<li><a href="javascript:void(0);" title="'.$tabClass.'">'.$tabElements[0].'</a></li>';
				}
				$html[] = '</ul>';
				$__ret = implode('', $html);
				
			}
			return $__ret;
		}
		
		private function tabsElements($box, $elemKey) {
			// get tabs
			$__tabs = isset($box['tabs']) ? $box['tabs'] : array();

			$__ret = '';
			if (is_array($__tabs) && count($__tabs)>0) {
				foreach ($__tabs as $tabClass=>$tabElements) {

					$tabElements = $tabElements[1];
					$tabElements = trim($tabElements);
					$tabElements = array_map('trim', explode(',', $tabElements));
					if (in_array($elemKey, $tabElements)) 
						$__ret .= ($tabClass.' '); //support element on multiple tabs!
				}
			}
			return ' '.trim($__ret).' ';
		}
	}
}