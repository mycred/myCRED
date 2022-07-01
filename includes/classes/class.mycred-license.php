<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_License class
 * Used for the addons license and update.
 * @since 2.3
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_License' ) ) :
	class myCRED_License {

		// Plugin Version
		private $version;

		// Plugin Slug
		private $slug;

		private $base;

		private $license;

		private $license_key_name;

		private $cache_key_name;

		private $api_base_url;

		/**
		 * Construct
		 */
		public function __construct( $data ) { 

			$this->version           = $data['version'];
			$this->slug              = $data['slug'];
			$this->base              = $data['base'];
			$this->filename          = plugin_basename( $this->base );
			$this->license_key_name  = 'mycred_membership_key';
			$this->transient_key     = 'mcl_' . md5( $this->slug );
			$this->api_endpoint      = 'https://license.mycred.me/wp-json/license/get-plugins';

			$this->init();

		}

		public function get_plugin_detail( $force = false ) {

			$plugin_info = get_site_transient( $this->transient_key );

			if ( false === $plugin_info || $force ) {
				
				$plugins_info_remote = $this->get_api_data();

				foreach ( $plugins_info_remote as $plugin ) {
					
					if ( ! empty( $plugin->package ) ) {
						
						$plugin->package = add_query_arg( 
							array( 
						        'license_key' => $this->get_license_key(),
						        'site'        => get_bloginfo( 'url' ),
								'api-key'     => md5( get_bloginfo( 'url' ) ),
								'slug'        => $plugin->slug
						    ), 
							$plugin->package 
						);

					}

					$transient_key = 'mcl_' . md5( $plugin->slug );
					set_site_transient( $transient_key, $plugin, 5 * HOUR_IN_SECONDS );

					if ( $plugin->slug == $this->slug ) 
						$plugin_info = $plugin;
				
				}

			}

			return $plugin_info;

		}

		public function init() {

			add_filter( 'mycred_license_addons',                 array( $this, 'register_addon_for_license' ) );
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ), 80 );
			add_filter( 'plugins_api', 							 array( $this, 'plugin_api_call' ), 80, 3 );
			add_filter( 'plugin_row_meta', 						 array( $this, 'plugin_view_info' ), 80, 3 );

		}

		public function register_addon_for_license( $addons ) {
			
			array_push( $addons, $this->slug );

			return $addons;

		}

		public function check_update( $data ) {

			$plugin_info = $this->get_plugin_detail();

			if ( ! empty( $plugin_info ) && ! empty( $plugin_info->new_version ) ) {
				
				if ( version_compare( $this->version, $plugin_info->new_version, '<' ) ) {
	                $data->response[ $this->filename ] = $plugin_info;
	            } else {
	                $data->no_update[ $this->filename ] = $plugin_info;
	            }

			}

	        $data->last_checked = time();
	        $data->checked[ $this->filename ] = $this->version;

			return $data;

		}

		public function plugin_api_call( $result, $action, $args ) {

			if ( empty( $args->slug ) || $args->slug != $this->slug ) 
				return $result;

			$data = $this->get_plugin_detail();

			if ( isset( $data->banners ) )
				$data->banners = (array) $data->banners;

			if ( isset( $data->sections ) ) {
				
				if ( ! empty( $data->sections->description ) )
					$data->sections->description = html_entity_decode( $data->sections->description );
				
				if ( ! empty( $data->sections->change_log ) )
					$data->sections->change_log = html_entity_decode( $data->sections->change_log );
				
				if ( ! empty( $data->sections->installation ) )
					$data->sections->installation = html_entity_decode( $data->sections->installation );

				$data->sections = (array) $data->sections;

			}

			return $data;

		}

		public function plugin_view_info( $plugin_meta, $file, $plugin_data ) {

			if ( $file != plugin_basename( $this->base ) ) return $plugin_meta;

			$plugin_info = $this->get_plugin_detail();

			if ( empty( $plugin_info->package ) && ( empty( $plugin_info->expiry ) || empty( $plugin_info->expiry->expiration_date ) ) ) {
				
				$message = 'License not found.';

				if ( ! empty( $plugin_info->message ) ) {
					$message = $plugin_info->message;
				}
				
				$plugin_meta[] = '<a href="http://mycred.me/about/terms/#product-licenses" style="color:red;" target="_blank"><strong>' . $message . '</strong></a>';

			}
			else {

				$plugin_meta[] = '<strong style="color:green;">Expires in ' . $this->calculate_license_expiry( $plugin_info->expiry->expiration_date ) . '</strong>';

			}


			return $plugin_meta;

		}

		public function get_mycred_addons() {

			return apply_filters( 'mycred_license_addons', array() );

		}

		public function get_license_key() {

			return get_option( $this->license_key_name );

		}

		public function get_api_data() {

			$cache_key    = 'mycred_license_remote_data';
		    $plugins_data = wp_cache_get( $cache_key );
		    
		    if ( false === $plugins_data ) {

				$plugins_data = new stdClass();
		        $license_key  = $this->get_license_key();
				$addons       = $this->get_mycred_addons();
				$request_args = array(
					'body' => array(
						'license_key' => $license_key,
						'site'        => get_bloginfo( 'url' ),
						'api-key'     => md5( get_bloginfo( 'url' ) ),
						'addons'      => $addons
					),
					'timeout' => 12
				);

				// Start checking for an update
				$response = wp_remote_post( $this->api_endpoint, $request_args );

				if ( ! is_wp_error( $response ) ) {

					$response_data = json_decode( $response['body'] );

					if ( ! empty( $response_data->status ) && $response_data->status == 'success' ) {
						
						$plugins_data = $response_data->data;

					}

				}
		  
		    }

	        wp_cache_set( $cache_key, $plugins_data );
			return $plugins_data;
			
		}

		public function calculate_license_expiry( $expire_date ) {

			$interval = date_create('now')->diff( date_create( $expire_date ) );

			$label_y = $interval->y > 1 ? "{$interval->y} years " : ( $interval->y == 1 ? "{$interval->y} year " : '' );
			$label_m = $interval->m > 1 ? "{$interval->m} months " : ( $interval->m == 1 ? "{$interval->m} month " : '' );
			$label_d = $interval->d > 1 ? "{$interval->d} days " : ( $interval->d == 1 ? "{$interval->d} day " : '' );

			return "{$label_y}{$label_m}{$label_d}";

		}
		

	}
endif;