<?php

add_action('enqueue_block_editor_assets', 'mycred_history');

function mycred_history() {

    wp_enqueue_script(
            'mycred-history', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-history', array(
    'render_callback' => 'mycred_history_callback'
));

function mycred_history_callback($attributes) {

    if ($attributes['type'] == '')
        $attributes['type'] = 'mycred_default';

    return "[mycred_history " . mycred_extract_attributes($attributes) . "]";
}
