<?php

/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 *
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */
class AT_WordPress_AI_Assistant_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('at_ai_assistant_cleanup');
        
        // Clear transients
        self::clear_transients();
    }
    
    /**
     * Clear plugin transients
     *
     * @since    1.0.0
     */
    private static function clear_transients() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_at_ai_assistant_%' 
             OR option_name LIKE '_transient_timeout_at_ai_assistant_%'"
        );
    }
}