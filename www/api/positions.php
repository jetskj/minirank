<?php

require __DIR__.'/../../core/db.php';

$pdo = conn();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$keywordId = $input['keyword_id'] ?? ($_GET['keyword_id'] ?? null);
$today = date('Y-m-d');

if ($method === 'POST') {
    if ($keywordId !== null) {
        $keywordId = (int)$keywordId;
        $stmt = $pdo->prepare('SELECT id FROM keywords WHERE id = :id');
        $stmt->bindValue(':id', $keywordId, PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Keyword not found']);
            exit;
        }

        $position = rand(1, 100);
        $stmtIns = $pdo->prepare('INSERT OR REPLACE INTO positions (keyword_id, date, position) VALUES (:keyword_id, :date, :position)');
        $stmtIns->bindValue(':keyword_id', $keywordId, PDO::PARAM_INT);
        $stmtIns->bindValue(':date', $today, PDO::PARAM_STR);
        $stmtIns->bindValue(':position', $position, PDO::PARAM_INT);
        $stmtIns->execute();

        $trend = get_keyword_trend($pdo, $keywordId);

        echo json_encode([
            'keyword_id' => $keywordId,
            'date' => $today,
            'position' => $position,
            'trend' => $trend,
            'success' => true,
        ]);
        exit;
    } else {
        $allKeywords = $pdo->query('SELECT id FROM keywords')->fetchAll();
        $results = [];
        foreach ($allKeywords as $kw) {
            $kwId = $kw['id'];
            $position = rand(1, 100);
            $stmtIns = $pdo->prepare('INSERT OR REPLACE INTO positions (keyword_id, date, position) VALUES (:keyword_id, :date, :position)');
            $stmtIns->bindValue(':keyword_id', $kwId, PDO::PARAM_INT);
            $stmtIns->bindValue(':date', $today, PDO::PARAM_STR);
            $stmtIns->bindValue(':position', $position, PDO::PARAM_INT);
            $stmtIns->execute();

            $trend = get_keyword_trend($pdo, $kwId);

            $results[] = [
                'keyword_id' => $kwId,
                'date' => $today,
                'position' => $position,
                'trend' => $trend,
                'success' => true,
            ];
        }
        echo json_encode($results);
        exit;
    }
} else {
    if ($keywordId !== null) {
        $keywordId = (int)$keywordId;
        $stmt = $pdo->prepare('SELECT date, position FROM positions WHERE keyword_id = :keyword_id ORDER BY date DESC');
        $stmt->bindValue(':keyword_id', $keywordId, PDO::PARAM_INT);
        $stmt->execute();
        $positions = $stmt->fetchAll();
        echo json_encode($positions);
        exit;
    } else {
        $stmt = $pdo->query('SELECT keyword_id, date, position FROM positions ORDER BY date DESC');
        echo json_encode($stmt->fetchAll());
        exit;
    }
}