<?php
/**
 * SPACEX Trading Academy — Purchases API
 * Endpoints: create, verify, history, status
 * Integrated with Razorpay payment gateway
 */

require_once __DIR__ . '/../includes/auth_middleware.php';
require_once __DIR__ . '/../config/razorpay.php';

$action = get_param('action', '');

switch ($action) {
    case 'create':
        create_purchase();
        break;
    case 'verify':
        verify_payment();
        break;
    case 'history':
        get_purchase_history();
        break;
    case 'status':
        check_purchase_status();
        break;
    default:
        ApiResponse::error('Invalid action. Use: create, verify, history, or status', 400);
}


/**
 * POST /api/purchases.php?action=create
 * Create a Razorpay order and purchase record
 */
function create_purchase() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    $user = require_auth();
    $body = get_json_body();
    $courseId = intval($body['course_id'] ?? 0);

    if (!$courseId) {
        ApiResponse::error('course_id is required', 400);
    }

    $db = Database::getInstance();

    // Get course
    $course = $db->fetch(
        "SELECT id, title, price, discount_price, currency FROM courses WHERE id = ? AND status = 'published'",
        [$courseId]
    );

    if (!$course) {
        ApiResponse::notFound('Course not found');
    }

    // Check if already purchased
    $existingPurchase = $db->fetch(
        "SELECT id FROM purchases WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
        [$user['id'], $courseId]
    );

    if ($existingPurchase) {
        ApiResponse::error('You have already purchased this course', 409);
    }

    // Determine payment amount
    $amount = $course['discount_price'] ?? $course['price'];
    $receipt = 'rcpt_' . time() . '_' . $user['id'];

    // Apply coupon if provided
    $couponId = null;
    $discountAmount = 0;
    $couponCode = $body['coupon_code'] ?? '';

    if (!empty($couponCode)) {
        $coupon = $db->fetch(
            "SELECT * FROM coupons WHERE code = ? AND is_active = 1 
             AND (max_uses IS NULL OR used_count < max_uses)
             AND (valid_from IS NULL OR valid_from <= NOW())
             AND (valid_to IS NULL OR valid_to >= NOW())",
            [$couponCode]
        );

        if ($coupon) {
            $couponId = $coupon['id'];
            if ($coupon['discount_type'] === 'percent') {
                $discountAmount = round($amount * $coupon['discount_value'] / 100, 2);
            } else {
                $discountAmount = min($coupon['discount_value'], $amount);
            }
            $amount = max(0, $amount - $discountAmount);

            // Increment coupon usage
            $db->query("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?", [$couponId]);
        }
    }

    // Create purchase record
    $db->query(
        "INSERT INTO purchases (user_id, course_id, amount, currency, payment_gateway, payment_status, receipt, coupon_id, discount_amount) 
         VALUES (?, ?, ?, ?, 'razorpay', 'pending', ?, ?, ?)",
        [$user['id'], $courseId, $amount, $course['currency'], $receipt, $couponId, $discountAmount]
    );

    $purchaseId = $db->lastInsertId();

    // Create Razorpay order
    try {
        $notes = [
            'course_id'   => (string) $courseId,
            'user_id'     => (string) $user['id'],
            'purchase_id' => (string) $purchaseId,
            'user_email'  => $user['email'],
        ];

        // Use real Razorpay API or simulated order based on config
        if (RazorpayConfig::getKeyId() !== 'rzp_test_xxxxxxxxxxxxx') {
            $order = RazorpayConfig::createOrder($amount, $course['currency'], $receipt, $notes);
        } else {
            // Development mode — simulated order
            $order = RazorpayConfig::createSimulatedOrder($amount, $course['currency'], $receipt);
        }
    } catch (\Exception $e) {
        // Fallback to simulated order if Razorpay API fails
        error_log('Razorpay order creation failed: ' . $e->getMessage());
        $order = RazorpayConfig::createSimulatedOrder($amount, $course['currency'], $receipt);
    }

    // Update purchase with order ID
    $db->query(
        "UPDATE purchases SET payment_order_id = ? WHERE id = ?",
        [$order['id'], $purchaseId]
    );

    ApiResponse::success([
        'purchase_id'    => (int) $purchaseId,
        'order_id'       => $order['id'],
        'amount'         => (float) $amount,
        'original_price' => (float) $course['price'],
        'discount'       => (float) $discountAmount,
        'currency'       => $course['currency'],
        'course_title'   => $course['title'],
        'key_id'         => RazorpayConfig::getKeyId(),
        'user_name'      => $user['name'],
        'user_email'     => $user['email'],
        'is_test'        => RazorpayConfig::isTestMode(),
    ], 'Order created successfully');
}


/**
 * POST /api/purchases.php?action=verify
 * Verify payment callback from Razorpay
 */
function verify_payment() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ApiResponse::error('Method not allowed', 405);
    }

    $user = require_auth();
    $body = get_json_body();

    $orderId   = $body['razorpay_order_id'] ?? '';
    $paymentId = $body['razorpay_payment_id'] ?? '';
    $signature = $body['razorpay_signature'] ?? '';

    if (empty($orderId) || empty($paymentId)) {
        ApiResponse::error('Payment details are required', 400);
    }

    $db = Database::getInstance();

    // Find the purchase
    $purchase = $db->fetch(
        "SELECT * FROM purchases WHERE payment_order_id = ? AND user_id = ?",
        [$orderId, $user['id']]
    );

    if (!$purchase) {
        ApiResponse::notFound('Purchase not found');
    }

    if ($purchase['payment_status'] === 'completed') {
        ApiResponse::error('Payment already verified', 409);
    }

    // Verify Razorpay signature
    $signatureValid = false;

    if (RazorpayConfig::getKeyId() !== 'rzp_test_xxxxxxxxxxxxx' && !empty($signature)) {
        // Real signature verification
        $signatureValid = RazorpayConfig::verifyPaymentSignature($orderId, $paymentId, $signature);

        if (!$signatureValid) {
            $db->query(
                "UPDATE purchases SET payment_status = 'failed', notes = 'Signature verification failed' WHERE id = ?",
                [$purchase['id']]
            );
            ApiResponse::error('Payment verification failed — invalid signature', 400);
        }
    } else {
        // Development mode — accept all payments
        $signatureValid = true;
    }

    // Update purchase as completed
    $db->query(
        "UPDATE purchases SET payment_id = ?, payment_signature = ?, payment_status = 'completed' WHERE id = ?",
        [$paymentId, $signature, $purchase['id']]
    );

    ApiResponse::success([
        'purchase_id' => (int) $purchase['id'],
        'course_id'   => (int) $purchase['course_id'],
        'status'      => 'completed',
    ], 'Payment verified successfully! Course unlocked.');
}


/**
 * GET /api/purchases.php?action=history
 * Get user's purchase history
 */
function get_purchase_history() {
    $user = require_auth();
    $db = Database::getInstance();

    $purchases = $db->fetchAll(
        "SELECT p.id, p.amount, p.currency, p.payment_status, p.payment_gateway, p.created_at,
                p.discount_amount, p.payment_id,
                c.title as course_title, c.slug as course_slug, c.thumbnail_url
         FROM purchases p
         JOIN courses c ON p.course_id = c.id
         WHERE p.user_id = ?
         ORDER BY p.created_at DESC",
        [$user['id']]
    );

    foreach ($purchases as &$p) {
        $p['amount_formatted'] = format_price($p['amount'], $p['currency']);
    }

    ApiResponse::success($purchases);
}


/**
 * GET /api/purchases.php?action=status&course_id={id}
 * Quick check if user has purchased a specific course
 */
function check_purchase_status() {
    $user = require_auth();
    $courseId = intval(get_param('course_id', 0));

    if (!$courseId) {
        ApiResponse::error('course_id is required', 400);
    }

    $db = Database::getInstance();

    $purchase = $db->fetch(
        "SELECT id, payment_status, created_at FROM purchases 
         WHERE user_id = ? AND course_id = ? AND payment_status = 'completed'",
        [$user['id'], $courseId]
    );

    ApiResponse::success([
        'purchased'   => !!$purchase,
        'purchase_id' => $purchase ? (int) $purchase['id'] : null,
        'purchased_at'=> $purchase ? $purchase['created_at'] : null,
    ]);
}
