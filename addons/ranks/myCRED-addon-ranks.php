<?php
/**
 * Addon: Ranks
 * Addon URI: http://mycred.me/add-ons/ranks/
 * Version: 1.5
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

define( 'myCRED_RANKS',         __FILE__ );
define( 'myCRED_RANKS_DIR',     myCRED_ADDONS_DIR . 'ranks/' );
define( 'myCRED_RANKS_VERSION', '1.5' );

// Default badge width
if ( ! defined( 'MYCRED_RANK_WIDTH' ) )
	define( 'MYCRED_RANK_WIDTH', 250 );

// Default badge height
if ( ! defined( 'MYCRED_RANK_HEIGHT' ) )
	define( 'MYCRED_RANK_HEIGHT', 250 );

require_once myCRED_RANKS_DIR . 'includes/mycred-rank-functions.php';
require_once myCRED_RANKS_DIR . 'includes/mycred-rank-object.php';
require_once myCRED_RANKS_DIR . 'includes/mycred-rank-shortcodes.php';

/**
 * myCRED_Ranks_Module class
 * While myCRED rankings just ranks users according to users total amount of
 * points, ranks are titles that can be given to users when their reach a certain
 * amount.
 * @since 1.1
 * @version 1.4
 */
if ( ! class_exists( 'myCRED_Ranks_Module' ) ) :
	class myCRED_Ranks_Module extends myCRED_Module {

		/**
		 * Construct
		 */
		function __construct() {

			parent::__construct( 'myCRED_Ranks_Module', array(
				'module_name' => 'rank',
				'defaults'    => array(
					'public'      => 0,
					'base'        => 'current',
					'slug'        => 'mycred_rank',
					'bb_location' => 'top',
					'bb_template' => 'Rank: %rank_title%',
					'bp_location' => '',
					'bb_template' => 'Rank: %rank_title%',
					'order'       => 'ASC',
					'support'     => array(
						'content'         => 0,
						'excerpt'         => 0,
						'comments'        => 0,
						'page-attributes' => 0,
						'custom-fields'   => 0
					)
				),
				'register'    => false,
				'add_to_core' => false,
				'menu_pos'    => 100
			) );

			if ( ! isset( $this->rank['order'] ) )
				$this->rank['order'] = 'ASC';

			if ( ! isset( $this->rank['support'] ) )
				$this->rank['support'] = array(
					'content'         => 0,
					'excerpt'         => 0,
					'comments'        => 0,
					'page-attributes' => 0,
					'custom-fields'   => 0
				);

		}

		/**
		 * Load
		 * Custom module load for multiple point type support.
		 * @since 1.6
		 * @version 1.0
		 */
		public function load() {

			add_action( 'mycred_pre_init',             array( $this, 'module_pre_init' ) );
			add_action( 'mycred_init',                 array( $this, 'module_init' ) );
			add_action( 'mycred_admin_init',           array( $this, 'module_admin_init' ), $this->menu_pos );

		}

		/**
		 * Hook into Init
		 * @since 1.4.4
		 * @version 1.0.1
		 */
		public function module_pre_init() {

			add_filter( 'mycred_has_tags',           array( $this, 'user_tags' ) );
			add_filter( 'mycred_parse_tags_user',    array( $this, 'parse_rank' ), 10, 3 );
			add_filter( 'mycred_post_type_excludes', array( $this, 'exclude_ranks' ) );
			add_filter( 'mycred_add_finished',       array( $this, 'balance_adjustment' ), 20, 3 );
			add_action( 'mycred_zero_balances',      array( $this, 'zero_balance_action' ) );

		}

		/**
		 * Hook into Init
		 * @since 1.1
		 * @version 1.5
		 */
		public function module_init() {

			$this->register_ranks();
			$this->add_default_rank();
			$this->add_multiple_point_types_support();

			add_action( 'pre_get_posts',                            array( $this, 'adjust_wp_query' ), 20 );
			add_action( 'mycred_admin_enqueue',                     array( $this, 'enqueue_scripts' ), $this->menu_pos );

			// Instances to update ranks
			add_action( 'transition_post_status',                   array( $this, 'post_status_change' ), 99, 3 );

			// BuddyPress
			if ( class_exists( 'BuddyPress' ) ) {
				add_action( 'bp_before_member_header_meta',         array( $this, 'insert_rank_header' ) );
				add_action( 'bp_after_profile_loop_content',        array( $this, 'insert_rank_profile' ) );
			}

			// bbPress
			if ( class_exists( 'bbPress' ) ) {
				add_action( 'bbp_theme_after_reply_author_details', array( $this, 'insert_rank_bb_reply' ) );
				add_action( 'bbp_template_after_user_profile',      array( $this, 'insert_rank_bb_profile' ) );
			}

			// Shortcodes
			add_shortcode( 'mycred_my_rank',                        'mycred_render_my_rank' );
			add_shortcode( 'mycred_my_ranks',                       'mycred_render_my_ranks' );
			add_shortcode( 'mycred_users_of_rank',                  'mycred_render_users_of_rank' );
			add_shortcode( 'mycred_users_of_all_ranks',             'mycred_render_users_of_all_ranks' );
			add_shortcode( 'mycred_list_ranks',                     'mycred_render_rank_list' );

			// Admin Management items
			add_action( 'wp_ajax_mycred-calc-totals',               array( $this, 'calculate_totals' ) );

		}

		/**
		 * Hook into Admin Init
		 * @since 1.1
		 * @version 1.2
		 */
		public function module_admin_init() {

			add_filter( 'parent_file',                            array( $this, 'parent_file' ) );
			add_filter( 'submenu_file',                           array( $this, 'subparent_file' ), 10, 2 );
			add_filter( 'admin_url',                              array( $this, 'replace_add_new_rank_url' ), 10, 3 );

			add_filter( 'manage_mycred_rank_posts_columns',       array( $this, 'adjust_column_headers' ), 50 );
			add_action( 'manage_mycred_rank_posts_custom_column', array( $this, 'adjust_column_content' ), 10, 2 );

			add_filter( 'post_row_actions',                       array( $this, 'adjust_row_actions' ), 10, 2 );

			add_filter( 'post_updated_messages',                  array( $this, 'post_updated_messages' ) );
			add_filter( 'enter_title_here',                       array( $this, 'enter_title_here' ) );
			add_action( 'save_post_mycred_rank',                  array( $this, 'save_rank' ), 10, 2 );

			add_action( 'wp_ajax_mycred-action-delete-ranks',     array( $this, 'action_delete_ranks' ) );
			add_action( 'wp_ajax_mycred-action-assign-ranks',     array( $this, 'action_assign_ranks' ) );

			add_filter( 'mycred_users_balance_column',            array( $this, 'custom_user_column_content' ), 10, 3 );

			add_action( 'mycred_user_edit_after_balance',         array( $this, 'show_rank_in_user_editor' ), 40, 3 );

		}

		/**
		 * Add Multiple Point Types Support
		 * @since 1.6
		 * @version 1.0
		 */
		public function add_multiple_point_types_support() {

			add_action( 'mycred_management_prefs', array( $this, 'rank_management' ) );
			add_action( 'mycred_after_core_prefs', array( $this, 'after_general_settings' ) );
			add_filter( 'mycred_save_core_prefs',  array( $this, 'sanitize_extra_settings' ), 90, 3 );

			add_action( 'mycred_add_menu', array( $this, 'add_menus' ), $this->menu_pos );

			if ( count( $this->point_types ) > 1 ) {

				$priority = 10;
				foreach ( $this->point_types as $type_id => $label ) {

					add_action( 'mycred_management_prefs' . $type_id, array( $this, 'rank_management' ), $priority );
					
					add_action( 'mycred_after_core_prefs' . $type_id, array( $this, 'after_general_settings' ), $priority );
					add_filter( 'mycred_save_core_prefs' . $type_id,  array( $this, 'sanitize_extra_settings' ), $priority, 3 );

					$priority = $priority+10;

				}
			}

		}

		/**
		 * Register Rank Post Type
		 * @since 1.1
		 * @version 1.3.1
		 */
		public function register_ranks() {

			if ( isset( $_GET['ctype'] ) && array_key_exists( $_GET['ctype'], $this->point_types ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'mycred_rank' )
				$name = sprintf( __( 'Ranks for %s', 'mycred' ), $this->point_types[ $_GET['ctype'] ] );
			else
				$name = __( 'Ranks', 'mycred' );

			$labels = array(
				'name'                  => $name,
				'singular_name'         => __( 'Rank', 'mycred' ),
				'add_new'               => __( 'Add New', 'mycred' ),
				'add_new_item'          => __( 'Add New', 'mycred' ),
				'edit_item'             => __( 'Edit Rank', 'mycred' ),
				'new_item'              => __( 'New Rank', 'mycred' ),
				'all_items'             => __( 'Ranks', 'mycred' ),
				'view_item'             => __( 'View Rank', 'mycred' ),
				'search_items'          => __( 'Search Ranks', 'mycred' ),
				'featured_image'        => __( 'Rank Logo', 'mycred' ),
				'set_featured_image'    => __( 'Set rank logo', 'mycred' ),
				'remove_featured_image' => __( 'Remove rank logo', 'mycred' ),
				'use_featured_image'    => __( 'Use as Logo', 'mycred' ),
				'not_found'             => __( 'No ranks found', 'mycred' ),
				'not_found_in_trash'    => __( 'No ranks found in Trash', 'mycred' ), 
				'parent_item_colon'     => '',
				'menu_name'             => __( 'Ranks', 'mycred' )
			);

			// Support
			$supports = array( 'title', 'thumbnail' );
			if ( isset( $this->rank['support']['content'] ) && $this->rank['support']['content'] )
				$supports[] = 'editor';
			if ( isset( $this->rank['support']['excerpt'] ) && $this->rank['support']['excerpt'] )
				$supports[] = 'excerpts';
			if ( isset( $this->rank['support']['comments'] ) && $this->rank['support']['comments'] )
				$supports[] = 'comments';
			if ( isset( $this->rank['support']['page-attributes'] ) && $this->rank['support']['page-attributes'] )
				$supports[] = 'page-attributes';
			if ( isset( $this->rank['support']['custom-fields'] ) && $this->rank['support']['custom-fields'] )
				$supports[] = 'custom-fields';

			// Custom Post Type Attributes
			$args = array(
				'labels'               => $labels,
				'public'               => (bool) $this->rank['public'],
				'publicly_queryable'   => (bool) $this->rank['public'],
				'has_archive'          => (bool) $this->rank['public'],
				'show_ui'              => true, 
				'show_in_menu'         => false,
				'capability_type'      => 'page',
				'supports'             => $supports,
				'register_meta_box_cb' => array( $this, 'add_metaboxes' )
			);

			// Rewrite
			if ( $this->rank['public'] && ! empty( $this->rank['slug'] ) )
				$args['rewrite'] = array( 'slug' => $this->rank['slug'] );

			register_post_type( 'mycred_rank', apply_filters( 'mycred_register_ranks', $args, $this ) );

		}

		/**
		 * Adjust Post Updated Messages
		 * @since 1.1
		 * @version 1.2
		 */
		public function post_updated_messages( $messages ) {

			$messages['mycred_rank'] = array(
				0 => '',
				1 => __( 'Rank Updated.', 'mycred' ),
				2 => __( 'Rank Updated.', 'mycred' ),
				3 => __( 'Rank Updated.', 'mycred' ),
				4 => __( 'Rank Updated.', 'mycred' ),
				5 => __( 'Rank Updated.', 'mycred' ),
				6 => __( 'Rank Enabled.', 'mycred' ),
				7 => __( 'Rank Saved.', 'mycred' ),
				8 => __( 'Rank Updated.', 'mycred' ),
				9 => __( 'Rank Updated.', 'mycred' ),
				10 => ''
			);

			return $messages;

		}

		/**
		 * Replace Add New Rank URL
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function replace_add_new_rank_url( $url, $path, $blog_id ) {

			global $post;

			if ( $path == 'post-new.php?post_type=mycred_rank' ) {

				if ( isset( $_GET['ctype'] ) )
					return get_site_url( $blog_id, 'wp-admin/', 'admin' ) . 'post-new.php?post_type=mycred_rank&ctype=' . ( ( isset( $_GET['ctype'] ) ) ? $_GET['ctype'] : MYCRED_DEFAULT_TYPE_KEY );

				elseif ( isset( $post->post_type ) && $post->post_type == 'mycred_rank' && get_post_meta( $post->ID, 'ctype', true ) != '' )
					return get_site_url( $blog_id, 'wp-admin/', 'admin' ) . 'post-new.php?post_type=mycred_rank&ctype=' . get_post_meta( $post->ID, 'ctype', true );

			}

			return $url;

		}

		/**
		 * Add Admin Menu Item
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function add_menus() {

			$cap = $this->core->edit_creds_cap();

			foreach ( $this->point_types as $type_id => $label ) {

				$menu_slug = MYCRED_SLUG;
				if ( $type_id != MYCRED_DEFAULT_TYPE_KEY )
					$menu_slug = MYCRED_SLUG . '_' . $type_id;

				add_submenu_page(
					$menu_slug,
					__( 'Ranks', 'mycred' ),
					__( 'Ranks', 'mycred' ),
					$cap,
					'edit.php?post_type=mycred_rank&ctype=' . $type_id
				);

			}

		}

		/**
		 * Parent File
		 * @since 1.6
		 * @version 1.0.2
		 */
		public function parent_file( $parent = '' ) {

			global $pagenow;

			// When listing ranks, we need to indicate that we are under the appropriate point type menu
			if ( ( $pagenow == 'edit.php' || $pagenow == 'post-new.php' ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'mycred_rank' ) {
			
				if ( isset( $_GET['ctype'] ) && sanitize_key( $_GET['ctype'] ) != MYCRED_DEFAULT_TYPE_KEY )
					return 'mycred_' . sanitize_key( $_GET['ctype'] );

				return 'mycred';
			
			}

			// When editing a rank, we need to indicate that we are under the appropriate point type menu
			elseif ( $pagenow == 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'mycred_rank' ) {

				if ( isset( $_GET['ctype'] ) && $_GET['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
					return 'mycred_' . sanitize_key( $_GET['ctype'] );

				$point_type = get_post_meta( $_GET['post'], 'ctype', true );
				$point_type = sanitize_key( $point_type );

				if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
					return 'mycred_' . $point_type;

				return 'mycred';

			}

			return $parent;

		}

		/**
		 * Sub Parent File
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function subparent_file( $subparent = '', $parent = '' ) {

			global $pagenow;

			// When listing ranks, we need to highlight the "Ranks" submenu to indicate where we are
			if ( ( $pagenow == 'edit.php' || $pagenow == 'post-new.php' ) && isset( $_GET['post_type'] ) && $_GET['post_type'] == 'mycred_rank' ) {

				if ( isset( $_GET['ctype'] ) )
					return 'edit.php?post_type=mycred_rank&ctype=' . $_GET['ctype'];

				return 'edit.php?post_type=mycred_rank&ctype=' . MYCRED_DEFAULT_TYPE_KEY;
			
			}

			// When editing a rank, we need to highlight the "Ranks" submenu to indicate where we are
			elseif ( $pagenow == 'post.php' && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == 'mycred_rank' ) {

				if ( isset( $_GET['ctype'] ) )
					return 'edit.php?post_type=mycred_rank&ctype=' . $_GET['ctype'];

				$point_type = get_post_meta( $_GET['post'], 'ctype', true );
				$point_type = sanitize_key( $point_type );

				if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
					return 'edit.php?post_type=mycred_rank&ctype=' . $point_type;

				return 'edit.php?post_type=mycred_rank&ctype=' . MYCRED_DEFAULT_TYPE_KEY;

			}

			return $subparent;

		}

		/**
		 * Exclude Ranks from Publish Content Hook
		 * @since 1.3
		 * @version 1.0
		 */
		public function exclude_ranks( $excludes ) {

			$excludes[] = 'mycred_rank';
			return $excludes;

		}

		/**
		 * AJAX: Calculate Totals
		 * @since 1.2
		 * @version 1.3.2
		 */
		public function calculate_totals() {

			// Security
			check_ajax_referer( 'mycred-calc-totals', 'token' );

			$type = MYCRED_DEFAULT_TYPE_KEY;
			if ( isset( $_POST['ctype'] ) && array_key_exists( $_POST['ctype'], $this->point_types ) )
				$type = sanitize_text_field( $_POST['ctype'] );

			$balance_key = $type;
			$mycred      = mycred( $type );

			if ( $mycred->is_multisite && $GLOBALS['blog_id'] > 1 && ! $mycred->use_central_logging )
				$balance_key .= '_' . $GLOBALS['blog_id'];

			global $wpdb;

			// Get all users that have a balance. Excluded users will have no balance
			$users = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT user_id 
				FROM {$wpdb->usermeta} 
				WHERE meta_key = %s", $balance_key ) );

			$count = 0;
			if ( ! empty( $users ) ) {

				// Get the total for each user with a balance
				foreach ( $users as $user_id ) {

					$total = mycred_query_users_total( $user_id, $type );
					mycred_update_user_meta( $user_id, $type, '_total', $total );
					$count ++;

				}

			}

			wp_send_json( sprintf( __( 'Completed - Total of %d users effected', 'mycred' ), $count ) );

		}

		/**
		 * Balance Adjustment
		 * Check if users rank should change.
		 * @since 1.1
		 * @version 1.5.1
		 */
		public function balance_adjustment( $result, $request, $mycred ) {

			// If the result was declined
			if ( $result === false ) return $result;

			extract( $request );

			// If ranks for this type is based on total and this is not a admin adjustment
			if ( mycred_rank_based_on_total( $type ) && $amount < 0 && $ref != 'manual' )
				return $result;

			// Find users rank
			$rank = mycred_find_users_rank( $user_id, $type );

			// If users rank changed, save it now.
			if ( isset( $rank->rank_id ) && $rank->rank_id !== $rank->current_id )
				mycred_save_users_rank( $user_id, $rank->rank_id, $type );

			return $result;

		}

		/**
		 * Publishing Content
		 * Check if users rank should change.
		 * @since 1.1
		 * @version 1.4
		 */
		public function post_status_change( $new_status, $old_status, $post ) {

			global $mycred_ranks;

			// Only ranks please
			if ( $post->post_type != 'mycred_rank' ) return;

			$type = get_post_meta( $post->ID, 'ctype', true );
			if ( $type == '' ) {
				$type = MYCRED_DEFAULT_TYPE_KEY;
				update_post_meta( $post->ID, 'ctype', $type );
			}

			// Publishing rank
			if ( $new_status == 'publish' && $old_status != 'publish' )
				mycred_assign_ranks( $type );

			// Trashing of rank
			elseif ( $new_status == 'trash' && $old_status != 'trash' )
				mycred_assign_ranks( $type );

		}

		/**
		 * User Related Template Tags
		 * Adds support for ranks of custom point types.
		 * @since 1.6
		 * @version 1.0
		 */
		public function user_tags( $tags ) {

			$tags   = explode( '|', $tags );
			$tags[] = 'rank';
			$tags[] = 'rank_logo';

			foreach ( $this->point_types as $type_id => $label ) {

				if ( $type_id == MYCRED_DEFAULT_TYPE_KEY ) continue;
				$tags[] = 'rank_' . $type_id;
				$tags[] = 'rank_logo_' . $type_id;

			}

			return implode( '|', $tags );

		}

		/**
		 * Parse Rank
		 * Parses the %rank% and %rank_logo% template tags.
		 * @since 1.1
		 * @version 1.3
		 */
		public function parse_rank( $content, $user = '', $data = '' ) {

			// No rank no need to run
			if ( ! preg_match( '/(%rank[%|_])/', $content ) ) return $content;

			// User ID does not exist ( user no longer exists )
			if ( ! isset( $user->ID ) ) {
				foreach ( $this->point_types as $type_id => $label ) {

					if ( $type_id == MYCRED_DEFAULT_TYPE_KEY ) {
						$content = str_replace( '%rank%',      '', $content );
						$content = str_replace( '%rank_logo%', '', $content );
					}
					else {
						$content = str_replace( '%rank_' . $type_id . '%',      '', $content );
						$content = str_replace( '%rank_logo_' . $type_id . '%', '', $content );
					}

				}
			}

			// Got a user ID
			else {

				// Loop the point types and replace template tags
				foreach ( $this->point_types as $type_id => $label ) {

					$rank_id = mycred_get_users_rank_id( $user->ID, $type_id );
					if ( $rank_id === false ) {

						if ( $type_id == MYCRED_DEFAULT_TYPE_KEY ) {
							$content = str_replace( '%rank%',      '', $content );
							$content = str_replace( '%rank_logo%', '', $content );
						}
						else {
							$content = str_replace( '%rank_' . $type_id . '%',      '', $content );
							$content = str_replace( '%rank_logo_' . $type_id . '%', '', $content );
						}

					}
					else {

						if ( $type_id == MYCRED_DEFAULT_TYPE_KEY ) {
							$content = str_replace( '%rank%',      get_the_title( $rank_id ),        $content );
							$content = str_replace( '%rank_logo%', mycred_get_rank_logo( $rank_id ), $content );
						}
						else {
							$content = str_replace( '%rank_' . $type_id . '%',      get_the_title( $rank_id ),        $content );
							$content = str_replace( '%rank_logo_' . $type_id . '%', mycred_get_rank_logo( $rank_id ), $content );
						}

					}

				}
			}

			return $content;

		}

		/**
		 * Insert Rank In Profile Header
		 * @since 1.1
		 * @version 1.3
		 */
		public function insert_rank_header() {

			$output       = '';
			$user_id      = bp_displayed_user_id();
			$mycred_types = mycred_get_usable_types();

			foreach ( $mycred_types as $type_id ) {

				// Load type
				$mycred = mycred( $type_id );

				// No settings
				if ( ! isset( $mycred->rank['bb_location'] ) ) continue;

				// Not shown
				if ( ! in_array( $mycred->rank['bb_location'], array( 'top', 'both' ) ) || $mycred->rank['bb_template'] == '' ) continue;

				// Get rank (if user has one)
				$users_rank = mycred_get_users_rank_id( $user_id, $type_id );
				if ( $users_rank === false ) continue;

				// Parse template
				$template = $mycred->rank['bb_template'];
				$template = str_replace( '%rank_title%', get_the_title( $users_rank ), $template );
				$template = str_replace( '%rank_logo%',  mycred_get_rank_logo( $users_rank, 'full' ), $template );

				$template = $mycred->template_tags_general( $template );
				$template = '<div class="mycred-my-rank ' . $type_id . '">' . $template . '</div>';

				// Let others play
				$output .= apply_filters( 'mycred_bp_header_ranks_row', $template, $user_id, $users_rank, $mycred, $this );

			}

			if ( $output == '' ) return;

			echo '<div id="mycred-my-ranks">' . apply_filters( 'mycred_bp_rank_in_header', $output, $user_id, $this ) . '</div>';

		}

		/**
		 * Insert Rank In Profile Details
		 * @since 1.1
		 * @version 1.4
		 */
		public function insert_rank_profile() {

			$output       = '';
			$user_id      = bp_displayed_user_id();
			$mycred_types = mycred_get_usable_types();

			$count = 0;
			foreach ( $mycred_types as $type_id ) {

				// Load type
				$mycred = mycred( $type_id );

				// No settings
				if ( ! isset( $mycred->rank['bb_location'] ) ) continue;

				// Not shown
				if ( ! in_array( $mycred->rank['bb_location'], array( 'profile_tab', 'both' ) ) || $mycred->rank['bb_template'] == '' ) continue;

				// Get rank (if user has one)
				$users_rank = mycred_get_users_rank_id( $user_id, $type_id );
				if ( $users_rank === false ) continue;

				// Parse template
				$template = $mycred->rank['bb_template'];
				$template = str_replace( '%rank_title%', get_the_title( $users_rank ), $template );
				$template = str_replace( '%rank_logo%',  mycred_get_rank_logo( $users_rank ), $template );

				$template = $mycred->template_tags_general( $template );
				$template = '<div class="mycred-my-rank ' . $type_id . '">' . $template . '</div>';

				// Let others play
				$output .= apply_filters( 'mycred_bp_profile_ranks_row', $template, $user_id, $users_rank, $mycred, $this );
				$count ++;

			}

			if ( $output == '' ) return;

?>
<div class="bp-widget mycred-field">
	<table class="profile-fields">
		<tr id="mycred-users-rank">
			<td class="label"><?php if ( $count == 1 ) _e( 'Rank', 'mycred' ); else _e( 'Ranks', 'mycred' ); ?></td>
			<td class="data">
				<?php echo apply_filters( 'mycred_bp_rank_in_profile', $output, $user_id, $this ); ?>

			</td>
		</tr>
	</table>
</div>
<?php

		}

		/**
		 * Insert Rank In bbPress Reply
		 * @since 1.6
		 * @version 1.1
		 */
		public function insert_rank_bb_reply() {

			$output  = '';
			$user_id = bbp_get_reply_author_id();
			if ( $user_id == 0 ) return;

			$mycred_types = mycred_get_usable_types();

			foreach ( $mycred_types as $type_id ) {

				// Load type
				$mycred = mycred( $type_id );

				// No settings
				if ( ! isset( $mycred->rank['bp_location'] ) ) continue;

				// Not shown
				if ( ! in_array( $mycred->rank['bp_location'], array( 'reply', 'both' ) ) || $mycred->rank['bp_template'] == '' ) continue;

				// Get rank (if user has one
				$users_rank = mycred_get_users_rank_id( $user_id, $type_id );
				if ( $users_rank === false ) continue;

				// Parse template
				$template = $mycred->rank['bp_template'];
				$template = str_replace( '%rank_title%', get_the_title( $users_rank ), $template );
				$template = str_replace( '%rank_logo%',  mycred_get_rank_logo( $users_rank ), $template );

				$template = $mycred->template_tags_general( $template );
				$template = '<div class="mycred-my-rank ' . $type_id . '">' . $template . '</div>';

				// Let others play
				$output .= apply_filters( 'mycred_bb_reply_ranks_row', $template, $user_id, $users_rank, $mycred, $this );

			}

			if ( $output == '' ) return;

			echo '<div id="mycred-my-ranks">' . apply_filters( 'mycred_bb_rank_in_reply', $output, $user_id, $this ) . '</div>';

		}

		/**
		 * Insert Rank In bbPress Profile
		 * @since 1.6
		 * @version 1.0
		 */
		public function insert_rank_bb_profile() {

			$output       = '';
			$user_id      = bbp_get_displayed_user_id();
			$mycred_types = mycred_get_usable_types();

			foreach ( $mycred_types as $type_id => $label ) {

				// Load type
				$mycred = mycred( $type_id );

				// No settings
				if ( ! isset( $mycred->rank['bp_location'] ) ) continue;

				// Not shown
				if ( ! in_array( $mycred->rank['bp_location'], array( 'profile', 'both' ) ) || $mycred->rank['bp_template'] == '' ) continue;

				// Get rank (if user has one
				$users_rank = mycred_get_users_rank_id( $user_id, $type_id );
				if ( $users_rank === false ) continue;

				// Parse template
				$template = $mycred->rank['bp_template'];
				$template = str_replace( '%rank_title%', get_the_title( $users_rank ), $template );
				$template = str_replace( '%rank_logo%',  mycred_get_rank_logo( $users_rank ), $template );

				$template = $mycred->template_tags_general( $template );
				$template = '<div class="mycred-my-rank ' . $type_id . '">' . $template . '</div>';

				// Let others play
				$output .= apply_filters( 'mycred_bb_profile_ranks_row', $template, $user_id, $users_rank, $mycred, $this );

			}

			if ( $output == '' ) return;

			echo '<div id="mycred-my-ranks">' . apply_filters( 'mycred_bb_rank_in_profile', $output, $user_id, $this ) . '</div>';

		}

		/**
		 * Add Default Rank
		 * Adds the default "Newbie" rank and adds all non-exluded user to this rank.
		 * Note! This method is only called when there are zero ranks as this will create the new default rank.
		 * @since 1.1
		 * @version 1.2
		 */
		public function add_default_rank() {

			global $mycred_ranks;

			// If there are no ranks at all
			if ( ! mycred_have_ranks() ) {

				// Construct a new post
				$rank                = array();
				$rank['post_title']  = 'Newbie';
				$rank['post_type']   = 'mycred_rank';
				$rank['post_status'] = 'publish';

				// Insert new rank post
				$rank_id = wp_insert_post( $rank );

				// Update min and max values
				update_post_meta( $rank_id, 'mycred_rank_min', 0 );
				update_post_meta( $rank_id, 'mycred_rank_max', 9999999 );
				update_post_meta( $rank_id, 'ctype',           MYCRED_DEFAULT_TYPE_KEY );

				$mycred_ranks = 1;
				mycred_assign_ranks();

			}

		}

		/**
		 * Custom User Balance Content
		 * Inserts a users rank for each point type.
		 * @since 1.6
		 * @version 1.1
		 */
		public function custom_user_column_content( $balance, $user_id, $type ) {

			$rank = mycred_get_users_rank( $user_id, $type );
			if ( $rank !== false )
				$balance .= '<small style="display:block;">' . sprintf( '<strong>%s:</strong> %s', __( 'Rank', 'mycred' ), $rank->title ) . '</small>';

			else
				$balance .= '<small style="display:block;">' . sprintf( '<strong>%s:</strong> -', __( 'Rank', 'mycred' ) ) . '</small>';

			return $balance;

		}

		/**
		 * Show Rank in User Editor
		 * @since 1.7
		 * @version 1.2
		 */
		public function show_rank_in_user_editor( $point_type, $user, $data ) {

			if ( $data['excluded'] ) {
				echo '<div class="balance-desc current-rank">-</div>';
				return;
			}

			if ( ! mycred_have_ranks( $point_type ) ) {
				echo '<div class="balance-desc current-rank"><em>' . __( 'No ranks exists.', 'mycred' ) . '</em></div>';
				return;
			}

			$rank = mycred_get_users_rank( $user->ID, $point_type );
			$rank_title = '-';
			if ( isset( $rank->title ) )
				$rank_title = $rank->title;

			echo '<div class="balance-desc current-rank">' . sprintf( '<strong>%s:</strong> %s', __( 'Rank', 'mycred' ), $rank_title ) . '</div>';

		}

		/**
		 * Register Scripts & Styles
		 * @since 1.7
		 * @version 1.0
		 */
		public function scripts_and_styles() { }

		/**
		 * Enqueue Scripts & Styles
		 * @since 1.1
		 * @version 1.3.2
		 */
		public function enqueue_scripts() {

			$adjust_header = false;
			$screen        = get_current_screen();

			wp_register_script(
				'mycred-rank-tweaks',
				plugins_url( 'assets/js/tweaks.js', myCRED_RANKS ),
				array( 'jquery' ),
				myCRED_VERSION . '.1'
			);

			wp_register_script(
				'mycred-rank-management',
				plugins_url( 'assets/js/management.js', myCRED_RANKS ),
				array( 'jquery' ),
				myCRED_VERSION . '.1'
			);

			// Ranks List Page
			if ( strpos( 'edit-mycred_rank', $screen->id ) > -1 ) {

				wp_enqueue_style( 'mycred-admin' );

				if ( isset( $_GET['ctype'] ) && array_key_exists( $_GET['ctype'], $this->point_types ) ) :

					wp_localize_script(
						'mycred-rank-tweaks',
						'myCRED_Ranks',
						array(
							'rank_ctype' => $_GET['ctype']
						)
					);
					wp_enqueue_script( 'mycred-rank-tweaks' );

				endif;

			}

			// Edit Rank Page
			if ( strpos( 'mycred_rank', $screen->id ) > -1 ) {

				wp_dequeue_script( 'autosave' );
				wp_enqueue_style( 'mycred-bootstrap-grid' );
				wp_enqueue_style( 'mycred-forms' );

				add_filter( 'postbox_classes_mycred_rank_mycred-rank-setup', array( $this, 'metabox_classes' ) );

?>
<style type="text/css">
#misc-publishing-actions .misc-pub-curtime { display: none; }
#misc-publishing-actions #visibility { display: none; }
</style>
<?php

			}

			// Insert management script
			if ( in_array( substr( $screen->id, -9 ), array( '_settings', '-settings' ) ) ) {

				wp_localize_script(
					'mycred-rank-management',
					'myCRED_Ranks',
					array(
						'ajaxurl'        => admin_url( 'admin-ajax.php' ),
						'token'          => wp_create_nonce( 'mycred-management-actions-roles' ),
						'working'        => esc_attr__( 'Processing...', 'mycred' ),
						'confirm_del'    => esc_attr__( 'Warning! All ranks will be deleted! This can not be undone!', 'mycred' ),
						'confirm_assign' => esc_attr__( 'Are you sure you want to re-assign user ranks?', 'mycred' )
					)
				);
				wp_enqueue_script( 'mycred-rank-management' );

			}

		}

		/**
		 * Adjust Rank Sort Order
		 * Adjusts the wp query when viewing ranks to order by the min. point requirement.
		 * @since 1.1.1
		 * @version 1.2
		 */
		public function adjust_wp_query( $query ) {

			// Front End Queries
			if ( ! is_admin() ) {

				if ( ! is_post_type_archive( 'mycred_rank' ) ) return;

				// By default we want to only show ranks for the main point type
				if ( ! isset( $_GET['ctype'] ) && $query->is_main_query() ) {
					$query->set( 'meta_query', array(
						array(
							'key'     => 'ctype',
							'value'   => MYCRED_DEFAULT_TYPE_KEY,
							'compare' => '='
						)
					) );
				}

				// Otherwise if ctype is set and it is a point type filter the results
				elseif ( isset( $_GET['ctype'] ) && array_key_exists( $_GET['ctype'], $this->point_types ) ) {
					$query->set( 'meta_query', array(
						array(
							'key'     => 'ctype',
							'value'   => $_GET['ctype'],
							'compare' => '='
						)
					) );
				}

			}

			// Admin Queries
			else {

				if ( ! isset( $query->query['post_type'] ) || $query->query['post_type'] != 'mycred_rank' ) return;

				// If ctype is set, filter ranks according to it's value
				if ( isset( $_GET['ctype'] ) && array_key_exists( $_GET['ctype'], $this->point_types ) ) {
					$query->set( 'meta_query', array(
						array(
							'key'     => 'ctype',
							'value'   => $_GET['ctype'],
							'compare' => '='
						)
					) );
				}

			}

			// Sort by meta value
			$query->set( 'meta_key', 'mycred_rank_min' );
			$query->set( 'orderby',  'meta_value_num' );

			// Sort order
			if ( ! isset( $this->rank['order'] ) ) $this->rank['order'] = 'ASC';
			$query->set( 'order',    $this->rank['order'] );

		}

		/**
		 * Adjust Rank Column Header
		 * @since 1.1
		 * @version 1.2
		 */
		public function adjust_column_headers( $defaults ) {

			$columns       = array();
			$columns['cb'] = $defaults['cb'];

			// Add / Adjust
			$columns['title']             = __( 'Rank Title', 'mycred' );
			$columns['mycred-rank-logo']  = __( 'Logo', 'mycred' );
			$columns['mycred-rank-req']   = __( 'Requirement', 'mycred' );
			$columns['mycred-rank-users'] = __( 'Users', 'mycred' );

			if ( count( $this->point_types ) > 1 )
				$columns['mycred-rank-type']  = __( 'Point Type', 'mycred' );

			// Return
			return $columns;

		}

		/**
		 * Adjust Rank Column Content
		 * @since 1.1
		 * @version 1.1
		 */
		public function adjust_column_content( $column_name, $post_id ) {

			$type = get_post_meta( $post_id, 'ctype', true );
			if ( $type == '' )
				$type = MYCRED_DEFAULT_TYPE_KEY;

			// Rank Logo (thumbnail)
			if ( $column_name == 'mycred-rank-logo' ) {
				$logo = mycred_get_rank_logo( $post_id, 'thumbnail' );
				if ( empty( $logo ) )
					echo __( 'No Logo Set', 'mycred' );
				else
					echo $logo;

			}

			// Rank Requirement (custom metabox)
			elseif ( $column_name == 'mycred-rank-req' ) {

				$mycred = $this->core;
				if ( $type != MYCRED_DEFAULT_TYPE_KEY )
					$mycred = mycred( $type );

				$min = get_post_meta( $post_id, 'mycred_rank_min', true );
				if ( empty( $min ) && (int) $min !== 0 )
					$min = __( 'Any Value', 'mycred' );

				$min = $mycred->template_tags_general( __( 'Minimum %plural%', 'mycred' ) ) . ': ' . $min;
				$max = get_post_meta( $post_id, 'mycred_rank_max', true );
				if ( empty( $max ) )
					$max = __( 'Any Value', 'mycred' );

				$max = $mycred->template_tags_general( __( 'Maximum %plural%', 'mycred' ) ) . ': ' . $max;
				echo $min . '<br />' . $max;

			}

			// Rank Users (user list)
			elseif ( $column_name == 'mycred-rank-users' ) {

				echo mycred_count_users_with_rank( $post_id );

			}

			// Rank Point Type
			if ( $column_name == 'mycred-rank-type' ) {

				if ( isset( $this->point_types[ $type ] ) )
					echo $this->point_types[ $type ];
				else
					echo $this->core->plural();

			}

		}

		/**
		 * Adjust Row Actions
		 * @since 1.1
		 * @version 1.0
		 */
		public function adjust_row_actions( $actions, $post ) {

			if ( $post->post_type == 'mycred_rank' ) {
				unset( $actions['inline hide-if-no-js'] );

				if ( ! $this->rank['public'] )
					unset( $actions['view'] );
			}

			return $actions;

		}

		/**
		 * Adjust Enter Title Here
		 * @since 1.1
		 * @version 1.0
		 */
		public function enter_title_here( $title ) {

			global $post_type;
			if ( $post_type == 'mycred_rank' )
				return __( 'Rank Title', 'mycred' );

			return $title;

		}

		/**
		 * Add Meta Boxes
		 * @since 1.1
		 * @version 1.0
		 */
		public function add_metaboxes() {

			add_meta_box(
				'mycred-rank-setup',
				__( 'Rank Setup', 'mycred' ),
				array( $this, 'rank_settings' ),
				'mycred_rank',
				'normal',
				'high'
			);

		}

		/**
		 * Rank Settings Metabox
		 * @since 1.1
		 * @version 1.2.1
		 */
		public function rank_settings( $post ) {

			// Get type
			$type = get_post_meta( $post->ID, 'ctype', true );
			if ( $type == '' ) {
				$type = MYCRED_DEFAULT_TYPE_KEY;
				update_post_meta( $post->ID, 'ctype', $type );
			}

			// If a custom type has been requested via the URL
			if ( isset( $_REQUEST['ctype'] ) && ! empty( $_REQUEST['ctype'] ) )
				$type = sanitize_key( $_REQUEST['ctype'] );

			// Load the appropriate type object
			$mycred = $this->core;
			if ( $type != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $type );

			$rank = mycred_get_rank( $post->ID );

?>
<div class="form">
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
			<div class="row">
				<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<div class="form-group">
						<label for="mycred-rank-min"><?php _e( 'Minimum Balance Requirement', 'mycred' ); ?></label>
						<input type="text" name="mycred_rank[mycred_rank_min]" id="mycred-rank-min" class="form-control" value="<?php echo esc_attr( $rank->minimum ); ?>" />
					</div>
				</div>
				<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
					<div class="form-group">
						<label for="mycred-rank-max"><?php _e( 'Maximum Balance Requirement', 'mycred' ); ?></label>
						<input type="text" name="mycred_rank[mycred_rank_max]" id="mycred-rank-max" class="form-control" value="<?php echo esc_attr( $rank->maximum ); ?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">

					<?php if ( count( $this->point_types ) > 1 ) : ?>

					<div class="form-group">
						<label for="mycred-rank-point-type"><?php _e( 'Point Type', 'mycred' ); ?></label>
						<?php mycred_types_select_from_dropdown( 'mycred_rank[ctype]', 'mycred-rank-point-type', $type, false, '  class="form-control"' ); ?>
					</div>

					<?php else : ?>

					<div class="form-group">
						<p class="form-control-static"><?php echo $mycred->plural(); ?></p>
						<input type="hidden" name="mycred_rank[ctype]" value="mycred_default" />
					</div>

					<?php endif; ?>

				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
<?php

			// Get all published ranks for this type
			$all_ranks = mycred_get_ranks( 'publish', '-1', 'DESC', $type );

			if ( ! empty( $all_ranks ) ) {

				echo '<ul>';
				foreach ( $all_ranks as $rank ) {

					if ( $rank->minimum == '' ) $rank->minimum = __( 'Not Set', 'mycred' );
					if ( $rank->maximum == '' ) $rank->maximum = __( 'Not Set', 'mycred' );

					echo '<li><strong>' . $rank->title . '</strong> ' . $rank->minimum . ' - ' . $rank->maximum . '</li>';

				}
				echo '</ul>';

			}
			else {

				echo '<p>' . __( 'No ranks found', 'mycred' ) . '.</p>';

			}

?>
		</div>
	</div>
</div>
<?php

		}

		/**
		 * Save Rank Details
		 * @since 1.1
		 * @version 1.4.1
		 */
		public function save_rank( $post_id, $post ) {

			// Make sure fields exists
			if ( $post === NULL || ! current_user_can( $this->core->edit_creds_cap() ) || ! isset( $_POST['mycred_rank'] ) ) return;

			$changed    = 0;
			$point_type = sanitize_key( $_POST['mycred_rank']['ctype'] );
			if ( ! mycred_point_type_exists( $point_type ) )
				$point_type = MYCRED_DEFAULT_TYPE_KEY;

			update_post_meta( $post_id, 'ctype', $point_type );

			$mycred = $this->core;
			if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $point_type );

			foreach ( $_POST['mycred_rank'] as $meta_key => $meta_value ) {

				if ( $meta_key == 'ctype' ) continue;

				$new_value = sanitize_text_field( $meta_value );
				$new_value = $mycred->number( $new_value );
				update_post_meta( $post_id, $meta_key, $new_value );

			}

			mycred_assign_ranks( $point_type );

		}

		/**
		 * Add to General Settings
		 * @since 1.1
		 * @version 1.3.1
		 */
		public function after_general_settings( $mycred = NULL ) {

			$prefs             = $this->rank;
			$this->add_to_core = true;
			if ( $mycred->mycred_type != MYCRED_DEFAULT_TYPE_KEY ) {

				if ( ! isset( $mycred->rank ) )
					$prefs = $this->default_prefs;
				else
					$prefs = $mycred->rank;

				$this->option_id = $mycred->option_id;

			}

			if ( $prefs['base'] == 'current' )
				$box = 'display: none;';
			else
				$box = 'display: block;';

?>
<h4><span class="dashicons dashicons-admin-plugins static"></span><?php _e( 'Ranks', 'mycred' ); ?></h4>
<div class="body" style="display:none;">

	<?php if ( $mycred->mycred_type == MYCRED_DEFAULT_TYPE_KEY ) { ?>

	<label class="subheader" for="<?php echo $this->field_id( 'public' ); ?>"><?php _e( 'Rank Features', 'mycred' ); ?></label>
	<ol id="myCRED-rank-supports">
		<li>
			<label><input type="checkbox" value="1" checked="checked" disabled="disabled" /> <?php _e( 'Title', 'mycred' ); ?></label><br />
			<label><input type="checkbox" value="1" checked="checked" disabled="disabled" /> <?php echo $mycred->core->template_tags_general( __( '%plural% requirement', 'mycred' ) ); ?></label><br />
			<label><input type="checkbox" value="1" checked="checked" disabled="disabled" /> <?php _e( 'Rank Logo', 'mycred' ); ?></label><br />

			<label for="<?php echo $this->field_id( array( 'support' => 'content' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'support' => 'content' ) ); ?>" id="<?php echo $this->field_id( array( 'support' => 'content' ) ); ?>" <?php checked( $prefs['support']['content'], 1 ); ?> value="1" /> <?php _e( 'Content', 'mycred' ); ?></label><br />

			<label for="<?php echo $this->field_id( array( 'support' => 'excerpt' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'support' => 'excerpt' ) ); ?>" id="<?php echo $this->field_id( array( 'support' => 'excerpt' ) ); ?>" <?php checked( $prefs['support']['excerpt'], 1 ); ?> value="1" /> <?php _e( 'Excerpt', 'mycred' ); ?></label><br />

			<label for="<?php echo $this->field_id( array( 'support' => 'comments' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'support' => 'comments' ) ); ?>" id="<?php echo $this->field_id( array( 'support' => 'comments' ) ); ?>" <?php checked( $prefs['support']['comments'], 1 ); ?> value="1" /> <?php _e( 'Comments', 'mycred' ); ?></label><br />

			<label for="<?php echo $this->field_id( array( 'support' => 'page-attributes' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'support' => 'page-attributes' ) ); ?>" id="<?php echo $this->field_id( array( 'support' => 'page-attributes' ) ); ?>" <?php checked( $prefs['support']['page-attributes'], 1 ); ?> value="1" /> <?php _e( 'Page Attributes', 'mycred' ); ?></label><br />

			<label for="<?php echo $this->field_id( array( 'support' => 'custom-fields' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'support' => 'custom-fields' ) ); ?>" id="<?php echo $this->field_id( array( 'support' => 'custom-fields' ) ); ?>" <?php checked( $prefs['support']['custom-fields'], 1 ); ?> value="1" /> <?php _e( 'Custom Fields', 'mycred' ); ?></label>

		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( 'public' ); ?>"><?php _e( 'Public', 'mycred' ); ?></label>
	<ol id="myCRED-rank-public">
		<li>
			<input type="checkbox" name="<?php echo $this->field_name( 'public' ); ?>" id="<?php echo $this->field_id( 'public' ); ?>" <?php checked( $prefs['public'], 1 ); ?> value="1" />
			<label for="<?php echo $this->field_id( 'public' ); ?>"><?php _e( 'If you want to create a template archive for each rank, you must select to have ranks public. Defaults to disabled.', 'mycred' ); ?></label>
		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( 'slug' ); ?>"><?php _e( 'Archive URL', 'mycred' ); ?></label>
	<ol id="mycred-rank-archive-url">
		<li>
			<div class="h2"><?php bloginfo( 'url' ); ?>/ <input type="text" name="<?php echo $this->field_name( 'slug' ); ?>" id="<?php echo $this->field_id( 'slug' ); ?>" value="<?php echo esc_attr( $prefs['slug'] ); ?>" size="20" />/</div>
			<span class="description"><?php _e( 'Ignored if Ranks are not public', 'mycred' ); ?></span>
		</li>
	</ol>
	<label class="subheader" for="<?php echo $this->field_id( 'order' ); ?>"><?php _e( 'Display Order', 'mycred' ); ?></label>
	<ol id="myCRED-rank-order">
		<li>
			<select name="<?php echo $this->field_name( 'order' ); ?>" id="<?php echo $this->field_id( 'order' ); ?>">
<?php

			// Order added in 1.1.1
			$options = array(
				'ASC'  => __( 'Ascending - Lowest rank to highest', 'mycred' ),
				'DESC' => __( 'Descending - Highest rank to lowest', 'mycred' )
			);
			foreach ( $options as $option_value => $option_label ) {
				echo '<option value="' . $option_value . '"';
				if ( $prefs['order'] == $option_value ) echo ' selected="selected"';
				echo '>' . $option_label . '</option>';
			}

?>

			</select><br />
			<span class="description"><?php _e( 'Select in what order ranks should be displayed in your admin area and/or front if ranks are "Public"', 'mycred' ); ?></span>
		</li>
	</ol>

	<?php } ?>

	<label class="subheader" for="<?php echo $this->field_id( array( 'base' => 'current' ) ); ?>"><?php _e( 'Rank Basis', 'mycred' ); ?></label>
	<ol id="myCRED-rank-basis">
		<li>
			<input type="radio" name="<?php echo $this->field_name( 'base' ); ?>" id="<?php echo $this->field_id( array( 'base' => 'current' ) ); ?>"<?php checked( $prefs['base'], 'current' ); ?> value="current" /> <label for="<?php echo $this->field_id( array( 'base' => 'current' ) ); ?>"><?php _e( 'Users are ranked according to their current balance.', 'mycred' ); ?></label>
		</li>
		<li>
			<input type="radio" name="<?php echo $this->field_name( 'base' ); ?>" id="<?php echo $this->field_id( array( 'base' => 'total' ) ); ?>"<?php checked( $prefs['base'], 'total' ); ?> value="total" /> <label for="<?php echo $this->field_id( array( 'base' => 'total' ) ); ?>"><?php echo $mycred->core->template_tags_general( __( 'Users are ranked according to the total amount of %_plural% they have accumulated.', 'mycred' ) ); ?></label>
		</li>
	</ol>
	<div id="calc-total" style="<?php echo $box; ?>">
		<label class="subheader" for=""><?php _e( 'Calculate Totals', 'mycred' ); ?></label>
		<ol id="mycred-rank-calculate">
			<li>
				<p><?php _e( 'Use this button to calculate or recalculate your users totals. If not used, the users current balance will be used as a starting point.', 'mycred' ); ?><br /><?php _e( 'Once a users total has been calculated, they will be assigned to their appropriate roles. For this reason, it is highly recommended that you first setup your ranks!', 'mycred' ); ?></p>
				<p><strong><?php _e( 'Depending on your log size and number of users this process may take a while. Please do not leave, click "Update Settings" or re-fresh this page until this is completed!', 'mycred' ); ?></strong></p>
				<input type="button" name="mycred-update-totals" data-type="<?php echo $mycred->mycred_type; ?>" id="mycred-update-totals" value="<?php _e( 'Calculate Totals', 'mycred' ); ?>" class="button button-large button-<?php if ( $prefs['base'] == 'current' ) echo 'secondary'; else echo 'primary'; ?>"<?php if ( $prefs['base'] == 'current' ) echo ' disabled="disabled"'; ?> />
			</li>
		</ol>
	</div>
<?php

			// BuddyPress
			if ( class_exists( 'BuddyPress' ) ) {

				if ( ! isset( $prefs['bb_location'] ) )
					$prefs['bb_location'] = '';

				if ( ! isset( $prefs['bb_template'] ) )
					$prefs['bb_template'] = 'Rank: %rank_title%';

				$rank_locations = array(
					''            => __( 'Do not show.', 'mycred' ),
					'top'         => __( 'Include in Profile Header.', 'mycred' ),
					'profile_tab' => __( 'Include under the "Profile" tab', 'mycred' ),
					'both'        => __( 'Include under the "Profile" tab and Profile Header.', 'mycred' )
				);

?>
	<label class="subheader" for="<?php echo $this->field_id( 'bb_location' ); ?>"><?php _e( 'Rank in BuddyPress', 'mycred' ); ?></label>
	<ol id="myCRED-rank-bb-location">
		<li>
			<select name="<?php echo $this->field_name( 'bb_location' ); ?>" id="<?php echo $this->field_id( 'bb_location' ); ?>">
<?php

				foreach ( $rank_locations as $value => $label ) {
					echo '<option value="' . $value . '"';
					if ( $prefs['bb_location'] == $value ) echo ' selected="selected"';
					echo '>' . $label . '</option>';
				} ?>

			</select>
		</li>
		<li>
			<label for="<?php echo $this->field_id( 'bb_template' ); ?>"><?php _e( 'Template', 'mycred' ); ?></label>
			<textarea name="<?php echo $this->field_name( 'bb_template' ); ?>" id="<?php echo $this->field_id( 'bb_template' ); ?>" rows="5" cols="50" class="large-text code"><?php echo esc_attr( $prefs['bb_template'] ); ?></textarea>
			<span class="description"><?php _e( 'Template to use when showing a users Rank in BuddyPress. Use %rank_title% for the title and %rank_logo% to show the rank logo. HTML is allowed.', 'mycred' ); ?></span>
		</li>
	</ol>
<?php

			}
			else {
				echo '<input type="hidden" name="' . $this->field_name( 'bb_location' ) . '" value="" />';
				echo '<input type="hidden" name="' . $this->field_name( 'bb_template' ) . '" value="Rank: %rank_title%" />';
			}

			// bbPress
			if ( class_exists( 'bbPress' ) ) {

				if ( ! isset( $prefs['bp_location'] ) )
					$prefs['bp_location'] = '';

				if ( ! isset( $prefs['bp_template'] ) )
					$prefs['bp_template'] = 'Rank: %rank_title%';

				$rank_locations = array(
					''        => __( 'Do not show.', 'mycred' ),
					'reply'   => __( 'Include in Topic Replies', 'mycred' ),
					'profile' => __( 'Include in Profile', 'mycred' ),
					'both'    => __( 'Include in Topic Replies and Profile', 'mycred' )
				);

?>
	<label class="subheader" for="<?php echo $this->field_id( 'bp_location' ); ?>"><?php _e( 'Rank in bbPress', 'mycred' ); ?></label>
	<ol id="myCRED-rank-bp-location">
		<li>
			<select name="<?php echo $this->field_name( 'bp_location' ); ?>" id="<?php echo $this->field_id( 'bp_location' ); ?>">
<?php

				foreach ( $rank_locations as $value => $label ) {
					echo '<option value="' . $value . '"';
					if ( $prefs['bp_location'] == $value ) echo ' selected="selected"';
					echo '>' . $label . '</option>';
				} ?>

			</select>
		</li>
		<li>
			<label for="<?php echo $this->field_id( 'bp_template' ); ?>"><?php _e( 'Template', 'mycred' ); ?></label>
			<textarea name="<?php echo $this->field_name( 'bp_template' ); ?>" id="<?php echo $this->field_id( 'bp_template' ); ?>" rows="5" cols="50" class="large-text code"><?php echo esc_attr( $prefs['bp_template'] ); ?></textarea>
			<span class="description"><?php _e( 'Template to use when showing a users Rank in BuddyPress. Use %rank_title% for the title and %rank_logo% to show the rank logo. HTML is allowed.', 'mycred' ); ?></span>
		</li>
	</ol>
<?php

			}
			else {
				echo '<input type="hidden" name="' . $this->field_name( 'bp_location' ) . '" value="" />';
				echo '<input type="hidden" name="' . $this->field_name( 'bp_template' ) . '" value="Rank: %rank_title%" />';
			}

?>
<script type="text/javascript">
jQuery(function($){

	$( 'input[name="<?php echo $this->field_name( 'base' ); ?>"]' ).change(function(){
		var basis = $(this).val();
		var button = $('#mycred-update-totals');
		// Update
		if ( basis != 'total' ) {
			$( '#calc-total' ).hide();
			button.attr( 'disabled', 'disabled' );
			button.removeClass( 'button-primary' );
			button.addClass( 'button-seconday' );
		}
		else {
			$( '#calc-total' ).show();
			button.removeAttr( 'disabled' );
			button.removeClass( 'button-seconday' );
			button.addClass( 'button-primary' );
		}
	});

	var mycred_calc = function( button, pointtype ) {
		$.ajax({
			type       : "POST",
			data       : {
				action    : 'mycred-calc-totals',
				token     : '<?php echo wp_create_nonce( 'mycred-calc-totals' ); ?>',
				ctype     : pointtype
			},
			dataType   : "JSON",
			url        : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			// Before we start
			beforeSend : function() {
				button.attr( 'disabled', 'disabled' );
				button.removeClass( 'button-primary' );
				button.addClass( 'button-seconday' );
				button.val( '<?php echo esc_js( esc_attr__( 'Processing...', 'mycred' ) ); ?>' );
			},
			// On Successful Communication
			success    : function( data ) {
				button.val( data );
			},
			// Error (sent to console)
			error      : function( jqXHR, textStatus, errorThrown ) {
				// Debug - uncomment to use
				//console.log( jqXHR );

				button.removeAttr( 'disabled' );
				button.removeClass( 'button-seconday' );
				button.addClass( 'button-primary' );
				button.val( '<?php echo esc_js( esc_attr__( 'Script Communication Error', 'mycred' ) ); ?>' );
			}
		});
	};

	$( 'input#mycred-update-totals' ).click(function(){
		mycred_calc( $(this), $(this).data( 'type' ) );
	});
});
</script>
</div>
<?php

		}

		/**
		 * Save Settings
		 * @since 1.1
		 * @version 1.3.1
		 */
		public function sanitize_extra_settings( $new_data, $data, $core ) {

			$new_data['rank']['support']['content']         = ( isset( $data['rank']['support']['content'] ) ) ? true : false;
			$new_data['rank']['support']['excerpt']         = ( isset( $data['rank']['support']['excerpt'] ) ) ? true : false;
			$new_data['rank']['support']['comments']        = ( isset( $data['rank']['support']['comments'] ) ) ? true : false;
			$new_data['rank']['support']['page-attributes'] = ( isset( $data['rank']['support']['page-attributes'] ) ) ? true : false;
			$new_data['rank']['support']['custom-fields']   = ( isset( $data['rank']['support']['custom-fields'] ) ) ? true : false;

			$new_data['rank']['base']                       = sanitize_text_field( $data['rank']['base'] );
			$new_data['rank']['public']                     = ( isset( $data['rank']['public'] ) ) ? true : false;
			$new_data['rank']['slug']                       = ( isset( $data['rank']['slug'] ) ) ? sanitize_text_field( $data['rank']['slug'] ) : '';
			$new_data['rank']['order']                      = ( isset( $data['rank']['order'] ) ) ? sanitize_text_field( $data['rank']['order'] ) : '';

			$allowed_tags = $this->core->allowed_html_tags();
			$new_data['rank']['bb_location']                = sanitize_text_field( $data['rank']['bb_location'] );
			$new_data['rank']['bb_template']                = wp_kses( $data['rank']['bb_template'], $allowed_tags );
			$new_data['rank']['bp_location']                = sanitize_text_field( $data['rank']['bp_location'] );
			$new_data['rank']['bp_template']                = wp_kses( $data['rank']['bp_template'], $allowed_tags );

			return $new_data;

		}

		/**
		 * Management
		 * @since 1.3.2
		 * @version 1.1
		 */
		public function rank_management( $mycred ) {

			$count = mycred_get_published_ranks_count( $mycred->mycred_type );

			$reset_block = false;
			if ( $count == 0 || $count === false )
				$reset_block = true;

			$rank_meta_key = 'mycred_rank';
			if ( $this->core->is_multisite && $GLOBALS['blog_id'] > 1 && ! $this->core->use_master_template )
				$rank_meta_key .= '_' . $GLOBALS['blog_id'];

			if ( $mycred->mycred_type != MYCRED_DEFAULT_TYPE_KEY )
				$rank_meta_key .= $mycred->mycred_type;

?>
<label class="subheader"><?php _e( 'Ranks', 'mycred' ); ?></label>
<ol id="myCRED-rank-actions" class="inline">
	<li>
		<label><?php _e( 'User Meta Key', 'mycred' ); ?></label>
		<div class="h2"><input type="text" id="mycred-rank-post-type" disabled="disabled" value="<?php echo $rank_meta_key; ?>" class="readonly" /></div>
	</li>
	<li>
		<label><?php _e( 'No. of ranks', 'mycred' ); ?></label>
		<div class="h2"><input type="text" id="mycred-ranks-no-of-ranks" disabled="disabled" value="<?php echo $count; ?>" class="readonly short" /></div>
	</li>
	<li>
		<label><?php _e( 'Actions', 'mycred' ); ?></label>
		<div class="h2"><input type="button" id="mycred-manage-action-reset-ranks" data-type="<?php echo $mycred->mycred_type; ?>" value="<?php _e( 'Remove All Ranks', 'mycred' ); ?>" class="button button-large large <?php if ( $reset_block ) echo '" disabled="disabled'; else echo 'button-primary'; ?>" /> <input type="button" id="mycred-manage-action-assign-ranks" data-type="<?php echo $mycred->mycred_type; ?>" value="<?php _e( 'Assign Ranks to Users', 'mycred' ); ?>" class="button button-large large <?php if ( $reset_block ) echo '" disabled="disabled'; ?>" /></div>
	</li>
</ol>
<?php

		}

		/**
		 * Zero Balance Action
		 * When an admin selects to zero out all balances
		 * we want to remove all ranks as well.
		 * @since 1.6
		 * @version 1.0
		 */
		public function zero_balance_action( $type = '' ) {

			global $wpdb;

			// Get rank key
			$rank_meta_key = 'mycred_rank';
			if ( $this->core->is_multisite && $GLOBALS['blog_id'] > 1 && ! $this->core->use_master_template )
				$rank_meta_key .= '_' . $GLOBALS['blog_id'];

			if ( array_key_exists( $type, $this->point_types ) && $type != MYCRED_DEFAULT_TYPE_KEY )
				$rank_meta_key .= $type;

			$wpdb->delete(
				$wpdb->usermeta,
				array( 'meta_key' => $rank_meta_key ),
				array( '%s' )
			);

		}

		/**
		 * Delete Ranks
		 * @since 1.3.2
		 * @version 1.1
		 */
		public function action_delete_ranks() {

			// Security
			check_ajax_referer( 'mycred-management-actions-roles', 'token' );

			// Define type
			$type = MYCRED_DEFAULT_TYPE_KEY;
			if ( isset( $_POST['ctype'] ) && array_key_exists( $_POST['ctype'], $this->point_types ) )
				$type = sanitize_text_field( $_POST['ctype'] );

			global $wpdb;

			// Get the appropriate tables based on setup
			if ( ! mycred_override_settings() ) {
				$posts = $wpdb->posts;
				$postmeta = $wpdb->postmeta;
			}
			else {
				$posts = $wpdb->base_prefix . 'posts';
				$postmeta = $wpdb->base_prefix . 'postmeta';
			}

			// First get the ids of all existing ranks
			$rank_ids = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT ranks.ID 
				FROM {$posts} ranks 
				INNER JOIN {$postmeta} ctype 
					ON ( ranks.ID = ctype.post_id AND ctype.meta_key = %s )
				WHERE ranks.post_type = %s
				AND ctype.meta_value = %s;", 'ctype', 'mycred_rank', $type ) );

			// If ranks were found
			$rows = 0;
			if ( ! empty( $rank_ids ) ) {

				$id_list = implode( ',', $rank_ids );

				// Remove posts
				$wpdb->query( "
					DELETE FROM {$posts} 
					WHERE post_type = 'mycred_rank'
					AND post_id IN ({$id_list});" );

				// Remove post meta
				$wpdb->query( "
					DELETE FROM {$postmeta} 
					WHERE post_id IN ({$id_list});" );

				// Confirm that ranks are gone by counting ranks
				// If all went well this should return zero.
				$rows = $wpdb->get_var( $wpdb->prepare( "
					SELECT COUNT(*) 
					FROM {$posts} ranks 
					INNER JOIN {$postmeta} ctype 
						ON ( ranks.ID = ctype.post_id AND ctype.meta_key = %s )
					WHERE ranks.post_type = %s
					AND ctype.meta_value = %s;", 'ctype', 'mycred_rank', $type ) );

			}

			die( json_encode( array( 'status' => 'OK', 'rows' => $rows ) ) );

		}

		/**
		 * Assign Ranks
		 * @since 1.3.2
		 * @version 1.1
		 */
		public function action_assign_ranks() {

			check_ajax_referer( 'mycred-management-actions-roles', 'token' );

			$type = MYCRED_DEFAULT_TYPE_KEY;
			if ( isset( $_POST['ctype'] ) && array_key_exists( $_POST['ctype'], $this->point_types ) )
				$type = sanitize_text_field( $_POST['ctype'] );

			$adjustments = mycred_assign_ranks( $type );
			die( json_encode( array( 'status' => 'OK', 'rows' => $adjustments ) ) );

		}

	}
endif;

/**
 * Load Ranks Module
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_load_ranks_addon' ) ) :
	function mycred_load_ranks_addon( $modules, $point_types ) {

		$modules['solo']['ranks'] = new myCRED_Ranks_Module();
		$modules['solo']['ranks']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_ranks_addon', 80, 2 );

?>