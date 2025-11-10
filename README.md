# OptiCore

OptiCore is a performance-focused WordPress plugin that ships with a curated set of switches for common front-end and back-end optimizations. The plugin exposes a modern settings panel inside the admin dashboard that lets site owners toggle features without writing code, while developers can selectively load the underlying optimisation snippets on demand.

## Features

- Disable heavy or unused core assets (emojis, Dashicons, embeds, jQuery Migrate, global styles, REST links, etc.).
- Fine tune editor ergonomics with autosave interval and revision limits.
- Control the WordPress Heartbeat API, including frequency and page-level exceptions.
- Harden installations by disabling XML-RPC, blocking anonymous REST requests, and hiding version metadata.
- Remove miscellaneous render-blocking items such as RSS feeds, self-pingbacks, and HTML comments.
- Persist settings using the WordPress options API with a zero-configuration setup.

## Requirements

- WordPress 5.8 or newer.
- PHP 7.4 or newer.

## Installation

1. Download or clone this repository into your WordPress installation under `wp-content/plugins/opticore`.
2. In the WordPress admin, navigate to **Plugins → Installed Plugins**.
3. Activate **OptiCore**.

> **Tip:** When developing locally, you can run `composer dump-autoload` inside the plugin directory if you add new PHP namespaces.

## Usage

1. Open **OptiCore → Settings** in the WordPress admin menu.
2. Toggle the features you wish to enable. Some items expose additional configuration fields (e.g., REST API access level, Heartbeat behaviour, autosave interval).
3. Click **Save Changes**. The plugin stores options via AJAX and reports the status using toast notifications.

All enabled settings are loaded dynamically at runtime. The plugin only requires the PHP snippets associated with active toggles, keeping the footprint minimal.

## Development

- Autoloading is handled by `vendor/Autoloader.php`, which maps the `Opticore` namespace to `includes/`.
- Admin UI assets live under `assets/admin`. Tailwind utility classes are inlined via `tailwindcss-4.1.16.js`.
- Settings sections and field metadata are defined in `includes/Framework.php`. Rendering logic automatically honours field dependencies.
- JavaScript that drives the settings screen (menu navigation, AJAX, dependency toggling) is located at `assets/admin/js/script.js`.

When contributing code:

1. Ensure PHP files follow WordPress coding standards.
2. Provide internationalisation (i18n) wrapping for user-facing strings.
3. Add feature flags through the framework so they can be toggled safely.

## Testing

- Verify the settings screen behaves correctly with JavaScript both enabled and disabled (AJAX gracefully degrades to standard form submission through progressive enhancement work in progress).
- Test toggled features on the front end and admin area using various roles.
- Inspect browser console and server logs for missing assets (e.g., custom Heartbeat replacements) after enabling options.

## Support

- Check open issues in the GitHub repository before filing a new one.
- When reporting a bug, include WordPress/PHP versions, active theme, and the OptiCore settings export if possible.
- For security disclosures, please follow the process in `SECURITY.md`.

## License

OptiCore is licensed under the GPL v2 (or later). See `LICENSE` for the full text.


