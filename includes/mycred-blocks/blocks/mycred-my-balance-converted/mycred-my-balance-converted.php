<?php

add_action('enqueue_block_editor_assets', 'mycred_my_balance_converted_assets');

function mycred_my_balance_converted_assets() {

    wp_enqueue_script(
            'mycred-my-balance-converted', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-my-balance-converted', array(
    'render_callback' => 'mycred_my_balance_converted_callback'
));

function mycred_my_balance_converted_callback( $attributes ) {

    return "[mycred_my_balance_converted " . mycred_extract_attributes( $attributes ) . "]";
}
