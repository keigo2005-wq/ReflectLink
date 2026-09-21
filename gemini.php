<?php

function callGeminiApi(string $prompt): string
{
    if (file_exists(__DIR__ . "/gemini.local.php")) {
        require __DIR__ . "/gemini.local.php";
    } elseif (file_exists(__DIR__ . "/gemini.key.php")) {
        require __DIR__ . "/gemini.key.php";
    } else {
        throw new RuntimeException("gemini.local.php が見つかりません。gemini.local.php.example を参考に作成してください。");
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/"
        . $geminiModel . ":generateContent?key=" . urlencode($geminiApiKey);

    $body = json_encode([
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ]
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException("Gemini APIへの接続に失敗しました: " . $curlError);
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $message = $data["error"]["message"] ?? $response;
        throw new RuntimeException("Gemini APIエラー(HTTP {$httpCode}): " . $message);
    }

    $text = $data["candidates"][0]["content"]["parts"][0]["text"] ?? null;

    if ($text === null) {
        throw new RuntimeException("Gemini APIから期待した形式の応答がありませんでした。");
    }

    return $text;
}
