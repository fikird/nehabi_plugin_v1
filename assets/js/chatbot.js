jQuery(document).ready(function($) {
    // Chatbot UI elements
    const chatbotContainer = $('<div>', {
        id: 'nehabi-chatbot-container',
        class: 'nehabi-chatbot-closed'
    });

    const chatbotToggle = $('<button>', {
        id: 'nehabi-chatbot-toggle',
        class: 'nehabi-chatbot-button',
        html: '<span class="dashicons dashicons-format-chat"></span>'
    });

    const chatbotPanel = $('<div>', {
        id: 'nehabi-chatbot-panel',
        class: 'nehabi-chatbot-panel'
    });

    const chatbotHeader = $('<div>', {
        class: 'nehabi-chatbot-header',
        html: '<h3>AI Assistant</h3><button id="nehabi-chatbot-close">×</button>'
    });

    const chatbotMessages = $('<div>', {
        id: 'nehabi-chatbot-messages',
        class: 'nehabi-chatbot-messages'
    });

    const chatbotInputContainer = $('<div>', {
        class: 'nehabi-chatbot-input-container'
    });

    const chatbotInput = $('<input>', {
        type: 'text',
        id: 'nehabi-chatbot-input',
        placeholder: 'Type your message...'
    });

    const chatbotSendButton = $('<button>', {
        id: 'nehabi-chatbot-send',
        html: '<span class="dashicons dashicons-arrow-right-alt"></span>'
    });

    // Construct chatbot UI
    chatbotInputContainer.append(chatbotInput, chatbotSendButton);
    chatbotPanel.append(
        chatbotHeader, 
        chatbotMessages, 
        chatbotInputContainer
    );
    chatbotContainer.append(chatbotToggle, chatbotPanel);

    // Append to body
    $('body').append(chatbotContainer);

    // Toggle chatbot
    chatbotToggle.on('click', function() {
        chatbotContainer.toggleClass('nehabi-chatbot-closed');
    });

    // Close chatbot
    $('#nehabi-chatbot-close').on('click', function() {
        chatbotContainer.addClass('nehabi-chatbot-closed');
    });

    // Send message
    function sendMessage() {
        const message = chatbotInput.val().trim();
        if (!message) return;

        // Add user message to chat
        addMessage('user', message);
        chatbotInput.val('');

        // Send AJAX request
        $.ajax({
            url: nehabi_chat_params.ajax_url,
            type: 'POST',
            data: {
                action: 'nehabi_chat_request',
                message: message,
                security: nehabi_chat_params.chat_nonce
            },
            success: function(response) {
                if (response.success) {
                    addMessage('ai', response.data);
                } else {
                    addMessage('ai', 'Sorry, I encountered an error: ' + response.data);
                }
            },
            error: function() {
                addMessage('ai', 'Network error. Please try again.');
            }
        });
    }

    // Add message to chat
    function addMessage(sender, text) {
        const messageElement = $('<div>', {
            class: `nehabi-chatbot-message nehabi-${sender}-message`
        }).text(text);

        chatbotMessages.append(messageElement);
        chatbotMessages.scrollTop(chatbotMessages[0].scrollHeight);
    }

    // Event listeners
    chatbotSendButton.on('click', sendMessage);
    chatbotInput.on('keypress', function(e) {
        if (e.which === 13) sendMessage();
    });

    // Logo Generator Functionality
    const logoGeneratorPanel = $('<div>', {
        id: 'nehabi-logo-generator-panel',
        class: 'nehabi-logo-generator-panel'
    });

    const logoGeneratorHeader = $(`
        <div class="logo-generator-header">
            <h2>Logo Generator</h2>
            <button class="logo-generator-collapse-btn" aria-label="Collapse Logo Generator">
                <span class="collapse-icon">−</span>
            </button>
        </div>
    `);

    const logoGeneratorContent = $('<div>', {
        class: 'logo-generator-content'
    });

    const logoGeneratorForm = $(`
        <form id="nehabi-logo-form">
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" required>
            </div>

            <div class="form-group">
                <label for="industry">Industry</label>
                <select id="industry" name="industry" required>
                    <option value="">Select Industry</option>
                    <option value="technology">Technology</option>
                    <option value="finance">Finance</option>
                    <option value="healthcare">Healthcare</option>
                    <option value="retail">Retail</option>
                    <option value="food">Food & Restaurant</option>
                    <option value="education">Education</option>
                    <option value="creative">Creative & Design</option>
                    <option value="sports">Sports & Fitness</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Logo Style</label>
                <div class="style-grid">
                    <label class="style-option">
                        <input type="radio" name="style" value="modern" required>
                        <span class="style-preview modern">Modern</span>
                    </label>
                    <label class="style-option">
                        <input type="radio" name="style" value="minimalist">
                        <span class="style-preview minimalist">Minimalist</span>
                    </label>
                    <label class="style-option">
                        <input type="radio" name="style" value="classic">
                        <span class="style-preview classic">Classic</span>
                    </label>
                    <label class="style-option">
                        <input type="radio" name="style" value="tech">
                        <span class="style-preview tech">Tech</span>
                    </label>
                    <label class="style-option">
                        <input type="radio" name="style" value="playful">
                        <span class="style-preview playful">Playful</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label>Color Scheme</label>
                <div class="color-grid">
                    <label class="color-option">
                        <input type="radio" name="color_scheme" value="blue_white" required>
                        <span class="color-preview blue-white">Blue & White</span>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color_scheme" value="black_gold">
                        <span class="color-preview black-gold">Black & Gold</span>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color_scheme" value="green_gray">
                        <span class="color-preview green-gray">Green & Gray</span>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color_scheme" value="red_black">
                        <span class="color-preview red-black">Red & Black</span>
                    </label>
                    <label class="color-option">
                        <input type="radio" name="color_scheme" value="custom">
                        <span class="color-preview custom">Custom Colors</span>
                    </label>
                </div>
            </div>

            <div id="custom-colors" class="form-group" style="display: none;">
                <label>Custom Colors</label>
                <div class="custom-colors-grid">
                    <div class="color-input">
                        <label for="primary_color">Primary Color</label>
                        <input type="color" id="primary_color" name="primary_color">
                    </div>
                    <div class="color-input">
                        <label for="secondary_color">Secondary Color</label>
                        <input type="color" id="secondary_color" name="secondary_color">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" id="generate-logo-btn">Generate Logo</button>
            </div>

            <div id="logo-result" class="logo-result"></div>
        </form>
    `);

    // Append elements
    logoGeneratorContent.append(logoGeneratorForm);
    logoGeneratorPanel.append(logoGeneratorHeader, logoGeneratorContent);
    $('body').append(logoGeneratorPanel);

    // Collapse/Expand functionality
    $('.logo-generator-collapse-btn').on('click', function() {
        const panel = $('#nehabi-logo-generator-panel');
        const collapseIcon = $('.collapse-icon');
        
        panel.toggleClass('collapsed');
        
        if (panel.hasClass('collapsed')) {
            collapseIcon.text('+');
        } else {
            collapseIcon.text('−');
        }
    });

    // Toggle custom colors
    $('input[name="color_scheme"]').on('change', function() {
        $('#custom-colors').toggle($(this).val() === 'custom');
    });

    // Logo Generation and Manipulation
    function initLogoManipulation() {
        const logoGeneratorPanel = $('#nehabi-logo-generator-panel');
        const logoCanvasContainer = $('<div>', {
            id: 'logo-canvas-container',
            class: 'logo-canvas-container'
        });

        // Create canvas and overlay for manipulation
        const logoCanvas = $('<canvas>', {
            id: 'logo-manipulation-canvas',
            class: 'logo-manipulation-canvas'
        });
        const canvasOverlay = $('<div>', {
            id: 'logo-canvas-overlay',
            class: 'logo-canvas-overlay'
        });

        // Manipulation controls
        const manipulationControls = $('<div>', {
            class: 'logo-manipulation-controls',
            html: `
                <div class="manipulation-tools">
                    <button class="tool-btn active" data-tool="move">
                        <i class="icon-move">↔</i> Move
                    </button>
                    <button class="tool-btn" data-tool="rotate">
                        <i class="icon-rotate">↻</i> Rotate
                    </button>
                    <button class="tool-btn" data-tool="scale">
                        <i class="icon-scale">⤢</i> Scale
                    </button>
                    <button class="tool-btn" data-tool="filter">
                        <i class="icon-filter">✦</i> Filter
                    </button>
                </div>
                <div class="manipulation-settings">
                    <div class="setting-group" data-tool="move">
                        <label>X Position: <input type="range" min="-50" max="50" value="0" class="position-x"></label>
                        <label>Y Position: <input type="range" min="-50" max="50" value="0" class="position-y"></label>
                    </div>
                    <div class="setting-group" data-tool="rotate" style="display:none;">
                        <label>Rotation: <input type="range" min="0" max="360" value="0" class="rotation-angle"></label>
                    </div>
                    <div class="setting-group" data-tool="scale" style="display:none;">
                        <label>Scale: <input type="range" min="50" max="150" value="100" class="scale-percentage"></label>
                    </div>
                    <div class="setting-group" data-tool="filter" style="display:none;">
                        <label>Brightness: <input type="range" min="0" max="200" value="100" class="brightness-filter"></label>
                        <label>Contrast: <input type="range" min="0" max="200" value="100" class="contrast-filter"></label>
                        <label>Saturation: <input type="range" min="0" max="200" value="100" class="saturation-filter"></label>
                    </div>
                </div>
            `
        });

        // Assemble components
        logoCanvasContainer.append(logoCanvas, canvasOverlay);
        logoGeneratorPanel.find('.logo-generator-content').append(
            logoCanvasContainer, 
            manipulationControls
        );

        // Tool selection logic
        $('.tool-btn').on('click', function() {
            // Toggle active state
            $('.tool-btn').removeClass('active');
            $(this).addClass('active');

            // Show corresponding settings
            const tool = $(this).data('tool');
            $('.setting-group').hide();
            $(`.setting-group[data-tool="${tool}"]`).show();
        });

        // Canvas interaction
        let isDragging = false;
        let startX, startY;
        let currentTool = 'move';

        logoCanvas.on('mousedown', function(e) {
            isDragging = true;
            startX = e.clientX - logoCanvas.offset().left;
            startY = e.clientY - logoCanvas.offset().top;
        });

        $(document).on('mousemove', function(e) {
            if (!isDragging) return;

            const currentX = e.clientX - logoCanvas.offset().left;
            const currentY = e.clientY - logoCanvas.offset().top;

            switch(currentTool) {
                case 'move':
                    // Update position inputs
                    $('.position-x').val(currentX - startX);
                    $('.position-y').val(currentY - startY);
                    break;
                case 'rotate':
                    // Calculate rotation angle
                    const angle = Math.atan2(currentY - startY, currentX - startX) * (180 / Math.PI);
                    $('.rotation-angle').val(angle);
                    break;
                case 'scale':
                    // Calculate scale based on distance
                    const distance = Math.sqrt(
                        Math.pow(currentX - startX, 2) + 
                        Math.pow(currentY - startY, 2)
                    );
                    $('.scale-percentage').val(100 + distance);
                    break;
            }
        });

        $(document).on('mouseup', function() {
            isDragging = false;
        });

        // Filter and transformation sliders
        $('.position-x, .position-y').on('input', function() {
            // Implement canvas translation
            console.log('Position updated');
        });

        $('.rotation-angle').on('input', function() {
            // Implement canvas rotation
            console.log('Rotation updated');
        });

        $('.scale-percentage').on('input', function() {
            // Implement canvas scaling
            console.log('Scale updated');
        });

        $('.brightness-filter, .contrast-filter, .saturation-filter').on('input', function() {
            // Implement image filtering
            console.log('Filters updated');
        });

        // Advanced color picker for filters
        const colorPickerHtml = `
            <div class="color-overlay-settings">
                <label>Color Overlay: 
                    <input type="color" class="color-overlay-picker">
                </label>
                <label>Overlay Intensity: 
                    <input type="range" min="0" max="100" value="50" class="color-overlay-intensity">
                </label>
            </div>
        `;
        manipulationControls.find('.manipulation-settings').append(colorPickerHtml);

        $('.color-overlay-picker, .color-overlay-intensity').on('input', function() {
            // Implement color overlay
            console.log('Color overlay updated');
        });
    }

    // Initialize on document ready
    initLogoManipulation();
});
