jQuery(document).ready(function($) {
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
});