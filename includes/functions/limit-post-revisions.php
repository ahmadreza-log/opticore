<?php

/**
 * Limit the number of stored revisions if not overridden elsewhere.
 */
$setting = $value ?: 5;

if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', $setting);
}
