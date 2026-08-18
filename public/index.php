<?php
require __DIR__.'/../core/db.php';

$router = new stdClass();
$router->dispatch = function($uri) {
    $uri = trim($uri, '/');
    $keywordId = $_GET['keyword'] ?? null;

    if ($keywordId !== null) {
        $keywordId = (int)$keywordId;
        $pdo = conn();

        $stmt = $pdo->prepare('SELECT id, phrase FROM keywords WHERE id = :id');
        $stmt->bindValue(':id', $keywordId, PDO::PARAM_INT);
        $stmt->execute();
        $keyword = $stmt->fetch();

        $positions = [];
        if ($keyword) {
            $stmtPos = $pdo->prepare('SELECT date, position FROM positions WHERE keyword_id = :keyword_id ORDER BY date ASC');
            $stmtPos->bindValue(':keyword_id', $keywordId, PDO::PARAM_INT);
            $stmtPos->execute();
            $positions = $stmtPos->fetchAll();
        }

        $today = date('Y-m-d');
        $firstDate = $positions ? $positions[0]['date'] : $today;
        $lastDate = $positions ? $positions[count($positions)-1]['date'] : $today;

        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniRank - ' . ($keyword ? htmlspecialchars($keyword['phrase']) : 'Keyword') . '</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <h1>MiniRank - ' . ($keyword ? htmlspecialchars($keyword['phrase']) : 'Keyword') . '</h1>
        <p><a href="?view=dashboard">Back to Dashboard</a>' . ($keyword ? ' | <a href="api/export?keyword_id=' . (int)$keywordId . '">Download CSV</a>' : '') . '</p>';

        if ($keyword) {
            echo '<div style="background: #fff; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <canvas id="trendChart"></canvas>
            </div>';
        }

        echo '<table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Position</th>
                </tr>
            </thead>
            <tbody>';
        if ($positions) {
            echo implode('', array_map(function($pos) {
                return '<tr>
                            <td>' . htmlspecialchars($pos['date']) . '</td>
                            <td>' . htmlspecialchars($pos['position']) . '</td>
                        </tr>';
            }, $positions));
        } else {
            echo '<tr><td colspan="2">No position history available</td></tr>';
        }
        echo '
            </tbody>
        </table>
        <p>Date range: ' . htmlspecialchars($firstDate) . ' - ' . htmlspecialchars($lastDate) . '</p>
        <p>Showing 30-day history</p>
        <p><a href="?view=dashboard">Back to Dashboard</a>' . ($keyword ? ' | <a href="api/export?keyword_id=' . (int)$keywordId . '">Download CSV</a>' : '') . '</p>
    </div>';

        if ($keyword) {
            echo '<script>
            document.addEventListener("DOMContentLoaded", async () => {
                const keywordId = ' . (int)$keywordId . ';
                try {
                    const res = await fetch(`api/positions?keyword_id=${keywordId}`);
                    const data = await res.json();
                    const sortedData = data.reverse();
                    
                    const labels = sortedData.map(item => item.date);
                    const positions = sortedData.map(item => item.position);

                    const ctx = document.getElementById("trendChart").getContext("2d");
                    new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: labels,
                            datasets: [{
                                label: "Position",
                                data: positions,
                                borderColor: "#2563eb",
                                backgroundColor: "rgba(37, 99, 235, 0.1)",
                                tension: 0.1,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    reverse: true,
                                    ticks: {
                                        precision: 0
                                    }
                                }
                            }
                        }
                    });
                } catch (err) {
                    console.error("Failed to load chart data", err);
                }
            });
            </script>';
        }

        echo '</body>
</html>';
    } elseif ($uri === '' || $uri === 'dashboard' || $uri === '/') {
        $pdo = conn();
        $projects = get_projects($pdo);
        $selectedProjectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : ($projects[0]['id'] ?? 1);

        $selectedProjectDomain = 'example.com';
        foreach ($projects as $proj) {
            if ($proj['id'] === $selectedProjectId) {
                $selectedProjectDomain = $proj['domain_name'];
                break;
            }
        }

        $stmt = $pdo->prepare('SELECT id, phrase FROM keywords WHERE project_id = :project_id ORDER BY phrase');
        $stmt->bindValue(':project_id', $selectedProjectId, PDO::PARAM_INT);
        $stmt->execute();
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
    <title>MiniRank Dashboard - ' . htmlspecialchars($selectedProjectDomain) . '</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container" data-project-id="' . $selectedProjectId . '">
        <h1>Tracking: ' . htmlspecialchars($selectedProjectDomain) . '</h1>
        <div class="search-filter-bar">
            <div class="project-selector-wrapper">
                <label for="project-selector"><strong>Project:</strong></label>
                <select id="project-selector" class="trend-filter">
                    ' . implode('', array_map(function($proj) use ($selectedProjectId) {
                        $selected = ($proj['id'] === $selectedProjectId) ? 'selected' : '';
                        return '<option value="' . $proj['id'] . '" ' . $selected . '>' . htmlspecialchars($proj['domain_name']) . '</option>';
                    }, $projects)) . '
                </select>
            </div>
            <input type="text" id="search" placeholder="Search phrases..." class="search-input">
            <select id="trend-filter" class="trend-filter">
                <option value="All">All Trends</option>
                <option value="Improved">Improved</option>
                <option value="Declined">Declined</option>
                <option value="Stable">Stable</option>
            </select>
            <select id="range-filter" class="trend-filter">
                <option value="All">All Positions</option>
                <option value="Top 3">Top 3 (1-3)</option>
                <option value="Top 10">Top 10 (1-10)</option>
                <option value="Top 50">Top 50 (1-50)</option>
                <option value="51+">51+</option>
            </select>
        </div>
        
        <form id="add-keyword-form" class="add-keyword-form">
            <input type="text" id="new-keyword" placeholder="New keyword...">
            <button type="submit">Add Keyword</button>
        </form>
        
        <table>
            <thead>
                <tr>
                    <th>Phrase</th>
                    <th>Position</th>
                    <th>7-day Trend</th>
                    <th>Actions</th>
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
                            <td><a href="?keyword=' . $row['keyword_id'] . '">' . htmlspecialchars($row['phrase']) . '</a></td>
                            <td class="kw-pos">' . htmlspecialchars($row['position']) . '</td>
                            <td class="kw-trend ' . $trendClass . '">' . $trendLabel . '</td>
                            <td>
                                <button class="edit-btn" data-id="' . $row['keyword_id'] . '" data-phrase="' . htmlspecialchars($row['phrase'], ENT_QUOTES) . '">Edit</button>
                                <button class="delete-btn" data-id="' . $row['keyword_id'] . '">Delete</button>
                                <button class="refresh-btn" data-id="' . $row['keyword_id'] . '">Refresh</button>
                            </td>
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
        require __DIR__.'/../www/api/keywords.php';
    } elseif ($uri === 'api/positions') {
        require __DIR__.'/../www/api/positions.php';
    } elseif ($uri === 'api/export') {
        require __DIR__.'/../www/api/export.php';
    } else {
        http_response_code(404);
        echo 'Not found';
    }
};

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($uri, PHP_URL_PATH) ?? '/';
$uri = strtok($uri, '?');

($router->dispatch)($uri);
