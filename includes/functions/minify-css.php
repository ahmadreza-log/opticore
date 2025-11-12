<?php

$settings = get_option('opticore-settings');

if (!is_array($settings)) {
    $settings = [];
}

add_action('wp_enqueue_scripts', function () use ($settings) {

    $exclude = isset($settings['exclude-css']) ? explode("\n", $settings['exclude-css']) : [];

    $styles = wp_styles();

    if (empty($styles->queue)) {    
        return;
    }

    foreach ($styles->queue as $handle) {

        if (!isset($styles->registered[$handle])) {
            continue;
        }

        $style = $styles->registered[$handle];
        $file = $style->src;
        $deps = $style->deps;
        $media = $style->args ?? 'all';

        if (in_array($file, $exclude, true)) {
            continue;
        }

        // Dequeue and deregister the style.
        wp_dequeue_style($handle);
        wp_deregister_style($handle);

        $output = $settings['output-type-css'] ?? 'file';

        if ($output === 'file') {
            $relative = 'cache/opticore/minified/css';
            $directory = trailingslashit(WP_CONTENT_DIR) . $relative;
            $filename = md5($handle) . '.min.css';
            $path = trailingslashit($directory) . $filename;
            $url = trailingslashit(content_url($relative)) . $filename;

            if (file_exists($path)) {
                wp_enqueue_style($handle, $url, $deps, filemtime($path), $media);
                continue;
            }
        }

        if (!wp_http_validate_url($file)) {
            continue;
        }

        $response = wp_remote_get($file, ['timeout' => 5]);

        if (is_wp_error($response)) {
            continue;
        }

        $status = wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            continue;
        }

        $css = opticore_minify_css(
            wp_remote_retrieve_body($response)
        );

        switch ($output) {
            case 'internal':
                wp_add_inline_style($handle, $css);
            case 'file':
            default:
                if (!wp_mkdir_p($directory)) {
                    break;
                }

                $written = file_put_contents($path, $css);

                if ($written === false) {
                    break;
                }

                wp_enqueue_style($handle, $url, $deps, filemtime($path), $media);
                wp_style_add_data($handle, 'opticore-minified', $path);
        }
    }
}, 1000);
