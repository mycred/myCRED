<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Account class
 * @see http://codex.mycred.me/classes/mycred_account/
 * @since 1.7
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Account' ) ) :
	class myCRED_Account extends myCRED_Object {

		public $user_id     = false;
		public $point_types = array();

		public $balance     = false;

		/**
		 * Construct
		 */
		function __construct( $user_id = NULL, $type = '' ) {

			parent::__construct();

			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			$user_id = absint( $user_id );
			if ( $user_id === 0 ) return;

			$type = sanitize_key( $type );

			$this->user_id     = $user_id;
			$this->point_types = mycred_get_types( true );

			$this->populate( $type );

		}

		protected function populate( $type = '' ) {

			if ( empty( $this->point_types ) ) return;

			$this->balance = array();

			// Populate one particular point type
			if ( $type != '' && mycred_point_type_exists( $type ) ) {

				$mycred = mycred( $type );

				if ( $mycred->exclude_user( $this->user_id ) ) return;

				$this->balance[] = $this->get_balance( $type, $mycred );

			}

			// Populate all point types
			else {

				foreach ( $this->point_types as $type_id => $type_label ) {

					$mycred = mycred( $type_id );

					if ( $mycred->exclude_user( $this->user_id ) ) continue;

					$this->balance[ $type_id ] = $this->get_balance( $type_id, $mycred );

				}

			}

		}

		public function get_balance( $type_id = MYCRED_DEFAULT_TYPE_KEY, $mycred = NULL ) {

			$mycred = $this->get_mycred( $mycred, $type_id );

			$balance       = new myCRED_Balance( $this->user_id, $type_id, $mycred );
			$balance->type = $this->get_type( $type_id, $mycred );

			return $balance;

		}

		public function get_type( $type_id = MYCRED_DEFAULT_TYPE_KEY, $mycred = NULL ) {

			$mycred = $this->get_mycred( $mycred, $type_id );

			$type = new myCRED_Point_Type( $type_id, $mycred );

			return $type;

		}

	}
endif;

/**
 * myCRED_Balance class
 * @see http://codex.mycred.me/classes/mycred_balance/
 * @since 1.7
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Balance' ) ) :
	class myCRED_Balance extends myCRED_Object {

		public $current     = 0;
		public $accumulated = 0;
		public $type        = '';

		/**
		 * Construct
		 */
		function __construct( $user_id = NULL, $type = '', $mycred = NULL ) {

			parent::__construct();

			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			$user_id = absint( $user_id );
			if ( $user_id === 0 ) return;

			$type = sanitize_key( $type );

			$this->populate( $user_id, $type, $mycred );

		}

		protected function populate( $user_id = NULL, $type_id = '', $mycred = NULL ) {

			$mycred = $this->get_mycred( $mycred, $type_id );

			$this->current     = $mycred->get_users_balance( $user_id, $type_id );
			$this->accumulated = mycred_get_users_total( $user_id, $type_id );

		}

	}
endif;

/**
 * myCRED_Point_Type class
 * @see http://codex.mycred.me/classes/mycred_point_type/
 * @since 1.7
 * @version 1.0.1
 */
if ( ! class_exists( 'myCRED_Point_Type' ) ) :
	class myCRED_Point_Type extends myCRED_Object {

		public $cred_id  = '';
		public $singular = '';
		public $plural   = '';
		public $prefix   = '';
		public $suffix   = '';
		public $format   = array();

		/**
		 * Construct
		 */
		function __construct( $type = '', $mycred = NULL ) {

			parent::__construct();

			$type = sanitize_key( $type );
			if ( ! mycred_point_type_exists( $type ) ) return;

			$this->populate( $type, $mycred );

		}

		protected function populate( $type_id = '', $mycred = NULL ) {

			$mycred = $this->get_mycred( $mycred, $type_id );

			$this->cred_id  = $type_id;
			$this->singular = $mycred->singular();
			$this->plural   = $mycred->plural();
			$this->prefix   = $mycred->before;
			$this->suffix   = $mycred->after;
			$this->format   = $mycred->format;

		}

		public function number( $number ) {

			$number = str_replace( '+', '', $number );

			if ( ! isset( $this->format['decimals'] ) )
				$decimals = (int) $this->core['format']['decimals'];

			else
				$decimals = (int) $this->format['decimals'];

			$result = intval( $number );
			if ( $decimals > 0 )
				$result = floatval( number_format( (float) $number, $decimals, '.', '' ) );

			return apply_filters( 'mycred_type_number', $result, $number, $this );

		}

		public function format( $number ) {

			$number   = $this->number( $number );
			$decimals = $this->format['decimals'];
			$sep_dec  = $this->format['separators']['decimal'];
			$sep_tho  = $this->format['separators']['thousand'];

			// Format
			$number = number_format( $number, (int) $decimals, $sep_dec, $sep_tho );

			$prefix = '';
			if ( ! empty( $this->prefix ) )
				$prefix = $this->prefix . ' ';

			// Suffix
			$suffix = '';
			if ( ! empty( $this->suffix ) )
				$suffix = ' ' . $this->suffix;

			return apply_filters( 'mycred_type_format', $prefix . $number . $suffix, $number, $this );

		}

	}
endif;
