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
        add_action('wp_ajax_at_ai_create_translation', array($this, 'ajax_create_translation'));

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

    /* ---------------------------------------------------------------------
     * Multilingual system integration (Polylang / WPML)
     * ------------------------------------------------------------------- */

    /**
     * Detect the active multilingual system.
     *
     * Polylang is preferred (used by the Haruv site); WPML is also supported.
     *
     * @return string 'polylang' | 'wpml' | '' (none)
     */
    public function get_active_language_system() {
        if (function_exists('pll_set_post_language') && function_exists('pll_save_post_translations')) {
            return 'polylang';
        }

        if (defined('ICL_SITEPRESS_VERSION')) {
            return 'wpml';
        }

        return '';
    }

    /**
     * Get the list of languages registered in the active multilingual system.
     *
     * @return array Map of language slug/code => human readable name.
     */
    public function get_site_languages() {
        $system = $this->get_active_language_system();
        $languages = array();

        if ($system === 'polylang') {
            $slugs = pll_languages_list(array('fields' => 'slug'));
            $names = pll_languages_list(array('fields' => 'name'));

            if (is_array($slugs) && is_array($names) && count($slugs) === count($names)) {
                $languages = array_combine($slugs, $names);
            } elseif (is_array($slugs)) {
                foreach ($slugs as $slug) {
                    $languages[$slug] = $this->get_language_name($slug);
                }
            }
        } elseif ($system === 'wpml') {
            $wpml_languages = apply_filters('wpml_active_languages', null, array('skip_missing' => 0));
            if (is_array($wpml_languages)) {
                foreach ($wpml_languages as $code => $lang) {
                    $languages[$code] = !empty($lang['native_name']) ? $lang['native_name'] : $code;
                }
            }
        }

        return $languages;
    }

    /**
     * Get the language code of a given post in the active system.
     *
     * @param int $post_id
     * @return string Language slug/code, or '' if unavailable.
     */
    public function get_post_language($post_id) {
        $system = $this->get_active_language_system();

        if ($system === 'polylang') {
            $lang = pll_get_post_language($post_id, 'slug');
            if (empty($lang) && function_exists('pll_default_language')) {
                $lang = pll_default_language('slug');
            }
            return $lang ?: '';
        }

        if ($system === 'wpml') {
            $details = apply_filters('wpml_post_language_details', null, $post_id);
            if (is_array($details) && !empty($details['language_code'])) {
                return $details['language_code'];
            }
            // Fallback to the site default language.
            return apply_filters('wpml_default_language', '');
        }

        return '';
    }

    /**
     * Get existing translations linked to a post.
     *
     * @param int $post_id
     * @return array Map of language code => translated post ID (excludes the source itself).
     */
    public function get_existing_translations($post_id) {
        $system = $this->get_active_language_system();
        $translations = array();

        if ($system === 'polylang') {
            $all = pll_get_post_translations($post_id);
            if (is_array($all)) {
                foreach ($all as $lang => $tr_id) {
                    if ((int) $tr_id !== (int) $post_id) {
                        $translations[$lang] = (int) $tr_id;
                    }
                }
            }
        } elseif ($system === 'wpml') {
            $post_type = get_post_type($post_id);
            $trid_details = apply_filters('wpml_element_language_details', null, array(
                'element_id'   => $post_id,
                'element_type' => 'post_' . $post_type,
            ));

            if (is_object($trid_details) && !empty($trid_details->trid)) {
                $elements = apply_filters('wpml_get_element_translations', null, $trid_details->trid, 'post_' . $post_type);
                if (is_array($elements)) {
                    foreach ($elements as $lang => $element) {
                        if (!empty($element->element_id) && (int) $element->element_id !== (int) $post_id) {
                            $translations[$lang] = (int) $element->element_id;
                        }
                    }
                }
            }
        }

        return $translations;
    }

    /**
     * Get the translation of a post in a specific language, if it exists.
     *
     * @param int    $post_id
     * @param string $target_language
     * @return int Translated post ID, or 0 if none.
     */
    public function get_translation_in_language($post_id, $target_language) {
        $translations = $this->get_existing_translations($post_id);
        return isset($translations[$target_language]) ? (int) $translations[$target_language] : 0;
    }

    /**
     * Create a linked translated copy of a post in the target language.
     *
     * Translates title/content/excerpt via the AI provider, creates a new draft
     * post of the same type, copies the featured image and terms, then links it
     * as a translation in the active multilingual system.
     *
     * @param int    $source_id
     * @param string $target_language
     * @return array|WP_Error On success: array with new_post_id, edit_link, etc.
     */
    public function create_translation($source_id, $target_language) {
        $source_id = (int) $source_id;
        $source = get_post($source_id);

        if (!$source) {
            return new WP_Error('invalid_post', __('Source post not found', 'wordpress-ai-assistant'));
        }

        if (!current_user_can('edit_post', $source_id)) {
            return new WP_Error('forbidden', __('Insufficient permissions to translate this post', 'wordpress-ai-assistant'));
        }

        $system = $this->get_active_language_system();
        if (!$system) {
            return new WP_Error(
                'no_language_system',
                __('A multilingual plugin is required. Please activate Polylang or WPML.', 'wordpress-ai-assistant')
            );
        }

        $target_language = sanitize_text_field($target_language);
        $site_languages = $this->get_site_languages();

        if (empty($target_language) || !isset($site_languages[$target_language])) {
            return new WP_Error('invalid_language', __('The selected target language is not registered in the site.', 'wordpress-ai-assistant'));
        }

        $source_language = $this->get_post_language($source_id);

        if ($source_language && $source_language === $target_language) {
            return new WP_Error('same_language', __('The source and target languages are identical.', 'wordpress-ai-assistant'));
        }

        // Prevent duplicates: if a translation already exists, return it as a notice.
        $existing_id = $this->get_translation_in_language($source_id, $target_language);
        if ($existing_id) {
            return new WP_Error(
                'translation_exists',
                sprintf(
                    __('A translation to %s already exists.', 'wordpress-ai-assistant'),
                    $site_languages[$target_language]
                ),
                array(
                    'existing_post_id' => $existing_id,
                    'edit_link'        => get_edit_post_link($existing_id, 'raw'),
                )
            );
        }

        $context = array('wordpress', 'post', $source->post_type);

        // Translate the title (skip empty).
        $translated_title = $source->post_title;
        if (trim($source->post_title) !== '') {
            $result = $this->translate_text($source->post_title, $target_language, $source_language ?: 'auto', $context);
            if (is_wp_error($result)) {
                return $result;
            }
            $translated_title = $result;
        }

        // Translate the content (skip empty).
        $translated_content = $source->post_content;
        if (trim($source->post_content) !== '') {
            $result = $this->translate_text($source->post_content, $target_language, $source_language ?: 'auto', $context);
            if (is_wp_error($result)) {
                return $result;
            }
            $translated_content = $result;
        }

        // Translate the excerpt (skip empty).
        $translated_excerpt = $source->post_excerpt;
        if (trim($source->post_excerpt) !== '') {
            $result = $this->translate_text($source->post_excerpt, $target_language, $source_language ?: 'auto', $context);
            if (is_wp_error($result)) {
                return $result;
            }
            $translated_excerpt = $result;
        }

        // Create the new translated post as a draft.
        $new_post = array(
            'post_type'    => $source->post_type,
            'post_status'  => 'draft',
            'post_title'   => $translated_title,
            'post_content' => $translated_content,
            'post_excerpt' => $translated_excerpt,
            'post_author'  => $source->post_author,
            'comment_status' => $source->comment_status,
            'ping_status'  => $source->ping_status,
        );

        $new_id = wp_insert_post(wp_slash($new_post), true);

        if (is_wp_error($new_id)) {
            return $new_id;
        }

        // Copy the featured image.
        $thumbnail_id = get_post_thumbnail_id($source_id);
        if ($thumbnail_id) {
            set_post_thumbnail($new_id, $thumbnail_id);
        }

        // Copy terms (categories / tags / custom taxonomies), skipping language taxonomies.
        $this->copy_post_terms($source_id, $new_id, $source->post_type);

        // Link the new post as a translation in the active system.
        $linked = $this->link_translation($source_id, $new_id, $source_language, $target_language, $system);
        if (is_wp_error($linked)) {
            // The post was created; surface the linking error but keep the draft.
            at_ai_assistant_log('translation', 'error', $linked->get_error_message(), array(
                'source_id' => $source_id,
                'new_id'    => $new_id,
                'system'    => $system,
            ));
        }

        at_ai_assistant_log('translation', 'success', __('Translated post created', 'wordpress-ai-assistant'), array(
            'source_id'       => $source_id,
            'new_id'          => $new_id,
            'source_language' => $source_language,
            'target_language' => $target_language,
            'system'          => $system,
        ));

        return array(
            'new_post_id'     => $new_id,
            'edit_link'       => get_edit_post_link($new_id, 'raw'),
            'target_language' => $target_language,
            'language_name'   => $site_languages[$target_language],
            'linked'          => !is_wp_error($linked),
        );
    }

    /**
     * Copy taxonomy terms from source post to target post.
     *
     * @param int    $source_id
     * @param int    $target_id
     * @param string $post_type
     */
    private function copy_post_terms($source_id, $target_id, $post_type) {
        // Taxonomies managed internally by the language plugins - never copy these.
        $skip_taxonomies = array('language', 'post_translations', 'term_language', 'term_translations');

        $taxonomies = get_object_taxonomies($post_type);

        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy, $skip_taxonomies, true)) {
                continue;
            }

            $term_ids = wp_get_object_terms($source_id, $taxonomy, array('fields' => 'ids'));

            if (!is_wp_error($term_ids) && !empty($term_ids)) {
                wp_set_object_terms($target_id, array_map('intval', $term_ids), $taxonomy);
            }
        }
    }

    /**
     * Link two posts as translations of each other.
     *
     * @param int    $source_id
     * @param int    $new_id
     * @param string $source_language
     * @param string $target_language
     * @param string $system 'polylang' | 'wpml'
     * @return true|WP_Error
     */
    private function link_translation($source_id, $new_id, $source_language, $target_language, $system) {
        if ($system === 'polylang') {
            // Assign the language to the new post.
            pll_set_post_language($new_id, $target_language);

            // Make sure the source post has a language too.
            if ($source_language) {
                pll_set_post_language($source_id, $source_language);
            }

            // Preserve any existing translation links and add the new one.
            $translations = pll_get_post_translations($source_id);
            if (!is_array($translations)) {
                $translations = array();
            }
            if ($source_language) {
                $translations[$source_language] = $source_id;
            }
            $translations[$target_language] = $new_id;

            pll_save_post_translations($translations);

            return true;
        }

        if ($system === 'wpml') {
            $post_type = get_post_type($source_id);

            $source_details = apply_filters('wpml_element_language_details', null, array(
                'element_id'   => $source_id,
                'element_type' => 'post_' . $post_type,
            ));

            $trid = (is_object($source_details) && !empty($source_details->trid)) ? $source_details->trid : null;

            if (!$source_language && is_object($source_details) && !empty($source_details->language_code)) {
                $source_language = $source_details->language_code;
            }

            do_action('wpml_set_element_language_details', array(
                'element_id'           => $new_id,
                'element_type'         => 'post_' . $post_type,
                'trid'                 => $trid,
                'language_code'        => $target_language,
                'source_language_code' => $source_language,
            ));

            return true;
        }

        return new WP_Error('no_language_system', __('No supported multilingual system found.', 'wordpress-ai-assistant'));
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
     * AJAX handler: create a linked translated post in the target language.
     */
    public function ajax_create_translation() {
        // Check permissions.
        if (!at_ai_assistant_user_can_use_ai()) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wordpress-ai-assistant')));
        }

        // Verify nonce.
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_create_translation')) {
            wp_send_json_error(array('message' => __('Security check failed', 'wordpress-ai-assistant')));
        }

        $source_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $target_language = sanitize_text_field($_POST['target_language'] ?? '');

        if (!$source_id) {
            wp_send_json_error(array('message' => __('Missing source post', 'wordpress-ai-assistant')));
        }

        // Per-post capability check.
        if (!current_user_can('edit_post', $source_id)) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wordpress-ai-assistant')));
        }

        if (empty($target_language)) {
            wp_send_json_error(array('message' => __('Target language is required', 'wordpress-ai-assistant')));
        }

        $result = $this->create_translation($source_id, $target_language);

        if (is_wp_error($result)) {
            $data = $result->get_error_data();
            wp_send_json_error(array(
                'message'   => $result->get_error_message(),
                'code'      => $result->get_error_code(),
                'edit_link' => is_array($data) && isset($data['edit_link']) ? $data['edit_link'] : '',
            ));
        }

        wp_send_json_success(array(
            'message'   => sprintf(
                __('Translation to %s created as a draft.', 'wordpress-ai-assistant'),
                $result['language_name']
            ),
            'edit_link' => $result['edit_link'],
            'post_id'   => $result['new_post_id'],
            'linked'    => $result['linked'],
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
        $system = $this->get_active_language_system();

        // No multilingual plugin active - show a clear notice and stop.
        if (!$system) {
            ?>
            <div class="at-ai-translation-meta">
                <p style="margin: 0; color: #b32d2e;">
                    <?php esc_html_e('A multilingual plugin is required. Please activate Polylang or WPML to create linked translations.', 'wordpress-ai-assistant'); ?>
                </p>
            </div>
            <?php
            return;
        }

        $source_language = $this->get_post_language($post->ID);
        $site_languages = $this->get_site_languages();
        $existing = $this->get_existing_translations($post->ID);

        // Target languages = all site languages except the source language.
        $target_languages = $site_languages;
        if ($source_language && isset($target_languages[$source_language])) {
            unset($target_languages[$source_language]);
        }

        $create_nonce = wp_create_nonce('at_ai_create_translation');
        ?>
        <div class="at-ai-translation-meta">
            <p style="margin-top: 0;">
                <?php
                printf(
                    /* translators: %s: multilingual system name */
                    esc_html__('Multilingual system: %s', 'wordpress-ai-assistant'),
                    '<strong>' . esc_html($system === 'polylang' ? 'Polylang' : 'WPML') . '</strong>'
                );
                ?>
                <?php if ($source_language && isset($site_languages[$source_language])): ?>
                    <br>
                    <?php
                    printf(
                        /* translators: %s: language name */
                        esc_html__('Post language: %s', 'wordpress-ai-assistant'),
                        '<strong>' . esc_html($site_languages[$source_language]) . '</strong>'
                    );
                    ?>
                <?php endif; ?>
            </p>

            <div class="at-ai-translation-list" style="margin: 10px 0;">
                <?php if (!empty($existing)): ?>
                    <strong><?php esc_html_e('Existing Translations:', 'wordpress-ai-assistant'); ?></strong>
                    <ul style="margin: 5px 0; padding-inline-start: 20px;">
                        <?php foreach ($existing as $lang => $tr_id):
                            $lang_label = isset($site_languages[$lang]) ? $site_languages[$lang] : $lang;
                            $edit_link = get_edit_post_link($tr_id, 'raw');
                        ?>
                            <li>
                                <?php echo esc_html($lang_label); ?>:
                                <?php if ($edit_link): ?>
                                    <a href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'wordpress-ai-assistant'); ?></a>
                                <?php else: ?>
                                    <?php echo esc_html(get_the_title($tr_id)); ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <?php if (!empty($target_languages)): ?>
                <div>
                    <select id="at_ai_translate_target" style="width: 100%;">
                        <option value=""><?php esc_html_e('Create translation to...', 'wordpress-ai-assistant'); ?></option>
                        <?php foreach ($target_languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button button-primary button-small" id="at_ai_create_translation_btn" style="margin-top: 8px;">
                        <?php esc_html_e('Create translation', 'wordpress-ai-assistant'); ?>
                    </button>
                    <span id="at_ai_translation_spinner" class="spinner" style="float: none; margin: 0 6px; vertical-align: middle;"></span>
                    <span class="description at-ai-field-help">
                        <?php esc_html_e('יוצר פוסט תרגום חדש כטיוטה מקושרת בשפה שנבחרה. התוכן מתורגם ב-AI — תוכל לעיין ולערוך לפני פרסום. אינו משנה את הפוסט הנוכחי.', 'wordpress-ai-assistant'); ?>
                    </span>
                    <p id="at_ai_translation_result" style="margin: 8px 0 0;" aria-live="polite"></p>
                </div>
            <?php else: ?>
                <p style="margin: 0;"><?php esc_html_e('No additional languages are configured for translation.', 'wordpress-ai-assistant'); ?></p>
            <?php endif; ?>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var i18n = {
                selectLang: <?php echo wp_json_encode(__('Please select a target language', 'wordpress-ai-assistant')); ?>,
                creating: <?php echo wp_json_encode(__('Creating translation...', 'wordpress-ai-assistant')); ?>,
                btn: <?php echo wp_json_encode(__('Create translation', 'wordpress-ai-assistant')); ?>,
                editLabel: <?php echo wp_json_encode(__('The translation was created - edit it', 'wordpress-ai-assistant')); ?>,
                networkError: <?php echo wp_json_encode(__('Network error occurred', 'wordpress-ai-assistant')); ?>
            };

            $('#at_ai_create_translation_btn').on('click', function() {
                var $btn = $(this);
                var $spinner = $('#at_ai_translation_spinner');
                var $result = $('#at_ai_translation_result');
                var targetLang = $('#at_ai_translate_target').val();

                if (!targetLang) {
                    $result.css('color', '#b32d2e').text(i18n.selectLang);
                    return;
                }

                $btn.prop('disabled', true);
                $spinner.addClass('is-active');
                $result.css('color', '').text(i18n.creating);

                $.post(ajaxurl, {
                    action: 'at_ai_create_translation',
                    nonce: <?php echo wp_json_encode($create_nonce); ?>,
                    post_id: <?php echo (int) $post->ID; ?>,
                    target_language: targetLang
                })
                .done(function(response) {
                    if (response && response.success) {
                        var link = response.data.edit_link
                            ? ' <a href="' + response.data.edit_link + '">' + i18n.editLabel + '</a>'
                            : '';
                        $result.css('color', '#2271b1').html($('<span>').text(response.data.message).html() + link);
                    } else {
                        var msg = (response && response.data && response.data.message) ? response.data.message : i18n.networkError;
                        var errLink = (response && response.data && response.data.edit_link)
                            ? ' <a href="' + response.data.edit_link + '">' + i18n.editLabel + '</a>'
                            : '';
                        $result.css('color', '#b32d2e').html($('<span>').text(msg).html() + errLink);
                    }
                })
                .fail(function() {
                    $result.css('color', '#b32d2e').text(i18n.networkError);
                })
                .always(function() {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
            });
        });
        </script>
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
