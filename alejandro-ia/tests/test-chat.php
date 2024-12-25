<?php
/**
 * Tests pour la fonctionnalité de chat
 */
class Test_Alejandro_Chat extends WP_UnitTestCase {
    private $plugin;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = Alejandro_IA::get_instance();
    }

    public function test_shortcode_exists() {
        $this->assertTrue(shortcode_exists('alejandro'));
    }

    public function test_shortcode_output() {
        $output = do_shortcode('[alejandro]');
        $this->assertStringContainsString('alejandro-chat', $output);
        $this->assertStringContainsString('chat-messages', $output);
        $this->assertStringContainsString('chat-input', $output);
    }

    public function test_scripts_are_enqueued() {
        $this->plugin->enqueue_scripts();
        
        $this->assertTrue(wp_script_is('alejandro-chat', 'enqueued'));
        $this->assertTrue(wp_style_is('alejandro-style', 'enqueued'));
    }

    public function test_localized_script_data() {
        $this->plugin->enqueue_scripts();
        
        $script = wp_scripts()->registered['alejandro-chat'];
        $this->assertNotEmpty($script->extra['data']);
        $this->assertStringContainsString('alejandroConfig', $script->extra['data']);
        $this->assertStringContainsString('ajaxurl', $script->extra['data']);
        $this->assertStringContainsString('nonce', $script->extra['data']);
    }
}
