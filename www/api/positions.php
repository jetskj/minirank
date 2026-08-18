<?php
require __DIR__.'/../../core/db.php';
require __DIR__.'/../../core/auth.php';

$pdo = conn();
header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($method === 'POST') {
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? ($headers['x-csrf-token'] ?? ($input['csrf_token'] ?? ($_POST['csrf_token'] ?? '')));
    if (!validate_csrf_token($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
        exit;
    }
}

$keywordId = $input['keyword_id'] ?? ($_GET['keyword_id'] ?? null);
$projectId = $input['project_id'] ?? ($_GET['project_id'] ?? null);
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
        if ($projectId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM keywords WHERE project_id = :project_id');
            $stmt->bindValue(':project_id', (int)$projectId, PDO::PARAM_INT);
            $stmt->execute();
            $targetKeywords = $stmt->fetchAll();
        } else {
            $targetKeywords = $pdo->query('SELECT id FROM keywords')->fetchAll();
        }
        $results = [];
        foreach ($targetKeywords as $kw) {
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
        $stmtChk = $pdo->prepare('SELECT id FROM keywords WHERE id = :id');
        $stmtChk->bindValue(':id', $keywordId, PDO::PARAM_INT);
        $stmtChk->execute();
        if (!$stmtChk->fetch()) {
            http_response_code(404);
            echo json_encode([]);
            exit;
        }

        $stmt = $pdo->prepare('SELECT date, position FROM positions WHERE keyword_id = :keyword_id ORDER BY date DESC');
        $stmt->bindValue(':keyword_id', $keywordId, PDO::PARAM_INT);
        $stmt->execute();
        $positions = $stmt->fetchAll();
        echo json_encode($positions);
        exit;
    } else if ($projectId !== null) {
        $stmt = $pdo->prepare('SELECT p.keyword_id, p.date, p.position FROM positions p JOIN keywords k ON p.keyword_id = k.id WHERE k.project_id = :project_id ORDER BY p.date DESC');
        $stmt->bindValue(':project_id', (int)$projectId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll());
        exit;
    } else {
        $stmt = $pdo->query('SELECT p.keyword_id, p.date, p.position FROM positions p JOIN keywords k ON p.keyword_id = k.id ORDER BY p.date DESC');
        echo json_encode($stmt->fetchAll());
        exit;
    }
}
