import '../assets/js/chat';

describe('Alejandro Chat', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="alejandro-chat-container">
                <div id="alejandro-messages"></div>
                <input type="text" id="alejandro-input">
                <button id="alejandro-send">Envoyer</button>
            </div>
        `;
    });

    test('should add welcome message on init', () => {
        const messages = document.querySelectorAll('.alejandro-message');
        expect(messages.length).toBe(1);
        expect(messages[0]).toHaveClass('assistant');
    });

    test('should send message on button click', () => {
        const input = document.querySelector('#alejandro-input');
        const button = document.querySelector('#alejandro-send');
        
        input.value = 'Test message';
        button.click();

        const messages = document.querySelectorAll('.alejandro-message');
        expect(messages.length).toBe(2); // Welcome + new message
        expect(messages[1].textContent).toBe('Test message');
    });
}); 