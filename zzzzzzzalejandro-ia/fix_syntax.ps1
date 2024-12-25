# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# Corriger la syntaxe des guillemets et des caractères d'échappement
$content = $content -replace "value=\"<\?php echo esc_attr\(get_option\('alejandro_ia_gemini_key'\)\); \?>\"", 'value="<?php echo esc_attr(get_option(\'alejandro_ia_gemini_key\')); ?>"'
$content = $content -replace "value=\"<\?php echo esc_attr\(get_option\('alejandro_ia_deepl_key'\)\);\?\>\"", 'value="<?php echo esc_attr(get_option(\'alejandro_ia_deepl_key\')); ?>"'
$content = $content -replace "\\n", ""
$content = $content -replace "\\\\", "\"

# Sauvegarder les modifications
$content | Set-Content .\alejandro-ia.php -NoNewline

Write-Host "Correction de la syntaxe terminée"
