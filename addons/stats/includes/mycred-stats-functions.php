<?php
if ( ! defined( 'myCRED_STATS_VERSION' ) ) exit;

/**
 * Get Stats Days
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_stat_dates' ) ) :
	function mycred_get_stat_dates( $instance = '', $value = 0 ) {

		$now = current_time( 'timestamp' );

		$results = array();
		switch ( $instance ) {

			case 'x_dates' :

				$from = $value - 1;
				$start = date( 'U', strtotime( '-' . $from . ' days midnight', $now ) );
				for ( $i = 0 ; $i < $value ; $i ++ ) {
					if ( $i == 0 )
						$new_start = $start;
					else
						$new_start = $start + ( 86400 * $i );

					$results[] = array(
						'label' => date( 'Y-m-d', $new_start ),
						'from'  => $new_start,
						'until' => ( $new_start + 86399 )
					);
				}

			break;

			case 'today_this' :

				$start = date( 'U', strtotime( 'today midnight', $now ) );
				$results[] = array(
					'key'   => 'today',
					'from'  => $start,
					'until' => $now
				);

				$this_week = mktime( 0, 0, 0, date( "n", $now ), date( "j", $now ) - date( "N", $now ) + 1 );
				$results[] = array(
					'key'   => 'thisweek',
					'from'  => $this_week,
					'until' => $now
				);

				$this_month = mktime( 0, 0, 0, date( "n", $now ), 1, date( 'Y', $now ) );
				$results[] = array(
					'key'   => 'thismonth',
					'from'  => $this_month,
					'until' => $now
				);

				$this_year = mktime( 0, 0, 0, 1, 1, date( 'Y', $now ) );
				$results[] = array(
					'key'   => 'thisyear',
					'from'  => $this_year,
					'until' => $now
				);

			break;

		}
		return $results;

	}
endif;

/**
 * Get Type Color
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_type_color' ) ) :
	function mycred_get_type_color( $type = NULL ) {

		//$set = array( 'rgba(221,73,167,1)', 'rgba(106,187,218,1)', 'rgba(111,70,161,1)' );
		//$set = array( 'rgba(213,78,33,1)', 'rgba(46,162,204,1)', 'rgba(34,34,34,1)' );
		$set   = array( 'rgba(204,175,11,1)', 'rgba(221,130,59,1)', 'rgba(207,73,68,1)', 'rgba(180,60,56,1)', 'rgba(34,34,34,1)' );
		$types = mycred_get_types();
		$saved = mycred_get_option( 'mycred-point-colors', $set );

		$colors = array();
		$row    = 0;
		foreach ( $types as $type_id => $label ) {

			if ( array_key_exists( $type_id, $saved ) )
				$value = $saved[ $type_id ];
			else
				$value = $set[ $row ];

			$colors[ $type_id ] = $value;
			$row ++;

		}

		$result = $colors;
		if ( $type !== NULL && array_key_exists( $type, $colors ) )
			$result = $colors[ $type ];

		return apply_filters( 'mycred_point_type_colors', $result, $set, $type, $types );

	}
endif;

/**
 * RGB to HEX
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_rgb_to_hex' ) ) :
	function mycred_rgb_to_hex( $rgb = '' ) {

		if ( ! is_array( $rgb ) ) {
			$rgb = str_replace( array( ' ', 'rgb(', 'rgba(', ')' ), '', $rgb );
			$rgb = explode( ',', $rgb );
		}

		$hex = "#";
		$hex .= str_pad( dechex( $rgb[0] ), 2, "0", STR_PAD_LEFT );
		$hex .= str_pad( dechex( $rgb[1] ), 2, "0", STR_PAD_LEFT );
		$hex .= str_pad( dechex( $rgb[2] ), 2, "0", STR_PAD_LEFT );

		return $hex;
	}
endif;

/**
 * HEX to RGB
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_hex_to_rgb' ) ) :
	function mycred_hex_to_rgb( $hex = '', $rgba = true ) {

		$hex = str_replace( '#', '', $hex );

		if ( strlen( $hex ) == 3 ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}
		$rgb = array( $r, $g, $b );

		if ( $rgba )
			$rgb = 'rgba(' . implode( ',', $rgb ) . ',1)';
		else
			$rgb = 'rgb(' . implode( ',', $rgb ) . ')';

		return $rgb;

}
endif;

/**
 * Inverse HEX colors
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_inverse_hex_color' ) ) :
	function mycred_inverse_hex_color( $color = '' ) {

		$color = str_replace( '#', '', $color );
		if ( strlen( $color ) != 6 ) { return '000000'; }
		$rgb = '';
		for ( $x = 0 ; $x < 3 ; $x++ ) {
			$c = 255 - hexdec( substr( $color, ( 2*$x ), 2 ) );
			$c = ( $c < 0 ) ? 0 : dechex( $c );
			$rgb .= ( strlen( $c ) < 2 ) ? '0' . $c : $c;
		}
		return '#' . $rgb;

	}
endif;

/**
 * Inverse RGB color
 * @since 1.6
 * @version 1.0
 */
if ( ! function_exists( 'mycred_inverse_rgb_color' ) ) :
	function mycred_inverse_rgb_color( $color = '' ) {

		$color    = mycred_rgb_to_hex( $color );
		$inversed = mycred_inverse_hex_color( $color );
		$inversed = mycred_hex_to_rgb( $inversed );
		return $inversed;

	}
endif;
?>