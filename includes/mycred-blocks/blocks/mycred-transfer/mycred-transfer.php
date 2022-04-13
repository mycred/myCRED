<?php

add_action('enqueue_block_editor_assets', 'mycred_transfers');

function mycred_transfers() {
    wp_enqueue_script(
            'mycred-transfers', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-transfers', array(
    'render_callback' => 'mycred_transfer_callback'
));

function mycred_transfer_callback($attributes) {
    return "[mycred_transfer " . mycred_extract_attributes($attributes) . "]";
}
