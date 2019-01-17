<?php
/**
 * Addon: Sell Content
 * Addon URI: http://mycred.me/add-ons/sell-content/
 * Version: 2.0
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_SELL',              __FILE__ );
define( 'myCRED_SELL_VERSION',      '1.5' );
define( 'MYCRED_SELL_DIR',          myCRED_ADDONS_DIR . 'sell-content/' );
define( 'MYCRED_SELL_ASSETS_DIR',   MYCRED_SELL_DIR . 'assets/' );
define( 'MYCRED_SELL_INCLUDES_DIR', MYCRED_SELL_DIR . 'includes/' );

require_once MYCRED_SELL_INCLUDES_DIR . 'mycred-sell-functions.php';
require_once MYCRED_SELL_INCLUDES_DIR . 'mycred-sell-shortcodes.php';

/**
 * myCRED_Sell_Content_Module class
 * @since 0.1
 * @version 2.0
 */
if ( ! class_exists( 'myCRED_Sell_Content_Module' ) ) :
	class myCRED_Sell_Content_Module extends myCRED_Module {

		public $current_user_id = 0;

		/**
		 * Construct
		 */
		function __construct() {

			parent::__construct( 'myCRED_Sell_Content_Module', array(
				'module_name' => 'sell_content',
				'register'    => false,
				'defaults'    => array(
					'post_types'  => 'post,page',
						'filters'     => array(),
					'type'        => array( MYCRED_DEFAULT_TYPE_KEY ),
					'reload'      => 0,
					'working'     => 'Processing ...',
					'templates'   => array(
						'members'     => '<div class="text-center"><h3>Premium Content</h3><p>Buy access to this content.</p><p>%buy_button%</p></div>',
						'visitors'    => '<div class="text-center"><h3>Premium Content</h3><p>Buy access to this content.</p><p><strong>Insufficient Funds</strong></p></div>',
						'cantafford'  => '<div class="text-center"><h3>Premium Content</h3><p>Login to buy access to this content.</p></div>'
					)
				),
				'add_to_core' => true
			) );

			if ( ! is_array( $this->sell_content['type'] ) )
				$this->sell_content['type'] = array( $this->sell_content['type'] );

		}

		/**
		 * Module Init
		 * @since 0.1
		 * @version 1.2
		 */
		public function module_init() {

			$this->current_user_id = get_current_user_id();

			// Email add-on support
			add_filter( 'mycred_get_email_events',         array( $this, 'email_notice_instance' ), 10, 2 );
			add_filter( 'mycred_email_before_send',        array( $this, 'email_notices' ), 40, 2 );

			// Setup Content Override
			add_action( 'template_redirect',               array( $this, 'template_redirect' ), 99990 );

			// Register shortcodes
			add_shortcode( 'mycred_sell_this',             'mycred_render_sell_this' );
			add_shortcode( 'mycred_sell_this_ajax',        'mycred_render_sell_this_ajax' );
			add_shortcode( 'mycred_sales_history',         'mycred_render_sell_history' );
			add_shortcode( 'mycred_content_sale_count',    'mycred_render_sell_count' );
			add_shortcode( 'mycred_content_buyer_count',   'mycred_render_sell_buyer_count' );
			add_shortcode( 'mycred_content_buyer_avatars', 'mycred_render_sell_buyer_avatars' );

			// Setup AJAX handler
			add_action( 'mycred_register_assets',          array( $this, 'register_assets' ) );
			add_action( 'mycred_front_enqueue_footer',     array( $this, 'enqueue_footer' ) );
			add_action( 'wp_ajax_mycred-buy-content',      array( $this, 'action_buy_content' ) );

		}

		/**
		 * Module Admin Init
		 * @since 1.7
		 * @version 1.0
		 */
		public function module_admin_init() {

			add_action( 'admin_notices',                   array( $this, 'one_seven_update_notice' ), 90 );

			// Setup the "Sell This" Metaboxes
			$post_types = explode( ',', $this->sell_content['post_types'] );
			if ( ! empty( $post_types ) ) {

				foreach ( $post_types as $type ) {
					add_action( "add_meta_boxes_{$type}", array( $this, 'add_metabox' ) );
					add_action( "save_post_{$type}",      array( $this, 'save_metabox' ) );
				}

			}

			// User Override
			add_action( 'mycred_user_edit_after_balances', array( $this, 'sell_content_user_screen' ), 50 );

			add_action( 'personal_options_update',         array( $this, 'save_manual_profit_share' ), 50 );
			add_action( 'edit_user_profile_update',        array( $this, 'save_manual_profit_share' ), 50 );

		}

		/**
		 * 1.7 Update Notice
		 * @since 1.7
		 * @version 1.0
		 */
		public function one_seven_update_notice() {

			if ( ! current_user_can( $this->core->edit_plugin_cap() ) || get_option( 'mycred_sell_content_one_seven_updated', false ) !== false ) return;

			if ( isset( $_GET['page'] ) && $_GET['page'] === MYCRED_SLUG . '-settings' ) return;

			echo '
<div id="message" class="notice notice-info">
	<h2>' . __( 'Sell Content Add-on Update Required', 'mycred' ) . '</h2>
	<p>' . __( 'Before continuing to use this add-on you must setup and save your settings.', 'mycred' ) . '</p>
	<p><a href="' . esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-settings', 'open-tab' => 'sell_content_module' ), admin_url( 'admin.php' ) ) ) . '" class="button button-secondary">Go to Settings</a> <a href="' . get_mycred_addon_deactivation_url( 'sell-content' ) . '" class="button button-secondary">Disable Add-on</a></p>
</div>';

		}

		/**
		 * Register Assets
		 * @since 1.7
		 * @version 1.0
		 */
		public function register_assets() {

			wp_register_script(
				'mycred-sell-this',
				plugins_url( 'assets/js/buy-content.js', myCRED_SELL ),
				array( 'jquery' ),
				'1.1',
				true
			);

		}

		/**
		 * Load Script
		 * @since 1.7
		 * @version 1.0
		 */
		public function enqueue_footer() {

			wp_localize_script(
				'mycred-sell-this',
				'myCREDBuyContent',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'token'   => wp_create_nonce( 'mycred-buy-this-content' ),
					'working' => esc_js( $this->sell_content['working'] ),
					'reload'  => $this->sell_content['reload']
				)
			);

			wp_enqueue_script( 'mycred-sell-this' );

		}

		/**
		 * Setup Content Filter
		 * @since 1.7
		 * @version 1.0
		 */
		public function template_redirect() {

			// Filter the content
			add_filter( 'the_content', array( $this, 'the_content' ), 5 );

		}

		/**
		 * User Level Override
		 * @since 1.5
		 * @version 1.3.1
		 */
		public function sell_content_user_screen( $user ) {

			// Only visible to admins
			if ( ! mycred_is_admin() ) return;

			$mycred_types      = mycred_get_types( true );
			$available_options = array();

			foreach ( $mycred_types as $point_type_key => $label ) {

				$setup = array( 'name' => $label, 'enabled' => false, 'default' => 0, 'excluded' => true, 'override' => false, 'custom' => 0 );

				if ( ! empty( $this->sell_content['type'] ) && in_array( $point_type_key, $this->sell_content['type'] ) ) {

					$setup['enabled']  = true;
					$mycred            = mycred( $point_type_key );

					if ( ! $mycred->exclude_user( $user->ID ) ) {

						$setup['excluded'] = false;

						$settings          = mycred_get_option( 'mycred_sell_this_' . $point_type_key );

						$setup['default']  = $settings['profit_share'];

						$users_share = mycred_get_user_meta( $user->ID, 'mycred_sell_content_share_' . $point_type_key, '', true );
						if ( strlen( $users_share ) > 0 ) {

							$setup['override'] = true;
							$setup['custom']   = $users_share;

						}

					}

				}

				$available_options[ $point_type_key ] = $setup;

			}

			if ( empty( $available_options ) ) return;

?>
<p class="mycred-p"><?php _e( 'Users profit share when their content is purchased.', 'mycred' ); ?></p>
<table class="form-table mycred-inline-table">
	<tr>
		<th scope="row"><?php _e( 'Profit Share', 'mycred' ); ?></th>
		<td>
			<fieldset id="mycred-badge-list" class="badge-list">
				<legend class="screen-reader-text"><span><?php _e( 'Profit Share', 'mycred' ); ?></span></legend>
<?php

			foreach ( $available_options as $point_type => $data ) {

				// This point type is not for sale
				if ( ! $data['enabled'] ) {

?>
				<div class="mycred-wrapper buycred-wrapper disabled-option color-option">
					<div><?php printf( _x( '%s Profit Share', 'Points Name', 'mycred' ), $data['name'] ); ?></div>
					<div class="balance-row">
						<div class="balance-view"><?php _e( 'Disabled', 'mycred' ); ?></div>
						<div class="balance-desc"><em><?php _e( 'Not accepted as payment.', 'mycred' ); ?></em></div>
					</div>
				</div>
<?php

				}

				// This user is excluded from this point type
				elseif ( $data['excluded'] ) {

?>
				<div class="mycred-wrapper buycred-wrapper disabled-option color-option">
					<div><?php printf( _x( '%s Profit Share', 'Points Name', 'mycred' ), $data['name'] ); ?></div>
					<div class="balance-row">
						<div class="balance-view"><?php _e( 'Excluded', 'mycred' ); ?></div>
						<div class="balance-desc"><em><?php printf( _x( 'User can not pay using %s', 'Points Name', 'mycred' ), $data['name'] ); ?></em></div>
					</div>
				</div>
<?php

				}

				// Eligeble user
				else {

?>
				<div class="mycred-wrapper buycred-wrapper color-option selected">
					<div><?php printf( _x( '%s Profit Share', 'Buying Points', 'mycred' ), $data['name'] ); ?></div>
					<div class="balance-row">
						<div class="balance-view"><input type="text" size="8" name="mycred_sell_this[<?php echo $point_type; ?>]" class="half" placeholder="<?php echo esc_attr( $data['default'] ); ?>" value="<?php if ( $data['override'] ) echo esc_attr( $data['custom'] ); ?>" /> %</div>
						<div class="balance-desc"><em><?php _e( 'Leave empty to use the default.', 'mycred' ); ?></em></div>
					</div>
				</div>
<?php

				}

			}

?>
			</fieldset>
		</td>
	</tr>
</table>
<hr />
<?php

		}

		/**
		 * Save Override
		 * @since 1.5
		 * @version 1.2
		 */
		function save_manual_profit_share( $user_id ) {

			// Only visible to admins
			if ( ! mycred_is_admin() ) return;

			if ( isset( $_POST['mycred_sell_this'] ) && ! empty( $_POST['mycred_sell_this'] ) ) {

				foreach ( $_POST['mycred_sell_this'] as $point_type => $share ) {

					$share = sanitize_text_field( $share );

					mycred_delete_user_meta( $user_id, 'mycred_sell_content_share_' . $point_type );
					if ( $share != '' && is_numeric( $share ) )
						mycred_update_user_meta( $user_id, 'mycred_sell_content_share_' . $point_type, '', $share );

				}

			}

		}

		/**
		 * Enabled / Disabled Select Options
		 * @since 1.7
		 * @version 1.0
		 */
		protected function enabled_options( $selected = '' ) {

			$options = array(
				'disabled' => __( 'Disabled', 'mycred' ),
				'enabled'  => __( 'Enabled', 'mycred' )
			);

			$output = '';
			foreach ( $options as $value => $label ) {
				$output .= '<option value="' . $value . '"';
				if ( $selected == $value ) $output .= ' selected="selected"';
				$output .= '>' . $label . '</option>';
			}

			return $output;

		}

		/**
		 * Settings Page
		 * @since 0.1
		 * @version 1.3
		 */
		public function after_general_settings( $mycred = NULL ) {

			$post_types     = mycred_sell_content_post_types();
			$selected_types = explode( ',', $this->sell_content['post_types'] );

			$point_types    = mycred_get_types( true );

?>
<h4><span class="dashicons dashicons-admin-plugins static"></span><?php _e( 'Sell Content', 'mycred' ); ?></h4>
<div class="body" style="display: none;">
	<label class="subheader"><?php _e( 'Post Types', 'mycred' ); ?></label>
	<ol id="myCRED-sell-post-types" class="inline sub-bordered">
		<li class="block"><span class="description"><?php _e( 'Select all the post types you want to sell.', 'mycred' ); ?></span></li>
<?php

			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type => $post_type_label ) {

					$selected = '';
					if ( in_array( $post_type, $selected_types ) )
						$selected = ' checked="checked"';

					echo '<li><label for="mycred-sell-' . $this->field_name( array( 'post_types' => $post_type ) ) . '-post-type"><input type="checkbox" name="' . $this->field_name( array( 'post_types' => $post_type ) ) . '"' . $selected . ' class="mycred-check-count" id="mycred-sell-' . $this->field_name( array( 'post_types' => $post_type ) ) . '-post-type" value="' . $post_type . '" data-type="' . $post_type . '" />' . $post_type_label . '</label></li>';

				}
			}

?>
		<li id="mycred-sell-content-post-type-warning" class="block" style="display: none; clear: both;"><div class="inline-warning"><p><?php _e( 'You must select at least one post type to sell.', 'mycred' ); ?></p></div></li>
	</ol>
	<div id="mycred-sell-this-post-type-filter">
<?php

			if ( ! empty( $post_types ) ) {
				foreach ( $post_types as $post_type => $post_type_label ) {

					$settings = array( 'by' => 'all', 'list' => '' );
					if ( array_key_exists( $post_type, $this->sell_content['filters'] ) )
						$settings = $this->sell_content['filters'][ $post_type ];

					$selected = 'none';
					if ( in_array( $post_type, $selected_types ) )
						$selected = 'block';

					$options = mycred_get_post_type_options( $post_type );

?>
		<div id="mycred-sell-post-type-<?php echo $post_type; ?>-wrap" style="display: <?php echo $selected; ?>;">
			<ol class="inline sub-bordered slim">
				<li style="width: 30%;">
					<select name="<?php echo $this->field_name( array( 'filters' => $post_type ) ); ?>[by]" class="toggle-filter-menu" data-type="<?php echo $post_type; ?>">
<?php

					if ( ! empty( $options ) ) {
						foreach ( $options as $value => $option ) {

							echo '<option value="' . $value . '"';
							if ( $value == $settings['by'] ) echo ' selected="selected"';
							if ( $option['data'] != '' ) echo ' data-place="' . $option['data'] . '"';
							echo '>' . $option['label'] . '</option>';

						}
					}

?>
					</select>
				</li>
				<li id="post-type-filter-<?php echo $post_type; ?>" style="width: 65%; display: <?php if ( ! in_array( $settings['by'], array( 'all', 'manual' ) ) ) echo 'block'; else echo 'none'; ?>;">
					<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'filters' => $post_type ) ); ?>[list]" id="" value="<?php echo esc_attr( $settings['list'] ); ?>" placeholder="<?php if ( array_key_exists( $settings['by'], $options ) ) echo esc_attr( $options[ $settings['by'] ]['data'] ); ?>" class="long" /></div>
				</li>
			</ol>
		</div>
<?php

				}
			}

?>
	</div>

	<label class="subheader"><?php _e( 'Point Types', 'mycred' ); ?></label>
	<ol id="myCRED-sell-point-type" class="inline">
		<li class="block"><span class="description"><?php _e( 'Select all the point types accepted as payment.', 'mycred' ); ?></span></li>
<?php

			if ( ! empty( $point_types ) ) {
				foreach ( $point_types as $point_type => $point_type_label ) {

					$selected = '';
					if ( in_array( $point_type, $this->sell_content['type'] ) )
						$selected = ' checked="checked"';

					if ( count( $point_types ) === 1 )
						$selected = ' checked="checked" disabled="disabled"';

					echo '<li><label for="mycred-sell-' . $this->field_name( array( 'type' => $point_type ) ) . '-point-type"><input type="checkbox" name="mycred_pref_core[sell_content][type][]"' . $selected . ' class="mycred-check-count" id="mycred-sell-' . $this->field_name( array( 'type' => $point_type ) ) . '-point-type" value="' . $point_type . '" data-type="' . $point_type . '" />' . $point_type_label . '</label></li>';

				}
			}

?>
		<li id="mycred-sell-content-point-type-warning" class="block" style="display: none; clear: both;"><div class="inline-warning"><p><?php _e( 'You must select at least one point type to accept as payment.', 'mycred' ); ?></p></div></li>
	</ol>
	<ol class="slim sub-bordered">
		<li></li>
	</ol>
<?php

			if ( ! empty( $point_types ) ) {
				foreach ( $point_types as $point_type => $point_type_label ) {

					$selected = 'none';
					if ( in_array( $point_type, $this->sell_content['type'] ) )
						$selected = 'block';

					if ( count( $point_types ) === 1 )
						$selected = 'block';

					$mycred     = mycred( $point_type );
					$type_setup = mycred_get_option( 'mycred_sell_this_' . $point_type );
					$type_setup = wp_parse_args( $type_setup, array(
						'status'         => 'disabled',
						'price'          => 0,
						'expire'         => 0,
						'profit_share'   => 0,
						'button_label'   => 'Pay %price%',
						'button_classes' => 'btn btn-primary btn-lg',
						'log_payment'    => 'Purchase of %link_with_title%',
						'log_sale'       => 'Sale of %link_with_title%'
					) );

?>
	<div id="mycred-sell-<?php echo $point_type; ?>-wrap" style="display: <?php echo $selected; ?>;">
		<label class="subheader"><?php printf( __( '%s Setup', 'mycred' ), $point_type_label ); ?></label>
		<ol class="inline slim">
			<li style="min-width: 200px;">
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-status"><?php _e( 'Status', 'mycred' ); ?></label>
				<div class="h2"><select name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][status]" id="mycred-sell-<?php echo $point_type; ?>-setup-status"><?php echo $this->enabled_options( $type_setup['status'] ); ?></select></div>
			</li>
			<li style="min-width: 200px;">
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-price"><?php _e( 'Price', 'mycred' ); ?></label>
				<div class="h2"><?php echo $mycred->before; ?><input type="text" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][price]" id="mycred-sell-<?php echo $point_type; ?>-setup-price" value="<?php echo $mycred->number( $type_setup['price'] ); ?>" size="8" /><?php echo $mycred->after; ?></div>
			</li>
			<li style="min-width: 200px;">
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-expire"><?php _e( 'Expiration', 'mycred' ); ?></label>
				<div class="h2"><input type="text" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][expire]" id="mycred-sell-<?php echo $point_type; ?>-setup-expire" value="<?php echo absint( $type_setup['expire'] ); ?>" size="8" /> <?php echo apply_filters( 'mycred_sell_exp_title', __( 'Hour(s)', 'mycred' ) ); ?></div>
				<span class="description"><?php _e( 'Use zero to disable.', 'mycred' ); ?></span>
			</li>
			<li style="min-width: 200px;">
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-profit_share"><?php _e( 'Profit Share', 'mycred' ); ?></label>
				<div class="h2"><input type="text" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][profit_share]" id="mycred-sell-<?php echo $point_type; ?>-setup-profit_share" value="<?php echo esc_attr( $type_setup['profit_share'] ); ?>" size="8" /> %</div>
				<span class="description"><?php _e( 'Use zero to disable.', 'mycred' ); ?></span>
			</li>
		</ol>
		<ol class="inline slim">
			<li>
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-button_label"><?php _e( 'Button Label', 'mycred' ); ?></label>
				<div class="h2"><input type="text" size="30" class="medium" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][button_label]" id="mycred-sell-<?php echo $point_type; ?>-setup-button_label" value="<?php echo esc_attr( $type_setup['button_label'] ); ?>" /></div>
				<span class="description"><?php _e( 'Use %price% to show the price set for each post.', 'mycred' ); ?></span>
			</li>
			<li>
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-button_classes"><?php _e( 'Button CSS Classes', 'mycred' ); ?></label>
				<div class="h2"><input type="text" size="30" class="medium" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][button_classes]" id="mycred-sell-<?php echo $point_type; ?>-setup-button_classes" value="<?php echo esc_attr( $type_setup['button_classes'] ); ?>" /></div>
			</li>
		</ol>
		<ol class="sub-bordered">
			<li>
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-log_payment"><?php _e( 'Payment log entry template', 'mycred' ); ?></label>
				<div class="h2"><input type="text" size="30" class="long" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][log_payment]" id="mycred-sell-<?php echo $point_type; ?>-setup-log_payment" value="<?php echo esc_attr( $type_setup['log_payment'] ); ?>" /></div>
				<?php echo $this->core->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</li>
			<li>
				<label for="mycred-sell-<?php echo $point_type; ?>-setup-log_sale"><?php _e( 'Profit Share payout log entry template', 'mycred' ); ?></label>
				<div class="h2"><input type="text" size="30" class="long" name="mycred_pref_core[sell_content][post_type_setup][<?php echo $point_type; ?>][log_sale]" id="mycred-sell-<?php echo $point_type; ?>-setup-log_sale" value="<?php echo esc_attr( $type_setup['log_sale'] ); ?>" /></div>
				<span class="description"><?php _e( 'Only used if profit sharing is enabled for this point type.', 'mycred' ); ?></span>
				<?php echo $this->core->available_template_tags( array( 'general', 'post' ) ); ?></span>
			</li>
		</ol>
	</div>
<?php

				}
			}

?>
	<label class="subheader" for="<?php echo $this->field_id( 'reload' ); ?>"><?php _e( 'Transactions', 'mycred' ); ?></label>
	<ol id="myCRED-buy-template-button">
		<li>
			<label for="<?php echo $this->field_id( 'reload' ); ?>"><input type="checkbox" name="<?php echo $this->field_name( 'reload' ); ?>" id="<?php echo $this->field_id( 'reload' ); ?>"<?php checked( $this->sell_content['reload'], 1 ); ?> value="1" /> <?php _e( 'Reload page after successful payments.', 'mycred' ); ?></label>
		</li>
		<li>
			<label for="<?php echo $this->field_id( 'working' ); ?>"><?php _e( 'Button Label', 'mycred' ); ?></label>
			<div class="h2"><input type="text" size="30" class="long code" name="<?php echo $this->field_name( 'working' ); ?>" id="<?php echo $this->field_id( 'working' ); ?>" value="<?php echo esc_attr( $this->sell_content['working'] ); ?>" /></div>
			<span class="description"><?php _e( 'Option to show a custom button label while the payment is being processed. HTML is allowed.', 'mycred' ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Templates', 'mycred' ); ?></label>
	<ol>
		<li>
			<h3><?php _e( 'Members', 'mycred' ); ?></h3>
			<p class="description"><?php _e( 'The template to use when a content is viewed by a member that is logged in and can afford to pay. Only applied to content that is set for sale.', 'mycred' ); ?></p>
		</li>
		<li>
<?php

			wp_editor( $this->sell_content['templates']['members'], $this->field_id( array( 'templates' => 'members' ) ), array(
				'textarea_name' => $this->field_name( array( 'templates' => 'members' ) ),
				'textarea_rows' => 10
			) );

			echo '<p>' . $this->core->available_template_tags( array( 'post' ), '%buy_button%' ) . '</p>';

?>
		</li>
		<li class="empty">&nbsp;</li>
		<li>
			<h3><?php _e( 'Visitors', 'mycred' ); ?></h3>
			<p class="description"><?php _e( 'The template to use when a content is viewed by someone who is not logged in. Only applied to content that is set for sale.', 'mycred' ); ?></p>
		</li>
		<li>
<?php

			wp_editor( $this->sell_content['templates']['visitors'], $this->field_id( array( 'templates' => 'visitors' ) ), array(
				'textarea_name' => $this->field_name( array( 'templates' => 'visitors' ) ),
				'textarea_rows' => 10
			) );

			echo '<p>' . $this->core->available_template_tags( array( 'post' ) ) . '</p>';

?>

		</li>
		<li class="empty">&nbsp;</li>
		<li>
			<h3><?php _e( 'Insufficient Funds', 'mycred' ); ?></h3>
			<p class="description"><?php _e( 'The template to use when a content is viewed by a member that is logged but can not afford to buy. Only applied to content that is set for sale.', 'mycred' ); ?></p>
		</li>
		<li>
<?php

			wp_editor( $this->sell_content['templates']['cantafford'], $this->field_id( array( 'templates' => 'cantafford' ) ), array(
				'textarea_name' => $this->field_name( array( 'templates' => 'cantafford' ) ),
				'textarea_rows' => 10
			) );

			echo '<p>' . $this->core->available_template_tags( array( 'post' ) ) . '</p>';

?>
		</li>
	</ol>
</div>
<script type="text/javascript">
(function($) {

	var selectedposttypes  = <?php echo count( $selected_types ); ?>;
	var selectedpointtypes = <?php echo count( $this->sell_content['type'] ); ?>;
	
	$( '#myCRED-sell-post-types .mycred-check-count' ).click(function(){

		if ( $(this).is( ':checked' ) ) {

			selectedposttypes++;
			$( '#mycred-sell-content-post-type-warning' ).hide();

			$( '#mycred-sell-post-type-' + $(this).data( 'type' ) + '-wrap' ).show();

		}
		else {
			selectedposttypes--;
			if ( selectedposttypes <= 0 )
				$( '#mycred-sell-content-post-type-warning' ).show();
			else
				$( '#mycred-sell-content-post-type-warning' ).hide();

			$( '#mycred-sell-post-type-' + $(this).data( 'type' ) + '-wrap' ).hide();
		}

	});
	
	$( '#myCRED-sell-point-type .mycred-check-count' ).click(function(){

		if ( $(this).is( ':checked' ) ) {

			selectedpointtypes++;
			$( '#mycred-sell-content-point-type-warning' ).hide();

			$( '#mycred-sell-' + $(this).data( 'type' ) + '-wrap' ).show();

		}
		else {
			selectedpointtypes--;
			if ( selectedpointtypes <= 0 )
				$( '#mycred-sell-content-point-type-warning' ).show();
			else
				$( '#mycred-sell-content-point-type-warning' ).hide();

			$( '#mycred-sell-' + $(this).data( 'type' ) + '-wrap' ).hide();
		}

	});

	$( '#mycred-sell-this-post-type-filter' ).on( 'change', 'select.toggle-filter-menu', function(){

		var post_type      = $(this).data( 'type' );
		var selectedfilter = $(this).find( ':selected' );
		var placeholder    = selectedfilter.data( 'place' );

		if ( selectedfilter === undefined || selectedfilter.val() == 'all' || selectedfilter.val() == 'manual' ) {
			$( '#post-type-filter-' + post_type ).hide();
			$( '#post-type-filter-' + post_type + ' input' ).val( '' );
		}

		else {
			$( '#post-type-filter-' + post_type ).show();
		}

		if ( placeholder === undefined )
			$( '#post-type-filter-' + post_type + ' input' ).attr( 'placeholder', '' );

		else
			$( '#post-type-filter-' + post_type + ' input' ).attr( 'placeholder', placeholder );

	});

})( jQuery );
</script>
<?php

		}

		/**
		 * Sanitize & Save Settings
		 * @since 0.1
		 * @version 1.4
		 */
		public function sanitize_extra_settings( $new_data, $data, $general ) {

			$settings = $data['sell_content'];

			// Post Types
			$post_types = array();
			if ( array_key_exists( 'post_types', $settings ) && is_array( $settings['post_types'] ) && ! empty( $settings['post_types'] ) ) {

				foreach ( $settings['post_types'] as $post_type ) {
					$post_types[] = sanitize_text_field( $post_type );
				}

			}
			$new_data['sell_content']['post_types'] = implode( ',', $post_types );

			// Post Type Filter
			$filters = array();
			if ( array_key_exists( 'filters', $settings ) && is_array( $settings['filters'] ) && ! empty( $settings['filters'] ) ) {

				foreach ( $settings['filters'] as $post_type => $setup ) {

					if ( ! in_array( $post_type, $post_types ) ) continue;

					$filters[ $post_type ] = array( 'by' => 'all', 'list' => '' );

					$by = sanitize_text_field( $setup['by'] );
					if ( $by != '' ) {

						// Unless we selected all, we need to check the list
						if ( $by !== 'all' && $by !== 'manual' ) {

							// Clean up list by sanitizing and removing stray empty spaces
							$list = sanitize_text_field( $setup['list'] );
							if ( $list != '' ) {
								$_list = array();
								foreach ( explode( ',', $list ) as $object_slug ) {
									$object_slug = sanitize_text_field( $object_slug );
									$object_slug = trim( $object_slug );
									$_list[] = $object_slug;
								}
								$list = implode( ',', $_list );
							}

							$filters[ $post_type ]['by']   = $by;
							$filters[ $post_type ]['list'] = $list;

						}
						elseif ( $by === 'manual' ) {

							$filters[ $post_type ]['by'] = 'manual';

						}

					}

				}

			}
			$new_data['sell_content']['filters'] = $filters;

			// Point Types
			$point_types = array();
			if ( array_key_exists( 'type', $settings ) && is_array( $settings['type'] ) && ! empty( $settings['type'] ) ) {

				foreach ( $settings['type'] as $point_type ) {
					$point_types[] = sanitize_key( $point_type );
				}

			}
			if ( empty( $point_types ) )
				$point_types[] = MYCRED_DEFAULT_TYPE_KEY;

			$new_data['sell_content']['type'] = $point_types;

			// Point type default setup
			if ( array_key_exists( 'post_type_setup', $settings ) ) {
				foreach ( $settings['post_type_setup'] as $point_type => $setup ) {

					$new = wp_parse_args( $setup, array(
						'status'         => 'disabled',
						'price'          => 0,
						'expire'         => 0,
						'profit_share'   => 0,
						'button_label'   => '',
						'button_classes' => '',
						'log_payment'    => '',
						'log_sale'       => ''
					) );

					mycred_update_option( 'mycred_sell_this_' . $point_type, $new );

				}
			}

			$new_data['sell_content']['reload']                  = ( ( isset( $settings['reload'] ) ) ? absint( $settings['reload'] ) : 0 );
			$new_data['sell_content']['working']                 = wp_kses_post( $settings['working'] );

			// Templates
			$new_data['sell_content']['templates']['members']    = wp_kses_post( $settings['templates']['members'] );
			$new_data['sell_content']['templates']['visitors']   = wp_kses_post( $settings['templates']['visitors'] );
			$new_data['sell_content']['templates']['cantafford'] = wp_kses_post( $settings['templates']['cantafford'] );

			update_option( 'mycred_sell_content_one_seven_updated', time() );

			return $new_data;

		}

		/**
		 * Scripts & Styles
		 * @since 1.7
		 * @version 1.0
		 */
		public function scripts_and_styles() {

			$screen = get_current_screen();

			if ( in_array( $screen->id, explode( ',', $this->sell_content['post_types'] ) ) ) {
				wp_enqueue_style( 'mycred-bootstrap-grid' );
				wp_enqueue_style( 'mycred-forms' );
			}

		}

		/**
		 * Add Meta Box to Content
		 * @since 0.1
		 * @version 1.1
		 */
		public function add_metabox( $post ) {

			$settings = mycred_sell_content_settings();

			// Do not add the metabox unless we set this post type to be "manual"
			if ( $settings['filters'][ $post->post_type ]['by'] !== 'manual' ) return;

			add_meta_box(
				'mycred-sell-content-setup',
				apply_filters( 'mycred_sell_this_label', __( 'Sell Content', 'mycred' ), $this ),
				array( $this, 'metabox' ),
				$post->post_type,
				'side',
				'high'
			);

			add_filter( 'postbox_classes_' . $post->post_type . '_mycred-sell-content-setup',  array( $this, 'metabox_classes' ) );

		}

		/**
		 * Sell Meta Box
		 * @since 0.1
		 * @version 1.2
		 */
		public function metabox( $post ) {

			$settings   = mycred_sell_content_settings();
			$expiration = apply_filters( 'mycred_sell_exp_title', __( 'Hour(s)', 'mycred' ) );
			$is_author  = ( ( $post->post_author == $this->current_user_id ) ? true : false );

?>
<style type="text/css">
#mycred-sell-content-setup .inside { padding: 0 !important; }
#mycred-sell-content-setup .inside .row { margin-bottom: 0; }
#mycred-sell-content-setup .inside .container-fluid { padding-left: 0; padding-right: 0; }
#mycred-sell-content-setup .inside .row .col-lg-12 .form-group { padding: 12px 12px 10px 12px; background-color: white; border-bottom: 1px solid #ddd; }
#mycred-sell-content-types .point-type-setup .cover { border-bottom: 1px solid #ddd; }
#mycred-sell-content-types .point-type-setup .cover > .row { padding-top: 6px; padding-bottom: 12px; }
#mycred-sell-content-types .point-type-setup:last-child .cover { border-bottom: none; }
</style>
<div id="mycred-sell-content-types" class="container-fluid">
	<input type="hidden" name="mycred-sell-this-setup-token" value="<?php echo wp_create_nonce( 'mycred-sell-this-content' ); ?>" />
<?php

			if ( ! empty( $settings['type'] ) ) {
				foreach ( $settings['type'] as $point_type ) {

					$setup  = mycred_get_option( 'mycred_sell_this_' . $point_type );

					if ( $setup['status'] === 'disabled' ) continue;

					$mycred     = mycred( $point_type );

					$suffix = '_' . $point_type;
					if ( $point_type == MYCRED_DEFAULT_TYPE_KEY )
						$suffix = '';

					$sale_setup = (array) get_post_meta( $post->ID, 'myCRED_sell_content' . $suffix, true );
					$sale_setup = wp_parse_args( $sale_setup, array(
						'status' => 'disabled',
						'price'  => 0,
						'expire' => 0 
					) );

					$expiration_description = __( 'Never expires', 'mycred' );
					if ( absint( $sale_setup['expire'] ) > 0 )
						$expiration_description = $sale_setup['expire'] . ' ' . $expiration;

?>
	<div class="form point-type-setup">
		<div class="row row-narrow">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<div class="form-group slim">
					<label for="mycred-sell-this-<?php echo $point_type; ?>-status" class="slim"><input type="checkbox" name="mycred_sell_this[<?php echo $point_type; ?>][status]" id="mycred-sell-this-<?php echo $point_type; ?>-status"<?php if ( $sale_setup['status'] === 'enabled' ) echo ' checked="checked"'; ?> value="enabled" class="toggle-setup" data-type="<?php echo $point_type; ?>" /> <?php printf( __( 'Sell using %s', 'Point types name', 'mycred' ), $mycred->plural() ); ?></label>
				</div>
			</div>
		</div>
		<div class="cover">
			<div class="row row-narrow padded-row mycred-sell-setup-container" id="mycred-sell-content-<?php echo $point_type; ?>-wrap" style="display: <?php if ( $sale_setup['status'] === 'enabled' ) echo 'block'; else echo 'none'; ?>;">
				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
					<div class="form-group slim">
						<label for="mycred-sell-this-<?php echo $point_type; ?>-price"><?php _e( 'Price', 'mycred' ); ?></label>
						<input type="text" name="mycred_sell_this[<?php echo $point_type; ?>][price]" id="mycred-sell-this-<?php echo $point_type; ?>-price" class="form-control" value="<?php echo esc_attr( $sale_setup['price'] ); ?>" />
					</div>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
					<div class="form-group slim">
						<label for="mycred-sell-this-<?php echo $point_type; ?>-expire"><?php _e( 'Expiration', 'mycred' ); ?></label>
						<input type="text" name="mycred_sell_this[<?php echo $point_type; ?>][expire]" id="mycred-sell-this-<?php echo $point_type; ?>-expire" class="form-control" value="<?php echo absint( $sale_setup['expire'] ); ?>" />
					</div>
				</div>
			</div>
		</div>
	</div>
<?php

				}
			}

?>
<script type="text/javascript">
(function($) {
	
	$( '#mycred-sell-content-types .toggle-setup' ).click(function(){

		if ( $(this).is( ':checked' ) ) {
			$( '#mycred-sell-content-types #mycred-sell-content-' + $(this).data( 'type' ) + '-wrap' ).slideDown();
		}
		else {
			$( '#mycred-sell-content-types #mycred-sell-content-' + $(this).data( 'type' ) + '-wrap' ).slideUp();
		}

	});

})( jQuery );
</script>
</div>
<?php

		}

		/**
		 * Save Sell Meta Box
		 * @since 0.1
		 * @version 1.1
		 */
		public function save_metabox( $post_id ) {

			// Minimum requirement
			if ( ! isset( $_POST['mycred_sell_this'] ) || ! is_array( $_POST['mycred_sell_this'] ) || empty( $_POST['mycred_sell_this'] ) ) return;

			// Verify nonce
			if ( isset( $_POST['mycred-sell-this-setup-token'] ) && wp_verify_nonce( $_POST['mycred-sell-this-setup-token'], 'mycred-sell-this-content' ) ) {

				$settings   = mycred_sell_content_settings();

				if ( ! empty( $settings['type'] ) ) {
					foreach ( $settings['type'] as $point_type ) {

						if ( ! array_key_exists( $point_type, $_POST['mycred_sell_this'] ) ) continue;

						$mycred     = mycred( $point_type );

						$new_setup  = array( 'status' => '', 'price' => 0, 'expire' => 0 );
						$submission = wp_parse_args( $_POST['mycred_sell_this'][ $point_type ], array(
							'status' => 'disabled',
							'price'  => '',
							'expire' => ''
						) );

						// If not empty and different from the general setup, save<
						if ( in_array( $submission['status'], array( 'enabled', 'disabled' ) ) )
							$new_setup['status'] = sanitize_key( $submission['status'] );

						// If not empty and different from the general setup, save<
						if ( strlen( $submission['price'] ) > 0 )
							$new_setup['price'] = $mycred->number( sanitize_text_field( $submission['price'] ) );

						// If not empty and different from the general setup, save<
						if ( strlen( $submission['expire'] ) > 0 )
							$new_setup['expire'] = absint( sanitize_text_field( $submission['expire'] ) );

						$suffix = '_' . $point_type;
						if ( $point_type == MYCRED_DEFAULT_TYPE_KEY )
							$suffix = '';

						update_post_meta( $post_id, 'myCRED_sell_content' . $suffix, $new_setup );

					}
				}

			}

		}

		/**
		 * The Content Overwrite
		 * Handles content sales by replacing the posts content with the appropriate template
		 * for those who have not paid. Admins and authors are excluded.
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function the_content( $content ) {

			global $mycred_partial_content_sale, $mycred_sell_this;

			$mycred_partial_content_sale = false;

			$post_id = mycred_sell_content_post_id();
			$post    = get_post( $post_id );

			// If content is for sale
			if ( mycred_post_is_for_sale( $post_id ) ) {

				$mycred_sell_this = true;

				// Parse shortcodes now to see if mycred_sell_this has been used
				$content = do_shortcode( $content );

				// Partial Content Sale - We have already done the work in the shortcode
				if ( $mycred_partial_content_sale === true )
					return $content;

				// Logged in users
				if ( is_user_logged_in() ) {

					// Authors and admins do not pay
					if ( ! mycred_is_admin() && $post->post_author !== $this->current_user_id ) {

						// In case we have not paid
						if ( ! mycred_user_paid_for_content( $this->current_user_id, $post_id ) ) {

							// Get Payment Options
							$payment_options = mycred_sell_content_payment_buttons( $this->current_user_id, $post_id );

							// User can buy
							if ( $payment_options !== false ) {

								$content = $this->sell_content['templates']['members'];
								$content = str_replace( '%buy_button%', $payment_options, $content );
								$content = mycred_sell_content_template( $content, $post, 'mycred-sell-entire-content', 'mycred-sell-unpaid' );

							}

							// Can not afford to buy
							else {

								$content = $this->sell_content['templates']['cantafford'];
								$content = mycred_sell_content_template( $content, $post, 'mycred-sell-entire-content', 'mycred-sell-insufficient' );

							}

						}

					}

				}

				// Visitors
				else {

					$content = $this->sell_content['templates']['visitors'];
					$content = mycred_sell_content_template( $content, $post, 'mycred-sell-entire-content', 'mycred-sell-visitor' );

				}

			}

			return do_shortcode( $content );

		}

		/**
		 * Make Purchase AJAX
		 * @since 1.1
		 * @version 1.4
		 */
		public function action_buy_content() {

			// Security
			check_ajax_referer( 'mycred-buy-this-content', 'token' );

			// Prep
			$post_id    = absint( $_POST['postid'] );
			$user_id    = get_current_user_id();

			// Current User
			$user_id = get_current_user_id();

			if ( mycred_force_singular_session( $user_id, 'mycred-last-content-purchase' ) )
				wp_send_json( 101 );

			$point_type = sanitize_key( $_POST['ctype'] );
			$post       = get_post( $post_id );

			// Minimum requirements
			if ( $post_id === 0 || ! mycred_point_type_exists( $point_type ) || ! isset( $post->post_author ) ) die( 0 );

			// Attempt purchase
			$purchase   = mycred_sell_content_new_purchase( $post, $user_id, $point_type );

			$content    = '';

			// Successfull purchase
			if ( $purchase === true ) {

				preg_match('/\[mycred_sell_this[^\]]*](.*)\[\/mycred_sell_this[^\]]*]/uis', $post->post_content , $match );

				$content = $post->post_content;
				if ( is_array( $match ) && array_key_exists( 1, $match ) )
					$content = $match[1];

				do_action( 'mycred_sell_before_content_render' );

				remove_filter( 'the_content', array( $this, 'the_content' ), 5 );
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				$content = do_shortcode( $content );
				add_filter( 'the_content', array( $this, 'the_content' ), 5 );

			}

			// Something went wrong
			else {

				$content = str_replace( '%error%', $purchase, $content );

			}

			// Let others play
			$content = apply_filters( 'mycred_content_purchase_ajax', $content, $purchase );

			if ( $purchase !== true )
				wp_send_json_error( $purchase );

			wp_send_json_success( $content );

		}

		/**
		 * Add Email Notice Instance
		 * @since 1.5.4
		 * @version 1.0
		 */
		public function email_notice_instance( $events, $request ) {

			if ( $request['ref'] == 'buy_content' ) {
				if ( $request['amount'] < 0 )
					$events[] = 'buy_content|negative';
				elseif ( $request['amount'] > 0 )
					$events[] = 'buy_content|positive';
			}

			return $events;

		}

		/**
		 * Support for Email Notices
		 * @since 1.1
		 * @version 1.0
		 */
		public function email_notices( $data ) {

			if ( $data['request']['ref'] == 'buy_content' ) {
				$message         = $data['message'];
				$data['message'] = $this->core->template_tags_post( $message, $data['request']['ref_id'] );
			}

			return $data;

		}

	}

endif;

/**
 * Load Sell Content Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_sell_content_addon' ) ) :
	function mycred_load_sell_content_addon( $modules, $point_types ) {

		$modules['solo']['content'] = new myCRED_Sell_Content_Module();
		$modules['solo']['content']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_sell_content_addon', 90, 2 );

?>