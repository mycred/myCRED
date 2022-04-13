<?php

add_action('enqueue_block_editor_assets', 'mycred_total_balance');

function mycred_total_balance() {

    wp_enqueue_script(
            'mycred-total-balance', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-total-balance', array(
    'render_callback' => 'mycred_total_balance_callback'
));

function mycred_total_balance_callback($attributes) {
    if ($attributes['types'] == '')
        $attributes['types'] = 'mycred_default';

    return "[mycred_total_balance " . mycred_extract_attributes($attributes) . "]";
}
