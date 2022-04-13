<?php

add_action('enqueue_block_editor_assets', 'mycred_best_user');

function mycred_best_user() {

    wp_enqueue_script(
            'mycred-best-user', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-best-user', array(
    'render_callback' => 'mycred_best_user_callback',
));

function mycred_best_user_callback($attributes) {
	$content = "";
    if (isset($attributes['content'])) {
        $content = $attributes['content'];
    }
    unset($attributes['content']);
    return "[mycred_best_user " . mycred_extract_attributes($attributes) . "]" . $content . "[/mycred_best_user]";
}
