<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Affiliate ID
 * @since 1.5.3
 * @version 
 */
if ( ! function_exists( 'mycred_render_affiliate_id' ) ) :
	function mycred_render_affiliate_id( $atts, $content = '' ) {

		$type = MYCRED_DEFAULT_TYPE_KEY;
		if ( isset( $atts['type'] ) && $atts['type'] != '' )
			$type = $atts['type'];

		return apply_filters( 'mycred_affiliate_id_' . $type, '', $atts, $content );

	}
endif;
add_shortcode( 'mycred_affiliate_id', 'mycred_render_affiliate_id' );
