<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain');

$url     = "https://www.cbn.gov.ng/api/GetAllCirculars";
$base    = "https://www.cbn.gov.ng";
$saveDir = __DIR__ . "/documents/";
$jsonOut = __DIR__ . "/cbn_circulars.json";

/**
 * Simple cURL GET helper
 */
function curl_get($url)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
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
if ($response === false) {
    die("❌ Failed to fetch CBN API");
}

$items = json_decode($response, true);
if (!is_array($items)) {
    die("❌ Invalid JSON from API");
}

$data = [];

foreach ($items as $item) {

    if (empty($item['link'])) {
        continue;
    }

    // Build absolute URL
    $href = (strpos($item['link'], 'http') === 0)
        ? $item['link']
        : $base . $item['link'];

    // Safe filename
    $filename = basename(parse_url($href, PHP_URL_PATH));
    if (!$filename) {
        $filename = uniqid('cbn_') . '.pdf';
    }

    $filename  = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $filename);
    $localPath = $saveDir . $filename;

    // Download document ONLY if not already saved
    if (!file_exists($localPath)) {
        $file = curl_get($href);

        // Basic sanity check (avoid HTML/error pages)
        if ($file && strlen($file) > 1000 && substr($file, 0, 4) === '%PDF') {
            file_put_contents($localPath, $file);
        } else {
            // Skip bad downloads but still save metadata
            echo "⚠️ Skipped invalid PDF: $href\n";
        }
    }

    // Collect JSON data
    $data[] = [
        "title"        => $item['title'] ?? 'CBN Circular',
        "file_url"     => $href,
        "local_file"   => "documents/" . $filename,
        "documentDate" => $item['documentDate'] ?? null,
        "filesize"     => $item['filesize'] ?? null
    ];
}

/**
 * Save JSON file (WITH ERROR CHECK)
 */
$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false) {
    die("❌ JSON encoding failed");
}

if (file_put_contents($jsonOut, $json) === false) {
    die("❌ Failed to write cbn_circulars.json (check permissions)");
}

echo "✅ Saved " . count($data) . " records to cbn_circulars.json\n";
