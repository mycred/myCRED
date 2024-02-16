<?php
/**
 * myCred_Woo_Extend_Store_API class
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;

class myCred_Woo_Extend_Store_Endpoint {

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @var string
	 */
	const IDENTIFIER = 'mycredwoo';

	/**
	 * Initialize.
	 */
	public static function init() {
		if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
			return;
		}

		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => CartSchema::IDENTIFIER,
				'namespace'       => self::IDENTIFIER,
				'data_callback'   => array( 'myCred_Woo_Extend_Store_Endpoint', 'extend_checkout_block_data' ),
				'schema_callback' => array( 'myCred_Woo_Extend_Store_Endpoint', 'extend_checkout_block_schema' ),
				'schema_type'     => ARRAY_A,
			)
		);

	}

	/**
	 * Register myCred WooCommerce data into cart/checkout endpoint.
	 *
	 * @return array $item_data Registered data or empty array if condition is not satisfied.
	 */
	public static function extend_checkout_block_data() {

		if ( ! is_user_logged_in() ) {
			return array();
		}

		// Only available for logged in non-excluded users
		global $woocommerce;
		
		$cart_total = '';
		if ( isset( WC()->session->cart_totals ) && WC()->session->cart_totals ) {
			$cart_total = WC()->session->cart_totals['total'];
		}

		$available_gateways = $woocommerce->payment_gateways->get_available_payment_gateways();
		if ( ! isset( $available_gateways['mycred'] ) ) { 
			return array();
		}
		
		$point_type = $available_gateways['mycred']->get_option( 'point_type' );

		if ( $point_type === NULL ) {
			$point_type = MYCRED_DEFAULT_TYPE_KEY;
		}

		$mycred     = mycred( $point_type );
		$user_id    = get_current_user_id();

		// Nothing to do if we are excluded
		if ( $mycred->exclude_user( $user_id ) ) {
			return array();
		}

		$show_total     		= $available_gateways['mycred']->get_option( 'show_total' );
		$balance        		= $mycred->get_users_balance( $user_id, $point_type );
		$balance_label  		= $available_gateways['mycred']->get_option( 'balance_format' );
		$cost 					= '';
		$order_total_in_points	= '';

		$currency = get_woocommerce_currency();
		if ( ! mycred_point_type_exists( $currency ) && $currency != 'MYC' ) {
			if ( $cart_total ) {
				// Apply Exchange Rate
				$cost	= $mycred->number( ( $cart_total / $available_gateways['mycred']->get_option( 'exchange_rate' ) ) );
			}
			$order_total_in_points  = $mycred->template_tags_general( $available_gateways['mycred']->get_option( 'total_label' ) );
		}

		if ( $balance < $cost ) {
			$enabled = 'no';
		} else {
			$enabled = 'yes';
		}

		$item_data = array(
			'mycred_woo_total' 			=> $mycred->format_creds( $cost ),
			'mycred_woo_total_label' 	=> $order_total_in_points,
			'mycred_woo_balance' 		=> $mycred->format_creds( $balance ),
			'mycred_woo_balance_label'	=> $balance_label,
			'payment_gateway'			=> $enabled,
			
		);

		return $item_data;
	}

	/**
	 * Register myCred WooCommerce schema into cart/checkout endpoint.
	 *
	 * @return array Registered schema.
	 */
	public static function extend_checkout_block_schema() {
		return array(
			'mycred_woo_total'			=> array(
				'description' 			=> __( 'myCred WooCommerce order total.', 'mycred-woocommerce' ),
				'type'        			=> array( 'integer', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
			'mycred_woo_total_label'	=> array(
				'description' 			=> __( 'The label of myCred WooCommece total field', 'mycred-woocommerce' ),
				'type'        			=> array( 'string', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
			'mycred_woo_balance'		=> array(
				'description' 			=> __( 'The balance of myCred points', 'mycred-woocommerce' ),
				'type'					=> array( 'integer', 'null' ),
				'context'				=> array( 'view', 'edit' ),
				'readonly'				=> true,
			),
			'mycred_woo_balance_label'	=> array(
				'description' 			=> __( 'The label of myCred points balance field', 'mycred-woocommerce' ),
				'type'        			=> array( 'string', 'null' ),
				'context'     			=> array( 'view', 'edit' ),
				'readonly'    			=> true,
			),
		);
	}
}