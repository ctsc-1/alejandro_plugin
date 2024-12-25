<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/interfaces/interface-weather-provider.php';

class AEMET_Provider implements Weather_Provider_Interface {
    private $api_key;
    private $api_url = 'https://opendata.aemet.es/opendata/api';
    private $cache;
    private $language = 'es';

    public function __construct() {
        $this->api_key = get_option('alejandro_ia_aemet_key');
        $this->cache = new Alejandro_Cache();
        $this->language = $this->get_current_language();
    }

    /**
     * Obtient la langue actuelle
     */
    private function get_current_language() {
        $locale = get_locale();
        $supported_languages = ['es', 'fr', 'en'];
        $lang = substr($locale, 0, 2);
        return in_array($lang, $supported_languages) ? $lang : 'es';
    }

    /**
     * Fait un appel à l'API AEMET
     */
    private function make_api_request($endpoint, $params = [], $cache_type = 'weather') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API AEMET non configurée', 'alejandro-ia'));
        }

        $url = add_query_arg($params, $this->api_url . $endpoint);
        $cache_key = md5($url);

        // Vérifier le cache
        $cached_data = $this->cache->get($cache_key, $cache_type, $this->language);
        if ($cached_data !== false) {
            return $cached_data;
        }

        $response = wp_remote_get($url, [
            'headers' => [
                'api_key' => $this->api_key,
                'Accept' => 'application/json'
            ],
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

        // Pour AEMET, nous devons souvent faire une deuxième requête pour obtenir les données réelles
        if (isset($data['datos']) && filter_var($data['datos'], FILTER_VALIDATE_URL)) {
            $data_response = wp_remote_get($data['datos']);
            if (!is_wp_error($data_response)) {
                $data = json_decode(wp_remote_retrieve_body($data_response), true);
            }
        }

        // Mettre en cache
        $this->cache->set($cache_key, $data, $cache_type, $this->language);

        return $data;
    }

    /**
     * Trouve le code de station AEMET le plus proche
     */
    private function find_nearest_station($lat, $lon) {
        // Cache des stations pour éviter des appels répétés
        $stations_cache_key = 'aemet_stations';
        $stations = $this->cache->get($stations_cache_key, 'weather_static', $this->language);

        if ($stations === false) {
            $stations_response = $this->make_api_request('/valores/climatologicos/inventarioestaciones/todasestaciones');
            if (!is_wp_error($stations_response)) {
                $stations = $stations_response;
                $this->cache->set($stations_cache_key, $stations, 'weather_static', $this->language);
            }
        }

        if (empty($stations)) {
            return false;
        }

        $nearest_station = null;
        $min_distance = PHP_FLOAT_MAX;

        foreach ($stations as $station) {
            $distance = $this->calculate_distance(
                $lat, 
                $lon, 
                $station['latitud'], 
                $station['longitud']
            );

            if ($distance < $min_distance) {
                $min_distance = $distance;
                $nearest_station = $station;
            }
        }

        return $nearest_station ? $nearest_station['indicativo'] : false;
    }

    /**
     * Calcule la distance entre deux points (formule haversine)
     */
    private function calculate_distance($lat1, $lon1, $lat2, $lon2) {
        $earth_radius = 6371; // km

        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $d_lat = $lat2 - $lat1;
        $d_lon = $lon2 - $lon1;

        $a = sin($d_lat/2) * sin($d_lat/2) + cos($lat1) * cos($lat2) * sin($d_lon/2) * sin($d_lon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earth_radius * $c;
    }

    /**
     * Obtient les prévisions météo actuelles
     */
    public function get_current_weather($lat, $lon) {
        $station_id = $this->find_nearest_station($lat, $lon);
        if (!$station_id) {
            return new WP_Error('no_station', __('Aucune station météo trouvée à proximité', 'alejandro-ia'));
        }

        return $this->make_api_request('/observacion/convencional/datos/estacion/' . $station_id);
    }

    /**
     * Obtient les prévisions météo pour les prochains jours
     */
    public function get_forecast($lat, $lon, $days = 5) {
        $municipality_id = $this->find_nearest_municipality($lat, $lon);
        if (!$municipality_id) {
            return new WP_Error('no_municipality', __('Aucune municipalité trouvée à proximité', 'alejandro-ia'));
        }

        $response = $this->make_api_request('/prediccion/especifica/municipio/diaria/' . $municipality_id);

        if (is_wp_error($response)) {
            return $response;
        }

        // Limiter le nombre de jours
        if (isset($response['prediccion']['dia'])) {
            $response['prediccion']['dia'] = array_slice($response['prediccion']['dia'], 0, $days);
        }

        return $response;
    }

    /**
     * Obtient les alertes météo pour une zone
     */
    public function get_alerts($lat, $lon) {
        $province_id = $this->find_province($lat, $lon);
        if (!$province_id) {
            return new WP_Error('no_province', __('Province non trouvée', 'alejandro-ia'));
        }

        return $this->make_api_request('/avisos_cap/ultimoelaborado/area/' . $province_id);
    }

    /**
     * Obtient les conditions marines
     */
    public function get_marine_conditions($lat, $lon) {
        $coastal_area = $this->find_nearest_coastal_area($lat, $lon);
        if (!$coastal_area) {
            return new WP_Error('no_coastal_area', __('Zone côtière non trouvée', 'alejandro-ia'));
        }

        return $this->make_api_request('/prediccion/maritima/costera/costa/' . $coastal_area);
    }

    /**
     * Trouve la municipalité la plus proche
     */
    private function find_nearest_municipality($lat, $lon) {
        // Implémentation de la recherche de municipalité
        // À compléter avec les données réelles d'AEMET
        return '28079'; // Madrid par défaut
    }

    /**
     * Trouve la province
     */
    private function find_province($lat, $lon) {
        // Implémentation de la recherche de province
        // À compléter avec les données réelles d'AEMET
        return 'mad'; // Madrid par défaut
    }

    /**
     * Trouve la zone côtière la plus proche
     */
    private function find_nearest_coastal_area($lat, $lon) {
        // Implémentation de la recherche de zone côtière
        // À compléter avec les données réelles d'AEMET
        return '42'; // Costa del Sol par défaut
    }

    /**
     * Traduit la description météo
     */
    public function translate_weather_description($description) {
        if ($this->language === 'es') {
            return $description;
        }

        $translations = [
            'fr' => [
                'Despejado' => 'Dégagé',
                'Nuboso' => 'Nuageux',
                'Lluvia' => 'Pluie',
                // Ajouter plus de traductions selon besoin
            ],
            'en' => [
                'Despejado' => 'Clear',
                'Nuboso' => 'Cloudy',
                'Lluvia' => 'Rain',
                // Ajouter plus de traductions selon besoin
            ]
        ];

        return isset($translations[$this->language][$description]) 
            ? $translations[$this->language][$description] 
            : $description;
    }
}
