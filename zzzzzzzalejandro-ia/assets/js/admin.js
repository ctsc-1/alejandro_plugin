jQuery(document).ready(function($) {
    'use strict';

    // Fonction pour sauvegarder les paramètres via AJAX
    function saveSettings() {
        var formData = $('#alejandro-settings-form').serialize();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_alejandro_settings',
                nonce: $('#alejandro_nonce').val(),
                formData: formData
            },
            success: function(response) {
                if (response.success) {
                    // Afficher un message de succès
                    $('.notice').remove();
                    $('#alejandro-settings-form').before('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                } else {
                    // Afficher un message d'erreur
                    $('.notice').remove();
                    $('#alejandro-settings-form').before('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                // Afficher un message d'erreur en cas d'échec de la requête
                $('.notice').remove();
                $('#alejandro-settings-form').before('<div class="notice notice-error is-dismissible"><p>Une erreur est survenue lors de la sauvegarde des paramètres.</p></div>');
            }
        });
    }

    // Gestionnaire d'événement pour le formulaire
    $('#alejandro-settings-form').on('submit', function(e) {
        e.preventDefault();
        saveSettings();
    });

    // Rendre les notices dismissible
    $(document).on('click', '.notice-dismiss', function() {
        $(this).parent().remove();
    });
});
