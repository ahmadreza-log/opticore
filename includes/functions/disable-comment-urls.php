<?php

/**
 * Remove website URL fields and links from comment authors to reduce spam value.
 */
add_action('template_redirect', function () {

    add_filter('get_comment_author_link', function ($return, $author, $comment_ID) {
        return $author;
    }, 10, 3);
    
    add_filter('get_comment_author_url', '__return_false');
    
    add_filter('comment_form_default_fields', function ($fields) {
        unset($fields['url']);
        return $fields;
    }, 9999);

});
