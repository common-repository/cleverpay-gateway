<!-- <div class="">Pay with your bank. The safest and fastest way to pay</div> -->


<?php 
	// $gateways = WC()->payment_gateways->get_available_payment_gateways();
	// $settings = $gateways['clever_gateway']->settings;
	$cart_items = [];
	foreach ( WC()->cart->get_cart() as $cart_item ){
		$product_id = $cart_item['product_id'];
		$product = wc_get_product($product_id);
		$image_url = get_the_post_thumbnail_url($product_id);
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
		// debug($cart_item)
		$cart_items[] = [
			'id' => $product_id,
			'name' => $product->get_name(),
			'type' => $product->get_type(),
			'quantity' => $cart_item['quantity'],
			'price' => $cart_item['line_total']/$cart_item['quantity'],
			'image_url' => $image_url ? $image_url : '',
			'sku' => $product->get_sku(),
			'price' => $product->get_price(),
			'virtual' => $product->is_virtual(),
			'categories' => $category_array,
			'sub_total' => $cart_item['line_subtotal'],
			'total' => $cart_item['line_total'],
			'total_tax' => $cart_item['line_tax'],
		];

	} 

	$cart_total = WC()->cart->cart_contents_total;
	$coupons = WC()->cart->get_applied_coupons();
	$coupon_lines = [];
	$currency = get_woocommerce_currency();
	if($coupons){
		foreach ($coupons as $key => $code) {
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
	$shipping_lines = [];
	if(isset(WC()->session->get('chosen_shipping_methods')[0]) &&  WC()->session->get('chosen_shipping_methods')[0]){
		$shipping_packages = WC()->shipping->get_packages();
		$chosen_shipping_method = WC()->session->get('chosen_shipping_methods')[0];

		if (!empty($shipping_packages)) {

		    foreach ($shipping_packages[0]['rates'] as $key => $shipping_package) {
		        if ($chosen_shipping_method === $key) {
                	$shipping_lines = [
                		'id' => $shipping_package->get_id(),
                		'method_id' => $shipping_package->get_method_id(),
                		'title' => $shipping_package->get_label(),
                		'cost' => $shipping_package->get_cost(),
                		'taxes' => $shipping_package->get_taxes(),
                	];
		            break;
		        }
		    }
		}
	}

	$fee_lines = [];
	$fees = WC()->cart->get_fees();
	if($fees){
		$fee_lines = $fees;
	}
	


?>
<div class="clever-method-description">
	<input type="hidden" value="<?php echo $cart_total  ?>" class="cl-cart-total">
	<input type="hidden" id="clever_cart_items" data-json='<?php echo json_encode(['order_details' => ['items' => $cart_items, 'coupon_lines' => $coupon_lines, 'shipping_lines' => $shipping_lines, 'fee_lines' => $fee_lines], 'total_amount' => $cart_total, 'currency' => $currency]) ?>'>
	<!-- <div class="cl-des-top-item">Pay with your bank. The safest and fastest way to pay. <span class="off-m">Earn <span class="cl-money">$<span class="cb_value">0.00</span></span> instant cashback. </span></div> -->
	<div class="cl-des-top-item no-cashback off-m"><b>Shop and pay with Hello Clever</b></div>
	<div class="cl-des-top-item is-cashback off-m"><b>Shop, pay and earn cashback with Hello Clever</b></div>

	<div class="cl-des-item"> <img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/sc_bank.svg" alt=""> <span>Pay securely with your debit/credit cards or PayID.</span></div>

	<div class="cl-des-item off-m cb-percentage"> <img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/sc_clever.svg" alt=""> <span class="cb-content"></span></div>
	<div class="cl-des-item off-m cb-absolute"> <img src="<?php echo CLEVER_GATEWAY_URL ?>/public/images/sc_clever.svg" alt=""> <span class="cb-content"></span></div>

</div>