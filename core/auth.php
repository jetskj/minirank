<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('get_current_username')) {
    function get_current_username() {
        return $_SESSION['username'] ?? null;
    }
}

if (!function_exists('register_user')) {
    function register_user($pdo, $username, $password) {
        $username = trim($username);
        $password = trim($password);

        if ($username === '' || $password === '') {
            return ['success' => false, 'error' => 'Username and password cannot be empty'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters long'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->bindValue(':password_hash', $passwordHash, PDO::PARAM_STR);
            if ($stmt->execute()) {
                $userId = $pdo->lastInsertId();
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$userId;
                $_SESSION['username'] = $username;
                return ['success' => true];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'Username already taken or database error'];
        }

        return ['success' => false, 'error' => 'Registration failed'];
    }
}

if (!function_exists('login_user')) {
    function login_user($pdo, $username, $password) {
        $username = trim($username);
        $password = trim($password);

        if ($username === '' || $password === '') {
            return ['success' => false, 'error' => 'Username and password cannot be empty'];
        }

        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username');
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Invalid username or password'];
    }
}

if (!function_exists('logout_user')) {
    function logout_user() {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

if (!function_exists('get_csrf_token')) {
    function get_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }
}
