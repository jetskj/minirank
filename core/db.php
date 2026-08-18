<?php
if (!function_exists('db_error')) {
    function db_error($msg) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => $msg]);
        exit(1);
    }
}

if (!function_exists('conn')) {
    function conn() {
        static $pdo = null;
        if ($pdo === null) {
            $pdo = new PDO(
                'sqlite:'.__DIR__.'/../data/minirank.sqlite',
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
}

if (!function_exists('get_keyword_trend')) {
    function get_keyword_trend($pdo, $keyword_id) {
        $stmt = $pdo->prepare(
            'SELECT position FROM positions WHERE keyword_id = :keyword_id ORDER BY date DESC, id DESC LIMIT 7'
        );
        $stmt->bindValue(':keyword_id', $keyword_id, PDO::PARAM_INT);
        $stmt->execute();
        $positions = array_column($stmt->fetchAll(), 'position');

        if (count($positions) < 7) return 'stable';

        $posFirst = $positions[0];   // most recent (newest)
        $posLast = $positions[6];    // 7 days ago (oldest)

        if ($posFirst < $posLast) {
            return 'improved';
        } elseif ($posFirst > $posLast) {
            return 'declined';
        } else {
            return 'stable';
        }
    }
}