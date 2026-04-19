<?php
/**
 * SPACEX Trading Academy — Lessons API
 * Endpoints: list lessons for a course (requires purchase)
 */

require_once __DIR__ . '/../includes/auth_middleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$courseId = get_param('course_id');

if ($method !== 'GET') {
    ApiResponse::error('Method not allowed', 405);
}

if (!$courseId) {
    ApiResponse::error('course_id parameter is required', 400);
}

$user = require_auth();
$db = Database::getInstance();

// Verify purchase
$purchase = $db->fetch(
    "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
    [$user['id'], $courseId]
);

if (!$purchase && $user['role'] !== 'admin') {
    ApiResponse::forbidden('You must purchase this course to access lessons.');
}

// Get lessons with progress
$lessons = $db->fetchAll(
    "SELECT l.id, l.module_name, l.module_order, l.title, l.description, 
            l.video_url, l.video_duration, l.pdf_url, l.sort_order, l.is_preview,
            COALESCE(p.completed, 0) as completed,
            p.completed_at, p.watch_time
     FROM lessons l
     LEFT JOIN progress p ON l.id = p.lesson_id AND p.user_id = ?
     WHERE l.course_id = ?
     ORDER BY l.module_order ASC, l.sort_order ASC",
    [$user['id'], $courseId]
);

// Group by module
$modules = [];
foreach ($lessons as $lesson) {
    $moduleName = $lesson['module_name'];
    if (!isset($modules[$moduleName])) {
        $modules[$moduleName] = [
            'name'    => $moduleName,
            'order'   => $lesson['module_order'],
            'lessons' => [],
        ];
    }
    $modules[$moduleName]['lessons'][] = $lesson;
}

// Calculate progress
$totalLessons = count($lessons);
$completedLessons = count(array_filter($lessons, fn($l) => $l['completed']));
$progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

ApiResponse::success([
    'course_id'  => (int) $courseId,
    'modules'    => array_values($modules),
    'progress'   => [
        'total'     => $totalLessons,
        'completed' => $completedLessons,
        'percent'   => $progressPercent,
    ],
]);
