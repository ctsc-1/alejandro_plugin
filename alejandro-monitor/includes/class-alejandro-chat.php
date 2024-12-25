<?php
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Chat {
    public function __construct() {
        add_shortcode('alejandro', array($this, 'render'));
        add_action('wp_ajax_alejandro_send_message', array($this, 'handle_message'));
        add_action('wp_ajax_nopriv_alejandro_send_message', array($this, 'handle_message'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render() {
        ob_start();
        ?>
        <div id="alejandro-chat-container" class="alejandro-chat">
            <div id="alejandro-messages" class="alejandro-messages"></div>
            <div class="alejandro-input-container">
                <input type="text" 
                       id="alejandro-input" 
                       placeholder="<?php esc_attr_e('Posez votre question...', 'alejandro-monitor'); ?>">
                <button id="alejandro-send" aria-label="<?php esc_attr_e('Envoyer', 'alejandro-monitor'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="none" d="M0 0h24v24H0z"/>
                        <path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_message() {
        check_ajax_referer('alejandro_chat', 'nonce');

        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error(array(
                'message' => __('Message vide', 'alejandro-monitor')
            ));
        }

        // Pour l'instant, retourne un simple écho
        wp_send_json_success(array(
            'response' => sprintf(
                __('Vous avez dit : %s', 'alejandro-monitor'),
                $message
            )
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'alejandro-chat',
            ALEJANDRO_MONITOR_PLUGIN_URL . 'assets/css/chat.css',
            array(),
            ALEJANDRO_MONITOR_VERSION
        );

        wp_enqueue_script(
            'alejandro-chat',
            ALEJANDRO_MONITOR_PLUGIN_URL . 'assets/js/chat.js',
            array('jquery'),
            ALEJANDRO_MONITOR_VERSION,
            true
        );

        wp_localize_script('alejandro-chat', 'alejandroChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alejandro_chat'),
            'i18n' => array(
                'error' => __('Une erreur est survenue', 'alejandro-monitor')
            )
        ));
    }
} 