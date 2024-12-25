<?php
/**
 * Plugin Name: Alejandro Monitor
 * Plugin URI: 
 * Description: Assistant IA pour Club Costa Tropical
 * Version: 1.0.0
 * Author: Votre Nom
 * License: GPL v2 or later
 * Text Domain: alejandro-monitor
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes de base
define('ALEJANDRO_MONITOR_VERSION', '1.0.0');
define('ALEJANDRO_MONITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALEJANDRO_MONITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Classe principale minimale
class Alejandro_Monitor {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // Chargement des classes
        require_once ALEJANDRO_MONITOR_PLUGIN_DIR . 'includes/class-alejandro-chat.php';
        
        // Initialisation du chat
        new Alejandro_Chat();
    }
}

// Initialisation du plugin
add_action('plugins_loaded', function() {
    Alejandro_Monitor::get_instance();
}); 