<?php
/**
 * PHPUnit bootstrap file
 */

// Chemin vers le répertoire des tests WordPress
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Vérification de l'existence des fichiers de test WordPress
if (!file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find $_tests_dir/includes/functions.php\n";
    exit(1);
}

// Chargement des fonctions WordPress de test
require_once $_tests_dir . '/includes/functions.php';

// Chargement automatique du plugin
function _manually_load_plugin() {
    require dirname(dirname(__FILE__)) . '/alejandro-monitor.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Chargement du bootstrap WordPress
require $_tests_dir . '/includes/bootstrap.php'; 