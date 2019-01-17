<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Settings class
 * @see http://codex.mycred.me/classes/mycred_settings/
 * @since 0.1
 * @version 1.5.1
 */
if ( ! class_exists( 'myCRED_Settings' ) ) :
	class myCRED_Settings {

		public $core;
		public $log_table;
		public $cred_id;

		public $is_multisite        = false;
		public $use_master_template = false;
		public $use_central_logging = false;

		/**
		 * Construct
		 */
		function __construct( $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

			// Prep
			$this->is_multisite        = is_multisite();
			$this->use_master_template = mycred_override_settings();
			$this->use_central_logging = mycred_centralize_log();

			if ( ! is_string( $point_type ) || sanitize_key( $point_type ) == '' || $point_type === NULL )
				$point_type = MYCRED_DEFAULT_TYPE_KEY;

			$this->cred_id = $point_type;

			// Load Settings
			$option_id = 'mycred_pref_core';
			if ( $this->cred_id != MYCRED_DEFAULT_TYPE_KEY )
				$option_id .= '_' . $this->cred_id;

			$this->core = mycred_get_option( $option_id, $this->defaults() );

			if ( $this->core !== false ) {
				foreach ( (array) $this->core as $key => $value ) {
					$this->$key = $value;
				}
			}

			if ( defined( 'MYCRED_LOG_TABLE' ) )
				$this->log_table = MYCRED_LOG_TABLE;

			else {

				global $wpdb;

				if ( $this->is_multisite && $this->use_central_logging )
					$this->log_table = $wpdb->base_prefix . 'myCRED_log';
				else
					$this->log_table = $wpdb->prefix . 'myCRED_log';

			}

			do_action_ref_array( 'mycred_settings', array( &$this ) );

		}

		/**
		 * Default Settings
		 * @since 1.3
		 * @version 1.0
		 */
		public function defaults() {

			return array(
				'cred_id'   => MYCRED_DEFAULT_TYPE_KEY,
				'format'    => array(
					'type'       => 'bigint',
					'decimals'   => 0,
					'separators' => array(
						'decimal'   => '.',
						'thousand'  => ','
					)
				),
				'name'      => array(
					'singular' => __( 'Point', 'mycred' ),
					'plural'   => __( 'Points', 'mycred' )
				),
				'before'    => '',
				'after'     => '',
				'caps'      => array(
					'plugin'   => 'manage_options',
					'creds'    => 'export'
				),
				'max'       => 0,
				'exclude'   => array(
					'plugin_editors' => 0,
					'cred_editors'   => 0,
					'list'           => ''
				),
				'frequency' => array(
					'rate'     => 'always',
					'date'     => ''
				),
				'delete_user' => 0
			);

		}

		/**
		 * Singular myCRED name
		 * @since 0.1
		 * @version 1.1
		 */
		public function singular() {

			if ( ! isset( $this->core['name']['singular'] ) )
				return $this->name['singular'];

			return $this->core['name']['singular'];

		}

		/**
		 * Plural myCRED name
		 * @since 0.1
		 * @version 1.1
		 */
		public function plural() {

			if ( ! isset( $this->core['name']['plural'] ) )
				return $this->name['plural'];

			return $this->core['name']['plural'];

		}

		/**
		 * Zero
		 * Returns zero formated with or without decimals.
		 * @since 1.3
		 * @version 1.0
		 */
		public function zero() {

			if ( ! isset( $this->format['decimals'] ) )
				$decimals = $this->core['format']['decimals'];

			else
				$decimals = $this->format['decimals'];

			return number_format( 0, $decimals );

		}

		/**
		 * Number
		 * Returns a given creds formated either as a float with the set number of decimals or as a integer.
		 * This function should be used when you need to make sure the variable is returned in correct format
		 * but without any custom layout you might have given your creds.
		 *
		 * @param $number (int|float) the initial number
		 * @returns the given number formated either as an integer or float
		 * @since 0.1
		 * @version 1.2
		 */
		public function number( $number = '' ) {

			if ( $number === '' ) return $number;

			$number = str_replace( '+', '', $number );

			if ( ! isset( $this->format['decimals'] ) )
				$decimals = (int) $this->core['format']['decimals'];

			else
				$decimals = (int) $this->format['decimals'];

			if ( $decimals > 0 )
				return number_format( (float) $number, $decimals, '.', '' );

			return (int) $number;

		}

		/**
		 * Format Number
		 * Returns a given creds formated with set decimal and thousands separator and either as a float with
		 * the set number of decimals or as a integer. This function should be used when you want to display creds
		 * formated according to your settings. Do not use this function when adding/removing points!
		 *
		 * @param $number (int|float) the initial number
		 * @returns the given number formated either as an integer or float
		 * @filter 'mycred_format_number'
		 * @since 0.1
		 * @version 1.1
		 */
		public function format_number( $number = '' ) {

			if ( $number === '' ) return $number;

			$number   = $this->number( $number );
			$decimals = $this->format['decimals'];
			$sep_dec  = $this->format['separators']['decimal'];
			$sep_tho  = $this->format['separators']['thousand'];

			// Format
			$creds = number_format( $number, (int) $decimals, $sep_dec, $sep_tho );

			return apply_filters( 'mycred_format_number', $creds, $number, $this->core );

		}

		/**
		 * Format Creds
		 * Returns a given number formated with prefix and/or suffix along with any custom presentation set.
		 *
		 * @param $creds (int|float) number of creds
		 * @param $before (string) optional string to insert before the number
		 * @param $after (string) optional string to isnert after the number
		 * @param $force_in (boolean) option to force $before after prefix and $after before suffix
		 * @filter 'mycred_format_creds'
		 * @returns formated string
		 * @since 0.1
		 * @version 1.0
		 */
		public function format_creds( $creds = 0, $before = '', $after = '', $force_in = false ) {

			// Prefix
			$prefix = '';
			if ( ! empty( $this->before ) )
				$prefix = $this->before . ' ';

			// Suffix
			$suffix = '';
			if ( ! empty( $this->after ) )
				$suffix = ' ' . $this->after;

			// Format creds
			$creds = $this->format_number( $creds );

			// Optional extras to insert before and after
			if ( $force_in )
				$layout = $prefix . $before . $creds . $after . $suffix;

			else
				$layout = $before . $prefix . $creds . $suffix . $after;

			return apply_filters( 'mycred_format_creds', $layout, $creds, $this );

		}

		/**
		 * Round Value
		 * Will round a given value either up or down with the option to use precision.
		 *
		 * @param $amount (int|float) required amount to round
		 * @param $up_down (string|boolean) choice of rounding up or down. using false bypasses this function
		 * @param $precision (int) the optional number of decimal digits to round to. defaults to 0
		 * @returns rounded int or float
		 * @since 0.1
		 * @version 1.1
		 */
		public function round_value( $amount = 0, $up_down = false, $precision = 0 ) {

			if ( $amount == 0 || ! $up_down ) return $amount;

			// Use round() for precision
			if ( $precision !== false ) {

				if ( $up_down == 'up' )
					$_amount = round( $amount, (int) $precision, PHP_ROUND_HALF_UP );

				elseif ( $up_down == 'down' )
					$_amount = round( $amount, (int) $precision, PHP_ROUND_HALF_DOWN );

			}

			// Use ceil() or floor() for everything else
			else {

				if ( $up_down == 'up' )
					$_amount = ceil( $amount );

				elseif ( $up_down == 'down' )
					$_amount = floor( $amount );

			}

			return apply_filters( 'mycred_round_value', $_amount, $amount, $up_down, $precision );

		}

		/**
		 * Get Lowest Value
		 * Returns the lowest point value available based on the number of decimal places
		 * we use. So with 1 decimal = 0.1, 2 decimals 0.01 etc. Defaults to 1.
		 * @since 1.7
		 * @version 1.1
		 */
		public function get_lowest_value() {

			$lowest   = 1;
			$decimals = $this->format['decimals'] - 1;

			if ( $decimals > 0 ) {

				$lowest = '0.' . str_repeat( '0', $decimals ) . '1';
				$lowest = (float) $lowest;

			}

			return $lowest;

		}

		/**
		 * Apply Exchange Rate
		 * Applies a given exchange rate to the given amount.
		 * 
		 * @param $amount (int|float) the initial amount
		 * @param $rate (int|float) the exchange rate to devide by
		 * @param $round (bool) option to round values, defaults to yes.
		 * @since 0.1
		 * @version 1.3
		 */
		public function apply_exchange_rate( $amount = 0, $rate = 1, $round = true ) {

			if ( ! is_numeric( $rate ) || $rate == 1 ) return $amount;

			$exchange = $amount/(float) $rate;
			if ( $round ) $exchange = round( $exchange );

			return apply_filters( 'mycred_apply_exchange_rate', $exchange, $amount, $rate, $round );

		}

		/**
		 * Parse Template Tags
		 * Parses template tags in a given string by checking for the 'ref_type' array key under $log_entry->data.
		 * @since 0.1
		 * @version 1.0
		 */
		public function parse_template_tags( $content = '', $log_entry ) {

			// Prep
			$reference = $log_entry->ref;
			$ref_id    = $log_entry->ref_id;
			$data      = $log_entry->data;

			// Unserialize if serialized
			$data      = maybe_unserialize( $data );

			// Run basic template tags first
			$content   = $this->template_tags_general( $content );

			// Start by allowing others to play
			$content   = apply_filters( 'mycred_parse_log_entry',              $content, $log_entry );
			$content   = apply_filters( "mycred_parse_log_entry_{$reference}", $content, $log_entry );

			// Get the reference type
			if ( isset( $data['ref_type'] ) || isset( $data['post_type'] ) ) {

				if ( isset( $data['ref_type'] ) )
					$type = $data['ref_type'];

				elseif ( isset( $data['post_type'] ) )
					$type = $data['post_type'];

				if ( $type == 'post' )
					$content = $this->template_tags_post( $content, $ref_id, $data );

				elseif ( $type == 'user' )
					$content = $this->template_tags_user( $content, $ref_id, $data );

				elseif ( $type == 'comment' )
					$content = $this->template_tags_comment( $content, $ref_id, $data );

				$content = apply_filters( "mycred_parse_tags_{$type}", $content, $log_entry );

			}

			return $content;

		}

		/**
		 * General Template Tags
		 * Replaces the general template tags in a given string.
		 * @since 0.1
		 * @version 1.2
		 */
		public function template_tags_general( $content = '' ) {

			$content = apply_filters( 'mycred_parse_tags_general', $content );

			// Singular
			$content = str_replace( array( '%singular%', '%Singular%' ), $this->singular(), $content );
			$content = str_replace( '%_singular%',       strtolower( $this->singular() ), $content );

			// Plural
			$content = str_replace(  array( '%plural%', '%Plural%' ), $this->plural(), $content );
			$content = str_replace( '%_plural%',         strtolower( $this->plural() ), $content );

			// Login URL
			$content = str_replace( '%login_url%',       wp_login_url(), $content );
			$content = str_replace( '%login_url_here%',  wp_login_url( get_permalink() ), $content );

			// Logout URL
			$content = str_replace( '%logout_url%',      wp_logout_url(), $content );
			$content = str_replace( '%logout_url_here%', wp_logout_url( get_permalink() ), $content );

			// Blog Related
			if ( preg_match( '%(num_members|blog_name|blog_url|blog_info|admin_email)%', $content, $matches ) ) {
				$content = str_replace( '%num_members%',     $this->count_members(), $content );
				$content = str_replace( '%blog_name%',       get_bloginfo( 'name' ), $content );
				$content = str_replace( '%blog_url%',        get_bloginfo( 'url' ), $content );
				$content = str_replace( '%blog_info%',       get_bloginfo( 'description' ), $content );
				$content = str_replace( '%admin_email%',     get_bloginfo( 'admin_email' ), $content );
			}

			return $content;

		}

		/**
		 * Amount Template Tags
		 * Replaces the amount template tags in a given string.
		 * @since 0.1
		 * @version 1.0.3
		 */
		public function template_tags_amount( $content = '', $amount = 0 ) {

			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'amount', 'cred|cred_f', $content ) ) return $content;

			$content = apply_filters( 'mycred_parse_tags_amount', $content, $amount, $this );
			$content = str_replace( '%cred_f%', $this->format_creds( $amount ), $content );
			$content = str_replace( '%cred%',   $amount, $content );

			return $content;

		}

		/**
		 * Post Related Template Tags
		 * Replaces the post related template tags in a given string.
		 *
		 * @param $content (string) string containing the template tags
		 * @param $ref_id (int) required post id as reference id
		 * @param $data (object) Log entry data object
		 * @return (string) parsed string
		 * @since 0.1
		 * @version 1.0.4
		 */
		public function template_tags_post( $content = '', $ref_id = NULL, $data = '' ) {

			if ( $ref_id === NULL ) return $content;

			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'post', 'post_title|post_url|link_with_title|post_type', $content ) ) return $content;

			// Get Post Object
			$post = get_post( $ref_id );

			// Post does not exist
			if ( $post === NULL ) {

				if ( ! is_array( $data ) || ! array_key_exists( 'ID', $data ) ) return $content;

				$post = new StdClass();
				foreach ( $data as $key => $value ) {
					if ( $key == 'post_title' ) $value .= ' (' . __( 'Deleted', 'mycred' ) . ')';
					$post->$key = $value;
				}

				$url = get_permalink( $post->ID );
				if ( empty( $url ) ) $url = '#item-has-been-deleted';

			}
			else {

				$url = get_permalink( $post->ID );

			}

			// Let others play first
			$content = apply_filters( 'mycred_parse_tags_post', $content, $post, $data );

			// Replace template tags
			$content = str_replace( '%post_title%',      get_the_title( $post ), $content );
			$content = str_replace( '%post_url%',        $url, $content );
			$content = str_replace( '%link_with_title%', '<a href="' . $url . '">' . $post->post_title . '</a>', $content );

			$post_type = get_post_type_object( $post->post_type );
			if ( $post_type !== NULL ) {
				$content = str_replace( '%post_type%', $post_type->labels->singular_name, $content );
				unset( $post_type );
			}

			return $content;

		}

		/**
		 * User Related Template Tags
		 * Replaces the user related template tags in the given string.
		 *
		 * @param $content (string) string containing the template tags
		 * @param $ref_id (int) required user id as reference id
		 * @param $data (object) Log entry data object
		 * @return (string) parsed string
		 * @since 0.1
		 * @version 1.3.1
		 */
		public function template_tags_user( $content = '', $ref_id = NULL, $data = '' ) {

			if ( $ref_id === NULL ) return $content;

			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'user', 'user_id|user_name|user_name_en|display_name|user_profile_url|user_profile_link|user_nicename|user_email|user_url|balance|balance_f', $content ) ) return $content;

			// Get User Object
			if ( $ref_id !== false )
				$user = get_userdata( $ref_id );

			// User object is passed on though $data
			elseif ( $ref_id === false && is_object( $data ) && isset( $data->ID ) )
				$user = $data;

			// User array is passed on though $data
			elseif ( $ref_id === false && is_array( $data ) || array_key_exists( 'ID', (array) $data ) ) {

				$user = new StdClass();
				foreach ( $data as $key => $value ) {

					if ( $key == 'login' )
						$user->user_login = $value;

					else
						$user->$key = $value;

				}

			}

			else return $content;

			// Let others play first
			$content     = apply_filters( 'mycred_parse_tags_user', $content, $user, $data );

			if ( ! isset( $user->ID ) ) return $content;

			// Replace template tags
			$content     = str_replace( '%user_id%',            $user->ID, $content );
			$content     = str_replace( '%user_name%',          $user->user_login, $content );
			$content     = str_replace( '%user_name_en%',       urlencode( $user->user_login ), $content );

			$profile_url = mycred_get_users_profile_url( $user->ID );

			$content     = str_replace( '%display_name%',       $user->display_name, $content );
			$content     = str_replace( '%user_profile_url%',   $profile_url, $content );
			$content     = str_replace( '%user_profile_link%',  '<a href="' . $profile_url . '">' . $user->display_name . '</a>', $content );

			$content     = str_replace( '%user_nicename%',      ( isset( $user->user_nicename ) ) ? $user->user_nicename : '', $content );
			$content     = str_replace( '%user_email%',         ( isset( $user->user_email ) ) ? $user->user_email : '', $content );
			$content     = str_replace( '%user_url%',           ( isset( $user->user_url ) ) ? $user->user_url : '', $content );

			// Balance Related
			$balance     = $this->get_users_balance( $user->ID );

			$content     = str_replace( '%balance%',            $balance, $content );
			$content     = str_replace( '%balance_f%',          $this->format_creds( $balance ), $content );

			return $content;

		}

		/**
		 * Comment Related Template Tags
		 * Replaces the comment related template tags in a given string.
		 *
		 * @param $content (string) string containing the template tags
		 * @param $ref_id (int) required comment id as reference id
		 * @param $data (object) Log entry data object
		 * @return (string) parsed string
		 * @since 0.1
		 * @version 1.0.4
		 */
		public function template_tags_comment( $content = '', $ref_id = NULL, $data = '' ) {

			if ( $ref_id === NULL ) return $content;

			$content = $this->template_tags_general( $content );
			if ( ! $this->has_tags( 'comment', 'comment_id|c_post_id|c_post_title|c_post_url|c_link_with_title', $content ) ) return $content;

			// Get Comment Object
			$comment = get_comment( $ref_id );

			// Comment does not exist
			if ( $comment === NULL ) {

				if ( ! is_array( $data ) || ! array_key_exists( 'comment_ID', $data ) ) return $content;

				$comment = new StdClass();
				foreach ( $data as $key => $value ) {
					$comment->$key = $value;
				}

				$url = get_permalink( $comment->comment_post_ID );
				if ( empty( $url ) ) $url = '#item-has-been-deleted';

				$title = get_the_title( $comment->comment_post_ID );
				if ( empty( $title ) ) $title = __( 'Deleted Item', 'mycred' );

			}
			else {

				$url   = get_permalink( $comment->comment_post_ID );
				$title = get_the_title( $comment->comment_post_ID );

			}

			// Let others play first
			$content = apply_filters( 'mycred_parse_tags_comment', $content, $comment, $data );

			$content = str_replace( '%comment_id%',        $comment->comment_ID, $content );

			$content = str_replace( '%c_post_id%',         $comment->comment_post_ID, $content );
			$content = str_replace( '%c_post_title%',      $title, $content );

			$content = str_replace( '%c_post_url%',        $url, $content );
			$content = str_replace( '%c_link_with_title%', '<a href="' . $url . '">' . $title . '</a>', $content );

			return $content;

		}

		/**
		 * Has Tags
		 * Checks if a string has any of the defined template tags.
		 *
		 * @param $type (string) template tag type
		 * @param $tags (string) tags to search for, list with |
		 * @param $content (string) content to search
		 * @filter 'mycred_has_tags'
		 * @filter 'mycred_has_tags_{$type}'
		 * @returns (boolean) true or false
		 * @since 1.2.2
		 * @version 1.0
		 */
		public function has_tags( $type = '', $tags = '', $content = '' ) {

			$tags = apply_filters( 'mycred_has_tags', $tags, $content );
			$tags = apply_filters( 'mycred_has_tags_' . $type, $tags, $content );

			if ( ! preg_match( '%(' . trim( $tags ) . ')%', $content, $matches ) ) return false;

			return true;

		}

		/**
		 * Available Template Tags
		 * Based on an array of template tag types, a list of codex links
		 * are generted for each tag type.
		 * @since 1.4
		 * @version 1.0
		 */
		public function available_template_tags( $available = array(), $custom = '' ) {

			// Prep
			$links = $template_tags = array();

			// General
			if ( in_array( 'general', $available ) )
				$template_tags[] = array(
					'title' => __( 'General', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-general/'
				);

			// User
			if ( in_array( 'user', $available ) )
				$template_tags[] = array(
					'title' => __( 'User Related', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-user/'
				);

			// Post
			if ( in_array( 'post', $available ) )
				$template_tags[] = array(
					'title' => __( 'Post Related', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-post/'
				);

			// Comment
			if ( in_array( 'comment', $available ) )
				$template_tags[] = array(
					'title' => __( 'Comment Related', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-comment/'
				);

			// Widget
			if ( in_array( 'widget', $available ) )
				$template_tags[] = array(
					'title' => __( 'Widget Related', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-widget/'
				);

			// Amount
			if ( in_array( 'amount', $available ) )
				$template_tags[] = array(
					'title' => __( 'Amount Related', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-amount/'
				);

			// Video
			if ( in_array( 'video', $available ) )
				$template_tags[] = array(
					'title' => __( 'Video Related', 'mycred' ),
					'url'   => 'http://codex.mycred.me/category/template-tags/temp-video/'
				);

			if ( ! empty( $template_tags ) ) {
				foreach ( $template_tags as $tag ) {
					$links[] = '<a href="' . $tag['url'] . '" target="_blank">' . $tag['title'] . '</a>';
				}
			}

			if ( ! empty( $custom ) )
				$custom = ' ' . __( 'and', 'mycred' ) . ' ' . $custom;

			return __( 'Available Template Tags:', 'mycred' ) . ' ' . implode( ', ', $links ) . $custom . '.';

		}

		/**
		 * Allowed Tags
		 * Strips HTML tags from a given string.
		 *
		 * @param $data (string) to strip tags off
		 * @param $allow (string) allows you to overwrite the default filter with a custom set of tags to strip
		 * @filter 'mycred_allowed_tags'
		 * @returns (string) string stripped of tags
		 * @since 0.1
		 * @version 1.1
		 */
		public function allowed_tags( $data = '', $allow = '' ) {

			if ( $allow === false )
				return strip_tags( $data );

			elseif ( ! empty( $allow ) )
				return strip_tags( $data, $allow );

			return strip_tags( $data, apply_filters( 'mycred_allowed_tags', '<a><br><em><strong><span>' ) );

		}

		/**
		 * Allowed HTML Tags
		 * Used for settings that support HTML. These settings are
		 * sanitized using wp_kses() where these tags are used.
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function allowed_html_tags() {

			return apply_filters( 'mycred_allowed_html_tags', array(
				'a'    => array( 'href' => array(), 'title' => array(), 'target' => array() ),
				'abbr' => array( 'title' => array() ), 'acronym' => array( 'title' => array() ),
				'code' => array(), 'pre' => array(), 'em' => array(), 'strong' => array(),
				'div'  => array( 'class' => array(), 'id' => array() ), 'span' => array( 'class' => array() ),
				'p'    => array(), 'ul' => array(), 'ol' => array(), 'li' => array(),
				'h1'   => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
				'img'  => array( 'src' => array(), 'class' => array(), 'alt' => array() ),
				'br'   => array( 'class' => array() )
			), $this );

		}

		/**
		 * Edit Creds Cap
		 * Returns the set edit creds capability.
		 *
		 * @returns capability (string)
		 * @since 0.1
		 * @version 1.1
		 */
		public function edit_creds_cap() {

			if ( ! isset( $this->caps['creds'] ) || empty( $this->caps['creds'] ) )
				$this->caps['creds'] = 'delete_users';

			return apply_filters( 'mycred_edit_creds_cap', $this->caps['creds'] );

		}

		/**
		 * Can Edit Creds
		 * Check if user can edit other users creds. If no user id is given
		 * we will attempt to get the current users id.
		 *
		 * @param $user_id (int) user id
		 * @returns true or false
		 * @since 0.1
		 * @version 1.1
		 */
		public function can_edit_creds( $user_id = '' ) {

			$result = false;

			if ( ! function_exists( 'get_current_user_id' ) )
				require_once( ABSPATH . WPINC . '/user.php' );

			// Grab current user id
			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! function_exists( 'user_can' ) )
				require_once( ABSPATH . WPINC . '/capabilities.php' );

			// Check if user can
			if ( user_can( $user_id, $this->edit_creds_cap() ) )
				$result = true;

			return apply_filters( 'mycred_can_edit_creds', $result, $user_id );

		}

		/**
		 * Edit Plugin Cap
		 * Returns the set edit plugin capability.
		 *
		 * @returns capability (string)
		 * @since 0.1
		 * @version 1.1
		 */
		public function edit_plugin_cap() {

			if ( ! isset( $this->caps['plugin'] ) || empty( $this->caps['plugin'] ) )
				$this->caps['plugin'] = 'manage_options';

			return apply_filters( 'mycred_edit_plugin_cap', $this->caps['plugin'] );

		}

		/**
		 * Can Edit This Plugin
		 * Checks if a given user can edit this plugin. If no user id is given
		 * we will attempt to get the current users id.
		 *
		 * @param $user_id (int) user id
		 * @returns true or false
		 * @since 0.1
		 * @version 1.1
		 */
		public function can_edit_plugin( $user_id = '' ) {

			$result = false;

			if ( ! function_exists( 'get_current_user_id' ) )
				require_once( ABSPATH . WPINC . '/user.php' );

			// Grab current user id
			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! function_exists( 'user_can' ) )
				require_once( ABSPATH . WPINC . '/capabilities.php' );

			// Check if user can
			if ( user_can( $user_id, $this->edit_plugin_cap() ) )
				$result = true;

			return apply_filters( 'mycred_can_edit_plugin', $result, $user_id );

		}

		/**
		 * Check if user id is in exclude list
		 * @return true or false
		 * @since 0.1
		 * @version 1.1
		 */
		public function in_exclude_list( $user_id = '' ) {

			$result = false;

			// Grab current user id
			if ( empty( $user_id ) )
				$user_id = get_current_user_id();

			if ( ! isset( $this->exclude['list'] ) )
				$this->exclude['list'] = '';

			$list = wp_parse_id_list( $this->exclude['list'] );
			if ( in_array( $user_id, $list ) )
				$result = true;

			return apply_filters( 'mycred_is_excluded_list', $result, $user_id );

		}

		/**
		 * Exclude Plugin Editors
		 * @return true or false
		 * @since 0.1
		 * @version 1.0
		 */
		public function exclude_plugin_editors() {

			return (bool) $this->exclude['plugin_editors'];

		}

		/**
		 * Exclude Cred Editors
		 * @return true or false
		 * @since 0.1
		 * @version 1.0
		 */
		public function exclude_creds_editors() {

			return (bool) $this->exclude['cred_editors'];

		}

		/**
		 * Exclude User
		 * Checks is the given user id should be excluded.
		 *
		 * @param $user_id (int), required user id
		 * @returns boolean true on user should be excluded else false
		 * @since 0.1
		 * @version 1.0.3
		 */
		public function exclude_user( $user_id = NULL ) {

			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			if ( apply_filters( 'mycred_exclude_user', false, $user_id, $this ) === true ) return true;

			if ( $this->exclude_plugin_editors() && $this->can_edit_plugin( $user_id ) ) return true;
			if ( $this->exclude_creds_editors() && $this->can_edit_creds( $user_id ) ) return true;

			if ( $this->in_exclude_list( $user_id ) ) return true;

			return false;

		}

		/**
		 * Count Members
		 * @since 1.1
		 * @version 1.1
		 */
		public function count_members() {

			global $wpdb;

			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( DISTINCT user_id ) FROM {$wpdb->usermeta} WHERE meta_key = %s;", mycred_get_meta_key( $this->cred_id ) ) );
			if ( $count === NULL ) $count = 0;

			return $count;

		}

		/**
		 * Get Cred ID
		 * Returns the default cred id.
		 * @since 0.1
		 * @version 1.0
		 */
		public function get_cred_id() {

			if ( ! isset( $this->cred_id ) || $this->cred_id == '' )
				$this->cred_id = MYCRED_DEFAULT_TYPE_KEY;

			return $this->cred_id;

		}

		/**
		 * Get Max
		 * @since 1.3
		 * @version 1.0
		 */
		public function max() {

			if ( ! isset( $this->max ) )
				$this->max = 0;

			return $this->max;

		}

		/**
		 * Get users balance
		 * Returns the users balance unformated.
		 *
		 * @param $user_id (int), required user id
		 * @param $type (string), optional cred type to check for
		 * @returns zero if user id is not set or if no creds were found, else returns amount
		 * @since 0.1
		 * @version 1.4.1
		 */
		public function get_users_balance( $user_id = NULL, $type = NULL ) {

			if ( $user_id === NULL ) return $this->zero();

			// Type
			$point_types = mycred_get_types();
			if ( $type === NULL || ! array_key_exists( $type, $point_types ) ) $type = $this->get_cred_id();

			$balance = mycred_get_user_meta( $user_id, $type, '', true );
			if ( $balance == '' ) $balance = $this->zero();

			// Let others play
			$balance = apply_filters( 'mycred_get_users_cred', $balance, $this, $user_id, $type );

			return $this->number( $balance );

		}
			// Replaces
			public function get_users_cred( $user_id = NULL, $type = NULL ) {

				return $this->get_users_balance( $user_id, $type );

			}

		/**
		 * Update users balance
		 * Returns the updated balance of the given user.
		 *
		 * @param $user_id (int), required user id
		 * @param $amount (int|float), amount to add/deduct from users balance. This value must be pre-formated.
		 * @param $type (string), optional point type key to adjust instead of the current one.
		 * @returns the new balance.
		 * @since 0.1
		 * @version 1.4.2
		 */
		public function update_users_balance( $user_id = NULL, $amount = NULL, $type = NULL ) {

			// Minimum Requirements: User id and amount can not be null
			if ( $user_id === NULL || $amount === NULL ) return $amount;

			// Type
			$point_types = mycred_get_types();
			if ( $type === NULL || ! array_key_exists( $type, $point_types ) ) $type = $this->get_cred_id();

			// Enforce max
			if ( $this->max() > $this->zero() && $amount > $this->max() ) {

				$_amount = $amount;
				$amount  = $this->number( $this->max() );

				do_action( 'mycred_max_enforced', $user_id, $_amount, $this->max() );

			}

			// Adjust creds
			$current_balance = $this->get_users_balance( $user_id, $type );
			$new_balance     = $current_balance+$amount;

			// Update creds
			mycred_update_user_meta( $user_id, $type, '', $new_balance );

			// Update total creds
			$total = mycred_query_users_total( $user_id, $type );
			mycred_update_user_meta( $user_id, $type, '_total', $total );

			// Clear caches
			mycred_delete_option( 'mycred-cache-total-' . $type );

			// Let others play
			do_action( 'mycred_update_user_balance', $user_id, $current_balance, $amount, $type );

			// Return the new balance
			return $this->number( $new_balance );

		}

		/**
		 * Set users balance
		 * Changes a users balance to the amount given.
		 *
		 * @param $user_id (int), required user id
		 * @param $new_balance (int|float), amount to add/deduct from users balance. This value must be pre-formated.
		 * @returns (bool) true on success or false on fail.
		 * @since 1.7.3
		 * @version 1.0.1
		 */
		public function set_users_balance( $user_id = NULL, $new_balance = NULL ) {

			// Minimum Requirements: User id and amount can not be null
			if ( $user_id === NULL || $new_balance === NULL ) return false;

			$type        = $this->get_cred_id();
			$new_balance = $this->number( $new_balance );
			$old_balance = $this->get_users_balance( $user_id, $type );

			// Update balance
			mycred_update_user_meta( $user_id, $type, '', $new_balance );

			// Clear caches
			mycred_delete_option( 'mycred-cache-total-' . $type );

			// Let others play
			do_action( 'mycred_set_user_balance', $user_id, $new_balance, $old_balance, $this );

			return true;

		}

		/**
		 * Add Creds
		 * Adds creds to a given user. A refernece ID, user id and number of creds must be given.
		 * Important! This function will not check if the user should be excluded from gaining points, this must
		 * be done before calling this function!
		 *
		 * @param $ref (string), required reference id
		 * @param $user_id (int), required id of the user who will get these points
		 * @param $cred (int|float), required number of creds to give or deduct from the given user.
		 * @param $ref_id (int), optional array of reference IDs allowing the use of content specific keywords in the log entry
		 * @param $data (object|array|string|int), optional extra data to save in the log. Note that arrays gets serialized!
		 * @param $type (string), optional point name, defaults to MYCRED_DEFAULT_TYPE_KEY
		 * @returns boolean true on success or false on fail
		 * @since 0.1
		 * @version 1.6.1
		 */
		public function add_creds( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = MYCRED_DEFAULT_TYPE_KEY ) {

			// Minimum Requirements: Reference not empty, User ID not empty and Amount is not empty
			if ( empty( $ref ) || empty( $user_id ) || empty( $amount ) ) return false;

			// Check exclusion
			if ( $this->exclude_user( $user_id ) === true ) return false;

			// Format amount
			$amount = $this->number( $amount );
			if ( $amount == $this->zero() || $amount == 0 ) return false;

			// Enforce max
			if ( $this->max() > $this->zero() && $amount > $this->max() ) {

				$_amount = $amount;
				$amount  = $this->number( $this->max() );

				do_action( 'mycred_max_enforced', $user_id, $_amount, $this->max() );

			}

			// Type
			$point_types = mycred_get_types();
			if ( ! array_key_exists( $type, $point_types ) ) $type = $this->get_cred_id();

			// Execution Override
			// Allows us to stop an execution. 
			// excepts a boolean reply
			$execute = apply_filters( 'mycred_add', true, compact( 'ref', 'user_id', 'amount', 'entry', 'ref_id', 'data', 'type' ), $this );

			// Acceptable answers:
			// true (boolean)
			if ( $execute === true ) {

				// Allow the adjustment of the values before we run them
				$run_this = apply_filters( 'mycred_run_this', compact( 'ref', 'user_id', 'amount', 'entry', 'ref_id', 'data', 'type' ), $this );

				// Add to log
				$this->add_to_log(
					$run_this['ref'],
					$run_this['user_id'],
					$run_this['amount'],
					$run_this['entry'],
					$run_this['ref_id'],
					$run_this['data'],
					$run_this['type']
				);

				// Update balance
				$this->update_users_balance( (int) $run_this['user_id'], $run_this['amount'], $run_this['type'] );

			}

			// false (boolean)
			else { $run_this = false; }

			// For all features that need to run once we have done or not done something
			return apply_filters( 'mycred_add_finished', $execute, $run_this, $this );

		}

		/**
		 * Add Log Entry
		 * Adds a new entry into the log. A reference id, user id and number of credits must be set.
		 *
		 * @param $ref (string), required reference id
		 * @param $user_id (int), required id of the user who will get these points
		 * @param $cred (int|float), required number of creds to give or deduct from the given user.
		 * @param $ref_id (array), optional array of reference IDs allowing the use of content specific keywords in the log entry
		 * @param $data (object|array|string|int), optional extra data to save in the log. Note that arrays gets serialized!
		 * @returns false if requirements are not set or db insert id if successful.
		 * @since 0.1
		 * @version 1.3.2
		 */
		public function add_to_log( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = MYCRED_DEFAULT_TYPE_KEY ) {

			// Minimum Requirements: Reference not empty, User ID not empty and Amount is not empty
			if ( empty( $ref ) || empty( $user_id ) || empty( $amount ) || empty( $entry ) ) return false;

			// Amount can not be zero!
			if ( $amount == $this->zero() || $amount == 0 ) return false;

			$insert_id = 0;

			// Option to disable logging
			if ( MYCRED_ENABLE_LOGGING ) {

				global $wpdb;

				// Strip HTML from log entry
				$entry = $this->allowed_tags( $entry );

				// Enforce max
				if ( $this->max() > $this->zero() && $amount > $this->max() )
					$amount = $this->number( $this->max() );

				// Type
				$point_types = mycred_get_types();
				if ( ! array_key_exists( $type, $point_types ) ) $type = $this->get_cred_id();

				$time   = apply_filters( 'mycred_log_time', current_time( 'timestamp' ), $ref, $user_id, $amount, $entry, $ref_id, $data, $type );
				$insert = array(
					'ref'     => $ref,
					'ref_id'  => $ref_id,
					'user_id' => (int) $user_id,
					'creds'   => $amount,
					'ctype'   => $type,
					'time'    => $time,
					'entry'   => $entry,
					'data'    => ( is_array( $data ) || is_object( $data ) ) ? serialize( $data ) : $data
				);

				// Insert into DB
				$wpdb->insert(
					$this->log_table,
					$insert,
					array( '%s', '%d', '%d', '%s', '%s', '%d', '%s', ( is_numeric( $data ) ) ? '%d' : '%s' )
				);

				$insert_id = $wpdb->insert_id;

				wp_cache_delete( 'mycred_references' . $type, 'mycred' );

			}

			delete_transient( 'mycred_log_entries' );

			return apply_filters( 'mycred_new_log_entry_id', $insert_id, $insert, $this );

		}

		/**
		 * Update Log Entry
		 * Updates an existing log entry.
		 *
		 * @param $entry_id (id), required log entry id
		 * @param $data (array), required column data to update
		 * @param $prep (array), required column prep
		 * @returns false if requirements are not met or true
		 * @since 1.6.7
		 * @version 1.1
		 */
		public function update_log_entry( $entry_id = NULL, $data = array(), $prep = array() ) {

			if ( $entry_id === NULL || empty( $data ) || empty( $prep ) ) return false;

			// If logging is disabled, pretend we did the job
			if ( ! MYCRED_ENABLE_LOGGING ) return true;

			global $wpdb;

			$wpdb->update(
				$this->log_table,
				$data,
				array( 'id' => $entry_id ),
				$prep,
				array( '%d' )
			);

			do_action( 'mycred_log_entry_updated', $entry_id, $data );

			return true;

		}

		/**
		 * Has Entry
		 * Checks to see if a given action with reference ID and user ID exists in the log database.
		 * @param $reference (string) required reference ID
		 * @param $ref_id (int) optional reference id
		 * @param $user_id (int) optional user id
		 * @param $data (array|string) option data to search
		 * @since 0.1
		 * @version 1.3.2
		 */
		function has_entry( $reference = NULL, $ref_id = NULL, $user_id = NULL, $data = NULL, $type = NULL ) {

			if ( ! MYCRED_ENABLE_LOGGING ) return false;

			global $wpdb;

			$wheres   = array();

			if ( $reference !== NULL )
				$wheres[] = $wpdb->prepare( "ref = %s", $reference );

			if ( $ref_id !== NULL )
				$wheres[] = $wpdb->prepare( "ref_id = %d", $ref_id );

			if ( $user_id !== NULL )
				$wheres[] = $wpdb->prepare( "user_id = %d", $user_id );

			if ( $data !== NULL )
				$wheres[] = $wpdb->prepare( "data = %s", maybe_serialize( $data ) );

			if ( $type === NULL ) $type = $this->get_cred_id();
			$wheres[] = $wpdb->prepare( "ctype = %s", $type );

			$where    = implode( ' AND ', $wheres );

			$has = true;
			if ( ! empty( $wheres ) ) {

				$check = $wpdb->get_var( "SELECT id FROM {$this->log_table} WHERE {$where};" );
				if ( $check === NULL )
					$has = false;

			}

			return apply_filters( 'mycred_has_entry', $has, $reference, $ref_id, $user_id, $data, $type );

		}

	}
endif;

/**
 * myCRED Label
 * Returns the myCRED Label
 * @since 1.3.3
 * @version 1.1
 */
if ( ! function_exists( 'mycred_label' ) ) :
	function mycred_label( $trim = false ) {

		global $mycred_label;

		if ( $mycred_label === NULL )
			$mycred_label = apply_filters( 'mycred_label', MYCRED_DEFAULT_LABEL );

		$name = $mycred_label;
		if ( $trim )
			$name = strip_tags( $mycred_label );

		return $name;

	}
endif;

/**
 * Get myCRED
 * Returns myCRED's general settings and core functions.
 * Replaces mycred_get_settings()
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred' ) ) :
	function mycred( $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $type != MYCRED_DEFAULT_TYPE_KEY )
			return new myCRED_Settings( $type );

		global $mycred;

		if ( ! isset( $mycred ) || ! is_object( $mycred ) )
			$mycred = new myCRED_Settings();

		return $mycred;

	}
endif;

/**
 * Get Module
 * @since 1.7.3
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_get_module' ) ) :
	function mycred_get_module( $module = '', $type = 'solo' ) {

		global $mycred_modules;

		if ( $type == 'solo' ) {

			if ( ! array_key_exists( $module, $mycred_modules['solo'] ) )
				return false;

			return $mycred_modules['solo'][ $module ];

		}

		if ( ! array_key_exists( $type, $mycred_modules['type'] ) )
			return false;

		return $mycred_modules['type'][ $type ][ $module ];

	}
endif;

/**
 * Get Addon Settings
 * @since 1.7.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_addon_settings' ) ) :
	function mycred_get_addon_settings( $addon = '', $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $addon == '' ) return false;

		$mycred = $_mycred = mycred();
		if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$mycred = mycred( $point_type );

		// If we are trying to get the settings under a custom point type and it does not exists
		// try and see if it exits under the main type
		if ( ! isset( $mycred->$addon ) && $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$mycred = $_mycred;

		$settings = false;
		if ( isset( $mycred->$addon ) )
			$settings = $mycred->$addon;

		return apply_filters( 'mycred_get_addon_settings', $settings, $addon, $point_type );

	}
endif;

/**
 * Get Post Types
 * Returns an array of post types that myCRED uses.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'get_mycred_post_types' ) ) :
	function get_mycred_post_types() {

		return apply_filters( 'mycred_post_types', array( 'mycred_coupon', 'mycred_badge', 'mycred_rank', 'buycred_payment' ) );

	}
endif;

/**
 * Get User ID
 * Attempts to return a user ID based on the request passed to this function-
 * Supports:
 * - NULL / empty string - returns the current users ID.
 * - "current" string - returns the current users ID.
 * - "bbprofile" string - returns the BuddyPress profile ID. Requires use on BP profiles.
 * - "author" string - returns the post authors user ID. Requires use inside the loop.
 * - "replyauthor" string - returns the bbPress reply authors ID. Requires use in bbPress forums topics.
 *
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_user_id' ) ) :
	function mycred_get_user_id( $requested = '' ) {

		if ( is_string( $requested ) && strlen( $requested ) == 0 ) return $requested;

		$user_id = 0;
		if ( ! is_numeric( $requested ) ) {

			if ( $requested === 'current' || strlen( $requested ) == 0 )
				$user_id = get_current_user_id();

			elseif ( $requested === 'bbprofile' ) {

				if ( function_exists( 'bp_displayed_user_id' ) )
					$requested = bp_displayed_user_id();

			}

			elseif ( $requested === 'author' ) {

				global $post;

				$author = get_the_author_meta( 'ID' );

				if ( empty( $author ) && isset( $post->post_author ) )
					$author = $post->post_author;

				if ( absint( $author ) )
					$user_id = $author;

			}

			elseif ( $requested === 'replyauthor' ) {

				if ( function_exists( 'bbp_get_reply_author_id' ) )
					$user_id = bbp_get_reply_author_id( bbp_get_reply_id() );

			}

			else {

				if ( is_email( $requested ) ) {

					$user = get_user_by( 'email', $requested );
					if ( isset( $user->ID ) )
						$user_id = $user->ID;

				}

				else {

					$user = get_user_by( 'login', $requested );
					if ( isset( $user->ID ) )
						$user_id = $user->ID;

					else {

						$user = get_user_by( 'slug', $requested );
						if ( isset( $user->ID ) )
							$user_id = $user->ID;

					}

				}

			}

		}
		else {

			$user_id = absint( $requested );

		}

		return apply_filters( 'mycred_get_user_id', $user_id, $requested );

	}
endif;

/**
 * Get Users Profile URL
 * Returns a given users profile URL.
 * @since 1.7.4
 * @version 1.0.2
 */
if ( ! function_exists( 'mycred_get_users_profile_url' ) ) :
	function mycred_get_users_profile_url( $user_id = NULL ) {

		$profile_url = '';
		if ( $user_id === NULL || absint( $user_id ) === 0 ) return $profile_url;

		$user        = get_userdata( $user_id );
		$profile_url = get_author_posts_url( $user_id );

		// BuddyPress option
		if ( function_exists( 'bp_core_get_user_domain' ) )
			$profile_url = bp_core_get_user_domain( $user_id );

		return apply_filters( 'mycred_users_profile_url', $profile_url, $user );

	}
endif;

/**
 * Get Users Account
 * Returns either the current users or the given users account object.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_account' ) ) :
	function mycred_get_account( $user_id = NULL ) {

		global $mycred_account;

		if ( $user_id === NULL ) $user_id = get_current_user_id();
		$user_id = absint( $user_id );

		if ( $user_id === 0 ) return false;

		if ( isset( $mycred_account->user_id ) && $mycred_account->user_id == $user_id )
			$account = $mycred_account;

		else {

			$account = new myCRED_Account( $user_id );
			if ( $account->user_id === false )
				$account = false;

		}

		return apply_filters( 'mycred_get_account', $account, $user_id );

	}
endif;

/**
 * Get Point Type Name
 * Returns the name given to a particular point type.
 * @param $signular (boolean) option to return the plural version, returns singular by default
 * @since 0.1
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_point_type_name' ) ) :
	function mycred_get_point_type_name( $type = MYCRED_DEFAULT_TYPE_KEY, $singular = true ) {

		$mycred = mycred( $type );

		if ( $singular )
			return $mycred->singular();

		return $mycred->plural();

	}
endif;

	// Deprecated
	if ( ! function_exists( 'mycred_name' ) ) :
		function mycred_name( $singular = true, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			_deprecated_function( 'mycred_name', '1.6.8', 'mycred_get_point_type_name()' );

			return mycred_get_point_type_name( $type, $singular );

		}
	endif;

/**
 * Get Cred Types
 * Returns an associative array of registered point types.
 * @param $name_first (bool) option to replace "myCRED" with the point type name set.
 * @since 1.4
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_types' ) ) :
	function mycred_get_types( $name_first = false ) {

		global $mycred_types;

		if ( is_array( $mycred_types ) && ! empty( $mycred_types ) )
			$types = $mycred_types;

		else {

			$types = array();

			$available_types = mycred_get_option( 'mycred_types', array( MYCRED_DEFAULT_TYPE_KEY => mycred_label() ) );
			if ( count( $available_types ) > 1 ) {

				foreach ( $available_types as $type => $label ) {

					if ( $type == MYCRED_DEFAULT_TYPE_KEY )
						$label   = mycred_get_point_type_name( MYCRED_DEFAULT_TYPE_KEY, false );

					$types[ $type ] = $label;

				}

			}
			else {

				if ( $name_first )
					$available_types[ MYCRED_DEFAULT_TYPE_KEY ] = mycred_get_point_type_name( MYCRED_DEFAULT_TYPE_KEY, false );

				$types = $available_types;

			}

		}

		return apply_filters( 'mycred_types', $types );

	}
endif;

/**
 * Point Type Exists
 * @since 1.6.8
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_point_type_exists' ) ) :
	function mycred_point_type_exists( $type = NULL ) {

		$result = false;
		$types  = mycred_get_types();
		$type   = sanitize_key( $type );

		// Remove _total from total balances to get the underlaying id
		$type   = str_replace( '_total', '', $type );

		// Need to remove blog id suffix on multisites
		// This function should not be used to check for point type ids with
		// blog ID suffixes but in case it is used incorrectly, we need to fix this.
		if ( is_multisite() )
			$type = str_replace( '_' . get_current_blog_id(), '', $type );

		if ( strlen( $type ) > 0 && array_key_exists( $type, $types ) )
			$result = true;

		return $result;

	}
endif;

/**
 * Get Usable Point Types
 * Returns an array of point type keys that a given user is allowed to use.
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_get_usable_types' ) ) :
	function mycred_get_usable_types( $user_id = NULL ) {

		$original_id = $user_id;
		if ( $user_id === NULL )
			$user_id = get_current_user_id();

		$usable = array();
		if ( is_user_logged_in() || $original_id !== NULL ) {

			global $mycred;

			$types = mycred_get_types();

			if ( count( $types ) == 1 && ! $mycred->exclude_user( $user_id ) )
				$usable[] = MYCRED_DEFAULT_TYPE_KEY;

			else {

				foreach ( $types as $type_id => $type ) {

					if ( $type_id == MYCRED_DEFAULT_TYPE_KEY && ! $mycred->exclude_user( $user_id ) )
						$usable[] = MYCRED_DEFAULT_TYPE_KEY;

					else {

						$custom_type = mycred( $type_id );
						if ( ! $custom_type->exclude_user( $user_id ) )
							$usable[] = $type_id;

					}

				}

			}

		}

		return $usable;

	}
endif;

/**
 * Select Point Type from Select Dropdown
 * @since 1.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_types_select_from_dropdown' ) ) :
	function mycred_types_select_from_dropdown( $name = '', $id = '', $selected = '', $return = false, $extra = '' ) {

		$types  = mycred_get_types();
		$output = '';

		if ( count( $types ) == 1 )
			$output .= '<input type="hidden"' . $extra . ' name="' . $name . '" id="' . $id . '" value="mycred_default" />';

		else {

			$output .= '<select' . $extra . ' name="' . $name . '" id="' . $id . '">';

			foreach ( $types as $type => $label ) {

				if ( $type == MYCRED_DEFAULT_TYPE_KEY ) {
					$_mycred = mycred( $type );
					$label   = $_mycred->plural();
				}

				$output .= '<option value="' . $type . '"';
				if ( $selected == $type ) $output .= ' selected="selected"';
				$output .= '>' . $label . '</option>';

			}

			$output .= '</select>';

		}

		if ( $return )
			return $output;

		echo $output;

	}
endif;

/**
 * Select Point Type from Checkboxes
 * @since 1.4
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_types_select_from_checkboxes' ) ) :
	function mycred_types_select_from_checkboxes( $name = '', $id = '', $selected_values = array(), $return = false ) {

		$types = mycred_get_types();

		$output = '';
		if ( count( $types ) > 0 ) {
			foreach ( $types as $type => $label ) {
				$selected = '';
				if ( in_array( $type, (array) $selected_values ) )
					$selected = ' checked="checked"';

				$id .= '-' . $type;

				$output .= '<label for="' . $id . '"><input type="checkbox" name="' . $name . '" id="' . $id . '" value="' . $type . '"' . $selected . ' /> ' . $label . '</label>';
			}
		}

		if ( $return )
			return $output;

		echo $output;

	}
endif;

/**
 * Get Network Settings
 * Returns myCRED's network settings or false if multisite is not enabled.
 * @since 0.1
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_settings_network' ) ) :
	function mycred_get_settings_network() {

		if ( ! is_multisite() ) return false;

		$defaults            = array(
			'master'            => 0,
			'central'           => 0,
			'block'             => ''
		);
		$settings            = get_blog_option( 1, 'mycred_network', $defaults );
		$settings            = wp_parse_args( $settings, $defaults );

		$settings['master']  = (bool) $settings['master'];
		$settings['central'] = (bool) $settings['central'];

		return $settings;

	}
endif;

/**
 * Override Settings
 * @since 0.1
 * @version 1.0
 */
if ( ! function_exists( 'mycred_override_settings' ) ) :
	function mycred_override_settings() {

		// Not a multisite
		if ( ! is_multisite() ) return false;

		$mycred_network = mycred_get_settings_network();
		if ( $mycred_network['master'] ) return true;

		return false;

	}
endif;

/**
 * Centralize Log
 * @since 1.3
 * @version 1.0
 */
if ( ! function_exists( 'mycred_centralize_log' ) ) :
	function mycred_centralize_log() {

		// Not a multisite
		if ( ! is_multisite() ) return true;

		$mycred_network = mycred_get_settings_network();
		if ( $mycred_network['central'] ) return true;

		return false;

	}
endif;

/**
 * Add Option
 * @since 1.7.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_add_option' ) ) :
	function mycred_add_option( $option_id, $value = '' ) {

		if ( is_multisite() ) {

			if ( mycred_override_settings() )
				return add_blog_option( 1, $option_id, $value );

			return add_blog_option( $GLOBALS['blog_id'], $option_id, $value );

		}
		return add_option( $option_id, $value );

	}
endif;

/**
 * Get Option
 * @since 1.4
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_get_option' ) ) :
	function mycred_get_option( $option_id, $default = array() ) {

		if ( is_multisite() ) {

			if ( mycred_override_settings() )
				return get_blog_option( 1, $option_id, $default );

			return get_blog_option( $GLOBALS['blog_id'], $option_id, $default );

		}

		return get_option( $option_id, $default );

	}
endif;

/**
 * Update Option
 * @since 1.4
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_update_option' ) ) :
	function mycred_update_option( $option_id, $value = '' ) {

		if ( is_multisite() ) {

			if ( mycred_override_settings() )
				return update_blog_option( 1, $option_id, $value );

			return update_blog_option( $GLOBALS['blog_id'], $option_id, $value );

		}

		return update_option( $option_id, $value );

	}
endif;

/**
 * Delete Option
 * @since 1.5.2
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_delete_option' ) ) :
	function mycred_delete_option( $option_id ) {

		if ( is_multisite() ) {

			if ( mycred_override_settings() )
				return delete_blog_option( 1, $option_id );

			return delete_blog_option( $GLOBALS['blog_id'], $option_id );

		}

		return delete_option( $option_id );

	}
endif;

/**
 * Get Meta Key
 * @since 1.6.8
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_meta_key' ) ) :
	function mycred_get_meta_key( $key, $end = '' ) {

		if ( is_multisite() ) {

			$blog_id = get_current_blog_id();

			if ( $blog_id > 1 && ! mycred_centralize_log() && $key != 'mycred_rank' )
				$key .= '_' . $blog_id;

			elseif ( $blog_id > 1 && ! mycred_override_settings() && $key == 'mycred_rank' )
				$key .= '_' . $blog_id;

		}

		if ( strlen( $end ) > 0 )
			$key .= $end;

		return $key;

	}
endif;

/**
 * Add User Meta
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_add_user_meta' ) ) :
	function mycred_add_user_meta( $user_id, $key, $end = '', $value = '', $unique = true ) {

		$key = mycred_get_meta_key( $key, $end );

		return add_user_meta( $user_id, $key, $value, $unique );

	}
endif;

/**
 * Get User Meta
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_user_meta' ) ) :
	function mycred_get_user_meta( $user_id, $key, $end = '', $unique = true ) {

		$key = mycred_get_meta_key( $key, $end );

		return get_user_meta( $user_id, $key, $unique );

	}
endif;

/**
 * Update User Meta
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_update_user_meta' ) ) :
	function mycred_update_user_meta( $user_id, $key, $end = '', $value = '' ) {

		$key = mycred_get_meta_key( $key, $end );

		return update_user_meta( $user_id, $key, $value );

	}
endif;

/**
 * Delete User Meta
 * @since 1.5
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_delete_user_meta' ) ) :
	function mycred_delete_user_meta( $user_id, $key, $end = '', $value = NULL ) {

		$key = mycred_get_meta_key( $key, $end );

		if ( $value === NULL )
			return delete_user_meta( $user_id, $key );

		return delete_user_meta( $user_id, $key, $value );

	}
endif;

/**
 * Strip Tags
 * Strippes HTML tags from a given string.
 * @param $string (string) string to stip
 * @param $overwrite (string), optional HTML tags to allow
 * @since 0.1
 * @version 1.0
 */
if ( ! function_exists( 'mycred_strip_tags' ) ) :
	function mycred_strip_tags( $string = '', $overwride = '' ) {

		$mycred = mycred();

		return $mycred->allowed_tags( $string, $overwrite );

	}
endif;

/**
 * Is Admin
 * Conditional tag that checks if a given user or the current user
 * can either edit the plugin or creds.
 * @param $user_id (int), optional user id to check, defaults to current user
 * @returns true or false
 * @since 0.1
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_is_admin' ) ) :
	function mycred_is_admin( $user_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL )
			$user_id = get_current_user_id();

		if ( $user_id === 0 ) return false;

		$mycred = mycred( $type );

		if ( $mycred->can_edit_creds( $user_id ) || $mycred->can_edit_plugin( $user_id ) )
			return true;

		return false;

	}
endif;

/**
 * Exclude User
 * Checks if a given user is excluded from using myCRED.
 * @see http://codex.mycred.me/functions/mycred_exclude_user/
 * @param $user_id (int), optional user to check, defaults to current user
 * @since 0.1
 * @version 1.1.2
 */
if ( ! function_exists( 'mycred_exclude_user' ) ) :
	function mycred_exclude_user( $user_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL )
			$user_id = get_current_user_id();

		if ( (int) $user_id === 0 ) return false;

		$mycred = mycred( $type );

		return $mycred->exclude_user( $user_id );

	}
endif;

/**
 * Get Users Creds
 * Returns the given users current cred balance. If no user id is given this function
 * will default to the current user!
 * @param $user_id (int) user id
 * @return users balance (int|float)
 * @since 0.1
 * @version 1.2.1
 */ 
if ( ! function_exists( 'mycred_get_users_cred' ) ) :
	function mycred_get_users_cred( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL ) $user_id = get_current_user_id();

		if ( (int) $user_id === 0 ) return false;

		if ( ! mycred_point_type_exists( $point_type ) )
			$point_type = MYCRED_DEFAULT_TYPE_KEY;

		$mycred = mycred( $point_type );

		if ( $mycred->exclude_user( $user_id ) ) return false;

		return $mycred->get_users_balance( $user_id, $point_type );

	}
endif;

/**
 * Get Users Balance (pseudo)
 * Pseudo function for mycred_get_users_cred.
 * @since 1.7.4
 * @version 1.0
 */ 
if ( ! function_exists( 'mycred_get_users_balance' ) ) :
	function mycred_get_users_balance( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		return mycred_get_users_cred( $user_id, $point_type );

	}
endif;

/**
 * Get Users Total Balance
 * @since 1.7.6
 * @version 1.0
 */ 
if ( ! function_exists( 'mycred_get_users_total_balance' ) ) :
	function mycred_get_users_total_balance( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL ) $user_id = get_current_user_id();

		if ( (int) $user_id === 0 ) return false;

		if ( ! mycred_point_type_exists( $point_type ) )
			$point_type = MYCRED_DEFAULT_TYPE_KEY;

		$mycred      = mycred( $point_type );

		if ( $mycred->exclude_user( $user_id ) ) return false;

		$users_total = mycred_get_user_meta( $user_id, $point_type, '_total', true );
		if ( $users_total == '' ) {

			$users_total = mycred_query_users_total( $user_id, $point_type );

			mycred_update_user_meta( $user_id, $point_type, '_total', $users_total );

		}

		return $mycred->number( $users_total );

	}
endif;

/**
 * Get Users Creds Formated
 * Returns the given users current cred balance formated. If no user id is given
 * this function will return false!
 * @param $user_id (int), required user id
 * @return users balance (string) or false if no user id is given
 * @since 0.1
 * @version 1.2
 */
if ( ! function_exists( 'mycred_get_users_fcred' ) ) :
	function mycred_get_users_fcred( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL ) $user_id = get_current_user_id();

		if ( (int) $user_id === 0 ) return false;

		$mycred  = mycred( $point_type );

		if ( $mycred->exclude_user( $user_id ) ) return false;

		$balance = $mycred->get_users_balance( $user_id, $point_type );

		return $mycred->format_creds( $balance );

	}
endif;

/**
 * Display Users Balance
 * Pseudo function for mycred_get_users_fcred.
 * @since 1.7.4
 * @version 1.0
 */ 
if ( ! function_exists( 'mycred_display_users_balance' ) ) :
	function mycred_display_users_balance( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		return mycred_get_users_fcred( $user_id, $point_type );

	}
endif;

/**
 * Display Users Total Balance
 * @since 1.7.6
 * @version 1.0
 */ 
if ( ! function_exists( 'mycred_display_users_total_balance' ) ) :
	function mycred_display_users_total_balance( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL ) $user_id = get_current_user_id();

		if ( (int) $user_id === 0 ) return false;

		if ( ! mycred_point_type_exists( $point_type ) )
			$point_type = MYCRED_DEFAULT_TYPE_KEY;

		$mycred      = mycred( $point_type );

		if ( $mycred->exclude_user( $user_id ) ) return false;

		$users_total = mycred_get_user_meta( $user_id, $point_type, '_total', true );
		if ( $users_total == '' ) {

			$users_total = mycred_query_users_total( $user_id, $point_type );

			mycred_update_user_meta( $user_id, $point_type, '_total', $users_total );

		}

		return $mycred->format_creds( $users_total );

	}
endif;

/**
 * Flush Widget Cache
 * @since 0.1
 * @version 1.0
 */
if ( ! function_exists( 'mycred_flush_widget_cache' ) ) :
	function mycred_flush_widget_cache( $id = NULL ) {

		if ( $id === NULL ) return;
		wp_cache_delete( $id, 'widget' );

	}
endif;

/**
 * Format Number
 * @since 1.3.3
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_format_number' ) ) :
	function mycred_format_number( $value = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$mycred = mycred( $type );
		if ( $value === NULL )
			return $mycred->zero();

		return $mycred->format_number( $value );

	}
endif;

/**
 * Format Creds
 * @since 1.3.3
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_format_creds' ) ) :
	function mycred_format_creds( $value = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$mycred = mycred( $type );
		if ( $value === NULL ) $mycred->zero();

		return $mycred->format_creds( $value );

	}
endif;

/**
 * Add Creds
 * Adds creds to a given user. A refernece ID, user id and amount must be given.
 * Important! This function will not check if the user should be excluded from gaining points, this must
 * be done before calling this function!
 *
 * @see http://codex.mycred.me/functions/mycred_add/
 * @param $ref (string), required reference id
 * @param $user_id (int), required id of the user who will get these points
 * @param $amount (int|float), required number of creds to give or deduct from the given user.
 * @param $ref_id (array), optional array of reference IDs allowing the use of content specific keywords in the log entry
 * @param $data (object|array|string|int), optional extra data to save in the log. Note that arrays gets serialized!
 * @returns boolean true on success or false on fail
 * @since 0.1
 * @version 1.2.1
 */
if ( ! function_exists( 'mycred_add' ) ) :
	function mycred_add( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = MYCRED_DEFAULT_TYPE_KEY ) {

		// $ref, $user_id and $cred is required
		if ( $ref == '' || $user_id == '' || $amount == '' ) return false;

		// Init myCRED
		$mycred = mycred( $type );

		// Add creds
		return $mycred->add_creds( $ref, $user_id, $amount, $entry, $ref_id, $data, $type );

	}
endif;

/**
 * Subtract Creds
 * Subtracts creds from a given user. Works just as mycred_add() but the creds are converted into a negative value.
 * @see http://codex.mycred.me/functions/mycred_subtract/
 * @uses mycred_add()
 * @since 0.1
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_subtract' ) ) :
	function mycred_subtract( $ref = '', $user_id = '', $amount = '', $entry = '', $ref_id = '', $data = '', $type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $ref == '' || $user_id == '' || $amount == '' ) return false;
		if ( $amount > 0 ) $amount = 0 - $amount;

		return mycred_add( $ref, $user_id, $amount, $entry, $ref_id, $data, $type );

	}
endif;

/**
 * Update users total creds
 * Updates a given users total creds balance.
 *
 * @param $user_id (int), required user id
 * @param $request (array), required request array with information on users id (user_id) and amount
 * @param $mycred (myCRED_Settings object), required myCRED settings object
 * @returns zero if user id is not set or if no total were found, else returns total
 * @since 1.2
 * @version 1.3.1
 */
if ( ! function_exists( 'mycred_update_users_total' ) ) :
	function mycred_update_users_total( $type = MYCRED_DEFAULT_TYPE_KEY, $request = NULL, $mycred = NULL ) {

		if ( $request === NULL || ! is_object( $mycred ) || ! isset( $request['user_id'] ) || ! isset( $request['amount'] ) ) return false;

		if ( ! mycred_point_type_exists( $type ) )
			$type = $mycred->get_cred_id();

		$amount      = $mycred->number( $request['amount'] );
		$user_id     = absint( $request['user_id'] );

		$users_total = mycred_get_user_meta( $user_id, $type, '_total', true );
		if ( $users_total == '' )
			$users_total = mycred_query_users_total( $user_id, $type );

		$new_total   = $mycred->number( $users_total+$amount );
		mycred_update_user_meta( $user_id, $type, '_total', $new_total );

		return apply_filters( 'mycred_update_users_total', $new_total, $type, $request, $mycred );

	}
endif;

/**
 * Apply Defaults
 * Based on the shortcode_atts() function with support for
 * multidimentional arrays.
 * @since 1.1.2
 * @version 1.0
 */
if ( ! function_exists( 'mycred_apply_defaults' ) ) :
	function mycred_apply_defaults( &$pref, $set ) {

		$set    = (array) $set;
		$return = array();

		foreach ( $pref as $key => $value ) {

			if ( array_key_exists( $key, $set ) ) {

				if ( is_array( $value ) && ! empty( $value ) )
					$return[ $key ] = mycred_apply_defaults( $value, $set[ $key ] );

				else
					$return[ $key ] = $set[ $key ];

			}

			else $return[ $key ] = $value;

		}

		return $return;

	}
endif;

/**
 * Get Remote API Settings
 * @since 1.3
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_remote' ) ) :
	function mycred_get_remote() {

		$defaults = apply_filters( 'mycred_remote_defaults', array(
			'enabled' => 0,
			'key'     => '',
			'uri'     => 'api-dev',
			'debug'   => 0
		) );

		return mycred_apply_defaults( $defaults, get_option( 'mycred_pref_remote', array() ) );

	}
endif;

/**
 * Check if site is blocked
 * @since 1.5.4
 * @version 1.0.4
 */
if ( ! function_exists( 'mycred_is_site_blocked' ) ) :
	function mycred_is_site_blocked( $blog_id = NULL ) {

		// Only applicable for multisites
		if ( ! is_multisite() ) return false;

		// Blog ID
		if ( $blog_id === NULL )
			$blog_id = get_current_blog_id();

		// Get Network settings
		$network = mycred_get_settings_network();
		$blocked = false;

		// Only applicable if the block is set and this is not the main blog
		if ( strlen( $network['block'] ) > 0 && $blog_id > 1 ) {

			// Clean up list to make sure no white spaces are used
			$list  = explode( ',', $network['block'] );
			$clean = array();
			foreach ( $list as $listed_id ) {

				$listed_id = absint( trim( $listed_id ) );

				if ( $listed_id === 0 || $listed_id === 1 ) continue;
				$clean[]   = $listed_id;

			}

			// Check if blog is blocked from using myCRED.
			if ( in_array( $blog_id, $clean ) )
				$blocked = true;

		}

		return apply_filters( 'mycred_is_site_blocked', $blocked, $blog_id );

	}
endif;

/**
 * Is myCRED Ready
 * @since 1.3
 * @version 1.1
 */
if ( ! function_exists( 'is_mycred_ready' ) ) :
	function is_mycred_ready() {

		if ( mycred_is_installed() !== false ) return true;

		return false;

	}
endif;

/**
 * Is myCRED Installed
 * Returns either false (setup has not been run) or the timestamp when it was completed.
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_is_installed' ) ) :
	function mycred_is_installed() {

		return mycred_get_option( 'mycred_setup_completed', false );

	}
endif;

/**
 * Maybe Install myCRED Table
 * Check to see if maybe the myCRED table needs to be installed.
 * @since 1.7.6
 * @version 1.0
 */
if ( ! function_exists( 'maybe_install_mycred_table' ) ) :
	function maybe_install_mycred_table() {

		// No need to check this if we have disabled logging. Prevent this from being used using AJAX
		if ( ! MYCRED_ENABLE_LOGGING || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || apply_filters( 'mycred_maybe_install_db', true ) === false ) return;

		global $wpdb, $mycred;

		// Check if the table exists
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $mycred->log_table ) ) != $mycred->log_table ) {

			mycred_install_log( NULL, true );

			do_action( 'mycred_reinstalled_table' );

		}

	}
endif;

/**
 * Install Log
 * Installs the log for a site.
 * @since 1.3
 * @version 1.4.1
 */
if ( ! function_exists( 'mycred_install_log' ) ) :
	function mycred_install_log( $decimals = NULL, $force = false ) {

		if ( ! MYCRED_ENABLE_LOGGING ) return true;
		$mycred = mycred();

		if ( ! $force ) {

			$db_version = mycred_get_option( 'mycred_version_db', false );

			// DB Already installed
			if ( $db_version == myCRED_DB_VERSION ) return true;

		}

		global $wpdb;

		$table       = $mycred->log_table;
		$cred_format = 'bigint(22)';
		$point_type  = $mycred->cred_id;

		// If decimals is not provided
		if ( $decimals === NULL )
			$decimals = $mycred->format['decimals'];

		// Point format in the log
		if ( $decimals > 0 ) {

			if ( $decimals > 4 )
				$cred_format = "decimal(32,$decimals)";

			else
				$cred_format = "decimal(22,$decimals)";

		}

		$wpdb->hide_errors();

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {

			if ( ! empty( $wpdb->charset ) )
				$collate .= "DEFAULT CHARACTER SET {$wpdb->charset}";

			if ( ! empty( $wpdb->collate ) )
				$collate .= " COLLATE {$wpdb->collate}";

		}

		// Log structure
		$sql = "
			id            INT(11) NOT NULL AUTO_INCREMENT, 
			ref           VARCHAR(256) NOT NULL, 
			ref_id        INT(11) DEFAULT NULL, 
			user_id       INT(11) DEFAULT NULL, 
			creds         {$cred_format} DEFAULT NULL, 
			ctype         VARCHAR(64) DEFAULT '{$point_type}', 
			time          BIGINT(20) DEFAULT NULL, 
			entry         LONGTEXT DEFAULT NULL, 
			data          LONGTEXT DEFAULT NULL, 
			PRIMARY KEY   (id), 
			UNIQUE KEY id (id)"; 

		// Insert table
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$table} ( " . $sql . " ) $collate;" );

		mycred_update_option( 'mycred_version_db', myCRED_DB_VERSION );

		return true;

	}
endif;

/**
 * Plugin Activation
 * @since 1.3
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_plugin_activation' ) ) :
	function mycred_plugin_activation() {

		// Load Installer
		require_once myCRED_INCLUDES_DIR . 'mycred-install.php';
		$installer = mycred_installer();

		// Compatibility check
		$installer::compat();

		// First time activation
		if ( get_option( 'mycred_version', false ) === false )
			$installer::activate();

		// Re-activation
		else
			$installer::reactivate();

	}
endif;

/**
 * Runs when the plugin is deactivated
 * @since 1.3
 * @version 1.0
 */
if ( ! function_exists( 'mycred_plugin_deactivation' ) ) :
	function mycred_plugin_deactivation() {

		// Clear Cron
		wp_clear_scheduled_hook( 'mycred_reset_key' );
		wp_clear_scheduled_hook( 'mycred_banking_recurring_payout' );
		wp_clear_scheduled_hook( 'mycred_banking_interest_compound' );
		wp_clear_scheduled_hook( 'mycred_banking_interest_payout' );

		do_action( 'mycred_deactivation' );

	}
endif;

/**
 * Runs when the plugin is deleted
 * @since 1.3
 * @version 1.0.2
 */
if ( ! function_exists( 'mycred_plugin_uninstall' ) ) :
	function mycred_plugin_uninstall() {

		// Load Installer
		require_once myCRED_INCLUDES_DIR . 'mycred-install.php';
		$installer = mycred_installer();

		do_action( 'mycred_before_deletion', $installer );

		// Run uninstaller
		$installer::uninstall();

		do_action( 'mycred_after_deletion', $installer );

	}
endif;

/**
 * Get Exchange Rates
 * Returns the exchange rates for point types
 * @since 1.5
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_exchange_rates' ) ) :
	function mycred_get_exchange_rates( $point_type = '' ) {

		$types   = mycred_get_types();
		$default = array();

		foreach ( $types as $type => $label ) {
			if ( $type == $point_type ) continue;
			$default[ $type ] = 0;
		}

		$settings = mycred_get_option( 'mycred_pref_exchange_' . $point_type, $default );
		$settings = mycred_apply_defaults( $default, $settings );

		return $settings;

	}
endif;

/**
 * Is Float?
 * @since 1.5
 * @version 1.0
 */
if ( ! function_exists( 'isfloat' ) ) :
	function isfloat( $f ) {

		return ( $f == (string)(float) $f );

	}
endif;

/**
 * Translate Limit Code
 * @since 1.6
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_translate_limit_code' ) ) :
	function mycred_translate_limit_code( $code = '' ) {

		if ( $code == '' ) return '-';

		if ( $code == '0/x' || $code == 0 )
			return __( 'No limit', 'mycred' );

		$result = '-';
		$check  = explode( '/', $code );
		if ( count( $check ) == 2 ) {

			$per    = __( 'in total', 'mycred' );
			if ( $check[1] == 'd' )
				$per = __( 'per day', 'mycred' );

			elseif ( $check[1] == 'w' )
				$per = __( 'per week', 'mycred' );

			elseif ( $check[1] == 'm' )
				$per = __( 'per month', 'mycred' );

			$result = sprintf( _n( 'Maximum once', 'Maximum %d times', $check[0], 'mycred' ), $check[0] ) . ' ' . $per;

		}

		elseif ( is_numeric( $code ) ) {

			$result = sprintf( _n( 'Maximum once', 'Maximum %d times', $code, 'mycred' ), $code );

		}

		return apply_filters( 'mycred_translate_limit_code', $result, $code );

	}
endif;

/**
 * Ordinal Suffix
 * @since 1.7
 * @version 1.1
 */
if ( ! function_exists( 'mycred_ordinal_suffix' ) ) :
	function mycred_ordinal_suffix( $num = 0, $depreciated = true ) {

		if ( ! is_numeric( $num ) ) return $num;

		$value  = $num;
		$num    = $num % 100; // protect against large numbers

		$result = sprintf( _x( '%d th', 'e.g. 5 th', 'mycred' ), $value );
		if ( $num < 11 || $num > 13 ) {
			switch ( $num % 10 ) {

				case 1 : $result = sprintf( _x( '%d st', 'e.g. 1 st', 'mycred' ), $value );
				case 2 : $result = sprintf( _x( '%d nd', 'e.g. 2 nd', 'mycred' ), $value );
				case 3 : $result = sprintf( _x( '%d rd', 'e.g. 3 rd', 'mycred' ), $value );

			}
		}

		return apply_filters( 'mycred_ordinal_suffix', $result, $value );

	}
endif;

/**
 * Date to Timestamp
 * Converts a well formatted date string into GMT unixtimestamp.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_date_to_gmt_timestamp' ) ) :
	function mycred_date_to_gmt_timestamp( $string = '' ) {

		return strtotime( get_gmt_from_date( $string ) );

	}
endif;

/**
 * Timestamp to Date
 * Converts a GMT unixtimestamp to local timestamp
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_gmt_timestamp_to_local' ) ) :
	function mycred_gmt_timestamp_to_local( $string = '' ) {

		return strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $string ), 'Y-m-d H:i:s' ) );

	}
endif;

/**
 * Force Singular Session
 * Used to prevent multiple simultaneous AJAX calls from any one user.
 * The $timelimit sets the minimum amount of seconds that must have passed between
 * two AJAX requests.
 * @since 1.7
 * @version 1.1
 */
if ( ! function_exists( 'mycred_force_singular_session' ) ) :
	function mycred_force_singular_session( $user_id = NULL, $key = NULL, $timelimit = MYCRED_MIN_TIME_LIMIT ) {

		$force      = false;
		$time       = time();
		$user_id    = absint( $user_id );
		$key        = sanitize_text_field( $key );
		$timelimit  = absint( $timelimit );

		if ( $key == '' ) return true;

		// 1 - Cookies
		$last_call  = $time - $timelimit;
		$cookie_key = md5( $user_id . $key );
		if ( isset( $_COOKIE[ $cookie_key ] ) )
			$last_call = absint( $_COOKIE[ $cookie_key ] );

		if ( ( $time - $last_call ) < $timelimit )
			$force = true;

		setcookie( $cookie_key, $time, ( time() + DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );

		return apply_filters( 'mycred_force_singular_session', $force, $user_id, $key, $timelimit );

	}
endif;

/**
 * Locate Template
 * @since 1.0
 * @version 1.0
 */
if ( ! function_exists( 'mycred_locate_template' ) ) :
	function mycred_locate_template( $template_name, $template_path = 'mycred', $default_path = '' ) {

		if ( empty( $template_path ) || empty( $default_path ) ) return false;

		if ( substr( $template_path, -1 ) != '/' )
			$template_path = trailingslashit( $template_path );

		// Look within passed path within the theme - this is priority.
		$template = locate_template( array( $template_path . $template_name, $template_name ) );

		// Get default template/
		if ( ! $template ) $template = $default_path . $template_name;

		// Return what we found.
		return apply_filters( 'mycred_locate_template', $template, $template_name, $template_path );

	}
endif;
