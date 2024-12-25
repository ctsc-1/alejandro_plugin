jQuery(document).ready(function($) {
    // Fonction pour copier le shortcode dans le presse-papier
    $('.copy-shortcode').on('click', function(e) {
        e.preventDefault();
        
        const shortcode = $(this).prev('code').text();
        const tempInput = $('<input>');
        
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        document.execCommand('copy');
        tempInput.remove();
        
        // Afficher un message de confirmation
        const $message = $('<div class="notice notice-success is-dismissible"><p>Shortcode copié !</p></div>')
            .hide()
            .insertAfter($(this))
            .slideDown();
            
        setTimeout(function() {
            $message.slideUp(function() {
                $(this).remove();
            });
        }, 2000);
    });
}); 