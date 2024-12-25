<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Response {
    private $elevenlabs;
    private $current_language;

    public function __construct() {
        require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-elevenlabs-integration.php';
        $this->elevenlabs = new Alejandro_ElevenLabs_Integration();
        $this->current_language = $this->detect_language();
    }

    /**
     * Génère une réponse complète (texte + audio)
     */
    public function generate_response($text) {
        // Nettoyer les anciens fichiers audio
        $this->elevenlabs->cleanup_old_files();

        // Générer l'audio
        $audio = $this->elevenlabs->text_to_speech($text, $this->current_language);

        if (is_wp_error($audio)) {
            return [
                'success' => false,
                'text' => $text,
                'error' => $audio->get_error_message()
            ];
        }

        return [
            'success' => true,
            'text' => $text,
            'audio_url' => $audio['url']
        ];
    }

    /**
     * Détecte la langue actuelle du site
     */
    private function detect_language() {
        $locale = get_locale();
        $language_map = [
            'es' => ['es_ES', 'es_MX', 'es_CO'],
            'fr' => ['fr_FR', 'fr_BE', 'fr_CA'],
            'en' => ['en_US', 'en_GB', 'en_AU']
        ];

        foreach ($language_map as $lang => $locales) {
            if (in_array($locale, $locales)) {
                return $lang;
            }
        }

        return 'es'; // Langue par défaut
    }
}
