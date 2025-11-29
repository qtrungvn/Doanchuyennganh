<?php
require_once "config.php";
require_once "embed.php";

// lấy dữ liệu
$sql = "SELECT id, name, description FROM products";
$result = $conn->query($sql);

$texts = [];
$map   = [];

while ($row = $result->fetch_assoc()) {
    $text = $row["name"] . " - " . $row["description"];
    $texts[] = $text;
    $map[] = $row["id"];
}


// gọi embedding dạng batch
$embeddings = getEmbeddingBatch($texts);

// lưu vào DB
for ($i = 0; $i < count($texts); $i++) {
    $content = $texts[$i];
    $vec = json_encode($embeddings[$i]);

    $stmt = $conn->prepare("INSERT INTO rag_chunks (content, embedding) VALUES (?, ?)");
    $stmt->bind_param("ss", $content, $vec);
    $stmt->execute();
}

echo "DONE FAST";
