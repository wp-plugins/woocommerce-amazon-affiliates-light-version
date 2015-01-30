<?php 
/*
* Define class Modules Manager List
* Make sure you skip down to the end of this file, as there are a few
* lines of code that are very important.
*/
! defined( 'ABSPATH' ) and exit;

if(class_exists('aaModulesManger') != true) {

	class aaModulesManger {
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

		public function printListInterface ()
		{
			$html = array();
			
			$html[] = '<table class="WooZoneLight-table" id="' . ( $this->cfg['default']['alias'] ) . '-module-manager">';
			$html[] = '<thead>
						<tr>
							<th width="20">Icon</th>
							<th width="20">Version</th>
							<th width="240" align="left">Name</th>
							<th align="left">About</th>
						</tr>
					</thead>';
			
			$html[] = '<tbody>';
			$cc = 0;
			foreach ($this->cfg['modules'] as $key => $value){
				$icon = '';
				if(is_file($value["folder_path"] . $value[$key]['menu']['icon'])) {
					$icon = $value["folder_uri"] . $value[$key]['menu']['icon'];
				}

				$html[] = '<tr class="' . ( $cc % 2 ? 'odd' : 'even') . '">
					<td align="center">' . ( trim($icon) != "" ? '<img src="' . ( $icon ) . '" />' : '' ) . '</td>
					<td align="center">' . ( $value[$key]['version'] ) . '</td>
					<td>
						' . ( $value[$key]['menu']['title'] ) . ' <br />';
						
				// activate / deactivate plugin button
				if($value['status'] == true){
					if(!in_array($key, $this->cfg['core-modules'])) {
						$html[] = '<a href="#deactivate" class="deactivate" rel="' . ( $key ) . '">Deactivate</a>';
					}else{
						$html[] = "<i>Core Module, can't be deactivated!</i>";
					}
				}
				else{
					$html[] = '<a href="#activate" class="activate" rel="' . ( $key ) . '">Activate</a>';
				}
				
				$html[] = '</td>
					<td>' . ( $value[$key]['description'] ) . '</td>
				</tr>';
				
				$cc++;
			}
			
			$html[] = '</tbody>';
			$html[] = '</table>';
			
			return implode("\n", $html);
		}
	}
}

// Initalize the your aaModulesManger
$aaModulesManger = new aaModulesManger($this->cfg);