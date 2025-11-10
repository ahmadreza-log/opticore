<?php

/**
 * Fully disable XML-RPC access and remove pingback headers.
 */
add_filter('xmlrpc_enabled', '__return_false');
add_filter('pings_open', '__return_false', 9999);
add_filter('pre_update_option_enable_xmlrpc', '__return_false');
add_filter('pre_option_enable_xmlrpc', '__return_zero');

add_filter('wp_headers', function ($headers) {
    unset($headers['X-Pingback'], $headers['x-pingback']);
    return $headers;
});

add_filter('perfmatters_output_buffer_template_redirect', function ($html) {
    preg_match_all('#<link[^>]+rel=["\']pingback["\'][^>]+?\/?>#is', $html, $links, PREG_SET_ORDER);
    if (!empty($links)) {
        foreach ($links as $link) {
            $html = str_replace($link[0], "", $html);
        }
    }
    return $html;
}, 2);

add_action('init', function () {
    if (!isset($_SERVER['SCRIPT_FILENAME'])) {
        return;
    }

    // Direct requests only: short-circuit XML-RPC file execution.
    if ('xmlrpc.php' !== basename($_SERVER['SCRIPT_FILENAME'])) {
        return;
    }

    $header = 'HTTP/1.1 403 Forbidden';
    header($header);
    echo $header;
    die();
});
