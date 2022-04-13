<?php

add_action('enqueue_block_editor_assets', 'mycred_give');

function mycred_give() {

    wp_enqueue_script(
            'mycred-give', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-give', array(
    'render_callback' => 'mycred_give_callback'
));

function mycred_give_callback($attributes) {
    if ($attributes['type'] == ''){
        $attributes['type'] = 'mycred_default';
    }
    $content='';
    if ($attributes['content'] == '') {
        $content = $attributes['content'];
    }
        unset($attributes['content']);
    return "[mycred_give " . mycred_extract_attributes($attributes) . "]" . $content . "[/mycred_give]";
}
