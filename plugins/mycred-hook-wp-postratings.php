<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.6
 * @version 1.0
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_wp_postratings_hook', 110 );
function mycred_register_wp_postratings_hook( $installed ) {

	if ( ! defined( 'WP_POSTRATINGS_VERSION' ) ) return $installed;

	$installed['wp_postratings'] = array(
		'title'       => __( 'Post Ratings', 'mycred' ),
		'description' => __( 'Awards %_plural% for post ratings. Supports awarding %_plural% both to post author and the user rating.', 'mycred' ),
		'callback'    => array( 'myCRED_WP_Postratings' )
	);

	return $installed;

}

/**
 * WP Postratings Hook
 * @since 1.6
 * @version 1.0
 */
add_action( 'mycred_load_hooks', 'mycred_load_wp_postratings_hook', 110 );
function mycred_load_wp_postratings_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_WP_Postratings' ) || ! defined( 'WP_POSTRATINGS_VERSION' ) ) return;

	class myCRED_WP_Postratings extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'wp_postratings',
				'defaults' => array(
					'rating'     => array(
						'creds' => 0,
						'log'   => '%plural% for rating',
						'limit' => '0/x',
						'value' => 0
					),
					'rated'    => array(
						'creds' => 0,
						'log'   => '%plural% for getting a rating',
						'limit' => '0/x',
						'value' => 0
					)
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.6
		 * @version 1.0
		 */
		public function run() {

			add_action( 'rate_post',               array( $this, 'new_rating' ), 10, 3 );
			add_filter( 'mycred_hook_table_creds', array( $this, 'table_amount' ), 10, 3 );

		}

		/**
		 * Table Amount
		 * @since 1.6
		 * @version 1.0
		 */
		public function table_amount( $amount, $id, $prefs ) {

			if ( ! in_array( $id, array( 'rating', 'rated' ) ) || ! isset( $prefs['value'] ) ) return $amount;

			if ( $prefs['value'] == 1 )
				return __( 'Based on rating', 'mycred' );

			return $amount;

		}

		/**
		 * Successful Form Submission
		 * @since 1.6
		 * @version 1.0
		 */
		public function new_rating( $user_id, $post_id, $rating_value ) {

			// Get post
			$post = get_post( $post_id );

			// Authors can not get points for rating their own stuff
			if ( ! isset( $post->post_author ) && $post->post_author == $user_id ) return;

			// Determen the amount to award
			$amount = $this->prefs['rating']['creds'];
			if ( $this->prefs['rating']['value'] == 1 )
				$amount = $rating_value;

			// If enabled - award the rater
			if ( $amount != 0 ) {

				// Only award if the user is not excluded and not over their limit
				if ( ! $this->core->exclude_user( $user_id ) && ! $this->over_hook_limit( 'rating', 'post_rating', $user_id ) )
					$this->core->add_creds(
						'post_rating',
						$user_id,
						$amount,
						$this->prefs['rating']['log'],
						$post_id,
						array( 'ref_type' => 'post', 'value' => $rating_value ),
						$this->mycred_type
					);

			}

			// Determen the amount to award for author
			$amount = $this->prefs['rated']['creds'];
			if ( $this->prefs['rated']['value'] == 1 )
				$amount = $rating_value;

			// If enabled - award the rater
			if ( $amount != 0 ) {

				// Only award if the author is not excluded and not over their limit
				if ( ! $this->core->exclude_user( $post->post_author ) && ! $this->over_hook_limit( 'rated', 'post_rating_author', $post->post_author ) )
					$this->core->add_creds(
						'post_rating_author',
						$post->post_author,
						$amount,
						$this->prefs['rated']['log'],
						$post_id,
						array( 'ref_type' => 'post', 'value' => $rating_value ),
						$this->mycred_type
					);

			}

		}

		/**
		 * Preferences for WP Postratings Hook
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function preferences() {

			$prefs = $this->prefs;

?>
<label for="<?php echo $this->field_id( array( 'rating', 'creds' ) ); ?>" class="subheader"><?php _e( 'Adding a Rating', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'rating', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'rating', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['rating']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'rating', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'rating', 'limit' ) ), $this->field_id( array( 'rating', 'limit' ) ), $prefs['rating']['limit'] ); ?>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'rating', 'value' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'rating', 'value' ) ); ?>" id="<?php echo $this->field_id( array( 'rating', 'value' ) ); ?>" <?php checked( $prefs['rating']['value'], 1 ); ?> value="1" /> <?php _e( 'Use the Rating Value instead of the amount set here.', 'mycred' ); ?></label>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'rating', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'rating', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'rating', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['rating']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ) ); ?></span>
	</li>
</ol>
<label for="<?php echo $this->field_id( array( 'rated', 'creds' ) ); ?>" class="subheader"><?php _e( 'Receiving a Rating', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'rated', 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'rated', 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['rated']['creds'] ); ?>" size="8" /></div>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'rated', 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
		<?php echo $this->hook_limit_setting( $this->field_name( array( 'rated', 'limit' ) ), $this->field_id( array( 'rated', 'limit' ) ), $prefs['rated']['limit'] ); ?>
	</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'rated', 'value' ) ); ?>"><input type="checkbox" name="<?php echo $this->field_name( array( 'rated', 'value' ) ); ?>" id="<?php echo $this->field_id( array( 'rated', 'value' ) ); ?>" <?php checked( $prefs['rated']['value'], 1 ); ?> value="1" /> <?php _e( 'Use the Rating Value instead of the amount set here.', 'mycred' ); ?></label>
	</li>
	<li class="empty">&nbsp;</li>
	<li>
		<label for="<?php echo $this->field_id( array( 'rated', 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'rated', 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'rated', 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['rated']['log'] ); ?>" class="long" /></div>
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

			$data['rating']['value'] = ( isset( $data['rating']['value'] ) ) ? $data['rating']['value'] : 0;

			if ( isset( $data['rating']['limit'] ) && isset( $data['rating']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['rating']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['rating']['limit'] = $limit . '/' . $data['rating']['limit_by'];
				unset( $data['rating']['limit_by'] );
			}

			$data['rated']['value'] = ( isset( $data['rated']['value'] ) ) ? $data['rated']['value'] : 0;

			if ( isset( $data['rated']['limit'] ) && isset( $data['rated']['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['rated']['limit'] );
				if ( $limit == '' ) $limit = 0;
				$data['rated']['limit'] = $limit . '/' . $data['rated']['limit_by'];
				unset( $data['rated']['limit_by'] );
			}

			return $data;

		}

	}

}

?>