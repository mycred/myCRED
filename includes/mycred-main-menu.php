<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Main_Menu class
 * Manages myCred main menu WordPress admin area.
 * @since 0.1
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Main_Menu' ) ):
	class myCRED_Main_Menu {

		/**
		 * Construct
		 * @since 0.1
		 * @version 1.0
		 */
		function __construct( $modules ) {

			global $mycred;

			add_menu_page(
				'myCred',
				'myCred',
				$mycred->get_point_editor_capability(),
				MYCRED_MAIN_SLUG,
				'',
				'dashicons-mycred'
			);

			mycred_add_main_submenu(
				'General Settings',
				'General Settings',
				$mycred->get_point_editor_capability(),
				MYCRED_MAIN_SLUG,
				array( $modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['settings'], 'admin_page' )
			);

			global $pagenow;

			if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == 'mycred-main' ) {
				
				$modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['settings']->scripts_and_styles();
				$modules['type'][ MYCRED_DEFAULT_TYPE_KEY ]['settings']->settings_header();

				wp_enqueue_style( 'mycred-admin' );
				wp_enqueue_script( 'mycred-accordion' );
			
			}

			add_action( 'admin_menu', array( $this, 'add_submenu' ) );

		}

		public function add_submenu() {

			mycred_add_main_submenu(
				__( 'About', 'mycred' ),
				__( 'About', 'mycred' ),
				'moderate_comments',
				MYCRED_SLUG . '-about',
				'mycred_about_page'
			);

		}

	}
endif;