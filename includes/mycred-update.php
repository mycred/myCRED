<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED 1.5 Update
 * Updated existing myCRED installations to 1.5
 * @since 1.5
 * @version 1.0
 */
add_action( 'mycred_reactivation', 'mycred_update_to_onefive', 5 );
if ( ! function_exists( 'mycred_update_to_onefive' ) ) :
	function mycred_update_to_onefive( $version = NULL ) {

		if ( $version === NULL ) return;

		// Clean up after the 1.4.6 Email Notice bug
		if ( version_compare( $version, '1.4.7', '<' ) ) {
			$cron = get_option( 'cron' );
			if ( ! empty( $cron ) ) {
				foreach ( $cron as $time => $job ) {
					if ( isset( $job['mycred_send_email_notices'] ) )
						unset( $cron[ $time ] );
					
				}
				update_option( 'cron', $cron );
			}
		}

		// 1.4 Update
		if ( version_compare( $version, '1.4', '>=' ) ) {
			delete_option( 'mycred_update_req_settings' );
			delete_option( 'mycred_update_req_hooks' );
		}

		// 1.5 Update
		if ( version_compare( $version, '1.5', '<' ) && class_exists( 'myCRED_buyCRED_Module' ) ) {

			// Update buyCRED Settings
			$type_set = MYCRED_DEFAULT_TYPE_KEY;
			$setup = mycred_get_option( 'mycred_pref_core', false );
			if ( isset( $setup['buy_creds'] ) ) {
				if ( isset( $setup['buy_creds']['type'] ) ) {
					$type_set = $setup['buy_creds']['type'];
					unset( $setup['buy_creds']['type'] );
					$setup['buy_creds']['types'] = array( $type_set );
					mycred_update_option( 'mycred_pref_core', $setup );
				}
			}
			
			// Update buyCRED Gateways Settings
			$buy_cred = mycred_get_option( 'mycred_pref_buycreds', false );
			if ( isset( $buy_cred['gateway_prefs'] ) ) {
				foreach ( $buy_cred['gateway_prefs'] as $gateway_id => $prefs ) {
					if ( ! isset( $prefs['exchange'] ) ) continue;
					$buy_cred['gateway_prefs'][ $gateway_id ]['exchange'] = array(
						$type_set => $prefs['exchange']
					);
				}
				
				$buy_cred['active'] = array();
				
				mycred_update_option( 'mycred_pref_buycreds', $buy_cred );
				add_option( 'mycred_buycred_reset', 'true' );
			}

			// Update complted
			update_option( 'mycred_version', '1.5.4' );

		}
		else {
			delete_option( 'mycred_buycred_reset' );
		}

	}
endif;

/**
 * myCRED 1.6 Update
 * Updated existing myCRED installations to 1.6
 * @since 1.6
 * @version 1.1
 */
add_action( 'mycred_reactivation', 'mycred_update_to_onesix', 10 );
if ( ! function_exists( 'mycred_update_to_onesix' ) ) :
	function mycred_update_to_onesix( $version = NULL ) {

		if ( $version === NULL ) return;

		// 1.6 Update
		if ( version_compare( $version, '1.6', '<' ) ) {

			global $wpdb;

			$types = mycred_get_types();

			// Remove Login hook markers
			if ( count( $types ) == 1 )
				$wpdb->delete(
					$wpdb->usermeta,
					array( 'meta_key' => 'mycred_last_login' ),
					array( '%s' )
				);

			else {
				foreach ( $types as $type_id => $label ) {
					$wpdb->delete(
						$wpdb->usermeta,
						array( 'meta_key' => 'mycred_last_login_' . $type_id ),
						array( '%s' )
					);
				}
			}

			// Update email notices to support multiple point types
			if ( class_exists( 'myCRED_Email_Notice_Module' ) ) {

				$notices = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mycred_email_notice' AND post_status != 'trash';" );
				if ( ! empty( $notices ) ) {
					foreach ( $notices as $notice_id )
						update_post_meta( (int) $notice_id, 'mycred_email_ctype', 'all' );
				}

			}

			// Update ranks to support multiple point types
			if ( class_exists( 'myCRED_Ranks_Module' ) ) {

				$ranks = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mycred_rank' AND post_status != 'trash';" );
				if ( ! empty( $ranks ) ) {
					foreach ( $ranks as $rank_id )
						update_post_meta( (int) $rank_id, 'ctype', MYCRED_DEFAULT_TYPE_KEY );
				}

			}

		}

		// 1.6.7
		elseif ( version_compare( $version, '1.6.7', '<' ) ) {

			$addons = mycred_get_option( 'mycred_pref_addons' );
			$addons = maybe_unserialize( $addons );

			// Remove built-in add-ons Paths.
			if ( ! empty( $addons['installed'] ) ) {

				foreach ( $addons['installed'] as $id => $info ) {

					$addons['installed'][ $id ]['path'] = str_replace( myCRED_ADDONS_DIR, '', $info['path'] );

				}

				mycred_update_option( 'mycred_pref_addons', $addons );

			}

		}

		// Update complted
		update_option( 'mycred_version', myCRED_VERSION );

	}
endif;

?>