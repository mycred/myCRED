<?php
if ( ! defined( 'MYCRED_PURCHASE' ) ) exit;

/**
 * myCRED_buyCRED_Module class
 * @since 0.1
 * @version 1.4.1
 */
if ( ! class_exists( 'myCRED_buyCRED_Module' ) ) :
	class myCRED_buyCRED_Module extends myCRED_Module {

		public $purchase_log = '';

		/**
		 * Construct
		 */
		function __construct( $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( 'myCRED_BuyCRED_Module', array(
				'module_name' => 'gateways',
				'option_id'   => 'mycred_pref_buycreds',
				'defaults'    => array(
					'installed'     => array(),
					'active'        => array(),
					'gateway_prefs' => array()
				),
				'labels'      => array(
					'menu'        => __( 'Payment Gateways', 'mycred' ),
					'page_title'  => __( 'Payment Gateways', 'mycred' ),
					'page_header' => __( 'Payment Gateways', 'mycred' )
				),
				'screen_id'   => MYCRED_SLUG . '-gateways',
				'accordion'   => true,
				'add_to_core' => true,
				'menu_pos'    => 70
			), $type );

			// Adjust Module to the selected point type
			$this->mycred_type = MYCRED_DEFAULT_TYPE_KEY;
			if ( isset( $this->core->buy_creds['type'] ) )
				$this->mycred_type = $this->core->buy_creds['type'];

		}

		/**
		 * Load
		 * @version 1.0.2
		 */
		public function load() {

			add_filter( 'mycred_parse_log_entry',  array( $this, 'render_gift_tags' ), 10, 2 );

			add_action( 'mycred_init',             array( $this, 'module_init' ), $this->menu_pos );
			add_action( 'wp_loaded',               array( $this, 'module_run' ) );
			add_action( 'mycred_admin_init',       array( $this, 'module_admin_init' ), $this->menu_pos );

			add_action( 'mycred_admin_init',       array( $this, 'register_settings' ), $this->menu_pos+1 );
			add_action( 'mycred_add_menu',         array( $this, 'add_menu' ), $this->menu_pos );
			add_action( 'mycred_add_menu',         array( $this, 'add_to_menu' ), $this->menu_pos+1 );
			add_action( 'mycred_after_core_prefs', array( $this, 'after_general_settings' ) );
			add_filter( 'mycred_save_core_prefs',  array( $this, 'sanitize_extra_settings' ), 90, 3 );

		}

		/**
		 * Init
		 * Register shortcodes.
		 * @since 0.1
		 * @version 1.4
		 */
		public function module_init() {

			// Add shortcodes first
			add_shortcode( 'mycred_buy',      array( $this, 'render_shortcode_basic' ) );
			add_shortcode( 'mycred_buy_form', array( $this, 'render_shortcode_form' ) );

			$this->current_user_id = get_current_user_id();

		}

		/**
		 * Get Payment Gateways
		 * Retreivs all available payment gateways that can be used to buy CREDs.
		 * @since 0.1
		 * @version 1.1.1
		 */
		public function get() {

			$installed = array();

			// PayPal Standard
			$installed['paypal-standard'] = array(
				'title'       => 'PayPal Payments Standard',
				'callback'    => array( 'myCRED_PayPal_Standard' ),
				'icon'        => 'dashicons-admin-generic',
				'external'    => true,
				'custom_rate' => true
			);

			// BitPay
			$installed['bitpay'] = array(
				'title'       => 'BitPay (Bitcoins)',
				'callback'    => array( 'myCRED_Bitpay' ),
				'icon'        => 'dashicons-admin-generic',
				'external'    => true,
				'custom_rate' => true
			);

			// NetBilling
			$installed['netbilling'] = array(
				'title'       => 'NETBilling',
				'callback'    => array( 'myCRED_NETbilling' ),
				'icon'        => 'dashicons-admin-generic',
				'external'    => true,
				'custom_rate' => true
			);

			// Skrill
			$installed['skrill'] = array(
				'title'       => 'Skrill (Moneybookers)',
				'callback'    => array( 'myCRED_Skrill' ),
				'icon'        => 'dashicons-admin-generic',
				'external'    => true,
				'custom_rate' => true
			);

			// Zombaio
			$installed['zombaio'] = array(
				'title'       => 'Zombaio',
				'callback'    => array( 'myCRED_Zombaio' ),
				'icon'        => 'dashicons-admin-generic',
				'external'    => true,
				'custom_rate' => false
			);

			// Bank Transfers
			$installed['bank'] = array(
				'title'       => __( 'Bank Transfer', 'mycred' ),
				'callback'    => array( 'myCRED_Bank_Transfer' ),
				'icon'        => 'dashicons-admin-generic',
				'external'    => false,
				'custom_rate' => true
			);

			$installed = apply_filters( 'mycred_setup_gateways', $installed );

			// Untill all custom gateways have been updated, make sure all gateways have an external setting
			if ( ! empty( $installed ) ) {
				foreach ( $installed as $id => $settings ) {

					if ( ! array_key_exists( 'external', $settings ) )
						$installed[ $id ]['external'] = true;

					if ( ! array_key_exists( 'custom_rate', $settings ) )
						$installed[ $id ]['custom_rate'] = false;

				}
			}

			return $installed;

		}

		/**
		 * Run
		 * Runs a gateway if requested.
		 * @since 1.7
		 * @version 1.0
		 */
		public function module_run() {

			// Prep
			$installed = $this->get();

			// Make sure we have installed gateways.
			if ( empty( $installed ) ) return;

			/**
			 * Step 1 - Look for returns
			 * Runs though all active payment gateways and lets them decide if this is the
			 * user returning after a remote purchase. Each gateway should know what to look
			 * for to determen if they are responsible for handling the return.
			 */
			foreach ( $installed as $id => $data ) {

				// Only applicable if the gateway is active and marked as external (new in 1.7)
				if ( $this->is_active( $id ) && $data['external'] === true )
					$this->call( 'returning', $installed[ $id ]['callback'] );

			}

			/**
			 * Step 2 - Check for gateway calls
			 * Checks to see if a gateway should be loaded.
			 */
			$gateway_id = false;
			$process    = false;

			if ( isset( $_REQUEST['mycred_call'] ) )
				$gateway_id = trim( $_REQUEST['mycred_call'] );

			elseif ( isset( $_REQUEST['mycred_buy'] ) && is_user_logged_in() )
				$gateway_id = trim( $_REQUEST['mycred_buy'] );

			elseif ( isset( $_REQUEST['wp_zombaio_ips'] ) || isset( $_REQUEST['ZombaioGWPass'] ) ) {
				$gateway_id = 'zombaio';
				$process    = true;
			}

			$gateway_id = apply_filters( 'mycred_gateway_id', $gateway_id );

			// If we have a valid gateway ID and the gateway is active, lets run that gateway.
			if ( $gateway_id !== false && array_key_exists( $gateway_id, $installed ) && $this->is_active( $gateway_id ) ) {

				// Construct Gateway
				$gateway = buycred_gateway( $gateway_id );

				// Check payment processing
				if ( isset( $_REQUEST['mycred_call'] ) || ( $gateway_id == 'zombaio' && $process ) ) {

					$gateway->process();

					do_action( 'mycred_buycred_process', $gateway_id, $this->gateway_prefs, $this->core->buy_creds );
					do_action( "mycred_buycred_process_{$gateway_id}", $this->gateway_prefs, $this->core->buy_creds );

				}

				// Check purchase request
				if ( isset( $_REQUEST['mycred_buy'] ) ) {

					// Validate token
					$token = false;
					if ( isset( $_REQUEST['token'] ) && wp_verify_nonce( $_REQUEST['token'], 'mycred-buy-creds' ) )
						$token = true;

					// Validate amount ( amount is not zero and higher then minimum required )
					$amount = false;
					if ( isset( $_REQUEST['amount'] ) && is_numeric( $_REQUEST['amount'] ) && $_REQUEST['amount'] >= $this->core->buy_creds['minimum'] )
						$amount = true;

					if ( $token && $amount ) {

						$gateway->buy();

						do_action( 'mycred_buycred_buy', $gateway_id, $this->gateway_prefs, $this->core->buy_creds );
						do_action( "mycred_buycred_buy_{$gateway_id}", $this->gateway_prefs, $this->core->buy_creds );

					}

				}

			}

		}

		/**
		 * Admin Init
		 * @since 1.5
		 * @version 1.1
		 */
		function module_admin_init() {

			add_action( 'mycred_user_edit_after_balances', array( $this, 'exchange_rates_user_screen' ), 30 );

			add_action( 'personal_options_update',         array( $this, 'save_manual_exchange_rates' ), 30 );
			add_action( 'edit_user_profile_update',        array( $this, 'save_manual_exchange_rates' ), 30 );

			// Prep
			$installed = $this->get();

			// Make sure we have installed gateways.
			if ( empty( $installed ) ) return;

			/**
			 * Admin Init
			 * Runs though all installed gateways to allow admin inits.
			 */
			foreach ( $installed as $id => $data )
				$this->call( 'admin_init', $installed[ $id ]['callback'] );

		}

		/**
		 * Add to General Settings
		 * @since 0.1
		 * @version 1.2
		 */
		public function after_general_settings( $mycred = NULL ) {

			// Reset while on this screen so we can use $this->field_id() and $this->field_name()
			$this->module_name = 'buy_creds';
			$this->option_id   = '';

			// Since we are both registering our own settings and want to hook into
			// the core settings, we need to define our "defaults" here.
			$defaults = array(
				'minimum'    => 1,
				'types'      => array( MYCRED_DEFAULT_TYPE_KEY ),
				'exchange'   => 1,
				'log'        => '%plural% purchase',
				'login'      => __( 'Please login to purchase %_plural%', 'mycred' ),
				'custom_log' => 0,
				'thankyou'   => array(
					'use'        => 'page',
					'custom'     => '',
					'page'       => ''
				),
				'cancelled'  => array(
					'use'        => 'custom',
					'custom'     => '',
					'page'       => ''
				),
				'gifting'    => array(
					'members'    => 1,
					'authors'    => 1,
					'log'        => __( 'Gift purchase from %display_name%.', 'mycred' )
				)
			);

			if ( isset( $this->core->buy_creds ) )
				$settings = $this->core->buy_creds;
			else
				$settings = $defaults;

?>
<h4><span class="dashicons dashicons-admin-plugins static"></span><strong>buy</strong>CRED</h4>
<div class="body" style="display:none;">

	<h3><?php _e( 'Features', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="mycred-transfer-type"><?php _e( 'Point Types', 'mycred' ); ?></label>
				<?php if ( count( $this->point_types ) > 1 ) : ?>
				<?php mycred_types_select_from_checkboxes( 'mycred_pref_core[buy_creds][types][]', $this->field_id( 'types' ), $settings['types'] ); ?>
				<p><span class="description"><?php _e( 'Select the point types that users can buy. You must select at least one!', 'mycred' ); ?></span></p>
				<?php else : ?>
				<p class="form-control-static"><?php echo $this->core->plural(); ?></p>
				<input type="hidden" name="mycred_pref_core[buy_creds][types][]" value="<?php echo MYCRED_DEFAULT_TYPE_KEY; ?>" />
				<?php endif; ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( 'minimum' ); ?>"><?php echo $this->core->template_tags_general( __( 'Minimum %plural%', 'mycred' ) ); ?></label>
				<input type="text" name="<?php echo $this->field_name( 'minimum' ); ?>" id="<?php echo $this->field_id( 'minimum' ); ?>" class="form-control" placeholder="<?php _e( 'Required', 'mycred' ); ?>" value="<?php echo esc_attr( $settings['minimum'] ); ?>" />
				<p><span class="description"><?php echo $this->core->template_tags_amount( __( 'Minimum amount of %plural% a user must purchase. Will default to %cred%.', 'mycred' ), 1 ); ?></span></p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<div class="checkbox">
					<label for="<?php echo $this->field_id( 'custom_log' ); ?>"><input type="checkbox" name="<?php echo $this->field_name( 'custom_log' ); ?>" id="<?php echo $this->field_id( 'custom_log' ); ?>"<?php checked( $settings['custom_log'], 1 ); ?> value="1" /> <?php echo $this->core->template_tags_general( __( 'Show seperate log for %_plural% purchases.', 'mycred' ) ); ?></label>
				</div>
			</div>
		</div>
	</div>

	<h3><?php _e( 'Redirects', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<p style="margin-top: 0;"><span class="description"><?php _e( 'Where should users be redirected to upon successfully completing a purchase. You can nominate a specific URL or a page.', 'mycred' ); ?></span></p>
			</div>
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'thankyou' => 'page' ) ); ?>"><?php _e( 'Redirect to Page', 'mycred' ); ?></label>
<?php

			// Thank you page dropdown
			$thankyou_args = array(
				'name'             => $this->field_name( array( 'thankyou' => 'page' ) ),
				'id'               => $this->field_id( array( 'thankyou' => 'page' ) ) . '-id',
				'selected'         => $settings['thankyou']['page'],
				'show_option_none' => __( 'Select', 'mycred' ),
				'class'            => 'form-control'
			);
			wp_dropdown_pages( $thankyou_args );

?>
			</div>
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'thankyou' => 'custom' ) ); ?>"><?php _e( 'Redirect to URL', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'thankyou' => 'custom' ) ); ?>" id="<?php echo $this->field_id( array( 'thankyou' => 'custom' ) ); ?>" placeholder="https://" class="form-control" value="<?php echo esc_attr( $settings['thankyou']['custom'] ); ?>" />
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<p style="margin-top: 0;"><span class="description"><?php _e( 'Where should users be redirected to if they cancel a transaction. You can nominate a specific URL or a page.', 'mycred' ); ?></span></p>
			</div>
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'cancelled' => 'page' ) ); ?>"><?php _e( 'Redirect to Page', 'mycred' ); ?></label>
<?php

			// Thank you page dropdown
			$thankyou_args = array(
				'name'             => $this->field_name( array( 'cancelled' => 'page' ) ),
				'id'               => $this->field_id( array( 'cancelled' => 'page' ) ) . '-id',
				'selected'         => $settings['cancelled']['page'],
				'show_option_none' => __( 'Select', 'mycred' ),
				'class'            => 'form-control'
			);
			wp_dropdown_pages( $thankyou_args );

?>
			</div>
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'cancelled' => 'custom' ) ); ?>"><?php _e( 'Redirect to URL', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'cancelled' => 'custom' ) ); ?>" id="<?php echo $this->field_id( array( 'cancelled' => 'custom' ) ); ?>" placeholder="https://" class="form-control" value="<?php echo esc_attr( $settings['cancelled']['custom'] ); ?>" />
			</div>
		</div>
	</div>

	<h3><?php _e( 'Templates', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( 'login' ); ?>"><?php _e( 'Login Message', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( 'login' ); ?>" id="<?php echo $this->field_id( 'login' ); ?>" class="form-control" value="<?php echo esc_attr( $settings['login'] ); ?>" />
				<p><span class="description"><?php _e( 'Message to show in shortcodes when viewed by someone who is not logged in.', 'mycred' ); ?></span></p>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( 'log' ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( 'log' ); ?>" id="<?php echo $this->field_id( 'log' ); ?>" class="form-control" placeholder="<?php _e( 'Required', 'mycred' ); ?>" value="<?php echo esc_attr( $settings['log'] ); ?>" />
				<p><span class="description"><?php echo $this->core->available_template_tags( array( 'general' ), '%gateway%' ); ?></span></p>
			</div>
		</div>
	</div>

	<h3><?php _e( 'Gifting', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<div class="checkbox">
					<label for="<?php echo $this->field_id( array( 'gifting' => 'members' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'gifting' => 'members' ) ); ?>" id="<?php echo $this->field_id( array( 'gifting' => 'members' ) ); ?>"<?php checked( $settings['gifting']['members'], 1 ); ?> value="1" /> <?php echo $this->core->template_tags_general( __( 'Allow users to buy %_plural% for other users.', 'mycred' ) ); ?></label>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<div class="checkbox">
					<label for="<?php echo $this->field_id( array( 'gifting' => 'authors' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'gifting' => 'authors' ) ); ?>" id="<?php echo $this->field_id( array( 'gifting' => 'authors' ) ); ?>"<?php checked( $settings['gifting']['authors'], 1 ); ?> value="1" /> <?php echo $this->core->template_tags_general( __( 'Allow users to buy %_plural% for content authors.', 'mycred' ) ); ?></label>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( 'gifting' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( 'gifting' => 'log' ) ); ?>" id="<?php echo $this->field_id( 'log' ); ?>" class="form-control" placeholder="<?php _e( 'Required', 'mycred' ); ?>" value="<?php echo esc_attr( $settings['gifting']['log'] ); ?>" />
				<p><span class="description"><?php echo $this->core->available_template_tags( array( 'general', 'user' ) ); ?></span></p>
			</div>
		</div>
	</div>

	<h3 style="margin-bottom: 0;"><?php _e( 'Available Shortcodes', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<p><a href="http://codex.mycred.me/shortcodes/mycred_buy/" target="_blank">[mycred_buy]</a>, <a href="http://codex.mycred.me/shortcodes/mycred_buy_form/" target="_blank">[mycred_buy_form]</a>, <a href="http://codex.mycred.me/shortcodes/mycred_buy_pending/" target="_blank">[mycred_buy_pending]</a></p>
		</div>
	</div>

</div>
<?php

			$this->module_name = 'gateways';
			$this->option_id   = 'mycred_pref_buycreds';

		}

		/**
		 * Save Settings
		 * @since 0.1
		 * @version 1.1.2
		 */
		public function sanitize_extra_settings( $new_data, $data, $core ) {

			if ( ! isset( $data['buy_creds']['types'] ) )
				$new_data['buy_creds']['types'] = array( MYCRED_DEFAULT_TYPE_KEY );
			else
				$new_data['buy_creds']['types'] = $data['buy_creds']['types'];

			$new_data['buy_creds']['minimum']             = abs( $data['buy_creds']['minimum'] );
			$new_data['buy_creds']['log']                 = sanitize_text_field( $data['buy_creds']['log'] );
			$new_data['buy_creds']['login']               = wp_kses_post( $data['buy_creds']['login'] );

			$new_data['buy_creds']['thankyou']['page']    = absint( $data['buy_creds']['thankyou']['page'] );
			$new_data['buy_creds']['thankyou']['custom']  = sanitize_text_field( $data['buy_creds']['thankyou']['custom'] );
			$new_data['buy_creds']['thankyou']['use']     = ( $new_data['buy_creds']['thankyou']['custom'] != '' ) ? 'custom' : 'page';

			$new_data['buy_creds']['cancelled']['page']   = absint( $data['buy_creds']['cancelled']['page'] );
			$new_data['buy_creds']['cancelled']['custom'] = sanitize_text_field( $data['buy_creds']['cancelled']['custom'] );
			$new_data['buy_creds']['cancelled']['use']    = ( $new_data['buy_creds']['cancelled']['custom'] != '' ) ? 'custom' : 'page';

			$new_data['buy_creds']['custom_log']          = ( ! isset( $data['buy_creds']['custom_log'] ) ) ? 0 : 1;

			$new_data['buy_creds']['gifting']['members']  = ( ! isset( $data['buy_creds']['gifting']['members'] ) ) ? 0 : 1;
			$new_data['buy_creds']['gifting']['authors']  = ( ! isset( $data['buy_creds']['gifting']['authors'] ) ) ? 0 : 1;
			$new_data['buy_creds']['gifting']['log']      = sanitize_text_field( $data['buy_creds']['gifting']['log'] );

			delete_option( 'mycred_buycred_reset' );

			return $new_data;

		}

		/**
		 * Render Gift Tags
		 * @since 1.4.1
		 * @version 1.0
		 */
		public function render_gift_tags( $content, $log ) {

			if ( substr( $log->ref, 0, 15 ) != 'buy_creds_with_' ) return $content;
			return $this->core->template_tags_user( $content, absint( $log->ref_id ) );

		}

		/**
		 * Add Admin Menu Item
		 * @since 0.1
		 * @version 1.1.1
		 */
		public function add_to_menu() {

			if ( isset( $this->core->buy_creds['custom_log'] ) && $this->core->buy_creds['custom_log'] ) {

				$types = array( MYCRED_DEFAULT_TYPE_KEY );
				if ( isset( $this->core->buy_creds['types'] ) )
					$types = $this->core->buy_creds['types'];

				$pages = array();
				foreach ( $types as $type ) {

					$mycred    = mycred( $type );
					$menu_slug = MYCRED_SLUG;

					if ( $type != MYCRED_DEFAULT_TYPE_KEY )
						$menu_slug = MYCRED_SLUG . '_' . trim( $type );

					$pages[] = add_submenu_page(
						$menu_slug,
						__( 'buyCRED Purchase Log', 'mycred' ),
						__( 'Purchase Log', 'mycred' ),
						$mycred->edit_plugin_cap(),
						MYCRED_SLUG . '-purchases-' . $type,
						array( $this, 'purchase_log_page' )
					);

				}

				foreach ( $pages as $page ) {

					add_action( 'admin_print_styles-' . $page, array( $this, 'settings_page_enqueue' ) );
					add_action( 'load-' . $page,               array( $this, 'screen_options' ) );

				}

				$this->purchase_log = $pages;

			}

		}

		/**
		 * Page Header
		 * @since 1.3
		 * @version 1.1
		 */
		public function settings_header() {

			wp_enqueue_style( 'mycred-admin' );
			wp_enqueue_style( 'mycred-bootstrap-grid' );
			wp_enqueue_style( 'mycred-forms' );

		}

		/**
		 * Payment Gateways Page
		 * @since 0.1
		 * @version 1.2.2
		 */
		public function admin_page() {

			// Security
			if ( ! $this->core->can_edit_creds() ) wp_die( 'Access Denied' );

			$installed = $this->get();

?>
<div class="wrap list mycred-metabox" id="myCRED-wrap">
	<h1><?php printf( __( '%s Payment Gateways', 'mycred' ), '<strong>buy</strong>CRED' ); ?> <a href="<?php echo esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-settings', 'open-tab' => 'buycred_module' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action"><?php _e( 'Settings', 'mycred' ); ?></a></h1>
<?php

			// Updated settings
			if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true )
				echo '<div class="updated settings-error"><p>' . __( 'Settings Updated', 'mycred' ) . '</p></div>';

?>
	<form method="post" action="options.php" class="form">

		<?php settings_fields( $this->settings_name ); ?>

		<?php do_action( 'mycred_before_buycreds_page', $this ); ?>

		<div class="list-items expandable-li" id="accordion">
<?php

			if ( ! empty( $installed ) ) {
				foreach ( $installed as $key => $data ) {

					if ( ! array_key_exists( 'icon', $data ) )
						$data['icon'] = 'dashicons-admin-plugins';

?>
			<h4><span class="dashicons <?php echo $data['icon']; ?><?php if ( $this->is_active( $key ) ) { if ( array_key_exists( 'sandbox', $this->gateway_prefs[ $key ] ) && $this->gateway_prefs[ $key ]['sandbox'] === 1 ) echo ' debug'; else echo ' active'; } else echo ' static'; ?>"></span><?php echo $this->core->template_tags_general( $data['title'] ); ?></h4>
			<div class="body" style="display: none;">
				<label class="subheader"><?php _e( 'Enable', 'mycred' ); ?></label>
				<ol>
					<li>
						<input type="checkbox" name="mycred_pref_buycreds[active][]" id="mycred-gateway-<?php echo $key; ?>" value="<?php echo $key; ?>"<?php if ( $this->is_active( $key ) ) echo ' checked="checked"'; ?> />
					</li>
				</ol>

				<?php if ( array_key_exists( $key, $this->gateway_prefs ) && array_key_exists( 'sandbox', $this->gateway_prefs[ $key ] ) ) : ?>

				<label class="subheader" for="mycred-gateway-<?php echo $key; ?>-sandbox"><?php _e( 'Sandbox Mode', 'mycred' ); ?></label>
				<ol>
					<li>
						<input type="checkbox" name="mycred_pref_buycreds[gateway_prefs][<?php echo $key; ?>][sandbox]" id="mycred-gateway-<?php echo $key; ?>-sandbox" value="1"<?php checked( $this->gateway_prefs[ $key ]['sandbox'], 1 ); ?> /> <span class="description"><?php _e( 'Enable for test purchases.', 'mycred' ); ?></span>
					</li>
				</ol>

				<?php endif; ?>

				<?php $this->call( 'preferences', $data['callback'] ); ?>

				<input type="hidden" name="mycred_pref_buycreds[installed]" value="<?php echo $key; ?>" />
			</div>
<?php

				}
			}

?>
		</div>

		<?php do_action( 'mycred_after_buycreds_page', $this ); ?>

		<p><?php submit_button( __( 'Update Settings', 'mycred' ), 'primary large', 'submit', false ); ?> <a href="http://mycred.me/product-category/buycred-gateways/" class="button button-secondary button-large" target="_blank"><?php _e( 'More Gateways', 'mycred' ); ?></a></p>

	</form>

	<?php do_action( 'mycred_bottom_buycreds_page', $this ); ?>

<script type="text/javascript">
jQuery(function($) {
	$( 'select.currency' ).change(function(){
		var target = $(this).attr( 'data-update' );
		$( '.' + target ).empty();
		$( '.' + target ).text( $(this).val() );
	});
});
</script>
</div>
<?php

		}

		/**
		 * Sanititze Settings
		 * @since 0.1
		 * @version 1.3.1
		 */
		public function sanitize_settings( $data ) {

			$data      = apply_filters( 'mycred_buycred_save_prefs', $data );
			$installed = $this->get();

			if ( empty( $installed ) ) return $data;

			foreach ( $installed as $id => $gdata )
				$data['gateway_prefs'][ $id ] = $this->call( 'sanitise_preferences', $installed[ $id ]['callback'], ( ( array_key_exists( $id, $data['gateway_prefs'] ) ) ? $data['gateway_prefs'][ $id ] : array() ) );

			return $data;

		}

		/**
		 * Purchase Log Screen Options
		 * @since 1.4
		 * @version 1.1
		 */
		public function screen_options() {

			if ( empty( $this->purchase_log ) ) return;

			$meta_key = 'mycred_payments_' . str_replace( MYCRED_SLUG . '-purchases-', '', $_GET['page'] );

			if ( isset( $_REQUEST['wp_screen_options']['option'] ) && isset( $_REQUEST['wp_screen_options']['value'] ) ) {
			
				if ( $_REQUEST['wp_screen_options']['option'] == $meta_key ) {
					$value = absint( $_REQUEST['wp_screen_options']['value'] );
					update_user_meta( $this->current_user_id, $meta_key, $value );
				}

			}

			$args = array(
				'label'   => __( 'Payments', 'mycred' ),
				'default' => 10,
				'option'  => $meta_key
			);
			add_screen_option( 'per_page', $args );

		}

		/**
		 * Purchase Log
		 * Render the dedicated admin screen where all point purchases are shown from the myCRED Log.
		 * This screen is added in for each point type that is set to be for sale.
		 * @since 1.4
		 * @version 1.4.2
		 */
		public function purchase_log_page() {

			$point_type           = str_replace( 'mycred-purchases-', '', $_GET['page'] );
			$installed            = $this->get();

			$mycred = $this->core;
			if ( $point_type != MYCRED_DEFAULT_TYPE_KEY && mycred_point_type_exists( $point_type ) )
				$mycred = mycred( $point_type );

			// Security (incase the user has setup different capabilities to manage this particular point type)
			if ( ! $mycred->can_edit_creds() ) wp_die( 'Access Denied' );

			$search_args          = mycred_get_search_args();

			$per_page             = mycred_get_user_meta( $this->current_user_id, 'mycred_payments_' . $point_type, '', true );
			if ( empty( $per_page ) || $per_page < 1 ) $per_page = 10;

			// Entries per page
			if ( ! array_key_exists( 'number', $search_args ) )
				$search_args['number'] = absint( $per_page );

			// Get references
			$references           = apply_filters( 'mycred_buycred_log_refs', array(
				'buy_creds_with_paypal_standard',
				'buy_creds_with_skrill',
				'buy_creds_with_zombaio',
				'buy_creds_with_netbilling',
				'buy_creds_with_bitpay',
				'buy_creds_with_bank'
			), $this, $point_type );

			$search_args['ctype'] = $point_type;

			$search_args['ref']   = array(
				'ids'     => $references,
				'compare' => 'IN'
			);

			$log                  = new myCRED_Query_Log( $search_args );

			$log->headers         = apply_filters( 'mycred_buycred_log_columns', array(
				'column-gateway'  => __( 'Gateway', 'mycred' ),
				'column-username' => __( 'Buyer', 'mycred' ),
				'column-date'     => __( 'Date', 'mycred' ),
				'column-amount'   => $mycred->plural(),
				'column-payed'    => __( 'Payed', 'mycred' ),
				'column-tranid'   => __( 'Transaction ID', 'mycred' )
			) );

			$filter_url           = admin_url( 'admin.php?page=' . MYCRED_SLUG . '-purchases-' . $point_type );

?>
<div class="wrap list" id="myCRED-wrap">
	<h1><?php _e( 'Purchase Log', 'mycred' ); ?></h1>

	<?php $log->filter_dates( esc_url( $filter_url ) ); ?>

	<form method="get" action="" name="mycred-buycred-form" novalidate>
		<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
<?php

			if ( array_key_exists( 's', $search_args ) )
				echo '<input type="hidden" name="s" value="' . esc_attr( $search_args['s'] ) . '" />';

			if ( isset( $_GET['ref'] ) )
				echo '<input type="hidden" name="show" value="' . esc_attr( $_GET['ref'] ) . '" />';

			if ( isset( $_GET['show'] ) )
				echo '<input type="hidden" name="show" value="' . esc_attr( $_GET['show'] ) . '" />';

			if ( array_key_exists( 'order', $search_args ) )
				echo '<input type="hidden" name="order" value="' . esc_attr( $search_args['order'] ) . '" />';

			if ( array_key_exists( 'paged', $search_args ) )
				echo '<input type="hidden" name="paged" value="' . esc_attr( $search_args['paged'] ) . '" />';

			$log->search();

?>

		<?php do_action( 'mycred_above_payment_log_table', $this ); ?>

		<div class="tablenav top">

			<?php $log->table_nav( 'top' ); ?>

		</div>
		<table class="table wp-list-table widefat mycred-table log-entries" cellspacing="0">
			<thead>
				<tr>
<?php

			foreach ( $log->headers as $col_id => $col_title )
				echo '<th scope="col" id="' . str_replace( 'column-', '', $col_id ) . '" class="manage-column ' . $col_id . '">' . $col_title . '</th>';

?>
				</tr>
			</thead>
			<tfoot>
				<tr>
<?php

			foreach ( $log->headers as $col_id => $col_title )
				echo '<th scope="col" class="manage-column ' . $col_id . '">' . $col_title . '</th>';

?>
				</tr>
			</tfoot>
			<tbody id="the-list">
<?php

			// If we have results
			if ( $log->have_entries() ) {

				// Prep
				$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$entry_data  = '';
				$alt         = 0;

				// Loop results
				foreach ( $log->results as $log_entry ) {

					// Highlight alternate rows
					$alt = $alt+1;
					if ( $alt % 2 == 0 )
						$class = ' alt';
					else
						$class = '';

					// Prep Sales data for use in columns
					$sales_data = $this->get_sales_data_from_log_data( $log_entry->data );
					list ( $buyer_id, $payer_id, $amount, $cost, $currency, $token, $other ) = $sales_data;

					// Default Currency
					if ( empty( $currency ) )
						$currency = 'USD';

					$gateway_name = str_replace( 'buy_creds_with_', '', $log_entry->ref );

					// Color rows based on if the transaction was made in Sandbox mode or using a gateway that no longer is used.
					if ( ! array_key_exists( str_replace( '_', '-', $gateway_name ), $installed ) )
						$style = ' style="color:silver;"';
					elseif ( ! $this->is_active( str_replace( '_', '-', $gateway_name ) ) )
						$style = ' style="color:gray;"';
					elseif ( substr( $log_entry->entry, 0, 4 ) == 'TEST' )
						$style = ' style="color:orange;"';
					else
						$style = '';

					echo '<tr class="myCRED-log-row' . $class . '" id="mycred-log-entry-' . $log_entry->id . '">';

					// Run though columns
					foreach ( $log->headers as $column_id => $column_name ) {

						echo '<td class="' . $column_id . '"' . $style . '>';

						switch ( $column_id ) {

							// Used gateway
							case 'column-gateway' :

								$gateway = str_replace( array( '-', '_' ), ' ', $gateway_name );
								echo ucwords( $gateway );

							break;

							// Username Column
							case 'column-username' :

								$user = get_userdata( $log_entry->user_id );
								if ( $user === false )
									echo 'ID: ' . $log_entry->user_id;
								else
									echo $user->display_name . ' <em><small>(ID: ' . $log_entry->user_id . ')</small></em>';

							break;

							// Date & Time Column
							case 'column-date' :

								echo date( $date_format, $log_entry->time );

							break;

							// Amount Column
							case 'column-amount' :

								echo $mycred->format_creds( $log_entry->creds );

							break;

							// Amount Paid
							case 'column-payed' :

								$cost     = 'n/a';
								$currency = '';
								$data     = maybe_unserialize( $log_entry->data );
								if ( is_array( $data ) && array_key_exists( 'sales_data', $data ) ) {

									$sales_data = explode( '|', $data['sales_data'] );
									if ( count( $sales_data ) >= 5 ) {
										$cost     = $sales_data[3];
										$currency = $sales_data[4];
									}

								}

								if ( $cost === 'n/a' )
									echo 'n/a';

								else {

									$rendered_cost = apply_filters( 'mycred_buycred_display_cost',                  $cost . ' ' . $currency, $sales_data, $log_entry, $gateway_name );
									$rendered_cost = apply_filters( 'mycred_buycred_display_cost_' . $gateway_name, $rendered_cost, $sales_data, $log_entry );

									echo $rendered_cost;

								}

							break;

							// Transaction ID
							case 'column-tranid' :

								$transaction_id = $log_entry->time . $log_entry->user_id;
								$saved_data     = maybe_unserialize( $log_entry->data );
								if ( isset( $saved_data['txn_id'] ) )
									$transaction_id = $saved_data['txn_id'];
								elseif ( isset( $saved_data['transaction_id'] ) )
									$transaction_id = $saved_data['transaction_id'];

								echo $transaction_id;

							break;

							default :

								do_action( "mycred_payment_log_{$column_id}", $log_entry );
								do_action( "mycred_payment_log_{$column_id}_{$type}", $log_entry );

							break;

						}

						echo '</td>';

					}

					echo '</tr>';

				}

			}

			// No log entry
			else {

				echo '<tr><td colspan="' . count( $log->headers ) . '" class="no-entries">' . __( 'No purchases found', 'mycred' ) . '</td></tr>';

			}

?>
			</tbody>
		</table>
		<div class="tablenav bottom">

			<?php $log->table_nav( 'bottom' ); ?>

		</div>

		<?php do_action( 'mycred_below_payment_log_table', $this ); ?>

	</form>
</div>
<?php

		}

		/**
		 * Get Sales Data from Log Data
		 * @since 1.4
		 * @version 1.0.1
		 */
		public function get_sales_data_from_log_data( $log_data = '' ) {

			$defaults = array( '', '', '', '', '', '', '' );
			$log_data = maybe_unserialize( $log_data );

			$found_data = array();
			if ( is_array( $log_data ) && array_key_exists( 'sales_data', $log_data ) ) {
				if ( is_array( $log_data['sales_data'] ) )
					$found_data = $log_data['sales_data'];
				else
					$found_data = explode( '|', $log_data['sales_data'] );
			}
			elseif ( ! empty( $log_data ) && ! is_array( $log_data ) ) {
				$try = explode( '|', $log_data );
				if ( count( $try == 7 ) )
					$found_data = $log_data;
			}

			return wp_parse_args( $found_data, $defaults );

		}

		/**
		 * User Rates Admin Screen
		 * @since 1.5
		 * @version 1.0
		 */
		public function exchange_rates_user_screen( $user ) {

			// Make sure buyCRED is setup
			if ( ! isset( $this->core->buy_creds['types'] ) || empty( $this->core->buy_creds['types'] ) ) return;

			// Only visible to admins
			if ( ! mycred_is_admin() ) return;

			$mycred_types         = mycred_get_types( true );
			$point_types_for_sale = $this->core->buy_creds['types'];
			$installed            = $this->get();
			$available_options    = array();

			foreach ( $installed as $gateway_id => $prefs ) {

				// Gateway is not active or settings have not yet been saved
				if ( ! $this->is_active( $gateway_id ) || ! array_key_exists( $gateway_id, $this->gateway_prefs ) || ! $prefs['custom_rate'] ) continue;

				$gateway_prefs = $this->gateway_prefs[ $gateway_id ];

				// Need a currency
				if ( array_key_exists( 'currency', $gateway_prefs ) && $gateway_prefs['currency'] == '' ) continue;

				if ( ! array_key_exists( 'currency', $gateway_prefs ) )
					$gateway_prefs['currency'] = 'USD';

				$setup = array( 'name' => $prefs['title'], 'currency' => $gateway_prefs['currency'], 'types' => array() );

				foreach ( $mycred_types as $point_type_key => $label ) {

					$row = array( 'name' => $label, 'enabled' => false, 'excluded' => true, 'default' => 0, 'override' => false, 'custom' => '', 'before' => '' );

					if ( in_array( $point_type_key, $point_types_for_sale ) && array_key_exists( $point_type_key, $gateway_prefs['exchange'] ) ) {

						$row['enabled'] = true;

						$mycred = mycred( $point_type_key );

						if ( ! $mycred->exclude_user( $user->ID ) ) {

							$row['excluded'] = false;
							$row['default']  = $gateway_prefs['exchange'][ $point_type_key ];

							$row['before']   = $mycred->format_creds( 1 ) . ' = ';

							$saved_overrides = (array) mycred_get_user_meta( $user->ID, 'mycred_buycred_rates_' . $point_type_key, '', true );

							if ( ! empty( $saved_overrides ) && array_key_exists( $gateway_id, $saved_overrides ) ) {

								$row['override'] = true;
								$row['custom']   = $saved_overrides[ $gateway_id ];

							}

						}

					}

					$setup['types'][ $point_type_key ] = $row;

				}

				$available_options[ $gateway_id ] = $setup;

			}

			if ( empty( $available_options ) ) return;

?>
<p class="mycred-p"><?php _e( 'Users exchange rate when buying points.', 'mycred' ); ?></p>
<table class="form-table mycred-inline-table">
<?php

			foreach ( $available_options as $gateway_id => $setup ) :

?>
	<tr>
		<th scope="row"><?php echo esc_attr( $setup['name'] ); ?></th>
		<td>
			<fieldset id="mycred-buycred-list" class="buycred-list">
				<legend class="screen-reader-text"><span><?php _e( 'buyCRED Exchange Rates', 'mycred' ); ?></span></legend>
<?php

				foreach ( $setup['types'] as $type_id => $data ) {

					// This point type is not for sale
					if ( ! $data['enabled'] ) {

?>
					<div class="mycred-wrapper buycred-wrapper disabled-option color-option">
						<div><?php printf( _x( 'Buying %s', 'Points Name', 'mycred' ), $data['name'] ); ?></div>
						<div class="balance-row">
							<div class="balance-view"><?php _e( 'Disabled', 'mycred' ); ?></div>
							<div class="balance-desc"><em><?php _e( 'This point type is not for sale.', 'mycred' ); ?></em></div>
						</div>
					</div>
<?php

					}

					// This user is excluded from this point type
					elseif ( $data['excluded'] ) {

?>
					<div class="mycred-wrapper buycred-wrapper excluded-option color-option">
						<div><?php printf( _x( 'Buying %s', 'Buying Points', 'mycred' ), $data['name'] ); ?></div>
						<div class="balance-row">
							<div class="balance-view"><?php _e( 'Excluded', 'mycred' ); ?></div>
							<div class="balance-desc"><em><?php printf( _x( 'User can not buy %s', 'Points Name', 'mycred' ), $data['name'] ); ?></em></div>
						</div>
					</div>
<?php

					}

					// Eligeble user
					else {

?>
					<div class="mycred-wrapper buycred-wrapper color-option selected">
						<div><?php printf( _x( 'Buying %s', 'Buying Points', 'mycred' ), $data['name'] ); ?></div>
						<div class="balance-row">
							<div class="balance-view"><?php echo $data['before']; ?><input type="text" name="mycred_adjust_users_buyrates[<?php echo $type_id; ?>][<?php echo $gateway_id; ?>]" placeholder="<?php echo $data['default']; ?>" value="<?php if ( $data['override'] ) echo esc_attr( $data['custom'] ); ?>" class="short" size="8" /><?php echo ' ' . $setup['currency']; ?></div>
							<div class="balance-desc"><em><?php _e( 'Leave empty to use the default rate.', 'mycred' ); ?></em></div>
						</div>
					</div>
<?php

					}

				}

?>
			</fieldset>
		</td>
	</tr>
<?php

			endforeach;

?>
</table>
<hr />
<script type="text/javascript">
jQuery(function($) {

	$( '.buycred-wrapper label input.trigger-buycred' ).change(function(){

		if ( $(this).val().length > 0 )
			$(this).parent().parent().parent().addClass( 'selected' );

		else
			$(this).parent().parent().parent().removeClass( 'selected' );

	});

});
</script>
<?php

		}

		/**
		 * Save Override
		 * @since 1.5
		 * @version 1.2
		 */
		public function save_manual_exchange_rates( $user_id ) {

			if ( ! mycred_is_admin() ) return;

			if ( isset( $_POST['mycred_adjust_users_buyrates'] ) && is_array( $_POST['mycred_adjust_users_buyrates'] ) && ! empty( $_POST['mycred_adjust_users_buyrates'] ) ) {

				foreach ( $_POST['mycred_adjust_users_buyrates'] as $ctype => $gateway ) {

					$ctype  = sanitize_key( $ctype );
					$mycred = mycred( $ctype );

					if ( ! $mycred->exclude_user( $user_id ) ) {

						$new_rates = array();
						foreach ( (array) $gateway as $gateway_id => $rate ) {

							if ( $rate == '' ) continue;

							if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
								$rate = (float) '0' . $rate;

							$new_rates[ $gateway_id ] = $rate;

						}

						if ( ! empty( $new_rates ) )
							mycred_update_user_meta( $user_id, 'mycred_buycred_rates_' . $ctype, '', $new_rates );
						else
							mycred_delete_user_meta( $user_id, 'mycred_buycred_rates_' . $ctype );

					}

				}

			}

		}

		/**
		 * Render Shortcode Basic
		 * This shortcode returns a link element to a specified payment gateway.
		 * @since 0.1
		 * @version 1.6
		 */
		public function render_shortcode_basic( $atts, $title = '' ) {

			// Make sure the add-on has been setup
			if ( ! isset( $this->core->buy_creds ) ) {
				if ( mycred_is_admin() )
					return '<p style="color:red;"><a href="' . $this->get_settings_url( 'buycred_module' ) . '">This Add-on needs to setup before you can use this shortcode.</a></p>';
				else
					return '';
			}

			extract( shortcode_atts( array(
				'gateway' => '',
				'ctype'   => MYCRED_DEFAULT_TYPE_KEY,
				'amount'  => '',
				'gift_to' => '',
				'class'   => 'mycred-buy-link btn btn-primary btn-lg',
				'login'   => $this->core->template_tags_general( $this->core->buy_creds['login'] )
			), $atts ) );

			// Make sure we are trying to sell a point type that is allowed to be purchased
			if ( ! in_array( $ctype, $this->core->buy_creds['types'] ) )
				$ctype = $this->core->buy_creds['types'][0];

			if ( $ctype == $this->core->cred_id )
				$mycred = $this->core;
			else
				$mycred = mycred( $ctype );

			global $post;

			// If we are not logged in
			if ( $this->current_user_id == 0 ) return '<div class="mycred-buy login">' . $mycred->template_tags_general( $login ) . '</div>';

			// Arguments
			$args = array( 'ctype' => $ctype, 'token' => wp_create_nonce( 'mycred-buy-creds' ) );

			// Gateways
			$installed          = $this->get();

			if ( empty( $installed ) )                                                   return 'No gateways installed.';
			elseif ( ! empty( $gateway ) && ! array_key_exists( $gateway, $installed ) ) return 'Gateway does not exist.';
			elseif ( empty( $this->active ) )                                            return 'No active gateways found.';

			// If no gateway is set, chooce the first one that is active
			if ( empty( $gateway ) || ! array_key_exists( $gateway, $installed ) ) {

				$gateway_to_use = false;
				foreach ( $installed as $gateway_id => $info ) {
					if ( ! $this->is_active( $gateway_id ) ) continue;
					$gateway_to_use = $gateway_id;
					break;
				}

				// Doubt this should happen if we come this far but you can never be sure
				if ( $gateway_to_use === false ) return 'No active gateways found.';

				$gateway = $gateway_to_use;

			}

			$args['mycred_buy'] = $gateway;
			$classes[]          = $gateway;

			// Prep
			$buyer_id           = $this->current_user_id;
			$recipient_id       = $buyer_id;

			if ( $this->core->buy_creds['gifting']['authors'] && $gift_to == 'author' )
				$recipient_id = $post->post_author;

			if ( $this->core->buy_creds['gifting']['members'] && absint( $gift_to ) !== 0 )
				$recipient_id = absint( $gift_to );

			if ( $recipient_id !== $buyer_id )
				$args['gift_to'] = $recipient_id;

			// Allow user related template tags to be used in the button label
			$title              = $mycred->template_tags_general( $title );
			$title              = $mycred->template_tags_user( $title, $recipient_id );

			// Amount
			$minimum            = $mycred->number( $this->core->buy_creds['minimum'] );
			$amount             = $mycred->number( $amount );

			// Enfoce the minimum we set in our buyCRED settings
			if ( $amount < $minimum )
				$amount = $minimum;

			$args['amount']     = $amount;

			// Let others add items to the arguments
			$args = apply_filters( 'mycred_buy_args', $args, $atts, $mycred );

			// Classes
			$classes            = explode( ' ', $class );

			if ( empty( $classes ) )
				$classes[] = 'mycred-buy-link';

			$current_url        = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

			// Construct anchor element to take us to the checkout page
			return '<a href="' . esc_url( add_query_arg( $args, $current_url ) ) . '" class="' . implode( ' ', $classes ) . '" title="' . esc_attr( strip_tags( $title ) ) . '">' . do_shortcode( $title ) . '</a>';

		}

		/**
		 * Render Shortcode Form
		 * Returns an advanced version allowing for further customizations.
		 * @since 0.1
		 * @version 1.6
		 */
		public function render_shortcode_form( $atts, $content = '' ) {

			// Make sure the add-on has been setup
			if ( ! isset( $this->core->buy_creds ) ) {
				if ( mycred_is_admin() )
					return '<p style="color:red;"><a href="' . $this->get_settings_url( 'buycred_module' ) . '">This Add-on needs to setup before you can use this shortcode.</a></p>';
				else
					return '';
			}

			extract( shortcode_atts( array(
				'button'  => __( 'Buy Now', 'mycred' ),
				'gateway' => '',
				'ctype'   => MYCRED_DEFAULT_TYPE_KEY,
				'amount'  => '',
				'gift_to' => '',
				'gift_by' => __( 'Username', 'mycred' ),
				'inline'  => 0
			), $atts ) );

			// If we are not logged in
			if ( $this->current_user_id == 0 ) return $content;

			// Get gateways
			$installed = $this->get();

			// Catch errors
			if ( empty( $installed ) )                                                   return 'No gateways installed.';
			elseif ( ! empty( $gateway ) && ! array_key_exists( $gateway, $installed ) ) return 'Gateway does not exist.';
			elseif ( empty( $this->active ) )                                            return 'No active gateways found.';
			elseif ( ! empty( $gateway ) && ! $this->is_active( $gateway ) )             return 'The selected gateway is not active.';

			// Make sure we are trying to sell a point type that is allowed to be purchased
			if ( ! in_array( $ctype, $this->core->buy_creds['types'] ) )
				$ctype = $this->core->buy_creds['types'][0];

			if ( $ctype == $this->core->cred_id )
				$mycred = $this->core;
			else
				$mycred = mycred( $ctype );

			global $post;

			// Prep
			$buyer_id     = $this->current_user_id;
			$recipient_id = $buyer_id;
			$classes      = array( 'myCRED-buy-form' );

			if ( $this->core->buy_creds['gifting']['authors'] && $gift_to == 'author' )
				$recipient_id = $post->post_author;

			if ( $this->core->buy_creds['gifting']['members'] && absint( $gift_to ) !== 0 )
				$recipient_id = absint( $gift_to );

			$button_label = $mycred->template_tags_general( $button );

			if ( ! empty( $gateway ) ) {
				$gateway_name = explode( ' ', $installed[ $gateway ]['title'] );
				$button_label = str_replace( '%gateway%', $gateway_name[0], $button_label );
				$classes[]    = $gateway_name[0];
			}

			$amounts = array();
			$minimum = $this->core->number( $this->core->buy_creds['minimum'] );
			if ( ! empty( $amount ) ) {
				$_amounts = explode( ',', $amount );
				foreach ( $_amounts as $_amount ) {
					$_amount = $mycred->number( $_amount );
					if ( $_amount === $mycred->zero() ) continue;
					$amounts[] = $_amount;
				}
			}

			ob_start();

?>
<div class="row">
	<div class="col-xs-12">
		<form method="post" class="form<?php if ( $inline == 1 ) echo '-inline'; ?> <?php echo implode( ' ', $classes ); ?>" action="">
			<input type="hidden" name="token" value="<?php echo wp_create_nonce( 'mycred-buy-creds' ); ?>" />
			<input type="hidden" name="transaction_id" value="<?php echo strtoupper( wp_generate_password( 6, false, false ) ); ?>" />
			<input type="hidden" name="ctype" value="<?php echo $ctype; ?>" />

			<div class="form-group">
				<label><?php echo $mycred->plural(); ?></label>
<?php

			// No amount given - user must nominate the amount
			if ( count( $amounts ) == 0 ) :

?>
				<input type="text" name="amount" class="form-control" placeholder="<?php echo $mycred->format_creds( $minimum ); ?>" min="<?php echo $minimum; ?>" value="" />
<?php

			// Amount given
			else :

				// One amount - this is the amount a user must buy
				if ( count( $amount ) > 1 ) {

?>
				<p class="form-control-static"><?php echo $mycred->format_creds( $amounts[0] ); ?></p>
				<input type="hidden" name="amount" value="<?php echo $amounts[0]; ?>" />
<?php

				}

				// Multiple items - user selects the amount from a dropdown menu
				else {

?>
				<select name="amount" class="form-control">
<?php

					foreach ( $amounts as $amount )
						echo '<option value="' . $amount . '">' . $mycred->format_creds( $amount ) . '</option>';

?>
				</select>
<?php

				}

			endif;

?>
				<?php if ( $gift_to != '' ) : ?>

				<div class="form-group">
					<label for="gift_to"><?php _e( 'Recipient', 'mycred' ); ?></label>
<?php

			// Post author - show the authors name
			if ( $this->core->buy_creds['gifting']['authors'] && $gift_to == 'author' ) {

				$author = get_userdata( $recipient_id );

?>
					<p class="form-control-static"><?php echo $author->display_name; ?></p>
					<input type="hidden" name="gift_to" value="<?php echo $recipient_id; ?>" />
<?php

			}

			// Specific User - show the members name
			elseif ( $this->core->buy_creds['gifting']['members'] && absint( $gift_to ) !== 0 ) {

				$member = get_userdata( $recipient_id );

?>
					<p class="form-control-static"><?php echo $member->display_name; ?></p>
					<input type="hidden" name="gift_to" value="<?php echo $recipient_id; ?>" />
<?php

			}

			// Users need to nominate recipient
			else {

?>
					<input type="text" class="form-control pick-user" name="gift_to" placeholder="<?php echo $gift_by; ?>" value="" />
<?php

			}

?>
				</div>

				<?php endif; ?>

				<?php if ( empty( $gateway ) && count( $installed ) > 1 ) : ?>

				<div class="form-group">
					<label for="gateway"><?php _e( 'Pay Using', 'mycred' ); ?></label>
					<select name="mycred_buy" class="form-control">
<?php

			foreach ( $installed as $gateway_id => $info ) {

				if ( $this->is_active( $gateway_id ) )
					echo '<option value="' . $gateway_id . '">' . $info['title'] . '</option>';

			}

?>
					</select>
				</div>

				<?php else : ?>

				<input type="hidden" name="mycred_buy" value="<?php echo $gateway; ?>" />

				<?php endif; ?>

				<div class="form-group">
					<input type="submit" class="btn btn-primary btn-block btn-lg" value="<?php echo $button_label; ?>" />
				</div>
			</div>

		</form>
	</div>
</div>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			return $content;

		}

	}
endif;

/**
 * Load buyCRED Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_buycred_core_addon' ) ) :
	function mycred_load_buycred_core_addon( $modules, $point_types ) {

		$modules['solo']['buycred'] = new myCRED_buyCRED_Module();
		$modules['solo']['buycred']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_buycred_core_addon', 30, 2 );
