<?php
/**
 * Service météo utilisant OpenWeather et AEMET
 */
class Alejandro_Weather_Service {
    private $openweather_key;
    private $aemet_key;

    public function __construct() {
        $this->openweather_key = get_option('alejandro_ia_weather_key');
        $this->aemet_key = get_option('alejandro_ia_aemet_key');
    }

    /**
     * Obtient la météo pour une localisation
     */
    public function get_weather($location, $language = 'fr') {
        try {
            // D'abord essayer OpenWeather
            $weather = $this->get_openweather($location, $language);
            if ($weather) {
                return $weather;
            }

            // Si pas de résultat, essayer AEMET pour l'Espagne
            return $this->get_aemet_weather($location);

        } catch (Exception $e) {
            error_log('[Alejandro IA] Weather Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtient la météo via OpenWeather
     */
    private function get_openweather($location, $language) {
        if (empty($this->openweather_key)) {
            return null;
        }

        $response = wp_remote_get('https://api.openweathermap.org/data/2.5/weather?' . http_build_query([
            'q' => $location,
            'appid' => $this->openweather_key,
            'units' => 'metric',
            'lang' => $language
        ]));

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['main']) || !isset($body['weather'][0])) {
            return null;
        }

        return [
            'provider' => 'openweather',
            'temperature' => round($body['main']['temp']),
            'humidity' => $body['main']['humidity'],
            'description' => $body['weather'][0]['description'],
            'icon' => 'https://openweathermap.org/img/w/' . $body['weather'][0]['icon'] . '.png'
        ];
    }

    /**
     * Obtient la météo via AEMET (Espagne)
     */
    private function get_aemet_weather($location) {
        if (empty($this->aemet_key)) {
            return null;
        }

        // D'abord obtenir le code de la station météo
        $response = wp_remote_get('https://opendata.aemet.es/opendata/api/valores/climatologicos/inventarioestaciones/todasestaciones/', [
            'headers' => [
                'api_key' => $this->aemet_key
            ]
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['datos'])) {
            return null;
        }

        // Obtenir les données météo
        $response = wp_remote_get($body['datos'], [
            'headers' => [
                'api_key' => $this->aemet_key
            ]
        ]);

        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data) || empty($data)) {
            return null;
        }

        // Trouver la station la plus proche
        // Note: Ceci est une simplification, il faudrait implémenter un vrai calcul de distance
        $station = $data[0];

        return [
            'provider' => 'aemet',
            'temperature' => round($station['temperatura']),
            'humidity' => $station['humedadRelativa'],
            'description' => $station['estadoCielo'],
            'icon' => null // AEMET ne fournit pas d'icônes
        ];
    }
}
