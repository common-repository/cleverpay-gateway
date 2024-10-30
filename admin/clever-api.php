<?php 
	
	class Clever_Api  {
		static $api_url = [
			'sandbox' => 'https://ecom.cleverhub.co',
			'production' => 'https://ecom.helloclever.co',
			'endpoint' => [
				'SUCCESS_ORDER_TRACKING' => '/api/v1/event_hooks/receive_tracking_event',
				'TEST_CONNECT' => '/v1/ecom/connect',
				'CREATE_PAYMENT' => '/v1/ecom/create-payment',
				'CONFIRM_PAYMENT' => '/v1/ecom/confirm_payment',
				'CREATE_CHECKOUT_EXPRESS' => '/v1/express_checkouts/request_order',
				'CONFIRM_CHECKOUT_EXPRESS' => '/v1/express_checkouts/confirm_payment',
				'GET_CASHBACK_RATE' => '/v1/ecom/current_cashback',
				'GET_CASHBACK_RATE_V2' => '/v1/ecom/get_cashback',
				'MIGRATE_EXCLUDE_DATA' => '/v1/ecom/migrate_exclude_plugin',

 
			]
		];

		static function buildHeader($auth){

			return [
				'secret-key' => $auth['secret_key'],
				'app-id' => $auth['app_id'],
				'Plugin-Type' => 'woocommerce',
				'Plugin-Version'=> CLEVER_GATEWAY_VERSION 
			];
		}


		static function success_order_tracking($auth, $data){
			$jwt = new Clever_JWT();
			$response = wp_remote_post( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['SUCCESS_ORDER_TRACKING'], array(
				'timeout' => 25,
			    'body'    => $data,
			    'headers' => self::buildHeader($auth),
			) );

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function testConnect($auth){

			// self::migrateExcludeData($auth);


			$response = wp_remote_get( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['TEST_CONNECT'] ,
             array( 'timeout' => 25,'headers' => self::buildHeader($auth)));

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
			
		}

		static function createPayment($auth, $data){
			$jwt = new Clever_JWT();
			$data = ['info' => $jwt->encode($data, $auth['secret_key'])];

			$response = wp_remote_post( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['CREATE_PAYMENT'], array(
				'timeout' => 25,
			    'body'    => $data,
			    'headers' => self::buildHeader($auth),
			) );

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function createPaymentExpress($auth, $data){
			$jwt = new Clever_JWT();
			$data = ['info' => $jwt->encode($data, $auth['secret_key'])];
			$response = wp_remote_post( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['CREATE_CHECKOUT_EXPRESS'], array(
				'timeout' => 25,
			    'body'    => $data,
			    'headers' => self::buildHeader($auth),
			) );

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function confirmPayment($auth, $order_id){

			$response = wp_remote_get( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['CONFIRM_PAYMENT'] . '?transaction_id=' . $order_id , 
			array( 'timeout' => 25, 'headers' => self::buildHeader($auth)));

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function confirmPaymentExpress($auth, $order_id){

			$response = wp_remote_get( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['CONFIRM_CHECKOUT_EXPRESS'] . '?transaction_id=' . $order_id , 
			array( 'timeout' => 25, 'headers' => self::buildHeader($auth)));

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function getCashbackRate($auth){
			$response = wp_remote_get( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['GET_CASHBACK_RATE'] ,
             array( 'timeout' => 25, 'headers' => self::buildHeader($auth)));

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function getCashbackRateV2($auth, $data){
			$jwt = new Clever_JWT();
			$data = ['info' => $jwt->encode($data, $auth['secret_key'])];
			$response = wp_remote_post( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['GET_CASHBACK_RATE_V2'] ,
             array( 'timeout' => 25,
		    'body'    => $data,
            'headers' => self::buildHeader($auth)));

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}
		}

		static function auth(){

			$payments_settings = get_option('woocommerce_clever_gateway_settings');

			if(!$payments_settings)
				return;

			$environment = $payments_settings['api_environment'];
			$secret_key = 'secret_key';
			$app_id = 'app_id';
			$url = self::$api_url['production'];
			if($environment == 'sandbox'){
				$secret_key = 'sandbox_secret_key';
				$app_id = 'sandbox_app_id';
				$url = self::$api_url['sandbox'];

			}
			$secret_key = $payments_settings[$secret_key];
			$app_id = $payments_settings[$app_id];

			$auth =  [
				'success' => $secret_key && $app_id ? true : false, 
				'secret_key' => $secret_key,
				'app_id' => $app_id,
				'environment' => $environment,
				'url' => $url
			];
			return $auth;
		}

		static function migrateExcludeData(){

			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			$settings = $gateways['clever_gateway']->settings;
			$product_ids = $settings['cashback_exclude_category_val'];
			$categories_ids = $settings['cashback_exclude_product_val'];

			$data = [
				'product_ids' => trim($product_ids),
				'categories_ids' => trim($categories_ids)
			];

			$environment =$settings['api_environment'];
			$secret_key = 'secret_key';
			$app_id = 'app_id';
			if($environment == 'sandbox'){
				$secret_key = 'sandbox_secret_key';
				$app_id = 'sandbox_app_id';
			}
			$secret_key = $settings[$secret_key];
			$app_id = $settings[$app_id];

			$auth = [
						'success' => $secret_key && $app_id ? true : false, 
						'secret_key' => $secret_key,
						'app_id' => $app_id,
						'environment' => $environment,
			];
			$response = wp_remote_post( self::$api_url[$auth['environment']] . self::$api_url['endpoint']['MIGRATE_EXCLUDE_DATA'] ,
             array( 'timeout' => 25,
		    'body'    => $data,
            'headers' => self::buildHeader($auth)));

			if ( is_wp_error( $response ) ) {
			    $error_message = $response->get_error_message();
			    echo "Something went wrong: $error_message";
			} else {
				return $response['body'];
			}


		}

	}

?>