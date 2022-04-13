<?php

add_action('enqueue_block_editor_assets', 'mycred_list_ranks');

function mycred_list_ranks() {

    wp_enqueue_script(
            'mycred-list-ranks', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-list-ranks', array(
    'render_callback' => 'mycred_list_ranks_callback'
));

function mycred_list_ranks_callback($attributes) {

    if ($attributes['ctype'] == '')
        $attributes['ctype'] = 'mycred_default';

    return "[mycred_list_ranks  " . mycred_extract_attributes($attributes) . "]";
}
