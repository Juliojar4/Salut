<?php

use App\Blocks\BlockManager;

/*
|--------------------------------------------------------------------------
| Register Custom Blocks
|--------------------------------------------------------------------------
|
| This file is responsible for registering all custom Gutenberg blocks.
| The BlockManager will automatically discover and register all blocks
| listed in the $blocks array.
|
*/

// Initialize and register all blocks
add_action('init', function () {
    $blockManager = new BlockManager();
    $blockManager->register();
});
