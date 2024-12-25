<?php
class Test_Alejandro_Chat extends WP_UnitTestCase {
    private $chat;

    public function setUp(): void {
        parent::setUp();
        $this->chat = new Alejandro_Chat();
    }

    public function test_shortcode_exists() {
        $this->assertTrue(shortcode_exists('alejandro'));
    }

    public function test_chat_render() {
        $output = $this->chat->render();
        
        // Vérifie la présence des éléments essentiels
        $this->assertStringContainsString('alejandro-chat-container', $output);
        $this->assertStringContainsString('alejandro-messages', $output);
        $this->assertStringContainsString('alejandro-input', $output);
    }

    public function test_ajax_endpoint_registered() {
        $this->assertTrue(
            has_action('wp_ajax_alejandro_send_message') !== false
        );
        $this->assertTrue(
            has_action('wp_ajax_nopriv_alejandro_send_message') !== false
        );
    }
} 