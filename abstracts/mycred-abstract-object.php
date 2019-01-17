<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Object class
 * @see http://codex.mycred.me/classes/mycred_object/
 * @since 1.7
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Object' ) ) :
	abstract class myCRED_Object {

		/**
		 * Construct
		 */
		function __construct() {

			if ( ! did_action( 'init' ) )
				wp_die( 'myCRED_Account class used too early. This class should be called after the init action fires.' );

		}

		public function get_mycred( $object = NULL, $type_id = MYCRED_DEFAULT_TYPE_KEY ) {

			if ( ! is_object( $object ) ) {

				if ( ! is_string( $type_id ) || ! mycred_point_type_exists( $type_id ) )
					$type_id = MYCRED_DEFAULT_TYPE_KEY;

				$object = mycred( $type_id );

			}

			return $object;

		}

	}
endif;

?>