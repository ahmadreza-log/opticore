<?php

/**
 * Admin-specific functionality
 * Handles admin menu, settings, and scripts
 *
 * @package OptiCore
 */

namespace Opticore;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax
{
    /**
     * Singleton storage for the AJAX controller.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Retrieve (or lazily create) the singleton instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register AJAX endpoints.
     */
    public function __construct()
    {
        add_action('wp_ajax_opticore-save-settings', [$this, 'save_settings']);
    }

    /**
     * Disallow cloning to preserve singleton behaviour.
     */
    public function __clone(): void {}

    /**
     * Disallow unserialisation to preserve singleton behaviour.
     *
     * @throws \Exception Always thrown to prevent unserialisation.
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Persist settings submitted from the admin screen.
     */
    public function save_settings(): void
    {
        check_ajax_referer('opticore-nonce', 'opticore-nonce');

        // Settings currently post via GET for compatibility with `$.serialize()`; revisit with POST in future.
        $fields = array_map('sanitize_text_field', wp_unslash($_GET));

        unset($fields['action'], $fields['opticore-nonce']);

        $list = [];

        foreach ($fields as $rawKey => $value) {
            $key = str_replace('opticore-setting-', '', $rawKey);
            $list[$key] = $value;
        }

        update_option('opticore-settings', $list);

        $directory = trailingslashit(WP_CONTENT_DIR) . 'cache/opticore';

        if (is_dir($directory)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $item) {
                $path = $item->getPathname();

                if ($item->isDir()) {
                    rmdir($path);
                } else {
                    unlink($path);
                }
            }
        }

        wp_mkdir_p($directory);

        wp_send_json_success([
            'message' => __('Settings saved successfully! Cache cleared.', 'opticore'),
        ]);
    }
}
