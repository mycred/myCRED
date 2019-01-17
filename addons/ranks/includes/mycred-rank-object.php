<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Rank class
 * @see http://codex.mycred.me/classes/mycred_rank/
 * @since 1.7
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Rank' ) ) :
	class myCRED_Rank extends myCRED_Object {

		public $post_id      = false;
		public $post         = false;
		public $title        = '';
		public $minimum      = NULL;
		public $maximum      = NULL;
		public $count        = 0;

		public $has_logo     = false;
		public $logo_id      = false;
		public $logo_url     = false;
		public $image_width  = false;
		public $image_height = false;

		public $point_type   = false;

		/**
		 * Construct
		 */
		function __construct( $rank_id = NULL ) {

			parent::__construct();

			$rank_id = absint( $rank_id );
			if ( $rank_id === 0 ) return;

			if ( get_post_type( $rank_id ) != 'mycred_rank' ) return;

			$this->image_width  = MYCRED_RANK_WIDTH;
			$this->image_height = MYCRED_RANK_HEIGHT;

			$this->populate( $rank_id );

		}

		protected function populate( $rank_id = NULL ) {

			$this->post_id    = absint( $rank_id );
			$this->post       = get_post( $this->post_id );
			$this->title      = get_the_title( $this->post_id );
			$this->minimum    = get_post_meta( $this->post_id, 'mycred_rank_min', true );
			$this->maximum    = get_post_meta( $this->post_id, 'mycred_rank_max', true );
			$this->count      = mycred_count_users_with_rank( $this->post_id );

			$this->has_logo   = mycred_rank_has_logo( $this->post_id );
			$this->logo_id    = get_post_thumbnail_id( $this->post );
			$this->logo_url   = wp_get_attachment_url( $this->logo_id );

			$point_type = get_post_meta( $this->post_id, 'ctype', true );
			if ( ! mycred_point_type_exists( $point_type ) )
				$point_type = MYCRED_DEFAULT_TYPE_KEY;

			$this->point_type = new myCRED_Point_Type( $point_type );

		}

		public function get_image( $image = 'logo' ) {

			if ( $image === 'logo' )
				return '<img src="' . esc_url( $this->logo_url ) . '" alt="' . esc_attr( $this->title ) . '" width="' . $this->image_width . '" height="' . $this->image_height . '" />';

			return '';

		}

	}
endif;

?>