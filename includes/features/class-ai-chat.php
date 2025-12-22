<?php
/**
 * AI Chat Feature
 *
 * @package WordPress_AI_Assistant
 * @subpackage Features
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Chat Class
 * 
 * Provides chat interface functionality using AI providers
 */
class AT_AI_Chat {
    
    /**
     * AI Manager instance
     *
     * @var AT_AI_Manager
     */
    private $ai_manager;
    
    /**
     * Chat session ID
     *
     * @var string
     */
    private $session_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_manager = AT_AI_Manager::get_instance();
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_at_ai_chat_send_message', array($this, 'ajax_send_message'));
        add_action('wp_ajax_nopriv_at_ai_chat_send_message', array($this, 'ajax_send_message_nopriv'));
        add_action('wp_ajax_at_ai_chat_get_history', array($this, 'ajax_get_history'));
        add_action('wp_ajax_at_ai_chat_clear_history', array($this, 'ajax_clear_history'));
        add_action('wp_ajax_at_ai_chat_export_history', array($this, 'ajax_export_history'));
        
        // Shortcode for embedding chat
        add_shortcode('ai_chat', array($this, 'render_chat_shortcode'));
        
        // Add chat widget to admin bar
        add_action('admin_bar_menu', array($this, 'add_admin_bar_item'), 100);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Auto-add floating chat widget if enabled
        if (at_ai_assistant_get_option('chat_auto_show_floating', false)) {
            add_action('wp_footer', array($this, 'render_floating_chat'));
            add_action('admin_footer', array($this, 'render_floating_chat'));
        }
    }
    
    /**
     * Get or create session ID
     */
    private function get_session_id() {
        if (!$this->session_id) {
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $this->session_id = 'user_' . $user_id;
            } else {
                // For non-logged users, use session
                if (!session_id()) {
                    session_start();
                }
                if (!isset($_SESSION['at_ai_chat_session'])) {
                    $_SESSION['at_ai_chat_session'] = wp_generate_password(32, false);
                }
                $this->session_id = 'anon_' . $_SESSION['at_ai_chat_session'];
            }
        }
        return $this->session_id;
    }
    
    /**
     * AJAX handler for sending messages
     */
    public function ajax_send_message() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_chat_nonce')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }
        
        // Check permissions - Allow administrators by default
        if (!current_user_can('manage_options') && !current_user_can('use_ai_chat')) {
            // Check if chat is enabled for all users
            if (!at_ai_assistant_get_option('chat_enabled_for_all', false)) {
                wp_send_json_error(__('You do not have permission to use the chat', 'wordpress-ai-assistant'));
            }
        }
        
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $context = sanitize_text_field($_POST['context'] ?? 'general');
        $language = sanitize_text_field($_POST['language'] ?? 'he');
        
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty', 'wordpress-ai-assistant'));
        }
        
        // Get chat history
        $history = $this->get_chat_history();
        
        // Build conversation context
        $conversation = $this->build_conversation_context($history, $message, $context);
        
        // Get response from AI
        $response = $this->get_ai_response($conversation, $language);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        // Save to history
        $this->save_to_history($message, $response['text'], $context);
        
        wp_send_json_success(array(
            'response' => $response['text'],
            'tokens_used' => $response['usage']['total_tokens'] ?? 0,
            'model' => $response['model'] ?? '',
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * AJAX handler for non-logged users
     */
    public function ajax_send_message_nopriv() {
        // Check if chat is enabled for non-logged users
        if (!at_ai_assistant_get_option('chat_enabled_for_visitors', false)) {
            wp_send_json_error(__('Chat is not available for visitors', 'wordpress-ai-assistant'));
        }
        
        $this->ajax_send_message();
    }
    
    /**
     * Get AI response
     */
    private function get_ai_response($conversation, $language = 'he') {
        // Build system prompt
        $system_prompt = $this->build_system_prompt($language);
        
        // Get general instructions if set
        $general_instructions = at_ai_assistant_get_option('general_prompt_instructions', '');
        if (!empty($general_instructions)) {
            $system_prompt .= "\n\n" . $general_instructions;
        }
        
        // Prepare messages for AI
        $messages = array(
            array('role' => 'system', 'content' => $system_prompt)
        );
        
        // Add conversation history
        foreach ($conversation as $msg) {
            $messages[] = array(
                'role' => $msg['role'],
                'content' => $msg['content']
            );
        }
        
        // Get response from AI
        $options = array(
            'max_tokens' => at_ai_assistant_get_option('chat_max_tokens', 1000),
            'temperature' => at_ai_assistant_get_option('chat_temperature', 0.7),
            'messages' => $messages
        );
        
        return $this->ai_manager->chat_completion($messages, $options);
    }
    
    /**
     * Build system prompt based on language
     */
    private function build_system_prompt($language = 'he') {
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        
        if ($language === 'he') {
            $prompt = "אתה עוזר AI חכם ומועיל עבור האתר '{$site_name}'.";
            if (!empty($site_description)) {
                $prompt .= " תיאור האתר: {$site_description}.";
            }
            $prompt .= " ענה תמיד בעברית בצורה ברורה, מקצועית וידידותית.";
            $prompt .= " אם נשאלת על תכונות של האתר או תוכן ספציפי, השתמש במידע הזמין בהקשר.";
            $prompt .= " אם אינך יודע משהו, אמור זאת בכנות.";
        } else {
            $prompt = "You are a helpful AI assistant for the website '{$site_name}'.";
            if (!empty($site_description)) {
                $prompt .= " Site description: {$site_description}.";
            }
            $prompt .= " Always respond clearly, professionally, and in a friendly manner.";
            $prompt .= " If asked about site features or specific content, use available context information.";
            $prompt .= " If you don't know something, say so honestly.";
        }
        
        return $prompt;
    }
    
    /**
     * Build conversation context
     */
    private function build_conversation_context($history, $new_message, $context = 'general') {
        $conversation = array();
        
        // Add relevant history (last 10 messages)
        $recent_history = array_slice($history, -10);
        foreach ($recent_history as $msg) {
            $conversation[] = array(
                'role' => $msg['role'],
                'content' => $msg['content']
            );
        }
        
        // Add context if needed
        if ($context !== 'general') {
            $context_info = $this->get_context_info($context);
            if (!empty($context_info)) {
                $conversation[] = array(
                    'role' => 'system',
                    'content' => $context_info
                );
            }
        }
        
        // Add new message
        $conversation[] = array(
            'role' => 'user',
            'content' => $new_message
        );
        
        return $conversation;
    }
    
    /**
     * Get context information
     */
    private function get_context_info($context) {
        switch ($context) {
            case 'post':
                if (is_single()) {
                    global $post;
                    return sprintf(
                        __('Current page context: Post titled "%s"', 'wordpress-ai-assistant'),
                        get_the_title($post)
                    );
                }
                break;
                
            case 'page':
                if (is_page()) {
                    global $post;
                    return sprintf(
                        __('Current page context: Page titled "%s"', 'wordpress-ai-assistant'),
                        get_the_title($post)
                    );
                }
                break;
                
            case 'product':
                if (function_exists('is_product') && is_product()) {
                    global $product;
                    if ($product) {
                        return sprintf(
                            __('Current context: Product "%s" - Price: %s', 'wordpress-ai-assistant'),
                            $product->get_name(),
                            $product->get_price_html()
                        );
                    }
                }
                break;
        }
        
        return '';
    }
    
    /**
     * Get chat history
     */
    private function get_chat_history() {
        $session_id = $this->get_session_id();
        $history = get_transient('at_ai_chat_history_' . $session_id);
        
        return is_array($history) ? $history : array();
    }
    
    /**
     * Save to chat history
     */
    private function save_to_history($user_message, $ai_response, $context = 'general') {
        $session_id = $this->get_session_id();
        $history = $this->get_chat_history();
        
        // Add user message
        $history[] = array(
            'role' => 'user',
            'content' => $user_message,
            'timestamp' => current_time('mysql'),
            'context' => $context
        );
        
        // Add AI response
        $history[] = array(
            'role' => 'assistant',
            'content' => $ai_response,
            'timestamp' => current_time('mysql'),
            'context' => $context
        );
        
        // Keep only last 50 messages
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }
        
        // Save for 24 hours
        set_transient('at_ai_chat_history_' . $session_id, $history, DAY_IN_SECONDS);
        
        // Save to database for logged-in users
        if (is_user_logged_in()) {
            $this->save_to_database($user_message, $ai_response, $context);
        }
    }
    
    /**
     * Save chat to database
     */
    private function save_to_database($user_message, $ai_response, $context) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ai_assistant_chat_logs';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id(),
                'session_id' => $this->get_session_id(),
                'user_message' => $user_message,
                'ai_response' => $ai_response,
                'context' => $context,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * AJAX handler for getting chat history
     */
    public function ajax_get_history() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_chat_nonce')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }
        
        $history = $this->get_chat_history();
        
        wp_send_json_success(array(
            'history' => $history,
            'count' => count($history)
        ));
    }
    
    /**
     * AJAX handler for clearing history
     */
    public function ajax_clear_history() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_chat_nonce')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }
        
        $session_id = $this->get_session_id();
        delete_transient('at_ai_chat_history_' . $session_id);
        
        wp_send_json_success(__('Chat history cleared', 'wordpress-ai-assistant'));
    }
    
    /**
     * AJAX handler for exporting history
     */
    public function ajax_export_history() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_chat_nonce')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }
        
        $history = $this->get_chat_history();
        $format = sanitize_text_field($_POST['format'] ?? 'json');
        
        if ($format === 'txt') {
            $export = $this->export_as_text($history);
        } else {
            $export = json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        wp_send_json_success(array(
            'data' => $export,
            'format' => $format,
            'filename' => 'chat_history_' . date('Y-m-d_H-i-s') . '.' . $format
        ));
    }
    
    /**
     * Export history as text
     */
    private function export_as_text($history) {
        $text = __('Chat History Export', 'wordpress-ai-assistant') . "\n";
        $text .= __('Date: ', 'wordpress-ai-assistant') . current_time('Y-m-d H:i:s') . "\n";
        $text .= str_repeat('=', 50) . "\n\n";
        
        foreach ($history as $msg) {
            $role = $msg['role'] === 'user' ? __('You', 'wordpress-ai-assistant') : __('AI', 'wordpress-ai-assistant');
            $text .= "[{$msg['timestamp']}] {$role}:\n";
            $text .= $msg['content'] . "\n\n";
        }
        
        return $text;
    }
    
    /**
     * Render chat shortcode
     */
    public function render_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => __('AI Assistant', 'wordpress-ai-assistant'),
            'placeholder' => __('Type your message...', 'wordpress-ai-assistant'),
            'position' => 'inline', // inline, fixed-bottom-right, fixed-bottom-left
            'theme' => 'light', // light, dark, auto
            'height' => '400px',
            'width' => '100%'
        ), $atts, 'ai_chat');
        
        // Check if chat is enabled
        if (!at_ai_assistant_get_option('chat_enabled', false)) {
            return '';
        }
        
        // Check permissions - Allow administrators by default
        if (!current_user_can('manage_options') && !current_user_can('use_ai_chat')) {
            if (!at_ai_assistant_get_option('chat_enabled_for_all', false)) {
                if (!at_ai_assistant_get_option('chat_enabled_for_visitors', false) && !is_user_logged_in()) {
                    return '';
                }
            }
        }
        
        ob_start();
        include WORDPRESS_AI_ASSISTANT_PATH . 'templates/chat-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Add admin bar item
     */
    public function add_admin_bar_item($wp_admin_bar) {
        if (!at_ai_assistant_get_option('chat_show_in_admin_bar', true)) {
            return;
        }
        
        // Check permissions - Allow administrators by default
        if (!current_user_can('manage_options') && !current_user_can('use_ai_chat')) {
            if (!at_ai_assistant_get_option('chat_enabled_for_all', false)) {
                return;
            }
        }
        
        $wp_admin_bar->add_node(array(
            'id' => 'at-ai-chat',
            'title' => '<span class="ab-icon dashicons dashicons-format-chat"></span>' . __('AI Chat', 'wordpress-ai-assistant'),
            'href' => '#',
            'meta' => array(
                'class' => 'at-ai-chat-toggle'
            )
        ));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (!at_ai_assistant_get_option('chat_enabled', false)) {
            return;
        }
        
        // Check if should load on current page
        $load_on_pages = at_ai_assistant_get_option('chat_load_on_pages', 'all');
        if ($load_on_pages !== 'all') {
            $pages = at_ai_assistant_get_option('chat_specific_pages', array());
            if (!in_array(get_the_ID(), $pages)) {
                return;
            }
        }
        
        wp_enqueue_style(
            'at-ai-chat',
            WORDPRESS_AI_ASSISTANT_URL . 'public/css/chat.css',
            array(),
            WORDPRESS_AI_ASSISTANT_VERSION
        );
        
        wp_enqueue_script(
            'at-ai-chat',
            WORDPRESS_AI_ASSISTANT_URL . 'public/js/chat.js',
            array('jquery'),
            WORDPRESS_AI_ASSISTANT_VERSION,
            true
        );
        
        wp_localize_script('at-ai-chat', 'at_ai_chat', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('at_ai_chat_nonce'),
            'strings' => array(
                'sending' => __('Sending...', 'wordpress-ai-assistant'),
                'typing' => __('AI is typing...', 'wordpress-ai-assistant'),
                'error' => __('An error occurred', 'wordpress-ai-assistant'),
                'clear_confirm' => __('Are you sure you want to clear the chat history?', 'wordpress-ai-assistant'),
                'welcome_message' => at_ai_assistant_get_option('chat_welcome_message', __('Hi! How can I help you today?', 'wordpress-ai-assistant')),
                'connection_error' => __('Connection error. Please try again.', 'wordpress-ai-assistant'),
                'copy_success' => __('Copied to clipboard!', 'wordpress-ai-assistant'),
                'export_success' => __('Chat exported successfully!', 'wordpress-ai-assistant')
            ),
            'settings' => array(
                'auto_open' => at_ai_assistant_get_option('chat_auto_open', false),
                'sound_enabled' => at_ai_assistant_get_option('chat_sound_enabled', true),
                'show_timestamp' => at_ai_assistant_get_option('chat_show_timestamp', true),
                'enable_markdown' => at_ai_assistant_get_option('chat_enable_markdown', true),
                'language' => at_ai_assistant_get_post_language()
            )
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        if (!at_ai_assistant_get_option('chat_enabled', false)) {
            return;
        }
        
        if (!at_ai_assistant_get_option('chat_enabled_in_admin', true)) {
            return;
        }
        
        $this->enqueue_frontend_scripts();
    }
    
    /**
     * Render floating chat widget
     */
    public function render_floating_chat() {
        // Check if chat is enabled
        if (!at_ai_assistant_get_option('chat_enabled', false)) {
            return;
        }
        
        // Check context
        if (is_admin() && !at_ai_assistant_get_option('chat_enabled_in_admin', true)) {
            return;
        }
        
        // Render the chat widget
        echo do_shortcode('[ai_chat position="fixed-bottom-right" theme="light"]');
    }
}

// Initialize the class
new AT_AI_Chat();