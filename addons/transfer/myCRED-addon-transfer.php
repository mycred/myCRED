<?php
/**
 * Addon: Transfer
 * Addon URI: http://mycred.me/add-ons/transfer/
 * Version: 1.4
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_TRANSFER',         __FILE__ );
define( 'myCRED_TRANSFER_DIR',     myCRED_ADDONS_DIR . 'transfer/' );
define( 'myCRED_TRANSFER_VERSION', '1.4' );

require_once myCRED_TRANSFER_DIR . 'includes/mycred-transfer-functions.php';
require_once myCRED_TRANSFER_DIR . 'includes/mycred-transfer-shortcodes.php';
require_once myCRED_TRANSFER_DIR . 'includes/mycred-transfer-widgets.php';

/**
 * myCRED_Transfer_Module class
 * Manages this add-on by hooking into myCRED where needed. Regsiters our custom shortcode and widget
 * along with scripts and styles needed. Also adds settings to the myCRED settings page.
 * @since 0.1
 * @version 1.3.1
 */
if ( ! class_exists( 'myCRED_Transfer_Module' ) ) :
	class myCRED_Transfer_Module extends myCRED_Module {

		/**
		 * Construct
		 */
		function __construct() {

			parent::__construct( 'myCRED_Transfer_Module', array(
				'module_name' => 'transfers',
				'defaults'    => array(
					'types'      => array( MYCRED_DEFAULT_TYPE_KEY ),
					'logs'       => array(
						'sending'   => 'Transfer of %plural% to %display_name%',
						'receiving' => 'Transfer of %plural% from %display_name%'
					),
					'errors'     => array(
						'low'       => __( 'You do not have enough %plural% to send.', 'mycred' ),
						'over'      => __( 'You have exceeded your %limit% transfer limit.', 'mycred' )
					),
					'templates'  => array(
						'login'     => '',
						'balance'   => 'Your current balance is %balance%',
						'limit'     => 'Your current %limit% transfer limit is %left%',
						'button'    => __( 'Transfer', 'mycred' )
					),
					'autofill'   => 'user_login',
					'reload'     => 1,
					'limit'      => array(
						'amount'    => 1000,
						'limit'     => 'none'
					)
				),
				'register'    => false,
				'add_to_core' => true
			) );

		}

		/**
		 * Init
		 * @since 0.1
		 * @version 1.0.1
		 */
		public function module_init() {

			add_filter( 'mycred_get_email_events',     array( $this, 'email_notice_instance' ), 10, 2 );
			add_filter( 'mycred_email_before_send',    array( $this, 'email_notices' ), 50, 2 );

			// Register Scripts & Styles
			add_action( 'mycred_front_enqueue',        array( $this, 'register_script' ), 30 );

			// Register Shortcode
			add_shortcode( 'mycred_transfer',          'mycred_transfer_render' );

			// Potentially load script
			add_action( 'wp_footer',                   array( $this, 'maybe_load_script' ) );

			// Ajax Calls
			add_action( 'wp_ajax_mycred-new-transfer', array( $this, 'ajax_call_transfer' ) );
			add_action( 'wp_ajax_mycred-autocomplete', array( $this, 'ajax_call_autocomplete' ) );

		}

		/**
		 * Register Widgets
		 * @since 0.1
		 * @version 1.0
		 */
		public function module_widgets_init() {

			register_widget( 'myCRED_Widget_Transfer' );

		}

		/**
		 * Enqueue Front
		 * @since 0.1
		 * @version 1.1
		 */
		public function register_script() {

			global $mycred_do_transfer;

			$mycred_do_transfer = false;

			// Register script
			wp_register_script(
				'mycred-transfer',
				plugins_url( 'assets/js/mycred-transfer.js', myCRED_TRANSFER ),
				array( 'jquery', 'jquery-ui-autocomplete' ),
				'1.6'
			);

		}

		/**
		 * Front Footer
		 * @filter 'mycred_transfer_messages'
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function maybe_load_script() {

			global $mycred_do_transfer;

			if ( $mycred_do_transfer !== true ) return;

			// Autofill CSS
			echo '<style type="text/css">' . apply_filters( 'mycred_transfer_autofill_css', '.ui-autocomplete { position: absolute; z-index: 1000; cursor: default; padding: 0; margin-top: 2px; list-style: none; background-color: #ffffff; border: 1px solid #ccc; -webkit-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); -moz-box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); } .ui-autocomplete > li { padding: 3px 20px; } .ui-autocomplete > li:hover { background-color: #DDD; cursor: pointer; } .ui-autocomplete > li.ui-state-focus { background-color: #DDD; } .ui-helper-hidden-accessible { display: none; }', $this ) . '</style>';

			// Prep Script
			$base = array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'user_id'   => get_current_user_id(),
				'working'   => esc_attr__( 'Processing...', 'mycred' ),
				'token'     => wp_create_nonce( 'mycred-autocomplete' ),
				'reload'    => $this->transfers['reload']
			);

			// Messages
			$messages = apply_filters( 'mycred_transfer_messages', array(
				'completed' => esc_attr__( 'Transaction completed.', 'mycred' ),
				'error_1'   => esc_attr__( 'Security token could not be verified. Please contact your site administrator!', 'mycred' ),
				'error_2'   => esc_attr__( 'Communications error. Please try again later.', 'mycred' ),
				'error_3'   => esc_attr__( 'Recipient not found. Please try again.', 'mycred' ),
				'error_4'   => esc_attr__( 'Transaction declined by recipient.', 'mycred' ),
				'error_5'   => esc_attr__( 'Incorrect amount. Please try again.', 'mycred' ),
				'error_6'   => esc_attr__( 'This myCRED Add-on has not yet been setup! No transfers are allowed until this has been done!', 'mycred' ),
				'error_7'   => esc_attr__( 'Insufficient Funds. Please try a lower amount.', 'mycred' ),
				'error_8'   => esc_attr__( 'Transfer Limit exceeded.', 'mycred' ),
				'error_9'   => esc_attr__( 'Communications error. Please try again later.', 'mycred' ),
				'error_10'  => esc_attr__( 'The selected point type can not be transferred.', 'mycred' )
			) );

			wp_localize_script(
				'mycred-transfer',
				'myCREDTransfer',
				array_merge_recursive( $base, $messages )
			);

			wp_enqueue_script( 'mycred-transfer' );

		}

		/**
		 * AJAX Autocomplete
		 * @since 0.1
		 * @version 1.1
		 */
		public function ajax_call_autocomplete() {

			// Security
			check_ajax_referer( 'mycred-autocomplete' , 'token' );

			if ( ! is_user_logged_in() ) die;

			$results = array();
			$user_id = get_current_user_id();
			$prefs   = $this->transfers;

			// Let other play
			do_action( 'mycred_transfer_autofill_find', $prefs, $this->core );

			global $wpdb;

			// Query
			$select     = $prefs['autofill'];
			$blog_users = $wpdb->get_results( $wpdb->prepare( "
				SELECT {$select}, ID 
				FROM {$wpdb->users} 
				WHERE ID != %d 
					AND {$select} LIKE %s;", $user_id, '%' . $_REQUEST['string']['term'] . '%' ), 'ARRAY_N' );

			if ( $wpdb->num_rows > 0 ) {

				foreach ( $blog_users as $hit ) {

					if ( $this->core->exclude_user( $hit[1] ) ) continue;
					$results[] = $hit[0];

				}

			}

			die( json_encode( $results ) );

		}

		/**
		 * AJAX Transfer Creds
		 * @since 0.1
		 * @version 1.6.1
		 */
		public function ajax_call_transfer() {

			parse_str( $_POST['form'], $post );

			// Generate Transaction ID for our records
			$user_id        = get_current_user_id();
			$transaction_id = 'TXID' . current_time( 'timestamp' ) . $user_id;

			if ( mycred_force_singular_session( $user_id, 'mycred-last-transfer' ) )
				wp_send_json_error( 'error_9' );

			$request = shortcode_atts( apply_filters( 'mycred_new_transfer_request', array(
				'token'        => NULL,
				'recipient_id' => NULL,
				'user_id'      => 'current',
				'ctype'        => MYCRED_DEFAULT_TYPE_KEY,
				'amount'       => NULL,
				'reference'    => 'transfer'
			), $post ), $post['mycred_new_transfer'] );

			// Security
			if ( ! wp_verify_nonce( $request['token'], 'mycred-new-transfer-' . $request['reference'] ) )
				wp_send_json_error( 'error_1' );

			// Make sure add-on has been setup
			if ( ! isset( $this->transfers ) )
				wp_send_json_error( 'error_6' );

			// Make sure we are transfering an existing point type
			if ( ! mycred_point_type_exists( $request['ctype'] ) || ! in_array( $request['ctype'], $this->transfers['types'] ) )
				wp_send_json_error( 'error_10' );

			// Make sure we have a reference
			if ( $request['reference'] == '' )
				$request['reference'] = 'transfer';

			// Prep
			$point_type   = sanitize_key( $request['ctype'] );
			$mycred       = mycred( $point_type );
			$amount       = $mycred->number( abs( $request['amount'] ) );
			$recipient_id = $this->get_recipient( sanitize_text_field( $request['recipient_id'] ) );
			$reference    = sanitize_key( $request['reference'] );

			// If we insist on using a point type we are excluded from using
			if ( $mycred->exclude_user( $user_id ) )
				wp_send_json_error( 'error_4' );

			// Ok, lets start validating the request
			// Recipient not found
			if ( $recipient_id === false )
				wp_send_json_error( 'error_3' );

			// We are trying to transfer to ourselves
			if ( $recipient_id == $user_id )
				wp_send_json_error( 'error_4' );

			// The recipient is excluded from the point type
			if ( $mycred->exclude_user( $recipient_id ) )
				wp_send_json_error( 'error_4' );

			// Amount can not be zero
			if ( $amount == $mycred->zero() )
				wp_send_json_error( 'error_5' );

			// Check if we can complete this transaction before we run it
			$attempt_check = mycred_user_can_transfer( $user_id, $amount, $point_type, $reference );

			// Insufficient funds
			if ( $attempt_check === 'low' )
				wp_send_json_error( 'error_7' );

			// Limit reached
			elseif ( $attempt_check === 'limit' )
				wp_send_json_error( 'error_8' );

			// Let others play before we execute the transfer
			do_action( 'mycred_transfer_ready', $transaction_id, $request, $this->transfers );

			$data = apply_filters( 'mycred_transfer_data', array( 'ref_type' => 'user', 'tid' => $transaction_id ), $transaction_id, $request, $this->transfers );

			// Prevent Duplicate transactions
			if ( $mycred->has_entry( $reference, $recipient_id, $user_id, $data, $point_type ) )
				wp_send_json_error( 'error_9' );

			// First take the amount from the sender
			if ( $mycred->add_creds(
				$reference,
				$user_id,
				0 - $amount,
				$this->transfers['logs']['sending'],
				$recipient_id,
				$data,
				$point_type
			) ) {

				// Then add the amount to the receipient
				if ( ! $mycred->has_entry( $reference, $user_id, $recipient_id, $data, $point_type ) ) {

					$mycred->add_creds(
						$reference,
						$recipient_id,
						$amount,
						$this->transfers['logs']['receiving'],
						$user_id,
						$data,
						$point_type
					);

					// Let others play once transaction is completed
					do_action( 'mycred_transfer_completed', $transaction_id, $request, $this->transfers );

					// Return the good news
					wp_send_json_success( array(
						'css'     => '.mycred-balance-' . $request['ctype'],
						'balance' => $mycred->format_creds( $attempt_check ),
						'zero'    => ( ( $attempt_check <= $mycred->zero() ) ? true : false )
					) );

				}

			}

			wp_send_json_error( 'error_9' );

		}

		/**
		 * Settings Page
		 * @since 0.1
		 * @version 1.3
		 */
		public function after_general_settings( $mycred = NULL ) {

			// Settings
			$settings = $this->transfers;

			$before   = $this->core->before;
			$after    = $this->core->after;

			// Limits
			$limit    = $settings['limit']['limit'];
			$limits   = array(
				'none'   => __( 'No limits.', 'mycred' ),
				'daily'  => __( 'Impose daily limit.', 'mycred' ),
				'weekly' => __( 'Impose weekly limit.', 'mycred' )
			);
			$available_limits = apply_filters( 'mycred_transfer_limits', $limits, $settings );

			// Autofill by
			$autofill  = $settings['autofill'];
			$autofills = array(
				'user_login'   => __( 'User Login (user_login)', 'mycred' ),
				'user_email'   => __( 'User Email (user_email)', 'mycred' )
			);
			$available_autofill = apply_filters( 'mycred_transfer_autofill_by', $autofills, $settings );

			if ( ! isset( $settings['types'] ) )
				$settings['types'] = $this->default_prefs['types'];

?>
<h4><span class="dashicons dashicons-admin-plugins static"></span><?php _e( 'Transfers', 'mycred' ); ?></h4>
<div class="body" style="display:none;">

	<?php if ( count( $this->point_types ) > 1 ) : ?>

	<label class="subheader"><?php _e( 'Point Types', 'mycred' ); ?></label>
	<ol id="myCRED-transfer-logging-send">
		<li>
			<?php mycred_types_select_from_checkboxes( 'mycred_pref_core[transfers][types][]', 'mycred-transfer-type', $settings['types'] ); ?>

			<span class="description"><?php _e( 'Select the point types that users can transfer.', 'mycred' ); ?></span>
		</li>
	</ol>

	<?php else : ?>

	<input type="hidden" name="mycred_pref_core[transfers][types][]" value="mycred_default" />

	<?php endif; ?>

	<label class="subheader"><?php _e( 'Log template for sending', 'mycred' ); ?></label>
	<ol id="myCRED-transfer-logging-send">
		<li>
			<div class="h2"><input type="text" name="mycred_pref_core[transfers][logs][sending]" id="myCRED-transfer-log-sender" value="<?php echo esc_attr( $settings['logs']['sending'] ); ?>" class="long" /></div>
			<span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'user' ) ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Log template for receiving', 'mycred' ); ?></label>
	<ol id="myCRED-transfer-logging-receive">
		<li>
			<div class="h2"><input type="text" name="mycred_pref_core[transfers][logs][receiving]" id="myCRED-transfer-log-receiver" value="<?php echo esc_attr( $settings['logs']['receiving'] ); ?>" class="long" /></div>
			<span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'user' ) ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Autofill Recipient', 'mycred' ); ?></label>
	<ol id="myCRED-transfer-autofill-by">
		<li>
			<select name="mycred_pref_core[transfers][autofill]" id="myCRED-transfer-autofill">
<?php

			foreach ( $available_autofill as $key => $label ) {
				echo '<option value="' . $key . '"';
				if ( $settings['autofill'] == $key ) echo ' selected="selected"';
				echo '>' . $label . '</option>';
			}

?>
			</select><br />
			<span class="description"><?php _e( 'Select what user details recipients should be autofilled by.', 'mycred' ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Reload', 'mycred' ); ?></label>
	<ol id="myCRED-transfer-logging-receive">
		<li>
			<input type="checkbox" name="mycred_pref_core[transfers][reload]" id="myCRED-transfer-reload" <?php checked( $settings['reload'], 1 ); ?> value="1" /> <label for="myCRED-transfer-reload"><?php _e( 'Reload page on successful transfers.', 'mycred' ); ?></label>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Limits', 'mycred' ); ?></label>
	<ol id="myCRED-transfer-limits">
<?php

			// Loop though limits
			if ( ! empty( $limits ) ) {
				foreach ( $limits as $key => $description ) {

?>
		<li>
			<input type="radio" name="mycred_pref_core[transfers][limit][limit]" id="myCRED-limit-<?php echo $key; ?>" <?php checked( $limit, $key ); ?> value="<?php echo $key; ?>" />
			<label for="myCRED-limit-<?php echo $key; ?>"><?php echo $description; ?></label>
		</li>
<?php

				}
			}

?>
		<li class="empty">&nbsp;</li>
		<li>
			<label for="<?php echo $this->field_id( array( 'limit' => 'amount' ) ); ?>"><?php _e( 'Limit Amount', 'mycred' ); ?></label>
			<div class="h2"><?php echo $before; ?> <input type="text" name="<?php echo $this->field_name( array( 'limit' => 'amount' ) ); ?>" id="<?php echo $this->field_id( array( 'limit' => 'amount' ) ); ?>" value="<?php echo $this->core->number( $settings['limit']['amount'] ); ?>" size="8" /> <?php echo $after; ?></div>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Templates', 'mycred' ); ?></label>
	<ol>
		<li>
			<h3><?php _e( 'Visitors', 'mycred' ); ?></h3>
			<p class="description"><?php _e( 'The template to use when the transfer shortcode or widget is viewed by someone who is not logged in.', 'mycred' ); ?></p>
		</li>
		<li>
<?php

			wp_editor( $settings['templates']['login'], $this->field_id( array( 'templates' => 'login' ) ), array(
				'textarea_name' => $this->field_name( array( 'templates' => 'login' ) ),
				'textarea_rows' => 10
			) );

?>
		</li>
		<li class="empty">&nbsp;</li>
		<li>
			<h3><?php _e( 'Limit', 'mycred' ); ?></h3>
			<p class="description"><?php _e( 'The template to use if you select to show the transfer limit in the transfer shortcode or widget. Ignored if there is no limit enforced.', 'mycred' ); ?></p>
		</li>
		<li>
<?php

			wp_editor( $settings['templates']['limit'], $this->field_id( array( 'templates' => 'limit' ) ), array(
				'textarea_name' => $this->field_name( array( 'templates' => 'limit' ) ),
				'textarea_rows' => 10
			) );

			echo '<p>' . $this->core->available_template_tags( array( 'general' ), '%limit%', '%left%' ) . '</p>';

?>
		</li>
		<li class="empty">&nbsp;</li>
		<li>
			<h3><?php _e( 'Balance', 'mycred' ); ?></h3>
			<p class="description"><?php _e( 'The template to use if you select to show the users balance in the transfer shortcode or widget. Ignored if balances are not shown.', 'mycred' ); ?></p>
		</li>
		<li>
<?php

			wp_editor( $settings['templates']['balance'], $this->field_id( array( 'templates' => 'balance' ) ), array(
				'textarea_name' => $this->field_name( array( 'templates' => 'balance' ) ),
				'textarea_rows' => 5
			) );

			echo '<p>' . $this->core->available_template_tags( array( 'general', 'amount' ) ) . '</p>';

?>
		</li>
		<li class="empty">&nbsp;</li>
		<li>
			<label for="<?php echo $this->field_id( array( 'templates' => 'button' ) ); ?>"><?php _e( 'Default Button Label', 'mycred' ); ?></label>
			<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'templates' => 'button' ) ); ?>" id="<?php echo $this->field_id( array( 'templates' => 'button' ) ); ?>" value="<?php echo esc_attr( $settings['templates']['button'] ); ?>" class="medium code" /></div>
			<span class="description"><?php _e( 'The default transfer button label. You can override this in the shortcode or widget if needed.', 'mycred' ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Insufficient Funds Warning', 'mycred' ); ?></label>
	<ol>
		<li>
			<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'errors' => 'low' ) ); ?>" id="<?php echo $this->field_id( array( 'errors' => 'low' ) ); ?>" value="<?php echo esc_attr( $settings['errors']['low'] ); ?>" class="long" /></div>
			<span class="description"><?php _e( 'Message to show the user if they try to send more then they can afford.', 'mycred' ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Limit Reached Warning', 'mycred' ); ?></label>
	<ol>
		<li>
			<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'errors' => 'over' ) ); ?>" id="<?php echo $this->field_id( array( 'errors' => 'over' ) ); ?>" value="<?php echo esc_attr( $settings['errors']['over'] ); ?>" class="long" /></div>
			<span class="description"><?php _e( 'Message to show the user once they reach their transfer limit. Ignored if no limits are enforced.', 'mycred' ); ?></span>
		</li>
	</ol>
</div>
<?php

		}

		/**
		 * Sanitize & Save Settings
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function sanitize_extra_settings( $new_data, $data, $general ) {

			$new_data['transfers']['types']                = $data['transfers']['types'];
			$new_data['transfers']['logs']['sending']      = wp_kses_post( $data['transfers']['logs']['sending'] );
			$new_data['transfers']['logs']['receiving']    = wp_kses_post( $data['transfers']['logs']['receiving'] );
			$new_data['transfers']['autofill']             = sanitize_text_field( $data['transfers']['autofill'] );
			$new_data['transfers']['reload']               = ( isset( $data['transfers']['reload'] ) ) ? 1 : 0;

			$new_data['transfers']['templates']['login']   = wp_kses_post( $data['transfers']['templates']['login'] );
			$new_data['transfers']['templates']['balance'] = wp_kses_post( $data['transfers']['templates']['balance'] );
			$new_data['transfers']['templates']['limit']   = wp_kses_post( $data['transfers']['templates']['limit'] );
			$new_data['transfers']['templates']['button']  = sanitize_text_field( $data['transfers']['templates']['button'] );

			$new_data['transfers']['errors']['low']        = sanitize_text_field( $data['transfers']['errors']['low'] );
			$new_data['transfers']['errors']['over']       = sanitize_text_field( $data['transfers']['errors']['over'] );

			$new_data['transfers']['limit']['limit']       = sanitize_text_field( $data['transfers']['limit']['limit'] );
			$new_data['transfers']['limit']['amount']      = absint( $data['transfers']['limit']['amount'] );

			return $new_data;

		}

		/**
		 * Get Recipient
		 * @since 1.3.2
		 * @version 1.1.1
		 */
		public function get_recipient( $to = '' ) {

			if ( empty( $to ) ) return false;

			if ( is_numeric( $to ) && absint( $to ) !== 0 ) return absint( $to );

			switch ( $this->transfers['autofill'] ) {

				case 'user_login' :

					$user = get_user_by( 'login', $to );
					if ( $user === false ) return false;
					$user_id = $user->ID;

				break;

				case 'user_email' :

					$user = get_user_by( 'email', $to );
					if ( $user === false ) return false;
					$user_id = $user->ID;

				break;

				default :

					$user_id = apply_filters( 'mycred_transfer_autofill_get', false, $to );
					if ( $user_id === false ) return false;

				break;

			}

			return $user_id;

		}

		/**
		 * Add Email Notice Instance
		 * @since 1.5.4
		 * @version 1.0
		 */
		public function email_notice_instance( $events, $request ) {

			if ( $request['ref'] == 'transfer' ) {

				if ( $request['amount'] < 0 )
					$events[] = 'transfer|negative';

				elseif ( $request['amount'] > 0 )
					$events[] = 'transfer|positive';

			}

			return $events;

		}

		/**
		 * Support for Email Notices
		 * @since 1.1
		 * @version 1.1
		 */
		public function email_notices( $data ) {

			if ( $data['request']['ref'] == 'transfer' ) {
				$message = $data['message'];
				if ( $data['request']['ref_id'] == get_current_user_id() )
					$data['message'] = $this->core->template_tags_user( $message, false, wp_get_current_user() );
				else
					$data['message'] = $this->core->template_tags_user( $message, $data['request']['ref_id'] );
			}

			return $data;

		}

	}
endif;

/**
 * Load Transfer Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_transfer_addon' ) ) :
	function mycred_load_transfer_addon( $modules, $point_types ) {

		$modules['solo']['transfer'] = new myCRED_Transfer_Module();
		$modules['solo']['transfer']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_transfer_addon', 110, 2 );

?>