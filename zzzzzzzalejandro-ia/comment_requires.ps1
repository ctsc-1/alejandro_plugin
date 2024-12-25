# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# Liste unique des requires dans l'ordre de dépendance
$requires = @"
// 1. Interface de base
//require_once dirname(__FILE__) . '/includes/interfaces/interface-weather-provider.php';

// 2. Classes de base
//require_once dirname(__FILE__) . '/includes/class-alejandro-cache.php';
//require_once dirname(__FILE__) . '/includes/class-alejandro-response.php';

// 3. Services principaux
//require_once dirname(__FILE__) . '/includes/class-deepl-translator.php';
//require_once dirname(__FILE__) . '/includes/class-elevenlabs-integration.php';
//require_once dirname(__FILE__) . '/includes/class-weather-manager.php';
//require_once dirname(__FILE__) . '/includes/class-aemet-provider.php';
//require_once dirname(__FILE__) . '/includes/class-andalusia-boundary.php';
//require_once dirname(__FILE__) . '/includes/class-google-maps-service.php';
//require_once dirname(__FILE__) . '/includes/class-gemini-provider.php';
//require_once dirname(__FILE__) . '/includes/class-ajax-handler.php';

// 4. Services spécifiques
//require_once dirname(__FILE__) . '/includes/services/class-ia-service.php';
//require_once dirname(__FILE__) . '/includes/services/class-gemini-service.php';
//require_once dirname(__FILE__) . '/includes/services/class-speech-service.php';
//require_once dirname(__FILE__) . '/includes/services/class-translation-service.php';
//require_once dirname(__FILE__) . '/includes/services/class-weather-service.php';
//require_once dirname(__FILE__) . '/includes/services/class-location-service.php';
//require_once dirname(__FILE__) . '/includes/services/class-advertisers-service.php';
"@

# Remplacer toutes les sections require_once par notre nouvelle version
$content = $content -replace '(?s)require_once.*?class-advertisers-service\.php'';', $requires

# Sauvegarder le fichier
[System.IO.File]::WriteAllText(".\alejandro-ia.php", $content, [System.Text.Encoding]::UTF8)

Write-Host "Les requires ont été commentés et organisés"
