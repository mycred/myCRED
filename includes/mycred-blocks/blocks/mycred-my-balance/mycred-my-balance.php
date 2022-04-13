<?php

add_action('enqueue_block_editor_assets', 'mycred_my_balance');

function mycred_my_balance() {

    wp_enqueue_script(
            'mycred-my-balance', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-my-balance', array(
    'render_callback' => 'mycred_my_balance_callback'
));

function mycred_my_balance_callback($attributes) {
    if ($attributes['type'] == ''){
        $attributes['type'] = 'mycred_default';
    }
    
	$content = "";
    if (isset($attributes['content'])) {
        $content = $attributes['content'];
    }
    unset($attributes['content']);

    return "[mycred_my_balance " . mycred_extract_attributes($attributes) . "]" . $content . "[/mycred_my_balance]";
}
