<?php

add_action('enqueue_block_editor_assets', 'mycred_my_rank');

function mycred_my_rank() {

    wp_enqueue_script(
            'mycred-my-rank', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-my-rank', array(
    'render_callback' => 'mycred_my_rank_callback'
));

function mycred_my_rank_callback($attributes) {

    if ($attributes['ctype'] == '')
        $attributes['ctype'] = 'mycred_default';

    return "[mycred_my_rank  " . mycred_extract_attributes($attributes) . "]";
}
