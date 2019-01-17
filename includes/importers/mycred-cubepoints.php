<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Import: CubePoint Balances
 * @since 1.2
 * @version 1.2
 */
if ( class_exists( 'WP_Importer' ) ) :
	class myCRED_Importer_CubePoints extends WP_Importer {

		var $id;
		var $file_url;
		var $import_page;
		var $delimiter;
		var $posts = array();
		var $imported;
		var $skipped;

		/**
		 * Construct
		 */
		public function __construct() {

			$this->import_page = 'mycred_import_cp';

		}

		/**
		 * Run the importer based on current step
		 */
		function load() {

			$this->header();

			$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
			switch ( $step ) {

				case 0 :

					$this->greet();

				break;

				case 1 :

					if ( $this->check_cubepoints() ) {

						@set_time_limit(0);
						@ob_flush();
						@flush();

						$this->import();
					}

				break;

			}

			$this->footer();

		}

		/**
		 * import function.
		 */
		function import() {

			global $wpdb, $mycred;

			$this->imported = $this->skipped = 0;

			$action = $_POST['action'];
			$type   = $_POST['type'];
			$cp_log = $wpdb->prefix . 'cp';

			if ( $type != MYCRED_DEFAULT_TYPE_KEY )
				$mycred = mycred( $type );

			if ( $mycred->format['decimals'] > 0 )
				$format = '%f';
			elseif ( $mycred->format['decimals'] == 0 )
				$format = '%d';
			else
				$format = '%s';

			// Import Log
			if ( $action == 'log' || $action == 'both' ) {

				$entries = $wpdb->get_results( "SELECT * FROM {$cp_log};" );
				if ( ! empty( $entries ) ) {
					foreach ( $entries as $entry ) {

						$ref_id = false;
						if ( $entry->type == 'comment' ) {

							$ref = 'approved_comment';
							$log = '%plural% for approved comment';
							$data = array( 'ref_type' => 'comment' );

						}
						elseif ( $entry->type == 'comment_remove' ) {

							$ref = 'unapproved_comment';
							$log = '%plural% for deleted comment';
							$data = array( 'ref_type' => 'comment' );

						}
						elseif ( $entry->type == 'post' ) {

							$ref = 'publishing_content';
							$log = '%plural% for publishing content';
							$data = array( 'ref_type' => 'post' );

						}
						elseif ( $entry->type == 'register' ) {

							$ref = 'registration';
							$log = '%plural% for registration';
							$data = '';

						}
						elseif ( $entry->type == 'addpoints' ) {

							$ref = 'manual';
							$log = '%plural% via manual adjustment';
							$data = array( 'ref_type' => 'user' );

						}
						elseif ( $entry->type == 'dailypoints' ) {

							$ref = 'payout';
							$log = 'Daily %plural%';
							$data = '';

						}
						elseif ( $entry->type == 'donate_from' ) {

							$ref = 'transfer';

							$data = maybe_unserialize( $entry->data );
							if ( isset( $data['to'] ) )
								$ref_id = absint( $data['to'] );

							$log = 'Transfer from %display_name%';
							$data = array(
								 'ref_type' => 'user',
								 'tid'      => 'TXID' . $entry->timestamp . $entry->uid 
							);

						}
						elseif ( $entry->type == 'donate_to' ) {

							$ref = 'transfer';

							$data = maybe_unserialize( $entry->data );
							if ( isset( $data['to'] ) )
								$ref_id = absint( $data['to'] );

							$log = 'Transfer to %display_name%';
							$data = array(
								 'ref_type' => 'user',
								 'tid'      => 'TXID' . $entry->timestamp . $entry->uid 
							);

						}
						elseif ( $entry->type == 'pcontent' ) {

							$ref = 'buy_content';

							$log = 'Purchase of %link_with_title%';
							$ref_id = absint( $entry->data );

							$data = array(
								'ref_type'    => 'post',
								'purchase_id' => 'TXID' . $entry->timestamp
							);

						}
						elseif ( $entry->type == 'pcontent_author' ) {

							$ref = 'buy_content';

							$log = 'Sale of %link_with_title%';

							$data = maybe_unserialize( $entry->data );
							$ref_id = absint( $data[0] );

							$data = array(
								'ref_type'    => 'post',
								'purchase_id' => 'TXID' . $entry->timestamp,
								'buyer'       => $data[1]
							);

						}
						elseif ( $entry->type == 'paypal' ) {

							$ref = 'buy_creds_with_paypal_standard';

							$log = '%plural% purchase';

							$data = maybe_unserialize( $entry->data );
							$data = array(
								'txn_id'       => $data['txn_id'],
								'payer_id'     => $data['payer_email']
							);

						}
						elseif ( $entry->type == 'post_comment' ) {

							$ref = 'approved_comment';
							$log = '%plural% for approved comment';
							$data = array( 'ref_type' => 'comment' );

						}
						elseif ( $entry->type == 'post_comment_remove' ) {

							$ref = 'unapproved_comment';
							$log = '%plural% for deleted comment';
							$data = array( 'ref_type' => 'comment' );

						}
						elseif ( $entry->type == 'youtube' ) {

							$ref = 'watching_video';
							$log = '%plural% for viewing video';
							$data = absint( $entry->data );

						}
						else {
							$this->skipped ++;
							continue;
						}

						$entry_data = maybe_unserialize( $entry->data );
						if ( $ref_id === false && ! empty( $entry_data ) && ! is_array( $entry_data ) )
							$ref_id = absint( $entry->data );

						if ( $ref_id === false )
							$ref_id = 0;

						$wpdb->insert(
							$mycred->log_table,
							array(
								'ref'     => $ref,
								'ref_id'  => $ref_id,
								'user_id' => absint( $entry->uid ),
								'creds'   => $entry->points,
								'ctype'   => $type,
								'time'    => $entry->timestamp,
								'entry'   => $log,
								'data'    => ( is_array( $data ) || is_object( $data ) ) ? serialize( $data ) : $data
							),
							array(
								'%s',
								'%d',
								'%d',
								$format,
								'%s',
								'%d',
								'%s',
								( is_numeric( $data ) ) ? '%d' : '%s'
							)
						);

						$this->imported++;

					}

				}

			}

			if ( $action == 'balance' || $action == 'both' ) {

				$wpdb->delete(
					$wpdb->usermeta,
					array( 'meta_key' => $type ),
					array( '%s' )
				);

				$rows = $wpdb->query( $wpdb->prepare( "
					INSERT INTO {$wpdb->usermeta} ( user_id, meta_key, meta_value )
					SELECT um.user_id, %s, um.meta_value
					FROM {$wpdb->usermeta} um 
					WHERE um.meta_key = %s;
				", $type, 'cpoints' ) );

				$this->imported = $rows;

			}

			// Show Result
			if ( $this->imported == 0 ) {

				echo '
<div class="updated below-h2">
	<p>' . ( $action == 'balance' ) ? __( 'No balances were imported.', 'mycred' ) : __( 'No log entries were imported!', 'mycred' ) . '</p>
</div>';

			}
			else {

				echo '
<div class="updated below-h2">
	<p>' . sprintf( __( 'Import complete - A total of <strong>%d</strong> entries were successfully imported. <strong>%d</strong> was skipped.', 'mycred' ), $this->imported, $this->skipped ) . '</p>
</div>';

			}

			$this->import_end();

		}

		/**
		 * Adds link to the log after completed import
		 */
		function import_end() {

			echo '<p><a href="' . admin_url( 'admin.php?page=myCRED' ) . '" class="button button-large button-primary">' . __( 'View Log', 'mycred' ) . '</a> <a href="' . admin_url( 'import.php' ) . '" class="button button-large button-primary">' . __( 'Import More', 'mycred' ) . '</a></p>';

			do_action( 'import_end' );

		}

		/**
		 * Checks CubePoints Installation
		 */
		function check_cubepoints() {

			global $wpdb;

			$cp_log = $wpdb->prefix . 'cp';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$cp_log}'" ) != $cp_log ) {
				echo '<p><strong>' . __( 'No CubePoints log.', 'mycred' ) . '</strong><br />';
				return false;
			}

			return true;

		}

		/**
		 * header function.
		 */
		function header() {

			echo '<div class="wrap"><h2>' . __( 'Import CubePoints Log', 'mycred' ) . '</h2>';

		}

		/**
		 * footer function.
		 */
		function footer() {

			echo '</div>';

		}

		/**
		 * greet function.
		 */
		function greet() {

			global $wpdb, $mycred;

			$actions = array(
				''        => __( 'Select what to import', 'mycred' ),
				'log'     => __( 'Log Entries Only', 'mycred' ),
				'balance' => __( 'CubePoints Balances Only', 'mycred' ),
				'both'    => __( 'Log Entries and Balances', 'mycred' )
			);

			$action = 'admin.php?import=mycred_import_cp&step=1';
			$mycred_types = mycred_get_types();

			echo '<div class="narrow">';
			echo '<p>' . __( 'Import CubePoints log entries and / or balances.', 'mycred' ).'</p>';

?>
<form id="import-setup" method="post" action="<?php echo esc_attr( wp_nonce_url( $action, 'import-upload' ) ); ?>">
	<table class="form-table">
		<tbody>
			<tr>
				<th>
					<label for="import-action"><?php _e( 'Import', 'mycred' ); ?></label>
				</th>
				<td>
					<select name="action" id="import-action"><?php

			foreach ( $actions as $action => $label )
				echo '<option value="' . $action . '">' . $label . '</option>';

?></select><br />
					<span class="description"><?php _e( 'Warning! Importing CubePoints balances will replace your users myCRED balance!', 'mycred' ); ?></span>
				</td>
			</tr>
			<tr>
				<th>
					<label for="import-action"><?php _e( 'Point Type', 'mycred' ); ?></label>
				</th>
				<td>

					<?php if ( count( $mycred_types ) == 1 ) : ?>

					<strong><?php echo $mycred->plural(); ?></strong><input type="hidden" name="type" value="mycred_default" />

					<?php else : ?>

					<?php mycred_types_select_from_dropdown( 'type', 'mycred-type', MYCRED_DEFAULT_TYPE_KEY ); ?>

					<?php endif; ?>

				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input type="submit" class="button" value="<?php esc_attr_e( 'Import Log' ); ?>" />
	</p>
</form>
<?php

			echo '</div>';

		}

	}
endif;

?>