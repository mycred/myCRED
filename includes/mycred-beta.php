<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * BETA: Check for Updates
 * Calls home to the myCRED repository for new beta versions.
 * @version 1.0
 */
function mycred_beta_check_for_update( $checked_data ) {

	global $wp_version;

	if ( empty( $checked_data->checked ) )
		return $checked_data;

	$args = array(
		'slug'    => 'mycred',
		'version' => myCRED_VERSION,
		'site'    => site_url()
	);
	$request_string = array(
		'body'       => array(
			'action'     => 'version', 
			'request'    => serialize( $args ),
			'api-key'    => md5( get_bloginfo( 'url' ) )
		),
		'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
	);

	// Start checking for an update
	$response = wp_remote_post( 'https://mycred.me/api/plugins/', $request_string );

	if ( ! is_wp_error( $response ) ) {

		$result = maybe_unserialize( $response['body'] );

		if ( is_object( $result ) && ! empty( $result ) )
			$checked_data->response['mycred/mycred.php'] = $result;

	}

	return $checked_data;

}
add_filter( 'pre_set_site_transient_update_plugins', 'mycred_beta_check_for_update' );

/**
 * BETA: API Call
 * @version 1.0
 */
function mycred_beta_api_call( $result, $action, $args ) {

	global $wp_version, $wpdb;

	if ( ! isset( $args->slug ) || ( $args->slug != 'mycred' ) )
		return $result;

	// Get the current version
	$args = array(
		'slug'        => 'mycred',
		'version'     => myCRED_VERSION,
		'site'        => site_url(),
		'environment' => array(
			'wordpress'   => $wp_version,
			'multisite'   => ( ( function_exists( 'is_multisite' ) && is_multisite() ) ? 1 : 0 ),
			'php'         => phpversion(),
			'mysql'       => $wpdb->db_version()
		)
	);
	$request_string = array(
		'body'       => array(
			'action'     => 'info', 
			'request'    => serialize( $args ),
			'api-key'    => md5( get_bloginfo( 'url' ) )
		),
		'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' )
	);

	$request = wp_remote_post( 'https://mycred.me/api/plugins/', $request_string );

	if ( ! is_wp_error( $request ) )
		$result = maybe_unserialize( $request['body'] );

	return $result;

}
add_filter( 'plugins_api', 'mycred_beta_api_call', 10, 3 );

/**
 * BETA: Get Info
 * Override the get information popup with our custom one.
 * @version 1.0
 */
function mycred_beta_view_plugin_info( $plugin_meta, $file, $plugin_data ) {

	if ( $file != plugin_basename( myCRED_THIS ) ) return $plugin_meta;

	$plugin_meta[] = sprintf( '<a href="%s" class="thickbox" aria-label="%s" data-title="%s">%s</a>',
		esc_url( network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=mycred&TB_iframe=true&width=600&height=550' ) ),
		esc_attr( 'More information about this plugin' ),
		esc_attr( sprintf( 'myCRED %s', myCRED_VERSION ) ),
		'View details'
	);

	return $plugin_meta;

}
add_filter( 'plugin_row_meta', 'mycred_beta_view_plugin_info', 10, 3 );
