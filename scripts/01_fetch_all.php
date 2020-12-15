<?php
$basePath = dirname(__DIR__);
$nodes = array('13370', '13371', '13303', '13372', '1962', '1963', '13373', '4960', '4961', '4962', '4963', '4964', '4965', '4966', '4967');
$newsContentUrl = 'https://www.tainan.gov.tw/News_Content.aspx?';
foreach($nodes AS $node) {
    $rawPath = $basePath . '/raw/' . $node;
    if(!file_exists($rawPath)) {
        mkdir($rawPath, 0777, true);
    }
    $totalPages = 1;
    for($i = 1; $i <= $totalPages; $i++) {
        $rawFile = $rawPath . '/' . $i . '.html';
        if(!file_exists($rawFile)) {
            file_put_contents($rawFile, file_get_contents('https://www.tainan.gov.tw/News.aspx?n=' . $node . '&PageSize=500&page=' . $i));
        }
        $rawPage = file_get_contents($rawFile);
        if($totalPages === 1) {
            $pos = strpos($rawPage, '<span class="count">');
            $posEnd = strpos($rawPage, '</span>', $pos);
            $recordCount = intval(substr(strip_tags(substr($rawPage, $pos, $posEnd - $pos)), 1));
            $totalPages = ceil($recordCount / 500);
        }
        $pos = strpos($rawPage, '<td class="CCMS_jGridView_td_Class_0"');
        while(false !== $pos) {
            $posEnd = strpos($rawPage, '</tr>', $pos);
            $line = substr($rawPage, $pos, $posEnd - $pos);
            $cols = explode('</td>', $line);
            $link = '';
            $isFour = false;
            if(count($cols) === 4) {
                $isFour = true;
            }
            foreach($cols AS $k => $v) {
                if(($isFour && $k === 1) || (!$isFour && $k === 2)) {
                    $parts = explode('News_Content.aspx?', $v);
                    if(count($parts) === 2) {
                        $partPos = strpos($parts[1], '"');
                        $link = substr($parts[1], 0, $partPos);
                    }
                }
                $cols[$k] = trim(strip_tags($v));
            }
            $parts = explode('s=', $link);
            if(!empty($parts[1])) {
                $nodeFile = $rawPath . '/node_' . $parts[1] . '.html';

                $dateParts = explode('-', $cols[0]);
                $dateParts[0] += 1911;
                $json = array(
                    'published' => implode('-', $dateParts),
                    'title' => '',
                    'department' => '',
                    'url' => $newsContentUrl . $link,
                );
                if(count($cols) === 4) {
                    $json['title'] = $cols[1];
                    $json['department'] = $cols[2];
                } else {
                    $json['title'] = $cols[2];
                    $json['department'] = $cols[3];
                }
                $dataPath = $basePath . '/data/' . $dateParts[0] . '/' . $dateParts[1];
                if(!file_exists($dataPath)) {
                    mkdir($dataPath, 0777, true);
                }
                $jsonFile = $dataPath . '/' . $json['published'] . '_' . $parts[1] . '.json';
                
                if(!file_exists($nodeFile)) {
                    error_log('fetching ' . $link);
                    file_put_contents($nodeFile, file_get_contents($json['url']));
                }
                $node = file_get_contents($nodeFile);
                $nodePos = strpos($node, '<div class="area-essay page-caption-p"');
                if(false !== $nodePos) {
                    $nodePosEnd = strpos($node, '<div class="area-editor system-info"', $nodePos);
                    $body = substr($node, $nodePos, $nodePosEnd - $nodePos);
                    $body = str_replace(array('</p>', '&nbsp;'), array("\n", ''), $body);
                    $json['content'] = trim(strip_tags($body));
                    $nodePos = $nodePosEnd;
                } else {
                    $json['content'] = '';
                    $nodePos = strpos($node, '<div class="area-editor system-info"');
                }
                if(false !== $nodePos) {
                    $nodePosEnd = strpos($node, '<div class="group page-footer"', $nodePos);
                    $body = substr($node, $nodePos, $nodePosEnd - $nodePos);
                    $json['tags'] = mb_substr(trim(strip_tags($body)), 3, null, 'utf-8');    
                } else {
                    $json['tags'] = '';
                }
                file_put_contents($jsonFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
    
            $pos = strpos($rawPage, '<td class="CCMS_jGridView_td_Class_0"', $posEnd);
        }
    }
}