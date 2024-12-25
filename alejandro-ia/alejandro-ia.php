<?php
/**
 * Plugin Name: Alejandro IA
 * Description: Assistant virtuel intelligent pour le Club Costa Tropical
 * Version: 1.0.0
 * Author: Club Costa Tropical
 * Text Domain: alejandro-ia
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_IA {
    private static $instance = null;
    private $api_config = null;
    private $usage_stats = [];

    private function __construct() {
        // Définir les constantes
        define('ALEJANDRO_PATH', plugin_dir_path(__FILE__));
        define('ALEJANDRO_URL', plugin_dir_url(__FILE__));
        define('ALEJANDRO_CONFIG_PATH', ALEJANDRO_PATH . '.config/');

        // Charger la configuration
        $this->load_config();

        // Hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_shortcode('alejandro', [$this, 'render_chat']);

        // Initialiser le compteur d'utilisation
        $this->usage_stats = get_option('alejandro_usage_stats', [
            'total_requests' => 0,
            'total_tokens' => 0,
            'last_reset' => current_time('timestamp')
        ]);
    }

    private function load_config() {
        $config_file = ALEJANDRO_CONFIG_PATH . 'api-keys.php';
        if (file_exists($config_file)) {
            $this->api_config = require $config_file;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>Fichier de configuration des APIs manquant pour Alejandro IA.</p></div>';
            });
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Alejandro IA',
            'Alejandro IA',
            'manage_options',
            'alejandro-ia',
            [$this, 'render_admin_page'],
            'dashicons-admin-generic'
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }
        ?>
        <div class="wrap">
            <h1>Alejandro IA - Tableau de bord</h1>

            <div class="card">
                <h2>Shortcode</h2>
                <p>Utilisez ce shortcode pour afficher Alejandro sur vos pages :</p>
                <code>[alejandro]</code>
            </div>

            <div class="card">
                <h2>Statistiques d'utilisation</h2>
                <table class="widefat">
                    <tr>
                        <th>Requêtes totales</th>
                        <td><?php echo number_format($this->usage_stats['total_requests']); ?></td>
                    </tr>
                    <tr>
                        <th>Tokens utilisés</th>
                        <td><?php echo number_format($this->usage_stats['total_tokens']); ?></td>
                    </tr>
                    <tr>
                        <th>Dernier reset</th>
                        <td><?php echo date('d/m/Y H:i', $this->usage_stats['last_reset']); ?></td>
                    </tr>
                </table>
            </div>

            <div class="card">
                <h2>État des APIs</h2>
                <?php
                if ($this->api_config) {
                    foreach ($this->api_config as $api => $config) {
                        $status = !empty($config['key']) ? 
                            '<span style="color: green;">✓ Configurée</span>' : 
                            '<span style="color: red;">✗ Non configurée</span>';
                        echo "<p><strong>API $api :</strong> $status</p>";
                    }
                } else {
                    echo '<p style="color: red;">Configuration des APIs non trouvée</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style(
            'alejandro-style',
            ALEJANDRO_URL . 'assets/css/style.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'alejandro-chat',
            ALEJANDRO_URL . 'assets/js/chat.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('alejandro-chat', 'alejandroConfig', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('alejandro_chat')
        ]);
    }

    public function render_chat() {
        ob_start();
        ?>
        <div id="alejandro-chat" class="alejandro-chat">
            <div class="chat-messages"></div>
            <div class="chat-input">
                <textarea placeholder="Écrivez votre message..."></textarea>
                <button class="send-button">Envoyer</button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function get_api_key($service) {
        return isset($this->api_config[$service]['key']) ? 
            $this->api_config[$service]['key'] : null;
    }

    public function update_usage_stats($tokens_used = 1) {
        $this->usage_stats['total_requests']++;
        $this->usage_stats['total_tokens'] += $tokens_used;
        update_option('alejandro_usage_stats', $this->usage_stats);
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Initialisation
add_action('plugins_loaded', function() {
    Alejandro_IA::get_instance();
});
