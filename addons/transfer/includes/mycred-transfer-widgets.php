<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Widget: myCRED Transfer
 * @since 0.1
 * @version 1.2.2
 */
if ( ! class_exists( 'myCRED_Widget_Transfer' ) ) :
	class myCRED_Widget_Transfer extends WP_Widget {

		/**
		 * Construct
		 */
		public function __construct() {

			parent::__construct(
				'mycred_widget_transfer',
				sprintf( __( '(%s) Transfer', 'mycred' ), mycred_label( true ) ),
				array(
					'classname'   => 'widget-my-cred-transfer',
					'description' => __( 'Allow transfers between users.', 'mycred' )
				)
			);

		}

		/**
		 * Widget Output
		 */
		public function widget( $args, $instance ) {

			$allowed_html = array(
				'label'	=> array(
					'class'			=> array()
				),
				'input' => array(
					'type'  		=> array(),
					'value' 		=> array(),
					'name'  		=> array(),
					'class'			=> array(),
					'aria-required'	=> array(),
					'data-form'		=> array(),
					'placeholder'	=> array(),
					'autocomplete'	=> array(),
					'id'			=> array()
				),
				'span' => array(
					'class'		=> array()
				),
				'form' 	=> array(
					'id'			=> array(),
					'class'			=> array(),
					'method'		=> array(),
					'data-ref'		=> array()
				),
				'div'	=> array(
					'class'			=> array(),
					'id'			=> array()
				),
				'button' => array(
					'class'			=> array()
				),
				'select' => array(
					'name'  		=> array(),
					'class'			=> array()
				),
				'option' => array(
					'value'    		=> array(),
					'selected' 		=> array()
				)
			);
			
			extract( $args, EXTR_SKIP );

			$instance = shortcode_atts( array(
				'title'        => '',
				'button'       => 'Transfer',
				'pay_to'       => '',
				'show_balance' => 0,
				'show_limit'   => 0,
				'reference'    => 'transfer',
				'amount'       => '',
				'excluded'     => '',
				'types'        => MYCRED_DEFAULT_TYPE_KEY,
				'placeholder'  => ''
			), $instance );

			echo wp_kses_post( $before_widget );

			// Title
			if ( ! empty( $instance['title'] ) )
				echo wp_kses_post( $before_title . $instance['title'] . $after_title );

			// Let the shortcode to the job
			echo wp_kses( mycred_transfer_render( array(
				'button'       => $instance['button'],
				'pay_to'       => $instance['pay_to'],
				'show_balance' => $instance['show_balance'],
				'show_limit'   => $instance['show_limit'],
				'ref'          => $instance['reference'],
				'amount'       => $instance['amount'],
				'excluded'     => $instance['excluded'],
				'types'        => $instance['types'],
				'placeholder'  => $instance['placeholder']
			) ), $allowed_html );

			echo wp_kses_post( $after_widget );

		}

		/**
		 * Outputs the options form on admin
		 */
		public function form( $instance ) {

			// Defaults
			$title        = isset( $instance['title'] )        ? $instance['title']        : 'Transfer';
			$show_balance = isset( $instance['show_balance'] ) ? $instance['show_balance'] : 0;
			$show_limit   = isset( $instance['show_limit'] )   ? $instance['show_balance'] : 0;
			$button       = isset( $instance['button'] )       ? $instance['button']       : 'Transfer';
			$amount       = isset( $instance['amount'] )       ? $instance['amount']       : '';
			$reference    = isset( $instance['reference'] )    ? $instance['reference']    : 'transfer';
			$recipient    = isset( $instance['pay_to'] )       ? $instance['pay_to']       : '';
			$point_types  = isset( $instance['types'] )        ? $instance['types']        : MYCRED_DEFAULT_TYPE_KEY;
			$excluded     = isset( $instance['excluded'] )     ? $instance['excluded']     : '';
			$placeholder  = isset( $instance['placeholder'] )  ? $instance['placeholder']  : '';

?>
<!-- Widget Options -->
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'optional', 'mycred' ); ?>" value="<?php echo esc_attr( $title ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'show_balance' ) ); ?>"><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_balance' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'show_balance' ) ); ?>" value="1"<?php checked( $show_balance, true ); ?> class="checkbox" /> <?php esc_html_e( 'Show users balance', 'mycred' ); ?></label>
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'show_limit' ) ); ?>"><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_limit' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'show_limit' ) ); ?>" value="1"<?php checked( $show_balance, true ); ?> class="checkbox" /> <?php esc_html_e( 'Show users limit', 'mycred' ); ?></label>
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'button' ) ); ?>"><?php esc_html_e( 'Button Label', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'button' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $button ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'amount' ) ); ?>"><?php esc_html_e( 'Amount', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'amount' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'amount' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $amount ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'reference' ) ); ?>"><?php esc_html_e( 'Reference', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'reference' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'reference' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $reference ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'pay_to' ) ); ?>"><?php esc_html_e( 'Recipient', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'pay_to' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'pay_to' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'optional', 'mycred' ); ?>" value="<?php echo esc_attr( $recipient ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>"><?php esc_html_e( 'Recipient Placeholder', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'placeholder' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'placeholder' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'optional', 'mycred' ); ?>" value="<?php echo esc_attr( $placeholder ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'types' ) ); ?>"><?php esc_html_e( 'Point Types', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'types' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'types' ) ); ?>" type="text" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $point_types ); ?>" class="widefat" />
</p>
<p class="myCRED-widget-field">
	<label for="<?php echo esc_attr( $this->get_field_id( 'excluded' ) ); ?>"><?php esc_html_e( 'Message for Excluded Users', 'mycred' ); ?>:</label>
	<input id="<?php echo esc_attr( $this->get_field_id( 'excluded' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'excluded' ) ); ?>" placeholder="<?php esc_attr_e( 'optional', 'mycred' ); ?>" type="text" value="<?php echo esc_attr( $excluded ); ?>" class="widefat" />
</p>
<?php

		}

		/**
		 * Processes widget options to be saved
		 */
		public function update( $new_instance, $old_instance ) {

			$instance                 = $old_instance;

			$instance['title']        = wp_kses_post( $new_instance['title'] );
			$instance['show_balance'] = ( isset( $new_instance['show_balance'] ) ) ? 1 : 0;
			$instance['show_limit']   = ( isset( $new_instance['show_limit'] ) ) ? 1 : 0;
			$instance['button']       = sanitize_text_field( $new_instance['button'] );
			$instance['amount']       = sanitize_text_field( $new_instance['amount'] );
			$instance['reference']    = sanitize_key( $new_instance['reference'] );
			$instance['pay_to']       = sanitize_text_field( $new_instance['pay_to'] );
			$instance['placeholder']  = sanitize_text_field( $new_instance['placeholder'] );
			$instance['types']        = sanitize_text_field( $new_instance['types'] );
			$instance['excluded']     = sanitize_text_field( $new_instance['excluded'] );

			mycred_flush_widget_cache( 'mycred_widget_transfer' );

			return $instance;

		}

	}
endif;
