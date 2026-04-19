<?php
/**
 * SPACEX Trading Academy — Authentication Middleware
 * JWT validation, CORS headers, and role-based access
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/response.php';

/**
 * Set CORS headers for API requests
 */
function set_cors_headers() {
    // Load allowed origins from env or use defaults
    $envOrigins = Env::get('CORS_ALLOWED_ORIGINS', 'http://localhost,http://localhost:3000,http://127.0.0.1');
    $allowedOrigins = array_map('trim', explode(',', $envOrigins));
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowedOrigins) || strpos($origin, 'spacextrading.com') !== false) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: *");
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token');
    header('Access-Control-Max-Age: 86400');
    header('Access-Control-Allow-Credentials: true');

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Authenticate the current request
 * Returns user data if valid, null otherwise
 */
function authenticate() {
    $user = get_current_user_from_token();

    if ($user === null) {
        return null;
    }

    // Verify user exists in database
    $db = Database::getInstance();
    $dbUser = $db->fetch(
        "SELECT id, name, email, role, is_active FROM users WHERE id = ? AND is_active = 1",
        [$user['user_id']]
    );

    if (!$dbUser) {
        return null;
    }

    return $dbUser;
}

/**
 * Require authentication — sends 401 if not authenticated
 */
function require_auth() {
    $user = authenticate();
    if ($user === null) {
        ApiResponse::unauthorized();
    }
    return $user;
}

/**
 * Require admin role
 */
function require_admin() {
    $user = require_auth();
    if ($user['role'] !== 'admin') {
        ApiResponse::forbidden('Admin access required.');
    }
    return $user;
}

/**
 * Require specific role
 */
function require_role($role) {
    $user = require_auth();
    if ($user['role'] !== $role) {
        ApiResponse::forbidden("Role '$role' required.");
    }
    return $user;
}

/**
 * Require that the user has purchased a specific course
 */
function require_purchase($courseId) {
    $user = require_auth();
    
    // Admins bypass purchase check
    if ($user['role'] === 'admin') {
        return $user;
    }
    
    $db = Database::getInstance();
    $purchase = $db->fetch(
        "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
        [$user['id'], $courseId]
    );
    
    if (!$purchase) {
        ApiResponse::forbidden('You must purchase this course to access this content.');
    }
    
    return $user;
}

/**
 * Rate limiting (basic implementation)
 */
function rate_limit($maxRequests = 60, $windowSeconds = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . md5($ip);

    // Simple file-based rate limiting
    $rateLimitFile = sys_get_temp_dir() . '/' . $key;

    $data = ['count' => 0, 'reset' => time() + $windowSeconds];

    if (file_exists($rateLimitFile)) {
        $data = json_decode(file_get_contents($rateLimitFile), true);
        
        if ($data['reset'] < time()) {
            $data = ['count' => 0, 'reset' => time() + $windowSeconds];
        }
    }

    $data['count']++;

    file_put_contents($rateLimitFile, json_encode($data));

    if ($data['count'] > $maxRequests) {
        header('Retry-After: ' . ($data['reset'] - time()));
        ApiResponse::error('Too many requests. Please try again later.', 429);
    }
}

// Apply CORS headers to all API requests
set_cors_headers();
