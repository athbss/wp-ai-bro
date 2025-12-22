<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 *
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    WordPress_AI_Assistant
 * @subpackage WordPress_AI_Assistant/includes
 * @author     Amit Trabelsi <amit@amit-trabelsi.co.il>
 */
class AT_WordPress_AI_Assistant_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        // Force load Hebrew if site locale is Hebrew
        $locale = get_locale();
        $mofile = sprintf(
            '%s-%s.mo',
            WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN,
            $locale
        );
        $mofile_local = WORDPRESS_AI_ASSISTANT_PATH . 'languages/' . $mofile;
        $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

        // Try to load from plugin directory first, then global
        if (file_exists($mofile_local)) {
            load_textdomain(WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN, $mofile_local);
        } elseif (file_exists($mofile_global)) {
            load_textdomain(WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN, $mofile_global);
        }

        // Fallback to standard load_plugin_textdomain
        load_plugin_textdomain(
            WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN,
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}