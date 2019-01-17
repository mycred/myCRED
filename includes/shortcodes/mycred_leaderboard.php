<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Shortcode: mycred_leaderboard
 * @see http://codex.mycred.me/shortcodes/mycred_leaderboard/
 * @since 0.1
 * @version 1.5.2
 */
if ( ! function_exists( 'mycred_render_shortcode_leaderboard' ) ) :
	function mycred_render_shortcode_leaderboard( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'number'       => 25,
			'order'        => 'DESC',
			'offset'       => 0,
			'type'         => MYCRED_DEFAULT_TYPE_KEY,
			'based_on'     => 'balance',
			'total'        => 0,
			'wrap'         => 'li',
			'template'     => '#%position% %user_profile_link% %cred_f%',
			'nothing'      => 'Leaderboard is empty',
			'current'      => 0,
			'exclude_zero' => 1,
			'timeframe'    => ''
		), $atts ) );

		if ( ! MYCRED_ENABLE_LOGGING ) return '';

		if ( ! mycred_point_type_exists( $type ) )
			$type = MYCRED_DEFAULT_TYPE_KEY;

		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) )
			$order = 'DESC';

		if ( $number != '-1' )
			$limit = 'LIMIT ' . absint( $offset ) . ',' . absint( $number );
		else
			$limit = '';

		$mycred = mycred( $type );

		global $wpdb;

		// Option to exclude zero balances
		$excludes = '';
		if ( $exclude_zero == 1 ) {

			$balance_format = '%d';
			if ( isset( $mycred->format['decimals'] ) && $mycred->format['decimals'] > 0 ) {
				$length         = 65 - $mycred->format['decimals'];
				$balance_format = 'CAST( %f AS DECIMAL( ' . $length . ', ' . $mycred->format['decimals'] . ' ) )';
			}

			if ( $total == 0 )
				$excludes = $wpdb->prepare( "AND um.meta_value != {$balance_format}", $mycred->zero() );

		}

		$based_on = sanitize_text_field( $based_on );

		// Leaderboard based on balance
		if ( $based_on == 'balance' ) {

			$multisite_check = "";
			if ( ! mycred_centralize_log() ) {

				$blog_id         = absint( $GLOBALS['blog_id'] );
				$multisite_check = "LEFT JOIN {$wpdb->usermeta} cap ON ( l.user_id = cap.user_id AND cap.meta_key = 'cap.wp_{$blog_id}_capabilities' )";

			}

			// Total balance
			if ( $total == 1 ) {

				$query = $wpdb->prepare( "
					SELECT l.user_id AS ID, SUM( l.creds ) AS cred 
					FROM {$mycred->log_table} l 
					{$multisite_check} 
					WHERE l.ctype = %s AND ( ( l.creds > 0 ) OR ( l.creds < 0 AND l.ref = 'manual' ) )
					{$excludes} 
					GROUP BY l.user_id
					ORDER BY SUM( l.creds ) {$order}, l.user_id ASC 
					{$limit};", $type );

			}

			// Current Balance
			else {

				$query = $wpdb->prepare( "
					SELECT DISTINCT u.ID, um.meta_value AS cred 
					FROM {$wpdb->users} u 
					INNER JOIN {$wpdb->usermeta} um ON ( u.ID = um.user_id ) 
					{$multisite_check} 
					WHERE um.meta_key = %s 
					{$excludes} 
					ORDER BY um.meta_value+0 {$order}, um.user_id ASC
					{$limit};", mycred_get_meta_key( $type ) );

			}

		}

		// Leaderboard based on reference
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
					$time_filter = $wpdb->prepare( "AND log.time BETWEEN %d AND %d", strtotime( 'today midnight', $now ), $now );

				// Filter: Weekly
				elseif ( $timeframe == 'this-week' )
					$time_filter = $wpdb->prepare( "AND log.time BETWEEN %d AND %d", strtotime( $week_starts . ' this week', $now ), $now );

				// Filter: Monthly
				elseif ( $timeframe == 'this-month' )
					$time_filter = $wpdb->prepare( "AND log.time BETWEEN %d AND %d", strtotime( 'Y-m-01', $now ), $now );

				else {

					$start_from = strtotime( $timeframe, $now );
					if ( $start_from !== false && $start_from > 0 )
						$time_filter = $wpdb->prepare( "AND log.time BETWEEN %d AND %d", $start_from, $now );

				}

				$time_filter = apply_filters( 'mycred_leaderboard_time_filter', $time_filter, $based_on, $user_id, $ctype );

			}

			if ( mycred_centralize_log() )
				$query = $wpdb->prepare( "SELECT DISTINCT log.user_id AS ID, SUM( log.creds ) AS cred FROM {$mycred->log_table} log WHERE log.ref = %s {$time_filter} GROUP BY log.user_id ORDER BY SUM( log.creds ) {$order} {$limit};", $based_on );

			// Multisite support
			else {

				$blog_id = absint( $GLOBALS['blog_id'] );
				$query   = $wpdb->prepare( "
					SELECT DISTINCT log.user_id AS ID, SUM( log.creds ) AS cred 
					FROM {$mycred->log_table} log 
					LEFT JOIN {$wpdb->usermeta} cap ON ( log.user_id = cap.user_id AND cap.meta_key = 'cap.wp_{$blog_id}_capabilities' ) 
					WHERE log.ref = %s 
					{$time_filter} 
					GROUP BY log.user_id 
					ORDER BY SUM( log.creds ) {$order}, log.user_id ASC
					{$limit};", $based_on );

			}

		}

		$leaderboard  = $wpdb->get_results( apply_filters( 'mycred_ranking_sql', $query, $atts ), 'ARRAY_A' );
		$output       = '';
		$in_list      = false;
		$current_user = wp_get_current_user();

		if ( ! empty( $leaderboard ) ) {

			// Check if current user is in the leaderboard
			if ( $current == 1 && is_user_logged_in() ) {

				// Find the current user in the leaderboard
				foreach ( $leaderboard as $position => $user ) {
					if ( $user['ID'] == $current_user->ID ) {
						$in_list = true;
						break;
					}
				}

			}

			// Load myCRED
			$mycred = mycred( $type );

			// Wrapper
			if ( $wrap == 'li' )
				$output .= '<ol class="myCRED-leaderboard list-unstyled">';

			// Loop
			foreach ( $leaderboard as $position => $user ) {

				// Prep
				$class = array();

				// Position
				if ( $offset != '' && $offset > 0 )
					$position = $position + $offset;

				// Classes
				$class[] = 'item-' . $position;
				if ( $position == 0 )
					$class[] = 'first-item';

				if ( $user['ID'] == $current_user->ID )
					$class[] = 'current-user';

				if ( $position % 2 != 0 )
					$class[] = 'alt';

				$row_template = $template;
				if ( ! empty( $content ) )
					$row_template = $content;

				// Template Tags
				$layout = str_replace( array( '%ranking%', '%position%' ), $position+1, $row_template );

				$layout = $mycred->template_tags_amount( $layout, $user['cred'] );
				$layout = $mycred->template_tags_user( $layout, $user['ID'] );

				// Wrapper
				if ( ! empty( $wrap ) )
					$layout = '<' . $wrap . ' class="%classes%">' . $layout . '</' . $wrap . '>';

				$layout = str_replace( '%classes%', apply_filters( 'mycred_ranking_classes', implode( ' ', $class ) ), $layout );
				$layout = apply_filters( 'mycred_ranking_row', $layout, $template, $user, $position+1 );

				$output .= $layout . "\n";

			}

			// Current user is not in list but we want to show his position
			if ( ! $in_list && $current == 1 && is_user_logged_in() ) {

				// Flush previous query
				$wpdb->flush();

				$current_position = mycred_render_shortcode_leaderbaord_position( array(
					'based_on'  => $based_on,
					'user_id'   => 'current',
					'timeframe' => $timeframe,
					'ctype'     => $type
				), $content );

				$row_template = $template;
				if ( ! empty( $content ) )
					$row_template = $content;

				// Template Tags
				$layout = str_replace( array( '%ranking%', '%position%' ), $current_position, $row_template );

				$layout = $mycred->template_tags_amount( $layout, $mycred->get_users_balance( $current_user->ID, $type ) );
				$layout = $mycred->template_tags_user( $layout, false, $current_user );

				// Wrapper
				if ( ! empty( $wrap ) )
					$layout = '<' . $wrap . ' class="%classes%">' . $layout . '</' . $wrap . '>';

				$layout = str_replace( '%classes%', apply_filters( 'mycred_ranking_classes', implode( ' ', $class ) ), $layout );
				$layout = apply_filters( 'mycred_ranking_row', $layout, $template, $current_user, $current_position );

				$output .= $layout . "\n";

			}

			if ( $wrap == 'li' )
				$output .= '</ol>';

		}

		// No result template is set
		else {

			$output .= '<p class="mycred-leaderboard-none">' . $nothing . '</p>';

		}

		return do_shortcode( apply_filters( 'mycred_leaderboard', $output, $atts ) );

	}
endif;
add_shortcode( 'mycred_leaderboard', 'mycred_render_shortcode_leaderboard' );
