<?php

add_action('enqueue_block_editor_assets', 'mycred_video');

function mycred_video() {

    wp_enqueue_script(
            'mycred-video', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-video', array(
    'render_callback' => 'mycred_video_callback'
));

function mycred_video_callback($attributes) {

    if ($attributes['ctype'] == '')
        $attributes['ctype'] = 'mycred_default';

    return "[mycred_video " . mycred_extract_attributes($attributes) . "]";
}
