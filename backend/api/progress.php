<?php
/**
 * SPACEX Trading Academy — Progress API
 * Endpoints: get progress, update progress
 */

require_once __DIR__ . '/../includes/auth_middleware.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        get_progress();
        break;
    case 'POST':
        update_progress();
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}


/**
 * GET /api/progress.php?course_id={id}
 * Get user's progress for a course
 */
function get_progress() {
    $user = require_auth();
    $courseId = get_param('course_id');

    if (!$courseId) {
        ApiResponse::error('course_id is required', 400);
    }

    $db = Database::getInstance();

    $progress = $db->fetchAll(
        "SELECT p.lesson_id, p.completed, p.watch_time, p.completed_at,
                l.title as lesson_title, l.module_name, l.sort_order
         FROM progress p
         JOIN lessons l ON p.lesson_id = l.id
         WHERE p.user_id = ? AND l.course_id = ?
         ORDER BY l.sort_order ASC",
        [$user['id'], $courseId]
    );

    $totalLessons = $db->fetch(
        "SELECT COUNT(*) as total FROM lessons WHERE course_id = ?",
        [$courseId]
    )['total'];

    $completedCount = count(array_filter($progress, fn($p) => $p['completed']));
    $percent = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

    $totalWatchTime = array_sum(array_column($progress, 'watch_time'));

    ApiResponse::success([
        'course_id'   => (int) $courseId,
        'total'       => (int) $totalLessons,
        'completed'   => $completedCount,
        'percent'     => $percent,
        'watch_time'  => $totalWatchTime,
        'lessons'     => $progress,
    ]);
}


/**
 * POST /api/progress.php
 * Update lesson completion status
 */
function update_progress() {
    $user = require_auth();
    $body = get_json_body();

    $lessonId  = intval($body['lesson_id'] ?? 0);
    $completed = (bool) ($body['completed'] ?? false);
    $watchTime = intval($body['watch_time'] ?? 0);

    if (!$lessonId) {
        ApiResponse::error('lesson_id is required', 400);
    }

    $db = Database::getInstance();

    // Verify lesson exists and user has access
    $lesson = $db->fetch("SELECT id, course_id FROM lessons WHERE id = ?", [$lessonId]);
    if (!$lesson) {
        ApiResponse::notFound('Lesson not found');
    }

    // Verify purchase (unless admin)
    if ($user['role'] !== 'admin') {
        $purchase = $db->fetch(
            "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
            [$user['id'], $lesson['course_id']]
        );
        if (!$purchase) {
            ApiResponse::forbidden('You must purchase this course first');
        }
    }

    // Upsert progress
    $existing = $db->fetch(
        "SELECT id FROM progress WHERE user_id = ? AND lesson_id = ?",
        [$user['id'], $lessonId]
    );

    if ($existing) {
        $db->query(
            "UPDATE progress SET completed = ?, watch_time = watch_time + ?, completed_at = ? WHERE id = ?",
            [$completed ? 1 : 0, $watchTime, $completed ? date('Y-m-d H:i:s') : null, $existing['id']]
        );
    } else {
        $db->query(
            "INSERT INTO progress (user_id, lesson_id, completed, watch_time, completed_at) VALUES (?, ?, ?, ?, ?)",
            [$user['id'], $lessonId, $completed ? 1 : 0, $watchTime, $completed ? date('Y-m-d H:i:s') : null]
        );
    }

    // Get updated progress
    $courseProgress = calculate_progress($user['id'], $lesson['course_id'], $db);

    ApiResponse::success([
        'lesson_id'       => $lessonId,
        'completed'       => $completed,
        'course_progress' => $courseProgress,
    ], 'Progress updated');
}
