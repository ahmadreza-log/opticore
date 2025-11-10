<?php

/**
 * Disable RSS/Atom feeds and redirect visitors back to the homepage.
 */
add_action('init', function () {
    function disable_feed()
    {
        wp_die(
            sprintf(
                esc_html__('No feed available, please visit our %1$shomepage%2$s!','opticore'),
                ' <a href="' . esc_url(home_url('/')) . '">',
                '</a>'
            )
        );
    }

    add_action('do_feed_rdf', 'disable_feed', 1);
    add_action('do_feed_rss', 'disable_feed', 1);
    add_action('do_feed_rss2', 'disable_feed', 1);
    add_action('do_feed_atom', 'disable_feed', 1);
    add_action('do_feed_rss2_comments', 'disable_feed', 1);
    add_action('do_feed_atom_comments', 'disable_feed', 1);

    remove_action('wp_head', 'feed_links_extra', 3);
    remove_action('wp_head', 'feed_links', 2);
});
