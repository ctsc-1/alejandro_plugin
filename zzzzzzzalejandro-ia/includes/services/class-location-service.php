<?php
/**
 * Service de géolocalisation et points d'intérêt
 */
class Alejandro_Location_Service {
    private $google_key;
    private $local_data_file;
    private $cache;

    public function __construct() {
        $this->google_key = get_option('alejandro_ia_google_key');
        $this->local_data_file = plugin_dir_path(dirname(__FILE__)) . 'data/local_pois.json';
        $this->cache = new Alejandro_Cache('location');
    }

    /**
     * Obtient les coordonnées d'un lieu
     */
    public function geocode($address) {
        try {
            $cache_key = 'geocode_' . md5($address);
            $cached = $this->cache->get($cache_key);
            if ($cached) return $cached;

            $url = 'https://maps.googleapis.com/maps/api/geocode/json?' . http_build_query([
                'address' => $address,
                'key' => $this->google_key,
                'region' => 'es'
            ]);

            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                throw new Exception('Erreur de géocodage: ' . $response->get_error_message());
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data['status'] !== 'OK') {
                throw new Exception('Erreur de géocodage: ' . $data['status']);
            }

            $result = [
                'lat' => $data['results'][0]['geometry']['location']['lat'],
                'lng' => $data['results'][0]['geometry']['location']['lng'],
                'formatted_address' => $data['results'][0]['formatted_address']
            ];

            $this->cache->set($cache_key, $result, 86400); // Cache 24h
            return $result;

        } catch (Exception $e) {
            error_log('[Alejandro IA] Geocoding Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calcule l'itinéraire entre deux points
     */
    public function get_directions($origin, $destination, $mode = 'driving') {
        try {
            $cache_key = 'directions_' . md5($origin . $destination . $mode);
            $cached = $this->cache->get($cache_key);
            if ($cached) return $cached;

            // Géocoder l'origine et la destination si ce sont des adresses
            if (!is_array($origin)) {
                $origin = $this->geocode($origin);
            }
            if (!is_array($destination)) {
                $destination = $this->geocode($destination);
            }

            $url = 'https://maps.googleapis.com/maps/api/directions/json?' . http_build_query([
                'origin' => $origin['lat'] . ',' . $origin['lng'],
                'destination' => $destination['lat'] . ',' . $destination['lng'],
                'mode' => $mode,
                'key' => $this->google_key,
                'language' => get_locale(),
                'region' => 'es'
            ]);

            $response = wp_remote_get($url);
            if (is_wp_error($response)) {
                throw new Exception('Erreur d\'itinéraire: ' . $response->get_error_message());
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);
            if ($data['status'] !== 'OK') {
                throw new Exception('Erreur d\'itinéraire: ' . $data['status']);
            }

            $route = $data['routes'][0]['legs'][0];
            $result = [
                'distance' => $route['distance']['text'],
                'duration' => $route['duration']['text'],
                'steps' => array_map(function($step) {
                    return [
                        'instruction' => strip_tags($step['html_instructions']),
                        'distance' => $step['distance']['text'],
                        'duration' => $step['duration']['text']
                    ];
                }, $route['steps'])
            ];

            $this->cache->set($cache_key, $result, 3600); // Cache 1h
            return $result;

        } catch (Exception $e) {
            error_log('[Alejandro IA] Directions Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recherche des points d'intérêt
     */
    public function find_places($query, $location = null, $type = null, $radius = 5000) {
        try {
            // D'abord chercher dans notre base locale
            $local_results = $this->search_local_places($query, $type);
            
            // Ensuite chercher via Google Places
            $google_results = $this->search_google_places($query, $location, $type, $radius);
            
            // Fusionner et trier les résultats
            $results = array_merge($local_results, $google_results);
            
            // Trier par pertinence et distance si location fournie
            if ($location) {
                usort($results, function($a, $b) use ($location) {
                    $da = $this->calculate_distance($location, ['lat' => $a['lat'], 'lng' => $a['lng']]);
                    $db = $this->calculate_distance($location, ['lat' => $b['lat'], 'lng' => $b['lng']]);
                    return $da - $db;
                });
            }
            
            return $results;

        } catch (Exception $e) {
            error_log('[Alejandro IA] Places Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recherche dans la base de données locale
     */
    private function search_local_places($query, $type = null) {
        if (!file_exists($this->local_data_file)) {
            return [];
        }

        $data = json_decode(file_get_contents($this->local_data_file), true);
        if (!$data) return [];

        $results = [];
        foreach ($data as $place) {
            // Vérifier le type si spécifié
            if ($type && (!isset($place['type']) || $place['type'] !== $type)) {
                continue;
            }

            // Rechercher dans le nom et la description
            if (stripos($place['name'], $query) !== false || 
                stripos($place['description'], $query) !== false) {
                $results[] = [
                    'id' => $place['id'],
                    'name' => $place['name'],
                    'type' => $place['type'],
                    'address' => $place['address'],
                    'lat' => $place['lat'],
                    'lng' => $place['lng'],
                    'rating' => $place['rating'] ?? null,
                    'description' => $place['description'],
                    'source' => 'local'
                ];
            }
        }

        return $results;
    }

    /**
     * Recherche via Google Places API
     */
    private function search_google_places($query, $location = null, $type = null, $radius = 5000) {
        $cache_key = 'places_' . md5($query . json_encode($location) . $type . $radius);
        $cached = $this->cache->get($cache_key);
        if ($cached) return $cached;

        $params = [
            'query' => $query,
            'key' => $this->google_key,
            'language' => get_locale()
        ];

        if ($location) {
            $params['location'] = $location['lat'] . ',' . $location['lng'];
            $params['radius'] = $radius;
        }

        if ($type) {
            $params['type'] = $type;
        }

        $url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query($params);

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            throw new Exception('Erreur Places API: ' . $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if ($data['status'] !== 'OK' && $data['status'] !== 'ZERO_RESULTS') {
            throw new Exception('Erreur Places API: ' . $data['status']);
        }

        $results = [];
        foreach ($data['results'] as $place) {
            $results[] = [
                'id' => $place['place_id'],
                'name' => $place['name'],
                'type' => $place['types'][0],
                'address' => $place['formatted_address'],
                'lat' => $place['geometry']['location']['lat'],
                'lng' => $place['geometry']['location']['lng'],
                'rating' => $place['rating'] ?? null,
                'source' => 'google'
            ];
        }

        $this->cache->set($cache_key, $results, 3600); // Cache 1h
        return $results;
    }

    /**
     * Calcule la distance entre deux points
     */
    private function calculate_distance($point1, $point2) {
        $lat1 = deg2rad($point1['lat']);
        $lat2 = deg2rad($point2['lat']);
        $lng1 = deg2rad($point1['lng']);
        $lng2 = deg2rad($point2['lng']);
        
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlng/2) * sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return 6371 * $c; // Rayon de la Terre en km
    }
}
