<?php

require __DIR__.'/../../core/db.php';

$pdo = conn();

header('Content-Type: application/json');

$keywordId = $_GET['keyword_id'] ?? null;

if ($keywordId !== null) {
    $keywordId = (int)$keywordId;
    $stmt = $pdo->prepare('SELECT id, phrase FROM keywords WHERE id = :id');
    $stmt->bindValue(':id', $keywordId, PDO::PARAM_INT);
    $stmt->execute();
    $keyword = $stmt->fetch();
    if (!$keyword) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Keyword not found']);
        exit;
    }
} else {
    $keyword = null;
}

$today = date('Y-m-d');

if ($keywordId !== null) {
    $stmt = $pdo->prepare(
        'INSERT OR REPLACE INTO positions (keyword_id, date, position) VALUES (:keyword_id, :date, :position)'
    );
    $stmt->bindValue(':keyword_id', $keywordId, PDO::PARAM_INT);
    $stmt->bindValue(':date', $today, PDO::PARAM_STR);
    $position = random_int(1, 100);
    $stmt->bindValue(':position', $position, PDO::PARAM_INT);
    $stmt->execute();

    $result = [
        'keyword_id' => $keywordId,
        'date' => $today,
        'position' => $position,
        'success' => true,
    ];
} else {
    $stmt = $pdo->prepare('SELECT id FROM keywords');
    $stmt->execute();
    $keywords = $stmt->fetchAll();

    $results = [];
    foreach ($keywords as $kw) {
        $kwId = $kw['id'];
        $position = random_int(1, 100);
        $stmt2 = $pdo->prepare(
            'INSERT OR REPLACE INTO positions (keyword_id, date, position) VALUES (:keyword_id, :date, :position)'
        );
        $stmt2->bindValue(':keyword_id', $kwId, PDO::PARAM_INT);
        $stmt2->bindValue(':date', $today, PDO::PARAM_STR);
        $stmt2->bindValue(':position', $position, PDO::PARAM_INT);
        $stmt2->execute();

        $results[] = [
            'keyword_id' => $kwId,
            'date' => $today,
            'position' => $position,
            'success' => true,
        ];
    }

    echo json_encode($results);
    exit;
}

echo json_encode($result);
exit;