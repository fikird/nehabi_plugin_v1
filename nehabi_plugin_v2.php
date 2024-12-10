<?php
/**
 * Plugin Name: Nehabi AI Assistant
 * Description: An AI-powered chatbot and assistant for WordPress
 * Version: 1.0
 * Author: Fikir Ashenafi
 */

// Prevent direct access to the plugin
if (!defined('ABSPATH')) {
    exit;
}

class NehabhAIAssistant {
    private $huggingface_api_key;

    public function __construct() {
        // Hook into WordPress initialization
        add_action('init', [$this, 'init']);
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_logo_generator_assets']);
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Logo-related hooks
        add_action('init', [$this, 'register_logo_post_type']);
        add_action('add_meta_boxes', [$this, 'add_logo_meta_boxes']);
        add_action('save_post', [$this, 'save_logo_details_meta_box']);
        
        // Admin AJAX actions for logo generation
        add_action('wp_ajax_nehabi_generate_logo', [$this, 'handle_logo_generation_request']);
        add_action('wp_ajax_nopriv_nehabi_generate_logo', [$this, 'handle_logo_generation_request']);

        // Shortcode
        add_shortcode('nehabi_logos', [$this, 'nehabi_logos_shortcode']);
    }

    public function init() {
        // Load plugin text domain for internationalization
        load_plugin_textdomain('nehabi-ai-assistant', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        
        // Frontend chatbot AJAX action
        add_action('wp_ajax_nehabi_frontend_chat', [$this, 'handle_frontend_ai_chat_request']);
        add_action('wp_ajax_nopriv_nehabi_frontend_chat', [$this, 'handle_frontend_ai_chat_request']);

        // Enqueue frontend assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Register frontend chatbot shortcode
        add_shortcode('nehabi_chatbot', [$this, 'nehabi_frontend_chatbot_shortcode']);

        // Add chatbot to footer for frontend pages
        add_action('wp_footer', [$this, 'render_frontend_chatbot']);
    }

    public function enqueue_frontend_assets() {
        // Minimal frontend assets, can be empty if no frontend functionality is needed
        wp_enqueue_style(
            'nehabi-plugin-style', 
            plugin_dir_url(__FILE__) . 'css/logo-generator.css', 
            [], 
            '1.0', 
            'all'
        );
        
        // Only enqueue chatbot assets if it should be displayed
        if ($this->should_display_chatbot()) {
            // Enqueue logo generator styles
            wp_enqueue_style(
                'nehabi-logo-generator-styles', 
                plugin_dir_url(__FILE__) . 'assets/css/frontend-logo-generator.css', 
                [], 
                '1.0.0'
            );

            // Enqueue chatbot frontend assets
            wp_enqueue_script('nehabi-chatbot-frontend', 
                plugin_dir_url(__FILE__) . 'assets/js/frontend-chatbot.js', 
                ['jquery'], 
                '1.0', 
                true
            );

            wp_enqueue_style('nehabi-chatbot-frontend', 
                plugin_dir_url(__FILE__) . 'assets/css/frontend-chatbot.css'
            );

            // Localize script with necessary data
            wp_localize_script('nehabi-chatbot-frontend', 'nehabi_chatbot_frontend', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nehabi_frontend_chat_nonce')
            ]);
        }
        
        // Enqueue logo generation scripts
        wp_enqueue_script(
            'nehabi-logos-script', 
            plugin_dir_url(__FILE__) . 'assets/js/logos.js', 
            ['jquery'], 
            '1.0', 
            true
        );

        // Pass admin-ajax.php URL and nonce to frontend script
        wp_localize_script('nehabi-logos-script', 'nehabi_admin_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            // Create a nonce with a specific action
            'logo_nonce' => wp_create_nonce('nehabi_logo_generation_action')
        ]);
    }

    public function enqueue_admin_assets($hook) {
        // Only enqueue on our plugin pages
        if (strpos($hook, 'nehabi_ai_assistant') === false) {
            return;
        }

        // Enqueue color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Enqueue logo generator admin script
        wp_enqueue_script(
            'nehabi-logo-generator-admin', 
            plugin_dir_url(__FILE__) . 'assets/js/logo-generator-admin.js', 
            ['jquery'], 
            '1.0', 
            true
        );

        // Localize script with necessary data
        wp_localize_script('nehabi-logo-generator-admin', 'nehabi_logo_gen', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nehabi_logo_generation_nonce'),
            // Retrieve Hugging Face API key from options
            'hugging_face_api_key' => get_option('nehabi_huggingface_api_key', ''),
        ]);
    }

    public function enqueue_logo_generator_assets($hook) {
        // Check if the current page is the logo generator page
        if ($hook === 'toplevel_page_nehabi-logo-generator') {
            // Enqueue logo generator specific scripts and styles
            wp_enqueue_script('logo-generator-admin', plugin_dir_url(__FILE__) . 'assets/js/logo-generator-admin.js', array('jquery'), '1.0', true);
            wp_enqueue_style('logo-generator-admin', plugin_dir_url(__FILE__) . 'assets/css/logo-generator-admin.css');
            
            // Optional: Localize script with any necessary data
            wp_localize_script('logo-generator-admin', 'logoGeneratorData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nehabi_logo_generator_nonce')
            ));
        }
    }

    public function add_admin_menu() {
        // Remove any existing menu registration
        remove_action('admin_menu', array($this, 'nehabi_logo_gen_menu'));
        
        // Add the menu item within the class method
        add_action('admin_menu', function() {
            // Remove any existing menu with the same slug to prevent duplicates
            global $submenu, $menu;
            
            // Check and remove existing menu entries
            if (isset($submenu['nehabi-logo-generator'])) {
                unset($submenu['nehabi-logo-generator']);
            }
            
            foreach ($menu as $key => $menu_item) {
                if (isset($menu_item[2]) && $menu_item[2] === 'nehabi-logo-generator') {
                    unset($menu[$key]);
                }
            }

            // Add the menu item
            add_menu_page(
                'AI Logo Generator', 
                'AI Logo Generator', 
                'manage_options', 
                'nehabi-logo-generator', 
                array($this, 'render_logo_generator_page'), 
                'dashicons-art', 
                99
            );
        }, 999);
    }

    public function render_logo_generator_page() {
        // Prevent multiple renderings
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;

        ?>
        <div class="wrap nehabi-logo-generator" id="nehabi-logo-generator-debug">
            <div id="nehabi-logo-generator-error-container" style="display:none; background-color: #ffdddd; padding: 10px; margin-bottom: 15px; border: 1px solid red;">
                <strong>Error:</strong> 
                <p id="nehabi-logo-generator-error-message"></p>
            </div>

            <h1>AI Logo Generator</h1>
            
            <form id="logo-generator-form" class="logo-generator-form">
                <div class="form-group">
                    <label for="company-name">Company Name</label>
                    <input type="text" id="company-name" placeholder="Enter Company Name" required>
                </div>
                
                <div class="form-group">
                    <label for="industry-select">Industry</label>
                    <select id="industry-select" required>
                        <option value="">Select Industry</option>
                        <option value="technology">Technology - Software, Hardware, IT</option>
                        <option value="food-beverage">Food & Beverage - Restaurants, Cafes, Food Trucks</option>
                        <option value="agriculture">Agriculture - Farming, Livestock, Produce</option>
                        <option value="finance">Finance - Banking, Accounting, Investments</option>
                        <option value="healthcare">Healthcare - Medical, Dental, Wellness</option>
                        <option value="education">Education - Schools, Universities, Online Courses</option>
                        <option value="retail">Retail - E-commerce, Brick and Mortar, Wholesale</option>
                        <option value="construction">Construction - Building, Architecture, Engineering</option>
                        <option value="entertainment">Entertainment - Music, Film, Theater</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="logo-style">Logo Style</label>
                    <select id="logo-style" required>
                        <option value="">Select Style</option>
                        <option value="minimalist">Minimalist - Simple, Clean, Modern</option>
                        <option value="vintage">Vintage - Classic, Retro, Distressed</option>
                        <option value="modern">Modern - Sleek, Contemporary, Abstract</option>
                        <option value="playful">Playful - Fun, Whimsical, Colorful</option>
                        <option value="elegant">Elegant - Sophisticated, Luxurious, Refined</option>
                        <option value="geometric">Geometric - Shapes, Patterns, Symmetry</option>
                        <option value="handcrafted">Handcrafted - Artisanal, Hand-drawn, Unique</option>
                        <option value="corporate">Corporate - Professional, Formal, Conservative</option>
                    </select>
                </div>
                
                <div class="form-group color-picker-group">
                    <label for="logo-color-picker">Primary Color</label>
                    <input type="color" id="logo-color-picker" value="#4A90E2">
                </div>
                
                <div class="form-group color-picker-group">
                    <label for="secondary-color-picker">Secondary Color</label>
                    <input type="color" id="secondary-color-picker" value="#FF6B6B">
                </div>
                
                <div class="form-group">
                    <button type="submit" class="button button-primary">Generate Logo</button>
                </div>
                
                <div id="logo-generation-status" class="form-group"></div>
                
                <div id="generated-logo" class="form-group"></div>
                
                <div id="logo-controls" class="form-group" style="display:none;">
                    <div class="logo-control-group">
                        <label for="logo-resize">Resize Logo</label>
                        <input type="range" id="logo-resize" min="100" max="500" value="300">
                    </div>
                    <button type="button" id="use-logo-btn" class="button">Save Logo to Media Library</button>
                </div>
            </form>
        </div>
        <script>
        // Add global error handling
        window.addEventListener('error', function(event) {
            console.error('Unhandled error:', event.error);
            var errorContainer = document.getElementById('nehabi-logo-generator-error-container');
            var errorMessage = document.getElementById('nehabi-logo-generator-error-message');
            if (errorContainer && errorMessage) {
                errorMessage.textContent = event.error ? event.error.message : 'An unknown error occurred';
                errorContainer.style.display = 'block';
            }
        });

        // Diagnostic logging
        console.log('Logo Generator Page Loaded');
        console.log('Current Page URL:', window.location.href);
        console.log('Plugin Directory URL:', <?php echo json_encode(plugin_dir_url(__FILE__)); ?>);

        // Remove any duplicate logo generator initializations
        if (window.logoGenerator) {
            console.warn('Removing duplicate logo generator initialization');
            window.logoGenerator = null;
        }
        </script>
        <style>
            /* Ensure the page is always visible */
            #nehabi-logo-generator-debug {
                min-height: 500px;
                background-color: #f9f9f9;
                padding: 20px;
                border: 1px solid #ddd;
            }
        </style>
        <?php
    }

    /**
     * Generate a logo based on input parameters
     * 
     * @param array $args Logo generation arguments
     * @return array|WP_Error Generated logo details
     */
    private function generate_logo($args) {
        // Validate input arguments
        $validated_args = $this->validate_logo_args($args);
        if (is_wp_error($validated_args)) {
            return $validated_args;
        }

        // Construct prompt
        $prompt = $this->construct_logo_prompt($validated_args);

        // Call Hugging Face API
        $api_response = $this->call_huggingface_api($prompt);
        
        if (is_wp_error($api_response)) {
            return $api_response;
        }

        // Process and save logo
        $logo_details = $this->process_logo_image($api_response, $validated_args);
        
        return $logo_details;
    }

    /**
     * Validate logo generation arguments
     * 
     * @param array $args Input arguments
     * @return array|WP_Error Validated arguments
     */
    private function validate_logo_args($args) {
        $defaults = [
            'company_name' => '',
            'industry' => '',
            'style' => 'modern',
            'primary_color' => '#000000',
            'secondary_color' => '#FFFFFF'
        ];

        $args = wp_parse_args($args, $defaults);

        // Sanitize inputs
        $sanitized_args = [
            'company_name' => sanitize_text_field($args['company_name']),
            'industry' => sanitize_text_field($args['industry']),
            'style' => sanitize_text_field($args['style']),
            'primary_color' => sanitize_hex_color($args['primary_color']),
            'secondary_color' => sanitize_hex_color($args['secondary_color'])
        ];

        // Validate required fields
        $errors = [];
        if (empty($sanitized_args['company_name'])) {
            $errors[] = 'Company name is required';
        }
        if (empty($sanitized_args['industry'])) {
            $errors[] = 'Industry is required';
        }

        if (!empty($errors)) {
            return new WP_Error('logo_validation_error', 'Invalid logo generation parameters', [
                'errors' => $errors,
                'input' => $sanitized_args
            ]);
        }

        return $sanitized_args;
    }

    /**
     * Construct a detailed logo generation prompt
     * 
     * @param array $args Validated logo arguments
     * @return string Generated prompt
     */
    private function construct_logo_prompt($args) {
        $prompt_parts = [
            "A {$args['style']} logo for a {$args['industry']} company",
            "Company name: {$args['company_name']}",
            "Primary color: {$args['primary_color']}",
            "Secondary color: {$args['secondary_color']}",
            "High-quality, professional design, vector style, clean lines"
        ];

        return implode(', ', $prompt_parts);
    }

    /**
     * Call Hugging Face API for logo generation
     * 
     * @param string $prompt Logo generation prompt
     * @return array|WP_Error API response or error
     */
    private function call_huggingface_api($prompt) {
        // Retrieve Hugging Face API key
        $api_key = get_option('nehabi_huggingface_api_key', '');

        // Validate API key
        if (empty($api_key)) {
            error_log('NEHABI LOGO GEN: API Key is missing or empty');
            return new WP_Error('api_key_missing', 'Hugging Face API key is not configured', [
                'debug_info' => [
                    'api_key_option_exists' => get_option('nehabi_huggingface_api_key') !== false,
                    'current_user' => wp_get_current_user()->user_login
                ]
            ]);
        }

        $api_base_url = 'https://api-inference.huggingface.co/models/artificialguybr/LogoRedmond-LogoLoraForSDXL-V2';

        // Detailed logging for debugging
        error_log('NEHABI LOGO GEN: Attempting API Call');
        error_log('NEHABI LOGO GEN: Prompt - ' . $prompt);
        error_log('NEHABI LOGO GEN: API URL - ' . $api_base_url);

        $args = [
            'method' => 'POST',
            'timeout' => 120, // Increased timeout for image generation
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => wp_json_encode([
                'inputs' => $prompt,
                'parameters' => [
                    'width' => 512,
                    'height' => 512,
                    'num_inference_steps' => 50,
                    'guidance_scale' => 7.5
                ]
            ]),
            // Disable SSL verification for local development if needed
            'sslverify' => !(defined('WP_DEBUG') && WP_DEBUG === true)
        ];

        // Log request details (be careful not to log sensitive information)
        error_log('NEHABI LOGO GEN: Request Headers - ' . json_encode(array_map(function($v) {
            return $v === $api_key ? '***REDACTED***' : $v;
        }, $args['headers'])));

        // Make the API request
        $response = wp_remote_post($api_base_url, $args);

        // Log raw response for debugging
        error_log('NEHABI LOGO GEN: Raw Response Type: ' . gettype($response));

        // Check for WP HTTP API errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('NEHABI LOGO GEN: WP HTTP API Error - ' . $error_message);
            return new WP_Error('api_request_failed', 'Failed to connect to Hugging Face API', [
                'error' => $error_message,
                'error_data' => $response->get_error_data()
            ]);
        }

        // Check response code
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        // Log detailed response information
        error_log('NEHABI LOGO GEN: Response Code - ' . $response_code);
        error_log('NEHABI LOGO GEN: Response Body (first 500 chars) - ' . substr($response_body, 0, 500));

        // Handle specific HTTP status codes
        switch ($response_code) {
            case 200:
                // Successful response
                break;
            case 401:
                error_log('NEHABI LOGO GEN: Unauthorized - Check API Key');
                return new WP_Error('api_unauthorized', 'Hugging Face API authentication failed', [
                    'status_code' => $response_code,
                    'response_body' => $response_body
                ]);
            case 403:
                error_log('NEHABI LOGO GEN: Forbidden - Permission Denied');
                return new WP_Error('api_forbidden', 'Access to Hugging Face API is forbidden', [
                    'status_code' => $response_code,
                    'response_body' => $response_body,
                    'api_key_length' => strlen($api_key),
                    'api_key_starts_with' => substr($api_key, 0, 5) . '...'
                ]);
            case 429:
                error_log('NEHABI LOGO GEN: Rate Limited');
                return new WP_Error('api_rate_limited', 'Hugging Face API rate limit exceeded', [
                    'status_code' => $response_code,
                    'response_body' => $response_body
                ]);
            case 500:
            case 502:
            case 503:
                error_log('NEHABI LOGO GEN: Server Error');
                return new WP_Error('api_server_error', 'Hugging Face API server error', [
                    'status_code' => $response_code,
                    'response_body' => $response_body
                ]);
            default:
                error_log('NEHABI LOGO GEN: Unexpected Response Code');
                return new WP_Error('api_unexpected_response', 'Unexpected response from Hugging Face API', [
                    'status_code' => $response_code,
                    'response_body' => $response_body
                ]);
        }

        // Parse response body
        $image_data = json_decode($response_body, true);

        // Validate image data
        if (empty($image_data)) {
            error_log('NEHABI LOGO GEN: Empty Response Data');
            return new WP_Error('image_generation_failed', 'No data received from Hugging Face API', [
                'response_body' => $response_body
            ]);
        }

        // Check for error in API response
        if (isset($image_data['error'])) {
            error_log('NEHABI LOGO GEN: API Error Response');
            return new WP_Error('api_error', $image_data['error'], [
                'full_error_details' => $image_data
            ]);
        }

        // Validate generated image
        if (!isset($image_data[0]['generated_image'])) {
            error_log('NEHABI LOGO GEN: No Generated Image Found');
            return new WP_Error('image_generation_failed', 'No image was generated', [
                'response_data' => $image_data
            ]);
        }

        error_log('NEHABI LOGO GEN: Image Generation Successful');
        return $image_data[0]['generated_image'];
    }

    /**
     * Process and save generated logo image
     * 
     * @param string $image_base64 Base64 encoded image
     * @param array $args Logo generation arguments
     * @return array Logo details
     */
    private function process_logo_image($image_base64, $args) {
        // Decode base64 image
        $image_binary = base64_decode($image_base64);
        
        // Generate unique filename
        $upload_dir = wp_upload_dir();
        $filename = sanitize_file_name(
            'logo_' . 
            $args['company_name'] . '_' . 
            $args['industry'] . '_' . 
            current_time('timestamp') . 
            '.png'
        );
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Save image file
        file_put_contents($file_path, $image_binary);

        // Create WordPress attachment
        $attachment = [
            'guid' => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title' => sprintf('Logo for %s - %s', $args['company_name'], $args['industry']),
            'post_content' => '',
            'post_status' => 'inherit'
        ];
        $attachment_id = wp_insert_attachment($attachment, $file_path);

        // Generate attachment metadata
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        return [
            'id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'path' => $file_path,
            'generation_details' => $args
        ];
    }

    // AJAX Handler for Logo Generation
    public function handle_logo_generation_request() {
        // Extensive logging and debugging
        error_log('NEHABI LOGO GEN AJAX: Request Received');
        
        // Check nonce for security
        check_ajax_referer('nehabi_logo_generation_nonce', 'security');
        
        // Log current user capabilities
        $current_user = wp_get_current_user();
        error_log('NEHABI LOGO GEN AJAX: Current User - ' . $current_user->user_login);
        error_log('NEHABI LOGO GEN AJAX: User Capabilities - ' . implode(', ', $current_user->allcaps));
        
        // Verify user permissions
        if (!current_user_can('manage_options')) {
            error_log('NEHABI LOGO GEN AJAX: Insufficient Permissions');
            wp_send_json_error([
                'message' => 'You do not have sufficient permissions',
                'user_login' => $current_user->user_login,
                'user_roles' => $current_user->roles
            ], 403);
            wp_die();
        }

        // Sanitize and validate input
        $company_name = sanitize_text_field($_POST['company_name'] ?? '');
        $industry = sanitize_text_field($_POST['industry'] ?? '');
        $color = sanitize_hex_color($_POST['color'] ?? '');
        $style = sanitize_text_field($_POST['style'] ?? '');

        // Validate inputs
        if (empty($company_name)) {
            error_log('NEHABI LOGO GEN AJAX: Missing Company Name');
            wp_send_json_error([
                'message' => 'Company name is required',
                'input_received' => $_POST
            ], 400);
            wp_die();
        }

        // Generate logo
        $logo_result = $this->generate_logo([
            'company_name' => $company_name,
            'industry' => $industry,
            'color' => $color,
            'style' => $style
        ]);

        // Handle logo generation result
        if (is_wp_error($logo_result)) {
            error_log('NEHABI LOGO GEN AJAX: Logo Generation Failed');
            error_log('Error Code: ' . $logo_result->get_error_code());
            error_log('Error Message: ' . $logo_result->get_error_message());
            
            wp_send_json_error([
                'message' => $logo_result->get_error_message(),
                'error_code' => $logo_result->get_error_code(),
                'error_data' => $logo_result->get_error_data()
            ], 500);
            wp_die();
        }

        // Success response
        error_log('NEHABI LOGO GEN AJAX: Logo Generated Successfully');
        wp_send_json_success([
            'logo_url' => $logo_result,
            'message' => 'Logo generated successfully'
        ]);
        wp_die();
    }

    public function register_logo_post_type() {
        $labels = [
            'name'               => 'Logos',
            'singular_name'      => 'Logo',
            'menu_name'          => 'Logos',
            'add_new'            => 'Add New',
            'add_new_item'       => 'Add New Logo',
            'edit_item'          => 'Edit Logo',
            'new_item'           => 'New Logo',
            'view_item'          => 'View Logo',
            'search_items'       => 'Search Logos',
            'not_found'          => 'No logos found',
            'not_found_in_trash' => 'No logos found in Trash',
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'query_var'           => true,
            'rewrite'             => ['slug' => 'logo'],
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => ['title', 'thumbnail', 'custom-fields'],
            'menu_position'       => 20,
            'menu_icon'           => 'dashicons-art',
        ];

        register_post_type('nehabi_logo', $args);
    }

    // Add custom meta boxes for logo details
    public function add_logo_meta_boxes() {
        add_meta_box(
            'nehabi_logo_details',
            'Logo Details',
            [$this, 'render_logo_details_meta_box'],
            'nehabi_logo',
            'normal',
            'default'
        );
    }

    // Render logo details meta box
    public function render_logo_details_meta_box($post) {
        wp_nonce_field('nehabi_logo_details_nonce', 'nehabi_logo_details_nonce');
        
        // Retrieve existing meta values
        $company_name = get_post_meta($post->ID, '_nehabi_logo_company_name', true);
        $industry = get_post_meta($post->ID, '_nehabi_logo_industry', true);
        $style = get_post_meta($post->ID, '_nehabi_logo_style', true);
        $color_scheme = get_post_meta($post->ID, '_nehabi_logo_color_scheme', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="company_name">Company Name</label></th>
                <td><input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($company_name); ?>" /></td>
            </tr>
            <tr>
                <th><label for="industry">Industry</label></th>
                <td>
                    <select id="industry" name="industry">
                        <option value="">Select Industry</option>
                        <option value="technology" <?php selected($industry, 'technology'); ?>>Technology</option>
                        <option value="finance" <?php selected($industry, 'finance'); ?>>Finance</option>
                        <option value="healthcare" <?php selected($industry, 'healthcare'); ?>>Healthcare</option>
                        <option value="retail" <?php selected($industry, 'retail'); ?>>Retail</option>
                        <option value="food" <?php selected($industry, 'food'); ?>>Food & Restaurant</option>
                        <option value="education" <?php selected($industry, 'education'); ?>>Education</option>
                        <option value="creative" <?php selected($industry, 'creative'); ?>>Creative & Design</option>
                        <option value="sports" <?php selected($industry, 'sports'); ?>>Sports & Fitness</option>
                        <option value="other" <?php selected($industry, 'other'); ?>>Other</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="style">Logo Style</label></th>
                <td>
                    <select id="style" name="style">
                        <option value="">Select Style</option>
                        <option value="modern" <?php selected($style, 'modern'); ?>>Modern</option>
                        <option value="minimalist" <?php selected($style, 'minimalist'); ?>>Minimalist</option>
                        <option value="classic" <?php selected($style, 'classic'); ?>>Classic</option>
                        <option value="tech" <?php selected($style, 'tech'); ?>>Tech</option>
                        <option value="playful" <?php selected($style, 'playful'); ?>>Playful</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="color_scheme">Color Scheme</label></th>
                <td>
                    <select id="color_scheme" name="color_scheme">
                        <option value="">Select Color Scheme</option>
                        <option value="blue_white" <?php selected($color_scheme, 'blue_white'); ?>>Blue & White</option>
                        <option value="black_gold" <?php selected($color_scheme, 'black_gold'); ?>>Black & Gold</option>
                        <option value="green_gray" <?php selected($color_scheme, 'green_gray'); ?>>Green & Gray</option>
                        <option value="red_black" <?php selected($color_scheme, 'red_black'); ?>>Red & Black</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    // Save logo details meta box data
    public function save_logo_details_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['nehabi_logo_details_nonce']) || 
            !wp_verify_nonce($_POST['nehabi_logo_details_nonce'], 'nehabi_logo_details_nonce')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save meta fields
        if (isset($_POST['company_name'])) {
            update_post_meta($post_id, '_nehabi_logo_company_name', sanitize_text_field($_POST['company_name']));
        }

        if (isset($_POST['industry'])) {
            update_post_meta($post_id, '_nehabi_logo_industry', sanitize_text_field($_POST['industry']));
        }

        if (isset($_POST['style'])) {
            update_post_meta($post_id, '_nehabi_logo_style', sanitize_text_field($_POST['style']));
        }

        if (isset($_POST['color_scheme'])) {
            update_post_meta($post_id, '_nehabi_logo_color_scheme', sanitize_text_field($_POST['color_scheme']));
        }
    }

    // Shortcode for displaying logos
    public function nehabi_logos_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts([
            'type' => 'grid',      // grid, carousel, list
            'count' => 10,         // number of logos to display
            'industry' => '',       // filter by industry
            'style' => '',          // filter by style
            'columns' => 4,         // for grid layout
            'size' => 'medium'      // thumbnail size
        ], $atts, 'nehabi_logos');

        // Query logos
        $query_args = [
            'post_type' => 'nehabi_logo',
            'posts_per_page' => intval($atts['count']),
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        // Add industry filter
        if (!empty($atts['industry'])) {
            $query_args['meta_query'][] = [
                'key' => '_nehabi_logo_industry',
                'value' => sanitize_text_field($atts['industry']),
                'compare' => '='
            ];
        }

        // Add style filter
        if (!empty($atts['style'])) {
            $query_args['meta_query'][] = [
                'key' => '_nehabi_logo_style',
                'value' => sanitize_text_field($atts['style']),
                'compare' => '='
            ];
        }

        $logos_query = new WP_Query($query_args);

        // Start output buffer
        ob_start();

        // Check if logos exist
        if ($logos_query->have_posts()) {
            // Enqueue styles and scripts
            wp_enqueue_style('nehabi-logos-style');
            
            // Start container based on type
            echo '<div class="nehabi-logos-container nehabi-logos-' . esc_attr($atts['type']) . '">';

            // Carousel requires specific markup
            if ($atts['type'] === 'carousel') {
                echo '<div class="nehabi-logos-carousel-wrapper">';
            }

            // Grid layout
            if ($atts['type'] === 'grid') {
                echo '<div class="nehabi-logos-grid" style="display: grid; grid-template-columns: repeat(' . 
                     intval($atts['columns']) . ', 1fr); gap: 15px;">';
            }

            // Loop through logos
            while ($logos_query->have_posts()) {
                $logos_query->the_post();
                
                // Get logo details
                $company_name = get_post_meta(get_the_ID(), '_nehabi_logo_company_name', true);
                $industry = get_post_meta(get_the_ID(), '_nehabi_logo_industry', true);
                $style = get_post_meta(get_the_ID(), '_nehabi_logo_style', true);

                // Logo item markup
                echo '<div class="nehabi-logo-item" data-industry="' . esc_attr($industry) . '" data-style="' . esc_attr($style) . '">';
                
                if (has_post_thumbnail()) {
                    the_post_thumbnail($atts['size'], [
                        'class' => 'nehabi-logo-image',
                        'alt' => esc_attr($company_name)
                    ]);
                }
                
                echo '<div class="nehabi-logo-details">';
                echo '<h3 class="nehabi-logo-title">' . esc_html($company_name) . '</h3>';
                echo '<span class="nehabi-logo-meta">' . esc_html($industry) . ' | ' . esc_html($style) . '</span>';
                echo '</div>';
                echo '</div>';
            }

            // Close grid/carousel container
            echo '</div>';

            // Close carousel wrapper if needed
            if ($atts['type'] === 'carousel') {
                echo '</div>';
            }

            // Reset post data
            wp_reset_postdata();
        } else {
            echo '<p>No logos found.</p>';
        }

        // Return buffered content
        return ob_get_clean();
    }

    // Enqueue logo styles and scripts
    public function enqueue_logo_assets() {
        // Logo display styles
        wp_register_style('nehabi-logos-style', plugin_dir_url(__FILE__) . 'css/logos.css', [], '1.0', 'all');
        
        // Slick Carousel for carousel layout (optional)
        wp_register_style('slick-carousel', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');
        wp_register_script('slick-carousel', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', ['jquery'], '1.8.1', true);
    }

    public function register_settings() {
        // Register Hugging Face API key setting
        register_setting(
            'nehabi_logo_settings_group', 
            'nehabi_huggingface_api_key', 
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => ''
            ]
        );

        // Add settings section
        add_settings_section(
            'nehabi_logo_settings_section', 
            'Logo Generation Settings', 
            [$this, 'logo_settings_section_callback'], 
            'nehabi-logo-generator'
        );

        // Add API key field
        add_settings_field(
            'nehabi_huggingface_api_key', 
            'Hugging Face API Key', 
            [$this, 'huggingface_api_key_callback'], 
            'nehabi-logo-generator', 
            'nehabi_logo_settings_section'
        );
    }

    public function logo_settings_section_callback() {
        echo '<p>Configure your Hugging Face API key for logo generation.</p>';
    }

    public function huggingface_api_key_callback() {
        $api_key = get_option('nehabi_huggingface_api_key', '');
        echo '<input type="text" name="nehabi_huggingface_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">Enter your Hugging Face API key. You can obtain one from <a href="https://huggingface.co/settings/tokens" target="_blank">Hugging Face Account Settings</a>.</p>';
    }

    public function handle_frontend_ai_chat_request() {
        // Verify nonce for security
        check_ajax_referer('nehabi_frontend_chat_nonce', 'security');

        // Sanitize and validate input
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error('Empty message');
        }

        // Get site context
        $site_context = $this->get_site_context();

        // Get Hugging Face API key
        $api_key = get_option('nehabi_huggingface_api_key');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }

        // Get selected model
        $model = get_option('nehabi_chatbot_model', 'qwen');
        $model_map = [
            'qwen' => 'Qwen/QwQ-32B-Preview',
            'gpt2' => 'gpt2-large',
            'bloom' => 'bigscience/bloom'
        ];
        $selected_model = $model_map[$model] ?? $model_map['qwen'];

        // Call Hugging Face API
        $response = $this->call_huggingface_chat_api($message, $site_context, $api_key, $selected_model);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        wp_send_json_success($response);
    }

    private function call_huggingface_chat_api($message, $context, $api_key, $model) {
        $url = "https://api-inference.huggingface.co/models/{$model}";
        
        // Prepare augmented prompt with site context and response guidelines
        $augmented_prompt = "Site Context: {$context}\n\n" .
                            "User Query: {$message}\n\n" .
                            "Response Guidelines:\n" .
                            "- Respond in first person (I, my, we)\n" .
                            "- Use contractions (I'm, we're, it's)\n" .
                            "- Keep responses conversational and friendly\n" .
                            "- Limit response to 2-3 sentences\n" .
                            "- Speak as if you represent the website\n" .
                            "- If greeting, offer help related to the site\n\n" .
                            "Generate Response:";

        $body = [
            'inputs' => $augmented_prompt,
            'parameters' => [
                'max_new_tokens' => 100,
                'temperature' => 0.7,
                'return_full_text' => false
            ]
        ];

        $args = [
            'body' => wp_json_encode($body),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => "Bearer {$api_key}"
            ],
            'timeout' => 30
        ];

        // Log the request details for debugging
        error_log('Nehabi AI Chat Request Body: ' . wp_json_encode($body));

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('Nehabi AI Chat WP Error: ' . $response->get_error_message());
            return "Hi there! I'm having a bit of trouble right now, but I'm here to help when I can.";
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // More robust error handling
        if (empty($data) || !isset($data[0]['generated_text'])) {
            error_log('Nehabi AI Chat Invalid Response: ' . $body);
            return "Hey! I'm ready to assist you with any questions about our site.";
        }

        return trim($data[0]['generated_text']);
    }

    private function get_site_context() {
        // Gather comprehensive contextual information about the website
        $context = [
            'site_info' => [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => get_home_url(),
                'language' => get_bloginfo('language')
            ],
            'current_page' => [
                'title' => wp_title('', false),
                'url' => get_permalink(),
                'type' => get_post_type()
            ],
            'recent_content' => $this->gather_recent_content()
        ];

        return json_encode($context);
    }

    private function gather_recent_content($limit = 3) {
        $recent_posts = wp_get_recent_posts([
            'numberposts' => $limit,
            'post_status' => 'publish'
        ]);

        $content_snippets = [];
        foreach ($recent_posts as $post) {
            $content_snippets[] = [
                'title' => $post['post_title'],
                'excerpt' => wp_trim_excerpt('', $post['ID']),
                'url' => get_permalink($post['ID'])
            ];
        }

        return $content_snippets;
    }

    public function nehabi_frontend_chatbot_shortcode($atts = []) {
        // Shortcode to display chatbot widget
        $atts = shortcode_atts([
            'title' => 'AI Assistant',
            'button_text' => 'Chat with AI',
            'position' => 'bottom-right'
        ], $atts, 'nehabi_chatbot');

        // Start output buffering
        ob_start();
        ?>
        <div id="nehabi-frontend-chatbot" class="nehabi-chatbot-widget <?php echo esc_attr($atts['position']); ?>">
            <div class="chatbot-toggle-btn">
                <?php echo esc_html($atts['button_text']); ?>
            </div>
            <div class="chatbot-container">
                <div class="chatbot-header">
                    <h3><?php echo esc_html($atts['title']); ?></h3>
                    <button class="chatbot-close-btn">&times;</button>
                </div>
                <div class="chatbot-messages"></div>
                <form class="chatbot-input-form">
                    <textarea placeholder="Type your message..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            </div>
        </div>
        <?php
        // Return the buffered content
        return ob_get_clean();
    }

    public function render_frontend_chatbot() {
        // Debug logging
        error_log('Nehabi Chatbot Debug: Render Frontend Chatbot Called');

        // Only render chatbot if conditions are met
        if (!$this->should_display_chatbot()) {
            error_log('Nehabi Chatbot Debug: Chatbot Not Displayed');
            return;
        }

        error_log('Nehabi Chatbot Debug: Rendering Chatbot');

        // Use the existing shortcode method to render
        echo $this->nehabi_frontend_chatbot_shortcode([
            'title' => get_bloginfo('name') . ' AI Assistant',
            'button_text' => 'Chat with AI',
            'position' => 'bottom-right'
        ]);
    }

    public function should_display_chatbot() {
        // Always log current page details for debugging
        error_log('Nehabi Chatbot Debug: Current Page Details');
        error_log('Pagenow: ' . ($GLOBALS['pagenow'] ?? 'Not Set'));
        error_log('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Not Set'));
        
        // Check for explicit login-related conditions
        $login_conditions = [
            // WordPress default login pages
            strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false,
            strpos($_SERVER['REQUEST_URI'], 'wp-signup.php') !== false,
            
            // Common login/signup paths
            strpos($_SERVER['REQUEST_URI'], '/login/') !== false,
            strpos($_SERVER['REQUEST_URI'], '/signin/') !== false,
            strpos($_SERVER['REQUEST_URI'], '/signup/') !== false,
            strpos($_SERVER['REQUEST_URI'], '/register/') !== false,
            strpos($_SERVER['REQUEST_URI'], '/account/') !== false,
            
            // WooCommerce account page
            (function_exists('is_account_page') && is_account_page()),
            
            // BuddyPress login page
            (function_exists('bp_is_login_page') && bp_is_login_page())
        ];

        // Check if any login condition is true
        foreach ($login_conditions as $condition) {
            if ($condition) {
                error_log('Nehabi Chatbot Debug: Login page detected');
                return false;
            }
        }

        // Additional checks for content
        $is_admin = is_admin();
        $is_404 = is_404();
        $content_length = strlen(get_the_content());

        error_log('Nehabi Chatbot Debug: Additional Checks');
        error_log('Is Admin: ' . ($is_admin ? 'Yes' : 'No'));
        error_log('Is 404: ' . ($is_404 ? 'Yes' : 'No'));
        error_log('Content Length: ' . $content_length);

        // Exclude admin pages and 404 pages
        if ($is_admin || $is_404) {
            return false;
        }

        // Optional: Minimum content length check (adjust as needed)
        // If you want to ensure some content exists
        // if ($content_length < 50) {
        //     error_log('Nehabi Chatbot Debug: Content too short');
        //     return false;
        // }

        // Default: show chatbot
        error_log('Nehabi Chatbot Debug: Chatbot Allowed');
        return true;
    }

    // Utility method to convert URL to protocol-relative
    private function make_url_protocol_relative($url) {
        // Remove http: or https: from the beginning of the URL
        return preg_replace('/^https?:/', '', $url);
    }

    public function generate_logo_prompt($company_name, $industry, $style, $color_scheme = '', $primary_color = '', $secondary_color = '') {
        // Sanitize inputs
        $company_name = sanitize_text_field($company_name);
        $industry = sanitize_text_field($industry);
        $style = sanitize_text_field($style);
        $primary_color = sanitize_hex_color($primary_color);
        $secondary_color = sanitize_hex_color($secondary_color);

        // Construct a detailed prompt for logo generation
        $prompt = "Generate a professional logo for a {$industry} company named '{$company_name}'. ";
        
        // Add style details
        switch ($style) {
            case 'modern':
                $prompt .= "Use a modern, sleek design with clean lines. ";
                break;
            case 'minimalist':
                $prompt .= "Create a minimalist design with simple, elegant elements. ";
                break;
            case 'classic':
                $prompt .= "Design a classic, timeless logo with traditional aesthetics. ";
                break;
            case 'playful':
                $prompt .= "Develop a playful and creative logo with vibrant elements. ";
                break;
            case 'elegant':
                $prompt .= "Craft an elegant and sophisticated logo design. ";
                break;
            default:
                $prompt .= "Create a professional and versatile logo design. ";
        }

        // Add color information if colors are provided
        if (!empty($primary_color) || !empty($secondary_color)) {
            $prompt .= "Color scheme: ";
            if (!empty($primary_color)) {
                $prompt .= "Primary color {$primary_color}. ";
            }
            if (!empty($secondary_color)) {
                $prompt .= "Secondary color {$secondary_color}. ";
            }
        }

        // Add additional context based on industry
        switch ($industry) {
            case 'technology':
                $prompt .= "Incorporate tech-inspired geometric shapes and innovative design elements. ";
                break;
            case 'finance':
                $prompt .= "Use professional, trustworthy design with subtle financial symbolism. ";
                break;
            case 'healthcare':
                $prompt .= "Design a compassionate and clean logo that conveys trust and care. ";
                break;
            case 'education':
                $prompt .= "Create an inspiring logo that represents knowledge and growth. ";
                break;
            case 'retail':
                $prompt .= "Develop an attractive and memorable logo that appeals to consumers. ";
                break;
            case 'hospitality':
                $prompt .= "Design a welcoming and warm logo that suggests excellent service. ";
                break;
            default:
                $prompt .= "Create a versatile logo that represents the company's core values. ";
        }

        // Final prompt refinement
        $prompt .= "Ensure the logo is versatile, scalable, and works in both color and monochrome. ";
        $prompt .= "Avoid using text in the logo. Focus on a unique, memorable symbol or icon.";

        return $prompt;
    }
}

// Initialize the plugin
function nehabi_ai_assistant_init() {
    new NehabhAIAssistant();
}
add_action('plugins_loaded', 'nehabi_ai_assistant_init');

// Remove any standalone menu registration functions
if (function_exists('nehabi_logo_gen_menu')) {
    remove_action('admin_menu', 'nehabi_logo_gen_menu');
}

// Remove any standalone logo generator page function
if (function_exists('nehabi_logo_generator_page')) {
    remove_action('admin_menu', 'nehabi_logo_generator_page');
}
