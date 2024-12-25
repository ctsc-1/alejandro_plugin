jQuery(document).ready(function($) {
    const chatForm = $('#alejandro-chat-form');
    const chatInput = $('#alejandro-chat-input');
    const chatMessages = $('#alejandro-chat-messages');
    
    chatForm.on('submit', function(e) {
        e.preventDefault();
        
        const message = chatInput.val().trim();
        if (!message) return;
        
        // Afficher le message de l'utilisateur
        appendMessage('user', message);
        chatInput.val('');
        
        // Envoyer la requête à WordPress
        $.ajax({
            url: alejandro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'alejandro_process_message',
                nonce: alejandro_ajax.nonce,
                message: message
            },
            beforeSend: function() {
                // Afficher un indicateur de chargement
                appendMessage('assistant', '<em>En train d\'écrire...</em>', 'typing');
            },
            success: function(response) {
                // Supprimer l'indicateur de chargement
                $('.typing').remove();
                
                if (response.success && response.data) {
                    // Afficher la réponse d'Alejandro
                    appendMessage('assistant', response.data.message);
                } else {
                    appendMessage('assistant', 'Désolé, une erreur s\'est produite.');
                }
            },
            error: function() {
                $('.typing').remove();
                appendMessage('assistant', 'Désolé, une erreur s\'est produite.');
            }
        });
    });
    
    function appendMessage(sender, message, className = '') {
        const messageDiv = $('<div>', {
            class: `message ${sender} ${className}`,
            html: message
        });
        chatMessages.append(messageDiv);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }
});
