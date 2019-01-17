<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 0.1
 * @version 1.0
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_contact_form_seven_hook', 50 );
function mycred_register_contact_form_seven_hook( $installed ) {

	if ( ! function_exists( 'wpcf7' ) ) return $installed;

	$installed['contact_form7'] = array(
		'title'       => __( 'Contact Form 7 Form Submissions', 'mycred' ),
		'description' => __( 'Awards %_plural% for successful form submissions (by logged in users).', 'mycred' ),
		'callback'    => array( 'myCRED_Contact_Form7' )
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

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

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

			add_action( 'wpcf7_mail_sent', array( $this, 'form_submission' ) );

		}

		/**
		 * Get Forms
		 * Queries all Contact Form 7 forms.
		 * @since 0.1
		 * @version 1.2
		 */
		public function get_forms() {

			global $wpdb;

			$restuls = array();
			$forms   = $wpdb->get_results( $wpdb->prepare( "
				SELECT ID, post_title  
				FROM {$wpdb->posts} 
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
		 * @version 1.4
		 */
		public function form_submission( $cf7_form ) {

			// Login is required
			if ( ! is_user_logged_in() ) return;

			$form_id = $cf7_form->id;
			if ( ! isset( $this->prefs[ $form_id ] ) || ! $this->prefs[ $form_id ]['creds'] != 0 ) return;

			// Check for exclusions
			$user_id = get_current_user_id();
			if ( $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( $form_id, 'contact_form_submission' ) ) return;

			$this->core->add_creds(
				'contact_form_submission',
				$user_id,
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
		 * @version 1.1
		 */
		public function preferences() {

			$prefs = $this->prefs;
			$forms = $this->get_forms();

			// No forms found
			if ( empty( $forms ) ) {
				echo '<p>' . __( 'No forms found.', 'mycred' ) . '</p>';
				return;
			}

			// Loop though prefs to make sure we always have a default settings (happens when a new form has been created)
			foreach ( $forms as $form_id => $form_title ) {

				if ( ! isset( $prefs[ $form_id ] ) ) {
					$prefs[ $form_id ] = array(
						'creds' => 1,
						'log'   => '',
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
<label for="<?php echo $this->field_id( array( $form_id, 'creds' ) ); ?>" class="subheader"><?php echo $form_title; ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( $form_id, 'creds' ) ); ?>" id="<?php echo $this->field_id( array( $form_id, 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs[ $form_id ]['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( $form_id, 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( $form_id, 'limit' ) ), $this->field_id( array( $form_id, 'limit' ) ), $prefs[ $form_id ]['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( $form_id, 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( $form_id, 'log' ) ); ?>" id="<?php echo $this->field_id( array( $form_id, 'log' ) ); ?>" value="<?php echo esc_attr( $prefs[ $form_id ]['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<?php

			}

		}
		
		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.0
		 */
		function sanitise_preferences( $data ) {

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

?>