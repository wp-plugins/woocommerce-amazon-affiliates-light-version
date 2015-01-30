<?php
/**
 * Config file, return as json_encode
 * http://www.aa-team.com
 * =======================
 *
 * @author		Andrei Dinca, AA-Team
 * @version		0.1 - in development mode
 */
 echo json_encode(
	array(
		'modules_manager' => array(
			'version' => '0.1',
			'menu' => array(
				'order' => 19,
				'title' => 'Modules manager',
				'icon' => 'assets/16_modules.png'
			),
			'in_dashboard' => array(
				'icon' 	=> 'assets/32_modulesmanager.png',
				'url'	=> admin_url("admin.php?page=WooZoneLight#!/modules_manager")
			),
			'help' => array(
				'type' => 'remote',
				'url' => 'http://docs.aa-team.com/woocommerce-amazon-affiliates/documentation/modules-manager/'
			),
			'description' => "In the modules manager module you can deactivate modules that you don’t want to use.",
			'load_in' => array(
				'backend' => array(
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