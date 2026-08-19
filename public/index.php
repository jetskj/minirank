<?php
require __DIR__.'/../core/db.php';
require __DIR__.'/../core/auth.php';

$pdo = conn();
$action = $_POST['action'] ?? ($_GET['action'] ?? '');
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf)) {
        $error = 'Invalid CSRF token';
    } else {
        if ($action === 'login') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $res = login_user($pdo, $username, $password);
            if ($res['success']) {
                header('Location: ?view=dashboard');
                exit;
            } else {
                $error = $res['error'];
            }
        } elseif ($action === 'register') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $res = register_user($pdo, $username, $password);
            if ($res['success']) {
                header('Location: ?view=dashboard');
                exit;
            } else {
                $error = $res['error'];
            }
        }
    }
}

if ($action === 'logout') {
    logout_user();
    header('Location: /');
    exit;
}

$router = new stdClass();
$router->dispatch = function($uri) use ($pdo, $error, $success) {
    $uri = trim($uri, '/');
    $keywordId = $_GET['keyword'] ?? null;

    if (!is_logged_in()) {
        $csrfToken = get_csrf_token();
        $authMode = $_GET['mode'] ?? 'login';
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MiniRank - Authentication</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .auth-container { max-width: 400px; margin: 4rem auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .auth-container h1 { margin-bottom: 1rem; font-size: 1.5rem; }
        .auth-form { display: flex; flex-direction: column; gap: 1rem; }
        .auth-form input { padding: 0.75rem; font-size: 1rem; border: 1px solid #ddd; border-radius: 4px; }
        .auth-form button { padding: 0.75rem; font-size: 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .auth-form button:hover { background: #1d4ed8; }
        .error-msg { background: #fee2e2; color: #b91c1c; padding: 0.75rem; border-radius: 4px; margin-bottom: 1rem; }
        .auth-switch { margin-top: 1rem; text-align: center; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>MiniRank - ' . ($authMode === 'register' ? 'Register' : 'Log In') . '</h1>';
        
        if ($error) {
            echo '<div class="error-msg">' . htmlspecialchars($error) . '</div>';
        }

        if ($authMode === 'register') {
            echo '<form method="POST" class="auth-form">
                <input type="hidden" name="action" value="register">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password (min 6 chars)" required>
                <button type="submit">Register</button>
            </form>
            <div class="auth-switch">
                Already have an account? <a href="?mode=login">Log In</a>
            </div>';
        } else {
            echo '<form method="POST" class="auth-form">
                <input type="hidden" name="action" value="login">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken) . '">
                <input type="text" name="username" placeholder="Username" required autofocus>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Log In</button>
            </form>
            <div class="auth-switch">
                Don\'t have an account? <a href="?mode=register">Register</a>
            </div>';
        }

        echo '</div>
</body>
</html>';
        return;
    }

    if ($keywordId !== null) {
        $keywordId = (int)$keywordId;

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
    <meta name="csrf-token" content="' . htmlspecialchars(get_csrf_token()) . '">
    <title>MiniRank - ' . ($keyword ? htmlspecialchars($keyword['phrase']) : 'Keyword') . '</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <span>Logged in as <strong>' . htmlspecialchars(get_current_username()) . '</strong></span>
            <a href="?action=logout" style="background: #dc2626; color: #fff; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">Log Out</a>
        </div>
        <h1>MiniRank - ' . ($keyword ? htmlspecialchars($keyword['phrase']) : 'Keyword') . '</h1>
        <p><a href="?view=dashboard">Back to Dashboard</a>' . ($keyword ? ' | <a href="api/export?keyword_id=' . (int)$keywordId . '">Download CSV</a>' : '') . '</p>';

        if ($keyword) {
            echo '<div style="background: #fff; padding: 1rem; border-radius: 4px; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <canvas id="trendChart"></canvas>
            </div>';
        }

        echo '<div class="table-responsive">
        <table>
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
        </div>
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
    <meta name="csrf-token" content="' . htmlspecialchars(get_csrf_token()) . '">
    <title>MiniRank Dashboard - ' . htmlspecialchars($selectedProjectDomain) . '</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container" data-project-id="' . $selectedProjectId . '">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <span>Logged in as <strong>' . htmlspecialchars(get_current_username()) . '</strong></span>
            <a href="?action=logout" style="background: #dc2626; color: #fff; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; font-size: 0.9rem;">Log Out</a>
        </div>
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
        
        <div class="table-responsive">
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
    </div>
    <script src="assets/script.js"></script>
</body>
</html>';
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
