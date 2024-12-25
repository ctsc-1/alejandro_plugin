jQuery(document).ready(function($) {
    const chatContainer = $('#alejandro-messages');
    const input = $('#alejandro-input');
    const sendButton = $('#alejandro-send');

    function addMessage(message, isUser = false) {
        const messageDiv = $('<div>')
            .addClass('message')
            .addClass(isUser ? 'user-message' : 'alejandro-message')
            .text(message);
        chatContainer.append(messageDiv);
        chatContainer.scrollTop(chatContainer[0].scrollHeight);
    }

    function sendMessage() {
        const message = input.val().trim();
        if (!message) return;

        addMessage(message, true);
        input.val('').focus();

        $.ajax({
            url: alejandroData.ajaxurl,
            type: 'POST',
            data: {
                action: 'alejandro_process_message',
                message: message,
                nonce: alejandroData.nonce,
                language: alejandroData.language
            },
            success: function(response) {
                if (response.success && response.data) {
                    addMessage(response.data.response);
                } else {
                    const errorMessage = response.data ? response.data.message : 'Une erreur est survenue';
                    addMessage('Désolé, ' + errorMessage.toLowerCase() + '.', false);
                    console.error('Erreur Alejandro:', response);
                }
            },
            error: function(xhr, status, error) {
                addMessage('Désolé, une erreur de communication est survenue.');
                console.error('Erreur AJAX:', {xhr, status, error});
                alejandro_debug_log('Erreur AJAX: ' + error);
            }
        });
    }

    sendButton.on('click', sendMessage);
    input.on('keypress', function(e) {
        if (e.which === 13) sendMessage();
    });

    // Fonction de débogage côté client
    function alejandro_debug_log(message) {
        if (window.console && window.console.log) {
            console.log('Alejandro Debug:', message);
        }
    }
}); 