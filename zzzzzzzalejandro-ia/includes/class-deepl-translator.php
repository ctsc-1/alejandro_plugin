<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe de traduction DeepL pour Alejandro IA
 * Utilisée uniquement pour les traductions à la demande dans les conversations
 * Les traductions de l'interface sont gérées par TranslatePress
 */
class Alejandro_DeepL_Translator {
    private $api_key;
    private $api_url = 'https://api.deepl.com/v2';
    private $cache;
    
    // Langues principales du Club Costa Tropical
    private $club_languages = [
        'es' => 'Spanish',   // Langue principale
        'fr' => 'French',    // Deuxième langue la plus utilisée
        'en' => 'English'    // Troisième langue
    ];

    public function __construct() {
        $this->api_key = get_option('alejandro_ia_deepl_key');
                $this->cache = new Alejandro_Cache();
    }

    /**
     * Traduit un texte dans la langue cible
     * Utilisé quand un membre demande spécifiquement une traduction
     * 
     * @param string $text Texte à traduire
     * @param string $target_lang Code de la langue cible (ex: 'FR' pour français)
     * @param string $source_lang Code de la langue source (requis)
     * @return string|WP_Error Texte traduit ou erreur
     */
    public function translate($text, $target_lang, $source_lang) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API DeepL non configurée', 'alejandro-ia'));
        }

        // Vérifier que les langues sont supportées
        if (!isset($this->club_languages[strtolower($source_lang)]) || 
            !isset($this->club_languages[strtolower($target_lang)])) {
            return new WP_Error('unsupported_language', 
                __('Traduction disponible uniquement en Español, Français et English', 'alejandro-ia')
            );
        }

        // Vérifier le cache
        $cache_key = md5($text . $target_lang . $source_lang);
        $cached_translation = $this->cache->get($cache_key, 'translation', $target_lang);
        if ($cached_translation !== false) {
            return $cached_translation;
        }

        // Préparer les paramètres
        $params = [
            'text' => $text,
            'target_lang' => strtoupper($target_lang),
            'source_lang' => strtoupper($source_lang),
            'preserve_formatting' => 1
        ];

        // Faire la requête à l'API
        $response = wp_remote_post($this->api_url . '/translate', [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $this->api_key,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => $params,
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Erreur de décodage JSON', 'alejandro-ia'));
        }

        if (!isset($data['translations'][0]['text'])) {
            return new WP_Error('translation_error', __('Erreur lors de la traduction', 'alejandro-ia'));
        }

        $translation = $data['translations'][0]['text'];

        // Mettre en cache la traduction
        $this->cache->set($cache_key, $translation, 'translation', $target_lang);

        return $translation;
    }

    /**
     * Vérifie si une langue est supportée par le club
     */
    public function is_club_language($lang_code) {
        return isset($this->club_languages[strtolower($lang_code)]);
    }

    /**
     * Obtient la liste des langues du club
     */
    public function get_club_languages() {
        return $this->club_languages;
    }

    /**
     * Obtient l'utilisation de l'API
     */
    public function get_usage() {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API DeepL non configurée', 'alejandro-ia'));
        }

        $response = wp_remote_get($this->api_url . '/usage', [
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $this->api_key
            ],
            'timeout' => 15
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
}
