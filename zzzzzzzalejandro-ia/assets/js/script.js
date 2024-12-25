// Ensure regeneratorRuntime is available
if (typeof regeneratorRuntime === 'undefined') {
    console.error('regeneratorRuntime is not defined. Loading polyfill...');
    import('regenerator-runtime').then(() => {
        console.log('regeneratorRuntime polyfill loaded successfully');
    }).catch(error => {
        console.error('Failed to load regeneratorRuntime:', error);
    });
}

jQuery(document).ready(function($) {
    const chatMessages = $('#chat-messages');
    const userInput = $('#user-message');
    const sendButton = $('#send-message');
    
    // Détecte la langue actuelle via TranslatePress
    const getCurrentLanguage = () => {
        // Essayer d'abord de récupérer la langue de TranslatePress
        const trpLang = window.trp_data ? window.trp_data.trp_current_language : null;
        if (trpLang) {
            console.log('Langue TranslatePress détectée:', trpLang);
            return trpLang.toLowerCase().split('_')[0];
        }
        
        // Sinon, utiliser l'attribut lang de HTML
        const htmlLang = $('html').attr('lang') || 'fr';
        const lang = htmlLang.split('-')[0];
        console.log('Langue HTML détectée:', htmlLang, '→', lang);
        return lang;
    };

    // Ajoute un message au chat
    const addMessage = (message, isUser = false) => {
        const messageDiv = $('<div></div>')
            .addClass('message')
            .addClass(isUser ? 'user' : 'alejandro');
        
        if (!isUser) {
            // Ajouter l'avatar pour les messages d'Alejandro
            const avatarImg = $('<img>')
                .addClass('avatar')
                .attr('src', alejandroIA.pluginUrl + 'assets/media/images/avatar/alejandro-new.svg')
                .attr('alt', 'Alejandro')
                .attr('width', '32')
                .attr('height', '32');
            messageDiv.append(avatarImg);
        }
        
        const contentDiv = $('<div></div>')
            .addClass('message-content')
            .attr('data-trp-translate', 'true')
            .text(message);
        
        messageDiv.append(contentDiv);
        chatMessages.append(messageDiv);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
        
        // Déclencher la traduction TranslatePress
        if (window.trp_custom_ajax_handler && !isUser) {
            window.trp_custom_ajax_handler.reload_scripts();
        }
    };

    // Ajoute l'indicateur de frappe
    const addTypingIndicator = () => {
        const indicator = $('<div class="typing-indicator"><span></span><span></span><span></span></div>');
        chatMessages.append(indicator);
        chatMessages.scrollTop(chatMessages[0].scrollHeight);
        return indicator;
    };

    // Envoie le message
    const sendMessage = () => {
        const message = userInput.val().trim();
        if (!message) return;

        // Ajoute le message de l'utilisateur
        addMessage(message, true);
        userInput.val('');

        // Ajoute l'indicateur de frappe
        const typingIndicator = addTypingIndicator();

        // Envoie la requête AJAX
        $.ajax({
            url: alejandroIA.ajaxurl,
            type: 'POST',
            data: {
                action: 'alejandro_process_message',
                nonce: alejandroIA.nonce,
                message: message,
                language: getCurrentLanguage()
            },
            success: function(response) {
                typingIndicator.remove();
                if (response.success) {
                    addMessage(response.data.message);
                } else {
                    const errorMessages = {
                        'fr': "Désolé, je ne peux pas vous répondre pour le moment.",
                        'es': "Lo siento, no puedo responder en este momento.",
                        'en': "Sorry, I cannot respond at the moment."
                    };
                    addMessage(errorMessages[getCurrentLanguage()] || errorMessages['en']);
                }
            },
            error: function() {
                typingIndicator.remove();
                const errorMessages = {
                    'fr': "Désolé, une erreur est survenue.",
                    'es': "Lo siento, ha ocurrido un error.",
                    'en': "Sorry, an error occurred."
                };
                addMessage(errorMessages[getCurrentLanguage()] || errorMessages['en']);
            }
        });
    };

    // Gestionnaires d'événements
    sendButton.on('click', sendMessage);
    userInput.on('keypress', function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
