<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_give_block') ) :

    class mycred_give_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-give', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-give', 
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

            $content = '';

            if ( empty( $attributes['type'] ) )
                $attributes['type'] = 'mycred_default';

            if ( ! empty( $attributes['content'] ) )
                $content = $attributes['content'];
            
            unset( $attributes['content'] );

            return "[mycred_give " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]" . $content . "[/mycred_give]";

        }

    }

endif;

new mycred_give_block();