<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use Automattic\WooCommerce\StoreApi\Payments\PaymentResult;

final class MyCred_Woo_Payment_Method extends AbstractPaymentMethodType {

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'mycred';

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_myCRED
	 */
	private $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_mycred_settings', array() );
		$this->gateway  = new WC_Gateway_myCRED();
		add_action( 'woocommerce_thankyou_mycred', array( $this, 'thankyou_page' ) );
	}

	/**
	 * Thank You Page
	 * @since 0.1
	 * @version 1.0
	 */
	public function thankyou_page() {
		$thankyou_msg = apply_filters( 'mycred_woo_thank_you_message', '<p>' . __( 'Your account has successfully been charged.', 'mycred' ) . '</p>' );
		echo wp_kses_post( $thankyou_msg );
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_asset_path = plugins_url( '/build/payment/payment-method.asset.php', __FILE__ );
		$script_asset      = file_exists( $script_asset_path )
			? require $script_asset_path
			: array(
				'dependencies' => array(),
				'version'      => myCRED_GATE_VERSION,
			);

		$script_url = plugins_url( '/build/payment/payment-method.js', __FILE__ );

		wp_register_script(
			'mycred-woo-payment-method',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'mycred-woo-payment-method', 'mycred' );
		}

		return array( 'mycred-woo-payment-method' );
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		$point_type = $this->gateway->get_option( 'point_type' );
		$order_total_label = $this->gateway->get_option( 'total_label' );
		$balance_label = $this->gateway->get_option( 'balance_format' );
		$mycred = mycred( $point_type );
		return [
			'title'       		=> $this->get_setting( 'title' ),
			'description' 		=> $this->get_setting( 'description' ),
			'supports'    		=> array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),
			'order_total'  		=> $mycred->format_creds( "49.20" ),
			'order_total_label'	=> $order_total_label,
			'balance'			=> $mycred->format_creds( "550" ),
			'balance_label'		=> $balance_label,
		];
	}
}
