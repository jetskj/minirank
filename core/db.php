<?php
function db_error($msg) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error => $msg']);
    exit(1);
}

function conn() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'sqlite:'.dirname(__DIR__, 2).'/data/minirank.sqlite',
            null,
            null,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }
    return $pdo;
}