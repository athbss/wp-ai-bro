<?php
/**
 * OpenAI Provider
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
 * OpenAI Provider Class
 */
class AT_OpenAI_Provider extends AT_AI_Provider {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'openai';
        $this->display_name = 'OpenAI';
        $this->default_model = 'gpt-5.6-terra';
        $this->available_models = array(
            // GPT-5.6 series.
            'gpt-5.6' => 'GPT-5.6 Sol - highest quality',
            'gpt-5.6-terra' => 'GPT-5.6 Terra - balanced (recommended)',
            'gpt-5.6-luna' => 'GPT-5.6 Luna - high-volume and efficient',
            // GPT-5 Series.
            'gpt-5.2' => 'GPT-5.2 - previous flagship',
            'gpt-5.1' => 'GPT-5.1 ⚡ Flagship (fast, mid-cost)',
            'gpt-5-mini' => 'GPT-5 mini ⚡💰 Cheaper/faster tier',
            'gpt-5-nano' => 'GPT-5 nano ⚡⚡💰💰 Fastest/cheapest',
            'gpt-5-pro' => 'GPT-5 pro 🎯💎 Highest precision (expensive)',
            // GPT-4.1 Series
            'gpt-4.1' => 'GPT-4.1 ⚡ Strong non-reasoning (mid-cost)',
            'gpt-4.1-mini' => 'GPT-4.1 mini ⚡💰 Low latency',
            // GPT-4o Series (Legacy)
            'gpt-4o' => 'GPT-4o ⚡ Legacy omni flagship',
            'gpt-4o-mini' => 'GPT-4o mini ⚡💰💰 Best cost/latency',
            // Reasoning Models (O3 Series)
            'o3' => 'o3 🧠💎 Reasoning frontier (slow, dynamic pricing)',
            'o3-mini' => 'o3-mini 🧠⚡ Small reasoning',
        );

        // Standard API pricing per 1M tokens (July 2026).
        $this->pricing = array(
            'input' => array(
                // GPT-5.6 series.
                'gpt-5.6' => 5.00,
                'gpt-5.6-terra' => 2.50,
                'gpt-5.6-luna' => 1.00,
                // GPT-5 Series.
                'gpt-5.2' => 1.75,
                'gpt-5.1' => 1.25,
                'gpt-5-mini' => 0.25,
                'gpt-5-nano' => 0.05,
                'gpt-5-pro' => 15.00,
                // GPT-4.1 Series
                'gpt-4.1' => 2.00,
                'gpt-4.1-mini' => 0.40,
                // GPT-4o Series (Legacy)
                'gpt-4o' => 2.50,
                'gpt-4o-mini' => 0.15,
                // Reasoning Models (O3 Series)
                'o3' => 0, // Dynamic pricing
                'o3-mini' => 1.10,
            ),
            'output' => array(
                // GPT-5.6 series.
                'gpt-5.6' => 30.00,
                'gpt-5.6-terra' => 15.00,
                'gpt-5.6-luna' => 6.00,
                // GPT-5 Series.
                'gpt-5.2' => 14.00,
                'gpt-5.1' => 10.00,
                'gpt-5-mini' => 2.00,
                'gpt-5-nano' => 0.40,
                'gpt-5-pro' => 120.00,
                // GPT-4.1 Series
                'gpt-4.1' => 8.00,
                'gpt-4.1-mini' => 1.60,
                // GPT-4o Series (Legacy)
                'gpt-4o' => 10.00,
                'gpt-4o-mini' => 0.60,
                // Reasoning Models (O3 Series)
                'o3' => 0, // Dynamic pricing
                'o3-mini' => 4.40,
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
        $url = 'https://api.openai.com/v1/models';
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
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
            return new WP_Error('missing_api_key', __('OpenAI API key is required', 'wordpress-ai-assistant'));
        }

        $model = isset($options['model']) ? $options['model'] : $this->get_current_model();
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 1000;
        $temperature = isset($options['temperature']) ? $options['temperature'] : 0.7;

        $url = 'https://api.openai.com/v1/responses';
        $payload = array(
            'model' => $model,
            'input' => array(
                array(
                    'role' => 'user',
                    'content' => array(
                        array(
                            'type' => 'input_text',
                            'text' => $prompt,
                        ),
                    ),
                ),
            ),
            'max_output_tokens' => $max_tokens,
            'store' => false,
        );

        if (!$this->is_reasoning_model($model)) {
            $payload['temperature'] = $temperature;
        }

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $text = isset($response['output_text']) ? $response['output_text'] : $this->extract_response_text($response);
        if ($text === '') {
            return new WP_Error('invalid_response', __('Invalid response from OpenAI API', 'wordpress-ai-assistant'));
        }

        return array(
            'text' => $text,
            'usage' => array(
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ),
            'model' => $model,
        );
    }

    /**
     * Analyze image
     *
     * @param string $image_url
     * @param array $options
     * @return array|WP_Error
     */
    public function analyze_image($image_url, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('OpenAI API key is required', 'wordpress-ai-assistant'));
        }

        $model = isset($options['model']) ? $options['model'] : $this->get_current_model();
        $prompt = isset($options['prompt']) ? $options['prompt'] : 'Describe this image in detail, including any text visible in the image.';

        $url = 'https://api.openai.com/v1/responses';
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'model' => $model,
                'input' => array(
                    array(
                        'role' => 'user',
                        'content' => array(
                            array(
                                'type' => 'input_text',
                                'text' => $prompt,
                            ),
                            array(
                                'type' => 'input_image',
                                'image_url' => $image_url,
                            ),
                        ),
                    ),
                ),
                'max_output_tokens' => 500,
                'store' => false,
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $description = isset($response['output_text']) ? $response['output_text'] : $this->extract_response_text($response);
        if ($description === '') {
            return new WP_Error('invalid_response', __('Invalid response from OpenAI API', 'wordpress-ai-assistant'));
        }

        return array(
            'description' => $description,
            'usage' => array(
                'input_tokens' => $response['usage']['input_tokens'] ?? 0,
                'output_tokens' => $response['usage']['output_tokens'] ?? 0,
                'total_tokens' => $response['usage']['total_tokens'] ?? 0,
            ),
            'model' => $model,
        );
    }

    /**
     * Extract text from a Responses API payload when output_text is unavailable.
     *
     * @param array $response Response body.
     * @return string
     */
    private function extract_response_text($response) {
        $parts = array();
        foreach ((array) ($response['output'] ?? array()) as $item) {
            foreach ((array) ($item['content'] ?? array()) as $content) {
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    $parts[] = $content['text'];
                }
            }
        }
        return trim(implode("\n", $parts));
    }

    /**
     * Reasoning models do not accept all sampling controls.
     *
     * @param string $model Model ID.
     * @return bool
     */
    private function is_reasoning_model($model) {
        return strpos($model, 'gpt-5') === 0 || strpos($model, 'o') === 0;
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
