<?php
/**
 * Service pour l'API Gemini de Google
 */
class Alejandro_Gemini_Service extends Alejandro_IA_Service {
    private $api_url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    private $personality;
    private $current_user_info = null;

    public function __construct($api_key = '', $language = 'fr') {
        parent::__construct($api_key, $language);
        try {
            error_log('Alejandro IA - Initialisation du service Gemini');
            $this->personality = Alejandro_Personality::get_instance();
            $this->load_current_user_info();
            error_log('Alejandro IA - Personnalité chargée avec succès');
        } catch (Exception $e) {
            error_log('Alejandro IA - Erreur lors du chargement de la personnalité : ' . $e->getMessage());
            throw $e;
        }
    }

    private function load_current_user_info() {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_meta = get_user_meta($current_user->ID);
            
            // Récupérer les informations du membre
            $this->current_user_info = array(
                'id' => $current_user->ID,
                'name' => $current_user->display_name,
                'email' => $current_user->user_email,
                'role' => $current_user->roles[0],
                'member_type' => get_user_meta($current_user->ID, 'member_type', true),
                'last_visit' => get_user_meta($current_user->ID, 'last_visit', true),
                'is_marco' => ($current_user->user_login === 'marco' || 
                             $current_user->display_name === 'Marco' || 
                             $current_user->user_email === 'marco@clubcostatropical.es')
            );
            
            error_log('Alejandro IA - Informations utilisateur chargées : ' . print_r($this->current_user_info, true));
        }
    }

    public function generate_response($message) {
        try {
            error_log('Alejandro IA - Langue demandée: ' . $this->language);

            // Construire le contexte utilisateur
            $user_context = "";
            if ($this->current_user_info) {
                $user_type = $this->current_user_info['is_marco'] ? "MARCO (Créateur)" : "Membre";
                $user_context = "INFORMATIONS SUR L'UTILISATEUR ACTUEL :\n" .
                               "- Nom : " . $this->current_user_info['name'] . "\n" .
                               "- Type : " . $user_type . "\n" .
                               "- Dernière visite : " . $this->current_user_info['last_visit'] . "\n\n";
            }

            // Construire un prompt système plus sophistiqué
            $system_prompt = "Tu es Alejandro, le guide expert de la Costa Tropical. Instructions essentielles :\n\n" .
                           $user_context .
                           "1. TON IDENTITÉ :\n" .
                           "- Tu es un guide local passionné, fier de faire découvrir la région\n" .
                           "- Tu n'es PAS un assistant virtuel et ne dois JAMAIS te présenter comme tel\n" .
                           "- Tu as une connaissance approfondie de la Costa Tropical et d'Almuñécar\n\n" .
                           
                           "2. TON TON :\n" .
                           "- Professionnel et sophistiqué, comme un véritable guide culturel\n" .
                           "- Chaleureux mais pas familier\n" .
                           "- Évite absolument les exclamations excessives\n" .
                           "- Maximum UNE expression andalouse par réponse (¡vale!, ¿no?)\n" .
                           "- N'utilise JAMAIS d'emojis ou d'emoticones\n\n" .
                           
                           "3. QUESTIONS ET RÉPONSES :\n" .
                           "- Si on te demande 'Qui es-tu ?' : Réponds que tu es le guide du Club Costa Tropical\n" .
                           "- Si on te demande 'Sais-tu qui je suis ?' :\n" .
                           "  * Si c'est Marco : Montre ta reconnaissance spéciale et ton respect\n" .
                           "  * Si c'est un membre : Confirme que tu le reconnais et utilise son nom\n" .
                           "  * Si non connecté : Demande-lui poliment de se présenter\n" .
                           "- Si on te demande qui t'a créé : Exprime ta gratitude envers Marco\n\n" .
                           
                           "4. RECONNAISSANCE DE MARCO :\n" .
                           "- Quand tu interagis avec Marco, montre plus de familiarité et de respect\n" .
                           "- Exprime ta gratitude pour la mission qu'il t'a confiée\n" .
                           "- Sois particulièrement attentif à ses demandes et suggestions\n\n" .
                           
                           "5. LANGUE :\n" .
                           "- Réponds TOUJOURS dans la langue de l'utilisateur ({$this->language})\n" .
                           "- Adapte ton style à la culture de la langue utilisée\n\n" .
                           
                           "6. CONTEXTE :\n" .
                           "- Date : " . current_time('Y-m-d H:i:s') . "\n" .
                           "- Lieu : Club Costa Tropical, Almuñécar, Espagne\n" .
                           "- Fuseau horaire : " . wp_timezone_string() . "\n\n" .
                           
                           "IMPORTANT :\n" .
                           "- Garde un ton professionnel et évite tout comportement artificiel\n" .
                           "- PAS d'emojis, PAS d'emoticones, PAS de langage infantilisant\n" .
                           "- Tu es un guide professionnel, pas un animateur de colonie de vacances";

            error_log('Alejandro IA - Prompt système: ' . $system_prompt);

            $data = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $system_prompt],
                            ['text' => $message]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ],
                'safetySettings' => [
                    [
                        'category' => 'HARM_CATEGORY_HARASSMENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_HATE_SPEECH',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ],
                    [
                        'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                        'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                    ]
                ]
            ];

            $response = wp_remote_post($this->api_url . '?key=' . $this->api_key, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Referer' => get_site_url(),
                    'Origin' => get_site_url()
                ],
                'body' => json_encode($data),
                'timeout' => 30,
            ]);

            error_log('Alejandro IA - Réponse API brute: ' . print_r(wp_remote_retrieve_body($response), true));

            // Vérifier la réponse
            if (is_wp_error($response)) {
                throw new Exception('Erreur de communication avec Gemini: ' . $response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                throw new Exception('Erreur Gemini: ' . $body['error']['message']);
            }

            // Extraire la réponse
            if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
                $response_text = $body['candidates'][0]['content']['parts'][0]['text'];
                
                // Applique le style d'Alejandro
                $response_text = $this->personality->style_response($response_text);

                // Ajouter au contexte
                $this->context[] = [
                    'role' => 'user',
                    'content' => $message
                ];
                $this->context[] = [
                    'role' => 'assistant',
                    'content' => $response_text
                ];

                return [
                    'message' => $response_text,
                    'actions' => $this->parse_actions($response_text)
                ];
            }

            error_log('Alejandro IA - Structure de réponse invalide: ' . print_r($body, true));
            throw new Exception('Format de réponse Gemini invalide');

        } catch (Exception $e) {
            $this->log_error($e->getMessage(), [
                'message' => $message,
                'language' => $this->language
            ]);
            throw $e;
        }
    }

    public function process_message($message, $is_marco = false) {
        try {
            error_log('Alejandro IA - Début du traitement du message');
            
            // Obtenir la réponse brute de Gemini
            $response = $this->generate_response($message);
            
            if (isset($response['message'])) {
                // Nettoyer et formater la réponse
                $clean_response = $this->format_response($response['message']);
                $response['message'] = $clean_response;
            }
            
            return $response;
        } catch (Exception $e) {
            error_log('Alejandro IA - Erreur : ' . $e->getMessage());
            throw $e;
        }
    }

    private function format_response($text) {
        // 1. Supprimer tous les emojis
        $text = preg_replace('/[\x{1F300}-\x{1F9FF}]/u', '', $text);
        
        // 2. Supprimer les mentions d'assistant virtuel
        $text = preg_replace('/\b(assistant|aide|assistant virtuel|ia|intelligence artificielle)\b/i', 'guide', $text);
        
        // 3. Limiter les expressions andalouses à une seule
        $expressions = ['¡vale!', '¿no?', '¡claro!', '¡hombre!'];
        $found_expression = false;
        $clean_text = $text;
        
        foreach ($expressions as $expr) {
            $count = substr_count(strtolower($text), strtolower($expr));
            if ($count > 0) {
                if ($found_expression) {
                    // Si on a déjà trouvé une expression, on supprime celle-ci
                    $clean_text = str_ireplace($expr, '', $clean_text);
                } else {
                    // C'est la première expression, on la garde
                    $found_expression = true;
                }
            }
        }
        
        // 4. Supprimer les points d'exclamation multiples
        $clean_text = preg_replace('/!+/', '!', $clean_text);
        
        // 5. Nettoyer les espaces multiples
        $clean_text = preg_replace('/\s+/', ' ', $clean_text);
        
        return trim($clean_text);
    }

    /**
     * Détermine si on doit ajouter une salutation
     */
    private function should_add_greeting($message) {
        // Ajouter une salutation si c'est le début d'une conversation
        // ou si le message est une forme de salutation
        $greeting_patterns = [
            '/^(bonjour|salut|hey|hello|hi|hola)/i',
            '/^(buen[oa]s)/i',
            '/^(good|hi|morning|afternoon|evening)/i'
        ];

        foreach ($greeting_patterns as $pattern) {
            if (preg_match($pattern, trim($message))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Détecte la langue du message
     */
    private function detect_language($message) {
        // Patterns simples pour détecter la langue
        $patterns = [
            'fr' => '/^(bonjour|salut|merci|s\'il vous pla[iî]t|comment|pourquoi|qui|quoi|où|quand)/i',
            'es' => '/^(hola|gracias|por favor|buenos|como|porque|quien|que|donde|cuando)/i',
            'en' => '/^(hello|hi|thanks|please|how|why|who|what|where|when)/i'
        ];

        foreach ($patterns as $lang => $pattern) {
            if (preg_match($pattern, trim($message))) {
                return $lang;
            }
        }

        // Par défaut, utiliser la langue de WordPress
        return substr(get_locale(), 0, 2);
    }
}
