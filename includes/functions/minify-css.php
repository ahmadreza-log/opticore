<?php

$settings = get_option('opticore-settings');

if (!is_array($settings)) {
    $settings = [];
}

add_action('wp_enqueue_scripts', function () use ($settings) {

    $exclude_css = isset($settings['exclude-css']) ? explode("\n", $settings['exclude-css']) : [];

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
        $ver = $style->ver;
        $media = isset($style->args) ? $style->args : 'all';

        if (in_array($file, $exclude_css)) {
            continue;
        }

        wp_dequeue_style($handle);
        wp_deregister_style($handle);

        $output_type = isset($settings['output-type-css']) ? $settings['output-type-css'] : 'file';

        if ($output_type === 'file') {
            $relative_dir = 'cache/opticore/minified/css';
            $directory = trailingslashit(WP_CONTENT_DIR) . $relative_dir;
            $filename = md5($handle) . '.min.css';
            $path = trailingslashit($directory) . $filename;
            $url = trailingslashit(content_url($relative_dir)) . $filename;

            if (file_exists($path)) {
                wp_enqueue_style($handle, $url, $deps, filemtime($path), $media);
                continue;
            }
        }

        if (wp_http_validate_url($file)) {
            $response = wp_remote_get($file, ['timeout' => 5]);

            if (is_wp_error($response)) {
                continue;
            }

            $status_code = wp_remote_retrieve_response_code($response);

            if ($status_code < 200 || $status_code >= 300) {
                continue;
            }

            $css = wp_remote_retrieve_body($response);

            if (!is_string($css) || $css === '') {
                continue;
            }

            // Normalize line endings and trim once.
            $css = trim(str_replace(["\r\n", "\r"], "\n", $css));

            // Remove all block comments but keep /*! ... */.
            $css = preg_replace('#/\*(?!\!)(?>.|\n)*?\*/#', '', $css);

            // Collapse whitespace around symbols.
            $css = preg_replace(
                [
                    '/\s*([{};,>:])\s*/',
                    '/\s{2,}/',
                    '/;}/', // remove trailing semicolons before }
                ],
                [
                    '$1',
                    ' ',
                    '}',
                ],
                $css
            );

            // Remove space around operators inside calc() and similar expressions.
            $css = preg_replace('/\s*([+\-*\/])\s*(?=[^{}]*\))/', '$1', $css);

            // Shorten zeros.
            $css = preg_replace(
                [
                    '/(?<=[:\s])0+\.(\d+)/',
                    '/(?<!\d)0+px\b/',
                    '/(:|\s)0+%/',
                ],
                [
                    '.$1',
                    '0',
                    '$1' . '0',
                ],
                $css
            );

            // Remove unnecessary units on zero values.
            $css = preg_replace('/(:|\s)0(?:in|cm|mm|pc|pt|px|em|ex|ch|rem|vh|vw|vmin|vmax|%)\b/', '$1' . '0', $css);

            $css = trim($css);

            if ($output_type === 'file') {
                if (!wp_mkdir_p($directory)) {
                    continue;
                }

                $written = file_put_contents($path, $css);

                if ($written === false) {
                    continue;
                }

                wp_enqueue_style($handle, $url, $deps, filemtime($path), $media);
            } elseif ($output_type === 'inline') {
                wp_register_style($handle, false, $deps, $ver, $media);
                wp_enqueue_style($handle);
                wp_add_inline_style($handle, $css);
            }
        }
    }
}, 9999);
