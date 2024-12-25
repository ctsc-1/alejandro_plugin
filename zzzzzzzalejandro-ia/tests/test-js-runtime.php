<?php
/**
 * Tests pour les dépendances JavaScript et runtime
 */
class Test_JS_Runtime extends WP_UnitTestCase {
    private $plugin_dir;
    private $js_dir;

    public function setUp(): void {
        parent::setUp();
        $this->plugin_dir = dirname(__DIR__);
        $this->js_dir = $this->plugin_dir . '/assets/js';
    }

    /**
     * Test 1: Vérifie que les scripts async/await sont correctement protégés
     */
    public function test_async_await_usage() {
        $js_files = array(
            'script.js',
            'alejandro-chat.js',
            'admin.js'
        );

        foreach ($js_files as $file) {
            $content = file_get_contents($this->js_dir . '/' . $file);
            
            // Vérifie si async/await est utilisé
            if (preg_match('/\basync\b|\bawait\b/', $content)) {
                // Vérifie que regeneratorRuntime est importé ou référencé
                $this->assertRegExp(
                    '/regeneratorRuntime|require.*regenerator-runtime/',
                    $content,
                    "Le fichier {$file} utilise async/await mais ne gère pas regeneratorRuntime"
                );
            }
        }
    }

    /**
     * Test 2: Vérifie l'ordre de chargement des scripts
     */
    public function test_script_loading_order() {
        $main_file = file_get_contents($this->plugin_dir . '/alejandro-ia.php');
        
        // Vérifie l'ordre des scripts
        $this->assertRegExp(
            '/wp_enqueue_script.*regenerator-runtime.*wp_enqueue_script.*script\.js/s',
            $main_file,
            "regenerator-runtime doit être chargé avant script.js"
        );

        // Vérifie les dépendances
        $this->assertRegExp(
            '/array\([\'"]jquery[\'"]\s*,\s*[\'"]regenerator-runtime[\'"]\s*/',
            $main_file,
            "Les dépendances doivent inclure jQuery et regenerator-runtime"
        );
    }

    /**
     * Test 3: Vérifie la présence des polyfills nécessaires
     */
    public function test_required_polyfills() {
        $js_files = array(
            'script.js',
            'alejandro-chat.js',
            'admin.js'
        );

        $required_polyfills = array(
            'Promise',
            'fetch',
            'async',
            'await'
        );

        foreach ($js_files as $file) {
            $content = file_get_contents($this->js_dir . '/' . $file);
            
            foreach ($required_polyfills as $polyfill) {
                if (preg_match('/\b' . $polyfill . '\b/', $content)) {
                    $this->assertTrue(
                        strpos($content, 'regeneratorRuntime') !== false || 
                        strpos($main_file, 'regenerator-runtime') !== false,
                        "Le fichier {$file} utilise {$polyfill} mais n'a pas les polyfills nécessaires"
                    );
                }
            }
        }
    }

    /**
     * Test 4: Vérifie la gestion des erreurs pour les fonctions asynchrones
     */
    public function test_async_error_handling() {
        $js_files = array(
            'script.js',
            'alejandro-chat.js',
            'admin.js'
        );

        foreach ($js_files as $file) {
            $content = file_get_contents($this->js_dir . '/' . $file);
            
            if (preg_match('/\basync\b/', $content)) {
                $this->assertRegExp(
                    '/try\s*{.*}\s*catch\s*\(.*\)\s*{/s',
                    $content,
                    "Le fichier {$file} contient des fonctions async mais pas de gestion d'erreurs try/catch"
                );
            }
        }
    }
}
