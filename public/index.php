<?php
require __DIR__.'/../core/db.php';

$router = new stdClass();
$router->dispatch = function($uri) {
    $uri = trim($uri, '/');
    if ($uri === '' || $uri === 'dashboard') {
        $pdo = conn();
        $stmt = $pdo->query('SELECT id, phrase FROM keywords ORDER BY phrase');
        $keywords = $stmt->fetchAll();

        $rows = [];
        foreach ($keywords as $keyword) {
            $trend = get_keyword_trend($pdo, $keyword['id']);
            $stmtPositions = $pdo->prepare(
                'SELECT position FROM positions WHERE keyword_id = :keyword_id ORDER BY date DESC LIMIT 7'
            );
            $stmtPositions->bindValue(':keyword_id', $keyword['id'], PDO::PARAM_INT);
            $stmtPositions->execute();
            $positions = array_column($stmtPositions->fetchAll(), 'position');

            $rows[] = [
                'phrase' => $keyword['phrase'],
                'position' => count($positions) > 0 ? $positions[0] : '',
                'trend' => $trend,
                'keyword_id' => $keyword['id'],
                'positions' => $positions,
            ];
        }

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniRank Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <h1>MiniRank Dashboard</h1>
        <input type="text" id="search" placeholder="Search phrases..." class="search-input">
        <table>
            <thead>
                <tr>
                    <th>Phrase</th>
                    <th>Position</th>
                    <th>7-day Trend</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                ' . implode('', array_map(function($row) {
                    $trendClass = match($row['trend']) {
                        'improved' => 'improved',
                        'declined' => 'declined',
                        'stable' => 'stable',
                        default => 'stable',
                    };
                    $trendLabel = match($row['trend']) {
                        'improved' => 'Improved',
                        'declined' => 'Declined',
                        'stable' => 'Stable',
                        default => 'Stable',
                    };
return '<tr class="' . $trendClass . '" data-id="' . $row['keyword_id'] . '" data-positions=\'' . json_encode($row['positions']) . '\'>
                            <td>' . htmlspecialchars($row['phrase']) . '</td>
                            <td class="kw-pos">' . htmlspecialchars($row['position']) . '</td>
                            <td class="kw-trend ' . $trendClass . '">' . $trendLabel . '</td>
                            <td><button class="refresh-btn" data-id="' . $row['keyword_id'] . '">Refresh</button></td>
                        </tr>';
                }, $rows)) . '
            </tbody>
        </table>
    </div>
    <script src="assets/script.js"></script>
</body>
</html>';
    } elseif ($uri === 'keyword') {
        echo 'Keyword endpoint';
    } elseif ($uri === 'api/keywords') {
        if (!empty($_POST['action'])) {
            require __DIR__.'/../www/api/keywords.php';
        } else {
            http_response_code(404);
            echo 'Not found';
        }
    } elseif ($uri === 'api/positions') {
        require __DIR__.'/../www/api/positions.php';
    } else {
        http_response_code(404);
        echo 'Not found';
    }
};

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH) ?? '/';
$uri = strtok($uri, '?');

($router->dispatch)($uri);