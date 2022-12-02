<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_video_block') ) :
    class mycred_video_block {

        public function __construct() {

            add_action('enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-video', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-video', 
                plugins_url('index.js', __FILE__), 
                array( 
                    'wp-blocks', 
                    'wp-element', 
                    'wp-components', 
                    'wp-block-editor'
                )
            );

        }

        public function render_block( $attributes, $content ) {
            // var_dump($attributes['video_id']);
            if ( empty( $attributes['ctype'] ) )
                $attributes['ctype'] = 'mycred_default';

            if( ! empty( $attributes['video_id'] ) )
                $attributes['id'] = $attributes['video_id'];

            return "[mycred_video " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]";

        }

    }
endif;

new mycred_video_block();