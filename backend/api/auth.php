<?php
/**
 * SPACEX Trading Academy — Auth API
 * Endpoints: register, login, logout, me
 */

require_once __DIR__ . '/../includes/auth_middleware.php';

$action = get_param('action', '');

switch ($action) {
    case 'register':
        handle_register();
        break;
    case 'login':
        handle_login();
        break;
    case 'logout':
        handle_logout();
        break;
    case 'me':
        handle_me();
        break;
    default:
        ApiResponse::error('Invalid action', 400);
}


/**
 * POST /api/auth.php?action=register
 */
function handle_register() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    rate_limit(10, 60); // 10 registrations per minute

    $body = get_json_body();

    $name     = sanitize_input($body['name'] ?? '');
    $email    = sanitize_email_input($body['email'] ?? '');
    $password = $body['password'] ?? '';

    // Validate
    $errors = [];
    if (empty($name) || strlen($name) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    }
    if (!validate_email($email)) {
        $errors['email'] = 'Valid email is required';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if (!empty($errors)) {
        ApiResponse::validationError($errors);
    }

    $db = Database::getInstance();

    // Check if email exists
    $existing = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
    if ($existing) {
        ApiResponse::error('An account with this email already exists', 409);
    }

    // Create user
    $passwordHash = hash_password($password);

    $db->query(
        "INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'student')",
        [$name, $email, $passwordHash]
    );

    $userId = $db->lastInsertId();

    // Generate token
    $token = jwt_encode([
        'user_id' => (int) $userId,
        'email'   => $email,
        'role'    => 'student',
    ]);

    // Update last login
    $db->query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$userId]);

    ApiResponse::success([
        'token' => $token,
        'user'  => [
            'id'    => (int) $userId,
            'name'  => $name,
            'email' => $email,
            'role'  => 'student',
        ],
    ], 'Registration successful', 201);
}


/**
 * POST /api/auth.php?action=login
 */
function handle_login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    rate_limit(20, 60); // 20 login attempts per minute

    $body = get_json_body();

    $email    = sanitize_email_input($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (empty($email) || empty($password)) {
        ApiResponse::error('Email and password are required', 400);
    }

    $db = Database::getInstance();

    // Find user
    $user = $db->fetch(
        "SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = ?",
        [$email]
    );

    if (!$user) {
        ApiResponse::error('Invalid email or password', 401);
    }

    if (!$user['is_active']) {
        ApiResponse::error('Your account has been deactivated. Please contact support.', 403);
    }

    // Verify password
    if (!verify_password($password, $user['password_hash'])) {
        ApiResponse::error('Invalid email or password', 401);
    }

    // Generate token
    $token = jwt_encode([
        'user_id' => (int) $user['id'],
        'email'   => $user['email'],
        'role'    => $user['role'],
    ]);

    // Update last login
    $db->query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);

    ApiResponse::success([
        'token' => $token,
        'user'  => [
            'id'    => (int) $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ],
    ], 'Login successful');
}


/**
 * POST /api/auth.php?action=logout
 */
function handle_logout() {
    // With JWT, logout is handled client-side by removing the token
    // Optionally, you could add token to a blacklist
    ApiResponse::success(null, 'Logged out successfully');
}


/**
 * GET /api/auth.php?action=me
 */
function handle_me() {
    $user = require_auth();

    $db = Database::getInstance();
    $fullUser = $db->fetch(
        "SELECT id, name, email, role, avatar_url, phone, created_at, last_login_at FROM users WHERE id = ?",
        [$user['id']]
    );

    if (!$fullUser) {
        ApiResponse::notFound('User not found');
    }

    ApiResponse::success($fullUser);
}
