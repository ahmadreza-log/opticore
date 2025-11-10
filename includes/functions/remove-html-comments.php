<?php

/**
 * Strip non-essential HTML comments on the front end to reduce page weight.
 */
add_action('template_redirect', function () {
    if (is_admin()) {
        return;
    }

    ob_start(static function (string $buffer): string {
        // Allow conditional comments and block comments while removing the rest.
        return preg_replace('/<!--(?!\[if|\s*(?:<!|\/?wp:|\[endif)).*?-->/s', '', $buffer);
    });
});
