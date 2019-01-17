<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Skrill class
 * Skrill (Moneybookers) - Payment Gateway
 * @since 0.1
 * @version 1.1.1
 */
if ( ! class_exists( 'myCRED_Skrill' ) ) :
	class myCRED_Skrill extends myCRED_Payment_Gateway {

		/**
		 * Construct
		 */
		function __construct( $gateway_prefs ) {

			$types = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label )
				$default_exchange[ $type ] = 1;

			parent::__construct( array(
				'id'               => 'skrill',
				'label'            => 'Skrill Payment',
				'gateway_logo_url' => plugins_url( 'assets/images/skrill.png', MYCRED_PURCHASE ),
				'defaults' => array(
					'sandbox'           => 0,
					'currency'          => '',
					'account'           => '',
					'word'              => '',
					'account_title'     => '',
					'account_logo'      => '',
					'confirmation_note' => '',
					'email_receipt'     => 0,
					'item_name'         => 'Purchase of myCRED %plural%',
					'exchange'          => $default_exchange
				)
			), $gateway_prefs );

		}

		/**
		 * Adjust Currencies
		 * @since 1.0.6
		 * @version 1.0
		 */
		public function skrill_currencies( $currencies ) {

			$currencies['RON'] = 'Romanian Leu';
			$currencies['TRY'] = 'New Turkish Lira';
			$currencies['RON'] = 'Romanian Leu';
			$currencies['AED'] = 'Utd. Arab Emir. Dirham';
			$currencies['MAD'] = 'Moroccan Dirham';
			$currencies['QAR'] = 'Qatari Rial';
			$currencies['SAR'] = 'Saudi Riyal';
			$currencies['SKK'] = 'Slovakian Koruna';
			$currencies['EEK'] = 'Estonian Kroon';
			$currencies['BGN'] = 'Bulgarian Leva';
			$currencies['ISK'] = 'Iceland Krona';
			$currencies['INR'] = 'Indian Rupee';
			$currencies['LVL'] = 'Latvian Lat';
			$currencies['KRW'] = 'South-Korean Won';
			$currencies['ZAR'] = 'South-African Rand';
			$currencies['HRK'] = 'Croatian Kuna';
			$currencies['LTL'] = 'Lithuanian Litas';
			$currencies['JOD'] = 'Jordanian Dinar';
			$currencies['OMR'] = 'Omani Rial';
			$currencies['RSD'] = 'Serbian Dinar';
			$currencies['TND'] = 'Tunisian Dinar';

			unset( $currencies['MXN'] );
			unset( $currencies['BRL'] );
			unset( $currencies['PHP'] );

			return $currencies;

		}

		/**
		 * IPN - Is Valid Call
		 * Replaces the default check
		 * @since 1.4
		 * @version 1.1
		 */
		public function IPN_is_valid_call() {

			$result = true;

			$check = $_POST['merchant_id'] . $_POST['transaction_id'] . strtoupper( md5( $this->prefs['word'] ) ) . $_POST['mb_amount'] . $_POST['mb_currency'] . $_POST['status'];
			if ( strtoupper( md5( $check ) ) !== $_POST['md5sig'] )
				$result = false;

			if ( $_POST['pay_to_email'] != trim( $this->prefs['account'] ) )
				$result = false;

			return $result;

		}

		/**
		 * Process Handler
		 * @since 0.1
		 * @version 1.2
		 */
		public function process() {

			// Required fields
			if ( isset( $_POST['sales_data'] ) && isset( $_POST['transaction_id'] ) && isset( $_POST['amount'] ) ) {

				// Get Pending Payment
				$pending_post_id = sanitize_key( $_POST['sales_data'] );
				$pending_payment = $this->get_pending_payment( $pending_post_id );
				if ( $pending_payment !== false ) {

					// Verify Call with PayPal
					if ( $this->IPN_is_valid_call() ) {

						$errors   = false;
						$new_call = array();

						// Check amount paid
						if ( $_POST['amount'] != $pending_payment->cost ) {
							$new_call[] = sprintf( __( 'Price mismatch. Expected: %s Received: %s', 'mycred' ), $pending_payment->cost, $_POST['amount'] );
							$errors     = true;
						}

						// Check currency
						if ( $_POST['currency'] != $pending_payment->currency ) {
							$new_call[] = sprintf( __( 'Currency mismatch. Expected: %s Received: %s', 'mycred' ), $pending_payment->currency, $_POST['currency'] );
							$errors     = true;
						}

						// Check status
						if ( $_POST['status'] != '2' ) {
							$new_call[] = sprintf( __( 'Payment not completed. Received: %s', 'mycred' ), $_POST['status'] );
							$errors     = true;
						}

						// Credit payment
						if ( $errors === false ) {

							// If account is credited, delete the post and it's comments.
							if ( $this->complete_payment( $pending_payment, $_POST['transaction_id'] ) )
								$this->trash_pending_payment( $pending_post_id );
							else
								$new_call[] = __( 'Failed to credit users account.', 'mycred' );

						}

						// Log Call
						if ( ! empty( $new_call ) )
							$this->log_call( $pending_post_id, $new_call );

					}

				}

			}

		}

		/**
		 * Results Handler
		 * @since 0.1
		 * @version 1.1
		 */
		public function returning() {

			if ( isset( $_GET['transaction_id'] ) && ! empty( $_GET['transaction_id'] ) && isset( $_GET['msid'] ) && ! empty( $_GET['msid'] ) ) {
				$this->get_page_header( __( 'Success', 'mycred' ), $this->get_thankyou() );
				echo '<h1>' . __( 'Thank you for your purchase', 'mycred' ) . '</h1>';
				$this->get_page_footer();
				exit;
			}

		}

		/**
		 * Buy Handler
		 * @since 0.1
		 * @version 1.2
		 */
		public function buy() {

			if ( ! isset( $this->prefs['account'] ) || empty( $this->prefs['account'] ) ) wp_die( __( 'Please setup this gateway before attempting to make a purchase!', 'mycred' ) );

			// Location
			$location     = 'https://www.moneybookers.com/app/payment.pl';

			// Prep
			$type         = $this->get_point_type();
			$mycred       = mycred( $type );

			$amount       = $mycred->number( $_REQUEST['amount'] );
			$amount       = abs( $amount );

			$cost         = $this->get_cost( $amount, $type );
			$to           = $this->get_to();
			$from         = get_current_user_id();
			$thankyou_url = $this->get_thankyou();

			// Item Name
			$item_name    = str_replace( '%number%', $amount, $this->prefs['item_name'] );
			$item_name    = $mycred->template_tags_general( $item_name );

			// Revisiting pending payment
			if ( isset( $_REQUEST['revisit'] ) )
				$this->transaction_id = strtoupper( sanitize_text_field( $_REQUEST['revisit'] ) );

			// New pending payment
			else {
				$post_id              = $this->add_pending_payment( array( $to, $from, $amount, $cost, $this->prefs['currency'], $type ) );
				$this->transaction_id = get_the_title( $post_id );
			}

			$cancel_url = $this->get_cancelled( $this->transaction_id );

			// Start constructing merchant details
			$hidden_fields = array(
				'pay_to_email'    => $this->prefs['account'],
				'transaction_id'  => $this->transaction_id,
				'return_url'      => $thankyou_url,
				'cancel_url'      => $cancel_url,
				'status_url'      => $this->callback_url(),
				'return_url_text' => __( 'Return to ', 'mycred' ) . get_bloginfo( 'name' ),
				'hide_login'      => 1
			);

			// Customize Checkout Page
			if ( isset( $this->prefs['account_title'] ) && ! empty( $this->prefs['account_title'] ) )
				$hidden_fields = array_merge_recursive( $hidden_fields, array(
					'recipient_description' => $mycred->template_tags_general( $this->prefs['account_title'] )
				) );

			if ( isset( $this->prefs['account_logo'] ) && ! empty( $this->prefs['account_logo'] ) )
				$hidden_fields = array_merge_recursive( $hidden_fields, array(
					'logo_url'              => $this->prefs['account_logo']
				) );

			if ( isset( $this->prefs['confirmation_note'] ) && ! empty( $this->prefs['confirmation_note'] ) )
				$hidden_fields = array_merge_recursive( $hidden_fields, array(
					'confirmation_note'     => $mycred->template_tags_general( $this->prefs['confirmation_note'] )
				) );

			// If we want an email receipt for purchases
			if ( isset( $this->prefs['email_receipt'] ) && ! empty( $this->prefs['email_receipt'] ) )
				$hidden_fields = array_merge_recursive( $hidden_fields, array(
					'status_url2'           => $this->prefs['account']
				) );

			// Hidden form fields
			$sale_details = array(
				'merchant_fields'     => 'sales_data',
				'sales_data'          => $this->transaction_id,
				'amount'              => $cost,
				'currency'            => $this->prefs['currency'],
				'detail1_description' => __( 'Product:', 'mycred' ),
				'detail1_text'        => $item_name
			);
			$hidden_fields = array_merge_recursive( $hidden_fields, $sale_details );

			// Gifting
			if ( $to != $from ) {
				$user = get_userdata( $to );
				$gift_details = array(
					'detail2_description' => __( 'Gift to:', 'mycred' ),
					'detail2_text'        => $user->display_name . ' ' . __( '(author)', 'mycred' )
				);
				$hidden_fields = array_merge_recursive( $hidden_fields, $gift_details );
				unset( $user );
			}

			// Generate processing page
			$this->get_page_header( __( 'Processing payment &hellip;', 'mycred' ) );
			$this->get_page_redirect( $hidden_fields, $location );
			$this->get_page_footer();

			exit;

		}

		/**
		 * Preferences
		 * @since 0.1
		 * @version 1.0.1
		 */
		function preferences() {

			add_filter( 'mycred_dropdown_currencies', array( $this, 'skrill_currencies' ) );
			$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( 'currency' ); ?>"><?php _e( 'Currency', 'mycred' ); ?></label>
<ol>
	<li>
		<?php $this->currencies_dropdown( 'currency', 'mycred-gateway-skrill-currency' ); ?>

	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'account' ); ?>"><?php _e( 'Merchant Account Email', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'account' ); ?>" id="<?php echo $this->field_id( 'account' ); ?>" value="<?php echo $prefs['account']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'word' ); ?>"><?php _e( 'Secret Word', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'word' ); ?>" id="<?php echo $this->field_id( 'word' ); ?>" value="<?php echo $prefs['word']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'item_name' ); ?>"><?php _e( 'Item Name', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'item_name' ); ?>" id="<?php echo $this->field_id( 'item_name' ); ?>" value="<?php echo $prefs['item_name']; ?>" class="long" /></div>
		<span class="description"><?php _e( 'Description of the item being purchased by the user.', 'mycred' ); ?></span>
	</li>
</ol>
<label class="subheader"><?php _e( 'Exchange Rates', 'mycred' ); ?></label>
<ol>
	<?php $this->exchange_rate_setup(); ?>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'email_receipt' ); ?>"><?php _e( 'Confirmation Email', 'mycred' ); ?></label>
<ol>
	<li>
		<label for="<?php echo $this->field_id( 'email_receipt' ); ?>"><input type="checkbox" name="<?php echo $this->field_name( 'email_receipt' ); ?>" id="<?php echo $this->field_id( 'email_receipt' ); ?>" value="1"<?php checked( $prefs['email_receipt'], 1 ); ?> /> <?php _e( 'Ask Skrill to send me a confirmation email for each successful purchase.', 'mycred' ); ?></label>
	</li>
</ol>
<label class="subheader"><?php _e( 'Checkout Page', 'mycred' ); ?></label>
<ol>
	<li>
		<label for="<?php echo $this->field_id( 'account_title' ); ?>"><?php _e( 'Title', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'account_title' ); ?>" id="<?php echo $this->field_id( 'account_title' ); ?>" value="<?php echo $prefs['account_title']; ?>" class="long" /></div>
		<span class="description"><?php _e( 'If left empty, your account email is used as title on the Skill Payment Page.', 'mycred' ); ?></span>
	</li>
	<li>
		<label for="<?php echo $this->field_id( 'account_logo' ); ?>"><?php _e( 'Logo URL', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'account_logo' ); ?>" id="<?php echo $this->field_id( 'account_title' ); ?>" value="<?php echo $prefs['account_logo']; ?>" class="long" /></div>
		<span class="description"><?php _e( 'The URL to the image you want to use on the top of the gateway. For best integration results we recommend you use logos with dimensions up to 200px in width and 50px in height.', 'mycred' ); ?></span>
	</li>
	<li>
		<label for="<?php echo $this->field_id( 'confirmation_note' ); ?>"><?php _e( 'Confirmation Note', 'mycred' ); ?></label><br />
		<textarea rows="10" cols="50" style="width: 85%;" name="<?php echo $this->field_name( 'confirmation_note' ); ?>" id="<?php echo $this->field_id( 'confirmation_note' ); ?>" class="large-text code"><?php echo $prefs['confirmation_note']; ?></textarea><br />
		<span class="description"><?php _e( 'Optional text to show user once a transaction has been successfully completed. This text is shown by Skrill.', 'mycred' ); ?></span>
	</li>
</ol>
<?php

		}

		/**
		 * Sanatize Prefs
		 * @since 0.1
		 * @version 1.1
		 */
		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['sandbox']           = ( isset( $data['sandbox'] ) ) ? 1 : 0;
			$new_data['currency']          = sanitize_text_field( $data['currency'] );
			$new_data['account']           = sanitize_text_field( $data['account'] );
			$new_data['word']              = sanitize_text_field( $data['word'] );
			$new_data['email_receipt']     = ( isset( $data['email_receipt'] ) ) ? 1 : 0;
			$new_data['item_name']         = sanitize_text_field( $data['item_name'] );
			$new_data['account_title']     = substr( $data['account_title'], 0, 30 );
			$new_data['account_logo']      = sanitize_text_field( $data['account_logo'] );
			$new_data['confirmation_note'] = substr( $data['confirmation_note'], 0, 240 );

			// If exchange is less then 1 we must start with a zero
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
			}
			$new_data['exchange'] = $data['exchange'];

			return $new_data;

		}

	}
endif;
?>