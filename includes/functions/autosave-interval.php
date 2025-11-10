<?php

/**
 * Override the core autosave interval (seconds) when not defined elsewhere.
 */
$setting = $value ?: 60;

if (!defined('AUTOSAVE_INTERVAL')) {
    define('AUTOSAVE_INTERVAL', $setting);
}