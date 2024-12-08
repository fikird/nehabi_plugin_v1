<?php
/**
 * Plugin Name: Nehabi AI Assistant
 * Description: An AI-powered chatbot and assistant for WordPress
 * Version: 1.0
 * Author: Nehabi Technologies
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
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Register AJAX actions
        add_action('wp_ajax_nehabi_chat_request', [$this, 'handle_ai_chat_request']);
        add_action('wp_ajax_nopriv_nehabi_chat_request', [$this, 'handle_ai_chat_request']);
        
        // Add AJAX action for logo generation
        add_action('wp_ajax_nehabi_generate_logo', [$this, 'handle_logo_generation_request']);
        add_action('wp_ajax_nopriv_nehabi_generate_logo', [$this, 'handle_logo_generation_request']);

        // Logo-related hooks
        add_action('init', [$this, 'register_logo_post_type']);
        add_action('add_meta_boxes', [$this, 'add_logo_meta_boxes']);
        add_action('save_post', [$this, 'save_logo_details_meta_box']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_logo_assets']);
        
        // Shortcode
        add_shortcode('nehabi_logos', [$this, 'nehabi_logos_shortcode']);
    }

    public function init() {
        // Load plugin text domain for internationalization
        load_plugin_textdomain('nehabi-ai-assistant', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_frontend_assets() {
        // Only enqueue logo generation for admin users
        if (is_user_logged_in() && current_user_can('manage_options')) {
            // Enqueue styles
            wp_enqueue_style(
                'nehabi-logo-generator-style', 
                plugin_dir_url(__FILE__) . 'css/logo-generator.css', 
                [], 
                '1.0', 
                'all'
            );

            // Enqueue logo generation script
            wp_enqueue_script(
                'nehabi-logo-generator-script', 
                plugin_dir_url(__FILE__) . 'assets/js/logo-generator.js', 
                ['jquery'], 
                '1.0', 
                true
            );

            // Localize script with logo generation parameters
            wp_localize_script('nehabi-logo-generator-script', 'nehabi_logo_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'logo_nonce' => wp_create_nonce('nehabi_logo_generation_nonce')
            ]);
        }

        // Enqueue styles
        wp_enqueue_style(
            'nehabi-chatbot-style', 
            plugin_dir_url(__FILE__) . 'css/chatbot.css', 
            [], 
            '1.0', 
            'all'
        );

        // Enqueue scripts
        wp_enqueue_script(
            'nehabi-chatbot-script', 
            plugin_dir_url(__FILE__) . 'assets/js/chatbot.js', 
            ['jquery'], 
            '1.0', 
            true
        );

        // Localize script with ajax url and nonces
        wp_localize_script('nehabi-chatbot-script', 'nehabi_chat_params', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'chat_nonce' => wp_create_nonce('nehabi_chat_nonce'),
            'logo_nonce' => wp_create_nonce('nehabi_logo_generation_nonce')
        ]);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Nehabi AI Assistant', 
            'AI Assistant', 
            'manage_options', 
            'nehabi-ai-assistant', 
            [$this, 'render_admin_page'], 
            'dashicons-format-chat', 
            99
        );
    }

    public function register_settings() {
        // Register a new setting for the Hugging Face API key
        register_setting(
            'nehabi_ai_settings_group', // Option group
            'nehabi_huggingface_api_key', // Option name
            [
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            ]
        );

        // Add settings section
        add_settings_section(
            'nehabi_ai_settings_section', // ID
            'AI Assistant Settings', // Title
            [$this, 'settings_section_callback'], // Callback
            'nehabi-ai-assistant' // Page slug
        );

        // Add settings field for API key
        add_settings_field(
            'nehabi_huggingface_api_key', // ID
            'Hugging Face API Key', // Title 
            [$this, 'huggingface_api_key_callback'], // Callback
            'nehabi-ai-assistant', // Page slug
            'nehabi_ai_settings_section' // Section ID
        );

        // Add new setting for logo generation
        register_setting(
            'nehabi_ai_settings_group', 
            'nehabi_logo_generation_enabled', 
            [
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true,
            ]
        );

        // Add settings section for logo generation
        add_settings_section(
            'nehabi_logo_settings_section', 
            'Logo Generation Settings', 
            [$this, 'logo_settings_section_callback'], 
            'nehabi-ai-assistant'
        );

        // Add settings field for logo generation toggle
        add_settings_field(
            'nehabi_logo_generation_enabled', 
            'Enable Logo Generation', 
            [$this, 'logo_generation_toggle_callback'], 
            'nehabi-ai-assistant', 
            'nehabi_logo_settings_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>Enter your Hugging Face API key to enable the AI Assistant.</p>';
    }

    public function huggingface_api_key_callback() {
        $api_key = get_option('nehabi_huggingface_api_key', '');
        ?>
        <input 
            type="password" 
            name="nehabi_huggingface_api_key" 
            id="nehabi_huggingface_api_key" 
            value="<?php echo esc_attr($api_key); ?>" 
            class="regular-text"
            placeholder="Enter your Hugging Face API key"
        />
        <p class="description">
            You can obtain an API key from 
            <a href="https://huggingface.co/settings/tokens" target="_blank">Hugging Face</a>
        </p>
        <?php
    }

    // Callback for logo generation settings section
    public function logo_settings_section_callback() {
        echo '<p>Control the logo generation feature for site development.</p>';
    }

    // Callback for logo generation toggle
    public function logo_generation_toggle_callback() {
        $logo_gen_enabled = get_option('nehabi_logo_generation_enabled', true);
        ?>
        <label>
            <input 
                type="checkbox" 
                name="nehabi_logo_generation_enabled" 
                value="1" 
                <?php checked(1, $logo_gen_enabled, true); ?> 
            />
            Enable logo generation tool for site development
        </label>
        <p class="description">
            When enabled, the logo generation tool will be available in the admin area.
            Disable this for the live site.
        </p>
        <?php
    }

    public function render_admin_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Admin page HTML
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields('nehabi_ai_settings_group');
                // Output setting sections
                do_settings_sections('nehabi-ai-assistant');
                // Submit button
                submit_button('Save Settings');
                ?>
            </form>
        </div>
        <?php
    }

    public function handle_ai_chat_request() {
        // Verify nonce for security
        check_ajax_referer('nehabi_chat_nonce', 'security');

        // Sanitize and validate input
        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        
        if (empty($message)) {
            wp_send_json_error('Empty message');
        }

        // Get site context
        $site_context = $this->get_site_context();

        // Prepare API request to Hugging Face
        $api_key = get_option('nehabi_huggingface_api_key');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
        }

        $response = $this->call_huggingface_api($message, $site_context, $api_key);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }

        wp_send_json_success($response);
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

    private function call_huggingface_api($message, $context, $api_key) {
        $url = 'https://api-inference.huggingface.co/models/Qwen/QwQ-32B-Preview';
        
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
                'max_new_tokens' => 100,  // Reduced to encourage brevity
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
        error_log('Nehabi AI RAG Request Body: ' . wp_json_encode($body));

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            error_log('Nehabi AI WP Error: ' . $response->get_error_message());
            return "Hi there! I'm having a bit of trouble right now, but I'm here to help when I can.";
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // More robust error handling
        if (empty($data) || !isset($data[0]['generated_text'])) {
            error_log('Nehabi AI Invalid Response: ' . $body);
            return "Hey! I'm ready to assist you with any questions about our site.";
        }

        return trim($data[0]['generated_text']);
    }

    // Add logo generation method
    public function handle_logo_generation_request() {
        // Check if logo generation is enabled
        $logo_gen_enabled = get_option('nehabi_logo_generation_enabled', true);
        if (!$logo_gen_enabled) {
            wp_send_json_error('Logo generation is disabled');
            wp_die();
        }

        // Verify nonce with a specific logo generation nonce
        if (!check_ajax_referer('nehabi_logo_generation_nonce', 'security', false)) {
            error_log('Logo Generation Nonce Verification Failed');
            wp_send_json_error('Security check failed');
            wp_die();
        }

        // Check user permissions for non-logged-in users
        if (!is_user_logged_in() && !apply_filters('nehabi_allow_logo_generation_for_guests', false)) {
            wp_send_json_error('User not authorized');
            wp_die();
        }

        // Sanitize and validate inputs
        $company_name = isset($_POST['company_name']) ? sanitize_text_field($_POST['company_name']) : '';
        $industry = isset($_POST['industry']) ? sanitize_text_field($_POST['industry']) : '';
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';
        $color_scheme = isset($_POST['color_scheme']) ? sanitize_text_field($_POST['color_scheme']) : '';
        $primary_color = isset($_POST['primary_color']) ? sanitize_text_field($_POST['primary_color']) : '';
        $secondary_color = isset($_POST['secondary_color']) ? sanitize_text_field($_POST['secondary_color']) : '';

        // Validate required fields
        if (empty($company_name) || empty($industry) || empty($style)) {
            wp_send_json_error('Missing required logo generation parameters');
            wp_die();
        }

        // Get Hugging Face API key
        $api_key = get_option('nehabi_huggingface_api_key');
        if (empty($api_key)) {
            wp_send_json_error('API key not configured');
            wp_die();
        }

        // Generate logo prompt
        $logo_prompt = $this->generate_logo_prompt(
            $company_name, 
            $industry, 
            $style, 
            $color_scheme, 
            $primary_color, 
            $secondary_color
        );

        // Call Hugging Face API for logo generation
        $response = $this->call_logo_generation_api($logo_prompt, $api_key);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            wp_die();
        }

        wp_send_json_success($response);
    }

    private function generate_logo_prompt($company_name, $industry, $style, $color_scheme, $primary_color, $secondary_color) {
        // Construct a detailed prompt for logo generation
        $prompt = "Generate a professional logo for a {$industry} company named '{$company_name}'. ";
        
        // Add style specifics
        switch ($style) {
            case 'modern':
                $prompt .= "Create a sleek, minimalist design with clean lines and contemporary typography. ";
                break;
            case 'classic':
                $prompt .= "Design an elegant, timeless logo with traditional design elements. ";
                break;
            case 'tech':
                $prompt .= "Develop a futuristic, innovative logo with geometric shapes and sharp edges. ";
                break;
            case 'minimalist':
                $prompt .= "Craft a simple, understated logo with maximum white space and minimal elements. ";
                break;
            case 'playful':
                $prompt .= "Create a fun, energetic logo with playful typography and creative iconography. ";
                break;
        }

        // Add color scheme
        if ($color_scheme === 'custom') {
            $prompt .= "Use primary color {$primary_color} and secondary color {$secondary_color}. ";
        } else {
            switch ($color_scheme) {
                case 'blue_white':
                    $prompt .= "Use a color palette of blue and white. ";
                    break;
                case 'black_gold':
                    $prompt .= "Incorporate black and gold colors for a luxurious feel. ";
                    break;
                case 'green_gray':
                    $prompt .= "Utilize green and gray tones for a professional look. ";
                    break;
                case 'red_black':
                    $prompt .= "Design with a bold red and black color scheme. ";
                    break;
            }
        }

        $prompt .= "Ensure the logo is versatile, scalable, and represents the company's core values.";

        return $prompt;
    }

    private function call_logo_generation_api($prompt, $api_key) {
        $url = 'https://api-inference.huggingface.co/models/Shakker-Labs/FLUX.1-dev-LoRA-Logo-Design';
        
        $body = [
            'inputs' => $prompt
        ];

        // Increase timeout and add more robust error handling
        $args = [
            'method' => 'POST',
            'timeout' => 120,  // Increased to 2 minutes
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking' => true,
            'headers' => [
                'Authorization' => "Bearer {$api_key}",
                'Content-Type' => 'application/json',
                'Accept' => 'image/png'
            ],
            'body' => wp_json_encode($body),
            'sslverify' => false  // Disable SSL verification if needed
        ];

        // Log request details for debugging
        error_log('Logo Generation API Request: ' . wp_json_encode($args));

        // Use wp_remote_post to make the request
        $response = wp_remote_post($url, $args);

        // Check for WP HTTP API errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('Logo Generation API WP Error: ' . $error_message);
            
            // Specific error handling
            if (strpos($error_message, 'timed out') !== false) {
                return new WP_Error('logo_generation_timeout', 'Logo generation request timed out. Please try again.');
            }
            
            return $response;
        }

        // Get response details
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        // Log response details
        error_log('Logo Generation API Response Code: ' . $response_code);
        error_log('Logo Generation API Content Type: ' . $content_type);

        // Check response code and content type
        if ($response_code !== 200) {
            error_log('Logo Generation API Unexpected Response: ' . $body);
            return new WP_Error('logo_generation_error', 'Unexpected API response (Code: ' . $response_code . '): ' . $body);
        }

        // Verify response is an image
        if (strpos($content_type, 'image/') !== 0) {
            error_log('Logo Generation API Non-Image Response: ' . $content_type);
            return new WP_Error('logo_generation_error', 'Received non-image response. Content-Type: ' . $content_type);
        }

        // Generate unique filename
        $upload_dir = wp_upload_dir();
        $filename = 'logo_' . uniqid() . '.png';
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Save file to WordPress uploads
        $file_saved = file_put_contents($file_path, $body);
        if (!$file_saved) {
            error_log('Failed to save logo image');
            return new WP_Error('logo_generation_error', 'Failed to save logo image');
        }

        // Prepare attachment data
        $attachment = [
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/png',
            'post_title'     => sanitize_title('Logo for ' . $_POST['company_name']),
            'post_content'   => $prompt,  // Store generation prompt as content
            'post_status'    => 'inherit'
        ];

        // Insert attachment to media library
        $attach_id = wp_insert_attachment($attachment, $file_path);

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Return attachment URL for frontend
        return [
            'url' => wp_get_attachment_url($attach_id),
            'id' => $attach_id,
            'prompt' => $prompt
        ];
    }

    // Add logo post type
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
}

// Initialize the plugin
function nehabi_ai_assistant_init() {
    new NehabhAIAssistant();
}
add_action('plugins_loaded', 'nehabi_ai_assistant_init');
