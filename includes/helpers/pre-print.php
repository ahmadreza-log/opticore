<?php
if (!function_exists('opticore_pre_print')) {
    function opticore_pre_print($data)
    {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}
