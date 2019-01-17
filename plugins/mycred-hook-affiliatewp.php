<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.6
 * @version 1.0.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_affiliatewp_hook', 10 );
function mycred_register_affiliatewp_hook( $installed ) {

	if ( ! class_exists( 'Affiliate_WP' ) ) return $installed;

	$installed['affiliatewp'] = array(
		'title'       => __( 'AffiliateWP', 'mycred' ),
		'description' => __( 'Awards %_plural% for affiliate signups, referring visitors and store sale referrals.', 'mycred' ),
		'callback'    => array( 'myCRED_AffiliateWP' )
	);

	return $installed;

}

/**
 * Affiliate WP Hook
 * @since 1.6
 * @version 1.0.2
 */
add_action( 'mycred_load_hooks', 'mycred_load_affiliatewp_hook', 10 );
function mycred_load_affiliatewp_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_AffiliateWP' ) || ! class_exists( 'Affiliate_WP' ) ) return;

	class myCRED_AffiliateWP extends myCRED_Hook {

		public $currency;

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'affiliatewp',
				'defaults' => array(
					'signup' => array(
						'creds'  => 0,
						'log'    => '%plural% for becoming an affiliate'
					),
					'visits' => array(
						'creds'  => 0,
						'log'    => '%plural% for referral of a visitor',
						'limit'  => '0/x'
					),
					'referrals' => array(
						'creds'      => 0,
						'log'        => '%plural% for store referral',
						'remove_log' => '%plural% refund for rejected sale',
						'pay'        => 'amount'
					)
				)
			), $hook_prefs, $type );

			$this->currency = affiliate_wp()->settings->get( 'currency', 'USD' );

		}

		/**
		 * Run
		 * @since 1.6
		 * @version 1.0
		 */
		public function run() {

			// If we reward affiliate signups
			if ( $this->prefs['signup']['creds'] != 0 )
				add_action( 'affwp_register_user', array( $this, 'affiliate_signup' ), 10, 3 );

			// If we reward visit referrals
			if ( $this->prefs['visits']['creds'] != 0 )
				add_action( 'affwp_post_insert_visit', array( $this, 'new_visit' ), 10, 2 );

			// If we reward referrals
			if ( $this->prefs['referrals']['creds'] != 0 )
				add_action( 'affwp_set_referral_status', array( $this, 'referral_payouts' ), 10, 3 );

		}

		/**
		 * Affiliate Signup
		 * @since 1.6
		 * @version 1.0
		 */
		public function affiliate_signup( $affiliate_id, $status, $args ) {

			if ( $status == 'pending' ) return;

			// Get user id from affiliate id
			$user_id = affwp_get_affiliate_user_id( $affiliate_id );

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Execute (if not done so already)
			if ( ! $this->has_entry( 'affiliate_signup', $affiliate_id, $user_id ) )
				$this->core->add_creds(
					'affiliate_signup',
					$user_id,
					$this->prefs['signup']['creds'],
					$this->prefs['signup']['log'],
					$affiliate_id,
					'',
					$this->mycred_type
				);

		}

		/**
		 * New Visit
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function new_visit( $insert_id, $data ) {

			$affiliate_id = absint( $data['affiliate_id'] );
			$user_id      = affwp_get_affiliate_user_id( $affiliate_id );

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'visits', 'affiliate_visit_referral', $user_id ) ) return;

			// Execute
			$this->core->add_creds(
				'affiliate_visit_referral',
				$user_id,
				$this->prefs['visits']['creds'],
				$this->prefs['visits']['log'],
				$insert_id,
				$data,
				$this->mycred_type
			);

		}

		/**
		 * Referral Payout
		 * @since 1.6
		 * @version 1.0
		 */
		public function referral_payouts( $referral_id, $new_status, $old_status ) {

			// If the referral id isn't valid
			if ( ! is_numeric( $referral_id ) ) {
				return;
			}

			// Get the referral object
			$referral = affwp_get_referral( $referral_id );

			// Get the user id
			$user_id = affwp_get_affiliate_user_id( $referral->affiliate_id );

			if ( array_key_exists( $currency, $this->point_types ) && $this->prefs['referrals']['pay'] == 'store' )
				$amount = $referral->amount;

			elseif ( $this->prefs['referrals']['pay'] == 'amount' )
				$amount = $this->prefs['referrals']['creds'];

			else
				$amount = $this->core->number( ( $referral->amount / $this->prefs['referrals']['creds'] ) );

			$amount = apply_filters( 'mycred_affiliatewp_payout', $amount, $referral, $new_status, $old_status, $this );

			if ( 'paid' === $new_status ) {

				$this->core->add_creds(
					'affiliate_referral',
					$user_id,
					$amount,
					$this->prefs['referrals']['log'],
					$referral_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);

			}
			
			elseif ( ( 'paid' === $old_status ) && ( 'unpaid' === $new_status ) ) {

				$this->core->add_creds(
					'affiliate_referral_refund',
					$user_id,
					0-$amount,
					$this->prefs['referrals']['remove_log'],
					$referral_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);

			}

		}

		/**
		 * Preferences
		 * @since 1.6
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;

			$label = $block = '';
			if ( ! array_key_exists( $this->currency, $this->point_types ) )
				$label = '1 ' . $this->currency . ' = ';
			else
				$block = 'readonly="readonly"';

?>
<label for="<?php echo $this->field_id( array( 'signup', 'creds' ) ); ?>" class="subheader"><?php _e( 'Affiliate Signup', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'signup', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'signup', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['signup']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'signup', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'signup', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'signup', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['signup']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>

<label for="<?php echo $this->field_id( array( 'visits', 'creds' ) ); ?>" class="subheader"><?php _e( 'Referring Visitors', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'visits', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'visits', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['visits']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'visits', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'visits', 'limit' ) ), $this->field_id( array( 'visits', 'limit' ) ), $prefs['visits']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'visits', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'visits', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'visits', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['visits']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>

<label for="<?php echo $this->field_id( array( 'referrals', 'pay' ) ); ?>" class="subheader"><?php _e( 'Referring Sales', 'mycred' ); ?></label>
<ol>
	<li>
		<label for="<?php echo $this->field_id( array( 'referrals', 'pay-amount' ) ); ?>"><input type="radio" name="<?php echo $this->field_name( array( 'referrals', 'pay' ) ); ?>" id="<?php echo $this->field_id( array( 'referrals', 'pay-amount' ) ); ?>"<?php checked( $this->prefs['referrals']['pay'], 'amount' ); ?> value="amount" class="toggles-creds" /> <?php _e( 'Pay a set amount for all referrals.', 'mycred' ); ?></label><br />
		<label for="<?php echo $this->field_id( array( 'referrals', 'pay-store' ) ); ?>"><input type="radio" name="<?php echo $this->field_name( array( 'referrals', 'pay' ) ); ?>" id="<?php echo $this->field_id( array( 'referrals', 'pay-store' ) ); ?>"<?php checked( $this->prefs['referrals']['pay'], 'store' ); ?> value="store" class="toggles-creds" /> <?php echo $this->core->template_tags_general( __( 'AffiliateWP will use %plural% as currency so pay the referral amount.', 'mycred' ) ); ?></label><br />
		<label for="<?php echo $this->field_id( array( 'referrals', 'pay-ex' ) ); ?>"><input type="radio" name="<?php echo $this->field_name( array( 'referrals', 'pay' ) ); ?>"<?php echo $block; ?> id="<?php echo $this->field_id( array( 'referrals', 'pay-ex' ) ); ?>"<?php checked( $this->prefs['referrals']['pay'], 'ex' ); ?> value="ex" class="toggles-creds" /> <?php _e( 'Apply an exchange rate against the referral amount.', 'mycred' ); ?></label>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'referrals', 'creds-ex' ) ); ?>"><?php _e( 'Amount', 'mycred' ); ?></label>
		<div class="h2"><span id="<?php echo $this->field_id( array( 'referrals', 'creds-ex' ) ); ?>" style="display:none;"><?php echo $label; ?></span><input type="text" name="<?php echo $this->field_name( array( 'referrals', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'referrals', 'creds' ) ); ?>" value="<?php echo $prefs['referrals']['creds']; ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'referrals', 'log' ) ); ?>"><?php _e( 'Log template - Payout', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'referrals', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'referrals', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['referrals']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'referrals', 'remove_log' ) ); ?>"><?php _e( 'Log template - Refund', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'referrals', 'remove_log' ) ); ?>" id="<?php echo $this->field_id( array( 'referrals', 'remove_log' ) ); ?>" value="<?php echo esc_attr( $prefs['referrals']['remove_log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<script type="text/javascript">
jQuery(function($) {
	$( '.toggles-creds' ).change(function(){
		if ( $(this).val() == 'ex' )
			$( '#<?php echo $this->field_id( array( 'referrals', 'creds-ex' ) ); ?>' ).show();
		else
			$( '#<?php echo $this->field_id( array( 'referrals', 'creds-ex' ) ); ?>' ).hide();
	});
});
</script>
<?php

		}
		
		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.0
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['visits']['limit'] ) && isset( $data['visits']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['visits']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['visits']['limit'] = $limit . '/' . $data['visits']['limit_by'];
				unset( $data['visits']['limit_by'] );
			}

			return $data;

		}

	}

	class myCRED_Adjust_AffiliateWP {

		protected static $instance = NULL;
		public $mycred;
		public $point_types;

		/**
		 * Access this plugin’s working instance
		 * @since 1.6
		 * @version 1.0
		 */
		public static function get_instance() {

			NULL === self::$instance and self::$instance = new self;
			return self::$instance;

		}

		/**
		 * Start
		 * @since 1.6
		 * @version 1.0
		 */
		public function plugin_setup() {

			// Add option to use myCRED Points as currency
			add_filter( 'affwp_currencies',    array( $this, 'add_currencies' ) );

			// If a point type is set as currency, filter the way amounts are displayed
			$currency = affiliate_wp()->settings->get( 'currency', MYCRED_DEFAULT_TYPE_KEY );
			if ( array_key_exists( $currency, $this->point_types ) ) {

				add_filter( 'affwp_format_amount',                            array( $this, 'amount' ) );
				add_filter( 'affwp_sanitize_amount_decimals',                 array( $this, 'decimals' ) );
				add_filter( 'affwp_' . $currency . '_currency_filter_before', array( $this, 'before' ), 10, 3 );
				add_filter( 'affwp_' . $currency . '_currency_filter_after',  array( $this, 'after' ), 10, 3 );

			}

		}

		/**
		 * Construct
		 * @since 1.6
		 * @version 1.0
		 */
		public function __construct() {

			// Get all types
			$this->point_types = mycred_get_types();

			// Get the used type (if used)
			$type     = MYCRED_DEFAULT_TYPE_KEY;
			$currency = affiliate_wp()->settings->get( 'currency', MYCRED_DEFAULT_TYPE_KEY );
			if ( array_key_exists( $currency, $this->point_types ) )
				$type = $currency;

			// Load myCRED
			$this->mycred = mycred( $type );

		}

		public function add_currencies( $currencies ) {

			foreach ( $this->point_types as $type_id => $label ) {
				if ( ! array_key_exists( $type_id, $currencies ) )
					$currencies[ $type_id ] = $label;
			}
			return $currencies;

		}

		public function amount( $amount ) {

			// Format myCRED way
			return $this->mycred->format_number( $amount );

		}

		public function before( $formatted, $currency, $amount ) {

			// No need to add if empty
			if ( $this->mycred->before != '' )
				$formatted = $this->mycred->before . ' ' . $amount;

			// Some might have applied adjustments how points are shown, apply them here as well
			return apply_filters( 'mycred_format_creds', $formatted, $amount, $this->mycred );

		}

		public function after( $formatted, $currency, $amount ) {

			// No need to add if empty
			if ( $this->mycred->after != '' )
				$formatted = $amount . ' ' . $this->mycred->after;

			// Some might have applied adjustments how points are shown, apply them here as well
			return apply_filters( 'mycred_format_creds', $formatted, $amount, $this->mycred );

		}

		public function decimals( $decimals ) {

			// Get decimal setup
			return absint( $this->mycred->format['decimals'] );

		}

	}

	$mycred_affiliatewp = new myCRED_Adjust_AffiliateWP();
	$mycred_affiliatewp->plugin_setup();

}

?>