<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Register Hook
 * @since 1.5
 * @version 1.1
 */
add_filter( 'mycred_setup_hooks', 'mycred_register_sharethis_hook', 80 );
function mycred_register_sharethis_hook( $installed ) {

	if ( ! function_exists( 'install_ShareThis' ) ) return $installed;

	$installed['sharethis'] = array(
		'title'         => __( '%plural% for Sharing', 'mycred' ),
		'description'   => __( 'Awards %_plural% for users sharing / liking your website content to popular social media sites.', 'mycred' ),
		'documentation' => 'http://codex.mycred.me/hooks/sharethis-actions/',
		'callback'      => array( 'myCRED_ShareThis' )
	);

	return $installed;

}

/**
 * ShareThis Hook
 * @since 1.5
 * @version 1.0.1
 */
add_action( 'mycred_load_hooks', 'mycred_load_sharethis_hook', 80 );
function mycred_load_sharethis_hook() {

	// If the hook has been replaced or if plugin is not installed, exit now
	if ( class_exists( 'myCRED_ShareThis' ) || ! function_exists( 'install_ShareThis' ) ) return;

	class myCRED_ShareThis extends myCRED_Hook {

		/**
		 * Construct
		 */
		public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'sharethis',
				'defaults' => array()
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.5
		 * @version 1.0
		 */
		public function run() {

			add_filter( 'mycred_parse_log_entry',                          array( $this, 'parse_tags' ), 10, 2 );
			add_action( 'wp_footer',                                       array( $this, 'detect_shares' ), 80 );
			add_action( 'wp_ajax_mycred-share-this-' . $this->mycred_type, array( $this, 'ajax' ) );

		}

		/**
		 * Parse ShareThis Tags
		 * @since 1.5
		 * @version 1.0
		 */
		public function parse_tags( $content, $log ) {

			// Only applicable to this hook
			if ( $log->ref != 'share' ) return $content;

			$data  = maybe_unserialize( $log->data );
			$names = mycred_get_share_service_names();

			if ( isset( $names[ $data['service'] ] ) )
				$service = $names[ $data['service'] ];
			else
				$service = ucfirst( $data['service'] );

			$content = str_replace( '%service%', $service, $content );

			return $content;

		}

		/**
		 * Detect Shares
		 * @since 1.5
		 * @version 1.2
		 */
		public function detect_shares() {

			if ( ! is_user_logged_in() ) return;

			if ( is_singular() && apply_filters( 'mycred_load_share_this', true, $this ) ) {

				// Get post / page ID from outside the loop
				if ( ! in_the_loop() ) {

					if ( is_ssl() )
						$actual_link = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
					else
						$actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

					$post_id = url_to_postid( $actual_link );

				}

				// Get post / page ID from inside the loop
				else {

					global $post;

					$post_id = $post->ID;

				}

				$post_id = apply_filters( 'mycred_shared_post_id', $post_id, $this );

?>
<script type="text/javascript">
jQuery(function($) {

	function mycred_detect_share_<?php echo sanitize_key( $this->mycred_type ); ?>( event,service ) {

		console.log( 'Event: ' + event );
		console.log( 'Service: ' + service );

		$.ajax({
			type     : "POST",
			data     : {
				action   : 'mycred-share-this-<?php echo $this->mycred_type; ?>',
				token    : '<?php echo wp_create_nonce( 'mycred-share-this' . $this->mycred_type ); ?>',
				post_id  : <?php echo $post_id; ?>,
				via      : service
			},
			dataType : "JSON",
			url      : '<?php echo admin_url( 'admin-ajax.php' ); ?>',
			success  : function( response ) {
				console.log( response );
			}
		});

	};

	stLight.options({ publisher : '<?php echo get_option( 'st_pubid' ); ?>' });
	stLight.subscribe( 'click', mycred_detect_share_<?php echo sanitize_key( $this->mycred_type ); ?> );

});
</script>
<?php

			}

		}

		/**
		 * Ajax Handler
		 * @since 1.5
		 * @version 1.0
		 */
		public function ajax() {

			check_ajax_referer( 'mycred-share-this' . $this->mycred_type, 'token' );

			if ( ! isset( $_POST['post_id'] ) || ! isset( $_POST['via'] ) ) wp_send_json( 'ERROR' );

			$post_id = absint( $_POST['post_id'] );
			$service = sanitize_key( $_POST['via'] );
			if ( $service == 'sharethis' ) wp_send_json( '' );

			// Make sure this instance is enabled
			if ( ! isset( $this->prefs[ $service ] ) || $this->prefs[ $service ]['creds'] == 0 ) wp_send_json( '' );

			$user_id = get_current_user_id();

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) wp_send_json( '' );

			// Make sure this share is unique
			$data = array( 'ref_type' => 'post', 'service' => $service );
			if ( $this->core->has_entry( 'share', $post_id, $user_id, $data, $this->mycred_type ) ) wp_send_json( 'HAS ENTRY' );

			// Limit
			if ( $this->over_hook_limit( $service, 'share' ) ) wp_send_json( 'LIMIT' );

			// Execute
			$this->core->add_creds(
				'share',
				$user_id,
				$this->prefs[ $service ]['creds'],
				$this->prefs[ $service ]['log'],
				$post_id,
				$data,
				$this->mycred_type
			);

			wp_send_json( 'DONE' );

		}

		/**
		 * Preferences for ShareThis Hook
		 * @since 0.1
		 * @version 1.1
		 */
		public function preferences() {

			$st_public_key = get_option( 'st_pubid', false );
			$st_services   = get_option( 'st_services', false );

			// Public key is not yet setup
			if ( $st_public_key === false ) :

				echo '<p>' . __( 'Your ShareThis public key is not set.', 'mycred' ) . '</p>';

			// Services is not yet setup
			elseif ( $st_services === false ) :

				echo '<p>' . __( 'No ShareThis services detected. Please check your installation.', 'mycred' ) . '</p>';

			// All is well!
			else :

				$names    = mycred_get_share_service_names();

				// Loop though selected services
				$services = explode( ',', $st_services );

				// Add facebook unlike to facebook like.
				if ( in_array( 'fblike', $services ) )
					$services[] = 'fbunlike';

				foreach ( $services as $service ) {

					$service = str_replace( ' ', '', $service );
					if ( $service == '' || $service == 'sharethis' ) continue;

					if ( ! isset( $this->prefs[ $service ] ) )
						$this->prefs[ $service ] = array(
							'creds' => 0,
							'log'   => '%plural% for sharing %link_with_title% on %service%',
							'limit' => '0/x'
						);

					if ( ! isset( $this->prefs[ $service ]['limit'] ) )
						$this->prefs[ $service ]['limit'] = '0/x';

					if ( isset( $names[ $service ] ) )
						$service_name = $names[ $service ];
					else
						$service_name = ucfirst( $service );

?>
<div class="hook-instance">
	<h3><?php _e( 'Publishing Posts', 'mycred' ); ?></h3>
	<div class="row">
		<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $service => 'creds' ) ); ?>"><?php echo $this->core->plural(); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $service => 'creds' ) ); ?>" id="<?php echo $this->field_id( array( $service => 'creds' ) ); ?>" value="<?php echo $this->core->number( $this->prefs[ $service ]['creds'] ); ?>" class="form-control" />
			</div>
		</div>
		<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $service => 'limit' ) ); ?>"><?php _e( 'Limit', 'mycred' ); ?></label>
				<?php echo $this->hook_limit_setting( $this->field_name( array( $service => 'limit' ) ), $this->field_id( array( $service => 'limit' ) ), $this->prefs[ $service ]['limit'] ); ?>
			</div>
		</div>
		<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
			<div class="form-group">
				<label for="<?php echo $this->field_id( array( $service => 'log' ) ); ?>"><?php _e( 'Log template', 'mycred' ); ?></label>
				<input type="text" name="<?php echo $this->field_name( array( $service => 'log' ) ); ?>" id="<?php echo $this->field_id( array( $service => 'log' ) ); ?>" placeholder="<?php _e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $this->prefs[ $service ]['log'] ); ?>" class="form-control" />
				<span class="description"><?php echo $this->available_template_tags( array( 'general', 'post' ), '%service%' ); ?></span>
			</div>
		</div>
	</div>
</div>
<?php
				}

			endif;

		}

		/**
		 * Sanitise Preferences
		 * @since 1.6
		 * @version 1.0.1
		 */
		public function sanitise_preferences( $data ) {

			$st_services = get_option( 'st_services', false );

			// Loop though selected services
			$services = explode( ',', $st_services );

			// Add facebook unlike to facebook like.
			if ( in_array( 'fblike', $services ) )
				$services[] = 'fbunlike';

			foreach ( $services as $service ) {

				$service = str_replace( ' ', '', $service );

				if ( isset( $data[ $service ]['limit'] ) && isset( $data[ $service ]['limit_by'] ) ) {
					$limit = sanitize_text_field( $data[ $service ]['limit'] );
					if ( $limit == '' ) $limit = 0;
					$data[ $service ]['limit'] = $limit . '/' . $data[ $service ]['limit_by'];
					unset( $data[ $service ]['limit_by'] );
				}

			}

			return $data;

		}

	}

}

/**
 * ShareThis Service Names
 * @since 1.7.4
 * @version 1.0
 */
if ( ! function_exists( 'mycred_get_share_service_names' ) ) :
	function mycred_get_share_service_names() {

		return apply_filters( 'mycred_get_sharethis_service_name', array(
			'facebook'         => 'Facebook',
			'fblike'           => 'Facebook Like',
			'fbunlike'         => 'Facebook Unlike',
			'fbsub'            => 'Facebook Subscribe',
			'fbsend'           => 'Facebook Send',
			'fbrec'            => 'Facebook Recommend',
			'wordpress'        => 'WordPress',
			'google_bmarks'    => 'Google Bookmarks',
			'youtube'          => 'YouTube',
			'twitterfollow'    => 'Twitter Follow',
			'pinterestfollow'  => 'Pinterest Follow',
			'plusone'          => 'Google +1',
			'instagram'        => 'Instagram Badge',
			'foursquarefollow' => 'Foursquare Follow',
			'foursquaresave'   => 'Foursquare Save',
			'blogger'          => 'Blogger',
			'twitter'          => 'Tweet',
			'linkedin'         => 'LinkedIn',
			'pinterest'        => 'Pinterest',
			'email'            => 'Email',
			'googleplus'       => 'Google+',
			'amazon_wishlist'  => 'Amazon Wishlist',
			'bebo'             => 'Bebo',
			'delicious'        => 'Delicious',
			'myspace'          => 'MySpace',
			'reddit'           => 'Reddit',
			'slashdot'         => 'Slashdot',
			'tumblr'           => 'Tumblr'
		) );

	}
endif;
