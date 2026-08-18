<?php
require __DIR__.'/../core/db.php';

$router = new stdClass();
$router->dispatch = function($uri) {
    $uri = trim($uri, '/');
    if ($uri === '' || $uri === 'dashboard') {
        echo 'Dashboard placeholder';
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