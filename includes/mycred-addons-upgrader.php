<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;


if ( ! class_exists( 'myCRED_Addons_Upgrader' ) ) :
    Class myCRED_Addons_Upgrader {

        /**
         * Construct
         */
        public function __construct() {

            add_action( 'admin_menu',                            array( $this, 'mycred_upgrader_menu' ) );
            add_action( 'admin_notices',                         array( $this, 'mycred_update_notice') );
            add_action( 'admin_init',                            array( $this, 'admin_init') );
            add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_transient' ), 99 );
            add_filter( 'update_bulk_plugins_complete_actions',  array( $this, 'update_actions' ), 99, 2 );
        
        }

        /**
         * Register upgrader menu
         */
        public function mycred_upgrader_menu() {

            add_submenu_page( 
                'options.php',
                'Update', 
                'Update', 
                'manage_options', 
                'mycred-update',
                array( $this, 'mycred_addons_update' ) 
            );
            
        }

        public function mycred_update_notice() {

            $all_installed_plugins   = get_plugins();
            $mycred_installed_addons = $this->get_installed_mycred_addons( $all_installed_plugins );

            if ( ! empty( $mycred_installed_addons ) ) {
                    
                $is_upgrade_done = get_option( 'mycred_addons_upgrade' );

                if ( false === $is_upgrade_done ) :?>
                <div class="notice notice-warning is-dismissible">
                    <h2 style="margin-bottom: 8px;">myCred Addons Update Required</h2>
                    <p style="margin-bottom: 8px;">From myCred version 2.3.1 and onwards, you will be able to use our latest license management system that allows you to run your add-ons more smoothly. Please update your add-ons immediately for a better experience!<br /><a href="https://mycred.me/blog/why-do-you-need-to-update-your-mycred-addons/" target="_blank">Why am I seeing this notice?</a></p>
                    <a href="<?php echo admin_url('options.php?page=mycred-update'); ?>" class="button button-primary button-large">Update Addons Now</a>
                    <a class="button button-large" href="<?php echo add_query_arg( 'mycred_addons_upgrader', 'mycred-addons-updated', home_url( $_SERVER['REQUEST_URI'] ) ) ?>">I have already updated</a>
                    <br>
                    <br>
                </div>
                <?php endif;

            }

        }

        public function admin_init() {

            global $pagenow;
            if ( $pagenow == 'options.php' && isset( $_GET['page'] ) && $_GET['page'] == 'mycred-update' ) {
                remove_all_actions( 'admin_notices' );
            }

            if ( isset( $_GET['mycred_addons_upgrader'] ) && $_GET['mycred_addons_upgrader'] == 'mycred-addons-updated' ) {
                
                update_option( 'mycred_addons_upgrade', 'done' );
                wp_redirect( remove_query_arg( 'mycred_addons_upgrader', home_url( $_SERVER['REQUEST_URI'] ) ) );

            }

        }

        public function update_transient( $data ) {

            if ( empty( $data->checked ) ) return $data;

            if ( isset( $_GET['mautype'] ) && isset( $_GET['plugins'] )  ) {

                $addons = explode( ',', $_GET['plugins'] );

                if ( ! empty( $addons ) && is_array( $addons ) ) {

                    $all_installed_plugins   = get_plugins();
                    $mycred_installed_addons = $this->get_installed_mycred_addons( $all_installed_plugins );
                    $mycred_addons_detail    = (array) $this->get_addons_detail( $mycred_installed_addons );

                    foreach ( $addons as $key => $addon ) {

                        $addon_slug = explode( '/', $addon );

                        if ( ! empty( $addon_slug[0] ) && ! empty( $mycred_addons_detail[ $addon_slug[0] ] ) ) {

                            $data->response[ $addon ] = $mycred_addons_detail[ $addon_slug[0] ];

                            if ( ! empty( $data->response[ $addon ]->package ) ) {
                                
                                $data->response[ $addon ]->package = add_query_arg(
                                    array(
                                        'site'        => site_url(),
                                        'api-key'     => md5( get_bloginfo( 'url' ) ),
                                        'slug'        => $data->response[ $addon ]->slug
                                    ), 
                                    $data->response[ $addon ]->package 
                                );

                                $data->no_update[ $addon ] = $data->response[ $addon ];

                            }

                        }

                    }

                }

            }

            return $data;
        }

        public function update_actions( $update_actions, $plugin_info ) {

            if ( isset( $_GET['mautype'] ) && $_GET['mautype'] == true ) {

                $update_actions['plugins_page'] = sprintf(
                    '<a href="%s" target="_parent">%s</a>',
                    self_admin_url( 'options.php?page=mycred-update' ),
                    __( 'Go to myCred Addons Update page' )
                );

                $update_actions['updates_page'] = sprintf(
                    '<a href="%s" target="_parent">%s</a>',
                    self_admin_url( 'plugins.php' ),
                    __( 'Go to Plugins page' )
                );

            }

            update_option( 'mycred_addons_upgrade', 'done' );

            return $update_actions;

        }

        
        public function mycred_addons_update() {
            
            $all_installed_plugins   = get_plugins();
            $mycred_installed_addons = $this->get_installed_mycred_addons( $all_installed_plugins );
            $mycred_addons_detail    = (array) $this->get_addons_detail( $mycred_installed_addons );

            if ( isset( $_POST['mycred_addons_update'] ) ) {

                delete_site_transient('update_plugins');

                $title = __( 'myCred Addons Update' );

                wp_enqueue_script( 'updates' );
                $url = self_admin_url( 'update.php?action=update-selected&amp;mautype=true&amp;plugins=' . urlencode( implode( ',', $_POST['mycred_addons_update'] ) ) );
                $url = wp_nonce_url( $url, 'bulk-update-plugins' );

                ?>
                <div class="wrap">
                    <h1><?php echo esc_html( $title ); ?></h1>
                    <div class="notice notice-warning">
                        <h2 id="mycred-addons-update-msg-title">Please wait for a while! <span class="is-active spinner" style="margin:-6px 10px 0;float: none;"></span></h2>
                        <p>After updating addons, you are required to get a new license key from myCred.me (Dashboard).</p>
                        <p><span style="color:#d63638;font-weight: bold;">Note:</span> If you're facing issues while updating your addons, you can manually update them from <a href="https://mycred.me/redirect-to-membership/" target="_blank">myCred.me (Dashboard).</a><br /><a href="https://mycred.me/blog/why-do-you-need-to-update-your-mycred-addons/" target="_blank">Why am I seeing this notice?</a></p>
                        <a href="https://mycred.me/redirect-to-membership/" target="_blank" class="button button-primary button-large">Get your Updated License Key</a>
                        <br>
                        <br>
                    </div>
                    <iframe id="mycred-addons-update-frame" src="<?php echo $url; ?>" style="width: 100%; height:100%; min-height:850px;"></iframe>
                </div>
                <script type="text/javascript">
                    
                    jQuery('#mycred-addons-update-frame').on('load', function(){
                        jQuery('#mycred-addons-update-msg-title').hide();
                    });

                </script>
                <?php  
            }
            else { ?>
            <div class="wrap">
                <h1>myCred Addons Update</h1>
                <form method="post" name="upgrade-plugins" class="upgrade">
                    <p>
                        <input id="upgrade-plugins" class="button" type="submit" value="Update Plugins" name="upgrade">
                    </p>
                    <table class="widefat updates-table" id="update-plugins-table">
                        <thead>
                        <tr>
                            <td class="manage-column check-column"><input type="checkbox" id="plugins-select-all"></td>
                            <td class="manage-column"><label for="plugins-select-all">Select All</label></td>
                        </tr>
                        </thead>
                        <tbody class="plugins">
                            <?php 

                            $count = 0;

                            foreach ( $mycred_installed_addons as $key => $value ) :

                                $addon_slug = explode( '/', $value );

                                if ( ! isset( $mycred_addons_detail[ $addon_slug[0] ] ) ) continue;
 
                                $addon = $mycred_addons_detail[ $addon_slug[0] ];

                                if ( version_compare( $all_installed_plugins[ $value ]['Version'], $addon->new_version, '>=' ) ) continue;

                                $count++;

                            ?>
                            <tr>
                                <td class="check-column">
                                    <input type="checkbox" name="mycred_addons_update[]" id="checkbox_<?php echo $addon_slug[0]; ?>" value="<?php echo $value; ?>">
                                    <label for="checkbox_<?php echo $addon_slug[0]; ?>" class="screen-reader-text">Select myCred</label>
                                </td>
                                <td class="plugin-title">
                                    <p>
                                        <img src="https://mycred.me/wp-content/uploads/2013/02/mycred-token-icon-100x100.png" alt="">
                                        <strong><?php echo $addon->name; ?></strong> You have version <?php echo $all_installed_plugins[ $value ]['Version']; ?> installed. Update to <?php echo $addon->new_version ?>.
                                        <br>Compatibility with WordPress <?php echo $addon->tested ?>: 100% (according to its author)<br>Improved license system.       
                                    </p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if ( $count == 0 ): ?>
                                <tr>
                                    <td colspan="2">Empty</td>
                                </tr>
                            <?php endif ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td class="manage-column check-column"><input type="checkbox" id="plugins-select-all-2"></td>
                            <td class="manage-column"><label for="plugins-select-all-2">Select All</label></td>
                        </tr>
                        </tfoot>
                    </table>
                    <p>
                        <input id="upgrade-plugins-2" class="button" type="submit" value="Update Plugins" name="upgrade">
                    </p>
                </form>
            </div>
            <?php    
            }

        }

        public function get_installed_mycred_addons( $installed_plugins ) {

            $mycred_addons = array(
                'mycred-2co/mycred-2co.php',
                'mycred-coinbase/mycred-coinbase.php',
                'mycred-coinpayment/mycred-coinpayment.php',
                'mycred-compropago/mycred-compropago.php',
                'mycred-payfast/mycred-payfast.php',
                'mycred-paymentwall/mycred-paymentwall.php',
                'mycred-payza/mycred-payza.php',
                'mycred-robokassa/mycred-robokassa.php',
                'mycred-stripe/mycred-stripe.php',
                'mycred-wepay/mycred-wepay.php',
                'mycred-cashcred-paypal/mycred-cashcred-paypal.php',
                'mycred-cashcred-stripe/mycred-cashcred-stripe.php',
                'mycred-anniversary-pro/mycred-anniversary-pro.php',
                'mycred-birthday-plus/myCred-birthday-plus.php',
                'mycred-bp-charges/mycred-bp-charges.php',
                'mycred-cashcred-paystack/mycred-cashcred-paystack.php',
                'mycred-coupons-plus/mycred-coupons-plus.php',
                'mycred-daily-login-rewards/mycred-daily-login-rewards.php',
                'mycred-dokan/mycred-dokan.php',
                'mycred-email-digest/mycred-email-digest.php',
                'mycred-expiration-addon/mycred-expiration-addon.php',
                'mycred-beaver-builder/mycred-beaver-builder.php',
                'mycred-userpro/mycred-userpro.php',
                'mycred-usersultra/mycred-usersultra.php',
                'mycred-vc/mycred-vc.php',
                'mycred-gateway-edd/mycred-gateway-edd.php',
                'mycred-gateway-jigoshop/mycred-gateway-jigoshop.php',
                'mycred-gateway-fundraising/mycred-gateway-fundraising.php',
                'mycred-level-cred/mycred-level-cred.php',
                'mycred-notice-plus/mycred-notice-plus.php',
                'mycred-pacman/mycred-pacman.php',
                'mycred-paystack/mycred-paystack.php',
                'mycred-points-cap/mycred-points-cap.php',
                'mycred-progress-bar/mycred-progress-bar.php',
                'mycred-progress-map/mycred-progress-map.php',
                'mycred-reset-points/mycred-reset-points.php',
                'mycred-rest/mycred-rest.php',
                'mycred-sms-payments/mycred-sms-payments.php',
                'mycred-social-proof/mycred-social-proof.php',
                'mycred-social-shares/mycred-social-shares.php',
                'mycred-transfer-plus/mycred-transfer-plus.php',
                'mycred-videos/mycred-videos.php',
                'mycred-jwplayer/mycred-jwplayer.php',
                'mycred-wc-vendor/mycred-wc-vendors-addon.php',
                'mycred-wheel-of-fortune/mycred-wheel-of-fortune.php',
                'mycred-woocommerce-plus/mycred-woocommerce-plus.php',
                'mycred-zapier/mycred-zapier.php'
            );

            return array_intersect( $mycred_addons, array_keys( $installed_plugins ) );

        }

        public function get_addons_detail( $installed_addons ) {

            $transient_key = md5( implode( ",", $installed_addons ) );
            $plugins_data  = get_site_transient( $transient_key );

            if ( false === $plugins_data ) {
                
                $addons = array();
                
                foreach ( $installed_addons as $key => $addon ) {
                    
                    $addon_file = explode( '/', $addon );

                    if ( ! empty( $addon_file[0] ) ) array_push( $addons, $addon_file[0] );

                }

                $plugins_data = new stdClass();
                $request_args = array(
                    'body' => array(
                        'site'    => site_url(),
                        'api-key' => md5( get_bloginfo( 'url' ) ),
                        'addons'  => $addons
                    ),
                    'timeout' => 12
                );

                // Start checking for an update
                $response = wp_remote_post( 'https://license.mycred.me/wp-json/license/get-new-plugins', $request_args );

                if ( ! is_wp_error( $response ) ) {

                    $response_data = json_decode( $response['body'] );

                    if ( ! empty( $response_data->status ) && $response_data->status == 'success' ) {
                        
                        $plugins_data = $response_data->data;
                        set_site_transient( $transient_key, $plugins_data, 5 * HOUR_IN_SECONDS );

                    }

                }

            }

            return $plugins_data;

        }

    }
endif;

new myCRED_Addons_Upgrader();