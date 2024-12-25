<?php
/**
 * Plugin Name: Alejandro IA
 * Description: Assistant virtuel intelligent pour le Club Costa Tropical
 * Version: 1.0.0
 * Author: Club Costa Tropical
 * Text Domain: alejandro-ia
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('ALEJANDRO_IA_VERSION', '1.0.0');
define('ALEJANDRO_IA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALEJANDRO_IA_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activer le débogage
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-debug.php';
Alejandro_Debug::enable_debug();

// Vérifier les dépendances
$missing_dependencies = Alejandro_Debug::check_dependencies();
if (!empty($missing_dependencies)) {
    foreach ($missing_dependencies as $missing) {
        Alejandro_Debug::log_error($missing);
    }
    return;
}

// 1. Interface de base
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/interfaces/interface-weather-provider.php';

// 2. Classes de base
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-alejandro-cache.php';
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-alejandro-response.php';
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-alejandro-personality.php';

// 3. Services principaux
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/class-ajax-handler.php';

// 4. Services spécifiques
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/services/class-ia-service.php';
require_once ALEJANDRO_IA_PLUGIN_DIR . 'includes/services/class-claude-service.php';

// Vérifier la configuration du plugin
$config_issues = Alejandro_Debug::check_plugin_config();
if (!empty($config_issues)) {
    foreach ($config_issues as $issue) {
        Alejandro_Debug::log_error($issue);
    }
}

/**
 * Classe principale du plugin
 */
class Alejandro_IA {
    private static $instance = null;
    private $claude_service;
    private $cache;

    /**
     * Constructeur
     */
    private function __construct() {
        try {
            Alejandro_Debug::log_info('Initialisation du plugin Alejandro IA');
            $this->init();
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors de l\'initialisation', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
        }
    }

    /**
     * Initialise le plugin
     */
    private function init() {
        try {
            // Charger les traductions
            load_plugin_textdomain('alejandro-ia', false, ALEJANDRO_IA_PLUGIN_DIR . 'languages');

            // Initialiser les services
            $this->init_services();

            // Ajouter les hooks
            add_action('init', array($this, 'init_hooks'));
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

            // Ajouter le shortcode
            add_shortcode('alejandro', array($this, 'render_chat'));

            Alejandro_Debug::log_info('Plugin initialisé avec succès');
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors de l\'initialisation', array(
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            throw $e;
        }
    }

    /**
     * Initialise les hooks
     */
    public function init_hooks() {
        try {
            // Initialiser les handlers AJAX
            add_action('wp_ajax_alejandro_process_message', array($this, 'process_message_ajax'));
            add_action('wp_ajax_nopriv_alejandro_process_message', array($this, 'process_message_ajax'));
            
            Alejandro_Debug::log_info('Hooks initialisés avec succès');
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors de l\'initialisation des hooks', array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Initialise les services
     */
    private function init_services() {
        try {
            $this->claude_service = new Alejandro_Claude_Service();
            $this->cache = new Alejandro_Cache();
            
            Alejandro_Debug::log_info('Services initialisés avec succès');
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors de l\'initialisation des services', array(
                'message' => $e->getMessage()
            ));
            throw $e;
        }
    }

    /**
     * Enregistre les scripts et styles
     */
    public function enqueue_scripts() {
        try {
            // Styles
            wp_enqueue_style(
                'alejandro-ia',
                ALEJANDRO_IA_PLUGIN_URL . 'assets/css/style.css',
                array(),
                ALEJANDRO_IA_VERSION
            );

            // Scripts
            wp_enqueue_script(
                'regenerator-runtime',
                'https://cdnjs.cloudflare.com/ajax/libs/regenerator-runtime/0.13.11/regenerator-runtime.min.js',
                array(),
                '0.13.11',
                true
            );

            wp_enqueue_script(
                'alejandro-chat',
                ALEJANDRO_IA_PLUGIN_URL . 'assets/js/alejandro-chat.js',
                array('jquery', 'regenerator-runtime'),
                ALEJANDRO_IA_VERSION,
                true
            );

            wp_localize_script('alejandro-chat', 'alejandroIA', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('alejandro_ia_nonce'),
                'loading' => __('Je réfléchis...', 'alejandro-ia'),
                'error' => __('Désolé, une erreur est survenue', 'alejandro-ia')
            ));

            Alejandro_Debug::log_info('Scripts et styles chargés avec succès');
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors du chargement des scripts', array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Enregistre les scripts admin
     */
    public function enqueue_admin_scripts() {
        try {
            wp_enqueue_script(
                'alejandro-admin',
                ALEJANDRO_IA_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                ALEJANDRO_IA_VERSION,
                true
            );
            
            Alejandro_Debug::log_info('Scripts admin chargés avec succès');
        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors du chargement des scripts admin', array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Traite le message AJAX
     */
    public function process_message_ajax() {
        try {
            check_ajax_referer('alejandro_ia_nonce', 'nonce');

            $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
            
            if (empty($message)) {
                throw new Exception(__('Message vide', 'alejandro-ia'));
            }

            $response = $this->claude_service->generate_response($message);
            wp_send_json_success(array('message' => $response));

        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors du traitement du message', array(
                'message' => $e->getMessage(),
                'post_data' => $_POST
            ));
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Rendu du chat
     */
    public function render_chat() {
        try {
            ob_start();
            ?>
            <div id="alejandro-chat" class="alejandro-chat">
                <div id="chat-messages" class="chat-messages"></div>
                <div class="chat-input">
                    <textarea id="user-message" 
                              placeholder="<?php esc_attr_e('Écrivez votre message ici...', 'alejandro-ia'); ?>"
                              rows="3"></textarea>
                    <button id="send-message" class="button">
                        <?php esc_html_e('Envoyer', 'alejandro-ia'); ?>
                    </button>
                </div>
            </div>
            <?php
            $output = ob_get_clean();
            
            Alejandro_Debug::log_info('Chat rendu avec succès');
            return $output;

        } catch (Exception $e) {
            Alejandro_Debug::log_error('Erreur lors du rendu du chat', array(
                'message' => $e->getMessage()
            ));
            return '<div class="error">Une erreur est survenue lors du chargement du chat.</div>';
        }
    }

    /**
     * Point d'entrée du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            try {
                self::$instance = new self();
            } catch (Exception $e) {
                Alejandro_Debug::log_error('Erreur lors de la création de l\'instance', array(
                    'message' => $e->getMessage()
                ));
                return null;
            }
        }
        return self::$instance;
    }
}

// Initialisation du plugin
try {
    add_action('plugins_loaded', array('Alejandro_IA', 'get_instance'));
    Alejandro_Debug::log_info('Plugin chargé avec succès');
} catch (Exception $e) {
    Alejandro_Debug::log_error('Erreur lors du chargement du plugin', array(
        'message' => $e->getMessage()
    ));
}
