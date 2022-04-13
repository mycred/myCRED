<?php

add_action('enqueue_block_editor_assets', 'mycred_affiliate_id');

function mycred_affiliate_id() {

    wp_enqueue_script(
            'mycred-affiliate-id', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-rich-text')
    );
    $mycred_types = mycred_get_types(true);
    $mycred_types = array_merge(array('' => __('Select point type', 'mycred')), $mycred_types);
    wp_localize_script('mycred-affiliate-id', 'mycred_types', $mycred_types);
}

register_block_type('mycred-blocks/mycred-affiliate-id', array(
    'render_callback' => 'mycred_affiliate_id_callback',
));

function mycred_affiliate_id_callback($attributes) {
    if ($attributes['type'] == '')
        $attributes['type'] = 'mycred_default';

        return "[mycred_affiliate_id " . mycred_extract_attributes($attributes) . "]";
}
