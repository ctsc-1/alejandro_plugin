<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_ElevenLabs_Integration {
    private $api_key;
    private $voice_id;
    private $api_url = 'https://api.elevenlabs.io/v1';
    private $cache;

    public function __construct() {
        $this->api_key = get_option('alejandro_ia_elevenlabs_key');
        $this->voice_id = get_option('alejandro_ia_elevenlabs_voice_id', '4FMxnogu8ehUVsRIxx9H');
        
                $this->cache = new Alejandro_Cache();
    }

    /**
     * Convertit le texte en parole avec l'accent approprié
     */
    public function text_to_speech($text, $language = 'es') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API ElevenLabs non configurée', 'alejandro-ia'));
        }

        // Générer une clé de cache unique basée sur le texte et les paramètres
        $cache_key = md5($text . $language . $this->voice_id);
        
        // Vérifier le cache
        $cached_result = $this->cache->get($cache_key, 'voice', $language);
        if ($cached_result) {
            // Vérifier si le fichier existe toujours
            if (isset($cached_result['path']) && file_exists($cached_result['path'])) {
                return $cached_result;
            }
        }

        // Obtenir la vitesse en fonction de la langue
        $speed = $this->get_speed_by_language($language);

        $endpoint = "{$this->api_url}/text-to-speech/{$this->voice_id}";
        
        $body = wp_json_encode([
            'text' => $text,
            'model_id' => 'eleven_multilingual_v2',
            'voice_settings' => [
                'stability' => 0.5,
                'similarity_boost' => 0.75,
                'style' => 1.0,
                'speaking_rate' => $speed
            ]
        ]);

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Accept' => 'audio/mpeg',
                'xi-api-key' => $this->api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => $body,
            'timeout' => 30
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            return new WP_Error(
                'elevenlabs_error',
                sprintf(__('Erreur ElevenLabs: %s', 'alejandro-ia'), wp_remote_retrieve_response_message($response))
            );
        }

        // Générer un nom de fichier unique
        $upload_dir = wp_upload_dir();
        $filename = 'alejandro_speech_' . uniqid() . '.mp3';
        $filepath = $upload_dir['path'] . '/' . $filename;

        // Sauvegarder le fichier audio
        file_put_contents($filepath, wp_remote_retrieve_body($response));

        $result = [
            'url' => $upload_dir['url'] . '/' . $filename,
            'path' => $filepath
        ];

        // Mettre en cache le résultat
        $this->cache->set($cache_key, $result, 'voice', $language, $filepath);

        return $result;
    }

    /**
     * Obtient la vitesse de parole en fonction de la langue
     */
    private function get_speed_by_language($language) {
        $speeds = [
            'es' => get_option('alejandro_ia_voice_speed_es', 0.95),
            'fr' => get_option('alejandro_ia_voice_speed_fr', 0.90),
            'en' => get_option('alejandro_ia_voice_speed_en', 0.90)
        ];

        return isset($speeds[$language]) ? $speeds[$language] : 0.95;
    }

    /**
     * Précharge les phrases courantes dans le cache
     */
    public function preload_common_phrases() {
        $common_phrases = [
            'es' => [
                'Hola, soy Alejandro. ¿En qué puedo ayudarte?',
                'Un momento por favor.',
                'No he entendido. ¿Podrías repetirlo?',
                'Gracias por tu paciencia.'
            ],
            'fr' => [
                'Bonjour, je suis Alejandro. Comment puis-je vous aider ?',
                'Un moment s\'il vous plaît.',
                'Je n\'ai pas compris. Pourriez-vous répéter ?',
                'Merci de votre patience.'
            ],
            'en' => [
                'Hello, I\'m Alejandro. How can I help you?',
                'One moment please.',
                'I didn\'t understand. Could you repeat that?',
                'Thank you for your patience.'
            ]
        ];

        foreach ($common_phrases as $lang => $phrases) {
            foreach ($phrases as $phrase) {
                $cache_key = md5($phrase . $lang . $this->voice_id);
                if (!$this->cache->get($cache_key, 'voice', $lang)) {
                    $this->text_to_speech($phrase, $lang);
                }
            }
        }
    }

    /**
     * Nettoie les fichiers audio inutilisés
     */
    public function cleanup_unused_audio() {
        $this->cache->cleanup_expired_cache();
    }
}
