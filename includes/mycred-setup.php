<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Setup class
 * Used when the plugin has been activated for the first time. Handles the setup
 * wizard along with temporary admin menus.
 * @since 0.1
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Setup' ) ) :
	class myCRED_Setup {

		public $status = false;
		public $core;

		/**
		 * Construct
		 */
		public function __construct() {

			$this->core = mycred();

		}

		/**
		 * Load Class
		 * @since 1.7
		 * @version 1.0
		 */
		public function load() {

			add_action( 'admin_notices',         array( $this, 'admin_notice' ) );
			add_action( 'admin_menu',            array( $this, 'setup_menu' ) );
			add_action('current_screen', array( $this, 'menu_setup' ));


			add_action( 'wp_ajax_mycred-setup',  array( $this, 'ajax_setup' ) );

			global $pagenow;

			if ( $pagenow == 'index.php' && isset( $_GET['page'] ) && $_GET['page'] == 'mycred-about' ) {
				remove_all_actions( 'admin_notices' );
			}
		}

		/**
		 * Setup Setup Nag
		 * @since 0.1
		 * @version 1.0.1
		 */
		public function admin_notice() {

			$screen = get_current_screen();
			if ( $screen->id == 'plugins_page_' . MYCRED_SLUG . '-setup&mycred_tour_guide=1' || ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) || ! mycred_is_admin() ) return;

			echo '<div class="info notice notice-info"><p>' . sprintf( __( '%s needs your attention.', 'mycred' ), mycred_label() ) . ' <a href="' . admin_url( 'plugins.php?page=' . MYCRED_SLUG . '-setup' ) . '">' . __( 'Run Setup', 'mycred' ) . '</a></p></div>';

		}

		/**
		 * Add Setup page under "Plugins"
		 * @since 0.1
		 * @version 1.0
		 */
		public function setup_menu() {

			$page = add_submenu_page(
				'plugins.php',
				__( 'myCred Setup', 'mycred' ),
				__( 'myCred Setup', 'mycred' ),
				'manage_options',
				MYCRED_SLUG . '-setup',
				array( $this, 'setup_page' )
			);

			add_action( 'admin_print_styles-' . $page, array( $this, 'settings_header' ) );

		}

		/**
		 * Setup Header
		 * @since 0.1
		 * @version 1.1
		 */
		public function settings_header() {

			wp_enqueue_style( 'mycred-admin' );
			wp_enqueue_style( 'mycred-bootstrap-grid' );
			wp_enqueue_style( 'mycred-forms' );
			
		}

		/**
		 * Setup Screen
		 * Outputs the setup page.
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function setup_page() {

			$whitelabel = mycred_label();

?>
<style type="text/css">
#myCRED-wrap p { font-size: 13px; line-height: 17px; }
#mycred-setup-completed, #mycred-setup-progress { padding-top: 48px; }
#mycred-setup-completed h1, #mycred-setup-progress h1 { font-size: 3em; line-height: 3.2em; }
pre { margin: 0 0 12px 0; padding: 10px; background-color: #dedede; }
</style>
<div class="wrap mycred-metabox" id="myCRED-wrap">
	<h1><?php printf( __( '%s Setup', 'mycred' ), $whitelabel ); ?></h1>
	<p><?php printf( __( 'Before you can begin using %s, you must setup your first point type. This includes what you want to call your points, how these points are presented and who has access to it.', 'mycred' ), $whitelabel ); ?></p>
	<form method="post" action="" class="form" id="mycred-setup-form">

		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<h1><?php _e( 'Your First Point Type', 'mycred' ); ?></h1>
			</div>
		</div>

		<div id="mycred-form-content">

			<?php $this->new_point_type(); ?>

			<?php do_action( 'mycred_setup_after_form' ); ?>

		</div>

		<div id="mycred-advanced-setup-options" style="display: none;">

			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
					<h1><?php _e( 'Advanced Settings', 'mycred' ); ?></h1>
				</div>
			</div>

			<div class="row">
				<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<h3><?php _e( 'Change Default Point Type Key', 'mycred' ); ?></h3>
					<pre>define( 'MYCRED_DEFAULT_TYPE_KEY', 'yourkey' );</pre>
					<p><span class="description"><?php _e( 'You can change the meta key used to store the default point type using the MYCRED_DEFAULT_TYPE_KEY constant. Copy the above code to your wp-config.php file to use.', 'mycred' ); ?></span></p>
					<p><span class="description"><?php _e( 'If you intend to change the default meta key, you should do so before continuing on in this setup!', 'mycred' ); ?></span></p>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<h3><?php _e( 'Whitelabel', 'mycred' ); ?></h3>
					<pre>define( 'MYCRED_DEFAULT_LABEL', 'SuperPoints' );</pre>
					<p><span class="description"><?php _e( 'You can re-label myCRED using the MYCRED_DEFAULT_LABEL constant. Copy the above code to your wp-config.php file to use.', 'mycred' ); ?></span></p>
				</div>
			</div>

		</div>

		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<p><input type="submit" id="mycred-tour" class="button button-primary button-large" value="<?php _e( 'Create Point Type', 'mycred' ); ?>" /><button type="button" id="toggle-advanced-options" class="button button-secondary pull-right" data-hide="<?php _e( 'Hide', 'mycred' ); ?>" data-show="<?php _e( 'Advanced', 'mycred' ); ?>"><?php _e( 'Advanced', 'mycred' ); ?></button></p>
			</div>
		</div>

	</form>
	<div id="mycred-setup-progress" style="display: none;">
		<h1 class="text-center"><?php _e( 'Processing ...', 'mycred' ); ?></h1>
	</div>
	<div id="mycred-setup-completed" style="display: none;">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<h1 class="text-center"><?php _e( 'Setup Complete!', 'mycred' ); ?></h1>
				<p class="text-center" style="font-weight: bold; color: green;"><?php _e( 'Congratulations! You are now ready to use myCRED. What\'s next?', 'mycred' ); ?></p>
			</div>
		</div>
		<div class="row">
			<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<h3><?php _e( 'Enabling Hooks', 'mycred' ); ?></h3>
				<p><span class="description"><?php _e( 'If you intend to give your users points for interacting with your website automatically, your next step should be to enable and setup the hooks you want to use.', 'mycred' ); ?></span></p>
				<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-hooks' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary"><?php _e( 'Setup Hooks', 'mycred' ); ?></a></p>
			</div>
			<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<h3><?php _e( 'Add-ons', 'mycred' ); ?></h3>
				<p><span class="description"><?php _e( 'If you want to use advanced features such as Transfers, Point Purchases etc. your next step should be to enable and setup your add-ons.', 'mycred' ); ?></span></p>
				<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-addons' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary"><?php _e( 'Setup Add-ons', 'mycred' ); ?></a></p>
			</div>
			<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<h3><?php _e( 'Adjust Settings', 'mycred' ); ?></h3>
				<p><span class="description"><?php _e( 'If you need to make further changes to your settings or add new point types, you can visit your default point type\'s settings.', 'mycred' ); ?></span></p>
				<p><a href="<?php echo esc_url( add_query_arg( array( 'page' => MYCRED_SLUG . '-settings' ), admin_url( 'admin.php' ) ) ); ?>" class="button button-secondary"><?php _e( 'View Settings', 'mycred' ); ?></a></p>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
jQuery(function($) {

	var mycred_tour_guide = {};

	$( '#toggle-advanced-options' ).click(function(){

		var hidelabel = $(this).data( 'hide' );
		var showlabel = $(this).data( 'show' );

		if ( ! $(this).hasClass( 'open' ) ) {
			$( '#mycred-advanced-setup-options' ).slideDown();
			$(this).text( hidelabel ).addClass( 'open' );
		}
		else {
			$( '#mycred-advanced-setup-options' ).slideUp();
			$(this).text( showlabel ).removeClass( 'open' );
		}

	});

	$( '#myCRED-wrap' ).on( 'submit', 'form#mycred-setup-form', function(e){

		var progressbox  = $( '#mycred-setup-progress' );
		var completedbox = $( '#mycred-setup-completed' );
		var setupform    = $(this);

		e.preventDefault();

		$.ajax({
			type       : "POST",
			data       : {
				action   : 'mycred-setup',
				setup    : $(this).serialize(),
				token    : '<?php echo wp_create_nonce( 'mycred-run-setup' ); ?>'
			},
			dataType   : "JSON",
			url        : ajaxurl,
			beforeSend : function(){

				setupform.hide();
				progressbox.show();

				if ( $( '#toggle-advanced-options' ).hasClass( 'open' ) )
					$( '#toggle-advanced-options' ).click();
				

			},
			success    : function( response ) {

				console.log( response );

				if ( response.success === undefined )
					location.reload();

				else {

					progressbox.hide();

					if ( response.success ) {
						window.location.href = '<?php echo admin_url('admin.php?page=mycred&mycred_tour_guide=2'); ?>';
						//completedbox.slideDown();
						setupform.remove();
					}
					else {
						$( '#mycred-form-content' ).empty().append( response.data );
						setupform.slideDown();
					}

				}

			}
		});

	});

});
</script>

<?php

		}

		/**
		 * New Point Type Form
		 * @since 1.7
		 * @version 1.1
		 */
		protected function new_point_type( $posted = array() ) {

			$mycred = mycred();
			$posted = wp_parse_args( $posted, $mycred->defaults() );

?>

<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php _e( 'Labels', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-name-singular"><?php _e( 'Singular', 'mycred' ); ?></label>
					<input type="text" name="first_type[name][singular]" id="mycred-setup-name-singular" placeholder="<?php _e( 'Required', 'mycred' ); ?>" class="form-control" value="<?php echo esc_attr( $posted['name']['singular'] ); ?>" />
				</div>
			</div>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-name-plural"><?php _e( 'Plural', 'mycred' ); ?></label>
					<input type="text" name="first_type[name][plural]" id="mycred-setup-name-plural" placeholder="<?php _e( 'Required', 'mycred' ); ?>" class="form-control" value="<?php echo esc_attr( $posted['name']['plural'] ); ?>" />
				</div>
			</div>
		</div>
		<p><span class="description"><?php _e( 'These labels are used throughout the admin area and when presenting points to your users.', 'mycred' ); ?></span></p>
	</div>
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<h3><?php _e( 'Format', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-before"><?php _e( 'Prefix', 'mycred' ); ?></label>
					<input type="text" name="first_type[before]" id="mycred-setup-before" class="form-control" value="<?php echo esc_attr( $posted['before'] ); ?>" />
				</div>
			</div>
			<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-format-separators-thousand"><?php _e( 'Separators', 'mycred' ); ?></label>
					<div class="form-inline">
						<label>1</label> <input type="text" name="first_type[format][separators][thousand]" id="mycred-setup-format-separators-thousand" placeholder="," class="form-control" size="2" value="<?php echo esc_attr( $posted['format']['separators']['thousand'] ); ?>" /> <label>000</label> <input type="text" name="first_type[format][separators][decimal]" id="mycred-setup-format-separators-decimal" placeholder="." class="form-control" size="2" value="<?php echo esc_attr( $posted['format']['separators']['decimal'] ); ?>" /> <label>00</label>
					</div>
				</div>
			</div>
			<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for=""><?php _e( 'Decimals', 'mycred' ); ?></label>
					<input type="text" name="first_type[format][decimals]" id="mycred-setup-format-decimals" placeholder="0" class="form-control" value="<?php echo esc_attr( $posted['format']['decimals'] ); ?>" />
				</div>
			</div>
			<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for=""><?php _e( 'Suffix', 'mycred' ); ?></label>
					<input type="text" name="first_type[after]" id="mycred-setup-after" class="form-control" value="<?php echo esc_attr( $posted['after'] ); ?>" />
				</div>
			</div>
		</div>
		<p><span class="description"><?php _e( 'Set to decimals to zero if you prefer to use whole numbers.', 'mycred' ); ?></span></p>
	</div>
</div>

<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<h3><?php _e( 'Security', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-caps-creds"><?php _e( 'Point Editors', 'mycred' ); ?></label>
					<input type="text" name="first_type[caps][creds]" id="mycred-setup-caps-creds" placeholder="<?php _e( 'Required', 'mycred' ); ?>" class="form-control" value="<?php echo esc_attr( $posted['caps']['creds'] ); ?>" />
					<p><span class="description"><?php _e( 'The capability of users who can edit balances.', 'mycred' ); ?></span></p>
				</div>
			</div>
			<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-caps-plugin"><?php _e( 'Point Administrators', 'mycred' ); ?></label>
					<input type="text" name="first_type[caps][plugin]" id="mycred-setup-caps-plugin" placeholder="<?php _e( 'Required', 'mycred' ); ?>" class="form-control" value="<?php echo esc_attr( $posted['caps']['plugin'] ); ?>" />
					<p><span class="description"><?php _e( 'The capability of users who can edit settings.', 'mycred' ); ?></span></p>
				</div>
			</div>
			<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-max"><?php _e( 'Max. Amount', 'mycred' ); ?></label>
					<input type="text" name="first_type[max]" id="mycred-setup-max" class="form-control" value="<?php echo esc_attr( $posted['max'] ); ?>" />
					<p><span class="description"><?php _e( 'The maximum amount allowed to be paid out in a single instance.', 'mycred' ); ?></span></p>
				</div>
			</div>
			<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="mycred-setup-exclude-list"><?php _e( 'Exclude by User ID', 'mycred' ); ?></label>
					<input type="text" name="first_type[exclude][list]" id="mycred-setup-exclude-list" placeholder="<?php _e( 'Optional', 'mycred' ); ?>" class="form-control" value="<?php echo esc_attr( $posted['exclude']['list'] ); ?>" />
					<p><span class="description"><?php _e( 'Comma separated list of user IDs to exclude from using this point type.', 'mycred' ); ?></span></p>
				</div>
				<div class="form-group">
					<div class="checkbox">
						<label for="mycred-setup-exclude-cred-editors"><input type="checkbox" name="first_type[exclude][cred_editors]" id="mycred-setup-exclude-cred-editors"<?php checked( $posted['exclude']['cred_editors'], 1 ); ?> value="1" /> <?php _e( 'Exclude point editors', 'mycred' ); ?></label>
					</div>
					<div class="checkbox">
						<label for="mycred-setup-exclude-plugin-editors"><input type="checkbox" name="first_type[exclude][plugin_editors]" id="mycred-setup-exclude-plugin-editors"<?php checked( $posted['exclude']['plugin_editors'], 1 ); ?> value="1" /> <?php _e( 'Exclude point administrators', 'mycred' ); ?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php

		}

		/**
		 * Process Setup Steps
		 * @since 0.1
		 * @version 1.2
		 */
		public function ajax_setup() {

			// Security
			check_admin_referer( 'mycred-run-setup', 'token' );

			if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();

			parse_str( $_POST['setup'], $posted );

			$errors               = array();
			$defaults             = $this->core->defaults();
			$decimals             = 0;

			if ( ! array_key_exists( 'first_type', $posted ) ) {

				ob_start();

				echo '<div class="info notice notice-info"><p>' . __( 'Please make sure you fill out all required fields!', 'mycred' ) . '</a></p></div>';

				$this->new_point_type( $defaults );

				$output = ob_get_contents();
				ob_end_clean();

				wp_send_json_error( $output );

			}

			$setup                = mycred_apply_defaults( $defaults, $posted['first_type'] );
			$first_type           = $defaults;

			$singular_name        = sanitize_text_field( $setup['name']['singular'] );
			if ( empty( $singular_name ) )
				$errors[] = 'empty';

			elseif ( $singular_name != $first_type['name']['singular'] )
				$first_type['name']['singular'] = $singular_name;

			$plural_name          = sanitize_text_field( $setup['name']['plural'] );
			if ( empty( $plural_name ) )
				$errors[] = 'empty';

			elseif ( $plural_name != $first_type['name']['plural'] )
				$first_type['name']['plural'] = $plural_name;

			$first_type['before'] = sanitize_text_field( $setup['before'] );
			$first_type['after']  = sanitize_text_field( $setup['after'] );

			$point_editor_cap     = sanitize_key( $setup['caps']['creds'] );
			if ( empty( $point_editor_cap ) )
				$errors[] = 'empty';

			if ( $point_editor_cap != $first_type['caps']['creds'] )
				$first_type['caps']['creds'] = $point_editor_cap;

			$point_admin_cap      = sanitize_key( $setup['caps']['plugin'] );
			if ( empty( $point_admin_cap ) )
				$errors[] = 'empty';

			if ( $point_admin_cap != $first_type['caps']['plugin'] )
				$first_type['caps']['plugin'] = $point_admin_cap;

			if ( absint( $setup['format']['decimals'] ) > 0 ) {
				$first_type['format']['type']     = 'decimal';
				$first_type['format']['decimals'] = absint( $setup['format']['decimals'] );
				$decimals                         = $first_type['format']['decimals'];
			}

			$errors = apply_filters( 'mycred_setup_errors', $errors, $posted );

			// Something went wrong
			if ( ! empty( $errors ) ) {

				ob_start();

				echo '<div class="info notice notice-info"><p>' . __( 'Please make sure you fill out all required fields!', 'mycred' ) . '</a></p></div>';

				$this->new_point_type( $setup );

				$output = ob_get_contents();
				ob_end_clean();

				wp_send_json_error( apply_filters( 'mycred_setup_error_output', $output, $posted ) );

			}

			// Save our first point type
			mycred_update_option( 'mycred_pref_core', $first_type );

			// Install database
			if ( ! function_exists( 'mycred_install_log' ) )
				require_once myCRED_INCLUDES_DIR . 'mycred-functions.php';

			mycred_install_log( $decimals, true );

			mycred_add_option( 'mycred_setup_completed', time() );

			// Return the good news
			wp_send_json_success();

		}
		public function menu_setup( $current_screen ){
			
			if ( $current_screen->id == 'plugins_page_mycred-setup' && ! isset( $_GET['mycred_tour_guide'] ) ){
				wp_redirect( 
					add_query_arg( 
						array('page'=>'mycred-setup', 'mycred_tour_guide' => 1), 
						admin_url('plugins.php') 
					) 
				);
				exit;
			}
		}

	}
endif;
