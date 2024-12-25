<?php
/**
 * Gestion des chemins pour Alejandro IA
 */
class Alejandro_Paths {
    private static $instance = null;
    private $plugin_dir;
    private $plugin_url;
    private $config_dir;
    private $languages_dir;
    private $assets_dir;
    private $assets_url;

    private function __construct() {
        $this->plugin_dir = plugin_dir_path(dirname(__FILE__));
        $this->plugin_url = plugin_dir_url(dirname(__FILE__));
        $this->config_dir = $this->plugin_dir . '.config/';
        $this->languages_dir = $this->plugin_dir . 'languages/';
        $this->assets_dir = $this->plugin_dir . 'assets/';
        $this->assets_url = $this->plugin_url . 'assets/';

        // Vérifier et créer les répertoires nécessaires
        $this->ensure_directories();
    }

    private function ensure_directories() {
        $directories = [
            $this->config_dir,
            $this->languages_dir,
            $this->assets_dir . 'css',
            $this->assets_dir . 'js'
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }

        // Protection du répertoire .config
        $htaccess_file = $this->config_dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Order deny,allow\nDeny from all");
        }
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get_plugin_dir() {
        return $this->plugin_dir;
    }

    public function get_plugin_url() {
        return $this->plugin_url;
    }

    public function get_config_dir() {
        return $this->config_dir;
    }

    public function get_languages_dir() {
        return $this->languages_dir;
    }

    public function get_assets_dir() {
        return $this->assets_dir;
    }

    public function get_assets_url() {
        return $this->assets_url;
    }

    public function get_css_url() {
        return $this->assets_url . 'css/';
    }

    public function get_js_url() {
        return $this->assets_url . 'js/';
    }

    public function get_config_file($filename) {
        $file = $this->config_dir . $filename;
        if (!file_exists($file)) {
            throw new Exception(sprintf(__('Configuration file %s not found.', 'alejandro-ia'), $filename));
        }
        return $file;
    }
}
