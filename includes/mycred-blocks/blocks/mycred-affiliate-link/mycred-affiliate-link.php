<?php

add_action('enqueue_block_editor_assets', 'mycred_affiliate_link');

function mycred_affiliate_link() {

    wp_enqueue_script(
            'mycred-affiliate-link', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );
}

register_block_type('mycred-blocks/mycred-affiliate-link', array(
    'render_callback' => 'mycred_affiliate_link_callback'
));

function mycred_affiliate_link_callback($attributes) {
    if ($attributes['pt_type'] == '')
        $attributes['pt_type'] = 'mycred_default';

    return "[mycred_affiliate_link " . mycred_extract_attributes($attributes) . "]";
}
