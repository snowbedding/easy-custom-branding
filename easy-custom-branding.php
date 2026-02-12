<?php
/**
 * Plugin Name: Easy Custom Branding
 * Plugin URI: https://github.com/snowbedding/easy-custom-branding
 * Description: A complete solution to customize your WordPress branding in the admin area and the login page.
 * Version: 1.0.0
 * Author: snowbedding
 * Author URI: https://github.com/snowbedding
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-custom-branding
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'Easy_Custom_Branding' ) ) {

    final class Easy_Custom_Branding {

        private static $instance;

        /**
         * Plugin Version.
         *
         * @var string
         */
        public $version = '1.0.0';

        /**
         * Singleton instance
         */
        public static function get_instance() {
            if ( ! isset( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor.
         */
        private function __construct() {
            $this->define_constants();
            $this->includes();
            $this->init_hooks();
        }

        /**
         * Define Constants.
         */
        private function define_constants() {
            define( 'EASY_CUSTOM_BRANDING_VERSION', $this->version );
            define( 'EASY_CUSTOM_BRANDING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
            define( 'EASY_CUSTOM_BRANDING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        }

        /**
         * Include required files.
         */
        public function includes() {
            require_once EASY_CUSTOM_BRANDING_PLUGIN_DIR . 'includes/class-easy-custom-branding-settings.php';
        }

        /**
         * Hook into actions and filters.
         */
        private function init_hooks() {
            add_action( 'plugins_loaded', [ $this, 'on_plugins_loaded' ] );
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );
        }

        /**
         * On plugins_loaded.
         */
        public function on_plugins_loaded() {
            // Initialization
            Easy_Custom_Branding_Settings::get_instance();
        }

        /**
         * Add settings link to plugin action links.
         */
        public function add_settings_link( $links ) {
            $settings_link = '<a href="' . admin_url( 'options-general.php?page=easy_custom_branding' ) . '">' . __( 'Settings', 'easy-custom-branding' ) . '</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }
    }
}

/**
 * Begins execution of the plugin.
 */
function easy_custom_branding() {
    return Easy_Custom_Branding::get_instance();
}

// Let's go...
easy_custom_branding();
