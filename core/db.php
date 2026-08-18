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

            // Schema migration for projects and project_id
            $pdo->exec('CREATE TABLE IF NOT EXISTS projects (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                domain_name TEXT NOT NULL UNIQUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );');

            // Schema migration for users table
            $pdo->exec('CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );');

            $cols = $pdo->query("PRAGMA table_info(keywords)")->fetchAll();
            $hasProjectId = false;
            $hasUserId = false;
            foreach ($cols as $col) {
                if ($col['name'] === 'project_id') {
                    $hasProjectId = true;
                }
                if ($col['name'] === 'user_id') {
                    $hasUserId = true;
                }
            }
            if (!$hasProjectId) {
                $pdo->exec('ALTER TABLE keywords ADD COLUMN project_id INTEGER DEFAULT 1');
            }
            if (!$hasUserId) {
                $pdo->exec('ALTER TABLE keywords ADD COLUMN user_id INTEGER DEFAULT 1');
            }

            // Ensure default project exists
            $stmt = $pdo->query('SELECT COUNT(*) FROM projects');
            if ($stmt->fetch()['COUNT(*)'] == 0) {
                $pdo->exec("INSERT INTO projects (domain_name) VALUES ('example.com'), ('myportfolio.dev'), ('shop.example')");
            }

            // Ensure default user exists
            $stmtUser = $pdo->query('SELECT COUNT(*) FROM users');
            if ($stmtUser->fetch()['COUNT(*)'] == 0) {
                $defaultHash = password_hash('admin123', PASSWORD_DEFAULT);
                $stmtIns = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
                $stmtIns->bindValue(':username', 'admin', PDO::PARAM_STR);
                $stmtIns->bindValue(':password_hash', $defaultHash, PDO::PARAM_STR);
                $stmtIns->execute();
            }
        }
        return $pdo;
    }
}

if (!function_exists('get_projects')) {
    function get_projects($pdo) {
        $stmt = $pdo->query('SELECT id, domain_name FROM projects ORDER BY domain_name');
        return $stmt->fetchAll();
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