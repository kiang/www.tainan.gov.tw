<?php
// Base directory path
$basePath = dirname(__DIR__);

// Load the JSON data file
$json = json_decode(file_get_contents($basePath . '/docs/data/2025/2025-04-04.json'), true);

// Process each entry and remove its raw HTML file
foreach($json AS $k => $v) {
    // Parse URL to get node and section IDs
    $parts = parse_url($v);
    parse_str($parts['query'], $q);
    
    // Build raw file path and remove if exists
    $f = $basePath . '/raw/' . $q['n'] . '/node_' . $q['s'] . '.html';
    if(file_exists($f)) {
        unlink($f);
    }
}
