<?php
/**
 * Class to connect mycred with membership
 * 
 * @since 1.0
 * @version 1.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'myCRED_Connect_Membership' ) ) :
    Class myCRED_Connect_Membership {

        /**
         * Construct
         */
        public function __construct() {

            add_action( 'admin_menu',        array( $this, 'mycred_membership_menu' ) );
            add_action( 'admin_menu',        array( $this, 'mycred_treasures' ) );
            add_action( 'admin_menu',        array( $this, 'mycred_support' ) );
            add_action( 'admin_init',        array( $this, 'add_styles' ) );
            add_filter( 'admin_footer_text', array( $this, 'mycred_admin_footer_text') );
        
        }

        public function add_styles() {

            wp_register_style('admin-subscription-css', plugins_url( 'assets/css/admin-subscription.css', myCRED_THIS ), array(), '1.2', 'all');
            
            if( isset($_GET['page']) && $_GET['page'] == 'mycred-membership' ) {
                wp_enqueue_style( 'mycred-bootstrap-grid' );
            }

             elseif( isset($_GET['page']) && $_GET['page'] == 'mycred-treasures' ) {
                wp_enqueue_style( 'mycred-bootstrap-grid' );
            }

            elseif( isset($_GET['page']) && $_GET['page'] == 'mycred-support' ) {
                wp_enqueue_style( 'mycred-bootstrap-grid' );
            }
            
            wp_enqueue_style('admin-subscription-css');

        }

        public function mycred_admin_footer_text( $footer_text ) {
            
            global $typenow;

            if( isset($_GET['page']) && $_GET['page'] == 'mycred-support' ) {

                    $mycred_footer_text = sprintf( __( 'Thank you for being a <a href="%1$s" target="_blank">myCred </a>user! Please give your <a href="%2$s" target="_blank">%3$s</a> rating on WordPress.org', 'mycred' ),
                        'https://mycred.me',
                        'https://wordpress.org/support/plugin/mycred/reviews/?rate=5#new-post',
                        '&#9733;&#9733;&#9733;&#9733;&#9733;'
                    );

                  return str_replace( '</span>', '', $footer_text ) . ' | ' . $mycred_footer_text . '</span>';

            }
            else {

                return $footer_text;

            }

        }

        /**
         * Register membership menu
         */
        public function mycred_membership_menu() {
            mycred_add_main_submenu( 
                'License', 
                'License', 
                'manage_options', 
                'mycred-membership',
                array( $this, 'mycred_membership_callback' ) 
            );
        }

         /**
         * Register membership menu
         */
        public function mycred_treasures() {
            mycred_add_main_submenu( 
                'Treasures', 
                'Treasures', 
                'manage_options', 
                'mycred-treasures',
                array( $this, 'mycred_treasures_callback' ) 
            );
        }

        /**
         * Register Help / Support menu
         */
        public function mycred_support() {
            mycred_add_main_submenu( 
                'Support', 
                'Support', 
                'manage_options', 
                'mycred-support',
                array( $this, 'mycred_support_callback' ) 
            );
        }

         public function mycred_support_callback() {

            $references  = mycred_get_all_references();

            ?>
            
            <div class="wrap mycred-support-page-container">
                <h1 class="wp-heading-inline">myCred Help and Support</h1>
                
                <div class="mycred-support-page-content">
                    
                    <h2>About myCred:</h2>
                    <p>myCred is an intelligent and adaptive points management system that allows you to build and manage a broad range of digital rewards including points, ranks and, badges on your WordPress-powered website.</p>

                    <hr>

                    <h2>Documentation:</h2>
                    <p>For complete information about myCred and its collection of add-ons, visit the <a target="_blank" href="http://codex.mycred.me/">official documentation</a>.</p>
                    <hr>

                    <h2>Help/Support:</h2>
                    <p>Connect with us for support or feature enhancements - myCred Support Forums or <a target="_blank" href="https://objectsws.atlassian.net/servicedesk/customer/portal/7/group/7/create/46">Open a support ticket</a>.</p>
                    <hr>

                    <h2>Suggestion:</h2>
                    <p>If you have suggestions for myCred and their addons, feel free to add them <a target="_blank" href="https://app.productstash.io/roadmaps/5f8d483c053518002b4441c4/public">here</a>.</p>
                    <hr>

                    <h2>Free add-ons</h2>
                    <p>Power your WordPress website with 30+ free add-ons for myCred - enhance your website's functionality with our free add-ons for store gateways, third-party bridges, and gamification. <a target="_blank" href="https://mycred.me/product-category/freebies/">Visit our complete collection</a>.</p>
                    <hr>
                    
                    <h2>Premium add-ons</h2>
                    <p>Enjoy the best that myCred has to offer with our collection of premium add-ons that enable you to perform complex tasks such as buy or sell points in exchange for real money or create a points management system for your WooCommerce store. <a target="_blank" href="https://mycred.me/store/">View our premium add-ons</a>.</p>
                    <hr>
                    
                    <h2>Customization:</h2>
                    <p>If you need to build a custom feature, simply <a href="https://objectsws.atlassian.net/servicedesk/customer/portal/11/create/92">submit a request</a> on our myCred website.</p>
                    <hr>
                    
                    <h2>myCred Log References:</h2>
                    <div class="row mycred-all-references-list">
                        <?php foreach ( $references as $key => $entry ):?>   
                        <div class="col-md-6 mb-2"><code><?php echo esc_html( $key );?></code> - <?php echo esc_html( $entry );?></div>
                        <?php endforeach;?>
                    </div>

                </div>
                
            </div>

           
           <?php
        }

        /**
         * Treasures menu callback
         */
        public function mycred_treasures_callback() {?>
            <div class="wrap" id="myCRED-wrap">
                <div class="mycred-addon-outer">    
                    <div class="myCRED-addon-heading">
                        <h1>Treasures </h1>
                    </div>
                    <div class="clear"></div>        
                </div>
                <div class="theme-browser">
                    <div class="themes">
                        <div class="theme active mycred-treasure-pack">
                            <div class="mycred-treasure-pack-content">
                                <img src="<?php echo esc_url( plugins_url( 'assets/images/treasures/badges.png', myCRED_THIS ) );?>" alt="Treasure Badges">
                                <h3>Badges</h3>
                                <p>40 unique and beautifully designed Badge designs available in Gold, Silver and Bronze.</p>
                            </div>
                            <div class="theme-id-container">
                                <h2 class="theme-name">Get Info</h2>
                                <div class="theme-actions">
                                    <a href="https://mycred.me/treasure/badges/" target="_blank" class="button button-primary mycred-action">Get this Asset</a>
                                </div>
                            </div>
                        </div>
                        <div class="theme active mycred-treasure-pack">
                            <div class="mycred-treasure-pack-content">
                                <img src="<?php echo esc_url( plugins_url( 'assets/images/treasures/rank.png', myCRED_THIS ) );?>" alt="Treasure Ranks">
                                <h3>Ranks</h3>
                                <p>40 unique and beautifully designed virtual Ranks are available in Red, Silver and Gold.</p>
                            </div>
                            <div class="theme-id-container">
                                <h2 class="theme-name">Get Info</h2>
                                <div class="theme-actions">
                                    <a href="https://mycred.me/treasure/ranks/" target="_blank" class="button button-primary mycred-action">Get this Asset</a>
                                </div>
                            </div>
                        </div>
                        <div class="theme active mycred-treasure-pack">
                            <div class="mycred-treasure-pack-content">
                                <img src="<?php echo esc_url( plugins_url( 'assets/images/treasures/currency.png', myCRED_THIS ) );?>" alt="Treasure Currencies">
                                <h3>Currency</h3>
                                <p>17 unique and beautifully designed Currency designs available in Gold, Silver & Bronze.</p>
                            </div>
                            <div class="theme-id-container">
                                <h2 class="theme-name">Get Info</h2>
                                <div class="theme-actions">
                                    <a href="https://mycred.me/treasure/currency/" target="_blank" class="button button-primary mycred-action">Get this Asset</a>
                                </div>
                            </div>
                        </div>
                        <div class="theme active mycred-treasure-pack">
                            <div class="mycred-treasure-pack-content">
                                <img src="<?php echo esc_url( plugins_url( 'assets/images/treasures/learning.png', myCRED_THIS ) );?>" alt="Treasure Learning">
                                <h3>Learning</h3>
                                <p>30 unique and beautifully designed Learning icons are available in four different shapes.</p>
                            </div>
                            <div class="theme-id-container">
                                <h2 class="theme-name">Get Info</h2>
                                <div class="theme-actions">
                                    <a href="https://mycred.me/treasure/learning/" target="_blank" class="button button-primary mycred-action">Get this Asset</a>
                                </div>
                            </div>
                        </div>
                        <div class="theme active mycred-treasure-pack">
                            <div class="mycred-treasure-pack-content">
                                <img src="<?php echo esc_url( plugins_url( 'assets/images/treasures/fitness.png', myCRED_THIS ) );?>" alt="Treasure Fitness">
                                <h3>Fitness</h3>
                                <p>30 unique and beautifully designed Fitness icons are available in three different shapes.</p>
                            </div>
                            <div class="theme-id-container">
                                <h2 class="theme-name">Get Info</h2>
                                <div class="theme-actions">
                                    <a href="https://mycred.me/treasure/fitness/" target="_blank" class="button button-primary mycred-action">Get this Asset</a>
                                </div>
                            </div>
                        </div>
                        <div class="theme active mycred-treasure-pack">
                            <div class="mycred-treasure-pack-content">
                                <img src="<?php echo esc_url( plugins_url( 'assets/images/treasures/gems.png', myCRED_THIS ) );?>" alt="Treasure Gems">
                                <h3>Gems</h3>
                                <p>500 unique and beautifully designed gem icons are available in four different shapes.</p>
                            </div>
                            <div class="theme-id-container">
                                <h2 class="theme-name">Get Info</h2>
                                <div class="theme-actions">
                                    <a href="https://mycred.me/treasure/gems/" target="_blank" class="button button-primary mycred-action">Get this Asset</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>        
           <?php
        }

        /**
         * Membership menu callback
         */
        public function mycred_membership_callback() {
            $user_id = get_current_user_id();
            $this->mycred_save_license();
            $membership_key = get_option( 'mycred_membership_key' );
            if( !isset( $membership_key )  && !empty( $membership_key ) )
                $membership_key = '';
            ?>
            <div class="wrap">
                <div class="mmc_welcome">
                    <div class="mmc_welcome_content">
                        <div class="mmc_title"><?php esc_html_e( 'Welcome to myCred Premium Club', 'mycred' ); ?></div>
                        <form action="#" method="post">
                        <?php 
                            if(mycred_is_valid_license_key( $membership_key )) {
                                echo '<span class="dashicons dashicons-yes-alt membership-license-activated"></span>';
                            } 
                            else {
                                // if membership is not active in current site and the membership key is entered
                                echo '<span class="dashicons dashicons-dismiss membership-license-inactive"></span>';
                            } 
                                
                                
                        ?>
                        
                            <input type="text" name="mmc_lincense_key" class="mmc_lincense_key" placeholder="<?php esc_attr_e( 'Add Your License key', 'mycred' ); ?>" value="<?php echo esc_attr( $membership_key );?>">
                            <input type="submit" class="mmc_save_license button-primary" value="Save"/>
                            <div class="mmc_license_link"><a href="https://mycred.me/redirect-to-membership/" target="_blank"><span class="dashicons dashicons-editor-help"></span><?php esc_html_e('Click here to get your License Key','mycred') ?></a>
                            </div>
                            <div class="mmc_license_link">
                                

                            </div>
                        </form>
                    </div>
                    
                </div>

                
            </div>
            <?php
        }

        /**
         * Saving user membership key
         */
        public function mycred_save_license() {
            
            if( !isset($_POST['mmc_lincense_key']) ) return;

            $license_key = sanitize_text_field( wp_unslash( $_POST['mmc_lincense_key'] ) );

            if( isset( $license_key ) ) {

                update_option( 'mycred_membership_key', $license_key );
                mycred_is_valid_license_key( $license_key, true );
                $this->removeLicenseTransients();

            }
            
        }

        public function removeLicenseTransients() {
            
            $addons      = apply_filters( 'mycred_license_addons', array() );
            $update_data = get_site_transient( 'update_plugins' );

            foreach ( $addons as $addon ) {

                if ( isset( $update_data->response[ $addon . '/' . $addon . '.php' ] ) ) {
                    unset( $update_data->response[ $addon . '/' . $addon . '.php' ] );
                }

                if ( isset( $update_data->no_update[ $addon . '/' . $addon . '.php' ] ) ) {
                    unset( $update_data->no_update[ $addon . '/' . $addon . '.php' ] );
                }

                if ( isset( $update_data->checked[ $addon . '/' . $addon . '.php' ] ) ) {
                    unset( $update_data->checked[ $addon . '/' . $addon . '.php' ] );
                }
                    
                $transient_key = 'mcl_' . md5( $addon );
                delete_site_transient( $transient_key );

            }

            set_site_transient( 'update_plugins', $update_data );

        }

    }
endif;

$myCRED_Connect_Membership = new myCRED_Connect_Membership();