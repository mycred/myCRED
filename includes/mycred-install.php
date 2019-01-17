<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Install class
 * Used when the plugin is activated/de-activated or deleted. Installs core settings and
 * base templates, checks compatibility and uninstalls.
 * @since 0.1
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Install' ) ) :
	final class myCRED_Install {

		// Instnace
		protected static $_instance = NULL;

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
		 * Construct
		 */
		function __construct() { }

		/**
		 * Compat
		 * Check to make sure we reach minimum requirements for this plugin to work propery.
		 * @since 0.1
		 * @version 1.3
		 */
		public static function compat() {

			global $wpdb;

			$message = array();

			// WordPress check
			$wp_version = $GLOBALS['wp_version'];
			if ( version_compare( $wp_version, '4.0', '<' ) && MYCRED_FOR_OLDER_WP === false )
				$message[] = __( 'myCRED requires WordPress 4.0 or higher. Version detected:', 'mycred' ) . ' ' . $wp_version;

			// PHP check
			$php_version = phpversion();
			if ( version_compare( $php_version, '5.3', '<' ) )
				$message[] = __( 'myCRED requires PHP 5.3 or higher. Version detected: ', 'mycred' ) . ' ' . $php_version;

			// SQL check
			$sql_version = $wpdb->db_version();
			if ( version_compare( $sql_version, '5.0', '<' ) )
				$message[] = __( 'myCRED requires SQL 5.0 or higher. Version detected: ', 'mycred' ) . ' ' . $sql_version;

			// mcrypt library check (if missing, this will cause a fatal error)
			$extensions = get_loaded_extensions();
			if ( ! in_array( 'mcrypt', $extensions ) && MYCRED_DISABLE_PROTECTION === false )
				$message[] = __( 'The mcrypt PHP library must be enabled in order to use this plugin! Please check your PHP configuration or contact your host and ask them to enable it for you!', 'mycred' );

			// Not empty $message means there are issues
			if ( ! empty( $message ) ) {

				die( __( 'Sorry but your WordPress installation does not reach the minimum requirements for running myCRED. The following errors were given:', 'mycred' ) . "\n" . implode( "\n", $message ) );

			}

		}

		/**
		 * First time activation
		 * @since 0.1
		 * @version 1.3
		 */
		public static function activate() {

			$mycred = mycred();

			// Add general settings
			add_option( 'mycred_version',   myCRED_VERSION );
			add_option( 'mycred_key',       wp_generate_password( 12, true, true ) );
			add_option( 'mycred_pref_core', $mycred->defaults() );

			// Add add-ons settings
			add_option( 'mycred_pref_addons', array(
				'installed' => array(),
				'active'    => array()
			) );

			// Add hooks settings
			add_option( 'mycred_pref_hooks', array(
				'installed'  => array(),
				'active'     => array(),
				'hook_prefs' => array()
			) );

			do_action( 'mycred_activation' );

			if ( isset( $_GET['activate-multi'] ) )
				return;

			set_transient( '_mycred_activation_redirect', true, 60 );

			flush_rewrite_rules();

		}

		/**
		 * Re-activation
		 * @since 0.1
		 * @version 1.3.1
		 */
		public static function reactivate() {

			do_action( 'mycred_reactivation', get_option( 'mycred_version', false ) );

			if ( isset( $_GET['activate-multi'] ) )
				return;

			set_transient( '_mycred_activation_redirect', true, 60 );

		}

		/**
		 * Uninstall
		 * Removes all myCRED related data from the database.
		 * @since 0.1
		 * @version 1.5
		 */
		public static function uninstall() {

			global $wpdb;

			$mycred_types = mycred_get_types();

			// Options to delete
			$options_to_delete = array(
				'mycred_pref_core',
				'mycred_pref_hooks',
				'mycred_pref_addons',
				'mycred_pref_bank',
				'mycred_pref_remote',
				'mycred_types',
				'woocommerce_mycred_settings',
				'mycred_sell_content_one_seven_updated',
				'mycred_setup_completed',
				'mycred_version',
				'mycred_version_db',
				'mycred_key',
				'mycred_network',
				'widget_mycred_widget_balance',
				'widget_mycred_widget_list',
				'widget_mycred_widget_transfer',
				'mycred_ref_hook_counter',
				'mycred_espresso_gateway_prefs',
				'mycred_eventsmanager_gateway_prefs'
			);

			foreach ( $mycred_types as $type => $label ) {
				$options_to_delete[] = 'mycred_pref_core_' . $type;
				$options_to_delete[] = 'mycred-cache-total-' . $type;
			}
			$options_to_delete = apply_filters( 'mycred_uninstall_options', $options_to_delete );

			if ( ! empty( $options_to_delete ) ) {
				foreach ( $options_to_delete as $option_id )
					delete_option( $option_id );
			}

			// Unschedule cron jobs
			$mycred_crons_to_clear = apply_filters( 'mycred_uninstall_schedules', array(
				'mycred_reset_key',
				'mycred_banking_recurring_payout',
				'mycred_banking_do_batch',
				'mycred_banking_interest_compound',
				'mycred_banking_do_compound_batch',
				'mycred_banking_interest_payout',
				'mycred_banking_interest_do_batch',
				'mycred_send_email_notices'
			) );

			if ( ! empty( $mycred_crons_to_clear ) ) {
				foreach ( $mycred_crons_to_clear as $schedule_id )
					wp_clear_scheduled_hook( $schedule_id );
			}

			// Delete all custom post types created by myCRED
			$mycred_post_types_to_delete = apply_filters( 'mycred_uninstall_post_types', array(
				'mycred_badge',
				'mycred_coupon',
				'mycred_email_notice',
				'mycred_rank',
				'buycred_payment'
			) );

			if ( ! empty( $mycred_post_types_to_delete ) ) {
				foreach ( $mycred_post_types_to_delete as $post_type ) {

					$posts = new WP_Query( array( 'posts_per_page' => -1, 'post_type' => $post_type, 'fields' => 'ids' ) );
					if ( $posts->have_posts() ) {

						// wp_delete_post() will also handle all post meta deletions
						foreach ( $query->posts as $post_id )
							wp_delete_post( $post_id, true );

					}
					wp_reset_postdata();

				}
			}

			// Delete user meta
			// 'meta_key' => true (exact key) / false (use LIKE)
			$mycred_usermeta_to_delete = array(
				'mycred_rank'                  => true,
				'mycred-last-send'             => true,
				'mycred-last-linkclick'        => true,
				'mycred-last-transfer'         => true,
				'mycred_affiliate_link'        => true,
				'mycred_email_unsubscriptions' => true,
				'mycred_transactions'          => true,
				'mycred_badge%'                => false,
				'mycred_rank%'                 => false,
				'mycred_epp_%'                 => false,
				'mycred_payments_%'            => false,
				'mycred_comment_limit_post_%'  => false,
				'mycred_comment_limit_day_%'   => false
			);

			if ( MYCRED_UNINSTALL_CREDS ) {

				foreach ( $mycred_types as $type => $label ) {

					$mycred_usermeta_to_delete[ $type ]                                = true;
					$mycred_usermeta_to_delete[ $type . '_total' ]                     = true;
					$mycred_usermeta_to_delete[ 'mycred_ref_counts-' . $type ]         = true;
					$mycred_usermeta_to_delete[ 'mycred_ref_sums-' . $type ]           = true;
					$mycred_usermeta_to_delete[ $type . '_comp' ]                      = true;
					$mycred_usermeta_to_delete[ 'mycred_banking_rate_' . $type ]       = true;
					$mycred_usermeta_to_delete[ 'mycred_buycred_rates_' . $type ]      = true;
					$mycred_usermeta_to_delete[ 'mycred_sell_content_share_' . $type ] = true;
					$mycred_usermeta_to_delete[ 'mycred_transactions_' . $type ]       = true;

				}

			}
			$mycred_usermeta_to_delete = apply_filters( 'mycred_uninstall_usermeta', $mycred_usermeta_to_delete );

			if ( ! empty( $mycred_usermeta_to_delete ) ) {
				foreach ( $mycred_usermeta_to_delete as $meta_key => $exact ) {

					if ( $exact )
						delete_metadata( 'user', 0, $meta_key, '', true );
					else
						$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s;", $meta_key ) );

				}
			}

			// Delete log table
			if ( MYCRED_UNINSTALL_LOG ) {

				if ( defined( 'MYCRED_LOG_TABLE' ) )
					$table_name = MYCRED_LOG_TABLE;

				else {

					if ( mycred_centralize_log() )
						$table_name = $wpdb->base_prefix . 'myCRED_log';
					else
						$table_name = $wpdb->prefix . 'myCRED_log';

				}

				$wpdb->query( "DROP TABLE IF EXISTS {$table_name};" );

			}

			// Good bye.
			flush_rewrite_rules();

		}

	}
endif;

/**
 * Get Installer
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_installer' ) ) :
	function mycred_installer() {
		return myCRED_Install::instance();
	}
endif;

?>