<?php
if ( !class_exists( 'myCred_Open_Badge_Settings' ) ):
    class myCred_Open_Badge_Settings {

        private static $_instance;

        /**
         * myCred_Open_Badge_Settings constructor.
         * @since 2.1.1
         * @version 1.0
         */
        public function __construct() {

            add_action( 'mycred_after_core_prefs', array( $this, 'after_general_settings' ) );
            add_filter( 'mycred_save_core_prefs',  array( $this, 'sanitize_extra_settings' ), 10, 3 );
        
        }

        /**
         * @return mixed
         * @since 2.1.1
         * @version 1.0
         */
        public static function get_instance() {

            if ( self::$_instance == null )
                self::$_instance = new self();

            return self::$_instance;
        
        }

        /**
         * Add to General Settings
         * @since 1.0
         * @version 1.1
         */
        public function after_general_settings( $mycred = NULL ) { 

            $hooks = mycred_get_option( 'mycred_pref_core' );

            $settings = property_exists( $mycred->core, 'open_badge' ) ? $mycred->core->open_badge : array(); ?>
            
            <div class="mycred-ui-accordion">
                <div class="mycred-ui-accordion-header">
                    <h4 class="mycred-ui-accordion-header-title">
                        <span class="dashicons dashicons-mycred-open-badge static mycred-ui-accordion-header-icon"></span>
                        <label><?php esc_html_e( 'Open Badge Setting', 'mycred' ); ?></label>
                    </h4>
                    <div class="mycred-ui-accordion-header-actions hide-if-no-js">
                        <button type="button" aria-expanded="true">
                            <span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="body mycred-ui-accordion-body" style="display:none;">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                           <h3><?php esc_html_e( 'Open Badge', 'mycred' ); ?></h3>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label for="mycred-open-badge">
                                        <input type="checkbox" name="mycred_pref_core[open_badge][is_enabled]" id="mycred-open-badge" <?php ! empty( $settings ) ? checked( $settings['is_enabled'], 1 ) : false; ?> value="1" > 
                                        <?php esc_html_e( 'Enable Open Badge.', 'mycred' ); ?>
                                    </label>
                                </div>
                            </div>
                        <?php if( ! empty( $settings ) && $settings['is_enabled'] == '1' ):?>
                            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <div class="form-group">
                                    <label for="mycred-open-badge-evidence-page"><?php esc_html_e( 'Evidence Page', 'mycred' ); ?></label>
                                    <?php       

                                        $selectedEvidencePage = mycred_get_evidence_page_id( );   
                                        $args = array(
                                            'id'       => 'mycred-open-badge-evidence-page',
                                            'name'     => 'mycred_pref_core[open_badge][evidence_page]',
                                            'class'    => 'form-control',
                                            'selected' => $selectedEvidencePage,
                                            'echo'     => 0
                                        );

                                        echo wp_kses(
                                            wp_dropdown_pages( $args ),
                                            array(
                                                'select' => array(
                                                    'id' => array(),
                                                    'name' => array(),
                                                    'class' => array()
                                                ),
                                                'option' => array(
                                                    'value' => array(),
                                                    'selected' => array()
                                                )
                                            )
                                        );
                                    ?>
                                </div>
                            </div>
                            <?php do_action('mycred_open_badges_html'); ?>
                            <?php endif;?>
                        </div>
                       <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                            <h3><?php esc_html_e( 'Open Badge Social Sharing', 'mycred' ); ?></h3>
                            <?php do_action('mycred_admin_open_badge_setting'); ?>
                        </div>
                 
                </div>
            </div>
        </div>
            <?php
        }



        /**
         * Sanitizes and saves settings
         * @param $new_data
         * @param $data
         * @param $core
         * @return mixed
         * @since 2.1.1
         * @version 1.0
         */
        public function sanitize_extra_settings($new_data, $data, $core )
        {

            if( array_key_exists( 'open_badge', $data ) )
            {
                
                $new_data['open_badge']['is_enabled'] = ( isset( $data['open_badge']['is_enabled'] ) ) ? sanitize_text_field( $data['open_badge']['is_enabled'] ) : 0;
                
                $new_data['open_badge']['evidence_page'] = ( isset( $data['open_badge']['evidence_page'] ) ) ? sanitize_text_field( $data['open_badge']['evidence_page'] ) : 0;
               
            }

            return $new_data;
        }

    }
endif;

myCred_Open_Badge_Settings::get_instance();
