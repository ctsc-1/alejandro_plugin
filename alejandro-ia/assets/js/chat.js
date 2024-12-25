jQuery(document).ready(function($) {
    const chatMessages = $('.chat-messages');
    const textarea = $('.chat-input textarea');
    const sendButton = $('.send-button');

    function addMessage(content, isUser = false) {
        const messageDiv = $('<div>')
            .addClass('message')
            .addClass(isUser ? 'user' : 'alejandro')
            .text(content);
        chatMessages.append(messageDiv);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
    }

    function sendMessage() {
        const message = textarea.val().trim();
        if (!message) return;

        // Ajouter le message de l'utilisateur
        addMessage(message, true);
        textarea.val('').focus();

        // Désactiver l'interface pendant la requête
        textarea.prop('disabled', true);
        sendButton.prop('disabled', true);

        // Envoyer la requête AJAX
        $.ajax({
            url: alejandroConfig.ajaxurl,
            type: 'POST',
            data: {
                action: 'alejandro_chat',
                message: message,
                nonce: alejandroConfig.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    addMessage(response.data.message);
                } else {
                    addMessage('Désolé, une erreur est survenue.');
                }
            },
            error: function() {
                addMessage('Désolé, une erreur de communication est survenue.');
            },
            complete: function() {
                // Réactiver l'interface
                textarea.prop('disabled', false);
                sendButton.prop('disabled', false);
            }
        });
    }

    // Gestionnaires d'événements
    sendButton.on('click', sendMessage);
    textarea.on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
