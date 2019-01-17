<?php
/**
 * Plugin Name: myCRED
 * Plugin URI: http://mycred.me
 * Description: An adaptive points management system for WordPress powered websites.
 * Version: 1.7.5
 * Tags: points, tokens, credit, management, reward, charge, buddypress, bbpress, jetpack, woocommerce, marketpress, wp e-commerce, gravity forms, simplepress
 * Author: Gabriel S Merovingi
 * Author URI: http://www.merovingi.com
 * Author Email: support@mycred.me
 * Requires at least: WP 4.0
 * Tested up to: WP 4.7
 * Text Domain: mycred
 * Domain Path: /lang
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Forum URI: http://mycred.me/support/forums/
 */
if ( ! class_exists( 'myCRED_Core' ) ) :
	final class myCRED_Core {

		// Plugin Version
		public $version             = '1.7.5';

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		// Modules
		public $modules             = NULL;

		// Point Types
		public $point_types         = NULL;

		// Account Object
		public $account             = NULL;

		/**
		 * Setup Instance
		 * @since 1.7
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.7
		 * @version 1.0
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.7' ); }

		/**
		 * Not allowed
		 * @since 1.7
		 * @version 1.0
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.7' ); }

		/**
		 * Get
		 * @since 1.7
		 * @version 1.0
		 */
		public function __get( $key ) {
			if ( in_array( $key, array( 'point_types', 'modules', 'account' ) ) )
				return $this->$key();
		}

		/**
		 * Define
		 * @since 1.7
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) )
				define( $name, $value );
			elseif ( ! $definable && defined( $name ) )
				_doing_it_wrong( 'myCRED_Core->define()', 'Could not define: ' . $name . ' as it is already defined somewhere else!', '1.7' );
		}

		/**
		 * Require File
		 * @since 1.7
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
			else
				_doing_it_wrong( 'myCRED_Core->file()', 'Requested file ' . $required_file . ' not found.', '1.7' );
		}

		/**
		 * Construct
		 * @since 1.7
		 * @version 1.0
		 */
		public function __construct() {

			$this->define_constants();
			$this->includes();

			// Multisite Feature: If the site is blocked from using myCRED, exit now
			if ( mycred_is_site_blocked() ) return;

			// Register plugin hooks
			register_activation_hook(   myCRED_THIS, 'mycred_plugin_activation' );
			register_deactivation_hook( myCRED_THIS, 'mycred_plugin_deactivation' );
			register_uninstall_hook(    myCRED_THIS, 'mycred_plugin_uninstall' );

			// If myCRED is ready to be used
			if ( is_mycred_ready() ) {

				$this->internal();
				$this->wordpress();

				do_action( 'mycred_ready' );

			}

			// We need to run the setup
			else {

				// Load translation and register assets for the setup
				add_action( 'init', array( $this, 'load_plugin_textdomain' ), 10 );
				add_action( 'init', array( $this, 'register_assets' ), 20 );

				// Load the setup module
				$this->file( myCRED_INCLUDES_DIR . 'mycred-setup.php' );

				$setup = new myCRED_Setup();
				$setup->load();

			}

			// Plugin Related
			add_filter( 'plugin_action_links_mycred/mycred.php',      array( $this, 'plugin_links' ), 10, 4 );
			add_filter( 'plugin_row_meta',                            array( $this, 'plugin_description_links' ), 10, 2 );
			add_action( 'in_plugin_update_message-mycred/mycred.php', array( $this, 'plugin_update_warning' ) );

		}

		/**
		 * Define Constants
		 * First, we start with defining all requires constants if they are not defined already.
		 * @since 1.7
		 * @version 1.0
		 */
		private function define_constants() {

			// Ok to override
			$this->define( 'myCRED_VERSION',            $this->version );
			$this->define( 'MYCRED_SLUG',               'mycred' );
			$this->define( 'MYCRED_DEFAULT_LABEL',      'myCRED' );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY',   'mycred_default' );
			$this->define( 'MYCRED_FOR_OLDER_WP',       false );
			$this->define( 'MYCRED_MIN_TIME_LIMIT',     3 );
			$this->define( 'MYCRED_ENABLE_LOGGING',     true );
			$this->define( 'MYCRED_UNINSTALL_LOG',      true );
			$this->define( 'MYCRED_UNINSTALL_CREDS',    true );
			$this->define( 'MYCRED_DISABLE_PROTECTION', false );

			// Not ok to override
			$this->define( 'myCRED_THIS',               __FILE__, false );
			$this->define( 'myCRED_ROOT_DIR',           plugin_dir_path( myCRED_THIS ), false );
			$this->define( 'myCRED_ABSTRACTS_DIR',      myCRED_ROOT_DIR . 'abstracts/', false );
			$this->define( 'myCRED_ADDONS_DIR',         myCRED_ROOT_DIR . 'addons/', false );
			$this->define( 'myCRED_ASSETS_DIR',         myCRED_ROOT_DIR . 'assets/', false );
			$this->define( 'myCRED_INCLUDES_DIR',       myCRED_ROOT_DIR . 'includes/', false );
			$this->define( 'myCRED_LANG_DIR',           myCRED_ROOT_DIR . 'lang/', false );
			$this->define( 'myCRED_MODULES_DIR',        myCRED_ROOT_DIR . 'modules/', false );
			$this->define( 'myCRED_PLUGINS_DIR',        myCRED_ROOT_DIR . 'plugins/', false );
			$this->define( 'myCRED_CLASSES_DIR',        myCRED_INCLUDES_DIR . 'classes/', false );
			$this->define( 'myCRED_IMPORTERS_DIR',      myCRED_INCLUDES_DIR . 'importers/', false );
			$this->define( 'myCRED_SHORTCODES_DIR',     myCRED_INCLUDES_DIR . 'shortcodes/', false );

		}

		/**
		 * Include Plugin Files
		 * @since 1.7
		 * @version 1.0
		 */
		public function includes() {

			$this->file( myCRED_INCLUDES_DIR . 'mycred-functions.php' );

			$this->file( myCRED_CLASSES_DIR . 'class.query-log.php' );
			$this->file( myCRED_CLASSES_DIR . 'class.query-export.php' );

			$this->file( myCRED_ABSTRACTS_DIR . 'mycred-abstract-hook.php' );
			$this->file( myCRED_ABSTRACTS_DIR . 'mycred-abstract-module.php' );
			$this->file( myCRED_ABSTRACTS_DIR . 'mycred-abstract-object.php' );

			// Multisite Feature - Option to block usage of myCRED on a particular site
			if ( ! mycred_is_site_blocked() ) {

				// Core
				$this->file( myCRED_INCLUDES_DIR . 'mycred-object.php' );
				$this->file( myCRED_INCLUDES_DIR . 'mycred-remote.php' );
				$this->file( myCRED_INCLUDES_DIR . 'mycred-update.php' );
				$this->file( myCRED_INCLUDES_DIR . 'mycred-about.php' );
				$this->file( myCRED_INCLUDES_DIR . 'mycred-protect.php' );

				// If myCRED has been setup and is ready to begin
				if ( mycred_is_installed() ) {

					$this->file( myCRED_INCLUDES_DIR . 'mycred-widgets.php' );
					$this->file( myCRED_INCLUDES_DIR . 'mycred-referrals.php' );

					// Supported plugins
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-affiliatewp.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-badgeOS.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-bbPress.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-buddypress-media.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-buddypress.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-contact-form7.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-events-manager-light.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-gravityforms.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-invite-anyone.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-jetpack.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-sharethis.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-simplepress.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-woocommerce.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-wp-favorite-posts.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-wp-polls.php' );
					$this->file( myCRED_PLUGINS_DIR . 'mycred-hook-wp-postratings.php' );

					// Modules
					$this->file( myCRED_MODULES_DIR . 'mycred-module-addons.php' );
					$this->file( myCRED_MODULES_DIR . 'mycred-module-settings.php' );
					$this->file( myCRED_MODULES_DIR . 'mycred-module-hooks.php' );
					$this->file( myCRED_MODULES_DIR . 'mycred-module-log.php' );
					$this->file( myCRED_MODULES_DIR . 'mycred-module-export.php' );
					$this->file( myCRED_MODULES_DIR . 'mycred-module-management.php' );

					if ( is_multisite() ) {

						$this->file( myCRED_MODULES_DIR . 'mycred-module-network.php' );

					}

				}

			}

		}

		/**
		 * Internal Setup
		 * @since 1.7
		 * @version 1.0
		 */
		private function internal() {

			$this->point_types = mycred_get_types( true );
			$this->modules     = array(
				'solo' => array(),
				'type' => array()
			);

			$this->pre_init_globals();

		}

		/**
		 * WordPress
		 * Next we hook into WordPress
		 * @since 1.7
		 * @version 1.0
		 */
		public function wordpress() {

			add_action( 'plugins_loaded',    array( $this, 'after_plugin' ), 999 );
			add_action( 'after_setup_theme', array( $this, 'after_theme' ), 50 );
			add_action( 'after_setup_theme', array( $this, 'load_shortcodes' ), 60 );
			add_action( 'init',              array( $this, 'init' ), 5 );
			add_action( 'widgets_init',      array( $this, 'widgets_init' ), 50 );
			add_action( 'admin_init',        array( $this, 'admin_init' ), 50 );

			add_action( 'mycred_reset_key',  array( $this, 'cron_reset_key' ), 10 );

		}

		/**
		 * After Plugins Loaded
		 * Used to setup modules that are not replacable.
		 * @since 1.7
		 * @version 1.0
		 */
		public function after_plugin() {

			$this->modules['solo']['addons'] = new myCRED_Addons_Module();
			$this->modules['solo']['addons']->load();
			$this->modules['solo']['addons']->run_addons();

		}

		/**
		 * After Themes Loaded
		 * Used to load internal features via modules.
		 * @since 1.7
		 * @version 1.0
		 */
		public function after_theme() {

			global $mycred, $mycred_modules;

			// Lets start with Multisite
			if ( is_multisite() ) {

				// Normally the is_plugin_active_for_network() function is only available in the admin area
				if ( ! function_exists( 'is_plugin_active_for_network' ) )
					$this->file( ABSPATH . '/wp-admin/includes/plugin.php' );

				// The network "module" is only needed if the plugin is activated network wide
				if ( is_plugin_active_for_network( 'mycred/mycred.php' ) ) {
					$this->modules['solo']['network'] = new myCRED_Network_Module();
					$this->modules['solo']['network']->load();
				}

			}

			// The log module can not be loaded if logging is disabled
			if ( MYCRED_ENABLE_LOGGING ) {

				// Attach the log to each point type we use
				foreach ( $this->point_types as $type => $title ) {
					$this->modules['type'][ $type ]['log'] = new myCRED_Log_Module( $type );
					$this->modules['type'][ $type ]['log']->load();
				}

			}

			do_action( 'mycred_load_hooks' );

			// Attach hooks module to each point type we use
			foreach ( $this->point_types as $type => $title ) {
				$this->modules['type'][ $type ]['hooks'] = new myCRED_Hooks_Module( $type );
				$this->modules['type'][ $type ]['hooks']->load();
			}

			// Attach each module to each point type we use
			foreach ( $this->point_types as $type => $title ) {
				$this->modules['type'][ $type ]['settings'] = new myCRED_Settings_Module( $type );
				$this->modules['type'][ $type ]['settings']->load();
			}

			// Attach BuddyPress module to the main point type only
			if ( class_exists( 'BuddyPress' ) ) {

				$this->file( myCRED_MODULES_DIR . 'mycred-module-buddypress.php' );
				$this->modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['buddypress'] = new myCRED_BuddyPress_Module( MYCRED_DEFAULT_TYPE_KEY );
				$this->modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['buddypress']->load();

			}

			// Attach the Management module to the main point type
			$this->modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['management'] = new myCRED_Management_Module();
			$this->modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['management']->load();

			$mycred_modules = $this->modules['type'];

			// The export module can not be loaded if logging is disabled
			if ( MYCRED_ENABLE_LOGGING ) {

				// Load Export module
				$this->modules['solo']['exports'] = new myCRED_Export_Module();
				$this->modules['solo']['exports']->load();

			}

			// Let third-parties register and load custom myCRED modules
			$mycred_modules = apply_filters( 'mycred_load_modules', $this->modules, $this->point_types );

			// Let others play
			do_action( 'mycred_pre_init' );

		}

		/**
		 * Load Shortcodes
		 * @since 1.7
		 * @version 1.0
		 */
		public function load_shortcodes() {

			$this->file( myCRED_SHORTCODES_DIR . 'mycred_affiliate_id.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_affiliate_link.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_best_user.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_exchange.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_give.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_hide_if.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_history.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_hook_table.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_leaderboard_position.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_leaderboard.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_link.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_my_balance.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_send.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_show_if.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_total_balance.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_total_points.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_total_since.php' );
			$this->file( myCRED_SHORTCODES_DIR . 'mycred_video.php' );

			do_action( 'mycred_load_shortcode' );

		}

		/**
		 * Init
		 * General plugin setup during the init hook.
		 * @since 1.7
		 * @version 1.0
		 */
		public function init() {

			// Let others play
			do_action( 'mycred_init' );

			// Lets begin
			$this->post_init_globals();

			// Textdomain
			$this->load_plugin_textdomain();

			// Register Assets
			$this->register_assets();

			// Setup Cron
			$this->setup_cron_jobs();

			// Enqueue scripts & styles
			add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_front_before' ) );
			add_action( 'wp_footer',             array( $this, 'enqueue_front_after' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_before' ) );

			// Admin bar and toolbar adjustments
			add_action( 'admin_menu',            array( $this, 'adjust_admin_menu' ), 9 );
			add_action( 'admin_bar_menu',        array( $this, 'adjust_toolbar' ) );

			// Page fix - remove the about page from the menu
			add_action( 'admin_head',            array( $this, 'fix_remove_about_page' ), 999 );

		}

		/**
		 * Pre Init Globals
		 * Globals that does not reply on external sources and can be loaded before init.
		 * @since 1.7
		 * @version 1.0
		 */
		private function pre_init_globals() {

			global $mycred, $mycred_log_table, $mycred_types, $mycred_modules, $mycred_label;

			$mycred             = new myCRED_Settings();
			$mycred_log_table   = $mycred->log_table;
			$mycred_types       = $this->point_types;
			$mycred_label       = apply_filters( 'mycred_label', MYCRED_DEFAULT_LABEL );
			$mycred_modules     = $this->modules;

		}

		/**
		 * Post Init Globals
		 * Globals that needs to be defined after init. Mainly used for user related globals.
		 * @since 1.7
		 * @version 1.0
		 */
		private function post_init_globals() {

			// Just in case, this should never happen
			if ( ! did_action( 'init' ) ) return;

			global $mycred_account;

			$this->account  = mycred_get_account();
			$mycred_account = $this->account;

			do_action( 'mycred_set_globals' );

		}

		/**
		 * Load Plugin Textdomain
		 * @since 1.7
		 * @version 1.0
		 */
		public function load_plugin_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), 'mycred' );

			load_textdomain( 'mycred', WP_LANG_DIR . '/mycred/mycred-' . $locale . '.mo' );
			load_plugin_textdomain( 'mycred', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

		}

		/**
		 * Register Assets
		 * @since 1.7
		 * @version 1.0
		 */
		public function register_assets() {

			// Styles
			wp_register_style( 'mycred-front',          plugins_url( 'assets/css/mycred-front.css', myCRED_THIS ),        array(), $this->version, 'all' );
			wp_register_style( 'mycred-admin',          plugins_url( 'assets/css/mycred-admin.css', myCRED_THIS ),        array(), $this->version, 'all' );
			wp_register_style( 'mycred-edit-balance',   plugins_url( 'assets/css/mycred-edit-balance.css', myCRED_THIS ), array(), $this->version, 'all' );
			wp_register_style( 'mycred-edit-log',       plugins_url( 'assets/css/mycred-edit-log.css', myCRED_THIS ),     array(), $this->version, 'all' );
			wp_register_style( 'mycred-bootstrap-grid', plugins_url( 'assets/css/bootstrap-grid.css', myCRED_THIS ),      array(), $this->version, 'all' );
			wp_register_style( 'mycred-forms',          plugins_url( 'assets/css/mycred-forms.css', myCRED_THIS ),        array(), $this->version, 'all' );

			// Scripts
			wp_register_script( 'mycred-send-points',   plugins_url( 'assets/js/send.js', myCRED_THIS ),                 array( 'jquery' ), $this->version, true );
			wp_register_script( 'mycred-accordion',     plugins_url( 'assets/js/mycred-accordion.js', myCRED_THIS ),     array( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion' ), $this->version );
			wp_register_script( 'jquery-numerator',     plugins_url( 'assets/libs/jquery-numerator.js', myCRED_THIS ),   array( 'jquery' ), '0.2.1' );
			wp_register_script( 'mycred-mustache',      plugins_url( 'assets/libs/mustache.min.js', myCRED_THIS ),       array(), '2.2.1' );
			wp_register_script( 'mycred-widgets',       plugins_url( 'assets/js/mycred-admin-widgets.js', myCRED_THIS ), array( 'jquery', 'jquery-ui-sortable', 'jquery-ui-draggable', 'jquery-ui-droppable' ), $this->version );
			wp_register_script( 'mycred-edit-balance',  plugins_url( 'assets/js/mycred-edit-balance.js', myCRED_THIS ),  array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-effects-core', 'jquery-effects-slide', 'jquery-numerator' ), $this->version );
			wp_register_script( 'mycred-edit-log',      plugins_url( 'assets/js/mycred-edit-log.js', myCRED_THIS ),      array( 'jquery', 'jquery-ui-core', 'jquery-ui-dialog', 'jquery-effects-core', 'jquery-effects-slide', 'common' ), $this->version );

			do_action( 'mycred_register_assets' );

		}

		/**
		 * Setup Cron Jobs
		 * @since 1.7
		 * @version 1.0
		 */
		private function setup_cron_jobs() {

			// Add schedule if none exists
			if ( ! wp_next_scheduled( 'mycred_reset_key' ) )
				wp_schedule_event( time(), apply_filters( 'mycred_cron_reset_key', 'daily' ), 'mycred_reset_key' );

		}

		/**
		 * Register Importers
		 * @since 1.7
		 * @version 1.0
		 */
		private function register_importers() {

			/**
			 * Register Importer: Log Entries
			 * @since 1.4
			 * @version 1.0
			 */
			register_importer(
				'mycred_import_log',
				sprintf( __( '%s Log Import', 'mycred' ), mycred_label() ),
				__( 'Import log entries via a CSV file.', 'mycred' ),
				array( $this, 'import_log_entries' )
			);

			/**
			 * Register Importer: Balances
			 * @since 1.4.2
			 * @version 1.0
			 */
			register_importer(
				'mycred_import_balance',
				sprintf( __( '%s Balance Import', 'mycred' ), mycred_label() ),
				__( 'Import balances.', 'mycred' ),
				array( $this, 'import_balances' )
			);

			/**
			 * Register Importer: CubePoints
			 * @since 1.4
			 * @version 1.0
			 */
			register_importer(
				'mycred_import_cp',
				sprintf( __( '%s CubePoints Import', 'mycred' ), mycred_label() ),
				__( 'Import CubePoints log entries and / or balances.', 'mycred' ),
				array( $this, 'import_cubepoints' )
			);

		}

		/**
		 * Front Enqueue Before
		 * Enqueues scripts and styles that must run before content is loaded.
		 * @since 1.7
		 * @version 1.0
		 */
		public function enqueue_front_before() {

			// Widget Style (can be disabled)
			if ( apply_filters( 'mycred_remove_widget_css', false ) === false )
				wp_enqueue_style( 'mycred-front' );

			// Let others play
			do_action( 'mycred_front_enqueue' );

		}

		/**
		 * Front Enqueue After
		 * Enqueuest that must run after content has loaded.
		 * @since 1.7
		 * @version 1.0
		 */
		public function enqueue_front_after() {

			global $mycred_sending_points;

			// myCRED Send Feature via the mycred_send shortcode
			if ( $mycred_sending_points === true || apply_filters( 'mycred_enqueue_send_js', false ) === true ) {

				$base = array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'token'   => wp_create_nonce( 'mycred-send-points' )
				);

				$language = apply_filters( 'mycred_send_language', array(
					'working' => esc_attr__( 'Processing...', 'mycred' ),
					'done'    => esc_attr__( 'Sent', 'mycred' ),
					'error'   => esc_attr__( 'Error - Try Again', 'mycred' )
				) );

				wp_localize_script(
					'mycred-send-points',
					'myCREDsend',
					array_merge_recursive( $base, $language )
				);
				wp_enqueue_script( 'mycred-send-points' );

			}

			do_action( 'mycred_front_enqueue_footer' );

		}

		/**
		 * Admin Enqueue
		 * @since 1.7
		 * @version 1.0
		 */
		public function enqueue_admin_before() {

			// Let others play
			do_action( 'mycred_admin_enqueue' );

		}

		/**
		 * Widgets Init
		 * @since 1.7
		 * @version 1.0
		 */
		public function widgets_init() {

			// Register Widgets
			register_widget( 'myCRED_Widget_Balance' );
			register_widget( 'myCRED_Widget_Leaderboard' );

			if ( count( $this->point_types ) > 1 )
				register_widget( 'myCRED_Widget_Wallet' );

			// Let others play
			do_action( 'mycred_widgets_init' );

		}

		/**
		 * Admin Init
		 * @since 1.7
		 * @version 1.0
		 */
		public function admin_init() {

			// Sudden change of version number indicates an update
			$mycred_version = get_option( 'mycred_version', $this->version );
			if ( $mycred_version != $this->version )
				do_action( 'mycred_reactivation', $mycred_version );

			// Dashboard Overview
			$this->file( myCRED_INCLUDES_DIR . 'mycred-overview.php' );

			// Importers
			if ( defined( 'WP_LOAD_IMPORTERS' ) )
				$this->register_importers();

			// Let others play
			do_action( 'mycred_admin_init' );

			// When the plugin is activated after an update, redirect to the about page
			// Checks for the _mycred_activation_redirect transient
			if ( get_transient( '_mycred_activation_redirect' ) === apply_filters( 'mycred_active_redirect', false ) )
				return;

			delete_transient( '_mycred_activation_redirect' );

			wp_safe_redirect( add_query_arg( array( 'page' => MYCRED_SLUG . '-about' ), admin_url( 'index.php' ) ) );
			die;

		}

		/**
		 * Load Importer: Log Entries
		 * @since 1.4
		 * @version 1.1
		 */
		public function import_log_entries() {

			$this->file( ABSPATH . 'wp-admin/includes/import.php' );

			if ( ! class_exists( 'WP_Importer' ) )
				$this->file( ABSPATH . 'wp-admin/includes/class-wp-importer.php' );

			$this->file( myCRED_IMPORTERS_DIR . 'mycred-log-entries.php' );
	
			$importer = new myCRED_Importer_Log_Entires();
			$importer->load();

		}

		/**
		 * Load Importer: Point Balances
		 * @since 1.4
		 * @version 1.1
		 */
		public function import_balances() {

			$this->file( ABSPATH . 'wp-admin/includes/import.php' );

			if ( ! class_exists( 'WP_Importer' ) )
				$this->file( ABSPATH . 'wp-admin/includes/class-wp-importer.php' );

			$this->file( myCRED_IMPORTERS_DIR . 'mycred-balances.php' );

			$importer = new myCRED_Importer_Balances();
			$importer->load();

		}

		/**
		 * Load Importer: CubePoints
		 * @since 1.4
		 * @version 1.1
		 */
		public function import_cubepoints() {

			$this->file( ABSPATH . 'wp-admin/includes/import.php' );

			global $wpdb;

			// No use continuing if there is no log to import
			if ( $wpdb->query( $wpdb->prepare( "SHOW TABLES LIKE %s;", $wpdb->prefix . 'cp' ) ) == 0 ) {
				echo '<p>' . __( 'No CubePoints log exists.', 'mycred' ) . '</p>';
				return;
			}

			if ( ! class_exists( 'WP_Importer' ) )
				$this->file( ABSPATH . 'wp-admin/includes/class-wp-importer.php' );

			$this->file( myCRED_IMPORTERS_DIR . 'mycred-cubepoints.php' );

			$importer = new myCRED_Importer_CubePoints();
			$importer->load();

		}

		/**
		 * Admin Menu
		 * @since 1.7
		 * @version 1.0
		 */
		public function adjust_admin_menu() {

			global $mycred, $wp_version;

			$pages     = array();
			$name      = mycred_label( true );
			$menu_icon = 'dashicons-star-filled';

			if ( version_compare( $wp_version, '3.8', '<' ) )
				$menu_icon = '';

			// Add skeleton menus for each point type so modules can
			// insert their content under each of these menus
			foreach ( $this->point_types as $type_id => $title ) {

				$type_slug = MYCRED_SLUG;
				if ( $type_id != MYCRED_DEFAULT_TYPE_KEY )
					$type_slug = MYCRED_SLUG . '_' . trim( $type_id );

				$pages[] = add_menu_page(
					$title,
					$title,
					$mycred->edit_creds_cap(),
					$type_slug,
					'',
					$menu_icon
				);

			}

			// Add about page
			$pages[] = add_dashboard_page(
				sprintf( __( 'About %s', 'mycred' ), $name ),
				sprintf( __( 'About %s', 'mycred' ), $name ),
				'moderate_comments',
				MYCRED_SLUG . '-about',
				'mycred_about_page'
			);

			// Add credits page
			$pages[] = add_dashboard_page(
				__( 'Awesome People', 'mycred' ),
				__( 'Awesome People', 'mycred' ),
				'moderate_comments',
				MYCRED_SLUG . '-credit',
				'mycred_about_credit_page'
			);

			// Add styling to our admin screens
			$pages = apply_filters( 'mycred_admin_pages', $pages, $mycred );
			foreach ( $pages as $page )
				add_action( 'admin_print_styles-' . $page, array( $this, 'fix_admin_page_styles' ) );

			// Let others play
			do_action( 'mycred_add_menu', $mycred );

		}

		/**
		 * Toolbar
		 * @since 1.7
		 * @version 1.0
		 */
		public function adjust_toolbar( $wp_admin_bar ) {

			if ( ! is_user_logged_in() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) return;

			global $mycred;

			$user_id      = get_current_user_id();
			$usable_types = mycred_get_usable_types( $user_id );
			$history_url  = admin_url( 'users.php' );

			if ( empty( $usable_types ) ) return;

			$using_buddypress = false;
			if ( function_exists( 'bp_loggedin_user_domain' ) )
				$using_buddypress = true;

			$main_label = __( 'Balance', 'mycred' );
			if ( count( $usable_types ) == 1 )
				$main_label = $mycred->plural();

			// BuddyPress
			if ( $using_buddypress ) {

				$wp_admin_bar->add_menu( array(
					'parent' => 'my-account-buddypress',
					'id'     => MYCRED_SLUG . '-account',
					'title'  => $main_label,
					'href'   => false
				) );

				if ( isset( $mycred->buddypress['history_url'] ) && ! empty( $mycred->buddypress['history_url'] ) )
					$history_url = bp_loggedin_user_domain() . $mycred->buddypress['history_url'] . '/';

				// Disable history url until the variable needed is setup
				else
					$history_url = '';

			}

			// Default
			else {

				$wp_admin_bar->add_menu( array(
					'parent' => 'my-account',
					'id'     => MYCRED_SLUG . '-account',
					'title'  => $main_label,
					'meta'   => array( 'class' => 'ab-sub-secondary' )
				) );

			}

			// Add balance and history link for each point type
			foreach ( $usable_types as $type_id ) {

				if ( $type_id == MYCRED_DEFAULT_TYPE_KEY )
					$point_type = $mycred;
				else
					$point_type = mycred( $type_id );

				$history_url = '';
				if ( ! $using_buddypress )
					$history_url = add_query_arg( array( 'page' => $type_id . '-history' ), admin_url( 'users.php' ) );

				else
					$history_url = add_query_arg( array( 'show-ctype' => $type_id ), $history_url );

				$balance          = $point_type->get_users_balance( $user_id, $type_id );
				$history_url      = apply_filters( 'mycred_my_history_url', $history_url, $type_id, $point_type );
				$adminbar_menu_id = str_replace( '_', '-', $type_id );

				// Show balance
				$wp_admin_bar->add_menu( array(
					'parent' => MYCRED_SLUG . '-account',
					'id'     => MYCRED_SLUG . '-account-balance-' . $adminbar_menu_id,
					'title'  => $point_type->template_tags_amount( apply_filters( 'mycred_label_my_balance', '%plural%: %cred_f%', $user_id, $point_type ), $balance ),
					'href'   => false
				) );

				// History link
				if ( $history_url != '' && apply_filters( 'mycred_admin_show_history_' . $type_id, true ) === true )
					$wp_admin_bar->add_menu( array(
						'parent' => MYCRED_SLUG . '-account',
						'id'     => MYCRED_SLUG . '-account-history-' . $adminbar_menu_id,
						'title'  => sprintf( '%s %s', $point_type->plural(), __( 'History', 'mycred' ) ),
						'href'   => $history_url
					) );

			}
	
			// Let others play
			do_action( 'mycred_tool_bar', $wp_admin_bar, $mycred );

		}

		/**
		 * Cron: Reset Encryption Key
		 * @since 1.2
		 * @version 1.0
		 */
		public function cron_reset_key() {

			$protect = mycred_protect();
			if ( $protect !== false )
				$protect->reset_key();

		}

		/**
		 * FIX: Remove About and Credit Page
		 * @since 1.7
		 * @version 1.0
		 */
		public function fix_remove_about_page() {

			remove_submenu_page( 'index.php', MYCRED_SLUG . '-about' );
			remove_submenu_page( 'index.php', MYCRED_SLUG . '-credit' );

		}

		/**
		 * FIX: Add admin page style
		 * @since 1.7
		 * @version 1.0
		 */
		public function fix_admin_page_styles() {

			wp_enqueue_style( 'mycred-admin' );

		}

		/**
		 * Plugin Links
		 * @since 1.7
		 * @version 1.0
		 */
		public function plugin_links( $actions, $plugin_file, $plugin_data, $context ) {

			// Link to Setup
			if ( ! mycred_is_installed() )
				$actions['_setup'] = '<a href="' . admin_url( 'plugins.php?page=' . MYCRED_SLUG . '-setup' ) . '">' . __( 'Setup', 'mycred' ) . '</a>';
			else
				$actions['_settings'] = '<a href="' . admin_url( 'admin.php?page=' . MYCRED_SLUG . '-settings' ) . '" >' . __( 'Settings', 'mycred' ) . '</a>';

			ksort( $actions );
			return $actions;

		}

		/**
		 * Plugin Description Links
		 * @since 1.7
		 * @version 1.0
		 */
		public function plugin_description_links( $links, $file ) {

			if ( $file != plugin_basename( myCRED_THIS ) ) return $links;

			// Link to Setup
			if ( ! is_mycred_ready() ) {

				$links[] = '<a href="' . admin_url( 'plugins.php?page=' . MYCRED_SLUG . '-setup' ) . '">' . __( 'Setup', 'mycred' ) . '</a>';
				return $links;

			}

			// Usefull links
			$links[] = '<a href="' . admin_url( 'index.php?page=' . MYCRED_SLUG ) . '">About</a>';
			$links[] = '<a href="http://mycred.me/documentation/" target="_blank">Documentation</a>';
			$links[] = '<a href="http://codex.mycred.me/" target="_blank">Codex</a>';
			$links[] = '<a href="http://mycred.me/store/" target="_blank">Store</a>';

			return $links;

		}

		/**
		 * Plugin Update Warning
		 * @since 1.7
		 * @version 1.0
		 */
		public function plugin_update_warning() {

			echo '<div style="color:#cc0000;">' . __( 'Make sure to backup your database and files before updating, in case anything goes wrong!', 'mycred' ) . '</div>';

		}

	}
endif;

function mycred_core() {
	return myCRED_Core::instance();
}
mycred_core();
