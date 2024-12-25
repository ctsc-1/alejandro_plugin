<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Language {
    private static $supported_languages = ['fr', 'es', 'en'];
    private $current_language;

    public function __construct() {
        $this->init();
    }

    private function init() {
        add_action('init', array($this, 'set_current_language'));
        add_filter('alejandro_process_message', array($this, 'handle_language_detection'), 10, 1);
    }

    public function set_current_language() {
        // Détection de la langue via TranslatePress
        if (function_exists('trp_get_current_language')) {
            $this->current_language = trp_get_current_language();
        } else {
            $this->current_language = 'fr'; // Langue par défaut
        }
    }

    public function get_current_language() {
        return $this->current_language;
    }

    public function handle_language_detection($message) {
        return [
            'message' => $message,
            'language' => $this->current_language
        ];
    }
} 