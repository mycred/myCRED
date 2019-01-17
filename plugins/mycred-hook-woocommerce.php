<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * WooCommerce Setup
 * @since 1.5
 * @version 1.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_woocommerce_reward', 90 );
if ( ! function_exists( 'mycred_load_woocommerce_reward' ) ) :
	function mycred_load_woocommerce_reward() {

		if ( ! class_exists( 'WooCommerce' ) ) return;

		add_filter( 'mycred_comment_gets_cred',     'mycred_woo_remove_review_from_comments', 10, 2 );
		add_action( 'add_meta_boxes_product',       'mycred_woo_add_product_metabox' );
		add_action( 'save_post',                    'mycred_woo_save_reward_settings' );
		add_action( 'woocommerce_payment_complete', 'mycred_woo_payout_rewards' );

	}
endif;

/**
 * Remove Reviews from Comment Hook
 * Prevents the comment hook from granting points twice for a review.
 * @since 1.6.3
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_remove_review_from_comments' ) ) :
	function mycred_woo_remove_review_from_comments( $reply, $comment ) {

		if ( get_post_type( $comment->comment_post_ID ) == 'product' ) return false;

		return $reply;

	}
endif;

/**
 * Add Reward Metabox
 * @since 1.5
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_add_product_metabox' ) ) :
	function mycred_woo_add_product_metabox() {

		add_meta_box(
			'mycred_woo_sales_setup',
			mycred_label(),
			'mycred_woo_product_metabox',
			'product',
			'side',
			'high'
		);

	}
endif;

/**
 * Product Metabox
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_woo_product_metabox' ) ) :
	function mycred_woo_product_metabox( $post ) {

		if ( ! current_user_can( apply_filters( 'mycred_woo_reward_cap', 'edit_others_posts' ) ) ) return;

		$types = mycred_get_types();
		$prefs = (array) get_post_meta( $post->ID, 'mycred_reward', true );

		foreach ( $types as $type => $label ) {
			if ( ! isset( $prefs[ $type ] ) )
				$prefs[ $type ] = '';
		}

		$count = 0;
		$cui   = get_current_user_id();
		foreach ( $types as $type => $label ) {

			$count ++;
			$mycred = mycred( $type );

			if ( ! $mycred->can_edit_creds( $cui ) ) continue;

?>
<p class="<?php if ( $count == 1 ) echo 'first'; ?>"><label for="mycred-reward-purchase-with-<?php echo $type; ?>"><input class="toggle-mycred-reward" data-id="<?php echo $type; ?>" <?php if ( $prefs[ $type ] != '' ) echo 'checked="checked"'; ?> type="checkbox" name="mycred_reward[<?php echo $type; ?>][use]" id="mycred-reward-purchase-with-<?php echo $type; ?>" value="<?php echo $prefs[ $type ]; ?>" /> <?php echo $mycred->template_tags_general( __( 'Reward with %plural%', 'mycred' ) ); ?></label></p>
<div class="mycred-woo-wrap" id="reward-<?php echo $type; ?>" style="display:<?php if ( $prefs[ $type ] == '' ) echo 'none'; else echo 'block'; ?>">
	<label><?php echo $mycred->plural(); ?></label> <input type="text" size="8" name="mycred_reward[<?php echo $type; ?>][amount]" value="<?php echo $prefs[ $type ]; ?>" placeholder="<?php echo $mycred->zero(); ?>" />
</div>
<?php

		}

?>
<script type="text/javascript">
jQuery(function($) {

	$( '.toggle-mycred-reward' ).click(function(){
		var target = $(this).attr( 'data-id' );
		$( '#reward-' + target ).toggle();
	});

});
</script>
<style type="text/css">
#mycred_woo_sales_setup .inside { margin: 0; padding: 0; }
#mycred_woo_sales_setup .inside > p { padding: 12px; margin: 0; border-top: 1px solid #ddd; }
#mycred_woo_sales_setup .inside > p.first { border-top: none; }
#mycred_woo_sales_setup .inside .mycred-woo-wrap { padding: 6px 12px; line-height: 27px; text-align: right; border-top: 1px solid #ddd; background-color: #F5F5F5; }
#mycred_woo_sales_setup .inside .mycred-woo-wrap label { display: block; font-weight: bold; float: left; }
#mycred_woo_sales_setup .inside .mycred-woo-wrap input { width: 50%; }
#mycred_woo_sales_setup .inside .mycred-woo-wrap p { margin: 0; padding: 0 12px; font-style: italic; text-align: center; }
</style>
<?php

	}
endif;

/**
 * Save Reward Setup
 * @since 1.5
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_save_reward_settings' ) ) :
	function mycred_woo_save_reward_settings( $post_id ) {

		if ( ! isset( $_POST['mycred_reward'] ) || get_post_type( $post_id ) != 'product' ) return;

		$new_settings = array();
		foreach ( $_POST['mycred_reward'] as $type => $prefs ) {

			$mycred = mycred( $type );
			if ( isset( $prefs['use'] ) )
				$new_settings[ $type ] = $mycred->number( $prefs['amount'] );

		}

		update_post_meta( $post_id, 'mycred_reward', $new_settings );

	}
endif;

/**
 * Payout Rewards
 * @since 1.5
 * @version 1.0
 */
if ( ! function_exists( 'mycred_woo_payout_rewards' ) ) :
	function mycred_woo_payout_rewards( $order_id ) {

		// Get Order
		$order = wc_get_order( $order_id );

		// If we paid with myCRED we do not award points by default
		if ( $order->payment_method == 'mycred' && apply_filters( 'mycred_woo_reward_mycred_payment', false ) === false )
			return;

		// Get items
		$items = $order->get_items();
		$types = mycred_get_types();

		// Loop
		foreach ( $types as $type => $label ) {

			// Load type
			$mycred = mycred( $type );

			// Check for exclusions
			if ( $mycred->exclude_user( $order->user_id ) ) continue;

			// Calculate reward
			$reward = $mycred->zero();
			foreach ( $items as $item ) {
				$prefs = (array) get_post_meta( $item['product_id'], 'mycred_reward', true );
				if ( isset( $prefs[ $type ] ) && $prefs[ $type ] != '' )
					$reward = ( $reward + ( $prefs[ $type ] * $item['qty'] ) );
			}

			// Let others play with the reference and log entry
			$reference = apply_filters( 'mycred_woo_reward_reference', 'reward', $order_id, $type );
			$log       = apply_filters( 'mycred_woo_reward_log',       '%plural% reward for store purchase', $order_id, $type );

			// Award
			if ( ! $mycred->has_entry( $reference, $order_id, $order->user_id ) ) {

				// Execute
				$mycred->add_creds(
					$reference,
					$order->user_id,
					$reward,
					$log,
					$order_id,
					array( 'ref_type' => 'post' ),
					$type
				);

			}

		}

	}
endif;

/**
 * Register Hook
 * @since 1.5
 * @version 1.0.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_woocommerce_hook', 95 );
function mycred_register_woocommerce_hook( $installed ) {

	if ( ! class_exists( 'WooCommerce' ) ) return $installed;

	$installed['wooreview'] = array(
		'title'       => __( 'WooCommerce Product Reviews', 'mycred' ),
		'description' => __( 'Awards %_plural% for users leaving reviews on your WooCommerce products.', 'mycred' ),
		'callback'    => array( 'myCRED_Hook_WooCommerce_Reviews' )
	);

	return $installed;

}

/**
 * WooCommerce Product Review Hook
 * @since 1.5
 * @version 1.1.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_woocommerce_hook', 95 );
function mycred_load_woocommerce_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_Hook_WooCommerce_Reviews' ) || ! class_exists( 'WooCommerce' ) ) return;

	class myCRED_Hook_WooCommerce_Reviews extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'wooreview',
				'defaults' => array(
					'creds' => 1,
					'log'   => '%plural% for product review',
					'limit' => '0/x'
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.5
		 * @version 1.0
		 */
		public function run() {

			add_action( 'comment_post',              array( $this, 'new_review' ), 99, 2 );
			add_action( 'transition_comment_status', array( $this, 'review_transitions' ), 99, 3 );

		}

		/**
		 * New Review
		 * @since 1.5
		 * @version 1.0
		 */
		public function new_review( $comment_id, $comment_status ) {

			// Approved comment
			if ( $comment_status == '1' )
				$this->review_transitions( 'approved', 'unapproved', $comment_id );

		}

		/**
		 * Review Transitions
		 * @since 1.5
		 * @version 1.2
		 */
		public function review_transitions( $new_status, $old_status, $comment ) {

			// Only approved reviews give points
			if ( $new_status != 'approved' ) return;

			// Passing an integer instead of an object means we need to grab the comment object ourselves
			if ( ! is_object( $comment ) )
				$comment = get_comment( $comment );

			// No comment object so lets bail
			if ( $comment === NULL ) return;

			// Only applicable for reviews
			if ( get_post_type( $comment->comment_post_ID ) != 'product' ) return;

			// Check for exclusions
			if ( $this->core->exclude_user( $comment->user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( '', 'product_review', $comment->user_id ) ) return;

			// Execute
			$data = array( 'ref_type' => 'post' );
			if ( ! $this->core->has_entry( 'product_review', $comment->comment_post_ID, $comment->user_id, $data, $this->mycred_type ) )
				$this->core->add_creds(
					'product_review',
					$comment->user_id,
					$this->prefs['creds'],
					$this->prefs['log'],
					$comment->comment_post_ID,
					$data,
					$this->mycred_type
				);

		}

		/**
		 * Preferences for WooCommerce Product Reviews
		 * @since 1.5
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>
<label class="subheader"><?php echo $this->core->plural(); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'creds' ); ?>" id="<?php echo $this->field_id( 'creds' ); ?>" value="<?php echo $this->core->number( $prefs['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( 'limit' ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), $prefs['limit'] ); ?>
	</li>
</ol>
<label class="subheader"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'log' ); ?>" id="<?php echo $this->field_id( 'log' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<?php

		}

		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.0
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $data['limit_by'] );
			}

			return $data;

		}

	}

}

?>