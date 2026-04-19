<?php
/**
 * SPACEX Trading Academy — Courses API
 * Endpoints: list, get, create, update
 */

require_once __DIR__ . '/../includes/auth_middleware.php';

$method = $_SERVER['REQUEST_METHOD'];
$courseId = get_param('id');

switch ($method) {
    case 'GET':
        if ($courseId) {
            get_course($courseId);
        } else {
            list_courses();
        }
        break;
    case 'POST':
        create_course();
        break;
    case 'PUT':
        update_course($courseId);
        break;
    default:
        ApiResponse::error('Method not allowed', 405);
}


/**
 * GET /api/courses.php — List all published courses
 */
function list_courses() {
    $db = Database::getInstance();

    $courses = $db->fetchAll(
        "SELECT id, title, slug, short_description, price, discount_price, currency, 
                thumbnail_url, total_duration, total_lessons, created_at
         FROM courses 
         WHERE status = 'published'
         ORDER BY created_at DESC"
    );

    // Format prices
    foreach ($courses as &$course) {
        $course['price_formatted'] = format_price($course['price'], $course['currency']);
        if ($course['discount_price']) {
            $course['discount_price_formatted'] = format_price($course['discount_price'], $course['currency']);
            $course['discount_percent'] = round((1 - $course['discount_price'] / $course['price']) * 100);
        }
    }

    ApiResponse::success($courses);
}


/**
 * GET /api/courses.php?id={id} — Get single course with lessons
 */
function get_course($courseId) {
    $db = Database::getInstance();

    $course = $db->fetch(
        "SELECT * FROM courses WHERE id = ? AND status = 'published'",
        [$courseId]
    );

    if (!$course) {
        ApiResponse::notFound('Course not found');
    }

    // Get lessons grouped by module
    $lessons = $db->fetchAll(
        "SELECT id, module_name, module_order, title, description, video_duration, is_preview, sort_order
         FROM lessons 
         WHERE course_id = ?
         ORDER BY module_order ASC, sort_order ASC",
        [$courseId]
    );

    // Group lessons by module
    $modules = [];
    foreach ($lessons as $lesson) {
        $moduleName = $lesson['module_name'];
        if (!isset($modules[$moduleName])) {
            $modules[$moduleName] = [
                'name'   => $moduleName,
                'order'  => $lesson['module_order'],
                'lessons' => [],
            ];
        }
        $modules[$moduleName]['lessons'][] = $lesson;
    }

    $course['modules'] = array_values($modules);
    $course['price_formatted'] = format_price($course['price'], $course['currency']);
    if ($course['discount_price']) {
        $course['discount_price_formatted'] = format_price($course['discount_price'], $course['currency']);
    }

    // Check if current user has purchased
    $user = authenticate();
    $course['is_purchased'] = false;
    if ($user) {
        $purchase = $db->fetch(
            "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
            [$user['id'], $courseId]
        );
        $course['is_purchased'] = !!$purchase;
    }

    ApiResponse::success($course);
}


/**
 * POST /api/courses.php — Admin: Create course
 */
function create_course() {
    $user = require_admin();

    $body = get_json_body();

    $title       = sanitize_input($body['title'] ?? '');
    $description = $body['description'] ?? '';
    $price       = floatval($body['price'] ?? 0);
    $discountPrice = isset($body['discount_price']) ? floatval($body['discount_price']) : null;

    if (empty($title)) {
        ApiResponse::validationError(['title' => 'Title is required']);
    }

    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title));

    $db = Database::getInstance();

    $db->query(
        "INSERT INTO courses (title, slug, description, price, discount_price, status) 
         VALUES (?, ?, ?, ?, ?, 'draft')",
        [$title, $slug, $description, $price, $discountPrice]
    );

    $courseId = $db->lastInsertId();
    $course = $db->fetch("SELECT * FROM courses WHERE id = ?", [$courseId]);

    ApiResponse::success($course, 'Course created successfully', 201);
}


/**
 * PUT /api/courses.php?id={id} — Admin: Update course
 */
function update_course($courseId) {
    $user = require_admin();

    if (!$courseId) {
        ApiResponse::error('Course ID required', 400);
    }

    $db = Database::getInstance();
    $course = $db->fetch("SELECT * FROM courses WHERE id = ?", [$courseId]);

    if (!$course) {
        ApiResponse::notFound('Course not found');
    }

    $body = get_json_body();

    $updates = [];
    $params = [];

    $fields = ['title', 'description', 'short_description', 'price', 'discount_price', 'status', 'thumbnail_url', 'total_duration'];
    foreach ($fields as $field) {
        if (isset($body[$field])) {
            $updates[] = "$field = ?";
            $params[] = $body[$field];
        }
    }

    if (empty($updates)) {
        ApiResponse::error('No fields to update', 400);
    }

    $params[] = $courseId;
    $db->query(
        "UPDATE courses SET " . implode(', ', $updates) . " WHERE id = ?",
        $params
    );

    $updated = $db->fetch("SELECT * FROM courses WHERE id = ?", [$courseId]);
    ApiResponse::success($updated, 'Course updated successfully');
}
