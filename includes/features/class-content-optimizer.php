<?php
/**
 * Content Optimizer - Advanced AI features for posts
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
 * Content Optimizer Class
 * 
 * Advanced AI features for content:
 * - Smart taxonomy tagging (from existing taxonomies)
 * - SEO/AEO optimization
 * - Detailed logging
 */
class AT_Content_Optimizer {

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

        // AJAX handlers
        add_action('wp_ajax_at_ai_suggest_taxonomies', array($this, 'ajax_suggest_taxonomies'));
        add_action('wp_ajax_at_ai_apply_taxonomy_suggestions', array($this, 'ajax_apply_taxonomy_suggestions'));
        add_action('wp_ajax_at_ai_optimize_content', array($this, 'ajax_optimize_content'));
    }

    /**
     * Add optimizer meta box
     * 
     * Note: We don't add a separate meta box anymore.
     * The functionality is integrated into the existing AT_WordPress_AI_Assistant_Admin meta box.
     */
    public function add_optimizer_meta_box() {
        // Functionality moved to admin class
        // This method kept for backwards compatibility
    }

    /**
     * Render optimizer meta box
     *
     * @param WP_Post $post
     */
    public function render_optimizer_meta_box($post) {
        // This method is no longer used as functionality is integrated in admin meta box
        // Kept for backwards compatibility
    }


    /**
     * AJAX: Suggest taxonomies
     */
    public function ajax_suggest_taxonomies() {
        $this->verify_ajax_request();

        $post_id = intval($_POST['post_id'] ?? 0);
        $selected_taxonomies = $_POST['taxonomies'] ?? array();

        if (!$post_id) {
            wp_send_json_error(__('מזהה פוסט לא תקין', 'wordpress-ai-assistant'));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('פוסט לא נמצא', 'wordpress-ai-assistant'));
        }

        // Extract content
        $content = $this->extract_post_content($post);
        if (empty($content)) {
            wp_send_json_error(__('אין תוכן לניתוח', 'wordpress-ai-assistant'));
        }

        // Get existing terms for selected taxonomies
        $existing_terms = array();
        foreach ($selected_taxonomies as $taxonomy) {
            $terms = get_terms(array(
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'number' => 50, // Limit per taxonomy
            ));

            if (!is_wp_error($terms) && !empty($terms)) {
                $existing_terms[$taxonomy] = wp_list_pluck($terms, 'name', 'term_id');
            }
        }

        // Build prompt
        $prompt = $this->build_taxonomy_prompt($content, $existing_terms, $post_id);

        // Log start
        $start_time = microtime(true);

        // Generate suggestions
        $result = $this->ai_manager->generate_text($prompt, array('max_tokens' => 500), $post_id);

        if (is_wp_error($result)) {
            $this->log_ai_action('taxonomy_suggestion', $post_id, $prompt, null, $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        // Parse suggestions
        $suggestions = $this->parse_taxonomy_suggestions($result['text'], $existing_terms);

        // Log success
        $this->log_ai_action('taxonomy_suggestion', $post_id, $prompt, $result, null, microtime(true) - $start_time);

        wp_send_json_success(array(
            'suggestions' => $suggestions,
            'usage' => $result['usage'],
            'message' => __('הצעות נוצרו בהצלחה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX: Apply taxonomy suggestions
     */
    public function ajax_apply_taxonomy_suggestions() {
        $this->verify_ajax_request();

        $post_id = intval($_POST['post_id'] ?? 0);
        $suggestions = $_POST['suggestions'] ?? array();

        if (!$post_id) {
            wp_send_json_error(__('מזהה פוסט לא תקין', 'wordpress-ai-assistant'));
        }

        $applied = array();

        foreach ($suggestions as $taxonomy => $term_ids) {
            if (!taxonomy_exists($taxonomy)) {
                continue;
            }

            // Convert term IDs to integers
            $term_ids = array_map('intval', $term_ids);

            // Apply terms (append mode)
            $result = wp_set_object_terms($post_id, $term_ids, $taxonomy, true);

            if (!is_wp_error($result)) {
                $applied[$taxonomy] = count($term_ids);
            }
        }

        // Log application
        at_ai_assistant_log('taxonomy_applied', 'success', __('תגיות והקטגוריות הוחלו', 'wordpress-ai-assistant'), array(
            'post_id' => $post_id,
            'applied' => $applied,
        ), $post_id);

        wp_send_json_success(array(
            'applied' => $applied,
            'message' => __('התגיות והקטגוריות הוחלו בהצלחה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * AJAX: Optimize content for SEO/AEO
     */
    public function ajax_optimize_content() {
        $this->verify_ajax_request();

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(__('מזהה פוסט לא תקין', 'wordpress-ai-assistant'));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('פוסט לא נמצא', 'wordpress-ai-assistant'));
        }

        // Extract content
        $content = $this->extract_post_content($post);
        if (empty($content)) {
            wp_send_json_error(__('אין תוכן לניתוח', 'wordpress-ai-assistant'));
        }

        // Build optimization prompt
        $prompt = $this->build_optimization_prompt($content, $post_id);

        // Log start
        $start_time = microtime(true);

        // Generate optimization suggestions
        $result = $this->ai_manager->generate_text($prompt, array('max_tokens' => 1000), $post_id);

        if (is_wp_error($result)) {
            $this->log_ai_action('content_optimization', $post_id, $prompt, null, $result->get_error_message());
            wp_send_json_error($result->get_error_message());
        }

        // Parse optimization suggestions
        $optimization = $this->parse_optimization_result($result['text']);

        // Log success
        $this->log_ai_action('content_optimization', $post_id, $prompt, $result, null, microtime(true) - $start_time);

        wp_send_json_success(array(
            'optimization' => $optimization,
            'usage' => $result['usage'],
            'message' => __('אופטימיזציה הושלמה', 'wordpress-ai-assistant'),
        ));
    }

    /**
     * Extract post content
     *
     * @param WP_Post $post
     * @return string
     */
    private function extract_post_content($post) {
        $content = '';

        if (!empty($post->post_title)) {
            $content .= "כותרת: " . $post->post_title . "\n\n";
        }

        if (!empty($post->post_content)) {
            $content .= "תוכן: " . wp_strip_all_tags($post->post_content);
        }

        if (!empty($post->post_excerpt)) {
            $content .= "\n\nתקציר: " . $post->post_excerpt;
        }

        return trim($content);
    }

    /**
     * Build taxonomy suggestion prompt
     *
     * @param string $content
     * @param array $existing_terms
     * @param int $post_id
     * @return string
     */
    private function build_taxonomy_prompt($content, $existing_terms, $post_id) {
        $post_language = at_ai_assistant_get_post_language($post_id);
        $lang_name = at_ai_assistant_get_language_name($post_language);

        $prompt = sprintf(
            __('IMPORTANT: The content is in %s. Analyze it and suggest the most relevant terms from the existing taxonomies.', 'wordpress-ai-assistant'),
            $lang_name
        ) . "\n\n";

        $prompt .= __('Content to analyze:', 'wordpress-ai-assistant') . "\n{$content}\n\n";

        $prompt .= __('Available taxonomies and terms:', 'wordpress-ai-assistant') . "\n";
        foreach ($existing_terms as $taxonomy => $terms) {
            $taxonomy_obj = get_taxonomy($taxonomy);
            $prompt .= $taxonomy_obj->labels->name . ":\n";
            foreach ($terms as $term_id => $term_name) {
                $prompt .= "  - {$term_name} (ID: {$term_id})\n";
            }
            $prompt .= "\n";
        }

        $prompt .= __('Instructions:', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('1. Select 3-7 most relevant terms for each taxonomy', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('2. Only choose from the existing terms listed above', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('3. Respond in this format:', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('[Taxonomy Name]: term1 (ID), term2 (ID), term3 (ID)', 'wordpress-ai-assistant') . "\n";

        return $prompt;
    }

    /**
     * Parse taxonomy suggestions from AI response
     *
     * @param string $response
     * @param array $existing_terms
     * @return array
     */
    private function parse_taxonomy_suggestions($response, $existing_terms) {
        $suggestions = array();
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Match pattern: [Taxonomy Name]: term1 (ID), term2 (ID)
            if (preg_match('/^([^:]+):\s*(.+)$/u', $line, $matches)) {
                $taxonomy_label = trim($matches[1]);
                $terms_str = trim($matches[2]);

                // Find taxonomy by label
                foreach ($existing_terms as $taxonomy => $terms) {
                    $taxonomy_obj = get_taxonomy($taxonomy);
                    if (stripos($taxonomy_obj->labels->name, $taxonomy_label) !== false) {
                        // Extract term IDs from the string
                        preg_match_all('/\((\d+)\)/u', $terms_str, $id_matches);
                        if (!empty($id_matches[1])) {
                            $suggestions[$taxonomy] = array_map('intval', $id_matches[1]);
                        }
                        break;
                    }
                }
            }
        }

        return $suggestions;
    }

    /**
     * Build optimization prompt
     *
     * @param string $content
     * @param int $post_id
     * @return string
     */
    private function build_optimization_prompt($content, $post_id) {
        $post_language = at_ai_assistant_get_post_language($post_id);
        $lang_name = at_ai_assistant_get_language_name($post_language);

        $prompt = sprintf(
            __('IMPORTANT: The content is in %s. Analyze it for SEO and AEO (Answer Engine Optimization) improvements.', 'wordpress-ai-assistant'),
            $lang_name
        ) . "\n\n";

        $prompt .= __('Content to optimize:', 'wordpress-ai-assistant') . "\n{$content}\n\n";

        $prompt .= __('Provide optimization suggestions in the following categories:', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('1. **Structure**: How to improve headings, paragraphs, and content organization', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('2. **Keywords**: Suggested keywords and phrases to add', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('3. **Readability**: Tips to improve clarity and readability', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('4. **Featured Snippets**: How to optimize for Google featured snippets', 'wordpress-ai-assistant') . "\n";
        $prompt .= __('5. **AI Answers**: How to optimize for AI-powered search engines (ChatGPT, Perplexity, etc.)', 'wordpress-ai-assistant') . "\n\n";

        $prompt .= __('Format each suggestion clearly with the category name followed by specific actionable recommendations.', 'wordpress-ai-assistant');

        return $prompt;
    }

    /**
     * Parse optimization result
     *
     * @param string $response
     * @return array
     */
    private function parse_optimization_result($response) {
        $categories = array(
            'structure' => __('מבנה', 'wordpress-ai-assistant'),
            'keywords' => __('מילות מפתח', 'wordpress-ai-assistant'),
            'readability' => __('קריאות', 'wordpress-ai-assistant'),
            'featured_snippets' => __('Featured Snippets', 'wordpress-ai-assistant'),
            'ai_answers' => __('תשובות AI', 'wordpress-ai-assistant'),
        );

        $optimization = array();
        $current_category = null;
        $current_content = '';

        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim($line);

            // Check if line is a category header
            foreach ($categories as $key => $label) {
                if (preg_match('/^\*?\*?' . preg_quote($label, '/') . '/ui', $line) || 
                    preg_match('/^\d+\.\s*\*?\*?' . preg_quote($label, '/') . '/ui', $line)) {
                    
                    // Save previous category
                    if ($current_category && $current_content) {
                        $optimization[$current_category] = trim($current_content);
                    }

                    $current_category = $key;
                    $current_content = '';
                    continue 2;
                }
            }

            // Add content to current category
            if ($current_category && !empty($line)) {
                $current_content .= $line . "\n";
            }
        }

        // Save last category
        if ($current_category && $current_content) {
            $optimization[$current_category] = trim($current_content);
        }

        return $optimization;
    }

    /**
     * Verify AJAX request
     */
    private function verify_ajax_request() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('אין הרשאות מספיקות', 'wordpress-ai-assistant'));
        }

        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'at_ai_content_optimizer')) {
            wp_send_json_error(__('בדיקת אבטחה נכשלה', 'wordpress-ai-assistant'));
        }
    }

    /**
     * Log AI action
     *
     * @param string $action_type
     * @param int $post_id
     * @param string $prompt
     * @param array|null $result
     * @param string|null $error
     * @param float|null $duration
     */
    private function log_ai_action($action_type, $post_id, $prompt, $result = null, $error = null, $duration = null) {
        $log_data = array(
            'action' => 'content_' . $action_type,
            'post_id' => $post_id,
            'prompt' => $prompt,
            'timestamp' => current_time('mysql'),
            'duration' => $duration,
        );

        if ($error) {
            $log_data['status'] = 'error';
            $log_data['error'] = $error;
        } else {
            $log_data['status'] = 'success';
            $log_data['response'] = $result['text'] ?? '';
            $log_data['usage'] = $result['usage'] ?? array();
            $log_data['model'] = $result['model'] ?? '';
        }

        at_ai_assistant_log('content_optimization', $log_data['status'], json_encode($log_data, JSON_UNESCAPED_UNICODE), $log_data, $post_id);
    }
}

