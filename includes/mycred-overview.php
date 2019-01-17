<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * Dashboard Widget: Overview
 * @see https://codex.wordpress.org/Example_Dashboard_Widget
 * @since 1.3.3
 * @version 1.3
 */
add_action( 'wp_dashboard_setup', array( 'myCRED_Dashboard_Widget_Overview', 'init' ) );
if ( ! class_exists( 'myCRED_Dashboard_Widget_Overview' ) ) {
	class myCRED_Dashboard_Widget_Overview {

		const mycred_wid = 'mycred_overview';

		/**
		 * Init Widget
		 */
		public static function init() {
			if ( ! current_user_can( apply_filters( 'mycred_overview_capability', 'edit_users' ) ) ) return;

			// Add widget
			wp_add_dashboard_widget(
				self::mycred_wid,
				sprintf( __( '%s Overview', 'mycred' ), mycred_label() ),
				array( 'myCRED_Dashboard_Widget_Overview', 'widget' )
			);
		}

		/**
		 * Widget output
		 */
		public static function widget() {

			global $wpdb;

			$types = mycred_get_types();

?>
<style type="text/css">
#mycred_overview .inside { margin: 0; padding: 0; }
div.overview-module-wrap { margin: 0; padding: 0; }

div.overview-module-wrap div.module-title { line-height: 48px; height: 48px; font-size: 18px; border-bottom: 1px solid #eee; }
div.overview-module-wrap div.module-title a { float: right; padding-right: 12px; }
div.overview-module-wrap div.module-title .type-icon { display: block; width: 48px; height: 48px; float: left; line-height: 48px; text-align: center; }
div.overview-module-wrap div.module-title .type-icon > div { line-height: inherit; }
div.overview-module-wrap div.mycred-type { border-top: 1px solid #ddd; }
div.overview-module-wrap div.mycred-type.first { border-top: none; }
div.overview-module-wrap div.mycred-type .overview { padding: 0; float: none; clear: both; margin-bottom: -1px; }
div.overview-module-wrap div.mycred-type .overview .section { height: 48px; float: left; margin: 0; border-right: 1px solid #eee; }
div.overview-module-wrap div.mycred-type .overview .section.border { border-bottom: 1px solid #eee; }
div.overview-module-wrap div.mycred-type .overview .section.dimm p { opacity: 0.3; }
div.overview-module-wrap div.mycred-type .overview .section:last-child { border-right: none; }
div.overview-module-wrap div.mycred-type .overview .section strong { padding: 0 6px 0 12px; }
</style>
<div class="overview-module-wrap clear">
<?php

			do_action( 'mycred_overview_before', $types );

			$counter = 0;
			foreach ( $types as $type => $label ) {

				$mycred = mycred( $type );

				$page = 'mycred';
				if ( $type != MYCRED_DEFAULT_TYPE_KEY )
					$page .= '_' . $type;

				$url       = admin_url( 'admin.php?page=' . $page );
				$total     = $wpdb->get_var( "SELECT SUM( meta_value ) FROM {$wpdb->usermeta} WHERE meta_key = '{$type}';" );

				$gained    = $wpdb->get_var( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE creds > 0 AND ctype = '{$type}';" );
				$gain_url  = add_query_arg( array( 'num' => 0, 'compare' => urlencode( '>' ) ), $url );

				$lost      = $wpdb->get_var( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE creds < 0 AND ctype = '{$type}';" );
				$loose_url = add_query_arg( array( 'num' => 0, 'compare' => urlencode( '<' ) ), $url );

?>
	<div class="mycred-type clear<?php if ( $counter == 0 ) echo ' first'; ?>">
		<div class="module-title"><div class="type-icon"><div class="dashicons dashicons-star-filled"></div></div><?php echo $mycred->plural(); ?><a href="<?php echo $url; ?>" title="<?php _e( 'Total amount in circulation', 'mycred' ); ?>"><?php echo $mycred->format_creds( $total ); ?></a></div>
		<div class="overview clear">
			<div class="section border" style="width: 50%;">
				<p><strong style="color:green;"><?php _e( 'Awarded', 'mycred' ); ?>:</strong> <a href="<?php echo esc_url( $gain_url ); ?>"><?php echo $mycred->format_creds( $gained ); ?></a></p>
			</div>
			<div class="section border" style="width: 50%; margin-left: -1px;">
				<p><strong style="color:red;"><?php _e( 'Deducted', 'mycred' ); ?>:</strong> <a href="<?php echo esc_url( $loose_url ); ?>"><?php echo $mycred->format_creds( $lost ); ?></a></p>
			</div>
		</div>
	</div>
<?php
				$counter++;

			}

			do_action( 'mycred_overview_after', $types );

?>
	<div class="clear"></div>
</div>
<?php

		}

	}

}

?>