<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/public
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The public-facing functionality of the plugin.
 */
class AT_WordPress_AI_Assistant_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * AI Manager instance
     *
     * @var AT_AI_Manager
     */
    private $ai_manager;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->ai_manager = AT_AI_Manager::get_instance();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            WORDPRESS_AI_ASSISTANT_URL . 'public/css/public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            WORDPRESS_AI_ASSISTANT_URL . 'public/js/public.js',
            array('jquery'),
            $this->version,
            false
        );

        wp_localize_script($this->plugin_name, 'at_ai_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('at_ai_public_nonce'),
        ));
    }

    /**
     * Process post save with AI features
     *
     * @since    1.0.0
     * @param    int     $post_id    The post ID.
     * @param    WP_Post $post       The post object.
     */
    public function process_post_save($post_id, $post) {
        // Prevent infinite loops
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if post type is supported
        if (!at_ai_assistant_is_post_type_supported($post->post_type)) {
            return;
        }

        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // Process with AI features if enabled
        $auto_tagging = at_ai_assistant_get_option('auto_tagging_enabled', false);
        if ($auto_tagging) {
            $this->process_auto_tagging($post_id, $post);
        }
    }

    /**
     * Process media upload with AI features
     *
     * @since    1.0.0
     * @param    int     $attachment_id    The attachment ID.
     */
    public function process_media_upload($attachment_id) {
        // Check if auto-generate alt text is enabled
        $auto_alt = at_ai_assistant_get_option('auto_generate_alt_text', true);
        
        if ($auto_alt) {
            $this->process_image_alt($attachment_id);
        }
    }

    /**
     * Process auto tagging for post
     *
     * @since    1.0.0
     * @param    int     $post_id    The post ID.
     * @param    WP_Post $post       The post object.
     */
    private function process_auto_tagging($post_id, $post) {
        if (empty($post->post_content)) {
            return;
        }

        try {
            $auto_tagger = new AT_Auto_Tagger();
            $tags = $auto_tagger->generate_tags($post->post_content, $post->post_title);
            
            if (!empty($tags) && !is_wp_error($tags)) {
                wp_set_post_tags($post_id, $tags, false);
                at_ai_assistant_log('auto_tag', 'success', sprintf(__('Auto-tagged with: %s', 'wordpress-ai-assistant'), implode(', ', $tags)), array('tags' => $tags), $post_id);
            }
        } catch (Exception $e) {
            at_ai_assistant_log('auto_tag', 'error', $e->getMessage(), array('error' => $e->getMessage()), $post_id);
        }
    }

    /**
     * Process image alt text generation
     *
     * @since    1.0.0
     * @param    int     $attachment_id    The attachment ID.
     */
    private function process_image_alt($attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        
        // Only process images
        if (strpos($mime_type, 'image/') !== 0) {
            return;
        }

        // Check if alt text already exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return;
        }

        try {
            $image_url = wp_get_attachment_image_url($attachment_id, 'full');
            if (!$image_url) {
                return;
            }

            $alt_generator = new AT_Image_Alt_Generator();
            $alt_text = $alt_generator->generate_alt_text($image_url);
            
            if (!empty($alt_text) && !is_wp_error($alt_text)) {
                update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
                at_ai_assistant_log('image_alt', 'success', sprintf(__('Generated alt text: %s', 'wordpress-ai-assistant'), $alt_text), array('alt_text' => $alt_text), null, null);
            }
        } catch (Exception $e) {
            at_ai_assistant_log('image_alt', 'error', $e->getMessage(), array('error' => $e->getMessage()), null, null);
        }
    }

    /**
     * Add TTS player to footer
     *
     * @since    1.0.0
     */
    public function add_tts_player() {
        // Only add on single posts/pages if enabled
        if (!is_singular()) {
            return;
        }

        $tts_enabled = at_ai_assistant_get_option('tts_enabled', false);
        if (!$tts_enabled) {
            return;
        }

        // TTS player HTML would go here
        // For now, just a placeholder
    }

    /**
     * Get TTS audio via AJAX
     *
     * @since    1.0.0
     */
    public function get_tts_audio() {
        check_ajax_referer('at_ai_public_nonce', 'nonce');

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'wordpress-ai-assistant')));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found', 'wordpress-ai-assistant')));
        }

        // TTS generation would go here
        // For now, just return success
        wp_send_json_success(array('message' => __('TTS feature coming soon', 'wordpress-ai-assistant')));
    }

    /**
     * Register REST API routes
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route('wordpress-ai-assistant/v1', '/process', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_process_content'),
            'permission_callback' => array($this, 'rest_permission_check'),
        ));
    }

    /**
     * REST API permission check
     *
     * @since    1.0.0
     * @return   bool    Whether the user has permission.
     */
    public function rest_permission_check() {
        return current_user_can('edit_posts');
    }

    /**
     * REST API process content endpoint
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request    The REST request.
     * @return   WP_REST_Response|WP_Error  The REST response.
     */
    public function rest_process_content($request) {
        $action = $request->get_param('action');
        $content = $request->get_param('content');
        
        if (empty($action) || empty($content)) {
            return new WP_Error('missing_params', __('Missing required parameters', 'wordpress-ai-assistant'), array('status' => 400));
        }

        // Process based on action
        switch ($action) {
            case 'translate':
                $translator = new AT_Text_Translator();
                $result = $translator->translate($content, $request->get_param('target_lang'));
                break;
            
            default:
                return new WP_Error('invalid_action', __('Invalid action', 'wordpress-ai-assistant'), array('status' => 400));
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return rest_ensure_response(array(
            'success' => true,
            'data' => $result,
        ));
    }
}

