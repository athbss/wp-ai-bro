<?php
/**
 * AT Suite Adapter
 *
 * Provides a safe bridge to AT Agency Manager Suite Core.
 * All functions check for suite availability before execution.
 *
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 */

/**
 * Check if AT Suite Core is active and available.
 *
 * @return bool
 */
function at_ai_suite_is_available() {
    return function_exists('at_is_suite_core_active') && at_is_suite_core_active();
}

/**
 * Get a service from the suite registry.
 *
 * @param string $key Service key.
 * @param mixed $default Default value if service not found.
 * @return mixed
 */
function at_ai_suite_get_service($key, $default = null) {
    if (at_ai_suite_is_available()) {
        return at_suite_get_service($key, $default);
    }
    return $default;
}

/**
 * Emit a suite event.
 *
 * @param string $event Event name.
 * @param array $payload Event payload.
 * @return void
 */
function at_ai_suite_emit($event, $payload = array()) {
    if (at_ai_suite_is_available()) {
        at_suite_emit_event($event, $payload);
    }
}

/**
 * Register settings schema with the suite.
 *
 * @param array $fields Schema fields definition.
 * @return void
 */
function at_ai_suite_register_settings_schema($fields) {
    if (at_ai_suite_is_available()) {
        add_filter('at_suite_settings_schema', function($schemas) use ($fields) {
            $schemas['wordpress-ai-assistant'] = $fields;
            return $schemas;
        });
    }
}
