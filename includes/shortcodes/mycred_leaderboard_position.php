<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Shortcode: mycred_leaderboard_position
 * @see http://codex.mycred.me/shortcodes/mycred_leaderboard_position/
 * Replaces the mycred_my_ranking shortcode.
 * @since 1.7
 * @version 1.1.3
 */
if ( ! function_exists( 'mycred_render_shortcode_leaderbaord_position' ) ) :
	function mycred_render_shortcode_leaderbaord_position( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'user_id'   => 'current',
			'ctype'     => MYCRED_DEFAULT_TYPE_KEY,
			'based_on'  => 'balance',
			'total'     => 0,
			'missing'   => '-',
			'suffix'    => 0,
			'timeframe' => ''
		), $atts ) );

		// If we want the current users position but we are not logged in, we can not know a position.
		if ( ! is_user_logged_in() && $user_id === 'current' )
			return $content;

		if ( ! MYCRED_ENABLE_LOGGING ) return '';

		// Make sure we use a type that exists
		if ( ! mycred_point_type_exists( $ctype ) )
			$ctype = MYCRED_DEFAULT_TYPE_KEY;

		global $wpdb, $mycred;

		// Prep
		$user_id  = mycred_get_user_id( $user_id );
		$based_on = sanitize_text_field( $based_on );

		// Get Position based on balance
		if ( $based_on == 'balance' ) {

			$multisite_check = "";
			if ( ! mycred_centralize_log() ) {

				$blog_id         = absint( $GLOBALS['blog_id'] );
				$multisite_check = "LEFT JOIN {$wpdb->usermeta} cap ON ( t.user_id = cap.user_id AND cap.meta_key = 'cap.wp_{$blog_id}_capabilities' )";

			}

			// Current Balance
			if ( $total == 0 )
				$position = $wpdb->get_var( $wpdb->prepare( "
					SELECT rank FROM (
						SELECT s.*, @rank := @rank + 1 rank FROM (
							SELECT t.user_id, t.meta_value AS Balance FROM {$wpdb->usermeta} t 
							{$multisite_check} 
							WHERE t.meta_key = %s 
						) s, (SELECT @rank := 0) init
						ORDER BY Balance+0 DESC, s.user_id ASC 
					) r 
					WHERE user_id = %d", mycred_get_meta_key( $ctype ), $user_id ) );

			// Total Balance
			else
				$position = $wpdb->get_var( $wpdb->prepare( "
					SELECT rank FROM (
						SELECT s.*, @rank := @rank + 1 rank FROM (
							SELECT t.user_id, sum(t.creds) TotalPoints FROM {$mycred->log_table} t 
							{$multisite_check}
							WHERE t.ctype = %s AND ( ( t.creds > 0 ) OR ( t.creds < 0 AND t.ref = 'manual' ) ) 
							GROUP BY t.user_id
							) s, (SELECT @rank := 0) init
						ORDER BY TotalPoints DESC, s.user_id ASC 
					) r 
					WHERE user_id = %d", $ctype, $user_id ) );

		}

		// Get Position based on reference e.g. Most point gains for approved comments
		else {

			$time_filter = '';
			$now         = current_time( 'timestamp' );
			if ( $timeframe != '' ) {

				// Start of the week based of our settings
				$week_starts = get_option( 'start_of_week' );
				if ( $week_starts == 0 )
					$week_starts = 'sunday';
				else
					$week_starts = 'monday';

				// Filter: Daily
				if ( $timeframe == 'today' )
					$time_filter = $wpdb->prepare( "AND time BETWEEN %d AND %d", strtotime( 'today midnight', $now ), $now );

				// Filter: Weekly
				elseif ( $timeframe == 'this-week' )
					$time_filter = $wpdb->prepare( "AND time BETWEEN %d AND %d", strtotime( $week_starts . ' this week', $now ), $now );

				// Filter: Monthly
				elseif ( $timeframe == 'this-month' )
					$time_filter = $wpdb->prepare( "AND time BETWEEN %d AND %d", strtotime( date( 'Y-m-01', $now ) ), $now );

				else
					$time_filter = $wpdb->prepare( "AND time BETWEEN %d AND %d", strtotime( $timeframe, $now ), $now );

				$time_filter = apply_filters( 'mycred_leaderboard_time_filter', $time_filter, $based_on, $user_id, $ctype );

			}

			$position = $wpdb->get_var( $wpdb->prepare( "
				SELECT rank FROM (
					SELECT s.*, @rank := @rank + 1 rank FROM (
						SELECT t.user_id, sum(t.creds) TotalPoints FROM {$mycred->log_table} t 
						WHERE t.ref != 'manual' AND t.creds > 0 AND t.ctype = %s AND t.ref = %s {$time_filter}
						GROUP BY t.user_id
					) s, (SELECT @rank := 0) init
					ORDER BY TotalPoints DESC, s.user_id ASC 
				) r 
				WHERE user_id = %d", $ctype, $based_on, $user_id ) );

		}

		if ( $position === NULL )
			$position = $missing;

		elseif ( $suffix == 1 )
			$position = mycred_ordinal_suffix( $position, true );

		return apply_filters( 'mycred_get_leaderboard_position', $position, $user_id, $ctype );

	}
endif;
add_shortcode( 'mycred_leaderboard_position', 'mycred_render_shortcode_leaderbaord_position' );

/**
 * myCRED Shortcode: mycred_my_ranking
 * @see http://codex.mycred.me/shortcodes/mycred_my_ranking/
 * Depreciated since 1.7. Replaced by mycred_leaderboard_position
 * @since 0.1
 * @version 1.6
 */
if ( ! function_exists( 'mycred_render_shortcode_my_ranking' ) ) :
	function mycred_render_shortcode_my_ranking( $atts, $content = '' ) {

		return mycred_render_shortcode_leaderbaord_position( $atts, $content );

	}
endif;
add_shortcode( 'mycred_my_ranking', 'mycred_render_shortcode_my_ranking' );
