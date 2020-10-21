<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Get Badge
 * @since 1.7
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_badge' ) ) :
	function mycred_get_badge( $badge_id = NULL, $level = NULL ) {

		if ( absint( $badge_id ) === 0 || mycred_get_post_type( $badge_id ) != MYCRED_BADGE_KEY ) return false;

		global $mycred_badge;

		$badge_id     = absint( $badge_id );

		if ( isset( $mycred_badge )
			&& ( $mycred_badge instanceof myCRED_Badge )
			&& ( $badge_id === $mycred_badge->post_id )
		) {
			return $mycred_badge;
		}

		$mycred_badge = new myCRED_Badge( $badge_id, $level );

		do_action( 'mycred_get_badge' );

		return $mycred_badge;

	}
endif;

/**
 * Get Badge References
 * Returns an array of references used by badges for quicker checks.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_badge_references' ) ) :
	function mycred_get_badge_references( $point_type = MYCRED_DEFAULT_TYPE_KEY, $force = false ) {

		$references = mycred_get_option( 'mycred-badge-refs-' . $point_type );
		if ( ! is_array( $references ) || empty( $references ) || $force ) {

			global $wpdb;

			$new_list = array();

			// Old versions
			$references = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'badge_requirements';" );
			if ( ! empty( $references ) ) {
				foreach ( $references as $entry ) {

					$requirement = maybe_unserialize( $entry->meta_value );
					if ( ! is_array( $requirement ) || empty( $requirement ) ) continue;

					if ( ! array_key_exists( 'type', $requirement ) || $requirement['type'] != $point_type || $requirement['reference'] == '' ) continue;

					if ( ! array_key_exists( $requirement['reference'], $new_list ) )
						$new_list[ $requirement['reference'] ] = array();

					if ( ! in_array( $entry->post_id, $new_list[ $requirement['reference'] ] ) )
						$new_list[ $requirement['reference'] ][] = $entry->post_id;

				}
			}


			// New version (post 1.7)
			$table      = mycred_get_db_column( 'postmeta' );
			$references = $wpdb->get_results( "SELECT post_id, meta_value FROM {$table} WHERE meta_key = 'badge_prefs';" );
			if ( ! empty( $references ) ) {
				foreach ( $references as $entry ) {

					// Manual badges should be ignored
					if ( absint( mycred_get_post_meta( $entry->post_id, 'manual_badge', true ) ) === 1 ) continue;

					$levels = maybe_unserialize( $entry->meta_value );
					if ( ! is_array( $levels ) || empty( $levels ) ) continue;

					foreach ( $levels as $level => $setup ) {

						if ( $level > 0 ) continue;

						foreach ( $setup['requires'] as $requirement_row => $requirement ) {

							if ( $requirement['type'] != $point_type || $requirement['reference'] == '' ) continue;

							if ( ! array_key_exists( $requirement['reference'], $new_list ) )
								$new_list[ $requirement['reference'] ] = array();

							if ( ! in_array( $entry->post_id, $new_list[ $requirement['reference'] ] ) )
								$new_list[ $requirement['reference'] ][] = $entry->post_id;

						}

					}

				}
			}

			if ( ! empty( $new_list ) )
				mycred_update_option( 'mycred-badge-references-' . $point_type, $new_list );

			$references = $new_list;

		}

		return apply_filters( 'mycred_get_badge_references', $references, $point_type );

	}
endif;

/**
 * Get Badge Requirements
 * Returns the badge requirements as an array.
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_badge_requirements' ) ) :
	function mycred_get_badge_requirements( $badge_id = NULL ) {

		return mycred_get_badge_levels( $badge_id );

	}
endif;

/**
 * Get Badge Levels
 * Returns an array of levels associated with a given badge.
 * @since 1.7
 * @version 1.0.1
 */
if ( ! function_exists( 'mycred_get_badge_levels' ) ) :
	function mycred_get_badge_levels( $badge_id ) {

		$setup = mycred_get_post_meta( $badge_id, 'badge_prefs', true );
		if ( ! is_array( $setup ) || empty( $setup ) ) {

			// Backwards comp.
			$old_setup = mycred_get_post_meta( $badge_id, 'badge_requirements', true );

			// Convert old setup to new
			if ( is_array( $old_setup ) && ! empty( $old_setup ) ) {

				$new_setup = array();
				foreach ( $old_setup as $level => $requirements ) {

					$level_image = mycred_get_post_meta( $badge_id, 'level_image' . $level, true );
					if ( $level_image == '' || $level == 0 )
						$level_image = mycred_get_post_meta( $badge_id, 'main_image', true );

					$row = array(
						'image_url'     => $level_image,
						'attachment_id' => 0,
						'label'         => '',
						'compare'       => 'AND',
						'requires'      => array(),
						'reward'        => array(
							'type'   => MYCRED_DEFAULT_TYPE_KEY,
							'amount' => 0,
							'log'    => ''
						)
					);

					$row['requires'][] = $requirements;

					$new_setup[] = $row;

				}

				if ( ! empty( $new_setup ) ) {

					mycred_update_post_meta( $badge_id, 'badge_prefs', $new_setup );
					mycred_delete_post_meta( $badge_id, 'badge_requirements' );

					$setup = $new_setup;

				}

			}

		}

		if ( empty( $setup ) && ! is_array( $setup ) )
			$setup = array();

		if ( empty( $setup ) )
			$setup[] = array(
				'image_url'     => '',
				'attachment_id' => 0,
				'label'         => '',
				'compare'       => 'AND',
				'requires'      => array(
					0 => array(
						'type'      => MYCRED_DEFAULT_TYPE_KEY,
						'reference' => '',
						'amount'    => '',
						'by'        => ''
					)
				),
				'reward'        => array(
					'type'   => MYCRED_DEFAULT_TYPE_KEY,
					'amount' => 0,
					'log'    => ''
				)
			);

		return apply_filters( 'mycred_badge_levels', $setup, $badge_id );

	}
endif;

/**
 * Display Badge Requirements
 * Returns the badge requirements as a string in a readable format.
 * @since 1.5
 * @version 1.2.2
 */
if ( ! function_exists( 'mycred_display_badge_requirement' ) ) :
	function mycred_display_badge_requirements( $badge_id = NULL ) {

		$levels = mycred_get_badge_levels( $badge_id );
		if ( empty( $levels ) ) {

			$reply = '-';

		}
		else {

			$point_types = mycred_get_types( true );
			$references  = mycred_get_all_references();
			$req_count   = count( $levels[0]['requires'] );

			// Get the requirements for the first level
			$base_requirements = array();
			foreach ( $levels[0]['requires'] as $requirement_row => $requirement ) {

				if ( $requirement['type'] == '' )
					$requirement['type'] = MYCRED_DEFAULT_TYPE_KEY;

				if ( ! array_key_exists( $requirement['type'], $point_types ) )
					continue;

				if ( ! array_key_exists( $requirement['reference'], $references ) )
					$reference = '-';
				else
					$reference = $references[ $requirement['reference'] ];

				$base_requirements[ $requirement_row ] = array(
					'type'   => $requirement['type'],
					'ref'    => $reference,
					'amount' => $requirement['amount'],
					'by'     => $requirement['by']
				);

			}

			// Loop through each level
			$output = array();
			foreach ( $levels as $level => $setup ) {

				$level_label = '<strong>' . sprintf( __( 'Level %s', 'mycred' ), ( $level + 1 ) ) . ':</strong>';
				if ( $levels[ $level ]['label'] != '' )
					$level_label = '<strong>' . $levels[ $level ]['label'] . ':</strong>';

				// Construct requirements to be used in an unorganized list.
				$level_req = array();
				foreach ( $setup['requires'] as $requirement_row => $requirement ) {

					$level_value = $requirement['amount'];
					$requirement = $base_requirements[ $requirement_row ];

					$mycred = mycred( $requirement['type'] );

					if ( $level > 0 )
						$requirement['amount'] = $level_value;

					if ( $requirement['by'] == 'count' )
						$rendered_row = sprintf( _x( '%s for "%s" x %d', '"Points" for "reference" x times', 'mycred' ), $mycred->plural(), $requirement['ref'], $requirement['amount'] );
					else
						$rendered_row = sprintf( _x( '%s %s for "%s"', '"Gained/Lost" "x points" for "reference"', 'mycred' ), ( ( $requirement['amount'] < 0 ) ? __( 'Lost', 'mycred' ) : __( 'Gained', 'mycred' ) ), $mycred->format_creds( $requirement['amount'] ), $requirement['ref'] );

					$compare = _x( 'OR', 'Comparison of badge requirements. A OR B', 'mycred' );
					if ( $setup['compare'] === 'AND' )
						$compare = _x( 'AND', 'Comparison of badge requirements. A AND B', 'mycred' );

					if ( $req_count > 1 && $requirement_row+1 < $req_count )
						$rendered_row .= ' <span>' . $compare . '</span>';

					$level_req[] = $rendered_row;

				}

				if ( empty( $level_req ) ) continue;

				$output[] = $level_label . '<ul class="mycred-badge-requirement-list"><li>' . implode( '</li><li>', $level_req ) . '</li></ul>';

			}

			if ( (int) mycred_get_post_meta( $badge_id, 'manual_badge', true ) === 1 )
				$output[] = '<strong><small><em>' . __( 'This badge is manually awarded.', 'mycred' ) . '</em></small></strong>';

			$reply = implode( '', $output );

		}

		return apply_filters( 'mycred_badge_display_requirements', $reply, $badge_id );

	}
endif;

/**
 * Count Users with Badge
 * Counts the number of users that has the given badge. Option to get count
 * of a specific level.
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_count_users_with_badge' ) ) :
	function mycred_count_users_with_badge( $badge_id = NULL, $level = NULL ) {

		$badge_id = absint( $badge_id );

		if ( $badge_id === 0 ) return false;

		// Get the badge object
		$badge    = mycred_get_badge( $badge_id );

		// Most likely not a badge post ID
		if ( $badge === false ) return false;

		return $badge->get_user_count( $level );

	}
endif;

/**
 * Count Users without Badge
 * Counts the number of users that does not have a given badge.
 * @since 1.5
 * @version 1.2
 */
if ( ! function_exists( 'mycred_count_users_without_badge' ) ) :
	function mycred_count_users_without_badge( $badge_id = NULL ) {

		$total      = count_users();
		$with_badge = mycred_count_users_with_badge( $badge_id );
		if ( $with_badge === false ) $with_badge = 0;

		$without_badge = $total['total_users'] - $with_badge;

		return apply_filters( 'mycred_count_users_without_badge', absint( $without_badge ), $badge_id );

	}
endif;

/**
 * Reference Has Badge
 * Checks if a given reference has a badge associated with it.
 * @since 1.5
 * @version 1.4
 */
if ( ! function_exists( 'mycred_ref_has_badge' ) ) :
	function mycred_ref_has_badge( $reference = NULL, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

		$badge_ids        = array();
		if ( $reference === NULL || strlen( $reference ) == 0 || ! mycred_point_type_exists( $point_type ) ) return $badge_ids;

		$badge_references = mycred_get_badge_references( $point_type );
		$badge_references = maybe_unserialize( $badge_references );

		if ( ! empty( $badge_references ) && array_key_exists( $reference, $badge_references ) )
			$badge_ids = $badge_references[ $reference ];

		if ( empty( $badge_ids ) )
			$badge_ids = false;

		return apply_filters( 'mycred_ref_has_badge', $badge_ids, $reference, $badge_references, $point_type );

	}
endif;

/**
 * Badge Level Reached
 * Checks what level a user has earned for a badge. Returns false if badge was not earned.
 * @since 1.7
 * @version 1.1
 */
if ( ! function_exists( 'mycred_badge_level_reached' ) ) :
	function mycred_badge_level_reached( $user_id = NULL, $badge_id = NULL ) {

		$user_id  = absint( $user_id );
		$badge_id = absint( $badge_id );

		if ( $user_id === 0 || $badge_id === 0 ) return false;

		// Get the badge object
		$badge    = mycred_get_badge( $badge_id );

		// Most likely not a badge post ID
		if ( $badge === false ) return false;

		return $badge->query_users_level( $user_id );

	}
endif;

/**
 * Check if User Gets Badge
 * Checks if a given user has earned one or multiple badges.
 * @since 1.5
 * @version 1.4
 */
if ( ! function_exists( 'mycred_check_if_user_gets_badge' ) ) :
	function mycred_check_if_user_gets_badge( $user_id = NULL, $badge_ids = array(), $depreciated = array(), $save = true ) {

		$user_id          = absint( $user_id );
		if ( $user_id === 0 ) return false;

		$earned_badge_ids = array();
		if ( ! empty( $badge_ids ) ) {
			foreach ( $badge_ids as $badge_id ) {

				$badge         = mycred_get_badge( $badge_id );
				if ( $badge === false ) continue;

				$level_reached = $badge->get_level_reached( $user_id );
				if ( $level_reached !== false ) {

					if ( $save )
						$badge->assign( $user_id, $level_reached );

					$earned_badge_ids[] = $badge_id;

				}

			}
		}

		return $earned_badge_ids;

	}
endif;

/**
 * Assign Badge
 * Assigns a given badge to all users that fulfill the badges requirements.
 * @since 1.7
 * @version 1.2
 */
if ( ! function_exists( 'mycred_assign_badge' ) ) :
	function mycred_assign_badge( $badge_id = NULL ) {

		$user_id  = absint( $user_id );
		$badge_id = absint( $badge_id );

		if ( $user_id === 0 || $badge_id === 0 ) return false;

		// Get the badge object
		$badge    = mycred_get_badge( $badge_id );

		// Most likely not a badge post ID
		if ( $badge === false ) return false;

		return $badge->assign_all();

	}
endif;

/**
 * Assign Badge to User
 * @since 1.7
 * @version 1.1
 */
if ( ! function_exists( 'mycred_assign_badge_to_user' ) ) :
	function mycred_assign_badge_to_user( $user_id = NULL, $badge_id = NULL, $level = 0 ) {

		$user_id  = absint( $user_id );
		$badge_id = absint( $badge_id );
		$level    = absint( $level );

		if ( $user_id === 0 || $badge_id === 0 ) return false;

		// Get the badge object
		$badge    = mycred_get_badge( $badge_id );

		// Most likely not a badge post ID
		if ( $badge === false ) return false;

		return $badge->assign( $user_id, $level );

	}
endif;

/**
 * User Has Badge
 * Checks if a user has a particular badge by badge ID.
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'mycred_user_has_badge' ) ) :
	function mycred_user_has_badge( $user_id = 0, $badge_id = NULL, $level_id = 0 ) {

		$user_id  = absint( $user_id );
		$badge_id = absint( $badge_id );
		$level_id = absint( $level_id );

		if ( $user_id === 0 || $badge_id === 0 ) return false;

		global $mycred_current_account;

		if ( mycred_is_current_account( $user_id ) && isset( $mycred_current_account->badge_ids ) && ! empty( $mycred_current_account->badge_ids ) ) {

			$has_badge = array_key_exists( $badge_id, $mycred_current_account->badge_ids );

		}
		else {

			// Get the badge object
			$badge    = mycred_get_badge( $badge_id );

			// Most likely not a badge post ID
			if ( $badge !== false )
				$has_badge = $badge->user_has_badge( $user_id, $level_id );

		}

		return $has_badge;

	}
endif;

/**
 * Get Users Badge Level
 * @since 1.8
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_users_badge_level' ) ) :
	function mycred_get_users_badge_level( $user_id = 0, $badge_id = NULL ) {

		$user_id  = absint( $user_id );
		$badge_id = absint( $badge_id );

		if ( $user_id === 0 || $badge_id === 0 ) return false;

		global $mycred_current_account;

		if ( mycred_is_current_account( $user_id ) && isset( $mycred_current_account->badges ) && ! empty( $mycred_current_account->badges ) && array_key_exists( $badge_id, $mycred_current_account->badges ) )
			return $mycred_current_account->badges[ $badge_id ]->level_id;

		// Get the badge object
		$badge    = mycred_get_badge( $badge_id );

		// Most likely not a badge post ID
		if ( $badge === false ) return false;

		return $badge->get_users_current_level( $user_id );

	}
endif;

/**
 * Get Users Badges
 * Returns the badge post IDs that a given user currently holds.
 * @since 1.5
 * @version 1.3
 */
if ( ! function_exists( 'mycred_get_users_badges' ) ) :
	function mycred_get_users_badges( $user_id = NULL, $force = false ) {

		if ( $user_id === NULL ) return array();

		global $mycred_current_account;

		if ( mycred_is_current_account( $user_id ) && isset( $mycred_current_account->badge_ids ) && $force == false )
			return $mycred_current_account->badge_ids;

		$badge_ids = mycred_get_user_meta( $user_id, MYCRED_BADGE_KEY . '_ids', '', true );
		if ( !isset($badge_ids) || $badge_ids == '' || $force ) {

			global $wpdb;

			$badge_ids = array();
			$query     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s", $user_id, mycred_get_meta_key( MYCRED_BADGE_KEY ) . '%' ) );

			if ( ! empty( $query ) ) {

				foreach ( $query as $badge ) {

					$badge_id = str_replace( MYCRED_BADGE_KEY, '', $badge->meta_key );
					if ( $badge_id == '' ) continue;
				
					$badge_id = absint( $badge_id );
					if ( ! array_key_exists( $badge_id, $badge_ids ) )
						$badge_ids[ $badge_id ] = absint( $badge->meta_value );

				}

				mycred_update_user_meta( $user_id, MYCRED_BADGE_KEY . '_ids', '', $badge_ids );

			}

		}

		$clean_ids = array();
		if ( ! empty( $badge_ids ) ) {
			foreach ( $badge_ids as $id => $level ) {

				$id = absint( $id );
				if ( $id === 0 || strlen( $level ) < 1 ) continue;
				$clean_ids[ $id ] = absint( $level );

			}
		}

		return apply_filters( 'mycred_get_users_badges', $clean_ids, $user_id );

	}
endif;

/**
 * Display Users Badges
 * Will echo all badge images a given user has earned.
 * @since 1.5
 * @version 1.3.2
 */
if ( ! function_exists( 'mycred_display_users_badges' ) ) :
	function mycred_display_users_badges( $user_id = NULL, $width = MYCRED_BADGE_WIDTH, $height = MYCRED_BADGE_HEIGHT ) {

		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return;

		$users_badges = mycred_get_users_badges( $user_id );

		echo '<div class="row" id="mycred-users-badges"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';

		do_action( 'mycred_before_users_badges', $user_id, $users_badges );

		if ( ! empty( $users_badges ) ) {

			foreach ( $users_badges as $badge_id => $level ) {

				$badge = mycred_get_badge( $badge_id, $level );
				if ( $badge === false ) continue;

				$badge->image_width  = $width;
				$badge->image_height = $height;

				if ( $badge->level_image !== false )
					echo apply_filters( 'mycred_the_badge', $badge->get_image( $level ), $badge_id, $badge, $user_id );

			}

		}

		do_action( 'mycred_after_users_badges', $user_id, $users_badges );

		echo '</div></div>';

	}
endif;

/**
 * Get Badge IDs
 * Returns all published badge post IDs.
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_get_badge_ids' ) ) :
	function mycred_get_badge_ids() {

		$badge_ids = wp_cache_get( 'badge_ids', MYCRED_SLUG );
		if ( $badge_ids !== false && is_array( $badge_ids ) ) return $badge_ids;

		global $wpdb;

		$table     = mycred_get_db_column( 'posts' );
		$badge_ids = $wpdb->get_col( $wpdb->prepare( "
			SELECT ID 
			FROM {$table} 
			WHERE post_type = %s 
			AND post_status = 'publish' 
			ORDER BY post_date ASC;", MYCRED_BADGE_KEY ) );

		wp_cache_set( 'badge_ids', $badge_ids, MYCRED_SLUG );

		return apply_filters( 'mycred_get_badge_ids', $badge_ids );

	}
endif;
