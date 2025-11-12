<?php

/**
 * Compress front-end HTML output to reduce payload size.
 *
 * Skips minification within <pre>, <textarea>, <script>, and <style> blocks to
 * avoid breaking formatting or executable code.
 */
add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }

    ob_start(static function (string $buffer): string {
        if (trim($buffer) === '') {
            return $buffer;
        }

        $preserved = [];
        $placeholderPrefix = '__OPTICORE_HTML_BLOCK_';
        $index = 0;

        $buffer = preg_replace_callback(
            '#<(script|style|pre|textarea)\b[^>]*>.*?</\1>#is',
            static function (array $matches) use (&$preserved, $placeholderPrefix, &$index) {
                $token = sprintf('%s%d__', $placeholderPrefix, $index++);
                $preserved[$token] = $matches[0];
                return $token;
            },
            $buffer
        );

        if ($buffer === null) {
            return '';
        }

        $buffer = preg_replace([
            // Collapse whitespace between HTML tags.
            '/>\s+</s',
            // Trim leading/trailing whitespace.
            '/^\s+|\s+$/m',
            // Reduce multiple spaces to a single space.
            '/\s{2,}/'
        ], [
            '><',
            '',
            ' '
        ], $buffer);

        if ($buffer === null) {
            return '';
        }

        foreach ($preserved as $token => $content) {
            $buffer = str_replace($token, $content, $buffer);
        }

        return trim($buffer);
    });
});

