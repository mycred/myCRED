<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Get Badge
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_badge' ) ) :
	function mycred_get_badge( $badge_id = NULL, $level = NULL ) {

		if ( absint( $badge_id ) === 0 || get_post_type( $badge_id ) != 'mycred_badge' ) return false;

		$badge = new myCRED_Badge( $badge_id, $level );

		return apply_filters( 'mycred_get_badge', $badge, $badge_id, $level );

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
			$references = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'badge_prefs';" );
			if ( ! empty( $references ) ) {
				foreach ( $references as $entry ) {

					// Manual badges should be ignored
					if ( absint( get_post_meta( $entry->post_id, 'manual_badge', true ) ) === 1 ) continue;

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
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_badge_levels' ) ) :
	function mycred_get_badge_levels( $badge_id ) {

		$setup = get_post_meta( $badge_id, 'badge_prefs', true );
		if ( ! is_array( $setup ) || empty( $setup ) ) {

			// Backwards comp.
			$old_setup = get_post_meta( $badge_id, 'badge_requirements', true );

			// Convert old setup to new
			if ( is_array( $old_setup ) && ! empty( $old_setup ) ) {

				$new_setup = array();
				foreach ( $old_setup as $level => $requirements ) {

					$level_image = get_post_meta( $badge_id, 'level_image' . $level, true );
					if ( $level_image == '' || $level == 0 )
						$level_image = get_post_meta( $badge_id, 'main_image', true );

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

					update_post_meta( $badge_id, 'badge_prefs', $new_setup );
					delete_post_meta( $badge_id, 'badge_requirements' );

					$setup = $new_setup;

				}

			}

		}

		//$setup = array();

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
 * @version 1.2.1
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
				if ( $levels[0]['label'] != '' )
					$level_label = '<strong>' . $levels[0]['label'] . ':</strong>';

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

			if ( (int) get_post_meta( $badge_id, 'manual_badge', true ) === 1 )
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

		if ( $badge_id === NULL ) return 0;

		$count = get_post_meta( $badge_id, 'total-users-with-badge', true );

		if ( $count == '' || $level !== NULL ) {

			global $wpdb;

			$level_filter = '';
			if ( $level !== NULL && is_numeric( $level ) )
				$level_filter = $wpdb->prepare( "AND meta_value = %s", $level );

			$count = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT( DISTINCT user_id ) 
				FROM {$wpdb->usermeta} 
				WHERE meta_key = %s {$level_filter};", mycred_get_meta_key( 'mycred_badge' . $badge_id ) ) );

			if ( $count === NULL ) $count = 0;

			if ( $count > 0 && $level === NULL )
				add_post_meta( $badge_id, 'total-users-with-badge', $count, true );

		}

		return apply_filters( 'mycred_count_users_with_badge', absint( $count ), $badge_id );

	}
endif;

/**
 * Count Users without Badge
 * Counts the number of users that does not have a given badge.
 * @since 1.5
 * @version 1.1
 */
if ( ! function_exists( 'mycred_count_users_without_badge' ) ) :
	function mycred_count_users_without_badge( $badge_id = NULL ) {

		$total      = count_users();
		$with_badge = mycred_count_users_with_badge( $badge_id );

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

		$badge_references = mycred_get_badge_references( $point_type );
		$badge_references = maybe_unserialize( $badge_references );

		$badge_ids = array();
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
 * @version 1.0
 */
if ( ! function_exists( 'mycred_badge_level_reached' ) ) :
	function mycred_badge_level_reached( $user_id = NULL, $badge_id = NULL ) {

		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return false;

		$badge_id = absint( $badge_id );
		if ( $badge_id === 0 ) return false;

		global $wpdb;

		$levels            = mycred_get_badge_levels( $badge_id );
		if ( empty( $levels ) ) return false;

		$base_requirements = $levels[0]['requires'];
		$compare           = $levels[0]['compare'];
		$requirements      = count( $base_requirements );
		$level_reached     = false;
		$results           = array();

		// Based on the base requirements, we first get the users log entry results
		if ( ! empty( $base_requirements ) ) {
			foreach ( $base_requirements as $requirement_id => $requirement ) {

				if ( $requirement['type'] == '' )
					$requirement['type'] = MYCRED_DEFAULT_TYPE_KEY;

				$mycred = mycred( $requirement['type'] );
				if ( $mycred->exclude_user( $user_id ) ) continue;

				$having = 'COUNT(*)';
				if ( $requirement['by'] != 'count' )
					$having = 'SUM(creds)';

				$query = $wpdb->get_var( $wpdb->prepare( "SELECT {$having} FROM {$mycred->log_table} WHERE ctype = %s AND ref = %s AND user_id = %d;", $requirement['type'], $requirement['reference'], $user_id ) );
				if ( $query === NULL ) $query = 0;

				$results[ $requirement['reference'] ] = $query;

			}
		}

		// Next we loop through the levels and see compare the previous results to the requirements to determan our level
		foreach ( $levels as $level_id => $level_setup ) {

			$reqs_met = 0;
			foreach ( $level_setup['requires'] as $requirement_id => $requirement ) {

				if ( $results[ $requirement['reference'] ] >= $requirement['amount'] )
					$reqs_met++;

			}

			if ( $compare === 'AND' && $reqs_met >= $requirements )
				$level_reached = $level_id;

			elseif ( $compare === 'OR' && $reqs_met > 0 )
				$level_reached = $level_id;

		}

		do_action( 'mycred_badge_level_reached', $user_id, $badge_id, $level_reached );

		return $level_reached;

	}
endif;

/**
 * Check if User Gets Badge
 * Checks if a given user has earned one or multiple badges.
 * @since 1.5
 * @version 1.3
 */
if ( ! function_exists( 'mycred_check_if_user_gets_badge' ) ) :
	function mycred_check_if_user_gets_badge( $user_id = NULL, $badge_ids = array(), $depreciated = array(), $save = true ) {

		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return false;

		$earned_badge_ids       = array();
		if ( ! empty( $badge_ids ) ) {
			foreach ( $badge_ids as $badge_id ) {

				$level_reached = mycred_badge_level_reached( $user_id, $badge_id );
				if ( $level_reached !== false ) {

					$earned_badge_ids[] = $badge_id;
					delete_post_meta( $badge_id, 'total-users-with-badge' );

					if ( $save )
						mycred_assign_badge_to_user( $user_id, $badge_id, $level_reached );
				}

			}
		}

		return $earned_badge_ids;

	}
endif;

/**
 * Assign Badge
 * Assigns a given badge to all users that fulfill the
 * badges requirements.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_assign_badge' ) ) :
	function mycred_assign_badge( $badge_id = NULL ) {

		$badge_id     = absint( $badge_id );
		if ( $badge_id === 0 ) return false;

		global $wpdb;

		$user_ids     = array();
		$levels       = mycred_get_badge_levels( $badge_id );
		if ( empty( $levels ) ) return false;

		$requirements = count( $levels[0]['requires'] );
		$compare      = $levels[0]['compare'];

		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level_id => $level_setup ) {

				$level_user_ids = array();

				// Get all user IDs that fulfill each requirements set
				if ( ! empty( $level_setup['requires'] ) ) {
					foreach ( $level_setup['requires'] as $requirement_id => $requirement ) {

						if ( $requirement['type'] == '' )
							$requirement['type'] = MYCRED_DEFAULT_TYPE_KEY;

						$mycred = mycred( $requirement['type'] );
						$having = "COUNT(id)";
						$format = '%d';

						if ( $requirement['by'] != 'count' )
							$having = "SUM(creds)";

						if ( $requirement['by'] != 'count' && $mycred->format['decimals'] > 0 )
							$format = '%f';

						$level_user_ids[ $requirement_id ] = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT user_id FROM {$mycred->log_table} WHERE ctype = %s AND ref = %s GROUP BY user_id HAVING {$having} >= {$format};", $requirement['type'], $requirement['reference'], $requirement['amount'] ) );

					}
				}

				// OR = get all unique IDs
				if ( $compare == 'OR' ) {

					$list = array();
					foreach ( $level_user_ids as $requirement_id => $list_of_ids ) {
						if ( ! empty( $list_of_ids ) ) {
							foreach ( $list_of_ids as $uid ) {
								if ( ! in_array( $uid, $list ) )
									$list[] = $uid;
							}
						}
					}

				}

				// AND = get IDs that are in all requirements
				else {

					$list = $_list = array();

					foreach ( $level_user_ids as $requirement_id => $list_of_ids ) {
						if ( ! empty( $list_of_ids ) ) {
							foreach ( $list_of_ids as $uid ) {
								if ( ! array_key_exists( $uid, $_list ) )
									$_list[ $uid ] = 1;
								else
									$_list[ $uid ]++;
							}
						}
					}

					foreach ( $_list as $uid => $count ) {
						if ( $count >= $requirements )
							$list[] = $uid;
					}

				}

				// If no user has reached the first level, no one will have reached higher levels and there is no need to continue
				if ( $level_id == 0 && empty( $list ) ) break;

				// Create a list where the array key represents the user ID and the array value represents the badge level reached by the user
				foreach ( $list as $user_id ) {
					$user_ids[ $user_id ] = $level_id;
				}

			}
		}

		// If we have results, save
		if ( ! empty( $user_ids ) ) {
			foreach ( $user_ids as $user_id => $level_reached ) {

				// Assign the badge
				mycred_assign_badge_to_user( $user_id, $badge_id, $level_reached );

				// Payout reward
				if ( $levels[ $level_reached ]['reward']['log'] != '' && $levels[ $level_reached ]['reward']['amount'] != 0 ) {

					$reward_type = $levels[ $level_reached ]['reward']['type'];
					if ( $reward_type == '' )
						$reward_type = MYCRED_DEFAULT_TYPE_KEY;

					$mycred = mycred( $reward_type );

					// Make sure we only get points once for each level we reach for each badge
					if ( ! $mycred->has_entry( 'badge_reward', $badge_id, $user_id, $level_reached, $reward_type ) )
						$mycred->add_creds(
							'badge_reward',
							$user_id,
							$levels[ $level_reached ]['reward']['amount'],
							$levels[ $level_reached ]['reward']['log'],
							$badge_id,
							$level_reached,
							$reward_type
						);

				}

			}
		}

		return $user_ids;

	}
endif;

/**
 * Assign Badge to User
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_assign_badge_to_user' ) ) :
	function mycred_assign_badge_to_user( $user_id = NULL, $badge_id = NULL, $level = 0 ) {

		$user_id  = absint( $user_id );
		if ( $user_id === 0 ) return false;

		$badge_id = absint( $badge_id );
		if ( $badge_id === 0 ) return false;

		$level    = absint( $level );

		mycred_update_user_meta( $user_id, 'mycred_badge' . $badge_id, '', apply_filters( 'mycred_badge_user_value', $level, $user_id, $badge_id ) );

	}
endif;

/**
 * Get Users Badges
 * Returns the badge post IDs that a given user currently holds.
 * @since 1.5
 * @version 1.2
 */
if ( ! function_exists( 'mycred_get_users_badges' ) ) :
	function mycred_get_users_badges( $user_id = NULL ) {

		if ( $user_id === NULL ) return '';

		global $wpdb;

		$query = $wpdb->get_results( $wpdb->prepare( "
			SELECT * 
			FROM {$wpdb->usermeta} 
			WHERE user_id = %d 
			AND meta_key LIKE %s", $user_id, 'mycred_badge%' ) );

		$badge_ids = array();
		if ( ! empty( $query ) ) {
			foreach ( $query as $badge ) {

				$badge_id = substr( $badge->meta_key, 12 );
				if ( $badge_id == '' ) continue;
				
				$badge_id = (int) $badge_id;
				if ( array_key_exists( $badge_id, $badge_ids ) ) continue;

				$badge_ids[ $badge_id ] = $badge->meta_value;

			}
		}

		return apply_filters( 'mycred_get_users_badges', $badge_ids, $user_id );

	}
endif;

/**
 * Display Users Badges
 * Will echo all badge images a given user has earned.
 * @since 1.5
 * @version 1.3
 */
if ( ! function_exists( 'mycred_display_users_badges' ) ) :
	function mycred_display_users_badges( $user_id = NULL ) {

		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return;

		$users_badges = mycred_get_users_badges( $user_id );

		echo '<div class="row" id="mycred-users-badges"><div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">';

		do_action( 'mycred_before_users_badges', $user_id, $users_badges );

		if ( ! empty( $users_badges ) ) {

			foreach ( $users_badges as $badge_id => $level ) {

				$badge = mycred_get_badge( $badge_id, $level );
				if ( $badge->level_image !== false )
					echo apply_filters( 'mycred_the_badge', $badge->level_image, $badge_id, $badge, $user_id );

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
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_badge_ids' ) ) :
	function mycred_get_badge_ids() {

		global $wpdb;

		$badge_ids = $wpdb->get_col( "
			SELECT ID 
			FROM {$wpdb->posts} 
			WHERE post_type = 'mycred_badge' 
			AND post_status = 'publish' 
			ORDER BY post_date ASC;" );

		return apply_filters( 'mycred_get_badge_ids', $badge_ids );

	}
endif;

?>