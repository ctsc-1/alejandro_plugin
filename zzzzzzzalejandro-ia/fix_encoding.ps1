# Lire le contenu avec l'encodage UTF-8
$content = [System.IO.File]::ReadAllText(".\alejandro-ia.php", [System.Text.Encoding]::UTF8)

# Corriger les caractères spéciaux
$content = $content -replace "�Hola!", "¡Hola!"
$content = $content -replace "Cl�", "Clé"

# Sauvegarder avec l'encodage UTF-8 avec BOM
[System.IO.File]::WriteAllText(".\alejandro-ia.php", $content, [System.Text.Encoding]::UTF8)

Write-Host "Encodage corrigé"
