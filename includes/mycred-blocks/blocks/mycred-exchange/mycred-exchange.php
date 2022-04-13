<?php

add_action('enqueue_block_editor_assets', 'mycred_exchange');

function mycred_exchange() {

    wp_enqueue_script(
            'mycred-exchange', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-exchange', array(
    'render_callback' => 'mycred_exchange_user_callback',
));

function mycred_exchange_user_callback($attributes) {
    return "[mycred_exchange " . mycred_extract_attributes($attributes) . "]";
}
