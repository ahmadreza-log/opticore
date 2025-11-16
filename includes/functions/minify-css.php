<?php

/**
 * Minify and optionally rewrite enqueued CSS styles on the front‑end.
 *
 * This file is loaded conditionally when the "Minify CSS" feature is enabled in the settings
 * screen. It inspects the global styles queue, downloads each stylesheet, minifies the content,
 * and then either:
 * - serves it from a generated file under `wp-content/cache/opticore/minified/css`, or
 * - injects it as inline CSS, depending on the chosen output mode.
 */

// Retrieve the current OptiCore settings once; this array is shared with the closure below.
$settings = get_option('opticore-settings');

if (!is_array($settings)) {
    // Normalise to an array so later lookups do not need to handle non‑array cases.
    $settings = [];
}

add_action('wp_enqueue_scripts', function () use ($settings) {
    /**
     * Build a list of stylesheet URLs that should be ignored by the minifier.
     *
     * The textarea in the settings UI stores raw URLs separated by newlines. We explode the
     * string into an array so we can run `in_array()` checks for each enqueued style below.
     *
     * @var array<int, string> $exclude
     */
    $exclude = isset($settings['exclude-css']) ? explode("\n", $settings['exclude-css']) : [];

    // Obtain the global styles registry so we can inspect the queue and registered styles.
    $styles = wp_styles();

    // If nothing is queued there is no work to do for this request.
    if (empty($styles->queue)) {
        return;
    }

    // Iterate over every stylesheet handle that WordPress plans to output.
    foreach ($styles->queue as $handle) {
        // Skip unknown handles that have been queued but not registered.
        if (!isset($styles->registered[$handle])) {
            continue;
        }

        // Extract core properties from the registered style object.
        $style = $styles->registered[$handle];
        $file = $style->src;                 // Original stylesheet URL.
        $deps = $style->deps;                // Dependency handles that should remain intact.
        $media = $style->args ?? 'all';      // Media attribute, defaulting to "all".

        // If the stylesheet is explicitly excluded, leave it untouched.
        if (in_array($file, $exclude, true)) {
            continue;
        }

        /**
         * At this point we know the stylesheet is eligible for optimisation.
         *
         * First, dequeue and deregister the original style so we can take control over how it
         * is printed (either via a generated file or inline CSS).
         */
        wp_dequeue_style($handle);
        wp_deregister_style($handle);

        // Determine how the minified CSS should be delivered ("file" or "internal").
        $output = $settings['output-type-css'] ?? 'file';

        /**
         * When the output type is "file" we generate a cache path under wp-content/cache/opticore
         * and attempt to reuse an existing minified file if one has already been written.
         */
        if ($output === 'file') {
            $relative = 'cache/opticore/minified/css';
            $directory = trailingslashit(WP_CONTENT_DIR) . $relative;
            $filename = md5($handle) . '.min.css';
            $path = trailingslashit($directory) . $filename;
            $url = trailingslashit(content_url($relative)) . $filename;

            // If a cached file is already present, enqueue it immediately and skip processing.
            if (file_exists($path)) {
                wp_enqueue_style($handle, $url, $deps, filemtime($path), $media);
                continue;
            }
        }

        // Only attempt to fetch remote CSS when the URL passes WordPress' validation rules.
        if (!wp_http_validate_url($file)) {
            continue;
        }

        // Download the original stylesheet contents with a small timeout to avoid blocking.
        $response = wp_remote_get($file, ['timeout' => 5]);

        // Abort this handle if the HTTP request encountered an error.
        if (is_wp_error($response)) {
            continue;
        }

        // Guard against non‑2xx responses so we do not attempt to minify error pages.
        $status = wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            continue;
        }

        // Run the fetched CSS through the helper minifier shipped with the plugin.
        $css = opticore_minify_css(
            wp_remote_retrieve_body($response)
        );

        // If minification failed for any reason, skip rewriting this handle but continue the loop.
        if ($css === null || $css === '') {
            continue;
        }

        /**
         * Re‑enqueue the stylesheet according to the configured output mode.
         *
         * - "internal": CSS is injected directly into the page inside a <style> tag.
         * - "file": CSS is written to disk once and then served as a static asset.
         */
        switch ($output) {
            case 'internal':
                // Attach the minified CSS as inline styles associated with the original handle.
                wp_add_inline_style($handle, $css);
                // Intentionally fall through to the "file" case so a cached file is also created.

            case 'file':
            default:
                // Ensure the cache directory exists before attempting to write the file.
                if (!wp_mkdir_p($directory)) {
                    break;
                }

                $written = file_put_contents($path, $css);

                // If writing failed, do not enqueue a broken asset.
                if ($written === false) {
                    break;
                }

                // Enqueue the generated file and attach metadata for debugging/inspection.
                wp_enqueue_style($handle, $url, $deps, filemtime($path), $media);
                wp_style_add_data($handle, 'opticore-minified', $path);
        }
    }
}, 1000);
