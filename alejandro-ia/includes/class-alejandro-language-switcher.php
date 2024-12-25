<?php
/**
 * Widget de changement de langue pour Alejandro IA
 */
class Alejandro_Language_Switcher extends WP_Widget {
    private $i18n;

    public function __construct() {
        parent::__construct(
            'alejandro_language_switcher',
            __('Alejandro Language Switcher', 'alejandro-ia'),
            ['description' => __('Language switcher for Alejandro IA', 'alejandro-ia')]
        );
        
        $this->i18n = Alejandro_I18n::get_instance();
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $current_language = $this->i18n->get_current_language();
        $language_urls = $this->i18n->get_language_urls();

        echo '<div class="alejandro-language-switcher">';
        echo '<ul class="language-list">';
        
        foreach ($language_urls as $locale => $url) {
            $lang_code = substr($locale, 0, 2);
            $class = ($locale === $current_language) ? 'active' : '';
            
            echo sprintf(
                '<li class="%s"><a href="%s" lang="%s">%s</a></li>',
                esc_attr($class),
                esc_url($url),
                esc_attr($lang_code),
                esc_html($this->get_language_name($locale))
            );
        }
        
        echo '</ul>';
        echo '</div>';

        echo $args['after_widget'];
    }

    private function get_language_name($locale) {
        $languages = [
            'fr_FR' => 'Français',
            'es_ES' => 'Español',
            'en_US' => 'English'
        ];
        
        return isset($languages[$locale]) ? $languages[$locale] : $locale;
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_attr_e('Title:', 'alejandro-ia'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) 
            ? strip_tags($new_instance['title']) 
            : '';

        return $instance;
    }
}
