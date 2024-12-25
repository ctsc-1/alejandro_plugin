# Lire le contenu actuel
$content = Get-Content .\alejandro-ia.php -Raw

# Préparer les nouveaux requires
$new_requires = @"
// Interfaces (doivent être chargées en premier)
require_once dirname(__FILE__) . '/includes/interfaces/interface-weather-provider.php';

// Classes de base et utilitaires
require_once dirname(__FILE__) . '/includes/class-alejandro-cache.php';
require_once dirname(__FILE__) . '/includes/class-alejandro-response.php';

// Services principaux
require_once dirname(__FILE__) . '/includes/class-deepl-translator.php';
require_once dirname(__FILE__) . '/includes/class-elevenlabs-integration.php';
require_once dirname(__FILE__) . '/includes/class-weather-manager.php';
require_once dirname(__FILE__) . '/includes/class-aemet-provider.php';
require_once dirname(__FILE__) . '/includes/class-andalusia-boundary.php';
require_once dirname(__FILE__) . '/includes/class-google-maps-service.php';
require_once dirname(__FILE__) . '/includes/class-gemini-provider.php';
require_once dirname(__FILE__) . '/includes/class-ajax-handler.php';

// Services spécifiques
require_once dirname(__FILE__) . '/includes/services/class-ia-service.php';
require_once dirname(__FILE__) . '/includes/services/class-gemini-service.php';
require_once dirname(__FILE__) . '/includes/services/class-speech-service.php';
require_once dirname(__FILE__) . '/includes/services/class-translation-service.php';
require_once dirname(__FILE__) . '/includes/services/class-weather-service.php';
require_once dirname(__FILE__) . '/includes/services/class-location-service.php';
require_once dirname(__FILE__) . '/includes/services/class-advertisers-service.php';
"@

# Remplacer la section des requires
$content = $content -replace '(?s)// Interfaces.*?class-advertisers-service\.php'';', $new_requires

# Sauvegarder le fichier
$content | Set-Content .\alejandro-ia.php -NoNewline
