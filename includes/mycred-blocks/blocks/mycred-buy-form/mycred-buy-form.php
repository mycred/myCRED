<?php

add_action('enqueue_block_editor_assets', 'mycred_buy_form');

function mycred_buy_form() {

    wp_enqueue_script(
            'mycred-buy-form', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-buy-form', array(
    'render_callback' => 'mycred_buy_form_callback'
));

function mycred_buy_form_callback($attributes) {
    return "[mycred_buy_form " . mycred_extract_attributes($attributes) . "]";
}
