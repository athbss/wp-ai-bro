<?php
/**
 * AI Manager - Central AI Services Management
 *
 * @since      1.1.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes/ai
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Manager Class
 *
 * Central manager for all AI services and providers
 */
class AT_AI_Manager {

    /**
     * Single instance of the class
     *
     * @var AT_AI_Manager
     */
    private static $instance = null;

    /**
     * Available AI providers
     *
     * @var array
     */
    private $providers = array();

    /**
     * Current active provider
     *
     * @var string
     */
    private $active_provider = 'openai';

    /**
     * Usage tracker instance
     *
     * @var AT_AI_Usage_Tracker
     */
    private $usage_tracker;

    /**
     * Get singleton instance
     *
     * @return AT_AI_Manager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_providers();
        $this->usage_tracker = new AT_AI_Usage_Tracker();
        $this->active_provider = at_ai_assistant_get_option('active_provider', 'openai');
    }

    /**
     * Initialize AI providers
     */
    private function init_providers() {
        // Initialize available providers
        $this->providers['openai'] = new AT_OpenAI_Provider();
        $this->providers['anthropic'] = new AT_Anthropic_Provider();
        $this->providers['google'] = new AT_Google_Provider();
    }

    /**
     * Get available providers
     *
     * @return array
     */
    public function get_providers() {
        return array_keys($this->providers);
    }

    /**
     * Get provider instance
     *
     * @param string $provider_name
     * @return AT_AI_Provider|null
     */
    public function get_provider($provider_name = null) {
        if (null === $provider_name) {
            $provider_name = $this->active_provider;
        }

        return isset($this->providers[$provider_name]) ? $this->providers[$provider_name] : null;
    }

    /**
     * Set active provider
     *
     * @param string $provider_name
     * @return bool
     */
    public function set_active_provider($provider_name) {
        if (!isset($this->providers[$provider_name])) {
            return false;
        }

        $this->active_provider = $provider_name;
        at_ai_assistant_update_option('active_provider', $provider_name);
        return true;
    }

    /**
     * Generate text using AI
     *
     * @param string $prompt
     * @param array $options
     * @param int $post_id Optional post ID for language context
     * @return array|WP_Error
     */
    public function generate_text($prompt, $options = array(), $post_id = null) {
        $provider = $this->get_provider();
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        // Get global prompt instructions
        $general_instructions = at_ai_assistant_get_option('general_prompt_instructions', '');
        if (!empty($general_instructions)) {
            $prompt = $general_instructions . "\n\n" . $prompt;
        }

        // Add language context if post ID is provided
        if ($post_id) {
            $prompt = at_ai_assistant_build_language_aware_prompt($prompt, $post_id);
        }

        $result = $provider->generate_text($prompt, $options);

        if (!is_wp_error($result)) {
            $this->usage_tracker->track_usage($this->active_provider, 'text_generation', $result);
            
            // Emit suite event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_text_generated', array(
                    'provider' => $this->active_provider,
                    'model'    => isset($result['model']) ? $result['model'] : 'unknown',
                    'tokens'   => isset($result['usage']['total_tokens']) ? $result['usage']['total_tokens'] : 0,
                    'post_id'  => $post_id
                ));
            }
        } else {
            // Emit error event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_error', array(
                    'provider'      => $this->active_provider,
                    'method'        => 'generate_text',
                    'error_message' => $result->get_error_message()
                ));
            }
        }

        return $result;
    }

    /**
     * Chat completion using AI
     *
     * @param array $messages Array of messages with role and content
     * @param array $options Additional options
     * @return array|WP_Error
     * @since 1.3.0
     */
    public function chat_completion($messages, $options = array()) {
        $provider = $this->get_provider();
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        // Check if provider supports chat completion
        if (method_exists($provider, 'chat_completion')) {
            $result = $provider->chat_completion($messages, $options);
        } else {
            // Fallback to text generation for providers that don't support chat
            $prompt = $this->convert_messages_to_prompt($messages);
            $result = $provider->generate_text($prompt, $options);
        }

        if (!is_wp_error($result)) {
            $this->usage_tracker->track_usage($this->active_provider, 'chat', $result);

            // Emit suite event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_chat_completed', array(
                    'provider'       => $this->active_provider,
                    'model'          => isset($result['model']) ? $result['model'] : 'unknown',
                    'messages_count' => count($messages)
                ));
            }
        } else {
            // Emit error event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_error', array(
                    'provider'      => $this->active_provider,
                    'method'        => 'chat_completion',
                    'error_message' => $result->get_error_message()
                ));
            }
        }

        return $result;
    }

    /**
     * Convert chat messages to prompt for providers without chat support
     *
     * @param array $messages
     * @return string
     */
    private function convert_messages_to_prompt($messages) {
        $prompt = '';
        
        foreach ($messages as $message) {
            $role = $message['role'];
            $content = $message['content'];
            
            if ($role === 'system') {
                $prompt .= "System: " . $content . "\n\n";
            } elseif ($role === 'user') {
                $prompt .= "User: " . $content . "\n\n";
            } elseif ($role === 'assistant') {
                $prompt .= "Assistant: " . $content . "\n\n";
            }
        }
        
        $prompt .= "Assistant: ";
        
        return trim($prompt);
    }

    /**
     * Generate image from text
     *
     * @param string $prompt
     * @param array $options
     * @return array|WP_Error
     */
    public function generate_image($prompt, $options = array()) {
        $provider = $this->get_provider();
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        // Check if provider supports image generation
        if (!method_exists($provider, 'generate_image')) {
            return new WP_Error('image_generation_not_supported', __('Image generation not supported by current provider', 'wordpress-ai-assistant'));
        }

        $result = $provider->generate_image($prompt, $options);

        if (!is_wp_error($result)) {
            $this->usage_tracker->track_usage($this->active_provider, 'image_generation', $result);

            // Emit suite event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_image_generated', array(
                    'provider' => $this->active_provider,
                    'prompt'   => $prompt
                ));
            }
        } else {
            // Emit error event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_error', array(
                    'provider'      => $this->active_provider,
                    'method'        => 'generate_image',
                    'error_message' => $result->get_error_message()
                ));
            }
        }

        return $result;
    }

    /**
     * Analyze image using AI
     *
     * @param string $image_url
     * @param array $options
     * @return array|WP_Error
     */
    public function analyze_image($image_url, $options = array()) {
        $provider = $this->get_provider();
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        $result = $provider->analyze_image($image_url, $options);

        if (!is_wp_error($result)) {
            $this->usage_tracker->track_usage($this->active_provider, 'image_analysis', $result);

            // Emit suite event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_image_analyzed', array(
                    'provider'  => $this->active_provider,
                    'image_url' => $image_url
                ));
            }
        } else {
            // Emit error event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_error', array(
                    'provider'      => $this->active_provider,
                    'method'        => 'analyze_image',
                    'error_message' => $result->get_error_message()
                ));
            }
        }

        return $result;
    }

    /**
     * Translate text using AI
     *
     * @param string $text
     * @param string $target_language
     * @param string $source_language
     * @param array $context
     * @param int $post_id Optional post ID for language context
     * @return string|WP_Error
     */
    public function translate_text($text, $target_language, $source_language = 'auto', $context = array(), $post_id = null) {
        $provider = $this->get_provider();
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        $prompt = $this->build_translation_prompt($text, $target_language, $source_language, $context, $post_id);
        $result = $provider->generate_text($prompt, array('max_tokens' => 1000));

        if (!is_wp_error($result)) {
            $this->usage_tracker->track_usage($this->active_provider, 'translation', $result);

            // Emit suite event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_text_translated', array(
                    'provider'        => $this->active_provider,
                    'target_language' => $target_language
                ));
            }

            return $result['text'];
        } else {
            // Emit error event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_error', array(
                    'provider'      => $this->active_provider,
                    'method'        => 'translate_text',
                    'error_message' => $result->get_error_message()
                ));
            }
        }

        return $result;
    }

    /**
     * Build translation prompt
     *
     * @param string $text
     * @param string $target_language
     * @param string $source_language
     * @param array $context
     * @param int $post_id Optional post ID for language context
     * @return string
     */
    private function build_translation_prompt($text, $target_language, $source_language, $context, $post_id = null) {
        $target_lang_name = at_ai_assistant_get_language_name($target_language);
        $prompt = sprintf(__('Translate the following text to %s (%s)', 'wordpress-ai-assistant'), $target_lang_name, $target_language);

        if ($source_language !== 'auto') {
            $source_lang_name = at_ai_assistant_get_language_name($source_language);
            $prompt .= sprintf(__(' from %s (%s)', 'wordpress-ai-assistant'), $source_lang_name, $source_language);
        }

        if (!empty($context)) {
            $prompt .= ". " . __('Context:', 'wordpress-ai-assistant') . " " . implode(', ', $context);
        }

        // Add language context if post ID is provided
        if ($post_id) {
            $post_lang = at_ai_assistant_get_post_language($post_id);
            if ($post_lang && $post_lang !== $target_language) {
                $prompt .= ". " . sprintf(__('The post is in %s, ensure the translation maintains the same tone and style.', 'wordpress-ai-assistant'), at_ai_assistant_get_language_name($post_lang));
            }
        }

        $prompt .= ":\n\n{$text}\n\n" . __('Translation:', 'wordpress-ai-assistant');

        return $prompt;
    }

    /**
     * Generate tags for content
     *
     * @param string $content
     * @param array $existing_taxonomies
     * @param string $language_code Optional language code for the content
     * @return array|WP_Error
     */
    public function generate_tags($content, $existing_taxonomies = array(), $language_code = null) {
        $provider = $this->get_provider();
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        $prompt = $this->build_tagging_prompt($content, $existing_taxonomies, $language_code);
        $result = $provider->generate_text($prompt, array('max_tokens' => 500));

        if (!is_wp_error($result)) {
            $this->usage_tracker->track_usage($this->active_provider, 'tagging', $result);
            
            $tags = $this->parse_tags_from_response($result['text']);

            // Emit suite event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_tags_generated', array(
                    'provider'   => $this->active_provider,
                    'tags_count' => count($tags['tags']) + count($tags['categories'])
                ));
            }

            return $tags;
        } else {
            // Emit error event
            if (function_exists('at_ai_suite_emit')) {
                at_ai_suite_emit('ai_error', array(
                    'provider'      => $this->active_provider,
                    'method'        => 'generate_tags',
                    'error_message' => $result->get_error_message()
                ));
            }
        }

        return $result;
    }

    /**
     * Build tagging prompt
     *
     * @param string $content
     * @param array $existing_taxonomies
     * @param string $language_code Optional language code for the content
     * @return string
     */
    private function build_tagging_prompt($content, $existing_taxonomies, $language_code = null) {
        $lang_instruction = '';
        if ($language_code) {
            $lang_name = at_ai_assistant_get_language_name($language_code);
            $lang_instruction = sprintf(__('IMPORTANT: The content is in %s (%s). Generate tags and categories in the same language.', 'wordpress-ai-assistant'), $lang_name, $language_code) . "\n\n";
        }

        $prompt = $lang_instruction . __('Analyze the following content and suggest relevant tags and categories.', 'wordpress-ai-assistant') . "\n\n";
        $prompt .= __('Content:', 'wordpress-ai-assistant') . " {$content}\n\n";

        if (!empty($existing_taxonomies)) {
            $prompt .= __('Available taxonomies:', 'wordpress-ai-assistant') . "\n";
            foreach ($existing_taxonomies as $taxonomy => $terms) {
                $prompt .= "- {$taxonomy}: " . implode(', ', $terms) . "\n";
            }
        }

        $prompt .= "\n" . __('Return ONLY valid JSON. Do not add any text before or after JSON.', 'wordpress-ai-assistant') . "\n";
        $prompt .= "{\n";
        $prompt .= '  "taxonomies": {' . "\n";
        $prompt .= '    "post_tag": ["tag1", "tag2"],' . "\n";
        $prompt .= '    "category": ["category1"]' . "\n";
        $prompt .= "  },\n";
        $prompt .= '  "audience": ["audience1", "audience2"]' . "\n";
        $prompt .= "}\n\n";
        $prompt .= __('Rules:', 'wordpress-ai-assistant') . "\n";
        $prompt .= "- " . __('Prefer terms from available taxonomies list when relevant.', 'wordpress-ai-assistant') . "\n";
        $prompt .= "- " . __('Only include taxonomies that are provided in the available taxonomies list.', 'wordpress-ai-assistant') . "\n";
        $prompt .= "- " . __('If a taxonomy has no suitable terms, return an empty array for it.', 'wordpress-ai-assistant') . "\n";
        $prompt .= "- " . __('Keep terms concise and avoid duplicates.', 'wordpress-ai-assistant');

        return $prompt;
    }

    /**
     * Parse tags from AI response
     *
     * @param string $response
     * @return array
     */
    private function parse_tags_from_response($response) {
        $tags = array(
            'tags' => array(),
            'categories' => array(),
            'audience' => array(),
            'taxonomies' => array(),
        );

        // Preferred path: parse JSON response.
        $decoded = json_decode(trim($response), true);
        if (!is_array($decoded)) {
            // Handle extra text before/after JSON.
            $start = strpos($response, '{');
            $end = strrpos($response, '}');
            if ($start !== false && $end !== false && $end > $start) {
                $decoded = json_decode(substr($response, $start, $end - $start + 1), true);
            }
        }

        if (is_array($decoded)) {
            if (!empty($decoded['taxonomies']) && is_array($decoded['taxonomies'])) {
                foreach ($decoded['taxonomies'] as $taxonomy => $terms) {
                    if (!is_string($taxonomy) || !is_array($terms)) {
                        continue;
                    }
                    $sanitized_terms = $this->sanitize_term_list($terms);
                    if (!empty($sanitized_terms)) {
                        $tags['taxonomies'][$taxonomy] = $sanitized_terms;
                    }
                }
            }

            if (!empty($decoded['tags']) && is_array($decoded['tags'])) {
                $tags['tags'] = $this->sanitize_term_list($decoded['tags']);
            }

            if (!empty($decoded['categories']) && is_array($decoded['categories'])) {
                $tags['categories'] = $this->sanitize_term_list($decoded['categories']);
            }

            if (!empty($decoded['audience']) && is_array($decoded['audience'])) {
                $tags['audience'] = $this->sanitize_term_list($decoded['audience']);
            }

            // Backward compatibility: mirror common taxonomies.
            if (empty($tags['tags']) && !empty($tags['taxonomies']['post_tag'])) {
                $tags['tags'] = $tags['taxonomies']['post_tag'];
            }
            if (empty($tags['categories']) && !empty($tags['taxonomies']['category'])) {
                $tags['categories'] = $tags['taxonomies']['category'];
            }

            return $tags;
        }

        // Fallback path for non-JSON models: tolerant parsing for EN/HE labels.
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            $line = trim(ltrim($line, "-* \t"));
            if ($line === '') {
                continue;
            }

            if (preg_match('/^(Tags|תגיות)\s*:\s*(.+)$/iu', $line, $matches)) {
                $tags['tags'] = $this->sanitize_term_list(explode(',', $matches[2]));
                continue;
            }

            if (preg_match('/^(Categories|קטגוריות)\s*:\s*(.+)$/iu', $line, $matches)) {
                $tags['categories'] = $this->sanitize_term_list(explode(',', $matches[2]));
                continue;
            }

            if (preg_match('/^(Target Audience|Audience|קהל יעד)\s*:\s*(.+)$/iu', $line, $matches)) {
                $tags['audience'] = $this->sanitize_term_list(explode(',', $matches[2]));
                continue;
            }

            // Generic taxonomy line: taxonomy_slug: term1, term2
            if (preg_match('/^([a-z0-9_\-]+)\s*:\s*(.+)$/i', $line, $matches)) {
                $taxonomy = sanitize_key($matches[1]);
                $terms = $this->sanitize_term_list(explode(',', $matches[2]));
                if (!empty($taxonomy) && !empty($terms)) {
                    $tags['taxonomies'][$taxonomy] = $terms;
                }
            }
        }

        if (empty($tags['tags']) && !empty($tags['taxonomies']['post_tag'])) {
            $tags['tags'] = $tags['taxonomies']['post_tag'];
        }
        if (empty($tags['categories']) && !empty($tags['taxonomies']['category'])) {
            $tags['categories'] = $tags['taxonomies']['category'];
        }

        return $tags;
    }

    /**
     * Sanitize a terms list.
     *
     * @param array $terms
     * @return array
     */
    private function sanitize_term_list($terms) {
        if (!is_array($terms)) {
            return array();
        }

        $sanitized = array();
        foreach ($terms as $term) {
            if (!is_scalar($term)) {
                continue;
            }
            $clean = trim(wp_strip_all_tags((string) $term));
            if ($clean !== '') {
                $sanitized[] = $clean;
            }
        }

        return array_values(array_unique($sanitized));
    }

    /**
     * Get usage statistics
     *
     * @param string $period
     * @return array
     */
    public function get_usage_stats($period = 'month') {
        return $this->usage_tracker->get_stats($period);
    }

    /**
     * Get cost summary
     *
     * @param string $period
     * @return array
     */
    public function get_cost_summary($period = 'month') {
        return $this->usage_tracker->get_cost_summary($period);
    }

    /**
     * Test provider connection
     *
     * @param string $provider_name
     * @return bool|WP_Error
     */
    public function test_connection($provider_name = null) {
        if (null === $provider_name) {
            $provider_name = $this->active_provider;
        }

        $provider = $this->get_provider($provider_name);
        if (!$provider) {
            return new WP_Error('ai_provider_not_found', __('AI provider not found', 'wordpress-ai-assistant'));
        }

        return $provider->test_connection();
    }
}
