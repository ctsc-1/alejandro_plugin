<?php
/**
 * Tests pour l'interface d'administration
 */
class Test_Alejandro_Admin extends WP_UnitTestCase {
    private $plugin;
    private $user_id;

    public function setUp(): void {
        parent::setUp();
        $this->plugin = Alejandro_IA::get_instance();
        
        // Créer un utilisateur administrateur
        $this->user_id = $this->factory->user->create([
            'role' => 'administrator'
        ]);
    }

    public function tearDown(): void {
        wp_delete_user($this->user_id);
        parent::tearDown();
    }

    public function test_admin_menu_exists() {
        wp_set_current_user($this->user_id);
        
        $this->plugin->add_admin_menu();
        global $menu;
        
        $menu_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'alejandro-ia') {
                $menu_exists = true;
                break;
            }
        }
        
        $this->assertTrue($menu_exists);
    }

    public function test_usage_stats_structure() {
        $stats = get_option('alejandro_usage_stats');
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('total_tokens', $stats);
        $this->assertArrayHasKey('last_reset', $stats);
    }

    public function test_update_usage_stats() {
        $initial_stats = get_option('alejandro_usage_stats');
        $initial_requests = $initial_stats['total_requests'];
        $initial_tokens = $initial_stats['total_tokens'];

        $this->plugin->update_usage_stats(10);

        $updated_stats = get_option('alejandro_usage_stats');
        $this->assertEquals($initial_requests + 1, $updated_stats['total_requests']);
        $this->assertEquals($initial_tokens + 10, $updated_stats['total_tokens']);
    }

    public function test_admin_page_access() {
        wp_set_current_user($this->user_id);
        
        ob_start();
        $this->plugin->render_admin_page();
        $output = ob_get_clean();

        $this->assertStringContainsString('Alejandro IA - Tableau de bord', $output);
        $this->assertStringContainsString('[alejandro]', $output);
        $this->assertStringContainsString('Statistiques d\'utilisation', $output);
    }

    public function test_admin_page_access_denied() {
        // Créer un utilisateur non-admin
        $user_id = $this->factory->user->create([
            'role' => 'subscriber'
        ]);
        wp_set_current_user($user_id);

        $this->expectException('WPDieException');
        $this->plugin->render_admin_page();
    }
}
