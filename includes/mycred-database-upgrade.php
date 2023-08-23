<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;


if ( ! class_exists( 'myCRED_Database_Upgrader' ) ) :
    Class myCRED_Database_Upgrader {
       
        /**
         * Construct
         */
        public function __construct() {

            add_action( 'mycred_init', array( $this, 'mycred_init_database' ) );
            
        }

        public function mycred_init_database() {

            $db_version = mycred_get_option( 'mycred_version_db', false );

            if ( version_compare( myCRED_DB_VERSION, $db_version ) ){

                add_action( 'admin_notices',                         array( $this, 'mycred_update_notice' ) );
                add_action( 'wp_ajax_mycred_update_database',        array( $this, 'mycred_update_database' ) );
                add_action( 'wp_ajax_nopriv_mycred_update_database', array( $this, 'mycred_update_database' ) );
            
            }

        }

        public function mycred_update_notice() { ?>
            
            <div class="notice notice-warning is-dismissible">
                <h2 style="margin-bottom: 8px;">myCred DataBase Update Required</h2>
                <p class="mycred-update-description" style="margin-bottom: 8px;">We need to upgrade the database so that it can work smoothly.<br /><a href="https://mycred.me/blog/database-update/" target="_blank">Why am I seeing this notice?</a></p>
                <p class="mycred-update-waiting" style="display: none" >Please wait while database is upgrading.</p>
                <p class="mycred-update-success" style="display: none" >Thank you.</p>
               
                <button class="mycred-update-database button button-primary button-large" ><span class="dashicons dashicons-update mycred-button1" ></span>Update Database Now </button>
               
                <br>
                <br>
            </div>
            <script type="text/javascript">
                jQuery(document).ready(function(){
                    jQuery('.mycred-update-database').click(function(){

                        jQuery.ajax({
                            url: ajaxurl,
                            data: {
                                action: 'mycred_update_database',
                            },
                            type: 'POST',
                            beforeSend: function() {
                                jQuery('.mycred-update-description').css("display", "none");
                                jQuery('.mycred-update-waiting').css("display", "inherit");
                                jQuery('.mycred-button1').css("display", "inherit");


                            },
                            success:function(data) {
                                jQuery('.mycred-update-database').css("display", "none");
                                jQuery('.mycred-update-waiting').css("display", "none");
                                jQuery('.mycred-update-success').css("display", "inherit");
                                console.log( data );
                            }
                        })

                    });

                });

            </script>
            <?php
        }

        public function mycred_update_database() {
              
            $this->add_indexes();

            wp_die();

        }

        
        public function add_indexes() {
               
            global $wpdb;
            $table = $wpdb->prefix . 'myCRED_log' ;
            
            $sql_ref = "CREATE INDEX `ref` ON `{$table}`(`ref`)";
            $query_result = $wpdb->query( $sql_ref );
            
            $sql_user_id = "CREATE INDEX `user_id` ON `{$table}`(`user_id`)";
            $query_result = $wpdb->query( $sql_user_id );
            
            $sql_ref_id = "CREATE INDEX `ref_id` ON `{$table}`(`ref_id`)";
            $query_result = $wpdb->query( $sql_ref_id );

            $sql_ctype = "CREATE INDEX `ctype` ON `{$table}`(`ctype`)";
            $query_result = $wpdb->query( $sql_ctype );

            // $sql_data = "CREATE INDEX `data` ON `{$table}`(`data`)";
            // $query_result = $wpdb->query( $sql_data );
            
            $sql_time = "CREATE INDEX `time` ON `{$table}`(`time`)";
            $query_result = $wpdb->query( $sql_time );
            
            update_option( 'mycred_version_db', myCRED_DB_VERSION );

        }

    }
endif;

new myCRED_Database_Upgrader();