<?php

/**
 * BuddyPress Links Hook
 * @since 0.1
 * @version 1.0
 */
if ( defined( 'myCRED_VERSION' ) ) {

	/**
	 * Register Hook
	 * @since 0.1
	 * @version 1.0
	 */
	add_filter( 'mycred_setup_hooks', 'BuddyPress_Links_myCRED_Hook' );
	function BuddyPress_Links_myCRED_Hook( $installed ) {

		$installed['hook_bp_links'] = array(
			'title'       => __( 'BuddyPress: Links', 'mycred' ),
			'description' => __( 'Awards %_plural% for link related actions.', 'mycred' ),
			'callback'    => array( 'myCRED_BuddyPress_Links' )
		);

		return $installed;
	}

	/**
	 * myCRED_BuddyPress_Links class
	 * Creds for new links, voting on links, updating links and deleting links
	 * @since 0.1
	 * @version 1.1
	 */
	if ( ! class_exists( 'myCRED_BuddyPress_Links' ) && class_exists( 'myCRED_Hook' ) ) {
		class myCRED_BuddyPress_Links extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = 'mycred_default' ) {
				parent::__construct( array(
					'id'       => 'hook_bp_links',
					'defaults' => array(
						'new_link'       => array(
							'creds'          => 1,
							'log'            => '%plural% for new Link'
						),
						'vote_link'      => array(
							'creds'          => 1,
							'log'            => '%plural% for voting on a link'
						),
						'vote_link_up'   => array(
							'creds'          => 1,
							'log'            => '%plural% for your link voted up'
						),
						'vote_link_down' => array(
							'creds'          => 1,
							'log'            => '%plural% for your link voted down'
						),
						'update_link'    => array(
							'creds'          => 1,
							'log'            => '%plural% for updating link'
						),
						'delete_link'    => array(
							'creds'          => '-1',
							'log'            => '%singular% deduction for deleting a link'
						),
					)
				), $hook_prefs, $type );
			}

			/**
			 * Run
			 * @since 0.1
			 * @version 1.1
			 */
			public function run() {
				if ( $this->prefs['new_link']['creds'] != 0 )
					add_action( 'bp_links_create_complete',   array( $this, 'create_link' ) );

				add_action( 'bp_links_cast_vote_success', array( $this, 'vote_link' ) );

				if ( $this->prefs['update_link']['creds'] != 0 )
					add_action( 'bp_links_posted_update',     array( $this, 'update_link' ), 20, 4 );

				if ( $this->prefs['delete_link']['creds'] != 0 )
					add_action( 'bp_links_delete_link',       array( $this, 'delete_link' ) );
			}

			/**
			 * New Link
			 * @since 0.1
			 * @version 1.0
			 */
			public function create_link( $link_id ) {
				global $bp;

				// Check if user is excluded
				if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'new_link', $link_id, $bp->loggedin_user->id ) ) return;

				// Execute
				$this->core->add_creds(
					'new_link',
					$bp->loggedin_user->id,
					$this->prefs['new_link']['creds'],
					$this->prefs['new_link']['log'],
					$link_id,
					'bp_links',
					$this->mycred_type
				);
			}

			/**
			 * Vote on Link
			 * @since 0.1
			 * @version 1.1
			 */
			public function vote_link( $link_id ) {
				global $bp;

				// Check if user is excluded
				if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'link_voting', $link_id, $bp->loggedin_user->id ) ) return;

				// Get the vote
				$vote = '';
				if ( isset( $_REQUEST['up_or_down'] ) )
					$vote = substr( $_REQUEST['up_or_down'], 0, 4 );

				// First if we award points for voting, do so now
				if ( $this->prefs['vote_link']['creds'] != 0 ) {
					// Execute
					$this->core->add_creds(
						'link_voting',
						$bp->loggedin_user->id,
						$this->prefs['vote_link']['creds'],
						$this->prefs['vote_link']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);
				}

				// Get link author
				if ( isset( $bp->links->current_link->user_id ) )
					$author = $bp->links->current_link->user_id;

				// Link author not found
				else
					return;

				// By default we do not allow votes on our own links
				if ( $author == $bp->loggedin_user->id && apply_filters( 'mycred_bp_link_self_vote', false ) === false ) return;

				// Up Vote
				if ( $vote == 'up' && $this->prefs['vote_link_up']['creds'] != 0 ) {
					// Execute
					$this->core->add_creds(
						'link_voting',
						$author,
						$this->prefs['vote_link_up']['creds'],
						$this->prefs['vote_link_up']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);
				}

				// Down Vote
				elseif ( $vote == 'down' && $this->prefs['vote_link_down']['creds'] != 0 ) {
					// Execute
					$this->core->add_creds(
						'link_voting',
						$author,
						$this->prefs['vote_link_down']['creds'],
						$this->prefs['vote_link_down']['log'],
						$link_id,
						'bp_links',
						$this->mycred_type
					);
				}
			}

			/**
			 * Update Link
			 * @since 0.1
			 * @version 1.0
			 */
			public function update_link( $content, $user_id, $link_id, $activity_id ) {
				// Check if user is excluded
				if ( $this->core->exclude_user( $user_id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'update_link', $activity_id, $user_id ) ) return;

				// Execute
				$this->core->add_creds(
					'update_link',
					$user_id,
					$this->prefs['update_link']['creds'],
					$this->prefs['update_link']['log'],
					$activity_id,
					'bp_links',
					$this->mycred_type
				);
			}

			/**
			 * Delete Link
			 * @since 0.1
			 * @version 1.0
			 */
			public function delete_link( $link_id ) {
				global $bp;

				// Check if user is excluded
				if ( $this->core->exclude_user( $bp->loggedin_user->id ) ) return;

				// Make sure this is unique event
				if ( $this->core->has_entry( 'link_deletion', $link_id, $bp->loggedin_user->id ) ) return;

				// Execute
				$this->core->add_creds(
					'link_deletion',
					$bp->loggedin_user->id,
					$this->prefs['delete_link']['creds'],
					$this->prefs['delete_link']['log'],
					$link_id,
					'bp_links',
					$this->mycred_type
				);
			}

			/**
			 * Preferences
			 * @since 0.1
			 * @version 1.1
			 */
			public function preferences() {
				$prefs = $this->prefs; ?>

<!-- Creds for New Link -->
<label for="<?php echo $this->field_id( array( 'new_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for New Links', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'new_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['new_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['new_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Vote Link -->
<label for="<?php echo $this->field_id( array( 'vote_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Vote on Link', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['vote_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['vote_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<label for="<?php echo $this->field_id( array( 'vote_link_up', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% per received Vote', 'mycred' ) ); ?></label>
<ol>
	<li>
		<label><?php _e( 'Vote Up', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_up', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_up', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['vote_link_up']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link_up', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_up', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_up', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['vote_link_up']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label><?php _e( 'Vote Down', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_down', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_down', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['vote_link_down']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'vote_link_down', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'vote_link_down', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'vote_link_down', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['vote_link_down']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Update Link -->
<label for="<?php echo $this->field_id( array( 'update_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Updating Links', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'update_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'update_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['update_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'update_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'update_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'update_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['update_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Deleting Links -->
<label for="<?php echo $this->field_id( array( 'delete_link', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Deleting Links', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_link', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_link', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['delete_link']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_link', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_link', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_link', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['delete_link']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<?php
			}
		}
	}

}

?>