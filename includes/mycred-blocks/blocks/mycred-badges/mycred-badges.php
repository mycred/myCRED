<?php

add_action('enqueue_block_editor_assets', 'mycred_badges');

function mycred_badges() {

    wp_enqueue_script(
            'mycred-badges', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}
    register_block_type('mycred-blocks/mycred-badges', array(
        'render_callback' => 'mycred_badges_callback'
    ));

function mycred_badges_callback($attributes) {
    return "[mycred_badges " . mycred_extract_attributes($attributes) . "]";
}
