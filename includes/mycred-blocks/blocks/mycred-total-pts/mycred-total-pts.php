<?php

add_action('enqueue_block_editor_assets', 'mycred_total_pts');

function mycred_total_pts() {

    wp_enqueue_script(
            'mycred-total-pts', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-total-pts', array(
    'render_callback' => 'mycred_total_pts_callback'
));

function mycred_total_pts_callback($attributes) {

    if ($attributes['type'] == '')
        $attributes['type'] = 'mycred_default';

    return "[mycred_total_points " . mycred_extract_attributes($attributes) . "]";
}
