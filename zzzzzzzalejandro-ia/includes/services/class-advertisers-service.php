<?php
/**
 * Service de gestion des annonceurs du Club Costa Tropical
 */
class Alejandro_Advertisers_Service {
    private $cache;
    private $db;
    private $categories_map;

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->cache = new Alejandro_Cache('advertisers');
        
        // Mapping des catégories pour la recherche contextuelle
        $this->categories_map = [
            // Automobile
            'mechanic' => ['garage', 'mécanicien', 'réparation auto', 'taller'],
            'car_rental' => ['location voiture', 'alquiler coche'],
            'car_dealer' => ['concessionnaire', 'vente voiture'],
            
            // Santé
            'doctor' => ['médecin', 'docteur', 'clinique'],
            'dentist' => ['dentiste', 'dental'],
            'veterinary' => ['vétérinaire', 'veterinario'],
            'pharmacy' => ['pharmacie', 'farmacia'],
            
            // Immobilier
            'real_estate' => ['immobilier', 'inmobiliaria', 'location', 'vente'],
            'construction' => ['construction', 'rénovation', 'obras'],
            
            // Restauration
            'restaurant' => ['restaurant', 'restaurante', 'cuisine'],
            'bar' => ['bar', 'café', 'pub'],
            
            // Services
            'insurance' => ['assurance', 'seguro'],
            'bank' => ['banque', 'banco'],
            'lawyer' => ['avocat', 'abogado', 'juridique'],
            
            // Loisirs
            'sport' => ['sport', 'fitness', 'gym'],
            'tourism' => ['tourisme', 'excursion', 'visite'],
            
            // Shopping
            'supermarket' => ['supermarché', 'supermercado'],
            'clothing' => ['vêtements', 'ropa', 'mode'],
            
            // Beauté
            'hairdresser' => ['coiffeur', 'peluquería'],
            'beauty' => ['beauté', 'belleza', 'spa']
        ];
    }

    /**
     * Recherche des annonceurs pertinents pour une requête
     */
    public function find_relevant_advertisers($query, $category = null, $location = null, $limit = 5) {
        try {
            $cache_key = 'advertisers_' . md5($query . $category . json_encode($location));
            $cached = $this->cache->get($cache_key);
            if ($cached) return $cached;

            // Détecter les catégories à partir de la requête si non spécifiée
            $categories = [];
            if ($category) {
                $categories[] = $category;
            } else {
                $categories = $this->detect_categories($query);
            }

            // Construire la requête SQL
            $sql = "SELECT 
                    a.id,
                    a.business_name,
                    a.category,
                    a.description,
                    a.address,
                    a.phone,
                    a.email,
                    a.website,
                    a.latitude,
                    a.longitude,
                    a.membership_level,
                    a.rating,
                    a.special_offers
                FROM {$this->db->prefix}cct_advertisers a
                WHERE a.status = 'active'";

            $params = [];

            // Filtrer par catégories
            if (!empty($categories)) {
                $placeholders = array_fill(0, count($categories), '%s');
                $sql .= " AND a.category IN (" . implode(',', $placeholders) . ")";
                $params = array_merge($params, $categories);
            }

            // Filtrer par localisation si spécifiée
            if ($location) {
                $sql .= " AND (
                    6371 * acos(
                        cos(radians(%f)) * cos(radians(a.latitude)) *
                        cos(radians(a.longitude) - radians(%f)) +
                        sin(radians(%f)) * sin(radians(a.latitude))
                    ) <= %d
                )";
                $params = array_merge($params, [
                    $location['lat'],
                    $location['lng'],
                    $location['lat'],
                    isset($location['radius']) ? $location['radius'] : 10 // Rayon par défaut : 10km
                ]);
            }

            // Trier par pertinence et niveau d'adhésion
            $sql .= " ORDER BY 
                    CASE a.membership_level
                        WHEN 'premium' THEN 1
                        WHEN 'standard' THEN 2
                        ELSE 3
                    END,
                    a.rating DESC
                    LIMIT %d";
            $params[] = $limit;

            // Exécuter la requête
            $results = $this->db->get_results($this->db->prepare($sql, $params));

            // Formater les résultats
            $advertisers = array_map(function($row) {
                return [
                    'id' => $row->id,
                    'name' => $row->business_name,
                    'category' => $row->category,
                    'description' => $row->description,
                    'address' => $row->address,
                    'contact' => [
                        'phone' => $row->phone,
                        'email' => $row->email,
                        'website' => $row->website
                    ],
                    'location' => [
                        'lat' => $row->latitude,
                        'lng' => $row->longitude
                    ],
                    'membership' => $row->membership_level,
                    'rating' => $row->rating,
                    'special_offers' => json_decode($row->special_offers, true)
                ];
            }, $results);

            // Mettre en cache
            $this->cache->set($cache_key, $advertisers, 3600); // Cache 1h
            return $advertisers;

        } catch (Exception $e) {
            error_log('[Alejandro IA] Advertisers Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Détecte les catégories pertinentes à partir du texte
     */
    private function detect_categories($text) {
        $text = strtolower($text);
        $detected = [];

        foreach ($this->categories_map as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, strtolower($keyword)) !== false) {
                    $detected[] = $category;
                    break;
                }
            }
        }

        return array_unique($detected);
    }

    /**
     * Formate une réponse avec mise en avant des annonceurs
     */
    public function format_response($advertisers, $query_type) {
        $responses = [
            'mechanic' => [
                'fr' => "J'ai trouvé {count} professionnel(s) du Club Costa Tropical qui peuvent vous aider :\n\n{list}\n\nCes professionnels sont membres de notre club et offrent un service de qualité.",
                'es' => "He encontrado {count} profesional(es) del Club Costa Tropical que pueden ayudarte:\n\n{list}\n\nEstos profesionales son miembros de nuestro club y ofrecen un servicio de calidad.",
                'en' => "I found {count} Club Costa Tropical professional(s) who can help you:\n\n{list}\n\nThese professionals are members of our club and offer quality service."
            ],
            'restaurant' => [
                'fr' => "Voici {count} restaurant(s) recommandé(s) par le Club Costa Tropical :\n\n{list}\n\nTous ces établissements sont reconnus pour leur qualité.",
                'es' => "Aquí tienes {count} restaurante(s) recomendado(s) por el Club Costa Tropical:\n\n{list}\n\nTodos estos establecimientos son reconocidos por su calidad.",
                'en' => "Here are {count} restaurant(s) recommended by Club Costa Tropical:\n\n{list}\n\nAll these establishments are recognized for their quality."
            ]
            // Ajouter d'autres types de réponses selon les catégories
        ];

        $list = '';
        foreach ($advertisers as $i => $ad) {
            $list .= ($i + 1) . ". " . $ad['name'] . "\n";
            $list .= "   📍 " . $ad['address'] . "\n";
            $list .= "   📞 " . $ad['contact']['phone'] . "\n";
            if (!empty($ad['special_offers'])) {
                $list .= "   🎉 Offre spéciale : " . $ad['special_offers'][0]['description'] . "\n";
            }
            $list .= "\n";
        }

        $template = $responses[$query_type][get_locale()] ?? $responses[$query_type]['fr'];
        return str_replace(
            ['{count}', '{list}'],
            [count($advertisers), $list],
            $template
        );
    }
}
