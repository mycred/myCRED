<?php

function mycred_extract_attributes($attributes) {
    if (empty($attributes)) {
        return;
    }
    foreach ($attributes as $k => $attribute) {
        $attr[] = $k . '=' . "'" . $attribute . "'";
    }
    return implode(' ',$attr);
}

if ( version_compare( $GLOBALS['wp_version'], '5.8-alpha-1', '<' ) ) {
    add_filter('block_categories', 'mb_block_categories', 10, 2);
} else {
    add_filter('block_categories_all', 'mb_block_categories', 10, 2);
}

function mb_block_categories($categories, $post) {
    /** If mb.ideas is already in categories return categories */
    return array_merge(
            $categories, array(
        array(
            'slug' => 'mycred',
            'title' => __('MYCRED', 'mycred'),
        ),
            )
    );
}
