<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Affiliate Link
 * @since 1.5.3
 * @version 
 */
if ( ! function_exists( 'mycred_render_affiliate_link' ) ) :
	function mycred_render_affiliate_link( $atts, $content = '' ) {

		$type = MYCRED_DEFAULT_TYPE_KEY;
		if ( isset( $atts['type'] ) && $atts['type'] != '' )
			$type = $atts['type'];

		return apply_filters( 'mycred_affiliate_link_' . $type, '', $atts, $content );

	}
endif;
add_shortcode( 'mycred_affiliate_link', 'mycred_render_affiliate_link' );
