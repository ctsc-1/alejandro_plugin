<?php
/**
 * Détection du contexte linguistique pour Alejandro IA
 */
class Alejandro_Context {
    private static $instance = null;
    private $current_language = 'fr'; // par défaut
    
    private function __construct() {
        $this->detect_language();
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function detect_language() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $path_parts = explode('/', trim($path, '/'));
            
            // Vérifier le premier segment du chemin
            if (!empty($path_parts[0])) {
                switch ($path_parts[0]) {
                    case 'fr':
                    case 'es':
                    case 'en':
                        $this->current_language = $path_parts[0];
                        break;
                }
            }
        }
    }

    public function get_language() {
        return $this->current_language;
    }

    public function get_base_url() {
        return 'https://clubcostatropical.es/' . $this->current_language . '/';
    }
}
