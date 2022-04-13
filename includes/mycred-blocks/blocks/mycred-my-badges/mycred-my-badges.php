<?php

add_action('enqueue_block_editor_assets', 'mycred_my_badges');

function mycred_my_badges() {

    wp_enqueue_script(
            'mycred-my-badges', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}
    register_block_type('mycred-blocks/mycred-my-badges', array(
    'render_callback' => 'mycred_my_badges_callback'
));

function mycred_my_badges_callback($attributes) {
    return "[mycred_my_badges " . mycred_extract_attributes($attributes) . "]";
}
