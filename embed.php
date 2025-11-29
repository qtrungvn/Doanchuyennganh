<?php


function getEmbedding($text) {
    $url = "https://openrouter.ai/api/v1/embeddings";

    $payload = json_encode([
        "model" => "text-embedding-3-small",
        "input" => $text
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . OPENROUTER_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($result, true);
    return $json["data"][0]["embedding"];
}


// embed nhiều đoạn (mới)
function getEmbeddingBatch($texts) {
    $url = "https://openrouter.ai/api/v1/embeddings";

    $payload = json_encode([
        "model" => "openai/text-embedding-ada-002",
        "input" => $texts
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . OPENROUTER_KEY
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);

    curl_close($ch);

    $json = json_decode($result, true);

    $vectors = [];
    foreach ($json["data"] as $item) {
        $vectors[] = $item["embedding"];
    }

    return $vectors;
}
