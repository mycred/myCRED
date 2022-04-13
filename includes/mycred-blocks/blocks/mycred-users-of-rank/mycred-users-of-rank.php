<?php

add_action('enqueue_block_editor_assets', 'mycred_users_of_rank');

function mycred_users_of_rank() {

    wp_enqueue_script(
            'mycred-users-of-rank', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-users-of-rank', array(
    'render_callback' => 'mycred_users_of_rank_callback'
));

function mycred_users_of_rank_callback($attributes) {

    if ($attributes['ctype'] == '')
        $attributes['ctype'] = 'mycred_default';

    return "[mycred_users_of_rank  " . mycred_extract_attributes($attributes) . "]";
}
