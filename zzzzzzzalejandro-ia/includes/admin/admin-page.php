<?php
/**
 * Page d'administration du plugin Alejandro IA
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Admin_Page {
    private static $instance = null;

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_admin_menu() {
        add_menu_page(
            'Alejandro IA',
            'Alejandro IA',
            'manage_options',
            'alejandro-ia',
            array($this, 'render_admin_page'),
            'dashicons-admin-generic',
            100
        );
    }

    public function register_settings() {
        register_setting('alejandro_ia_settings', 'alejandro_claude_api_key');
        
        add_settings_section(
            'alejandro_ia_main_section',
            'Configuration d\'Alejandro IA',
            array($this, 'render_section_info'),
            'alejandro-ia'
        );

        add_settings_field(
            'alejandro_claude_api_key',
            'Clé API Claude',
            array($this, 'render_api_key_field'),
            'alejandro-ia',
            'alejandro_ia_main_section'
        );
    }

    public function render_section_info() {
        echo '<p>Configurez les paramètres d\'Alejandro IA ci-dessous :</p>';
    }

    public function render_api_key_field() {
        $api_key = get_option('alejandro_claude_api_key');
        ?>
        <input type="password" 
               id="alejandro_claude_api_key" 
               name="alejandro_claude_api_key" 
               value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text">
        <p class="description">
            Entrez votre clé API Claude d'Anthropic. 
            <a href="https://console.anthropic.com/account/keys" target="_blank">
                Obtenir une clé API
            </a>
        </p>
        <?php
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Accès refusé');
        }

        // Sauvegarder les paramètres
        if (isset($_POST['submit'])) {
            check_admin_referer('alejandro_ia_settings');
            
            $api_key = sanitize_text_field($_POST['alejandro_claude_api_key']);
            update_option('alejandro_claude_api_key', $api_key);
            
            echo '<div class="notice notice-success"><p>Paramètres sauvegardés avec succès.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Configuration d'Alejandro IA</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('alejandro_ia_settings');
                do_settings_sections('alejandro-ia');
                submit_button('Enregistrer les modifications');
                ?>
            </form>

            <hr>

            <h2>Utilisation du shortcode</h2>
            <p>Pour afficher Alejandro sur une page ou un article, utilisez le shortcode suivant :</p>
            <code>[alejandro]</code>

            <h2>État du système</h2>
            <?php
            $api_key = get_option('alejandro_claude_api_key');
            $status = !empty($api_key) ? 
                '<span style="color: green;">✓ Configuré</span>' : 
                '<span style="color: red;">✗ Non configuré</span>';
            ?>
            <ul>
                <li><strong>API Claude :</strong> <?php echo $status; ?></li>
                <li><strong>Version du plugin :</strong> <?php echo ALEJANDRO_IA_VERSION; ?></li>
                <li><strong>Mode debug :</strong> <?php echo WP_DEBUG ? 'Activé' : 'Désactivé'; ?></li>
            </ul>

            <h2>Logs récents</h2>
            <?php
            if (WP_DEBUG && WP_DEBUG_LOG) {
                $log_file = WP_CONTENT_DIR . '/debug.log';
                if (file_exists($log_file)) {
                    $logs = file_get_contents($log_file);
                    $logs = array_filter(
                        explode("\n", $logs),
                        function($line) {
                            return strpos($line, 'Alejandro') !== false;
                        }
                    );
                    $logs = array_slice($logs, -10);
                    echo '<pre style="background: #f0f0f0; padding: 10px; max-height: 200px; overflow: auto;">';
                    echo implode("\n", $logs);
                    echo '</pre>';
                } else {
                    echo '<p>Aucun fichier de log trouvé.</p>';
                }
            } else {
                echo '<p>Le mode debug n\'est pas activé. Activez WP_DEBUG et WP_DEBUG_LOG dans wp-config.php pour voir les logs.</p>';
            }
            ?>
        </div>
        <?php
    }
}

// Initialisation
Alejandro_Admin_Page::get_instance();
