<?php
// Si WordPress n'appelle pas ce fichier, sortir
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Conserver les options (clés API) lors de la désinstallation
// Si vous voulez tout supprimer, décommentez les lignes suivantes
/*
delete_option('alejandro_ia_google_key');
delete_option('alejandro_ia_openweather_key');
delete_option('alejandro_ia_deepl_key');
delete_option('alejandro_ia_aemet_key');
*/

// Nettoyer le cache
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%alejandro_cache_%'");
