<?php

// 本番環境では includes/goals_client.local.php を置いて
// GOAL_SERVICE_URL を上書きする(このファイル自体はGitにコミットしない)。
if (file_exists(__DIR__ . "/goals_client.local.php")) {
    require_once __DIR__ . "/goals_client.local.php";
} else {
    define("GOAL_SERVICE_URL", "http://127.0.0.1:8080");
}

function goalApiRequest(string $method, string $path, ?array $body = null): array
{
    // PHP自体の実行時間上限が短いホストでも、下のcurlタイムアウトまで待てるようにする
    @set_time_limit(100);

    $ch = curl_init(GOAL_SERVICE_URL . $path);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        // 本番(Render無料プラン)はアクセスが無いとスリープし、
        // 次回アクセス時に起動(コールドスタート、Spring Bootの起動を含めると
        // 1分以上かかることがある)まで時間がかかるため長めに設定
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    ];

    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException("目標管理サービスに接続できませんでした。サービスが起動しているか確認してください。(" . $curlError . ")");
    }

    $data = $response === "" ? [] : json_decode($response, true);

    if ($httpCode >= 400) {
        $message = $data["error"] ?? "目標管理サービスでエラーが発生しました。";
        throw new RuntimeException($message);
    }

    return $data;
}

function listGoals(int $userId): array
{
    return goalApiRequest("GET", "/api/goals?userId=" . $userId);
}

function createGoal(int $userId, string $periodStart, string $periodEnd, string $goal, string $action): array
{
    return goalApiRequest("POST", "/api/goals", [
        "userId" => $userId,
        "periodStart" => $periodStart,
        "periodEnd" => $periodEnd,
        "goal" => $goal,
        "action" => $action,
    ]);
}

function updateGoal(int $id, string $periodStart, string $periodEnd, string $goal, string $action, string $result, string $status): array
{
    return goalApiRequest("PUT", "/api/goals/" . $id, [
        "periodStart" => $periodStart,
        "periodEnd" => $periodEnd,
        "goal" => $goal,
        "action" => $action,
        "result" => $result,
        "status" => $status,
    ]);
}

function deleteGoal(int $id): void
{
    goalApiRequest("DELETE", "/api/goals/" . $id);
}
