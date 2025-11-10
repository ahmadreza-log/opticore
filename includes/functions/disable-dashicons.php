<?php

/**
 * Remove Dashicons on the front end when not required.
 *
 * Also hides the admin bar to eliminate the dependency because Dashicons can
 * reappear otherwise. Ensure site admins are comfortable losing the toolbar.
 */
add_action('wp_enqueue_scripts', function () {
    wp_dequeue_style('dashicons');
    wp_deregister_style('dashicons');
    show_admin_bar(false);
});
