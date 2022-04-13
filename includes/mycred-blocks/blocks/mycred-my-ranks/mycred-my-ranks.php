<?php

add_action('enqueue_block_editor_assets', 'mycred_my_ranks');

function mycred_my_ranks() {

    wp_enqueue_script(
            'mycred-my-ranks', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-my-ranks', array(
    'render_callback' => 'mycred_my_ranks_callback'
));

function mycred_my_ranks_callback($attributes) {
	
    if ($attributes['ctype'] == '')
        $attributes['ctype'] = 'mycred_default';

    return "[mycred_my_ranks  " . mycred_extract_attributes($attributes) . "]";
}
