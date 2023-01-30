<?php
$basePath = dirname(__DIR__);
$json = json_decode(file_get_contents($basePath . '/data/2023/2023-01-30.json'), true);
foreach($json AS $k => $v) {
	$parts = parse_url($v);
	parse_str($parts['query'], $q);
	$f = $basePath . '/raw/' . $q['n'] . '/node_' . $q['s'] . '.html';
	if(file_exists($f)) {
		unlink($f);
	}
}
