<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Bank Service - Recurring Payouts
 * @since 1.2
 * @version 1.2
 */
if ( ! class_exists( 'myCRED_Banking_Service_Payouts' ) ) :
	class myCRED_Banking_Service_Payouts extends myCRED_Service {

		public $default_schedule = array();
		public $statuses         = array();
		public $schedules        = array();
		public $log_reference    = '';

		/**
		 * Construct
		 */
		function __construct( $service_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'payouts',
				'defaults' => array()
			), $service_prefs, $type );

			$this->default_schedule = mycred_get_recurring_payout_defaults();

			$this->statuses         = array(
				0 => __( 'Waiting to Start', 'mycred' ),
				1 => __( 'Active', 'mycred' ),
				2 => __( 'Running', 'mycred' ),
				3 => __( 'Finished', 'mycred' ),
				4 => __( 'Stopped', 'mycred' )
			);

			$this->schedules        = mycred_get_recurring_payout_schedules( $type );
			$this->log_reference    = apply_filters( 'mycred_bank_recurring_reference', 'recurring', $this );

		}

		/**
		 * Activate Service
		 * @since 1.5.2
		 * @version 1.1
		 */
		public function activate() {

			if ( ! empty( $this->schedules ) ) {
				foreach ( $this->schedules as $schedule_id => $setup ) {

					// Status 3 = finished and Status 4 = Stopped
					if ( absint( $setup['status'] ) > 2 ) continue;

					$next_run  = mycred_banking_get_next_payout( $setup['frequency'], $setup['last_run'] );
					$timestamp = wp_next_scheduled( 'mycred-recurring-' . $schedule_id );

					if ( $timestamp !== false )
						$next_run = $timestamp;

					// We did not miss our schedule, lets re-schedule
					if ( $next_run > $this->now )
						wp_schedule_single_event( $next_run, 'mycred-recurring-' . $schedule_id, array( 'id' => $schedule_id ) );

					// Upps, we missed it, clear and disable this setup
					else {

						$setup['status'] = 4;
						mycred_update_recurring_payout( $schedule_id, $setup, $this->mycred_type );

						if ( $timestamp !== false )
							wp_clear_scheduled_hook( 'mycred-recurring-' . $schedule_id, array( 'id' => $schedule_id ) );

					}

				}
			}

		}

		/**
		 * Deactivation
		 * @since 1.2
		 * @version 1.1
		 */
		public function deactivate() {

			if ( ! empty( $this->schedules ) ) {
				foreach ( $this->schedules as $schedule_id => $setup ) {

					// Status 3 = finished and Status 4 = Stopped
					if ( absint( $setup['status'] ) > 2 ) continue;

					wp_clear_scheduled_hook( 'mycred-recurring-' . $schedule_id, array( 'id' => $schedule_id ) );

				}
			}

		}

		/**
		 * Run
		 * @since 1.2
		 * @version 1.1
		 */
		public function run() {

			if ( ! empty( $this->schedules ) ) {
				foreach ( $this->schedules as $schedule_id => $setup ) {

					// Status 3 = finished and Status 4 = Stopped
					if ( absint( $setup['status'] ) > 2 ) continue;

					add_action( 'mycred-recurring-' . $schedule_id, array( $this, 'run_schedule' ) );

				}
			}

		}

		/**
		 * Run Schedule
		 * @since 1.2
		 * @version 1.1
		 */
		public function run_schedule( $schedule_id = NULL ) {

			if ( $schedule_id === NULL ) return;

			// Get settings
			$setup          = mycred_get_recurring_payout( $schedule_id, $this->mycred_type );
			$instance       = 'mycred-recurring-' . $schedule_id;
			$infinite_run   = ( ( $setup['runs_remaining'] < 0 ) ? true : false );
			$runs_left      = absint( $setup['runs_remaining'] );
			$next_run       = mycred_banking_get_next_payout( $setup['frequency'], $this->now );

			// Need to make sure amount is not zero
			if ( $setup['payout'] == '' || $this->core->number( $setup['payout'] ) === $this->core->zero() ) return;

			// No runs remaining - Move to finish
			if ( ! $infinite_run && $runs_left === 0 ) {

				$setup['status']   = 3;
				$setup['last_run'] = $this->now;

				mycred_update_recurring_payout( $schedule_id, $setup, $this->mycred_type );
				wp_clear_scheduled_hook( $instance );

				return;

			}

			// No need to run if the central bank is out of funds
			$settings            = mycred_get_banking_addon_settings( NULL, $this->mycred_type );
			$ignore_central_bank = false;
			if ( in_array( 'central', $settings['active'] ) && $setup['ignore_central'] == 1 )
				$ignore_central_bank = true;

			// Default number of balances we will be running through for now
			$number          = absint( apply_filters( 'mycred_recurring_max_limit', 1500, $this ) );
			$original_start  = mycred_get_option( 'mycred-recurring-next-run-' . $schedule_id, false );

			$single_instance = true;
			$transient_key   = 'mycred-recurring-' . $schedule_id;
			$offset          = get_transient( $transient_key );
			if ( $offset === false ) $offset = 0;

			// Get eligible users
			$eligeble_users  = $this->get_eligible_users( $number, $offset, $setup );

			// Check if we can do this in a single instance or if we need multiple
			if ( ( $eligeble_users['total'] - $offset ) > $number ) {

				$single_instance = false;

				// Schedule our next event in 2 minutes
				wp_schedule_single_event( ( $this->now + 180 ), $instance );

				// Apply limit and set a transient to keep track of our progress
				if ( $offset === 0 )
					set_transient( $transient_key, $number, HOUR_IN_SECONDS );

				// While in loop, we need to keep track of our progress
				else {

					delete_transient( $transient_key );
					$offset += count( $eligeble_users['results'] );
					set_transient( $transient_key, $offset, HOUR_IN_SECONDS );

				}

				if ( $original_start === false )
					update_option( 'mycred-recurring-next-run-' . $schedule_id, $next_run );

			}

			// Single instance
			else {

				if ( $original_start === false )
					$original_start = $next_run;

				else {
					delete_transient( $transient_key );
					delete_option( 'mycred-recurring-next-run-' . $schedule_id );
				}

				wp_schedule_single_event( $original_start, $instance, array( 'id' => $schedule_id ) );

			}

			// Loop through users and payout
			$missed = $completed = 0;
			if ( ! empty( $eligeble_users['results'] ) ) {

				foreach ( $eligeble_users['results'] as $user_id ) {

					if ( ! $ignore_central_bank ) {

						$paid = $this->core->add_creds(
							$this->log_reference,
							$user_id,
							$setup['payout'],
							$setup['log_template'],
							0,
							$schedule_id,
							$this->mycred_type
						);

					}

					// Ignore the central bank and just pay
					// Curcumvents any custom code using the mycred_add, mycred_run_this and mycred_add_finished filters.
					else {

						$this->core->update_users_balance( $user_id, $setup['payout'], $this->mycred_type );
						$this->core->add_to_log(
							$this->log_reference,
							$user_id,
							$setup['payout'],
							$setup['log_template'],
							0,
							$schedule_id,
							$this->mycred_type
						);

						$paid = true;

					}

					if ( $paid )
						$completed ++;
					else
						$missed ++;

				}

			}

			if ( $setup['status'] == 0 )
				$setup['status'] = 1;

			$setup['total_completed'] = ( $setup['total_completed'] + $completed );
			$setup['total_misses']    = ( $setup['total_misses'] + $missed );

			mycred_update_recurring_payout( $schedule_id, $setup, $this->mycred_type );

			// In case we finished, update the schedule and clear the CRON
			if ( $single_instance ) {

				if ( ! $infinite_run ) {

					$runs_left--;

					$setup['runs_remaining'] = $runs_left;

				}

				$setup['last_run']       = $this->now;

				if ( ! $infinite_run && $runs_left == 0 )
					$setup['status'] = 3;

				mycred_update_recurring_payout( $schedule_id, $setup, $this->mycred_type );

				if ( ! $infinite_run && $runs_left === 0 )
					wp_clear_scheduled_hook( $instance );

			}

		}

		/**
		 * Get Eligible Users
		 * @since 1.7
		 * @version 1.0
		 */
		public function get_eligible_users( $number = 0, $offset = 0, $setup ) {

			global $wpdb;

			$query_args = array();
			$meta_query = array();

			// Only interested in the user IDs.
			$query_args['fields']  = 'ID';
			$query_args['orderby'] = 'ID';

			$query_args['number']  = $number;

			if ( $offset > 0 )
				$query_args['offset'] = $offset;

			// Limit by minimum balance
			if ( $setup['min_balance'] != 0 && $setup['max_balance'] == 0 )
				$meta_query[] = array(
					'key'     => $this->mycred_type,
					'value'   => $this->core->number( $setup['min_balance'] ),
					'compare' => '>=',
					'type'    => 'NUMERIC'
				);

			// Limit by maximum balance
			elseif ( $setup['min_balance'] == 0 && $setup['max_balance'] != 0 )
				$meta_query[] = array(
					'key'     => $this->mycred_type,
					'value'   => $this->core->number( $setup['max_balance'] ),
					'compare' => '<',
					'type'    => 'NUMERIC'
				);

			// Range
			elseif ( $setup['min_balance'] != 0 && $setup['max_balance'] != 0 )
				$meta_query[] = array(
					'key'     => $this->mycred_type,
					'value'   => array( $this->core->number( $setup['min_balance'] ), $this->core->number( $setup['max_balance'] ) ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				);

			// Limit by id
			if ( $setup['id_list'] != '' ) {

				$user_id_list = array();

				$list_of_ids  = explode( ',', $setup['id_list'] );
				foreach ( $list_of_ids as $user_id ) {
					$user_id = absint( trim( $user_id ) );
					if ( $user_id !== 0 && ! in_array( $user_id, $clean_ids ) )
						$user_id_list[] = $user_id;
				}

				// Take into account users that have been excluded via the point type
				if ( ! empty( $this->core->exclude['list'] ) ) {
					$list_of_ids  = explode( ',', $this->core->exclude['list'] );
					foreach ( $list_of_ids as $user_id ) {
						$user_id = absint( trim( $user_id ) );
						if ( $user_id !== 0 && ! in_array( $user_id, $clean_ids ) )
							$user_id_list[] = $user_id;
					}
				}

				if ( ! empty( $user_id_list ) ) {

					if ( $setup['id_exclude'] == 'exclude' )
						$query_args['exclude'] = $user_id_list;
					else
						$query_args['include'] = $user_id_list;

				}

			}

			// Limit by role
			if ( ! empty( $setup['role_list'] ) ) {

				$blog_id = 0;
				if ( ! mycred_centralize_log() ) {
					$blog_id               = get_current_blog_id();
					$query_args['blog_id'] = $blog_id;
				}

				$role_query = array();

				// Exclude by role = role is not x AND role is not y
				// Include by role = role is x OR role is y
				if ( count( $setup['role_list'] ) > 1 )
					$role_query['relation'] = ( ( $setup['role_exclude'] == 'exclude' ) ? 'AND' : 'OR' );

				// Since the "role__in" and "role__not_in" arguments are not available until WordPress 4.4, we just use meta query for this.
				foreach ( $setup['role_list'] as $role )
					$role_query[] = array(
						'key'     => $wpdb->get_blog_prefix( $blog_id ) . 'capabilities',
						'value'   => '"' . $role . '"',
						'compare' => ( ( $setup['role_exclude'] == 'exclude' ) ? 'NOT LIKE' : 'LIKE' )
					);

				$meta_query[] = $role_query;

			}


			if ( ! empty( $meta_query ) )
				$query_args['meta_query'] = $meta_query;

			$query = new WP_User_Query( $query_args );

			return array(
				'total'   => $query->get_total(),
				'results' => $query->get_results()
			);

		}

		/**
		 * Display Schedule Table
		 * @since 1.7
		 * @version 1.0
		 */
		public function display_schedule_table( $schedule = NULL ) {

			$content     = '';
			$date_format = get_option( 'date_format' );
			$time_format = get_option( 'time_format' );
			$timeframes  = mycred_banking_get_timeframes();

			$schedules   = mycred_get_recurring_payout_schedules( $this->mycred_type );

			// This function is also used to display just one particular table row
			if ( $schedule !== NULL )
				$schedules = array( $schedule['id'] => $schedule );

			if ( ! empty( $schedules ) ) {

				ob_start();

				foreach ( $schedules as $schedule_id => $setup ) {

					$setup    = shortcode_atts( $this->default_schedule, $setup );
					$last_run = $this->timestamp_to_date( $setup['last_run'] );
					$next_run = mycred_banking_get_next_payout( $setup['frequency'], $last_run );

					// Pending start
					if ( $setup['status'] == 0 ) {
						$next_run = date( $date_format . ' ' . $time_format, $last_run );
						$last_run = __( 'Not yet started', 'mycred' );
					}

					// Running
					elseif ( $setup['status'] == 1 ) {
						$last_run = date( $date_format . ' ' . $time_format, $last_run );
						$next_run = date( $date_format . ' ' . $time_format, $next_run );
					}

					// CRON job is running right now
					elseif ( $setup['status'] == 2 ) {
						$last_run = __( 'Currently Running', 'mycred' );
						$next_run = '-';
					}

					// Finished or stopped
					else {
						$last_run = date( $date_format . ' ' . $time_format, $last_run );
						$next_run = '-';
					}

?>
<tr id="schedule-<?php echo $schedule_id; ?>">
	<td class="col-job-title">
		<div><?php echo esc_attr( $setup['job_title'] ); ?></div>
		<div class="row-actions">
			<span class="edit"><a href="javascript:void(0);" class="view-recurring-schedule" data-id="<?php echo $schedule_id; ?>" data-title="<?php _e( 'View Schedule', 'mycred' ); ?>"><?php _e( 'View', 'mycred' ); ?></a> | </span> <span class="delete"><a href="javascript:void(0);" class="delete-recurring-schedule" data-id="<?php echo $schedule_id; ?>" data-title="<?php _e( 'Delete Schedule', 'mycred' ); ?>"><?php _e( 'Delete', 'mycred' ); ?></a></span>
		</div>
	</td>
	<td class="col-job-status">
		<div><?php if ( array_key_exists( $setup['status'], $this->statuses ) ) echo $this->statuses[ $setup['status'] ]; else echo '-'; ?></div>
	</td>
	<td class="col-job-frequency">
		<div><?php if ( array_key_exists( $setup['frequency'], $timeframes ) ) echo $timeframes[ $setup['frequency'] ]['label']; else echo '-'; ?></div>
	</td>
	<td class="col-job-last-ran">
		<div><?php echo $last_run; ?></div>
	</td>
	<td class="col-job-next-run">
		<div><?php echo $next_run; ?></div>
	</td>
</tr>
<?php

				}

				$content = ob_get_contents();
				ob_end_clean();

			}

			return $content;

		}

		/**
		 * View Schedule Form
		 * @since 1.7
		 * @version 1.0.1
		 */
		public function view_schedule_form( $schedule_id, $setup ) {

			$limits         = array();
			$date_format    = get_option( 'date_format' );
			$time_format    = get_option( 'time_format' );
			$timeframes     = mycred_banking_get_timeframes();
			$settings       = mycred_get_banking_addon_settings( NULL, $this->mycred_type );

			global $wpdb, $mycred_log_table;

			$total_payout   = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(creds) FROM {$mycred_log_table} WHERE ref = %s AND data = %s;", $this->log_reference, $schedule_id ) );
			if ( $total_payout === NULL ) $total_payout = 0;

			$last_run       = mycred_gmt_timestamp_to_local( $setup['last_run'] );
			$eligeble_users = $this->get_eligible_users( 1, 5, $setup );

			if ( $setup['min_balance'] != 0 )
				$limits[] = '<div class="col-xs-6"><p>' . __( 'Min. Balance', 'mycred' ) . ': ' . $this->core->format_creds( $setup['min_balance'] ) . '</p></div>';

			if ( $setup['max_balance'] != 0 )
				$limits[] = '<div class="col-xs-6"><p>' . __( 'Max. Balance', 'mycred' ) . ': ' . $this->core->format_creds( $setup['max_balance'] ) . '</p></div>';

			if ( $setup['id_list'] != '' ) {

				if ( $setup['id_exclude'] == 'exclude' )
					$limits[] = '<div class="col-xs-6"><p>' . __( 'Exclude Users', 'mycred' ) . ': ' . esc_attr( $setup['id_list'] ) . '</p></div>';
				else
					$limits[] = '<div class="col-xs-6"><p>' . __( 'Include Users', 'mycred' ) . ': ' . esc_attr( $setup['id_list'] ) . '</p></div>';

			}

			if ( ! empty( $setup['role_list'] ) ) {

				if ( $setup['role_exclude'] == 'exclude' )
					$limits[] = '<div class="col-xs-12"><p>' . __( 'Exclude Roles', 'mycred' ) . ': <code>' . implode( '</code>, <code>', $setup['role_list'] ) . '</code></p></div>';
				else
					$limits[] = '<div class="col-xs-12"><p>' . __( 'Include Roles', 'mycred' ) . ': <code>' . implode( '</code>, <code>', $setup['role_list'] ) . '</code></p></div>';

			}

			ob_start();

?>
<div class="padded">
	<div class="row">
		<div class="col-xs-3">
			<div class="form-group">
				<label><?php _e( 'ID', 'mycred' ); ?></label>
				<p class="form-control-static"><?php echo esc_attr( $schedule_id ); ?></p>
			</div>
		</div>
		<div class="col-xs-6">
			<div class="form-group">
				<label><?php _e( 'Job Title', 'mycred' ); ?></label>
				<p class="form-control-static"><?php echo esc_attr( $setup['job_title'] ); ?></p>
			</div>
		</div>
		<div class="col-xs-3">
			<div class="form-group">
				<label><?php _e( 'Status', 'mycred' ); ?></label>
				<p class="form-control-static"><?php if ( array_key_exists( $setup['status'], $this->statuses ) ) echo $this->statuses[ $setup['status'] ]; else echo '-'; ?></p>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-5">
			<div class="form-group">
				<label><?php if ( $setup['status'] == 0 ) _e( 'Start Date', 'mycred' ); else _e( 'Last Run', 'mycred' ); ?></label>
				<p class="form-control-static"><?php echo date( $date_format . ' ' . $time_format, $last_run ); ?></p>
			</div>
		</div>
		<div class="col-xs-2">
			<div class="form-group">
				<label><?php _e( 'Eligible', 'mycred' ); ?></label>
				<p class="form-control-static"><?php printf( _n( '1 User', '%d Users', $eligeble_users['total'], 'mycred' ), $eligeble_users['total'] ); ?></p>
			</div>
		</div>
		<div class="col-xs-2">
			<div class="form-group">
				<label><?php _e( 'Runs', 'mycred' ); ?></label>
				<p class="form-control-static"><?php echo esc_attr( ( ( $setup['total_runs'] < 0 ) ? __( 'Infinite', 'mycred' ) : $setup['total_runs'] ) ); ?></p>
			</div>
		</div>
		<div class="col-xs-3">
			<div class="form-group">
				<label><?php _e( 'Payout', 'mycred' ); ?></label>
				<p class="form-control-static"><?php printf( '%s / %s', $this->core->format_creds( $setup['payout'] ), $timeframes[ $setup['frequency'] ]['single'] ); ?></p>
			</div>
		</div>
	</div>
	<?php if ( ! empty( $limits ) ) : ?>
	<div class="row">
		<div class="col-xs-12">
			<strong><label><?php _e( 'Limits', 'mycred' ); ?>:</label></strong>
			<div class="row list">
			<?php echo implode( '', $limits ); ?>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<div class="row">
		<div class="col-xs-12">
			<?php if ( $setup['ignore_central'] == 1 && in_array( 'central', $settings['active'] ) ) : ?>
			<p><strong><?php _e( 'Will payout even if the central bank account has run out of funds.', 'mycred' ); ?></strong></p>
			<?php endif; ?>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 25%;"><?php _e( 'Paid Out', 'mycred' ); ?></th>
						<th style="width: 25%;"><?php _e( 'Completed', 'mycred' ); ?></th>
						<th style="width: 25%;"><?php _e( 'Misses', 'mycred' ); ?> *</th>
						<th style="width: 25%;"><?php _e( 'Runs Left', 'mycred' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
							<h1><?php echo $this->core->format_creds( $total_payout ); ?></h1>
						</td>
						<td>
							<h1><?php echo absint( $setup['total_completed'] ); ?></h1>
						</td>
						<td>
							<h1><?php echo absint( $setup['total_misses'] ); ?></h1>
						</td>
						<td>
							<h1><?php echo absint( $setup['runs_remaining'] ); ?></h1>
						</td>
					</tr>
				</tbody>
			</table>
			<p><span class="description">* <?php _e( 'A miss is when a payout was declined by the plugin. This can be due to custom code declining the payout or if the user is excluded.', 'mycred' ); ?></span></p>
		</div>
	</div>
</div>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			return $content;

		}

		/**
		 * Manage Schedule Form
		 * @since 1.7
		 * @version 1.0
		 */
		public function manage_schedule_form( $schedule_id, $setup ) {

			$year = $month = $day = '';
			$time = NULL;

			$timeframes = mycred_banking_get_timeframes();
			$compound   = wp_next_scheduled( $setup['last_run'] );
			$settings   = mycred_get_banking_addon_settings( NULL, $this->mycred_type );

			if ( $compound !== false ) {

				$last_run = $this->timestamp_to_date( $setup['last_run'] );

				$date     = date( 'Y-m-d', $last_run );
				list ( $year, $month, $day ) = explode( '-', $date );

				$time     = date( 'H:i', $last_run );

			}

			ob_start();

?>
<div class="padded">
	<div class="row">
		<div class="col-xs-6">
			<div class="form-group">
				<label><?php _e( 'Schedule Title', 'mycred' ); ?></label>
				<input type="text" name="job_title" class="form-control cant-be-empty" value="<?php echo esc_attr( $setup['job_title'] ); ?>" />
			</div>
		</div>
		<div class="col-xs-3">
			<div class="form-group">
				<label><?php echo $this->core->plural(); ?></label>
				<input type="text" name="payout" class="form-control cant-be-empty" placeholder="<?php echo $this->core->zero(); ?>" value="" />
			</div>
		</div>
		<div class="col-xs-3">
			<div class="form-group">
				<label><?php _e( 'Frequency', 'mycred' ); ?></label>
				<select name="frequency" class="form-control">
<?php

			foreach ( $timeframes as $value => $data ) {
				echo '<option value="' . $value . '"';
				if ( $setup['frequency'] == $value ) echo ' selected="selected"';
				echo '>' . $data['label'] . '</option>';
			}

?>
				</select>
			</div>
		</div>
		<div class="col-xs-12">
			<div class="form-group">
				<label><?php _e( 'Log Template', 'mycred' ); ?></label>
				<input type="text" name="log_template" class="form-control cant-be-empty" value="<?php echo esc_attr( $setup['log_template'] ); ?>" />
			</div>
			<?php if ( in_array( 'central', $settings['active'] ) ) : ?>
			<div class="form-group">
				<div class="checkbox"><label for="new-schedule-ignore-central-bank"><input type="checkbox" name="ignore_central" id="new-schedule-ignore-central-bank"<?php checked( $setup['ignore_central'], 1 ); ?> value="1" /> <?php _e( 'Payout even if the central bank account runs out of funds.', 'mycred' ); ?></label></div>
			</div>
			<?php else : ?>
			<input type="hidden" name="ignore_central"<?php checked( $setup['ignore_central'], 1 ); ?> value="1" />
			<?php endif; ?>
		</div>
	</div>
	<h3><?php _e( 'First Payout', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-xs-8">
			<div class="row">
				<div class="col-xs-3">
					<div class="form-group">
						<label><?php _e( 'Year', 'mycred' ); ?></label>
						<input type="text" name="last_run[year]" class="form-control cant-be-empty" placeholder="YYYY" value="<?php echo esc_attr( $year ); ?>" />
					</div>
				</div>
				<div class="col-xs-3">
					<div class="form-group">
						<label><?php _e( 'Month', 'mycred' ); ?></label>
						<input type="text" name="last_run[month]" class="form-control cant-be-empty" placeholder="MM" value="<?php echo esc_attr( $month ); ?>" />
					</div>
				</div>
				<div class="col-xs-3">
					<div class="form-group">
						<label><?php _e( 'Date', 'mycred' ); ?></label>
						<input type="text" name="last_run[day]" class="form-control cant-be-empty" placeholder="DD" value="<?php echo esc_attr( $day ); ?>" />
					</div>
				</div>
				<div class="col-xs-3">
					<div class="form-group">
						<label><?php _e( 'Time', 'mycred' ); ?></label>
						<?php echo $this->time_select( 'last_run[time]', 'last-run-time', $time ); ?>
					</div>
				</div>
			</div>
		</div>
		<div class="col-xs-4">
			<div class="form-group">
				<label><?php _e( 'Repeat', 'mycred' ); ?></label>
				<input type="number" name="total_runs" class="form-control" placeholder="0" min="-1" max="256" value="<?php echo esc_attr( $setup['total_runs'] ); ?>" />
				<p><span class="description"><?php _e( 'Use -1 for infinite runs.', 'mycred' ); ?></span></p>
			</div>
		</div>
	</div>
	<h3><?php _e( 'Limits', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-sm-4 col-xs-12">
			<div class="row">
				<div class="col-sm-12 col-xs-6">
					<div class="form-group">
						<label><?php _e( 'Min. Balance', 'mycred' ); ?></label>
						<input type="number" name="min_balance" class="form-control" placeholder="0" min="0" value="<?php echo esc_attr( $setup['min_balance'] ); ?>" />
						<p><span class="description"><?php _e( 'Use zero to disable.', 'mycred' ); ?></span></p>
					</div>
				</div>
				<div class="col-sm-12 col-xs-6">
					<div class="form-group">
						<label><?php _e( 'Max. Balance', 'mycred' ); ?></label>
						<input type="number" name="max_balance" class="form-control" placeholder="0" min="0" value="<?php echo esc_attr( $setup['max_balance'] ); ?>" />
						<p><span class="description"><?php _e( 'Use zero to disable.', 'mycred' ); ?></span></p>
					</div>
				</div>
			</div>
		</div>
		<div class="col-sm-8 col-xs-12">
			<div class="row">
				<div class="col-sm-5 col-xs-12">
					<div class="form-group">
						<label><?php _e( 'Limit by ID', 'mycred' ); ?></label>
						<?php echo $this->include_exclude_dropdown( 'id_exclude', 'exclude-include-id', $setup['id_exclude'] ); ?>
					</div>
				</div>
				<div class="col-sm-7 col-xs-12">
					<div class="form-group">
						<label class="xs-hidden">&nbsp;</label>
						<input type="text" name="id_list" class="form-control" placeholder="<?php _e( 'Comma separated list of user IDs', 'mycred' ); ?>" value="<?php echo esc_attr( $setup['id_list'] ); ?>" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-5 col-xs-12">
					<div class="form-group">
						<label><?php _e( 'Limit by Role(s)', 'mycred' ); ?></label>
						<?php echo $this->include_exclude_dropdown( 'role_exclude', 'exclude-include-role', $setup['role_exclude'] ); ?>
					</div>
				</div>
				<div class="col-sm-7 col-xs-12">
					<div class="row">
<?php

			$editable_roles  = array_reverse( get_editable_roles() );
			foreach ( $editable_roles as $role => $details ) {

				$name = translate_user_role( $details['name'] );

				echo '<div class="col-sm-6 col-xs-4"><label for="role-list-' . esc_attr( $role ) . '"><input type="checkbox" name="role_list[]" id="role-list-' . esc_attr( $role ) . '" value="' . esc_attr( $role ) . '"';
				if ( in_array( $role, (array) $setup['role_list'] ) ) echo ' checked="checked"';
				echo ' />' . $name . '</label></div>';
			}

?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-xs-12 tr">
			<input type="hidden" name="new_id" value="<?php echo $schedule_id; ?>" />
			<input type="submit" class="button button-primary" value="<?php _e( 'Schedule', 'mycred' ); ?>" />
		</div>
	</div>
</div>
<?php

			$content = ob_get_contents();
			ob_end_clean();

			return $content;

		}

		/**
		 * Include Exclude Dropdown
		 * @since 1.7
		 * @version 1.0
		 */
		public function include_exclude_dropdown( $name = '', $id = '', $selected = '' ) {

			$options = array(
				'include' => __( 'Include:', 'mycred' ),
				'exclude' => __( 'Exclude:', 'mycred' )
			);

			$content = '<select name="' . $name . '" id="' . $id . '" class="form-control">';
			foreach ( $options as $value => $label ) {
				$content .= '<option value="' . $value . '"';
				if ( $selected == $value ) $content .= ' selected="selected"';
				$content .= '>' . $label . '</option>';
			}
			$content .= '</select>';

			return $content;

		}

		/**
		 * Preference for recurring payouts
		 * @since 1.2
		 * @version 1.2
		 */
		public function preferences() {

			$schedules = mycred_get_recurring_payout_schedules( $this->mycred_type );

?>
<p><?php _e( 'As long as this service remains disabled, none of your scheduled payouts will run!', 'mycred' ); ?></p>
<div class="row">
	<div class="col-xs-12">
		<h3><?php _e( 'Schedules', 'mycred' ); ?></h3>
		<table class="widefat fixed striped" cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th style="width: 25%;"><?php _e( 'Job Title', 'mycred' ); ?></th>
					<th style="width: 15%;"><?php _e( 'Status', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Frequency', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Last Ran', 'mycred' ); ?></th>
					<th style="width: 20%;"><?php _e( 'Next Run', 'mycred' ); ?></th>
				</tr>
			</thead>
			<tbody id="recurring-schedule-body">

				<?php echo $this->display_schedule_table(); ?>

				<tr id="no-banking-schedules"<?php if ( ! empty( $schedules ) ) echo ' style="display: none;"'; ?>>
					<td colspan="5"><?php _e( 'No schedules found.', 'mycred' ); ?></td>
				</tr>
			</tbody>
		</table>
		<input type="hidden" name="<?php echo $this->field_name( 'here' ); ?>" value="1" />
		<p style="text-align: right;"><button type="button" id="add-new-schedule" class="button button-secondary"><?php _e( 'Add New', 'mycred' ); ?></button>
	</div>
</div>
<?php

			do_action( 'mycred_banking_recurring_payouts', $this );

		}

		/**
		 * Sanitise Preferences
		 * @since 1.2
		 * @version 1.1
		 */
		function sanitise_preferences( $post ) {

			return apply_filters( 'mycred_banking_save_recurring', array(), $this );

		}

		/**
		 * Ajax Handler
		 * @since 1.7
		 * @version 1.0
		 */
		function ajax_handler() {

			parse_str( $_POST['form'], $form );

			// Add new Schedule
			if ( empty( $form ) ) {
				$new_id = strtolower( wp_generate_password( 6, false, false ) );
				wp_send_json_success( array( 'form' => $this->manage_schedule_form( $new_id, $this->default_schedule ), 'table' => false ) );
			}

			// Save new Schedule
			if ( array_key_exists( 'new_id', $form ) ) {

				$setup             = shortcode_atts( $this->default_schedule, $form );

				// Prep start time and convert it into a gmt unix timestamp
				$year              = absint( $form['last_run']['year'] );
				$month             = zeroise( absint( $form['last_run']['month'] ), 2 );
				$day               = zeroise( absint( $form['last_run']['day'] ), 2 );
				$time              = sanitize_text_field( $form['last_run']['time'] );

				$setup['last_run'] = $this->date_to_timestamp( $year . '-' . $month . '-' . $day . ' ' . $time . ':00' );

				// Attempt to add new payout schedule
				$results           = mycred_add_new_recurring_payout( $form['new_id'], $setup, $this->mycred_type );

				// Something went wrong
				if ( is_wp_error( $results ) ) {
					$content = '<div class="alert alert-warning">' . $results->get_error_message() . '</div>';
					$content .= $this->manage_schedule_form( $form['new_id'], $setup );
					wp_send_json_success( array( 'form' => $content, 'table' => false ) );
				}

				$results['id']  = $form['new_id'];
				$message        = '<div class="alert alert-success">' . __( 'Schedule Added', 'mycred' ) . '</div>';

				$table_row      = $this->display_schedule_table( $results );
				$eligeble_users = $this->get_eligible_users( 5, 0, $setup );

				// Warn user if no users are eligile for a payout based of their setup
				if ( $eligeble_users['total'] == 0 )
					$message .= '<div class="padded"><p>' . __( 'While the recurring payout has been successfully saved, based on the limits you set, right now there are no users that are eligible for a payout!', 'mycred' ) . '</p></div>';

				wp_send_json_success( array( 'form' => $message, 'table' => $table_row ) );

			}

			// View existing schedule
			elseif ( array_key_exists( 'schedule_id', $form ) ) {

				$schedule_id = sanitize_key( $form['schedule_id'] );
				$setup       = mycred_get_recurring_payout( $schedule_id, $this->mycred_type );
				if ( $setup === false ) {
					$content = '<div class="alert alert-warning">' . __( 'Schedule not found. Please refresh this page and try again.', 'mycred' ) . '</div>';
					wp_send_json_success( array( 'form' => $content, 'table' => false ) );
				}

				wp_send_json_success( array( 'form' => $this->view_schedule_form( $schedule_id, $setup ), 'table' => false ) );

			}

			// Delete existing schedule
			elseif ( array_key_exists( 'remove_token', $form ) ) {

				$schedule_id = sanitize_key( $form['remove_token'] );
				$setup       = mycred_get_recurring_payout( $schedule_id, $this->mycred_type );
				if ( $setup === false ) {
					$content = '<div class="alert alert-warning">' . __( 'Schedule not found. Please refresh this page and try again.', 'mycred' ) . '</div>';
					wp_send_json_success( array( 'form' => $content, 'table' => false ) );
				}

				mycred_delete_recurring_payout( $schedule_id, $this->mycred_type );
				$content  = '<div class="alert alert-success">' . __( 'Schedule Deleted', 'mycred' ) . '</div>';
				$content .= "<script type=\"text/javascript\">jQuery(function($) { $( 'tr#schedule-{$schedule_id}' ).remove(); });</script>";
				wp_send_json_success( array( 'form' => $content, 'table' => false ) );

			}

		}

	}
endif;
