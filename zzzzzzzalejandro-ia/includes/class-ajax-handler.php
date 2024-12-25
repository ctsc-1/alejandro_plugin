<?php
/**
 * Gestion des requêtes AJAX
 */
class Alejandro_Ajax_Handler {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Actions pour les utilisateurs connectés et non connectés
        add_action('wp_ajax_alejandro_chat', [$this, 'handle_chat']);
        add_action('wp_ajax_nopriv_alejandro_chat', [$this, 'handle_chat']);
        
        add_action('wp_ajax_alejandro_speech', [$this, 'handle_speech']);
        add_action('wp_ajax_nopriv_alejandro_speech', [$this, 'handle_speech']);
        
        add_action('wp_ajax_alejandro_translate', [$this, 'handle_translate']);
        add_action('wp_ajax_nopriv_alejandro_translate', [$this, 'handle_translate']);
        
        add_action('wp_ajax_alejandro_weather', [$this, 'handle_weather']);
        add_action('wp_ajax_nopriv_alejandro_weather', [$this, 'handle_weather']);

        add_action('wp_ajax_alejandro_location', [$this, 'handle_location']);
        add_action('wp_ajax_nopriv_alejandro_location', [$this, 'handle_location']);
        
        add_action('wp_ajax_alejandro_directions', [$this, 'handle_directions']);
        add_action('wp_ajax_nopriv_alejandro_directions', [$this, 'handle_directions']);
        
        add_action('wp_ajax_alejandro_places', [$this, 'handle_places']);
        add_action('wp_ajax_nopriv_alejandro_places', [$this, 'handle_places']);
    }
    
    /**
     * Vérifie le nonce AJAX
     */
    private function verify_nonce() {
        if (!check_ajax_referer('alejandro_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid security token'], 403);
        }
    }
    
    /**
     * Gère les requêtes de chat
     */
    public function handle_chat() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['ia'])) {
                throw new Exception('Service IA non disponible');
            }
            
            $message = sanitize_text_field($_POST['message'] ?? '');
            if (empty($message)) {
                throw new Exception('Message vide');
            }
            
            $language = sanitize_text_field($_POST['language'] ?? get_option('alejandro_ia_default_lang', 'fr'));
            
            // Générer la réponse
            $response = $alejandro_services['ia']->generate_response($message);
            
            // Si demandé, générer l'audio
            $audio_url = null;
            if (get_option('alejandro_ia_voice_enabled', false)) {
                try {
                    $audio = $alejandro_services['speech']->text_to_speech($response['message']);
                    $audio_url = $audio['url'];
                } catch (Exception $e) {
                    error_log('[Alejandro IA] Speech generation error: ' . $e->getMessage());
                }
            }
            
            wp_send_json_success([
                'message' => $response['message'],
                'actions' => $response['actions'],
                'audio_url' => $audio_url
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Gère les requêtes de synthèse vocale
     */
    public function handle_speech() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['speech'])) {
                throw new Exception('Service vocal non disponible');
            }
            
            $text = sanitize_text_field($_POST['text'] ?? '');
            if (empty($text)) {
                throw new Exception('Texte vide');
            }
            
            $language = sanitize_text_field($_POST['language'] ?? get_option('alejandro_ia_default_lang', 'fr'));
            
            $audio = $alejandro_services['speech']->text_to_speech($text);
            
            wp_send_json_success([
                'audio_url' => $audio['url']
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Gère les requêtes de traduction
     */
    public function handle_translate() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['translation'])) {
                throw new Exception('Service de traduction non disponible');
            }
            
            $text = sanitize_text_field($_POST['text'] ?? '');
            if (empty($text)) {
                throw new Exception('Texte vide');
            }
            
            $target_lang = sanitize_text_field($_POST['target_lang'] ?? '');
            if (empty($target_lang)) {
                throw new Exception('Langue cible non spécifiée');
            }
            
            // Détecter la langue source si non fournie
            $source_lang = sanitize_text_field($_POST['source_lang'] ?? '');
            if (empty($source_lang)) {
                $source_lang = $alejandro_services['translation']->detect_language($text);
            }
            
            $translated = $alejandro_services['translation']->translate($text, $target_lang);
            
            wp_send_json_success([
                'translated_text' => $translated,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Gère les requêtes météo
     */
    public function handle_weather() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['weather'])) {
                throw new Exception('Service météo non disponible');
            }
            
            $location = sanitize_text_field($_POST['location'] ?? '');
            if (empty($location)) {
                throw new Exception('Localisation non spécifiée');
            }
            
            $language = sanitize_text_field($_POST['language'] ?? get_option('alejandro_ia_default_lang', 'fr'));
            
            $weather = $alejandro_services['weather']->get_weather($location, $language);
            
            wp_send_json_success($weather);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gère les requêtes de géolocalisation
     */
    public function handle_location() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['location'])) {
                throw new Exception('Service de localisation non disponible');
            }
            
            $address = sanitize_text_field($_POST['address'] ?? '');
            if (empty($address)) {
                throw new Exception('Adresse non spécifiée');
            }
            
            $result = $alejandro_services['location']->geocode($address);
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gère les requêtes d'itinéraire
     */
    public function handle_directions() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['location'])) {
                throw new Exception('Service de localisation non disponible');
            }
            
            $origin = sanitize_text_field($_POST['origin'] ?? '');
            $destination = sanitize_text_field($_POST['destination'] ?? '');
            $mode = sanitize_text_field($_POST['mode'] ?? 'driving');
            
            if (empty($origin) || empty($destination)) {
                throw new Exception('Origine ou destination non spécifiée');
            }
            
            $result = $alejandro_services['location']->get_directions($origin, $destination, $mode);
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Gère les requêtes de points d'intérêt
     */
    public function handle_places() {
        $this->verify_nonce();
        
        try {
            global $alejandro_services;
            
            if (!isset($alejandro_services['location'])) {
                throw new Exception('Service de localisation non disponible');
            }
            
            $query = sanitize_text_field($_POST['query'] ?? '');
            $type = sanitize_text_field($_POST['type'] ?? '');
            
            // Location est optionnel
            $location = null;
            if (isset($_POST['lat']) && isset($_POST['lng'])) {
                $location = [
                    'lat' => floatval($_POST['lat']),
                    'lng' => floatval($_POST['lng'])
                ];
            }
            
            if (empty($query)) {
                throw new Exception('Requête non spécifiée');
            }
            
            $result = $alejandro_services['location']->find_places($query, $location, $type);
            
            wp_send_json_success($result);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
