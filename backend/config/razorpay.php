<?php
/**
 * SPACEX Trading Academy — Razorpay Configuration
 * Centralized gateway config with order creation and signature verification.
 */

require_once __DIR__ . '/env.php';

class RazorpayConfig {
    private static $keyId;
    private static $keySecret;
    private static $webhookSecret;
    private static $isTest;

    /**
     * Initialize Razorpay configuration from environment
     */
    public static function init() {
        self::$keyId         = Env::get('RAZORPAY_KEY_ID', 'rzp_test_xxxxxxxxxxxxx');
        self::$keySecret     = Env::get('RAZORPAY_KEY_SECRET', 'xxxxxxxxxxxxxxxxxxxx');
        self::$webhookSecret = Env::get('RAZORPAY_WEBHOOK_SECRET', 'xxxxxxxxxxxxxxxxxxxx');
        self::$isTest        = Env::get('RAZORPAY_ENV', 'test') === 'test';
    }

    /**
     * Get the public key ID (safe for frontend)
     */
    public static function getKeyId() {
        if (!self::$keyId) self::init();
        return self::$keyId;
    }

    /**
     * Check if running in test mode
     */
    public static function isTestMode() {
        if (self::$isTest === null) self::init();
        return self::$isTest;
    }

    /**
     * Create a Razorpay order via their API
     *
     * @param float  $amount   Amount in main currency unit (e.g., 4999.00 for INR)
     * @param string $currency Currency code
     * @param string $receipt  Unique receipt identifier
     * @param array  $notes    Optional metadata
     * @return array           Razorpay order response
     */
    public static function createOrder($amount, $currency = 'INR', $receipt = '', $notes = []) {
        if (!self::$keyId) self::init();

        $orderData = [
            'amount'   => intval($amount * 100), // Razorpay expects paise
            'currency' => $currency,
            'receipt'  => $receipt,
        ];

        if (!empty($notes)) {
            $orderData['notes'] = $notes;
        }

        $response = self::apiRequest('POST', '/v1/orders', $orderData);

        if (!$response || isset($response['error'])) {
            $errorMsg = $response['error']['description'] ?? 'Failed to create Razorpay order';
            throw new \Exception($errorMsg);
        }

        return $response;
    }

    /**
     * Fetch order details from Razorpay
     */
    public static function fetchOrder($orderId) {
        return self::apiRequest('GET', "/v1/orders/{$orderId}");
    }

    /**
     * Fetch payment details from Razorpay
     */
    public static function fetchPayment($paymentId) {
        return self::apiRequest('GET', "/v1/payments/{$paymentId}");
    }

    /**
     * Verify Razorpay payment signature (checkout callback)
     *
     * @param string $orderId    Razorpay order ID
     * @param string $paymentId  Razorpay payment ID
     * @param string $signature  Signature from checkout callback
     * @return bool
     */
    public static function verifyPaymentSignature($orderId, $paymentId, $signature) {
        if (!self::$keySecret) self::init();

        $payload = $orderId . '|' . $paymentId;
        $expectedSignature = hash_hmac('sha256', $payload, self::$keySecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Razorpay webhook signature
     *
     * @param string $payload   Raw request body
     * @param string $signature X-Razorpay-Signature header value
     * @return bool
     */
    public static function verifyWebhookSignature($payload, $signature) {
        if (!self::$webhookSecret) self::init();

        $expectedSignature = hash_hmac('sha256', $payload, self::$webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Make an API request to Razorpay
     */
    private static function apiRequest($method, $endpoint, $data = null) {
        if (!self::$keyId) self::init();

        $url = 'https://api.razorpay.com' . $endpoint;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, self::$keyId . ':' . self::$keySecret);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $headers = ['Content-Type: application/json'];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Razorpay cURL error: $curlError");
            return null;
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            error_log("Razorpay API error ($httpCode): $response");
        }

        return $decoded;
    }

    /**
     * Generate simulated order for development (no real API call)
     */
    public static function createSimulatedOrder($amount, $currency = 'INR', $receipt = '') {
        return [
            'id'       => 'order_sim_' . bin2hex(random_bytes(8)),
            'amount'   => intval($amount * 100),
            'currency' => $currency,
            'receipt'  => $receipt,
            'status'   => 'created',
            'created_at' => time(),
        ];
    }
}

// Auto-init
RazorpayConfig::init();
