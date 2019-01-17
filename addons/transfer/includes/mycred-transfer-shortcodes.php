<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Transfer Shortcode Render
 * Renders a transfer form that allows users to send points to other users.
 * @see http://mycred.me/functions/mycred_transfer_render/
 * @since 0.1
 * @version 1.6.3
 */
if ( ! function_exists( 'mycred_transfer_render' ) ) :
	function mycred_transfer_render( $atts, $content = NULL ) {

		global $mycred_do_transfer;

		// Settings
		$mycred  = mycred();
		$pref    = $mycred->transfers;

		// Get Attributes
		extract( shortcode_atts( array(
			'button'          => '',
			'pay_to'          => '',
			'show_balance'    => 0,
			'show_limit'      => 0,
			'ref'             => 'transfer',
			'amount'          => '',
			'placeholder'     => '',
			'types'           => $pref['types'],
			'excluded'        => '',
			'recipient_label' => __( 'Recipient', 'mycred' ),
			'amount_label'    => __( 'Amount', 'mycred' ),
			'balance_label'   => __( 'Balance', 'mycred' )
		), $atts ) );

		$output = '';

		if ( $ref == '' )
			$ref = 'transfer';

		// If we are not logged in
		if ( ! is_user_logged_in() ) {

			if ( isset( $pref['templates']['login'] ) && ! empty( $pref['templates']['login'] ) )
				$content .= '<p class="mycred-transfer-login">' . $mycred->template_tags_general( $pref['templates']['login'] ) . '</p>';

			return do_shortcode( $content );

		}

		$charge_from = get_current_user_id();

		// Point Types
		if ( ! is_array( $types ) )
			$raw = explode( ',', $types );
		else
			$raw = $types;

		$clean = array();
		foreach ( $raw as $id ) {

			$id = sanitize_key( $id );
			if ( $id != '' )
				$clean[] = sanitize_key( $id );

		}

		$available_types    = array();
		$available_balances = array();

		// Default
		if ( count( $clean ) == 1 ) {

			$point_type = $clean[0];
			if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $point_type );

			// Make sure user is not excluded
			if ( $mycred->exclude_user( $charge_from ) ) return '<div class="row"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><p>' . $excluded . '</p></div></div>';

			// See if we can send the lowest value
			$status     = mycred_user_can_transfer( $charge_from, $mycred->get_lowest_value(), $point_type, $ref );
			$balance    = $mycred->get_users_balance( $charge_from );

			// Error. Not enough creds
			if ( $status === 'low' ) {

				if ( isset( $pref['errors']['low'] )  && ! empty( $pref['errors']['low'] ) ) {
					$no_cred = str_replace( '%limit%', $pref['limit']['limit'], $pref['errors']['low'] );
					$no_cred = str_replace( '%Limit%', ucwords( $pref['limit']['limit'] ), $no_cred );
					$no_cred = str_replace( '%left%',  $mycred->format_creds( $status ), $no_cred );
					$output .= '<p class="mycred-transfer-low">' . $mycred->template_tags_general( $no_cred ) . '</p>';
				}

				return do_shortcode( $output );

			}

			// Error. Over limit
			if ( $status === 'limit' ) {

				if ( isset( $pref['errors']['over'] ) && ! empty( $pref['errors']['over'] ) ) {
					$no_cred = str_replace( '%limit%', $pref['limit']['limit'], $pref['errors']['over'] );
					$no_cred = str_replace( '%Limit%', ucwords( $pref['limit']['limit'] ), $no_cred );
					$no_cred = str_replace( '%left%',  $mycred->format_creds( $status ), $no_cred );
					$output .= '<p class="mycred-transfer-over">' . $mycred->template_tags_general( $no_cred ) . '</p>';
				}

				return do_shortcode( $output );

			}

			$available_types[ MYCRED_DEFAULT_TYPE_KEY ]    = $mycred->plural();
			$balance_template                              = $pref['templates']['balance'];
			$balance_template                              = str_replace( '%cred%', '<span class="mycred-balance-' . MYCRED_DEFAULT_TYPE_KEY . '">' . $balance . '</span>', $balance_template );
			$balance_template                              = str_replace( array( '%cred_f%', '%balance%' ), '<span class="mycred-balance-' . MYCRED_DEFAULT_TYPE_KEY . '">' . $mycred->format_creds( $balance ) . '</span>', $balance_template );
			$available_balances[ MYCRED_DEFAULT_TYPE_KEY ] = $mycred->template_tags_general( $balance_template );

		}

		// Multiple
		else {

			foreach ( $clean as $point_type ) {

				$points = mycred( $point_type );
				if ( $points->exclude_user( $charge_from ) ) continue;

				// See if we can send the lowest value
				$status = mycred_user_can_transfer( $charge_from, $points->get_lowest_value(), $point_type, $ref );
				if ( $status === 'low' || $status === 'limit' ) continue;

				$balance                           = $points->get_users_balance( $charge_from );

				$available_types[ $point_type ]    = $points->plural();
				$balance_template                  = $pref['templates']['balance'];
				$balance_template                  = str_replace( '%cred%', '<span class="mycred-balance-' . $point_type . '">' . $balance . '</span>', $balance_template );
				$balance_template                  = str_replace( array( '%cred_f%', '%balance%' ), '<span class="mycred-balance-' . $point_type . '">' . $points->format_creds( $balance ) . '</span>', $balance_template );
				$available_balances[ $point_type ] = $points->template_tags_general( $balance_template );

			}

			// User does not have access
			if ( count( $available_types ) == 0 )
				return '<div class="row"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><p>' . $excluded . '</p></div></div>';

		}

		// Flag for scripts & styles
		$mycred_do_transfer = true;

		// Placeholder
		if ( $placeholder == '' ) {

			if ( $pref['autofill'] == 'user_login' )
				$pln = __( 'username', 'mycred' );

			elseif ( $pref['autofill'] == 'user_email' )
				$pln = __( 'email', 'mycred' );

			$placeholder = sprintf( apply_filters( 'mycred_transfer_to_placeholder', __( 'recipients %s', 'mycred' ), $pref, $atts ), $pln );

		}

		// Recipient Input field
		$to_input = '<input type="text" name="mycred_new_transfer[recipient_id]" value="" aria-required="true" class="mycred-autofill form-control" data-form="' . $ref . '" placeholder="' . $placeholder . '" />';

		// If recipient is set, pre-populate it with the recipients details
		if ( $pay_to != '' ) {

			$pay_to = mycred_get_user_id( $pay_to );
			$user   = get_userdata( $pay_to );
			if ( $user !== false ) {

				$value = $user->display_name;
				if ( isset( $user->$pref['autofill'] ) )
					$value = $user->$pref['autofill'];

				$to_input = '<p class="form-control-static">' . $value . '</p><input type="hidden" name="mycred_new_transfer[recipient_id]" value="' . ( ( isset( $user->$pref['autofill'] ) ) ? $user->$pref['autofill'] : $pay_to ) . '" />';

			}

		}

		// Show Balance 
		$extras = array();
		if ( (bool) $show_balance && ! empty( $pref['templates']['balance'] ) ) {

			if ( ! empty( $available_balances ) ) {
				foreach ( $available_balances as $balance_type => $balance ) {
					$extras[] = $balance;
				}
			}

		}


		// Show Limits
		if ( (bool) $show_limit === true && ! empty( $pref['templates']['limit'] ) && $pref['limit']['limit'] != 'none' && count( $available_types ) == 1 ) {

			$limit_text = str_replace( '%_limit%', $pref['limit']['limit'], $pref['templates']['limit'] );
			$limit_text = str_replace( '%limit%',  ucwords( $pref['limit']['limit'] ), $limit_text );
			$limit_text = str_replace( '%left%',   $mycred->format_creds( $status ), $limit_text );
			$extras[]   = $mycred->template_tags_general( $limit_text );

		}

		if ( $button == '' )
			$button = $pref['templates']['button'];

		// Main output
		ob_start();

?>
<div class="mycred-transfer-cred-wrapper"<?php if ( $ref != '' ) echo ' id="transfer-form-' . $ref . '"'; ?>>
	<form class="form mycred-transfer mycred-transfer-form" id="mycred-transfer-form-<?php echo esc_attr( $ref ); ?>" method="post" data-ref="<?php echo esc_attr( $ref ); ?>" action="">

		<?php do_action( 'mycred_transfer_form_start', $atts, $pref ); ?>

		<div class="row">

			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group select-recipient-wrapper">
					<?php if ( $recipient_label != '' ) : ?><label><?php echo $recipient_label; ?>:</label><?php endif; ?>
					<?php echo $to_input; ?>
				</div>
				<?php do_action( 'mycred_transfer_form_to', $atts, $pref ); ?>
			</div>

<?php

		if ( count( $available_types ) == 1 ) {

?>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group select-amount-wrapper">
					<?php if ( $amount_label != '' ) : ?><label><?php echo $amount_label; ?>:</label><?php endif; ?>
					<?php if ( $amount == '' ) : ?>
					<input type="text" name="mycred_new_transfer[amount]" placeholder="<?php echo $mycred->get_lowest_value(); ?>" class="form-control" value="" />
					<?php else : ?>
					<input type="hidden" name="mycred_new_transfer[amount]" value="<?php echo $amount; ?>" />
					<p class="form-control-static"><?php echo $mycred->format_creds( $amount ); ?></p>
					<?php endif; ?>
					<input type="hidden" name="mycred_new_transfer[ctype]" value="<?php echo $clean[0]; ?>" />
				</div>
				<?php do_action( 'mycred_transfer_form_amount', $atts, $pref ); ?>
			</div>
<?php

		}

		else {

?>
			<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
				<div class="form-group select-amount-wrapper">
					<?php if ( $amount_label != '' ) : ?><label><?php echo $amount_label; ?>:</label><?php endif; ?>
					<?php if ( $amount == '' ) : ?>
					<input type="text" name="mycred_new_transfer[amount]" placeholder="<?php echo $mycred->get_lowest_value(); ?>" class="form-control" value="" />
					<?php else : ?>
					<input type="hidden" name="mycred_new_transfer[amount]" value="<?php echo $amount; ?>" />
					<p class="form-control-static"><?php echo $mycred->format_creds( $amount ); ?></p>
					<?php endif; ?>
				</div>
				<?php do_action( 'mycred_transfer_form_amount', $atts, $pref ); ?>
			</div>
			<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
				<div class="form-group select-point-type-wrapper">
					<?php if ( $balance_label != '' ) : ?><label><?php echo $balance_label; ?>:</label><?php endif; ?>
					<select name="mycred_new_transfer[ctype]" class="form-control">
					<?php foreach ( $available_types as $type => $plural ) echo '<option value="' . $type . '">' . $plural . '</option>'; ?>
					</select>
				</div>
			</div>
<?php

		}

?>

		</div>
<?php

		// Show extras
		if ( ! empty( $extras ) ) {

			$extras_to_show = count( $extras );
			$col = 'col-lg-12 col-md-12 col-sm-12 col-xs-12';
			if ( $extras_to_show == 2 )
				$col = 'col-lg-6 col-md-6 col-sm-12 col-xs-12';
			elseif ( $extras_to_show == 3 )
				$col = 'col-lg-4 col-md-4 col-sm-12 col-xs-12';
			elseif ( $extras_to_show == 4 )
				$col = 'col-lg-3 col-md-3 col-sm-12 col-xs-12';
			elseif ( $extras_to_show > 4 )
				$col = 'col-lg-2 col-md-2 col-sm-12 col-xs-12';

?>
			<div class="row">

				<?php foreach ( $extras as $extra_content ) { ?>

				<div class="<?php echo $col; ?>">
					<?php echo do_shortcode( $extra_content ); ?>
				</div>

				<?php } ?>

			</div>
<?php

		}

		do_action( 'mycred_transfer_form_extra', $atts, $pref );

?>
		<div class="row">

			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<input type="hidden" name="mycred_new_transfer[token]" value="<?php echo wp_create_nonce( 'mycred-new-transfer-' . $ref ); ?>" />
				<input type="hidden" name="mycred_new_transfer[reference]" value="<?php echo esc_attr( $ref ); ?>" />
				<input type="submit" class="btn btn-primary btn-block btn-lg mycred-submit-transfer" value="<?php echo esc_attr( $button ); ?>" />
			</div>

		</div>

		<?php do_action( 'mycred_transfer_form_end', $atts, $pref ); ?>

	</form>
</div>
<?php

		$output = ob_get_contents();
		ob_end_clean();

		return do_shortcode( apply_filters( 'mycred_transfer_render', $output, $atts, $mycred ) );

	}
endif;

?>