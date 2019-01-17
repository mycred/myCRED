<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * User Can Transfer
 * @see http://mycred.me/functions/mycred_user_can_transfer/
 * @param $user_id (int) requred user id
 * @param $amount (int) optional amount to check against balance
 * @returns true if no limit is set, 'limit' (string) if user is over limit else the amount of creds left
 * @filter 'mycred_user_can_transfer'
 * @filter 'mycred_transfer_limit'
 * @filter 'mycred_transfer_acc_limit'
 * @since 0.1
 * @version 1.4.1
 */
if ( ! function_exists( 'mycred_user_can_transfer' ) ) :
	function mycred_user_can_transfer( $user_id = NULL, $amount = NULL, $type = MYCRED_DEFAULT_TYPE_KEY, $reference = NULL ) {

		if ( $user_id === NULL )
			$user_id = get_current_user_id();

		if ( $reference === NULL )
			$reference = 'transfer';

		if ( ! mycred_point_type_exists( $type ) )
			$type = MYCRED_DEFAULT_TYPE_KEY;

		// Grab Settings
		$settings = mycred_get_addon_settings( 'transfers' );
		$mycred   = mycred( $type );
		$zero     = $mycred->zero();

		// Get users balance
		$balance  = $mycred->get_users_balance( $user_id, $type );

		// Get Transfer Max
		$max      = apply_filters( 'mycred_transfer_limit', $mycred->number( $settings['limit']['amount'] ), $user_id, $amount, $settings, $reference );

		// If an amount is given, deduct this amount to see if the transaction
		// brings us over the account limit
		if ( $amount !== NULL )
			$balance = $mycred->number( $balance - $amount );

		// Zero
		// The lowest amount a user can have on their account. By default, this
		// is zero. But you can override this via the mycred_transfer_acc_limit hook.
		$account_limit = $mycred->number( apply_filters( 'mycred_transfer_acc_limit', $zero, $type, $user_id, $reference ) );

		// Check if we would go minus
		if ( $balance < $account_limit ) return 'low';

		// If there are no limits, return the current balance
		if ( $settings['limit']['limit'] == 'none' ) return $balance;

		// Else we have a limit to impose
		$now = current_time( 'timestamp' );
		$max = $mycred->number( $settings['limit']['amount'] );

		// Daily limit
		if ( $settings['limit']['limit'] == 'daily' )
			$total = mycred_get_total_by_time( 'today', 'now', $reference, $user_id, $type );

		// Weekly limit
		elseif ( $settings['limit']['limit'] == 'weekly' ) {
			$this_week = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ) - date( 'n', $now ) + 1 );
			$total     = mycred_get_total_by_time( $this_week, 'now', $reference, $user_id, $type );
		}

		// Custom limits will need to return the result
		// here and now. Accepted answers are 'limit', 'low' or the amount left on limit.
		else {
			return apply_filters( 'mycred_user_can_transfer', 'limit', $user_id, $amount, $settings, $reference );
		}

		// We are adding up point deducations.
		$total = abs( $total );

		if ( $amount !== NULL ) {

			$total = $mycred->number( $total + $amount );

			// Transfer limit reached
			if ( $total > $max ) return 'limit';

		}

		else {

			// Transfer limit reached
			if ( $total >= $max ) return 'limit';

		}

		// Return whats remaining of limit
		return $mycred->number( $max - $total );

	}
endif;

/**
 * New Transfer
 * @since 1.7.6
 * @version 1.0.2
 */
if ( ! function_exists( 'mycred_new_transfer' ) ) :
	function mycred_new_transfer( $request = array() ) {

		$request = apply_filters( 'mycred_new_transfer_args', shortcode_atts( array(
			'transaction_id' => NULL,
			'sender_id'      => NULL,
			'recipient_id'   => NULL,
			'reference'      => 'transfer',
			'charge'         => NULL,
			'payout'         => NULL,
			'point_type'     => MYCRED_DEFAULT_TYPE_KEY,
			'data'           => ''
		), $request ) );

		extract( $request );

		if ( $transaction_id === NULL || $sender_id === NULL || $recipient_id === NULL || $charge === NULL || $payout === NULL ) return 'error_9';

		$point_type    = sanitize_key( $point_type );
		$settings      = mycred_get_addon_settings( 'transfers' );
		$mycred        = mycred( $point_type );

		$result        = 'error_9';

		if ( $mycred->exclude_user( $sender_id ) )
			return 'error_4';

		// The recipient is excluded from the point type
		if ( $mycred->exclude_user( $recipient_id ) )
			return 'error_4';

		// Check if we can complete this transaction before we run it
		$attempt_check = mycred_user_can_transfer( $sender_id, $charge, $point_type, $reference );

		// Insufficient funds
		if ( $attempt_check === 'low' )
			return 'error_7';

		// Limit reached
		if ( $attempt_check === 'limit' )
			return 'error_8';

		// Prevent Duplicate transactions
		if ( $mycred->has_entry( $reference, $recipient_id, $sender_id, $data, $point_type ) )
			return 'error_9';

		// Let others play before we execute the transfer
		do_action( 'mycred_transfer_ready', $transaction_id, $request, $settings );

		// First take the amount from the sender
		if ( $mycred->add_creds(
			$reference,
			$sender_id,
			0 - $charge,
			$settings['logs']['sending'],
			$recipient_id,
			$data,
			$point_type
		) ) {

			// Then add the amount to the receipient
			if ( ! $mycred->has_entry( $reference, $sender_id, $recipient_id, $data, $point_type ) ) {

				$mycred->add_creds(
					$reference,
					$recipient_id,
					$payout,
					$settings['logs']['receiving'],
					$sender_id,
					$data,
					$point_type
				);

				// Let others play once transaction is completed
				do_action( 'mycred_transfer_completed', $transaction_id, $request, $settings );

				// Return the good news
				$result = array(
					'amount'  => $payout,
					'css'     => '.mycred-balance-' . $point_type,
					'balance' => $mycred->format_creds( $attempt_check ),
					'zero'    => ( ( $attempt_check <= $mycred->zero() ) ? true : false )
				);

			}

		}

		return apply_filters( 'mycred_new_transfer', $result, $request, $attempt_check );

	}
endif;

/**
 * Get Users Transfer History
 * @since 1.3.3
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_users_transfer_history' ) ) :
	function mycred_get_users_transfer_history( $user_id, $type = MYCRED_DEFAULT_TYPE_KEY, $key = NULL ) {

		if ( $key === NULL )
			$key = 'mycred_transactions';

		if ( $type != MYCRED_DEFAULT_TYPE_KEY && $type != '' )
			$key .= '_' . $type;

		$default = array(
			'frame'  => '',
			'amount' => 0
		);
		return mycred_apply_defaults( $default, mycred_get_user_meta( $user_id, $key, '', true ) );

	}
endif;

/**
 * Render Transfer Message
 * @since 1.7.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_transfer_render_message' ) ) :
	function mycred_transfer_render_message( $original = '', $data = array() ) {

		if ( empty( $original ) || empty( $data ) ) return $original;

		// Default message
		$message = apply_filters( 'mycred_transfer_default_message', '-', $original, $data );

		// Get saved message
		if ( ! empty( $data ) && array_key_exists( 'message', $data ) && ! empty( $data['message'] ) )
			$message = $data['message'];

		$content = str_replace( '%transfer_message%', $message, $original );

		return apply_filters( 'mycred_transfer_message', $content, $original, $message, $data );

	}
endif;

/**
 * Update Users Transfer History
 * @since 1.3.3
 * @version 1.0
 */
if ( ! function_exists( 'mycred_update_users_transfer_history' ) ) :
	function mycred_update_users_transfer_history( $user_id, $history, $type = MYCRED_DEFAULT_TYPE_KEY, $key = NULL ) {

		if ( $key === NULL )
			$key = 'mycred_transactions';

		if ( $type != MYCRED_DEFAULT_TYPE_KEY && $type != '' )
			$key .= '_' . $type;

		// Get current history
		$current = mycred_get_users_transfer_history( $user_id, $type, $key );

		// Reset
		if ( $history === true )
			$new_history = array(
				'frame'  => '',
				'amount' => 0
			);

		// Update
		else $new_history = mycred_apply_defaults( $current, $history );

		mycred_update_user_meta( $user_id, $key, '', $new_history );

	}
endif;
