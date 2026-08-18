<?php

function add_phrase($pdo, $phrase) {
    $stmt = $pdo->prepare('INSERT INTO keywords (phrase) VALUES (:phrase)');
    $stmt->bindValue(':phrase', $phrase, PDO::PARAM_STR);
    if ($stmt->execute()) {
        return ['success' => true, 'id' => $pdo->lastInsertId()];
    }
    return ['success' => false];
}

function edit_phrase($pdo, $old, $new) {
    $stmt = $pdo->prepare('UPDATE keywords SET phrase = :new WHERE phrase = :old');
    $stmt->bindValue(':new', $new, PDO::PARAM_STR);
    $stmt->bindValue(':old', $old, PDO::PARAM_STR);
    if ($stmt->execute()) {
        return ['success' => true];
    }
    return ['success' => false];
}

function delete_phrase($pdo, $id) {
    $stmt = $pdo->prepare('DELETE FROM keywords WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    if ($stmt->execute()) {
        return ['success' => true];
    }
    return ['success' => false];
}

$pdo = conn();

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id !== null) {
        $id = (int)$id;
        $stmt = $pdo->prepare('SELECT id, phrase FROM keywords WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $keyword = $stmt->fetch();
        if ($keyword) {
            echo json_encode(['success' => true, 'id' => $keyword['id'], 'phrase' => $keyword['phrase']]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Keyword not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing id parameter']);
    }
    exit;
}

$action = $_POST['action'] ?? '';
$phrase = $_POST['phrase'] ?? '';

$result = ['success' => false];

switch ($action) {
    case 'add':
        $result = add_phrase($pdo, $phrase);
        break;
    case 'edit':
        $result = edit_phrase($pdo, $phrase, $_POST['new'] ?? '');
        break;
    case 'delete':
        $result = delete_phrase($pdo, (int)($phrase ?? 0));
        break;
}

echo json_encode($result);
exit;