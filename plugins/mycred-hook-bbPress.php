<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 0.1
 * @version 1.0.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_bbpress_hook', 20 );
function mycred_register_bbpress_hook( $installed ) {

	if ( ! class_exists( 'bbPress' ) ) return $installed;

	$installed['hook_bbpress'] = array(
		'title'       => __( 'bbPress' ),
		'description' => __( 'Awards %_plural% for bbPress actions.', 'mycred' ),
		'callback'    => array( 'myCRED_bbPress' )
	);

	return $installed;

}

/**
 * bbPress Hook
 * @since 0.1
 * @version 1.4.2
 */
add_action( 'mycred_load_hooks', 'mycred_load_bbpress_hook', 20 );
function mycred_load_bbpress_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_bbPress' ) || ! class_exists( 'bbPress' ) ) return;

	class myCRED_bbPress extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'hook_bbpress',
				'defaults' => array(
					'new_forum' => array(
						'creds'    => 1,
						'log'      => '%plural% for new forum',
						'limit'    => '0/x'
					),
					'delete_forum' => array(
						'creds'    => 0,
						'log'      => '%singular% deduction for deleted forum'
					),
					'new_topic' => array(
						'creds'    => 1,
						'log'      => '%plural% for new forum topic',
						'author'   => 0,
						'limit'    => '0/x'
					),
					'delete_topic' => array(
						'creds'    => 0,
						'log'      => '%singular% deduction for deleted topic'
					),
					'fav_topic' => array(
						'creds'    => 1,
						'log'      => '%plural% for someone favorited your forum topic',
						'limit'    => '0/x'
					),
					'new_reply' => array(
						'creds'    => 1,
						'log'      => '%plural% for new forum reply',
						'author'   => 0,
						'limit'    => '0/x'
					),
					'delete_reply' => array(
						'creds'    => 0,
						'log'      => '%singular% deduction for deleted reply'
					),
					'show_points_in_reply'   => 0,
					'show_points_in_profile' => 0
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function run() {

			add_filter( 'mycred_post_type_excludes', array( $this, 'exclude_post_type' ) );

			// Insert Points balance in profile
			if ( isset( $this->prefs['show_points_in_reply'] ) && $this->prefs['show_points_in_reply'] == 1 )
				add_action( 'bbp_theme_after_reply_author_details', array( $this, 'insert_balance_reply' ) );

			if ( isset( $this->prefs['show_points_in_profile'] ) && $this->prefs['show_points_in_profile'] == 1 )
				add_action( 'bbp_template_after_user_profile', array( $this, 'insert_balance_profile' ) );

			// New Forum
			if ( $this->prefs['new_forum']['creds'] != 0 )
				add_action( 'bbp_new_forum',    array( $this, 'new_forum' ), 20 );

			// Delete Forum
			if ( $this->prefs['delete_forum']['creds'] != 0 )
				add_action( 'bbp_delete_forum', array( $this, 'delete_forum' ) );

			// New Topic
			if ( $this->prefs['new_topic']['creds'] != 0 )
				add_action( 'bbp_new_topic',    array( $this, 'new_topic' ), 20, 4 );

			// Delete Topic
			if ( $this->prefs['delete_topic']['creds'] != 0 )
				add_action( 'bbp_delete_topic', array( $this, 'delete_topic' ) );

			// Fave Topic
			if ( $this->prefs['fav_topic']['creds'] != 0 )
				add_action( 'bbp_add_user_favorite', array( $this, 'fav_topic' ), 10, 2 );

			// New Reply
			if ( $this->prefs['new_reply']['creds'] != 0 )
				add_action( 'bbp_new_reply',    array( $this, 'new_reply' ), 20, 5 );

			// Delete Reply
			if ( $this->prefs['delete_reply']['creds'] != 0 )
				add_action( 'bbp_delete_reply', array( $this, 'delete_reply' ) );

		}

		/**
		 * Exclude bbPress Post Types
		 * @since 0.1
		 * @version 1.0
		 */
		public function exclude_post_type( $excludes ) {

			$excludes[] = bbp_get_forum_post_type();
			$excludes[] = bbp_get_topic_post_type();
			$excludes[] = bbp_get_reply_post_type();

			return $excludes;

		}

		/**
		 * Insert Balance in bbPress Profiles
		 * @since 1.1.1
		 * @version 1.2
		 */
		public function insert_balance_profile() {

			$user_id = bbp_get_displayed_user_id();

			if ( $this->core->exclude_user( $user_id ) || $user_id == 0 ) return;

			$balance = $this->core->get_users_balance( $user_id, $this->mycred_type );
			$layout  = $this->core->plural() . ': ' . $this->core->format_creds( $balance );

			echo apply_filters( 'mycred_bbp_profile_balance', '<div class="users-mycred-balance">' . $layout . '</div>', $layout, $user_id, $this );

		}

		/**
		 * Insert Balance
		 * @since 0.1
		 * @version 1.2.1
		 */
		public function insert_balance_reply() {

			$reply_id = bbp_get_reply_id();

			// Skip Anonymous replies
			if ( bbp_is_reply_anonymous( $reply_id ) ) return;

			// Get reply author
			$user_id = bbp_get_reply_author_id( $reply_id );

			// Check for exclusions and guests
			if ( $this->core->exclude_user( $user_id ) || $user_id == 0 ) return;

			$balance = $this->core->get_users_balance( $user_id, $this->mycred_type );
			$layout  = $this->core->plural() . ': ' . $this->core->format_creds( $balance );

			echo apply_filters( 'mycred_bbp_reply_balance', '<div class="users-mycred-balance">' . $layout . '</div>', $layout, $user_id, $this );

		}

		/**
		 * New Forum
		 * @since 1.1.1
		 * @version 1.2
		 */
		public function new_forum( $forum ) {

			// Forum id
			$forum_id = $forum['forum_id'];

			// Forum author
			$forum_author = $forum['forum_author'];

			// Check if user is excluded
			if ( $this->core->exclude_user( $forum_author ) ) return;

			// Limit
			if ( $this->over_hook_limit( 'new_forum', 'new_forum', $forum_author ) ) return;

			// Make sure this is unique event
			if ( $this->has_entry( 'new_forum', $forum_id, $forum_author ) ) return;

			// Execute
			$this->core->add_creds(
				'new_forum',
				$forum_author,
				$this->prefs['new_forum']['creds'],
				$this->prefs['new_forum']['log'],
				$forum_id,
				array( 'ref_type' => 'post' ),
				$this->mycred_type
			);

		}

		/**
		 * Delete Forum
		 * @since 1.2
		 * @version 1.1
		 */
		public function delete_forum( $forum_id ) {

			// Get Author
			$forum_author = bbp_get_forum_author_id( $forum_id );

			// If gained, points, deduct
			if ( $this->has_entry( 'new_forum', $forum_id, $forum_author ) ) {

				// Execute
				$this->core->add_creds(
					'deleted_forum',
					$forum_author,
					$this->prefs['delete_forum']['creds'],
					$this->prefs['delete_forum']['log'],
					$forum_id,
					'',
					$this->mycred_type
				);

			}

		}

		/**
		 * New Topic
		 * @since 0.1
		 * @version 1.2
		 */
		public function new_topic( $topic_id, $forum_id, $anonymous_data, $topic_author ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $topic_author ) ) return;

			// Check if forum author is allowed to get points for their own topics
			if ( (bool) $this->prefs['new_topic']['author'] == false ) {
				if ( bbp_get_forum_author_id( $forum_id ) == $topic_author ) return;
			}

			// Limit
			if ( $this->over_hook_limit( 'new_topic', 'new_forum_topic', $topic_author ) ) return;

			// Make sure this is unique event
			if ( $this->has_entry( 'new_forum_topic', $topic_id, $topic_author ) ) return;

			// Execute
			$this->core->add_creds(
				'new_forum_topic',
				$topic_author,
				$this->prefs['new_topic']['creds'],
				$this->prefs['new_topic']['log'],
				$topic_id,
				array( 'ref_type' => 'post' ),
				$this->mycred_type
			);

		}

		/**
		 * Delete Topic
		 * @since 1.2
		 * @version 1.1
		 */
		public function delete_topic( $topic_id ) {

			// Get Author
			$topic_author = bbp_get_topic_author_id( $topic_id );

			// If gained, points, deduct
			if ( $this->has_entry( 'new_forum_topic', $topic_id, $topic_author ) ) {

				// Execute
				$this->core->add_creds(
					'deleted_topic',
					$topic_author,
					$this->prefs['delete_topic']['creds'],
					$this->prefs['delete_topic']['log'],
					$topic_id,
					'',
					$this->mycred_type
				);

			}

		}

		/**
		 * Topic Added to Favorites
		 * @by Fee (http://wordpress.org/support/profile/wdfee)
		 * @since 1.1.1
		 * @version 1.5
		 */
		public function fav_topic( $user_id, $topic_id ) {

			// $user_id is loggedin_user, not author, so get topic author
			$topic_author = get_post_field( 'post_author', $topic_id );

			// Check if user is excluded (required)
			if ( $this->core->exclude_user( $topic_author ) || $topic_author == $user_id ) return;

			// Limit
			if ( $this->over_hook_limit( 'fav_topic', 'topic_favorited', $topic_author ) ) return;

			// Make sure this is a unique event (favorite not from same user)
			$data = array( 'ref_user' => $user_id, 'ref_type' => 'post' );
			if ( $this->has_entry( 'topic_favorited', $topic_id, $topic_author, $data ) ) return;

			// Execute
			$this->core->add_creds(
				'topic_favorited',
				$topic_author,
				$this->prefs['fav_topic']['creds'],
				$this->prefs['fav_topic']['log'],
				$topic_id,
				$data,
				$this->mycred_type
			);

		}

		/**
		 * New Reply
		 * @since 0.1
		 * @version 1.5
		 */
		public function new_reply( $reply_id, $topic_id, $forum_id, $anonymous_data, $reply_author ) {

			// Check if user is excluded
			if ( $this->core->exclude_user( $reply_author ) ) return;

			// Check if topic author gets points for their own replies
			if ( (bool) $this->prefs['new_reply']['author'] === false && bbp_get_topic_author_id( $topic_id ) == $reply_author ) return;

			// Limit
			if ( $this->over_hook_limit( 'new_reply', 'new_forum_reply', $reply_author ) ) return;

			// Make sure this is unique event
			if ( $this->has_entry( 'new_forum_reply', $reply_id, $reply_author ) ) return;

			// Execute
			$this->core->add_creds(
				'new_forum_reply',
				$reply_author,
				$this->prefs['new_reply']['creds'],
				$this->prefs['new_reply']['log'],
				$reply_id,
				array( 'ref_type' => 'post' ),
				$this->mycred_type
			);

		}

		/**
		 * Delete Reply
		 * @since 1.2
		 * @version 1.2.1
		 */
		public function delete_reply( $reply_id ) {

			// Get Author
			$reply_author = bbp_get_reply_author_id( $reply_id );

			// If gained, points, deduct
			if ( $this->has_entry( 'new_forum_reply', $reply_id, $reply_author ) ) {

				// Execute
				$this->core->add_creds(
					'deleted_reply',
					$reply_author,
					$this->prefs['delete_reply']['creds'],
					$this->prefs['delete_reply']['log'],
					$reply_id,
					'',
					$this->mycred_type
				);

			}

		}

		/**
		 * Preferences
		 * @since 0.1
		 * @version 1.2
		 */
		public function preferences() {

			$prefs = $this->prefs;

			if ( ! isset( $prefs['new_forum']['limit'] ) )
				$prefs['new_forum']['limit'] = '0/x';

			if ( ! isset( $prefs['new_topic']['limit'] ) )
				$prefs['new_topic']['limit'] = '0/x';

			if ( ! isset( $prefs['fav_topic']['limit'] ) )
				$prefs['fav_topic']['limit'] = '0/x';

			if ( ! isset( $prefs['new_reply']['limit'] ) )
				$prefs['new_reply']['limit'] = '0/x';

?>
<!-- Creds for New Forums -->
<label for="<?php echo $this->field_id( array( 'new_forum', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for New Forum', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_forum', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'new_forum', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['new_forum']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_forum', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_forum', 'limit' ) ), $this->field_id( array( 'new_forum', 'limit' ) ), $prefs['new_forum']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_forum', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_forum', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_forum', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['new_forum']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Deleting Forums -->
<label for="<?php echo $this->field_id( array( 'delete_forum', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Forum Deletion', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_forum', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_forum', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['delete_forum']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_forum', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_forum', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_forum', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['delete_forum']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for New Topic -->
<label for="<?php echo $this->field_id( array( 'new_topic', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for New Topic', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_topic', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'new_topic', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['new_topic']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_topic', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_topic', 'limit' ) ), $this->field_id( array( 'new_topic', 'limit' ) ), $prefs['new_topic']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_topic', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_topic', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_topic', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['new_topic']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<input type="checkbox" name="<?php echo $this->field_name( array( 'new_topic' => 'author' ) ); ?>" id="<?php echo $this->field_id( array( 'new_topic' => 'author' ) ); ?>" <?php checked( $prefs['new_topic']['author'], 1 ); ?> value="1" />
		<label for="<?php echo $this->field_id( array( 'new_topic' => 'author' ) ); ?>"><?php echo $this->core->template_tags_general( __( 'Forum authors can receive %_plural% for creating new topics.', 'mycred' ) ); ?></label>
	</li>
</ol>
<!-- Creds for Deleting Topic -->
<label for="<?php echo $this->field_id( array( 'delete_topic', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Topic Deletion', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_topic', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_topic', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['delete_topic']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_topic', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_topic', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_topic', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['delete_topic']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<!-- Creds for Faved Topic -->
<label for="<?php echo $this->field_id( array( 'fav_topic', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Favorited Topic', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'fav_topic', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'fav_topic', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['fav_topic']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'fav_topic', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'fav_topic', 'limit' ) ), $this->field_id( array( 'fav_topic', 'limit' ) ), $prefs['fav_topic']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'fav_topic', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'fav_topic', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'fav_topic', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['fav_topic']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<!-- Creds for New Reply -->
<label for="<?php echo $this->field_id( array( 'new_reply', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for New Reply', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_reply', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'new_reply', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['new_reply']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_reply', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'new_reply', 'limit' ) ), $this->field_id( array( 'new_reply', 'limit' ) ), $prefs['new_reply']['limit'] ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'new_reply', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'new_reply', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'new_reply', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['new_reply']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<input type="checkbox" name="<?php echo $this->field_name( array( 'new_reply' => 'author' ) ); ?>" id="<?php echo $this->field_id( array( 'new_reply' => 'author' ) ); ?>" <?php checked( $prefs['new_reply']['author'], 1 ); ?> value="1" />
		<label for="<?php echo $this->field_id( array( 'new_reply' => 'author' ) ); ?>"><?php echo $this->core->template_tags_general( __( 'Topic authors can receive %_plural% for replying to their own Topic', 'mycred' ) ); ?></label>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<input type="checkbox" name="<?php echo $this->field_name( 'show_points_in_reply' ); ?>" id="<?php echo $this->field_id( 'show_points_in_reply' ); ?>" <?php checked( $prefs['show_points_in_reply'], 1 ); ?> value="1" /> <label for="<?php echo $this->field_id( 'show_points_in_reply' ); ?>"><?php echo $this->core->template_tags_general( __( 'Show users %_plural% balance in replies', 'mycred' ) ); ?>.</label>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<input type="checkbox" name="<?php echo $this->field_name( 'show_points_in_profile' ); ?>" id="<?php echo $this->field_id( 'show_points_in_profile' ); ?>" <?php checked( $prefs['show_points_in_profile'], 1 ); ?> value="1" /> <label for="<?php echo $this->field_id( 'show_points_in_profile' ); ?>"><?php echo $this->core->template_tags_general( __( 'Show users %_plural% balance in their bbPress profiles.', 'mycred' ) ); ?>.</label>
	</li>
</ol>
<!-- Creds for Deleting Reply -->
<label for="<?php echo $this->field_id( array( 'delete_reply', 'creds' ) ); ?>" class="subheader"><?php echo $this->core->template_tags_general( __( '%plural% for Reply Deletion', 'mycred' ) ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_reply', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_reply', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['delete_reply']['creds'] ); ?>" size="8" /></div>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'delete_reply', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'delete_reply', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'delete_reply', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['delete_reply']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<?php

		}

		/**
		 * Sanitise Preference
		 * @since 1.1.1
		 * @version 1.1
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['new_forum']['limit'] ) && isset( $data['new_forum']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_forum']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_forum']['limit'] = $limit . '/' . $data['new_forum']['limit_by'];
				unset( $data['new_forum']['limit_by'] );
			}

			if ( isset( $data['new_topic']['limit'] ) && isset( $data['new_topic']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_topic']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_topic']['limit'] = $limit . '/' . $data['new_topic']['limit_by'];
				unset( $data['new_topic']['limit_by'] );
			}

			if ( isset( $data['fav_topic']['limit'] ) && isset( $data['fav_topic']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['fav_topic']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['fav_topic']['limit'] = $limit . '/' . $data['fav_topic']['limit_by'];
				unset( $data['fav_topic']['limit_by'] );
			}

			if ( isset( $data['new_reply']['limit'] ) && isset( $data['new_reply']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['new_reply']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['new_reply']['limit'] = $limit . '/' . $data['new_reply']['limit_by'];
				unset( $data['new_reply']['limit_by'] );
			}

			$data['new_topic']['author']    = ( isset( $data['new_topic']['author'] ) ) ? $data['new_topic']['author'] : 0;
			$data['new_reply']['author']    = ( isset( $data['new_reply']['author'] ) ) ? $data['new_reply']['author'] : 0;

			$data['show_points_in_reply']   = ( isset( $data['show_points_in_reply'] ) ) ? $data['show_points_in_reply'] : 0;
			$data['show_points_in_profile'] = ( isset( $data['show_points_in_profile'] ) ) ? $data['show_points_in_profile'] : 0;

			return $data;
		}

	}

}

?>