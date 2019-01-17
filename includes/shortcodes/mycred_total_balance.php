<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Shortcode: mycred_total_balance
 * This shortcode will return either the current user or a given users
 * total balance based on either all point types or a comma seperated list
 * of types.
 * @see http://codex.mycred.me/shortcodes/mycred_total_balance/
 * @since 1.4.3
 * @version 1.2.2
 */
if ( ! function_exists( 'mycred_render_shortcode_total' ) ) :
	function mycred_render_shortcode_total( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'user_id' => 'current',
			'types'   => MYCRED_DEFAULT_TYPE_KEY,
			'raw'     => 0,
			'total'   => 0
		), $atts ) );

		// If user ID is not set, get the current users ID
		if ( ! is_user_logged_in() && $user_id == 'current' )
			return $content;

		$user_id = mycred_get_user_id( $user_id );

		// Get types
		$types_to_addup = array();
		$all = false;
		$existing_types = mycred_get_types();

		if ( $types == 'all' )
			$types_to_addup = array_keys( $existing_types );

		else {

			$types = explode( ',', $types );
			if ( ! empty( $types ) ) {
				foreach ( $types as $type_key ) {
					$type_key = sanitize_text_field( $type_key );
					if ( ! array_key_exists( $type_key, $existing_types ) ) continue;

					if ( ! in_array( $type_key, $types_to_addup ) )
						$types_to_addup[] = $type_key;
				}
			}

		}

		// In case we still have no types, we add the default one
		if ( empty( $types_to_addup ) )
			$types_to_addup = array( MYCRED_DEFAULT_TYPE_KEY );

		// Add up all point type balances
		$total_balance = 0;
		foreach ( $types_to_addup as $type ) {

			// Get the balance for this type
			$mycred = mycred( $type );
			if ( $total == 1 )
				$balance = mycred_query_users_total( $user_id, $type );
			else
				$balance = $mycred->get_users_balance( $user_id, $type );

			$total_balance = $total_balance+$balance;

		}

		// If results should be formatted
		if ( $raw == 0 ) {

			$mycred = mycred();
			$total_balance = $mycred->format_number( $total_balance );

		}

		return apply_filters( 'mycred_total_balances_output', $total_balance, $atts );

	}
endif;
add_shortcode( 'mycred_total_balance', 'mycred_render_shortcode_total' );
