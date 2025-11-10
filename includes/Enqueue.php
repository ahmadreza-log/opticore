<?php

namespace Opticore;

/**
 * Handles asset loading for the plugin across admin, login, and front-end contexts.
 */
class Enqueue
{
    /**
     * Singleton storage for the enqueue manager.
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Return (and lazily create) the enqueue singleton.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Register WordPress hooks for loading assets.
     */
    public function __construct()
    {
        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';

        if ($page !== '' && strpos($page, 'opticore-') !== false) {
            add_action('admin_enqueue_scripts', [$this, 'admin']);
        }

        add_action('wp_enqueue_scripts', [$this, 'frontend']);
        add_action('login_enqueue_scripts', [$this, 'login']);
    }

    /**
     * Disallow cloning to preserve singleton state.
     */
    public function __clone(): void {}

    /**
     * Disallow unserialisation to preserve singleton state.
     *
     * @throws \Exception Always thrown to prevent unserialisation.
     */
    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    /**
     * Placeholder for front-end assets. Intentionally empty until required.
     */
    public function frontend(): void
    {
        // Front-end scripts/styles would be enqueued here when needed.
    }

    /**
     * Placeholder for login screen assets. Intentionally empty until required.
     */
    public function login(): void
    {
        // Login screen scripts/styles would be enqueued here when needed.
    }

    /**
     * Enqueue admin assets needed for the OptiCore settings experience.
     */
    public function admin(): void
    {
        // CSS
        wp_enqueue_style('opticore-icons', OPTICORE_PLUGIN_URL . 'assets/admin/css/icons.css', [], OPTICORE_PLUGIN_VERSION);
        wp_enqueue_style('opticore-google-font', 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap', [], null);
        wp_enqueue_style('opticore-material-design-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@300', [], '');
        wp_enqueue_style('opticore-style', OPTICORE_PLUGIN_URL . 'assets/admin/css/style.css', [], OPTICORE_PLUGIN_VERSION);

        // JS
        wp_enqueue_script('opticore-tailwind', OPTICORE_PLUGIN_URL . 'assets/admin/js/tailwindcss-4.1.16.js', [], '4.1.16', false);
        wp_enqueue_script('opticore-script', OPTICORE_PLUGIN_URL . 'assets/admin/js/script.js', ['jquery'], OPTICORE_PLUGIN_VERSION, true);

        wp_localize_script('opticore-script', 'opticore', [
            'ajax' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('opticore-nonce'),
        ]);
    }
}

