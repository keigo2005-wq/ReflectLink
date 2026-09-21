<?php

const ACTION_PLAN_SERVICE_URL = "http://127.0.0.1:8080";

function actionPlanApiRequest(string $method, string $path, ?array $body = null): array
{
    $ch = curl_init(ACTION_PLAN_SERVICE_URL . $path);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_TIMEOUT => 10,
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
        throw new RuntimeException("行動計画サービスに接続できませんでした。サービスが起動しているか確認してください。(" . $curlError . ")");
    }

    $data = $response === "" ? [] : json_decode($response, true);

    if ($httpCode >= 400) {
        $message = $data["error"] ?? "行動計画サービスでエラーが発生しました。";
        throw new RuntimeException($message);
    }

    return ["status" => $httpCode, "data" => $data];
}

function listActionPlans(int $postId): array
{
    return actionPlanApiRequest("GET", "/api/action-plans?postId=" . $postId)["data"];
}

function createActionPlan(int $postId, string $action, string $practiceMenu, string $dueDate, string $createdByName): array
{
    return actionPlanApiRequest("POST", "/api/action-plans", [
        "postId" => $postId,
        "action" => $action,
        "practiceMenu" => $practiceMenu !== "" ? $practiceMenu : null,
        "dueDate" => $dueDate !== "" ? $dueDate : null,
        "createdByName" => $createdByName,
    ])["data"];
}

function updateActionPlanStatus(int $id, string $status): array
{
    return actionPlanApiRequest("PATCH", "/api/action-plans/" . $id . "/status", ["status" => $status])["data"];
}

function deleteActionPlan(int $id): void
{
    actionPlanApiRequest("DELETE", "/api/action-plans/" . $id);
}
