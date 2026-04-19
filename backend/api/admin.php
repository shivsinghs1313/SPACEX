<?php
/**
 * SPACEX Trading Academy — Admin API
 * Endpoints: stats, users, course/lesson management
 */

require_once __DIR__ . '/../includes/auth_middleware.php';

$action = get_param('action', '');

switch ($action) {
    case 'stats':
        get_stats();
        break;
    case 'users':
        manage_users();
        break;
    case 'add_lesson':
        add_lesson();
        break;
    case 'update_lesson':
        update_lesson();
        break;
    case 'delete_lesson':
        delete_lesson();
        break;
    default:
        ApiResponse::error('Invalid action', 400);
}


/**
 * GET /api/admin.php?action=stats
 * Dashboard statistics
 */
function get_stats() {
    $user = require_admin();
    $db = Database::getInstance();

    $totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'student'")['count'];
    $totalPurchases = $db->fetch("SELECT COUNT(*) as count FROM purchases WHERE payment_status = 'completed'")['count'];
    $totalRevenue = $db->fetch("SELECT COALESCE(SUM(amount), 0) as total FROM purchases WHERE payment_status = 'completed'")['total'];
    $totalCourses = $db->fetch("SELECT COUNT(*) as count FROM courses")['count'];

    // Recent purchases
    $recentPurchases = $db->fetchAll(
        "SELECT p.id, p.amount, p.currency, p.created_at,
                u.name as user_name, u.email as user_email,
                c.title as course_title
         FROM purchases p
         JOIN users u ON p.user_id = u.id
         JOIN courses c ON p.course_id = c.id
         WHERE p.payment_status = 'completed'
         ORDER BY p.created_at DESC
         LIMIT 10"
    );

    // Signups this month
    $monthlySignups = $db->fetch(
        "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
    )['count'];

    ApiResponse::success([
        'total_users'       => (int) $totalUsers,
        'total_purchases'   => (int) $totalPurchases,
        'total_revenue'     => (float) $totalRevenue,
        'total_revenue_formatted' => format_price($totalRevenue),
        'total_courses'     => (int) $totalCourses,
        'monthly_signups'   => (int) $monthlySignups,
        'recent_purchases'  => $recentPurchases,
    ]);
}


/**
 * GET /api/admin.php?action=users
 * List users with pagination
 */
function manage_users() {
    $user = require_admin();
    $db = Database::getInstance();

    $page = max(1, intval(get_param('page', 1)));
    $limit = 20;
    $offset = ($page - 1) * $limit;

    $search = get_param('search', '');

    $whereClause = "WHERE role = 'student'";
    $params = [];

    if (!empty($search)) {
        $whereClause .= " AND (name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $total = $db->fetch("SELECT COUNT(*) as count FROM users $whereClause", $params)['count'];

    $params[] = $limit;
    $params[] = $offset;

    $users = $db->fetchAll(
        "SELECT id, name, email, is_active, created_at, last_login_at 
         FROM users $whereClause 
         ORDER BY created_at DESC 
         LIMIT ? OFFSET ?",
        $params
    );

    // Get purchase info for each user
    foreach ($users as &$u) {
        $purchases = $db->fetch(
            "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
             FROM purchases WHERE user_id = ? AND payment_status = 'completed'",
            [$u['id']]
        );
        $u['purchases'] = (int) $purchases['count'];
        $u['total_spent'] = format_price($purchases['total']);
    }

    ApiResponse::success([
        'users'       => $users,
        'total'       => (int) $total,
        'page'        => $page,
        'total_pages' => ceil($total / $limit),
    ]);
}


/**
 * POST /api/admin.php?action=add_lesson
 * Add a new lesson to a course
 */
function add_lesson() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    $user = require_admin();
    $body = get_json_body();

    $courseId    = intval($body['course_id'] ?? 0);
    $moduleName  = sanitize_input($body['module_name'] ?? '');
    $moduleOrder = intval($body['module_order'] ?? 0);
    $title       = sanitize_input($body['title'] ?? '');
    $description = $body['description'] ?? '';
    $videoUrl    = $body['video_url'] ?? '';
    $videoDuration = $body['video_duration'] ?? '';
    $pdfUrl      = $body['pdf_url'] ?? '';
    $sortOrder   = intval($body['sort_order'] ?? 0);

    $errors = [];
    if (!$courseId) $errors['course_id'] = 'Course ID is required';
    if (empty($title)) $errors['title'] = 'Title is required';
    if (empty($moduleName)) $errors['module_name'] = 'Module name is required';

    if (!empty($errors)) {
        ApiResponse::validationError($errors);
    }

    $db = Database::getInstance();

    // Verify course exists
    $course = $db->fetch("SELECT id FROM courses WHERE id = ?", [$courseId]);
    if (!$course) {
        ApiResponse::notFound('Course not found');
    }

    $db->query(
        "INSERT INTO lessons (course_id, module_name, module_order, title, description, video_url, video_duration, pdf_url, sort_order) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$courseId, $moduleName, $moduleOrder, $title, $description, $videoUrl, $videoDuration, $pdfUrl, $sortOrder]
    );

    // Update course lesson count
    $db->query(
        "UPDATE courses SET total_lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = ?) WHERE id = ?",
        [$courseId, $courseId]
    );

    $lesson = $db->fetch("SELECT * FROM lessons WHERE id = ?", [$db->lastInsertId()]);
    ApiResponse::success($lesson, 'Lesson added successfully', 201);
}


/**
 * POST /api/admin.php?action=update_lesson
 */
function update_lesson() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    $user = require_admin();
    $body = get_json_body();
    $lessonId = intval($body['lesson_id'] ?? 0);

    if (!$lessonId) {
        ApiResponse::error('lesson_id is required', 400);
    }

    $db = Database::getInstance();
    $lesson = $db->fetch("SELECT * FROM lessons WHERE id = ?", [$lessonId]);

    if (!$lesson) {
        ApiResponse::notFound('Lesson not found');
    }

    $updates = [];
    $params = [];
    $fields = ['title', 'description', 'module_name', 'module_order', 'video_url', 'video_duration', 'pdf_url', 'sort_order', 'is_preview'];
    
    foreach ($fields as $field) {
        if (isset($body[$field])) {
            $updates[] = "$field = ?";
            $params[] = $body[$field];
        }
    }

    if (empty($updates)) {
        ApiResponse::error('No fields to update', 400);
    }

    $params[] = $lessonId;
    $db->query("UPDATE lessons SET " . implode(', ', $updates) . " WHERE id = ?", $params);

    $updated = $db->fetch("SELECT * FROM lessons WHERE id = ?", [$lessonId]);
    ApiResponse::success($updated, 'Lesson updated');
}


/**
 * POST /api/admin.php?action=delete_lesson
 */
function delete_lesson() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    $user = require_admin();
    $body = get_json_body();
    $lessonId = intval($body['lesson_id'] ?? 0);

    if (!$lessonId) {
        ApiResponse::error('lesson_id is required', 400);
    }

    $db = Database::getInstance();
    $lesson = $db->fetch("SELECT id, course_id FROM lessons WHERE id = ?", [$lessonId]);

    if (!$lesson) {
        ApiResponse::notFound('Lesson not found');
    }

    $db->query("DELETE FROM lessons WHERE id = ?", [$lessonId]);

    // Update course lesson count
    $db->query(
        "UPDATE courses SET total_lessons = (SELECT COUNT(*) FROM lessons WHERE course_id = ?) WHERE id = ?",
        [$lesson['course_id'], $lesson['course_id']]
    );

    ApiResponse::success(null, 'Lesson deleted');
}
