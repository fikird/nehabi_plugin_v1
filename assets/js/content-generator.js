jQuery(document).ready(function($) {
    // Content Generator UI
    function createContentGeneratorModal() {
        const modal = $(`
            <div id="nehabi-content-generator-modal" class="nehabi-modal">
                <div class="nehabi-modal-content">
                    <span class="nehabi-close-modal">&times;</span>
                    <h2>Nehabi Content Generator</h2>
                    
                    <div class="content-type-selector">
                        <button class="content-type-btn active" data-type="image">Image</button>
                        <button class="content-type-btn" data-type="text">Text</button>
                    </div>

                    <div class="content-generator-form">
                        <div class="image-generator" data-type="image">
                            <h3>Image Generator</h3>
                            <form id="nehabi-image-generator-form">
                                <textarea id="image-prompt" placeholder="Describe the image you want to generate..." required></textarea>
                                <select id="image-model">
                                    <option value="stable-diffusion">Stable Diffusion XL</option>
                                    <option value="sdxl-turbo">SDXL Turbo</option>
                                    <option value="kandinsky">Kandinsky</option>
                                </select>
                                <div class="generation-actions">
                                    <button type="submit" class="generate-btn">Generate Image</button>
                                    <button type="button" class="record-btn">Save Image</button>
                                </div>
                            </form>
                            <div id="image-result" class="generation-result"></div>
                        </div>

                        <div class="text-generator" data-type="text" style="display:none;">
                            <h3>Text Generator</h3>
                            <form id="nehabi-text-generator-form">
                                <textarea id="text-prompt" placeholder="Enter a prompt for text generation..." required></textarea>
                                <select id="text-model">
                                    <option value="gpt-2">GPT-2</option>
                                    <option value="bloom">BLOOM</option>
                                    <option value="flan-t5">Flan-T5</option>
                                </select>
                                <input type="number" id="text-length" min="50" max="500" value="100" placeholder="Text Length">
                                <div class="generation-actions">
                                    <button type="submit" class="generate-btn">Generate Text</button>
                                    <button type="button" class="record-btn">Save Text</button>
                                </div>
                            </form>
                            <div id="text-result" class="generation-result"></div>
                        </div>
                    </div>
                </div>
            </div>
        `).appendTo('body');

        // Content type toggle
        $('.content-type-btn').on('click', function() {
            $('.content-type-btn').removeClass('active');
            $(this).addClass('active');
            
            const type = $(this).data('type');
            $('.image-generator, .text-generator').hide();
            $(`.${type}-generator`).show();
        });

        // Close modal
        $('.nehabi-close-modal').on('click', function() {
            $('#nehabi-content-generator-modal').hide();
        });

        // Image Generation
        $('#nehabi-image-generator-form').on('submit', function(e) {
            e.preventDefault();
            const prompt = $('#image-prompt').val();
            const model = $('#image-model').val();

            $.ajax({
                url: nehabi_content_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'nehabi_generate_image',
                    security: nehabi_content_params.nonce,
                    prompt: prompt,
                    model: model
                },
                beforeSend: function() {
                    $('#image-result').html('<p>Generating image...</p>');
                },
                success: function(response) {
                    if (response.success) {
                        $('#image-result').html(`
                            <img src="${response.data.url}" alt="Generated Image">
                            <p>Prompt: ${response.data.prompt}</p>
                        `);
                    } else {
                        $('#image-result').html(`<p class="error">${response.data}</p>`);
                    }
                },
                error: function() {
                    $('#image-result').html('<p class="error">Image generation failed</p>');
                }
            });
        });

        // Text Generation
        $('#nehabi-text-generator-form').on('submit', function(e) {
            e.preventDefault();
            const prompt = $('#text-prompt').val();
            const model = $('#text-model').val();
            const maxLength = $('#text-length').val();

            $.ajax({
                url: nehabi_content_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'nehabi_generate_text',
                    security: nehabi_content_params.nonce,
                    prompt: prompt,
                    model: model,
                    max_length: maxLength
                },
                beforeSend: function() {
                    $('#text-result').html('<p>Generating text...</p>');
                },
                success: function(response) {
                    if (response.success) {
                        $('#text-result').html(`
                            <pre>${response.data.text}</pre>
                            <p>Prompt: ${response.data.prompt}</p>
                        `);
                    } else {
                        $('#text-result').html(`<p class="error">${response.data}</p>`);
                    }
                },
                error: function() {
                    $('#text-result').html('<p class="error">Text generation failed</p>');
                }
            });
        });

        // Record Content
        $('.record-btn').on('click', function() {
            const type = $(this).closest('.generation-form').data('type');
            const content = type === 'image' 
                ? $('#image-result img').attr('src') 
                : $('#text-result pre').text();
            const prompt = type === 'image' 
                ? $('#image-result p').text().replace('Prompt: ', '') 
                : $('#text-result p').text().replace('Prompt: ', '');

            $.ajax({
                url: nehabi_content_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'nehabi_record_content',
                    security: nehabi_content_params.nonce,
                    content_type: type,
                    content: content,
                    prompt: prompt,
                    title: `Nehabi ${type.charAt(0).toUpperCase() + type.slice(1)} Generation`
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert('Failed to record content');
                    }
                },
                error: function() {
                    alert('Error recording content');
                }
            });
        });

        return modal;
    }

    // Add content generator button to admin bar
    $('#wp-admin-bar-new-content').after(`
        <li id="wp-admin-bar-nehabi-content-generator">
            <a href="#" class="ab-item nehabi-content-generator-trigger">
                🤖 Content Generator
            </a>
        </li>
    `);

    // Trigger modal
    $(document).on('click', '.nehabi-content-generator-trigger', function(e) {
        e.preventDefault();
        const modal = $('#nehabi-content-generator-modal');
        modal.length ? modal.show() : createContentGeneratorModal().show();
    });
});
