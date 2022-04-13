<?php

add_action('enqueue_block_editor_assets', 'mycred_email_subsc');

function mycred_email_subsc() {

    wp_enqueue_script(
            'mycred-email-subsc', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );

}
    register_block_type('mycred-blocks/mycred-email-subsc', array(
    'render_callback' => 'mycred_email_subsc_callback'
));

function mycred_email_subsc_callback($attributes) {
    return "[mycred_email_subscriptions " . mycred_extract_attributes($attributes) . "]";
}
