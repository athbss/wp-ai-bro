<?php
/**
 * Media AI Generator - AI buttons in media library
 *
 * @since      1.3.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes/features
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media AI Generator Class
 * 
 * Adds AI generation buttons to media library fields
 */
class AT_Media_AI_Generator {

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

        // Add AI buttons to attachment fields
        add_filter('attachment_fields_to_edit', array($this, 'add_ai_buttons_to_attachment_fields'), 10, 2);
        
        // Enqueue scripts for media modal
        add_action('wp_enqueue_media', array($this, 'enqueue_media_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // AJAX handlers
        add_action('wp_ajax_at_ai_generate_image_description', array($this, 'ajax_generate_image_description'));
        add_action('wp_ajax_at_ai_generate_alt_text_media', array($this, 'ajax_generate_alt_text'));
        add_action('wp_ajax_at_ai_generate_caption', array($this, 'ajax_generate_caption'));
        add_action('wp_ajax_at_ai_generate_title', array($this, 'ajax_generate_title'));
    }

    /**
     * Add AI generation buttons to attachment edit fields
     *
     * @param array $form_fields
     * @param WP_Post $post
     * @return array
     */
    public function add_ai_buttons_to_attachment_fields($form_fields, $post) {
        // Only for images
        if (!wp_attachment_is_image($post->ID)) {
            return $form_fields;
        }

        // Add AI button for Title
        if (isset($form_fields['post_title'])) {
            $form_fields['post_title']['helps'] = $this->render_ai_button('title', $post->ID);
        }

        // Add AI button for Caption
        if (isset($form_fields['post_excerpt'])) {
            $form_fields['post_excerpt']['helps'] = $this->render_ai_button('caption', $post->ID);
        }

        // Add AI button for Alt Text
        if (isset($form_fields['image_alt'])) {
            $form_fields['image_alt']['helps'] = $this->render_ai_button('alt', $post->ID);
        }

        // Add AI button for Description
        if (isset($form_fields['post_content'])) {
            $form_fields['post_content']['helps'] = $this->render_ai_button('description', $post->ID);
        }

        return $form_fields;
    }

    /**
     * Render AI generation button
     *
     * @param string $field_type
     * @param int $attachment_id
     * @return string
     */
    private function render_ai_button($field_type, $attachment_id) {
        $labels = array(
            'title' => __('צור כותרת AI', 'wordpress-ai-assistant'),
            'caption' => __('צור כיתוב AI', 'wordpress-ai-assistant'),
            'alt' => __('צור טקסט חלופי AI', 'wordpress-ai-assistant'),
            'description' => __('צור תיאור AI', 'wordpress-ai-assistant'),
        );

        $label = isset($labels[$field_type]) ? $labels[$field_type] : __('צור AI', 'wordpress-ai-assistant');
        
        ob_start();
        ?>
        <button type="button" 
                class="button button-secondary at-ai-generate-btn" 
                data-field-type="<?php echo esc_attr($field_type); ?>"
                data-attachment-id="<?php echo esc_attr($attachment_id); ?>">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php echo esc_html($label); ?>
        </button>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue scripts for media modal
     */
    public function enqueue_media_scripts() {
        wp_enqueue_script(
            'at-ai-media-generator',
            WORDPRESS_AI_ASSISTANT_URL . 'admin/js/media-generator.js',
            array('jquery', 'media-views'),
            WORDPRESS_AI_ASSISTANT_VERSION,
            true
        );

        $this->localize_script();
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'upload.php' || $hook === 'post.php' || $hook === 'post-new.php' || $hook === 'media-upload.php') {
            wp_enqueue_script(
                'at-ai-media-generator',
                WORDPRESS_AI_ASSISTANT_URL . 'admin/js/media-generator.js',
                array('jquery'),
                WORDPRESS_AI_ASSISTANT_VERSION,
                true
            );

            $this->localize_script();
        }
    }

    /**
     * Localize script
     */
    private function localize_script() {
        wp_localize_script('at-ai-media-generator', 'atAiMedia', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('at_ai_media_generator'),
            'strings' => array(
                'generating' => __('מייצר...', 'wordpress-ai-assistant'),
                'error' => __('שגיאה', 'wordpress-ai-assistant'),
                'success' => __('נוצר בהצלחה!', 'wordpress-ai-assistant'),
                'network_error' => __('שגיאת רשת', 'wordpress-ai-assistant'),
            ),
        ));
    }

    /**
     * AJAX: Generate image description
     */
    public function ajax_generate_image_description() {
        $this->verify_ajax_request();

        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        if (!$attachment_id) {
            wp_send_json_error(__('מזהה תמונה לא תקין', 'wordpress-ai-assistant'));
        }

        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            wp_send_json_error(__('לא נמצאה תמונה', 'wordpress-ai-assistant'));
        }

        $prompt = __('תאר תמונה זו בפירוט. כלול פרטים על הנושא העיקרי, הרקע, הצבעים, האווירה והאלמנטים החשובים. התיאור צריך להיות מקיף ומועיל למי שלא יכול לראות את התמונה.', 'wordpress-ai-assistant');

        // Log the request
        $start_time = microtime(true);
        
        $result = $this->ai_manager->analyze_image($image_url, array(
            'prompt' => $prompt,
        ));

        if (is_wp_error($result)) {
            $this->log_ai_action('image_description', $attachment_id, $prompt, null, $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        // Update post content with description
        $updated = wp_update_post(array(
            'ID' => $attachment_id,
            'post_content' => $result['description'],
        ));

        // Log successful generation
        $this->log_ai_action('image_description', $attachment_id, $prompt, $result, null, microtime(true) - $start_time);

        wp_send_json_success(array(
            'description' => $result['description'],
            'usage' => $result['usage'],
            'message' => __('תיאור התמונה נוצר בהצלחה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX: Generate alt text
     */
    public function ajax_generate_alt_text() {
        $this->verify_ajax_request();

        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        if (!$attachment_id) {
            wp_send_json_error(__('מזהה תמונה לא תקין', 'wordpress-ai-assistant'));
        }

        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            wp_send_json_error(__('לא נמצאה תמונה', 'wordpress-ai-assistant'));
        }

        $prompt = __('צור טקסט חלופי (Alt Text) תמציתי לתמונה זו. התמקד בנושא העיקרי והקונטקסט. השתמש במקסימום 125 תווים. אל תכלול ביטויים כמו "תמונה של" או "תצלום של" - רק תאר ישירות מה בתמונה.', 'wordpress-ai-assistant');

        $start_time = microtime(true);
        
        $result = $this->ai_manager->analyze_image($image_url, array(
            'prompt' => $prompt,
            'max_tokens' => 100,
        ));

        if (is_wp_error($result)) {
            $this->log_ai_action('alt_text', $attachment_id, $prompt, null, $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        // Clean and truncate alt text
        $alt_text = $this->clean_alt_text($result['description']);

        // Update alt text
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

        // Log successful generation
        $this->log_ai_action('alt_text', $attachment_id, $prompt, $result, null, microtime(true) - $start_time);

        wp_send_json_success(array(
            'alt_text' => $alt_text,
            'usage' => $result['usage'],
            'message' => __('טקסט חלופי נוצר בהצלחה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX: Generate caption
     */
    public function ajax_generate_caption() {
        $this->verify_ajax_request();

        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        if (!$attachment_id) {
            wp_send_json_error(__('מזהה תמונה לא תקין', 'wordpress-ai-assistant'));
        }

        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            wp_send_json_error(__('לא נמצאה תמונה', 'wordpress-ai-assistant'));
        }

        $prompt = __('צור כיתוב קצר ומעניין לתמונה זו. הכיתוב צריך להיות קליט, אינפורמטיבי ולהוסיף הקשר. השתמש ב-1-2 משפטים.', 'wordpress-ai-assistant');

        $start_time = microtime(true);
        
        $result = $this->ai_manager->analyze_image($image_url, array(
            'prompt' => $prompt,
            'max_tokens' => 150,
        ));

        if (is_wp_error($result)) {
            $this->log_ai_action('caption', $attachment_id, $prompt, null, $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        // Update caption
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_excerpt' => $result['description'],
        ));

        // Log successful generation
        $this->log_ai_action('caption', $attachment_id, $prompt, $result, null, microtime(true) - $start_time);

        wp_send_json_success(array(
            'caption' => $result['description'],
            'usage' => $result['usage'],
            'message' => __('כיתוב נוצר בהצלחה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX: Generate title
     */
    public function ajax_generate_title() {
        $this->verify_ajax_request();

        $attachment_id = intval($_POST['attachment_id'] ?? 0);
        if (!$attachment_id) {
            wp_send_json_error(__('מזהה תמונה לא תקין', 'wordpress-ai-assistant'));
        }

        $image_url = wp_get_attachment_url($attachment_id);
        if (!$image_url) {
            wp_send_json_error(__('לא נמצאה תמונה', 'wordpress-ai-assistant'));
        }

        $prompt = __('צור כותרת קצרה ותיאורית לתמונה זו. הכותרת צריכה להיות בין 3-7 מילים ולתפוס את המהות של התמונה.', 'wordpress-ai-assistant');

        $start_time = microtime(true);
        
        $result = $this->ai_manager->analyze_image($image_url, array(
            'prompt' => $prompt,
            'max_tokens' => 50,
        ));

        if (is_wp_error($result)) {
            $this->log_ai_action('title', $attachment_id, $prompt, null, $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        // Clean title (remove quotes, newlines, etc.)
        $title = trim(str_replace(array('"', "'", "\n", "\r"), '', $result['description']));

        // Update title
        wp_update_post(array(
            'ID' => $attachment_id,
            'post_title' => $title,
        ));

        // Log successful generation
        $this->log_ai_action('title', $attachment_id, $prompt, $result, null, microtime(true) - $start_time);

        wp_send_json_success(array(
            'title' => $title,
            'usage' => $result['usage'],
            'message' => __('כותרת נוצרה בהצלחה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * Clean alt text
     *
     * @param string $text
     * @return string
     */
    private function clean_alt_text($text) {
        // Remove common prefixes
        $prefixes = array(
            'This image shows',
            'The image depicts',
            'Image of',
            'Picture of',
            'Photo of',
            'תמונה של',
            'תצלום של',
            'מוצג',
        );

        foreach ($prefixes as $prefix) {
            if (stripos($text, $prefix) === 0) {
                $text = trim(substr($text, strlen($prefix)));
                break;
            }
        }

        // Truncate if too long
        if (mb_strlen($text) > 125) {
            $text = mb_substr($text, 0, 122) . '...';
        }

        return trim($text);
    }

    /**
     * Verify AJAX request
     */
    private function verify_ajax_request() {
        // Check permissions
        if (!current_user_can('upload_files')) {
            wp_send_json_error(__('אין הרשאות מספיקות', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_media_generator')) {
            wp_send_json_error(__('בדיקת אבטחה נכשלה', 'wordpress-ai-assistant'));
        }
    }

    /**
     * Log AI action with detailed information
     *
     * @param string $action_type
     * @param int $attachment_id
     * @param string $prompt
     * @param array|null $result
     * @param string|null $error
     * @param float|null $duration
     */
    private function log_ai_action($action_type, $attachment_id, $prompt, $result = null, $error = null, $duration = null) {
        $log_data = array(
            'action' => 'media_' . $action_type,
            'attachment_id' => $attachment_id,
            'prompt' => $prompt,
            'timestamp' => current_time('mysql'),
            'duration' => $duration,
        );

        if ($error) {
            $log_data['status'] = 'error';
            $log_data['error'] = $error;
        } else {
            $log_data['status'] = 'success';
            $log_data['response'] = $result['description'] ?? '';
            $log_data['usage'] = $result['usage'] ?? array();
            $log_data['model'] = $result['model'] ?? '';
        }

        // Log to custom table
        at_ai_assistant_log('media_ai_generation', $log_data['status'], json_encode($log_data, JSON_UNESCAPED_UNICODE), $log_data, $attachment_id);
    }
}

