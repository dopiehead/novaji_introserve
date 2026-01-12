<?php

$apiKey = "AIzaSyD5YtH7kC7L1jD46R74uBzC9yqHKC0Itcs"; 
$model  = "gemini-2.5-flash-lite";
$prompt = "Who is Donald Trump?";

// Build payload
$payload = [
    "contents" => [
        [
            "parts" => [
                ["text" => $prompt]
            ]
        ]
    ]
];

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

// Init cURL
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$response = curl_exec($ch);

if ($response === false) {
    die("❌ cURL error: " . curl_error($ch));
}

curl_close($ch);

$result = json_decode($response, true);

// Handle errors
if (isset($result['error'])) {
    $message = $result['error']['message'];
    $retryDelay = $result['error']['details'][2]['retryDelay'] ?? null;
    $retryText = $retryDelay ? "⏳ Retry after: {$retryDelay} seconds" : "";

    echo "<div style='font-family: monospace; padding: 20px; background: #ffeeee; border: 2px solid #ff0000; border-radius: 8px;'>";
    echo "<h2 style='color: #ff0000;'>❌ API Error</h2>";
    echo "<p>$message</p>";
    if ($retryText) echo "<p>$retryText</p>";
    echo "</div>";
    exit;
}

// Get response text
$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? "No response";

echo "<div style='font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; background: #f4f4f9; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>";
echo "<h2 style='color: #333;'>💬 Prompt:</h2>";
echo "<p style='background: #e0e0ff; padding: 10px; border-radius: 6px;'>{$prompt}</p>";
echo "<h2 style='color: #333;'>✨ Response:</h2>";
echo "<p style='background: #fff; padding: 15px; border-radius: 6px; border-left: 4px solid #4caf50; white-space: pre-wrap;'>{$text}</p>";
echo "</div>";
