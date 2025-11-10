<?php

/**
 * Load block styles on demand instead of via the global stylesheet.
 */
add_filter('should_load_separate_core_block_assets', '__return_true');
