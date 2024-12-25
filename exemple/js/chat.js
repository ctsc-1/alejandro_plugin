document.addEventListener('DOMContentLoaded', function() {
    // Initialisation du chat
    addMessage(translations[currentLang].welcome, 'alejandro', false);

    // Gestionnaire d'événements pour l'envoi de messages
    document.getElementById('sendButton').addEventListener('click', sendMessage);
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Gestionnaire pour les boutons de langue
    document.querySelectorAll('.language-btn').forEach(btn => {
        btn.addEventListener('click', () => setLanguage(btn.dataset.lang));
    });
});

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();

    if (message) {
        addMessage(message, 'user', true);
        input.value = '';

        showTypingIndicator();
        setTimeout(() => {
            hideTypingIndicator();
            processUserMessage(message);
        }, 1000);
    }
}

function processUserMessage(message) {
    // Ici, nous ajouterons plus tard la logique de traitement des messages
    // Pour l'instant, nous renvoyons juste un message par défaut
    const response = translations[currentLang].inDevelopment;
    addMessage(response, 'alejandro', false);
}

function addMessage(text, sender, isDynamic = true) {
    const messagesDiv = document.getElementById('messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${sender}${isDynamic ? ' message-dynamic' : ''}`;

    const avatar = sender === 'alejandro' ? 'alejandro.jpg' : 'users/user.png';
    const messageContent = document.createElement('div');
    messageContent.className = 'message-content';
    
    if (isDynamic) {
        messageContent.setAttribute('data-original-text', text);
        if (currentLang !== 'fr') {
            // Plus tard, nous ajouterons ici la traduction en temps réel
            text = translations[currentLang][text] || text;
        }
    }
    messageContent.textContent = text;

    messageDiv.innerHTML = `<img src="media/images/avatar/${avatar}" alt="${sender}" class="avatar-small">`;
    messageDiv.appendChild(messageContent);

    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function showTypingIndicator() {
    document.getElementById('typingIndicator').style.display = 'block';
}

function hideTypingIndicator() {
    document.getElementById('typingIndicator').style.display = 'none';
}

function setLanguage(lang) {
    if (lang === currentLang) return;

    currentLang = lang;
    
    // Mise à jour des boutons de langue
    document.querySelectorAll('.language-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.lang === lang);
    });

    // Mise à jour des éléments traduits
    document.querySelectorAll('[data-translation-key]').forEach(element => {
        const key = element.dataset.translationKey;
        if (translations[lang] && translations[lang][key]) {
            if (element.tagName === 'INPUT') {
                element.placeholder = translations[lang][key];
            } else if (element.tagName === 'BUTTON' && element.title) {
                element.title = translations[lang][key];
            } else {
                element.textContent = translations[lang][key];
            }
        }
    });

    // Mise à jour des messages dynamiques
    document.querySelectorAll('.message-dynamic .message-content').forEach(content => {
        const originalText = content.getAttribute('data-original-text');
        if (originalText && translations[lang][originalText]) {
            content.textContent = translations[lang][originalText];
        }
    });

    // Envoyer la préférence de langue au serveur
    fetch('api/set_language.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ lang: lang })
    });
}

function toggleToolbar() {
    const toolbar = document.querySelector('.toolbar');
    const toggle = document.querySelector('.toolbar-toggle');
    toolbar.classList.toggle('show');
    toggle.classList.toggle('active');
}
