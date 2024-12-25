<?php
/**
 * Tests pour la structure des fichiers et les dépendances
 */
class Test_File_Structure extends WP_UnitTestCase {
    private $plugin_dir;
    private $js_dir;
    private $required_files;

    public function setUp(): void {
        parent::setUp();
        $this->plugin_dir = dirname(__DIR__);
        $this->js_dir = $this->plugin_dir . '/assets/js';
        $this->required_files = array(
            'alejandro-ia.php',
            'assets/js/script.js',
            'assets/js/alejandro-chat.js',
            'assets/js/admin.js',
            'includes/services/class-claude-service.php',
            'includes/class-alejandro-personality.php'
        );
    }

    /**
     * Test 1: Vérifie que tous les fichiers requis existent
     */
    public function test_required_files_exist() {
        foreach ($this->required_files as $file) {
            $this->assertFileExists(
                $this->plugin_dir . '/' . $file,
                "Le fichier {$file} est manquant"
            );
        }
    }

    /**
     * Test 2: Vérifie que les fichiers JS n'ont pas de doublons d'ID
     */
    public function test_js_files_no_duplicate_ids() {
        $js_files = array(
            'script.js',
            'alejandro-chat.js',
            'admin.js'
        );

        foreach ($js_files as $file) {
            $content = file_get_contents($this->js_dir . '/' . $file);
            $this->assertNotRegExp(
                '/getElementById\([\'"]([^\'"]+)[\'"]\).*getElementById\([\'"]\\1[\'"]\)/s',
                $content,
                "Le fichier {$file} contient des doublons d'ID"
            );
            $this->assertNotRegExp(
                '/\$\([\'"]#([^\'"]+)[\'"]\).*\$\([\'"]#\\1[\'"]\)/s',
                $content,
                "Le fichier {$file} contient des doublons de sélecteurs jQuery"
            );
        }
    }

    /**
     * Test 3: Vérifie que les fichiers PHP n'ont pas de doublons d'ID dans les formulaires
     */
    public function test_php_forms_no_duplicate_ids() {
        $php_files = array(
            'alejandro-ia.php',
            'includes/admin/admin-page.php'
        );

        foreach ($php_files as $file) {
            $content = file_get_contents($this->plugin_dir . '/' . $file);
            $this->assertNotRegExp(
                '/id=[\'"]([^\'"]+)[\'"].*id=[\'"]\\1[\'"]/s',
                $content,
                "Le fichier {$file} contient des doublons d'ID dans les formulaires"
            );
        }
    }

    /**
     * Test 4: Vérifie que les dépendances JavaScript sont correctement déclarées
     */
    public function test_js_dependencies() {
        $main_file = file_get_contents($this->plugin_dir . '/alejandro-ia.php');
        
        // Vérifie que regenerator-runtime est chargé avant les autres scripts
        $this->assertRegExp(
            '/wp_enqueue_script.*regenerator-runtime.*wp_enqueue_script.*alejandro-chat/s',
            $main_file,
            "regenerator-runtime doit être chargé avant alejandro-chat"
        );

        // Vérifie que jQuery est une dépendance
        $this->assertRegExp(
            '/wp_enqueue_script.*array\([\'"]jquery[\'"]\)/',
            $main_file,
            "jQuery doit être déclaré comme dépendance"
        );
    }

    /**
     * Test 5: Vérifie la présence des hooks WordPress essentiels
     */
    public function test_essential_wordpress_hooks() {
        $main_file = file_get_contents($this->plugin_dir . '/alejandro-ia.php');
        
        $required_hooks = array(
            'plugins_loaded',
            'wp_enqueue_scripts',
            'admin_enqueue_scripts',
            'admin_menu',
            'init'
        );

        foreach ($required_hooks as $hook) {
            $this->assertRegExp(
                '/add_action\([\'"]' . $hook . '[\'"]/i',
                $main_file,
                "Le hook {$hook} est manquant"
            );
        }
    }
}
