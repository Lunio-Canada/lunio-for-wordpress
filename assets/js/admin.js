jQuery(document).ready(function($) {
    console.log('Lunio admin JS loaded');
    console.log('lunioAjax:', typeof lunioAjax !== 'undefined' ? lunioAjax : 'NOT FOUND');

    function updateAccountStatus(data) {
        if (!data) {
            $('#lunio-account-details').html('<div class="lunio-status-row"><span>No account data available.</span></div>');
            return;
        }
        var html = '';
        html += '<div class="lunio-status-row"><span>Plan:</span><span>' + (data.plan ? data.plan.name : 'N/A') + '</span></div>';
        html += '<div class="lunio-status-row"><span>Monthly Limit:</span><span>' + (data.plan ? data.plan.monthly_limit : 'N/A') + ' requests</span></div>';
        html += '<div class="lunio-status-row"><span>Used:</span><span>' + (data.usage ? data.usage.requests_used : 'N/A') + '</span></div>';
        html += '<div class="lunio-status-row"><span>Remaining:</span><span>' + (data.usage ? data.usage.requests_remaining : 'N/A') + '</span></div>';
        html += '<div class="lunio-status-row"><span>Period:</span><span>' + (data.usage ? data.usage.period : 'N/A') + '</span></div>';
        html += '<div class="lunio-status-row"><span>Branding Removal:</span><span>' + (data.features && data.features.can_remove_branding ? 'Available' : 'Not Available') + '</span></div>';
        $('#lunio-account-details').html(html);
    }

    function updatePluginStatus(cardHtml, cardClass) {
        var card = $('#lunio-update-card');

        if (!card.length) {
            return;
        }

        card.attr('class', 'lunio-update-card ' + cardClass).html(cardHtml);
    }

    $('#lunio-test-connection').on('click', function(e) {
        e.preventDefault();
        console.log('Test Connection clicked');
        var button = $(this);
        button.prop('disabled', true).text('Testing...');
        console.log('Posting to:', lunioAjax.ajaxurl);
        console.log('Action:', 'lunio_test_connection');
        console.log('Nonce:', lunioAjax.test_nonce);
        $.post(lunioAjax.ajaxurl, {
            action: 'lunio_test_connection',
            nonce: lunioAjax.test_nonce
        }, function(response) {
            console.log('Test Connection response:', response);
            if (response.data && response.data.message) {
                $('#lunio-test-result').html(response.data.message);
            } else {
                $('#lunio-test-result').html('<div class="notice notice-error"><p>Unexpected response format.</p></div>');
            }
            button.prop('disabled', false).text('Test Connection');
        }).fail(function(xhr, status, error) {
            console.error('Test Connection AJAX failed:', status, error);
            $('#lunio-test-result').html('<div class="notice notice-error"><p>AJAX error: ' + status + '</p></div>');
            button.prop('disabled', false).text('Test Connection');
        });
    });

    $('#lunio-refresh-status').on('click', function(e) {
        e.preventDefault();
        console.log('Refresh Status clicked');
        var button = $(this);
        button.prop('disabled', true).text('Refreshing...');
        console.log('Posting to:', lunioAjax.ajaxurl);
        console.log('Action:', 'lunio_refresh_status');
        $.post(lunioAjax.ajaxurl, {
            action: 'lunio_refresh_status',
            nonce: lunioAjax.refresh_nonce
        }, function(response) {
            console.log('Refresh Status response:', response);
            if (response.success && response.data && response.data.account_status) {
                updateAccountStatus(response.data.account_status);
                $('#lunio-refresh-result').html('<div class="notice notice-success"><p>Status updated successfully.</p></div>');
            } else {
                $('#lunio-refresh-result').html('<div class="notice notice-error"><p>Failed to refresh status.</p></div>');
            }
            button.prop('disabled', false).text('Refresh Status');
        }).fail(function(xhr, status, error) {
            console.error('Refresh Status AJAX failed:', status, error);
            $('#lunio-refresh-result').html('<div class="notice notice-error"><p>AJAX error: ' + status + '</p></div>');
            button.prop('disabled', false).text('Refresh Status');
        });
    });

    $('#lunio-check-updates').on('click', function(e) {
        e.preventDefault();
        console.log('Check for Updates clicked');
        var button = $(this);
        button.prop('disabled', true).text('Checking...');

        $.post(lunioAjax.ajaxurl, {
            action: 'lunio_check_updates',
            nonce: lunioAjax.update_nonce
        }, function(response) {
            console.log('Check for Updates response:', response);

            if (response.success && response.data) {
                if (response.data.card_html && response.data.card_class) {
                    updatePluginStatus(response.data.card_html, response.data.card_class);
                }

                if (response.data.message) {
                    $('#lunio-update-result').html(response.data.message);
                }
            } else {
                $('#lunio-update-result').html('<div class="notice notice-error"><p>Unable to check for updates.</p></div>');
            }

            button.prop('disabled', false).text('Check for Updates');
        }).fail(function(xhr, status, error) {
            console.error('Check for Updates AJAX failed:', status, error);
            $('#lunio-update-result').html('<div class="notice notice-error"><p>AJAX error: ' + status + '</p></div>');
            button.prop('disabled', false).text('Check for Updates');
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
