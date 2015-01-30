<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		1.0
 */
echo json_encode(
	array(
		'assets_download' => array(
			'version' => '1.0',
			'menu' => array(
				'order' => 4,
				'show_in_menu' => false,
				'title' => 'Assets download',
				'icon' => 'assets/16_assets.png'
			),
			'in_dashboard' => array(
				'icon' 	=> 'assets/32_assetsdwl.png',
				'url'	=> admin_url("admin.php?page=WooZoneLight_assets_download")
			),
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/assets-download/'
			),
			'description' => "Download assets for the products - this applies to all products, specially for products with lots of variations",
			'module_init' => 'init.php',
			'load_in' => array(
				'backend' => array(
					'admin.php?page=WooZoneLight_assets_download',
					'admin.php?page=WooZoneLight_asin_grabber',
					'admin-ajax.php'
				),
				'frontend' => false
			),
			'javascript' => array(
				'admin',
				'hashchange',
				'tipsy'
			),
			'css' => array(
				'admin',
				'tipsy'
			)
		)
	)
);