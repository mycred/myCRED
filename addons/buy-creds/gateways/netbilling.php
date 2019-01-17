<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_NETbilling class
 * NETbilling Payment Gateway
 * @see http://secure.netbilling.com/public/docs/merchant/public/directmode/directmode3protocol.html
 * @since 0.1
 * @version 1.2.2
 */
if ( ! class_exists( 'myCRED_NETbilling' ) ) :
	class myCRED_NETbilling extends myCRED_Payment_Gateway {

		protected $http_code = '';

		/**
		 * Construct
		 */
		function __construct( $gateway_prefs ) {

			global $netbilling_errors;

			$types = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label )
				$default_exchange[ $type ] = 1;

			parent::__construct( array(
				'id'               => 'netbilling',
				'label'            => 'NETbilling',
				'gateway_logo_url' => plugins_url( 'assets/images/netbilling.png', MYCRED_PURCHASE ),
				'defaults'         => array(
					'sandbox'          => 0,
					'account'          => '',
					'site_tag'         => '',
					'item_name'        => 'Purchase of myCRED %plural%',
					'exchange'         => $default_exchange,
					'cryptokey'        => '',
					'currency'         => 'USD'
				)
			), $gateway_prefs );

		}

		/**
		 * IPN - Is Valid Call
		 * Replaces the default check
		 * @since 1.4
		 * @version 1.0
		 */
		public function IPN_is_valid_call() {

			$result = true;

			// Accounts Match
			$account = explode( ':', $_REQUEST['Ecom_Ezic_AccountAndSitetag'] );
			if ( $account[0] != $this->prefs['account'] || $account[1] != $this->prefs['site_tag'] )
				$result = false;

			// Crypto Check
			$crypto_check = md5( $this->prefs['cryptokey'] . $_REQUEST['Ecom_Cost_Total'] . $_REQUEST['Ecom_Receipt_Description'] );
			if ( $crypto_check != $_REQUEST['Ecom_Ezic_Security_HashValue_MD5'] )
				$result = false;

			return $result;

		}

		/**
		 * Process
		 * @since 0.1
		 * @version 1.2
		 */
		public function process() {

			// Required fields
			if ( isset( $_REQUEST['Ecom_UserData_salesdata'] ) && isset( $_REQUEST['Ecom_Ezic_Response_TransactionID'] ) && isset( $_REQUEST['Ecom_Cost_Total'] ) ) {

				// Get Pending Payment
				$pending_post_id = sanitize_key( $_REQUEST['Ecom_UserData_salesdata'] );
				$pending_payment = $this->get_pending_payment( $pending_post_id );
				if ( $pending_payment !== false ) {

					// Verify Call with PayPal
					if ( $this->IPN_is_valid_call() ) {

						$errors   = false;
						$new_call = array();

						// Check amount paid
						if ( $_REQUEST['Ecom_Cost_Total'] != $pending_payment->cost ) {
							$new_call[] = sprintf( __( 'Price mismatch. Expected: %s Received: %s', 'mycred' ), $pending_payment->cost, $_REQUEST['Ecom_Cost_Total'] );
							$errors     = true;
						}

						// Check status
						if ( $_REQUEST['Ecom_Ezic_Response_StatusCode'] != 1 ) {
							$new_call[] = sprintf( __( 'Payment not completed. Received: %s', 'mycred' ), $_REQUEST['Ecom_Ezic_Response_StatusCode'] );
							$errors     = true;
						}

						// Credit payment
						if ( $errors === false ) {

							// If account is credited, delete the post and it's comments.
							if ( $this->complete_payment( $pending_payment, $_REQUEST['Ecom_Ezic_Response_TransactionID'] ) )
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
		 * Returns
		 * @since 0.1
		 * @version 1.1
		 */
		public function returning() {

			if ( isset( $_REQUEST['Ecom_Ezic_AccountAndSitetag'] ) && isset( $_REQUEST['Ecom_UserData_salesdata'] ) )
				$this->process();

		}

		/**
		 * Buy Handler
		 * @since 0.1
		 * @version 1.4
		 */
		public function buy() {

			if ( ! isset( $this->prefs['account'] ) || empty( $this->prefs['account'] ) ) wp_die( __( 'Please setup this gateway before attempting to make a purchase!', 'mycred' ) );

			// Prep
			$type         = $this->get_point_type();
			$mycred       = mycred( $type );

			$amount       = $mycred->number( $_REQUEST['amount'] );
			$amount       = abs( $amount );

			$cost         = $this->get_cost( $amount, $type );
			$cost         = number_format( $cost, 2, '.', '' );
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
				$post_id              = $this->add_pending_payment( array( $to, $from, $amount, $cost, 'USD', $type ) );
				$this->transaction_id = get_the_title( $post_id );
			}

			$cancel_url = $this->get_cancelled( $this->transaction_id );

			// Hidden form fields
			$hidden_fields = array(
				'Ecom_Ezic_AccountAndSitetag'         => $this->prefs['account'] . ':' . $this->prefs['site_tag'],
				'Ecom_Ezic_Payment_AuthorizationType' => 'SALE',
				'Ecom_Receipt_Description'            => $item_name,
				'Ecom_Ezic_Fulfillment_ReturnMethod'  => 'POST',
				'Ecom_Cost_Total'                     => $cost,
				'Ecom_UserData_salesdata'             => $this->transaction_id,
				'Ecom_Ezic_Fulfillment_ReturnURL'     => $thankyou_url,
				'Ecom_Ezic_Fulfillment_GiveUpURL'     => $cancel_url,
				'Ecom_Ezic_Security_HashValue_MD5'    => md5( $this->prefs['cryptokey'] . $cost . $item_name ),
				'Ecom_Ezic_Security_HashFields'       => 'Ecom_Cost_Total Ecom_Receipt_Description'
			);

			// Generate processing page
			$this->get_page_header( __( 'Processing payment &hellip;', 'mycred' ) );
			$this->get_page_redirect( $hidden_fields, 'https://secure.netbilling.com/gw/native/interactive2.2' );
			$this->get_page_footer();

			exit;

		}

		/**
		 * Preferences
		 * @since 0.1
		 * @version 1.1
		 */
		function preferences() {

			$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( 'account' ); ?>"><?php _e( 'Account ID', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'account' ); ?>" id="<?php echo $this->field_id( 'account' ); ?>" value="<?php echo $prefs['account']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'site_tag' ); ?>"><?php _e( 'Site Tag', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'site_tag' ); ?>" id="<?php echo $this->field_id( 'site_tag' ); ?>" value="<?php echo $prefs['site_tag']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'cryptokey' ); ?>"><?php _e( 'Order Integrity Key', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="password" name="<?php echo $this->field_name( 'cryptokey' ); ?>" id="<?php echo $this->field_id( 'cryptokey' ); ?>" value="<?php echo $prefs['cryptokey']; ?>" class="long" /></div>
		<span class="description"><?php _e( 'Found under Step 12 on the Fraud Defense page.', 'mycred' ); ?></span>
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
<label class="subheader"><?php _e( 'Postback CGI URL', 'mycred' ); ?></label>
<ol>
	<li>
		<code style="padding: 12px;display:block;"><?php echo $this->callback_url(); ?></code>
		<p><?php _e( 'For this gateway to work, you must login to your NETbilling account and edit your site. Under "Default payment form settings" make sure the Postback CGI URL is set to the above address and "Return method" is set to POST.', 'mycred' ); ?></p>
	</li>
</ol>
<?php

		}

		/**
		 * Sanatize Prefs
		 * @since 0.1
		 * @version 1.2
		 */
		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['sandbox']   = ( isset( $data['sandbox'] ) ) ? 1 : 0;
			$new_data['account']   = sanitize_text_field( $data['account'] );
			$new_data['site_tag']  = sanitize_text_field( $data['site_tag'] );
			$new_data['cryptokey'] = sanitize_text_field( $data['cryptokey'] );
			$new_data['item_name'] = sanitize_text_field( $data['item_name'] );

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

		/**
		 * Validate CC
		 * @since 1.3
		 * @version 1.0
		 */
		protected function validate_cc( $data = array() ) {

			$errors = array();

			// Credit Card
			if ( $data['payment_method'] == 'card' ) {
				// Check length
				if ( strlen( $data['card_number'] ) < 13 || strlen( $data['card_number'] ) > 19 || ! is_numeric( $data['card_number'] ) )
					$errors['number'] =  __( 'Incorrect Credit Card number', 'mycred' );

				// Check expiration date
				$exp_date   = mktime( 0, 0, 0, $data['card_expire_month'], 30, $data['card_expire_year'] );
				$today_date = current_time( 'timestamp' );
				if ( $exp_date < $today_date )
					$errors['expire'] =  __( 'The credit card entered is past its expiration date.', 'mycred' );
				
				if ( strlen( $data['card_cvv2'] ) < 3 || strlen( $data['card_cvv2'] ) > 4 || ! is_numeric( $data['card_cvv2'] ) )
					$errors['cvc'] =  __( 'The CVV2 number entered is not valid.', 'mycred' );
			}

			// Check
			else {
				// Check routing
				if ( strlen( $data['ach_routing'] ) != 9 || ! is_numeric( $data['ach_routing'] ) )
					$errors['routing'] =  __( 'The bank routing number entered is not valid.', 'mycred' );

				// Check account
				if ( strlen( $data['ach_account'] ) <= 5 || ! is_numeric( $data['ach_account'] ) )
					$errors['account'] =  __( 'The bank account number entered is not valid.', 'mycred' );
			}

			return $errors;

		}

	}
endif;
?>