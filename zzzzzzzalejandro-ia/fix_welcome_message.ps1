# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# Nouvelle version du message de bienvenue
$newWelcomeSection = @'
                            <textarea name="alejandro_ia_welcome_fr" 
                                      rows="2" 
                                      class="large-text"><?php echo esc_textarea(get_option("alejandro_ia_welcome_fr", "Bonjour ! Je suis Alejandro, votre assistant virtuel. Comment puis-je vous aider aujourd'hui ?")); ?></textarea>
'@

# Remplacer la section problématique
$content = $content -replace '(?s)<textarea name="alejandro_ia_welcome_fr".*?</textarea>', $newWelcomeSection

# Sauvegarder les modifications
$content | Set-Content .\alejandro-ia.php -NoNewline -Encoding UTF8

Write-Host "Message de bienvenue corrigé"
