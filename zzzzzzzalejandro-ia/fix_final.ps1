# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# 1. Nettoyer les caractères d'échappement
$content = $content -replace '\\n', "`n"
$content = $content -replace '\\"', '"'
$content = $content -replace "\\\'", "'"

# 2. Corriger les champs de formulaire
$formFields = @(
    @{
        name = "alejandro_ia_gemini_key"
        label = "Clé API Gemini"
    },
    @{
        name = "alejandro_ia_deepl_key"
        label = "Clé API DeepL"
    },
    @{
        name = "alejandro_ia_elevenlabs_key"
        label = "Clé API ElevenLabs"
    },
    @{
        name = "alejandro_ia_weather_key"
        label = "Clé API Météo"
    },
    @{
        name = "alejandro_ia_google_maps_key"
        label = "Clé API Google Maps"
    }
)

foreach ($field in $formFields) {
    $pattern = "<tr>.*?$($field.name).*?</tr>"
    $replacement = @"
                    <tr>
                        <th scope="row">$($field.label)</th>
                        <td>
                            <input type="password" 
                                   name="$($field.name)" 
                                   value="<?php echo esc_attr(get_option('$($field.name)')); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
"@
    $content = $content -replace $pattern, $replacement
}

# 3. Corriger les messages de bienvenue
$welcomeMessages = @(
    @{
        name = "alejandro_ia_welcome_fr"
        label = "Message de bienvenue (FR)"
        default = "Bonjour ! Je suis Alejandro, votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?"
    },
    @{
        name = "alejandro_ia_welcome_en"
        label = "Message de bienvenue (EN)"
        default = "Hello! I'm Alejandro, your virtual assistant. How can I help you today?"
    },
    @{
        name = "alejandro_ia_welcome_es"
        label = "Message de bienvenue (ES)"
        default = "¡Hola! Soy Alejandro, tu asistente virtual. ¿Cómo puedo ayudarte hoy?"
    }
)

foreach ($msg in $welcomeMessages) {
    $pattern = "<tr>.*?$($msg.name).*?</tr>"
    $replacement = @"
                    <tr>
                        <th scope="row">$($msg.label)</th>
                        <td>
                            <textarea name="$($msg.name)" 
                                      rows="2" 
                                      class="large-text"><?php echo esc_textarea(get_option('$($msg.name)', '$($msg.default)')); ?></textarea>
                        </td>
                    </tr>
"@
    $content = $content -replace $pattern, $replacement
}

# Sauvegarder avec encodage UTF-8
[System.IO.File]::WriteAllText(".\alejandro-ia.php", $content, [System.Text.Encoding]::UTF8)

Write-Host "Corrections finales appliquées"
