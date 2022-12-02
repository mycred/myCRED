<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 0.1
 * @version 1.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_contact_form_seven_hook', 50 );
function mycred_register_contact_form_seven_hook( $installed ) {

    if ( ! function_exists( 'wpcf7' ) ) return $installed;

    $installed['contact_form7'] = array(
        'title'         => __( 'Contact Form 7 Form Submissions', 'mycred' ),
        'description'   => __( 'Awards %_plural% for successful form submissions (by logged in users).', 'mycred' ),
        'documentation' => 'http://codex.mycred.me/hooks/submitting-contact-form-7-forms/',
        'callback'      => array( 'myCRED_Contact_Form7' )
    );

    return $installed;

}

/**
 * Contact Form 7 Hook
 * @since 0.1
 * @version 1.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_contact_form_seven_hook', 50 );
function mycred_load_contact_form_seven_hook() {

    // If the hook has been replaced or if plugin is not installed, exit now
    if ( class_exists( 'myCRED_Contact_Form7' ) || ! function_exists( 'wpcf7' ) ) return;

    class myCRED_Contact_Form7 extends myCRED_Hook {

        public $user_id = 0;

        /**
         * Construct
         */
        public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

            parent::__construct( array(
                'id'       => 'contact_form7',
                'defaults' => array()
            ), $hook_prefs, $type );

        }

        /**
         * Run
         * @since 0.1
         * @version 1.0
         */
        public function run() {

            if ( is_user_logged_in() ) {

                $this->user_id = get_current_user_id();
                
                add_action( 'wpcf7_submit', array( $this, 'form_submission' ), 10, 2 );
                
            }

        } 

        /**
         * Get Forms
         * Queries all Contact Form 7 forms.
         * @since 0.1
         * @version 1.3
         */
        public function get_forms() {

            global $wpdb;

            $restuls     = array();
            $posts_table = mycred_get_db_column( 'posts' );
            $forms       = $wpdb->get_results( $wpdb->prepare( "
                SELECT ID, post_title  
                FROM {$posts_table} 
                WHERE post_type = %s 
                ORDER BY ID ASC;", 'wpcf7_contact_form' ) );

            if ( $forms ) {
                foreach ( $forms as $form )
                    $restuls[ $form->ID ] = $form->post_title;
            }

            return $restuls;

        }

        /**
         * Successful Form Submission
         * @since 0.1
         * @version 1.4.1
         */
        public function form_submission( $form, $result ) {

            // Login is required
            if ( empty( $this->user_id ) ) return;

            $form_id = ( version_compare( WPCF7_VERSION, '4.8', '<' ) ) ? $form->id : $form->id();

            if ( ! isset( $this->prefs[ $form_id ] ) || ! $this->prefs[ $form_id ]['creds'] != 0 ) return;

            // Check for exclusions
            if ( $this->core->exclude_user( $this->user_id ) ) return;

            // Limit
            if ( $this->over_hook_limit( $form_id, 'contact_form_submission', $this->user_id ) ) return;
           
            $this->core->add_creds(
                'contact_form_submission',
                $this->user_id,
                $this->prefs[ $form_id ]['creds'],
                $this->prefs[ $form_id ]['log'],
                $form_id,
                array( 'ref_type' => 'post' ),
                $this->mycred_type
            );

        }

        /**
         * Preferences for Contact Form 7 Hook
         * @since 0.1
         * @version 1.2.1
         */
        public function preferences() {

            $prefs = $this->prefs;
            if ( $prefs === false ) $prefs = array();

            $forms = $this->get_forms();

            // No forms found
            if ( empty( $forms ) ) {
                echo '<p>' . esc_html__( 'No forms found.', 'mycred' ) . '</p>';
                return;
            }

            // Loop though prefs to make sure we always have a default settings (happens when a new form has been created)
            foreach ( $forms as $form_id => $form_title ) {

                if ( ! array_key_exists( $form_id, $prefs ) ) {
                    $prefs[ $form_id ] = array(
                        'creds' => 0,
                        'log'   => '%plural% for submitting form',
                        'limit' => '0/x'
                    );
                }
                
                if ( ! isset( $prefs[ $form_id ]['limit'] ) )
                    $prefs[ $form_id ]['limit'] = '0/x';

            }

            // Set pref if empty
            if ( empty( $prefs ) ) $this->prefs = $prefs;

            // Loop for settings
            foreach ( $forms as $form_id => $form_title ) {

?>
<div class="hook-instance">
    <h3><?php printf( esc_html__( 'Form: %s', 'mycred' ), esc_html( $form_title ) ); ?></h3>
    <div class="row">
        <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( $form_id, 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
                <input type="text" name="<?php echo esc_attr( $this->field_name( array( $form_id, 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( $form_id, 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs[ $form_id ]['creds'] ) ); ?>" class="form-control" />
            </div>
        </div>
        <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( $form_id, 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred' ); ?></label>
                <?php echo wp_kses(
                        $this->hook_limit_setting( $this->field_name( array( $form_id, 'limit' ) ), $this->field_id( array( $form_id, 'limit' ) ), $prefs[ $form_id ]['limit'] ),
                        array(
                            'div' => array(
                                'class' => array()
                            ),
                            'input' => array(
                                'type' => array(),
                                'size' => array(),
                                'class' => array(),
                                'name' => array(),
                                'id' => array(),
                                'value' => array()
                            ),
                            'select' => array(
                                'name' => array(),
                                'id' => array(),
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
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
            <div class="form-group">
                <label for="<?php echo esc_attr( $this->field_id( array( $form_id, 'log' ) ) ); ?>"><?php esc_html_e( 'Log template', 'mycred' ); ?></label>
                <input type="text" name="<?php echo esc_attr( $this->field_name( array( $form_id, 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( $form_id, 'log' ) ) ); ?>" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs[ $form_id ]['log'] ); ?>" class="form-control" />
                <span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general', 'post' ) ) ); ?></span>
            </div>
        </div>
    </div>
</div>
<?php

            }

        }
        
        /**
         * Sanitise Preferences
         * @since 1.6
         * @version 1.0
         */
        public function sanitise_preferences( $data ) {

            $forms = $this->get_forms();
            foreach ( $forms as $form_id => $form_title ) {

                if ( isset( $data[ $form_id ]['limit'] ) && isset( $data[ $form_id ]['limit_by'] ) ) {
                    $limit = sanitize_text_field( $data[ $form_id ]['limit'] );
                    if ( $limit == '' ) $limit = 0;
                    $data[ $form_id ]['limit'] = $limit . '/' . $data[ $form_id ]['limit_by'];
                    unset( $data[ $form_id ]['limit_by'] );
                }

            }

            return $data;

        }

    }

}