<?php

add_action('enqueue_block_editor_assets', 'mycred_hook_table');

function mycred_hook_table() {

    wp_enqueue_script(
            'mycred-hook-table', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-hook-table', array(
    'render_callback' => 'mycred_hook_table_callback'
));

function mycred_hook_table_callback($attributes) {
    if ($attributes['type'] == '')
        $attributes['type'] = 'mycred_default';

    return "[mycred_hook_table " . mycred_extract_attributes($attributes) . "]";
}
