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