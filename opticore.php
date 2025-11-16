<?php

/**
 * Plugin Name: OptiCore
 * Plugin URI: https://github.com/ahmadreza-log/opticore
 * Description: This plugin intelligently optimizes your WordPress site by improving load times,
 *              reducing server stress, and enhancing Core Web Vitals — all without breaking your
 *              design or functionality.
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
 * This file is the main entry point for the plugin. It wires up a small bootstrapper class which
 * takes care of autoloading, loading feature snippets based on saved settings, and booting the
 * admin / front‑end components.
 *
 * @package OptiCore
 */

use Opticore\Admin;
use Opticore\Enqueue;
use Opticore\Ajax;

/**
 * Bail early if WordPress core is not loaded.
 *
 * Direct access (e.g. calling this file via the browser) would otherwise allow PHP execution
 * outside of the WordPress environment.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define a set of convenience constants that describe the plugin's location and version.
 *
 * These are reused across the codebase (for example when registering assets) so paths stay
 * consistent even if the plugin folder is renamed or moved.
 */
defined('OPTICORE_PLUGIN_VERSION') || define('OPTICORE_PLUGIN_VERSION', '1.0.0');
defined('OPTICORE_PLUGIN_FILE') || define('OPTICORE_PLUGIN_FILE', __FILE__);
defined('OPTICORE_PLUGIN_DIR') || define('OPTICORE_PLUGIN_DIR', plugin_dir_path(OPTICORE_PLUGIN_FILE));
defined('OPTICORE_PLUGIN_URL') || define('OPTICORE_PLUGIN_URL', plugin_dir_url(OPTICORE_PLUGIN_FILE));
defined('OPTICORE_PLUGIN_BASENAME') || define('OPTICORE_PLUGIN_BASENAME', plugin_basename(OPTICORE_PLUGIN_FILE));

/**
 * Main OptiCore bootstrap class.
 *
 * This class acts as a small "orchestrator" responsible for:
 * - registering a lightweight PSR‑4 style autoloader,
 * - loading optimisation snippets based on the stored plugin settings,
 * - registering global hooks, and
 * - booting admin / front‑end components.
 *
 * The class is intentionally small; most behaviour is delegated to specialised classes under
 * the `Opticore` namespace or to feature files in `includes/functions`.
 */
final class OptiCore
{
    /**
     * Singleton instance for the plugin bootstrapper.
     *
     * Using a singleton avoids repeated initialisation while still giving other code a single
     * access point via {@see OptiCore::instance()} or the global {@see opticore()} helper.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Reference to the PSR‑4 style autoloader used by the plugin.
     *
     * Lazily initialised in {@see self::autoload()} and reused for the duration of the request.
     *
     * @var Opticore\Autoloader|null
     */
    private ?Opticore\Autoloader $autoloader = null;

    /**
     * Retrieve (or create) the bootstrapper instance and run the startup sequence.
     *
     * This method orchestrates the plugin startup lifecycle by wiring the autoloader,
     * loading conditional feature snippets, registering hooks, and booting the individual
     * components that drive admin/front‑end behaviour.
     *
     * @return self
     */
    public static function instance(): self
    {
        // Lazily create the singleton the first time it is requested.
        if (self::$instance === null) {
            self::$instance = new self();
        }

        // Run the boot sequence on the stored instance so repeated calls are cheap.
        self::$instance->autoload();
        self::$instance->includes();
        self::$instance->hooks();
        self::$instance->components();

        return self::$instance;
    }

    /**
     * Public constructor kept for compatibility with some WordPress tooling.
     *
     * Consumers should always obtain an instance via {@see OptiCore::instance()} so the
     * singleton guarantees are respected.
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
     * This method bootstraps the lightweight `Opticore\Autoloader` so that classes inside
     * the `Opticore` namespace are automatically resolved from the `includes` directory.
     *
     * @return void
     */
    public function autoload(): void
    {
        // Ensure the autoloader class is available – this file is shipped with the plugin.
        require_once OPTICORE_PLUGIN_DIR . 'vendor/Autoloader.php';

        // If we already have a configured autoloader, reuse it for this request.
        if ($this->autoloader instanceof Opticore\Autoloader) {
            return;
        }

        // Create and register the autoloader so PHP can resolve classes on demand.
        $this->autoloader = new Opticore\Autoloader();
        $this->autoloader->register();

        // Map the root namespace to the includes folder so component classes load automatically.
        $this->autoloader->namespace('Opticore', OPTICORE_PLUGIN_DIR . 'includes');
    }

    /**
     * Conditionally include feature snippets based on saved settings.
     *
     * Each toggle in the settings UI corresponds to a PHP file under `includes/functions/{id}.php`.
     * Only the snippets associated with active options are loaded so the plugin evaluates the
     * minimum amount of code necessary for the current configuration.
     *
     * @return void
     */
    public function includes(): void
    {
        // Helper functions are always available; they are lightweight and broadly useful.
        require_once OPTICORE_PLUGIN_DIR . 'includes/helpers/pre-print.php';
        require_once OPTICORE_PLUGIN_DIR . 'includes/helpers/minify-css.php';

        // Load all stored options as an associative array keyed by feature ID.
        $options = get_option('opticore-settings', []);

        foreach ($options as $key => $value) {
            // Translate the saved option key into a PHP file path.
            $file = OPTICORE_PLUGIN_DIR . 'includes/functions/' . $key . '.php';

            if (!file_exists($file)) {
                // Ignore unknown or legacy keys without failing the entire boot sequence.
                continue;
            }

            /**
             * Allow other code to adjust the value before the feature snippet sees it.
             *
             * Individual optimisation files rely on `$value` to configure behaviour, for example
             * heartbeat frequency or revision limits. Third‑party code can hook into this filter
             * to normalise or override values.
             *
             * @var mixed $value
             */
            $value = apply_filters('opticore/feature/value', $value, $key);

            // Execute the feature file; most snippets immediately register their own hooks.
            require $file;
        }
    }

    /**
     * Register core WordPress hooks used by the plugin.
     *
     * This method focuses on global hooks that are always needed, regardless of which
     * optimisation features are enabled.
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
     * By pointing `load_plugin_textdomain()` at the `languages` subdirectory we ensure that
     * calls to `__()`, `_e()`, etc. within the plugin can be translated via `.mo` files.
     *
     * @see https://developer.wordpress.org/reference/functions/load_plugin_textdomain/
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
     * Boot the plugin components that power admin and front‑end behaviour.
     *
     * Admin‑only components (menu, settings screen, AJAX handlers) are initialised when running
     * inside the dashboard, while the enqueue manager is always available so it can manage
     * assets for both admin and public views.
     *
     * @return void
     */
    public function components(): void
    {
        if (is_admin()) {
            // Register admin menu pages and settings screens.
            Admin::instance();

            // Handle AJAX requests for saving settings.
            Ajax::instance();
        }

        // Register asset loading hooks for admin, login, and front‑end contexts.
        Enqueue::instance();
    }
}

if (!function_exists('opticore')) {
    /**
     * Helper function mirroring the plugin instance accessor.
     *
     * Keeping a global‑style function maintains compatibility with common WordPress patterns
     * while letting developers access the bootstrapper without referencing the class directly.
     *
     * @return OptiCore
     */
    function opticore(): OptiCore
    {
        return OptiCore::instance();
    }
}

// Kick off the plugin bootstrap sequence as soon as this file is loaded by WordPress.
opticore();
