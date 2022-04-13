<?php

add_action('enqueue_block_editor_assets', 'mycred_buy');

function mycred_buy() {

    wp_enqueue_script(
            'mycred-buy', plugins_url('index.js', __FILE__), array('wp-blocks', 'wp-element', 'wp-components', 'wp-editor')
    );

    $buycred = new myCRED_buyCRED_Module();
    $gateways = array();
    foreach ($buycred->get() as $gateway_id => $gateway) {
        $gateways[$gateway['title']] = $gateway_id;
    }

    wp_localize_script('mycred-buy', 'mycred_buy', $gateways);
}

register_block_type('mycred-blocks/mycred-buy', array(
    'render_callback' => 'mycred_buy_callback'
));

function mycred_buy_callback($attributes) {
    if ($attributes['clss']) {
        $attributes['class'] = $attributes['clss'];
        unset($attributes['clss']);
    }

    if ($attributes['link_title']) {
        $content = $attributes['link_title'];
    }
    unset($attributes['link_title']);

    return "[mycred_buy " . mycred_extract_attributes($attributes) . "]" . $content . "[/mycred_buy]";
}
