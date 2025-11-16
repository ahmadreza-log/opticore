<?php

/**
 * Small debugging helper to pretty-print values inside a <pre> block.
 *
 * This is intentionally simple and only intended for local development or quick
 * inspection during troubleshooting. 🔍
 */
if (!function_exists('opticore_pre_print')) {
    /**
     * Dump a variable to the browser wrapped in <pre> tags.
     *
     * @param mixed $data Arbitrary data structure to print.
     * @return void
     */
    function opticore_pre_print($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}
