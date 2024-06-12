<?php
$basePath = dirname(__DIR__);
$json = json_decode(file_get_contents($basePath . '/data/2024/2024-06-12.json'), true);
foreach($json AS $k => $v) {
	$parts = parse_url($v);
	parse_str($parts['query'], $q);
	$f = $basePath . '/raw/' . $q['n'] . '/node_' . $q['s'] . '.html';
	if(file_exists($f)) {
		unlink($f);
	}
}
