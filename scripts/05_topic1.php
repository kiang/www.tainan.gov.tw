<?php
/*
cd data && grep 'title": "臺南市寺廟廟會活 動明細' -r . > ../topics/臺南市寺廟廟會活動明細/list.txt
*/
$basePath = dirname(__DIR__);
$rawPath = $basePath . '/raw/topic1';
if (!file_exists($rawPath)) {
    mkdir($rawPath, 0777, true);
}

$lines = explode("\n", file_get_contents($basePath . '/topics/臺南市寺廟廟會活動明細/list.txt'));
$pool = [];
$count = [];
foreach ($lines as $line) {
    $parts = explode(':    "title": "', $line);
    if (count($parts) === 2) {
        $parts[0] = substr($parts[0], 2);
        $data = json_decode(file_get_contents($basePath . '/docs/data/' . $parts[0]), true);
        $parts = preg_split('/[^0-9]+/', $data['url']);
        $rawFile = $rawPath . '/' . $parts[1] . '_' . $parts[2] . '.html';
        if (!file_exists($rawFile)) {
            file_put_contents($rawFile, file_get_contents($data['url']));
        }
        $raw = file_get_contents($rawFile);
        
        $pos = strpos($raw, '<th scope="col">');
        if (false !== $pos) {
            $posEnd = strpos($raw, '</tbody>', $pos);
            $table = str_replace('　', '', substr($raw, $pos, $posEnd - $pos));
            $trs = explode('</tr>', $table);
            foreach ($trs as $tr) {
                $tds = explode('</td>', $tr);
                if (count($tds) === 8) {
                    foreach ($tds as $k => $v) {
                        $tds[$k] = trim(strip_tags($v));
                    }
                    $tds[4] = preg_split('/[^0-9]+/', $tds[4]);
                    $countDates = count($tds[4]);
                    switch ($countDates) {
                        case 3:
                            $tds[4][0] += 1911;
                            $dateBegin = $dateEnd = mktime(null, null, null, $tds[4][1], $tds[4][2], $tds[4][0]);
                            break;
                        case 4:
                            $tds[4][0] += 1911;
                            $dateBegin = mktime(null, null, null, $tds[4][1], $tds[4][2], $tds[4][0]);
                            $dateEnd = mktime(null, null, null, $tds[4][1], intval($tds[4][3]), $tds[4][0]);
                            break;
                        case 5:
                            $tds[4][0] += 1911;
                            $dateBegin = mktime(null, null, null, $tds[4][1], $tds[4][2], $tds[4][0]);
                            $dateEnd = mktime(null, null, null, $tds[4][3], $tds[4][4], $tds[4][0]);
                            break;
                        case 6:
                            $tds[4][0] += 1911;
                            $tds[4][3] += 1911;
                            $dateBegin = mktime(null, null, null, $tds[4][1], $tds[4][2], $tds[4][0]);
                            $dateEnd = mktime(null, null, null, $tds[4][4], $tds[4][5], $tds[4][3]);
                            break;
                    }
                    if(!isset($pool[$tds[0]])) {
                        $pool[$tds[0]] = [];
                    }
                    if(!isset($pool[$tds[0]][$tds[1]])) {
                        $pool[$tds[0]][$tds[1]] = [];
                    }
                    if(!empty($tds[7])) {
                        $tds[6] .= "\n" . $tds[7];
                    }
                    $pool[$tds[0]][$tds[1]][$dateBegin] = [
                        'dateBegin' => date('Y-m-d', $dateBegin),
                        'dateEnd' => date('Y-m-d', $dateEnd),
                        'time' => $tds[5],
                        'contact' => $tds[2],
                        'phone' => $tds[3],
                        'event' => $tds[6],
                        'url' => $data['url'],
                    ];
                }
            }
        }
    }
}
$oFh = [];
$poi = json_decode(file_get_contents('/home/kiang/public_html/religion/data/poi/臺南市.json'), true);
$ref = [];
foreach($poi['features'] AS $f) {
    if($f['properties']['類型'] !== '寺廟') {
        continue;
    }
    $pos = strpos($f['properties']['地址'], '區');
    if(false !== $pos) {
        $area = str_replace($f['properties']['行政區'], '', substr($f['properties']['地址'], 0, $pos) . '區');
        $ref[$area][$f['properties']['名稱']] = $f['properties'];
    }
}
$count = [
    'found' => 0,
    'miss' => [],
];
foreach($pool AS $k1 => $v1) {
    if(!isset($oFh[$k1])) {
        $oFh[$k1] = fopen($basePath . '/topics/臺南市寺廟廟會活動明細/' . $k1 . '.csv', 'w');
        fputcsv($oFh[$k1], ['point', 'dateBegin', 'dateEnd', 'time', 'contact', 'phone', 'event', 'url']);
    }
    foreach($v1 AS $k2 => $v2) {
        if(isset($ref[$k1][$k2])) {
            ++$count['found'];
        } else {
            $pointFound = false;
            if(isset($ref[$k1])) {
                foreach($ref[$k1] AS $k => $v) {
                    if(false === $pointFound && false !== strpos($k, $k2)) {
                        $pointFound = true;
                        print_r($k2);
                        print_r($v);
                    }
                }
            }
            $count['miss'][] = $k1 . $k2;
        }
        ksort($v2);
        foreach($v2 AS $line) {
            fputcsv($oFh[$k1], array_merge([$k2], $line));
        }
    }
}
print_r($count);