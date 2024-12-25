jQuery(document).ready(function($) {
    const chatContainer = $('#alejandro-messages');
    const input = $('#alejandro-input');
    const sendButton = $('#alejandro-send');
    
    class AlejandroChat {
        constructor() {
            this.bindEvents();
            this.addWelcomeMessage();
        }

        bindEvents() {
            sendButton.on('click', () => this.sendMessage());
            input.on('keypress', (e) => {
                if (e.which === 13) this.sendMessage();
            });
        }

        addWelcomeMessage() {
            this.addMessage('Bonjour, je suis Alejandro. Comment puis-je vous aider ?', 'assistant');
        }

        addMessage(message, type = 'user') {
            const messageDiv = $('<div>')
                .addClass('alejandro-message')
                .addClass(type)
                .text(message);
            
            chatContainer.append(messageDiv);
            this.scrollToBottom();
        }

        scrollToBottom() {
            chatContainer.scrollTop(chatContainer[0].scrollHeight);
        }

        sendMessage() {
            const message = input.val().trim();
            if (!message) return;

            this.addMessage(message, 'user');
            input.val('').focus();

            $.ajax({
                url: alejandroChat.ajaxurl,
                type: 'POST',
                data: {
                    action: 'alejandro_send_message',
                    message: message,
                    nonce: alejandroChat.nonce
                },
                beforeSend: () => {
                    sendButton.prop('disabled', true);
                },
                success: (response) => {
                    if (response.success) {
                        this.addMessage(response.data.response, 'assistant');
                    } else {
                        this.addMessage(
                            response.data.message || alejandroChat.i18n.error,
                            'error'
                        );
                    }
                },
                error: () => {
                    this.addMessage(alejandroChat.i18n.error, 'error');
                },
                complete: () => {
                    sendButton.prop('disabled', false);
                }
            });
        }
    }

    // Initialisation du chat
    new AlejandroChat();
}); 