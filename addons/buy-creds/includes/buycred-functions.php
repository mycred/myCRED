<?php
if ( ! defined( 'MYCRED_PURCHASE' ) ) exit;

/**
 * Get Pending Payment
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'buycred_get_pending_payment_id' ) ) :
	function buycred_get_pending_payment_id( $payment_id = NULL ) {

		if ( $payment_id === NULL || $payment_id == '' ) return false;

		// In case we are using the transaction ID instead of the post ID.
		$post_id = false;
		if ( ! is_numeric( $payment_id ) ) {

			$post = get_page_by_title( strtoupper( $payment_id ), OBJECT, 'buycred_payment' );
			if ( $post === NULL ) return false;

			$post_id = $post->ID;

		}
		else {
			$post_id = absint( $payment_id );
		}

		return $post_id;

	}
endif;

/**
 * Get Pending Payment
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'buycred_get_pending_payment' ) ) :
	function buycred_get_pending_payment( $payment_id = NULL ) {

		// Construct fake pending object ( when no pending payment object exists )
		if ( is_array( $payment_id ) ) {

			$pending_payment                 = new StdClass();
			$pending_payment->payment_id     = false;
			$pending_payment->public_id      = $payment_id['public_id'];
			$pending_payment->point_type     = $payment_id['point_type'];
			$pending_payment->amount         = $payment_id['amount'];
			$pending_payment->cost           = $payment_id['cost'];
			$pending_payment->currency       = $payment_id['currency'];
			$pending_payment->buyer_id       = $payment_id['buyer_id'];
			$pending_payment->recipient_id   = $payment_id['recipient_id'];
			$pending_payment->gateway_id     = $payment_id['gateway_id'];
			$pending_payment->transaction_id = $payment_id['transaction_id'];
			$pending_payment->cancel_url     = false;
			$pending_payment->pay_now_url    = false;

		}

		else {

			$payment_id = buycred_get_pending_payment_id( $payment_id );

			if ( $payment_id === false ) return false;

			$pending_payment                 = new StdClass();
			$pending_payment->payment_id     = absint( $payment_id );
			$pending_payment->public_id      = get_the_title( $payment_id );
			$pending_payment->point_type     = get_post_meta( $payment_id, 'point_type', true );
			$pending_payment->amount         = get_post_meta( $payment_id, 'amount', true );
			$pending_payment->cost           = get_post_meta( $payment_id, 'cost', true );
			$pending_payment->currency       = get_post_meta( $payment_id, 'currency', true );
			$pending_payment->buyer_id       = get_post_meta( $payment_id, 'from', true );
			$pending_payment->recipient_id   = get_post_meta( $payment_id, 'to', true );
			$pending_payment->gateway_id     = get_post_meta( $payment_id, 'gateway', true );
			$pending_payment->transaction_id = get_the_title( $payment_id );

			$pending_payment->cancel_url     = buycred_get_cancel_transaction_url( $pending_payment->public_id );

			$pending_payment->pay_now_url    = add_query_arg( array(
				'mycred_buy' => $pending_payment->gateway_id,
				'amount'     => $pending_payment->amount,
				'revisit'    => $payment_id,
				'token'      => wp_create_nonce( 'mycred-buy-creds' )
			), set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) );

		}

		return apply_filters( 'buycred_get_pending_payment', $pending_payment, $payment_id );

	}
endif;

/**
 * Add Pending Comment
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'buycred_add_pending_comment' ) ) :
	function buycred_add_pending_comment( $payment_id = NULL, $comment = NULL, $time = NULL ) {

		if ( MYCRED_BUYCRED_PENDING_COMMENTS === false ) return true;

		$post_id = buycred_get_pending_payment_id( $payment_id );
		if ( $post_id === false ) return false;

		global $mycred_modules;

		if ( $time === NULL || $time == 'now' )
			$time = current_time( 'mysql' );

		$author       = 'buyCRED';
		$gateway      = get_post_meta( $post_id, 'gateway', true );
		$gateways     = $mycred_modules['solo']['buycred']->get();
		$author_url   = sprintf( 'buyCRED: %s %s', __( 'Unknown Gateway', 'mycred' ), $gateway );
		$author_email = apply_filters( 'mycred_buycred_comment_email', 'buycred-service@mycred.me' );

		if ( array_key_exists( $gateway, $gateways ) )
			$author = sprintf( 'buyCRED: %s %s', $gateways[ $gateway ]['title'], __( 'Gateway', 'mycred' ) );

		return wp_insert_comment( array(
			'comment_post_ID'      => $post_id,
			'comment_author'       => $author,
			'comment_author_email' => $author_email,
			'comment_content'      => $comment,
			'comment_type'         => 'comment',
			'comment_author_IP'    => $_SERVER['REMOTE_ADDR'],
			'comment_date'         => $time,
			'comment_approved'     => 1,
			'user_id'              => 0
		) );

	}
endif;

/**
 * Get Cancel URL
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'buycred_get_cancel_transaction_url' ) ) :
	function buycred_get_cancel_transaction_url( $transaction_id = NULL ) {

		$mycred = mycred();
		$base   = get_bloginfo( 'url' );

		// Cancel page
		if ( $mycred->buy_creds['cancelled']['use'] == 'page' ) {
			if ( ! empty( $mycred->buy_creds['cancelled']['page'] ) )
				$base = get_permalink( $mycred->buy_creds['cancelled']['page'] );
		}

		// Custom URL
		else {
			$base = get_bloginfo( 'url' ) . '/' . $mycred->buy_creds['cancelled']['custom'];
		}

		// Override
		if ( isset( $_REQUEST['return_to'] ) && esc_url_raw( $_REQUEST['return_to'] ) != '' )
			$base = esc_url_raw( $_REQUEST['return_to'] );

		if ( $transaction_id !== NULL )
			$url = add_query_arg( array( 'buycred-cancel' => $transaction_id, '_token' => wp_create_nonce( 'buycred-cancel-pending-payment' ) ), $base );
		else
			$url = $base;

		return apply_filters( 'mycred_buycred_cancel_url', $url, $transaction_id );

	}
endif;

	


/**
 * Get Users Pending Payments
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'buycred_get_users_pending_payments' ) ) :
	function buycred_get_users_pending_payments( $user_id = NULL, $point_type = NULL ) {

		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return false;

		$pending = get_user_meta( $user_id, 'buycred_pending_payments', true );
		if ( $pending == '' ) {

			global $wpdb;

			$pending = array();
			$saved   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->posts} posts WHERE posts.post_type = 'buycred_payment' AND posts.post_author = %d AND posts.post_status = 'publish';", $user_id ) );
			if ( ! empty( $saved ) ) {

				foreach ( $saved as $entry ) {

					$point_type = get_post_meta( $entry->ID, 'point_type', true );
					if ( $point_type == '' ) $point_type = MYCRED_DEFAULT_TYPE_KEY;

					if ( ! array_key_exists( $point_type, $pending ) )
						$pending[ $point_type ] = array();

					$pending[ $point_type ][] = buycred_get_pending_payment( (int) $entry->ID );

				}

				add_user_meta( $user_id, 'buycred_pending_payments', $pending, true );

			}

		}

		if ( $point_type !== NULL && mycred_point_type_exists( $point_type ) ) {

			if ( ! array_key_exists( $point_type, $pending ) )
				return false;

			return $pending[ $point_type ];

		}

		return $pending;

	}
endif;

/**
 * buyCRED Gateway Constructor
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'buycred_gateway' ) ) :
	function buycred_gateway( $gateway_id = NULL ) {

		global $mycred_modules;

		$gateway   = false;
		$installed = $mycred_modules['solo']['buycred']->get();

		if ( array_key_exists( $gateway_id, $installed ) ) {

			$class = $installed[ $gateway_id ]['callback'][0];

			// Construct Gateway
			$gateway = new $class( $mycred_modules['solo']['buycred']->gateway_prefs );

		}

		return $gateway;

	}
endif;

/**
 * Delete Pending Payment
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'buycred_trash_pending_payment' ) ) :
	function buycred_trash_pending_payment( $payment_id = NULL ) {

		$pending_payment = buycred_get_pending_payment( $payment_id );
		if ( $pending_payment === false ) return false;

		delete_user_meta( $pending_payment->buyer_id, 'buycred_pending_payments' );

		return wp_trash_post( $pending_payment->payment_id );

	}
endif;

/**
 * Complete Pending Payment
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'buycred_complete_pending_payment' ) ) :
	function buycred_complete_pending_payment( $pending_id ) {

		$pending_payment = buycred_get_pending_payment( $pending_id );
		if ( $pending_payment === false ) return false;

		$gateway = buycred_gateway( $pending_payment->gateway_id );
		if ( $gateway === false ) return false;

		// Complete Payment
		$paid = $gateway->complete_payment( $pending_payment, $pending_payment->transaction_id );

		if ( $paid )
			return buycred_trash_pending_payment( $pending_id );

		return $paid;

	}
endif;
