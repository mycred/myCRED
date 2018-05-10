<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Have Ranks
 * Checks if there are any rank posts.
 * @returns (bool) true or false
 * @since 1.1
 * @version 1.5.1
 */
if ( ! function_exists( 'mycred_have_ranks' ) ) :
	function mycred_have_ranks( $point_type = NULL ) {

		global $wpdb;

		if ( ! mycred_override_settings() ) {
			$posts    = $wpdb->posts;
			$postmeta = $wpdb->postmeta;
		}
		else {
			$posts    = $wpdb->base_prefix . 'posts';
			$postmeta = $wpdb->base_prefix . 'postmeta';
		}

		$type_filter = '';
		if ( $point_type !== NULL && mycred_point_type_exists( sanitize_key( $point_type ) ) )
			$type_filter = $wpdb->prepare( "INNER JOIN {$postmeta} ctype ON ( ranks.ID = ctype.post_id AND ctype.meta_key = 'ctype' AND ctype.meta_value = %s )", $point_type );

		$mycred_ranks = $wpdb->get_var( "
			SELECT COUNT(*) 
			FROM {$posts} ranks 
			{$type_filter} 
			WHERE ranks.post_type = 'mycred_rank';" );
		
		$return = false;
		if ( $mycred_ranks !== NULL && $mycred_ranks > 0 )
			$return = true;

		return apply_filters( 'mycred_have_ranks', $return, $point_type );

	}
endif;

/**
 * Have Published Ranks
 * Checks if there are any published rank posts.
 * @returns (int) the number of published ranks found.
 * @since 1.3.2
 * @version 1.2
 */
if ( ! function_exists( 'mycred_get_published_ranks_count' ) ) :
	function mycred_get_published_ranks_count( $point_type = NULL ) {

		global $wpdb;

		if ( ! mycred_override_settings() ) {
			$posts    = $wpdb->posts;
			$postmeta = $wpdb->postmeta;
		}
		else {
			$posts    = $wpdb->base_prefix . 'posts';
			$postmeta = $wpdb->base_prefix . 'postmeta';
		}

		$type_filter = '';
		if ( $point_type !== NULL && mycred_point_type_exists( sanitize_key( $point_type ) ) )
			$type_filter = $wpdb->prepare( "INNER JOIN {$postmeta} ctype ON ( ranks.ID = ctype.post_id AND ctype.meta_key = 'ctype' AND ctype.meta_value = %s )", $point_type );

		$count = $wpdb->get_var( "
			SELECT COUNT(*) 
			FROM {$posts} ranks 
			{$type_filter} 
			WHERE ranks.post_type = 'mycred_rank' 
			AND ranks.post_status = 'publish';" );

		if ( $count === NULL ) $count = 0;

		return apply_filters( 'mycred_get_published_ranks_count', $count, $point_type );

	}
endif;

/**
 * Get Rank Object ID
 * Makes sure a given post ID is a rank post ID or converts a rank title into a rank ID.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_rank_object_id' ) ) :
	function mycred_get_rank_object_id( $identifier = NULL ) {

		if ( $identifier === NULL ) return false;

		$rank_id = false;

		if ( ! mycred_override_settings() ) {

			if ( absint( $identifier ) !== 0 && get_post_type( absint( $identifier ) ) === 'mycred_rank' )
				$rank_id = absint( $identifier );

			else {

				$rank = get_page_by_title( $identifier, OBJECT, 'mycred_rank' );
				if ( isset( $rank->post_type ) && $rank->post_type === 'mycred_rank' )
					$rank_id = $rank->ID;

			}

		}

		else {

			$original_blog_id = get_current_blog_id();
			switch_to_blog( 1 );

			if ( absint( $identifier ) !== 0 && get_post_type( absint( $identifier ) ) === 'mycred_rank' )
				$rank_id = absint( $identifier );

			else {

				$rank = get_page_by_title( $identifier, OBJECT, 'mycred_rank' );
				if ( isset( $rank->post_type ) && $rank->post_type === 'mycred_rank' )
					$rank_id = $rank->ID;

			}

			switch_to_blog( $original_blog_id );

		}

		return $rank_id;

	}
endif;


/**
 * Get Rank
 * Returns the rank object.
 * @since 1.1
 * @version 1.3
 */
if ( ! function_exists( 'mycred_get_rank' ) ) :
	function mycred_get_rank( $rank_identifier = NULL ) {

		$rank_id = mycred_get_rank_object_id( $rank_identifier );
		if ( $rank_id === false ) return false;

		$rank = new myCRED_Rank( $rank_id );

		return $rank;

	}
endif;

/**
 * Rank Has Logo
 * Checks if a given rank has a logo.
 * @since 1.1
 * @version 1.3
 */
if ( ! function_exists( 'mycred_rank_has_logo' ) ) :
	function mycred_rank_has_logo( $rank_identifier = NULL ) {

		$rank_id = mycred_get_rank_object_id( $rank_identifier );
		if ( $rank_id === false ) return false;

		$return = false;
		if ( ! mycred_override_settings() ) {

			if ( has_post_thumbnail( $rank_id ) )
				$return = true;

		}
		else {

			$original_blog_id = get_current_blog_id();
			switch_to_blog( 1 );

			if ( has_post_thumbnail( $rank_id ) )
				$return = true;

			switch_to_blog( $original_blog_id );

		}

		return apply_filters( 'mycred_rank_has_logo', $return, $rank_id );

	}
endif;

/**
 * Get Rank Logo
 * Returns the given ranks logo.
 * @since 1.1
 * @version 1.3
 */
if ( ! function_exists( 'mycred_get_rank_logo' ) ) :
	function mycred_get_rank_logo( $rank_identifier = NULL, $size = 'post-thumbnail', $attr = NULL ) {

		$rank_id = mycred_get_rank_object_id( $rank_identifier );
		if ( $rank_id === false ) return false;

		if ( is_numeric( $size ) )
			$size = array( $size, $size );

		if ( ! mycred_override_settings() )
			$logo = get_the_post_thumbnail( $rank_id, $size, $attr );

		else {

			$original_blog_id = get_current_blog_id();
			switch_to_blog( 1 );

			$logo = get_the_post_thumbnail( $rank_id, $size, $attr );

			switch_to_blog( $original_blog_id );

		}

		return apply_filters( 'mycred_get_rank_logo', $logo, $rank_id, $size, $attr );

	}
endif;

/**
 * Count Users with Rank
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'mycred_count_users_with_rank' ) ) :
	function mycred_count_users_with_rank( $rank_identifier = NULL ) {

		$rank_id = mycred_get_rank_object_id( $rank_identifier );
		if ( $rank_id === false ) return 0;

		$user_count = get_post_meta( $rank_id, 'mycred_rank_users', true );
		if ( $user_count == '' ) {

			$type = get_post_meta( $rank_id, 'ctype', true );
			if ( $type == '' ) return 0;

			$mycred = mycred( $type );

			$rank_meta_key = 'mycred_rank';
			if ( $mycred->is_multisite && $GLOBALS['blog_id'] > 1 && ! $mycred->use_master_template )
				$rank_meta_key .= '_' . $GLOBALS['blog_id'];

			if ( $type != MYCRED_DEFAULT_TYPE_KEY )
				$rank_meta_key .= $type;

			global $wpdb;

			$user_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( user_id ) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %d;", $rank_meta_key, $rank_id ) );

			if ( $user_count === NULL ) $user_count = 0;

			update_post_meta( $rank_id, 'mycred_rank_users', $user_count );

		}

		return $user_count;

	}
endif;

/**
 * Get Users Rank ID
 * Returns the rank post ID for the given point type.
 * @since 1.6
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_get_users_rank_id' ) ) :
	function mycred_get_users_rank_id( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		$end = '';
		if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$end = $point_type;

		$rank_id = mycred_get_user_meta( $user_id, 'mycred_rank', $end, true );
		if ( $rank_id == '' ) {

			$rank = mycred_find_users_rank( $user_id, $point_type );

			// Found a rank, save it
			if ( $rank !== false ) {
				mycred_save_users_rank( $user_id, $rank->rank_id, $point_type );
				$rank_id = $rank->rank_id;
			}

		}

		return $rank_id;

	}
endif;

/**
 * Save Users Rank
 * Saves a given rank for a user.
 * @since 1.7.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_save_users_rank' ) ) :
	function mycred_save_users_rank( $user_id = NULL, $rank_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		if ( $user_id === NULL || $rank_id === NULL ) return false;

		$end = '';
		if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$end = $point_type;

		mycred_update_user_meta( $user_id, 'mycred_rank', $end, $rank_id );

		return true;

	}
endif;

/**
 * Get My Rank
 * Returns the current users rank
 * @since 1.1
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_my_rank' ) ) :
	function mycred_get_my_rank() {

		if ( ! is_user_logged_in() ) return;

		return mycred_get_users_rank( get_current_user_id() );

	}
endif;

/**
 * Get Users Rank
 * Retreaves the users current saved rank or if rank is missing
 * finds the appropriate rank and saves it.
 * @since 1.1
 * @version 1.5.1
 */
if ( ! function_exists( 'mycred_get_users_rank' ) ) :
	function mycred_get_users_rank( $user_id = NULL, $type = MYCRED_DEFAULT_TYPE_KEY ) {

		// User ID is required
		if ( $user_id === NULL || ! is_numeric( $user_id ) ) return false;

		$end = '';
		if ( $type != MYCRED_DEFAULT_TYPE_KEY )
			$end = $type;

		// Get users rank
		$rank_id = mycred_get_user_meta( $user_id, 'mycred_rank', $end, true );

		// No rank, try to assign one
		if ( $rank_id == '' ) {

			$rank = mycred_find_users_rank( $user_id, $type );

			// Found a rank, save it
			if ( $rank !== false ) {
				mycred_save_users_rank( $user_id, $rank->rank_id, $type );
				$rank_id = $rank->rank_id;
			}

		}

		// Get Rank object
		$rank = mycred_get_rank( $rank_id );

		return apply_filters( 'mycred_get_users_rank', $rank, $user_id, $rank_id );

	}
endif;

/**
 * Find Users Rank
 * Attenots to find a particular users rank for a particular point type.
 * @uses mycred_user_got_demoted if user got demoted to a lower rank.
 * @uses mycred_user_got_promoted if user got promoted to a higher rank.
 * @since 1.1
 * @version 1.6.1
 */
if ( ! function_exists( 'mycred_find_users_rank' ) ) :
	function mycred_find_users_rank( $user_id = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY, $act = true ) {

		global $wpdb;

		$mycred = mycred( $point_type );

		$end = '';
		if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$end = $point_type;

		// In case user id is not set
		if ( $user_id === NULL )
			$user_id = get_current_user_id();

		// Non logged in users have ID 0.
		if ( absint( $user_id ) === 0 ) return false;

		// If ranks are based on total we get the total balance which in turn
		// if not set will default to the users current balance.
		if ( mycred_rank_based_on_total( $point_type ) ) {

			// Since usermeta might be cached we can not trust the amount so we must query the DB for a fresh value.
			$balance = mycred_query_users_total( $user_id, $point_type );

		}
		else {

			// Since usermeta might be cached we can not trust the amount so we must query the DB for a fresh value.
			$balance = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key = %s;", $user_id, mycred_get_meta_key( $point_type ) ) );
			if ( $balance === NULL )
				$balance = 0;

		}

		// Prep format for the db query
		$balance_format = '%d';
		if ( isset( $mycred->format['decimals'] ) && $mycred->format['decimals'] > 0 ) {
			$length         = 65 - $mycred->format['decimals'];
			$balance_format = 'CAST( %f AS DECIMAL( ' . $length . ', ' . $mycred->format['decimals'] . ' ) )';
		}

		// Get the appropriate post tables
		if ( ! mycred_override_settings() ) {
			$posts    = $wpdb->posts;
			$postmeta = $wpdb->postmeta;
		}
		else {
			$posts    = $wpdb->base_prefix . 'posts';
			$postmeta = $wpdb->base_prefix . 'postmeta';
		}

		// See where the users balance fits in
		$results = $wpdb->get_row( $wpdb->prepare( "
			SELECT ranks.ID AS rank_id, min.meta_value AS minimum, max.meta_value AS maximum, meta.meta_value AS current_id
			FROM {$posts} ranks 
			INNER JOIN {$postmeta} ctype ON ( ranks.ID = ctype.post_id AND ctype.meta_key = 'ctype' AND ctype.meta_value = %s )
			INNER JOIN {$postmeta} min ON ( ranks.ID = min.post_id AND min.meta_key = 'mycred_rank_min' )
			INNER JOIN {$postmeta} max ON ( ranks.ID = max.post_id AND max.meta_key = 'mycred_rank_max' ) 
			LEFT JOIN {$wpdb->usermeta} meta ON ( meta.user_id = %d AND meta.meta_key = %s ) 
			WHERE ranks.post_type = 'mycred_rank'
				AND ranks.post_status = 'publish'
				AND {$balance_format} BETWEEN min.meta_value AND max.meta_value
			LIMIT 0,1;", $point_type, $user_id, mycred_get_meta_key( 'mycred_rank', $end ), $balance ) );

		// Found a new rank
		if ( $act === true && isset( $results->rank_id ) && $results->rank_id !== $results->current_id ) {

			// Demotions
			if ( $results->current_id !== NULL && get_post_meta( $results->current_id, 'mycred_rank_max', true ) > $results->maximum )
				do_action( 'mycred_user_got_demoted', $user_id, $results->rank_id, $results );

			// Promotions
			else
				do_action( 'mycred_user_got_promoted', $user_id, $results->rank_id, $results );

			// Reset counters
			delete_post_meta( $results->current_id, 'mycred_rank_users' );
			delete_post_meta( $results->rank_id, 'mycred_rank_users' );

		}

		if ( $results === NULL )
			$results = false;

		return apply_filters( 'mycred_find_users_rank', $results, $user_id, $point_type );

	}
endif;

/**
 * Assign Ranks
 * Runs though all user balances and assigns each users their
 * appropriate ranks.
 * @returns void
 * @since 1.3.2
 * @version 1.5.2
 */
if ( ! function_exists( 'mycred_assign_ranks' ) ) :
	function mycred_assign_ranks( $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		global $wpdb;

		$mycred = mycred( $point_type );

		$end = '';
		if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$end = $point_type;

		do_action( 'mycred_assign_ranks_start' );

		$balance_format = '%d';
		if ( isset( $mycred->format['decimals'] ) && $mycred->format['decimals'] > 0 ) {
			$length         = 65 - $mycred->format['decimals'];
			$balance_format = 'CAST( %f AS DECIMAL( ' . $length . ', ' . $mycred->format['decimals'] . ' ) )';
		}

		$ranks = mycred_get_ranks( 'publish', '-1', 'ASC', $point_type );

		$balance_key =  mycred_get_meta_key( $point_type );
		if ( mycred_rank_based_on_total( $point_type ) )
			$balance_key =  mycred_get_meta_key( $point_type, '_total' );

		$count = 0;
		if ( ! empty( $ranks ) ) {
			foreach ( $ranks as $rank ) {

				$count += $wpdb->query( $wpdb->prepare( "
					UPDATE {$wpdb->usermeta} ranks 
						INNER JOIN {$wpdb->usermeta} balance ON ( ranks.user_id = balance.user_id AND balance.meta_key = %s )
					SET ranks.meta_value = %d 
					WHERE ranks.meta_key = %s 
						AND balance.meta_value BETWEEN {$balance_format} AND {$balance_format};", $balance_key, $rank->post_id, mycred_get_meta_key( 'mycred_rank', $end ), $rank->minimum, $rank->maximum ) );

			}
		}

		do_action( 'mycred_assign_ranks_end' );

		return $count;

	}
endif;

/**
 * Get Ranks
 * Returns an associative array of ranks with the given status.
 * @param $status (string) post status, defaults to 'publish'
 * @param $number (int|string) number of ranks to return, defaults to all
 * @param $order (string) option to return ranks ordered Ascending or Descending
 * @param $type (string) optional point type
 * @returns (array) empty if no ranks are found or associative array with post ID as key and title as value
 * @since 1.1
 * @version 1.5.2
 */
if ( ! function_exists( 'mycred_get_ranks' ) ) :
	function mycred_get_ranks( $status = 'publish', $number = '-1', $order = 'DESC', $point_type = NULL ) {

		global $wpdb;

		// Order
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) )
			$order = 'DESC';

		// Limit
		if ( $number != '-1' )
			$limit = 'LIMIT 0,' . absint( $number );
		else
			$limit = '';

		if ( ! mycred_override_settings() ) {
			$posts    = $wpdb->posts;
			$postmeta = $wpdb->postmeta;
		}
		else {
			$posts    = $wpdb->base_prefix . 'posts';
			$postmeta = $wpdb->base_prefix . 'postmeta';
		}

		$type_join   = '';
		$type_filter = '';
		if ( $point_type !== NULL && mycred_point_type_exists( sanitize_key( $point_type ) ) ) {
			$type_join   = "LEFT JOIN {$postmeta} ctype ON ( ranks.ID = ctype.post_id AND ctype.meta_key = 'ctype' )";
			$type_filter = $wpdb->prepare( "AND ctype.meta_value = %s", $point_type );
		}

		// Get ranks
		$results  = array();
		$rank_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT ranks.ID
			FROM {$posts} ranks
			{$type_join}
			LEFT JOIN {$postmeta} min ON ( ranks.ID = min.post_id AND min.meta_key = 'mycred_rank_min' ) 
			WHERE ranks.post_type = 'mycred_rank' 
			AND ranks.post_status = %s 
			{$type_filter}
			ORDER BY min.meta_value+0 {$order} {$limit};", $status ) );

		if ( ! empty( $rank_ids ) ) {
			foreach ( $rank_ids as $rank_id )
				$results[] = mycred_get_rank( $rank_id );
		}

		return apply_filters( 'mycred_get_ranks', $results, $status, $number, $order );

	}
endif;

/**
 * Get Users of Rank
 * Returns an associative array of user IDs and display names of users for a given
 * rank.
 * @param $rank (int|string) either a rank id or rank name
 * @param $number (int) number of users to return
 * @returns (array) empty if no users were found or associative array with user ID as key and display name as value
 * @since 1.1
 * @version 1.5.1
 */
if ( ! function_exists( 'mycred_get_users_of_rank' ) ) :
	function mycred_get_users_of_rank( $rank_identifier = NULL, $number = '-1', $order = 'DESC', $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		$rank_id = mycred_get_rank_object_id( $rank_identifier );
		if ( $rank_id === false ) return false;

		global $wpdb;

		$mycred = mycred( $point_type );

		$end = '';
		if ( $point_type != MYCRED_DEFAULT_TYPE_KEY )
			$end = $point_type;

		$rank_meta_key = mycred_get_meta_key( 'mycred_rank', $end );
		$balance_key   = mycred_get_meta_key( $point_type );

		if ( ! mycred_override_settings() ) {
			$posts    = $wpdb->posts;
			$postmeta = $wpdb->postmeta;
		}
		else {
			$posts    = $wpdb->base_prefix . 'posts';
			$postmeta = $wpdb->base_prefix . 'postmeta';
		}

		// Order
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) )
			$order = 'DESC';

		// Limit
		if ( $number != '-1' )
			$limit = 'LIMIT 0,' . absint( $number );
		else
			$limit = '';

		$users = $wpdb->get_results( $wpdb->prepare( "
			SELECT users.*, creds.meta_value AS balance 
			FROM {$wpdb->users} users 
			LEFT JOIN {$wpdb->usermeta} rank ON ( users.ID = rank.user_id AND rank.meta_key = %s ) 
			LEFT JOIN {$wpdb->usermeta} creds ON ( users.ID = creds.user_id AND creds.meta_key = %s ) 
			WHERE rank.meta_value = %d 
			ORDER BY creds.meta_value+0 {$order} {$limit}", $rank_meta_key, $balance_key, $rank_id ) );

		return apply_filters( 'mycred_get_users_of_rank', $users, $rank_id, $number, $order, $point_type );

	}
endif;

/**
 * Rank Based on Total
 * Checks if ranks for a given point type are based on total or current
 * balance.
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_rank_based_on_total' ) ) :
	function mycred_rank_based_on_total( $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$prefs_key = 'mycred_pref_core';
		if ( $type != MYCRED_DEFAULT_TYPE_KEY )
			$prefs_key .= '_' . $type;

		$prefs = get_option( $prefs_key );

		$result = false;
		if ( isset( $prefs['rank']['base'] ) && $prefs['rank']['base'] == 'total' )
			$result = true;

		return $result;

	}
endif;

/**
 * Rank Shown in BuddyPress
 * Returns either false or the location where the rank is to be shown in BuddyPress.
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'mycred_show_rank_in_buddypress' ) ) :
	function mycred_show_rank_in_buddypress( $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$prefs = mycred_get_option( 'mycred_pref_core' );

		$result = false;
		if ( isset( $prefs['rank']['bb_location'] ) && $prefs['rank']['bb_location'] != '' )
			$result = $prefs['rank']['bb_location'];

		return $result;

	}
endif;

/**
 * Rank Shown in bbPress
 * Returns either false or the location where the rank is to be shown in bbPress.
 * @since 1.6
 * @version 1.1
 */
if ( ! function_exists( 'mycred_show_rank_in_bbpress' ) ) :
	function mycred_show_rank_in_bbpress( $type = MYCRED_DEFAULT_TYPE_KEY ) {

		$prefs = mycred_get_option( 'mycred_pref_core' );

		$result = false;
		if ( isset( $prefs['rank']['bp_location'] ) && $prefs['rank']['bp_location'] != '' )
			$result = $prefs['rank']['bp_location'];

		return $result;

	}
endif;
