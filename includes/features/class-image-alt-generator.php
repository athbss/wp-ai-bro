<?php
/**
 * Image Alt Text Generator
 *
 * @since      1.1.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes/features
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image Alt Text Generator Class
 */
class AT_Image_Alt_Generator {

    /**
     * AI Manager instance
     *
     * @var AT_AI_Manager
     */
    private $ai_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_manager = AT_AI_Manager::get_instance();

        // Hook into WordPress
        add_action('add_attachment', array($this, 'generate_alt_text_on_upload'), 10, 1);
        add_filter('wp_generate_attachment_metadata', array($this, 'generate_alt_text_for_images'), 10, 2);

        // AJAX handlers
        add_action('wp_ajax_at_ai_generate_alt_text', array($this, 'ajax_generate_alt_text'));
        add_action('wp_ajax_at_ai_regenerate_alt_text', array($this, 'ajax_regenerate_alt_text'));
    }

    /**
     * Generate alt text when attachment is uploaded
     *
     * @param int $attachment_id
     */
    public function generate_alt_text_on_upload($attachment_id) {
        // Check if auto-generation is enabled
        if (!at_ai_assistant_get_option('auto_generate_alt_text', true)) {
            return;
        }

        // Check if it's an image
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        // Check if alt text already exists
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($alt_text)) {
            return;
        }

        // Generate alt text
        $this->generate_alt_text($attachment_id);
    }

    /**
     * Generate alt text for images in metadata generation
     *
     * @param array $metadata
     * @param int $attachment_id
     * @return array
     */
    public function generate_alt_text_for_images($metadata, $attachment_id) {
        // Check if auto-generation is enabled
        if (!at_ai_assistant_get_option('auto_generate_alt_text', true)) {
            return $metadata;
        }

        // Check if it's an image
        if (!wp_attachment_is_image($attachment_id)) {
            return $metadata;
        }

        // Check if alt text already exists
        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($alt_text)) {
            return $metadata;
        }

        // Generate alt text (delay execution to ensure metadata is saved)
        if (!wp_next_scheduled('at_ai_generate_alt_text_delayed', array($attachment_id))) {
            wp_schedule_single_event(time() + 5, 'at_ai_generate_alt_text_delayed', array($attachment_id));
        }

        return $metadata;
    }

    /**
     * Generate alt text for attachment
     *
     * @param int $attachment_id
     * @return bool|WP_Error
     */
    public function generate_alt_text($attachment_id) {
        // Get image URL
        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            return new WP_Error('invalid_attachment', __('Invalid attachment', 'wordpress-ai-assistant'));
        }

        // Analyze image with AI
        $analysis = $this->ai_manager->analyze_image($image_url, array(
            'prompt' => $this->get_alt_text_prompt($attachment_id),
        ));

        if (is_wp_error($analysis)) {
            at_ai_assistant_log('image_analysis', 'error', $analysis->get_error_message(), array(
                'attachment_id' => $attachment_id,
                'image_url' => $image_url,
            ), $attachment_id);
            return $analysis;
        }

        // Extract alt text from description
        $alt_text = $this->extract_alt_text($analysis['description']);

        // Update attachment alt text
        $updated = update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

        // Log success
        at_ai_assistant_log('image_analysis', 'success', __('Alt text generated successfully', 'wordpress-ai-assistant'), array(
            'attachment_id' => $attachment_id,
            'alt_text' => $alt_text,
            'usage' => $analysis['usage'],
        ), $attachment_id);

        return $updated;
    }

    /**
     * Get prompt for alt text generation
     *
     * @param int $attachment_id Optional attachment ID for language context
     * @return string
     */
    private function get_alt_text_prompt($attachment_id = null) {
        $base_prompt = __("Analyze this image and provide a concise, descriptive alt text that would be appropriate for accessibility purposes. Focus on the main subject, key elements, and context. Keep it under 125 characters. Do not include phrases like 'image of' or 'picture of' - just describe what's in the image directly.", 'wordpress-ai-assistant');
        
        // Add language context if attachment ID is provided
        if ($attachment_id) {
            // Try to get post ID from attachment
            $post_id = get_post_meta($attachment_id, '_wp_attachment_parent', true);
            if ($post_id) {
                $post_language = at_ai_assistant_get_post_language($post_id);
                if ($post_language) {
                    $lang_name = at_ai_assistant_get_language_name($post_language);
                    $base_prompt = sprintf(__('IMPORTANT: Generate the alt text in %s (%s).', 'wordpress-ai-assistant'), $lang_name, $post_language) . "\n\n" . $base_prompt;
                }
            }
        }
        
        // Add general instructions if available
        $general_instructions = at_ai_assistant_get_option('general_prompt_instructions', '');
        if (!empty($general_instructions)) {
            $base_prompt = $general_instructions . "\n\n" . $base_prompt;
        }
        
        return $base_prompt;
    }

    /**
     * Extract alt text from AI description
     *
     * @param string $description
     * @return string
     */
    private function extract_alt_text($description) {
        // Clean up the description
        $description = strip_tags($description);
        $description = trim($description);

        // If description is too long, truncate it
        if (strlen($description) > 125) {
            $description = substr($description, 0, 122) . '...';
        }

        // Remove common prefixes that AI might add
        $remove_prefixes = array(
            'This image shows',
            'The image depicts',
            'Image of',
            'Picture of',
            'Photo of',
            'This is',
            'Here is',
        );

        foreach ($remove_prefixes as $prefix) {
            if (stripos($description, $prefix) === 0) {
                $description = trim(substr($description, strlen($prefix)));
                break;
            }
        }

        return $description;
    }

    /**
     * AJAX handler for generating alt text
     */
    public function ajax_generate_alt_text() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Insufficient permissions', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_generate_alt_text')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }

        $attachment_id = intval($_POST['attachment_id'] ?? 0);

        if (!$attachment_id) {
            wp_send_json_error(__('Invalid attachment ID', 'wordpress-ai-assistant'));
        }

        $result = $this->generate_alt_text($attachment_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

        wp_send_json_success(array(
            'alt_text' => $alt_text,
            'message' => __('Alt text generated successfully', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX handler for regenerating alt text
     */
    public function ajax_regenerate_alt_text() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('Insufficient permissions', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_regenerate_alt_text')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }

        $attachment_id = intval($_POST['attachment_id'] ?? 0);

        if (!$attachment_id) {
            wp_send_json_error(__('Invalid attachment ID', 'wordpress-ai-assistant'));
        }

        $result = $this->generate_alt_text($attachment_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        $alt_text = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);

        wp_send_json_success(array(
            'alt_text' => $alt_text,
            'message' => __('Alt text regenerated successfully', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * Get supported image types
     *
     * @return array
     */
    public function get_supported_mime_types() {
        return array(
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        );
    }

    /**
     * Check if attachment is supported image type
     *
     * @param int $attachment_id
     * @return bool
     */
    public function is_supported_image($attachment_id) {
        $mime_type = get_post_mime_type($attachment_id);
        return in_array($mime_type, $this->get_supported_mime_types());
    }
}
