<?php
/**
 * Plugin Name: WordPress AI Assistant
 * Plugin URI: https://github.com/athbss/wp-ai-bro
 * Description: תוסף WordPress מתקדם המספק יכולות בינה מלאכותית לשיפור תהליכי יצירת תוכן, נגישות וחוויית המשתמש
 * Version: 1.3.0
 * Author: Amit Trabelsi
 * Author URI: https://amit-trabelsi.co.il
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wordpress-ai-assistant
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 * Update URI: https://updates.amiteam.io/wordpress-ai-assistant/plugin-info.json
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 */
define('WORDPRESS_AI_ASSISTANT_VERSION', '1.3.0');

/**
 * Plugin directory path
 */
define('WORDPRESS_AI_ASSISTANT_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL
 */
define('WORDPRESS_AI_ASSISTANT_URL', plugin_dir_url(__FILE__));

/**
 * Plugin basename
 */
define('WORDPRESS_AI_ASSISTANT_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin text domain
 */
define('WORDPRESS_AI_ASSISTANT_TEXT_DOMAIN', 'wordpress-ai-assistant');

/**
 * The code that runs during plugin activation.
 */
function at_activate_wordpress_ai_assistant() {
    require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/class-activator.php';
    AT_WordPress_AI_Assistant_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function at_deactivate_wordpress_ai_assistant() {
    require_once WORDPRESS_AI_ASSISTANT_PATH . 'includes/class-deactivator.php';
    AT_WordPress_AI_Assistant_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'at_activate_wordpress_ai_assistant');
register_deactivation_hook(__FILE__, 'at_deactivate_wordpress_ai_assistant');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WORDPRESS_AI_ASSISTANT_PATH . 'includes/class-core.php';

/**
 * Begins execution of the plugin.
 */
function at_run_wordpress_ai_assistant() {
    $plugin = new AT_WordPress_AI_Assistant_Core();
    $plugin->run();
}

at_run_wordpress_ai_assistant();