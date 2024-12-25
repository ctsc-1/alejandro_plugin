<?php
/**
 * Gestionnaire de services météo
 */
class Alejandro_Weather_Manager {
    private $provider;
    private $cache;

    public function __construct() {
        require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-aemet-provider.php';
                
        $this->cache = new Alejandro_Cache();
        $this->provider = new AEMET_Provider();
    }

    /**
     * Obtient les informations météo complètes
     */
    public function get_weather_info($lat, $lon) {
        $cache_key = md5("weather_info_{$lat}_{$lon}");
        $cached_data = $this->cache->get($cache_key, 'weather_combined', get_locale());

        if ($cached_data !== false) {
            return $cached_data;
        }

        $weather_data = $this->get_aemet_data($lat, $lon);

        if (!is_wp_error($weather_data)) {
            $this->cache->set($cache_key, $weather_data, 'weather_combined', get_locale());
        }

        return $weather_data;
    }

    /**
     * Obtient les données d'AEMET
     */
    private function get_aemet_data($lat, $lon) {
        $current = $this->provider->get_current_weather($lat, $lon);
        if (is_wp_error($current)) {
            return $current;
        }

        $forecast = $this->provider->get_forecast($lat, $lon);
        $alerts = $this->provider->get_alerts($lat, $lon);
        $marine = $this->provider->get_marine_conditions($lat, $lon);

        return [
            'provider' => 'aemet',
            'current' => $current,
            'forecast' => $forecast,
            'alerts' => $alerts,
            'marine' => $marine
        ];
    }

    /**
     * Formate les données météo pour l'affichage
     */
    public function format_weather_data($weather_data) {
        if (is_wp_error($weather_data)) {
            return $weather_data;
        }

        $formatted = [
            'current' => [
                'temperature' => '',
                'description' => '',
                'humidity' => '',
                'wind_speed' => '',
                'wind_direction' => '',
                'pressure' => '',
                'visibility' => '',
                'icon' => ''
            ],
            'forecast' => [],
            'alerts' => [],
            'marine' => [
                'wave_height' => '',
                'wave_direction' => '',
                'water_temperature' => '',
                'warnings' => []
            ]
        ];

        $this->format_aemet_data($weather_data, $formatted);

        return $formatted;
    }

    /**
     * Formate les données AEMET
     */
    private function format_aemet_data($weather_data, &$formatted) {
        // Similaire à format_openweather_data mais adapté au format AEMET
        // À compléter avec le format exact des données AEMET
    }

    /**
     * Obtient la direction du vent en texte
     */
    private function get_wind_direction($degrees) {
        $directions = [
            'N' => ['min' => 337.5, 'max' => 22.5],
            'NE' => ['min' => 22.5, 'max' => 67.5],
            'E' => ['min' => 67.5, 'max' => 112.5],
            'SE' => ['min' => 112.5, 'max' => 157.5],
            'S' => ['min' => 157.5, 'max' => 202.5],
            'SO' => ['min' => 202.5, 'max' => 247.5],
            'O' => ['min' => 247.5, 'max' => 292.5],
            'NO' => ['min' => 292.5, 'max' => 337.5]
        ];

        foreach ($directions as $dir => $range) {
            if ($degrees > $range['min'] && $degrees <= $range['max']) {
                return $dir;
            }
        }

        return 'N';
    }
}
