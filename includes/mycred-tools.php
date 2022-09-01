<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
if ( ! class_exists( 'myCRED_Tools' ) ) :
class myCRED_Tools {

	private $response = array();

	/**
	 * Construct
	 */
	public function __construct() {

		add_action( 'admin_menu', array( $this, 'tools_sub_menu' ) );

		add_action( 'wp_ajax_mycred-tools-select-user', array( $this, 'tools_select_user' ) );

		if( isset( $_GET['page'] ) && $_GET['page'] == 'mycred-tools' )
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		
	}

	public function admin_enqueue_scripts() {

		wp_enqueue_style( MYCRED_SLUG . '-admin' );

		wp_enqueue_script( MYCRED_SLUG . '-select2-script' );

		wp_enqueue_style( MYCRED_SLUG . '-select2-style' );

		wp_enqueue_script( MYCRED_SLUG . '-tools-script', plugins_url( 'assets/js/mycred-tools.js', __DIR__ ), 'jquery', myCRED_VERSION, true );

		wp_enqueue_style( MYCRED_SLUG . '-buttons' );

		wp_localize_script( 
			MYCRED_SLUG . '-tools-script',
			'mycredTools',
			array(
				'ajax_url' 			   =>	admin_url( 'admin-ajax.php' ),
				'token'                =>	wp_create_nonce( 'mycred-tools' ),
				'awardConfirmText'     =>	__( 'Do you really want to bulk award?', 'mycred' ),
				'revokeConfirmText'    =>	__( 'Do you really want to bulk deduct?', 'mycred' ),
				'successfullyAwarded'  =>	__( 'Successfully Awarded.', 'mycred' ),
				'successfullyDeducted' =>	__( 'Successfully Deducted.', 'mycred' ),
				'pointsRequired'	   =>	__( 'Points field is required.', 'mycred' ),
				'logEntryRequired'	   =>	__( 'Log Entry is requried.', 'mycred' ),
				'revokeConfirmText'	   =>	__( 'Do you really want to bulk revoke?', 'mycred' ),
				'successfullyRevoked'  =>	__( 'Successfully Revoked.', 'mycred' ),
				'userOrRoleIsRequired' =>	__( 'Username or Role field required.', 'mycred' ),
				'tryLater'	           =>	__( 'Something went wrong try later.', 'mycred' ),
				'selectPointType'	   =>	__( 'Please select point type.', 'mycred' ),
				'accessDenied'	       =>	__( 'Access Denied', 'mycred' ),
				'selectUser'	       =>	__( 'Please select atleast one user.', 'mycred' ),
				'selectRank'	       =>	__( 'Please select rank.', 'mycred' ),
				'badgesFieldRequried'  =>  __( 'Please select atleast one badge.', 'mycred' ),
			)
		);
		
	}

	/**
	 * Register tools menu
	 * 
	 * @since 2.4.4.1 `$capability` check added
	 */
	public function tools_sub_menu() {

		$mycred     = new myCRED_Settings();
		$capability = $mycred->get_point_admin_capability();

		mycred_add_main_submenu( 
			'Tools', 
			'Tools', 
			$capability, 
			'mycred-tools',
			array( $this, 'tools_page' ),
			2
		);

	}

	/**
	 * Tools menu callback
	 * @since 2.3
	 * @since 2.4 Import Export Module Added
	 * @version 1.1
	 */
	public function tools_page() { 
		
		$import_export = get_mycred_tools_page_url('points');
		$logs_cleanup = get_mycred_tools_page_url('logs-cleanup');
		$reset_data = get_mycred_tools_page_url('reset-data');
		$pages = array( 
			'import-export',
			'points', 
			'badges', 
			'ranks',
			'setup'
		);
		?>

		<div class="" id="myCRED-wrap">
			<div class="mycredd-tools">
				<h1>Tools</h1>
			</div>
			<div class="clear"></div>
			<div class="mycred-tools-main-nav">
				<h2 class="nav-tab-wrapper">
					<a href="<?php echo esc_url( admin_url('admin.php?page=mycred-tools') ) ?>" class="nav-tab <?php echo !isset( $_GET['mycred-tools'] ) ? 'nav-tab-active' : ''; ?>">Bulk Assign</a>
					<a href="<?php echo esc_url( $import_export ) ?>" class="nav-tab <?php echo ( isset( $_GET['mycred-tools'] ) && in_array( $_GET['mycred-tools'], $pages ) ) ? 'nav-tab-active' : ''; ?>">Import/Export</a>
					<!-- <a href="<?php //echo $logs_cleanup ?>" class="nav-tab <?php //echo ( isset( $_GET['mycred-tools'] ) && $_GET['mycred-tools'] == 'logs-cleanup' ) ? 'nav-tab-active' : ''; ?>">Logs Cleanup</a>
					<a href="<?php //echo $reset_data ?>" class="nav-tab <?php //echo ( isset( $_GET['mycred-tools'] ) && $_GET['mycred-tools'] == 'reset-data' ) ? 'nav-tab-active' : ''; ?>">Reset Data</a> -->
				</h2>
			</div>
		
		<?php

		if ( isset( $_GET['mycred-tools'] ) ) {

			if ( in_array( $_GET['mycred-tools'], $pages ) )
			{ 
				$mycred_tools_import_export = new myCRED_Tools_Import_Export();

				$mycred_tools_import_export->get_header();
			}
		}

		if ( isset( $_GET['mycred-tools'] ) ) {
			if ( $_GET['mycred-tools'] == 'logs-cleanup' ) { ?>
				<h1>LOGS-CLEANUP</h1>
				<?php
			}
		}

		if ( isset( $_GET['mycred-tools'] ) ) 
		{
			if ( $_GET['mycred-tools'] == 'reset-data' ) { ?>
				<h1>RESET-DATA</h1>
				<?php
			}
		}
		else
		{

			$mycred_tools_bulk_assign = new myCRED_Tools_Bulk_Assign();

			$mycred_tools_bulk_assign->get_page();

		}

		?>
		</div>
		<?php
	}

	public function get_all_users()
	{
		$users = array();

		$wp_users = get_users();

		foreach( $wp_users as $user )
            $users[$user->user_email] = $user->display_name;

		return $users;
	}

	public function get_users_by_email( $emails )
	{
		$ids = array();

		foreach( $emails as $email )
			$ids[] = get_user_by( 'email', $email )->ID;

		return $ids;
	}

	public function get_users_by_role( $roles )
	{
		$user_ids = array();

		foreach( $roles as $role )
		{
			$args = array(
				'role'	=>	$role
			);

			$user_query = new WP_User_Query( $args );

			if ( ! empty( $user_query->get_results() ) ) 
			{
				foreach ( $user_query->get_results() as $user ) 
					$user_ids[] = $user->ID;
			}
		}

		return $user_ids;
	}

	public function tools_assign_award() {

		check_ajax_referer( 'mycred-tools', 'token' );

		$this->response = array( 'success' => 'tryLater' );

		if( isset( $_REQUEST['selected_type'] ) ) {

			$selected_type = sanitize_key( $_REQUEST['selected_type'] );

			switch ( $selected_type ) {
				case 'points':
					$this->process_points();
					break;
				case 'ranks':
					$this->process_ranks();
					break;
				case 'badges':
					$this->process_badges();
					break;
				default:
					break;
			}

		}

		wp_send_json( $this->response );
		wp_die();

	}

	private function process_points() {

		if ( ! isset( $_REQUEST['point_type'] ) ) {

			$this->response = array( 'success' => 'selectPointType' );
			return;
		
		}
		
		$point_type      = sanitize_key( $_REQUEST['point_type'] );
		$current_user_id = get_current_user_id();
		$mycred          = mycred( $point_type );

		if ( ! $mycred->user_is_point_admin( $current_user_id ) ) {

			$this->response = array( 'success' => 'accessDenied' );
			return;

		}

		if ( empty( $_REQUEST['points_to_award'] ) ) {

			$this->response = array( 'success' => 'pointsRequired' );
			return;
		
		}

		$points_to_award = sanitize_text_field( wp_unslash( $_REQUEST['points_to_award'] ) );

		$log_entry = isset( $_REQUEST['log_entry'] ) ? ( sanitize_key( $_REQUEST['log_entry'] ) == 'true' ? true : false ) : false;

		$users_to_award = $this->get_requested_users();

		if ( empty( $users_to_award ) ) return;

		foreach ( $users_to_award  as $user_id ) {

			if ( $mycred->exclude_user( $user_id ) ) continue;

			//Entries with log
			if( $log_entry ) {

				$log_entry_text = isset( $_REQUEST['log_entry_text'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['log_entry_text'] ) ) : '';

				if( empty( $log_entry_text ) ) {

					$this->response = array( 'success' => 'logEntryRequired' );
					return;

				}

				$mycred->add_creds(
					'bulk_assign',
					$user_id,
					$points_to_award,
					$log_entry_text
				);

			}
			else {

				$new_balance = $mycred->update_users_balance( $user_id, $points_to_award, $point_type );
			
			}

		}

		$this->response = array( 'success' => true );

	}

	private function process_ranks() {

		if( class_exists( 'myCRED_Ranks_Module' ) && mycred_manual_ranks() ) {

			if ( empty( $_REQUEST['rank_to_award'] ) ) {

				$this->response = array( 'success' => 'selectRank' );
				return;
			
			}

			$rank_id         = intval( $_REQUEST['rank_to_award'] );
			$point_type      = mycred_get_rank_pt( $rank_id );
			$current_user_id = get_current_user_id();
			$mycred          = mycred( $point_type );

			if ( ! $mycred->user_is_point_admin( $current_user_id ) ) {

				$this->response = array( 'success' => 'accessDenied' );
				return;

			}

			$users_to_award = $this->get_requested_users();

			if ( empty( $users_to_award ) ) return;

			foreach ( $users_to_award  as $user_id ) {

				if ( $mycred->exclude_user( $user_id ) ) continue;

				mycred_save_users_rank( $user_id, $rank_id, $point_type );

			}

			$this->response = array( 'success' => true );

		}

	}

	private function process_badges() {

		$current_user_id = get_current_user_id();
		$mycred          = mycred();
		$is_revoke       = ( isset( $_REQUEST['revoke'] ) && $_REQUEST['revoke'] == 'revoke' );

		if ( ! $mycred->user_is_point_admin( $current_user_id ) ) {

			$this->response = array( 'success' => 'accessDenied' );
			return;

		}
		
		if ( $is_revoke )
			$selected_badges = isset( $_REQUEST['badges_to_revoke'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['badges_to_revoke'] ) ) : '';
		else
			$selected_badges = isset( $_REQUEST['badges_to_award'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['badges_to_award'] ) ) : '';

		$selected_badges = json_decode( stripslashes( $selected_badges ) );

		if( empty( $selected_badges ) ) {

			$this->response = array( 'success' => 'badgesFieldRequried' );
			return;

		}

		$selected_users = $this->get_requested_users();

		if ( empty( $selected_users ) ) return;

		foreach( $selected_badges as $badge_id ) {

			foreach( $selected_users as $user_id ) {

				if ( $mycred->exclude_user( $user_id ) ) continue;

				if ( $is_revoke ) {
					
					$badge = mycred_get_badge( (int) $badge_id );
        			$badge->divest( $user_id );

				}
				else {

					mycred_assign_badge_to_user( $user_id, (int) $badge_id );

				}

			}

		}

		$this->response = array( 'success' => true );

	}

	private function get_requested_users() {
		
		$users_to_award = array();

		if ( isset( $_REQUEST['award_to_all_users'] ) ) {
			
			$award_to_all_users = sanitize_key( $_REQUEST['award_to_all_users'] ) == 'true' ? true : false;

			if ( $award_to_all_users ) {
				
				$users = $this->get_all_users();

				foreach( $users as $email => $user_name ) {
					$users_to_award[] = $email;
				}

				$users_to_award = $this->get_users_by_email( $users_to_award );

			}
			else {

				$selected_users      = isset( $_REQUEST['users'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['users'] ) ) : '[]';
				$selected_user_roles = isset( $_REQUEST['user_roles'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['user_roles'] ) ) : '[]';

				$selected_users      = json_decode( stripslashes( $selected_users ) );
				$selected_user_roles = json_decode( stripslashes( $selected_user_roles ) );

				$users_to_award      = $this->get_users_by_email( $selected_users );

				if( ! empty( $selected_user_roles ) ) {

					$users_by_role = $this->get_users_by_role( $selected_user_roles );
					$users_to_award = array_merge( $users_by_role, $users_to_award );
					$users_to_award = array_unique( $users_to_award );
				
				}

			}

		}

		if ( empty( $users_to_award ) ) 
			$this->response = array( 'success' => 'selectUser' );

		return $users_to_award;

	}

	/**
	 * Ajax Call-back
	 * @since 2.4.1
	 * @since 2.4.4.1 `current_user_can` security added
	 * @version 1.0
	 */
	public function tools_select_user()
	{

		check_ajax_referer( 'mycred-tools', 'token' );

		$mycred = new myCRED_Settings();
		$capability = $mycred->get_point_admin_capability();

		if( !current_user_can( $capability ) ) {
			die( '-1' );
		}
		
		if( isset( $_GET['action'] ) &&  $_GET['action'] == 'mycred-tools-select-user' )
		{
			$search = isset($_GET['search'] ) ? sanitize_key( $_GET['search'] ) : '';

			$results = mycred_get_users_by_name_email( $search, 'user_email' );

			echo json_encode( $results );

			die;
		}
	}
}
endif;

$mycred_tools = new myCRED_Tools();

if ( ! function_exists( 'get_mycred_tools_page_url' ) ) :
	function get_mycred_tools_page_url( $urls ) {
		
		$args = array(
			'page'         => MYCRED_SLUG . '-tools',
			'mycred-tools' =>  $urls,
		);

		return esc_url( add_query_arg( $args, admin_url( 'admin.php' ) ) );

	}
endif;