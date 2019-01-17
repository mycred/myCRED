<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.1
 * @version 1.0
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_wp_favorite_posts_hook', 100 );
function mycred_register_wp_favorite_posts_hook( $installed ) {

	if ( ! function_exists( 'wp_favorite_posts' ) ) return $installed;

	$installed['wpfavorite'] = array(
		'title'       => __( 'WP Favorite Posts', 'mycred' ),
		'description' => __( 'Awards %_plural% for users adding posts to their favorites.', 'mycred' ),
		'callback'    => array( 'myCRED_Hook_WPFavorite' )
	);

	return $installed;

}

/**
 * WP Favorite Hook
 * @since 1.1
 * @version 1.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_wp_favorite_posts_hook', 100 );
function mycred_load_wp_favorite_posts_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_Hook_WPFavorite' ) || ! function_exists( 'wp_favorite_posts' ) ) return;

	class myCRED_Hook_WPFavorite extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'wpfavorite',
				'defaults' => array(
					'add'    => array(
						'creds' => 1,
						'log'   => '%plural% for adding a post as favorite',
						'limit' => '0/x'
					),
					'added'    => array(
						'creds' => 1,
						'log'   => '%plural% for your post being added to favorite',
						'limit' => '0/x'
					),
					'remove' => array(
						'creds' => 1,
						'log'   => '%plural% deduction for removing a post from favorites'
					),
					'removed' => array(
						'creds' => 1,
						'log'   => '%plural% deduction for post removed from favorites'
					)
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.1
		 * @version 1.0.1
		 */
		public function run() {

			add_action( 'wpfp_after_add',    array( $this, 'add_favorite' ) );
			add_action( 'wpfp_after_remove', array( $this, 'remove_favorite' ) );

		}

		/**
		 * Add Favorite
		 * @since 1.1
		 * @version 1.2
		 */
		public function add_favorite( $post_id ) {

			// Must be logged in
			if ( ! is_user_logged_in() ) return;

			$post    = get_post( $post_id );
			$user_id = get_current_user_id();

			if ( $user_id != $post->post_author ) {

				// Award the user adding to favorite
				if ( $this->prefs['add']['creds'] != 0 && ! $this->core->exclude_user( $user_id ) ) {

					// Limit
					if ( ! $this->over_hook_limit( 'add', 'add_favorite_post', $user_id ) ) {

						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'add_favorite_post', $post_id, $user_id ) ) {

							// Execute
							$this->core->add_creds(
								'add_favorite_post',
								$user_id,
								$this->prefs['add']['creds'],
								$this->prefs['add']['log'],
								$post_id,
								array( 'ref_type' => 'post' ),
								$this->mycred_type
							);

						}

					}

				}

				// Award post author for being added to favorite
				if ( $this->prefs['added']['creds'] != 0 && ! $this->core->exclude_user( $post->post_author ) ) {

					// Limit
					if ( ! $this->over_hook_limit( 'added', 'add_favorite_post', $post->post_author ) ) {

						// Make sure this is unique event
						if ( ! $this->core->has_entry( 'favorited_post', $post_id, $post->post_author ) ) {

							// Execute
							$this->core->add_creds(
								'favorited_post',
								$post->post_author,
								$this->prefs['added']['creds'],
								$this->prefs['added']['log'],
								$post_id,
								array( 'ref_type' => 'post', 'by' => $user_id ),
								$this->mycred_type
							);

						}

					}

				}

			}

		}

		/**
		 * Remove Favorite
		 * @since 1.1
		 * @version 1.2
		 */
		public function remove_favorite( $post_id ) {

			// Must be logged in
			if ( ! is_user_logged_in() ) return;

			$post    = get_post( $post_id );
			$user_id = get_current_user_id();

			if ( $user_id != $post->post_author ) {

				if ( $this->prefs['remove']['creds'] != 0 && ! $this->core->exclude_user( $user_id ) ) {

					if ( ! $this->core->has_entry( 'favorite_post_removed', $post_id, $user_id ) ) {

						$this->core->add_creds(
							'favorite_post_removed',
							$user_id,
							$this->prefs['remove']['creds'],
							$this->prefs['remove']['log'],
							$post_id,
							array( 'ref_type' => 'post' ),
							$this->mycred_type
						);

					}

				}

				if ( $this->prefs['removed']['creds'] != 0 && ! $this->core->exclude_user( $post->post_author ) ) {

					if ( ! $this->core->has_entry( 'favorite_post_removal', $post_id, $post->post_author ) ) {

						$this->core->add_creds(
							'favorite_post_removal',
							$post->post_author,
							$this->prefs['removed']['creds'],
							$this->prefs['removed']['log'],
							$post_id,
							array( 'ref_type' => 'post', 'by' => $user_id ),
							$this->mycred_type
						);

					}

				}

			}

		}

		/**
		 * Preferences for WP-Polls
		 * @since 1.1
		 * @version 1.1
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( array( 'add' => 'creds' ) ); ?>"><?php _e( 'Adding Content to Favorites', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'add' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'add' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['add']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'add' => 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'add' => 'limit' ) ), $this->field_id( array( 'add' => 'limit' ) ), $prefs['add']['limit'] ); ?>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'add' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'add' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'add' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['add']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>

<label class="subheader" for="<?php echo $this->field_id( array( 'added' => 'creds' ) ); ?>"><?php _e( 'Authors Content added to favorites', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'added' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'added' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['added']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'added' => 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'added' => 'limit' ) ), $this->field_id( array( 'added' => 'limit' ) ), $prefs['added']['limit'] ); ?>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'added' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'added' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'added' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['added']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>

<label class="subheader" for="<?php echo $this->field_id( array( 'remove' => 'creds' ) ); ?>"><?php _e( 'Removing Content from Favorites', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'remove' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'remove' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['remove']['creds'] ); ?>" size="8" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'remove' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'remove' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'remove' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['remove']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'removed' => 'creds' ) ); ?>"><?php _e( 'Removing Content from Favorites (Author)', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'removed' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'removed' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['removed']['creds'] ); ?>" size="8" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'removed' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'removed' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'removed' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['removed']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<?php

		}
		
		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.0
		 */
		function sanitise_preferences( $data ) {

			if ( isset( $data['add']['limit'] ) && isset( $data['add']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['add']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['add']['limit'] = $limit . '/' . $data['add']['limit_by'];
				unset( $data['add']['limit_by'] );
			}

			if ( isset( $data['added']['limit'] ) && isset( $data['added']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['added']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['added']['limit'] = $limit . '/' . $data['added']['limit_by'];
				unset( $data['added']['limit_by'] );
			}

			return $data;

		}

	}

}

?>