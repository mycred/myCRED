<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.4
 * @version 1.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_gravity_forms_hook', 65 );
function mycred_register_gravity_forms_hook( $installed ) {

	if ( ! class_exists( 'GFForms' ) ) return $installed;

	$installed['gravityform'] = array(
		'title'         => __( 'GravityForms Submissions', 'mycred' ),
		'description'   => __( 'Awards %_plural% for successful form submissions.', 'mycred' ),
		'documentation' => 'http://codex.mycred.me/hooks/submitting-gravity-forms/',
		'callback'      => array( 'myCRED_Gravity_Forms' )
	);

	return $installed;

}

/**
 * Gravity Forms Hook
 * @since 1.4
 * @version 1.1.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_gravity_forms_hook', 65 );
function mycred_load_gravity_forms_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_Gravity_Forms' ) || ! class_exists( 'GFForms' ) ) return;

	class myCRED_Gravity_Forms extends myCRED_Hook {

		/**
		 * Construct
		 */
		public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'gravityform',
				'defaults' => array()
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.4
		 * @version 1.0
		 */
		public function run() {

			add_action( 'gform_after_submission', array( $this, 'form_submission' ), 10, 2 );
			add_action( 'gform_post_payment_completed', array( $this, 'payment_successfull_add_cred' ), 10, 2 );

		}

		public function payment_successfull_add_cred( $entry, $action ) {

			$form_id = $entry['form_id'];
			$user_id = $entry['created_by'];
			$amount = $this->prefs[ $form_id ]['creds'];
			$entry  = $this->prefs[ $form_id ]['log'];
			$has_paid = $this->prefs[ $form_id ]['has_paid'];

			// Limit
			if ( $this->over_hook_limit( $form_id, 'gravity_form_submission', $user_id, $form_id ) ) return;

			if ( 'enable' == $has_paid ) {
				$this->core->add_creds(
					'gravity_form_submission',
					$user_id,
					$amount,
					$entry,
					$form_id,
					'',
					$this->mycred_type
				);
			}
		}
		/**
		 * Successful Form Submission
		 * @since 1.4
		 * @version 1.1
		 */
		public function form_submission( $lead, $form ) {
			// Login is required
			if ( ! is_user_logged_in() || ! isset( $lead['form_id'] ) ) return;

			// Prep
			$user_id = absint( $lead['created_by'] );
			$form_id = absint( $lead['form_id'] );

			// Make sure form is setup and user is not excluded
			if ( ! isset( $this->prefs[ $form_id ] ) || $this->core->exclude_user( $user_id ) ) return;

			// Limit
			if ( $this->over_hook_limit( $form_id, 'gravity_form_submission', $user_id, $form_id ) ) return;

			// Default values
			$amount = $this->prefs[ $form_id ]['creds'];
			$entry  = $this->prefs[ $form_id ]['log'];
			$has_paid = $this->prefs[ $form_id ]['has_paid'];

			// See if the form contains myCRED fields that override these defaults
			if ( isset( $form['fields'] ) && ! empty( $form['fields'] ) ) {
				foreach ( $form['fields'] as $field ) {

					// Amount override
					if ( $field->label == 'mycred_amount' ) {
						$amount = $this->core->number( $field->defaultValue );
					}

					// Entry override
					if ( $field->label == 'mycred_entry' ) {
						$entry = sanitize_text_field( $field->defaultValue );
					}

				}
			}

			// Amount can not be zero
			if ( $amount == 0 ) return;
			$enable = false;
			
			$form_meta = RGFormsModel::get_form_meta( $form_id );
		
			foreach( $form_meta['fields'] as $key => $value ){
				
				$payment_gateways = array(
					'stripe_creditcard',
					'square_creditcard',
					'2checkout_creditcard',
					'paypal',
				);
				if( in_array( $value->type, $payment_gateways ) ) $enable = true;			
			}

			if ( $enable && 'enable' == $has_paid ) return;

			// Execute
			
			$this->core->add_creds(
				'gravity_form_submission',
				$user_id,
				$amount,
				$entry,
				$form_id,
				'',
				$this->mycred_type
			);
		}

		/**
		 * Check Limit
		 * @since 1.6
		 * @version 1.3
		 */
		public function over_hook_limit( $instance = '', $reference = '', $user_id = NULL, $ref_id = NULL ) {

			// If logging is disabled, we cant use this feature
			if ( ! MYCRED_ENABLE_LOGGING ) return false;

			// Enforce limit if this function is used incorrectly
			if ( ! isset( $this->prefs[ $instance ] ) && $instance != '' )
				return true;

			global $wpdb, $mycred_log_table;

			// Prep
			$wheres = array();
			$now    = current_time( 'timestamp' );

			// If hook uses multiple instances
			if ( isset( $this->prefs[ $instance ]['limit'] ) )
				$prefs = $this->prefs[ $instance ]['limit'];
			
			// no support for limits
			else {
				return false;
			}

			// If the user ID is not set use the current one
			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			if ( count( explode( '/', $prefs ) ) != 2 )
				$prefs = '0/x';

			// Set to "no limit"
			if ( $prefs === '0/x' ) return false;

			// Prep settings
			list ( $amount, $period ) = explode( '/', $prefs );
			$amount   = (int) $amount;

			// We start constructing the query.
			$wheres[] = $wpdb->prepare( "user_id = %d", $user_id );
			$wheres[] = $wpdb->prepare( "ref = %s", $reference );
			$wheres[] = $wpdb->prepare( "ctype = %s", $this->mycred_type );
			$wheres[] = $wpdb->prepare( "ref_id = %d", $ref_id );

			// If check is based on time
			if ( ! in_array( $period, array( 't', 'x' ) ) ) {

				// Per day
				if ( $period == 'd' )
					$from = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );

				// Per week
				elseif ( $period == 'w' )
					$from = mktime( 0, 0, 0, date( "n", $now ), date( "j", $now ) - date( "N", $now ) + 1 );

				// Per Month
				elseif ( $period == 'm' )
					$from = mktime( 0, 0, 0, date( "n", $now ), 1, date( 'Y', $now ) );

				$wheres[] = $wpdb->prepare( "time BETWEEN %d AND %d", $from, $now );

			}

			$over_limit = false;

			if ( ! empty( $wheres ) ) {

				// Put all wheres together into one string
				$wheres   = implode( " AND ", $wheres );

				$query = "SELECT COUNT(*) FROM {$mycred_log_table} WHERE {$wheres};";

				//Lets play for others
				$query = apply_filters( 'mycred_gravityform_hook_limit_query', $query, $instance, $reference, $user_id, $ref_id, $wheres, $this );

				// Count
				$count = $wpdb->get_var( $query );
				if ( $count === NULL ) $count = 0;

				// Limit check is first priority
				if ( $period != 'x' && $count >= $amount )
					$over_limit = true;

			}

			return apply_filters( 'mycred_gravityform_over_hook_limit', $over_limit, $instance, $reference, $user_id, $ref_id, $this );

		}

		/**
		 * Preferences for Gravityforms Hook
		 * @since 1.4
		 * @version 1.1
		 */
		public function preferences() {

			$prefs = $this->prefs;
			$forms = RGFormsModel::get_forms();

			// No forms found
			if ( empty( $forms ) ) {
				echo '<p>' . esc_html__( 'No forms found.', 'mycred' ) . '</p>';
				return;
			}

			// Loop though prefs to make sure we always have a default setting
			foreach ( $forms as $form ) {
				if ( ! isset( $prefs[ $form->id ] ) ) {
					$prefs[ $form->id ] = array(
						'creds' => 1,
						'log'   => '%plural% for successful submission.',
						'limit' => '0/x'
					);
				}

				if ( ! isset( $prefs[ $form->id ][ 'has_paid' ] ) ) {
					$prefs[ $form->id ][ 'has_paid' ] = 'disable';
				}

				if ( ! isset( $prefs[ $form->id ]['limit'] ) )
					$prefs[ $form->id ]['limit'] = '0/x';
			}

			// Set pref if empty
			if ( empty( $prefs ) ) $this->prefs = $prefs;

			// Loop for settings
			foreach ( $forms as $form ) {

				$form_meta = RGFormsModel::get_form_meta( $form->id );
?>
<div class="hook-instance">
	<h3><?php printf( esc_html__( 'Form: %s', 'mycred' ), esc_html( $form->title ) ); ?></h3>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( $form->id, 'creds' ) ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( $form->id, 'creds' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( $form->id, 'creds' ) ) ); ?>" value="<?php echo esc_attr( $this->core->number( $prefs[ $form->id ]['creds'] ) ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo esc_attr( $this->field_id( array( $form->id, 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred' ); ?></label>
				<?php echo wp_kses(
						$this->hook_limit_setting( $this->field_name( array( $form->id, 'limit' ) ), $this->field_id( array( $form->id, 'limit' ) ), $prefs[ $form->id ]['limit'] ),
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
				<label for="<?php echo esc_attr( $this->field_id( array( $form->id, 'log' ) ) ); ?>"><?php esc_html_e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo esc_attr( $this->field_name( array( $form->id, 'log' ) ) ); ?>" id="<?php echo esc_attr( $this->field_id( array( $form->id, 'log' ) ) ); ?>" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs[ $form->id ]['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
			</div>
		</div>
		<br>
		<?php

		$enable = false;
		foreach( $form_meta['fields'] as $key => $value ){
			
			$payment_gateways = array(
				'stripe_creditcard',
				'square_creditcard',
				'2checkout_creditcard',
				'paypal',
			);
			if( in_array( $value->type, $payment_gateways ) ) {
				$enable = true;
			}			
		}
		
		if ( true == $enable ) {
			?>
			<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
				<div class="form-group">
					<label for="<?php echo esc_attr( $this->field_id( array( $form->id, 'checkbox' ) ) ); ?>" class="description">
						<input type="checkbox" id="<?php echo esc_attr( $this->field_id( array( $form->id, 'has_paid' ) ) ); ?>" name="<?php echo esc_attr( $this->field_name( array( $form->id, 'has_paid' ) ) ); ?>" value="enable" <?php if ( 'enable' == $prefs[ $form->id ]['has_paid'] ) { echo 'checked'; } else{ echo ''; } ?> >
						<?php echo wp_kses_post( 'Enable to award points on successful payments' );?>
					</label>
				</div>
			</div>
			<?php
		}
		?>
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

			$forms = RGFormsModel::get_forms();
			foreach ( $forms as $form ) {
				if ( ! isset( $data[$form->id]['has_paid'] ) ) {

  					$data[$form->id]['has_paid'] = 'disable';
				} else {
					$data[$form->id]['has_paid'] = 'enable';
				}

				if ( isset( $data[ $form->id ]['limit'] ) && isset( $data[ $form->id ]['limit_by'] ) ) {
					$limit = sanitize_text_field( $data[ $form->id ]['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data[ $form->id ]['limit'] = $limit . '/' . $data[ $form->id ]['limit_by'];
					unset( $data[ $form->id ]['limit_by'] );
				}

			}

			return $data;

		}

	}

}