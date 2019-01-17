<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_Bank_Transfer class
 * Manual payment gateway - bank transfers
 * @since 1.7
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_Bank_Transfer' ) ) :
	class myCRED_Bank_Transfer extends myCRED_Payment_Gateway {

		/**
		 * Construct
		 */
		function __construct( $gateway_prefs ) {

			$types = mycred_get_types();
			$default_exchange = array();
			foreach ( $types as $type => $label )
				$default_exchange[ $type ] = 1;

			parent::__construct( array(
				'id'               => 'bank',
				'label'            => 'Bank Transfer',
				'gateway_logo_url' => '',
				'defaults'         => array(
					'logo'             => '',
					'title'            => '',
					'account'          => '',
					'currency'         => 'EUR',
					'exchange'         => $default_exchange
				)
			), $gateway_prefs );

		}

		/**
		 * Process Handler
		 * @since 1.0
		 * @version 1.0
		 */
		public function process() { }

		/**
		 * Results Handler
		 * @since 1.0
		 * @version 1.0
		 */
		public function returning() { }

		/**
		 * Buy Handler
		 * @since 1.0
		 * @version 1.0
		 */
		public function buy() {

			if ( empty( $this->prefs['account'] ) ) wp_die( __( 'Please setup this gateway before attempting to make a purchase!', 'mycred' ) );

			// Prep
			$type         = $this->get_point_type();
			$mycred       = mycred( $type );

			$amount       = $mycred->number( $_REQUEST['amount'] );
			$amount       = abs( $amount );

			$cost         = $this->get_cost( $amount, $type );
			$to           = $this->get_to();
			$from         = get_current_user_id();
			$thankyou_url = $this->get_thankyou();

			// Revisiting pending payment
			if ( isset( $_REQUEST['revisit'] ) )
				$this->transaction_id = strtoupper( sanitize_text_field( $_REQUEST['revisit'] ) );

			// New pending payment
			else {
				$post_id              = $this->add_pending_payment( array( $to, $from, $amount, $cost, $this->prefs['currency'], $type ) );
				$this->transaction_id = get_the_title( $post_id );
			}

			$cancel_url   = $this->get_cancelled( $this->transaction_id );

			// Set Logo
			$logo = '';
			if ( isset( $this->prefs['logo'] ) && ! empty( $this->prefs['logo'] ) )
				$logo = '<img src="' . $this->prefs['logo'] . '" alt="" />';

			elseif ( isset( $this->prefs['logo_url'] ) && ! empty( $this->prefs['logo_url'] ) )
				$logo = '<img src="' . $this->prefs['logo_url'] . '" alt="" />';
			elseif ( isset( $this->gateway_logo_url ) && ! empty( $this->gateway_logo_url ) )
				$logo = '<img src="' . $this->gateway_logo_url . '" alt="" />';
			
			if ( $this->sandbox_mode )
				$title = __( 'Test Payment', 'mycred' );
			elseif ( ! empty( $this->prefs['title'] ) )
				$title = $this->prefs['title'];
			else
				$title = __( 'Payment', 'mycred' );

			$this->get_page_header( $title );

?>
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<table cellpadding="0" cellspacing="0">
			<thead>
				<tr>
					<th id="gateway-order-item" class="order-item"><?php _e( 'Item', 'mycred' ); ?></th>
					<th id="gateway-order-amount" class="order-amount"><?php echo $this->core->plural() ?></th>
					<th id="gateway-order-cost" class="order-cost"><?php _e( 'Cost', 'mycred' ); ?></th>
					<th id="gateway-order-transaction" class="order-transaction"><?php _e( 'Transaction ID', 'mycred' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class="order-item"><?php echo apply_filters( 'mycred_buycred_order_name', sprintf( __( '%s Purchase', 'mycred' ), $this->core->singular() ), $amount, $cost, $this ); ?></td>
					<td class="order-amount"><?php echo $amount; ?></td>
					<td class="order-cost"><?php echo $cost; ?> <?php if ( isset( $this->prefs['currency'] ) ) echo $this->prefs['currency']; else echo 'USD'; ?></td>
					<td class="order-transaction"><?php echo $this->transaction_id; ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div id="buycred-bank-details">

			<?php echo wptexturize( wpautop( $this->prefs['account'] ) ); ?>

		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
		<div id="buycred-continue">
			<p style="text-align:center;"><a href="<?php echo $this->get_thankyou(); ?>"><?php _e( 'Continue', 'mycred' ); ?></a></p>
		</div>
	</div>
</div>
<?php

			$this->get_page_footer();

			exit;

		}

		/**
		 * Preferences
		 * @since 1.0
		 * @version 1.0
		 */
		function preferences() {

			$prefs = $this->prefs;

?>
<label class="subheader" for="<?php echo $this->field_id( 'title' ); ?>"><?php _e( 'Title', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'title' ); ?>" id="<?php echo $this->field_id( 'title' ); ?>" value="<?php echo esc_attr( $prefs['title'] ); ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'logo' ); ?>"><?php _e( 'Checkout Logo', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'logo' ); ?>" id="<?php echo $this->field_id( 'logo' ); ?>" value="<?php echo esc_attr( $prefs['logo'] ); ?>" class="long" /></div>
	</li>
</ol>
<label class="subheader" for="buycredbanktransferaccount"><?php _e( 'Bank Account Information', 'mycred' ); ?></label>
<ol>
	<li>
		<?php wp_editor( $prefs['account'], 'buycredbanktransferaccount', array( 'textarea_name' => $this->field_name( 'account' ), 'textarea_rows' => 10 ) ); ?>
		<span class="description"><?php _e( 'Bank transfer details to show the user on the checkout page.', 'mycred' ); ?></span>
	</li>
</ol>
<label class="subheader" for="<?php echo $this->field_id( 'currency' ); ?>"><?php _e( 'Currency', 'mycred' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" name="<?php echo $this->field_name( 'currency' ); ?>" id="<?php echo $this->field_id( 'currency' ); ?>" data-update="mycred-gateway-bank-currency" value="<?php echo esc_attr( $prefs['currency'] ); ?>" size="8" maxlength="3" /></div>
	</li>
</ol>
<label class="subheader"><?php _e( 'Exchange Rates', 'mycred' ); ?></label>
<ol>
	<?php $this->exchange_rate_setup(); ?>
</ol>
<script type="text/javascript">
jQuery(function($){

	$( '#mycred-gateway-prefs-bank-currency' ).change(function(){
		$( 'span.mycred-gateway-bank-currency' ).text( $(this).val() );
	});

});
</script>
<?php

		}

		/**
		 * Sanatize Prefs
		 * @since 1.0
		 * @version 1.0
		 */
		public function sanitise_preferences( $data ) {

			$new_data = array();

			$new_data['title']    = sanitize_text_field( $data['title'] );
			$new_data['logo']     = sanitize_text_field( $data['logo'] );
			$new_data['account']  = wp_kses_post( $data['account'] );
			$new_data['currency'] = sanitize_text_field( $data['currency'] );

			// If exchange is less then 1 we must start with a zero
			if ( isset( $data['exchange'] ) ) {
				foreach ( (array) $data['exchange'] as $type => $rate ) {
					if ( $rate != 1 && in_array( substr( $rate, 0, 1 ), array( '.', ',' ) ) )
						$data['exchange'][ $type ] = (float) '0' . $rate;
				}
			}
			$new_data['exchange'] = $data['exchange'];

			return $new_data;

		}

	}
endif;

?>