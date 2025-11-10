<?php

/**
 * Configure the WordPress Heartbeat API behaviour.
 *
 * `$value` is injected from the settings loader and represents the dropdown
 * selection (default/disable/only-editing).
 */
$setting = $value ?: 'default';

add_action('init', function () use ($setting) {

    if (is_admin()) {
        global $pagenow;

        if ($pagenow === 'admin.php') {
            $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

            if (!empty($page)) {
                $exceptions = [
                    'gf_edit_forms',
                    'gf_entries',
                    'gf_settings',
                ];

                if (in_array($page, $exceptions, true)) {
                    return;
                }
            }
        }

        if ($pagenow === 'site-health.php') {
            return;
        }
    }

    if ($setting === 'default') {
        return;
    }

    /**
     * Replace the core heartbeat script handle with a no-op file bundled by the plugin.
     *
     * TODO: provide `assets/admin/js/heartbeat.js` implementation to fully disable heartbeats.
     */
    $replaceScript = static function (): void {
        wp_deregister_script('heartbeat');
        wp_enqueue_script('heartbeat', OPTICORE_PLUGIN_URL . 'assets/admin/js/heartbeat.js', [], OPTICORE_PLUGIN_VERSION);
    };

    switch ($setting) {
        case 'disable':
            $replaceScript();
            break;
        case 'only-editing':
            global $pagenow;
            if ($pagenow !== 'post.php' && $pagenow !== 'post-new.php') {
                $replaceScript();
            }
            break;
    }
});
