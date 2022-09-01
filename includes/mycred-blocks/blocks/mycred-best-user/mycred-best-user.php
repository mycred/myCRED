<?php

namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_best_user_block') ) :

    class mycred_best_user_block {

        public function __construct() {

            add_action( 'enqueue_block_editor_assets', array( $this, 'register_assets' ) );

            register_block_type( 
                'mycred-gb-blocks/mycred-best-user', 
                array( 'render_callback' => array( $this, 'render_block' ) )
            );
        
        }

        public function register_assets() {

            wp_enqueue_script(
                'mycred-best-user', 
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
            
            $content = "";

            if ( isset( $attributes['content'] ) )
                $content = $attributes['content'];
            
            unset( $attributes['content'] );

            return "[mycred_best_user " . mycred_blocks_functions::mycred_extract_attributes( $attributes ) . "]" . $content . "[/mycred_best_user]";

        }

    }

endif;

new mycred_best_user_block();