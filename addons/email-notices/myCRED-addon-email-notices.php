<?php
/**
 * Addon: Email Notices
 * Addon URI: http://mycred.me/add-ons/email-notices/
 * Version: 1.3.1
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_EMAIL',         __FILE__ );
define( 'myCRED_EMAIL_DIR',     myCRED_ADDONS_DIR . 'email-notices/' );
define( 'myCRED_EMAIL_VERSION', '1.3.1' );

/**
 * myCRED_Email_Notice_Module class
 * @since 1.1
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Email_Notice_Module' ) ) :
	class myCRED_Email_Notice_Module extends myCRED_Module {

		public $instances = array();

		/**
		 * Construct
		 */
		function __construct() {

			parent::__construct( 'myCRED_Email_Notice_Module', array(
				'module_name' => 'emailnotices',
				'defaults'    => array(
					'from'        => array(
						'name'        => get_bloginfo( 'name' ),
						'email'       => get_bloginfo( 'admin_email' ),
						'reply_to'    => get_bloginfo( 'admin_email' )
					),
					'filter'      => array(
						'subject'     => 0,
						'content'     => 0
					),
					'use_html'    => true,
					'content'     => '',
					'styling'     => '',
					'send'        => '',
					'override'    => 0
				),
				'register'    => false,
				'add_to_core' => true,
				'menu_pos'    => 90
			) );

		}

		/**
		 * Hook into Init
		 * @since 1.1
		 * @version 1.2.2
		 */
		public function module_init() {

			$this->register_email_notices();
			$this->setup_instances();

			add_action( 'mycred_admin_enqueue',       array( $this, 'enqueue_scripts' ), $this->menu_pos );
			add_filter( 'mycred_add_finished',        array( $this, 'email_check' ), 80, 3 );
			add_action( 'mycred_badge_level_reached', array( $this, 'badge_check' ), 10, 3 );
			add_action( 'mycred_send_email_notices',  'mycred_email_notice_cron_job' );

			add_shortcode( 'mycred_email_subscriptions', array( $this, 'render_subscription_shortcode' ) );

			add_action( 'mycred_add_menu',           array( $this, 'add_to_menu' ), $this->menu_pos );

			// Schedule Cron
			if ( ! isset( $this->emailnotices['send'] ) ) return;

			if ( $this->emailnotices['send'] == 'hourly' && wp_next_scheduled( 'mycred_send_email_notices' ) === false )
				wp_schedule_event( time(), 'hourly', 'mycred_send_email_notices' );

			elseif ( $this->emailnotices['send'] == 'daily' && wp_next_scheduled( 'mycred_send_email_notices' ) === false )
				wp_schedule_event( time(), 'daily', 'mycred_send_email_notices' );

			elseif ( $this->emailnotices['send'] == '' && wp_next_scheduled( 'mycred_send_email_notices' ) !== false )
				wp_clear_scheduled_hook( 'mycred_send_email_notices' );

		}

		/**
		 * Hook into Admin Init
		 * @since 1.1
		 * @version 1.1
		 */
		public function module_admin_init() {

			add_filter( 'post_updated_messages',                          array( $this, 'post_updated_messages' ) );

			add_filter( 'manage_mycred_email_notice_posts_columns',       array( $this, 'adjust_column_headers' ), 50 );
			add_action( 'manage_mycred_email_notice_posts_custom_column', array( $this, 'adjust_column_content' ), 10, 2 );

			add_filter( 'parent_file',                                    array( $this, 'parent_file' ) );
			add_filter( 'submenu_file',                                   array( $this, 'subparent_file' ), 10, 2 );

			add_action( 'admin_head',                                     array( $this, 'admin_header' ) );

			add_filter( 'enter_title_here',                               array( $this, 'enter_title_here' ) );
			add_filter( 'post_row_actions',                               array( $this, 'adjust_row_actions' ), 10, 2 );

			add_filter( 'default_content',                                array( $this, 'default_content' ) );
			add_action( 'post_submitbox_start',                           array( $this, 'publish_warning' ) );

			add_action( 'save_post_mycred_email_notice',                  array( $this, 'save_email_notice' ), 10, 2 );

			if ( $this->emailnotices['use_html'] === false )
				add_filter( 'user_can_richedit', array( $this, 'disable_richedit' ) );

		}

		/**
		 * Register Email Notice Post Type
		 * @since 1.1
		 * @version 1.1
		 */
		protected function register_email_notices() {

			$labels = array(
				'name'               => __( 'Email Notifications', 'mycred' ),
				'singular_name'      => __( 'Email Notification', 'mycred' ),
				'add_new'            => __( 'Add New', 'mycred' ),
				'add_new_item'       => __( 'Add New', 'mycred' ),
				'edit_item'          => __( 'Edit Email Notification', 'mycred' ),
				'new_item'           => __( 'New Email Notification', 'mycred' ),
				'all_items'          => __( 'Email Notifications', 'mycred' ),
				'view_item'          => '',
				'search_items'       => __( 'Search Email Notifications', 'mycred' ),
				'not_found'          => __( 'No email notifications found', 'mycred' ),
				'not_found_in_trash' => __( 'No email notifications found in Trash', 'mycred' ), 
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Email Notifications', 'mycred' )
			);
			$args = array(
				'labels'               => $labels,
				'supports'             => array( 'title', 'editor' ),
				'hierarchical'         => true,
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

			register_post_type( 'mycred_email_notice', apply_filters( 'mycred_register_emailnotices', $args ) );

		}

		/**
		 * Adjust Post Updated Messages
		 * @since 1.1
		 * @version 1.1
		 */
		public function post_updated_messages( $messages ) {

			$messages['mycred_email_notice'] = array(
				0  => '',
				1  => __( 'Email Notice Updated.', 'mycred' ),
				2  => __( 'Email Notice Updated.', 'mycred' ),
				3  => __( 'Email Notice Updated.', 'mycred' ),
				4  => __( 'Email Notice Updated.', 'mycred' ),
				5  => false,
				6  => __( 'Email Notice Activated.', 'mycred' ),
				7  => __( 'Email Notice Updated.', 'mycred' ),
				8  => __( 'Email Notice Updated.', 'mycred' ),
				9  => __( 'Email Notice Updated.', 'mycred' ),
				10 => __( 'Email Notice Updated.', 'mycred' )
			);

			return $messages;

		}

		/**
		 * Add Admin Menu Item
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function add_to_menu() {

			add_submenu_page(
				MYCRED_SLUG,
				__( 'Email Notifications', 'mycred' ),
				__( 'Email Notifications', 'mycred' ),
				$this->core->edit_creds_cap(),
				'edit.php?post_type=mycred_email_notice'
			);

		}

		/**
		 * Parent File
		 * @since 1.7
		 * @version 1.0
		 */
		public function parent_file( $parent = '' ) {

			global $pagenow;

			if ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'mycred_email_notice' && isset( $_GET['action'] ) && $_GET['action'] == 'edit' )
				return 'mycred';

			if ( $pagenow == 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'mycred_email_notice' )
				return 'mycred';

			return $parent;

		}

		/**
		 * Sub Parent File
		 * @since 1.7
		 * @version 1.0
		 */
		public function subparent_file( $subparent = '', $parent = '' ) {

			global $pagenow;

			if ( ( $pagenow == 'edit.php' || $pagenow == 'post-new.php' ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'mycred_email_notice' ) {

				return 'edit.php?post_type=mycred_email_notice';
			
			}

			elseif ( $pagenow == 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'mycred_email_notice' ) {

				return 'edit.php?post_type=mycred_email_notice';

			}

			return $subparent;

		}

		/**
		 * Adjust Enter Title Here
		 * @since 1.1
		 * @version 1.0
		 */
		public function enter_title_here( $title ) {

			global $post_type;

			if ( $post_type == 'mycred_email_notice' )
				return __( 'Email Subject', 'mycred' );

			return $title;

		}

		/**
		 * Adjust Column Header
		 * @since 1.1
		 * @version 1.1
		 */
		public function adjust_column_headers( $defaults ) {

			$columns       = array();
			$columns['cb'] = $defaults['cb'];

			// Add / Adjust
			$columns['title']                  = __( 'Email Subject', 'mycred' );
			$columns['mycred-email-status']    = __( 'Status', 'mycred' );
			$columns['mycred-email-reference'] = __( 'Setup', 'mycred' );

			if ( count( $this->point_types ) > 1 )
				$columns['mycred-email-ctype'] = __( 'Point Type', 'mycred' );

			// Return
			return $columns;

		}

		/**
		 * Adjust Column Content
		 * @since 1.1
		 * @version 1.0
		 */
		public function adjust_column_content( $column_name, $post_id ) {

			// Get the post
			if ( in_array( $column_name, array( 'mycred-email-status', 'mycred-email-reference', 'mycred-email-ctype' ) ) )
				$post = get_post( $post_id );

			// Email Status Column
			if ( $column_name == 'mycred-email-status' ) {

				if ( $post->post_status != 'publish' && $post->post_status != 'future' )
					echo '<p>' . __( 'Not Active', 'mycred' ) . '</p>';

				elseif ( $post->post_status == 'future' )
					echo '<p>' . sprintf( __( 'Scheduled:<br /><strong>%1$s</strong>', 'mycred' ), date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $post->post_date ) ) ) . '</p>';

				else {
					$date = get_post_meta( $post_id, 'mycred_email_last_run', true );
					if ( empty( $date ) )
						echo '<p>' . __( 'Active', 'mycred' ) . '</p>';
					else
						echo '<p>' . sprintf( __( 'Active - Last run:<br /><strong>%1$s</strong>', 'mycred' ), date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $date ) ) . '</p>';
				}

			}

			// Email Setup Column
			elseif ( $column_name == 'mycred-email-reference' ) {

				echo '<p>';
				$instance_key = get_post_meta( $post->ID, 'mycred_email_instance', true );
				$label        = $this->get_instance( $instance_key );

				if ( ! empty( $instance_key ) && ! empty( $label ) )
					echo '<em>' . __( 'Email is sent when', 'mycred' ) .' ' . $label . '.</em></br />';
				else
					echo '<em>' . __( 'Missing instance for this notice!', 'mycred' ) . '</em><br />';

				$settings = get_post_meta( $post->ID, 'mycred_email_settings', true );
				if ( ! empty( $settings ) && isset( $settings['recipient'] ) )
					$recipient = $settings['recipient'];
				else
					$recipient = 'user';

				if ( $recipient == 'user' )
					echo '<strong>' . __( 'Sent To', 'mycred' ) . '</strong>: ' . __( 'User', 'mycred' ) . '</p>';
				elseif ( $recipient == 'admin' )
					echo '<strong>' . __( 'Sent To', 'mycred' ) . '</strong>: ' . __( 'Administrator', 'mycred' ) . '</p>';
				else
					echo '<strong>' . __( 'Sent To', 'mycred' ) . '</strong>: ' . __( 'Both Administrator and User', 'mycred' ) . '</p>';

			}

			// Email Setup Column
			elseif ( $column_name == 'mycred-email-ctype' ) {

				$type = get_post_meta( $post_id, 'mycred_email_ctype', true );
				if ( $type == '' ) $type = 'all';

				if ( $type == 'all' )
					echo __( 'All types', 'mycred' );

				elseif ( array_key_exists( $type, $this->point_types ) )
					echo $this->point_types[ $type ];

				else
					echo '-';

			}

		}

		/**
		 * Adjust Row Actions
		 * @since 1.1
		 * @version 1.0
		 */
		public function adjust_row_actions( $actions, $post ) {

			if ( $post->post_type == 'mycred_email_notice' ) {
				unset( $actions['inline hide-if-no-js'] );
				unset( $actions['view'] );
			}

			return $actions;

		}

		/**
		 * Add Meta Boxes
		 * @since 1.1
		 * @version 1.0
		 */
		public function add_metaboxes() {

			add_meta_box(
				'mycred-email-setup',
				__( 'Email Settings', 'mycred' ),
				array( $this, 'metabox_email_setup' ),
				'mycred_email_notice',
				'side',
				'high'
			);

			add_meta_box(
				'mycred-email-tags',
				__( 'Available Template Tags', 'mycred' ),
				array( $this, 'metabox_template_tags' ),
				'mycred_email_notice',
				'normal',
				'core'
			);

			if ( $this->emailnotices['use_html'] === false ) return;

			add_meta_box(
				'mycred-email-header',
				__( 'Email Header', 'mycred' ),
				array( $this, 'metabox_email_header' ),
				'mycred_email_notice',
				'normal',
				'high'
			);

		}

		/**
		 * Enqueue Scripts & Styles
		 * @since 1.1
		 * @version 1.1
		 */
		public function enqueue_scripts() {

			$screen = get_current_screen();
			// Commonly used
			if ( $screen->id == 'edit-mycred_email_notice' || $screen->id == 'mycred_email_notice' )
				wp_enqueue_style( 'mycred-admin' );

			// Edit Email Notice Styling
			if ( $screen->id == 'mycred_email_notice' ) {

				//wp_enqueue_style( 'mycred-email-edit-notice' );
				wp_enqueue_style( 'mycred-bootstrap-grid' );
				wp_enqueue_style( 'mycred-forms' );

				add_filter( 'postbox_classes_mycred_email_notice_mycred-email-setup',  array( $this, 'metabox_classes' ) );
				add_filter( 'postbox_classes_mycred_email_notice_mycred-email-tags',   array( $this, 'metabox_classes' ) );
				add_filter( 'postbox_classes_mycred_email_notice_mycred-email-header', array( $this, 'metabox_classes' ) );

			}

			// Email Notice List Styling
			elseif ( $screen->id == 'edit-mycred_email_notice' )
				wp_enqueue_style( 'mycred-email-notices' );

		}

		/**
		 * Disable WYSIWYG Editor
		 * @since 1.1
		 * @version 1.0.1
		 */
		public function disable_richedit( $default ) {

			global $post;

			if ( isset( $post->post_type ) && $post->post_type == 'mycred_email_notice' )
				return false;

			return $default;

		}

		/**
		 * Apply Default Content
		 * @since 1.1
		 * @version 1.0
		 */
		public function default_content( $content ) {

			global $post_type;

			if ( $post_type == 'mycred_email_notice' && !empty( $this->emailnotices['content'] ) )
				$content = $this->emailnotices['content'];

			return $content;

		}

		/**
		 * Add Publish Notice
		 * @since 1.1
		 * @version 1.0
		 */
		public function publish_warning() {

			global $post;

			if ( $post->post_type != 'mycred_email_notice' ) return;

			echo '<style type="text/css">#mycred-email-notice { margin-top: 0; } #visibility { display: none; }</style>';

			if ( $post->post_status != 'publish' && $post->post_status != 'future' )
				echo '<p id="mycred-email-notice"><span class="description">' . __( 'Once a notice is "published" it becomes active! Select "Save Draft" if you are not yet ready to use this email notice!', 'mycred' ) . '</span></p>';
			elseif ( $post->post_status == 'future' )
				echo '<p id="mycred-email-notice"><span class="description">' . sprintf( __( 'This notice will become active on:<br /><strong>%1$s</strong>', 'mycred' ), date( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $post->post_date ) ) ) . '</span></p>';
			else
				echo '<p id="mycred-email-notice"><span class="description">' . __( 'This email notice is active.', 'mycred' ) . '</span></p>';

		}

		/**
		 * Email Settings Metabox
		 * @since 1.1
		 * @version 1.1
		 */
		public function metabox_email_setup( $post ) {

			// Get instance
			$instance = get_post_meta( $post->ID, 'mycred_email_instance', true );

			// Get settings
			$settings = $this->get_email_settings( $post->ID );

			$set_type = get_post_meta( $post->ID, 'mycred_email_ctype', true );
			if ( $set_type == '' )
				$set_type = MYCRED_DEFAULT_TYPE_KEY;

?>
<div class="form">
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="mycred-email-instance"<?php if ( $post->post_status == 'publish' && empty( $instance ) ) echo ' style="color:red;font-weight:bold;"'; ?>><?php _e( 'Send this email notice when...', 'mycred' ); ?></label>
				<select name="mycred_email[instance]" id="mycred-email-instance" class="form-control">
<?php

			// Default
			echo '<option value=""';
			if ( empty( $instance ) ) echo ' selected="selected"';
			echo '>' . __( 'Select', 'mycred' ) . '</option>';

			// Loop though instances
			foreach ( $this->instances as $hook_ref => $values ) {

				if ( is_array( $values ) ) {
					foreach ( $values as $key => $value ) {

						// Make sure that the submitted value is unique
						$key_value = $hook_ref . '|' . $key;

						// Option group starts with 'label'
						if ( $key == 'label' )
							echo '<optgroup label="' . $value . '">';

						// Option group ends with 'end'
						elseif ( $key == 'end' )
							echo '</optgroup>';

						// The selectable options
						else {
							echo '<option value="' . $key_value . '"';
							if ( $instance == $key_value ) echo ' selected="selected"';
							echo '>... ' . $this->core->template_tags_general( $value ) . '</option>';
						}

					}

				}

			}

?>
				</select>
			</div>
			<div class="form-group">
				<label for="mycred-email-recipient-user"><?php _e( 'Recipient:', 'mycred' ); ?></label>
				<div class="inline-radio">
					<label for="mycred-email-recipient-user"><input type="radio" name="mycred_email[recipient]" id="mycred-email-recipient-user" value="user" <?php checked( $settings['recipient'], 'user' ); ?> /> <?php _e( 'User', 'mycred' ); ?></label>
				</div>
				<div class="inline-radio">
					<label for="mycred-email-recipient-admin"><input type="radio" name="mycred_email[recipient]" id="mycred-email-recipient-admin" value="admin" <?php checked( $settings['recipient'], 'admin' ); ?> /> <?php _e( 'Administrator', 'mycred' ); ?></label>
				</div>
				<div class="inline-radio">
					<label for="mycred-email-recipient-both"><input type="radio" name="mycred_email[recipient]" id="mycred-email-recipient-both" value="both" <?php checked( $settings['recipient'], 'both' ); ?> /> <?php _e( 'Both', 'mycred' ); ?></label>
				</div>
			</div>
			<div class="form-group">
				<label for="mycred-email-label"><?php _e( 'Label', 'mycred' ); ?></label>
				<input type="text" name="mycred_email[label]" id="mycred-email-label" class="form-control" value="<?php echo esc_attr( $settings['label'] ); ?>" />
			</div>

			<?php if ( count( $this->point_types ) > 1 ) : ?>

			<div class="form-group">
				<label for="mycred-email-ctype"><?php _e( 'Point Type', 'mycred' ); ?></label>
				<select name="mycred_email[ctype]" id="mycred-email-ctype" class="form-control">
<?php

			echo '<option value="all"';
			if ( $set_type == 'all' ) echo ' selected="selected"';
			echo '>' . __( 'All types', 'mycred' ) . '</option>';

			foreach ( $this->point_types as $type_id => $label ) {
				echo '<option value="' . $type_id . '"';
				if ( $set_type == $type_id ) echo ' selected="selected"';
				echo '>' . $label . '</option>';
			}

?>
				</select>
			</div>

			<?php else : ?>

			<input type="hidden" name="mycred_email[ctype]" id="mycred-email-ctype" value="<?php echo MYCRED_DEFAULT_TYPE_KEY; ?>" />

			<?php endif; ?>

			<div class="form-group">
				<label for="mycred-email-senders-name"><?php _e( 'Senders Name:', 'mycred' ); ?></label>
				<input type="text" name="mycred_email[senders_name]" id="mycred-email-senders-name" class="form-control" value="<?php echo esc_attr( $settings['senders_name'] ); ?>" />
			</div>
			<div class="form-group">
				<label for="mycred-email-senders-email"><?php _e( 'Senders Email:', 'mycred' ); ?></label>
				<input type="text" name="mycred_email[senders_email]" id="mycred-email-senders-email" class="form-control" value="<?php echo esc_attr( $settings['senders_email'] ); ?>" />
			</div>
			<div class="form-group">
				<label for="mycred-email-reply-to"><?php _e( 'Reply-To Email:', 'mycred' ); ?></label>
				<input type="text" name="mycred_email[reply_to]" id="mycred-email-reply-to" class="form-control" value="<?php echo esc_attr( $settings['reply_to'] ); ?>" />
			</div>
		</div>
	</div>

	<?php do_action( 'mycred_email_settings_box', $this ); ?>

</div>
<?php

		}

		/**
		 * Email Header Metabox
		 * @since 1.1
		 * @version 1.0
		 */
		public function metabox_email_header( $post ) {

?>
<div class="form">
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="mycred-email-styling"><?php _e( 'CSS Styling', 'mycred' ); ?></label>
				<textarea name="mycred_email[styling]" class="form-control code" rows="10" cols="30" id="mycred-email-styling"><?php echo $this->get_email_styling( $post->ID ); ?></textarea>
			</div>
		</div>
	</div>
</div>

<?php do_action( 'mycred_email_header_box', $this ); ?>

<?php

		}

		/**
		 * Template Tags Metabox
		 * @since 1.1
		 * @version 1.2
		 */
		public function metabox_template_tags( $post ) {

?>
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php _e( 'Site Related', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%blog_name%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'Your websites title', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%blog_url%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'Your websites address', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%blog_info%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'Your websites tagline (description)', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%admin_email%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'Your websites admin email', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%num_members%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'Total number of blog members', 'mycred' ); ?></div>
			</div>
		</div>
	</div>
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php _e( 'Instance Related', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%new_balance%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'The users new balance', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%old_balance%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'The users old balance', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%amount%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'The amount of points gained or lost in this instance', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<strong>%entry%</strong>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div><?php _e( 'The log entry', 'mycred' ); ?></div>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<div><?php printf( __( 'You can also use %s.', 'mycred' ), '<a href="http://codex.mycred.me/category/template-tags/temp-user/" target="_blank">' . __( 'user related template tags', 'mycred' ) . '</a>' ); ?></div>
			</div>
		</div>
	</div>
</div>
<?php

		}

		/**
		 * Save Email Notice Details
		 * @since 1.1
		 * @version 1.2
		 */
		public function save_email_notice( $post_id, $post = NULL ) {

			if ( $post === NULL || ! current_user_can( $this->core->edit_creds_cap() ) || ! isset( $_POST['mycred_email'] ) ) return $post_id;

			// Update Instance
			if ( ! empty( $_POST['mycred_email']['instance'] ) ) {

				$instance_key = sanitize_text_field( $_POST['mycred_email']['instance'] );
				$keys         = explode( '|', $instance_key );
				if ( ! empty( $keys ) );
					update_post_meta( $post_id, 'mycred_email_instance', $instance_key );

			}

			// Construct new settings
			$settings =        array();

			if ( ! empty( $_POST['mycred_email']['recipient'] ) )
				$settings['recipient']     = sanitize_text_field( $_POST['mycred_email']['recipient'] );
			else
				$settings['recipient']     = 'user';

			if ( ! empty( $_POST['mycred_email']['senders_name'] ) )
				$settings['senders_name']  = sanitize_text_field( $_POST['mycred_email']['senders_name'] );
			else
				$settings['senders_name']  = $this->emailnotices['from']['name'];

			if ( ! empty( $_POST['mycred_email']['senders_email'] ) )
				$settings['senders_email'] = sanitize_text_field( $_POST['mycred_email']['senders_email'] );
			else
				$settings['senders_email'] = $this->emailnotices['from']['email'];

			if ( ! empty( $_POST['mycred_email']['reply_to'] ) )
				$settings['reply_to']      = sanitize_text_field( $_POST['mycred_email']['reply_to'] );
			else
				$settings['reply_to']      = $this->emailnotices['from']['reply_to'];

			$settings['label'] = sanitize_text_field( $_POST['mycred_email']['label'] );

			// Save settings
			update_post_meta( $post_id, 'mycred_email_settings', $settings );

			$point_type = sanitize_key( $_POST['mycred_email']['ctype'] );
			if ( mycred_point_type_exists( $point_type ) )
				update_post_meta( $post_id, 'mycred_email_ctype', $point_type );

			// If rich editing is disabled bail now
			if ( $this->emailnotices['use_html'] === false ) return;

			// Save styling
			if ( ! empty( $_POST['mycred_email']['styling'] ) )
				update_post_meta( $post_id, 'mycred_email_styling', wp_kses_post( $_POST['mycred_email']['styling'] ) );

		}

		/**
		 * Admin Header
		 * @since 1.1
		 * @version 1.0
		 */
		public function admin_header() {

			$screen = get_current_screen();
			if ( $screen->id == 'mycred_email_notice' && $this->emailnotices['use_html'] === false ) {
				remove_action( 'media_buttons', 'media_buttons' );
				echo '<style type="text/css">#ed_toolbar { display: none !important; }</style>';
			}

		}

		/**
		 * Register Scripts & Styles
		 * @since 1.7
		 * @version 1.0
		 */
		public function scripts_and_styles() {

			// Register Email List Styling
			wp_register_style(
				'mycred-email-notices',
				plugins_url( 'assets/css/email-notice.css', myCRED_EMAIL ),
				false,
				myCRED_EMAIL_VERSION . '.1',
				'all'
			);

			// Register Edit Email Notice Styling
			wp_register_style(
				'mycred-email-edit-notice',
				plugins_url( 'assets/css/edit-email-notice.css', myCRED_EMAIL ),
				false,
				myCRED_EMAIL_VERSION . '.1',
				'all'
			);

		}

		/**
		 * Setup Instances
		 * @since 1.1
		 * @version 1.1
		 */
		public function setup_instances() {

			$instances['']        = __( 'Select', 'mycred' );
			$instances['general'] = array(
				'label'    => __( 'General', 'mycred' ),
				'all'      => __( 'users balance changes', 'mycred' ),
				'positive' => __( 'user gains %_plural%', 'mycred' ),
				'negative' => __( 'user lose %_plural%', 'mycred' ),
				'zero'     => __( 'users balance reaches zero', 'mycred' ),
				'minus'    => __( 'users balance goes minus', 'mycred' ),
				'end'      => ''
			);

			if ( class_exists( 'myCRED_Badge_Module' ) ) {
				$instances['badges'] = array(
					'label'    => __( 'Badge Add-on', 'mycred' ),
					'positive' => __( 'user gains a badge', 'mycred' ),
					'end'      => ''
				);
			}

			if ( class_exists( 'myCRED_Sell_Content_Module' ) ) {
				$instances['buy_content'] = array(
					'label'    => __( 'Sell Content Add-on', 'mycred' ),
					'negative' => __( 'user buys content', 'mycred' ),
					'positive' => __( 'authors content gets sold', 'mycred' ),
					'end'      => ''
				);
			}

			if ( class_exists( 'myCRED_buyCRED_Module' ) ) {
				$instances['buy_creds'] = array(
					'label'    => __( 'buyCREDs Add-on', 'mycred' ),
					'positive' => __( 'user buys %_plural%', 'mycred' ),
					'end'      => ''
				);
			}

			if ( class_exists( 'myCRED_Transfer_Module' ) ) {
				$instances['transfer'] = array(
					'label'    => __( 'Transfer Add-on', 'mycred' ),
					'negative' => __( 'user sends %_plural%', 'mycred' ),
					'positive' => __( 'user receives %_plural%', 'mycred' ),
					'end'      => ''
				);
			}

			if ( class_exists( 'myCRED_Ranks_Module' ) ) {
				$instances['ranks'] = array(
					'label'    => __( 'Ranks Add-on', 'mycred' ),
					'negative' => __( 'user is demoted', 'mycred' ),
					'positive' => __( 'user is promoted', 'mycred' ),
					'end'      => ''
				);
			}

			$this->instances = apply_filters( 'mycred_email_instances', $instances );

		}

		/**
		 * Get Instance
		 * @since 1.1
		 * @version 1.0
		 */
		public function get_instance( $key = '', $detail = NULL ) {

			$instance_keys = explode( '|', $key );
			if ( $instance_keys === false || empty( $instance_keys ) || count( $instance_keys ) != 2 ) return NULL;

			// By default we return the entire array for the given key
			if ( $detail === NULL && array_key_exists( $instance_keys[0], $this->instances ) )
				return $this->core->template_tags_general( $this->instances[ $instance_keys[0] ][ $instance_keys[1] ] );

			if ( $detail !== NULL && array_key_exists( $detail, $this->instances[ $instance_keys[0] ] ) )
				return $this->core->template_tags_general( $this->instances[ $instance_keys[0] ][ $detail ] );

			return NULL;

		}

		/**
		 * Email Notice Check
		 * @since 1.4.6
		 * @version 1.2.1
		 */
		public function get_events_from_instance( $request, $mycred ) {

			extract( $request );

			$events = array( 'general|all' );

			// Events based on amount being given or taken
			if ( $amount < $mycred->zero() )
				$events[] = 'general|negative';
			else
				$events[] = 'general|positive';

			// Events based on this transaction leading to the users balance
			// reaching or surpassing zero
			$users_current_balance = $mycred->get_users_balance( $user_id, $type );
			if ( ( $users_current_balance - $amount ) < $mycred->zero() )
				$events[] = 'general|minus';
			elseif ( ( $users_current_balance - $amount ) == $mycred->zero() )
				$events[] = 'general|zero';

			// Ranks Related
			if ( function_exists( 'mycred_get_users_rank' ) ) {

				$rank = mycred_find_users_rank( $user_id, $type, false );

				if ( isset( $rank->rank_id ) && $rank->rank_id !== $rank->current_id ) {

					if ( $rank->current_id !== NULL && get_post_meta( $rank->current_id, 'mycred_rank_max', true ) > $rank->maximum )
						$events[] = 'ranks|negative';

					else
						$events[] = 'ranks|positive';

				}

			}

			// Let others play
			return apply_filters( 'mycred_get_email_events', $events, $request, $mycred );

		}

		/**
		 * Email Notice Check
		 * @since 1.1
		 * @version 1.5
		 */
		public function email_check( $ran, $request, $mycred ) {

			// Exit now if $ran is false or new settings is not yet saved.
			if ( $ran === false || ! isset( $this->emailnotices['send'] ) ) return $ran;

			$user_id = absint( $request['user_id'] );

			// Construct events
			$events = $this->get_events_from_instance( $request, $mycred );

			// Do not send emails now
			if ( $this->emailnotices['send'] != '' ) {

				// Save for cron job
				mycred_add_user_meta( $user_id, 'mycred_scheduled_email_notices', '', array(
					'events'  => $events,
					'request' => $request
				) );

			}

			// Send emails now
			else {

				$this->do_email_notices( $events, $request );

			}

			return $ran;

		}

		/**
		 * Badge Check
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function badge_check( $user_id, $badge_id, $level_reached ) {

			if ( $level_reached === false ) return;

			$events  = array( 'badges|positive' );
			$request = array(
				'ref'     => 'badge',
				'user_id' => $user_id,
				'amount'  => 0,
				'entry'   => 'New Badge',
				'type'    => MYCRED_DEFAULT_TYPE_KEY,
				'time'    => current_time( 'timestamp' ),
				'ref_id'  => $badge_id,
				'data'    => array( 'ref_type' => 'post' )
			);

			// Do not send emails now
			if ( $this->emailnotices['send'] != '' ) {

				// Save for cron job
				mycred_add_user_meta( $user_id, 'mycred_scheduled_email_notices', '', array(
					'events'  => $events,
					'request' => $request
				) );

			}

			// Send emails now
			else {

				$this->do_email_notices( $events, $request );

			}

		}

		/**
		 * Do Email Notices
		 * @since 1.1
		 * @version 1.2
		 */
		public function do_email_notices( $events = array(), $request = array() ) {

			if ( ! isset( $request['user_id'] ) || empty( $events ) ) return;

			extract( $request );

			// Get all notices that a user has unsubscribed to
			$unsubscriptions = (array) mycred_get_user_meta( $user_id, 'mycred_email_unsubscriptions', '', true );

			global $wpdb;

			// Loop though events
			foreach ( $events as $event ) {

				// Get the email notice post object
				$notice = $wpdb->get_row( $wpdb->prepare( "
					SELECT * 
					FROM {$wpdb->posts} notices

					LEFT JOIN {$wpdb->postmeta} instances 
						ON ( notices.ID = instances.post_id AND instances.meta_key = 'mycred_email_instance' )

					LEFT JOIN {$wpdb->postmeta} pointtype 
						ON ( notices.ID = pointtype.post_id AND pointtype.meta_key = 'mycred_email_ctype' )

					WHERE instances.meta_value = %s 
						AND pointtype.meta_value IN (%s,'all') 
						AND notices.post_type = 'mycred_email_notice' 
						AND notices.post_status = 'publish';", $event, $request['type'] ) );

				// Notice found
				if ( $notice !== NULL ) {

					// Ignore unsubscribed events
					if ( in_array( $notice->ID, $unsubscriptions ) ) continue;

					// Get notice setup
					$settings = $this->get_email_settings( $notice->ID );

					// Send to user
					if ( $settings['recipient'] == 'user' || $settings['recipient'] == 'both' ) {
						$user = get_user_by( 'id', $user_id );
						$to = $user->user_email;
						unset( $user );
					}
					
					elseif ( $settings['recipient'] == 'admin' ) {
						$to = get_option( 'admin_email' );
					}

					// Filtered Subject
					if ( $this->emailnotices['filter']['subject'] === true )
						$subject = get_the_title( $notice->ID );

					// Unfiltered Subject
					else $subject = $notice->post_title;

					// Filtered Content
					if ( $this->emailnotices['filter']['content'] === true )
						$message = apply_filters( 'the_content', $notice->post_content );

					// Unfiltered Content
					else $message = $notice->post_content;

					$headers     = array();
					$attachments = '';

					if ( ! $this->emailnotices['override'] ) {

						// Construct headers
						if ( $this->emailnotices['use_html'] === true ) {
							$headers[] = 'MIME-Version: 1.0';
							$headers[] = 'Content-Type: text/HTML; charset="' . get_option( 'blog_charset' ) . '"';
						}
						$headers[] = 'From: ' . $settings['senders_name'] . ' <' . $settings['senders_email'] . '>';

						// Reply-To
						if ( $settings['reply_to'] != '' )
							$headers[] = 'Reply-To: ' . $settings['reply_to'];

						// Both means we blank carbon copy the admin so the user does not see email
						if ( $settings['recipient'] == 'both' )
							$headers[] = 'Bcc: ' . get_option( 'admin_email' );

						// If email was successfully sent we update 'last_run'
						if ( $this->wp_mail( $to, $subject, $message, $headers, $attachments, $request, $notice->ID ) === true )
							update_post_meta( $notice->ID, 'mycred_email_last_run', time() );

					}
					else {

						// If email was successfully sent we update 'last_run'
						if ( $this->wp_mail( $to, $subject, $message, $headers, $attachments, $request, $notice->ID ) === true ) {
							update_post_meta( $notice->ID, 'mycred_email_last_run', time() );

							if ( $settings['recipient'] == 'both' )
								$this->wp_mail( get_option( 'admin_email' ), $subject, $message, $headers, $attachments, $request, $notice->ID );
						}

					}

				}

			}

		}

		/**
		 * WP Mail
		 * @since 1.1
		 * @version 1.3.4
		 */
		public function wp_mail( $to, $subject, $message, $headers, $attachments, $request, $email_id ) {

			// Let others play before we do our thing
			$filtered = apply_filters( 'mycred_email_before_send', compact( 'to', 'subject', 'message', 'headers', 'attachments', 'request', 'email_id' ) );

			if ( ! isset( $filtered['request'] ) || ! is_array( $filtered['request'] ) ) return false;

			$subject = $this->template_tags_request( $filtered['subject'], $filtered['request'] );
			$message = $this->template_tags_request( $filtered['message'], $filtered['request'] );

			$entry   = $this->request_to_entry( $filtered['request'] );
			$mycred  = mycred( $filtered['request']['type'] );

			$subject = $mycred->template_tags_user( $subject, $filtered['request']['user_id'] );
			$message = $mycred->template_tags_user( $message, $filtered['request']['user_id'] );

			$subject = $mycred->template_tags_amount( $subject, $filtered['request']['amount'] );
			$message = $mycred->template_tags_amount( $message, $filtered['request']['amount'] );
			
			$subject = $mycred->parse_template_tags( $subject, $entry );
			$message = $mycred->parse_template_tags( $message, $entry );

			if ( $this->emailnotices['use_html'] === true )
				$message = wpautop( $message );

			$message = wptexturize( $message );

			$subject = apply_filters( 'mycred_email_subject_body', $subject, $filtered, $this );
			$message = apply_filters( 'mycred_email_message_body', $message, $filtered, $this );

			// Construct HTML Content
			if ( $this->emailnotices['use_html'] === true ) {
				$styling = $this->get_email_styling( $email_id );
				$message = '<html><head><title>' . $subject . '</title><style type="text/css" media="all"> ' . trim( $styling ) . '</style></head><body>' . $message . '</body></html>';
			}

			$body    = apply_filters( 'mycred_email_content_body', $message, $filtered, $this );

			// Send Email
			add_filter( 'wp_mail_content_type', array( $this, 'get_email_format' ) );
			$result  = wp_mail( $filtered['to'], $subject, $body, $filtered['headers'], $filtered['attachments'] );
			remove_filter( 'wp_mail_content_type', array( $this, 'get_email_format' ) );

			// Let others play
			do_action( 'mycred_email_sent', $filtered );

			return $result;

		}

		/**
		 * Get Email Format
		 * @since 1.1
		 * @version 1.0
		 */
		public function get_email_format() {

			if ( $this->emailnotices['use_html'] === false )
				return 'text/plain';

			return 'text/html';

		}

		/**
		 * Request Related Template Tags
		 * @since 1.1
		 * @version 1.3.2
		 */
		public function template_tags_request( $content, $request ) {

			$type = $this->core;
			if ( $request['type'] != MYCRED_DEFAULT_TYPE_KEY )
				$type = mycred( $request['type'] );

			$user_id     = absint( $request['user_id'] );
			$new_balance = $type->get_users_balance( $user_id, $request['type'] );

			if ( $request['amount'] > 0 )
				$old_balance = $type->number( $new_balance - $request['amount'] );
			else
				$old_balance = $type->number( $new_balance + $request['amount'] );

			$content = str_replace( '%old_balance%',   $old_balance, $content );
			$content = str_replace( '%old_balance_f%', $type->format_creds( $old_balance ), $content );

			$content = str_replace( '%new_balance%',   $new_balance, $content );
			$content = str_replace( '%new_balance_f%', $type->format_creds( $new_balance ), $content );

			$content = str_replace( '%amount%', $request['amount'], $content );
			$content = str_replace( '%entry%',  $request['entry'], $content );
			$content = str_replace( '%data%',   maybe_serialize( $request['data'] ), $content );

			$content = str_replace( '%blog_name%',   get_option( 'blogname' ), $content );
			$content = str_replace( '%blog_url%',    get_option( 'home' ), $content );
			$content = str_replace( '%blog_info%',   get_option( 'blogdescription' ), $content );
			$content = str_replace( '%admin_email%', get_option( 'admin_email' ), $content );
			$content = str_replace( '%num_members%', $this->core->count_members(), $content );

			return $content;

		}

		/**
		 * Get Email Settings
		 * @since 1.1
		 * @version 1.1
		 */
		public function get_email_settings( $post_id ) {

			$settings = get_post_meta( $post_id, 'mycred_email_settings', true );
			if ( $settings == '' )
				$settings = array();

			// Defaults
			$default = array(
				'recipient'     => 'user',
				'senders_name'  => $this->emailnotices['from']['name'],
				'senders_email' => $this->emailnotices['from']['email'],
				'reply_to'      => $this->emailnotices['from']['reply_to'],
				'label'         => ''
			);

			$settings = mycred_apply_defaults( $default, $settings );

			return apply_filters( 'mycred_email_notice_settings', $settings, $post_id );

		}

		/**
		 * Get Email Styling
		 * @since 1.1
		 * @version 1.0
		 */
		public function get_email_styling( $post_id ) {

			if ( $this->emailnotices['use_html'] === false ) return '';
			$style = get_post_meta( $post_id, 'mycred_email_styling', true );

			// Defaults
			if ( empty( $style ) )
				return $this->emailnotices['styling'];

			return $style;

		}

		/**
		 * Add to General Settings
		 * @since 1.1
		 * @version 1.1
		 */
		public function after_general_settings( $mycred = NULL ) {

			$this->emailnotices = mycred_apply_defaults( $this->default_prefs, $this->emailnotices );

?>
<h4><span class="dashicons dashicons-admin-plugins static"></span><?php _e( 'Email Notices', 'mycred' ); ?></h4>
<div class="body" style="display:none;">
	<p><?php _e( 'Settings that apply to all email notices and can not be overridden for individual emails.', 'mycred' ); ?></p>
	<label class="subheader" for="<?php echo $this->field_id( array( 'use_html' => 'no' ) ); ?>"><?php _e( 'Email Format', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-use-html">
		<li>
			<label for="<?php echo $this->field_id( array( 'use_html' => 'no' ) ); ?>"><input type="radio" name="<?php echo $this->field_name( 'use_html' ); ?>" id="<?php echo $this->field_id( array( 'use_html' => 'no' ) ); ?>" <?php checked( $this->emailnotices['use_html'], 0 ); ?> value="0" /> <?php _e( 'Plain text emails only.', 'mycred' ); ?></label>
		</li>
		<li>
			<label for="<?php echo $this->field_id( array( 'use_html' => 'yes' ) ); ?>"><input type="radio" name="<?php echo $this->field_name( 'use_html' ); ?>" id="<?php echo $this->field_id( array( 'use_html' => 'yes' ) ); ?>" <?php checked( $this->emailnotices['use_html'], 1 ); ?> value="1" /> <?php _e( 'HTML or Plain text emails.', 'mycred' ); ?></label>
		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( array( 'filter' => 'subject' ) ); ?>"><?php _e( 'Filters', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-allow-filters">
		<li>
			<input type="checkbox" name="<?php echo $this->field_name( array( 'filter' => 'subject' ) ); ?>" id="<?php echo $this->field_id( array( 'filter' => 'subject' ) ); ?>" <?php checked( $this->emailnotices['filter']['subject'], 1 ); ?> value="1" />
			<label for="<?php echo $this->field_id( array( 'filter' => 'subject' ) ); ?>"><?php _e( 'Allow WordPress and Third Party Plugins to filter the email subject before an email is sent.', 'mycred' ); ?></label>
		</li>
		<li>
			<input type="checkbox" name="<?php echo $this->field_name( array( 'filter' => 'content' ) ); ?>" id="<?php echo $this->field_id( array( 'filter' => 'content' ) ); ?>" <?php checked( $this->emailnotices['filter']['content'], 1 ); ?> value="1" />
			<label for="<?php echo $this->field_id( array( 'filter' => 'content' ) ); ?>"><?php _e( 'Allow WordPress and Third Party Plugins to filter the email content before an email is sent.', 'mycred' ); ?></label>
		</li>
	</ol>

	<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>

	<label class="subheader" for="<?php echo $this->field_id( 'send' ); ?>"><?php _e( 'Email Schedule', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-schedule">
		<li><?php _e( 'WordPress Cron is disabled. Emails will be sent immediately.', 'mycred' ); ?><input type="hidden" name="<?php echo $this->field_name( 'send' ); ?>" value="" /></li>
	</ol>

	<?php else : ?>

	<label class="subheader" for="<?php echo $this->field_id( 'send' ); ?>"><?php _e( 'Email Schedule', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-schedule">
		<li>
			<input type="radio" name="<?php echo $this->field_name( 'send' ); ?>" id="<?php echo $this->field_id( 'send' ); ?>-hourly" <?php checked( $this->emailnotices['send'], '' ); ?> value="" />
			<label for="<?php echo $this->field_id( 'send' ); ?>-hourly"><?php _e( 'Send emails immediately', 'mycred' ); ?></label>
		</li>
		<li>
			<input type="radio" name="<?php echo $this->field_name( 'send' ); ?>" id="<?php echo $this->field_id( 'send' ); ?>" <?php checked( $this->emailnotices['send'], 'hourly' ); ?> value="hourly" />
			<label for="<?php echo $this->field_id( 'send' ); ?>"><?php _e( 'Send emails once an hour', 'mycred' ); ?></label>
		</li>
		<li>
			<input type="radio" name="<?php echo $this->field_name( 'send' ); ?>" id="<?php echo $this->field_id( 'send' ); ?>-daily" <?php checked( $this->emailnotices['send'], 'daily' ); ?> value="daily" />
			<label for="<?php echo $this->field_id( 'send' ); ?>-daily"><?php _e( 'Send emails once a day', 'mycred' ); ?></label>
		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( 'send' ); ?>"><?php _e( 'Subscriptions', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-schedule">
		<li><?php printf( __( 'Use the %s shortcode to allow users to subscribe / unsubscribe to email updates.', 'mycred' ), '<a href="http://codex.mycred.me/shortcodes/mycred_email_subscriptions/">mycred_email_subscriptions</a>' ); ?></p></li>
	</ol>

	<?php endif; ?>

	<label class="subheader" for="<?php echo $this->field_id( 'override' ); ?>"><?php _e( 'SMTP Override', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-override">
		<li>
			<input type="checkbox" name="<?php echo $this->field_name( 'override' ); ?>" id="<?php echo $this->field_id( 'override' ); ?>" <?php checked( $this->emailnotices['override'], 1 ); ?> value="1" />
			<label for="<?php echo $this->field_id( 'override' ); ?>"><?php _e( 'SMTP Debug. Enable if you are experiencing issues with wp_mail() or if you use a SMTP plugin for emails.', 'mycred' ); ?></label>
		</li>
	</ol>
	<p><?php _e( 'Default email settings. These settings can be individually overridden when editing emails.', 'mycred' ); ?></p>
	<label class="subheader"><?php _e( 'Email Settings', 'mycred' ); ?></label>
	<ol id="myCRED-email-default-sender">
		<li>
			<label for="<?php echo $this->field_id( array( 'from' => 'name' ) ); ?>"><?php _e( 'Senders Name:', 'mycred' ); ?></label>
			<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'from' => 'name' ) ); ?>" id="<?php echo $this->field_id( array( 'from' => 'name' ) ); ?>" value="<?php echo $this->emailnotices['from']['name']; ?>" class="long" /></div>
		</li>
		<li>
			<label for="<?php echo $this->field_id( array( 'from' => 'email' ) ); ?>"><?php _e( 'Senders Email:', 'mycred' ); ?></label>
			<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'from' => 'email' ) ); ?>" id="<?php echo $this->field_id( array( 'from' => 'email' ) ); ?>" value="<?php echo $this->emailnotices['from']['email']; ?>" class="long" /></div>
		</li>
		<li>
			<label for="<?php echo $this->field_id( array( 'from' => 'reply_to' ) ); ?>"><?php _e( 'Reply-To:', 'mycred' ); ?></label>
			<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'from' => 'reply_to' ) ); ?>" id="<?php echo $this->field_id( array( 'from' => 'reply_to' ) ); ?>" value="<?php echo $this->emailnotices['from']['reply_to']; ?>" class="long" /></div>
		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( 'content' ); ?>"><?php _e( 'Default Email Content', 'mycred' ); ?></label>
	<ol id="myCRED-email-notice-defaults">
		<li>
			<textarea rows="10" cols="50" name="<?php echo $this->field_name( 'content' ); ?>" id="<?php echo $this->field_id( 'content' ); ?>" class="large-text code"><?php echo esc_attr( $this->emailnotices['content'] ); ?></textarea>
			<span class="description"><?php _e( 'Default email content.', 'mycred' ); ?></span>
		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( 'styling' ); ?>"><?php _e( 'Default Email Styling', 'mycred' ); ?></label>
	<ol>
		<li>
			<textarea rows="10" cols="50" name="<?php echo $this->field_name( 'styling' ); ?>" id="<?php echo $this->field_id( 'styling' ); ?>" class="large-text code"><?php echo esc_attr( $this->emailnotices['styling'] ); ?></textarea>
			<span class="description"><?php _e( 'Ignored if HTML is not allowed in emails.', 'mycred' ); ?></span>
		</li>
	</ol>
</div>
<?php

		}

		/**
		 * Save Settings
		 * @since 1.1
		 * @version 1.1
		 */
		public function sanitize_extra_settings( $new_data, $data, $core ) {

			$new_data['emailnotices']['use_html']          = ( isset( $data['emailnotices']['use_html'] ) ) ? absint( $data['emailnotices']['use_html'] ) : 0;

			$new_data['emailnotices']['filter']['subject'] = ( isset( $data['emailnotices']['filter']['subject'] ) ) ? 1 : 0;
			$new_data['emailnotices']['filter']['content'] = ( isset( $data['emailnotices']['filter']['content'] ) ) ? 1 : 0;

			$new_data['emailnotices']['from']['name']      = sanitize_text_field( $data['emailnotices']['from']['name'] );
			$new_data['emailnotices']['from']['email']     = sanitize_text_field( $data['emailnotices']['from']['email'] );
			$new_data['emailnotices']['from']['reply_to']  = sanitize_text_field( $data['emailnotices']['from']['reply_to'] );

			$new_data['emailnotices']['content']           = sanitize_text_field( $data['emailnotices']['content'] );
			$new_data['emailnotices']['styling']           = sanitize_text_field( $data['emailnotices']['styling'] );

			$new_data['emailnotices']['send']              = sanitize_text_field( $data['emailnotices']['send'] );

			if ( ! isset( $data['emailnotices']['override'] ) )
				$data['emailnotices']['override'] = 0;

			$new_data['emailnotices']['override'] = ( $data['emailnotices']['override'] == 1 ) ? 1 : 0;

			return $new_data;

		}

		/**
		 * Subscription Shortcode
		 * @since 1.4.6
		 * @version 1.0
		 */
		public function render_subscription_shortcode( $attr, $content = NULL ) {

			extract( shortcode_atts( array(
				'success' => __( 'Settings Updated', 'mycred' )
			), $attr ) );

			if ( ! is_user_logged_in() ) return $content;

			$user_id = get_current_user_id();

			$unsubscriptions = mycred_get_user_meta( $user_id, 'mycred_email_unsubscriptions', '', true );
			if ( $unsubscriptions == '' )
				$unsubscriptions = array();

			// Save
			$saved = false;
			if ( isset( $_REQUEST['do'] ) && $_REQUEST['do'] == 'mycred-unsubscribe' && wp_verify_nonce( $_REQUEST['token'], 'update-mycred-email-subscriptions' ) ) {

				if ( isset( $_POST['mycred_email_unsubscribe'] ) && ! empty( $_POST['mycred_email_unsubscribe'] ) )
					$new_selection = $_POST['mycred_email_unsubscribe'];
				else
					$new_selection = array();

				mycred_update_user_meta( $user_id, 'mycred_email_unsubscriptions', '', $new_selection );
				$unsubscriptions = $new_selection;
				$saved = true;

			}

			global $wpdb;

			$email_notices = $wpdb->get_results( $wpdb->prepare( "
				SELECT * 
				FROM {$wpdb->posts} notices

				LEFT JOIN {$wpdb->postmeta} prefs 
					ON ( notices.ID = prefs.post_id AND prefs.meta_key = 'mycred_email_settings' )

				WHERE notices.post_type = 'mycred_email_notice' 
					AND notices.post_status = 'publish'
					AND ( prefs.meta_value LIKE %s OR prefs.meta_value LIKE %s );", '%s:9:"recipient";s:4:"user";%', '%s:9:"recipient";s:4:"both";%' ) );

			ob_start();
			
			if ( $saved )
				echo '<p class="updated-email-subscriptions">' . $success . '</p>';

			$url = add_query_arg( array( 'do' => 'mycred-unsubscribe', 'user' => get_current_user_id(), 'token' => wp_create_nonce( 'update-mycred-email-subscriptions' ) ) );

?>
<form action="<?php echo esc_url( $url ); ?>" id="mycred-email-subscriptions" method="post">
	<table class="table">
		<thead>
			<tr>
				<th class="check"><?php _e( 'Unsubscribe', 'mycred' ); ?></th>
				<th class="notice-title"><?php _e( 'Email Notice', 'mycred' ); ?></th>
			</tr>
		</thead>
		<tbody>

		<?php if ( ! empty( $email_notices ) ) : ?>
		
			<?php foreach ( $email_notices as $notice ) : $settings = $this->get_email_settings( $notice->ID ); ?>

			<?php if ( $settings['label'] == '' ) continue; ?>

			<tr>
				<td class="check"><input type="checkbox" name="mycred_email_unsubscribe[]"<?php if ( in_array( $notice->ID, $unsubscriptions ) ) echo ' checked="checked"'; ?> value="<?php echo $notice->ID; ?>" /></td>
				<td class="notice-title"><?php echo $settings['label']; ?></td>
			</tr>

			<?php endforeach; ?>
		
		<?php else : ?>

			<tr>
				<td colspan="2"><?php _e( 'There are no email notifications yet.', 'mycred' ); ?></td>
			</tr>

		<?php endif; ?>

		</tbody>
	</table>
	<input type="submit" class="btn btn-primary button button-primary pull-right" value="<?php _e( 'Save Changes', 'mycred' ); ?>" />
</form>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			return apply_filters( 'mycred_render_email_subscriptions', $content, $attr );

		}

	}

endif;

/**
 * Load Email Notice Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_email_notice_addon' ) ) :
	function mycred_load_email_notice_addon( $modules, $point_types ) {

		$modules['solo']['emails'] = new myCRED_Email_Notice_Module();
		$modules['solo']['emails']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_email_notice_addon', 60, 2 );

/**
 * myCRED Email Notifications Cron Job
 * @since 1.2
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_email_notice_cron_job' ) ) :
	function mycred_email_notice_cron_job() {

		if ( ! class_exists( 'myCRED_Email_Notice_Module' ) ) return;

		$email_notice = new myCRED_Email_Notice_Module();

		global $wpdb;

		$pending = $wpdb->get_results( "
			SELECT * 
			FROM {$wpdb->usermeta} 
			WHERE meta_key = 'mycred_scheduled_email_notices';" );

		if ( $pending ) {

			foreach ( $pending as $instance ) {

				$_instance = maybe_unserialize( $instance->meta_value );
				$email_notice->do_email_notices( $_instance['events'], $_instance['request'] );

				$wpdb->delete(
					$wpdb->usermeta,
					array( 'umeta_id' => $instance->umeta_id ),
					array( '%d' )
				);

			}

		}

	}
endif;

?>