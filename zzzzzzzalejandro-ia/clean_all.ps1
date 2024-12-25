# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# Nettoyer tous les caractères d'échappement et les sauts de ligne incorrects
$content = $content -replace '\\n', "`n"
$content = $content -replace '\\"', '"'
$content = $content -replace "\\\'", "'"
$content = $content -replace '\\\\', '\'

# Corriger le formatage des inputs
$content = $content -replace '<input type="password"[^>]*value="[^"]*"[^>]*>', {
    $match = $_.ToString()
    if ($match -match 'name="([^"]*)"') {
        $name = $matches[1]
        @"
                                <input type="password" 
                                       name="$name" 
                                       value="<?php echo esc_attr(get_option('$name')); ?>" 
                                       class="regular-text">
"@
    }
}

# Sauvegarder les modifications
$content | Set-Content .\alejandro-ia.php -NoNewline

Write-Host "Nettoyage complet terminé"
