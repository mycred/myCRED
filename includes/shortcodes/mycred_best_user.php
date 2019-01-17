<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Best User
 * Allows database queries in the history table to determen the
 * "best user" based on references, time and point types.
 * @since 1.6.7
 * @version 1.0.3
 */
if ( ! function_exists( 'mycred_render_shortcode_best_user' ) ) :
	function mycred_render_shortcode_best_user( $attr, $content = '' ) {

		extract( shortcode_atts( array(
			'ref'     => '',
			'from'    => '',
			'until'   => '',
			'types'   => MYCRED_DEFAULT_TYPE_KEY,
			'nothing' => 'No user found',
			'order'   => 'DESC',
			'avatar'  => 50
		), $attr ) );

		if ( $ref != '' )
			$references = explode( ',', $ref );
		else
			$references = '';

		$point_types = explode( ',', $types );
		if ( empty( $point_types ) ) $point_types = array( MYCRED_DEFAULT_TYPE_KEY );

		$now = current_time( 'timestamp' );
		if ( $from == 'now' )
			$from = $now;

		elseif ( $from != '' )
			$from = strtotime( $from );

		if ( $from == 0 )
			$from = '';

		if ( $until == 'now' )
			$until = $now;

		elseif ( $until != '' )
			$until = strtotime( $until );

		if ( $until == 0 )
			$until = '';

		global $wpdb;

		if ( defined( 'MYCRED_LOG_TABLE' ) )
			$table = MYCRED_LOG_TABLE;

		else {

			if ( is_multisite() && mycred_centralize_log() )
				$table = $wpdb->base_prefix . 'myCRED_log';
			else
				$table = $wpdb->prefix . 'myCRED_log';

		}

		$wheres = $preps = array();

		if ( ! empty( $references ) ) {
			$wheres[] = "ref IN (" . str_repeat( '%s', count( $references ) ) . ")";
			foreach ( $references as $reference )
				$preps[] = $reference;
		}

		$wheres[] = "ctype IN (" . str_repeat( '%s', count( $point_types ) ) . ")";
		foreach ( $point_types as $point_type )
			$preps[] = $point_type;

		if ( $from != '' || $until != '' ) {

			if ( $from != '' && $until == '' )
				$wheres[] = $wpdb->prepare( "time >= %d", $from );

			elseif ( $from == '' && $until != '' )
				$wheres[] = $wpdb->prepare( "time <= %d", $until );

			elseif ( $from != '' && $until != '' )
				$wheres[] = $wpdb->prepare( "time BETWEEN %d AND %d", $from, $until );

		}

		$where = 'WHERE ' . implode( ' AND ', $wheres );
		$where = $wpdb->prepare( $where, $preps );

		if ( ! in_array( $order, array( 'DESC', 'ASC' ) ) )
			$order = 'DESC';

		$result = $wpdb->get_row( "SELECT user_id, SUM( creds ) AS total, COUNT(*) AS count FROM {$table} {$where} GROUP BY user_id ORDER BY SUM( creds ) {$order} LIMIT 1;" );
		if ( ! isset( $result->user_id ) )
			return '<p class="mycred-best-user-no-results text-center">' . $nothing . '</p>';

		$user = get_userdata( $result->user_id );
		if ( ! isset( $user->display_name ) )
			return '<p class="mycred-best-user-no-results text-center">' . $nothing . '</p>';

		if ( empty( $content ) )
			$content = '<div class="mycred-best-user text-center">%avatar%<h4>%display_name%</h4></div>';

		$content = apply_filters( 'mycred_best_user_content', $content, $attr, $table );

		$content = str_replace( '%display_name%', $user->display_name, $content );
		$content = str_replace( '%first_name%',   $user->first_name, $content );
		$content = str_replace( '%last_name%',    $user->last_name, $content );
		$content = str_replace( '%user_email%',   $user->user_email, $content );
		$content = str_replace( '%user_login%',   $user->user_login, $content );

		$content = str_replace( '%avatar%',       get_avatar( $result->user_id, $avatar ), $content );
		$content = str_replace( '%total%',        $result->total, $content );
		$content = str_replace( '%total_abs%',    abs( $result->total ), $content );
		$content = str_replace( '%count%',        $result->count, $content );

		return apply_filters( 'mycred_render_best_user', $content, $result, $attr, $table );

	}
endif;
add_shortcode( 'mycred_best_user', 'mycred_render_shortcode_best_user' );

?>