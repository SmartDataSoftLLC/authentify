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

		$this->remove_token();
		$this->delete_user();
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
					$this->delete_user();
				} else {
					$res = $data;
				}
			}
		} else {
			$res = 'The request is not from Shopify';
		}
		error_log('Response: '. $res , 3, __DIR__); //check error.log to see the result
		
  	}

	private function verify_webhook($data, $hmac_header){
		$secret_key = '';
		$calculated_hmac = base64_encode(hash_hmac('sha256', $data, $secret_key, true));
		return hash_equals($hmac_header, $calculated_hmac);
	}

	private function delete_user(){
		$shop = $_GET['ushop'];
		$user_unique = str_replace('.myshopify', '@myshopify', $shop);
		$sql = "DELETE FROM {$this->prefix}users WHERE user_login='$user_unique'";
		$this->execute($sql);
	}

	private function remove_token(){
		$shop = $_GET['ushop'];
		$app = $_GET['app'];
		$date = date('Y-m-d');
		$tables = "`{$this->prefix}authentify_apps` AS aa  JOIN `{$this->prefix}authentify_tokens` as at ON aa.auth_app_id = at.auth_app_id  JOIN `{$this->prefix}authentify_shops` as ah ON ah.auth_shop_id = at.auth_shop_id";

		$update_sql = "UPDATE $tables
			SET at.token = '0', at.expired = '$date'
			WHERE ah.shop = '$shop' AND aa.app_unique_id = $app";
		$this->execute($update_sql);
	}

}

new Authentify_Uninstaller();