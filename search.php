<?php
function cosineSimilarity($a, $b) {
    $dot = 0; $na = 0; $nb = 0;
    for ($i=0; $i<count($a); $i++) {
        $dot += $a[$i] * $b[$i];
        $na += $a[$i] * $a[$i];
        $nb += $b[$i] * $b[$i];
    }
    return $dot / (sqrt($na) * sqrt($nb));
}

function searchTopK($queryVec, $conn, $k = 5) {
    $data = [];
    $sql = "SELECT id, content, embedding FROM rag_chunks";
    $result = $conn->query($sql);

    while ($row = $result->fetch_assoc()) {
        $vec = json_decode($row["embedding"], true);
        $score = cosineSimilarity($queryVec, $vec);

        $data[] = [
            "content" => $row["content"],
            "score" => $score
        ];
    }

    usort($data, function($a, $b) {
        return $b["score"] <=> $a["score"];
    });

    return array_slice($data, 0, $k);
}
