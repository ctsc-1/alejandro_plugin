# Chemin vers wp-config.php
$wpConfigPath = "..\wp-config.php"

# Lire le contenu actuel
$content = Get-Content $wpConfigPath -Raw

# Activer le débogage WordPress
$content = $content -replace "define\( *'WP_DEBUG' *, *false *\);", "define('WP_DEBUG', true);"
$content = $content -replace "define\( *'WP_DEBUG_LOG' *, *false *\);", "define('WP_DEBUG_LOG', true);"
$content = $content -replace "define\( *'WP_DEBUG_DISPLAY' *, *true *\);", "define('WP_DEBUG_DISPLAY', false);"

# Ajouter les constantes si elles n'existent pas
if ($content -notmatch "WP_DEBUG_LOG") {
    $content = $content -replace "define\( *'WP_DEBUG'.*?\);", "`$0`ndefine('WP_DEBUG_LOG', true);"
}
if ($content -notmatch "WP_DEBUG_DISPLAY") {
    $content = $content -replace "define\( *'WP_DEBUG'.*?\);", "`$0`ndefine('WP_DEBUG_DISPLAY', false);"
}

# Sauvegarder les modifications
$content | Set-Content $wpConfigPath -NoNewline

Write-Host "Débogage WordPress activé. Les erreurs seront enregistrées dans wp-content/debug.log"
