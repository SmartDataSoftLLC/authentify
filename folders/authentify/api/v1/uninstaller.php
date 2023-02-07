<?php
include("connect.php");
class Authentify_Uninstaller extends DatabaseClass{

	public  function __construct(){
		try {
			parent::__construct();
		} catch (Exception $e) {
			echo $e->getMessage();
			die();
		}
		$this->uninstall_app();
	}

  	private function uninstall_app(){
		$res = '';
		$hmac_header = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'];
		$topic_header = $_SERVER['HTTP_X_SHOPIFY_TOPIC'];
		$shop_header = $_SERVER['HTTP_X_SHOPIFY_SHOP_DOMAIN'];
		$data = file_get_contents('php://input');
		$decoded_data = json_decode($data, true);

		$verified = $this->verify_webhook($data, $hmac_header);

		if( $verified == true ) {
			if( $topic_header == 'app/uninstalled' || $topic_header == 'shop/update') {
				if( $topic_header == 'app/uninstalled' ) {
					$this->remove_token();
					// $this->delete_user();
				} else {
					$res = $data;
				}
			}
		} else {
			$res = 'The request is not from Shopify';
		}

		error_log('Response: '. $res); //check error.log to see the result
		
  	}

	private function verify_webhook($data, $hmac_header){
		$secret_key = '';
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
		return hash_equals($hmac_header, $calculated_hmac);
	}

	private function delete_user(){

		$shop = $_GET['ushop'];
		// $sql = "DELETE FROM {$this->prefix}users WHERE store_url='".$shop_header."' LIMIT 1";
	}

	private function remove_token(){
		$shop = $_GET['ushop'];
		$app = $_GET['app'];
		$date = date('Y-m-d');
		$tables = "`{$this->prefix}apps` AS aa RIGHT JOIN `{$this->prefix}authentify_tokens` as at ON aa.auth_app_id = at.auth_app_id LEFT JOIN `{$this->prefix}authentify_shops` as ah ON ah.auth_shop_id = at.auth_shop_id";
		$sql = "SELECT aa.`auth_app_id`, ah.auth_shop_id FROM $tables WHERE ah.shop = '$shop' AND aa.app_unique_id = $app";
		$result = $this->execute($sql, true);

		if(is_array($result) && !empty($result)){
			$update_sql = "UPDATE `{$this->prefix}authentify_tokens`
			SET token = '0', expired = '$date'
			WHERE auth_app_id = " . $result['auth_app_id'] . " AND auth_shop_id = " . $result['auth_shop_id'] . ";";
			$this->execute($update_sql);
		}
	}

}

new Authentify_Uninstaller();