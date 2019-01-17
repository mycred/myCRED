<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Zombaio class
 * Zombaio Payment Gateway
 * @since 1.1
 * @version 1.1.3
 */
if ( ! class_exists( 'myCRED_Zombaio' ) ) :
	class myCRED_Zombaio extends myCRED_Payment_Gateway {

		/**
		 * Construct
		 */
		function __construct( $gateway_prefs ) {

			parent::__construct( array(
				'id'               => 'zombaio',
				'label'            => 'Zombaio',
				'gateway_logo_url' => plugins_url( 'assets/images/zombaio.png', MYCRED_PURCHASE ),
				'defaults'         => array(
					'sandbox'          => 0,
					'site_id'          => '',
					'pricing_id'       => '',
					'gwpass'           => '',
					'logo_url'         => '',
					'lang'             => 'ZOM',
					'bypass_ipn'       => 0
				)
			), $gateway_prefs );

		}

		/**
		 * Process
		 * @since 1.1
		 * @version 1.0
		 */
		public function process() {

			if ( isset( $_GET['wp_zombaio_ips'] ) && $_GET['wp_zombaio_ips'] == 1 ) {

				if ( isset( $_GET['csv'] ) && $_GET['csv'] == 1 ) {
					echo '<textarea style="width: 270px;" rows="10" readonly="readonly">' . implode( ',', $this->get_zombaio_ips() ) . '</textarea>';
					exit;
				}

				echo '<ul>';
				foreach ( $ips as $ip ) {
					echo '<li><input type="text" readonly="readonly" value="' . $ip . '" size="15" /></li>';
				}
				echo '</ul>';

				exit;

			}

			$this->handle_call();

		}

		/**
		 * First Comment
		 * @since 1.7.3
		 * @version 1.0.1
		 */
		public function first_comment( $comment ) {

			return 'New Zombaio purchase confirmation.';

		}

		/**
		 * Verify IPN IP
		 * @since 1.1
		 * @version 1.1
		 */
		public function verify_zombaio_call() {

			if ( $this->prefs['bypass_ipn'] ) return true;

			$zombaio_ips = $this->get_zombaio_ips();
			if ( empty( $zombaio_ips ) ) return true;

			if ( $_SERVER['REMOTE_ADDR'] != '' ) {

				$remote_addr = explode( '.', $_SERVER['REMOTE_ADDR'] );
				$remote_addr = $remote_addr[0] . '.' . $remote_addr[1] . '.' . $remote_addr[2] . '.';

				if ( in_array( $remote_addr, $zombaio_ips ) ) return true;

			}

			return false;

		}

		/**
		 * Load IPN IP List
		 * @since 1.1
		 * @version 1.1
		 */
		public function get_zombaio_ips() {

			$request = new WP_Http();
			$data    = $request->request( 'http://www.zombaio.com/ip_list.txt' );
			$data    = explode( '|', $data['body'] );

			$zombaio_ips = array();
			if ( ! empty( $data ) ) {
				foreach ( $data as $ip_range ) {
					if ( $ip_range != '' )
						$zombaio_ips[] = $ip_range;
				}
			}

			return $zombaio_ips;

		}

		/**
		 * IPN - Is Valid Call
		 * Replaces the default check
		 * @since 1.4
		 * @version 1.1
		 */
		public function IPN_is_valid_call() {

			$result = true;

			// Check password
			if ( $_GET['ZombaioGWPass'] != $this->prefs['gwpass'] )
				$result = false;

			// Check IPN
			if ( $result === true && $this->prefs['bypass_ipn'] == 0 ) {

				$zombaio_ips = $this->get_zombaio_ips();
				if ( ! empty( $zombaio_ips ) ) {

					if ( $_SERVER['REMOTE_ADDR'] != '' ) {

						$remote_addr = explode( '.', $_SERVER['REMOTE_ADDR'] );
						$remote_addr = $remote_addr[0] . '.' . $remote_addr[1] . '.' . $remote_addr[2] . '.';

						if ( ! in_array( $remote_addr, $zombaio_ips ) )
							$result = false;

					}

				}

			}

			// Check Site ID
			if ( $result === true && $_GET['SiteID'] != $this->prefs['site_id'] )
				$result = false;

			return $result;

		}

		/**
		 * Handle IPN Call
		 * @since 1.1
		 * @version 1.2.1
		 */
		public function handle_call() {

			$outcome = 'FAILED';

			// ZOA Validation
			if ( isset( $_GET['username'] ) && substr( $_GET['username'], 0, 4 ) == 'Test' ) {
				if ( ! headers_sent() )
					header( 'HTTP/1.1 200 OK' );

				echo 'OK';
				die;
			}

			// Required fields
			if ( isset( $_GET['ZombaioGWPass'] ) && isset( $_GET['SiteID'] ) && isset( $_GET['Action'] ) && isset( $_GET['Credits'] ) && isset( $_GET['TransactionID'] ) && isset( $_GET['Identifier'] ) ) {

				// In case this is a true Zombaio call but for other actions, return now
				// to allow other plugins to take over.
				if ( $_GET['Action'] != 'user.addcredits' )
					return;

				// Get Pending Payment
				$pending_post_id = sanitize_key( $_GET['Identifier'] );
				$pending_payment = $this->get_pending_payment( $pending_post_id );
				if ( $pending_payment !== false ) {

					// Validate call
					if ( $this->IPN_is_valid_call() ) {

						$errors         = false;
						$new_call       = array();
						$transaction_id = sanitize_text_field( $_GET['TransactionID'] );

						// Make sure transaction is unique
						if ( ! $this->transaction_id_is_unique( $transaction_id ) ) {
							$new_call[] = sprintf( __( 'Duplicate transaction. Received: %s', 'mycred' ), $transaction_id );
							$errors     = true;
						}

						// Live transaction during testing
						if ( $this->sandbox_mode && $transaction_id != '0000' ) {
							$new_call[] = sprintf( __( 'Live transaction while debug mode is enabled! Received: %s', 'mycred' ), $transaction_id );
							$errors     = true;
						}

						// Credit payment
						if ( $errors === false ) {

							$amount = sanitize_text_field( $_GET['Credits'] );
							if ( is_numeric( $amount ) && $amount > 0 ) {

								// Type
								$type   = $pending_payment->point_type;
								$mycred = mycred( $type );

								// Amount
								$amount                    = $mycred->number( $amount );
								$pending_payment->amount   = $amount;

								// Get Cost
								$pending_payment->cost     = 0;

								// If account is credited, delete the post and it's comments.
								if ( $this->complete_payment( $pending_payment, $transaction_id ) ) {
									$this->trash_pending_payment( $pending_post_id );
									$outcome = 'COMPLETED';
								}
								else
									$new_call[] = __( 'Failed to credit users account.', 'mycred' );

							}

						}

						// Log Call
						if ( ! empty( $new_call ) )
							$this->log_call( $pending_post_id, $new_call );

					}

				}

			}

			if ( $outcome == 'COMPLETED' )
				die( 'OK' );
			else
				die( 'ERROR' );

		}

		/**
		 * Buy Handler
		 * @since 1.1
		 * @version 1.3
		 */
		public function buy() {

			if ( ! isset( $this->prefs['site_id'] ) || empty( $this->prefs['site_id'] ) ) wp_die( __( 'Please setup this gateway before attempting to make a purchase!', 'mycred' ) );

			// Construct location
			$location = 'https://secure.zombaio.com/?' . $this->prefs['site_id'] . '.' . $this->prefs['pricing_id'] . '.' . $this->prefs['lang'];

			// Prep
			$type         = $this->get_point_type();
			$to           = $this->get_to();
			$from         = get_current_user_id();
			$thankyou_url = $this->get_thankyou();

			// Revisiting pending payment
			if ( isset( $_REQUEST['revisit'] ) )
				$this->transaction_id = strtoupper( sanitize_text_field( $_REQUEST['revisit'] ) );

			// New pending payment
			else {
				$post_id              = $this->add_pending_payment( array( $to, $from, '-', '-', 'USD', $type ) );
				$this->transaction_id = get_the_title( $post_id );
			}

			$cancel_url = $this->get_cancelled( $this->transaction_id );

			$hidden_fields = array(
				'identifier'    => $post_id,
				'approve_url'   => $thankyou_url,
				'decline_url'   => $cancel_url
			);

			// Generate processing page
			$this->get_page_header( __( 'Processing payment &hellip;', 'mycred' ) );
			$this->get_page_redirect( $hidden_fields, $location );
			$this->get_page_footer();

			exit;

		}

		/**
		 * Preferences
		 * @since 1.1
		 * @version 1.0.1
		 */
		function preferences() {

			$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( 'site_id' ); ?>"><?php _e( 'Site ID', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'site_id' ); ?>" id="<?php echo $this->field_id( 'site_id' ); ?>" value="<?php echo $prefs['site_id']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'gwpass' ); ?>"><?php _e( 'GW Password', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'gwpass' ); ?>" id="<?php echo $this->field_id( 'gwpass' ); ?>" value="<?php echo $prefs['gwpass']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'site_id' ); ?>"><?php _e( 'Pricing ID', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'pricing_id' ); ?>" id="<?php echo $this->field_id( 'pricing_id' ); ?>" value="<?php echo $prefs['pricing_id']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'logo_url' ); ?>"><?php _e( 'Logo URL', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'logo_url' ); ?>" id="<?php echo $this->field_id( 'logo_url' ); ?>" value="<?php echo $prefs['logo_url']; ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'bypass_ipn' ); ?>"><?php _e( 'IP Verification', 'mycred' ); ?></label>
<ol>
	<li>
		<label for="<?php echo $this->field_id( 'bypass_ipn' ); ?>"><input type="checkbox" name="<?php echo $this->field_name( 'bypass_ipn' ); ?>" id="<?php echo $this->field_id( 'bypass_ipn' ); ?>" value="1"<?php checked( $prefs['bypass_ipn'], 1 ); ?> /> <?php _e( 'Do not verify that callbacks are coming from Zombaio.', 'mycred' ); ?></label>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'lang' ); ?>"><?php _e( 'Language', 'mycred' ); ?></label>
<ol>
	<li>
		<?php $this->lang_dropdown( 'lang' ); ?>

	</li>
</ol>
<label class="subheader"><?php _e( 'Postback URL (ZScript)', 'mycred' ); ?></label>
<ol>
	<li>
		<code style="padding: 12px;display:block;"><?php echo get_bloginfo( 'url' ); ?></code>
		<p><?php _e( 'For this gateway to work, login to ZOA and set the Postback URL to the above address and click validate.', 'mycred' ); ?></p>
	</li>
</ol>
<?php

		}

		/**
		 * Sanatize Prefs
		 * @since 1.1
		 * @version 1.0
		 */
		public function sanitise_preferences( $data ) {

			$new_data               = array();

			$new_data['sandbox']    = ( array_key_exists( 'sandbox', $data ) ) ? 1 : 0;
			$new_data['site_id']    = sanitize_text_field( $data['site_id'] );
			$new_data['gwpass']     = sanitize_text_field( $data['gwpass'] );
			$new_data['pricing_id'] = sanitize_text_field( $data['pricing_id'] );
			$new_data['logo_url']   = sanitize_text_field( $data['logo_url'] );
			$new_data['bypass_ipn'] = ( array_key_exists( 'bypass_ipn', $data ) ) ? 1 : 0;
			$new_data['lang']       = sanitize_text_field( $data['lang'] );

			return $new_data;

		}

		/**
		 * Language Dropdown
		 * @since 1.1
		 * @version 1.0
		 */
		public function lang_dropdown( $name ) {

			$languages = array(
				'ZOM' => 'Let Zombaio Detect Language',
				'US'  => 'English',
				'FR'  => 'French',
				'DE'  => 'German',
				'IT'  => 'Italian',
				'JP'  => 'Japanese',
				'ES'  => 'Spanish',
				'SE'  => 'Swedish',
				'KR'  => 'Korean',
				'CH'  => 'Traditional Chinese',
				'HK'  => 'Simplified Chinese'
			);

			echo '<select name="' . $this->field_name( $name ) . '" id="' . $this->field_id( $name ) . '">';
			echo '<option value="">' . __( 'Select', 'mycred' ) . '</option>';
			foreach ( $languages as $code => $cname ) {
				echo '<option value="' . $code . '"';
				if ( isset( $this->prefs[ $name ] ) && $this->prefs[ $name ] == $code ) echo ' selected="selected"';
				echo '>' . $cname . '</option>';
			}
			echo '</select>';

		}

	}
endif;
