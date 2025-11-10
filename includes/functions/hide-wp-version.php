<?php

/**
 * Remove WordPress version disclosure from HTML outputs.
 */
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', static fn() => '');
