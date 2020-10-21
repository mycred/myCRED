<?php
/**
 * Global Functions which can be accessible everywhere to enhance the functionality
 * 
 */

 if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Get WooCommerce Subscription Products
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_subscription_products') ) {
    function mycred_get_subscription_products() {

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        $loop = get_posts( $args );

        $subs_prod = array();
        foreach ( $loop as $prod_id ) {
            $product_s = wc_get_product( $prod_id );
            
            if ($product_s->is_type('subscription')) {
                $subs_prod[ $product_s->get_id() ] = $product_s->get_title();
            }
        }

        return $subs_prod;
    }
}

/**
 * Get available subscription plans
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_subscription_plans') ) {
    function mycred_get_subscription_plans() {

        $args = array(
            'post_type'      => 'mycred-subscription',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        $loop = get_posts( $args );

        $subs_prod = array();
        foreach ( $loop as $prod_id ) {
            $subs_prod[ $prod_id] = get_the_title( $prod_id );
        }

        return $subs_prod;
    }
}

/**
 * Get all myCRED Addons
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_mycred_addons') ) {
    function mycred_get_mycred_addons() {

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        );

        $loop = get_posts( $args );

        $subs_prod = array();
        foreach ( $loop as $prod_id ) {
            $product_s = wc_get_product( $prod_id );
            
            if ( !$product_s->is_type('subscription') ) {
                $subs_prod[ $product_s->get_id() ] = $product_s->get_title();
            }
        }

        return $subs_prod;
    }
}

/**
 * Get user membership key
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_membership_key') ) {
    function mycred_get_membership_key() {

        $membership_key = wp_cache_get('mycred_membership_key');

        if( false === $membership_key ) {
            $membership_key = get_option( 'mycred_membership_key' );
            wp_cache_set( 'mycred_membership_key', $membership_key );
        }

        return $membership_key;
    }
}

/**
 * Get mycred USER ID (mycred.me)
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_my_id') ) {
    function mycred_get_my_id() {

        if( !empty( mycred_get_membership_key() ) ) {
            $membership_key = mycred_get_membership_key();
            $membership_key = explode( '-', $membership_key );

            return $membership_key[0];
        }
    }
}

/**
 * Get user membership order ID
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_subscription_order_id') ) {
    function mycred_get_subscription_order_id( $user_id = 0 ) {

        if( empty( $user_id ) ) $user_id = get_current_user_id();

        $customer_subscriptions = get_posts( array(
            'numberposts' => -1,
            'meta_key'    => '_customer_user',
            'meta_value'  => $user_id, // Or $user_id
            'post_type'   => 'shop_subscription', // WC orders post type
            'post_status' => 'wc-active' // Only orders with status "completed"
        ) );

        // Iterating through each post subscription object
        foreach( $customer_subscriptions as $customer_subscription ){
            // The subscription ID
            $subscription_id = $customer_subscription->ID;
        }

        return $subscription_id;
    }
}

/**
 * Get Membership purchase date
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_subscription_purchase_date') ) {
    function mycred_get_subscription_purchase_date( $user_id = 0 ) {

        if( empty( $user_id ) ) $user_id = get_current_user_id();

        $subscription_id = mycred_get_subscription_order_id( $user_id );
        $subscription = new WC_Subscription( $subscription_id );
        
        return $subscription->get_date('date_created');
    }
}

/**
 * Get membership end date
 * 
 * @since 1.0
 * @version 1.0
 */
if( !function_exists('mycred_get_subscription_end_date') ) {
    function mycred_get_subscription_end_date( $user_id = 0 ) {
            
        if( empty( $user_id ) ) $user_id = get_current_user_id();

        $subscription_id = mycred_get_subscription_order_id( $user_id );
        $subscription = new WC_Subscription( $subscription_id );
        
        return $subscription->get_date('next_payment');
    }
}

/**
 * Get membership end date
 * 
 * @since 1.0
 * @version 1.2
 */
if( !function_exists('mycred_is_membership_active') ) {
    function mycred_is_membership_active() {

        $membership_status = wp_cache_get('mycred_membership_status');

        if( false === $membership_status ) {

            $user_license_key = mycred_get_membership_key();

            $mycred_version = (int) str_replace( '.', '', myCRED_VERSION );
            
            $url = rtrim( get_bloginfo( 'url' ), '/' );
            if( $mycred_version >= 188 && !empty( $user_license_key ) &&
                mycred_get_membership_details()['plan'][0]['key'] == $user_license_key &&
                in_array( $url, mycred_get_membership_details()['sites'][0] )
            ) {
                $membership_status = true;
            }
            wp_cache_set( 'mycred_membership_status', $membership_status );
        }

        return $membership_status;
    }
}

/**
 * Get membership details
 * 
 * @since 1.0
 * @version 1.1
 */
if( !function_exists('mycred_get_membership_details') ) {
    function mycred_get_membership_details() {

        $membership_details = wp_cache_get('mycred_membership_details');

        if( false === $membership_details ) {

            $url = 'https://mycred.me/wp-json/membership/v1/member/'.mycred_get_my_id().'?time='.time();
            $data = wp_remote_get( $url );

            if( is_array( $data ) && ! is_wp_error( $data ) && ! empty( $data['response']['code'] ) && $data['response']['code'] == 200 ) {

                $membership_details = json_decode( $data['body'], true );

            } else {

                $membership_details = array (
                    "addons" => array(),
                    "sites" => array(),
                    "plan" => array(
                        array (
                            "ID" => "",
                            "title" => "",
                            "key" => "",
                        )
                    ),
                    "order" => array (
                        array ( 
                            "order_id" => NULL,
                            "purchase" => 0,
                            "expire" => 0,
                        )
                    )
                );
            
            }

            wp_cache_set( 'mycred_membership_details', $membership_details );
        }

        return $membership_details;

    }
}