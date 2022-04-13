<?php

add_action('enqueue_block_editor_assets', 'mycred_load_coupon');

function mycred_load_coupon() {

    wp_enqueue_script(
            'mycred-load-coupon', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-load-coupon', array(
    'render_callback' => 'mycred_load_coupon_callback'
));

function mycred_load_coupon_callback($attributes) {
    return "[mycred_load_coupon " . mycred_extract_attributes($attributes) . "]";
}
