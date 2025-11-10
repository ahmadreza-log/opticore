<?php

/**
 * Restrict WordPress REST API access based on the settings dropdown.
 *
 * `$value` can be `active`, `admin-only`, or `disable`.
 */
$setting = $value ?: 'active';

add_action('init', function () use ($setting) {
    $disabled = false;

    if ($setting === 'disable') {
        $disabled = true;
    } elseif ($setting === 'admin-only') {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            $disabled = true;
        }
    }

    if ($disabled) {
        add_filter('rest_authentication_errors', function () {
            return new \WP_Error('rest_authentication_error', __('Sorry, you do not have permission to make REST API requests.', 'opticore'), ['status' => 401]);
        }, 20);

        remove_action('xmlrpc_rsd_apis', 'rest_output_rsd');
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('template_redirect', 'rest_output_link_header', 11);
    }
});
