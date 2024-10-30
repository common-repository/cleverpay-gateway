<?php 
	
	class WC_Clever_Gateway extends WC_Payment_Gateway {
	 		public $clever_settings = [];
	 		private $jwt = null;
	 		public function __construct() {
				$this->id = 'clever_gateway';
				$this->icon = CLEVER_GATEWAY_URL . "public/images/logo-4.svg";
				$this->has_fields = true;
				$this->method_title = 'Hello Clever';
				$this->method_description = 'Pay securely with your debit/credit cards or PayID.';
				$this->debug = 'no';
				$this->supports = array(
					'products'
				);
 				$this->init_form_fields();
 				$this->init_settings();
 				$this->enabled = $this->get_option( 'enabled' );
 				$this->title = 'Hello Clever';
				$this->description = $this->get_option( 'description' );

 				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

 				add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 				
				add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ), 4 );
				add_filter( 'woocommerce_gateway_icon', [$this, 'custom_payment_gateway_icons' ], 10, 2 );
	 		}

	 		public function custom_payment_gateway_icons($icon, $gateway_id){
	 			if( $gateway_id == 'clever_gateway' ){
		 			$icon = '<img class="clever_payment_list" src="https://helloclever.co/static/clever-payment-list-v4.svg" alt="Hello Clever">';
	 			}
	 			return $icon;
	 		}

	 		public function admin_options(){
			 ?>
				 <h2><?php _e('Clever Gateway','woocommerce'); ?></h2>
				 <table class="form-table">
					 <?php $this->generate_settings_html(); ?>
					 
					 <tr>
					 	<th></th>
					 	<td>
						 <button name="save" class="button-primary test-connection-btn" style="display:none">Test Connection</button>
						 <div id="result-test"></div>
					 	</td>
					 </tr>
					
				 </table> 


				 <script>
				 	jQuery(document).ready(function($) {
		 		 		

				 		const checkIsExpress = () => {
				 			const value = $('#woocommerce_clever_gateway_checkout_express').is(":checked")
				 			$.each($('.form-table tbody tr'), function(i, val) {
				 				

				 			});
				 		}



				 		checkIsExpress()
				 		$('#woocommerce_clever_gateway_checkout_express').change(function(){
					 		checkIsExpress()
				 		})

				 		
				 		$(document).on('click', '.woocommerce-save-button', function(e){
				 			if(!$(this).hasClass('test-ok'))
				 			{
					 			e.preventDefault()
					 			document.body.style.cursor = "wait";
					 			$('.test-connection-btn').trigger('click')
				 			}
				 		});

				 		$('.test-connection-btn').click(function(event) {
				 			event.preventDefault();
				 			const environment = $('#woocommerce_clever_gateway_api_environment').val();
				 			let app_id = '#woocommerce_clever_gateway_app_id';
				 			let secret_key = '#woocommerce_clever_gateway_secret_key';
				 			if(environment == 'sandbox'){
				 				app_id = '#woocommerce_clever_gateway_sandbox_app_id';
				 				secret_key = '#woocommerce_clever_gateway_sandbox_secret_key';

				 			}
				 			app_id = $(app_id).val();
				 			secret_key = $(secret_key).val();

				 			const result_el = $('#result-test');
				 			$.ajax({
				 				url: '<?php echo admin_url('admin-ajax.php'); ?>',
				 				type: 'POST',
				 				dataType: 'json',
				 				data: {
				 					action: 'clever_gateway_test_connection',
				 					app_id,
				 					secret_key,
				 					environment
				 				},
				 			})
				 			.done(function(res) {
				 				if(res?.result == true){
				 					alert('Saved successfully.')
				 					$('.woocommerce-save-button').addClass('test-ok')
				 					document.querySelector('.woocommerce-save-button').click()
					 				// result_el.html('').append(`<div id="clever-gateway-test" class="notice notice-success settings-error is-dismissible"><p>Connection was successful<p></div>`)
				 				}
					 			else{
					 				alert('Connection failed, Please check your App ID and Secret Key.')
					 				// result_el.html('').append(`<div id="clever-gateway-test" class="notice notice-error settings-error is-dismissible"><p>Connection failed, Please check your App ID and Secret Key.<p></div>`)
					 			}

					 			document.body.style.cursor = "default";

				 			})


				 		});

				 		const changeEnvironment = () => {
				 			const environment = $('#woocommerce_clever_gateway_api_environment').val();
				 			$('.envi-field').parents('tr').hide();
				 			$(`.clever-gateway-${environment}`).parents('tr').show();
				 		}
				 		changeEnvironment();

				 		$('#woocommerce_clever_gateway_api_environment').change(function(event) {
					 		changeEnvironment();
				 		});


				 		$('.woocommerce-save-button').click(function(event) {
				 			
				 			const environment = $('#woocommerce_clever_gateway_api_environment').val();
				 			const fields = {
				 				'production': ['#woocommerce_clever_gateway_app_id', '#woocommerce_clever_gateway_secret_key'],
				 				'sandbox': ['#woocommerce_clever_gateway_sandbox_app_id', '#woocommerce_clever_gateway_sandbox_secret_key'],
				 			}

				 			const condition = fields[environment].every(f => {
				 				return $(f).val() != ''
				 			})
				 			if(!condition){
				 				event.preventDefault();
				 				return alert('App Id and Secret Key cannot be blank')
				 			}
				 		});

				 		let unsaved = false;

				 		
				 		document.querySelector("#woocommerce_clever_gateway_app_id").addEventListener("change", () => {
				 		  unsaved = true;
				 		});
				 		document.querySelector("#woocommerce_clever_gateway_secret_key").addEventListener("change", () => {
				 		  unsaved = true;
				 		});
				 		document.querySelector("#woocommerce_clever_gateway_sandbox_app_id").addEventListener("change", () => {
				 		  unsaved = true;
				 		});
				 		document.querySelector("#woocommerce_clever_gateway_sandbox_secret_key").addEventListener("change", () => {
				 		  unsaved = true;
				 		});

				 		const unloadPage = () => {
				 		  if (unsaved) {
				 		    return "You have unsaved changes on this page.";
				 		  }
				 		};

				 		window.onbeforeunload = unloadPage;


				 	});
				 </script>
				 <?php
			 }
	
			
	 		public function init_form_fields(){

	 			$this->form_fields = array(
					'enabled' => array(
						'title'       => __('Payment Option', 'clever-gateway'),
						'label'       => __('Enable Hello Clever Gateway', 'clever-gateway'),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'no'
					),
					'checkout_express' => array(
						'title'       => __('Checkout at Product Page', 'clever-gateway'),
						'label'       => __('Enable', 'clever-gateway'),
						'type'        => 'checkbox',
						'description' => '',
						'default'     => 'yes'
					),
					'api_environment'         => array(
				        'title'       => __( 'API Environment', 'clever-gateway' ),
				        'type'        => 'select',
				        'description' => '',
				        'default'     => 'html',
				        'class'       => 'wc-enhanced-select',
				        'options'     => [
				        	'production'	=> 'Production',
				        	'sandbox'	=> 'Sandbox',
				        ],
				        'desc_tip'    => true,
			      	),
					'app_id' => array(
					    'title' => __('APP ID (Production)', 'clever-gateway'),
					    'type' => 'text',
					    'default' => '',
					    'desc_tip'      => true,
					    'class'		=> 'envi-field clever-gateway-production'
					),
					'secret_key' => array(
					    'title' => __('Secret Key (Production)', 'clever-gateway'),
					    'type' => 'text',
					    'default' => '',
					    'desc_tip'      => true,
					    'class'		=> 'envi-field clever-gateway-production'
					),
					'sandbox_app_id' => array(
					    'title' => __('APP ID (Sandbox)', 'clever-gateway'),
					    'type' => 'text',
					    'default' => '',
					    'desc_tip'      => true,
					    'class'		=> 'envi-field clever-gateway-sandbox'
					),
					'sandbox_secret_key' => array(
					    'title' => __('Secret Key (Sandbox)', 'clever-gateway'),
					    'type' => 'text',
					    'default' => '',
					    'desc_tip'      => true,
					    'class'		=> 'envi-field clever-gateway-sandbox'
					),
			      	
					
					
				);

		
		 	}

		 	

			
			public function payment_fields() {
					
					include_once CLEVER_GATEWAY_PATH . 'public/partials/payment-method-description.php';
					 
			}

		 	public function payment_scripts() {

					if ( ! is_cart() && ! is_checkout() ) {
						return;
					}

					if ( 'no' === $this->enabled ) {
						return;
					}

		
		 	}

			public function validate_fields() {

				return true;

			}

			public function auth(){
				$environment = $this->get_option( 'api_environment' );
				$secret_key = 'secret_key';
				$app_id = 'app_id';
				if($environment == 'sandbox'){
					$secret_key = 'sandbox_secret_key';
					$app_id = 'sandbox_app_id';
				}
				$secret_key = $this->get_option( $secret_key );
				$app_id = $this->get_option( $app_id );

				return [
					'success' => $secret_key && $app_id ? true : false, 
					'secret_key' => $secret_key,
					'app_id' => $app_id,
					'environment' => $environment,
					'plugin_version' => CLEVER_GATEWAY_VERSION
				];

			}

			public function checkCashBackEnable($product_id){


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
					if($this->get_option('cashback_exclude_product_val')){
						$products = explode(',', $this->get_option('cashback_exclude_product_val'));
						if(in_array($product_id, $products))
							$flag = true;
					}
					if($this->get_option('cashback_exclude_category_val')){
						$categories = explode(',', $this->get_option('cashback_exclude_category_val'));
					    $c = array_intersect($categories, $listCategories);
						if(count($c))
						   	$flag = true;
					}
					return !$flag ? true : false;
			} 

			public function create_order($order_id, $return_order_obj = false){
					$order = wc_get_order( $order_id );
					$auth = $this->auth();
					$item = [];
					$item_price = 0;
					foreach( $order->get_items() as $item_ ){
						$data_item = $item_->get_data();
						$image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $item_->get_product_id() ), 'large' );

						$product        = $item_->get_product();

						$category_array = [];
						$categories = get_the_terms( $item_->get_product_id(), 'product_cat' );

						if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
						    $category_array = array();
						    foreach ( $categories as $category ) {
						        $category_array[] = [
						        	'name' => $category->name,
						        	'id' => $category->term_id,
						        ];
						    }
						}

						$item[] = [
							'id' =>  $item_->get_product_id(),
							'name' => $item_->get_name(),
							'enable_cashback' => $this->checkCashBackEnable($item_->get_product_id()),
							'quantity' => $item_->get_quantity(),
							'price' => $product->get_price(),
							'image_url' => isset($image_url[0]) ? $image_url[0] : '',
							'categories' => $category_array,
							'sub_total' => $data_item['subtotal'],
							'type' => $product->get_type(),
							'total' => $data_item['total'],
							'total_tax' => $data_item['total_tax'],
							'subtotal_tax' => $data_item['subtotal_tax'],
						];


						$item_price += floatval($product->get_price()) * $item_->get_quantity();
					}
					$coupon_lines = [];
					if($order->get_used_coupons()){
						foreach ($order->get_used_coupons() as $key => $code) {
							$c = new WC_Coupon($code);
							$c_info = $c->get_data();
							$coupon_lines[] = [
								'code' => $code,
								'type' => $c_info['discount_type'],
								'description' => $c_info['description'],
								'discount' => $c_info['amount'],
							
							];
						}
					}

					$fee_lines = [];
					if($order->get_items('fee')){
						foreach( $order->get_items('fee') as $item_id => $item_fee ){
							$fee_lines[] =  $item_fee->get_data();
						}
					}
					
				
				    $payload = [
				    	'order_id' => $order_id,
				    	'payment_method' => $order->get_payment_method(),
				    	'amount' => $order->get_total(),
				    	'order_details' => [
				    		'id' => $order_id,
				    		'order_key' => $order->get_order_key(),
				    		'status' => $order->get_status(),
				    		'date_created' => $order->get_date_created()->getTimestamp(),
				    		'date_modified' => $order->get_date_modified()->getTimestamp(),
				    		'coupon_lines' => $coupon_lines,
				    		'fee_lines' => $fee_lines,
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
				    	'order_received_url'   => $this->get_return_url( $order ),
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


				    if($return_order_obj)
				    	return $payload;

				    $res = Clever_Api::createPayment($auth, $payload);
				    $res = json_decode($res, true);
				    return $res;
			}	

			public function process_payment( $order_id ) {
				
			    $res = $this->create_order($order_id);

			    if(!isset($res['redirect_url']))	
				    $res = $this->create_order($order_id);

				if(!isset($res['redirect_url'])){
					if(isset($res['errors']['order']) && $res['errors']['order'] == 'Has been paid'){
						wc_add_notice( __('This order has been paid. Please create a new order') . '<a href="'.get_site_url().'" class="wc-backward" >Back to shop</a>', 'error' );
						WC()->cart->empty_cart();
					}
					else
						wc_add_notice( __('Error'), 'error' );
					return [
			    		'refresh' => false,
			    		'reload' => false,
			    		'result' => "failure",
			    		'messages' => ''
			    	];
				}
			    else{
			 		return array(
			 		    'result'    => 'success',
			 		    'redirect'  => $res['redirect_url']
			 		);
			    }
			    
			}

			/*
			 * In case you need a webhook, like PayPal IPN etc
			 */
			public function webhook() {

			
						
		 	}


		 	
		 	
		 	public function thankyou_page( $order_id ) {
		 	

		 		$auth = $this->auth();

		 	    $order = new WC_Order($order_id);
		 	    if($order->get_payment_method() != $this->id) return;
		 	    
		 	    $info = isset($_GET['info']) ? sanitize_text_field($_GET['info']) : '';
		 	    if($info){
		 	    	
		 	    	$info = Clever_JWT::decode($info, $auth['secret_key']);

		 	    	if($info && isset($info->transaction_id)){
						$transaction = Clever_Api::confirmPayment($auth, $info->transaction_id);		 	    		
					    $transaction = json_decode($transaction, true);

					    if(isset($transaction['order_id'])){
					    	$order = wc_get_order($order_id);

					    	if($order_id == $transaction['order_id'] && $order->get_total() <= $transaction['amount'] && $transaction['completed'] == true){
					    		if(!$order->is_paid()){
									$order->add_order_note( __( 'Payment completed', 'woocommerce' ) );
						    		$order->payment_complete();
						    		WC()->cart->empty_cart();
					    		}

					    	}

					    }
		 	    	}

		 	    }
		 	    if($order->is_paid()):
		 	    ?>
 	    			<div class="clever-gateway-result"><div class="clever-success-animation">
	 	    		<img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/done.png" >
	 	    		</div><div class="text-center clever-gateway-s-text"><?php echo __('Payment Successful', 'clever-gateway') ?></div></div>
		 	    <?php
			 	endif;
		 	}
		}

?>