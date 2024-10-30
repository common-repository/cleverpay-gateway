<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://helloclever.co/
 * @since      1.8.0
 *
 * @package    Clever_Gateway
 * @subpackage Clever_Gateway/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Clever_Gateway
 * @subpackage Clever_Gateway/public
 * @author     Hello Clever PTY LTD <support@helloclever.co>
 */
class Clever_Gateway_Public {

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		add_action( 'wp_footer', [$this, 'wp_footer'], 9999 );
		add_action( 'woocommerce_checkout_billing', [$this, 'woocommerce_checkout_billing'] );
		add_action( 'woocommerce_checkout_create_order', [$this, 'woocommerce_checkout_create_order'], 20, 1 );

		// Start session on init hook.
		// add_action( 'init', function () {
		// 	if ( !session_id() ) {
		// 		session_start();
		// 	}
		// });
	}


	public function woocommerce_checkout_create_order($order){

        if (isset($_POST['ct_token'])) {
            $ct_token = $_POST['ct_token'];
            if (!empty($ct_token)){
              $order->update_meta_data('_ct_token', $ct_token);
            } 
        }
	}

	public function woocommerce_checkout_billing(){
		 
        woocommerce_form_field( 'ct_token', array(
            'type'          => 'hidden',
            'class'         => array( 'ct_token' ),
            'label'         => __( '' ),
            'placeholder'   => __( '' ),
          ), '');
	}

	// public function woocommerce_checkout_order_processed($order_id, $posted_data, $order){
	// 	$ct_token = get_post_meta($order_id, '_ct_token', true);
	// }

	function wp_footer() {

	   global $wp;
	   if(is_checkout() || is_product())
	       include CLEVER_GATEWAY_PATH . '/public/partials/loading.php';

	//    if ( is_checkout() && empty( $wp->query_vars['order-pay'] ) && ! isset( $wp->query_vars['order-received'] ) ) {
	//     //   include CLEVER_GATEWAY_PATH . '/public/partials/popup.php';
	//    }

	   if(is_product()){
	      include CLEVER_GATEWAY_PATH . '/public/partials/express-checkout-popup.php';

	   }
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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
		if(is_checkout() || is_product())
			wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/clever-gateway-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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
		if(is_checkout() || is_product()){
			wp_enqueue_script( $this->plugin_name . '-micro-modal', plugin_dir_url( __FILE__ ) . 'js/micromodal.min.js', array( 'jquery' ), $this->version, false );
			// wp_enqueue_script( $this->plugin_name . '-tracking',  plugin_dir_url( __FILE__ ) . 'js/tracking.js', array( 'jquery' ), time(), false );
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/clever-gateway-public.js', array( 'jquery' ), time(), false );
	}
}
