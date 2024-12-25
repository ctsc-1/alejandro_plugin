<?php
class Test_Alejandro_Monitor extends WP_UnitTestCase {
    public function test_plugin_initialized() {
        $instance = Alejandro_Monitor::get_instance();
        $this->assertInstanceOf(Alejandro_Monitor::class, $instance);
    }
} 