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

    // Ensure we have valid post types
    $valid_types = array();
    if (!empty($enabled_types) && is_array($enabled_types)) {
        foreach ($enabled_types as $post_type) {
            if (post_type_exists($post_type)) {
                $valid_types[] = $post_type;
            }
        }
    }

    // If no valid types, return defaults
    if (empty($valid_types)) {
        $valid_types = $default_types;
    }

    return apply_filters('at_ai_assistant_supported_post_types', $valid_types);
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

/**
 * Get post language code
 * Supports WPML, Polylang, and WordPress native locale
 *
 * @param int $post_id
 * @return string Language code (e.g., 'he', 'en', 'es')
 */
function at_ai_assistant_get_post_language($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return at_ai_assistant_get_default_language();
    }

    // Try WPML first
    if (defined('ICL_SITEPRESS_VERSION') && function_exists('wpml_get_language_information')) {
        $language_info = wpml_get_language_information($post_id);
        if (!empty($language_info['language_code'])) {
            return $language_info['language_code'];
        }
    }

    // Try Polylang
    if (function_exists('pll_get_post_language')) {
        $lang = pll_get_post_language($post_id);
        if ($lang) {
            return $lang;
        }
    }

    // Try WPML alternative method
    if (function_exists('wpml_get_language_information')) {
        $language_info = wpml_get_language_information($post_id);
        if (!empty($language_info['language_code'])) {
            return $language_info['language_code'];
        }
    }

    // Fallback to WordPress locale
    $locale = get_locale();
    if ($locale) {
        // Convert locale to language code (e.g., 'he_IL' -> 'he')
        $lang_code = substr($locale, 0, 2);
        return $lang_code;
    }

    // Default fallback
    return at_ai_assistant_get_default_language();
}

/**
 * Get default language code
 *
 * @return string
 */
function at_ai_assistant_get_default_language() {
    $locale = get_locale();
    if ($locale) {
        return substr($locale, 0, 2);
    }
    return 'en'; // Default to English
}

/**
 * Get language name from code
 *
 * @param string $lang_code
 * @return string
 */
function at_ai_assistant_get_language_name($lang_code) {
    $languages = array(
        'en' => 'English',
        'he' => 'עברית',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'ar' => 'العربية',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
    );

    return isset($languages[$lang_code]) ? $languages[$lang_code] : $lang_code;
}

/**
 * Build prompt with language context
 *
 * @param string $base_prompt
 * @param int $post_id
 * @param string $default_lang
 * @return string
 */
function at_ai_assistant_build_language_aware_prompt($base_prompt, $post_id = null, $default_lang = null) {
    $lang_code = $default_lang;
    
    if ($post_id) {
        $lang_code = at_ai_assistant_get_post_language($post_id);
    } elseif (!$lang_code) {
        $lang_code = at_ai_assistant_get_default_language();
    }

    $lang_name = at_ai_assistant_get_language_name($lang_code);
    
    // Add language instruction to prompt
    $language_instruction = sprintf(
        __('IMPORTANT: Respond in %s (%s). All generated content must be in this language.', 'wordpress-ai-assistant'),
        $lang_name,
        $lang_code
    );

    return $language_instruction . "\n\n" . $base_prompt;
}