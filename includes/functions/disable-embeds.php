<?php

/**
 * Strip the WordPress oEmbed infrastructure to reduce HTTP requests and scripts.
 */
add_action('init', function () {
    remove_action('rest_api_init', 'wp_oembed_register_route');

    add_filter('embed_oembed_discover', '__return_false');

    remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');

    add_filter('tiny_mce_plugins', function ($plugins) {
        return array_diff($plugins, ['wpembed']);
    });

    add_filter('rewrite_rules_array', function ($rules) {
        foreach ($rules as $rule => $rewrite) {
            if (false !== strpos($rewrite, 'embed=true')) {
                unset($rules[$rule]);
            }
        }

        return $rules;
    });

    remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
}, 9999);
