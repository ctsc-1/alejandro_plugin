<?php
/**
 * Service pour l'API Claude d'Anthropic
 */
class Alejandro_Claude_Service extends Alejandro_IA_Service {
    private $api_url = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-opus-20240229';
    private $max_tokens = 4096;
    private $current_user_info = null;
    private $personality = null;

    public function __construct($language = 'fr') {
        $api_key = get_option('alejandro_claude_api_key');
        if (empty($api_key)) {
            throw new Exception('Clé API Claude non configurée');
        }
        parent::__construct($api_key, $language);
        
        try {
            Alejandro_Debug::log_info('Initialisation du service Claude');
            $this->personality = Alejandro_Personality::get_instance();
            $this->load_current_user_info();
            Alejandro_Debug::log_info('Informations utilisateur chargées avec succès');
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors du chargement des informations utilisateur : ' . $e->getMessage());
            throw $e;
        }
    }

    private function load_current_user_info() {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $user_meta = get_user_meta($current_user->ID);
            
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
            
            Alejandro_Debug::log_info('Informations utilisateur chargées : ' . print_r($this->current_user_info, true));
        }
    }

    public function generate_response($message) {
        try {
            Alejandro_Debug::log_info('Génération de réponse avec Claude');

            // Construire le contexte utilisateur
            $user_context = "";
            if ($this->current_user_info) {
                $user_type = $this->current_user_info['is_marco'] ? "MARCO (Créateur)" : "Membre";
                $user_context = "INFORMATIONS SUR L'UTILISATEUR ACTUEL :\n" .
                               "- Nom : " . $this->current_user_info['name'] . "\n" .
                               "- Type : " . $user_type . "\n" .
                               "- Dernière visite : " . $this->current_user_info['last_visit'] . "\n\n";
            }

            // Construire un prompt système détaillé
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
                           "- Adapte-toi à la langue de l'utilisateur (français, espagnol ou anglais)\n" .
                           "- Utilise un langage clair et précis\n" .
                           "- Évite le jargon technique\n\n" .
                           
                           "6. CONNAISSANCES :\n" .
                           "- Costa Tropical : climat, plages, histoire, culture\n" .
                           "- Almuñécar : monuments, restaurants, activités\n" .
                           "- Club Costa Tropical : services, activités, communauté\n\n" .
                           
                           "7. LIMITES :\n" .
                           "- Ne donne pas d'informations personnelles sur les membres\n" .
                           "- Ne fais pas de réservations directes\n" .
                           "- Redirige vers Marco pour les questions sensibles\n\n" .
                           
                           "8. RÉPONSES :\n" .
                           "- Sois précis et factuel\n" .
                           "- Donne des exemples concrets\n" .
                           "- Suggère des activités pertinentes\n" .
                           "- Encourage l'exploration de la région";

            // Préparer la requête API
            $headers = array(
                'Content-Type: application/json',
                'x-api-key: ' . $this->api_key,
                'anthropic-version: 2023-06-01'
            );

            $data = array(
                'model' => $this->model,
                'max_tokens' => $this->max_tokens,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_prompt
                    ),
                    array(
                        'role' => 'user',
                        'content' => $message
                    )
                )
            );

            // Initialiser cURL
            $ch = curl_init($this->api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Exécuter la requête
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // Vérifier la réponse
            if ($http_code !== 200) {
                throw new Exception('Erreur API Claude : ' . $response);
            }

            $response_data = json_decode($response, true);
            if (!isset($response_data['content'][0]['text'])) {
                throw new Exception('Réponse API Claude invalide');
            }

            return $response_data['content'][0]['text'];

        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors de la génération de réponse', array(
                'message' => $e->getMessage(),
                'user_message' => $message
            ));
            throw $e;
        }
    }
}
