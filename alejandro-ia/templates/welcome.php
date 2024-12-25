<?php
/**
 * Template pour la page d'accueil d'Alejandro
 */

$context = Alejandro_Context::get_instance();
$current_language = $context->get_language();

// Messages d'accueil dans les différentes langues
$welcome_messages = [
    'fr' => "Bonjour ! Je suis Alejandro, votre guide virtuel du Club Costa Tropical. Je suis là pour répondre à toutes vos questions sur la région, les activités du club et la vie à Almuñécar.",
    'es' => "¡Hola! Soy Alejandro, tu guía virtual del Club Costa Tropical. Estoy aquí para responder todas tus preguntas sobre la región, las actividades del club y la vida en Almuñécar.",
    'en' => "Hello! I'm Alejandro, your virtual guide at Club Costa Tropical. I'm here to answer all your questions about the region, club activities and life in Almuñécar."
];

// Noms des drapeaux
$flag_names = [
    'fr' => 'Français',
    'es' => 'Español',
    'en' => 'English'
];
?>

<div class="alejandro-welcome">
    <div class="alejandro-header">
        <div class="avatar-container">
            <img src="/media/images/avatar/alejandro.svg" alt="Alejandro" class="alejandro-avatar">
        </div>
        <div class="language-indicator">
            <img src="<?php echo plugins_url('assets/images/flags/' . $current_language . '.svg', dirname(__FILE__)); ?>" 
                 alt="<?php echo esc_attr($flag_names[$current_language]); ?>" 
                 class="language-flag"
                 title="<?php echo esc_attr($flag_names[$current_language]); ?>">
        </div>
    </div>
    
    <div class="welcome-message">
        <?php echo esc_html($welcome_messages[$current_language]); ?>
    </div>

    <div class="chat-container">
        <?php echo do_shortcode('[alejandro]'); ?>
    </div>
</div>
