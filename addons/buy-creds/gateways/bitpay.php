<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Bitpay class
 * BitPay (Bitcoins) - Payment Gateway
 * @since 1.4
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Bitpay' ) ) :
	class myCRED_Bitpay extends myCRED_Payment_Gateway {

		/**
		 * Construct
		 */
		public function __construct( $gateway_prefs ) {

			$types            = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label )
				$default_exchange[ $type ] = 1;

			parent::__construct( array(
				'id'               => 'bitpay',
				'label'            => 'Bitpay',
				'gateway_logo_url' => plugins_url( 'assets/images/bitpay.png', MYCRED_PURCHASE ),
				'defaults'         => array(
					'sandbox'          => 0,
					'api_token'        => '',
					'currency'         => 'USD',
					'exchange'         => $default_exchange,
					'item_name'        => 'Purchase of myCRED %plural%',
					'logo_url'         => '',
					'speed'            => 'high',
					'notifications'    => 1
				)
			), $gateway_prefs );

		}

		/**
		 * Process
		 * @since 1.4
		 * @version 1.2
		 */
		public function process() {

			$post = file_get_contents( "php://input" );
			if ( ! empty( $post ) ) {

				$new_call = array();
				$json     = json_decode( $post, true );

				if ( ! empty( $json ) && array_key_exists( 'id', $json['data'] ) && array_key_exists( 'url', $json['data'] ) ) {

					try {
						// Bitpay url
						$host = 'bitpay.com';
						if ( $this->sandbox_mode )
							$host = 'test.bitpay.com';

						$id = $json['data']['id'];
						
						$retrieve_invoice = 
							wp_remote_get( 'https://'.$host.'/invoices/'.$id.'?token='.$this->prefs['api_token'], 
								array(
								    'headers'     => 
									    array( 
									    	'X-Accept-Version' => '2.0.0',
		    								'Content-Type' => 'application/json'
									    )
								) 
							);
							
						$data 	 = json_decode( wp_remote_retrieve_body( $retrieve_invoice ) );
						$status  = $data->data->status;
						$orderId = $data->data->orderId;

					} catch ( \Exception $e ) {

						$new_call[] = $e->getMessage();

					}

					if ( empty( $new_call ) && $status == 'confirmed' ) {

						$transaction_id  = $orderId;
						$pending_post_id = buycred_get_pending_payment_id( $transaction_id );
						$pending_payment = $this->get_pending_payment( $pending_post_id );

						if ( $pending_payment !== false ) {

							// If account is credited, delete the post and it's comments.
							if ( $this->complete_payment( $pending_payment, $json['data']['id'] ) )
								$this->trash_pending_payment( $pending_post_id );
							else
								$new_call[] = __( 'Failed to credit users account.', 'mycred' );

							// Log Call
							if ( ! empty( $new_call ) )
								$this->log_call( $pending_post_id, $new_call );

						}

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
		 * Admin Init Handler
		 * @since 1.8
		 * @version 1.0
		 */
		public function admin_init() { }

		/**
		 * Prep Sale
		 * @since 1.8
		 * @version 1.0
		 */
		public function prep_sale( $new_transaction = false ) {

			// Set currency
			$this->currency = ( $this->currency == '' ) ? $this->prefs['currency'] : $this->currency;

			// Token
			$api_token = ! empty( $this->prefs['api_token'] ) ? $this->prefs['api_token'] : '';
			//Set Cost in raw format 
			$this->cost = $this->get_cost( $this->amount, $this->point_type, true );

			// Item Name
			$item_name      = str_replace( '%number%', $this->amount, $this->prefs['item_name'] );
			$item_name      = $this->core->template_tags_general( $item_name );
			$user           = get_userdata( $this->buyer_id );

			// Based on the "BitPay for WooCommerce" plugin issued by Bitpay
			try {

				// Bitpay url
				$host          	= 'bitpay.com';
				if ( $this->sandbox_mode )
					$host = 'test.bitpay.com';
				
				$request_body = 
					json_encode(
						array(
						    'currency' => $this->currency,
						    'price' => $this->cost,
						    'orderId' => $this->transaction_id,
						    'notificationURL' => $this->callback_url(),
						    'redirectURL' => $this->get_thankyou(),
						    'fullNotifications' => ( ( $this->prefs['notifications'] ) ? true : false ),
							'transactionSpeed' => $this->prefs['speed'],
							'description'	=> $item_name,
						    'buyer' => array(
						         'email' => $user->user_email
						    ),
						    'token' => $api_token
						),
					);

				$create_invoice = 
					wp_remote_post( 'https://'.$host.'/invoices', 
						array(
						    'method'      => 'POST',
						    'headers'     => 
							    array( 
							    	'X-Accept-Version' => '2.0.0',
    								'Content-Type' => 'application/json'
							    ),
						    'body'        => $request_body
						) 
					);

			} catch ( \Exception $e ) {

				$this->errors[] = $e->getMessage();

			}

			if ( empty( $this->errors ) ) {
				
				$this->redirect_to = json_decode( $create_invoice['body'] )->data->url;

			}

		}

		/**
		 * AJAX Buy Handler
		 * @since 1.8
		 * @version 1.0
		 */
		public function ajax_buy() {

			// Construct the checkout box content
			$content  = $this->checkout_header();
			$content .= $this->checkout_logo();
			$content .= $this->checkout_order();
			$content .= $this->checkout_cancel();
			$content .= $this->checkout_footer();

			// Return a JSON response
			$this->send_json( $content );

		}

		/**
		 * Checkout Page Body
		 * This gateway only uses the checkout body.
		 * @since 1.8
		 * @version 1.0
		 */
		public function checkout_page_body() {

			echo $this->checkout_header();
			echo $this->checkout_logo( false );

			echo $this->checkout_order();
			echo $this->checkout_cancel();

			echo $this->checkout_footer();

		}

		/**
		 * Gateway Prefs
		 * @since 1.4
		 * @version 1.0
		 */
		function preferences() {

			$prefs = $this->prefs;
?>
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php _e( 'Details', 'mycred' ); ?></h3>

		<div class="form-group">
			<label><?php _e( 'API Token', 'mycred' ); ?></label>
			<div class="form-inline" id="bitpay-pairing-wrapper">
				<input type="text" id="bitpay-pair-code" class="form-control" name="<?php echo esc_attr( $this->field_name('api_token') ); ?>" placeholder="Input Token" value="<?php echo esc_attr( $prefs['api_token'] ); ?>" /> 
			</div>
			<p class="description bitpay-link" id="bitpay-link-live" <?php echo $prefs['sandbox'] == 0 ? 'style="display: block;"' : 'style="display: none;"'; ?>><span>Get a pairing code: <a href="https://bitpay.com/api-tokens" target="_blank">https://bitpay.com/api-tokens</a></span></p>
			<p class="description bitpay-link" id="bitpay-link-test" <?php echo $prefs['sandbox'] == 1 ? 'style="display: block;"' : 'style="display: none;"'; ?>><span>Get a pairing code: <a href="https://test.bitpay.com/api-tokens" target="_blank">https://test.bitpay.com/api-tokens</a></span></p>
		</div>
		<script type="text/javascript">
		jQuery(function($){

			$( '#buycred-gateway-bitpay-sandbox' ).on( 'click', function(){

				if ( $( this ).is( ':checked' ) ) {
					$( '#bitpay-link-test' ).show();
					$( '#bitpay-link-live' ).hide();
				}else{
					$( '#bitpay-link-test' ).hide();
					$( '#bitpay-link-live' ).show();

				}

			});

		});
		</script>

		<div class="form-group">
			<label for="<?php echo $this->field_id( 'item_name' ); ?>"><?php _e( 'Item Name', 'mycred' ); ?></label>
			<input type="text" name="<?php echo $this->field_name( 'item_name' ); ?>" id="<?php echo $this->field_id( 'item_name' ); ?>" value="<?php echo esc_attr( $prefs['item_name'] ); ?>" class="form-control" />
		</div>
		<div class="form-group">
			<label for="<?php echo $this->field_id( 'logo_url' ); ?>"><?php _e( 'Logo URL', 'mycred' ); ?></label>
			<input type="text" name="<?php echo $this->field_name( 'logo_url' ); ?>" id="<?php echo $this->field_id( 'logo_url' ); ?>" value="<?php echo esc_attr( $prefs['logo_url'] ); ?>" class="form-control" />
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="<?php echo $this->field_id( 'speed' ); ?>"><?php _e( 'Transaction Speed', 'mycred' ); ?></label>
					<select name="<?php echo $this->field_name( 'speed' ); ?>" id="<?php echo $this->field_id( 'speed' ); ?>" class="form-control">
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
				</div>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="<?php echo $this->field_id( 'notifications' ); ?>"><?php _e( 'Full Notifications', 'mycred' ); ?></label>
					<select name="<?php echo $this->field_name( 'notifications' ); ?>" id="<?php echo $this->field_id( 'notifications' ); ?>" class="form-control">
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
				</div>
			</div>
		</div>
	</div>
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php _e( 'Setup', 'mycred' ); ?></h3>
		<div class="form-group">
			<label for="<?php echo $this->field_id( 'currency' ); ?>"><?php _e( 'Currency', 'mycred' ); ?></label>
			<input type="text" name="<?php echo $this->field_name( 'currency' ); ?>" id="<?php echo $this->field_id( 'currency' ); ?>" value="<?php echo $prefs['currency']; ?>" class="form-control" maxlength="3" placeholder="<?php _e( 'Currency Code', 'mycred' ); ?>" />

		</div>
		<div class="form-group">
			<label><?php _e( 'Exchange Rates', 'mycred' ); ?></label>

			<?php $this->exchange_rate_setup(); ?>

		</div>
	</div>
</div>
<?php

		}

		/**
		 * Sanatize Prefs
		 * @since 1.4
		 * @version 1.2
		 */
		public function sanitise_preferences( $data ) {

			$new_data                  = array();

			$new_data['api_token']     = isset( $data['api_token'] ) ? sanitize_text_field( $data['api_token'] ) : '';

			$new_data['sandbox']       = ( isset( $data['sandbox'] ) ) ? 1 : 0;
			$new_data['currency']      = sanitize_text_field( $data['currency'] );
			$new_data['item_name']     = sanitize_text_field( $data['item_name'] );
			$new_data['logo_url']      = sanitize_text_field( $data['logo_url'] );
			$new_data['speed']         = sanitize_text_field( $data['speed'] );
			$new_data['notifications'] = sanitize_text_field( $data['notifications'] );

			// If exchange is less then 1 we must start with a zero
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
			}
			$new_data['exchange']      = $data['exchange'];

			return $new_data;

		}

	}
endif;
