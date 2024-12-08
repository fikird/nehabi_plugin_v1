jQuery(document).ready(function($) {
    // Only run on admin pages
    if (!$('body').hasClass('wp-admin')) return;

    // Create logo generator modal
    const logoGeneratorModal = $(`
        <div id="nehabi-logo-generator-modal" class="nehabi-logo-generator-modal">
            <div class="logo-generator-modal-content">
                <div class="logo-generator-modal-header">
                    <h2>Site Logo Generator</h2>
                    <button class="logo-generator-close-btn">&times;</button>
                </div>
                <div class="logo-generator-modal-body">
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
                </div>
            </div>
        </div>
    `).appendTo('body');

    // Add logo generator button to admin bar
    $('#wp-admin-bar-new-content').after(`
        <li id="wp-admin-bar-nehabi-logo-generator">
            <a href="#" title="Generate Site Logo">
                <span class="ab-icon dashicons dashicons-art"></span>
                <span class="ab-label">Logo Generator</span>
            </a>
        </li>
    `);

    // Open modal
    $('#wp-admin-bar-nehabi-logo-generator a').on('click', function(e) {
        e.preventDefault();
        logoGeneratorModal.show();
    });

    // Close modal
    $('.logo-generator-close-btn').on('click', function() {
        logoGeneratorModal.hide();
    });

    // Toggle custom colors
    $('input[name="color_scheme"]').on('change', function() {
        $('#custom-colors').toggle($(this).val() === 'custom');
    });

    // Logo Generation Form Submission
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
                    logoResult.html(`
                        <h3>Generated Logo</h3>
                        <img src="${response.data.url}" alt="Generated Logo">
                        <div class="logo-actions">
                            <a href="${response.data.url}" download class="button">Download Logo</a>
                            <button class="button button-primary save-to-media-btn" 
                                    data-logo-id="${response.data.id}">
                                Save to Media Library
                            </button>
                        </div>
                        <p><small>Generated with prompt: ${response.data.prompt}</small></p>
                    `);
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
});
