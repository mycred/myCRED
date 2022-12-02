<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_chart_instance_block') ) :
    class mycred_chart_instance_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-instance-history', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-chart-instance-history', 
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
            return "[mycred_chart_instance_history " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]";
        }

    }
endif;

new mycred_chart_instance_block();