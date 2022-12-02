<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_hook_table_block') ) :

    class mycred_hook_table_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-hook-table', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-hook-table', 
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
            
            if ( empty( $attributes['type'] ) )
                $attributes['type'] = 'mycred_default';

            return "[mycred_hook_table " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]";
        }

    }

endif;

new mycred_hook_table_block();