<?php
/**
 * Service de traduction utilisant DeepL
 */
class Alejandro_Translation_Service {
    private $api_key;
    private $api_url = 'https://api-free.deepl.com/v2/translate';

    public function __construct() {
        $this->api_key = get_option('alejandro_ia_deepl_key');
    }

    /**
     * Traduit un texte vers la langue cible
     */
    public function translate($text, $target_lang) {
        try {
            if (empty($this->api_key)) {
                throw new Exception('Clé API DeepL non configurée');
            }

            $response = wp_remote_post($this->api_url, [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $this->api_key,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'text' => $text,
                    'target_lang' => strtoupper($target_lang),
                ],
                'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                throw new Exception('Erreur de traduction: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!isset($body['translations'][0]['text'])) {
                throw new Exception('Format de réponse DeepL invalide');
            }

            return $body['translations'][0]['text'];

        } catch (Exception $e) {
            error_log('[Alejandro IA] Translation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Détecte la langue d'un texte
     */
    public function detect_language($text) {
        try {
            if (empty($this->api_key)) {
                throw new Exception('Clé API DeepL non configurée');
            }

            $response = wp_remote_post('https://api-free.deepl.com/v2/detect', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $this->api_key,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'text' => $text,
                ],
                'timeout' => 30,
            ]);

            if (is_wp_error($response)) {
                throw new Exception('Erreur de détection: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (!isset($body['language'])) {
                throw new Exception('Format de réponse DeepL invalide');
            }

            return strtolower($body['language']);

        } catch (Exception $e) {
            error_log('[Alejandro IA] Language Detection Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
