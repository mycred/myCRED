<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED Bank Service - Schedule Deposit
 * @since 1.5.2
 * @version 1.0.1
 */
if ( ! class_exists( 'myCRED_Banking_Service_Schedule_Deposit' ) ) :
	class myCRED_Banking_Service_Schedule_Deposit extends myCRED_Service {
   
        /**
         * Construct
         */
        function __construct( $service_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

            parent::__construct( array(
                'id'       => 'schedule_deposit',
                'defaults' => array(
                    'schedule'      => 0,
                    'points'        => 0,
                    'recurring'     => 'off'
                )
            ), $service_prefs, $type );

            if ( ! empty( $service_prefs['central']['bank_id'] ) ) 
                $this->prefs['bank_id'] = absint( $service_prefs['central']['bank_id'] );

        }

        /**
         * Run
         * @since 1.5.2
         * @version 1.0
         */
        public function run() {

            if( class_exists( 'myCRED_Banking_Module' ) && class_exists( 'myCRED_Email_Notice_Module' ) ) {

                add_filter( 'mycred_email_instances', array( $this, 'mycred_email_banking_instances_func' ) );
                add_action( 'mycred_after_email_triggers', array( $this, 'mycred_after_email_banking_triggers' ) );
                add_action( 'mycred_save_email_notice', array( $this, 'mycred_save_banking_email_notice' ), 10, 2 );
                add_filter( 'mycred_add_finished', array( $this, 'mycred_notice_banking_email_check' ), 10, 3 );
            
            }
            
            add_action( 'mycred_schedule_deposit_event', array( $this, 'scheduled_event' ) );
            add_filter( 'mycred_check_schedule_deposite_entry', array( $this, 'mycred_check_schedule_deposite' ), 10, 4 );
            add_action( 'mycred_banking_settings_save', array( $this, 'mycred_save_banking_setting' ), 10, 2 );

        }

        // check if amount is deposit through schedule
        public function mycred_check_schedule_deposite( $con, $reply, $request, $mycred ) {
                
            if( $request['ref'] == 'central_schedule_amount' || $request['ref'] == 'central_recurring_schedule_amount' )
                $con = true;

            return $con;

        }

        // when will cron work and to save all setting of central deposit schedule 
        public function mycred_save_banking_setting( $post, $obj ) {

            if ( in_array( 'central', (array) $obj->active ) && ! in_array( 'central', $post['active'] ) ) {
                
                $post['active'] = array();

            }

            if( isset( $post['active'][1] ) && $post['active'][1] == 'schedule_deposit' ){
                
                if( ! wp_next_scheduled( 'mycred_schedule_deposit_event' ) ) {
                   
                    wp_schedule_event( time(), 'daily', 'mycred_schedule_deposit_event' );

                }

            }
            else {

                wp_clear_scheduled_hook( 'mycred_schedule_deposit_event' );

            }

            return $post;
            
        }

        public function scheduled_event() {   

            $prefs          = $this->prefs;
            $start_from     = isset( $prefs['start_from'] ) ? $prefs['start_from'] : '';
            $interval_days  = isset( $prefs['schedule'] ) ? absint( $prefs['schedule'] ) : 0;
            $points         = ! empty( $prefs['points'] ) ? $prefs['points'] : 0;

            if( empty( $start_from ) || empty( $interval_days ) ) return;

            $scheduled_date = date( 'Y-m-d', strtotime( "{$start_from} +{$interval_days} days" ) );
            
            // when the schedule date has arrived
            if( date( 'Y-m-d' ) >= $scheduled_date ) {
                
                $mycred = mycred( $this->mycred_type );

                $mycred->add_creds(
                    'central_schedule_amount',
                    $prefs['bank_id'],
                    $points,
                    '%plural% for Schedule amount',
                    $this->id
                );

                $settings = mycred_get_option('mycred_pref_bank');

                if( ! empty( $prefs['recurring'] ) && $prefs['recurring'] == 'on' ) {

                    $settings['service_prefs']['schedule_deposit']['start_from'] = $scheduled_date;

                }
                else {

                    $settings['active'] = array( 'central' );

                }

                mycred_update_option( 'mycred_pref_bank', $settings );

            }

        }

        /**
         * Preference for Central Bank
         * @since 1.5.2
         * @version 1.1
         */
        public function preferences() {

            $prefs = $this->prefs;
            
            if( ! empty( mycred_get_option('mycred_pref_bank')['active'] ) && in_array( 'schedule_deposit', mycred_get_option('mycred_pref_bank')['active'] ) ) {
                ?>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="row">
                            <div class="col-sm-4">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="<?php echo esc_attr( $this->field_id( 'start_from' ) ); ?>"><?php esc_html_e( 'Starting from', 'mycred' ); ?></label>
                                        
                                            <input type="date" name="<?php echo esc_attr( $this->field_name( 'start_from' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'start_from' ) ); ?>" value="<?php echo ! empty( $prefs['start_from'] ) ? esc_attr( $prefs['start_from'] ) : ''; ?>" class="mycred-input-date">
                                        </div>
                                        <p>
                                            <span class="description"><?php esc_html_e( 'Enter the start date for schedule.', 'mycred' ); ?></span>
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="<?php echo esc_attr( $this->field_id( 'schedule' ) ); ?>"><?php esc_html_e( 'Interval (in days)', 'mycred' ); ?></label>
                                            <input type="number" name="<?php echo esc_attr( $this->field_name( 'schedule' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'schedule' ) ); ?>" value="<?php echo ! empty( $prefs['schedule'] ) ? esc_attr( $prefs['schedule'] ) : ''; ?>" min="1">
                                        </div>
                                        <p>
                                            <span class="description"><?php esc_html_e( 'Deposit points after x days interval.', 'mycred' ); ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-3">
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $this->field_id( 'points' ) ); ?>"><?php esc_html_e( 'Amount', 'mycred' ); ?></label>
                                
                                    <input type="number" name="<?php echo esc_attr( $this->field_name( 'points' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'points' ) ); ?>" value="<?php echo ! empty( $prefs['points']  ) ? esc_attr( $prefs['points'] ) : ''; ?>"  min="1"> 
                                </div>
                                <p>
                                    <span class="description"><?php esc_html_e( 'The amount of points to be scheduled.', 'mycred' ); ?></span>
                                </p>
                            </div>
                            <div class="col-sm-5">
                                <div class="form-group">
                                    <label for="<?php echo esc_attr( $this->field_id( 'recurring' ) ); ?>"><?php esc_html_e( 'Recurring', 'mycred' ); ?></label>
                                    <label class="mycred-switch1">
                                        <input type="checkbox" id="<?php echo esc_attr( $this->field_id( 'recurring' ) ); ?>" name="<?php echo esc_attr( $this->field_name( 'recurring' ) ); ?>" <?php echo $prefs['recurring'] == 'on' ? 'checked' : '';?>> 
                                        <span class="slider round"></span>
                                    </label>
                                </div>
                                <label><?php esc_html_e( 'Enabling this options will deposit the amount recursively after the defined interval.', 'mycred' ); ?> </label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }

        }

        //Register email instance 
        public function mycred_email_banking_instances_func( $instances ) {

            if( class_exists( 'myCRED_Banking_Module' ) && class_exists( 'myCRED_Email_Notice_Module' ) ) {

                wp_register_script( 'mycred-central-deposit-email', plugins_url( 'assets/js/central-deposit-emails.js', myCRED_BANK ), array( 'jquery' ), myCRED_VERSION );
                wp_enqueue_script( 'mycred-central-deposit-email' );

            }

            if ( class_exists( 'myCRED_Email_Notice_Module' ) ) {
                
                $instances['central_min_balance'] = __( 'min balance for central deposit', 'mycred' );
                $instances['central_no_balance']  = __( 'no balance left for central deposit', 'mycred' );
           
                return $instances;
            }

        }

        public function mycred_after_email_banking_triggers($post) {
            
            // Get trigger
            $email         = mycred_get_email_notice( $post->ID );
            $trigger       = $email->get_trigger();

            $get_central_amount = mycred_get_post_meta( $post->ID, 'mycred_central_min_amount', true );
            $instances     = mycred_get_email_instances();
            $uses_generic  = ( $trigger == 'central_min_balance' && array_key_exists( $trigger, $instances ) ) ? true : false; ?>
                    
            <div id="areference-selection" style="display: <?php if ( $uses_generic ) echo 'block'; else echo 'none'; ?>;">
                <label for="mycred-email-ctype"><?php esc_html_e( 'Minimum Balance Left', 'mycred' ); ?></label>
                <input type="text" name="mycred_email[min_balance]" placeholder="<?php esc_html_e( '0', 'mycred' ); ?>" id="mycred-email-central-min" class="form-control" value="<?php echo ! empty( $get_central_amount ) ? esc_attr( $get_central_amount ) : ''; ?>" />
            </div><?php
                        
        }

        public function mycred_save_banking_email_notice( $post_id ) {

            if( array_key_exists( 'min_balance', $_POST['mycred_email'] ) &&
                ! empty( $_POST['mycred_email']['min_balance'] ) ) {

                $central_min_amount = floatval( $_POST['mycred_email']['min_balance'] );
                mycred_update_post_meta( $post_id, 'mycred_central_min_amount', $central_min_amount );

            }

        }

        public function deactivate() {

            wp_clear_scheduled_hook( 'mycred_schedule_deposit_event' );



        }

        public function mycred_notice_banking_email_check( $emailnotice, $request, $mycred ) {
            
            $user_bank_id = mycred_get_option('mycred_pref_bank')['service_prefs']['central']['bank_id'];
            $point_type = $mycred->get_point_type_key();
            $min_balance_emails = mycred_get_event_emails( $point_type, 'generic', 'central_min_balance' );
            $no_balance_emails  = mycred_get_event_emails( $point_type, 'generic', 'central_no_balance' );

            $emails  = array_merge( $min_balance_emails, $no_balance_emails );
            $balance = $mycred->get_users_balance( $user_bank_id );
            
            foreach ( $emails as $notice_id ) {
            
                $email         = mycred_get_email_notice( $notice_id );
                $trigger       = $email->get_trigger();
                $get_central_amount = intval( mycred_get_post_meta( $notice_id, 'mycred_central_min_amount', true ) );

                if ( $trigger == 'central_min_balance' && $balance <= $get_central_amount ) {

                    $request    = array(
                        'ref'      => 'central_min_balance',
                        'user_id'  => $user_bank_id,
                        'amount'   => $request['amount'],
                        'entry'    => 'central_min_balance',
                        'ref_id'   => $notice_id,
                        'data'     => array( 'ref_type' => 'post' ),
                        'type'     => $point_type,
                        'new'      => $balance,
                        'old'      => $balance
                    );

                    if ( mycred_user_wants_email( $user_bank_id, $notice_id ) ) {

                        mycred_send_new_email( $notice_id, $request, $point_type );
                
                    }

                }

                if ( $trigger == 'central_no_balance' && $balance <= 0 ) {

                    $request    = array(
                        'ref'      => 'central_no_balance',
                        'user_id'  => $user_bank_id,
                        'amount'   => $request['amount'],
                        'entry'    => 'central_no_balance',
                        'ref_id'   => $notice_id,
                        'data'     => array( 'ref_type' => 'post' ),
                        'type'     => $point_type,
                        'new'      => $balance,
                        'old'      => $balance
                    );
                    
                    if ( mycred_user_wants_email( $user_bank_id, $notice_id ) ) {

                        mycred_send_new_email( $notice_id, $request, $point_type );
                
                    }

                }

            }

            if ( empty( $emails ) ) return; 

            return $emailnotice;
        }

    }

endif;

                
