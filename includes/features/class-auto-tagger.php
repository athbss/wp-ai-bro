<?php
/**
 * Auto Tagger and Categorizer
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
 * Auto Tagger Class
 */
class AT_Auto_Tagger {

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
        add_action('save_post', array($this, 'auto_tag_post'), 20, 2); // Run after other save operations
        add_action('add_attachment', array($this, 'auto_tag_attachment'), 10, 1);

        // AJAX handlers
        add_action('wp_ajax_at_ai_generate_tags', array($this, 'ajax_generate_tags'));
        add_action('wp_ajax_at_ai_apply_tags', array($this, 'ajax_apply_tags'));

        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_tagging_meta_box'));

        // Add settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Auto tag post on save
     *
     * @param int $post_id
     * @param WP_Post $post
     */
    public function auto_tag_post($post_id, $post) {
        // Check if auto-tagging is enabled
        if (!at_ai_assistant_get_option('auto_tagging_enabled', false)) {
            return;
        }

        // Check if this is an autosave
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Check if AI processing is enabled for this post
        $ai_enabled = get_post_meta($post_id, '_at_ai_processing_enabled', true);
        if ($ai_enabled === '0') {
            return;
        }

        // Check if this post type is enabled for AI processing
        $enabled_post_types = at_ai_assistant_get_option('enabled_post_types', array('post', 'page'));
        if (!in_array($post->post_type, $enabled_post_types)) {
            return;
        }

        // Check if post has content
        if (empty($post->post_title) && empty($post->post_content)) {
            return;
        }

        // Check if tags were already generated recently
        $last_generated = get_post_meta($post_id, '_at_ai_tags_generated', true);
        if ($last_generated && (time() - $last_generated) < 3600) { // 1 hour cooldown
            return;
        }

        // Generate and apply tags
        $this->generate_and_apply_tags($post_id, $post);
    }

    /**
     * Auto tag attachment
     *
     * @param int $attachment_id
     */
    public function auto_tag_attachment($attachment_id) {
        // Check if auto-tagging is enabled for media
        if (!at_ai_assistant_get_option('auto_tag_media_enabled', true)) {
            return;
        }

        // Check if it's an image
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        $attachment = get_post($attachment_id);

        // Get image description from AI (alt text generation already provides this)
        $description = get_post_meta($attachment_id, '_at_ai_image_description', true);

        if (empty($description)) {
            // Fallback to attachment title and caption
            $content = $attachment->post_title . ' ' . $attachment->post_content . ' ' . $attachment->post_excerpt;
        } else {
            $content = $description;
        }

        if (empty(trim($content))) {
            return;
        }

        $this->generate_and_apply_tags($attachment_id, $attachment);
    }

    /**
     * Generate and apply tags to post/attachment
     *
     * @param int $object_id
     * @param WP_Post $object
     */
    private function generate_and_apply_tags($object_id, $object) {
        $content = $this->extract_content($object);

        if (empty($content)) {
            return;
        }

        // Get available taxonomies
        $taxonomies = $this->get_available_taxonomies($object->post_type);

        // Get post language
        $post_language = at_ai_assistant_get_post_language($object_id);

        // Generate tags with language context
        $tags = $this->ai_manager->generate_tags($content, $taxonomies, $post_language);

        if (is_wp_error($tags)) {
            at_ai_assistant_log('auto_tagging', 'error', $tags->get_error_message(), array(
                'object_id' => $object_id,
                'object_type' => $object->post_type,
                'language' => $post_language,
            ), $object_id);
            return;
        }

        // Apply tags
        $this->apply_generated_tags($object_id, $object, $tags);

        $applied_total = 0;
        if (!empty($tags['taxonomies']) && is_array($tags['taxonomies'])) {
            foreach ($tags['taxonomies'] as $taxonomy_terms) {
                if (is_array($taxonomy_terms)) {
                    $applied_total += count($taxonomy_terms);
                }
            }
        } else {
            $applied_total += count($tags['tags'] ?? array());
            $applied_total += count($tags['categories'] ?? array());
        }

        // Mark as processed
        update_post_meta($object_id, '_at_ai_tags_generated', time());

        at_ai_assistant_log('auto_tagging', 'success', __('Tags generated and applied successfully', 'wordpress-ai-assistant'), array(
            'object_id' => $object_id,
            'terms_applied' => $applied_total,
        ), $object_id);
    }

    /**
     * Extract content from post/attachment
     *
     * @param WP_Post $object
     * @return string
     */
    private function extract_content($object) {
        $content = '';

        // Add title
        if (!empty($object->post_title)) {
            $content .= $object->post_title . ' ';
        }

        // Add content
        if (!empty($object->post_content)) {
            $content .= wp_strip_all_tags($object->post_content) . ' ';
        }

        // Add excerpt
        if (!empty($object->post_excerpt)) {
            $content .= $object->post_excerpt . ' ';
        }

        // For attachments, add image description if available
        if ($object->post_type === 'attachment') {
            $description = get_post_meta($object->ID, '_at_ai_image_description', true);
            if (!empty($description)) {
                $content .= $description . ' ';
            }
        }

        return trim($content);
    }

    /**
     * Get available taxonomies for post type
     *
     * @param string $post_type
     * @return array
     */
    private function get_available_taxonomies($post_type) {
        $taxonomies = array();
        $selected_taxonomies = $this->get_selected_taxonomies($post_type);

        // Get all taxonomies for this post type
        $post_taxonomies = get_object_taxonomies($post_type, 'objects');

        foreach ($post_taxonomies as $taxonomy) {
            if (!$taxonomy->public || !in_array($taxonomy->name, $selected_taxonomies, true)) {
                continue;
            }

            // Get existing terms
            $terms = get_terms(array(
                'taxonomy' => $taxonomy->name,
                'hide_empty' => false,
                'number' => 30,
            ));

            $taxonomies[$taxonomy->name] = array();
            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term) {
                    $taxonomies[$taxonomy->name][] = $term->name;
                }
            }
        }

        return $taxonomies;
    }

    /**
     * Apply generated tags to post/attachment
     *
     * @param int $object_id
     * @param WP_Post $object
     * @param array $tags
     */
    private function apply_generated_tags($object_id, $object, $tags) {
        $selected_taxonomies = $this->get_selected_taxonomies($object->post_type);

        // Preferred format: explicit taxonomy map from AI.
        if (!empty($tags['taxonomies']) && is_array($tags['taxonomies'])) {
            foreach ($tags['taxonomies'] as $taxonomy => $terms) {
                if (!in_array($taxonomy, $selected_taxonomies, true)) {
                    continue;
                }
                $this->apply_taxonomy_terms($object_id, $object->post_type, $taxonomy, $terms);
            }
        } else {
            // Backward compatibility for legacy keys.
            if (!empty($tags['tags']) && is_array($tags['tags']) && in_array('post_tag', $selected_taxonomies, true)) {
                $this->apply_taxonomy_terms($object_id, $object->post_type, 'post_tag', $tags['tags']);
            }

            if (!empty($tags['categories']) && is_array($tags['categories']) && in_array('category', $selected_taxonomies, true)) {
                $this->apply_taxonomy_terms($object_id, $object->post_type, 'category', $tags['categories']);
            }
        }

        // Store AI-generated metadata
        update_post_meta($object_id, '_at_ai_generated_tags', $tags);
    }

    /**
     * Apply terms to a taxonomy.
     *
     * @param int $object_id
     * @param string $post_type
     * @param string $taxonomy
     * @param array $terms
     */
    private function apply_taxonomy_terms($object_id, $post_type, $taxonomy, $terms) {
        if (!taxonomy_exists($taxonomy) || !is_object_in_taxonomy($post_type, $taxonomy)) {
            return;
        }

        if (!is_array($terms) || empty($terms)) {
            return;
        }

        $term_ids = array();
        foreach ($terms as $term_name) {
            $term_name = trim((string) $term_name);
            if ($term_name === '') {
                continue;
            }

            $existing_term = get_term_by('name', $term_name, $taxonomy);
            if ($existing_term && !is_wp_error($existing_term)) {
                $term_ids[] = (int) $existing_term->term_id;
                continue;
            }

            $created_term = wp_insert_term($term_name, $taxonomy);
            if (!is_wp_error($created_term) && !empty($created_term['term_id'])) {
                $term_ids[] = (int) $created_term['term_id'];
            }
        }

        if (!empty($term_ids)) {
            wp_set_object_terms($object_id, array_values(array_unique($term_ids)), $taxonomy, true);
        }
    }

    /**
     * AJAX handler for generating tags
     */
    public function ajax_generate_tags() {
        // Check permissions
        if (!at_ai_assistant_user_can_use_ai()) {
            wp_send_json_error(__('Insufficient permissions', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_generate_tags')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }

        $object_id = intval($_POST['object_id'] ?? 0);
        $object_type = sanitize_text_field($_POST['object_type'] ?? 'post');

        if (!$object_id) {
            wp_send_json_error(__('Invalid object ID', 'wordpress-ai-assistant'));
        }

        $object = get_post($object_id);
        if (!$object) {
            wp_send_json_error(__('Object not found', 'wordpress-ai-assistant'));
        }

        $content = $this->extract_content($object);
        if (empty($content)) {
            wp_send_json_error(__('No content found to analyze', 'wordpress-ai-assistant'));
        }

        $taxonomies = $this->get_available_taxonomies($object->post_type);
        
        // Get post language
        $post_language = at_ai_assistant_get_post_language($object_id);
        
        $tags = $this->ai_manager->generate_tags($content, $taxonomies, $post_language);

        if (is_wp_error($tags)) {
            wp_send_json_error($tags->get_error_message());
        }

        wp_send_json_success(array(
            'tags' => $tags,
            'message' => __('Tags generated successfully', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX handler for applying tags
     */
    public function ajax_apply_tags() {
        // Check permissions
        if (!at_ai_assistant_user_can_use_ai()) {
            wp_send_json_error(__('Insufficient permissions', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_apply_tags')) {
            wp_send_json_error(__('Security check failed', 'wordpress-ai-assistant'));
        }

        $object_id = intval($_POST['object_id'] ?? 0);
        $tags = $_POST['tags'] ?? array();

        if (!$object_id) {
            wp_send_json_error(__('Invalid object ID', 'wordpress-ai-assistant'));
        }

        if (!is_array($tags)) {
            wp_send_json_error(__('Invalid tags data', 'wordpress-ai-assistant'));
        }

        $object = get_post($object_id);
        if (!$object) {
            wp_send_json_error(__('Object not found', 'wordpress-ai-assistant'));
        }

        $this->apply_generated_tags($object_id, $object, $tags);

        wp_send_json_success(array(
            'message' => __('Tags applied successfully', 'wordpress-ai-assistant'),
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
            'at-ai-auto-tagger',
            WORDPRESS_AI_ASSISTANT_URL . 'admin/js/auto-tagger.js',
            array('jquery'),
            WORDPRESS_AI_ASSISTANT_VERSION,
            true
        );

        wp_localize_script('at-ai-auto-tagger', 'at_ai_tagger', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('at_ai_generate_tags'),
            'apply_nonce' => wp_create_nonce('at_ai_apply_tags'),
            'strings' => array(
                'generating' => __('Generating tags...', 'wordpress-ai-assistant'),
                'applying' => __('Applying tags...', 'wordpress-ai-assistant'),
                'error' => __('Error occurred', 'wordpress-ai-assistant'),
                'success' => __('Tags generated successfully', 'wordpress-ai-assistant'),
                'applied' => __('Tags applied successfully', 'wordpress-ai-assistant'),
            ),
        ));
    }

    /**
     * Add tagging meta box to posts
     */
    public function add_tagging_meta_box() {
        $post_types = at_ai_assistant_get_supported_post_types();

        foreach ($post_types as $post_type) {
            add_meta_box(
                'at_ai_tagging_meta',
                __('AI Tagging', 'wordpress-ai-assistant'),
                array($this, 'render_tagging_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render tagging meta box
     *
     * @param WP_Post $post
     */
    public function render_tagging_meta_box($post) {
        $ai_enabled = get_post_meta($post->ID, '_at_ai_processing_enabled', true);
        if ($ai_enabled === '') {
            $ai_enabled = '1'; // Default to enabled
        }

        $generated_tags = get_post_meta($post->ID, '_at_ai_generated_tags', true) ?: array();

        wp_nonce_field('at_ai_tagging_meta', 'at_ai_tagging_nonce');
        ?>
        <div class="at-ai-tagging-meta">
            <p>
                <label>
                    <input type="checkbox" name="at_ai_processing_enabled" value="1" <?php checked($ai_enabled, '1'); ?>>
                    <?php _e('Enable AI processing for this post', 'wordpress-ai-assistant');
                    ?>
                </label>
            </p>

            <div class="at-ai-generated-tags" style="margin-top: 10px;">
                <?php if (!empty($generated_tags)): ?>
                    <strong><?php _e('AI Generated Tags:', 'wordpress-ai-assistant'); ?></strong>
                    <div style="margin: 5px 0;">
                        <?php if (!empty($generated_tags['tags'])): ?>
                            <div><strong><?php _e('Tags:', 'wordpress-ai-assistant'); ?></strong>
                                <?php echo esc_html(implode(', ', $generated_tags['tags'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($generated_tags['categories'])): ?>
                            <div><strong><?php _e('Categories:', 'wordpress-ai-assistant'); ?></strong>
                                <?php echo esc_html(implode(', ', $generated_tags['categories'])); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($generated_tags['audience'])): ?>
                            <div><strong><?php _e('Target Audience:', 'wordpress-ai-assistant'); ?></strong>
                                <?php echo esc_html(implode(', ', $generated_tags['audience'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top: 10px;">
                <button type="button" class="button button-small" id="at_ai_generate_tags_btn">
                    <?php _e('Generate Tags', 'wordpress-ai-assistant'); ?>
                </button>
                <button type="button" class="button button-small" id="at_ai_apply_tags_btn" style="margin-left: 5px;">
                    <?php _e('Apply Tags', 'wordpress-ai-assistant'); ?>
                </button>
            </div>

            <div id="at_ai_tags_preview" style="margin-top: 10px; display: none;">
                <strong><?php _e('Preview:', 'wordpress-ai-assistant'); ?></strong>
                <div id="at_ai_tags_content"></div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var generatedTags = <?php echo json_encode($generated_tags); ?>;

            $('#at_ai_generate_tags_btn').on('click', function() {
                $(this).prop('disabled', true).text(at_ai_tagger.strings.generating);

                $.post(ajaxurl, {
                    action: 'at_ai_generate_tags',
                    nonce: at_ai_tagger.nonce,
                    object_id: <?php echo $post->ID; ?>,
                    object_type: '<?php echo $post->post_type; ?>'
                })
                .done(function(response) {
                    if (response.success) {
                        generatedTags = response.data.tags;
                        displayTagsPreview(generatedTags);
                        $('#at_ai_tags_preview').show();
                    } else {
                        alert(response.data || at_ai_tagger.strings.error);
                    }
                })
                .fail(function() {
                    alert(at_ai_tagger.strings.error);
                })
                .always(function() {
                    $('#at_ai_generate_tags_btn').prop('disabled', false).text('<?php _e("Generate Tags", "wordpress-ai-assistant"); ?>');
                });
            });

            $('#at_ai_apply_tags_btn').on('click', function() {
                if (!generatedTags || Object.keys(generatedTags).length === 0) {
                    alert('<?php _e("Please generate tags first", "wordpress-ai-assistant"); ?>');
                    return;
                }

                $(this).prop('disabled', true).text(at_ai_tagger.strings.applying);

                $.post(ajaxurl, {
                    action: 'at_ai_apply_tags',
                    nonce: at_ai_tagger.apply_nonce,
                    object_id: <?php echo $post->ID; ?>,
                    tags: generatedTags
                })
                .done(function(response) {
                    if (response.success) {
                        alert(at_ai_tagger.strings.applied);
                        location.reload(); // Reload to show applied tags
                    } else {
                        alert(response.data || at_ai_tagger.strings.error);
                    }
                })
                .fail(function() {
                    alert(at_ai_tagger.strings.error);
                })
                .always(function() {
                    $('#at_ai_apply_tags_btn').prop('disabled', false).text('<?php _e("Apply Tags", "wordpress-ai-assistant"); ?>');
                });
            });

            function displayTagsPreview(tags) {
                var html = '';
                if (tags.tags && tags.tags.length > 0) {
                    html += '<div><strong><?php _e("Tags:", "wordpress-ai-assistant"); ?></strong> ' + tags.tags.join(', ') + '</div>';
                }
                if (tags.categories && tags.categories.length > 0) {
                    html += '<div><strong><?php _e("Categories:", "wordpress-ai-assistant"); ?></strong> ' + tags.categories.join(', ') + '</div>';
                }
                if (tags.audience && tags.audience.length > 0) {
                    html += '<div><strong><?php _e("Audience:", "wordpress-ai-assistant"); ?></strong> ' + tags.audience.join(', ') + '</div>';
                }
                $('#at_ai_tags_content').html(html);
            }
        });
        </script>
        <?php
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_tagging_enabled');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_tag_media_enabled');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_tagging_taxonomies');

        add_settings_field(
            'auto_tagging_enabled',
            __('Auto Tagging', 'wordpress-ai-assistant'),
            array($this, 'render_auto_tagging_setting'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );

        add_settings_field(
            'auto_tag_media_enabled',
            __('Auto Tag Media', 'wordpress-ai-assistant'),
            array($this, 'render_auto_tag_media_setting'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );

        add_settings_field(
            'tagging_taxonomies',
            __('Tagging Taxonomies', 'wordpress-ai-assistant'),
            array($this, 'render_tagging_taxonomies_setting'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );
    }

    /**
     * Render auto tagging setting
     */
    public function render_auto_tagging_setting() {
        $value = at_ai_assistant_get_option('auto_tagging_enabled', false);
        ?>
        <label>
            <input type="checkbox" name="at_ai_assistant_auto_tagging_enabled" value="1" <?php checked($value); ?>>
            <?php _e('Automatically generate and apply tags to posts on save', 'wordpress-ai-assistant'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, AI will analyze post content and automatically suggest relevant tags and categories.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render auto tag media setting
     */
    public function render_auto_tag_media_setting() {
        $value = at_ai_assistant_get_option('auto_tag_media_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="at_ai_assistant_auto_tag_media_enabled" value="1" <?php checked($value); ?>>
            <?php _e('Automatically generate tags for uploaded images', 'wordpress-ai-assistant'); ?>
        </label>
        <p class="description">
            <?php _e('When enabled, AI will analyze uploaded images and generate relevant tags based on image content.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render taxonomy selection setting.
     */
    public function render_tagging_taxonomies_setting() {
        $selected = at_ai_assistant_get_option('tagging_taxonomies', array());
        if (!is_array($selected)) {
            $selected = array();
        }

        $public_taxonomies = get_taxonomies(array('public' => true), 'objects');
        ?>
        <fieldset>
            <?php foreach ($public_taxonomies as $taxonomy): ?>
                <label style="display: block; margin-bottom: 6px;">
                    <input type="checkbox"
                           name="at_ai_assistant_tagging_taxonomies[]"
                           value="<?php echo esc_attr($taxonomy->name); ?>"
                           <?php checked(in_array($taxonomy->name, $selected, true)); ?>>
                    <?php echo esc_html($taxonomy->label . ' (' . $taxonomy->name . ')'); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <p class="description">
            <?php _e('Choose which taxonomies AI can tag. If none are selected, all public taxonomies for the post type will be used.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Get selected taxonomies for a post type.
     *
     * @param string $post_type
     * @return array
     */
    private function get_selected_taxonomies($post_type) {
        $post_taxonomies = get_object_taxonomies($post_type, 'objects');
        $available = array();
        foreach ($post_taxonomies as $taxonomy) {
            if ($taxonomy->public) {
                $available[] = $taxonomy->name;
            }
        }

        $selected = at_ai_assistant_get_option('tagging_taxonomies', array());
        if (!is_array($selected) || empty($selected)) {
            return $available;
        }

        $selected = array_map('sanitize_key', $selected);
        return array_values(array_intersect($available, $selected));
    }
}
