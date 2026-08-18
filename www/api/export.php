<?php
require_once __DIR__.'/../../core/db.php';
require_once __DIR__.'/../../core/auth.php';

$pdo = conn();

if (!is_logged_in()) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

$keywordId = $_GET['keyword_id'] ?? ($_GET['keyword'] ?? null);

if ($keywordId === null) {
    http_response_code(400);
    echo "Missing keyword_id";
    exit;
}

$keywordId = (int)$keywordId;

$stmtKw = $pdo->prepare('SELECT id, phrase FROM keywords WHERE id = :id');
$stmtKw->bindValue(':id', $keywordId, PDO::PARAM_INT);
$stmtKw->execute();
$keyword = $stmtKw->fetch();

if (!$keyword) {
    http_response_code(404);
    echo "Keyword not found";
    exit;
}

$stmtPos = $pdo->prepare('SELECT date, position FROM positions WHERE keyword_id = :keyword_id ORDER BY date ASC');
$stmtPos->bindValue(':keyword_id', $keywordId, PDO::PARAM_INT);
$stmtPos->execute();
$positions = $stmtPos->fetchAll(PDO::FETCH_ASSOC);

$filename = 'keyword_' . $keywordId . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $keyword['phrase']) . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Date', 'Position'], ',', '"', '\\');

foreach ($positions as $pos) {
    fputcsv($output, [$pos['date'], $pos['position']], ',', '"', '\\');
}

fclose($output);
exit;
