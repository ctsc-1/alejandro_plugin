import '@testing-library/jest-dom';

// Mock jQuery
global.$ = global.jQuery = require('jquery');

// Mock WordPress globals
global.ajaxurl = 'http://test.local/wp-admin/admin-ajax.php';
global.alejandroChat = {
    ajaxurl: 'http://test.local/wp-admin/admin-ajax.php',
    nonce: 'test-nonce',
    i18n: {
        error: 'Une erreur est survenue'
    }
}; 