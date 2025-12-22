<?php
/**
 * Anthropic Provider
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
 * Anthropic Provider Class
 */
class AT_Anthropic_Provider extends AT_AI_Provider {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'anthropic';
        $this->display_name = 'Anthropic';
        $this->default_model = 'claude-sonnet-4-5';
        $this->available_models = array(
            // Claude 4.5 Series (Latest)
            'claude-opus-4-5' => 'Claude Opus 4.5 🎯💎 Premium frontier (expensive)',
            'claude-sonnet-4-5' => 'Claude Sonnet 4.5 ⚡⭐ Best balance (fast, expensive)',
            'claude-haiku-4-5' => 'Claude Haiku 4.5 ⚡⚡💰 Fastest/cheapest Claude',
        );

        // Pricing per 1M tokens (as of Dec 2025)
        $this->pricing = array(
            'input' => array(
                'claude-opus-4-5' => 5.00,
                'claude-sonnet-4-5' => 3.00,
                'claude-haiku-4-5' => 1.00,
            ),
            'output' => array(
                'claude-opus-4-5' => 25.00,
                'claude-sonnet-4-5' => 15.00,
                'claude-haiku-4-5' => 5.00,
            ),
        );

        parent::__construct();
    }

    /**
     * Make test request
     *
     * @return bool|WP_Error
     */
    protected function make_test_request() {
        $url = 'https://api.anthropic.com/v1/messages';
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $this->default_model,
                'max_tokens' => 10,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello',
                    ),
                ),
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        return true;
    }

    /**
     * Generate text
     *
     * @param string $prompt
     * @param array $options
     * @return array|WP_Error
     */
    public function generate_text($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Anthropic API key is required', 'wordpress-ai-assistant'));
        }

        $model = isset($options['model']) ? $options['model'] : $this->get_current_model();
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 1000;
        $temperature = isset($options['temperature']) ? $options['temperature'] : 0.7;

        $url = 'https://api.anthropic.com/v1/messages';
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'max_tokens' => $max_tokens,
                'temperature' => $temperature,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt,
                    ),
                ),
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['content'][0]['text'])) {
            return new WP_Error('invalid_response', __('Invalid response from Anthropic API', 'wordpress-ai-assistant'));
        }

        return array(
            'text' => $response['content'][0]['text'],
            'usage' => array(
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0),
            ),
            'model' => $model,
        );
    }

    /**
     * Analyze image (Claude 3 supports vision)
     *
     * @param string $image_url
     * @param array $options
     * @return array|WP_Error
     */
    public function analyze_image($image_url, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Anthropic API key is required', 'wordpress-ai-assistant'));
        }

        $model = isset($options['model']) ? $options['model'] : 'claude-haiku-4-5';
        $prompt = isset($options['prompt']) ? $options['prompt'] : 'Describe this image in detail, including any text visible in the image.';

        // Download image and convert to base64
        $image_data = $this->download_image($image_url);
        if (is_wp_error($image_data)) {
            return $image_data;
        }

        $url = 'https://api.anthropic.com/v1/messages';
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'x-api-key' => $this->api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'max_tokens' => 500,
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => array(
                            array(
                                'type' => 'text',
                                'text' => $prompt,
                            ),
                            array(
                                'type' => 'image',
                                'source' => array(
                                    'type' => 'base64',
                                    'media_type' => $image_data['mime_type'],
                                    'data' => $image_data['base64'],
                                ),
                            ),
                        ),
                    ),
                ),
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['content'][0]['text'])) {
            return new WP_Error('invalid_response', __('Invalid response from Anthropic API', 'wordpress-ai-assistant'));
        }

        return array(
            'description' => $response['content'][0]['text'],
            'usage' => array(
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($response['usage']['input_tokens'] ?? 0) + ($response['usage']['output_tokens'] ?? 0),
            ),
            'model' => $model,
        );
    }

    /**
     * Download image and convert to base64
     *
     * @param string $image_url
     * @return array|WP_Error
     */
    private function download_image($image_url) {
        $response = wp_remote_get($image_url);

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('download_failed', __('Failed to download image', 'wordpress-ai-assistant'));
        }

        $image_data = wp_remote_retrieve_body($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if (empty($image_data)) {
            return new WP_Error('empty_image', __('Empty image data', 'wordpress-ai-assistant'));
        }

        return array(
            'base64' => base64_encode($image_data),
            'mime_type' => $content_type ?: 'image/jpeg',
        );
    }

    /**
     * Calculate cost for this provider
     *
     * @param array $usage
     * @return float
     */
    public function calculate_cost($usage) {
        $model = isset($usage['model']) ? $usage['model'] : $this->get_current_model();
        $input_tokens = isset($usage['input_tokens']) ? $usage['input_tokens'] : 0;
        $output_tokens = isset($usage['output_tokens']) ? $usage['output_tokens'] : 0;

        $input_cost_per_million = isset($this->pricing['input'][$model]) ? $this->pricing['input'][$model] : 0;
        $output_cost_per_million = isset($this->pricing['output'][$model]) ? $this->pricing['output'][$model] : 0;

        $input_cost = ($input_tokens / 1000000) * $input_cost_per_million;
        $output_cost = ($output_tokens / 1000000) * $output_cost_per_million;

        return $input_cost + $output_cost;
    }
}
