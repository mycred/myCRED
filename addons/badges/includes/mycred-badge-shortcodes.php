<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Shortcode: mycred_my_badges
 * Allows you to show the current users earned badges.
 * @since 1.5
 * @version 1.2.1
 */
if ( ! function_exists( 'mycred_render_my_badges' ) ) :
	function mycred_render_my_badges( $atts, $content = '' ) {

		extract( shortcode_atts( array(
			'show'     => 'earned',
			'width'    => MYCRED_BADGE_WIDTH,
			'height'   => MYCRED_BADGE_HEIGHT,
			'user_id'  => 'current'
		), $atts, MYCRED_SLUG . '_my_badges' ) );

		if ( ! is_user_logged_in() && $user_id == 'current' )
			return $content;

		$user_id = mycred_get_user_id( $user_id );

		ob_start();

		echo '<div class="row" id="mycred-users-badges"><div class="col-xs-12">';

		// Show only badges that we have earned
		if ( $show == 'earned' ) {

			mycred_display_users_badges( $user_id, $width, $height );

		}

		// Show all badges highlighting the ones we earned
		elseif ( $show == 'all' ) {

			$users_badges = mycred_get_users_badges( $user_id );
			$all_badges   = mycred_get_badge_ids();

			foreach ( $all_badges as $badge_id ) {

				echo '<div class="the-badge">';

				// User has not earned badge
				if ( ! array_key_exists( $badge_id, $users_badges ) ) {

					$badge = mycred_get_badge( $badge_id );
					$badge->image_width  = $width;
					$badge->image_height = $height;

					if ( $badge->main_image !== false )
						echo $badge->get_image( 'main' );

				}

				// User has earned badge
				else {

					$level = $users_badges[ $badge_id ];
					$badge = mycred_get_badge( $badge_id, $level );
					$badge->image_width  = $width;
					$badge->image_height = $height;

					if ( $badge->level_image !== false )
						echo $badge->get_image( $level );

				}

				echo '</div>';

			}

		}
		echo '</div></div>';

		$output = ob_get_contents();
		ob_end_clean();

		return apply_filters( 'mycred_my_badges', $output, $user_id );

	}
endif;

/**
 * Shortcode: mycred_badges
 * Allows you to show all published badges
 * @since 1.5
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_render_badges' ) ) :
	function mycred_render_badges( $atts, $template = '' ) {

		extract( shortcode_atts( array(
			'width'  => MYCRED_BADGE_WIDTH,
			'height' => MYCRED_BADGE_HEIGHT
		), $atts, MYCRED_SLUG . '_badges' ) );

		$all_badges = mycred_get_badge_ids();

		if ( $template == '' )
			$template = '<div class="the-badge row"><div class="col-xs-12"><h3 class="badge-title">%badge_title%</h3><div class="badge-requirements">%requirements%</div><div class="users-with-badge">%count%</div><div class="badge-images">%default_image% %main_image%</div></div></div>';

		$output = '<div id="mycred-all-badges">';

		if ( ! empty( $all_badges ) ) {

			foreach ( $all_badges as $badge_id ) {

				$badge               = mycred_get_badge( $badge_id, 0 );
				$badge->image_width  = $width;
				$badge->image_height = $height;

				$row = $template;
				$row = str_replace( '%badge_title%',   $badge->title,                                  $row );
				$row = str_replace( '%requirements%',  mycred_display_badge_requirements( $badge_id ), $row );
				$row = str_replace( '%count%',         $badge->earnedby,                               $row );
				$row = str_replace( '%default_image%', $badge->main_image,                             $row );
				
				if( mycred_user_has_badge( get_current_user_id(), $badge_id) ) {
					$row = str_replace( '%main_image%',    $badge->level_image, $row );
				}
				else {
					$row = str_replace( '%main_image%',    '', $row );
				}

				$output .= apply_filters( 'mycred_badges_badge', $row, $badge );

			}

		}

		$output .= '</div>';

		return apply_filters( 'mycred_badges', $output );

	}
endif;
