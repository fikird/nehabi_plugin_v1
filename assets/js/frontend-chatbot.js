jQuery(document).ready(function($) {
    var $chatbot = $('#nehabi-frontend-chatbot');
    var $toggleBtn = $chatbot.find('.chatbot-toggle-btn');
    var $closeBtn = $chatbot.find('.chatbot-close-btn');
    var $chatContainer = $chatbot.find('.chatbot-container');
    var $chatMessages = $chatbot.find('.chatbot-messages');
    var $chatForm = $chatbot.find('.chatbot-input-form');
    var $chatInput = $chatForm.find('textarea');

    // Toggle chatbot visibility
    $toggleBtn.on('click', function(e) {
        e.stopPropagation(); // Prevent event bubbling
        $chatContainer.addClass('open');
        $toggleBtn.hide();
    });

    // Close chatbot
    $closeBtn.on('click', function(e) {
        e.stopPropagation(); // Prevent event bubbling
        $chatContainer.removeClass('open');
        $toggleBtn.show();
    });

    // Close chatbot when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#nehabi-frontend-chatbot').length) {
            $chatContainer.removeClass('open');
            $toggleBtn.show();
        }
    });

    // Prevent closing when interacting with chatbot
    $chatContainer.on('click', function(e) {
        e.stopPropagation();
    });

    // Handle chat form submission
    $chatForm.on('submit', function(e) {
        e.preventDefault();

        var message = $chatInput.val().trim();
        if (!message) return;

        // Append user message
        $chatMessages.append(
            '<div class="chat-message user-message">' + 
            '<strong>You:</strong> ' + message + 
            '</div>'
        );

        // Clear input
        $chatInput.val('');

        // Show loading
        $chatMessages.append(
            '<div class="chat-message bot-message loading">' + 
            'Thinking...' + 
            '</div>'
        );

        // Scroll to bottom
        $chatMessages.scrollTop($chatMessages[0].scrollHeight);

        // AJAX request
        $.ajax({
            url: nehabi_chatbot_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'nehabi_frontend_chat',
                security: nehabi_chatbot_frontend.nonce,
                message: message
            },
            success: function(response) {
                // Remove loading message
                $chatMessages.find('.loading').remove();

                if (response.success) {
                    // Append bot response
                    $chatMessages.append(
                        '<div class="chat-message bot-message">' + 
                        '<strong>AI:</strong> ' + response.data + 
                        '</div>'
                    );
                } else {
                    // Show error
                    $chatMessages.append(
                        '<div class="chat-message error-message">' + 
                        '<strong>AI:</strong> ' + response.data + 
                        '</div>'
                    );
                }

                // Scroll to bottom
                $chatMessages.scrollTop($chatMessages[0].scrollHeight);
            },
            error: function(xhr, status, error) {
                // Remove loading message
                $chatMessages.find('.loading').remove();

                // Show error
                $chatMessages.append(
                    '<div class="chat-message error-message">' + 
                    '<strong>AI:</strong> Sorry, something went wrong. Please try again.' + 
                    '</div>'
                );

                console.error(error);
            }
        });
    });
});
