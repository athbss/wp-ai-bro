<?php

/**
 * Helper functions for WordPress AI Assistant
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get plugin option with prefix
 *
 * @param string $option_name
 * @param mixed $default
 * @return mixed
 */
function at_ai_assistant_get_option($option_name, $default = false) {
    return get_option('at_ai_assistant_' . $option_name, $default);
}

/**
 * Update plugin option with prefix
 *
 * @param string $option_name
 * @param mixed $value
 * @return bool
 */
function at_ai_assistant_update_option($option_name, $value) {
    return update_option('at_ai_assistant_' . $option_name, $value);
}

/**
 * Log message to plugin log table
 *
 * @param string $action
 * @param string $status
 * @param string $message
 * @param array $metadata
 * @param int $post_id
 * @param int $user_id
 * @return bool
 */
function at_ai_assistant_log($action, $status, $message = '', $metadata = array(), $post_id = null, $user_id = null) {
    global $wpdb;
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $table_name = $wpdb->prefix . 'ai_assistant_logs';
    
    return $wpdb->insert(
        $table_name,
        array(
            'action' => $action,
            'post_id' => $post_id,
            'user_id' => $user_id,
            'status' => $status,
            'message' => $message,
            'metadata' => maybe_serialize($metadata),
            'created_at' => current_time('mysql')
        ),
        array('%s', '%d', '%d', '%s', '%s', '%s', '%s')
    );
}

/**
 * Check if current user can use AI features
 *
 * @return bool
 */
function at_ai_assistant_user_can_use_ai() {
    return current_user_can('edit_posts') || current_user_can('manage_options');
}

/**
 * Get supported post types for AI processing
 *
 * @return array
 */
function at_ai_assistant_get_supported_post_types() {
    $default_types = array('post', 'page');
    $enabled_types = at_ai_assistant_get_option('enabled_post_types', $default_types);
    
    return apply_filters('at_ai_assistant_supported_post_types', $enabled_types);
}

/**
 * Check if post type is supported for AI processing
 *
 * @param string $post_type
 * @return bool
 */
function at_ai_assistant_is_post_type_supported($post_type) {
    $supported_types = at_ai_assistant_get_supported_post_types();
    return in_array($post_type, $supported_types);
}