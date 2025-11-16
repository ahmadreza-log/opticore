# OptiCore ⚡

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759B?logo=wordpress&logoColor=white)](#-requirements)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?logo=php&logoColor=white)](#-requirements)
[![License](https://img.shields.io/badge/License-GPLv2+-brightgreen.svg)](#-license)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-ff69b4.svg)](CONTRIBUTING.md)
[![GitHub Stars](https://img.shields.io/github/stars/ahmadreza-log/opticore?style=social)](https://github.com/ahmadreza-log/opticore)

> A lean, well-documented performance toolkit for WordPress – focused on real‑world speed without breaking your theme.  
> The codebase is heavily documented with inline PHPDoc and JS comments so you can treat it as a living guide to common WordPress optimisations.

## 📚 Table of Contents

- [✨ Features](#-features)
- [✅ Requirements](#-requirements)
- [📦 Installation](#-installation)
- [🛠️ Usage](#-usage)
- [🔧 Development](#-development)
- [🔒 Security](#-security)
- [🧪 Testing](#-testing)
- [💬 Support](#-support)
- [📄 License](#-license)

## ✨ Features

- Disable heavy or unused core assets (emojis, Dashicons, embeds, jQuery Migrate, global styles, REST links, etc.).
- Fine tune editor ergonomics with autosave interval and revision limits.
- Control the WordPress Heartbeat API, including frequency and page-level exceptions.
- Harden installations by disabling XML-RPC, blocking anonymous REST requests, and hiding version metadata.
- Remove miscellaneous render-blocking items such as RSS feeds, self-pingbacks, and HTML comments.
- Persist settings using the WordPress options API with a zero-configuration setup.
- Harden direct-access surface area by shipping `index.php` stubs in all plugin directories.

<details>
<summary><strong>Advanced optimisations & developer goodies</strong> 🔍</summary>

- Granular REST API controls (active / admin-only / fully disabled).
- Heartbeat frequency tuning and optional replacement script hooks.
- Minify HTML output while safely preserving `<script>`, `<style>`, `<pre>`, and `<textarea>` blocks.
- CSS minification with cache-aware file output or inline injection.
- Self-contained feature snippets under `includes/functions/` that can be audited or extended individually.

</details>

## ✅ Requirements

- WordPress 5.8 or newer.
- PHP 7.4 or newer.

## 📦 Installation

1. Download or clone this repository into your WordPress installation under `wp-content/plugins/opticore`.
2. In the WordPress admin, navigate to **Plugins → Installed Plugins**.
3. Activate **OptiCore**.

> 💡 **Tip:** When developing locally, you can run `composer dump-autoload` inside the plugin directory if you add new PHP namespaces.

## 🛠️ Usage

1. Open **OptiCore → Settings** in the WordPress admin menu.
2. Toggle the features you wish to enable. Some items expose additional configuration fields (e.g., REST API access level, Heartbeat behaviour, autosave interval).
3. Click **Save Changes**. The plugin stores options via AJAX and reports the status using toast notifications.

All enabled settings are loaded dynamically at runtime. The plugin only requires the PHP snippets associated with active toggles, keeping the footprint minimal.

## 🔧 Development

- Autoloading is handled by `vendor/Autoloader.php`, which maps the `Opticore` namespace to `includes/`.
- Admin UI assets live under `assets/admin`. Tailwind utility classes are inlined via `tailwindcss-4.1.16.js`.
- Settings sections and field metadata are defined in `includes/Framework.php`. Rendering logic automatically honours field dependencies.
- JavaScript that drives the settings screen (menu navigation, AJAX, dependency toggling) is located at `assets/admin/js/script.js`.
- Each optimisation snippet under `includes/functions/` is self-contained and documented, making it easy to audit or extend.

When contributing code:

1. Ensure PHP files follow WordPress coding standards.
2. Provide internationalisation (i18n) wrapping for user-facing strings.
3. Add feature flags through the framework so they can be toggled safely.
4. Keep inline comments and PHPDoc blocks up to date with behaviour changes.
5. See [`CONTRIBUTING.md`](CONTRIBUTING.md) for the full contribution guide.

## 🧱 Architecture at a Glance

```text
opticore/
├─ opticore.php          # Plugin bootstrap & singleton orchestrator
├─ includes/
│  ├─ Admin.php          # Admin menu, pages, and settings registration
│  ├─ Ajax.php           # AJAX endpoint for saving settings
│  ├─ Enqueue.php        # Asset loading for admin / login / front-end
│  ├─ Framework.php      # Settings framework (sections, fields, dependencies)
│  ├─ helpers/           # Shared helpers (CSS minifier, debug printer, …)
│  └─ functions/         # One PHP snippet per optimisation toggle
├─ assets/
│  ├─ admin/             # Settings UI (Tailwind runtime, JS, CSS, fonts)
│  └─ css, js/           # Placeholders for future public assets
├─ admin/                # Dashboard & settings view templates
├─ vendor/               # Lightweight PSR-4-style autoloader
└─ *.md, LICENSE         # GitHub-friendly documentation & licensing
```

## 🔒 Security

- All optimisation code is loaded conditionally based on saved settings to keep the runtime surface small.
- Directory browsing is mitigated by `index.php` guards in all plugin subdirectories.
- For vulnerability reports, please follow the process in [`SECURITY.md`](SECURITY.md).

## 🧪 Testing

- Verify the settings screen behaves correctly with JavaScript both enabled and disabled (AJAX gracefully degrades to standard form submission through progressive enhancement work in progress).
- Test toggled features on the front end and admin area using various roles.
- Inspect browser console and server logs for missing assets (e.g., custom Heartbeat replacements) after enabling options.

## 💬 Support

- Check open issues in the GitHub repository before filing a new one.
- When reporting a bug, include WordPress/PHP versions, active theme, and the OptiCore settings export if possible.
- For security disclosures, please follow the process in `SECURITY.md`.

## 📄 License

OptiCore is licensed under the GPL v2 (or later). See `LICENSE` for the full text.

Made with ❤️ for developers who care about both **performance** and **clean code**.


