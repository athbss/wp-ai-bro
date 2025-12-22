<?php
/**
 * AI Provider Base Class
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
 * Abstract AI Provider Class
 *
 * Base class for all AI providers
 */
abstract class AT_AI_Provider {

    /**
     * Provider name
     *
     * @var string
     */
    protected $name = '';

    /**
     * Provider display name
     *
     * @var string
     */
    protected $display_name = '';

    /**
     * API key
     *
     * @var string
     */
    protected $api_key = '';

    /**
     * Default model
     *
     * @var string
     */
    protected $default_model = '';

    /**
     * Available models
     *
     * @var array
     */
    protected $available_models = array();

    /**
     * Pricing per token (input/output)
     *
     * @var array
     */
    protected $pricing = array(
        'input' => 0,
        'output' => 0
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_credentials();
    }

    /**
     * Load provider credentials
     */
    protected function load_credentials() {
        $credentials = at_ai_assistant_get_option('ai_credentials', array());
        $this->api_key = isset($credentials[$this->name]['api_key']) ? $credentials[$this->name]['api_key'] : '';
    }

    /**
     * Get provider name
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get display name
     *
     * @return string
     */
    public function get_display_name() {
        return $this->display_name;
    }

    /**
     * Get available models
     *
     * @return array
     */
    public function get_available_models() {
        return $this->available_models;
    }

    /**
     * Get current model
     *
     * @return string
     */
    public function get_current_model() {
        return at_ai_assistant_get_option($this->name . '_model', $this->default_model);
    }

    /**
     * Set API key
     *
     * @param string $api_key
     */
    public function set_api_key($api_key) {
        $this->api_key = $api_key;
        $credentials = at_ai_assistant_get_option('ai_credentials', array());
        $credentials[$this->name]['api_key'] = $api_key;
        at_ai_assistant_update_option('ai_credentials', $credentials);
    }

    /**
     * Test connection to provider
     *
     * @return bool|WP_Error
     */
    public function test_connection() {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('API key is required', 'wordpress-ai-assistant'));
        }

        return $this->make_test_request();
    }

    /**
     * Make test request to provider
     *
     * @return bool|WP_Error
     */
    abstract protected function make_test_request();

    /**
     * Generate text
     *
     * @param string $prompt
     * @param array $options
     * @return array|WP_Error
     */
    abstract public function generate_text($prompt, $options = array());

    /**
     * Analyze image
     *
     * @param string $image_url
     * @param array $options
     * @return array|WP_Error
     */
    abstract public function analyze_image($image_url, $options = array());

    /**
     * Generate image from text
     *
     * @param string $prompt
     * @param array $options
     * @return array|WP_Error
     */
    public function generate_image($prompt, $options = array()) {
        return new WP_Error('not_implemented', __('Image generation not supported by this provider', 'wordpress-ai-assistant'));
    }

    /**
     * Calculate usage cost
     *
     * @param array $usage
     * @return float
     */
    public function calculate_cost($usage) {
        $input_tokens = isset($usage['input_tokens']) ? $usage['input_tokens'] : 0;
        $output_tokens = isset($usage['output_tokens']) ? $usage['output_tokens'] : 0;

        $input_cost = $input_tokens * $this->pricing['input'];
        $output_cost = $output_tokens * $this->pricing['output'];

        return $input_cost + $output_cost;
    }

    /**
     * Make HTTP request to API
     *
     * @param string $url
     * @param array $args
     * @return array|WP_Error
     */
    protected function make_request($url, $args = array()) {
        $default_args = array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
        );

        $args = wp_parse_args($args, $default_args);

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(__('API request failed with code %d: %s', 'wordpress-ai-assistant'), $response_code, $response_body)
            );
        }

        $data = json_decode($response_body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_decode_error', __('Failed to decode API response', 'wordpress-ai-assistant'));
        }

        return $data;
    }
}
