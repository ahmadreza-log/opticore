<?php

/**
 * Helper: compact a CSS string by removing comments and unnecessary whitespace.
 *
 * This function is intentionally conservative: it keeps comments intact and
 * avoids touching content inside functions such as `calc()` beyond safe whitespace
 * removal. It is used by OptiCore's CSS optimisation feature. 🎯
 */
if (!function_exists('opticore_minify_css')) {
    /**
     * Perform a series of regex-based transformations to shrink CSS content.
     *
     * @param string $css Raw stylesheet contents.
     * @return string|null Minified CSS or null when input is not valid.
     */
    function opticore_minify_css($css)
    {
        if (!is_string($css) || $css === '') {
            return null;
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

        return $css;
    }
}
