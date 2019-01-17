<?php

/**
 * GD Star Rating
 * @since 1.2
 * @version 1.0
 */
if ( defined( 'myCRED_VERSION' ) ) {

	/**
	 * Register Hook
	 * @since 1.2
	 * @version 1.0
	 */
	add_filter( 'mycred_setup_hooks', 'GD_Star_myCRED_Hook' );
	function GD_Star_myCRED_Hook( $installed ) {
		$installed['gdstars'] = array(
			'title'       => __( 'GD Star Rating', 'mycred' ),
			'description' => __( 'Awards %_plural% for users rate items using the GD Star Rating plugin.', 'mycred' ),
			'callback'    => array( 'myCRED_Hook_GD_Star_Rating' )
		);
		return $installed;
	}

	/**
	 * GD Star Rating Hook
	 * @since 1.2
	 * @version 1.0
	 */
	if ( ! class_exists( 'myCRED_Hook_GD_Star_Rating' ) && class_exists( 'myCRED_Hook' ) ) {
		class myCRED_Hook_GD_Star_Rating extends myCRED_Hook {

			/**
			 * Construct
			 */
			function __construct( $hook_prefs, $type = 'mycred_default' ) {
				parent::__construct( array(
					'id'       => 'gdstars',
					'defaults' => array(
						'star_rating' => array(
							'creds' => 1,
							'log'   => '%plural% for rating'
						),
						'up_down' => array(
							'creds' => 1,
							'log'   => '%plural% for rating'
						)
					)
				), $hook_prefs, $type );
			}

			/**
			 * Run
			 * @since 1.2
			 * @version 1.0
			 */
			public function run() {
				add_action( 'gdsr_vote', array( $this, 'vote' ), 10, 4 );
			}

			/**
			 * Vote
			 * @since 1.2
			 * @version 1.0
			 */
			public function vote( $vote_value, $post_id, $vote_tpl, $vote_size ) {
				if ( ! is_user_logged_in() ) return;

				if ( is_string( $vote_value ) && $this->prefs['up_down']['creds'] == 0 ) return;
				elseif ( ! is_string( $vote_value ) && $this->prefs['star_rating']['creds'] == 0 ) return;

				if ( is_string( $vote_value ) ) {
					$vote = 'up_down';
					$star = false;
				} else {
					$vote = 'star_rating';
					$star = true;
				}
				$user_id = get_current_user_id();

				if ( $this->core->has_entry( 'rating', $post_id, $user_id, $vote ) ) return;

				// Execute
				$this->core->add_creds(
					'rating',
					$user_id,
					( $star ) ? $this->prefs['star_rating']['creds'] : $this->prefs['up_down']['creds'],
					( $star ) ? $this->prefs['star_rating']['log'] : $this->prefs['up_down']['log'],
					$post_id,
					$vote,
					$this->mycred_type
				);
			}

			/**
			 * Preferences for GD Star Rating
			 * @since 1.2
			 * @version 1.0.1
			 */
			public function preferences() {
				$prefs = $this->prefs; ?>

<label class="subheader" for="<?php echo $this->field_id( array( 'star_rating' => 'creds' ) ); ?>"><?php _e( 'Rating', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'star_rating' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'star_rating' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['star_rating']['creds'] ); ?>" size="8" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'star_rating' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'star_rating' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'star_rating' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['star_rating']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'up_down' => 'creds' ) ); ?>"><?php _e( 'Up / Down Vote', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'up_down' => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( 'up_down' => 'creds' ) ); ?>" value="<?php echo $this->core->number( $prefs['up_down']['creds'] ); ?>" size="8" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( array( 'up_down' => 'log' ) ); ?>"><?php _e( 'Log Template', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( array( 'up_down' => 'log' ) ); ?>" id="<?php echo $this->field_id( array( 'up_down' => 'log' ) ); ?>" value="<?php echo esc_attr( $prefs['up_down']['log'] ); ?>" class="long" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
	</li>
</ol>
<?php
			}
		}
	}
}
?>