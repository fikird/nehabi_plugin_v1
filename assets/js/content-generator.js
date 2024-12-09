jQuery(document).ready(function($) {
    // Content Generation UI
    const contentGeneratorContainer = $('<div>', {
        id: 'nehabi-content-generator',
        class: 'nehabi-content-generator'
    });

    // Image Generation Section
    const imageGenerationSection = $('<div>', {
        class: 'content-generation-section image-generation',
        html: `
            <h3>AI Image Generator</h3>
            <form id="nehabi-image-generation-form">
                <div class="form-group">
                    <label for="image-prompt">Image Prompt</label>
                    <textarea id="image-prompt" name="prompt" required placeholder="Describe the image you want to generate..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="image-model">Model</label>
                        <select id="image-model" name="model">
                            <option value="stable-diffusion">Stable Diffusion XL</option>
                            <option value="sdxl-turbo">SDXL Turbo</option>
                            <option value="kandinsky">Kandinsky</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="image-size">Image Size</label>
                        <select id="image-size" name="size">
                            <option value="512x512">512x512</option>
                            <option value="1024x1024">1024x1024</option>
                            <option value="256x256">256x256</option>
                        </select>
                    </div>
                </div>
                <div class="generation-actions">
                    <button type="submit" class="button button-primary">Generate Image</button>
                </div>
                <div id="image-result" class="generation-result"></div>
            </form>
        `
    });

    // Text Generation Section
    const textGenerationSection = $('<div>', {
        class: 'content-generation-section text-generation',
        html: `
            <h3>AI Text Generator</h3>
            <form id="nehabi-text-generation-form">
                <div class="form-group">
                    <label for="text-prompt">Text Prompt</label>
                    <textarea id="text-prompt" name="prompt" required placeholder="Enter a prompt for text generation..."></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="text-model">Model</label>
                        <select id="text-model" name="model">
                            <option value="gpt-2">GPT-2</option>
                            <option value="bloom">BLOOM</option>
                            <option value="llama">LLaMA</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="text-length">Max Length</label>
                        <select id="text-length" name="length">
                            <option value="100">Short (100 tokens)</option>
                            <option value="200" selected>Medium (200 tokens)</option>
                            <option value="500">Long (500 tokens)</option>
                        </select>
                    </div>
                </div>
                <div class="generation-actions">
                    <button type="submit" class="button button-primary">Generate Text</button>
                </div>
                <div id="text-result" class="generation-result"></div>
            </form>
        `
    });

    // Append sections to container
    contentGeneratorContainer.append(
        imageGenerationSection, 
        textGenerationSection
    );

    // Inject into WordPress admin
    $('#wpbody-content .wrap').append(contentGeneratorContainer);

    // Image Generation Handler
    $('#nehabi-image-generation-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $resultContainer = $('#image-result');
        const $submitBtn = $form.find('button[type="submit"]');

        $submitBtn.prop('disabled', true).text('Generating...');
        $resultContainer.html('<p>Generating image... Please wait.</p>');

        const formData = new FormData(this);
        formData.append('action', 'nehabi_generate_image');
        formData.append('security', nehabi_content_params.content_nonce);

        $.ajax({
            url: nehabi_content_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Generate Image');

                if (response.success) {
                    const imageHtml = `
                        <div class="generated-image-preview">
                            <img src="${response.data.url}" alt="Generated Image">
                            <div class="image-actions">
                                <a href="${response.data.url}" download class="button">Download</a>
                                <button class="button button-primary save-to-media" 
                                        data-image-id="${response.data.id}">
                                    Save to Media
                                </button>
                            </div>
                            <p><small>Prompt: ${response.data.prompt}</small></p>
                        </div>
                    `;
                    $resultContainer.html(imageHtml);
                } else {
                    $resultContainer.html(`<div class="error">${response.data}</div>`);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text('Generate Image');
                $resultContainer.html('<div class="error">Image generation failed.</div>');
            }
        });
    });

    // Text Generation Handler
    $('#nehabi-text-generation-form').on('submit', function(e) {
        e.preventDefault();
        const $form = $(this);
        const $resultContainer = $('#text-result');
        const $submitBtn = $form.find('button[type="submit"]');

        $submitBtn.prop('disabled', true).text('Generating...');
        $resultContainer.html('<p>Generating text... Please wait.</p>');

        const formData = new FormData(this);
        formData.append('action', 'nehabi_generate_text');
        formData.append('security', nehabi_content_params.content_nonce);

        $.ajax({
            url: nehabi_content_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $submitBtn.prop('disabled', false).text('Generate Text');

                if (response.success) {
                    const textHtml = `
                        <div class="generated-text-preview">
                            <pre>${response.data.text}</pre>
                            <div class="text-actions">
                                <button class="button button-primary save-to-posts">
                                    Save as Draft
                                </button>
                            </div>
                            <p><small>Prompt: ${response.data.prompt}</small></p>
                        </div>
                    `;
                    $resultContainer.html(textHtml);
                } else {
                    $resultContainer.html(`<div class="error">${response.data}</div>`);
                }
            },
            error: function() {
                $submitBtn.prop('disabled', false).text('Generate Text');
                $resultContainer.html('<div class="error">Text generation failed.</div>');
            }
        });
    });

    // Save to Media Library
    $(document).on('click', '.save-to-media', function() {
        const imageId = $(this).data('image-id');
        // Implement media library saving logic
        alert('Image saved to media library!');
    });

    // Save Text to Posts
    $(document).on('click', '.save-to-posts', function() {
        const $textPreview = $(this).closest('.generated-text-preview');
        const text = $textPreview.find('pre').text();
        const prompt = $textPreview.find('small').text().replace('Prompt: ', '');

        const formData = new FormData();
        formData.append('action', 'nehabi_save_content');
        formData.append('security', nehabi_content_params.content_nonce);
        formData.append('content_type', 'generated_text');
        formData.append('content', text);
        formData.append('title', prompt);

        $.ajax({
            url: nehabi_content_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Text saved as draft. Edit post: ' + response.data.edit_link);
                } else {
                    alert('Failed to save text: ' + response.data);
                }
            }
        });
    });
});
