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
            add_action( 'admin_menu', array( $this, 'mycred_membership_menu' ) );
            add_action( 'admin_init', array( $this, 'add_styles' ) );
            add_action( 'mycred_admin_init', array( $this, 'membership_addon_actions' ) );
        }

        function add_styles() {

            wp_register_style('admin-subscription-css', plugins_url( 'assets/css/admin-subscription.css', myCRED_THIS ), array(), '1.2', 'all');
            
            if( isset($_GET['page']) && $_GET['page'] == 'mycred-membership' ) {
                wp_enqueue_style( 'mycred-bootstrap-grid' );
            }
            
            wp_enqueue_style('admin-subscription-css');
        }

        /**
         * Register membership menu
         */
        public function mycred_membership_menu() {
            add_submenu_page( 'mycred', 'Membership', 'Membership<span class="mycred-membership-menu-label">New</span>', 'manage_options', 'mycred-membership',array($this,'mycred_membership_callback'));
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
                <h1><?php _e( 'myCred Membership Club', 'mycred' ); ?></h1>
                <div class="mmc_welcome">
                    <div class="mmc_welcome_content">
                        <div class="mmc_title"><?php _e( 'Welcome to myCred Membership Club', 'mycred' ); ?></div>
                        <form action="#" method="post">
                        <?php 
                            if(mycred_is_membership_active()) {
                                echo '<span class="dashicons dashicons-yes-alt membership-license-activated"></span>';
                            } else if(!mycred_is_membership_active() && !empty(mycred_get_membership_key())){
                                // if membership is not active in current site and the membership key is entered
                                echo '<span class="dashicons dashicons-dismiss membership-license-inactive"></span>';
                            } 
                                
                                
                                ?>
                        
                            <input type="text" name="mmc_lincense_key" class="mmc_lincense_key" placeholder="<?php _e( 'Add Your Membership License', 'mycred' ); ?>" value="<?php echo $membership_key?>">
                            <input type="submit" class="mmc_save_license button-primary" value="Save"/>
                            <div class="mmc_license_link"><a href="https://mycred.me/redirect-to-membership/" target="_blank"><span class="dashicons dashicons-editor-help"></span><?php _e('Click here to get your Membership License','mycred') ?></a></div>
                        </form>
                    </div>
                    
                </div>

                <?php
                  if(mycred_is_membership_active()){
                        $this->mycred_display_membership_addons();
                   }
                   else{
                        $this->mycred_display_membership_table();
                    }

                ?>
            </div>
            <?php
        }

        /**
         * Saving user membership key
         */
        public function mycred_save_license() {
            
            if( !isset($_POST['mmc_lincense_key']) ) return;

            $license_key = sanitize_text_field( $_POST['mmc_lincense_key'] );
            if( isset( $license_key ) ) {
                update_option( 'mycred_membership_key', $license_key );
            }
        }

        public function mycred_display_membership_table(){


            $membership_plans = $this->membership_get_plans();

            if(!empty($membership_plans) && ! isset( $addons['code'])){
            ?>
                <script>
                jQuery(document).ready(function () {
                    jQuery("span.slider_btn.round").click(function(){
                    jQuery(".show_one_year").toggleClass("active_pkg");
                    jQuery(".show_three_year").toggleClass("active_pkg");
                    });     
                    
                    jQuery("span.slider_btn.round").click(function(){
                    jQuery(".three_year").toggleClass("show_cont");
                    jQuery(".one_year").toggleClass("hide");
                    jQuery(".three_year").toggleClass("hide");
                    jQuery(".one_year").toggleClass("hide_cont");
                        jQuery(".one_year").toggleClass("show_cont");
                    });

                    jQuery(".show_three_year").click(function(){
                    jQuery(".switch_btn input").prop("checked", true);
                    jQuery(".show_one_year").removeClass("active_pkg");
                    jQuery(".show_three_year").addClass("active_pkg");
                    jQuery("div.three_year").addClass("show_cont");
                    jQuery("span.three_year").addClass("show_cont");
                      jQuery(".one_year").addClass("hide");
                      jQuery(".three_year").removeClass("hide");
                      jQuery("div.one_year").addClass("hide_cont");
                });
                jQuery(".show_one_year").click(function(){
                    jQuery(".switch_btn input").prop("checked", false);
                    jQuery(".show_one_year").addClass("active_pkg");
                    jQuery(".show_three_year").removeClass("active_pkg");
                    
                    jQuery("div.three_year").removeClass("show_cont");
                    jQuery("span.three_year").removeClass("show_cont");
                    jQuery(".one_year").removeClass("hide");
                    jQuery(".three_year").addClass("hide");
                    jQuery("div.one_year").removeClass("hide_cont");
                      
                });
                });
                </script>
            <div class="mmc_table row">
                <div class="col-lg-12">
                    <div id="tabs_package_1">
                        <div class="radio_btns_pkg">
                            <a href="javascript:void(0);" class="show_one_year active_pkg">One Year Package</a>
                            <label class="switch_btn">
                            <input type="checkbox">
                            <span class="slider_btn round"></span>
                            </label>
                            <a href="javascript:void(0);" class="show_three_year">Three Years Package <sup>With Discount</sup></a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-1 col-md-1 hidden-sm hidden-xs"></div>
                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mmc_table_column border-right">
                        <div class="mmc_table_plan">AGENCY</div>
                    
                        <div class="mmc_table_pricing">
                            <p class="mmc_table_pricing_worth one_year">Worth $10000+</p>
                            <p class="mmc_table_pricing_current one_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[52250]['price']; ?></p>
                            <p class="mmc_table_pricing_worth three_year">Worth $30000+</p>
                            <p class="mmc_table_pricing_current three_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[54091]['price']; ?></p>
                        </div>
                        <div class="mmc_table_plan_details">
                            <p class="mmc_table_plan_sites">unlimited sites</p>
                            <p class="mmc_table_plan_billed one_year">billed yearly until cancelled</p>
                            <p class="mmc_table_plan_billed three_year">billed 3 years until cancelled</p>
                        </div>
                        <div class="mmc_table_addon_details">
                            <ul>
                                <li>All Enhancement addons</li>
                                <li>All integrations</li>
                                <li>Store Gateway addons</li>
                                <li>Gamification Addons</li>
                            </ul>
                        </div>
                        <div class="mmc_table_get_started">
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=52250" target="_blank" class="one_year"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=54091" target="_blank" class="three_year"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                        </div>
                    </div>


                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mmc_table_column border-right">
                        <div class="mmc_table_plan">BUSINESS</div>
                    
                        <div class="mmc_table_pricing">
                            <p class="mmc_table_pricing_worth one_year">Worth $5000+</p>
                            <p class="mmc_table_pricing_current one_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[52251]['price']; ?></p>
                            <p class="mmc_table_pricing_worth three_year">Worth $15000+</p>
                            <p class="mmc_table_pricing_current three_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[54089]['price']; ?></p>
                        </div>
                        <div class="mmc_table_plan_details">
                            <p class="mmc_table_plan_sites">upto 5 sites</p>
                            <p class="mmc_table_plan_billed one_year">billed yearly until cancelled</p>
                            <p class="mmc_table_plan_billed three_year">billed 3 years until cancelled</p>
                        </div>
                        <div class="mmc_table_addon_details">
                            <ul>
                                <li>All Enhancement addons</li>
                                <li>All integrations</li>
                                <li>Store Gateway addons</li>
                                <li>Gamification Addons</li>
                            </ul>
                        </div>
                        <div class="mmc_table_get_started">
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=52251" target="_blank" class="one_year"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=54089" target="_blank" class="three_year"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                        </div>
                    </div>


                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mmc_table_column border-right">
                        <div class="mmc_table_most_popular">Most Popular</div>
                        <div class="mmc_table_plan">PROFESSIONAL</div>
                    
                        <div class="mmc_table_pricing">
                            <p class="mmc_table_pricing_worth one_year">Worth $2000+</p>
                            <p class="mmc_table_pricing_current one_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[52496]['price']; ?></p>
                            <p class="mmc_table_pricing_worth three_year">Worth $6000+</p>
                            <p class="mmc_table_pricing_current three_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[54090]['price']; ?></p>
                        </div>
                        <div class="mmc_table_plan_details">
                            <p class="mmc_table_plan_sites">3 sites</p>
                            <p class="mmc_table_plan_billed one_year">billed yearly until cancelled</p>
                            <p class="mmc_table_plan_billed three_year">billed 3 years until cancelled</p>
                        </div>
                        <div class="mmc_table_addon_details">
                            <ul>
                                <li>All Enhancement addons</li>
                                <li>All integrations</li>
                                <li>Store Gateway addons</li>
                                <li>Gamification Addons</li>
                            </ul>
                        </div>
                        <div class="mmc_table_get_started">
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=52496"  class="one_year" target="_blank"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=54090"  class="three_year" target="_blank"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                        </div>
                    </div>


                    <div class="col-lg-2 col-md-2 col-sm-12 col-xs-12 mmc_table_column">
                        <div class="mmc_table_plan">STARTER</div>
                    
                        <div class="mmc_table_pricing">
                            <p class="mmc_table_pricing_worth one_year">Worth $500+</p>
                            <p class="mmc_table_pricing_current one_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[52495]['price']; ?></p>
                            <p class="mmc_table_pricing_worth three_year">Worth $1500+</p>
                            <p class="mmc_table_pricing_current three_year"><span class="mmc_table_pricing_dollar_sign">$</span><?php echo $membership_plans[54088]['price']; ?></p>
                        </div>
                        <div class="mmc_table_plan_details">
                            <p class="mmc_table_plan_sites">1 site</p>
                            <p class="mmc_table_plan_billed one_year">billed yearly until cancelled</p>
                            <p class="mmc_table_plan_billed three_year">billed 3 years until cancelled</p>
                        </div>
                        <div class="mmc_table_addon_details">
                            <ul>
                                <li>Basic enhancement addons</li>
                                <li>Basic Integrations</li>
                                <li>-</li> 
                                <li>-</li>
                            </ul>
                        </div>
                        <div class="mmc_table_get_started">
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=52495" target="_blank" class="one_year"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                            <a href="https://www.mycred.me/cart/?add-to-cart=52249&variation_id=54088" target="_blank" class="three_year"> <?php _e( 'Get Started', 'mycred' ); ?></a>
                        </div>
                    </div>
                    <div class="col-lg-1 col-md-1 hidden-sm hidden-xs"></div>
                </div>
                <?php
            }
        }

        public function mycred_display_membership_addons(){

            $addons = $this->get_membership_addons();
            ?>
            <style type="text/css">
.theme-browser .theme:focus, .theme-browser .theme:hover { cursor: default !important; }
.theme-browser .theme:hover .more-details { opacity: 1; }
.theme-browser .theme:hover a.more-details, .theme-browser .theme:hover a.more-details:hover { text-decoration: none; }
.theme-browser .theme .theme-screenshot img { height: 100%; }
</style>
                    <div class="theme-browser mmc-addons" >
                    <?php

                    // Messages
                    if ( isset( $_GET['success'] ) ) {

                        if ( $_GET['success'] == 1 ){
                            
                            if( $_GET['addon_action'] == 'activate' ){

                                echo '<div id="message" class="updated"><p>' . __( 'Add-on Activated', 'mycred' ) . '</p></div>';

                            } else if ( $_GET['addon_action'] == 'deactivate' ){

                                echo '<div id="message" class="updated"><p>' . __( 'Add-on Deactivated', 'mycred' ) . '</p></div>';
                                
                            } else if ( $_GET['addon_action'] == 'install' ){

                                echo '<div id="message" class="updated"><p>' . __( 'Add-on Installed', 'mycred' ) . '</p></div>';
                                
                            }
                            
                        }
                            

                        elseif ( $_GET['success'] == 0 ){
                            echo '<div id="message" class="error"><p>' . __( 'Could Not Perform Desired Action', 'mycred' ) . '</p></div>';
                        }
                            

                    }

                    ?>
                        <div class="themes">
                        <?php

                    // Loop though addons
                    if ( ! empty( $addons ) && ! isset( $addons['code']) ) {

                        foreach ( $addons as $addon ) {

                            $screenshot = '';
                            if(isset($addon['addon_image']) && !empty($addon['addon_image'])){
                                $screenshot = $addon['addon_image'];
                            }

                            $addon_url = '';
                            if(isset($addon['addon_image']) && !empty($addon['addon_url'])){
                                $addon_url = $addon['addon_url'];
                            }
                            
                            $aria_action = esc_attr( $addon['slug'] . '-action' );
                            $aria_name   = esc_attr( $addon['slug'] . '-name' );

                    ?>
                        <div class="theme<?php if ( $this->is_addon_active( $addon['folder']  ) ) echo ' active'; else echo ' inactive'; ?>" tabindex="0" aria-describedby="<?php echo $aria_action . ' ' . $aria_name; ?>">

                            <?php if ( $screenshot != '' ) : ?>

                            <div class="theme-screenshot">
                                <img src="<?php echo $screenshot; ?>" alt="" />
                            </div>

                            <?php else : ?>

                            <div class="theme-screenshot blank"></div>

                            <?php endif; ?>

                            <a class="more-details" id="<?php echo $aria_action; ?>" href="<?php echo $addon_url; ?>" target="_blank"><?php _e( 'Documentation', 'mycred' ); ?></a>

                            <div class="theme-id-container">

                                <?php if ( $this->is_addon_active( $addon['folder'] ) ) : ?>

                                <h2 class="theme-name" id="<?php echo $aria_name; ?>"><?php echo $addon['name']; ?></h2>

                                <?php else : ?>

                                <h2 class="theme-name" id="<?php echo $aria_name; ?>"><?php echo $addon['name']; ?></h2>

                                <?php endif; ?>

                                <div class="theme-actions">

                                <?php echo $this->activate_deactivate_install( $addon['folder'] ); ?>

                                </div>

                            </div>

                        </div>
                        <?php

                        }

                    }
                    
                    ?>
                    <br class="clear">
                </div>
            </div>
        <?php
        }

        public function get_membership_addons() {

            $membership_details = mycred_get_membership_details(true);
            $addons = array();

            if(isset($membership_details['addons']) && !empty($membership_details['addons'])){
                $addons = $membership_details['addons'];
            }
            
            return $addons;
            
        }
        
        public function is_addon_active($addon_folder_name) {

            $active_plugins = get_option('active_plugins');

            foreach($active_plugins as $active_plugin){
                $arr = explode("/", $active_plugin, 2);

                if($addon_folder_name == $arr[0]){
                    return true;
                }
            }
            return false;
        }

        public function is_addon_installed($addon_folder_name) {
            $installed_plugins = get_plugins();
           
            foreach($installed_plugins as $folder_name => $installed_plugin){
                $arr = explode("/", $folder_name, 2);

                if($addon_folder_name == $arr[0]){
                    return true;
                }
            }
            return false;
        }

        public function is_addon_network_active($addon_folder_name){

            $network_active_plugins = get_site_option('active_sitewide_plugins'); // Network activated plugins

            foreach($network_active_plugins as $network_active_plugin => $timestamp){
                $arr = explode("/", $network_active_plugin, 2);

                if($addon_folder_name == $arr[0]){
                    return true;
                }
            }
            return false;
        }

        public function activate_deactivate_install( $addon_folder = NULL ) {

            /* need to do this for multisite as well */

            $link_url  = $this->get_membership_addon_action_url( $addon_folder, 'install' );
            $link_text = __( 'Install', 'mycred' );
            $network_active = false;

            if(is_multisite() && $this->is_addon_network_active( $addon_folder )){

                $link_url  = "";
                $link_text = __( 'Network Active', 'mycred' );
                $network_active = true;

            } else if ( $this->is_addon_active( $addon_folder ) ) {

                $link_url  = $this->get_membership_addon_action_url( $addon_folder, 'deactivate' );
                $link_text = __( 'Deactivate', 'mycred' );

            } else if($this->is_addon_installed( $addon_folder )){

                $link_url  = $this->get_membership_addon_action_url( $addon_folder, 'activate' );
                $link_text = __( 'Activate', 'mycred' );

            }

            return '<a href="' . esc_url_raw( $link_url ) . '" title="' . esc_attr( $link_text ) . '" class="button button-primary mycred-action ' . esc_attr( $addon_folder ) . ' ' . ($network_active ? 'mycred-addon-network-active' : '') . '">' . esc_html( $link_text ) . '</a>';

        }


        function get_membership_addon_action_url( $addon_folder = NULL, $action = false ) {

            if ( $addon_folder === NULL || $action === false ) return '#';
    
            $args = array(
                'page'         => MYCRED_SLUG . '-membership',
                'addon_folder'     => $addon_folder,
                'addon_action' => $action,
                '_token'       => wp_create_nonce( 'mycred-membership-addon-action' )
            );
    
            return esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) );
    
        }
        

        public function membership_addon_actions() {

            //&& $this->core->user_is_point_admin()
            // Important - Here we need to add a check, if the plugin on which action is being performed really belongs to mycred membership (because user can modify plugin folder name from URL)
            // also need to test for multisite as well
        
            if ( isset( $_GET['addon_action'] ) && isset( $_GET['addon_folder'] ) && isset( $_GET['_token'] ) && wp_verify_nonce( $_GET['_token'], 'mycred-membership-addon-action' )  ) {

                $addon_folder = sanitize_text_field( $_GET['addon_folder'] );
                $action   = sanitize_text_field( $_GET['addon_action'] );

                $result = 0; // 0 = fail, 1 = success
                // Activation
                if ( $action == 'activate' ) {
                    $installed_plugins = get_plugins();
           
                    foreach( $installed_plugins as $folder_name => $installed_plugin ){
                        $arr = explode("/", $folder_name, 2);

                        if($addon_folder == $arr[0]){
                            $success = activate_plugin( $folder_name );
                            if ( $success === NULL ) {
                                $result = 1;
                            }
                        }
                    }

                } else if ( $action == 'deactivate' ) {

                    $active_plugins = get_option('active_plugins');

                    foreach($active_plugins as $active_plugin){
                        $arr = explode("/", $active_plugin, 2);

                        if($addon_folder == $arr[0]){
                            deactivate_plugins( $active_plugin );
                            $result = 1;
                        }
                    }

                } else if ( $action == 'install' ) {

                    // first check if plugin is already installed
                    if (! $this->is_addon_installed( $addon_folder )){ // plugin is not installed
                        $result = 0;
                        if($this->membership_download_addon($addon_folder)){
                            if($this->membership_unzip_addon($addon_folder)){
                                $result = 1;
                            }
                        }
                    }
                    
                }

                    $url = add_query_arg( array( 'page' => MYCRED_SLUG . '-membership', 'success' => $result,  'addon_action' => $action ), admin_url( 'admin.php' ) );

                    wp_safe_redirect( $url );
                    exit;

            }

        }
        
        public function membership_download_addon($addon_folder = ''){

            if(!empty($addon_folder)){

               $url = 'https://mycred.me/download-plugin/?memid='.mycred_get_my_id().'&addonfolder='.$addon_folder;

                $plugin_directory = ABSPATH.'wp-content/plugins/'.$addon_folder.'.zip';
               
                $data = wp_remote_get($url);

                if ( is_array( $data ) && ! is_wp_error( $data ) ) {

                    if ( file_put_contents($plugin_directory, $data['body'] ) ) {
                        return true;
                    }
                        
                }
               
            }

            return false;
        }

        public function membership_unzip_addon($addon_folder = ''){

            if(!empty($addon_folder)) {
                $addon_zip_file = ABSPATH.'wp-content/plugins/'.$addon_folder.'.zip';
                $plugins_directory = ABSPATH.'wp-content/plugins/';

                WP_Filesystem();
                $unzipfile = unzip_file( $addon_zip_file, $plugins_directory);
                        
                if ( $unzipfile ) {
                    unlink($addon_zip_file);
                    return true;      
                }
                
            }

            return false;
        }

        public function membership_get_plans(){

            $url = 'https://mycred.me/wp-json/membership/v1/membership/pricing';
            $data = wp_remote_get( $url );
            $membership_plans = array();

            if ( is_array( $data ) && ! is_wp_error( $data ) ) {

                $membership_plans = json_decode( $data['body'], true );

            }

            return $membership_plans;
        }

    }
endif;

$myCRED_Connect_Membership = new myCRED_Connect_Membership();