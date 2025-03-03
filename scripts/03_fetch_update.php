<?php
$basePath = dirname(__DIR__);

// Add new curl function at the top
function curlGet($url, $context = null) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: zh-TW,zh;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive'
        ]
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode == 200 && $response !== false) ? $response : false;
}

// Load required Facebook SDK and configuration
require_once $basePath . '/vendor/autoload.php';
$config = require $basePath . '/scripts/config.php';

// SSL context to handle HTTPS connections
$context = stream_context_create(array(
    "ssl" => array(
        "verify_peer" => false,
        "verify_peer_name" => false,
    ),
));

// Department node IDs for checking updates
$nodes = array('13370', '13371', '13303', '13372', '1962', '1963', '13373', '4960', '4961', '4962', '4963', '4964', '4965', '4966', '4967');
$newsContentUrl = 'https://www.tainan.gov.tw/News_Content.aspx?';

// Initialize Facebook SDK
$fb = new Facebook\Facebook([
    'app_id' => $config['app_id'],
    'app_secret' => $config['app_secret'],
    'default_graph_version' => 'v2.2',
]);

// Process each department node
foreach ($nodes as $node) {
    // Create directories and fetch updates
    $rawPath = $basePath . '/raw/' . $node;
    if (!file_exists($rawPath)) {
        mkdir($rawPath, 0777, true);
    }

    $rawFile = $rawPath . '/update.html';
    // Replace first file_get_contents with curl
    $content = curlGet('https://www.tainan.gov.tw/News.aspx?n=' . $node . '&PageSize=30&page=1');
    if ($content !== false) {
        file_put_contents($rawFile, $content);
    }
    $rawPage = file_get_contents($rawFile);

    $pos = strpos($rawPage, '<td class="CCMS_jGridView_td_Class_0"');
    while (false !== $pos) {
        $posEnd = strpos($rawPage, '</tr>', $pos);
        $line = substr($rawPage, $pos, $posEnd - $pos);
        $cols = explode('</td>', $line);
        $link = '';
        $isFour = false;
        if (count($cols) === 4) {
            $isFour = true;
        }
        foreach ($cols as $k => $v) {
            if (($isFour && $k === 1) || (!$isFour && $k === 2)) {
                $parts = explode('News_Content.aspx?', $v);
                if (count($parts) === 2) {
                    $partPos = strpos($parts[1], '"');
                    $link = substr($parts[1], 0, $partPos);
                }
            }
            $cols[$k] = trim(strip_tags($v));
        }
        $parts = explode('s=', $link);
        if (!empty($parts[1])) {
            $isNew = false;
            $nodeFile = $rawPath . '/node_' . $parts[1] . '.html';
            if (!file_exists($nodeFile)) {
                $isNew = true;
            }

            $dateParts = explode('-', $cols[0]);
            $dateParts[0] += 1911;
            $json = array(
                'published' => implode('-', $dateParts),
                'title' => '',
                'department' => '',
                'url' => $newsContentUrl . $link,
            );
            if (count($cols) === 4) {
                $json['title'] = $cols[1];
                $json['department'] = $cols[2];
            } else {
                $json['title'] = $cols[2];
                $json['department'] = $cols[3];
            }
            $dataPath = $basePath . '/docs/data/' . $dateParts[0] . '/' . $dateParts[1];
            if (!file_exists($dataPath)) {
                mkdir($dataPath, 0777, true);
            }
            $jsonFile = $dataPath . '/' . $json['published'] . '_' . $parts[1] . '.json';
            // Replace file_get_contents with curlGet
            $content = curlGet($json['url']);
            if ($content !== false) {
                file_put_contents($nodeFile, $content);
            }

            $node = file_get_contents($nodeFile);
            $nodePos = strpos($node, '<div class="area-essay page-caption-p"');
            if (false !== $nodePos) {
                $nodePosEnd = strpos($node, '<div class="area-editor system-info"', $nodePos);
                $body = substr($node, $nodePos, $nodePosEnd - $nodePos);
                $body = str_replace(array('</p>', '&nbsp;'), array("\n", ''), $body);

                $json['content'] = trim(strip_tags($body));
                $nodePos = $nodePosEnd;
            } else {
                $json['content'] = '';
                $nodePos = strpos($node, '<div class="area-editor system-info"');
            }

            // If new content with images found, post to Facebook
            if ($isNew && !empty($json['content'])) {
                // Prepare Facebook post content
                $message = $json['title'] . "\n\n" . $json['content'] . "\n\n" . $json['url'];
                $imgPool = [];
                $media = [];
                $imgPos = strpos($node, '<li data-src=');
                while (false !== $imgPos) {
                    $imgPosEnd = strpos($node, '<span style=', $imgPos);
                    $imgParts = explode('"', substr($node, $imgPos, $imgPosEnd - $imgPos));
                    $imgParts2 = explode('@', $imgParts[1]);
                    $imgParts2Ext = substr($imgParts2[1], strrpos($imgParts2[1], '.'));
                    $imgPool[] = $imgParts2[0] . $imgParts2Ext;
                    $imgPos = strpos($node, '<li data-src=', $imgPosEnd);
                }
                $imgPos = strpos($node, 'class="jpg"');
                while (false !== $imgPos) {
                    $imgPos = strpos($node, 'https://w3fs.tainan.gov.tw/', $imgPos);
                    $imgPosEnd = strpos($node, '"   data-ccms_hitcount_relfile', $imgPos);
                    $imgPool[] = substr($node, $imgPos, $imgPosEnd - $imgPos);
                    $imgPos = strpos($node, 'class="jpg"', $imgPosEnd);
                }
                if (!empty($imgPool)) {
                    foreach ($imgPool as $imgUrl) {
                        $p = pathinfo($imgUrl);
                        if ($p['dirname'] === 'https://w3fs.tainan.gov.tw' && $p['filename'] === 'Download') {
                            $p['extension'] = 'jpg';
                        }
                        $imgFile = $rawPath . '/img.' . $p['extension'];
                        file_put_contents($imgFile, file_get_contents($imgUrl, false, $context));
                        try {
                            $response = $fb->post('/' . $config['page_id'] . '/photos', [
                                'message' => $message,
                                'source' => $fb->fileToUpload($imgFile),
                                'published' => false,
                            ], $config['token']);
                        } catch (Facebook\Exceptions\FacebookResponseException $e) {
                            // echo 'Graph returned an error: ' . $e->getMessage();
                            // exit();
                        } catch (Facebook\Exceptions\FacebookSDKException $e) {
                            // echo 'Facebook SDK returned an error: ' . $e->getMessage();
                            // exit();
                        }
                        $media[] = ['media_fbid' => $response->getDecodedBody()['id']];
                    }
                    if (!empty($media)) {
                        $linkData = [
                            'message' => $message,
                            'attached_media' => $media,
                        ];

                        try {
                            $response = $fb->post('/' . $config['page_id'] . '/feed', $linkData, $config['token']);
                        } catch (Facebook\Exceptions\FacebookResponseException $e) {
                            // echo 'Graph returned an error: ' . $e->getMessage();
                            // exit;
                        } catch (Facebook\Exceptions\FacebookSDKException $e) {
                            // echo 'Facebook SDK returned an error: ' . $e->getMessage();
                            // exit;
                        }
                    }
                }
            }

            if (false !== $nodePos) {
                $nodePosEnd = strpos($node, '<div class="group page-footer"', $nodePos);
                $body = substr($node, $nodePos, $nodePosEnd - $nodePos);
                $json['tags'] = mb_substr(trim(strip_tags($body)), 3, null, 'utf-8');
            } else {
                $json['tags'] = '';
            }
            file_put_contents($jsonFile, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $p = pathinfo($jsonFile);
            $jParts = explode('_', $p['filename']);
            if (empty($jParts[1])) {
                unlink($jsonFile);
            } else {
                $metaFile = dirname($p['dirname']) . '/' . $jParts[0] . '.json';
                if (file_exists($metaFile)) {
                    $meta = json_decode(file_get_contents($metaFile), true);
                } else {
                    $meta = [];
                }
                if (!isset($meta[$jParts[1]])) {
                    $json = json_decode(file_get_contents($jsonFile), true);
                    $meta[$jParts[1]] = $json['url'];
                    ksort($meta);
                    file_put_contents($metaFile, json_encode($meta));
                }
            }
        }

        $pos = strpos($rawPage, '<td class="CCMS_jGridView_td_Class_0"', $posEnd);
    }
}
