<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Shortcode: mycred_give
 * This shortcode allows you to award or deduct points from a given user or the current user
 * when this shortcode is executed. You can insert this in page/post content
 * or in a template file. Note that users are awarded/deducted points each time
 * this shortcode exectutes!
 * @see http://codex.mycred.me/shortcodes/mycred_give/
 * @since 1.1
 * @version 1.3
 */
if ( ! function_exists( 'mycred_render_shortcode_give' ) ) :
	function mycred_render_shortcode_give( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'amount'  => '',
			'user_id' => 'current',
			'log'     => '',
			'ref'     => 'gift',
			'limit'   => 0,
			'type'    => MYCRED_DEFAULT_TYPE_KEY
		), $atts ) );

		if ( ! is_user_logged_in() && $user_id == 'current' )
			return $content;

		if ( ! mycred_point_type_exists( $type ) ) return 'Invalid point type.';

		$mycred  = mycred( $type );
		$user_id = mycred_get_user_id( $user_id );
		$ref     = sanitize_key( $ref );
		$limit   = absint( $limit );

		// Check for exclusion
		if ( $mycred->exclude_user( $user_id ) ) return;

		// Limit
		if ( $limit > 0 && mycred_count_ref_instances( $ref, $user_id, $type ) >= $limit ) return;

		$mycred->add_creds(
			$ref,
			$user_id,
			$amount,
			$log,
			'',
			'',
			$type
		);

	}
endif;
add_shortcode( 'mycred_give', 'mycred_render_shortcode_give' );

?>