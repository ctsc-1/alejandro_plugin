# Script de nettoyage maître pour Alejandro IA
# Ce script utilise une approche structurée pour nettoyer le code PHP

$content = Get-Content .\alejandro-ia.php -Raw -Encoding UTF8

# 1. Fonction pour dédupliquer les require_once
function Clean-RequireOnce {
    param($content)
    $lines = $content -split "`n"
    $seenFiles = @{}
    $newLines = @()
    
    foreach ($line in $lines) {
        if ($line -match 'require_once.*?([\w-]+\.php)') {
            $filename = $matches[1]
            if (-not $seenFiles.ContainsKey($filename)) {
                $newLines += $line
                $seenFiles[$filename] = $true
            }
        } else {
            $newLines += $line
        }
    }
    
    return $newLines -join "`n"
}

# 2. Fonction pour nettoyer les sections de shortcode
function Clean-Shortcode {
    param($content)
    $shortcodeSection = '<div class="card"><h2>Shortcode</h2><p>Utilisez ce shortcode pour intégrer Alejandro dans vos pages :</p><code>[alejandro_chatbot]</code></div></form>'
    $content = $content -replace '(?s)<div class="card"><h2>Shortcode</h2>.*?</form>', ''
    return $content -replace '(?s)</div>\s*$', "$shortcodeSection`n</div>"
}

# 3. Fonction pour nettoyer les hooks en double
function Clean-Hooks {
    param($content)
    $seenHooks = @{}
    $lines = $content -split "`n"
    $newLines = @()
    
    foreach ($line in $lines) {
        if ($line -match "add_action\('([^']+)',") {
            $hook = $matches[1]
            if (-not $seenHooks.ContainsKey($hook)) {
                $newLines += $line
                $seenHooks[$hook] = $true
            }
        } else {
            $newLines += $line
        }
    }
    
    return $newLines -join "`n"
}

# 4. Fonction pour nettoyer les div vides
function Clean-EmptyDivs {
    param($content)
    return $content -replace '<div class="card">\s*</div>', ''
}

# Exécuter toutes les étapes de nettoyage
$content = Clean-RequireOnce $content
$content = Clean-Shortcode $content
$content = Clean-Hooks $content
$content = Clean-EmptyDivs $content

# Sauvegarder avec le bon encodage
$content | Set-Content -Path .\alejandro-ia.php -Encoding UTF8 -NoNewline
