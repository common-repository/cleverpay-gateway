<?php 

Class Clever_Rest_API {

	public function __construct(){
		add_action( 'rest_api_init', [$this, 'register_routes'] );
	}

	public function register_routes() {
	    register_rest_route( 'clever/v1', '/products', array(
	        'methods' => 'GET',
	        'callback' => [$this, 'get_products'],
	        'permission_callback' => '__return_true'
	    ) );

	    register_rest_route( 'clever/v1', '/categories', array(
	            'methods' => 'GET',
	            'callback' => [$this, 'get_categories'],
	            'permission_callback' => '__return_true'
        ) );

        register_rest_route( 'clever/v1', '/migrate-exclude-data', array(
	            'methods' => 'GET',
	            'callback' => [$this, 'migrate_exclude_data'],
	            'permission_callback' => '__return_true'
        ) );
	}


	public function get_products( $request ) {

		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$settings = $gateways['clever_gateway']->settings;
		$exclude = isset($settings['cashback_exclude_product_val']) ? explode(',', $settings['cashback_exclude_product_val']) : [];
 		
	    global $wpdb;
	    $page = $request->get_param( 'page' ) ?: 1; 
	    $per_page = $request->get_param( 'per_page' ) ?: 10; 
	    $where = ['t1.post_type = "product"'];
	    $search = $request->get_param( 'search' );
        if ( ! empty( $search ) ) {
        		$search = strtolower($search);
        		$where[] = "LOWER(t1.post_title) LIKE '%{$search}%' OR (t2.meta_key = '_sku' AND LOWER(t2.meta_value) LIKE '%{$search}%')";

        }

	    $offset = ($page - 1) * $per_page;
	    $where = implode(' AND ', $where);
	    $sql = "SELECT SQL_CALC_FOUND_ROWS t1.ID FROM {$wpdb->prefix}posts as t1 INNER JOIN {$wpdb->prefix}postmeta as t2 ON t1.ID = t2.post_id WHERE {$where} GROUP BY t1.ID LIMIT {$offset}, {$per_page}";
	    $product_ids = $wpdb->get_col($sql);
	    $total = $wpdb->get_var('SELECT FOUND_ROWS()');
	    $products = array();
	   	
	   	if($product_ids){
   			foreach ($product_ids as $key => $product_id) {
   				$product = wc_get_product($product_id);
   				if(!$product)
   					continue;
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
   				

   				$product_data = array(
   				    'id' => $product_id,
   				    'name' => $product->get_name(),
   				    'image_url' => $image_url ? $image_url : '',
   				    'status' => $product->get_status(),
   				    'type' => $product->get_type(), 
   				    'sku' => $product->get_sku(),
   				    'price' => $product->get_price(),
   				    'regular_price' => $product->get_regular_price(),
   				    'sale_price' => $product->get_sale_price(),
   				    'on_sale' => $product->is_on_sale(),
   				    'virtual' => $product->is_virtual(),
   				    'categories' => $category_array,
   				    'is_exclude' => in_array($product_id, $exclude)

   				);

   				$products[] = $product_data;
   			}
	   	}
	    
	    $response = new WP_REST_Response( $products );
	    $response->header( 'X-WP-Total', $total );
	    $response->header( 'X-WP-TotalPages', ceil($total/$per_page) );

	    return $response;
	}

	function get_categories( $request ) {
		global $wpdb;

		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$settings = $gateways['clever_gateway']->settings;
		$exclude = isset($settings['cashback_exclude_category_val']) ? explode(',', $settings['cashback_exclude_category_val']) : [];

	    $page = $request->get_param( 'page' ) ?: 1;
	    $per_page = $request->get_param( 'per_page' ) ?: 10;
	    $offset = ($page - 1) * $per_page;

	    $where = ["tt.taxonomy = 'product_cat'"];
	    $search = $request->get_param( 'search' );
        if ( ! empty( $search ) ) {
        		$search = strtolower($search);
	            $where[] = "LOWER(t.name) LIKE '%$search%'";
        }
	    $where = implode(' AND ', $where);
	    $sql = "SELECT SQL_CALC_FOUND_ROWS tt.term_id, t.name, t.slug, tt.parent AS term_name
				FROM {$wpdb->prefix}term_taxonomy AS tt
				JOIN {$wpdb->prefix}terms AS t ON tt.term_id = t.term_id
				WHERE $where LIMIT {$offset}, {$per_page}";
		$categories = $wpdb->get_results($sql);
	    $total = $wpdb->get_var('SELECT FOUND_ROWS()');



	    $response = array();
	    foreach ( $categories as $category ) {

	    	$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true ); 
	    	$image_url = '';
	    	if($thumbnail_id){
		    	$image_url = wp_get_attachment_url( $thumbnail_id );
	    	}
	        $response[] = array(
	            'id' => $category->term_id,
	            'name' => $category->name,
	            'slug' => $category->slug,
	            'parent' => isset($category->parent) ? $category->parent : '',
	            'image_url' => $image_url ? $image_url : '',
	            'is_exclude' => in_array($category->term_id, $exclude)
	        );
	    }


	    $response =  new WP_REST_Response( $response, 200 );
	    $response->header( 'X-WP-Total', $total );
	    $response->header( 'X-WP-TotalPages', ceil($total/$per_page) );


	    
	    return $response;
	}

	public function migrate_exclude_data(){

		$gateways = WC()->payment_gateways->get_available_payment_gateways();
		$settings = $gateways['clever_gateway']->settings;
		$product_ids = $settings['cashback_exclude_product_val'];
		$category_ids = $settings['cashback_exclude_category_val'];

		$data = [
			'product_ids' => trim($product_ids),
			'category_ids' => trim($category_ids)
		];

		return new WP_REST_Response( $data, 200 );

	}


}


 ?>