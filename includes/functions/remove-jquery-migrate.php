<?php

/**
 * Drop jQuery Migrate from the front-end dependency tree when safe.
 */
add_filter('wp_default_scripts', function ($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, ['jquery-migrate']);
        }
    }
});
