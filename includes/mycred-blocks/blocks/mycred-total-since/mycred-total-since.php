<?php

add_action('enqueue_block_editor_assets', 'mycred_total_since');

function mycred_total_since() {

    wp_enqueue_script(
            'mycred-total-since', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-total-since', array(
    'render_callback' => 'mycred_total_since_callback'
));

function mycred_total_since_callback($attributes) {

    if ($attributes['type'] == '')
        $attributes['type'] = 'mycred_default';

    return "[mycred_total_since " . mycred_extract_attributes($attributes) . "]";
}
