<?php
/**
 * Classe de débogage pour Alejandro IA
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

class Alejandro_Debug {
    /**
     * Active le mode débogage
     */
    public static function enable_debug() {
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        if (!defined('WP_DEBUG_LOG')) {
            define('WP_DEBUG_LOG', true);
        }
        if (!defined('WP_DEBUG_DISPLAY')) {
            define('WP_DEBUG_DISPLAY', true);
        }
        @ini_set('display_errors', 1);
    }

    /**
     * Log une erreur
     */
    public static function log_error($message, $data = array()) {
        if (WP_DEBUG) {
            error_log('[Alejandro IA Error] ' . $message);
            if (!empty($data)) {
                error_log('[Alejandro IA Data] ' . print_r($data, true));
            }
        }
    }

    /**
     * Log une information
     */
    public static function log_info($message, $data = array()) {
        if (WP_DEBUG) {
            error_log('[Alejandro IA Info] ' . $message);
            if (!empty($data)) {
                error_log('[Alejandro IA Data] ' . print_r($data, true));
            }
        }
    }

    /**
     * Vérifie les dépendances requises
     */
    public static function check_dependencies() {
        $missing = array();
        
        // Vérifier la version de PHP
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $missing[] = 'PHP 7.4 ou supérieur est requis. Version actuelle : ' . PHP_VERSION;
        }

        // Vérifier les extensions PHP requises
        $required_extensions = array('curl', 'json', 'mbstring');
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = "L'extension PHP '$ext' est requise mais n'est pas installée.";
            }
        }

        // Vérifier les constantes WordPress requises
        $required_constants = array('ABSPATH', 'WP_CONTENT_DIR');
        foreach ($required_constants as $const) {
            if (!defined($const)) {
                $missing[] = "La constante WordPress '$const' n'est pas définie.";
            }
        }

        return $missing;
    }

    /**
     * Vérifie la configuration du plugin
     */
    public static function check_plugin_config() {
        $issues = array();

        // Vérifier les constantes du plugin
        if (!defined('ALEJANDRO_IA_VERSION')) {
            $issues[] = 'La constante ALEJANDRO_IA_VERSION n\'est pas définie.';
        }
        if (!defined('ALEJANDRO_IA_PLUGIN_DIR')) {
            $issues[] = 'La constante ALEJANDRO_IA_PLUGIN_DIR n\'est pas définie.';
        }
        if (!defined('ALEJANDRO_IA_PLUGIN_URL')) {
            $issues[] = 'La constante ALEJANDRO_IA_PLUGIN_URL n\'est pas définie.';
        }

        // Vérifier les fichiers requis
        $required_files = array(
            'includes/services/class-claude-service.php',
            'includes/class-alejandro-cache.php',
            'includes/admin/admin-page.php'
        );

        foreach ($required_files as $file) {
            if (!file_exists(ALEJANDRO_IA_PLUGIN_DIR . '/' . $file)) {
                $issues[] = "Le fichier '$file' est manquant.";
            }
        }

        // Vérifier les options de la base de données
        $required_options = array(
            'alejandro_settings'
        );

        foreach ($required_options as $option) {
            if (get_option($option) === false) {
                $issues[] = "L'option '$option' n'existe pas dans la base de données.";
            }
        }

        return $issues;
    }

    /**
     * Affiche les erreurs dans la console
     */
    public static function console_log($data) {
        if (WP_DEBUG) {
            echo '<script>';
            echo 'console.log(' . json_encode($data) . ')';
            echo '</script>';
        }
    }
}
