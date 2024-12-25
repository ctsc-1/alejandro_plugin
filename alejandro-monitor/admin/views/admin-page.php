<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="card">
        <h2><?php _e('Shortcode Alejandro', 'alejandro-monitor'); ?></h2>
        <p><?php _e('Utilisez ce shortcode pour intégrer Alejandro dans vos pages :', 'alejandro-monitor'); ?></p>
        <code>[alejandro]</code>
        <button class="button copy-shortcode"><?php _e('Copier', 'alejandro-monitor'); ?></button>
    </div>

    <div class="card">
        <h2><?php _e('Configuration API', 'alejandro-monitor'); ?></h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('alejandro_monitor_options');
            do_settings_sections('alejandro_monitor');
            submit_button();
            ?>
        </form>
    </div>

    <div class="card">
        <h2><?php _e('État du système', 'alejandro-monitor'); ?></h2>
        <table class="widefat striped">
            <tbody>
                <tr>
                    <td><?php _e('Version PHP', 'alejandro-monitor'); ?></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><?php _e('Version WordPress', 'alejandro-monitor'); ?></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><?php _e('Version du plugin', 'alejandro-monitor'); ?></td>
                    <td><?php echo ALEJANDRO_MONITOR_VERSION; ?></td>
                </tr>
                <tr>
                    <td><?php _e('TranslatePress', 'alejandro-monitor'); ?></td>
                    <td><?php echo function_exists('trp_get_current_language') ? '✅' : '❌'; ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div> 