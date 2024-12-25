# 1. Supprimer les require_once redondants des fichiers
$files = @(
    ".\includes\class-elevenlabs-integration.php",
    ".\includes\class-aemet-provider.php",
    ".\includes\class-weather-manager.php",
    ".\includes\class-deepl-translator.php",
    ".\includes\class-google-maps-service.php"
)

foreach ($file in $files) {
    (Get-Content $file) | 
        Where-Object { $_ -notmatch "require_once.*class-alejandro-cache\.php" } |
        Set-Content $file
}

# 2. Nettoyer le fichier principal
$mainFile = ".\alejandro-ia.php"
$content = Get-Content $mainFile -Raw

# Supprimer les anciens requires
$content = $content -replace '(?s)require_once.*?class-advertisers-service\.php'';[\r\n]*', ''

# Ajouter les nouveaux requires dans l'ordre correct
$newRequires = @"
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

# Insérer les nouveaux requires après <?php
$content = $content -replace '(?s)^<\?php[\r\n]*', "<?php`n$newRequires`n"

# Sauvegarder le fichier
$content | Set-Content $mainFile -NoNewline

Write-Host "Nettoyage terminé !"
