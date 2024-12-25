<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Monitor {
    private static $instance = null;
    private $nlp;
    private $language_handler;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        // Initialisation des classes
        $this->language_handler = new Alejandro_Language();
        $this->nlp = new Alejandro_NLP();

        // Hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // Shortcode
        add_shortcode('alejandro', array($this, 'render_alejandro_chat'));

        // AJAX
        add_action('wp_ajax_alejandro_process_message', array($this, 'handle_ajax_message'));
        add_action('wp_ajax_nopriv_alejandro_process_message', array($this, 'handle_ajax_message'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Alejandro Monitor', 'alejandro-monitor'),
            __('Alejandro', 'alejandro-monitor'),
            'manage_options',
            'alejandro-monitor',
            array($this, 'display_admin_page'),
            'dashicons-businessman'
        );
    }

    public function register_settings() {
        register_setting('alejandro_monitor_options', 'alejandro_anthropic_api_key');
        
        add_settings_section(
            'alejandro_monitor_main',
            __('Paramètres API', 'alejandro-monitor'),
            null,
            'alejandro_monitor'
        );

        add_settings_field(
            'alejandro_anthropic_api_key',
            __('Clé API Anthropic', 'alejandro-monitor'),
            array($this, 'render_api_key_field'),
            'alejandro_monitor',
            'alejandro_monitor_main'
        );
    }

    public function render_api_key_field() {
        $api_key = get_option('alejandro_anthropic_api_key');
        ?>
        <input type="password" 
               name="alejandro_anthropic_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text">
        <?php
    }

    public function render_alejandro_chat() {
        ob_start();
        ?>
        <div id="alejandro-chat-container" class="alejandro-chat">
            <div id="alejandro-messages" class="alejandro-messages"></div>
            <div class="alejandro-input-container">
                <input type="text" id="alejandro-input" placeholder="<?php esc_attr_e('Posez votre question à Alejandro...', 'alejandro-monitor'); ?>">
                <button id="alejandro-send"><?php esc_html_e('Envoyer', 'alejandro-monitor'); ?></button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'alejandro-chat',
            ALEJANDRO_MONITOR_PLUGIN_URL . 'assets/css/alejandro-chat.css',
            array(),
            ALEJANDRO_MONITOR_VERSION
        );

        wp_enqueue_script(
            'alejandro-chat',
            ALEJANDRO_MONITOR_PLUGIN_URL . 'assets/js/alejandro-chat.js',
            array('jquery'),
            ALEJANDRO_MONITOR_VERSION,
            true
        );

        wp_localize_script('alejandro-chat', 'alejandroData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alejandro_chat_nonce'),
            'language' => $this->language_handler->get_current_language()
        ));
    }

    public function display_admin_page() {
        require_once ALEJANDRO_MONITOR_PLUGIN_DIR . 'admin/views/admin-page.php';
    }

    public function handle_ajax_message() {
        try {
            // Vérification du nonce
            if (!check_ajax_referer('alejandro_chat_nonce', 'nonce', false)) {
                throw new Exception(__('Erreur de sécurité', 'alejandro-monitor'));
            }

            // Vérification du message
            $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
            if (empty($message)) {
                throw new Exception(__('Message vide', 'alejandro-monitor'));
            }

            // Traitement du message via NLP
            $response = $this->nlp->process_message($message);

            wp_send_json_success(array(
                'response' => $response['response'],
                'language' => $response['language']
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    private function log_error($error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Alejandro Monitor Error: ' . $error);
        }
    }

    public function enqueue_admin_scripts($hook) {
        // Ne charger les scripts que sur la page d'administration du plugin
        if ('toplevel_page_alejandro-monitor' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'alejandro-admin',
            ALEJANDRO_MONITOR_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            ALEJANDRO_MONITOR_VERSION
        );

        wp_enqueue_script(
            'alejandro-admin',
            ALEJANDRO_MONITOR_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            ALEJANDRO_MONITOR_VERSION,
            true
        );

        wp_localize_script('alejandro-admin', 'alejandroAdminData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alejandro_admin_nonce')
        ));
    }
} 