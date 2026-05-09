jQuery(document).ready(function($) {
    // Test Connection
    $('#lunio-test-connection').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        button.prop('disabled', true).text('Testing...');
        $.post(lunioAjax.ajaxurl, {
            action: 'lunio_test_connection',
            nonce: lunioAjax.nonce
        }, function(response) {
            $('#lunio-test-result').html(response.data.message);
            button.prop('disabled', false).text('Test Connection');
        });
    });

    // Copy Buttons
    $('.lunio-copy-btn').on('click', function() {
        var shortcode = $(this).data('shortcode');
        var btn = $(this);
        var originalText = btn.text();

        function copyToClipboard(text) {
            if (navigator.clipboard && window.isSecureContext) {
                return navigator.clipboard.writeText(text);
            } else {
                // Fallback for older browsers/admin contexts
                var textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                var success = document.execCommand('copy');
                document.body.removeChild(textArea);
                if (success) {
                    return Promise.resolve();
                } else {
                    return Promise.reject(new Error('Fallback copy failed'));
                }
            }
        }

        copyToClipboard(shortcode).then(function() {
            btn.addClass('copied').text('Copied!');
            setTimeout(function() {
                btn.removeClass('copied').text(originalText);
            }, 2000);
        }).catch(function(err) {
            console.error('Failed to copy: ', err);
            btn.text('Error');
            setTimeout(function() {
                btn.text(originalText);
            }, 2000);
        });
    });

    // Accordion
    $('.lunio-accordion-toggle').on('click', function() {
        var content = $(this).next('.lunio-accordion-content');
        var isOpen = content.is(':visible');

        // Close all accordions
        $('.lunio-accordion-content').slideUp();
        $('.lunio-accordion-toggle').removeClass('active');

        // Open clicked one if it wasn't open
        if (!isOpen) {
            content.slideDown();
            $(this).addClass('active');
        }
    });
});