<?php
// Server-side rendering for Block Name block

$content = $attributes['content'] ?? '';
$block_data = [
    'title' => 'Block Name',
    'content' => $content,
    'slug' => 'block-name'
];

echo view('blocks.block-name', $block_data)->render();