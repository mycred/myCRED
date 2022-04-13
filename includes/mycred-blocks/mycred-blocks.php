<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

define( 'mycred_gutenberg_SLUG', 'mycred-gutenberg-blocks' );
define( 'mycred_gutenberg_VERSION', '1.1.2' );
define( 'mycred_gutenberg', __FILE__ );

final class MyCred_Gutenberg {

    private static $_instance = null;

    /**
     * Instance
     * Ensures only one instance of the class is loaded or can be loaded.
     * @since 1.0.0
     * @access public
     * @static
     * @return MyCred_Gutenberg An instance of the class.
     */
    public static function instance() {

        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {

        if (!class_exists('myCRED_Core')) {
            add_action('admin_notices', [$this, 'admin_notice_mycred_missing_plugin']);
            return;
        }

        if (!function_exists('register_block_type'))
            return;

        $this->includes();
        add_action('init', [$this, 'load_modules']);
    }

    public function admin_notice_mycred_missing_plugin() {

        if (isset($_GET['activate']))
            unset($_GET['activate']);

        $message = sprintf(
                esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'mycred'), '<b>' . esc_html__('myCRED for Gutenberg', 'mycred') . '</b>', '<b>' . esc_html__('myCRED', 'mycred') . '</b>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    public function includes() {
        require_once( __DIR__ . '/includes/mycred-gutenberg-functions.php' );
    }

    public function load_modules() {

        $mycred_modules = [
            'mycred-affiliate-id',
            'mycred-affiliate-link',
            'mycred-best-user',
            'mycred-exchange',
            'mycred-give',
            'mycred-history',
            'mycred-hook-table',
            'mycred-leaderboard',
            'mycred-link',
            'mycred-my-balance',
            'mycred-total-balance',
            'mycred-total-pts',
            'mycred-total-since',
            'mycred-video',
            'mycred-my-balance-converted',
        ];

        if (class_exists('myCRED_Badge_Module')) {
            $mycred_modules[] = 'mycred-my-badges';
            $mycred_modules[] = 'mycred-badges';
        }

        if (class_exists('myCRED_buyCRED_Module')) {
            $mycred_modules[] = 'mycred-buy';
            $mycred_modules[] = 'mycred-buy-form';
        }
        if (class_exists('myCRED_Coupons_Module')) {
            $mycred_modules[] = 'mycred-load-coupon';
        }
        if (class_exists('myCRED_Email_Notice_Module')) {
            $mycred_modules[] = 'mycred-email-subsc';
        }

        if (class_exists('myCRED_Ranks_Module')) {
            $mycred_modules[] = 'mycred-my-rank';
            $mycred_modules[] = 'mycred-my-ranks';
            $mycred_modules[] = 'mycred-users-of-all-ranks';
            $mycred_modules[] = 'mycred-users-of-rank';
            $mycred_modules[] = 'mycred-list-ranks';
        }

        if (class_exists('myCRED_Transfer_Module')) {
            $mycred_modules[] = 'mycred-transfer';
        }

        foreach ($mycred_modules as $mycred_module) {
            require_once __DIR__ . "/blocks/$mycred_module/$mycred_module.php";
        }
    }

}

MyCred_Gutenberg::instance();
