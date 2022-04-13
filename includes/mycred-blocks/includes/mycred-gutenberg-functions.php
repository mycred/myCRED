<?php
namespace MG_Blocks;

if ( ! defined('ABSPATH') ) exit;

if ( ! class_exists('mycred_blocks_functions') ) :
    class mycred_blocks_functions {

        public static function mycred_extract_attributes($attributes) {
            if (empty($attributes)) {
                return;
            }
            foreach ($attributes as $k => $attribute) {
                $attr[] = $k . '=' . "'" . $attribute . "'";
            }
            return implode(' ',$attr);
        }

    }
endif;