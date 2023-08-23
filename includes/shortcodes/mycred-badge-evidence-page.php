<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Renders Badge's Evidence ShortCode
 * @param string $atts
 * @since 2.1
 * @version 1.0
 */
if ( !function_exists( 'mycred_render_badge_evidence' ) ) :
    function mycred_render_badge_evidence( $atts = '' ) {

        $content = '<div class="mycred-evidence-page">Evidence not found</div>'; 

        if ( isset( $_GET['uid'] ) && isset( $_GET['bid'] ) ) {

            $user_id  = intval( $_GET['uid'] );
            $badge_id = intval( $_GET['bid'] );

            $user_info = get_userdata( $user_id );
            $post  = get_post( $badge_id );
            
            $issued_on = '';
            if( $post->post_type == 'mycred_badge' ){
                $badge = mycred_get_badge( $badge_id );
                $issued_on = mycred_get_user_meta( $user_id, MYCRED_BADGE_KEY . $badge_id, '_issued_on', true );

            }

            if ( $post->post_type == 'mycred_badge_plus' ) {
                $badge = mycred_badge_plus_object( $badge_id );
                $issued_on = end( mycred_get_user_meta( $user_id, 'mycred_badge_plus_ids', '', true )[$badge_id] );
            }

            if ( $user_info && $badge->open_badge ) {

                $content = '<div class="mycred-evidence-page">
                                <div class="mycred-left">
                                    <img src="' . $badge->get_earned_image( $user_id ) . '" alt="">
                                </div>
                                <div class="mycred-left intro">
                                    <h4 class="mycred-remove-margin">' . $badge->title . '</h4>
                                    <div class="mycred-remove-margin">
                                        <p>Name: '. $user_info->display_name .'</p>
                                        <p>Email: ' . $user_info->user_email . '</p>
                                        <p>Issued On: ' . date( 'Y-m-d\TH:i:sP', $issued_on ) . '</p>
                                        <p><span class="dashicons dashicons-yes-alt"></span> <span class="icon-txt"> Verified</span></p>
                                    </div>
                                </div>
                                <div class="mycred-clear"></div>
                            </div>';

            }
            
            
        }

        return $content;
    }
endif;

add_shortcode( MYCRED_SLUG . '_badge_evidence', 'mycred_render_badge_evidence' );