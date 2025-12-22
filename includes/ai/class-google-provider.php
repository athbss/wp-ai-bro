<?php
/**
 * Google AI Provider
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
 * Google AI Provider Class
 */
class AT_Google_Provider extends AT_AI_Provider {

    /**
     * Constructor
     */
    public function __construct() {
        $this->name = 'google';
        $this->display_name = 'Google AI';
        $this->default_model = 'gemini-2.5-flash';
        $this->available_models = array(
            // Gemini 3 Series (Preview)
            'gemini-3-pro-preview' => 'Gemini 3 Pro Preview 🎯💎 Flagship preview (expensive)',
            // Gemini 2.5 Series (Stable)
            'gemini-2.5-pro' => 'Gemini 2.5 Pro ⚡💎 Stable flagship (expensive)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash ⚡💰 Low latency (recommended)',
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite ⚡⚡💰💰 Cheapest (fastest)',
            // Image Generation
            'gemini-2.5-flash-image' => '🖼️ Gemini 2.5 Flash Image (Nano Banana)',
            'gemini-3-pro-image' => '🖼️ Gemini 3 Pro Image (Nano Banana Pro)',
        );

        // Pricing per 1M tokens (as of Dec 2025)
        $this->pricing = array(
            'input' => array(
                // Gemini 3 Series
                'gemini-3-pro-preview' => 2.00,
                // Gemini 2.5 Series
                'gemini-2.5-pro' => 1.25,
                'gemini-2.5-flash' => 0.30,
                'gemini-2.5-flash-lite' => 0.10,
                // Image Generation
                'gemini-2.5-flash-image' => 0.30,
                'gemini-3-pro-image' => 0, // Not specified, likely expensive
            ),
            'output' => array(
                // Gemini 3 Series
                'gemini-3-pro-preview' => 12.00,
                // Gemini 2.5 Series
                'gemini-2.5-pro' => 10.00,
                'gemini-2.5-flash' => 2.50,
                'gemini-2.5-flash-lite' => 0.40,
                // Image Generation
                'gemini-2.5-flash-image' => 2.50,
                'gemini-3-pro-image' => 0, // Not specified, likely expensive
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
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $this->default_model . '?key=' . $this->api_key;

        $response = $this->make_request($url);

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
            return new WP_Error('missing_api_key', __('Google AI API key is required', 'wordpress-ai-assistant'));
        }

        $model = isset($options['model']) ? $options['model'] : $this->get_current_model();
        $max_tokens = isset($options['max_tokens']) ? $options['max_tokens'] : 1000;
        $temperature = isset($options['temperature']) ? $options['temperature'] : 0.7;

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->api_key;
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array(
                                'text' => $prompt,
                            ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => $max_tokens,
                    'temperature' => $temperature,
                ),
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', __('Invalid response from Google AI API', 'wordpress-ai-assistant'));
        }

        // Google AI doesn't provide token counts in the same way, so we'll estimate
        $estimated_input_tokens = strlen($prompt) / 4; // Rough estimation
        $output_text = $response['candidates'][0]['content']['parts'][0]['text'];
        $estimated_output_tokens = strlen($output_text) / 4;

        return array(
            'text' => $output_text,
            'usage' => array(
                'input_tokens' => intval($estimated_input_tokens),
                'output_tokens' => intval($estimated_output_tokens),
                'total_tokens' => intval($estimated_input_tokens + $estimated_output_tokens),
            ),
            'model' => $model,
        );
    }

    /**
     * Analyze image using Gemini Pro Vision
     *
     * @param string $image_url
     * @param array $options
     * @return array|WP_Error
     */
    public function analyze_image($image_url, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Google AI API key is required', 'wordpress-ai-assistant'));
        }

        $model = isset($options['model']) ? $options['model'] : 'gemini-2.5-flash';
        $prompt = isset($options['prompt']) ? $options['prompt'] : 'Describe this image in detail, including any text visible in the image.';

        // Download image and convert to base64
        $image_data = $this->download_image($image_url);
        if (is_wp_error($image_data)) {
            return $image_data;
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->api_key;
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array(
                                'text' => $prompt,
                            ),
                            array(
                                'inline_data' => array(
                                    'mime_type' => $image_data['mime_type'],
                                    'data' => $image_data['base64'],
                                ),
                            ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => 500,
                ),
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', __('Invalid response from Google AI API', 'wordpress-ai-assistant'));
        }

        $output_text = $response['candidates'][0]['content']['parts'][0]['text'];

        // Estimate token usage
        $estimated_input_tokens = (strlen($prompt) + strlen($image_data['base64'])) / 4;
        $estimated_output_tokens = strlen($output_text) / 4;

        return array(
            'description' => $output_text,
            'usage' => array(
                'input_tokens' => intval($estimated_input_tokens),
                'output_tokens' => intval($estimated_output_tokens),
                'total_tokens' => intval($estimated_input_tokens + $estimated_output_tokens),
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
     * Generate image from text using Gemini 2.5 Flash Image
     *
     * @param string $prompt
     * @param array $options
     * @return array|WP_Error
     */
    public function generate_image($prompt, $options = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Google AI API key is required', 'wordpress-ai-assistant'));
        }

        // Get global prompt instructions
        $general_instructions = at_ai_assistant_get_option('general_prompt_instructions', '');
        $visual_style = at_ai_assistant_get_option('visual_style_instructions', '');
        
        // Combine prompts
        $full_prompt = $prompt;
        if (!empty($visual_style)) {
            $full_prompt = $visual_style . '. ' . $full_prompt;
        }
        if (!empty($general_instructions)) {
            $full_prompt = $general_instructions . '. ' . $full_prompt;
        }

        $model = isset($options['model']) ? $options['model'] : 'gemini-2.5-flash-image';
        $num_images = isset($options['num_images']) ? intval($options['num_images']) : 1;
        $size = isset($options['size']) ? $options['size'] : '1024x1024';

        // Use Gemini 2.5 Flash Image API
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $this->api_key;
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array(
                                'text' => $full_prompt,
                            ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'candidateCount' => $num_images,
                    'responseModalities' => array('image'),
                ),
            )),
        );

        $response = $this->make_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parse response (structure may vary)
        if (!isset($response['candidates'])) {
            return new WP_Error('invalid_response', __('Invalid response from Google AI API', 'wordpress-ai-assistant'));
        }

        $images = array();
        foreach ($response['candidates'] as $candidate) {
            if (isset($candidate['content']['parts'])) {
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['inline_data'])) {
                        $images[] = array(
                            'data' => $part['inline_data']['data'],
                            'mime_type' => $part['inline_data']['mime_type'],
                        );
                    }
                }
            }
        }

        return array(
            'images' => $images,
            'prompt' => $full_prompt,
            'model' => $model,
            'usage' => array(
                'input_tokens' => strlen($full_prompt) / 4,
                'output_tokens' => count($images) * 100, // Estimated
                'total_tokens' => (strlen($full_prompt) / 4) + (count($images) * 100),
            ),
        );
    }

    /**
     * Get image generation models
     *
     * @return array
     */
    public function get_image_generation_models() {
        return array(
            'gemini-2.5-flash-image' => '🖼️ Gemini 2.5 Flash Image (Nano Banana) ⚡💰',
            'gemini-3-pro-image' => '🖼️ Gemini 3 Pro Image (Nano Banana Pro) 🎯💎',
        );
    }
    
    /**
     * Get supported modalities by model
     *
     * @param string $model
     * @return array
     */
    public function get_model_modalities($model) {
        $modalities = array(
            'gemini-3-pro-preview' => array('text', 'image', 'video', 'audio'),
            'gemini-2.5-pro' => array('text', 'image', 'video', 'audio'),
            'gemini-2.5-flash' => array('text', 'image', 'video', 'audio'),
            'gemini-2.5-flash-lite' => array('text', 'image', 'video', 'audio'),
            'gemini-2.5-flash-image' => array('image'),
            'gemini-3-pro-image' => array('image'),
        );
        
        return isset($modalities[$model]) ? $modalities[$model] : array('text');
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
