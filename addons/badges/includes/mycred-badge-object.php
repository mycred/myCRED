<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Badge class
 * @see http://codex.mycred.me/classes/mycred_badge/
 * @since 1.7
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Badge' ) ) :
	class myCRED_Badge extends myCRED_Object {

		public $post_id      = false;
		public $title        = '';
		public $earnedby     = 0;
		public $manual       = false;
		public $levels       = array();
		public $level        = false;
		public $level_label  = false;
		public $main_image   = false;
		public $level_image  = false;
		public $image_width  = false;
		public $image_height = false;

		/**
		 * Construct
		 */
		function __construct( $badge_id = NULL, $level = NULL ) {

			parent::__construct();

			$badge_id = absint( $badge_id );

			if ( get_post_type( $badge_id ) != 'mycred_badge' ) return;

			$this->image_width  = MYCRED_BADGE_WIDTH;
			$this->image_height = MYCRED_BADGE_HEIGHT;

			$this->populate( $badge_id, $level );

		}

		protected function populate( $badge_id = NULL, $level = NULL ) {

			$this->post_id      = absint( $badge_id );
			$this->title        = get_the_title( $this->post_id );
			$this->earnedby     = mycred_count_users_with_badge( $badge_id, $level );
			$this->levels       = mycred_get_badge_levels( $this->post_id );

			if ( absint( get_post_meta( $this->post_id, 'manual_badge', true ) ) === 1 )
				$this->manual = true;

			if ( $level !== NULL && ! empty( $this->levels ) && array_key_exists( $level, $this->levels ) ) {
				$this->level = $this->levels[ $level ];
				if ( $this->level['label'] != '' )
					$this->level_label = $this->level['label'];
			}

			$this->main_image  = $this->get_image( 'main' );
			$this->level_image = $this->get_image( $level );

		}

		public function get_image( $image = NULL ) {

			$image_identification = false;

			if ( $image === 'main' )
				$image_identification = get_post_meta( $this->post_id, 'main_image', true );

			elseif ( $image !== NULL && is_numeric( $image ) && isset( $this->levels[ $image ]['attachment_id'] ) ) {

				$image_identification = $this->levels[ $image ]['image_url'];
				if ( $this->levels[ $image ]['attachment_id'] > 0 )
					$image_identification = $this->levels[ $image ]['attachment_id'];

			}

			if ( $image_identification === false || strlen( $image_identification ) == 0 ) return false;

			$image_url = $image_identification;
			if ( is_numeric( $image_identification ) &&  strpos( '://', $image_identification ) === false )
				$image_url = wp_get_attachment_url( $image_identification );

			return '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $this->title ) . '" width="' . $this->image_width . '" height="' . $this->image_height . '" />';

		}

	}
endif;

?>