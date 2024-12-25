<?php
class Test_Alejandro_Language extends WP_UnitTestCase {
    private $language_handler;

    public function setUp(): void {
        parent::setUp();
        $this->language_handler = new Alejandro_Language();
    }

    public function test_language_detection() {
        $message = "Bonjour, comment allez-vous ?";
        $result = $this->language_handler->handle_language_detection($message);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('language', $result);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_supported_languages() {
        $languages = ['fr', 'es', 'en'];
        foreach ($languages as $lang) {
            // Simuler le changement de langue via TranslatePress
            do_action('trp_set_language', $lang);
            $this->language_handler->set_current_language();
            $this->assertEquals($lang, $this->language_handler->get_current_language());
        }
    }
} 