<?php
/**
 * SPACEX Trading Academy — Secure Video Streaming API
 * Serves video content only to authenticated, paid users.
 * Supports byte-range requests for seeking, signed URL tokens, and anti-hotlink protection.
 */

require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/env.php';

$action = get_param('action', 'stream');

switch ($action) {
    case 'token':
        generate_video_token();
        break;
    case 'stream':
        stream_video();
        break;
    case 'info':
        get_video_info();
        break;
    default:
        ApiResponse::error('Invalid action', 400);
}


/**
 * GET /api/stream.php?action=token&lesson_id={id}
 * Generate a time-limited signed token for video access
 */
function generate_video_token() {
    $user = require_auth();
    $lessonId = intval(get_param('lesson_id', 0));

    if (!$lessonId) {
        ApiResponse::error('lesson_id is required', 400);
    }

    $db = Database::getInstance();

    // Get lesson and course info
    $lesson = $db->fetch(
        "SELECT l.id, l.course_id, l.video_url, l.title, l.video_duration
         FROM lessons l WHERE l.id = ?",
        [$lessonId]
    );

    if (!$lesson) {
        ApiResponse::notFound('Lesson not found');
    }

    // Verify purchase (unless admin or preview lesson)
    $isPreview = $db->fetch(
        "SELECT is_preview FROM lessons WHERE id = ?",
        [$lessonId]
    )['is_preview'];

    if (!$isPreview && $user['role'] !== 'admin') {
        $purchase = $db->fetch(
            "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
            [$user['id'], $lesson['course_id']]
        );

        if (!$purchase) {
            ApiResponse::forbidden('Purchase required to access this lesson');
        }
    }

    // Generate signed token
    $expiresAt = time() + Env::int('VIDEO_TOKEN_EXPIRY', 7200);
    $tokenData = [
        'lesson_id' => $lessonId,
        'user_id'   => $user['id'],
        'expires'   => $expiresAt,
    ];

    $token = generate_video_signed_token($tokenData);

    // Get user's progress for this lesson
    $progress = $db->fetch(
        "SELECT watch_time, completed FROM progress WHERE user_id = ? AND lesson_id = ?",
        [$user['id'], $lessonId]
    );

    ApiResponse::success([
        'token'          => $token,
        'expires_at'     => $expiresAt,
        'lesson_id'      => $lessonId,
        'title'          => $lesson['title'],
        'duration'       => $lesson['video_duration'],
        'stream_url'     => "backend/api/stream.php?action=stream&token={$token}",
        'resume_at'      => $progress ? intval($progress['watch_time']) : 0,
        'completed'      => $progress ? (bool) $progress['completed'] : false,
    ]);
}


/**
 * GET /api/stream.php?action=stream&token={token}
 * Stream the actual video file with byte-range support
 */
function stream_video() {
    $token = get_param('token', '');

    if (empty($token)) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Video access token required']);
        exit;
    }

    // Verify token
    $tokenData = verify_video_signed_token($token);

    if (!$tokenData) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid or expired video token']);
        exit;
    }

    // Check expiry
    if ($tokenData['expires'] < time()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Video token has expired']);
        exit;
    }

    $lessonId = $tokenData['lesson_id'];

    $db = Database::getInstance();
    $lesson = $db->fetch("SELECT video_url FROM lessons WHERE id = ?", [$lessonId]);

    if (!$lesson || empty($lesson['video_url'])) {
        // If no video URL is set, serve a demo/placeholder
        serve_demo_video();
        return;
    }

    $videoUrl = $lesson['video_url'];

    // Determine if it's a local file path or external URL
    if (filter_var($videoUrl, FILTER_VALIDATE_URL)) {
        // External URL — redirect with token (CDN proxy)
        header("Location: $videoUrl");
        exit;
    }

    // Local file
    $videoPath = realpath(__DIR__ . '/../../' . $videoUrl);

    if (!$videoPath || !file_exists($videoPath)) {
        // Serve demo video frame
        serve_demo_video();
        return;
    }

    // Serve file with byte-range support
    serve_video_file($videoPath);
}


/**
 * GET /api/stream.php?action=info&lesson_id={id}
 * Get video metadata (duration, size, format) without streaming
 */
function get_video_info() {
    $user = require_auth();
    $lessonId = intval(get_param('lesson_id', 0));

    if (!$lessonId) {
        ApiResponse::error('lesson_id is required', 400);
    }

    $db = Database::getInstance();
    $lesson = $db->fetch(
        "SELECT id, title, video_url, video_duration, description, module_name, module_order, sort_order
         FROM lessons WHERE id = ?",
        [$lessonId]
    );

    if (!$lesson) {
        ApiResponse::notFound('Lesson not found');
    }

    // Check if user has access
    $courseId = $db->fetch("SELECT course_id FROM lessons WHERE id = ?", [$lessonId])['course_id'];
    $hasAccess = $user['role'] === 'admin';

    if (!$hasAccess) {
        $purchase = $db->fetch(
            "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
            [$user['id'], $courseId]
        );
        $hasAccess = !!$purchase;
    }

    $isPreview = $db->fetch("SELECT is_preview FROM lessons WHERE id = ?", [$lessonId])['is_preview'];

    ApiResponse::success([
        'lesson'     => $lesson,
        'has_access' => $hasAccess || $isPreview,
        'is_preview' => (bool) $isPreview,
    ]);
}


/**
 * Serve a video file with HTTP byte-range support for seeking
 */
function serve_video_file($filePath) {
    $fileSize = filesize($filePath);
    $mimeType = mime_content_type($filePath) ?: 'video/mp4';

    // Anti-hotlink: check referer
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $allowedHosts = ['localhost', '127.0.0.1', 'spacextrading.com'];
    $refererHost = parse_url($referer, PHP_URL_HOST) ?? '';

    // Allow empty referer (direct player requests) or matching hosts
    if (!empty($referer) && !in_array($refererHost, $allowedHosts)) {
        http_response_code(403);
        echo 'Hotlink protection: access denied';
        exit;
    }

    // Prevent caching and downloading
    header("Content-Type: $mimeType");
    header("Accept-Ranges: bytes");
    header("Content-Disposition: inline");
    header("X-Content-Type-Options: nosniff");
    header("Cache-Control: no-store, no-cache, must-revalidate, private");

    // Handle byte-range requests (for seeking in video)
    if (isset($_SERVER['HTTP_RANGE'])) {
        preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $matches);
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;

        if ($start > $end || $start >= $fileSize) {
            http_response_code(416);
            header("Content-Range: bytes */$fileSize");
            exit;
        }

        $length = $end - $start + 1;

        http_response_code(206);
        header("Content-Range: bytes $start-$end/$fileSize");
        header("Content-Length: $length");

        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);

        $bufferSize = 8192;
        $remaining = $length;

        while ($remaining > 0 && !feof($fp)) {
            $readSize = min($bufferSize, $remaining);
            echo fread($fp, $readSize);
            $remaining -= $readSize;
            flush();
        }

        fclose($fp);
    } else {
        // Full file download
        header("Content-Length: $fileSize");
        readfile($filePath);
    }

    exit;
}


/**
 * Serve a generated demo video placeholder (blank with message)
 */
function serve_demo_video() {
    // Return a JSON response indicating demo mode
    header('Content-Type: application/json');
    http_response_code(200);
    echo json_encode([
        'demo'    => true,
        'message' => 'Video file not yet uploaded. This is a demo placeholder.',
        'note'    => 'Add video files to the videos/ directory or set video_url in the lessons table.',
    ]);
    exit;
}


/**
 * Generate a signed token for video access
 */
function generate_video_signed_token($data) {
    $secret = Env::get('VIDEO_SIGNED_SECRET', 'spacex_video_signing_secret_change_me');
    $payload = base64_encode(json_encode($data));
    $signature = hash_hmac('sha256', $payload, $secret);
    return $payload . '.' . $signature;
}


/**
 * Verify a signed video token
 */
function verify_video_signed_token($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 2) return null;

    [$payload, $signature] = $parts;
    $secret = Env::get('VIDEO_SIGNED_SECRET', 'spacex_video_signing_secret_change_me');
    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    if (!hash_equals($expectedSignature, $signature)) {
        return null;
    }

    return json_decode(base64_decode($payload), true);
}
