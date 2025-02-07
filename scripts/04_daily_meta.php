<?php
$basePath = dirname(__DIR__);

// Process all JSON files in the data directory
foreach(glob($basePath . '/docs/data/*/*/*.json') AS $jsonFile) {
    // Extract path information
    $p = pathinfo($jsonFile);
    $parts = explode('_', $p['filename']);

    // Remove invalid files
    if(empty($parts[1])) {
        unlink($jsonFile);
    } else {
        // Update or create metadata file
        $metaFile = dirname($p['dirname']) . '/' . $parts[0] . '.json';
        if(file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
        } else {
            $meta = [];
        }

        // Add new entries to metadata
        if(!isset($meta[$parts[1]])) {
            $json = json_decode(file_get_contents($jsonFile), true);
            $meta[$parts[1]] = $json['url'];
            ksort($meta);
            file_put_contents($metaFile, json_encode($meta));
        }
    }
}