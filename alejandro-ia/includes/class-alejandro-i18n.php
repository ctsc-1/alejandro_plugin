<?php
/**
 * Gestion de l'internationalisation pour Alejandro IA
 */
class Alejandro_I18n {
    private static $instance = null;
    private $paths;
    private $current_locale;
    private $language_paths = [
        'fr_FR' => '/fr/',
        'es_ES' => '/es/',
        'en_US' => '/en/'
    ];
    private $default_language = 'fr_FR';
    private $current_path;

    private function __construct() {
        $this->paths = Alejandro_Paths::get_instance();
        $this->current_path = $this->get_current_path();
        $this->current_locale = $this->detect_locale_from_path();
        
        // Charger les traductions
        add_action('init', [$this, 'load_plugin_textdomain']);
        
        // Filtrer la locale pour le plugin
        add_filter('plugin_locale', [$this, 'get_locale'], 10, 2);
        
        // Ajouter le filtre pour les URLs
        add_filter('home_url', [$this, 'filter_home_url'], 10, 2);
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function get_current_path() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            return rtrim($path, '/') . '/';
        }
        return '/';
    }

    private function detect_locale_from_path() {
        $path_parts = explode('/', trim($this->current_path, '/'));
        $lang_code = !empty($path_parts[0]) ? $path_parts[0] : '';
        
        switch ($lang_code) {
            case 'fr':
                return 'fr_FR';
            case 'es':
                return 'es_ES';
            case 'en':
                return 'en_US';
            default:
                return $this->default_language;
        }
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'alejandro-ia',
            false,
            dirname(plugin_basename($this->paths->get_plugin_dir())) . '/languages/'
        );
    }

    public function get_locale($locale, $domain) {
        if ($domain === 'alejandro-ia') {
            return $this->current_locale;
        }
        return $locale;
    }

    public function filter_home_url($url, $path) {
        // Ne pas modifier les URLs des assets
        if (strpos($path, 'wp-') === 0 || strpos($path, 'assets/') !== false) {
            return $url;
        }

        $lang_path = $this->language_paths[$this->current_locale];
        if (!empty($path)) {
            return home_url($lang_path . ltrim($path, '/'));
        }
        return home_url($lang_path);
    }

    public function get_current_language() {
        return $this->current_locale;
    }

    public function get_language_path($locale = null) {
        if ($locale === null) {
            $locale = $this->current_locale;
        }
        return isset($this->language_paths[$locale]) ? $this->language_paths[$locale] : $this->language_paths[$this->default_language];
    }

    public function get_language_urls() {
        $urls = [];
        $current_url = $this->get_current_url_without_language();
        
        foreach ($this->language_paths as $locale => $path) {
            $urls[$locale] = home_url($path . $current_url);
        }
        
        return $urls;
    }

    private function get_current_url_without_language() {
        $path = $this->current_path;
        foreach ($this->language_paths as $lang_path) {
            if (strpos($path, $lang_path) === 0) {
                return substr($path, strlen($lang_path));
            }
        }
        return $path;
    }

    public function switch_language($locale) {
        if (!isset($this->language_paths[$locale])) {
            return false;
        }

        $current_url = $this->get_current_url_without_language();
        $new_url = home_url($this->language_paths[$locale] . $current_url);
        
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'alejandro_language', $locale);
        }

        wp_redirect($new_url);
        exit;
    }
}
