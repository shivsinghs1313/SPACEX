<?php
/**
 * SPACEX Trading Academy — Razorpay Webhook Handler
 * Handles server-to-server payment confirmations from Razorpay.
 * Endpoint: POST /api/webhook.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/razorpay.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/response.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read raw body
$rawBody = file_get_contents('php://input');

if (empty($rawBody)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

// Get Razorpay signature
$signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (empty($signature)) {
    error_log('Webhook: Missing X-Razorpay-Signature header');
    http_response_code(401);
    echo json_encode(['error' => 'Missing signature']);
    exit;
}

// Verify webhook signature
if (!RazorpayConfig::verifyWebhookSignature($rawBody, $signature)) {
    error_log('Webhook: Invalid signature');
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Parse event payload
$event = json_decode($rawBody, true);

if (!$event || !isset($event['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$eventType = $event['event'];
$db = Database::getInstance();

// Log to dedicated file for auditing
$logFile = __DIR__ . '/../../logs/payments.log';
$logDir = dirname($logFile);
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logEntry = "[" . date('Y-m-d H:i:s') . "] Event: $eventType | Raw: " . trim($rawBody) . PHP_EOL;
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Idempotency: Deduplicate events
$eventId = $event['payload']['payment']['entity']['id'] ?? ($event['payload']['refund']['entity']['id'] ?? 'unknown');

try {
    // Check if event already processed
    $existing = $db->fetch("SELECT id FROM payment_webhooks WHERE event_id = ? AND event_type = ?", [$eventId, $eventType]);
    if ($existing) {
        error_log("Webhook: Event $eventId of type $eventType already processed. Ignored.");
        http_response_code(200);
        echo json_encode(['status' => 'ignored', 'reason' => 'duplicate']);
        exit;
    }

    $db->query(
        "INSERT INTO payment_webhooks (event_type, event_id, payload, received_at) VALUES (?, ?, ?, NOW())",
        [$eventType, $eventId, $rawBody]
    );
} catch (\Exception $e) {
    // Table might not exist yet; log but don't fail
    error_log('Webhook: Could not log event to DB - ' . $e->getMessage());
}

// Handle events
switch ($eventType) {
    case 'payment.captured':
        handle_payment_captured($event, $db);
        break;

    case 'payment.failed':
        handle_payment_failed($event, $db);
        break;

    case 'refund.created':
    case 'refund.processed':
        handle_refund($event, $db);
        break;

    case 'payment.authorized':
        // Auto-capture is usually enabled, just log
        error_log("Webhook: Payment authorized - " . ($event['payload']['payment']['entity']['id'] ?? 'unknown'));
        break;

    default:
        error_log("Webhook: Unhandled event type: $eventType");
}

// Always return 200 to Razorpay (prevent retries for handled events)
http_response_code(200);
echo json_encode(['status' => 'ok', 'event' => $eventType]);
exit;


// ---- Event Handlers ----

/**
 * Handle successful payment capture
 */
function handle_payment_captured($event, $db) {
    $payment = $event['payload']['payment']['entity'];
    $orderId  = $payment['order_id'] ?? '';
    $paymentId = $payment['id'] ?? '';
    $amount   = ($payment['amount'] ?? 0) / 100; // Convert paise to rupees

    if (empty($orderId)) {
        error_log('Webhook payment.captured: No order_id in payload');
        return;
    }

    // Find purchase by order ID
    $purchase = $db->fetch(
        "SELECT * FROM purchases WHERE payment_order_id = ?",
        [$orderId]
    );

    if (!$purchase) {
        error_log("Webhook payment.captured: No purchase found for order $orderId");
        return;
    }

    // Skip if already completed (idempotency)
    if ($purchase['payment_status'] === 'completed') {
        error_log("Webhook payment.captured: Purchase {$purchase['id']} already completed");
        return;
    }

    // Update purchase to completed
    try {
        $db->beginTransaction();

        $db->query(
            "UPDATE purchases SET 
                payment_id = ?,
                payment_status = 'completed',
                notes = CONCAT(COALESCE(notes, ''), '\nWebhook confirmed: ', NOW())
             WHERE id = ?",
            [$paymentId, $purchase['id']]
        );

        $db->commit();

        error_log("Webhook: Payment captured for purchase {$purchase['id']}, order $orderId, amount $amount");

    } catch (\Exception $e) {
        $db->rollback();
        error_log("Webhook payment.captured: DB error - " . $e->getMessage());
    }
}


/**
 * Handle failed payment
 */
function handle_payment_failed($event, $db) {
    $payment = $event['payload']['payment']['entity'];
    $orderId = $payment['order_id'] ?? '';

    if (empty($orderId)) return;

    $purchase = $db->fetch(
        "SELECT * FROM purchases WHERE payment_order_id = ?",
        [$orderId]
    );

    if (!$purchase) return;

    // Only update if still pending
    if ($purchase['payment_status'] === 'pending') {
        $errorDescription = $payment['error_description'] ?? 'Payment failed';
        $db->query(
            "UPDATE purchases SET 
                payment_status = 'failed',
                notes = ?
             WHERE id = ?",
            ["Failed: $errorDescription", $purchase['id']]
        );

        error_log("Webhook: Payment failed for purchase {$purchase['id']}, order $orderId");
    }
}


/**
 * Handle refund events
 */
function handle_refund($event, $db) {
    $refund = $event['payload']['refund']['entity'] ?? [];
    $paymentId = $refund['payment_id'] ?? '';

    if (empty($paymentId)) return;

    $purchase = $db->fetch(
        "SELECT * FROM purchases WHERE payment_id = ?",
        [$paymentId]
    );

    if (!$purchase) return;

    $refundAmount = ($refund['amount'] ?? 0) / 100;

    $db->query(
        "UPDATE purchases SET 
            payment_status = 'refunded',
            notes = CONCAT(COALESCE(notes, ''), '\nRefund: ₹', ?, ' at ', NOW())
         WHERE id = ?",
        [$refundAmount, $purchase['id']]
    );

    error_log("Webhook: Refund processed for purchase {$purchase['id']}, amount ₹$refundAmount");
}
