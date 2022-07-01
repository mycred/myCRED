<?php
/**
 * Global Functions which can be accessible everywhere to enhance the functionality
 * 
 */

 if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Get membership end date
 * 
 * @since 1.0
 * @version 1.0
 */
if( ! function_exists('mycred_is_valid_license_key') ) :
    function mycred_is_valid_license_key( $key, $force = false ) {
            
        if( empty( $key ) ) return false;

        $is_valid = get_site_transient( 'mycred_is_valid_license_key' );

        if ( false === $is_valid || $force ) {

            $is_valid     = 'no';
            $api_endpoint = 'https://license.mycred.me/wp-json/license/is-valid-license-key';

            $request_args = array(
                'body' => array(
                    'license_key' => $key,
                    'site'        => get_bloginfo( 'url' ),
                    'api-key'     => md5( get_bloginfo( 'url' ) )
                ),
                'timeout' => 12
            );

            // Start checking for an update
            $response = wp_remote_post( $api_endpoint, $request_args );

            if ( ! is_wp_error( $response ) ) {

                $response_data = json_decode( $response['body'] );

                if ( 
                    ! empty( $response_data->status ) && 
                    $response_data->status == 'success' 
                ) {
                    
                    $is_valid = $response_data->data;

                }

            }

            set_site_transient( 'mycred_is_valid_license_key', $is_valid, DAY_IN_SECONDS * 9999 );
        }
        
        return ( $is_valid == 'yes' );

    }
endif;