<?php
/**
 * WordPress Abilities API bridge for AI media and taxonomy actions.
 *
 * @package WordPress_AI_Assistant
 */

defined('ABSPATH') || exit;

class AT_AI_Abilities {

    public function __construct() {
        if (!function_exists('wp_register_ability')) {
            return;
        }

        add_action('wp_abilities_api_categories_init', array($this, 'register_category'));
        add_action('wp_abilities_api_init', array($this, 'register_abilities'));
    }

    public function register_category() {
        wp_register_ability_category('wordpress-ai-assistant', array(
            'label' => __('WordPress AI Assistant', 'wordpress-ai-assistant'),
        ));
    }

    public function register_abilities() {
        $input_schema = array(
            'type' => 'object',
            'properties' => array(
                'post_id' => array('type' => 'integer', 'minimum' => 1),
            ),
            'required' => array('post_id'),
        );

        wp_register_ability('wordpress-ai-assistant/generate-image-alt-text', array(
            'label' => __('Generate image alt text', 'wordpress-ai-assistant'),
            'description' => __('Generates and stores accessible alt text for an image attachment.', 'wordpress-ai-assistant'),
            'category' => 'wordpress-ai-assistant',
            'callback' => array($this, 'generate_image_alt_text'),
            'input_schema' => $input_schema,
            'permission_callback' => array($this, 'can_edit_post'),
            'meta' => array('show_in_rest' => true),
        ));

        wp_register_ability('wordpress-ai-assistant/generate-post-taxonomies', array(
            'label' => __('Generate post taxonomies', 'wordpress-ai-assistant'),
            'description' => __('Analyzes a post, including allowed custom fields, and applies selected taxonomy terms.', 'wordpress-ai-assistant'),
            'category' => 'wordpress-ai-assistant',
            'callback' => array($this, 'generate_post_taxonomies'),
            'input_schema' => $input_schema,
            'permission_callback' => array($this, 'can_edit_post'),
            'meta' => array('show_in_rest' => true),
        ));
    }

    public function can_edit_post($input) {
        $post_id = absint($input['post_id'] ?? 0);
        return $post_id > 0 && current_user_can('edit_post', $post_id);
    }

    public function generate_image_alt_text($input) {
        $attachment_id = absint($input['post_id'] ?? 0);
        if (!wp_attachment_is_image($attachment_id)) {
            return new WP_Error('invalid_attachment', __('The requested post is not an image attachment.', 'wordpress-ai-assistant'));
        }

        $generator = new AT_Image_Alt_Generator(false);
        $result = $generator->generate_alt_text($attachment_id);
        if (is_wp_error($result)) {
            return $result;
        }

        return array(
            'attachment_id' => $attachment_id,
            'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
        );
    }

    public function generate_post_taxonomies($input) {
        $post_id = absint($input['post_id'] ?? 0);
        $tagger = new AT_Auto_Tagger(false);
        $tags = $tagger->process_post($post_id);
        if (is_wp_error($tags)) {
            return $tags;
        }

        return array(
            'post_id' => $post_id,
            'taxonomies' => $tags['taxonomies'] ?? array(),
        );
    }
}
