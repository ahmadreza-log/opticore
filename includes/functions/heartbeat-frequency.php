<?php

/**
 * Adjust the heartbeat interval when the feature is enabled.
 *
 * `$value` contains the selected number of seconds from the settings UI.
 */
$setting = $value ?: 'default';

add_filter('heartbeat_settings', function ($settings) use ($setting) {
    if ($setting == 'default') {
        return $settings;
    }

    $settings['interval'] = $setting;
    $settings['minimalInterval'] = $setting;

    return $settings;
});
