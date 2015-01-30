<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
global $WooZoneLight;
 echo json_encode(
	array(
		'advanced_search' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 4,
				'title' => 'Advanced Search',
				'icon' => 'assets/16_advancedsearch.png'
			),
			'in_dashboard' => array(
				'icon' 	=> 'assets/32_advsearch.png',
				'url'	=> admin_url("admin.php?page=WooZoneLight#!/advanced_search")
			),
			'description' => "Using this module you can bulk import multiple products at once on your store.",
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/advanced-search/'
			),
			'module_init' => 'ajax-request.php',
			'load_in' => array(
				'backend' => array(
					'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				'download_asset',
				'hashchange',
				'tipsy'
			),
			'css' => array(
				'admin',
				'tipsy'
			),
			'errors' => array(
				1 => __('
					You configured Advanced Search incorrectly. See 
					' . ( $WooZoneLight->convert_to_button ( array(
						'color' => 'white_blue WooZoneLight-show-docs-shortcut',
						'url' => 'javascript: void(0)',
						'title' => 'here'
					) ) ) . ' for more details on fixing it. <br />
					Setup the Amazon config mandatory settings ( Access Key ID, Secret Access Key, Main Affiliate ID ) 
					' . ( $WooZoneLight->convert_to_button ( array(
						'color' => 'white_blue',
						'url' => admin_url( 'admin.php?page=WooZoneLight#!/amazon' ),
						'title' => 'here'
					) ) ) . '
					', $WooZoneLight->localizationName),
				2 => __('
					You don\'t have WooCommerce installed/activated! Please activate it:
					' . ( $WooZoneLight->convert_to_button ( array(
						'color' => 'white_blue',
						'url' => admin_url('plugin-install.php?tab=search&s=woocommerce&plugin-search-input=Search+Plugins'),
						'title' => 'NOW'
					) ) ) . '
					', $WooZoneLight->localizationName),
				3 => __('
					You don\'t have neither the SOAP library or cURL library installed! Please activate it!
					', $WooZoneLight->localizationName),
				4 => __('
					You don\'t have the cURL library installed! Please activate it!
					', $WooZoneLight->localizationName)
			)
		)
	)
 );