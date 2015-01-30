<?php
/**
 * Amazon Webservices Client Class
 * http://www.amazon.country
 * =========================
 *
 * @package			aaAmazonWS
 * @author			 AA-Team
 */
class WooZoneLightServerStatus
{
	/**
	 * Responseconfigurationstorage
	 *
	 * @var array
	 */
	private $config = array(
		'error_codes_file' => 'error_codes.txt',
	);
	
	/**
	 * @param string $accessKey
	 * @param string $secretKey
	 * @param string $country
	 * @param string $associateTag
	 */
	public function __construct()
	{
	}
	
	/**
	 * read the error codes file
	 */
	private function read_error_codes()
	{
		var_dump('<pre>',$this->cfg['error_codes_file'],'</pre>');  
	}
}