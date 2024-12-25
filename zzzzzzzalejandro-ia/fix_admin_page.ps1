# Lire le contenu du fichier
$content = Get-Content .\alejandro-ia.php -Raw

# Nouvelle version de la section admin
$newAdminSection = @'
            <div class="wrap">
                <h1>Configuration Alejandro IA</h1>
                <form method="post" action="options.php">
                    <?php settings_fields('alejandro_ia_options'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Clé API Gemini</th>
                            <td>
                                <input type="password" 
                                       name="alejandro_ia_gemini_key" 
                                       value="<?php echo esc_attr(get_option('alejandro_ia_gemini_key')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Clé API DeepL</th>
                            <td>
                                <input type="password" 
                                       name="alejandro_ia_deepl_key" 
                                       value="<?php echo esc_attr(get_option('alejandro_ia_deepl_key')); ?>" 
                                       class="regular-text">
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
'@

# Remplacer la section problématique
$content = $content -replace '(?s)<div class="wrap">.*?</div>', $newAdminSection

# Sauvegarder les modifications
$content | Set-Content .\alejandro-ia.php -NoNewline

Write-Host "Page d'administration corrigée"
