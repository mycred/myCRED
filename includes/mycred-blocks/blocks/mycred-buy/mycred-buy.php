<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_buy_block') ) :
    class mycred_buy_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-buy', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-buy', 
                plugins_url('index.js', __FILE__), 
                array( 
                    'wp-blocks', 
                    'wp-element', 
                    'wp-components', 
                    'wp-block-editor' 
                )
            );

            $buycred = new \myCRED_buyCRED_Module();
            $gateways = array();
            foreach ( $buycred->get() as $gateway_id => $gateway ) {
                $gateways[$gateway['title']] = $gateway_id;
            }

            wp_localize_script( 'mycred-buy', 'mycred_buy', $gateways );

        }

        public function render_block( $attributes, $content ) {

            if ( ! empty( $attributes['clss'] ) ) {
                $attributes['class'] = $attributes['clss'];
                unset($attributes['clss']);
            }

            if ( ! empty( $attributes['link_title'] ) ) {
                $content = $attributes['link_title'];
                unset($attributes['link_title']);
            }

            return "[mycred_buy " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]" . $content . "[/mycred_buy]";

        }

    }
endif;

new mycred_buy_block();
