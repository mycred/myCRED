<?php

add_action('enqueue_block_editor_assets', 'mycred_leaderboard');

function mycred_leaderboard() {

    wp_enqueue_script(
            'mycred-leaderboard', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-leaderboard', array(
    'render_callback' => 'mycred_leaderboard_callback'
));

function mycred_leaderboard_callback($attributes) {
    if ($attributes['type'] == '')
        $attributes['type'] = 'mycred_default';

    return "[mycred_leaderboard " . mycred_extract_attributes($attributes) . "]";
}
