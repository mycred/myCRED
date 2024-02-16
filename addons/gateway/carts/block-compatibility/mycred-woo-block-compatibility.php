<?php
/**
 * myCred_Woo_Blocks_Compatibility class
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Blocks Compatibility.
 */
class myCred_Woo_Blocks_Compatibility {

	/**
	 * Initialize.
	 */
	public static function init() {
        
		if ( ! did_action( 'woocommerce_blocks_loaded' ) ) {
			return;
		}
    
		require_once myCRED_GATE_BLOCKS_DIR . 'mycred-woo-block-store-api.php';
        require_once myCRED_GATE_BLOCKS_DIR . 'mycred-woo-checkout-block-integration.php';
        require_once myCRED_GATE_BLOCKS_DIR . 'mycred-woo-cart-block-integration.php';

		myCred_Woo_Extend_Store_Endpoint::init();

        /**
         * Registers myCred WooCommerce Cart Block Integration.
         */
		add_action(
			'woocommerce_blocks_cart_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new myCred_Woo_Cart_Blocks_Integration() );
			}
		);

        /**
         * Registers myCred WooCommerce Checkout Block Integration.
         */
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function( $integration_registry ) {
                $integration_registry->register( new myCred_Woo_Checkout_Blocks_Integration() );
            }
        );

        /**
         * Registers myCred WooCommerce Payment Method.
         */
        if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            require_once myCRED_GATE_BLOCKS_DIR . 'mycred-woo-payment-method-integration.php';
            add_action(
                'woocommerce_blocks_payment_method_type_registration',
                static function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                    $payment_method_registry->register( new MyCred_Woo_Payment_Method() );
                }
            );
        }
	}
}

myCred_Woo_Blocks_Compatibility::init();
