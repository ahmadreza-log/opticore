<?php

/**
 * Inject a tiny blank favicon to avoid 404s on sites without a custom icon.
 *
 * This can reduce unnecessary requests when browsers probe `/favicon.ico` but
 * no icon has been configured. 🧩
 */
add_action('wp_head', function () {
    echo '<link href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAYAAABPYyMiAAAABmJLR0T///////8JWPfcAAAACXBIWXMAAABIAAAASABGyWs+AAAAF0lEQVRIx2NgGAWjYBSMglEwCkbBSAcACBAAAeaR9cIAAAAASUVORK5CYII=" rel="icon" type="image/x-icon" />';
});