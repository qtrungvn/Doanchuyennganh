<?php
require_once "config.php";
require_once "embed.php";
require_once "search.php";

header("Content-Type: application/json; charset=utf-8");

// lấy câu hỏi từ FE
$userQuestion = $_GET["q"] ?? "";
if (!$userQuestion) {
    echo json_encode(["error" => "missing question"]);
    exit;
}

// tạo embedding câu hỏi
$queryVec = getEmbedding($userQuestion);

// tìm top 5 context
$results = searchTopK($queryVec, $conn, 5);

$context = "";
foreach ($results as $item) {
    $context .= $item["content"] . "\n";
}

$prompt = "Bạn là nhân viên tư vấn đồ ăn của FastFood thân thiện, vui vẻ.

Dữ liệu:
$context

Câu hỏi: $userQuestion

Hãy trả lời:
- Ngắn gọn, tự nhiên như đang chat
- Gợi ý món phù hợp với nhu cầu
- Không giải thích dài dòng
- Nếu không có dữ liệu, từ chối khéo léo
- Nếu khách đặt hàng, đã đặt hàng giúp bạn
- hãy bám sát dữ liệu đã cho, không được tự ý thêm thông tin khác.
- trả lời liên quan đến câu hỏi trước đó của khách hàng";
// gọi OpenRouter
$url = "https://openrouter.ai/api/v1/chat/completions";

$payload = json_encode([
    "model" => "gpt-4o-mini",
    "messages" => [
        ["role" => "system", "content" => "Bạn là nhân viên tư vấn đồ ăn thân thiện, trả lời ngắn gọn, gần gũi như đang chat với bạn bè. Không dài dòng."],
        ["role" => "user", "content" => "$context\n\nCâu hỏi: $userQuestion"]
    ],
    "temperature" => 0.7, // Tăng độ tự nhiên
    "max_tokens" => 200    // Giới hạn độ dài
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: " . "Bearer " . OPENROUTER_KEY
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

// decode JSON OpenRouter
$data = json_decode($response, true);

$answer = $data["choices"][0]["message"]["content"] ?? "Xin lỗi, mình không trả lời được lúc này ";

// trả JSON về FE
echo json_encode([
    "answer" => $answer,
    "context" => $context,
    "raw" => $data
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
