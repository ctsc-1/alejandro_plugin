<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_NLP {
    private $anthropic_client;
    private $language_handler;
    
    public function __construct() {
        $this->init_anthropic_client();
        $this->language_handler = new Alejandro_Language();
    }

    private function init_anthropic_client() {
        $api_key = get_option('alejandro_anthropic_api_key');
        if (!empty($api_key)) {
            $this->anthropic_client = new Alejandro_Anthropic_Client($api_key);
        }
    }

    public function process_message($message) {
        try {
            // Détection de la langue
            $language_data = $this->language_handler->handle_language_detection($message);
            
            // Préparation du contexte multilingue
            $system_prompt = $this->get_system_prompt($language_data['language']);
            
            $response = $this->anthropic_client->send_message($message, [
                'system' => $system_prompt
            ]);

            return [
                'intent' => $this->detect_intent($response),
                'response' => $response,
                'language' => $language_data['language']
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    private function get_system_prompt($language) {
        $prompts = [
            'fr' => "Vous êtes Alejandro, un assistant virtuel pour Club Costa Tropical. Répondez en français.",
            'es' => "Eres Alejandro, un asistente virtual para Club Costa Tropical. Responde en español.",
            'en' => "You are Alejandro, a virtual assistant for Club Costa Tropical. Respond in English."
        ];
        
        return $prompts[$language] ?? $prompts['fr'];
    }

    private function detect_intent($message) {
        // Logique de détection d'intention
        return 'general_query';
    }
} 