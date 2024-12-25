<?php
/**
 * Service pour la synthèse et la reconnaissance vocale
 */
class Alejandro_Speech_Service {
    private $language;
    private $voices = [
        'fr' => [
            'lang' => 'fr-FR',
            'voice' => 'fr-FR-Standard-A'
        ],
        'es' => [
            'lang' => 'es-ES',
            'voice' => 'es-ES-Standard-A'
        ],
        'en' => [
            'lang' => 'en-US',
            'voice' => 'en-US-Standard-C'
        ]
    ];

    public function __construct($language = 'fr') {
        $this->language = $language;
    }

    /**
     * Convertit le texte en fichier audio
     */
    public function text_to_speech($text) {
        try {
            $voice_config = $this->voices[$this->language] ?? $this->voices['fr'];
            
            // Utiliser l'API Text-to-Speech de Google Cloud
            $google_key = get_option('alejandro_ia_google_key');
            if (empty($google_key)) {
                throw new Exception('Clé API Google non configurée');
            }

            $data = [
                'input' => [
                    'text' => $text
                ],
                'voice' => [
                    'languageCode' => $voice_config['lang'],
                    'name' => $voice_config['voice']
                ],
                'audioConfig' => [
                    'audioEncoding' => 'MP3',
                    'speakingRate' => 1.0,
                    'pitch' => 0.0
                ]
            ];

            $response = wp_remote_post('https://texttospeech.googleapis.com/v1/text:synthesize?key=' . $google_key, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($data),
                'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                throw new Exception('Erreur de synthèse vocale: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!isset($body['audioContent'])) {
                throw new Exception('Format de réponse audio invalide');
            }

            // Sauvegarder l'audio temporairement
            $upload_dir = wp_upload_dir();
            $audio_dir = $upload_dir['basedir'] . '/alejandro-ia/audio';
            if (!file_exists($audio_dir)) {
                wp_mkdir_p($audio_dir);
            }

            $filename = 'speech_' . md5(uniqid()) . '.mp3';
            $filepath = $audio_dir . '/' . $filename;
            file_put_contents($filepath, base64_decode($body['audioContent']));

            return [
                'url' => $upload_dir['baseurl'] . '/alejandro-ia/audio/' . $filename,
                'path' => $filepath
            ];

        } catch (Exception $e) {
            error_log('[Alejandro IA] Speech Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Nettoie les fichiers audio temporaires
     */
    public function cleanup_old_files() {
        $upload_dir = wp_upload_dir();
        $audio_dir = $upload_dir['basedir'] . '/alejandro-ia/audio';
        
        if (!is_dir($audio_dir)) {
            return;
        }

        $files = glob($audio_dir . '/speech_*.mp3');
        $now = time();
        
        foreach ($files as $file) {
            // Supprimer les fichiers de plus d'une heure
            if ($now - filemtime($file) > 3600) {
                unlink($file);
            }
        }
    }
}
