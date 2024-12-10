(function($) {
    // Ensure the DOM is fully loaded before running scripts
    $(document).ready(function() {
        console.log('Logo Generator Script Starting');

        // Global error handler
        window.onerror = function(message, source, lineno, colno, error) {
            console.error('Global Error:', {
                message: message,
                source: source,
                lineno: lineno,
                colno: colno,
                error: error
            });

            // Show error in the dedicated error container
            var errorContainer = document.getElementById('nehabi-logo-generator-error-container');
            var errorMessage = document.getElementById('nehabi-logo-generator-error-message');
            if (errorContainer && errorMessage) {
                errorMessage.textContent = message || 'An unknown error occurred';
                errorContainer.style.display = 'block';
            }

            // Prevent default error handling
            return true;
        };

        // Hardcoded Hugging Face API Key
        const HUGGING_FACE_API_KEY = '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';

        // Logo Generation and Editing Class
        class LogoGenerator {
            constructor() {
                try {
                    this.generatedImage = null;
                    this.initEventListeners();
                    this.initIndustryOptions();
                    this.initStyleOptions();
                    console.log('Logo Generator Initialized Successfully');
                } catch (error) {
                    console.error('Logo Generator Initialization Error:', error);
                    this.showErrorMessage('Failed to initialize Logo Generator: ' + error.message);
                }
            }

            showErrorMessage(message) {
                var errorContainer = document.getElementById('nehabi-logo-generator-error-container');
                var errorMessage = document.getElementById('nehabi-logo-generator-error-message');
                if (errorContainer && errorMessage) {
                    errorMessage.textContent = message;
                    errorContainer.style.display = 'block';
                }
            }

            initIndustryOptions() {
                const industries = [
                    { value: 'technology', label: 'Technology - Software, Hardware, IT' },
                    { value: 'food-beverage', label: 'Food & Beverage - Restaurants, Cafes, Food Trucks' },
                    { value: 'agriculture', label: 'Agriculture - Farming, Livestock, Produce' },
                    { value: 'finance', label: 'Finance - Banking, Accounting, Investments' },
                    { value: 'healthcare', label: 'Healthcare - Medical, Dental, Wellness' },
                    { value: 'education', label: 'Education - Schools, Universities, Online Courses' },
                    { value: 'retail', label: 'Retail - E-commerce, Brick and Mortar, Wholesale' },
                    { value: 'construction', label: 'Construction - Building, Architecture, Engineering' },
                    { value: 'entertainment', label: 'Entertainment - Music, Film, Theater' }
                ];
                
                const $industrySelect = $('#industry-select');
                
                industries.forEach(industry => {
                    $industrySelect.append(`<option value="${industry.value}">${industry.label}</option>`);
                });
            }

            initStyleOptions() {
                const styles = [
                    { value: 'minimalist', label: 'Minimalist - Simple, Clean, Modern' },
                    { value: 'vintage', label: 'Vintage - Classic, Retro, Distressed' },
                    { value: 'modern', label: 'Modern - Sleek, Contemporary, Abstract' },
                    { value: 'playful', label: 'Playful - Fun, Whimsical, Colorful' },
                    { value: 'elegant', label: 'Elegant - Sophisticated, Luxurious, Refined' },
                    { value: 'geometric', label: 'Geometric - Shapes, Patterns, Symmetry' },
                    { value: 'handcrafted', label: 'Handcrafted - Artisanal, Hand-drawn, Unique' },
                    { value: 'corporate', label: 'Corporate - Professional, Formal, Conservative' }
                ];
                
                const $styleSelect = $('#logo-style');
                
                styles.forEach(style => {
                    $styleSelect.append(`<option value="${style.value}">${style.label}</option>`);
                });
            }

            initEventListeners() {
                $('#logo-generator-form').on('submit', this.handleLogoGeneration.bind(this));
                $('#logo-resize').on('input', this.resizeLogo.bind(this));
                $('#logo-color-picker').on('change', this.changePrimaryColor.bind(this));
                $('#secondary-color-picker').on('change', this.changeSecondaryColor.bind(this));
                $('#use-logo-btn').on('click', this.useLogo.bind(this));
            }

            async handleLogoGeneration(e) {
                e.preventDefault();
                
                try {
                    // Gather all input parameters
                    const companyName = $('#company-name').val() || 'Company';
                    const industry = $('#industry-select').val();
                    const logoStyle = $('#logo-style').val();
                    const primaryColor = $('#logo-color-picker').val();
                    const secondaryColor = $('#secondary-color-picker').val();

                    // Validate inputs
                    if (!industry || !logoStyle) {
                        throw new Error('Please select an industry and logo style');
                    }

                    // Construct detailed prompt
                    const prompt = `${logoStyle} logo for ${companyName} in ${industry} industry, 
                                    primary color: ${primaryColor}, 
                                    secondary color: ${secondaryColor}, 
                                    clean and professional design`;

                    $('#logo-generation-status').html('<p>Generating logo...</p>');

                    const response = await fetch('https://api-inference.huggingface.co/models/artificialguybr/LogoRedmond-LogoLoraForSDXL-V2', {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${HUGGING_FACE_API_KEY}`,
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ 
                            inputs: prompt,
                            parameters: {
                                width: 512,
                                height: 512
                            }
                        })
                    });

                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`HTTP error! status: ${response.status}, details: ${errorText}`);
                    }

                    const imageBlob = await response.blob();
                    this.generatedImage = URL.createObjectURL(imageBlob);

                    $('#generated-logo').html(`
                        <img id="logo-preview" src="${this.generatedImage}" alt="Generated Logo" style="max-width: 300px;">
                    `);
                    $('#logo-controls').show();
                    $('#logo-generation-status').html('<p>Logo generated successfully!</p>');

                } catch (error) {
                    console.error('Logo Generation Error:', error);
                    this.showErrorMessage(error.message);
                    $('#logo-generation-status').html(`
                        <p>Error: ${error.message}</p>
                        <div class="error-details">
                            <strong>Troubleshooting Tips:</strong>
                            <ul>
                                <li>Check your Hugging Face API key</li>
                                <li>Ensure the API key has correct permissions</li>
                                <li>Verify network connection</li>
                                <li>Try a different prompt or style</li>
                            </ul>
                        </div>
                    `);
                }
            }

            resizeLogo() {
                if (!this.generatedImage) return;
                const size = $('#logo-resize').val();
                $('#logo-preview').css({
                    'max-width': `${size}px`,
                    'width': '100%',
                    'height': 'auto'
                });
            }

            changePrimaryColor() {
                const color = $('#logo-color-picker').val();
                $('#logo-preview').css('filter', `sepia(100%) hue-rotate(${this.convertHexToRotation(color)}deg)`);
            }

            changeSecondaryColor() {
                const color = $('#secondary-color-picker').val();
                $('#logo-preview').css('filter', `sepia(100%) hue-rotate(${this.convertHexToRotation(color)}deg)`);
            }

            convertHexToRotation(hex) {
                const r = parseInt(hex.slice(1, 3), 16);
                const g = parseInt(hex.slice(3, 5), 16);
                const b = parseInt(hex.slice(5, 7), 16);
                return (r + g + b) % 360;
            }

            useLogo() {
                if (!this.generatedImage) {
                    alert('Please generate a logo first');
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'nehabi_save_logo_to_media',
                        logo_url: this.generatedImage
                    },
                    success: (response) => {
                        if (response.success) {
                            alert('Logo saved to media library and can now be used in your website!');
                        } else {
                            alert('Failed to save logo: ' + response.data);
                        }
                    },
                    error: () => {
                        alert('Error saving logo to media library');
                    }
                });
            }
        }

        // Safely initialize Logo Generator
        try {
            window.logoGenerator = new LogoGenerator();
        } catch (error) {
            console.error('Failed to create Logo Generator:', error);
            var errorContainer = document.getElementById('nehabi-logo-generator-error-container');
            var errorMessage = document.getElementById('nehabi-logo-generator-error-message');
            if (errorContainer && errorMessage) {
                errorMessage.textContent = 'Failed to initialize Logo Generator: ' + error.message;
                errorContainer.style.display = 'block';
            }
        }

        // Diagnostic logging
        console.log('Logo Generator Script Loaded and Initialized');
    });
})(jQuery);
