<?php
/**
 * Private CDN updater for WordPress AI Assistant.
 *
 * @package WordPress_AI_Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

class AT_AI_Private_CDN_Updater {

    private $slug;
    private $plugin_file;
    private $current_version;
    private $manifest_url;
    private $details_url;
    private $cache_key;

    public function __construct($args) {
        $this->slug = $args['slug'];
        $this->plugin_file = $args['plugin_file'];
        $this->current_version = $args['current_version'];
        $this->manifest_url = $args['manifest_url'];
        $this->details_url = isset($args['details_url']) ? $args['details_url'] : $this->manifest_url;
        $this->cache_key = 'at_cdn_manifest_' . md5($this->manifest_url);

        $hostname = wp_parse_url($this->manifest_url, PHP_URL_HOST);
        if ($hostname) {
            add_filter('update_plugins_' . $hostname, array($this, 'filter_update'), 10, 4);
        }

        add_filter('plugins_api', array($this, 'plugin_information'), 10, 3);
        add_action('upgrader_process_complete', array($this, 'clear_cache_after_upgrade'), 10, 2);
        add_action('load-plugins.php', array($this, 'refresh_update_check'));
    }

    public function filter_update($update, $plugin_data, $plugin_file, $locales) {
        if ($plugin_file !== $this->plugin_file || !empty($update)) {
            return $update;
        }

        $manifest = $this->get_manifest();
        if (!$manifest || empty($manifest['version']) || empty($manifest['package'])) {
            return $update;
        }

        if (!version_compare($this->current_version, $manifest['version'], '<')) {
            return $update;
        }

        return (object) array(
            'id' => isset($plugin_data['UpdateURI']) ? $plugin_data['UpdateURI'] : $this->manifest_url,
            'slug' => $this->slug,
            'plugin' => $this->plugin_file,
            'new_version' => $manifest['version'],
            'version' => $manifest['version'],
            'url' => isset($manifest['homepage']) ? $manifest['homepage'] : $this->details_url,
            'package' => $manifest['package'],
            'tested' => isset($manifest['tested']) ? $manifest['tested'] : '',
            'requires' => isset($manifest['requires']) ? $manifest['requires'] : '',
            'requires_php' => isset($manifest['requires_php']) ? $manifest['requires_php'] : '',
            'icons' => isset($manifest['icons']) && is_array($manifest['icons']) ? $manifest['icons'] : array(),
            'banners' => isset($manifest['banners']) && is_array($manifest['banners']) ? $manifest['banners'] : array(),
        );
    }

    public function plugin_information($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== $this->slug) {
            return $result;
        }

        $manifest = $this->get_manifest();
        if (!$manifest) {
            return $result;
        }

        return (object) array(
            'name' => isset($manifest['name']) ? $manifest['name'] : $this->slug,
            'slug' => $this->slug,
            'version' => isset($manifest['version']) ? $manifest['version'] : $this->current_version,
            'author' => isset($manifest['author']) ? $manifest['author'] : '',
            'author_profile' => isset($manifest['author_profile']) ? $manifest['author_profile'] : '',
            'homepage' => isset($manifest['homepage']) ? $manifest['homepage'] : $this->details_url,
            'requires' => isset($manifest['requires']) ? $manifest['requires'] : '',
            'tested' => isset($manifest['tested']) ? $manifest['tested'] : '',
            'requires_php' => isset($manifest['requires_php']) ? $manifest['requires_php'] : '',
            'last_updated' => isset($manifest['last_updated']) ? $manifest['last_updated'] : '',
            'sections' => isset($manifest['sections']) && is_array($manifest['sections']) ? $manifest['sections'] : array(),
            'download_link' => isset($manifest['package']) ? $manifest['package'] : '',
            'banners' => isset($manifest['banners']) && is_array($manifest['banners']) ? $manifest['banners'] : array(),
        );
    }

    public function clear_cache_after_upgrade($upgrader, $options) {
        if (empty($options['type']) || $options['type'] !== 'plugin') {
            return;
        }

        if (!empty($options['plugins']) && in_array($this->plugin_file, (array) $options['plugins'], true)) {
            delete_site_transient($this->cache_key);
        }
    }

    /**
     * Refresh private updates from the Plugins screen at a controlled interval.
     *
     * WordPress normally checks plugin updates only once per hour on this screen,
     * which can leave a private release invisible immediately after publication.
     */
    public function refresh_update_check() {
        if (!current_user_can('update_plugins')) {
            return;
        }

        $refresh_key = 'at_cdn_update_check_' . md5($this->plugin_file);
        if (get_site_transient($refresh_key)) {
            return;
        }

        delete_site_transient($this->cache_key);
        set_site_transient($refresh_key, time(), 15 * MINUTE_IN_SECONDS);

        // A non-empty argument bypasses WordPress's normal one-hour screen cache.
        wp_update_plugins(array('at_cdn_plugin' => $this->slug));
    }

    private function get_manifest() {
        $cached = get_site_transient($this->cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $response = wp_remote_get($this->manifest_url, array(
            'timeout' => 10,
            'redirection' => 2,
            'headers' => array('Accept' => 'application/json'),
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $manifest = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($manifest)) {
            return false;
        }

        // Accept the legacy WordPress.org-style keyed response during migration.
        if (isset($manifest[$this->plugin_file]) && is_array($manifest[$this->plugin_file])) {
            $manifest = $manifest[$this->plugin_file];
        }

        if (empty($manifest['version']) || empty($manifest['package'])) {
            return false;
        }

        set_site_transient($this->cache_key, $manifest, 15 * MINUTE_IN_SECONDS);
        return $manifest;
    }
}
