<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/admin
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 */
class AT_WordPress_AI_Assistant_Admin {

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
     * @var      string    $version    The version of this plugin.
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
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->ai_manager = AT_AI_Manager::get_instance();

        $this->init();
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        // Enqueue Suite Tokens if available (AT Agency Manager is active)
        if (defined('AT_AGENCY_MANAGER_URL')) {
             wp_enqueue_style(
                'at-suite-tokens',
                AT_AGENCY_MANAGER_URL . 'assets/css/suite-tokens.css',
                array(),
                defined('AT_AGENCY_MANAGER_VERSION') ? AT_AGENCY_MANAGER_VERSION : '1.0.0'
            );
        }

        wp_enqueue_style(
            $this->plugin_name,
            WORDPRESS_AI_ASSISTANT_URL . 'admin/css/admin.css',
            defined('AT_AGENCY_MANAGER_URL') ? array('at-suite-tokens') : array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        wp_enqueue_script(
            $this->plugin_name,
            WORDPRESS_AI_ASSISTANT_URL . 'admin/js/admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Load content optimizer on post edit screens
        if ($screen && in_array($screen->base, array('post', 'page'))) {
            wp_enqueue_script(
                'at-ai-content-optimizer',
                WORDPRESS_AI_ASSISTANT_URL . 'admin/js/content-optimizer.js',
                array('jquery'),
                $this->version,
                true
            );
        }

        wp_localize_script($this->plugin_name, 'at_ai_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('at_ai_admin_nonce'),
            'strings' => array(
                'testing' => __('בודק חיבור...', 'wordpress-ai-assistant'),
                'test_success' => __('חיבור הצליח!', 'wordpress-ai-assistant'),
                'test_failed' => __('חיבור נכשל!', 'wordpress-ai-assistant'),
                'saving' => __('שומר...', 'wordpress-ai-assistant'),
                'saved' => __('הגדרות נשמרו!', 'wordpress-ai-assistant'),
                'processing' => __('מעבד עם AI...', 'wordpress-ai-assistant'),
                'processed' => __('עיבוד AI הושלם!', 'wordpress-ai-assistant'),
                'generating' => __('יוצר...', 'wordpress-ai-assistant'),
                'generated' => __('נוצר!', 'wordpress-ai-assistant'),
                'generation_failed' => __('יצירה נכשלה', 'wordpress-ai-assistant'),
                'processing_failed' => __('עיבוד נכשל', 'wordpress-ai-assistant'),
                'try_again' => __('נסה שוב', 'wordpress-ai-assistant'),
                'network_error' => __('שגיאת רשת', 'wordpress-ai-assistant'),
                'save_failed' => __('שמירה נכשלה', 'wordpress-ai-assistant'),
                'hide' => __('הסתר', 'wordpress-ai-assistant'),
                'show' => __('הצג', 'wordpress-ai-assistant'),
                'cost_label' => __('עלות ($)', 'wordpress-ai-assistant'),
                'usage_chart_title' => __('עלות שימוש AI לאורך זמן', 'wordpress-ai-assistant'),
            ),
        ));

        // Localize content optimizer script
        if ($screen && in_array($screen->base, array('post', 'page'))) {
            wp_localize_script('at-ai-content-optimizer', 'atAiOptimizer', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('at_ai_content_optimizer'),
                'strings' => array(
                    'processing' => __('מעבד...', 'wordpress-ai-assistant'),
                    'suggesting' => __('מציע תגיות...', 'wordpress-ai-assistant'),
                    'optimizing' => __('מבצע אופטימיזציה...', 'wordpress-ai-assistant'),
                    'error' => __('שגיאה', 'wordpress-ai-assistant'),
                    'success' => __('הצלחה!', 'wordpress-ai-assistant'),
                    'no_content' => __('אין תוכן לניתוח', 'wordpress-ai-assistant'),
                    'applied' => __('המלצות הוחלו בהצלחה', 'wordpress-ai-assistant'),
                ),
            ));
        }
    }

    /**
     * Add admin menu
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __('עוזר AI', 'wordpress-ai-assistant'),
            __('עוזר AI', 'wordpress-ai-assistant'),
            'manage_options',
            'wordpress-ai-assistant',
            array($this, 'display_settings_page'),
            'dashicons-brain',
            99999
        );

        add_submenu_page(
            'wordpress-ai-assistant',
            __('הגדרות', 'wordpress-ai-assistant'),
            __('הגדרות', 'wordpress-ai-assistant'),
            'manage_options',
            'wordpress-ai-assistant',
            array($this, 'display_settings_page')
        );

        add_submenu_page(
            'wordpress-ai-assistant',
            __('שימוש ועלויות', 'wordpress-ai-assistant'),
            __('שימוש ועלויות', 'wordpress-ai-assistant'),
            'manage_options',
            'wordpress-ai-assistant-usage',
            array($this, 'display_usage_page')
        );

        add_submenu_page(
            'wordpress-ai-assistant',
            __('מגרש משחקים AI', 'wordpress-ai-assistant'),
            __('מגרש משחקים AI', 'wordpress-ai-assistant'),
            'manage_options',
            'wordpress-ai-assistant-playground',
            array($this, 'display_playground_page')
        );

        add_submenu_page(
            'wordpress-ai-assistant',
            __('מצב מערכת', 'wordpress-ai-assistant'),
            __('מצב מערכת', 'wordpress-ai-assistant'),
            'manage_options',
            'wordpress-ai-assistant-system-status',
            array($this, 'display_system_status_page')
        );
        
        // Add chat test page if chat is enabled
        if (at_ai_assistant_get_option('chat_enabled', false)) {
            add_submenu_page(
                'wordpress-ai-assistant',
                __('בדיקת צ\'אט', 'wordpress-ai-assistant'),
                __('בדיקת צ\'אט', 'wordpress-ai-assistant'),
                'manage_options',
                'wordpress-ai-assistant-chat-test',
                array($this, 'display_chat_test_page')
            );
        }
    }

    /**
     * Initialize admin hooks
     *
     * @since    1.1.0
     */
    public function init() {
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_ai_meta_box'));

        // Save meta data
        add_action('save_post', array($this, 'save_ai_meta_data'));

        // AJAX handlers
        add_action('wp_ajax_at_ai_test_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_at_ai_process_post', array($this, 'ajax_process_post'));
    }

    /**
     * Add AI meta box to posts
     *
     * @since    1.1.0
     */
    public function add_ai_meta_box() {
        $enabled_post_types = at_ai_assistant_get_option('enabled_post_types', array('post', 'page'));

        // Only add meta box to enabled post types
        if (!empty($enabled_post_types)) {
            foreach ($enabled_post_types as $post_type) {
                // Verify post type exists before adding meta box
                if (post_type_exists($post_type)) {
                    add_meta_box(
                        'at_ai_assistant_meta',
                        __('עוזר AI', 'wordpress-ai-assistant'),
                        array($this, 'render_ai_meta_box'),
                        $post_type,
                        'side',
                        'default'
                    );
                }
            }
        }
    }

    /**
     * Render AI meta box
     *
     * @param WP_Post $post
     * @since    1.1.0
     */
    public function render_ai_meta_box($post) {
        $ai_enabled = get_post_meta($post->ID, '_at_ai_processing_enabled', true);
        if ($ai_enabled === '') {
            $ai_enabled = '1'; // Default to enabled
        }

        // Get available taxonomies
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        $public_taxonomies = array_filter($taxonomies, function($tax) {
            return $tax->public && $tax->show_ui;
        });

        wp_nonce_field('at_ai_meta_box', 'at_ai_meta_box_nonce');
        ?>
        <div class="at-ai-meta-box">
            <p>
                <label>
                    <input type="checkbox" name="at_ai_processing_enabled" value="1" <?php checked($ai_enabled, '1'); ?>>
                    <?php _e('הפעל עיבוד AI לפוסט זה', 'wordpress-ai-assistant'); ?>
                </label>
            </p>

            <!-- Smart Taxonomy Tagging -->
            <h4><?php _e('תיוג אוטומטי חכם', 'wordpress-ai-assistant'); ?></h4>
            
            <div class="at-ai-taxonomy-selection">
                <p class="description">
                    <?php _e('בחר טקסונומיות לתיוג:', 'wordpress-ai-assistant'); ?>
                </p>
                <?php foreach ($public_taxonomies as $taxonomy): ?>
                    <label style="display: block; margin: 5px 0;">
                        <input type="checkbox" 
                               class="at-ai-taxonomy-checkbox" 
                               value="<?php echo esc_attr($taxonomy->name); ?>"
                               <?php checked(in_array($taxonomy->name, array('post_tag', 'category'))); ?>>
                        <?php echo esc_html($taxonomy->labels->name); ?>
                        <?php
                        $term_count = wp_count_terms(array('taxonomy' => $taxonomy->name, 'hide_empty' => false));
                        if (!is_wp_error($term_count)) {
                            echo '<span class="description">(' . $term_count . ')</span>';
                        }
                        ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" class="button button-secondary at-ai-suggest-taxonomies" data-post-id="<?php echo $post->ID; ?>">
                    <span class="dashicons dashicons-tag"></span>
                    <?php _e('הצע תגיות וקטגוריות', 'wordpress-ai-assistant'); ?>
                </button>
            </p>

            <div id="at-ai-taxonomy-suggestions" style="display: none; margin-top: 10px;">
                <div class="at-ai-suggestions-content notice notice-info inline"></div>
                <p>
                    <button type="button" class="button button-primary at-ai-apply-suggestions">
                        <?php _e('החל המלצות', 'wordpress-ai-assistant'); ?>
                    </button>
                </p>
            </div>

            <hr>

            <!-- SEO/AEO Optimization -->
            <h4><?php _e('אופטימיזציה SEO ו-AEO', 'wordpress-ai-assistant'); ?></h4>
            <p class="description">
                <?php _e('שפר את התוכן למנועי חיפוש ומנועי תשובות AI', 'wordpress-ai-assistant'); ?>
            </p>

            <p>
                <button type="button" class="button button-secondary at-ai-optimize-content" data-post-id="<?php echo $post->ID; ?>">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('בצע אופטימיזציה', 'wordpress-ai-assistant'); ?>
                </button>
            </p>

            <div id="at-ai-optimization-results" style="display: none; margin-top: 10px;"></div>

            <hr>

            <div id="at-ai-processing-result" style="margin-top: 10px; display: none;"></div>
            
            <div id="at-ai-operation-stats" class="description"></div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#at-ai-process-post-btn').on('click', function(e) {
                e.preventDefault();

                var $button = $(this);
                var originalText = $button.text();

                $button.prop('disabled', true).text(at_ai_admin.strings.processing);

                $.post(at_ai_admin.ajax_url, {
                    action: 'at_ai_process_post',
                    nonce: at_ai_admin.nonce,
                    post_id: <?php echo $post->ID; ?>
                })
                .done(function(response) {
                    if (response.success) {
                        $('#at-ai-processing-result').html(
                            '<div class="notice notice-success inline"><p>' +
                            at_ai_admin.strings.processed +
                            '</p></div>'
                        ).show();

                        $button.text(at_ai_admin.strings.processed);

                        // Reload page after 2 seconds to show changes
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $('#at-ai-processing-result').html(
                            '<div class="notice notice-error inline"><p>' +
                            (response.data || '<?php _e("העיבוד נכשל", "wordpress-ai-assistant"); ?>') +
                            '</p></div>'
                        ).show();

                        $button.text('<?php _e("נסה שוב", "wordpress-ai-assistant"); ?>');
                    }
                })
                .fail(function() {
                    $('#at-ai-processing-result').html(
                        '<div class="notice notice-error inline"><p>' +
                        '<?php _e("אירעה שגיאת רשת", "wordpress-ai-assistant"); ?>' +
                        '</p></div>'
                    ).show();

                    $button.text(originalText);
                })
                .always(function() {
                    $button.prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Save AI meta data
     *
     * @param int $post_id
     * @since    1.1.0
     */
    public function save_ai_meta_data($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['at_ai_meta_box_nonce'])) {
            return;
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['at_ai_meta_box_nonce'], 'at_ai_meta_box')) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save AI processing enabled status
        $ai_enabled = isset($_POST['at_ai_processing_enabled']) ? 1 : 0;
        update_post_meta($post_id, '_at_ai_processing_enabled', $ai_enabled);
    }

    /**
     * AJAX handler for processing post with AI
     *
     * @since    1.1.0
     */
    public function ajax_process_post() {
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('אין הרשאות מספיקות', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_admin_nonce')) {
            wp_send_json_error(__('בדיקת אבטחה נכשלה', 'wordpress-ai-assistant'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(__('מזהה פוסט לא תקין', 'wordpress-ai-assistant'));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('פוסט לא נמצא', 'wordpress-ai-assistant'));
        }

        // Check if user can edit this post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('אין הרשאות מספיקות', 'wordpress-ai-assistant'));
        }

        // Check if this post type is enabled for AI processing
        $enabled_post_types = at_ai_assistant_get_option('enabled_post_types', array('post', 'page'));
        if (!in_array($post->post_type, $enabled_post_types)) {
            wp_send_json_error(__('עיבוד AI לא מופעל עבור סוג פוסט זה', 'wordpress-ai-assistant'));
        }

        // Process the post with AI
        $result = $this->process_post_with_ai($post);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(array(
            'message' => __('פוסט עובד בהצלחה עם AI', 'wordpress-ai-assistant'),
            'processed_features' => $result,
        ));
    }

    /**
     * Process post with AI
     *
     * @param WP_Post $post
     * @return array|WP_Error
     * @since    1.1.0
     */
    private function process_post_with_ai($post) {
        $processed_features = array();

        // Generate excerpt if empty
        if (empty($post->post_excerpt) && at_ai_assistant_get_option('auto_generate_excerpt', true)) {
            $excerpt_result = $this->generate_excerpt($post);
            if (!is_wp_error($excerpt_result)) {
                wp_update_post(array(
                    'ID' => $post->ID,
                    'post_excerpt' => $excerpt_result,
                ));
                $processed_features[] = 'excerpt';
            }
        }

        // Generate tags
        if (at_ai_assistant_get_option('auto_tagging_enabled', false)) {
            $content = $post->post_title . ' ' . $post->post_content;
            $taxonomies = array('post_tag' => array()); // Add existing tags
            
            // Get post language
            $post_language = at_ai_assistant_get_post_language($post->ID);

            $tags_result = $this->ai_manager->generate_tags($content, $taxonomies, $post_language);
            if (!is_wp_error($tags_result) && !empty($tags_result['tags'])) {
                wp_set_post_tags($post->ID, $tags_result['tags'], true);
                $processed_features[] = 'tags';
            }
        }

        // Generate categories if enabled
        if (at_ai_assistant_get_option('auto_categorize_enabled', false)) {
            $content = $post->post_title . ' ' . $post->post_content;
            $taxonomies = array('category' => array()); // Add existing categories
            
            // Get post language
            $post_language = at_ai_assistant_get_post_language($post->ID);

            $tags_result = $this->ai_manager->generate_tags($content, $taxonomies, $post_language);
            if (!is_wp_error($tags_result) && !empty($tags_result['categories'])) {
                $category_ids = array();
                foreach ($tags_result['categories'] as $category_name) {
                    $existing_category = get_term_by('name', $category_name, 'category');
                    if ($existing_category) {
                        $category_ids[] = $existing_category->term_id;
                    } else {
                        $new_category = wp_insert_term($category_name, 'category');
                        if (!is_wp_error($new_category)) {
                            $category_ids[] = $new_category['term_id'];
                        }
                    }
                }

                if (!empty($category_ids)) {
                    wp_set_post_categories($post->ID, $category_ids, true);
                    $processed_features[] = 'categories';
                }
            }
        }

        return $processed_features;
    }

    /**
     * Generate excerpt for post
     *
     * @param WP_Post $post
     * @return string|WP_Error
     * @since    1.1.0
     */
    private function generate_excerpt($post) {
        $content = wp_strip_all_tags($post->post_content);
        $content = substr($content, 0, 500); // Limit content length

        $prompt = sprintf(
            __('צור תקציר תמציתי (2-3 משפטים) לתוכן הבא: %s', 'wordpress-ai-assistant'),
            $content
        );

        // Generate with language context
        $result = $this->ai_manager->generate_text($prompt, array('max_tokens' => 150), $post->ID);

        if (is_wp_error($result)) {
            return $result;
        }

        return wp_trim_words($result['text'], 30, '...');
    }

    /**
     * Register settings
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // General settings
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_active_provider');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_enabled_post_types');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_generate_alt_text');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_tagging_enabled');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_generate_excerpt');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_categorize_enabled');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_auto_tag_media_enabled');
        
        // Prompt instructions
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_general_prompt_instructions');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_visual_style_instructions');
        
        // Chat settings
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_enabled');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_enabled_in_admin');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_enabled_for_all');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_enabled_for_visitors');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_show_in_admin_bar');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_auto_show_floating');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_welcome_message');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_max_tokens');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_chat_temperature');

        // API Credentials
        register_setting('at_ai_assistant_credentials', 'at_ai_assistant_ai_credentials');

        // Feature-specific settings
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_openai_model');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_anthropic_model');
        register_setting('at_ai_assistant_settings', 'at_ai_assistant_google_model');

        // Provider-specific settings
        $providers = $this->ai_manager->get_providers();
        foreach ($providers as $provider) {
            if (!in_array('at_ai_assistant_' . $provider . '_model', array(
                'at_ai_assistant_openai_model',
                'at_ai_assistant_anthropic_model',
                'at_ai_assistant_google_model'
            ))) {
                register_setting('at_ai_assistant_settings', 'at_ai_assistant_' . $provider . '_model');
            }
        }

        // Add settings sections
        add_settings_section(
            'at_ai_assistant_general',
            __('הגדרות כלליות', 'wordpress-ai-assistant'),
            array($this, 'render_general_section'),
            'at_ai_assistant_settings'
        );

        add_settings_section(
            'at_ai_assistant_providers',
            __('ספקי AI', 'wordpress-ai-assistant'),
            array($this, 'render_providers_section'),
            'at_ai_assistant_settings'
        );

        add_settings_section(
            'at_ai_assistant_features',
            __('תכונות AI', 'wordpress-ai-assistant'),
            array($this, 'render_features_section'),
            'at_ai_assistant_settings'
        );

        // Add settings fields
        add_settings_field(
            'active_provider',
            __('ספק פעיל', 'wordpress-ai-assistant'),
            array($this, 'render_active_provider_field'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );

        add_settings_field(
            'enabled_post_types',
            __('סוגי פוסטים עם עוזר AI', 'wordpress-ai-assistant'),
            array($this, 'render_enabled_post_types_field'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );

        add_settings_field(
            'ai_features_enabled',
            __('תכונות AI', 'wordpress-ai-assistant'),
            array($this, 'render_ai_features_field'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );

        add_settings_field(
            'general_prompt_instructions',
            __('הנחיות כלליות לפרומפטים', 'wordpress-ai-assistant'),
            array($this, 'render_general_prompt_instructions_field'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );

        add_settings_field(
            'visual_style_instructions',
            __('הנחיות סגנון ויזואלי', 'wordpress-ai-assistant'),
            array($this, 'render_visual_style_instructions_field'),
            'at_ai_assistant_settings',
            'at_ai_assistant_general'
        );
    }

    /**
     * Display settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('at_ai_assistant_messages', 'at_ai_assistant_message', __('הגדרות נשמרו', 'wordpress-ai-assistant'), 'updated');
        }

        settings_errors('at_ai_assistant_messages');
        ?>
        <div class="wrap">
            <h1><?php _e('הגדרות עוזר AI לוורדפרס', 'wordpress-ai-assistant'); ?></h1>

            <?php $this->render_tabs(); ?>

            <form action="options.php" method="post">
                <?php
                $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

                if ($active_tab === 'general') {
                    settings_fields('at_ai_assistant_settings');
                    do_settings_sections('at_ai_assistant_settings');
                } elseif ($active_tab === 'credentials') {
                    settings_fields('at_ai_assistant_credentials');
                    $this->render_credentials_section();
                }

                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render tabs
     */
    private function render_tabs() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        $tabs = array(
            'general' => __('כללי', 'wordpress-ai-assistant'),
            'credentials' => __('מפתחות API', 'wordpress-ai-assistant'),
        );
        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab_key => $tab_caption): ?>
                <a href="?page=wordpress-ai-assistant&tab=<?php echo $tab_key; ?>"
                   class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                    <?php echo $tab_caption; ?>
                </a>
            <?php endforeach; ?>
        </h2>
        <?php
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . __('הגדרת הגדרות כלליות לתוסף עוזר ה-AI.', 'wordpress-ai-assistant') . '</p>';
    }

    /**
     * Render providers section
     */
    public function render_providers_section() {
        echo '<p>' . __('הגדר ספקי AI והגדרותיהם.', 'wordpress-ai-assistant') . '</p>';
    }

    /**
     * Render features section
     */
    public function render_features_section() {
        echo '<p>' . __('הפעל או השבת תכונות AI ספציפיות.', 'wordpress-ai-assistant') . '</p>';
    }

    /**
     * Render active provider field
     */
    public function render_active_provider_field() {
        $current_provider = at_ai_assistant_get_option('active_provider', 'openai');
        $providers = $this->ai_manager->get_providers();
        ?>
        <select name="at_ai_assistant_active_provider">
            <?php foreach ($providers as $provider): ?>
                <option value="<?php echo esc_attr($provider); ?>" <?php selected($current_provider, $provider); ?>>
                    <?php echo esc_html(ucfirst($provider)); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php _e('בחר את ספק ה-AI לשימוש בכל פעולות ה-AI.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render enabled post types field
     */
    public function render_enabled_post_types_field() {
        $enabled_types = at_ai_assistant_get_option('enabled_post_types', array('post', 'page'));
        $post_types = get_post_types(array('public' => true), 'objects');
        ?>
        <div class="at-ai-post-types">
            <?php foreach ($post_types as $post_type): ?>
                <label style="display: block; margin-bottom: 5px;">
                    <input type="checkbox"
                           name="at_ai_assistant_enabled_post_types[]"
                           value="<?php echo esc_attr($post_type->name); ?>"
                           <?php checked(in_array($post_type->name, $enabled_types)); ?>>
                    <?php echo esc_html($post_type->label); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="description">
            <?php _e('בחר אילו סוגי פוסטים יכללו את עוזר ה-AI (תיבת מטא ועיבוד אוטומטי).', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render AI features field
     */
    public function render_ai_features_field() {
        $features = array(
            'auto_generate_alt_text' => __('צור טקסט חלופי אוטומטי לתמונות', 'wordpress-ai-assistant'),
            'auto_tagging_enabled' => __('תיוג וקיטלוג אוטומטי', 'wordpress-ai-assistant'),
            'auto_generate_excerpt' => __('צור תקציר אוטומטי', 'wordpress-ai-assistant'),
            'auto_categorize_enabled' => __('צור קטגוריות אוטומטית', 'wordpress-ai-assistant'),
            'auto_tag_media_enabled' => __('תייג מדיה מועלית אוטומטית', 'wordpress-ai-assistant'),
        );
        ?>
        <div class="at-ai-features">
            <?php foreach ($features as $feature_key => $feature_label): ?>
                <label style="display: block; margin-bottom: 8px;">
                    <input type="checkbox"
                           name="at_ai_assistant_<?php echo esc_attr($feature_key); ?>"
                           value="1"
                           <?php checked(at_ai_assistant_get_option($feature_key, false)); ?>>
                    <?php echo esc_html($feature_label); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <p class="description">
            <?php _e('הפעל או השבת תכונות AI ספציפיות בצורה גלובלית.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render general prompt instructions field
     */
    public function render_general_prompt_instructions_field() {
        $value = at_ai_assistant_get_option('general_prompt_instructions', '');
        ?>
        <textarea name="at_ai_assistant_general_prompt_instructions" 
                  rows="5" 
                  class="large-text"
                  placeholder="<?php esc_attr_e('דוגמה: כתוב בטון מקצועי, השתמש בשפה ברורה ותמציתית, הימנע מז\'רגון...', 'wordpress-ai-assistant'); ?>"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('הנחיות אלו יתווספו אוטומטית לכל הפרומפטים של AI. השתמש בזה כדי להגדיר סגנון כתיבה כללי, טון, או הנחיות תוכן.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render visual style instructions field
     */
    public function render_visual_style_instructions_field() {
        $value = at_ai_assistant_get_option('visual_style_instructions', '');
        ?>
        <textarea name="at_ai_assistant_visual_style_instructions" 
                  rows="5" 
                  class="large-text"
                  placeholder="<?php esc_attr_e('דוגמה: השתמש בסגנון עיצובי מודרני ונקי. העדף צבעים תוססים. שמור על מראה מקצועי...', 'wordpress-ai-assistant'); ?>"><?php echo esc_textarea($value); ?></textarea>
        <p class="description">
            <?php _e('הנחיות אלו יתווספו לפרומפטים של יצירת תמונות. השתמש בזה כדי להגדיר סגנון ויזואלי, ערכות צבעים, העדפות עיצוב, וכו\'.', 'wordpress-ai-assistant'); ?>
        </p>
        <?php
    }

    /**
     * Render credentials section
     */
    private function render_credentials_section() {
        $credentials = at_ai_assistant_get_option('ai_credentials', array());
        $providers = $this->ai_manager->get_providers();
        
        // API Key generation pages
        $api_key_pages = array(
            'openai' => array(
                'url' => 'https://platform.openai.com/api-keys',
                'help_text' => __('💡 איפה למצוא: Platform → API Keys → Create new secret key', 'wordpress-ai-assistant'),
            ),
            'anthropic' => array(
                'url' => 'https://console.anthropic.com/',
                'help_text' => __('💡 איפה למצוא: Console → Settings (ימין עליון) → API Keys → Create Key', 'wordpress-ai-assistant'),
            ),
            'google' => array(
                'url' => 'https://aistudio.google.com/app/apikey',
                'help_text' => __('💡 איפה למצוא: Google AI Studio → API Keys → Get API key (בחר/צור Cloud Project)', 'wordpress-ai-assistant'),
            ),
        );
        ?>
        <div class="at-ai-credentials">
            <?php foreach ($providers as $provider): ?>
                <div class="at-ai-provider-credentials" style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                    <h3>
                        <?php printf(__('הגדרות %s API', 'wordpress-ai-assistant'), ucfirst($provider)); ?>
                    </h3>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="at_ai_<?php echo $provider; ?>_api_key">API Key</label>
                            </th>
                            <td>
                                <input type="password"
                                       id="at_ai_<?php echo $provider; ?>_api_key"
                                       name="at_ai_assistant_ai_credentials[<?php echo $provider; ?>][api_key]"
                                       value="<?php echo esc_attr($credentials[$provider]['api_key'] ?? ''); ?>"
                                       class="regular-text">
                                <button type="button"
                                        class="button at-ai-test-connection"
                                        data-provider="<?php echo $provider; ?>">
                                    <?php _e('בדיקת חיבור', 'wordpress-ai-assistant'); ?>
                                </button>
                                <p class="description" style="margin: 8px 0; padding: 10px; background: #f0f6ff; border-right: 4px solid #0073aa; border-radius: 3px;">
                                    <?php if (isset($api_key_pages[$provider])): ?>
                                        <strong><?php echo $api_key_pages[$provider]['help_text']; ?></strong><br>
                                        <a href="<?php echo esc_url($api_key_pages[$provider]['url']); ?>" target="_blank" rel="noopener noreferrer" style="color: #0073aa; text-decoration: none; font-weight: 600;">
                                            🔗 <?php printf(__('פתח את קונסול %s', 'wordpress-ai-assistant'), ucfirst($provider)); ?>
                                        </a>
                                    <?php endif; ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="at_ai_<?php echo $provider; ?>_model"><?php _e('מודל ברירת מחדל', 'wordpress-ai-assistant'); ?></label>
                            </th>
                            <td>
                                <?php
                                $provider_instance = $this->ai_manager->get_provider($provider);
                                $current_model = at_ai_assistant_get_option($provider . '_model', '');
                                $available_models = $provider_instance ? $provider_instance->get_available_models() : array();
                                ?>
                                <select id="at_ai_<?php echo $provider; ?>_model"
                                        name="at_ai_assistant_<?php echo $provider; ?>_model">
                                    <?php foreach ($available_models as $model_key => $model_name): ?>
                                        <option value="<?php echo esc_attr($model_key); ?>" <?php selected($current_model, $model_key); ?>>
                                            <?php echo esc_html($model_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Display usage page
     */
    public function display_usage_page() {
        $usage_tracker = $this->ai_manager->get_usage_stats('month');
        
        // Safe check for get_cost_summary method
        if (method_exists($this->ai_manager, 'get_cost_summary')) {
            $cost_summary = $this->ai_manager->get_cost_summary('month');
        } else {
            // Fallback if method doesn't exist
            $cost_summary = array(
                'providers' => array(),
                'total_cost' => 0,
                'total_tokens' => 0,
                'total_requests' => 0,
            );
        }
        ?>
        <div class="wrap">
            <h1><?php _e('שימוש ועלויות AI', 'wordpress-ai-assistant'); ?></h1>

            <div class="at-ai-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="at-ai-stat-card" style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('סך בקשות', 'wordpress-ai-assistant'); ?></h3>
                    <div style="font-size: 32px; font-weight: bold; color: #0073aa;">
                        <?php echo number_format($usage_tracker['total_requests']); ?>
                    </div>
                </div>

                <div class="at-ai-stat-card" style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('סך טוקנים', 'wordpress-ai-assistant'); ?></h3>
                    <div style="font-size: 32px; font-weight: bold; color: #0073aa;">
                        <?php echo number_format($usage_tracker['total_tokens']); ?>
                    </div>
                </div>

                <div class="at-ai-stat-card" style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 4px;">
                    <h3><?php _e('עלות כוללת', 'wordpress-ai-assistant'); ?></h3>
                    <div style="font-size: 32px; font-weight: bold; color: #0073aa;">
                        $<?php echo number_format($cost_summary['total_cost'], 2); ?>
                    </div>
                </div>
            </div>

            <div class="at-ai-usage-details">
                <h2><?php _e('שימוש לפי ספק', 'wordpress-ai-assistant'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('ספק', 'wordpress-ai-assistant'); ?></th>
                            <th><?php _e('בקשות', 'wordpress-ai-assistant'); ?></th>
                            <th><?php _e('טוקנים', 'wordpress-ai-assistant'); ?></th>
                            <th><?php _e('עלות', 'wordpress-ai-assistant'); ?></th>
                            <th><?php _e('עלות ממוצעת לבקשה', 'wordpress-ai-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cost_summary['providers'] as $provider => $data): ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($provider)); ?></td>
                                <td><?php echo number_format($data['requests']); ?></td>
                                <td><?php echo number_format($data['tokens']); ?></td>
                                <td>$<?php echo number_format($data['cost'], 4); ?></td>
                                <td>$<?php echo number_format($data['avg_cost_per_request'], 4); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Display playground page
     */
    public function display_playground_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('מגרש משחקים AI', 'wordpress-ai-assistant'); ?></h1>

            <div class="at-ai-playground">
                <div class="at-ai-playground-form">
                    <h3><?php _e('בדיקת יצירת AI', 'wordpress-ai-assistant'); ?></h3>
                    <form id="at-ai-playground-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="at_ai_prompt"><?php _e('פרומפט', 'wordpress-ai-assistant'); ?></label>
                                </th>
                                <td>
                                    <textarea id="at_ai_prompt" name="prompt" rows="5" class="large-text"
                                              placeholder="<?php _e('הזן את הפרומפט שלך כאן...', 'wordpress-ai-assistant'); ?>"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="at_ai_max_tokens"><?php _e('מקסימום טוקנים', 'wordpress-ai-assistant'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="at_ai_max_tokens" name="max_tokens" value="500" min="1" max="4000">
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="at-ai-generate-btn">
                                <?php _e('צור', 'wordpress-ai-assistant'); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <div class="at-ai-playground-result" style="margin-top: 30px;">
                    <h3><?php _e('תוצאה', 'wordpress-ai-assistant'); ?></h3>
                    <div id="at-ai-result" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 4px; min-height: 100px;">
                        <?php _e('תוצאות יופיעו כאן...', 'wordpress-ai-assistant'); ?>
                    </div>
                </div>
            </div>
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#at-ai-playground-form').on('submit', function(e) {
                e.preventDefault();

                var prompt = $('#at_ai_prompt').val();
                var maxTokens = $('#at_ai_max_tokens').val();

                if (!prompt.trim()) {
                    alert('<?php _e("אנא הזן פרומפט", "wordpress-ai-assistant"); ?>');
                    return;
                }

                $('#at-ai-generate-btn').prop('disabled', true).text('<?php _e("יוצר...", "wordpress-ai-assistant"); ?>');

                $.post(ajaxurl, {
                    action: 'at_ai_generate_text',
                    nonce: at_ai_admin.nonce,
                    prompt: prompt,
                    max_tokens: maxTokens
                })
                .done(function(response) {
                    if (response.success) {
                        $('#at-ai-result').html('<pre>' + response.data.text + '</pre>');
                    } else {
                        $('#at-ai-result').html('<div style="color: red;">' + (response.data || '<?php _e("אירעה שגיאה", "wordpress-ai-assistant"); ?>') + '</div>');
                    }
                })
                .fail(function() {
                    $('#at-ai-result').html('<div style="color: red;"><?php _e("אירעה שגיאת רשת", "wordpress-ai-assistant"); ?></div>');
                })
                .always(function() {
                    $('#at-ai-generate-btn').prop('disabled', false).text('<?php _e("צור", "wordpress-ai-assistant"); ?>');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Display system status page
     */
    public function display_system_status_page() {
        $dependency_checker = AT_Dependency_Checker::get_instance();
        $dependencies_status = $dependency_checker->get_dependencies_status();
        
        $can_work = $dependency_checker->can_plugin_work();
        $has_critical = $dependency_checker->has_critical_missing();
        
        // Group by status
        $required_deps = array();
        $recommended_deps = array();
        $optional_deps = array();
        
        foreach ($dependencies_status as $key => $dep) {
            if ($dep['status'] === 'required') {
                $required_deps[$key] = $dep;
            } elseif ($dep['status'] === 'recommended') {
                $recommended_deps[$key] = $dep;
            } else {
                $optional_deps[$key] = $dep;
            }
        }
        ?>
        <div class="wrap">
            <h1><?php _e('מצב מערכת - WordPress AI Assistant', 'wordpress-ai-assistant'); ?></h1>

            <!-- סטטוס כללי -->
            <div class="at-ai-system-status-header" style="background: <?php echo $has_critical ? '#f8d7da' : '#d4edda'; ?>; 
                 padding: 20px; 
                 border-right: 4px solid <?php echo $has_critical ? '#dc3545' : '#28a745'; ?>; 
                 border-radius: 4px; 
                 margin: 20px 0;">
                <h2 style="margin: 0 0 10px 0; color: <?php echo $has_critical ? '#721c24' : '#155724'; ?>;">
                    <?php echo $has_critical ? '⚠️' : '✅'; ?>
                    <?php 
                    if ($has_critical) {
                        _e('נמצאו בעיות קריטיות', 'wordpress-ai-assistant');
                    } else {
                        _e('המערכת תקינה ומוכנה לעבודה', 'wordpress-ai-assistant');
                    }
                    ?>
                </h2>
                <p style="margin: 0; color: <?php echo $has_critical ? '#721c24' : '#155724'; ?>;">
                    <?php
                    if ($has_critical) {
                        _e('יש לטפל בתלויות הנדרשות כדי שהתוסף יוכל לפעול כראוי.', 'wordpress-ai-assistant');
                    } else {
                        _e('כל התלויות הנדרשות מותקנות. התוסף מוכן לשימוש מלא.', 'wordpress-ai-assistant');
                    }
                    ?>
                </p>
            </div>

            <!-- סביבת השרת -->
            <div class="at-ai-server-info" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
                <h2><?php _e('פרטי סביבת השרת', 'wordpress-ai-assistant'); ?></h2>
                <table class="widefat" style="margin-top: 15px;">
                    <tbody>
                        <tr>
                            <td style="width: 30%; font-weight: bold;"><?php _e('גרסת WordPress', 'wordpress-ai-assistant'); ?></td>
                            <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;"><?php _e('גרסת PHP', 'wordpress-ai-assistant'); ?></td>
                            <td><?php echo esc_html(PHP_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;"><?php _e('גרסת התוסף', 'wordpress-ai-assistant'); ?></td>
                            <td><?php echo esc_html(WORDPRESS_AI_ASSISTANT_VERSION); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;"><?php _e('סביבת שרת', 'wordpress-ai-assistant'); ?></td>
                            <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;"><?php _e('זיכרון PHP זמין', 'wordpress-ai-assistant'); ?></td>
                            <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                        </tr>
                        <?php
                        // בדיקת תמיכה בשפות מרובות
                        $is_multilingual = false;
                        $multilingual_plugin = '';
                        $current_language = '';
                        $default_language = '';
                        
                        // בדיקת WPML
                        if (defined('ICL_SITEPRESS_VERSION')) {
                            $is_multilingual = true;
                            $multilingual_plugin = 'WPML';
                            if (function_exists('wpml_get_current_language')) {
                                $current_language = wpml_get_current_language();
                            } elseif (function_exists('icl_get_languages')) {
                                $languages = icl_get_languages();
                                if (!empty($languages)) {
                                    foreach ($languages as $lang) {
                                        if ($lang['active']) {
                                            $current_language = $lang['code'];
                                            break;
                                        }
                                    }
                                }
                            }
                            if (function_exists('wpml_get_default_language')) {
                                $default_language = wpml_get_default_language();
                            }
                        }
                        // בדיקת Polylang
                        elseif (function_exists('pll_current_language') || defined('POLYLANG_VERSION')) {
                            $is_multilingual = true;
                            $multilingual_plugin = 'Polylang';
                            if (function_exists('pll_current_language')) {
                                $current_language = pll_current_language();
                            }
                            if (function_exists('pll_default_language')) {
                                $default_language = pll_default_language();
                            }
                        }
                        
                        // אם לא נמצא תוסף רב-לשוני, נבדוק את השפה הנוכחית של WordPress
                        if (!$is_multilingual) {
                            $locale = get_locale();
                            if ($locale) {
                                $current_language = substr($locale, 0, 2);
                                $locale_parts = explode('_', $locale);
                                if (count($locale_parts) > 1) {
                                    $current_language = $locale_parts[0] . ' (' . $locale . ')';
                                } else {
                                    $current_language = $locale;
                                }
                            } else {
                                $current_language = __('לא מוגדר', 'wordpress-ai-assistant');
                            }
                        }
                        ?>
                        <tr>
                            <td style="font-weight: bold;"><?php _e('תמיכה בשפות מרובות', 'wordpress-ai-assistant'); ?></td>
                            <td>
                                <?php if ($is_multilingual): ?>
                                    <?php echo esc_html(sprintf(__('כן (%s)', 'wordpress-ai-assistant'), $multilingual_plugin)); ?>
                                <?php else: ?>
                                    <?php _e('לא', 'wordpress-ai-assistant'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold;"><?php _e('שפה נוכחית', 'wordpress-ai-assistant'); ?></td>
                            <td>
                                <?php 
                                if ($is_multilingual && $current_language) {
                                    $lang_name = at_ai_assistant_get_language_name($current_language);
                                    echo esc_html(sprintf('%s (%s)', $lang_name, $current_language));
                                    if ($default_language && $current_language !== $default_language) {
                                        echo ' <span style="color: #666; font-size: 12px;">(' . esc_html(sprintf(__('ברירת מחדל: %s', 'wordpress-ai-assistant'), $default_language)) . ')</span>';
                                    }
                                } else {
                                    // אם current_language הוא בפורמט "he (he_IL)", נחלץ רק את הקוד
                                    $lang_code = $current_language;
                                    if (strpos($current_language, ' (') !== false) {
                                        $lang_code = substr($current_language, 0, strpos($current_language, ' ('));
                                    } else {
                                        $lang_code = substr($current_language, 0, 2);
                                    }
                                    $lang_name = at_ai_assistant_get_language_name($lang_code);
                                    echo esc_html($lang_name . ' (' . $current_language . ')');
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- תלויות נדרשות -->
            <?php if (!empty($required_deps)): ?>
            <div class="at-ai-dependencies-section" style="margin-bottom: 30px;">
                <h2><?php _e('תלויות נדרשות', 'wordpress-ai-assistant'); ?></h2>
                <p class="description"><?php _e('רכיבים שחובה להתקין כדי שהתוסף יפעל', 'wordpress-ai-assistant'); ?></p>
                
                <?php $this->render_dependencies_table($required_deps); ?>
            </div>
            <?php endif; ?>

            <!-- תלויות מומלצות -->
            <?php if (!empty($recommended_deps)): ?>
            <div class="at-ai-dependencies-section" style="margin-bottom: 30px;">
                <h2><?php _e('תלויות מומלצות', 'wordpress-ai-assistant'); ?></h2>
                <p class="description"><?php _e('רכיבים שישפרו את הביצועים והיכולות של התוסף', 'wordpress-ai-assistant'); ?></p>
                
                <?php $this->render_dependencies_table($recommended_deps); ?>
            </div>
            <?php endif; ?>

            <!-- תלויות אופציונליות -->
            <?php if (!empty($optional_deps)): ?>
            <div class="at-ai-dependencies-section" style="margin-bottom: 30px;">
                <h2><?php _e('תלויות אופציונליות', 'wordpress-ai-assistant'); ?></h2>
                <p class="description"><?php _e('רכיבים נוספים שיכולים לשפר חוויה ספציפית', 'wordpress-ai-assistant'); ?></p>
                
                <?php $this->render_dependencies_table($optional_deps); ?>
            </div>
            <?php endif; ?>

            <!-- הנחיות כלליות -->
            <div class="at-ai-guidelines" style="background: #e7f3ff; padding: 20px; border-right: 3px solid #0073aa; border-radius: 4px;">
                <h3 style="margin-top: 0;"><?php _e('💡 טיפים להתקנה', 'wordpress-ai-assistant'); ?></h3>
                <ul style="margin-bottom: 0;">
                    <li><?php _e('רוב הרכיבים הנדרשים (PHP Extensions) צריכים להיות מותקנים על ידי מנהל השרת.', 'wordpress-ai-assistant'); ?></li>
                    <li><?php _e('ספריות חיצוניות כמו Feature API ניתן להתקין דרך Composer או באופן ידני.', 'wordpress-ai-assistant'); ?></li>
                    <li><?php _e('אם אינך בטוח איך להתקין רכיב מסוים, לחץ על הכפתור "הוראות התקנה" ליד הרכיב.', 'wordpress-ai-assistant'); ?></li>
                    <li><?php _e('במקרה של בעיה, צור קשר עם מנהל השרת או צוות התמיכה.', 'wordpress-ai-assistant'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render dependencies table
     * 
     * @param array $dependencies
     */
    private function render_dependencies_table($dependencies) {
        ?>
        <table class="wp-list-table widefat fixed striped" style="margin-top: 15px;">
            <thead>
                <tr>
                    <th style="width: 30px;"></th>
                    <th style="width: 25%;"><?php _e('שם', 'wordpress-ai-assistant'); ?></th>
                    <th style="width: 15%;"><?php _e('סוג', 'wordpress-ai-assistant'); ?></th>
                    <th style="width: 15%;"><?php _e('סטטוס', 'wordpress-ai-assistant'); ?></th>
                    <th><?php _e('תיאור', 'wordpress-ai-assistant'); ?></th>
                    <th style="width: 150px;"><?php _e('פעולות', 'wordpress-ai-assistant'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dependencies as $key => $dep): ?>
                <tr style="<?php echo !$dep['available'] ? 'background-color: #fff3cd;' : ''; ?>">
                    <td style="text-align: center; font-size: 20px;">
                        <?php echo $dep['available'] ? '✅' : '❌'; ?>
                    </td>
                    <td>
                        <strong><?php echo esc_html($dep['name']); ?></strong>
                    </td>
                    <td>
                        <span class="at-ai-badge" style="display: inline-block; padding: 3px 8px; background: #f0f0f0; border-radius: 3px; font-size: 11px;">
                            <?php echo esc_html(ucfirst($dep['type'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($dep['available']): ?>
                            <span style="color: #28a745; font-weight: bold;">
                                <?php _e('מותקן', 'wordpress-ai-assistant'); ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #dc3545; font-weight: bold;">
                                <?php _e('חסר', 'wordpress-ai-assistant'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size: 13px; color: #666;">
                            <?php echo esc_html($dep['description']); ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!$dep['available'] && !empty($dep['docs_url'])): ?>
                            <a href="<?php echo esc_url($dep['docs_url']); ?>" 
                               class="button button-small button-primary" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <?php _e('הוראות התקנה', 'wordpress-ai-assistant'); ?>
                            </a>
                        <?php elseif ($dep['available']): ?>
                            <span style="color: #28a745;">✓ פעיל</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * AJAX handler for testing API connection
     */
    public function test_api_connection() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('אין הרשאות מספיקות', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_admin_nonce')) {
            wp_send_json_error(__('בדיקת אבטחה נכשלה', 'wordpress-ai-assistant'));
        }

        $provider = sanitize_text_field($_POST['provider'] ?? '');

        if (empty($provider)) {
            wp_send_json_error(__('ספק לא צוין', 'wordpress-ai-assistant'));
        }

        $result = $this->ai_manager->test_connection($provider);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success(__('חיבור הצליח!', 'wordpress-ai-assistant'));
    }

    /**
     * Display chat test page
     */
    public function display_chat_test_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('בדיקת צ\'אט AI', 'wordpress-ai-assistant'); ?></h1>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; margin: 20px 0;">
                <h2><?php _e('סטטוס הצ\'אט', 'wordpress-ai-assistant'); ?></h2>
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><strong><?php _e('צ\'אט מופעל', 'wordpress-ai-assistant'); ?>:</strong></td>
                            <td><?php echo at_ai_assistant_get_option('chat_enabled', false) ? '✅ ' . __('כן', 'wordpress-ai-assistant') : '❌ ' . __('לא', 'wordpress-ai-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('מופעל באדמין', 'wordpress-ai-assistant'); ?>:</strong></td>
                            <td><?php echo at_ai_assistant_get_option('chat_enabled_in_admin', true) ? '✅ ' . __('כן', 'wordpress-ai-assistant') : '❌ ' . __('לא', 'wordpress-ai-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('מופעל לכל המשתמשים', 'wordpress-ai-assistant'); ?>:</strong></td>
                            <td><?php echo at_ai_assistant_get_option('chat_enabled_for_all', false) ? '✅ ' . __('כן', 'wordpress-ai-assistant') : '❌ ' . __('לא', 'wordpress-ai-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('מופעל לאורחים', 'wordpress-ai-assistant'); ?>:</strong></td>
                            <td><?php echo at_ai_assistant_get_option('chat_enabled_for_visitors', false) ? '✅ ' . __('כן', 'wordpress-ai-assistant') : '❌ ' . __('לא', 'wordpress-ai-assistant'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php _e('הצג בסרגל הניהול', 'wordpress-ai-assistant'); ?>:</strong></td>
                            <td><?php echo at_ai_assistant_get_option('chat_show_in_admin_bar', true) ? '✅ ' . __('כן', 'wordpress-ai-assistant') : '❌ ' . __('לא', 'wordpress-ai-assistant'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div style="background: #f0f8ff; padding: 20px; border: 1px solid #0073aa; border-radius: 4px; margin: 20px 0;">
                <h2><?php _e('שורטקוד לשימוש', 'wordpress-ai-assistant'); ?></h2>
                <code style="background: #fff; padding: 10px; display: block; margin: 10px 0;">[ai_chat position="fixed-bottom-right" theme="light"]</code>
                
                <h3><?php _e('פרמטרים זמינים', 'wordpress-ai-assistant'); ?>:</h3>
                <ul>
                    <li><strong>position:</strong> inline, fixed-bottom-right, fixed-bottom-left</li>
                    <li><strong>theme:</strong> light, dark, auto</li>
                    <li><strong>title:</strong> <?php _e('כותרת הצ\'אט', 'wordpress-ai-assistant'); ?></li>
                    <li><strong>height:</strong> <?php _e('גובה (למיקום inline)', 'wordpress-ai-assistant'); ?></li>
                    <li><strong>width:</strong> <?php _e('רוחב (למיקום inline)', 'wordpress-ai-assistant'); ?></li>
                </ul>
            </div>
            
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px;">
                <h2><?php _e('דוגמה חיה', 'wordpress-ai-assistant'); ?></h2>
                <p><?php _e('הצ\'אט אמור להופיע כאן:', 'wordpress-ai-assistant'); ?></p>
                
                <?php echo do_shortcode('[ai_chat position="inline" height="500px" title="' . __('עוזר AI - בדיקה', 'wordpress-ai-assistant') . '"]'); ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for generating text
     */
    public function generate_text() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('אין הרשאות מספיקות', 'wordpress-ai-assistant'));
        }

        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_admin_nonce')) {
            wp_send_json_error(__('בדיקת אבטחה נכשלה', 'wordpress-ai-assistant'));
        }

        $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
        $max_tokens = intval($_POST['max_tokens'] ?? 500);

        if (empty($prompt)) {
            wp_send_json_error(__('פרומפט לא יכול להיות ריק', 'wordpress-ai-assistant'));
        }

        $result = $this->ai_manager->generate_text($prompt, array('max_tokens' => $max_tokens));

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }
}
