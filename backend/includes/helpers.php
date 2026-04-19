<?php
/**
 * SPACEX Trading Academy — Helper Functions
 * Utility functions for JWT, sanitization, CSRF, signed URLs, and file handling
 */

if (!defined('SPACEX_LOADED')) {
    define('SPACEX_LOADED', true);
}

require_once __DIR__ . '/../config/env.php';

// JWT Secret — loaded from environment
define('JWT_SECRET', Env::get('JWT_SECRET', 'spacex_trading_jwt_secret_key_change_in_production_2026'));
define('JWT_EXPIRY', Env::int('JWT_EXPIRY', 60 * 60 * 24 * 7)); // 7 days

/**
 * Generate a JWT token
 */
function jwt_encode($payload) {
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $payloadEncoded = base64url_encode(json_encode($payload));
    
    $signature = base64url_encode(
        hash_hmac('sha256', "$header.$payloadEncoded", JWT_SECRET, true)
    );
    
    return "$header.$payloadEncoded.$signature";
}

/**
 * Decode and verify a JWT token
 */
function jwt_decode($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    
    [$header, $payload, $signature] = $parts;
    
    // Verify signature
    $expectedSig = base64url_encode(
        hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
    );
    
    if (!hash_equals($expectedSig, $signature)) {
        return null;
    }
    
    $data = json_decode(base64url_decode($payload), true);
    
    // Check expiration
    if (isset($data['exp']) && $data['exp'] < time()) {
        return null;
    }
    
    return $data;
}

/**
 * Base64 URL-safe encode
 */
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/**
 * Base64 URL-safe decode
 */
function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Sanitize string input
 */
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

/**
 * Sanitize email
 */
function sanitize_email_input($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate a random token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Get JSON request body
 */
function get_json_body() {
    $body = file_get_contents('php://input');
    return json_decode($body, true) ?? [];
}

/**
 * Get request parameter (GET or POST)
 */
function get_param($key, $default = null) {
    return $_GET[$key] ?? $_POST[$key] ?? $default;
}

/**
 * Handle file upload
 */
function handle_file_upload($fileKey, $allowedTypes, $maxSize, $uploadDir) {
    if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }

    $file = $_FILES[$fileKey];
    $fileType = mime_content_type($file['tmp_name']);
    $fileSize = $file['size'];

    // Validate type
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type: ' . $fileType];
    }

    // Validate size
    if ($fileSize > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Max: ' . ($maxSize / 1048576) . 'MB'];
    }

    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = generate_token(16) . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }

    return ['success' => false, 'message' => 'Failed to save file'];
}

/**
 * Get current user from JWT token in Authorization header
 */
function get_current_user_from_token() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        return null;
    }

    $token = substr($authHeader, 7);
    return jwt_decode($token);
}

/**
 * Format price for display
 */
function format_price($amount, $currency = 'INR') {
    if ($currency === 'INR') {
        return '₹' . number_format($amount, 0);
    }
    return '$' . number_format($amount, 2);
}

/**
 * Calculate course progress percentage
 */
function calculate_progress($userId, $courseId, $db) {
    $totalLessons = $db->fetch(
        "SELECT COUNT(*) as total FROM lessons WHERE course_id = ?",
        [$courseId]
    )['total'];

    if ($totalLessons == 0) return 0;

    $completedLessons = $db->fetch(
        "SELECT COUNT(*) as completed FROM progress p 
         JOIN lessons l ON p.lesson_id = l.id 
         WHERE p.user_id = ? AND l.course_id = ? AND p.completed = 1",
        [$userId, $courseId]
    )['completed'];

    return round(($completedLessons / $totalLessons) * 100);
}


/**
 * Generate a CSRF token and store in session
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Verify a CSRF token
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate a time-limited signed URL
 */
function generate_signed_url($path, $expirySeconds = 7200) {
    $secret = Env::get('VIDEO_SIGNED_SECRET', 'spacex_video_signing_secret_change_me');
    $expires = time() + $expirySeconds;
    $data = $path . '|' . $expires;
    $signature = hash_hmac('sha256', $data, $secret);
    
    return $path . '?expires=' . $expires . '&sig=' . $signature;
}

/**
 * Verify a signed URL
 */
function verify_signed_url($path, $expires, $signature) {
    if ($expires < time()) {
        return false; // Expired
    }
    
    $secret = Env::get('VIDEO_SIGNED_SECRET', 'spacex_video_signing_secret_change_me');
    $data = $path . '|' . $expires;
    $expectedSig = hash_hmac('sha256', $data, $secret);
    
    return hash_equals($expectedSig, $signature);
}

/**
 * Generate a URL-safe unique order receipt
 */
function generate_receipt($userId) {
    return 'SPACEX_' . strtoupper(bin2hex(random_bytes(4))) . '_' . $userId . '_' . time();
}
