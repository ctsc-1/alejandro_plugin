<?php
class Test_Alejandro_NLP extends WP_UnitTestCase {
    private $nlp;

    public function setUp(): void {
        parent::setUp();
        $this->nlp = new Alejandro_NLP();
    }

    public function test_process_message() {
        $message = "Bonjour, quel temps fait-il aujourd'hui ?";
        $response = $this->nlp->process_message($message);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('intent', $response);
        $this->assertArrayHasKey('response', $response);
    }
} 