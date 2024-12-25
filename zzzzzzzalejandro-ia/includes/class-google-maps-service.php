<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Service Google Maps pour Alejandro IA
 * Gère toutes les interactions avec les APIs Google Maps
 */
class Alejandro_Google_Maps_Service {
    private $api_key;
    private $cache;
    private $base_url = 'https://maps.googleapis.com';
    
    // Centre approximatif de la zone d'activité du club
    private $club_center = [
        'lat' => 36.7340,  // Almuñécar
        'lng' => -3.6894
    ];

    // Rayon maximum en km pour les recherches (zone d'Andalousie)
    private $max_radius = 200;

    public function __construct() {
        $this->api_key = get_option('alejandro_ia_google_key');
                $this->cache = new Alejandro_Cache();
    }

    /**
     * Recherche des lieux à proximité
     * 
     * @param string $query Terme de recherche
     * @param array $location Coordonnées [lat, lng] du centre de recherche
     * @param int $radius Rayon de recherche en mètres
     * @param string $type Type de lieu (restaurant, hotel, etc.)
     * @return array|WP_Error Résultats de la recherche
     */
    public function search_places($query, $location = null, $radius = 50000, $type = '') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'alejandro-ia'));
        }

        // Si aucune localisation n'est fournie, utiliser le centre du club
        if (!$location) {
            $location = $this->club_center;
        }

        // Vérifier que le rayon ne dépasse pas la zone d'activité
        $radius = min($radius, $this->max_radius * 1000);

        // Vérifier le cache
        $cache_key = md5("places_{$query}_{$location['lat']}_{$location['lng']}_{$radius}_{$type}");
        $cached_results = $this->cache->get($cache_key, 'places');
        if ($cached_results !== false) {
            return $cached_results;
        }

        // Préparer les données pour l'API Places v2
        $data = [
            'textQuery' => $query,
            'locationBias' => [
                'circle' => [
                    'center' => [
                        'latitude' => $location['lat'],
                        'longitude' => $location['lng']
                    ],
                    'radius' => (float)$radius
                ]
            ]
        ];

        if (!empty($type)) {
            $data['includedTypes'] = [$type];
        }

        $headers = [
            'Content-Type: application/json',
            'X-Goog-Api-Key: ' . $this->api_key,
            'X-Goog-FieldMask: places.displayName,places.formattedAddress,places.location,places.types,places.rating,places.photos',
            'Referer: https://clubcostalmunecar.com/'
        ];

        $args = [
            'headers' => $headers,
            'body' => json_encode($data),
            'method' => 'POST',
            'sslverify' => false // Pour le développement uniquement
        ];

        $response = wp_remote_post('https://places.googleapis.com/v1/places:searchText', $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['places'])) {
            return new WP_Error('api_error', __('Erreur lors de la recherche de lieux', 'alejandro-ia'));
        }

        // Mettre en cache les résultats
        $this->cache->set($cache_key, $data['places'], 'places');

        return $data['places'];
    }

    /**
     * Obtient les détails d'un lieu
     * 
     * @param string $place_id ID Google du lieu
     * @return array|WP_Error Détails du lieu
     */
    public function get_place_details($place_id) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'alejandro-ia'));
        }

        // Vérifier le cache
        $cache_key = md5("place_details_{$place_id}");
        $cached_details = $this->cache->get($cache_key, 'place_details');
        if ($cached_details !== false) {
            return $cached_details;
        }

        $params = [
            'place_id' => $place_id,
            'key' => $this->api_key,
            'language' => $this->get_current_language(),
            'fields' => 'name,formatted_address,geometry,photos,rating,opening_hours,website,formatted_phone_number'
        ];

        $response = wp_remote_get($this->base_url . '/maps/api/place/details/json?' . http_build_query($params));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['result'])) {
            return new WP_Error('api_error', __('Erreur lors de la récupération des détails du lieu', 'alejandro-ia'));
        }

        // Mettre en cache les résultats
        $this->cache->set($cache_key, $data['result'], 'place_details');

        return $data['result'];
    }

    /**
     * Calcule l'itinéraire entre deux points
     * 
     * @param array $origin Point de départ [lat, lng]
     * @param array $destination Point d'arrivée [lat, lng]
     * @param string $mode Mode de transport (driving, walking, bicycling, transit)
     * @return array|WP_Error Instructions de l'itinéraire
     */
    public function get_directions($origin, $destination, $mode = 'driving') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'alejandro-ia'));
        }

        // Vérifier le cache
        $cache_key = md5("directions_{$origin['lat']}_{$origin['lng']}_{$destination['lat']}_{$destination['lng']}_{$mode}");
        $cached_directions = $this->cache->get($cache_key, 'directions');
        if ($cached_directions !== false) {
            return $cached_directions;
        }

        $params = [
            'origin' => "{$origin['lat']},{$origin['lng']}",
            'destination' => "{$destination['lat']},{$destination['lng']}",
            'mode' => $mode,
            'key' => $this->api_key,
            'language' => $this->get_current_language()
        ];

        $response = wp_remote_get($this->base_url . '/maps/api/directions/json?' . http_build_query($params));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['routes'][0])) {
            return new WP_Error('api_error', __('Erreur lors du calcul de l\'itinéraire', 'alejandro-ia'));
        }

        // Mettre en cache les résultats
        $this->cache->set($cache_key, $data['routes'][0], 'directions');

        return $data['routes'][0];
    }

    /**
     * Obtient une image de carte statique
     * 
     * @param array $center Centre de la carte [lat, lng]
     * @param int $zoom Niveau de zoom
     * @param array $markers Tableau de marqueurs [lat, lng, label]
     * @param string $size Taille de l'image (ex: '600x300')
     * @return string URL de l'image de carte
     */
    public function get_static_map($center, $zoom = 14, $markers = [], $size = '600x300') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'alejandro-ia'));
        }

        $params = [
            'center' => "{$center['lat']},{$center['lng']}",
            'zoom' => $zoom,
            'size' => $size,
            'key' => $this->api_key,
            'scale' => 2  // Pour les écrans haute résolution
        ];

        // Ajouter les marqueurs
        foreach ($markers as $marker) {
            $params['markers'][] = "{$marker['lat']},{$marker['lng']}";
        }

        return $this->base_url . '/maps/api/staticmap?' . http_build_query($params);
    }

    /**
     * Calcule la distance et le temps de trajet entre deux points
     * 
     * @param array $origin Point de départ [lat, lng]
     * @param array $destination Point d'arrivée [lat, lng]
     * @param string $mode Mode de transport
     * @return array|WP_Error Distance et durée
     */
    public function get_distance_matrix($origin, $destination, $mode = 'driving') {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'alejandro-ia'));
        }

        // Vérifier le cache
        $cache_key = md5("distance_{$origin['lat']}_{$origin['lng']}_{$destination['lat']}_{$destination['lng']}_{$mode}");
        $cached_distance = $this->cache->get($cache_key, 'distance');
        if ($cached_distance !== false) {
            return $cached_distance;
        }

        $params = [
            'origins' => "{$origin['lat']},{$origin['lng']}",
            'destinations' => "{$destination['lat']},{$destination['lng']}",
            'mode' => $mode,
            'key' => $this->api_key,
            'language' => $this->get_current_language()
        ];

        $response = wp_remote_get($this->base_url . '/maps/api/distancematrix/json?' . http_build_query($params));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['rows'][0]['elements'][0])) {
            return new WP_Error('api_error', __('Erreur lors du calcul de la distance', 'alejandro-ia'));
        }

        $result = $data['rows'][0]['elements'][0];

        // Mettre en cache les résultats
        $this->cache->set($cache_key, $result, 'distance');

        return $result;
    }

    /**
     * Géocode une adresse
     * 
     * @param string $address Adresse à géocoder
     * @return array|WP_Error Coordonnées [lat, lng]
     */
    public function geocode($address) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('Clé API Google Maps non configurée', 'alejandro-ia'));
        }

        // Vérifier le cache
        $cache_key = md5("geocode_{$address}");
        $cached_coords = $this->cache->get($cache_key, 'geocode');
        if ($cached_coords !== false) {
            return $cached_coords;
        }

        $params = [
            'address' => $address,
            'key' => $this->api_key,
            'language' => $this->get_current_language()
        ];

        $response = wp_remote_get($this->base_url . '/maps/api/geocode/json?' . http_build_query($params));

        if (is_wp_error($response)) {
            return $response;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($data['results'][0]['geometry']['location'])) {
            return new WP_Error('api_error', __('Erreur lors du géocodage de l\'adresse', 'alejandro-ia'));
        }

        $location = $data['results'][0]['geometry']['location'];

        // Mettre en cache les résultats
        $this->cache->set($cache_key, $location, 'geocode');

        return $location;
    }

    /**
     * Obtient la langue courante pour les requêtes
     */
    private function get_current_language() {
        $locale = get_locale();
        return substr($locale, 0, 2);
    }

    /**
     * Vérifie si un point est dans la zone d'activité du club
     */
    public function is_in_club_area($lat, $lon) {
        $distance = $this->calculate_distance(
            $lat,
            $lon,
            $this->club_center['lat'],
            $this->club_center['lng']
        );

        return $distance <= $this->max_radius;
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
}
