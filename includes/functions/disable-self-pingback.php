<?php

/**
 * Disable self pingback.
 *
 * @param array $links The links to check.
 * @return void
 */
add_action('pre_ping', function (&$links) {
    $home = home_url();
    foreach ($links as $l => $link) {
        if (strpos($link, $home) === 0) {
            unset($links[$l]);
        }
    }
});