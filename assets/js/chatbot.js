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
        const logoCanvas = $('<canvas>', {
            id: 'logo-manipulation-canvas',
            class: 'logo-manipulation-canvas'
        });
        const logoContainer = $('<div>', {
            id: 'logo-manipulation-container',
            class: 'logo-manipulation-container'
        });

        const resizeHandle = $('<div>', {
            class: 'logo-resize-handle',
            html: '<span class="resize-icon">↔</span>'
        });

        logoContainer.append(logoCanvas, resizeHandle);
        logoGeneratorPanel.find('.logo-generator-content').append(logoContainer);

        let isResizing = false;
        let startX, startWidth;

        resizeHandle.on('mousedown', function(e) {
            isResizing = true;
            startX = e.clientX;
            startWidth = logoCanvas.width();
            $('body').css('cursor', 'col-resize');
            
            $(document).on('mousemove', resizeLogo);
            $(document).on('mouseup', stopResize);
        });

        function resizeLogo(e) {
            if (!isResizing) return;
            
            const dx = e.clientX - startX;
            const newWidth = Math.max(50, Math.min(startWidth + dx, 300));
            
            logoCanvas.css('width', `${newWidth}px`);
            logoCanvas.css('height', `${newWidth}px`);
        }

        function stopResize() {
            isResizing = false;
            $('body').css('cursor', 'default');
            $(document).off('mousemove', resizeLogo);
            $(document).off('mouseup', stopResize);
        }

        // Logo Manipulation Tools
        const toolsContainer = $('<div>', {
            class: 'logo-manipulation-tools',
            html: `
                <button class="tool-btn" data-tool="move">Move</button>
                <button class="tool-btn" data-tool="rotate">Rotate</button>
                <button class="tool-btn" data-tool="scale">Scale</button>
                <button class="tool-btn" data-tool="filter">Filter</button>
            `
        });

        logoGeneratorPanel.find('.logo-generator-content').append(toolsContainer);

        // Tool Selection
        $('.tool-btn').on('click', function() {
            const tool = $(this).data('tool');
            // Implement specific tool logic here
            console.log(`Selected tool: ${tool}`);
        });

        // Generate Logo with Fixed Size
        function generateFixedSizeLogo(logoData) {
            const canvas = document.getElementById('logo-manipulation-canvas');
            const ctx = canvas.getContext('2d');
            
            // Set fixed canvas size
            canvas.width = 100;
            canvas.height = 100;
            
            // Clear previous content
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Create a new image
            const img = new Image();
            img.onload = function() {
                // Calculate scaling to fit within 100x100
                const scale = Math.min(
                    canvas.width / img.width, 
                    canvas.height / img.height
                );
                
                const scaledWidth = img.width * scale;
                const scaledHeight = img.height * scale;
                
                // Center the image
                const x = (canvas.width - scaledWidth) / 2;
                const y = (canvas.height - scaledHeight) / 2;
                
                // Draw the scaled and centered image
                ctx.drawImage(
                    img, 
                    x, y, 
                    scaledWidth, 
                    scaledHeight
                );
            };
            
            img.src = logoData;
            return canvas.toDataURL('image/png');
        }

        // Existing logo generation code modification
        $('#nehabi-logo-form').on('submit', function(e) {
            e.preventDefault();

            const generateBtn = $('#generate-logo-btn');
            const logoResult = $('#logo-result');

            generateBtn.prop('disabled', true).text('Generating...');
            logoResult.html('<p>Generating logo... Please wait.</p>');

            const formData = new FormData(this);
            formData.append('action', 'nehabi_generate_logo');
            formData.append('security', nehabi_logo_params.logo_nonce);

            $.ajax({
                url: nehabi_logo_params.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 150000,
                success: function(response) {
                    generateBtn.prop('disabled', false).text('Generate Logo');

                    if (response.success) {
                        // Generate fixed-size logo
                        const fixedSizeLogo = generateFixedSizeLogo(response.data.url);

                        logoResult.html(`
                            <h3>Generated Logo</h3>
                            <div class="logo-preview-container">
                                <canvas id="logo-manipulation-canvas" width="100" height="100"></canvas>
                                <div class="logo-manipulation-tools">
                                    <button class="tool-btn" data-tool="move">Move</button>
                                    <button class="tool-btn" data-tool="rotate">Rotate</button>
                                    <button class="tool-btn" data-tool="scale">Scale</button>
                                    <button class="tool-btn" data-tool="filter">Filter</button>
                                </div>
                                <div class="logo-actions">
                                    <a href="${fixedSizeLogo}" download class="button">Download Logo</a>
                                    <button class="button button-primary save-to-media-btn" 
                                            data-logo-id="${response.data.id}">
                                        Save to Media Library
                                    </button>
                                </div>
                            </div>
                            <p><small>Generated with prompt: ${response.data.prompt}</small></p>
                        `);

                        // Re-initialize logo manipulation
                        initLogoManipulation();
                    } else {
                        logoResult.html(`
                            <div class="error">
                                <p>Logo generation failed: ${response.data}</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr, status, error) {
                    generateBtn.prop('disabled', false).text('Generate Logo');
                    logoResult.html(`
                        <div class="error">
                            <p>An error occurred: ${error}</p>
                        </div>
                    `);
                }
            });
        });
    }

    function initLogoGeneratorPanel() {
        const logoGeneratorPanel = $('#nehabi-logo-generator-panel');

        // Create collapse button
        const collapseButton = $('<button>', {
            class: 'logo-generator-collapse-btn',
            html: '−',
            title: 'Collapse Logo Generator'
        });

        // Add collapse button to the panel header
        const panelHeader = $('<div>', {
            class: 'logo-generator-panel-header'
        }).append(
            $('<h2>').text('Logo Generator'),
            collapseButton
        );

        // Insert header before existing content
        logoGeneratorPanel.prepend(panelHeader);

        // Collapse functionality
        collapseButton.on('click', function() {
            const panelContent = logoGeneratorPanel.find('.logo-generator-content');
            const isCollapsed = logoGeneratorPanel.hasClass('collapsed');

            if (isCollapsed) {
                // Expand
                panelContent.show();
                logoGeneratorPanel.removeClass('collapsed');
                collapseButton.text('−');
            } else {
                // Collapse
                panelContent.hide();
                logoGeneratorPanel.addClass('collapsed');
                collapseButton.text('+');
            }
        });
    }

    // Initialize on document ready
    initLogoGeneratorPanel();
    initLogoManipulation();
});
