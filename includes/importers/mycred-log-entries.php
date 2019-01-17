<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Import: Log Entries
 * @since 1.2
 * @version 1.2
 */
if ( class_exists( 'WP_Importer' ) ) :
	class myCRED_Importer_Log_Entires extends WP_Importer {

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

			$this->import_page = 'mycred_import_log';

		}

		/**
		 * Registered callback function for the WordPress Importer
		 * Manages the three separate stages of the CSV import process
		 */
		function load() {

			$this->header();

			if ( ! empty( $_POST['delimiter'] ) )
				$this->delimiter = stripslashes( trim( $_POST['delimiter'] ) );

			if ( ! $this->delimiter )
				$this->delimiter = ',';

			$step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
			switch ( $step ) {

				case 0 :

					$this->greet();

				break;

				case 1 :

					check_admin_referer( 'import-upload' );

					if ( $this->handle_upload() ) {

						if ( $this->id )
							$file = get_attached_file( $this->id );
						else
							$file = ABSPATH . $this->file_url;

						add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );

						if ( function_exists( 'gc_enable' ) )
							gc_enable();

						@set_time_limit(0);
						@ob_flush();
						@flush();

						$this->import( $file );

					}

				break;

			}

			$this->footer();

		}

		/**
		 * format_data_from_csv function.
		 */
		function format_data_from_csv( $data, $enc ) {

			return ( $enc == 'UTF-8' ) ? $data : utf8_encode( $data );

		}

		/**
		 * import function.
		 */
		function import( $file ) {

			global $wpdb, $mycred;

			$this->imported = $this->skipped = 0;

			if ( ! is_file( $file ) ) {

				echo '<p><strong>' . __( 'Sorry, there has been an error.', 'mycred' ) . '</strong><br />';
				echo __( 'The file does not exist, please try again.', 'mycred' ) . '</p>';

				$this->footer();

				die;

			}

			ini_set( 'auto_detect_line_endings', '1' );

			if ( ( $handle = fopen( $file, "r" ) ) !== FALSE ) {

				$header = fgetcsv( $handle, 0, $this->delimiter );

				if ( sizeof( $header ) == 8 ) {

					$loop = 0;

					while ( ( $row = fgetcsv( $handle, 0, $this->delimiter ) ) !== FALSE ) {

						list( $ref, $ref_id, $user_id, $creds, $ctype, $time, $entry, $data ) = $row;

						if ( empty( $ref ) || empty( $user_id ) || empty( $creds ) || $creds === 0 || empty( $time ) ) {
							$this->skipped ++;
							continue;
						}

						$wpdb->insert(
							$mycred->log_table,
							array(
								'ref'     => $ref,
								'ref_id'  => absint( $ref_id ),
								'user_id' => absint( $user_id ),
								'creds'   => $mycred->number( $creds ),
								'ctype'   => sanitize_text_field( $ctype ),
								'time'    => absint( $time ),
								'entry'   => trim( $entry ),
								'data'    => maybe_serialize( $data )
							)
						);

						$loop ++;
						$this->imported++;

					}

				} else {

					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'mycred' ) . '</strong><br />';
					echo __( 'The CSV is invalid.', 'mycred' ) . '</p>';

					$this->footer();

					die;

				}

				fclose( $handle );

			}

			// Show Result
			echo '<div class="updated settings-error below-h2"><p>
				'.sprintf( __( 'Import complete - A total of <strong>%d</strong> entries were successfully imported. <strong>%d</strong> was skipped.', 'mycred' ), $this->imported, $this->skipped ).'
			</p></div>';

			$this->import_end();

		}

		/**
		 * Performs post-import cleanup of files and the cache
		 */
		function import_end() {

			echo '<p><a href="' . admin_url( 'admin.php?page=myCRED' ) . '" class="button button-large button-primary">' . __( 'View Log', 'mycred' ) . '</a> <a href="' . admin_url( 'import.php' ) . '" class="button button-large button-primary">' . __( 'Import More', 'mycred' ) . '</a></p>';

			do_action( 'import_end' );

		}

		/**
		 * Handles the CSV upload and initial parsing of the file to prepare for
		 * displaying author import options
		 * @return bool False if error uploading or invalid file, true otherwise
		 */
		function handle_upload() {

			if ( empty( $_POST['file_url'] ) ) {

				$file = wp_import_handle_upload();

				if ( isset( $file['error'] ) ) {

					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'mycred' ) . '</strong><br />';
					echo esc_html( $file['error'] ) . '</p>';

					return false;

				}

				$this->id = (int) $file['id'];

			} else {

				if ( file_exists( ABSPATH . $_POST['file_url'] ) ) {

					$this->file_url = esc_attr( $_POST['file_url'] );

				} else {

					echo '<p><strong>' . __( 'Sorry, there has been an error.', 'mycred' ) . '</strong></p>';
					return false;

				}

			}

			return true;

		}

		/**
		 * header function.
		 */
		function header() {

			echo '<div class="wrap"><h2>' . __( 'Import Log Entries', 'mycred' ) . '</h2>';

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

			global $mycred;

			echo '<div class="narrow">';
			echo '<p>' . __( 'Import log entries from a CSV file.', 'mycred' ).'</p>';

			$action = 'admin.php?import=mycred_import_log&step=1';

			$bytes      = apply_filters( 'import_upload_size_limit', wp_max_upload_size() );
			$size       = size_format( $bytes );
			$upload_dir = wp_upload_dir();
			if ( ! empty( $upload_dir['error'] ) ) :

?>
<div class="error"><p><?php _e( 'Before you can upload your import file, you will need to fix the following error:', 'mycred' ); ?></p>
<p><strong><?php echo $upload_dir['error']; ?></strong></p></div>
<?php

			else :

?>
<form enctype="multipart/form-data" id="import-upload-form" method="post" action="<?php echo esc_attr( wp_nonce_url( $action, 'import-upload' ) ); ?>">
	<table class="form-table">
		<tbody>
			<tr>
				<th>
					<label for="upload"><?php _e( 'Choose a file from your computer:', 'mycred' ); ?></label>
				</th>
				<td>
					<input type="file" id="upload" name="import" size="25" />
					<input type="hidden" name="action" value="save" />
					<input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
					<small><?php printf( __( 'Maximum size: %s', 'mycred' ), $size ); ?></small>
				</td>
			</tr>
			<tr>
				<th>
					<label for="file_url"><?php _e( 'OR enter path to file:', 'mycred' ); ?></label>
				</th>
				<td>
					<?php echo ' ' . ABSPATH . ' '; ?><input type="text" id="file_url" name="file_url" size="25" />
				</td>
			</tr>
			<tr>
				<th><label><?php _e( 'Delimiter', 'mycred' ); ?></label><br/></th>
				<td><input type="text" name="delimiter" placeholder="," size="2" /></td>
			</tr>
		</tbody>
	</table>
	<p class="submit">
		<input type="submit" class="button" value="<?php esc_attr_e( 'Upload file and import' ); ?>" />
	</p>
</form>
<?php

			endif;

			echo '</div>';

		}

		/**
		 * Added to http_request_timeout filter to force timeout at 60 seconds during import
		 * @return int 60
		 */
		function bump_request_timeout( $val ) {

			return 60;

		}

	}
endif;

?>