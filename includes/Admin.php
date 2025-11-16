<?php

/**
 * Admin-specific functionality for OptiCore.
 *
 * This class is responsible for wiring the plugin into the WordPress admin area:
 * - registering the top-level "OptiCore" menu and its subpages (Dashboard / Settings),
 * - registering the settings group that persists the options array, and
 * - exposing shortcut links on the Plugins screen for quick navigation. ✨
 *
 * @package OptiCore
 */

namespace Opticore;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
{
    /**
     * Prefix used for submenu slugs so we can differentiate dashboard/settings views.
     *
     * @var string
     */
    private string $prefix = 'opticore-';

    /**
     * Singleton storage for the admin manager.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Retrieve the singleton instance used to manage admin hooks.
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register admin-specific hooks.
     *
     * The constructor is invoked only once via {@see self::instance()}. It attaches the
     * admin menu, settings registration, and plugin action links to the appropriate hooks.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'register']);
        add_filter('plugin_action_links_' . OPTICORE_PLUGIN_BASENAME, [$this, 'links']);
    }

    /**
     * Disallow cloning to maintain singleton instance.
     *
     * @return void
     */
    public function __clone(): void {}

    /**
     * Disallow unserialisation to maintain singleton instance.
     *
     * @throws \Exception Always thrown to prevent unserialisation.
     * @return void
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Register the main menu page and auxiliary subpages.
     *
     * This method wires the plugin into the WordPress admin menu:
     * - top-level "OptiCore" entry (acts as the main container),
     * - "Dashboard" view (slug: `opticore-dashboard`),
     * - "Settings" view (slug: `opticore-settings`).
     *
     * @see https://developer.wordpress.org/reference/functions/add_menu_page/
     * @see https://developer.wordpress.org/reference/functions/add_submenu_page/
     *
     * @return void
     */
    public function menu(): void
    {
        add_menu_page(
            __('OptiCore Settings', 'opticore'),
            __('OptiCore', 'opticore'),
            'manage_options',
            'opticore',
            [$this, 'render'],
            'dashicons-performance',
            100
        );

        add_submenu_page(
            'opticore',
            __('Dashboard', 'opticore'),
            __('Dashboard', 'opticore'),
            'manage_options',
            $this->prefix . 'dashboard',
            [$this, 'render']
        );

        add_submenu_page(
            'opticore',
            __('Settings', 'opticore'),
            __('Settings', 'opticore'),
            'manage_options',
            $this->prefix . 'settings',
            [$this, 'render']
        );

        // Hide the redundant "OptiCore" submenu entry that WP adds automatically.
        // This keeps the sidebar tidy and avoids a duplicate link that points to the same screen.
        remove_submenu_page('opticore', 'opticore');
    }

    /**
     * Register the settings group so WordPress will persist the option array.
     *
     * @return void
     */
    public function register(): void
    {
        register_setting('opticore-settings-group', 'opticore-settings');
    }

    /**
     * Add quick links inside the plugin list to jump to plugin screens.
     *
     * @param array<int, string> $links Existing plugin action links.
     * @return array<int, string>
     */
    public function links($links): array
    {
        array_unshift(
            $links,
            '<a href="' . admin_url('admin.php?page=opticore') . '">' . __('Dashboard', 'opticore') . '</a>',
            '<a href="' . admin_url('admin.php?page=opticore-settings') . '">' . __('Settings', 'opticore') . '</a>'
        );

        return $links;
    }

    /**
     * Load the requested admin screen template.
     *
     * @return void
     */
    public function render(): void
    {
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : 'opticore-dashboard';
        $slug = ltrim($page, $this->prefix);

        echo '<div class="mr-5 mt-5 max-lg:mr-2.5 max-lg:mt-2.5 ' . esc_attr($page) . ' ' . esc_attr($page) . '-wrap font-poppins">';

        $file = OPTICORE_PLUGIN_DIR . 'admin' . DIRECTORY_SEPARATOR . $slug . '.php';

        if (file_exists($file)) {
            require $file;
        }

        echo '</div>';
    }
}
