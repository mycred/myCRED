<?php

add_action('enqueue_block_editor_assets', 'mycred_link');

function mycred_link() {

    wp_enqueue_script(
            'mycred-link', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-link', array(
    'render_callback' => 'mycred_link_callback'
));

function mycred_link_callback($attributes) {
    if ($attributes['ctype'] == '') {
        $attributes['ctype'] = 'mycred_default';
    }
	$content = "";
    if (isset($attributes['clss'])) {
        $attributes['class'] = $attributes['clss'];
        unset($attributes['clss']);
    }
    if ($attributes['content'] == '') {
        $content = $attributes['content'];
    }
    unset($attributes['content']);
    return "[mycred_link " . mycred_extract_attributes($attributes) . "]" . $content . "[/mycred_link]";
}
