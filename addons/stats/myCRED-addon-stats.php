<?php
/**
 * Addon: Stats
 * Addon URI: http://mycred.me/add-ons/stats/
 * Version: 1.2
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_STATS',             __FILE__ );
define( 'myCRED_STATS_VERSION',     '1.2' );
define( 'myCRED_STATS_DIR',         myCRED_ADDONS_DIR . 'stats/' );
define( 'myCRED_STATS_WIDGETS_DIR', myCRED_STATS_DIR . 'widgets/' );

/**
 * Required Files
 */
require_once myCRED_STATS_DIR . 'includes/mycred-stats-functions.php';
require_once myCRED_STATS_DIR . 'includes/mycred-stats-shortcodes.php';

require_once myCRED_STATS_DIR . 'includes/classes/class.query-stats.php';

require_once myCRED_STATS_DIR . 'abstracts/mycred-abstract-stat-widget.php';

/**
 * Core Widgets
 */
require_once myCRED_STATS_WIDGETS_DIR . 'mycred-stats-widget-circulation.php';
require_once myCRED_STATS_WIDGETS_DIR . 'mycred-stats-widget-daily-gains.php';
require_once myCRED_STATS_WIDGETS_DIR . 'mycred-stats-widget-daily-loses.php';

do_action( 'mycred_stats_load_widgets' );

/**
 * myCRED_Stats_Module class
 * @since 1.6
 * @version 1.1
 */
if ( ! class_exists( 'myCRED_Stats_Module' ) ) :
	class myCRED_Stats_Module extends myCRED_Module {

		public $user;
		public $screen;
		public $ctypes;
		public $colors;

		/**
		 * Construct
		 */
		function __construct( $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( 'myCRED_Stats_Module', array(
				'module_name' => 'stats',
				'accordion'   => false,
				'register'    => false,
				'add_to_core' => false
			), $type );

			$this->label  = sprintf( '%s %s', mycred_label(), __( 'Statistics', 'mycred' ) );
			$this->colors = mycred_get_type_color();

		}

		/**
		 * Init
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function module_init() {

			global $mycred_types, $mycred_load_stats;

			$mycred_load_stats = false;

			// Scripts & Styles
			add_action( 'mycred_front_enqueue',  array( $this, 'register_scripts' ), 30 );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ) );
			add_action( 'wp_footer',             array( $this, 'load_front_scripts' ), 5 );

			// Add shortcode
			add_shortcode( 'mycred_statistics', 'mycred_statistics_shortcode_render' );

			// Add color options to each point type
			add_action( 'mycred_core_prefs',      array( $this, 'color_settings' ) );
			add_filter( 'mycred_save_core_prefs', array( $this, 'sanitize_extra_settings' ), 10, 3 );
			foreach ( $mycred_types as $type_id => $type ) {

				if ( $type_id === MYCRED_DEFAULT_TYPE_KEY ) continue;

				add_action( 'mycred_core_prefs' . $type_id,      array( $this, 'color_settings' ) );
				add_filter( 'mycred_save_core_prefs' . $type_id, array( $this, 'sanitize_extra_settings' ), 10, 3 );

			}

			// Add admin screen
			add_action( 'admin_menu', array( $this, 'add_menu' ) );

		}

		/**
		 * Register Front Scripts
		 * @since 1.6
		 * @version 1.1
		 */
		public function register_scripts() {

			// Stylesheets
			wp_register_style(
				'mycred-stats',
				plugins_url( 'assets/css/mycred-statistics.css', myCRED_STATS ),
				array(),
				'1.1',
				'all'
			);

			// Stylesheets
			wp_register_style(
				'mycred-stats-admin',
				plugins_url( 'assets/css/mycred-statistics-admin.css', myCRED_STATS ),
				array(),
				myCRED_STATS_VERSION,
				'all'
			);

			// Scripts
			wp_register_script(
				'chart-js',
				plugins_url( 'assets/js/Chart.js', myCRED_STATS ),
				array( 'jquery' ),
				myCRED_STATS_VERSION,
				true
			);

		}

		/**
		 * Admin Screen Styles
		 * @since 1.6.8
		 * @version 1.1
		 */
		public function load_front_scripts() {

			global $mycred_load_stats;

			if ( $mycred_load_stats === true ) {

				wp_enqueue_style( 'mycred-stats' );
				wp_enqueue_script( 'chart-js' );

			}

		}

		/**
		 * Color Settings
		 * @since 1.7
		 * @version 1.0
		 */
		public function color_settings( $settings ) {

			$colors = mycred_get_type_color( $settings->mycred_type );
			if ( ! is_array( $colors ) ) {
				$colors = array(
					'positive' => mycred_rgb_to_hex( $colors ),
					'negative' => ''
				);
				$colors['negative'] = mycred_inverse_hex_color( $colors['positive'] );
			}
			elseif ( is_array( $colors ) && $colors['positive'] != '' && $colors['negative'] == '' )
				$colors['negative'] = mycred_rgb_to_hex( $colors );

?>
<h3><?php _e( 'Statistics Color', 'mycred' ); ?></h3>
<div class="row">
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<div class="form-group">
			<label for="<?php echo $settings->field_id( array( 'colors' => 'positive' ) ); ?>"><?php _e( 'Positive Values', 'mycred' ); ?></label>
			<input type="text" name="<?php echo $settings->field_name( array( 'colors' => 'positive' ) ); ?>" id="<?php echo $settings->field_id( array( 'colors' => 'positive' ) ); ?>" value="<?php echo esc_attr( $colors['positive'] ); ?>" class="wp-color-picker-field" data-default-color="#dedede" />
		</div>
	</div>
	<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
		<div class="form-group">
			<label for="<?php echo $settings->field_id( array( 'colors' => 'negative' ) ); ?>"><?php _e( 'Negative Values', 'mycred' ); ?></label>
			<input type="text" name="<?php echo $settings->field_name( array( 'colors' => 'negative' ) ); ?>" id="<?php echo $settings->field_id( array( 'colors' => 'negative' ) ); ?>" value="<?php echo esc_attr( $colors['negative'] ); ?>" class="wp-color-picker-field" data-default-color="" />
		</div>
	</div>
</div>
<script type="text/javascript">
jQuery(document).ready(function($){

	// Load wp color picker
	$( '.wp-color-picker-field' ).wpColorPicker();
	
});
</script>
<?php

		}

		/**
		 * Sanitize & Save Settings
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function sanitize_extra_settings( $new_data, $data, $general ) {

			if ( array_key_exists( 'colors', $data ) ) {

				$colors             = array();
				$colors['positive'] = sanitize_text_field( $data['colors']['positive'] );
				$colors['negative'] = sanitize_text_field( $data['colors']['negative'] );

				$saved                          = mycred_get_type_color();
				$saved[ $general->mycred_type ] = $colors;

				mycred_update_option( 'mycred-point-colors', $saved );

			}

			if ( array_key_exists( 'colors', $new_data ) )
				unset( $new_data['colors'] );

			return $new_data;

		}

		/**
		 * Add Menu
		 * @since 1.6
		 * @version 1.0
		 */
		public function add_menu() {

			$page = add_dashboard_page(
				$this->label,
				$this->label,
				$this->core->edit_creds_cap(),
				'mycred-stats',
				array( $this, 'admin_page' )
			);

			add_action( 'admin_print_styles-' . $page, array( $this, 'admin_page_header' ) );

		}

		public function get_tabs() {

			$tabs = array();
			$tabs['overview'] = __( 'Overview', 'mycred' );

			foreach ( $this->point_types as $type_id => $label ) {
				$mycred = mycred( $type_id );
				$tabs[ 'view_' . $type_id ] = $mycred->plural();
			}

			return apply_filters( 'mycred_statistics_tabs', $tabs );

		}

		/**
		 * Admin Page Header
		 * @since 1.6
		 * @version 1.1
		 */
		public function admin_page_header() {

			wp_enqueue_style( 'mycred-stats' );
			wp_enqueue_style( 'mycred-stats-admin' );
			wp_enqueue_script( 'chart-js' );

			do_action( 'mycred_stats_page_header', $this );

		}

		/**
		 * Has Entries
		 * @since 1.6
		 * @version 1.0
		 */
		public function has_entries() {

			global $wpdb;

			$reply = true;
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->core->log_table};" );
			if ( $count === NULL || $count < 1 )
				$reply = false;

			return apply_filters( 'mycred_stats_has_entries', $reply, $this );

		}

		/**
		 * Admin Page
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function admin_page() {

			// Security
			if ( ! $this->core->can_edit_creds() ) wp_die( 'Access Denied' );

			$current = 'overview';
			if ( isset( $_GET['view'] ) )
				$current = $_GET['view'];

			$tabs = $this->get_tabs();

?>
<div id="mycred-stats" class="wrap">
	<h1><?php echo $this->label; ?><a href="javascript:void(0);" onClick="window.location.href=window.location.href" class="add-new-h2" id="refresh-mycred-stats"><?php _e( 'Refresh', 'mycred' ); ?></a></h1>
<?php

			do_action( 'mycred_stats_page_before', $this );

			// No use loading the widgets if no log entries exists
			if ( $this->has_entries() ) {

?>
	<ul id="section-nav" class="nav-tab-wrapper">
<?php

				foreach ( $tabs as $tab_id => $tab_label ) {

					$classes = 'nav-tab';
					if ( $current == $tab_id ) $classes .= ' nav-tab-active';

					if ( $tab_id != 'general' )
						$url = add_query_arg( array( 'page' => $_GET['page'], 'view' => $tab_id ), admin_url( 'admin.php' ) );
					else
						$url = add_query_arg( array( 'page' => $_GET['page'] ), admin_url( 'admin.php' ) );

					echo '<li class="' . $classes . '"><a href="' . esc_url( $url ) . '">' . $tab_label . '</a></li>';

				}

?>
	</ul>

	<div id="mycred-stats-body" class="clear clearfix">
		
<?php

				// Render tab
				if ( has_action( 'mycred_stats_screen_' . $current ) ) {

					do_action( 'mycred_stats_screen_' . $current );

				}

				elseif ( $current === 'overview' ) {

					$widgets = apply_filters( 'mycred_stats_overview_widgets', array(
						0 => array( 'id' => 'overallcirculation', 'class' => 'myCRED_Stats_Widget_Circulation', 'args' => array( 'ctypes' => 'all' ) ),
						1 => array( 'id' => 'overallgains', 'class' => 'myCRED_Stats_Widget_Daily_Gains', 'args' => array( 'ctypes' => 'all', 'span' => 10, 'number' => 5 ) ),
						2 => array( 'id' => 'overallloses', 'class' => 'myCRED_Stats_Widget_Daily_Loses', 'args' => array( 'ctypes' => 'all', 'span' => 10, 'number' => 5 ) )
					), $this );

					if ( ! empty( $widgets ) ) {
						foreach ( $widgets as $num => $swidget ) {
		
							$widget = $swidget['class'];
							if ( class_exists( $widget ) ) {

								$w = new $widget( $swidget['id'], $swidget['args'] );

								echo '<div class="mycred-stat-widget">';
		
								$w->widget();
		
								echo '</div>';

								$w = NULL;
		
							}
		
						}
					}

				}

				elseif ( substr( $current, 0, 5 ) === 'view_' ) {

					$widgets = apply_filters( 'mycred_stats_' . $current . '_widgets', array(
						0 => array( 'id' => $current . 'circulation', 'class' => 'myCRED_Stats_Widget_Circulation', 'args' => array( 'ctypes' => $current ) ),
						1 => array( 'id' => $current . 'gains', 'class' => 'myCRED_Stats_Widget_Daily_Gains', 'args' => array( 'ctypes' => $current, 'span' => 10, 'number' => 5 ) ),
						2 => array( 'id' => $current . 'loses', 'class' => 'myCRED_Stats_Widget_Daily_Loses', 'args' => array( 'ctypes' => $current, 'span' => 10, 'number' => 5 ) )
					), $this );

					if ( ! empty( $widgets ) ) {
						foreach ( $widgets as $num => $swidget ) {

							$widget = $swidget['class'];
							if ( class_exists( $widget ) ) {

								$w = new $widget( $swidget['id'], $swidget['args'] );

								echo '<div class="mycred-stat-widget">';

								$w->widget();

								echo '</div>';

								$w = NULL;

							}

						}
					}

				}

			}
			else {

?>
<div id="mycred-log-is-empty">
	<p><?php _e( 'Your log is empty. No statistics can be shown.', 'mycred' ); ?></p>
</div>
<?php

			}

?>
		</div>
	</div>

</div>
<?php

			do_action( 'mycred_stats_page_after', $this );

		}

	}
endif;

/**
 * Load Statistics Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_statistics_addon' ) ) :
	function mycred_load_statistics_addon( $modules, $point_types ) {

		$modules['solo']['stats'] = new myCRED_Stats_Module();
		$modules['solo']['stats']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_statistics_addon', 100, 2 );

?>