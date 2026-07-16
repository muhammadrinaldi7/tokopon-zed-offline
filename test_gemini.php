<?php
$key = getenv('GEMINI_API_KEY');
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key;
$response = json_decode(file_get_contents($url), true);
foreach ($response['models'] as $m) {
    if (strpos($m['name'], 'flash') !== false || strpos($m['name'], 'pro') !== false || strpos($m['name'], 'gemini') !== false) {
        echo $m['name'] . "\n";
    }
}
