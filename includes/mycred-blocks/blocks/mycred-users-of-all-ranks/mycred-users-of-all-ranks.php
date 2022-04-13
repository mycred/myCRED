<?php

add_action('enqueue_block_editor_assets', 'mycred_users_of_all_ranks');

function mycred_users_of_all_ranks() {

    wp_enqueue_script(
            'mycred-users-of-all-ranks', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-users-of-all-ranks', array(
    'render_callback' => 'mycred_users_of_all_ranks_callback'
));

function mycred_users_of_all_ranks_callback($attributes) {

    if ($attributes['ctype'] == '')
        $attributes['ctype'] = 'mycred_default';

    return "[mycred_users_of_all_ranks  " . mycred_extract_attributes($attributes) . "]";
}
