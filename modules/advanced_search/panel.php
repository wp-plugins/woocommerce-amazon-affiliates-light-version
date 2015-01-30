<?php 
/*
* Define class bulkImport
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
! defined( 'ABSPATH' ) and exit;
// load the modules managers class
$module_class_path = $module['folder_path'] . 'advanced-search.class.php';
if(is_file($module_class_path)) {
	
	require_once( 'advanced-search.class.php' );
		
	// Initalize the your aaModulesManger
	$WooZoneLightAdvancedSearch = new WooZoneLightAdvancedSearch($this->cfg, $module);
	
	$__module_is_setup_valid = $WooZoneLightAdvancedSearch->moduleValidation();
	$__module_is_setup_valid = (bool) $__module_is_setup_valid['status'];
		
	// print the lists interface 
	echo $WooZoneLightAdvancedSearch->printSearchInterface();
}