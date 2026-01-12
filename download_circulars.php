<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

$url     = "https://www.cbn.gov.ng/api/GetAllCirculars";
$base    = "https://www.cbn.gov.ng";
$saveDir = __DIR__ . "/documents/";

/**
 * Simple cURL GET helper
 */
function curl_get($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0'
    ]);

    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

/**
 * Ensure documents directory exists
 */
if (!is_dir($saveDir)) {
    if (!mkdir($saveDir, 0755, true)) {
        die("❌ Cannot create documents directory");
    }
}

/**
 * Fetch API JSON
 */
$response = curl_get($url);
$items = json_decode($response, true);

if (!is_array($items)) {
    die("❌ Invalid JSON from API");
}

$downloaded = 0;

foreach ($items as $item) {
    if (empty($item['link'])) {
        continue;
    }

    // Full URL
    $href = (strpos($item['link'], 'http') === 0) ? $item['link'] : $base . $item['link'];

    // Safe filename
    $filename = basename(parse_url($href, PHP_URL_PATH)) ?: uniqid('cbn_') . '.pdf';
    $filename = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $filename);
    $localPath = $saveDir . $filename;

    // Download if not already saved
    if (!file_exists($localPath)) {
        $file = curl_get($href);

        // Basic sanity check (avoid HTML error pages)
        if ($file && strlen($file) > 1000) {
            file_put_contents($localPath, $file);
            $downloaded++;
            echo "✅ Downloaded: $filename\n";
        } else {
            echo "⚠️ Skipped invalid or empty file: $href\n";
        }
    } else {
        echo "ℹ️ Already exists: $filename\n";
    }
}

echo "\n📄 Total downloaded files: $downloaded\n";
