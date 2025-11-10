<?php

/**
 * Plugin Name: OptiCore
 * Plugin URI: https://github.com/ahmadreza-log/opticore
 * Description: This plugin intelligently optimizes your WordPress site by improving load times, reducing server stress, and enhancing Core Web Vitals — all without breaking your design or functionality.
 * Version: 1.0.0
 * Author: Ahmadreza Ebrahimi
 * Author URI: https://ahmadreza.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: opticore
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package OptiCore
 */

use Opticore\Admin;
use Opticore\Enqueue;
use Opticore\Ajax;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
defined('OPTICORE_PLUGIN_VERSION') || define('OPTICORE_PLUGIN_VERSION', '1.0.0');
defined('OPTICORE_PLUGIN_FILE') || define('OPTICORE_PLUGIN_FILE', __FILE__);
defined('OPTICORE_PLUGIN_DIR') || define('OPTICORE_PLUGIN_DIR', plugin_dir_path(OPTICORE_PLUGIN_FILE));
defined('OPTICORE_PLUGIN_URL') || define('OPTICORE_PLUGIN_URL', plugin_dir_url(OPTICORE_PLUGIN_FILE));
defined('OPTICORE_PLUGIN_BASENAME') || define('OPTICORE_PLUGIN_BASENAME', plugin_basename(OPTICORE_PLUGIN_FILE));

/**
 * Main OptiCore Class
 * This class acts as the main controller/orchestrator
 * It delegates functionality to specialized classes
 */
final class OptiCore
{
    /**
     * Holds the singleton instance for the plugin bootstrapper.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Reference to the PSR-4-like autoloader used by the plugin.
     *
     * @var Opticore\Autoloader|null
     */
    private ?Opticore\Autoloader $autoloader = null;

    /**
     * Retrieve (or create) the bootstrapper instance.
     *
     * This method orchestrates the plugin startup lifecycle by wiring the
     * autoloader, loading conditional feature snippets, registering hooks, and
     * booting the individual components that drive admin/front-end behaviour.
     *
     * @return self
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        self::$instance->autoload();
        self::$instance->includes();
        self::$instance->hooks();
        self::$instance->components();

        return self::$instance;
    }

    /**
     * Constructor - kept public to satisfy WordPress tooling, but usage should
     * always go through {@see OptiCore::instance()}.
     */
    public function __construct() {}

    /**
     * Disallow cloning to preserve the singleton contract.
     *
     * @return void
     */
    public function __clone(): void {}

    /**
     * Disallow unserialisation to preserve the singleton contract.
     *
     * @throws \Exception Always thrown to prevent unserialisation.
     * @return void
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Register the project autoloader and namespace mappings.
     *
     * @return void
     */
    public function autoload(): void
    {
        // Each request reuses the same loader; guard in case the file is removed.
        require_once OPTICORE_PLUGIN_DIR . 'vendor/Autoloader.php';

        if ($this->autoloader instanceof Opticore\Autoloader) {
            return;
        }

        $this->autoloader = new Opticore\Autoloader();
        $this->autoloader->register();

        // Map the root namespace to the includes folder so component classes load automatically.
        $this->autoloader->namespace('Opticore', OPTICORE_PLUGIN_DIR . 'includes');
    }

    /**
     * Conditionally include feature snippets based on saved settings.
     *
     * Each toggle adds a PHP file under `includes/functions/{feature}.php`.
     * Loading is deferred until runtime so the plugin only evaluates code for
     * enabled optimisations.
     *
     * @return void
     */
    public function includes(): void
    {
        $options = get_option('opticore-settings', []);

        foreach ($options as $key => $value) {
            $file = OPTICORE_PLUGIN_DIR . 'includes/functions/' . $key . '.php';

            if (!file_exists($file)) {
                continue;
            }

            /**
             * Expose the saved value to the included snippet.
             *
             * Individual optimisation files rely on `$value` to configure behaviour,
             * for example heartbeat frequency or revision limits.
             *
             * @var mixed $value
             */
            $value = apply_filters('opticore/feature/value', $value, $key);

            require $file;
        }
    }

    /**
     * Register core WordPress hooks used by the plugin.
     *
     * @return void
     */
    public function hooks(): void
    {
        add_action('init', [$this, 'load_textdomain']);
    }

    /**
     * Load plugin translation files.
     *
     * @return void
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'opticore',
            false,
            dirname(OPTICORE_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Boot the plugin components that power admin and front-end behaviour.
     *
     * @return void
     */
    public function components(): void
    {
        if (is_admin()) {
            Admin::instance();
            Ajax::instance();
        }

        Enqueue::instance();
    }
}

if (!function_exists('opticore')) {
    /**
     * Helper function mirroring the plugin instance accessor.
     *
     * Keeping a global-style function maintains compatibility with WordPress
     * conventions while letting developers access the bootstrapper without
     * referencing the class directly.
     *
     * @return OptiCore
     */
    function opticore(): OptiCore
    {
        return OptiCore::instance();
    }
}

// Start the plugin
opticore();
