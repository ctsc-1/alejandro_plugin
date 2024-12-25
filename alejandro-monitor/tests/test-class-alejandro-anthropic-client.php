<?php
class Test_Alejandro_Anthropic_Client extends WP_UnitTestCase {
    private $client;

    public function setUp(): void {
        parent::setUp();
        $api_key = get_option('alejandro_anthropic_api_key');
        if (empty($api_key)) {
            $this->markTestSkipped('API key not configured');
        }
        $this->client = new Alejandro_Anthropic_Client($api_key);
    }

    public function test_send_message() {
        try {
            $response = $this->client->send_message('Bonjour');
            $this->assertNotEmpty($response);
            $this->assertIsString($response);
        } catch (Exception $e) {
            $this->fail('Exception not expected: ' . $e->getMessage());
        }
    }

    public function test_send_message_with_system_prompt() {
        try {
            $response = $this->client->send_message('Bonjour', [
                'system' => 'Vous êtes Alejandro, un assistant virtuel français.'
            ]);
            $this->assertNotEmpty($response);
            $this->assertIsString($response);
        } catch (Exception $e) {
            $this->fail('Exception not expected: ' . $e->getMessage());
        }
    }
} 