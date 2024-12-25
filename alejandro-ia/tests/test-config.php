<?php
/**
 * Tests pour la gestion de la configuration
 */
class Test_Alejandro_Config extends WP_UnitTestCase {
    private $config_dir;
    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->config_dir = dirname(dirname(__FILE__)) . '/.config/';
        $this->plugin = Alejandro_IA::get_instance();
    }

    public function test_config_directory_exists() {
        $this->assertDirectoryExists($this->config_dir);
    }

    public function test_config_directory_is_protected() {
        $this->assertFileExists($this->config_dir . '.htaccess');
        $htaccess_content = file_get_contents($this->config_dir . '.htaccess');
        $this->assertStringContainsString('Deny from all', $htaccess_content);
    }

    public function test_api_keys_file_exists() {
        $this->assertFileExists($this->config_dir . 'api-keys.php');
    }

    public function test_api_keys_file_is_valid_php() {
        $config = require $this->config_dir . 'api-keys.php';
        $this->assertIsArray($config);
    }

    public function test_get_api_key() {
        $key = $this->plugin->get_api_key('claude');
        $this->assertNotEmpty($key);
        $this->assertIsString($key);
    }

    public function test_invalid_api_key() {
        $key = $this->plugin->get_api_key('invalid_service');
        $this->assertNull($key);
    }
}
