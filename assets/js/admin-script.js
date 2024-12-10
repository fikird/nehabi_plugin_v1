jQuery(document).ready(function($) {
    // Color picker initialization
    $('.color-picker').wpColorPicker();

    // Logo Generation Form
    $('#nehabi-logo-generation-form').on('submit', function(e) {
        e.preventDefault();

        // Clear previous results
        $('#logo-generation-result').html('');

        // Show loading indicator
        $('#generate-logo-btn').prop('disabled', true).html('Generating...');

        // Collect form data
        var formData = {
            action: 'nehabi_generate_logo',
            security: nehabi_admin_params.logo_nonce,
            company_name: $('#company_name').val(),
            industry: $('#industry').val(),
            style: $('#style').val(),
            primary_color: $('#primary_color').val(),
            secondary_color: $('#secondary_color').val()
        };

        // AJAX request
        $.ajax({
            url: nehabi_admin_params.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                $('#generate-logo-btn').prop('disabled', false).html('Generate Logo');

                if (response.success) {
                    // Display generated logo
                    var logoHtml = '<div class="generated-logo-container">' +
                        '<img src="' + response.data.url + '" alt="Generated Logo" class="generated-logo">' +
                        '<p>Logo generated for: ' + formData.company_name + '</p>' +
                        '<a href="' + response.data.url + '" download class="button">Download Logo</a>' +
                        '</div>';
                    $('#logo-generation-result').html(logoHtml);
                } else {
                    // Show error message
                    $('#logo-generation-result').html('<div class="error-message">' + response.data + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#generate-logo-btn').prop('disabled', false).html('Generate Logo');
                $('#logo-generation-result').html('<div class="error-message">An unexpected error occurred. Please try again.</div>');
                console.error(error);
            }
        });
    });

    // Chatbot Form
    $('#nehabi-chatbot-form').on('submit', function(e) {
        e.preventDefault();

        var $messageInput = $('#user-message');
        var $chatMessages = $('#chatbot-messages');
        var message = $messageInput.val().trim();

        if (!message) return;

        // Append user message
        $chatMessages.append('<div class="chat-message user-message">' + 
            '<strong>You:</strong> ' + message + 
        '</div>');

        // Clear input
        $messageInput.val('');

        // Show loading
        $chatMessages.append('<div class="chat-message bot-message loading">Thinking...</div>');

        // Scroll to bottom
        $chatMessages.scrollTop($chatMessages[0].scrollHeight);

        // AJAX request
        $.ajax({
            url: nehabi_admin_params.ajax_url,
            type: 'POST',
            data: {
                action: 'nehabi_ai_chat',
                security: nehabi_admin_params.chat_nonce,
                message: message
            },
            success: function(response) {
                // Remove loading message
                $chatMessages.find('.loading').remove();

                if (response.success) {
                    // Append bot response
                    $chatMessages.append('<div class="chat-message bot-message">' + 
                        '<strong>AI:</strong> ' + response.data + 
                    '</div>');
                } else {
                    // Show error
                    $chatMessages.append('<div class="chat-message error-message">' + 
                        '<strong>AI:</strong> ' + response.data + 
                    '</div>');
                }

                // Scroll to bottom
                $chatMessages.scrollTop($chatMessages[0].scrollHeight);
            },
            error: function(xhr, status, error) {
                // Remove loading message
                $chatMessages.find('.loading').remove();

                // Show error
                $chatMessages.append('<div class="chat-message error-message">' + 
                    '<strong>AI:</strong> Sorry, something went wrong. Please try again.' + 
                '</div>');

                console.error(error);
            }
        });
    });
});
