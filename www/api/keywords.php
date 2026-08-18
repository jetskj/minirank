<?php
require_once __DIR__.'/../../core/db.php';

$pdo = conn();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$action = $input['action'] ?? ($_POST['action'] ?? ($_GET['action'] ?? ''));
$phrase = $input['phrase'] ?? ($_POST['phrase'] ?? ($_GET['phrase'] ?? ''));
$id = $input['id'] ?? ($_POST['id'] ?? ($_GET['id'] ?? null));
$newPhrase = $input['new'] ?? ($input['new_phrase'] ?? ($_POST['new'] ?? ($_POST['new_phrase'] ?? '')));
$oldPhrase = $input['old'] ?? ($_POST['old'] ?? '');
$projectId = $input['project_id'] ?? ($_POST['project_id'] ?? ($_GET['project_id'] ?? 1));
$projectId = (int)$projectId;

function add_phrase($pdo, $phrase, $projectId) {
    $phrase = trim($phrase);
    if ($phrase === '') {
        return ['success' => false, 'error' => 'Phrase cannot be empty'];
    }
    try {
        $stmt = $pdo->prepare('INSERT INTO keywords (project_id, phrase) VALUES (:project_id, :phrase)');
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':phrase', $phrase, PDO::PARAM_STR);
        if ($stmt->execute()) {
            $id = $pdo->lastInsertId();
            return ['success' => true, 'id' => (int)$id, 'phrase' => $phrase, 'project_id' => $projectId];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Keyword already exists or database error'];
    }
    return ['success' => false];
}

function edit_phrase($pdo, $id, $old, $new) {
    $new = trim($new);
    if ($new === '') {
        return ['success' => false, 'error' => 'New phrase cannot be empty'];
    }
    try {
        if ($id) {
            $stmt = $pdo->prepare('UPDATE keywords SET phrase = :new, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
            $stmt->bindValue(':new', $new, PDO::PARAM_STR);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        } else {
            $stmt = $pdo->prepare('UPDATE keywords SET phrase = :new, updated_at = CURRENT_TIMESTAMP WHERE phrase = :old');
            $stmt->bindValue(':new', $new, PDO::PARAM_STR);
            $stmt->bindValue(':old', $old, PDO::PARAM_STR);
        }
        if ($stmt->execute()) {
            return ['success' => true, 'phrase' => $new];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Update failed or duplicate phrase'];
    }
    return ['success' => false];
}

function delete_phrase($pdo, $id) {
    if (!$id) {
        return ['success' => false, 'error' => 'Missing ID'];
    }
    try {
        $stmt = $pdo->prepare('DELETE FROM keywords WHERE id = :id');
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        if ($stmt->execute()) {
            return ['success' => true];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Delete failed'];
    }
    return ['success' => false];
}

if ($method === 'GET') {
    if ($id !== null) {
        $id = (int)$id;
        $stmt = $pdo->prepare('SELECT id, phrase, project_id FROM keywords WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $keyword = $stmt->fetch();
        if ($keyword) {
            echo json_encode(['success' => true, 'id' => $keyword['id'], 'phrase' => $keyword['phrase'], 'project_id' => $keyword['project_id']]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Keyword not found']);
        }
    } else {
        $stmt = $pdo->prepare('SELECT id, phrase, project_id FROM keywords WHERE project_id = :project_id ORDER BY phrase');
        $stmt->bindValue(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['success' => true, 'keywords' => $stmt->fetchAll()]);
    }
    exit;
}

$result = ['success' => false];

switch ($action) {
    case 'add':
        $result = add_phrase($pdo, $phrase, $projectId);
        break;
    case 'edit':
        $result = edit_phrase($pdo, $id, $oldPhrase, $newPhrase);
        break;
    case 'delete':
        $result = delete_phrase($pdo, $id ?: $phrase);
        break;
    default:
        $result = ['success' => false, 'error' => 'Invalid action'];
        break;
}

echo json_encode($result);
exit;
