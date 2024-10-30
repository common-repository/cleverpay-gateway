<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://helloclever.co/
 * @since      1.8.0
 *
 * @package    Clever_Gateway
 * @subpackage Clever_Gateway/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Clever_Gateway
 * @subpackage Clever_Gateway/admin
 * @author     Hello Clever PTY LTD <support@helloclever.co>
 */

class Clever_Gateway_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.8.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.8.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.8.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		include_once CLEVER_GATEWAY_PATH . '/admin/clever-api.php';

		add_action('wp_ajax_clever_gateway_test_connection', [$this, 'clever_gateway_test_connection']);

		add_action('wp_ajax_clever_update_order', [$this, 'clever_update_order']);
		add_action('wp_ajax_nopriv_clever_update_order', [$this, 'clever_update_order']);

		add_action('wp_ajax_clever_get_cashback_rate', [$this, 'clever_get_cashback_rate']);
		add_action('wp_ajax_nopriv_clever_get_cashback_rate', [$this, 'clever_get_cashback_rate']);

		add_action('wp_ajax_clever_get_cashback_rate_v2', [$this, 'clever_get_cashback_rate_v2']);
		add_action('wp_ajax_nopriv_clever_get_cashback_rate_v2', [$this, 'clever_get_cashback_rate_v2']);

		add_action('wp_ajax_clever_create_order', [$this, 'clever_create_order']);
		add_action('wp_ajax_nopriv_clever_create_order', [$this, 'clever_create_order']);

		add_action('wp_ajax_clever_completed_order', [$this, 'clever_completed_order']);
		add_action('wp_ajax_nopriv_clever_completed_order', [$this, 'clever_completed_order']);


		add_action('wp_ajax_clever_cancel_order', [$this, 'clever_cancel_order']);
		add_action('wp_ajax_nopriv_clever_cancel_order', [$this, 'clever_cancel_order']);


		add_filter( 'woocommerce_payment_gateways', [$this, 'clever_add_gateway_class'] );
		add_action( 'plugins_loaded', [$this, 'clever_init_gateway_class'] );

		add_filter( 'plugin_action_links', [$this, 'plugin_action_links'], 10, 2 );

		add_action( 'woocommerce_after_add_to_cart_form', array( $this, 'display_paypal_button_product' ), 1 );

		add_action('wp_ajax_clever_create_checkout_express', [$this, 'clever_create_checkout_express']);
		add_action('wp_ajax_nopriv_clever_create_checkout_express', [$this, 'clever_create_checkout_express']);

		add_action('wp_ajax_clever_handle_checkout_express', [$this, 'clever_handle_checkout_express']);
		add_action('wp_ajax_nopriv_clever_handle_checkout_express', [$this, 'clever_handle_checkout_express']);

		add_action('wp_ajax_clever_get_shipping_tax', [$this, 'clever_get_shipping_tax']);
		add_action('wp_ajax_nopriv_clever_get_shipping_tax', [$this, 'clever_get_shipping_tax']);


		add_filter( 'woocommerce_available_variation', [$this, 'woocommerce_available_variation'], 10, 3 );

		add_action( 'woocommerce_order_status_changed', [$this, 'woocommerce_order_status_changed'], 10, 4 );

		add_action( 'wp_ajax_cl_search_data_filter', [$this, 'cl_search_data_filter'] ); 
		add_action( 'wp_ajax_nopriv_cl_search_data_filter', [$this, 'cl_search_data_filter'] );

		add_action( 'wp_ajax_cl_load_filter', [$this, 'cl_load_filter'] ); 
		add_action( 'wp_ajax_nopriv_cl_load_filter', [$this, 'cl_load_filter'] );
		
		add_filter( 'woocommerce_ajax_variation_threshold', [$this, 'wc_ninja_ajax_threshold'] );

		new Clever_Rest_API();

	}

	public function wc_ninja_ajax_threshold() {
	    return 100;
	}
	public function cl_load_filter(){
		$list = isset($_POST['list']) ? $_POST['list'] : '';
		if($list){
			foreach ($list as $key => $l) {
				if(!$l)
					continue;
				$l = explode(',', $l);
				$results = [];
				switch ($key) {
					case 'woocommerce_clever_gateway_filter_product_val':
					case 'woocommerce_clever_gateway_exclude_product_val':
					case 'woocommerce_clever_gateway_cashback_exclude_product_val':
						foreach ($l as $p) {
							$product = wc_get_product($p);
							if($product)
								$results[] = [
									'id' => $p,
									'text' => $product->get_title() . " ({$p})"
								];
						}
						break;
					default:
						foreach ($l as $p) {
							$category = get_term($p, 'product_cat');
							if($category)
								$results[] = [
									'id' => $p,
									'text' => $category->name . " ({$p})"
								];
						}
						break;
				}

				$list[$key] = $results;
			}
		}
		echo json_encode( $list );
		die();
	}

	public function cl_search_data_filter() {
		global $wpdb;
	    $q = isset($_GET['q']) ? strtolower($_GET['q']) : '';
	    $type = isset($_GET['type']) ? $_GET['type'] : '';
		$return = [];
	    if($q && $type){
	    	switch ($type) {
	    		case 'product':
	    			$sql = "SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_parent = 0 AND LOWER(post_title) LIKE '%{$q}%'";
	    			$results = $wpdb->get_results($sql);

	    			break;
	    		
	    		default:
	    			$sql = "SELECT {$wpdb->prefix}terms.term_id as ID, {$wpdb->prefix}terms.name as post_title FROM `{$wpdb->prefix}terms` INNER JOIN {$wpdb->prefix}term_taxonomy ON {$wpdb->prefix}terms.term_id = {$wpdb->prefix}term_taxonomy.term_id WHERE {$wpdb->prefix}terms.name LIKE '%{$q}%' AND {$wpdb->prefix}term_taxonomy.taxonomy = 'product_cat'";
	    			$results = $wpdb->get_results($sql);
	    			break;
	    	}

	    	if($results){
	    		foreach ($results as $key => $r) {
	    			$return[] = [$r->ID, $r->post_title];
	    		}
	    	}

	    }

	    echo json_encode( $return );
	    wp_die();
	}

	public function tracking($order_id, $token_ = false){
				$order = wc_get_order( $order_id );
				$auth = $this->auth();
				$item = [];
				$item_price = 0;
				foreach( $order->get_items() as $item_ ){
					$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $item_->get_product_id() ), 'large' );

					$product        = $item_->get_product();
					$item[] = [
						'id' =>  $item_->get_product_id(),
						'name' => $item_->get_name(),
						'quantity' => $item_->get_quantity(),
						'price' => $product->get_price(),
						'image_url' => isset($image_url[0]) ? $image_url[0] : ''
					];
					$item_price += floatval($product->get_price()) * $item_->get_quantity();
				}

				if($token_)
					$token = $token_;
				else
					$token = get_post_meta($order_id, '_ct_token', true);

			    $payload = [
			    	'event' => "payment_succeeded",
			    	'app_id' => $auth['app_id'],
			    	'token' => $token ? $token : '',
			    	'order_id' => $order_id,
			    	'payment_method' => $order->get_payment_method(),
			    	'amount' => $order->get_total(),
			    	'order_details' => [
			    		'id' => $order_id,
			    		'order_key' => $order->get_order_key(),
			    		'status' => $order->get_status(),
			    		'date_created' => $order->get_date_created()->getTimestamp(),
			    		'date_modified' => $order->get_date_modified()->getTimestamp(),
			    		'total' => $order->get_total(),
			    		'total_tax' => $order->get_total_tax(),
			    		'shipping_total' => $order->get_shipping_total(),
						'shipping_tax'   => $order->get_shipping_tax(),
						'shipping_address' => [
							'first_name' 	=>$order->shipping_first_name,
							'last_name' 	=>$order->shipping_last_name,
							'phone_number' => $order->get_billing_phone(),
			    			'email' => $order->get_billing_email(),
							'company' 	=>$order->shipping_company,
							'street_address' 	=>$order->shipping_address_1,
							'apartment' 	=>$order->shipping_address_2,
							'city' 	=>$order->shipping_city,
							'state' 	=>$order->shipping_state,
							'postcode' 	=>$order->shipping_postcode,
							'country_code' 	=>$order->shipping_country,
							'country' 	=> WC()->countries->countries[ $order->get_billing_country() ],
						],
						'billing_address' => [
							'first_name' 	=>$order->billing_first_name,
							'last_name' 	=>$order->billing_last_name,
							'phone_number' => $order->get_billing_phone(),
			    			'email' => $order->get_billing_email(),
							'company' 	=>$order->billing_company,
							'street_address' 	=>$order->billing_address_1,
							'apartment' 	=>$order->billing_address_2,
							'city'   => isset($order->billing_city) ? $order->billing_city : '',
							'state' 	=>$order->billing_state,
							'postcode' 	=>$order->billing_postcode,
							'country_code' 	=>$order->billing_country,
							'country' 	=> WC()->countries->countries[ $order->get_shipping_country() ],
						],
			    		'customer_details' => [
				    		'customer_id' => get_post_meta($order_id, '_customer_user', true),
			    			'first_name' => $order->get_billing_first_name(),
			    			'last_name' => $order->get_billing_last_name(),
			    			'email' => $order->get_billing_email(),
			    			'address_1' => $order->get_billing_address_1(),
			    			'address_2' => $order->get_billing_address_2(),
			    		],
			    		'item' => $item,
			    	],
			    	'item_price' => $item_price,
			    	'amount'   => $order->get_total(),
			    	'order_received_url'   => $order->get_checkout_order_received_url(),
					'root_url'   => admin_url('admin-ajax.php'),
					'url_checkout' => wc_get_checkout_url(),
			    	'ip' => '',
			    	'shop' => [
			    	  'name' => get_bloginfo( 'name' ),
		    		  'url' => get_site_url(),
		    		  'icon' => get_site_icon_url(),
		    		  'address' => get_option( 'woocommerce_store_address' ),
		    		  'email' => get_bloginfo( 'admin_email' ),
		    		  'city' => get_option( 'woocommerce_store_city' ),
		    		  'post_code' => get_option( 'woocommerce_store_postcode' ),
		    		  'currency' => get_option( 'woocommerce_currency' ),
		    		  'country' => get_option( 'woocommerce_default_country' ),
			    	],
			    	'plugin_type'=> 'woocommerce'
			    ];

			   $res = Clever_Api::success_order_tracking($auth, $payload);
	}
	

	public function woocommerce_order_status_changed($id, $status_transition_from, $status_transition_to, $that){

		if($status_transition_to === 'processing')
			$this->tracking($id);
	}

	public function woocommerce_available_variation($data, $product, $variation){
		 $data['variant_id'] = $variation->get_id();
		 return $data;     

	}

	public function clever_get_cashback_rate(){
		$auth = $this->auth();
		$res = Clever_Api::getCashbackRate($auth);
		wp_send_json(json_decode($res));
		die();
	}

	public function clever_get_cashback_rate_v2(){
		$data = isset($_POST['data']) ? $_POST['data']: '';
		if($data){

			$auth = $this->auth();
			$data = [
				'total_amount' => $data['cart_total'],
				'items' => $data['cart_items'],
		    	'shop' => [
					'name' => get_bloginfo( 'name' ),
					'url' => get_site_url(),
					'icon' => get_site_icon_url(),
					'address' => get_option( 'woocommerce_store_address' ),
					'email' => get_bloginfo( 'admin_email' ),
					'city' => get_option( 'woocommerce_store_city' ),
					'post_code' => get_option( 'woocommerce_store_postcode' ),
					'currency' => get_option( 'woocommerce_currency' ),
					'country' => get_option( 'woocommerce_default_country' ),
				  ],
				  'plugin_type'=> 'woocommerce'
			];


			$res = Clever_Api::getCashbackRateV2($auth, $data);
			wp_send_json(json_decode($res));
			die();
		}
	}


	public function clever_get_shipping_tax(){

		// include_once WC_ABSPATH . 'includes/wc-cart-functions.php';
  	//       include_once WC_ABSPATH . 'includes/class-wc-cart.php';

        if ( is_null( WC()->cart ) ) {
            wc_load_cart();
        }

	    // $order_id   = isset($_POST['order_id'])?$_POST['order_id']:0;
	    $country        = isset($_GET['country'])?$_GET['country']:0;
	    $coupon        = isset($_GET['coupon'])?$_GET['coupon']:0;
	    $state          = isset($_GET['state'])?$_GET['state']:0;
	    $postcode       = isset($_GET['postcode'])?$_GET['postcode']:0;
	    $city           = isset($_GET['city'])?$_GET['city']:0;
	    $product_id     = isset($_GET['product_id']) ? $_GET['product_id']:0;
	    $variation_id     = isset($_GET['variation_id']) ? $_GET['variation_id']:0;
	    $attributes     = isset($_GET['attributes']) ? $_GET['attributes']: NULL;
	    $quantity       = isset($_GET['quantity']) ? $_GET['quantity']:0;
	    $chosen_shipping_methods       = isset($_GET['chosen_shipping_methods']) ? $_GET['chosen_shipping_methods']:0;

	    // debug(json_decode('{"attribute_pa_mau-sac": "den"}'));
	    // Order and order items
	    // $order          = wc_get_order( $order_id );
	    // $order_items    = $order->get_items();
	    WC()->cart->empty_cart();

	    if($attributes){
	    	$attributes = base64_decode($attributes);
	    	$attributes = json_decode($attributes, true);
	    }


	    if($variation_id)
		    WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes);
		else
		    WC()->cart->add_to_cart( $product_id, $quantity );


	    WC()->cart->calculate_totals();

	    // Reset shipping first
	    WC()->shipping()->reset_shipping();

	    // Set correct temporary location
	    if ( $country != '' ) {
	        WC()->customer->set_billing_location( $country, $state, $postcode, $city );
	        WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
	    } else {
	        WC()->customer->set_billing_address_to_base();
	        WC()->customer->set_shipping_address_to_base();
	    }

	  
	    // Calculate shipping
	    $packages = WC()->cart->get_shipping_packages();
	    $shipping = WC()->shipping->calculate_shipping($packages);

	    if($chosen_shipping_methods){

		    WC()->session->set('chosen_shipping_methods', array( $chosen_shipping_methods ) );
		    WC()->cart->calculate_totals();
	    }

	    $available_methods = WC()->shipping->get_packages();
	    // $chosen_methods['flat_rate:2'] = $available_methods[0]['rates']['flat_rate:2'];
	    // WC()->session->set( 'chosen_shipping_methods', $chosen_methods );

	    // debug($available_methods);

	    $result = [];
	    if($available_methods){
	        foreach ($available_methods[0]['rates'] as $k => $rate) {
	            $result[$k] = [
	                'id' => $rate->get_id(),
	                'label' => $rate->get_label(),
	                'instance_id' => $rate->get_instance_id(),
	                'cost' => $rate->get_cost(),
	                'taxes' => $rate->get_taxes(),
	            ];
	        }
	    }   

	    $taxes = array(); // Initialiizing (for display)

	    $location = array(
	        'country'   => $country,
	        'state'     => $state,
	        'city'      => $city,
	        'postcode'  => $postcode,
	    );

	    // Loop through tax classes
	    foreach ( wc_get_product_tax_class_options() as $tax_class => $tax_class_label ) {

	        // Get the tax data from customer location and product tax class
	        $tax_rates = WC_Tax::find_rates( array_merge(  $location, array( 'tax_class' => $tax_class ) ) );

	        // Finally we get the tax rate (percentage number) and display it:
	        if( ! empty($tax_rates) ) {
	            $rate_id      = array_keys($tax_rates);
	            $rate_data    = reset($tax_rates);

	            $rate_id      = reset($rate_id);        // Get the tax rate Id
	            $rate         = $rate_data['rate'];     // Get the tax rate
	            $rate_label   = $rate_data['label'];    // Get the tax label
	            $is_compound  = $rate_data['compound']; // Is tax rate compound
	            $for_shipping = $rate_data['shipping']; // Is tax rate used for shipping

	            // set for display
	            $taxes[] = [
	                'rate_id' => $rate_id,
	                'rate_data' => $rate_data,
	                'rate' => $rate,
	                'rate_label' => $rate_label,
	                'is_compound' => $is_compound,
	                'for_shipping' => $for_shipping,
	            ];
	        }
	    }

	    $coupon_apply = false;
	    $coupon_info = false;
	    if($coupon){
	    	$coupon_obj = new WC_Coupon( $coupon );
	    	if ($coupon_obj->is_valid()) {
	    		WC()->cart->apply_coupon( $coupon );
	    		WC()->cart->calculate_totals();
	    		$coupon_apply = true;
	    		$coupon_info = [
	    			'amount' => $coupon_obj->amount,
	    			'discount_type' => $coupon_obj->discount_type,
	    			'individual_use' => $coupon_obj->individual_use,
	    			'usage_count' => $coupon_obj->usage_count,
	    			'usage_limit' => $coupon_obj->usage_limit,
	    			'description' => $coupon_obj->description,
	    		];
	    	}
	    }

	    $product = get_product($product_id);
	    echo json_encode([
	    	'coupon' => [
	    		'apply' => $coupon_apply,
	    		'coupon_info' => $coupon_info
	    	], 
	    	'is_virtual' => $product->is_virtual(),
	    	'shipping_methods' => $result, 
	    	'total' => WC()->cart->total, 
	    	'shipping_total' => WC()->cart->get_shipping_total(), 
	    	'shipping_tax' => WC()->cart->get_shipping_tax(), 
	    	'tax_total' => WC()->cart->get_total_tax() ] + ['taxes' => $taxes]);
	    die();

	}


	public function createOrder_2($transaction, $checkout_express_id = 0) {

		$payment_gateways = WC()->payment_gateways->payment_gateways();
		$address = array(
			'first_name' => $transaction['customer']['first_name'],
			'last_name'  => $transaction['customer']['last_name'],
			'email'      => $transaction['customer']['email'],
			'address_1'  => $transaction['customer']['address'],
			'city'       => $transaction['customer']['city'],
			'state'      => $transaction['customer']['state'],
			'postcode'   => $transaction['customer']['postcode'],
			'country'    => $transaction['customer']['country'],
		);
	
		$shipping_address = array(
			'first_name' => $transaction['shipping_address']['first_name'],
			'last_name'  => $transaction['shipping_address']['last_name'],
			'email'      => $transaction['shipping_address']['email'],
			'address_1'  => $transaction['shipping_address']['address'],
			'city'       => $transaction['shipping_address']['city'],
			'state'      => $transaction['shipping_address']['state'],
			'postcode'   => $transaction['shipping_address']['postcode'],
			'country'    => $transaction['shipping_address']['country'],
		);
	
		$order = wc_create_order();
		foreach ($transaction['order_details'] as $key => $item) {
			// $product = get_product($item['id']);
			$args = array( 'totals' => array( 
					'subtotal' => $item['price'], 
					'total' => $item['price']*$item['quantity'],
			));
			if($item['attributes_'])
				$args['variation'] = $item['attributes_'];
			
			$order->add_product( wc_get_product( $item['variant_id'] ? $item['variant_id'] : $item['id'] ), $item['quantity'], $args ); 
		}

		if($transaction['coupon']){
			$coupon_obj = new WC_Coupon( $transaction['coupon'] );
			if ($coupon_obj->is_valid()) {
			$order->apply_coupon( $transaction['coupon'] );
			$order->calculate_totals();
			
			}
		}
			
		$order->set_address( $address, 'billing' );
		
		$order->set_address( $shipping_address, 'shipping' );

		if(isset($product) && !$product->is_virtual() && $transaction['shipping']) { 

			$item = new WC_Order_Item_Shipping();

			$country_code = $order->get_shipping_country();

			// Set the array for tax calculations
			$calculate_tax_for = array(
				'country' => $transaction['shipping_address']['country'],
				'state' => $transaction['shipping_address']['state'], 
				'postcode' => $transaction['shipping_address']['postcode'], 
				'city' => $transaction['shipping_address']['city'], 
			); 
			$item->set_method_title( $transaction['shipping']['label'] );
			$item->set_method_id( $transaction['shipping']['id'] );
			$item->set_total( $transaction['shipping']['cost'] );
			$item->calculate_taxes($calculate_tax_for);
			$order->add_item( $item );  
		}
	
		$order->calculate_totals();
			$order->set_payment_method($payment_gateways['clever_gateway']);   
	
		// $order->add_order_note( __( 'Payment completed via Express checkout', 'woocommerce' ) );
		// $order->payment_complete();
		$order->update_meta_data('_ct_token', $transaction['token'] ? $transaction['token'] : '');
		$order_id = $order->save();
		if($transaction['token'])
			$this->tracking($order_id, $transaction['token']);
	
		echo json_encode(['order_id' => $order_id, 'total' => $order->get_total()]);      
	
		die();
	}

	public function clever_cancel_order(){
		$info = isset($_GET['info']) ? sanitize_text_field($_GET['info']) : '';
		if($info){
			$auth = $this->auth();
			$info = Clever_JWT::decode($info, $auth['secret_key']);
			$order = wc_get_order($info->order_id);
			if($order){
				$cancelled_text = __("No successful payment", "woocommerce");
		        $order->update_status( 'cancelled',$cancelled_text);
		        echo json_encode(['success' => true]);
		        die();
			}
		}
	}

	public function clever_completed_order(){
		
		$info = isset($_GET['info']) ? sanitize_text_field($_GET['info']) : '';
		$onPopup = isset($_GET['on_popup']) ? sanitize_text_field($_GET['on_popup']) : '';

 	    if($info){
			$auth = $this->auth();
			$info = Clever_JWT::decode($info, $auth['secret_key']);

 	    	if($info && isset($info->transaction_id)){

 	    		$transaction = Clever_Api::confirmPayment($auth, $info->transaction_id);
 	    		$transaction = json_decode($transaction, true);

    		    if(isset($transaction['order_id'])){
    		    	$order = wc_get_order($transaction['order_id']);

    		    	if(!$order){
			    		echo json_encode(['success' => false]);
			    		die();
    		    	}

			    	// if($onPopup != 'true'){
	    		    	if($order_id == $transaction['order_id'] && $order->get_total() <= $transaction['amount'] && $transaction['completed'] == true){
	    		    		if(!$order->is_paid()){
	    						$order->add_order_note( __( 'Payment completed', 'woocommerce' ) );
	    			    		$order->payment_complete();

	    			    		
	    		    		}
	    		    		echo json_encode(['success' => true]);

	    		    	}
	    		    	else
				    		echo json_encode(['success' => false]);

			    		die(); 
			    	// }
    		    }
 	    	}

 	    	if(isset($info->order_id)){
 	    		$order = wc_get_order($info->order_id);
 	    		ob_start();
 	    		?>
 	    			<script>
 	    				window.opener.cleverRedirectFunc('<?php echo $order->get_checkout_order_received_url() ?>');
 	    				window.close();
 	    			</script>
 	    		<?php
 	    		echo ob_get_clean();
 	    		die();
 	    	}
 	    }
	}

	public function clever_handle_checkout_express(){
	
		$info = isset($_POST['info']) ? sanitize_text_field($_POST['info']) : '';
		if($info){
			$auth = $this->auth();
			$info = Clever_JWT::decode($info, $auth['secret_key']);
			$info = json_decode(json_encode($info), true);
			if($info && isset($info['data'])){
		    	$this->createOrder_2($info['data']);
			}
		}
		// die();
	}

	public function checkCashBackEnable($product_id){


			$gateways = WC()->payment_gateways->get_available_payment_gateways();
			$settings = $gateways['clever_gateway']->settings;

			$listCategories = [];

			$terms = wp_get_post_terms( $product_id, 'product_cat', ['fields' => 'ids'] );
			$listCategories = $terms;
			if($terms)
			{
			    foreach ($terms as $key => $term) {
			        $parent  = get_ancestors( $term, 'product_cat' );
			        $listCategories = array_merge($listCategories,$parent);
			    }

			
			    $listCategories = array_unique($listCategories);


			}

			$flag = false;
			if($settings['cashback_exclude_product_val']){
				$products = explode(',', $settings['cashback_exclude_product_val']);
				if(in_array($product_id, $products))
					$flag = true;
			}
			if($settings['cashback_exclude_category_val']){
				$categories = explode(',', $settings['cashback_exclude_category_val']);
			    $c = array_intersect($categories, $listCategories);
				if(count($c))
				   	$flag = true;
			}
			return !$flag ? true : false;
	} 

	public function clever_create_checkout_express(){

		$product_id = isset($_POST['product_id']) ? sanitize_text_field($_POST['product_id']) : '';
		$variation_id = isset($_POST['variation_id']) ? sanitize_text_field($_POST['variation_id']) : '';
		$quantity = isset($_POST['quantity']) ? sanitize_text_field($_POST['quantity']) : 1;
		$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
	    $attributes_     = isset($_POST['attributes']) ? $_POST['attributes']: NULL;
		 
		if($product_id){
			$id = $variation_id ? $variation_id : $product_id;
			$product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
			if(!$product)
				return;

			$category_array = [];
			$categories = get_the_terms( $product_id, 'product_cat' );

			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			    $category_array = array();
			    foreach ( $categories as $category ) {
			        $category_array[] = [
			        	'name' => $category->name,
			        	'id' => $category->term_id,
			        ];
			    }
			}


			$attributes = [];
			if($variation_id){
				$variation_attributes = $product->get_variation_attributes();
				foreach ($variation_attributes as $taxonomy => $term_names) {
					$taxonomy       = str_replace('attribute_', '', $taxonomy);
					$attribute_label_name = wc_attribute_label($taxonomy);
					$attribute_label_value = get_term_by('slug', $term_names, $taxonomy);
					$attributes[$attribute_label_name] = $attribute_label_value->name;
			   }
			}

			$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'large' );
			if(!$image_url)
				$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'large' );

			$data = [
				'amount' =>  floatval($product->get_price()) * $quantity,
				'order_details' => [
					'item' => [
						'id' => $product_id,
						'variant_id' => $variation_id,
						'image_url' => isset($image_url[0]) ? $image_url[0] : '',
						'name' => $product->get_title(),
						'enable_cashback' => $this->checkCashBackEnable($product_id),
						'quantity' => $quantity,
						//'price' => $product->get_price(),
						'type' => $product->get_type(),
                        'price' => wc_get_price_excluding_tax($product),
						'attributes' => $attributes,
						'attributes_' => $attributes_,
						'categories' => $category_array,
					]
				],
				'callback' => admin_url('admin-ajax.php'),
				'callback_url' => admin_url('admin-ajax.php') . '?action=clever_handle_checkout_express',
				'callback_shipping_tax_url' => admin_url('admin-ajax.php') . '?action=clever_get_shipping_tax',
				'exp' => time() + 60*15,
				'shop' => [
					'name' => get_bloginfo( 'name' ),
					'url' => get_site_url(),
					'icon' => get_site_icon_url(),
					'address' => get_option( 'woocommerce_store_address' ),
					'email' => get_bloginfo( 'admin_email' ),
					'city' => get_option( 'woocommerce_store_city' ),
					'post_code' => get_option( 'woocommerce_store_postcode' ),
					'currency' => get_option( 'woocommerce_currency' ),
					'country' => get_option( 'woocommerce_default_country' ),
				],
				'plugin_type'=> 'woocommerce',
				'token' => $token
			];	


			$auth = $this->auth();
			$res = Clever_Api::createPaymentExpress($auth, $data);	
			// $res = json_encode(['redirect_url' => 'http://localhost/lamgom/wp-admin/admin-ajax.php?action=clever_handle_checkout_express&info=eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0cmFuc2FjdGlvbl9pZCI6IkVYUFJFU1NfT1JERVJfMV8xNjQ2MDM3MDM0IiwiZXhwIjoxNjQ2MDM4OTQ1fQ.i6YvcXS6EjwldPpJP653ZUl8b4WPdmiNPnnGpGB-W3U']);	
			wp_send_json(json_decode($res, true));
		}
		die();
	}

	public function plugin_action_links( $links_array, $plugin_file_name){
		if( $plugin_file_name === 'cleverpay-gateway/cleverpay-gateway.php') {
			array_unshift( $links_array, '<a href="#" target="_blank">Integration Doc</a>' );
			// array_unshift( $links_array, '<a href="#" target="_blank">Support</a>' );
			array_unshift( $links_array, '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=clever_gateway') . '">Settings</a>' );
		}
		return $links_array;
		
	}

	public function display_paypal_button_product() {
		global $product;
		$product_id = $product->get_id();
		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		// debug($gateways['clever_gateway']->settings);
		if ( 	! is_product() || 
					! isset( $gateways['clever_gateway'] ) ||
					! isset($gateways['clever_gateway']->settings['checkout_express']) ||
					$gateways['clever_gateway']->settings['checkout_express'] == 'no' ||
					! $product->is_in_stock() ||
					$product->is_type( 'external' ) ||
					$product->is_type( 'grouped' ) ) 
		{
			return;
		}

	

		$category_array = [];
		$categories = get_the_terms( $product_id, 'product_cat' );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
		    $category_array = array();
		    foreach ( $categories as $category ) {
		        $category_array[] = [
		        	'name' => $category->name,
		        	'id' => $category->term_id,
		        ];
		    }
		}
		$product_info = [
			'id' => $product_id,
			'name' => $product->get_name(),
			'price' => wc_get_price_excluding_tax($product),
			'sku' => $product->get_sku(),
			'virtual' => $product->is_virtual(),
			'categories' => $category_array,
			'type' => $product->get_type(),
			'currency' => get_option( 'woocommerce_currency' )
		];

		$price = [
			'type' => $product->get_type(),
			'price' => wc_get_price_excluding_tax($product)
		];

		

		?>
		<input type="hidden" id="clever_product_info" data-json='<?php echo json_encode($product_info)?>'>
		<div class="">
			<a class="clever-pay-check-out-express">
				<span class="clever-text-l">
					<span class="cashback_value off-m cb-absolute">
						<span class="cb-content"></span><br>
					</span>
					<span class="cashback_value off-m cb-percentage">
						<span class="cb-content"></span><br>
					</span>
					<span class="cashback_title">Checkout with <span><img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/logo-3.svg" alt=""/></span>

						<svg class="cl-off-popup" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" width="512" height="512" x="0" y="0" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512" xml:space="preserve" class=""><g><path d="M256 0C114.497 0 0 114.507 0 256c0 141.503 114.507 256 256 256 141.503 0 256-114.507 256-256C512 114.497 397.493 0 256 0zm0 472c-119.393 0-216-96.615-216-216 0-119.393 96.615-216 216-216 119.393 0 216 96.615 216 216 0 119.393-96.615 216-216 216z" fill="#ffffff" opacity="1" data-original="#000000" class=""></path><path d="M256 128.877c-11.046 0-20 8.954-20 20V277.67c0 11.046 8.954 20 20 20s20-8.954 20-20V148.877c0-11.046-8.954-20-20-20z" fill="#ffffff" opacity="1" data-original="#000000" class=""></path><circle cx="256" cy="349.16" r="27" fill="#ffffff" opacity="1" data-original="#000000" class=""></circle></g></svg>
					</span>
				</span> 
			</a>
		</div>
		<div class="clever-pay-error">  </div>
		<?php

	}

	public function auth(){
		$payments_settings = get_option('woocommerce_clever_gateway_settings');

		$environment = $payments_settings['api_environment'];
		$secret_key = 'secret_key';
		$app_id = 'app_id';
		if($environment == 'sandbox'){
			$secret_key = 'sandbox_secret_key';
			$app_id = 'sandbox_app_id';
		}
		$secret_key = $payments_settings[$secret_key];
		$app_id = $payments_settings[$app_id];

		$auth =  [
			'success' => $secret_key && $app_id ? true : false, 
			'secret_key' => $secret_key,
			'app_id' => $app_id,
			'environment' => $environment,
		];
		return $auth;
	}

	public function clever_create_order(){

	}


	public function clever_update_order(){
		$info = isset($_POST['info']) ? sanitize_text_field($_POST['info']) : '';
		$status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'complete';
 	    if($info){

 	    	$auth =  $this->auth();
 	    	
 	    	$info = Clever_JWT::decode($info, $auth['secret_key']);

 	    	if($info && isset($info->transaction_id)){
				  $transaction = Clever_Api::confirmPayment($auth, $info->transaction_id);	



			    $transaction = json_decode($transaction, true);
			    if(isset($transaction['order_id'])){
			    	$order = wc_get_order($transaction['order_id']);

				    if($status == 'refund'){
				    	$order->update_status('refund');
				    	wp_send_json([
				    		'success' => true,
				    		'order_id' => $transaction['order_id'],
				    		'status' => $order->get_status(),
				    	]);
				    	die();
				    }
			    	else if($status == 'complete' && $order && $order->get_total() <= $transaction['amount'] && $transaction['completed'] == true){
			    		if(!$order->is_paid()){

			    			// $this->tracking($transaction['order_id']);

							$order->add_order_note( __( 'Payment completed', 'woocommerce' ) );
				    		$order->payment_complete();
				    		WC()->cart->empty_cart();

			    		}

			    		wp_send_json([
			    			'success' => true,
				    		'status' => $order->get_status(),
				    		'order_key' => $order->get_order_key(),
			    			'is_paid' => $order->is_paid(),
			    			'order_id' => $transaction['order_id'],
			    			'redirect_url' => $order->get_checkout_order_received_url()
			    		]);
			    		die();

			    	}

			    }
 	    	}

 	    	wp_send_json([
    			'success' => false,
    		]);
    		die();

 	    }
	}

	public function clever_gateway_test_connection(){
		$secret_key = isset($_POST['secret_key']) ? sanitize_text_field($_POST['secret_key']) : ''; 
		$app_id = isset($_POST['app_id']) ? sanitize_text_field($_POST['app_id']) : ''; 
		$environment = isset($_POST['environment']) ? sanitize_text_field($_POST['environment']) : '';
		if($app_id && $secret_key){
			$res = Clever_Api::testConnect([
				'secret_key' => $secret_key, 
				'app_id'	=> $app_id,
				'environment'	=> $environment
			]);
			wp_send_json(json_decode($res));
			die();
		}
		else
			echo json_encode(['success' => false]);
		die();
	}

	public function clever_add_gateway_class( $gateways ) {
		$gateways[] = 'WC_Clever_Gateway';
		return $gateways;
	}

	public function clever_init_gateway_class() {
		include "clever-gateway.php";
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.8.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clever_Gateway_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clever_Gateway_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clever-gateway-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.8.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Clever_Gateway_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Clever_Gateway_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/clever-gateway-admin.js', array( 'jquery' ), $this->version, false );

	}

}