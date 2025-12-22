<?php
/**
 * Text Translator
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
 * Text Translator Class
 */
class AT_Text_Translator {

    /**
     * AI Manager instance
     *
     * @var AT_AI_Manager
     */
    private $ai_manager;

    /**
     * Supported languages
     *
     * @var array
     */
    private $supported_languages = array(
        'en' => 'English',
        'he' => 'עברית',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'ar' => 'العربية',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->ai_manager = AT_AI_Manager::get_instance();

        // AJAX handlers
        add_action('wp_ajax_at_ai_translate_text', array($this, 'ajax_translate_text'));
        add_action('wp_ajax_at_ai_detect_language', array($this, 'ajax_detect_language'));

        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Meta box for posts
        add_action('add_meta_boxes', array($this, 'add_translation_meta_box'));
        add_action('save_post', array($this, 'save_translation_meta'));
    }

    /**
     * Translate text
     *
     * @param string $text
     * @param string $target_language
     * @param string $source_language
     * @param array $context
     * @return string|WP_Error
     */
    public function translate_text($text, $target_language, $source_language = 'auto', $context = array()) {
        if (empty(trim($text))) {
            return new WP_Error('empty_text', __('Text cannot be empty', 'wordpress-ai-assistant'));
        }

        // Get post ID from context if available
        $post_id = isset($context['post_id']) ? $context['post_id'] : null;
        
        $result = $this->ai_manager->translate_text($text, $target_language, $source_language, $context, $post_id);

        if (is_wp_error($result)) {
            at_ai_assistant_log('translation', 'error', $result->get_error_message(), array(
                'text_length' => strlen($text),
                'target_language' => $target_language,
                'source_language' => $source_language,
                'context' => $context,
            ));
            return $result;
        }

        at_ai_assistant_log('translation', 'success', __('Text translated successfully', 'wordpress-ai-assistant'), array(
            'text_length' => strlen($text),
            'target_language' => $target_language,
            'source_language' => $source_language,
            'result_length' => strlen($result),
        ));

        return $result;
    }

    /**
     * Detect language of text
     *
     * @param string $text
     * @return string|WP_Error
     */
    public function detect_language($text) {
        if (empty(trim($text))) {
            return new WP_Error('empty_text', __('Text cannot be empty', 'wordpress-ai-assistant'));
        }

        $prompt = sprintf(
            __('Detect the language of the following text and respond with only the language code (e.g., "en", "he", "es"). If you cannot determine the language, respond with "unknown". Text: %s', 'wordpress-ai-assistant'),
            $text
        );

        $result = $this->ai_manager->generate_text($prompt, array('max_tokens' => 10));

        if (is_wp_error($result)) {
            return $result;
        }

        $detected_lang = trim(strtolower($result['text']));

        // Validate detected language
        if (!array_key_exists($detected_lang, $this->supported_languages) && $detected_lang !== 'unknown') {
            $detected_lang = 'unknown';
        }

        return $detected_lang;
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    public function get_supported_languages() {
        return $this->supported_languages;
    }

    /**
     * Get language name by code
     *
     * @param string $code
     * @return string
     */
    public function get_language_name($code) {
        return isset($this->supported_languages[$code]) ? $this->supported_languages[$code] : $code;
    }

    /**
     * AJAX handler for text translation
     */
    public function ajax_translate_text() {
        // Check permissions
        if (!at_ai_assistant_user_can_use_ai()) {
            wp_send_json_error(__('Insufficient permissions', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_translate_text')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }

        $text = sanitize_textarea_field($_POST['text'] ?? '');
        $target_language = sanitize_text_field($_POST['target_language'] ?? '');
        $source_language = sanitize_text_field($_POST['source_language'] ?? 'auto');
        $context = isset($_POST['context']) ? array_map('sanitize_text_field', $_POST['context']) : array();

        if (empty($text)) {
            wp_send_json_error(__('Text cannot be empty', 'wordpress-ai-assistant'));
        }

        if (empty($target_language)) {
            wp_send_json_error(__('Target language is required', 'wordpress-ai-assistant'));
        }

        $result = $this->translate_text($text, $target_language, $source_language, $context);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'translated_text' => $result,
            'message' => __('Text translated successfully', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX handler for language detection
     */
    public function ajax_detect_language() {
        // Check permissions
        if (!at_ai_assistant_user_can_use_ai()) {
            wp_send_json_error(__('Insufficient permissions', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_detect_language')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }

        $text = sanitize_textarea_field($_POST['text'] ?? '');

        if (empty($text)) {
            wp_send_json_error(__('Text cannot be empty', 'wordpress-ai-assistant'));
        }

        $result = $this->detect_language($text);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'detected_language' => $result,
            'language_name' => $this->get_language_name($result),
            'message' => __('Language detected successfully', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * Enqueue scripts for admin
     *
     * @param string $hook
     */
    public function enqueue_scripts($hook) {
        // Only enqueue on post edit screens and our settings page
        if (!in_array($hook, array('post.php', 'post-new.php', 'toplevel_page_wordpress-ai-assistant'))) {
            return;
        }

        wp_enqueue_script(
            'at-ai-translator',
            WORDPRESS_AI_ASSISTANT_URL . 'admin/js/translator.js',
            array('jquery'),
            WORDPRESS_AI_ASSISTANT_VERSION,
            true
        );

        wp_localize_script('at-ai-translator', 'at_ai_translator', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('at_ai_translate_text'),
            'detect_nonce' => wp_create_nonce('at_ai_detect_language'),
            'supported_languages' => $this->supported_languages,
            'strings' => array(
                'translating' => __('Translating...', 'wordpress-ai-assistant'),
                'detecting' => __('Detecting language...', 'wordpress-ai-assistant'),
                'error' => __('Error occurred', 'wordpress-ai-assistant'),
                'no_text' => __('Please enter text to translate', 'wordpress-ai-assistant'),
                'no_target_lang' => __('Please select target language', 'wordpress-ai-assistant'),
            ),
        ));
    }

    /**
     * Add translation meta box to posts
     */
    public function add_translation_meta_box() {
        $enabled_post_types = at_ai_assistant_get_option('enabled_post_types', array('post', 'page'));

        foreach ($enabled_post_types as $post_type) {
            // Verify post type exists before adding meta box
            if (post_type_exists($post_type)) {
                add_meta_box(
                    'at_ai_translation_meta',
                    __('AI Translation', 'wordpress-ai-assistant'),
                    array($this, 'render_translation_meta_box'),
                    $post_type,
                    'side',
                    'default'
                );
            }
        }
    }

    /**
     * Render translation meta box
     *
     * @param WP_Post $post
     */
    public function render_translation_meta_box($post) {
        $translations = get_post_meta($post->ID, '_at_ai_translations', true) ?: array();
        $enabled = get_post_meta($post->ID, '_at_ai_translation_enabled', true) ?: false;

        wp_nonce_field('at_ai_translation_meta', 'at_ai_translation_nonce');
        ?>
        <div class="at-ai-translation-meta">
            <p>
                <label>
                    <input type="checkbox" name="at_ai_translation_enabled" value="1" <?php checked($enabled); ?>>
                    <?php _e('Enable AI translation for this post', 'wordpress-ai-assistant'); ?>
                </label>
            </p>

            <div class="at-ai-translation-list" style="margin-top: 10px;">
                <?php if (!empty($translations)): ?>
                    <strong><?php _e('Existing Translations:', 'wordpress-ai-assistant'); ?></strong>
                    <ul style="margin: 5px 0; padding-left: 20px;">
                        <?php foreach ($translations as $lang => $translation): ?>
                            <li><?php echo esc_html($this->get_language_name($lang)); ?>: <?php echo esc_html(substr($translation, 0, 50)); ?>...</li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div style="margin-top: 10px;">
                <select name="at_ai_quick_translate" style="width: 100%;">
                    <option value=""><?php _e('Quick translate to...', 'wordpress-ai-assistant'); ?></option>
                    <?php foreach ($this->supported_languages as $code => $name): ?>
                        <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button button-small" id="at_ai_quick_translate_btn" style="margin-top: 5px;">
                    <?php _e('Translate', 'wordpress-ai-assistant'); ?>
                </button>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#at_ai_quick_translate_btn').on('click', function() {
                var targetLang = $('select[name="at_ai_quick_translate"]').val();
                if (!targetLang) {
                    alert('<?php _e("Please select a target language", "wordpress-ai-assistant"); ?>');
                    return;
                }

                var postTitle = $('#title').val();
                var postContent = tinyMCE.activeEditor ? tinyMCE.activeEditor.getContent() : $('#content').val();

                if (!postTitle && !postContent) {
                    alert('<?php _e("Please enter some content to translate", "wordpress-ai-assistant"); ?>');
                    return;
                }

                $(this).prop('disabled', true).text('<?php _e("Translating...", "wordpress-ai-assistant"); ?>');

                $.post(ajaxurl, {
                    action: 'at_ai_translate_text',
                    nonce: at_ai_translator.nonce,
                    text: postTitle + '\n\n' + postContent,
                    target_language: targetLang,
                    context: ['wordpress', 'post', 'content']
                })
                .done(function(response) {
                    if (response.success) {
                        // Save translation
                        var translations = <?php echo json_encode($translations); ?>;
                        translations[targetLang] = response.data.translated_text;
                        $('input[name="at_ai_translations"]').val(JSON.stringify(translations));
                        alert('<?php _e("Translation completed! Check the translations list.", "wordpress-ai-assistant"); ?>');
                    } else {
                        alert(response.data || '<?php _e("Translation failed", "wordpress-ai-assistant"); ?>');
                    }
                })
                .fail(function() {
                    alert('<?php _e("Network error occurred", "wordpress-ai-assistant"); ?>');
                })
                .always(function() {
                    $('#at_ai_quick_translate_btn').prop('disabled', false).text('<?php _e("Translate", "wordpress-ai-assistant"); ?>');
                });
            });
        });
        </script>

        <input type="hidden" name="at_ai_translations" value="<?php echo esc_attr(json_encode($translations)); ?>">
        <?php
    }

    /**
     * Save translation meta data
     *
     * @param int $post_id
     */
    public function save_translation_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['at_ai_translation_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['at_ai_translation_nonce'], 'at_ai_translation_meta')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save translation enabled status
        $enabled = isset($_POST['at_ai_translation_enabled']) ? 1 : 0;
        update_post_meta($post_id, '_at_ai_translation_enabled', $enabled);

        // Save translations
        if (isset($_POST['at_ai_translations'])) {
            $translations = json_decode(stripslashes($_POST['at_ai_translations']), true);
            if (is_array($translations)) {
                update_post_meta($post_id, '_at_ai_translations', $translations);
            }
        }
    }
}
