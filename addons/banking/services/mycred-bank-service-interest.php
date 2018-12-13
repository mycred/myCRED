<?php
/**
 * myCRED Bank Service - Interest
 * @since 1.2
 * @version 1.1
 */
if ( ! defined( 'myCRED_VERSION' ) ) exit;

if ( ! class_exists( 'myCRED_Banking_Service_Interest' ) ) {
	class myCRED_Banking_Service_Interest extends myCRED_Service {

		/**
		 * Construct
		 */
		function __construct( $service_prefs ) {
			parent::__construct( array(
				'id'       => 'interest',
				'defaults' => array(
					'rate'         => array(
						'amount'       => 2,
						'period'       => 1,
						'pay_out'      => 'monthly'
					),
					'last_payout'   => '',
					'log'           => __( '%plural% interest rate payment', 'mycred' ),
					'min_balance'   => 1,
					'run_time'   => 60
				)
			), $service_prefs );
		}

		/**
		 * Run
		 * @since 1.2
		 * @version 1.0
		 */
		public function run() {
			add_action( 'wp_loaded',                        array( $this, 'process' ) );
			add_action( 'mycred_banking_interest_compound', array( $this, 'do_compound' ) );
			add_action( 'mycred_banking_do_compound_batch', array( $this, 'do_compound_batch' ) );
			
			add_action( 'mycred_banking_interest_payout',   array( $this, 'do_payouts' ) );
			add_action( 'mycred_banking_interest_do_batch', array( $this, 'do_interest_batch' ) );
		}

		/**
		 * Deactivation
		 * @since 1.2
		 * @version 1.0
		 */
		public function deactivate() {
			// Unschedule compounding
			$timestamp = wp_next_scheduled( 'mycred_banking_interest_compound' );
			if ( $timestamp !== false )
				wp_clear_scheduled_hook( $timestamp, 'mycred_banking_interest_compound' );

			// Unschedule payouts
			$timestamp = wp_next_scheduled( 'mycred_banking_interest_payout' );
			if ( $timestamp !== false )
				wp_clear_scheduled_hook( $timestamp, 'mycred_banking_interest_payout' );
		}
		
		/**
		 * Process
		 * Determines if we should run a payout or not and schedule the daily
		 * compounding.
		 * @since 1.2
		 * @version 1.0
		 */
		public function process() {
			// Unschedule if amount is set to zero
			if ( $this->prefs['rate']['amount'] == $this->core->zero() ) {
				$timestamp = wp_next_scheduled( 'mycred_banking_interest_compound' );
				if ( $timestamp !== false )
					wp_clear_scheduled_hook( $timestamp, 'mycred_banking_interest_compound' );
			}

			// Schedule if none exist
			if ( ! wp_next_scheduled( 'mycred_banking_interest_compound' ) ) {
				wp_schedule_event( time(), 'daily', 'mycred_banking_interest_compound' );
			}

			$unow = date_i18n( 'U' );
			// Cant pay interest on zero
			if ( $this->prefs['rate']['amount'] == $this->core->zero() ) return;
			
			// Should we payout
			$payout_now = $this->get_now( $this->prefs['rate']['pay_out'] );
			if ( empty( $this->prefs['last_payout'] ) || $this->prefs['last_payout'] === NULL ) {
				$last_payout = $this->get_last_run( $unow, $this->prefs['rate']['pay_out'] );
				$this->save( 'last_payout', $unow );
			}
			else {
				$last_payout = $this->get_last_run( $this->prefs['last_payout'], $this->prefs['rate']['pay_out'] );
			}
			if ( $payout_now === false || $last_payout === false ) return;

			// Time to run?
			if ( $this->time_to_run( $this->prefs['rate']['pay_out'], $last_payout ) ) {
				// Save
				$this->save( 'last_payout', $unow );
				
				// Schedule Payouts
				if ( wp_next_scheduled( 'mycred_banking_interest_payout' ) === false )
					wp_schedule_single_event( time(), 'mycred_banking_interest_payout' );
			}
		}

		/**
		 * Do Compound
		 * Either runs compounding on every single user in one go, or split the users
		 * up into groups of 2000 IDs and do them in batches.
		 * @since 1.2
		 * @version 1.0
		 */
		public function do_compound() {
			if ( $this->prefs['rate']['amount'] == $this->core->zero() ) return;
			// Get users
			$users = $this->get_users();
			$total = count( $users );
			$threshold = (int) apply_filters( 'mycred_do_banking_limit', 2000 );
			
			if ( (int) $total > $threshold ) {
				$batches = array_chunk( $users, $threshold );
				$time = time();
				
				$set = 0;
				foreach ( $batches as $batch_id => $batch ) {
					$set = $set+1;
					$run_time = ( $time + ( 60*$set ) );
					if ( wp_next_scheduled( $run_time, 'mycred_banking_do_compound_batch', array( $batch ) ) === false )
						wp_schedule_single_event( $run_time, 'mycred_banking_do_compound_batch', array( $batch ) );
				}
			}
			else {
				$this->do_compound_batch( $users );
			}
		}
		
		/**
		 * Do Compound Batch
		 * Compounds interest for each user ID given in batch.
		 * @since 1.2
		 * @version 1.2.1
		 */
		public function do_compound_batch( $batch ) {
			if ( !empty( $batch ) && is_array( $batch ) ) {

				set_time_limit( $this->prefs['run_time'] );

				foreach ( $batch as $user_id ) {
					$user_id = intval( $user_id );
										
					// Current balance
					$balance = $this->core->get_users_cred( $user_id );
					if ( $balance == 0 ) continue;
					
					// Get past interest
					$past_interest = mycred_get_user_meta( $user_id, $this->core->get_cred_id() . '_comp', '', true );
					if ( empty( $past_interest ) ) $past_interest = 0;
					
					// Min Balance Limit
					if ( $balance < $this->core->number( $this->prefs['min_balance'] ) ) continue;
					
					// Convert rate
					$rate = $this->prefs['rate']['amount']/100;
					
					// Period
					$period = $this->prefs['rate']['period']/$this->get_days_in_year();
					
					// Compound
					$interest = ( $balance + $past_interest ) * $rate * $period;
					$interest = round( $interest, 2 );
					
					// Save interest
					mycred_update_user_meta( $user_id, $this->core->get_cred_id() . '_comp', '', $interest );
				}
			}
		}

		/**
		 * Payout
		 * Will either payout to all users in one go or if there is more then
		 * 2000 members, do them in batches of 2000 at a time.
		 * @since 1.2
		 * @version 1.1
		 */
		public function do_payouts() {
			// Make sure to clear any stray schedules to prevent duplicates
			wp_clear_scheduled_hook( 'mycred_banking_interest_payout' );

			// Query
			$users = $this->get_users();
			$total = count( $users );
			$threshold = (int) apply_filters( 'mycred_do_banking_limit', 2000 );
			
			if ( (int) $total > $threshold ) {
				$batches = array_chunk( $users, $threshold );
				$time = time();
				
				$set = 0;
				foreach ( $batches as $batch_id => $batch ) {
					$set = $set+1;
					$run_time = ( $time + ( 60*$set ) );
					if ( wp_next_scheduled( $run_time, 'mycred_banking_interest_do_batch', array( $batch ) ) === false )
						wp_schedule_single_event( $run_time, 'mycred_banking_interest_do_batch', array( $batch ) );
				}
			}
			else {
				$this->do_interest_batch( $users );
			}
		}
		
		/**
		 * Do Payout
		 * Runs though all user compounded interest and pays.
		 * @since 1.2
		 * @version 1.2.1
		 */
		public function do_interest_batch( $batch ) {
			if ( !empty( $batch ) && is_array( $batch ) ) {

				set_time_limit( $this->prefs['run_time'] );

				foreach ( $batch as $user_id ) {
					$user_id = intval( $user_id );
										
					// Get past interest
					$past_interest = mycred_get_user_meta( $user_id, $this->core->get_cred_id() . '_comp', '', true );
					if ( empty( $past_interest ) || $past_interest == 0 ) continue;
					
					// Pay / Charge
					$this->core->add_creds(
						'payout',
						$user_id,
						$past_interest,
						$this->prefs['log']
					);
					
					// Reset past interest
					mycred_update_user_meta( $user_id, $this->core->get_cred_id() . '_comp', '', 0 );
				}
			}
		}
		
		/**
		 * Save
		 * Saves the given preference id for rates.
		 * from the active list.
		 * @since 1.2
		 * @version 1.1
		 */
		public function save( $id, $now ) {
			if ( !isset( $this->prefs[ $id ] ) ) return;
			$this->prefs[ $id ] = $now;

			// Get Bank settings
			$bank = mycred_get_option( 'mycred_pref_bank' );
			
			// Update settings
			$bank['service_prefs'][$this->id] = $this->prefs;

			// Save new settings
			mycred_update_option( 'mycred_pref_bank', $bank );
		}

		/**
		 * Preference for Savings
		 * @since 1.2
		 * @version 1.2
		 */
		public function preferences() {
			$prefs = $this->prefs; ?>

					<label class="subheader"><?php _e( 'Interest Rate', 'mycred' ); ?></label>
					<ol class="inline">
						<li>
							<label>&nbsp;</label>
							<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'rate' => 'amount' ) ); ?>" id="<?php echo $this->field_id( array( 'rate' => 'amount' ) ); ?>" value="<?php echo $this->core->format_number( $prefs['rate']['amount'] ); ?>" size="4" /> %</div>
						</li>
						<li>
							<label for="<?php echo $this->field_id( array( 'rate' => 'pay_out' ) ); ?>"><?php _e( 'Payed / Charged', 'mycred' ); ?></label><br />
							<?php $this->timeframe_dropdown( array( 'rate' => 'pay_out' ), false, false ); ?>

						</li>
						<li class="block">
							<input type="hidden" name="<?php echo $this->field_name( 'last_payout' ); ?>" value="<?php echo $prefs['last_payout']; ?>" />
							<span class="description"><?php _e( 'The interest rate can be either positive or negative and is compounded daily.', 'mycred' ); ?></span>
						</li>
					</ol>
					<label class="subheader"><?php _e( 'Minimum Balance', 'mycred' ); ?></label>
					<ol>
						<li>
							<div class="h2"><?php if ( $this->core->before != '' ) echo $this->core->before . ' '; ?><input type="text" name="<?php echo $this->field_name( 'min_balance' ); ?>" id="<?php echo $this->field_id( 'min_balance' ); ?>" value="<?php echo $this->core->format_number( $prefs['min_balance'] ); ?>" size="8" /><?php if ( $this->core->after != '' ) echo ' ' . $this->core->after; ?></div>
							<span class="description"><?php _e( 'The minimum requires balance for interest to apply.', 'mycred' ); ?></span>
						</li>
					</ol>
					<label class="subheader"><?php _e( 'Log Template', 'mycred' ); ?></label>
					<ol>
						<li>
							<div class="h2"><input type="text" name="<?php echo $this->field_name( 'log' ); ?>" id="<?php echo $this->field_id( 'log' ); ?>" value="<?php echo $prefs['log']; ?>" style="width: 65%;" /></div>
							<span class="description"><?php echo $this->core->available_template_tags( array( 'general' ), '%timeframe%, %rate%, %base%' ); ?></span>
						</li>
					</ol>
					<label class="subheader" for="<?php echo $this->field_id( 'run_time' ); ?>"><?php _e( 'Run Time', 'mycred' ); ?></label>
					<ol>
						<li>
							<div class="h2"><input type="text" name="<?php echo $this->field_name( 'run_time' ); ?>" id="<?php echo $this->field_id( 'run_time' ); ?>" value="<?php echo $prefs['run_time']; ?>" size="4" /></div>
							<span class="description"><?php _e( 'For large websites, if you are running into time out issues during payouts, you can set the number of seconds a process can run. Use zero for unlimited, but be careful especially if you are on a shared server.', 'mycred' ); ?></span>
						</li>
					</ol>
					<?php do_action( 'mycred_banking_compound_interest', $this->prefs ); ?>
<?php
		}

		/**
		 * Sanitise Preferences
		 * @since 1.2
		 * @version 1.2
		 */
		function sanitise_preferences( $post ) {
			$new_settings = $post;

			$new_settings['rate']['amount'] = str_replace( ',', '.', trim( $post['rate']['amount'] ) );

			$new_settings['rate']['period'] = $this->get_days_in_year();

			$new_settings['rate']['pay_out'] = sanitize_text_field( $post['rate']['pay_out'] );

			$new_settings['min_balance'] = str_replace( ',', '.', trim( $post['min_balance'] ) );
			
			$new_settings['last_payout'] = trim( $post['last_payout'] );

			$new_settings['log'] = trim( $post['log'] );

			$post['run_time'] = abs( $post['run_time'] );
			$new_settings['run_time'] = sanitize_text_field( $post['run_time'] );

			return apply_filters( 'mycred_banking_save_interest', $new_settings, $this->prefs );
		}
	}
}

?>