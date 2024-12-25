# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# Définir les messages de bienvenue corrects
$messages = @{
    "alejandro_ia_welcome_fr" = "Bonjour ! Je suis Alejandro, votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?"
    "alejandro_ia_welcome_en" = "Hello! I'm Alejandro, your virtual assistant. How can I help you today?"
    "alejandro_ia_welcome_es" = "¡Hola! Soy Alejandro, tu asistente virtual. ¿Cómo puedo ayudarte hoy?"
}

# Remplacer chaque message
foreach ($key in $messages.Keys) {
    $value = $messages[$key]
    $pattern = "(?s)<textarea name=`"$key`".*?</textarea>"
    $replacement = @"
                            <textarea name="$key" 
                                      rows="2" 
                                      class="large-text"><?php echo esc_textarea(get_option('$key', '$value')); ?></textarea>
"@
    $content = $content -replace $pattern, $replacement
}

# Sauvegarder les modifications
[System.IO.File]::WriteAllText(".\alejandro-ia.php", $content, [System.Text.Encoding]::UTF8)

Write-Host "Tous les messages ont été corrigés"
