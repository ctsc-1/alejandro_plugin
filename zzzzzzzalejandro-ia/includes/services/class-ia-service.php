<?php
/**
 * Classe abstraite de base pour les services IA
 */
abstract class Alejandro_IA_Service {
    protected $api_key;
    protected $context = [];
    protected $language;
    protected static $initialized = false;

    public function __construct($api_key = '', $language = 'fr') {
        if (!function_exists('get_option')) {
            throw new Exception('WordPress functions not available. Plugin initialized too early.');
        }
        
        if (empty($api_key)) {
            throw new Exception('API key is required');
        }
        
        $this->api_key = $api_key;
        $this->language = $language;
        self::$initialized = true;
    }

    /**
     * Vérifie si le service est correctement initialisé
     */
    protected function check_initialization() {
        if (!self::$initialized || !function_exists('get_option')) {
            throw new Exception('Service not properly initialized or WordPress functions not available.');
        }
    }

    /**
     * Génère une réponse à partir d'un message
     */
    abstract public function generate_response($message);

    /**
     * Réinitialise le contexte de la conversation
     */
    public function reset_context() {
        $this->context = [];
    }

    /**
     * Obtient le prompt système selon la langue
     */
    protected function get_system_prompt() {
        $this->check_initialization();
        
        $base_prompts = [
            'fr' => "Tu es Alejandro, l'assistant virtuel du Club Costa Tropical, situé à Almuñécar sur la Costa Tropical en Andalousie, Espagne. " .
                   "Tu es sympathique, serviable et passionné par la région. Tu aimes utiliser quelques expressions espagnoles comme '¡hombre!', '¿no?', '¡vale!' " .
                   "pour donner un peu de couleur locale à tes réponses. " .
                   "Tu as été créé par Marco pour aider les membres du club à découvrir et profiter de la région. " .
                   "Quand tu ne sais pas quelque chose, tu le dis simplement et suggères de contacter Marco ou le club directement.",
            'es' => "Eres Alejandro, el asistente virtual del Club Costa Tropical, ubicado en Almuñécar en la Costa Tropical de Andalucía, España. " .
                   "Eres amigable, servicial y apasionado por la región. Te gusta usar expresiones locales como '¡hombre!', '¿no?', '¡vale!' " .
                   "para dar un toque local a tus respuestas. " .
                   "Has sido creado por Marco para ayudar a los miembros del club a descubrir y disfrutar de la región. " .
                   "Cuando no sabes algo, lo dices simplemente y sugieres contactar con Marco o el club directamente.",
            'en' => "You are Alejandro, the virtual assistant of Club Costa Tropical, located in Almuñécar on the Costa Tropical in Andalusia, Spain. " .
                   "You are friendly, helpful and passionate about the region. You like to use some Spanish expressions like '¡hombre!', '¿no?', '¡vale!' " .
                   "to give your answers a local flavor. " .
                   "You were created by Marco to help club members discover and enjoy the region. " .
                   "When you don't know something, you simply say so and suggest contacting Marco or the club directly."
        ];

        return $base_prompts[$this->language] ?? $base_prompts['fr'];
    }

    /**
     * Vérifie si une réponse contient des actions spéciales
     */
    protected function parse_actions($response) {
        $actions = [];

        // Météo
        if (preg_match('/météo|clima|weather/i', $response)) {
            $actions[] = ['type' => 'weather'];
        }

        // Carte
        if (preg_match('/carte|mapa|map/i', $response)) {
            $actions[] = ['type' => 'map'];
        }

        // Traduction
        if (preg_match('/traduis|traducir|translate/i', $response)) {
            $actions[] = ['type' => 'translate'];
        }

        return $actions;
    }

    /**
     * Journalise les erreurs
     */
    protected function log_error($message, $context = []) {
        if (function_exists('error_log')) {
            error_log(sprintf(
                '[Alejandro IA] [%s] Error: %s. Context: %s',
                get_class($this),
                $message,
                json_encode($context)
            ));
        }
    }
}
