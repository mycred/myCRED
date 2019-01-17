<?php
if ( ! defined( 'MYCRED_PURCHASE' ) ) exit;

/**
 * buyCRED_Pending_Payments class
 * @since 1.7
 * @version 1.0.1
 */
if ( ! class_exists( 'buyCRED_Pending_Payments' ) ) :
	class buyCRED_Pending_Payments extends myCRED_Module {

		/**
		 * Construct
		 */
		function __construct( $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( 'buyCRED_Pending_Payments', array(
				'module_name' => 'pending_payments',
				'option_id'   => '',
				'defaults'    => array(),
				'screen_id'   => '',
				'accordion'   => false,
				'add_to_core' => false,
				'menu_pos'    => 75
			), $type );

		}

		/**
		 * Load
		 * @version 1.0.1
		 */
		public function load() {

			add_action( 'mycred_init',       array( $this, 'module_init' ), $this->menu_pos );
			add_action( 'mycred_admin_init', array( $this, 'module_admin_init' ), $this->menu_pos );

		}

		/**
		 * Module Init
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function module_init() {

			$this->register_pending_payments();

			add_shortcode( 'mycred_buy_pending', array( $this, 'render_shortcode_pending' ) );

			add_action( 'mycred_add_menu',       array( $this, 'add_to_menu' ), $this->menu_pos );
			add_action( 'template_redirect',     array( $this, 'intercept_cancellations' ) );

		}

		/**
		 * Intercept Cancellations
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function intercept_cancellations() {

			// Intercept payment cancellations
			if ( isset( $_REQUEST['buycred-cancel'] ) && isset( $_REQUEST['_token'] ) && wp_verify_nonce( $_REQUEST['_token'], 'buycred-cancel-pending-payment' ) ) {

				// Get pending payment object
				$pending_payment_id = sanitize_text_field( $_REQUEST['buycred-cancel'] );
				$pending_payment    = buycred_get_pending_payment( $pending_payment_id );

				// Make sure pending payment still exists and that we are cancelling our own and not someone elses
				if ( $pending_payment === false || $pending_payment->buyer_id != get_current_user_id() ) return;

				// Delete cache
				delete_user_meta( $pending_payment->buyer_id, 'buycred_pending_payments' );

				// Move item to trash
				wp_trash_post( $pending_payment->payment_id );

				// Redirect
				wp_redirect( remove_query_arg( array( 'buycred-cancel', '_token' ) ) );
				exit;

			}

		}

		/**
		 * Module Admin Init
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function module_admin_init() {

			add_filter( 'parent_file',                                array( $this, 'parent_file' ) );
			add_filter( 'submenu_file',                               array( $this, 'subparent_file' ), 10, 2 );

			add_action( 'admin_notices',                              array( $this, 'admin_notices' ) );
			add_filter( 'manage_buycred_payment_posts_columns',       array( $this, 'adjust_column_headers' ) );
			add_action( 'manage_buycred_payment_posts_custom_column', array( $this, 'adjust_column_content' ), 10, 2 );
			add_filter( 'bulk_actions-edit-buycred_payment',          array( $this, 'bulk_actions' ) );
			add_filter( 'post_row_actions',                           array( $this, 'adjust_row_actions' ), 10, 2 );
			add_action( 'admin_head-post.php',                        array( $this, 'edit_pending_payment_style' ) );
			add_action( 'admin_head-edit.php',                        array( $this, 'pending_payments_style' ) );
			add_action( 'save_post_buycred_payment',                  array( $this, 'save_pending_payment' ), 10, 2 );
			add_filter( 'post_updated_messages',                      array( $this, 'post_updated_messages' ) );

			// Intercept payment completions
			if ( isset( $_GET['credit'] ) && isset( $_GET['token'] ) && wp_verify_nonce( $_GET['token'], 'buycred-payout-pending' ) ) {

				$pending_id = absint( $_GET['credit'] );

				if ( $this->core->can_edit_creds() ) {

					$url = remove_query_arg( array( 'credit', 'token' ) );

					if ( buycred_complete_pending_payment( $pending_id ) ) {
						$url = add_query_arg( array( 'credited' => 1 ), $url );
					}
					else {
						$url = add_query_arg( array( 'credited' => 0 ), $url );
					}

					wp_safe_redirect( $url );
					exit;

				}

			}

		}

		/**
		 * Register Pending Payments
		 * @since 1.5
		 * @version 1.0.1
		 */
		protected function register_pending_payments() {

			$labels = array(
				'name'                => _x( 'Pending Payments', 'Post Type General Name', 'mycred' ),
				'singular_name'       => _x( 'Pending Payment', 'Post Type Singular Name', 'mycred' ),
				'menu_name'           => __( 'Pending Payments', 'mycred' ),
				'parent_item_colon'   => '',
				'all_items'           => __( 'Pending Payments', 'mycred' ),
				'view_item'           => '',
				'add_new_item'        => '',
				'add_new'             => '',
				'edit_item'           => __( 'Edit Pending Payment', 'mycred' ),
				'update_item'         => '',
				'search_items'        => '',
				'not_found'           => __( 'Not found in Trash', 'mycred' ),
				'not_found_in_trash'  => __( 'Not found in Trash', 'mycred' ),
			);
			$args = array(
				'labels'               => $labels,
				'supports'             => array( 'title', 'comments' ),
				'hierarchical'         => false,
				'public'               => false,
				'show_ui'              => true,
				'show_in_menu'         => false,
				'show_in_nav_menus'    => false,
				'show_in_admin_bar'    => false,
				'can_export'           => true,
				'has_archive'          => false,
				'exclude_from_search'  => true,
				'publicly_queryable'   => false,
				'register_meta_box_cb' => array( $this, 'add_metaboxes' )
			);
			register_post_type( 'buycred_payment', apply_filters( 'mycred_setup_pending_payment', $args ) );

		}

		/**
		 * Adjust Post Updated Messages
		 * @since 1.7
		 * @version 1.0
		 */
		public function post_updated_messages( $messages ) {

			$messages['buycred_payment'] = array(
				0 => '',
				1 => __( 'Payment Updated.', 'mycred' ),
				2 => __( 'Payment Updated.', 'mycred' ),
				3 => __( 'Payment Updated.', 'mycred' ),
				4 => __( 'Payment Updated.', 'mycred' ),
				5 => __( 'Payment Updated.', 'mycred' ),
				6 => __( 'Payment Updated.', 'mycred' ),
				7 => __( 'Payment Updated.', 'mycred' ),
				8 => __( 'Payment Updated.', 'mycred' ),
				9 => __( 'Payment Updated.', 'mycred' ),
				10 => ''
			);

			return $messages;

		}

		/**
		 * Add Comment
		 * @since 1.7
		 * @version 1.0
		 */
		public function add_comment( $post_id, $event = '', $time = NULL ) {

			return buycred_add_pending_comment( $post_id, $event, $time );

		}

		/**
		 * Admin Notices
		 * @since 1.7
		 * @version 1.0
		 */
		public function admin_notices() {

			if ( isset( $_GET['post_type'] ) && $_GET['post_type'] == 'buycred_payment' && isset( $_GET['credited'] ) ) {
				if ( $_GET['credited'] == 1 )
					echo '<div id="message" class="updated notice is-dismissible"><p>' . __( 'Pending payment successfully credited to account.', 'mycred' ) . '</p><button type="button" class="notice-dismiss"></button></div>';
				elseif ( $_GET['credited'] == 0 )
					echo '<div id="message" class="error notice is-dismissible"><p>' . __( 'Failed to credit the pending payment to account.', 'mycred' ) . '</p><button type="button" class="notice-dismiss"></button></div>';
			}

		}

		/**
		 * Add Admin Menu Item
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function add_to_menu() {

			add_submenu_page(
				MYCRED_SLUG,
				__( 'Pending Payments', 'mycred' ),
				__( 'Pending Payments', 'mycred' ),
				$this->core->edit_creds_cap(),
				'edit.php?post_type=buycred_payment'
			);

		}

		/**
		 * Parent File
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function parent_file( $parent = '' ) {

			global $pagenow;

			if ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'buycred_payment' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' )
				return MYCRED_SLUG;

			return $parent;

		}

		/**
		 * Sub Parent File
		 * @since 1.7.8
		 * @version 1.0
		 */
		public function subparent_file( $subparent = '', $parent = '' ) {

			global $pagenow;

			if ( ( $pagenow == 'edit.php' || $pagenow == 'post-new.php' ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'buycred_payment' ) {

				return 'edit.php?post_type=buycred_payment';
			
			}

			elseif ( $pagenow == 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'buycred_payment' ) {

				return 'edit.php?post_type=buycred_payment';

			}

			return $subparent;

		}

		/**
		 * Pending Payment Column Headers
		 * @since 1.5
		 * @version 1.0
		 */
		public function adjust_column_headers( $columns ) {

			return array(
				'cb'       => $columns['cb'],
				'comments' => $columns['comments'],
				'title'    => __( 'Transaction ID', 'mycred' ),
				'date'     => $columns['date'],
				'author'   => __( 'Buyer', 'mycred' ),
				'amount'   => __( 'Amount', 'mycred' ),
				'cost'     => __( 'Cost', 'mycred' ),
				'gateway'  => __( 'Gateway', 'mycred' ),
				'ctype'    => __( 'Type', 'mycred' )
			);

		}

		/**
		 * Pending Payment Column Content
		 * @since 1.5
		 * @version 1.0
		 */
		public function adjust_column_content( $column_name, $post_id ) {

			global $mycred_modules;

			switch ( $column_name ) {
				case 'author' :

					$from = (int) get_post_meta( $post_id, 'from', true );
					$user = get_userdata( $from );

					if ( isset( $user->display_name ) )
						echo '<a href="' . add_query_arg( array( 'user_id' => $user->ID ), admin_url( 'user-edit.php' ) ) . '">' . $user->display_name . '</a>';
					else
						echo 'ID: ' . $from;

				break;
				case 'amount';

					$type   = get_post_meta( $post_id, 'point_type', true );
					$amount = get_post_meta( $post_id, 'amount', true );
					$mycred = mycred( $type );

					echo $mycred->format_creds( $amount );

				break;
				case 'cost';

					$cost     = get_post_meta( $post_id, 'cost', true );
					$currency = get_post_meta( $post_id, 'currency', true );

					echo $cost . ' ' . $currency;

				break;
				case 'gateway';

					$gateway   = get_post_meta( $post_id, 'gateway', true );
					$installed = $mycred_modules['solo']['buycred']->get();

					if ( isset( $installed[ $gateway ] ) )
						echo $installed[ $gateway ]['title'];
					else
						echo $gateway;

				break;
				case 'ctype';

					$type = get_post_meta( $post_id, 'point_type', true );

					if ( isset( $this->point_types[ $type ] ) )
						echo $this->point_types[ $type ];
					else
						echo $type;

				break;
			}

		}

		/**
		 * Adjust Bulk Actions
		 * @since 1.5
		 * @version 1.0
		 */
		public function bulk_actions( $actions ) {

			unset( $actions['edit'] );
			return $actions;

		}

		/**
		 * Pending Payment Row Actions
		 * @since 1.5
		 * @version 1.1
		 */
		public function adjust_row_actions( $actions, $post ) {

			if ( $post->post_type == 'buycred_payment' && $post->post_status != 'trash' ) {

				unset( $actions['inline hide-if-no-js'] );

				// Add option to "Pay Out" now
				if ( $this->core->can_edit_creds() )
					$actions['credit'] = '<a href="' . esc_url( add_query_arg( array(
						'post_type' => $post->post_type,
						'credit'    => $post->ID,
						'token'     => wp_create_nonce( 'buycred-payout-pending' )
					), admin_url( 'edit.php' ) ) ) . '">' . __( 'Pay Out', 'mycred' ) . '</a>';

			}

			return $actions;

		}

		/**
		 * Edit Pending Payment Style
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function edit_pending_payment_style() {

			global $post_type;

			if ( $post_type !== 'buycred_payment' ) return;

			wp_enqueue_style( 'mycred-bootstrap-grid' );
			wp_enqueue_style( 'mycred-forms' );

			add_filter( 'postbox_classes_buycred_payment_buycred-pending-payment',  array( $this, 'metabox_classes' ) );
			add_filter( 'postbox_classes_buycred_payment_buycred-pending-comments', array( $this, 'metabox_classes' ) );

?>
<script type="text/javascript">
jQuery(function($){

	$(document).ready(function(){
		$( 'h1 .page-title-action, .wrap .page-title-action' ).remove();
		$( '#titlewrap #title' ).attr( 'readonly', 'readonly' ).addClass( 'readonly' );
	});

});
</script>
<?php

		}

		/**
		 * Pending Payment Style
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function pending_payments_style() {

			global $post_type;

			if ( $post_type !== 'buycred_payment' ) return;

?>
<script type="text/javascript">
jQuery(function($){

	$(document).ready(function(){
		$( 'h1 .page-title-action, .wrap .page-title-action' ).remove();
	});

});
</script>
<?php

		}

		/**
		 * Add Metaboxes
		 * @since 1.7
		 * @version 1.0
		 */
		public function add_metaboxes() {

			add_meta_box( 'buycred-pending-payment', __( 'Pending Payment', 'mycred' ), array( $this, 'metabox_pending_payment' ), 'buycred_payment', 'normal', 'high' );

			if ( MYCRED_BUYCRED_PENDING_COMMENTS !== false )
				add_meta_box( 'buycred-pending-comments', __( 'History', 'mycred' ), array( $this, 'metabox_pending_comments' ), 'buycred_payment', 'normal', 'default' );

			remove_meta_box( 'commentstatusdiv', 'buycred_payment', 'normal' );
			remove_meta_box( 'commentsdiv', 'buycred_payment', 'normal' );

			remove_meta_box( 'submitdiv', 'buycred_payment', 'side' );
			add_meta_box( 'submitdiv', __( 'Actions', 'mycred' ), array( $this, 'metabox_pending_actions' ), 'buycred_payment', 'side', 'high' );

		}

		/**
		 * Metabox: Pending Actions
		 * @since 1.7
		 * @version 1.0
		 */
		public function metabox_pending_actions( $post ) {

			$payout_url = add_query_arg( array(
				'post_type' => $post->post_type,
				'credit'    => $post->ID,
				'token'     => wp_create_nonce( 'buycred-payout-pending' )
			), admin_url( 'edit.php' ) );

			$delete_url = get_delete_post_link( $post->ID );

?>
<div class="submitbox mycred-metabox" id="submitpost">
	<div id="minor-publishing">
		<div style="display:none;">
		<?php submit_button( __( 'Save', 'mycred' ), 'button', 'save' ); ?>
		</div>

		<div id="minor-publishing-actions">

			<div><a href="<?php echo $payout_url; ?>" class="button button-secondary button-block"><?php _e( 'Pay Out', 'mycred' ); ?></a></div>
			<div><a href="<?php echo $delete_url; ?>" class="button button-secondary button-block"><?php _e( 'Trash', 'mycred' ); ?></a></div>

		</div>

		<div class="clear"></div>
	</div>
	<div id="major-publishing-actions">

		<div id="publishing-action">
			<span class="spinner"></span>

			<input type="submit" id="publish" class="button button-primary primary button-large" value="<?php _e( 'Save Changes', 'mycred' ); ?>" />

		</div>
		<div class="clear"></div>
	</div>
</div>
<?php

		}

		/**
		 * Metabox: Pending Payment
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function metabox_pending_payment( $post ) {

			global $mycred_modules;

			$pending_payment = buycred_get_pending_payment( $post->ID );
			$buyer_name      = 'ID: ' . $pending_payment->buyer_id;

			$buyer_object    = get_userdata( $pending_payment->buyer_id );
			if ( isset( $buyer_object->ID ) ) {
				$buyer_name = $buyer_object->display_name;
				if ( $buyer_name == '' )
					$buyer_name = $buyer_object->user_email;
			}

			if ( $pending_payment->recipient_id == $pending_payment->buyer_id )
				$recipient_name = $buyer_name;

			else {
				$recipient_name   = 'ID: ' . $pending_payment->recipient_id;
				$recipient_object = get_userdata( $pending_payment->recipient_id );
				if ( isset( $recipient_object->ID ) ) {
					$recipient_name = $recipient_object->display_name;
					if ( $recipient_name == '' )
						$recipient_name = $recipient_object->user_email;
				}
			}

			if ( $pending_payment->point_type == $this->core->cred_id )
				$mycred = $this->core;

			else
				$mycred = mycred( $type );

?>
<div class="form">
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label><?php _e( 'Payer', 'mycred' ); ?></label>
				<p class="form-control-static"><?php echo esc_attr( $buyer_name ); ?></p>
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label><?php _e( 'Recipient', 'mycred' ); ?></label>
				<p class="form-control-static"><?php echo esc_attr( $recipient_name ); ?></p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-3 col-sm-6">
			<div class="form-group">
				<label for="buycred-pending-payment-point_type"><?php _e( 'Point Type', 'mycred' ); ?></label>
<?php

			// Just one point type is set for sale. No need for a dropdown menu
			if ( count( $this->core->buy_creds['types'] ) == 1 ) {

?>
				<p class="form-control-static"><?php echo strip_tags( $mycred->plural() ); ?></p>
				<input type="hidden" name="buycred_pending_payment[point_type]" value="<?php echo $pending_payment->point_type; ?>" />
<?php

			}

			// Multiple point types are set for sale. Show a dropdown menu
			else {

?>
				<select name="buycred_pending_payment[point_type]" id="buycred-pending-payment-point_type" class="form-control">
<?php

				foreach ( $this->core->buy_creds['types'] as $point_type ) {

					echo '<option value="' . $point_type . '"';
					if ( $pending_payment->point_type == $point_type ) echo ' selected="selected"';
					echo '>' . mycred_get_point_type_name( $pending_payment->point_type, false ) . '</option>';

				}

?>
				</select>
<?php

			}

?>
			</div>
		</div>
		<div class="col-md-3 col-sm-6">
			<div class="form-group">
				<label for="buycred-pending-payment-gateway"><?php _e( 'Gateway', 'mycred' ); ?></label>
				<select name="buycred_pending_payment[gateway]" id="buycred-pending-payment-gateway" class="form-control">
<?php

			foreach ( $mycred_modules['solo']['buycred']->get() as $gateway_id => $info ) {

				echo '<option value="' . $gateway_id . '"';
				if ( $pending_payment->gateway_id == $gateway_id ) echo ' selected="selected"';
				if ( ! $mycred_modules['solo']['buycred']->is_active( $gateway_id ) ) echo ' disabled="disabled"';
				echo '>' . $info['title'] . '</option>';

			}

?>
				</select>
			</div>
		</div>
		<div class="col-md-2 col-sm-6">
			<div class="form-group">
				<label for="buycred-pending-payment-amount"><?php _e( 'Amount', 'mycred' ); ?></label>
				<input type="text" name="buycred_pending_payment[amount]" id="buycred-pending-payment-amount" class="form-control" value="<?php echo $mycred->number( $pending_payment->amount ); ?>" />
			</div>
		</div>
		<div class="col-md-2 col-sm-6">
			<div class="form-group">
				<label for="buycred-pending-payment-cost"><?php _e( 'Cost', 'mycred' ); ?></label>
				<input type="text" name="buycred_pending_payment[cost]" id="buycred-pending-payment-cost" class="form-control" value="<?php echo esc_attr( $pending_payment->cost ); ?>" />
			</div>
		</div>
		<div class="col-md-2 col-sm-6">
			<div class="form-group">
				<label for="buycred-pending-payment-currency"><?php _e( 'Currency', 'mycred' ); ?></label>
				<input type="text" name="buycred_pending_payment[currency]" id="buycred-pending-payment-currency" class="form-control" value="<?php echo esc_attr( $pending_payment->currency ); ?>" />
			</div>
		</div>
	</div>
</div>
<?php

		}

		/**
		 * Metabox: Pending Payment Comments
		 * @since 1.7
		 * @version 1.0
		 */
		public function metabox_pending_comments( $post ) {

			$comments = get_comments( array( 'post_id' => $post->ID ) );

			echo '<ul class="history">';

			if ( empty( $comments ) ) {

				$c                  = new StdClass();
				$c->comment_date    = $post->post_date;
				$c->comment_content = __( 'Pending request created.', 'mycred' );

				$event = $this->add_comment( $post->ID, $c->comment_content, $c->comment_date );
				if ( $event === false )
					$c->comment_content .= ' Unsaved';

				else
					$c->comment_content .= ' ' . $event;

				$comments[] = $c;

			}

			foreach ( $comments as $comment ) {

				echo '<li><time>' . $comment->comment_date . '</time><p>' . $comment->comment_content . '</p></li>';

			}

			echo '</ul>';

		}

		/**
		 * Save Pending Payment
		 * @since 1.7
		 * @version 1.0
		 */
		public function save_pending_payment( $post_id, $post ) {

			if ( ! current_user_can( $this->core->edit_creds_cap() ) || ! isset( $_POST['buycred_pending_payment'] ) ) return;

			$pending_payment = $_POST['buycred_pending_payment'];
			$changed         = false;

			foreach ( $pending_payment as $meta_key => $meta_value ) {

				$new_value = sanitize_text_field( $meta_value );
				$old_value = get_post_meta( $post_id, $meta_key, true );
				if ( $new_value != $old_value ) {
					update_post_meta( $post_id, $meta_key, $new_value );
					$changed = true;
				}

			}

			if ( $changed ) {
				$user = wp_get_current_user();
				$this->add_comment( $post_id, sprintf( __( 'Pending payment updated by %s', 'mycred' ), $user->user_login ) );
			}

		}

		/**
		 * Render Shortcode Pending
		 * @since 1.5
		 * @version 1.1
		 */
		public function render_shortcode_pending( $attr, $content = '' ) {

			// Must be logged in
			if ( ! is_user_logged_in() ) return $content;

			extract( shortcode_atts( array(
				'ctype'   => MYCRED_DEFAULT_TYPE_KEY,
				'pay_now' => __( 'Pay Now', 'mycred' ),
				'cancel'  => __( 'Cancel', 'mycred' )
			), $attr ) );

			$user_id = get_current_user_id();
			$pending = buycred_get_users_pending_payments( $user_id, $ctype );

			ob_start();

?>
<div id="pending-buycred-payments-<?php echo $ctype; ?>">
	<table class="table">
		<thead>
			<tr>
				<th class="column-transaction-id"><?php _e( 'Transaction ID', 'mycred' ); ?></th>
				<th class="column-gateway"><?php _e( 'Gateway', 'mycred' ); ?></th>
				<th class="column-amount"><?php _e( 'Amount', 'mycred' ); ?></th>
				<th class="column-cost"><?php _e( 'Cost', 'mycred' ); ?></th>
				<?php if ( $ctype == '' ) : ?><th class="column-ctype"><?php _e( 'Point Type', 'mycred' ); ?></th><?php endif; ?>
				<th class="column-actions"><?php _e( 'Actions', 'mycred' ); ?></th>
			</tr>
		</thead>
		<tbody>
<?php

			if ( ! empty( $pending ) ) {

				// Showing all point types
				if ( $ctype == '' ) {

					foreach ( $pending as $point_type => $entries ) {

						if ( empty( $entries ) ) continue;

						foreach ( $entries as $entry ) {

?>
			<tr>
				<td class="column-transaction-id"><?php echo esc_attr( $entry->public_id ); ?></td>
				<td class="column-gateway"><?php echo $this->adjust_column_content( 'gateway', $entry->payment_id ); ?></td>
				<td class="column-amount"><?php echo $this->adjust_column_content( 'amount', $entry->payment_id ); ?></td>
				<td class="column-cost"><?php echo $this->adjust_column_content( 'cost', $entry->payment_id ); ?></td>
				<td class="column-ctype"><?php echo mycred_get_point_type_name( $entry->point_type, false ); ?></td>
				<td class="column-actions">
					<a href="<?php echo esc_url( $entry->pay_now_url ); ?>"><?php echo $pay_now; ?></a> &bull; <a href="<?php echo esc_url( $entry->cancel_url ); ?>"><?php echo $cancel; ?></a>
				</td>
			</tr>
<?php
 
 						}
 
					}

				}

				// Showing a particular point type
				else {

					foreach ( $pending as $entry ) {

?>
			<tr>
				<td class="column-transaction-id"><?php echo esc_attr( $entry->public_id ); ?></td>
				<td class="column-gateway"><?php echo $this->adjust_column_content( 'gateway', $entry->payment_id ); ?></td>
				<td class="column-amount"><?php echo $this->adjust_column_content( 'amount', $entry->payment_id ); ?></td>
				<td class="column-cost"><?php echo $this->adjust_column_content( 'cost', $entry->payment_id ); ?></td>
				<td class="column-actions">
					<a href="<?php echo esc_url( $entry->pay_now_url ); ?>"><?php echo $pay_now; ?></a> &bull; <a href="<?php echo esc_url( $entry->cancel_url ); ?>"><?php echo $cancel; ?></a>
				</td>
			</tr>
<?php

					}

				}

			}
			else {

?>
			<tr>
				<td colspan="<?php if ( $ctype == '' ) echo '6'; else echo '5'; ?>"><?php _e( 'No pending payments found', 'mycred' ); ?></td>
			</tr>
<?php

			}

?>
		</tbody>
	</table>
</div>
<?php

			$output = ob_get_contents();
			ob_end_clean();

			return $output;

		}

	}
endif;

/**
 * Load buyCRED Pending Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_buycred_pending_addon' ) ) :
	function mycred_load_buycred_pending_addon( $modules, $point_types ) {

		$modules['solo']['buycred-pending'] = new buyCRED_Pending_Payments();
		$modules['solo']['buycred-pending']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_buycred_pending_addon', 40, 2 );
