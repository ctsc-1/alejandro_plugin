<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Personality {
    private static $instance = null;
    private $personality_data;
    private $current_lang;

    private function __construct() {
        $this->load_personality();
        $this->current_lang = substr(get_locale(), 0, 2);
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_personality() {
        $file_path = ALEJANDRO_IA_PLUGIN_DIR . 'config/personality.json';
        if (file_exists($file_path)) {
            $json_content = file_get_contents($file_path);
            $this->personality_data = json_decode($json_content, true);
        }
    }

    public function is_identity_question($message) {
        $patterns = $this->personality_data['personality']['identity_questions']['patterns'];
        $message = strtolower(trim($message));
        
        foreach ($patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $message)) {
                return true;
            }
        }
        return false;
    }

    public function get_identity_response($is_marco, $message, $lang = null) {
        $response_lang = $lang ?: $this->current_lang;
        $responses = $this->personality_data['personality']['identity_questions']['responses'];
        
        // Si c'est une question sur le créateur
        if (preg_match('/(père|papa|créateur|créé|fait|conçu)/i', $message)) {
            return $responses['creator'][array_rand($responses['creator'])];
        }
        
        $type = $is_marco ? 'marco' : 'visitor';
        return $responses[$type][array_rand($responses[$type])];
    }

    public function style_response($response, $add_greeting = false, $lang = null) {
        // Utiliser la langue spécifiée ou celle par défaut
        $response_lang = $lang ?: $this->current_lang;
        
        // 1. Nettoyer la réponse de toute tentative d'auto-présentation
        $response = preg_replace(
            '/(je suis|je m\'appelle|soy|i am) Alejandro(,?\s*)(votre |tu |el |your )?(assistant virtuel|asistente virtual|virtual assistant)/i',
            '',
            $response
        );

        // 2. Construire la réponse stylisée
        $styled = '';
        $rules = $this->personality_data['personality']['style_rules'];
        
        // Ajouter une salutation si demandé
        if ($add_greeting) {
            $greetings = $this->personality_data['personality']['greetings'][$response_lang];
            $styled .= $greetings[array_rand($greetings)] . ' ';
        }

        // Ajouter la réponse nettoyée
        $styled .= trim($response);

        // 3. Ajouter des expressions andalouses (max 2 par réponse)
        $expressions_count = 0;
        $max_expressions = $rules['max_expressions_per_response'];
        while ($expressions_count < $max_expressions && rand(0, 100) < ($rules['expression_frequency'] * 100)) {
            $expressions = $this->personality_data['personality']['expressions'][$response_lang];
            $styled .= ' ' . $expressions[array_rand($expressions)];
            $expressions_count++;
        }

        // 4. Ajouter un emoji avec une certaine probabilité
        if (rand(0, 100) < ($rules['emoji_frequency'] * 100)) {
            $emojis = $this->personality_data['personality']['emojis'];
            $styled .= ' ' . $emojis[array_rand($emojis)];
        }

        return trim($styled);
    }

    public function get_version() {
        return $this->personality_data['version'];
    }
}
