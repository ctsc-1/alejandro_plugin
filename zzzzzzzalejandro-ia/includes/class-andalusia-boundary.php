<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Andalusia_Boundary {
    // Coordonnées approximatives des frontières de l'Andalousie
    private $boundaries = [
        'north' => 38.7283,  // Latitude nord maximale
        'south' => 35.8456,  // Latitude sud minimale
        'east' => -1.6163,   // Longitude est maximale
        'west' => -7.5226    // Longitude ouest minimale
    ];

    // Liste des provinces d'Andalousie avec leurs capitales
    private $provinces = [
        'Almería' => ['lat' => 36.8381, 'lon' => -2.4597],
        'Cádiz' => ['lat' => 36.5271, 'lon' => -6.2886],
        'Córdoba' => ['lat' => 37.8882, 'lon' => -4.7794],
        'Granada' => ['lat' => 37.1773, 'lon' => -3.5986],
        'Huelva' => ['lat' => 37.2571, 'lon' => -6.9495],
        'Jaén' => ['lat' => 37.7796, 'lon' => -3.7849],
        'Málaga' => ['lat' => 36.7213, 'lon' => -4.4217],
        'Sevilla' => ['lat' => 37.3891, 'lon' => -5.9845]
    ];

    /**
     * Vérifie si les coordonnées sont en Andalousie
     */
    public function is_in_andalusia($lat, $lon) {
        return $lat <= $this->boundaries['north'] &&
               $lat >= $this->boundaries['south'] &&
               $lon <= $this->boundaries['east'] &&
               $lon >= $this->boundaries['west'];
    }

    /**
     * Obtient un message d'erreur humoristique
     */
    public function get_out_of_bounds_message($lang = 'es') {
        $messages = [
            'es' => [
                "¡Ay, lo siento! Aunque mi conocimiento es vasto, mi corazón y experiencia están en Andalucía. ",
                "Como buen andaluz, prefiero no aventurarme más allá de nuestras fronteras. ",
                "¡Qué pena! Esa ubicación está fuera de mi querida Andalucía. ",
                "Mi GPS interior solo funciona en Andalucía, ¡es que soy muy de mi tierra!"
            ],
            'fr' => [
                "Désolé ! Bien que mes connaissances soient vastes, mon cœur et mon expertise sont en Andalousie. ",
                "En bon andalou, je préfère ne pas m'aventurer au-delà de nos frontières. ",
                "Quel dommage ! Cet endroit est en dehors de ma chère Andalousie. ",
                "Mon GPS intérieur ne fonctionne qu'en Andalousie, je suis très attaché à ma région !"
            ],
            'en' => [
                "I'm sorry! Although my knowledge is vast, my heart and expertise are in Andalusia. ",
                "As a good Andalusian, I prefer not to venture beyond our borders. ",
                "What a shame! That location is outside my beloved Andalusia. ",
                "My internal GPS only works in Andalusia, I'm very attached to my land!"
            ]
        ];

        // Sélectionner un message aléatoire
        $lang_messages = isset($messages[$lang]) ? $messages[$lang] : $messages['es'];
        return $lang_messages[array_rand($lang_messages)];
    }

    /**
     * Trouve la ville la plus proche en Andalousie
     */
    public function get_nearest_city($lat, $lon) {
        $nearest_city = null;
        $min_distance = PHP_FLOAT_MAX;

        foreach ($this->provinces as $city => $coords) {
            $distance = $this->calculate_distance($lat, $lon, $coords['lat'], $coords['lon']);
            if ($distance < $min_distance) {
                $min_distance = $distance;
                $nearest_city = $city;
            }
        }

        return $nearest_city;
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
     * Suggère une ville proche en Andalousie
     */
    public function get_suggestion_message($lat, $lon, $lang = 'es') {
        $nearest_city = $this->get_nearest_city($lat, $lon);
        
        $messages = [
            'es' => "¿Por qué no consultas el tiempo en {$nearest_city}? ¡Es una ciudad maravillosa de nuestra tierra!",
            'fr' => "Pourquoi ne pas consulter la météo à {$nearest_city} ? C'est une ville merveilleuse de notre région !",
            'en' => "Why not check the weather in {$nearest_city}? It's a wonderful city in our region!"
        ];

        return isset($messages[$lang]) ? $messages[$lang] : $messages['es'];
    }
}
