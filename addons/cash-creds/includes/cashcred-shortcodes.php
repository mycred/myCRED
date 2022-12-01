<?php
if ( ! defined( 'MYCRED_CASHCRED' ) ) exit;

/**
 * Shortcode: mycred_cashcred
 * @see http://codex.mycred.me/shortcodes/mycred_cashcred/
 * @since 1.0
 * @version 1.3
 */
  
if ( ! function_exists( 'mycred_render_cashcred' ) ) :
	function mycred_render_cashcred( $atts = array(), $content = '' ) {

		extract( shortcode_atts( array(
			'button'       => __( 'Submit Request', 'mycred' ),
			'gateways'     => '',
			'types'        => '',
			'amount'       => '',
			'excluded'     => __( 'You have excluded from this point type', 'mycred' ),
			'insufficient' => __( 'Insufficient Points for Withdrawal', 'mycred' )
		), $atts, MYCRED_SLUG . '_cashcred' ) );


		// If we are not logged in
		if ( ! is_user_logged_in() ) return $content;

		global $cashcred_instance, $mycred_modules, $cashcred_withdraw;
		
		// Prepare
		$user_id = get_current_user_id();

		$gateways = cashcred_get_usable_gateways( $gateways );

		// Make sure we have a gateway we can use
		if ( empty( $gateways ) ) return __( 'No gateway available.', 'mycred' );

		$point_types = cashcred_get_point_types( $types, $user_id );

		//We are excluded
		if ( empty( $point_types ) ) return $excluded;

		$point_types = cashcred_is_user_have_balances( $point_types, $user_id );

		//Insufficient points for withdrawal.
		if ( empty( $point_types ) ) return $insufficient;

		// From this moment on, we need to indicate the shortcode usage for scripts and styles.
		$cashcred_withdraw = true;

		// Button Label
		$button_label = $point_types[ current(array_keys($point_types)) ]->template_tags_general( $button );

		$cashcred_setting = mycred_get_cashcred_settings();

		ob_start();
			
		$pending_withdrawal = cashcred_get_withdraw_requests('Pending');


	?>
<div id="cashcred">
	<ul class="cashcred-nav-tabs">
		<li id="tab1" class="active"><?php esc_html_e( 'Withdraw Request', 'mycred' ); ?></li>
		<li id="tab2"><?php esc_html_e( 'Approved Requests', 'mycred' ); ?></li>
		<li id="tab3"><?php esc_html_e( 'Cancelled Requests', 'mycred' ); ?></li>
		<li id="tab4"><?php esc_html_e( 'Payment Settings', 'mycred' ); ?></li>
	</ul>
	<div id="cashcred_tab_content">
		<!--------First tab--------->
		<div id="tab1c" class="cashcred-tab">
			<?php cashcred_display_message(); ?>

			
			<?php if( count( $pending_withdrawal ) > 0 ){ ?>
			<h4><?php esc_html_e( 'You have pending withdrawal', 'mycred' ); ?></h4>
			<table>
				<thead class="cashcred-table-heading">
					<tr>
						<th>ID</th>
						<th>Points</th>
						<?php
							if ( ! empty( $cashcred_setting['fees']['types'] ) ) {
								if ( $cashcred_setting['fees']['use'] == 1 ) { ?>
									<th><span class="nobr"><?php esc_html_e( 'Fee', 'mycred' ) ?></span></th><?php
								}
							}?>



						<th><?php esc_html_e( 'Amount', 'mycred' ) ?></th>
						<th><?php esc_html_e( 'Point Type', 'mycred' ) ?></th>
						<th>
							<?php 
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							apply_filters( 'mycred_change_gateway_text', 'Gateway' ); 
							?>	
						</th>

						
						<th class="date-heading">Date</th>
					</tr>
				</thead>
				<tbody class="cashcred-table-content">
					<?php foreach( $pending_withdrawal as $post ):?>

						<?php $post->post_date = date('F d, Y, h:i A'); ?>
					<tr class="cashcred-table-content">
						<td><?php echo esc_html( $post->post_name ); ?></td>
						<td><?php echo esc_html( get_post_meta($post->ID,'points',true) );?></td>
						<?php 

						if ( ! empty( $cashcred_setting['fees']['types'] ) ) {
							
							if ( $cashcred_setting['fees']['use'] == 1 ) { ?>
							
								<td><?php 
															
									$type_data = $cashcred_setting['fees']['types'][get_post_meta($post->ID,'point_type',true)];
										
									if ( $type_data['by'] == 'percent' ) {
										$fee = !empty($type_data['amount']) ? ( ( $type_data['amount'] / 100 ) * (int)get_post_meta($post->ID,'points',true) ) : '';
									}
									else{
										$fee = $type_data['amount'];
									}

									if( $type_data['min_cap'] != 0 )
										$fee = $fee + $type_data['min_cap'];

									if( $type_data['max_cap'] != 0 && $fee > $type_data['max_cap'] )
										$fee = $type_data['max_cap'];

									echo esc_html( $fee ); ?>
								</td><?php
							} 
						}?>
						<td>

							<?php echo esc_html( get_post_meta($post->ID,'currency',true). " " .get_post_meta($post->ID,'points',true) * get_post_meta($post->ID,'cost',true) ); ?>
						</td>
						<td><?php echo esc_html( mycred_get_types()[get_post_meta($post->ID,'point_type',true)] );?></td>
						<td>
					 		<?php 
								$gateway = get_post_meta($post->ID,'gateway',true);
								$installed = $mycred_modules['solo']['cashcred']->get();
								if ( isset( $installed[ $gateway ] ) )
									echo esc_html( $installed[ $gateway ]['title'] );
								else
									echo esc_html( $gateway );
							?>
						</td>
						<td class="date-format"><?php echo esc_html( $post->post_date ); ?>
							
						</td>
					</tr>
					<?php endforeach;?>				
				</tbody>
			</table>	
			<?php } else {
                $mycred_cashcred_gateway_notice = apply_filters( 'mycred_cashcred_gateway_notice', 'Selected gateway details are incomplete.' );
			    ?>

			<div class="cashcred_gateway_notice"><?php esc_html_e( $mycred_cashcred_gateway_notice, 'mycred' ) ?></div>

			<form method="post" class="mycred-cashcred-form" action="">

				<?php wp_nonce_field( 'cashCred-withdraw-request', 'cashcred_withdraw_wpnonce' ); ?>
				
				<?php if( count( $point_types ) > 1 ) {?>
					<div class="form-group"> 
						<label for="gateway"><?php esc_html_e( 'Withdraw Point Type', 'mycred' ); ?></label>
						<select id="cashcred_point_type" name="cashcred_point_type" class="form-control">
							<?php 
								foreach( $point_types as $point_type_id => $point_type_obj ) {
									echo '<option value="' . esc_attr( $point_type_id ) . '">' . esc_html( $point_type_obj->plural() ) . '</option>'; 
								}
							?>
						</select>
					</div>
				<?php } else {?>
					<input type="hidden" id="cashcred_point_type" name="cashcred_point_type" value="<?php echo esc_attr( current(array_keys($point_types)) ); ?>" />
				<?php } ?>

				<?php if ( count( $gateways ) > 1 ) { ?>
					<div class="form-group"> 
						<label for="gateway"><?php esc_html_e( 'Withdrawal Payment Method', 'mycred' ); ?></label>
						<select id="cashcred_pay_method" name="cashcred_pay_method" class="form-control">
							<?php 
								foreach ( $gateways as $gateway_id => $gateway_data ) {
									echo '<option value="' . esc_attr( $gateway_id ) . '">' . esc_html( $gateway_data['title'] ) . '</option>';
								}
							?>
						</select>
					</div>
				<?php } else { ?>
					<input type="hidden" id="cashcred_pay_method" name="cashcred_pay_method" value="<?php echo esc_attr( current(array_keys($gateways)) ); ?>" />
				<?php } ?>

			<div class="form-group">  
				<label><?php echo sprintf( esc_html__('Withdraw %s value', 'mycred'), esc_html( $point_types[ current(array_keys($point_types)) ]->plural() ) ); ?></label>
				<?php 
					$amount = ! empty( $amount ) ? floatval( $amount ) : 0;
					
				?> 
				<input type="number" id="withdraw_points" name="points" class="form-control" placeholder="0" value="<?php echo ! empty($amount) ? esc_attr( $amount ) : 0; ?>" required />
				<p class="cashcred-min"><?php echo esc_html__('Minimum Amount: ', 'mycred');?><span></span></p>
				
				<?php 
				
				if ( ! empty( $cashcred_setting['fees'] ) ){

				 	if( $cashcred_setting['fees']['use'] == 1 ) { ?>
					
						<p class="cashcred-fee" ><?php echo esc_html__('Fee : ', 'mycred'); ?>
							
							<span></span>
							
						</p> <?php
					}

					$format = array();	
					foreach ($point_types as $key => $value) {
						$format[$key] = $point_types[$key]->core['format'];
						
					}

					wp_localize_script( 'cashcred-withdraw', 'cashcred_data', 
						array( 
							'cashcred_setting' => $cashcred_setting['fees'],
							'format' => $format,
						)
					);
					
				}
				?>
			</div>
			<div class="mycred-cashcred-withdraw-form-footer">
				<div id="cashcred_total" class="form-group">
					<strong>
						<span class="amount_label"><?php echo esc_html__( 'Amount:', 'mycred' ); ?></span>
						<span id="cashcred_currency_symbol"></span> 
						<span id="cashcred_total_amount"></span>
					</strong>
				</div>
				<div id="submit_button" class="form-group">
					<input type="submit" class="button" value="<?php echo esc_attr( $button_label ); ?>" />
				</div>
				<div class="mycred-clearfix"></div>
			</div>
		</form>
		<?php } ?>
		</div>
		<!--------End First tab--------->

		<!--------Secound tab--------->
		<div id="tab2c" class="cashcred-tab">
			<h4>Approved Requests</h4>
			<?php	
				$posts = cashcred_get_withdraw_requests('Approved');
			?>		
			<table>
				<thead>
					<tr>
						<th>ID</th>
						<th>Points</th>
						<?php
						if ( ! empty( $cashcred_setting['fees']['types'] ) ) {
							if ( $cashcred_setting['fees']['use'] == 1 ) { ?>
								<th><span class="nobr">Fee</span></th><?php
							}
						}?>
						<th><?php esc_html_e( 'Amount', 'mycred' ) ?></th>
						<th><?php esc_html_e( 'Point Type', 'mycred' ) ?></th>
						<th>
							<?php 
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							apply_filters( 'mycred_change_gateway_text', 'Gateway' ); 
							?>	
						</th>
						<th>Date</th>


					</tr>
				</thead>
				<tbody>
					<?php foreach($posts as $post) {
						
						$post->post_date = date('F d, Y, h:i A');
						?>
					<tr>
						<td><?php echo esc_html( $post->post_name ); ?></td>
						<td><?php echo esc_html( get_post_meta($post->ID,'points',true) );?></td>
						<?php 
						if ( ! empty( $cashcred_setting['fees']['types'] ) ) {
							
							if ( $cashcred_setting['fees']['use'] == 1 ) { ?>
							
								<td><?php 
															
									$type_data = $cashcred_setting['fees']['types'][get_post_meta($post->ID,'point_type',true)];
										
									if ( $type_data['by'] == 'percent' ) {
										$fee = !empty($type_data['amount']) ? ( ( $type_data['amount'] / 100 ) * (int)get_post_meta($post->ID,'points',true) ) : '';
									}
									else{
										$fee = $type_data['amount'];
									}

									if( $type_data['min_cap'] != 0 )
										$fee = $fee + $type_data['min_cap'];

									if( $type_data['max_cap'] != 0 && $fee > $type_data['max_cap'] )
										$fee = $type_data['max_cap'];

									echo esc_html( $fee ); ?>
								</td><?php
							} 
						}?>
						<td>
							<?php echo esc_html( get_post_meta($post->ID,'currency',true). " " .get_post_meta($post->ID,'points',true) * get_post_meta($post->ID,'cost',true) );?>
						</td>
						<td><?php echo esc_html( mycred_get_types()[get_post_meta($post->ID,'point_type',true)] ); ?></td>
						<td>
							<?php 
								$gateway = get_post_meta($post->ID,'gateway',true);
								$installed = $mycred_modules['solo']['cashcred']->get();
								if ( isset( $installed[ $gateway ] ) )
									echo esc_html( $installed[ $gateway ]['title'] );
								else
									echo esc_html( $gateway );
							?>
						</td>
						<td><?php echo esc_html( $post->post_date ); ?></td>
					</tr>
					<?php } ?>
		 		</tbody>
			</table>
		</div>
		<!--------End Secound tab--------->

		<!--------Third tab--------->
		<div id="tab3c" class="cashcred-tab">
			<h4>Cancelled Requests</h4>
			<?php
				$posts = cashcred_get_withdraw_requests('Cancelled');
			?>
			
			<table>
				<thead>
					<tr>
						<th><span class="nobr"><?php esc_html_e( 'ID', 'mycred' ) ?></span></th>
						<th><span class="nobr"><?php esc_html_e(  'Points', 'mycred' ) ?></span></th>
						<?php
						if ( ! empty( $cashcred_setting['fees']['types'] ) ) {
							if ( $cashcred_setting['fees']['use'] == 1 ) { ?>
								<th><span class="nobr">Fee</span></th><?php
							}
						}?>
						<th><?php esc_html_e(  'Amount', 'mycred' ) ?></th>
						<th><?php esc_html_e(  'Point Type', 'mycred' ) ?></th>
						<th>
							<?php 
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							apply_filters( 'mycred_change_gateway_text', 'Gateway' ); 
							?>			
						</th>
						<th><?php esc_html_e( 'Date', 'mycred' ) ?></th>

					</tr>
				</thead>
				<tbody>
					<?php foreach($posts as $post) {

						$post->post_date = date('F d, Y, h:i A');

						?>
					<tr>
					 	<td><?php echo esc_html( $post->post_name ); ?></td>
						
						<td><?php echo esc_html( get_post_meta($post->ID,'points',true ) );?></td>
						<?php 
						if ( ! empty( $cashcred_setting['fees']['types'] ) ) {
							
							if ( $cashcred_setting['fees']['use'] == 1 ) { ?>
							
								<td><?php 
															
									$type_data = $cashcred_setting['fees']['types'][get_post_meta($post->ID,'point_type',true)];
										
									if ( $type_data['by'] == 'percent' ) {
										$fee = ( ( $type_data['amount'] / 100 ) * (int)get_post_meta($post->ID,'points',true) );
									}
									else{
										$fee = $type_data['amount'];
									}

									if( $type_data['min_cap'] != 0 )
										$fee = $fee + $type_data['min_cap'];

									if( $type_data['max_cap'] != 0 && $fee > $type_data['max_cap'] )
										$fee = $type_data['max_cap'];

									echo esc_html( $fee ); ?>
								</td><?php
							} 
						}?>
						<td>
							<?php echo esc_html( get_post_meta($post->ID,'currency',true). " " .get_post_meta($post->ID,'points',true) * get_post_meta($post->ID,'cost',true) );?>
						</td>
						<td><?php echo esc_html( mycred_get_types()[get_post_meta($post->ID,'point_type',true)] );?></td>
						<td>
						<?php 
							$gateway = get_post_meta($post->ID,'gateway',true);
							$installed = $mycred_modules['solo']['cashcred']->get();
							if ( isset( $installed[ $gateway ] ) )
								echo esc_html( $installed[ $gateway ]['title'] );
							else
								echo esc_html( $gateway );
							?>
						</td>
						<td><?php echo esc_html( $post->post_date ); ?></td>
					</tr>
					<?php } ?>
				</tbody>
			</table>
		</div>
		<!--------End Third tab--------->

		<!--------Fourth tab--------->
		<div id="tab4c" class="cashcred-tab">
			<form action="" method="POST">
				<?php if ( count( $gateways ) > 1 ):?>
					<select class="form-control" name="cashcred_save_settings" id="cashcred_save_settings">
						<?php 
							foreach ( $gateways as $key => $active_gateways_value ) {
								echo '<option value="' . esc_attr( $key ). '"> '. esc_html( $active_gateways_value['title'] ) .' </option>';
							}
						?>
					</select>
				<?php else:?>
					<input type="hidden" name="cashcred_save_settings" id="cashcred_save_settings" value="<?php echo esc_attr( current(array_keys($gateways)) ); ?>" />
				<?php endif;?>
				<?php 
					wp_nonce_field( 'cashCred-payment-settings', 'cashcred_settings_wpnonce' );

				    foreach ( $gateways as $key => $active_gateways_value ) {
						
						$MyCred_payment_setting_call = new $active_gateways_value['callback'][0]($key);
						$MyCred_payment_setting_call->cashcred_payment_settings($key) ;
							
					}
				?>
				<div id="cashcred_save_settings" class="form-group">
					<input type="submit" class="button" value="Save" />
				</div>
			</form>	
		</div>
		<!--------End Fourth tab--------->
	</div>
</div>
<?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}
endif;