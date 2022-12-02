<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_buy_pending_block') ) :
    class mycred_buy_pending_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-buy-pending', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-buy-pending', 
                plugins_url('index.js', __FILE__), 
                array( 
                    'wp-blocks', 
                    'wp-element', 
                    'wp-components', 
                    'wp-block-editor', 
                    'wp-rich-text' 
                )
            );

        }

        public function render_block( $attributes, $content ) {
            return "[mycred_buy_pending " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]";
        }

    }
endif;

new mycred_buy_pending_block();