<?php
$basePath = dirname(__DIR__);
foreach(glob($basePath . '/docs/data/*/*/*.json') AS $jsonFile) {
    $p = pathinfo($jsonFile);
    $parts = explode('_', $p['filename']);
    if(empty($parts[1])) {
        unlink($jsonFile);
    } else {
        $metaFile = dirname($p['dirname']) . '/' . $parts[0] . '.json';
        if(file_exists($metaFile)) {
            $meta = json_decode(file_get_contents($metaFile), true);
        } else {
            $meta = [];
        }
        if(!isset($meta[$parts[1]])) {
            $json = json_decode(file_get_contents($jsonFile), true);
            $meta[$parts[1]] = $json['url'];
            ksort($meta);
            file_put_contents($metaFile, json_encode($meta));
        }
    }
}