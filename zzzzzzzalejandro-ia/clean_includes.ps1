# Script pour nettoyer les includes redondants
$files = @(
    ".\includes\class-elevenlabs-integration.php",
    ".\includes\class-aemet-provider.php",
    ".\includes\class-weather-manager.php",
    ".\includes\class-deepl-translator.php",
    ".\includes\class-google-maps-service.php"
)

foreach ($file in $files) {
    $content = Get-Content $file -Raw
    $content = $content -replace "require_once ALEJANDRO_IA_PLUGIN_DIR \. 'includes/class-alejandro-cache\.php';`n", ""
    $content | Set-Content $file -NoNewline
}
