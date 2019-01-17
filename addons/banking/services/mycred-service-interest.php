<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Bank Service - Interest
 * @since 1.2
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Banking_Service_Interest' ) ) :
	class myCRED_Banking_Service_Interest extends myCRED_Service {

		public $cron_compound_key = '';
		public $cron_payout_key   = '';
		public $compound_meta_key = '';

		/**
		 * Construct
		 */
		function __construct( $service_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'interest',
				'defaults' => array(
					'rate'         => array(
						'amount'       => 2,
						'pay_out'      => 'monthly'
					),
					'log'           => __( '%plural% interest rate payment', 'mycred' ),
					'min_balance'   => 1,
					'exclude_ids'   => '',
					'exclude_roles' => array()
				)
			), $service_prefs, $type );

			$this->cron_compound_key = 'mycred_bank_interest_comp' . $type;
			$this->cron_payout_key   = 'mycred_bank_interest_pay' . $type;
			$this->compound_meta_key = $type . '_comp';
			$this->log_reference     = apply_filters( 'mycred_bank_interest_reference', 'interest', $this );

		}

		/**
		 * Deactivate Service
		 * Used if the service is no longer reach the minimum requirements or when the
		 * service is disabled in the admin area.
		 * @since 1.5.2
		 * @version 1.1
		 */
		public function deactivate() {

			$timestamp = wp_next_scheduled( $this->cron_compound_key );
			if ( $timestamp !== false )
				wp_clear_scheduled_hook( $this->cron_compound_key );

			$timestamp = wp_next_scheduled( $this->cron_payout_key );
			if ( $timestamp !== false )
				wp_clear_scheduled_hook( $this->cron_payout_key );

		}

		/**
		 * Is Ready
		 * Checks to make sure the most common "bugs" users experience with this service
		 * are taken care of. An empty log or empty interest rate will not work.
		 * @since 1.7
		 * @version 1.0
		 */
		protected function is_ready() {

			$rate = $this->prefs['rate']['amount'];
			if ( strlen( $rate ) == 0 ) return false;

			$log = $this->prefs['log'];
			if ( strlen( $log ) == 0 ) return false;

			return true;

		}

		/**
		 * Run
		 * Actions taken during WordPress init. Hooks into the cron jobs and schedules
		 * the cron jobs if not scheduled. Will only run if service is enabled.
		 * @since 1.2
		 * @version 1.1
		 */
		public function run() {

			// Make sure we can run this service
			if ( ! $this->is_ready() ) {

				$this->deactivate();
				return;

			}

			add_action( 'mycred_bank_interest_comp' . $this->mycred_type, array( $this, 'do_compounding' ), 10 );
			add_action( 'mycred_bank_interest_pay' . $this->mycred_type,  array( $this, 'do_interest_payout' ), 10 );

			$now = $this->now;

			// Make sure there is a compounding scheduled
			$compound = wp_next_scheduled( $this->cron_compound_key );
			if ( $compound === false )
				wp_schedule_single_event( $now + 600, $this->cron_compound_key );

			// Make sure there is a compounding scheduled
			$payout   = wp_next_scheduled( $this->cron_payout_key );
			if ( $payout === false ) {

				$now += mycred_banking_get_next_payout( $this->prefs['rate']['pay_out'] );
				wp_schedule_single_event( $now, $this->cron_payout_key );

			}

			add_action( 'personal_options_update',         array( $this, 'save_custom_rate' ), 30 );
			add_action( 'edit_user_profile_update',        array( $this, 'save_custom_rate' ), 30 );

		}

		/**
		 * Do Interest Compounding
		 * Compounds interest on balances each day.
		 * @since 1.5.2
		 * @version 1.1
		 */
		public function do_compounding() {

			global $wpdb;

			$limit      = '';
			$select     = "user_id, meta_value as value";

			// Default number of balances we will be running through for now
			$number     = absint( apply_filters( 'mycred_compound_max_limit', 1500, $this ) );

			// On large sites, we need to do this in batches
			if ( $this->is_large_site() ) {

				// Schedule our next event in 2 minutes
				wp_schedule_single_event( ( $this->now + 180 ), $this->cron_compound_key );

				$transient_key = 'mycred-compoun-' . $this->mycred_type;
				$offset        = get_transient( $transient_key );

				// Apply limit and set a transient to keep track of our progress
				if ( $offset === false ) {

					set_transient( $transient_key, $number, HOUR_IN_SECONDS );
					$limit = "LIMIT 0,{$number}";

				}

				// While in loop, we need to keep track of our progress
				else {

					delete_transient( $transient_key );
					set_transient( $transient_key, $offset + $number, HOUR_IN_SECONDS );
					$limit = "LIMIT {$offset},{$number}";

				}

				// Save total rows so we can check when we are finished
				$select = "SQL_CALC_FOUND_ROWS " . $select;

				// Used to store the time at which tomorrows run should be running
				$original_start = mycred_get_option( 'mycred_todays_compound_started', false );
				if ( $original_start === false )
					update_option( 'mycred_todays_compound_started', $this->now );

			}

			// Should be able to run through 1500 users on most sites
			else {
				wp_schedule_single_event( ( $this->now + DAY_IN_SECONDS ), $this->cron_compound_key );
			}

			$format = '%d';
			if ( $this->core->format['decimals'] > 0 )
				$format = '%f';

			$wheres        = array();
			$wheres[]      = $wpdb->prepare( "meta_key = %s", $this->mycred_type );
			$wheres[]      = $wpdb->prepare( "meta_value != {$format}", $this->core->zero() );

			// Check if we need to exclude certain users
			$excluded = $this->get_excluded_user_ids();
			if ( ! empty( $excluded ) )
				$wheres[] = "user_id NOT IN (" . implode( ', ', $excluded ) . ")";

			// Apply minimum balance requirement (if used)
			$minimum = ( ( $this->prefs['min_balance'] != '' ) ? $this->core->number( $this->prefs['min_balance'] ) : 0 );
			if ( $minimum > 0 )
				$wheres[] = $wpdb->prepare( "meta_value >= {$format}", $minimum );

			// A few items we need for calculations
			$days_in_year  = $this->get_days_in_year();
			$period        = apply_filters( 'mycred_compound_period', 1, $this );
			if ( $period > 0 ) $period = ( $period / $days_in_year );

			// Get the balance key
			$balance_key   = mycred_get_meta_key( $this->mycred_type );

			// Construct the WHERE statement
			$wheres        = implode( ' AND ', $wheres );

			// Run query
			$user_balances = $wpdb->get_results( "SELECT {$select} FROM {$wpdb->usermeta} WHERE {$wheres} ORDER BY umeta_id ASC {$limit};" );

			if ( ! empty( $user_balances ) ) {

				// Loop through each balance
				foreach ( $user_balances as $entry ) {

					$balance = (float) $entry->value;

					// Do not apply an interest on negative values
					if ( $balance <= $this->core->zero() && apply_filters( 'mycred_compound_negative_interest', false, $this ) === false ) continue;

					// Get the interest we have earned up until now
					$saved_interest = mycred_get_user_meta( $entry->user_id, $this->compound_meta_key, '', true );
					if ( $saved_interest == '' ) $saved_interest = 0;

					// Allow customization of the calculated interest
					$interest_rate = $this->get_users_interest_rate( $entry->user_id, $this->prefs['rate']['amount'] );
					if ( $interest_rate > 0 ) {

						// Compound interest
						$interest_rate = $interest_rate / 100;
						$interest      = ( ( $balance + $saved_interest ) * $interest_rate ) * $period;

						// Save the new interest
						mycred_update_user_meta( $entry->user_id, $this->compound_meta_key, '', $interest );

					}

					// NEXT!

				}

			}

			// On large sites, check if we finished.
			if ( $limit != '' ) {

				$offset    += $number;
				$total_rows = $wpdb->get_var( 'SELECT FOUND_ROWS()' );

				if ( count( $user_balances ) < $number || $offset > $total_rows ) {

					// Always clean up after ourselves
					delete_transient( $transient_key );

					// Get the time we should have started
					$original_start = (int) mycred_get_option( 'mycred_todays_compound_started', $this->now );
					delete_option( 'mycred_todays_compound_started' );

					// Schedule tomorrows run
					wp_schedule_single_event( ( $original_start + DAY_IN_SECONDS ), $this->cron_compound_key );

				}

			}

		}

		/**
		 * Do Interest Payout
		 * Handles payouts of pending compounded interests.
		 * @since 1.5.2
		 * @version 1.2
		 */
		public function do_interest_payout() {

			global $wpdb;

			$now = $this->now;

			// Default number of balances we will be running through for now
			$number        = absint( apply_filters( 'mycred_compound_pay_max_limit', 1500, $this ) );
			$total_rows    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s;", $this->compound_meta_key ) );

			// If we have more then what can handle in one go, schedule in the next run in 2 minutes time
			if ( $total_rows > $number ) {

				// Schedule our next event in 2 minutes
				wp_schedule_single_event( ( $now + 180 ), $this->cron_payout_key );

				// Used to store the time at which the next run should be running
				$original_start = mycred_get_option( 'mycred_todays_compound_payout_started', false );
				if ( $original_start === false )
					update_option( 'mycred_todays_compound_payout_started', $now );

			}

			// Get pending interests
			$pending_interests = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->usermeta} WHERE meta_key = %s LIMIT {$number};", $this->compound_meta_key ) );

			if ( ! empty( $pending_interests ) ) {

				// Loop through each
				foreach ( $pending_interests as $entry ) {

					// Make sure the payout is unique
					if ( ! $this->core->has_entry( $this->log_reference, $entry->umeta_id, $entry->user_id, '', $this->mycred_type ) ) {

						// Attempt to pay
						$payout   = $this->core->add_creds(
							$this->log_reference,
							$entry->user_id,
							$entry->meta_value,
							$this->prefs['log'],
							$entry->umeta_id,
							'',
							$this->mycred_type
						);

						// Payout successfull
						if ( $payout ) {
							$total_rows--;
							mycred_delete_user_meta( $entry->user_id, $this->compound_meta_key );
						}

						// NEXT!

					}

				}

			}

			// Nothing more to payout
			if ( $total_rows <= 0 ) {

				// Get the time we should have done our scheduling
				$original_start = (int) mycred_get_option( 'mycred_todays_compound_payout_started', $now );
				if ( $original_start != $now )
					delete_option( 'mycred_todays_compound_payout_started' );

				// Schedule next run
				$next = $original_start + mycred_banking_get_next_payout( $this->prefs['rate']['pay_out'] );
				wp_schedule_single_event( $next, $this->cron_payout_key );

			}

		}

		/**
		 * Get Pending Interest
		 * Returns the total amount of points that have been compounded but not yet paid out.
		 * @since 1.7
		 * @version 1.0
		 */
		protected function get_pending_interest() {

			global $wpdb;

			$amount = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( meta_value ) FROM {$wpdb->usermeta} WHERE meta_key = %s;", $this->compound_meta_key ) );
			if ( $amount === NULL ) $amount = 0;

			return $this->core->number( $amount );

		}

		/**
		 * Get Users Interet Rate
		 * Takes into account any custom rates that might have been saved for the given user.
		 * @since 1.7
		 * @version 1.0
		 */
		public function get_users_interest_rate( $user_id = 0, $default = 0 ) {

			$rate  = $default;
			$saved = mycred_get_user_meta( $user_id, 'mycred_banking_rate_' . $this->mycred_type, '', true );
			if ( strlen( $saved ) > 0 )
				$rate = $saved;

			return apply_filters( 'mycred_get_users_interest_rate', $rate, $user_id, $default, $this );

		}

		/**
		 * Settings Screen
		 * Renders the service settings on the Banking page in the admin area.
		 * @since 1.2
		 * @version 1.3
		 */
		public function preferences() {

			$cyear = $cmonth = $cday = $chour = $cminute = $csecond = '';
			$pyear = $pmonth = $pday = $phour = $pminute = $psecond = '';
			$editable_roles  = array_reverse( get_editable_roles() );

			// If scheduled, split the date and time of the cron timestamp
			$compound = wp_next_scheduled( $this->cron_compound_key );
			if ( $compound !== false ) {

				$compound = $this->timestamp_to_date( $compound );

				$date     = date( 'Y-m-d', $compound );
				list ( $cyear, $cmonth, $cday ) = explode( '-', $date );

				$time     = date( 'H:i:s', $compound );
				list ( $chour, $cminute, $cseconds ) = explode( ':', $time );

			}

			// If scheduled, split the date and time of the cron timestamp
			$payout = wp_next_scheduled( $this->cron_payout_key );
			if ( $payout !== false ) {

				$payout   = $this->timestamp_to_date( $payout );

				$date     = date( 'Y-m-d', $payout );
				list ( $pyear, $pmonth, $pday ) = explode( '-', $date );

				$time     = date( 'H:i:s', $payout );
				list ( $phour, $pminute, $pseconds ) = explode( ':', $time );

			}

?>
<div class="row">
	<div class="col-sm-6">

		<table class="widefat fixed striped" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th style="width: 100%;" colspan="4"><?php _e( 'Daily Compound Schedule', 'mycred' ); ?></th>
				</tr>
				<tr>
					<th style="width: 30%;"><?php _e( 'Year', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Month', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Day', 'mycred' ); ?></th>
					<th style="width: 30%;"><?php _e( 'Time', 'mycred' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><input type="text" name="<?php echo $this->field_name( array( 'cron_compound' => 'year' ) ); ?>" id="<?php echo $this->field_id( array( 'cron_compound' => 'year' ) ); ?>" class="form-control" placeholder="<?php echo $cyear; ?>" maxlength="4" size="6" value="" /></td>
					<td><input type="text" name="<?php echo $this->field_name( array( 'cron_compound' => 'month' ) ); ?>" id="<?php echo $this->field_id( array( 'cron_compound' => 'month' ) ); ?>" class="form-control" placeholder="<?php echo $cmonth; ?>" maxlength="2" size="4" value="" /></td>
					<td><input type="text" name="<?php echo $this->field_name( array( 'cron_compound' => 'day' ) ); ?>" id="<?php echo $this->field_id( array( 'cron_compound' => 'day' ) ); ?>" class="form-control" placeholder="<?php echo $cday; ?>" maxlength="2" size="4" value="" /></td>
					<td><?php echo $this->time_select( $this->field_name( array( 'cron_compound' => 'time' ) ), $this->field_id( array( 'cron_compound' => 'time' ) ), $compound ); ?></td>
				</tr>
			</tbody>
		</table>

	</div>
	<div class="col-sm-6">

		<table class="widefat fixed striped" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th style="width: 100%;" colspan="4"><?php _e( 'Payout Schedule', 'mycred' ); ?></th>
				</tr>
				<tr>
					<th style="width: 30%;"><?php _e( 'Year', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Month', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Day', 'mycred' ); ?></th>
					<th style="width: 30%;"><?php _e( 'Time', 'mycred' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><input type="text" name="<?php echo $this->field_name( array( 'cron_payout' => 'year' ) ); ?>" id="<?php echo $this->field_id( array( 'cron_payout' => 'year' ) ); ?>" class="form-control" placeholder="<?php echo $pyear; ?>" maxlength="4" size="6" value="" /></td>
					<td><input type="text" name="<?php echo $this->field_name( array( 'cron_payout' => 'month' ) ); ?>" id="<?php echo $this->field_id( array( 'cron_payout' => 'month' ) ); ?>" class="form-control" placeholder="<?php echo $pmonth; ?>" maxlength="2" size="4" value="" /></td>
					<td><input type="text" name="<?php echo $this->field_name( array( 'cron_payout' => 'day' ) ); ?>" id="<?php echo $this->field_id( array( 'cron_payout' => 'day' ) ); ?>" class="form-control" placeholder="<?php echo $pday; ?>" maxlength="2" size="4" value="" /></td>
					<td><?php echo $this->time_select( $this->field_name( array( 'cron_payout' => 'time' ) ), $this->field_id( array( 'cron_payout' => 'time' ) ), $payout ); ?></td>
				</tr>
			</tbody>
		</table>

	</div>
</div>
<div class="row">
	<div class="col-sm-6">
		<h3><?php _e( 'Setup', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-sm-3 col-xs-10">
				<div class="form-group">
					<label for="<?php echo $this->field_id( array( 'rate' => 'amount' ) ); ?>"><?php _e( 'Interest Rate', 'mycred' ); ?></label>
					<input type="text" name="<?php echo $this->field_name( array( 'rate' => 'amount' ) ); ?>" id="<?php echo $this->field_id( array( 'rate' => 'amount' ) ); ?>" class="form-control" placeholder="<?php _e( 'Required', 'mycred' ); ?>" value="<?php echo esc_attr( $this->prefs['rate']['amount'] ); ?>" />
				</div>
			</div>
			<div class="col-xs-1">
				<div class="form-group">
					<label>&nbsp;</label>
					<p class="form-control-static">%</p>
				</div>
			</div>
			<div class="col-sm-4 col-xs-6">
				<div class="form-group">
					<label for="<?php echo $this->field_id( array( 'name' => 'pay_out' ) ); ?>"><?php _e( 'Payout Frequency', 'mycred' ); ?></label>
					<?php $this->timeframe_dropdown( array( 'rate' => 'pay_out' ), false, false ); ?>
				</div>
			</div>
			<div class="col-sm-4 col-xs-6">
				<div class="form-group">
					<label for="<?php echo $this->field_id( 'min_balance' ); ?>"><?php _e( 'Minimum Balance', 'mycred' ); ?></label>
					<input type="text" name="<?php echo $this->field_name( 'min_balance' ); ?>" id="<?php echo $this->field_id( 'min_balance' ); ?>" class="form-control" placeholder="<?php _e( 'Required', 'mycred' ); ?>" value="<?php echo $this->core->number( $this->prefs['min_balance'] ); ?>" />
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6">
		<h3><?php _e( 'Payout Log Table', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-xs-12">
				<div class="form-group">
					<label for="<?php echo $this->field_id( 'log' ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
					<input type="text" name="<?php echo $this->field_name( 'log' ); ?>" id="<?php echo $this->field_id( 'log' ); ?>" class="form-control" value="<?php echo esc_attr( $this->prefs['log'] ); ?>" />
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-xs-12">
		<p><span class="description"><?php _e( 'Changing the payout period once the service is enabled, will only take effect once the currently scheduled payout runs. To change this, you will also need to adjust the payout schedule above.', 'mycred' ); ?></span></p>
	</div>
</div>
<div class="row">
	<div class="col-sm-6">
		<h3><?php _e( 'Exclude by ID', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-xs-12">
				<div class="form-group">
					<label for="<?php echo $this->field_id( 'exclude_ids' ); ?>"><?php _e( 'Comma separated list of user IDs', 'mycred' ); ?></label>
					<input type="text" name="<?php echo $this->field_name( 'exclude_ids' ); ?>" id="<?php echo $this->field_id( 'exclude_ids' ); ?>" class="form-control" placeholder="<?php _e( 'Comma separated list of user IDs', 'mycred' ); ?>" value="<?php echo esc_attr( $this->prefs['exclude_ids'] ); ?>" />
				</div>
			</div>
		</div>
	</div>
	<div class="col-sm-6">
		<h3><?php _e( 'Exclude by Role', 'mycred' ); ?></h3>
		<div class="row">
			<div class="col-xs-12">
				<div class="form-group">
					<label for="<?php echo $this->field_id( 'exclude_roles' ); ?>"><?php _e( 'Roles to exclude', 'mycred' ); ?></label>
				</div>
				<div class="row">
<?php

			foreach ( $editable_roles as $role => $details ) {

				$name = translate_user_role( $details['name'] );

				echo '<div class="col-xs-6"><div class="checkbox"><label for="' . $this->field_id( 'exclude-roles-' . $role ) . '"><input type="checkbox" name="' . $this->field_name( 'exclude_roles][' ) . '" id="' . $this->field_id( 'exclude-roles-' . $role ) . '" value="' . esc_attr( $role ) . '"';
				if ( in_array( $role, (array) $this->prefs['exclude_roles'] ) ) echo ' checked="checked"';
				echo ' />' . $name . '</label></div></div>';
			}

?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php

			do_action( 'mycred_banking_compound_interest', $this );

		}

		/**
		 * Sanitise Preferences
		 * @since 1.2
		 * @version 1.4
		 */
		public function sanitise_preferences( $post ) {

			$new_settings                    = array( 'rate' => array() );
			$new_settings['rate']['amount']  = str_replace( ',', '.', sanitize_text_field( $post['rate']['amount'] ) );
			if ( $new_settings['rate']['amount'] == '' ) $new_settings['rate']['amount'] = 0;

			$new_settings['rate']['pay_out'] = sanitize_text_field( $post['rate']['pay_out'] );

			$new_settings['log']             = sanitize_text_field( $post['log'] );
			$new_settings['min_balance']     = $this->core->number( str_replace( ',', '.', sanitize_text_field( $post['min_balance'] ) ) );
			$new_settings['exclude_ids']     = sanitize_text_field( $post['exclude_ids'] );

			if ( ! isset( $post['exclude_roles'] ) )
				$post['exclude_roles'] = array();

			$new_settings['exclude_roles']   = $post['exclude_roles'];

			$rescheduled = false;

			// Re-schedule compound cron job
			if ( $post['cron_compound']['year'] != '' && $post['cron_compound']['month'] != '' && $post['cron_compound']['day'] != '' && $post['cron_compound']['time'] != '' ) {

				$year  = absint( $post['cron_compound']['year'] );
				$month = zeroise( absint( $post['cron_compound']['month'] ), 2 );
				$day   = zeroise( absint( $post['cron_compound']['day'] ), 2 );

				$compound = $this->date_to_timestamp( $year . '-' . $month . '-' . $day . ' ' . $post['cron_compound']['time'] . ':00' );
				if ( $compound !== false && $compound > $this->now ) {

					$timestamp = wp_next_scheduled( $this->cron_compound_key );
					if ( $timestamp !== false ) {
						wp_clear_scheduled_hook( $this->cron_compound_key );
						$rescheduled = true;
					}

					wp_schedule_single_event( $compound, $this->cron_compound_key );

				}

			}

			// Re-schedule payout cron job
			if ( $post['cron_payout']['year'] != '' && $post['cron_payout']['month'] != '' && $post['cron_payout']['day'] != '' && $post['cron_payout']['time'] != '' ) {

				$year  = absint( $post['cron_payout']['year'] );
				$month = zeroise( absint( $post['cron_payout']['month'] ), 2 );
				$day   = zeroise( absint( $post['cron_payout']['day'] ), 2 );

				$payout = $this->date_to_timestamp( $year . '-' . $month . '-' . $day . ' ' . $post['cron_payout']['time'] . ':00' );
				if ( $payout !== false && $payout > $this->now ) {

					$timestamp = wp_next_scheduled( $this->cron_payout_key );
					if ( $timestamp !== false ) {
						wp_clear_scheduled_hook( $this->cron_payout_key );
						$rescheduled = true;
					}

					wp_schedule_single_event( $payout, $this->cron_payout_key );

				}

			}

			return apply_filters( 'mycred_banking_save_interest', $new_settings, $this, $rescheduled );

		}

		/**
		 * User Screen
		 * @since 1.7
		 * @version 1.0
		 */
		public function user_screen( $user ) {

			// Only visible to admins
			if ( ! mycred_is_admin() ) return;

			$users_rate = mycred_get_user_meta( $user->ID, 'mycred_banking_rate_' . $this->mycred_type, '', true );

?>
<table class="form-table">
	<tr>
		<th><label for="compoun-interest-rate-<?php echo $this->mycred_type; ?>"><?php _e( 'Interest Rate', 'mycred' ); echo ' (' . $this->core->plural() . ')'; ?></label></th>
		<td>
			<input type="text" size="8" name="mycred_interest_rate[<?php echo $this->mycred_type; ?>][rate]" id="compoun-interest-rate-<?php echo $this->mycred_type; ?>" placeholder="<?php echo esc_attr( $this->prefs['rate']['amount'] . ' %' ); ?>" value="<?php echo esc_attr( $users_rate ); ?>" /> %
			<p><span class="description"><?php _e( 'Leave empty to pay the default rate.', 'mycred' ); ?></span></p>
		</td>
	</tr>
</table>
<?php

		}

		/**
		 * Save User Override
		 * @since 1.5.2
		 * @version 1.0.2
		 */
		function save_custom_rate( $user_id ) {

			// Only visible to admins
			if ( ! mycred_is_admin() ) return;

			if ( isset( $_POST['mycred_interest_rate'] ) && array_key_exists( $this->mycred_type, $_POST['mycred_interest_rate'] ) ) {

				$rate = sanitize_text_field( $_POST['mycred_interest_rate'][ $this->mycred_type ]['rate'] );
				if ( strlen( $rate ) > 0 ) {

					$rate = str_replace( ',', '.', $rate );
					if ( $rate != $this->prefs['rate']['amount'] )
						mycred_update_user_meta( $user_id, 'mycred_banking_rate_' . $this->mycred_type, '', $rate );
					else
						mycred_delete_user_meta( $user_id, 'mycred_banking_rate_' . $this->mycred_type );

				}

			}

		}

	}
endif;
