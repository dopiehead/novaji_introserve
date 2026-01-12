<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

$url = "https://www.cbn.gov.ng/api/GetAllCirculars";
$base = "https://www.cbn.gov.ng";
$jsonOut = __DIR__ . "/cbn_circulars.json";

$response = file_get_contents($url);
if ($response === false) {
    http_response_code(500);
    die(json_encode(["error" => "Cannot reach CBN API"]));
}

$items = json_decode($response, true);
if (!is_array($items)) {
    http_response_code(500);
    die(json_encode(["error" => "Invalid JSON from API"]));
}

$data = [];

foreach ($items as $item) {
    if (empty($item['link'])) {
        continue;
    }

    $href = (strpos($item['link'], 'http') === 0)
        ? $item['link']
        : $base . $item['link'];

    $filename = basename(parse_url($href, PHP_URL_PATH));
    $filename = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $filename);

    $data[] = [
        "title"        => $item['title'] ?? null,
        "file_url"     => $href,
        "local_file"   => "documents/" . $filename,
        "documentDate" => $item['documentDate'] ?? null,
        "filesize"     => $item['filesize'] ?? null
    ];
}

if (file_put_contents(
    $jsonOut,
    json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
) === false) {
    http_response_code(500);
    die(json_encode(["error" => "Failed to write JSON file"]));
}

echo json_encode([
    "status" => "success",
    "records" => count($data)
], JSON_PRETTY_PRINT);
