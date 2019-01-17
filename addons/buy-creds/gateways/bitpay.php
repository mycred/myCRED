<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Bitpay class
 * BitPay (Bitcoins) - Payment Gateway
 * @since 1.4
 * @version 1.1.1
 */
if ( ! class_exists( 'myCRED_Bitpay' ) ) :
	class myCRED_Bitpay extends myCRED_Payment_Gateway {

		/**
		 * Construct
		 */
		function __construct( $gateway_prefs ) {

			$types = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label )
				$default_exchange[ $type ] = 1;

			parent::__construct( array(
				'id'               => 'bitpay',
				'label'            => 'Bitpay',
				'gateway_logo_url' => plugins_url( 'assets/images/bitpay.png', MYCRED_PURCHASE ),
				'defaults'         => array(
					'api_key'          => '',
					'currency'         => 'USD',
					'exchange'         => $default_exchange,
					'item_name'        => 'Purchase of myCRED %plural%',
					'speed'            => 'high',
					'notifications'    => 1
				)
			), $gateway_prefs );

		}

		/**
		 * Process
		 * @since 1.4
		 * @version 1.1
		 */
		public function process() {

			// Required fields
			if ( isset( $_POST['postData'] ) && isset( $_POST['id'] ) && isset( $_POST['price'] ) ) {

				// Get Pending Payment
				$pending_post_id = sanitize_key( $_POST['postData'] );
				$pending_payment = $this->get_pending_payment( $pending_post_id );
				if ( $pending_payment !== false ) {

					// Verify Call with PayPal
					if ( $this->IPN_is_valid_call() ) {

						$errors   = false;
						$new_call = array();

						// Check amount paid
						if ( $_POST['price'] != $pending_payment->cost ) {
							$new_call[] = sprintf( __( 'Price mismatch. Expected: %s Received: %s', 'mycred' ), $pending_payment->cost, $_POST['price'] );
							$errors     = true;
						}

						// Check currency
						if ( $_POST['currency'] != $pending_payment->currency ) {
							$new_call[] = sprintf( __( 'Currency mismatch. Expected: %s Received: %s', 'mycred' ), $pending_payment->currency, $_POST['currency'] );
							$errors     = true;
						}

						// Check status
						if ( $_POST['status'] != 'paid' ) {
							$new_call[] = sprintf( __( 'Payment not completed. Received: %s', 'mycred' ), $_POST['status'] );
							$errors     = true;
						}

						// Credit payment
						if ( $errors === false ) {

							// If account is credited, delete the post and it's comments.
							if ( $this->complete_payment( $pending_payment, $_POST['id'] ) )
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
		 * Returning
		 * @since 1.4
		 * @version 1.0
		 */
		public function returning() { }

		/**
		 * Create Invoice
		 * @since 1.4
		 * @version 1.0
		 */
		public function create_invoice( $args ) {

			$data = json_encode( $args );

			$curl = curl_init( 'https://bitpay.com/api/invoice/' );

			curl_setopt( $curl, CURLOPT_POST, 1 );
			curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
			$length = strlen( $data );

			$key = base64_encode( $args['apiKey'] );
			$header = array(
				'Content-Type: application/json',
				"Content-Length: $length",
				"Authorization: Basic $key",
			);

			curl_setopt( $curl, CURLOPT_PORT, 443 );
			curl_setopt( $curl, CURLOPT_HTTPHEADER, $header );
			curl_setopt( $curl, CURLOPT_TIMEOUT, 10 );
			curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC ) ;
			curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, 1 );
			curl_setopt( $curl, CURLOPT_SSL_VERIFYHOST, 2 );
			curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
			curl_setopt( $curl, CURLOPT_FORBID_REUSE, 1 );
			curl_setopt( $curl, CURLOPT_FRESH_CONNECT, 1 );

			$reply = curl_exec( $curl );

			if ( $reply == false )
				$response = curl_error( $curl );
			else
				$response = json_decode( $reply, true );

			curl_close( $curl );

			if ( is_string( $response ) )
				return array( 'error' => $response );	

			return $response;

		}

		/**
		 * Buy Creds
		 * @since 1.4
		 * @version 1.2
		 */
		public function buy() {

			if ( ! isset( $this->prefs['api_key'] ) || empty( $this->prefs['api_key'] ) ) wp_die( __( 'Please setup this gateway before attempting to make a purchase!', 'mycred' ) );

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

			// Hidden form fields
			$request = $this->create_invoice( array(
				'apiKey'            => $this->prefs['api_key'],
				'transactionSpeed'  => $this->prefs['speed'],
				'price'             => $cost,
				'currency'          => $this->prefs['currency'],
				'notificationURL'   => $this->callback_url(),
				'fullNotifications' => ( $this->prefs['notifications'] ) ? true : false,
				'posData'           => $this->transaction_id,
				'buyerName'         => $this->get_buyers_name( $from ),
				'itemDesc'          => $item_name
			) );

			// Request Failed
			if ( isset( $request['error'] ) ) {
				$this->get_page_header( __( 'Processing payment &hellip;', 'mycred' ) );

?>
<p><?php _e( 'Could not create a BitPay Invoice. Please contact the site administrator!', 'mycred' ); ?></p>
<p><?php printf( __( 'Bitpay returned the following error message:', 'mycred' ) . ' ', $request['error'] ); ?></p>
<?php

			}

			// Request success
			else {
				$this->get_page_header( __( 'Processing payment &hellip;', 'mycred' ) );

?>
<div class="continue-forward" style="text-align:center;">
	<p>&nbsp;</p>
	<img src="<?php echo plugins_url( 'assets/images/loading.gif', MYCRED_PURCHASE ); ?>" alt="Loading" />
	<p id="manual-continue"><a href="<?php echo $request['url']; ?>"><?php _e( 'Click here if you are not automatically redirected', 'mycred' ); ?></a></p>
</div>
<?php
			}

			$this->get_page_footer();
			exit;

		}

		/**
		 * Gateway Prefs
		 * @since 1.4
		 * @version 1.0
		 */
		function preferences() {

			$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( 'api_key' ); ?>"><?php _e( 'API Key', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'api_key' ); ?>" id="<?php echo $this->field_id( 'api_key' ); ?>" value="<?php echo $prefs['api_key']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'currency' ); ?>"><?php _e( 'Currency', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'currency' ); ?>" id="<?php echo $this->field_id( 'currency' ); ?>" value="<?php echo $prefs['currency']; ?>" class="medium" maxlength="3" placeholder="<?php _e( 'Currency Code', 'mycred' ); ?>" /></div>

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
<label class="subheader" for="<?php echo $this->field_id( 'speed' ); ?>"><?php _e( 'Transaction Speed', 'mycred' ); ?></label>
<ol>
	<li>
		<select name="<?php echo $this->field_name( 'speed' ); ?>" id="<?php echo $this->field_id( 'speed' ); ?>">
<?php

			$options = array(
				'high'   => __( 'High', 'mycred' ),
				'medium' => __( 'Medium', 'mycred' ),
				'low'    => __( 'Low', 'mycred' )
			);
			foreach ( $options as $value => $label ) {
				echo '<option value="' . $value . '"';
				if ( $prefs['speed'] == $value ) echo ' selected="selected"';
				echo '>' . $label . '</option>';
			}

?>

		</select>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'notifications' ); ?>"><?php _e( 'Full Notifications', 'mycred' ); ?></label>
<ol>
	<li>
		<select name="<?php echo $this->field_name( 'notifications' ); ?>" id="<?php echo $this->field_id( 'notifications' ); ?>">
<?php

			$options = array(
				0 => __( 'No', 'mycred' ),
				1 => __( 'Yes', 'mycred' )
			);
			foreach ( $options as $value => $label ) {
				echo '<option value="' . $value . '"';
				if ( $prefs['notifications'] == $value ) echo ' selected="selected"';
				echo '>' . $label . '</option>';
			}

?>

		</select>
	</li>
</ol>
<?php

		}

		/**
		 * Sanatize Prefs
		 * @since 1.4
		 * @version 1.1
		 */
		public function sanitise_preferences( $data ) {

			$new_data['api_key']       = sanitize_text_field( $data['api_key'] );
			$new_data['currency']      = sanitize_text_field( $data['currency'] );
			$new_data['item_name']     = sanitize_text_field( $data['item_name'] );
			$new_data['speed']         = sanitize_text_field( $data['speed'] );
			$new_data['notifications'] = sanitize_text_field( $data['notifications'] );

			// If exchange is less then 1 we must start with a zero
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
			}
			$new_data['exchange'] = $data['exchange'];

			return $data;

		}

	}
endif;
?>