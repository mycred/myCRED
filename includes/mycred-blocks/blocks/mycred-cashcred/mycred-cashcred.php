<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_cashcred_block') ) :
    class mycred_cashcred_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-cashcred', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            $mycred_cashcred_gateways = array( '' => __( 'Select Gateways', 'mycred' ) );

            foreach( cashcred_get_usable_gateways( array() ) as $id => $gateway ) {

                $mycred_cashcred_gateways[ $id ] = $gateway['title'];

            }

            wp_enqueue_script(
                'mycred-cashcred', 
                plugins_url('index.js', __FILE__), 
                array( 
                    'wp-blocks', 
                    'wp-element', 
                    'wp-components', 
                    'wp-block-editor'
                )
            );

            wp_localize_script( 'mycred-cashcred', 'mycred_cashcred_gateways', $mycred_cashcred_gateways );

        }

        public function render_block( $attributes, $content ) {

            if ( ! empty( $attributes['types'] ) && is_array( $attributes['types'] ) )
                $attributes['types'] = implode( ',', $attributes['types'] );

            if ( ! empty( $attributes['gateways'] ) && is_array( $attributes['gateways'] ) )
                $attributes['gateways'] = implode( ',', $attributes['gateways'] );

            return "[mycred_cashcred " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]";

        }

    }
endif;

new mycred_cashcred_block();