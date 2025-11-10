<?php

add_action('init', function () {
    remove_action('wp_head', 'wp_shortlink_wp_head');
});
