<?php
/**
 * Plugin Name:       Musoftware SMS Payment Gateway
 * Description:       Integrates WordPress with the Musoftware SMS Payment Gateway. Provides shortcodes for payment forms and handles webhook callbacks.
 * Version:           1.0.0
 * Author:            Musoftware
 * Text Domain:       musoftware-sms-gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'MUSOFTWARE_SMS_GATEWAY_VERSION', '2.0.0' );
define( 'MUSOFTWARE_SMS_GATEWAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MUSOFTWARE_SMS_GATEWAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load includes
require_once MUSOFTWARE_SMS_GATEWAY_PLUGIN_DIR . 'includes/class-db.php';
require_once MUSOFTWARE_SMS_GATEWAY_PLUGIN_DIR . 'includes/class-settings.php';
require_once MUSOFTWARE_SMS_GATEWAY_PLUGIN_DIR . 'includes/class-api.php';
require_once MUSOFTWARE_SMS_GATEWAY_PLUGIN_DIR . 'includes/class-webhook.php';
require_once MUSOFTWARE_SMS_GATEWAY_PLUGIN_DIR . 'includes/class-shortcode.php';

/**
 * Main Plugin Class
 */
class Musoftware_Sms_Gateway {

    /**
     * Initialize the plugin
     */
    public static function init() {
        // Register activation hook
        register_activation_hook( __FILE__, array( 'Musoftware_Sms_Gateway_DB', 'install' ) );

        // Initialize components
        Musoftware_Sms_Gateway_Settings::init();
        Musoftware_Sms_Gateway_Webhook::init();
        Musoftware_Sms_Gateway_Shortcode::init();

        // Load plugin textdomain for translations
        add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
    }

    /**
     * Load plugin textdomain
     */
    public static function load_textdomain() {
        load_plugin_textdomain( 'musoftware-sms-gateway', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
}

// Boot the plugin
Musoftware_Sms_Gateway::init();
