<?php
$basePath = dirname(__DIR__);

// Define RSS feed URLs for different content types
$nodes = array(
    // Key is content type, value is RSS feed URL
    '市府新聞' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=24474215983F6554',
    '機關新聞' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=9B973A5871579AC7',
    '勞工RSS' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=4933DB35000610C4',
    '機關新聞-RSS-教育局' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=B2C9ACBE62E87999',
    '市政公告' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=0C669D9634F511BC',
    '熱門活動' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=7D6B9BB2B62B80A5',
    '市長提示' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=DCD4BE1106ED5B01',
    '工程採購招標' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=D0322AD5E2C38096',
    '財物採購招標' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=99C67AA646CA5A6D',
    '勞務採購招標' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=CE8524192720696F',
    '十萬元以下報價' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=F6A29549FD03E057',
    '工程決標公告' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=0C935F1D1BD2B5BA',
    '財物決標公告' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=D04C74553DB60CAD',
    '勞務決標公告' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=1EA96E4785E6838F',
    '廠商評選結果公告' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=C40E6CD20CC179F1',
    '澄清專區' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=29B3D06675FAF607',
    '市政會議' => 'https://www.tainan.gov.tw/OpenData.aspx?SN=455B2352278A7C8D',
);

// Base URL for detailed news content
$newsContentUrl = 'https://www.tainan.gov.tw/News_Content.aspx?';

// Process each RSS feed
foreach($nodes AS $node) {
    // Load and parse XML feed
    $xml = simplexml_load_file($node);
    $entries = $xml->xpath("//item");

    // Process each news item
    foreach($entries AS $entry) {
        // Convert publication date
        $pubDate = date('Y-m-d', strtotime($entry->pubDate));
        $dateParts = explode('-', $pubDate);

        // Create JSON structure for news item
        $json = array(
            'published' => $pubDate,
            'title' => (string)$entry->title,
            'department' => '',
            'url' => (string)$entry->link,
        );

        // Extract article content and save
        $parts = explode('&s=', $json['url']);
        $nParts = explode('n=', $parts[0]);
        if(empty($nParts[1])) {
            continue;
        }
        $rawPath = $basePath . '/raw/' . $nParts[1];
        if(!file_exists($rawPath)) {
            mkdir($rawPath, 0777, true);
        }
        $nodeFile = $rawPath . '/node_' . $parts[1] . '.html';

        $dataPath = $basePath . '/docs/data/' . $dateParts[0] . '/' . $dateParts[1];
        if(!file_exists($dataPath)) {
            mkdir($dataPath, 0777, true);
        }
        $jsonFile = $dataPath . '/' . $json['published'] . '_' . $parts[1] . '.json';
        
        if(!file_exists($nodeFile)) {
            error_log('fetching ' . $json['url']);
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
}