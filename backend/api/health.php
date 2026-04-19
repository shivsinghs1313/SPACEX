<?php
/**
 * SPACEX Trading Academy — Health Check API
 * Validates PHP, extensions, database, and system requirements
 */

// Don't require auth for health checks
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/response.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$checks = [];
$allPassed = true;

// ---- PHP Version ----
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.4.0', '>=');
$checks['php_version'] = [
    'status'  => $phpOk ? 'pass' : 'fail',
    'value'   => $phpVersion,
    'minimum' => '7.4.0',
];
if (!$phpOk) $allPassed = false;

// ---- Required Extensions ----
$requiredExtensions = ['pdo_mysql', 'curl', 'mbstring', 'openssl', 'json', 'fileinfo'];
$extensionResults = [];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext);
    $extensionResults[$ext] = $loaded ? 'loaded' : 'missing';
    if (!$loaded) $allPassed = false;
}
$checks['extensions'] = [
    'status' => !in_array('missing', $extensionResults) ? 'pass' : 'fail',
    'details' => $extensionResults,
];

// ---- Database Connectivity ----
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        Env::get('DB_HOST', 'localhost'),
        Env::get('DB_PORT', '3306'),
        Env::get('DB_NAME', 'spacex_trading')
    );
    $pdo = new PDO($dsn, Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''), [
        PDO::ATTR_TIMEOUT => 5,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Check tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['users', 'courses', 'lessons', 'purchases', 'progress', 'auth_tokens'];
    $missingTables = array_diff($requiredTables, $tables);

    $checks['database'] = [
        'status'         => 'pass',
        'connection'     => 'ok',
        'database'       => Env::get('DB_NAME', 'spacex_trading'),
        'tables_found'   => count($tables),
        'missing_tables' => $missingTables ?: 'none',
    ];

    if (!empty($missingTables)) {
        $checks['database']['status'] = 'warn';
        $checks['database']['note'] = 'Run schema.sql to create missing tables';
    }
} catch (PDOException $e) {
    $allPassed = false;
    $checks['database'] = [
        'status'  => 'fail',
        'error'   => $e->getMessage(),
        'hint'    => 'Ensure MySQL is running and credentials in .env are correct',
    ];
}

// ---- cURL (for Razorpay API) ----
$curlOk = function_exists('curl_init');
$checks['curl'] = [
    'status'  => $curlOk ? 'pass' : 'fail',
    'version' => $curlOk ? curl_version()['version'] : 'not available',
    'ssl'     => $curlOk ? curl_version()['ssl_version'] : 'not available',
];
if (!$curlOk) $allPassed = false;

// ---- File Permissions ----
$tempDir = sys_get_temp_dir();
$tempWritable = is_writable($tempDir);
$checks['filesystem'] = [
    'status'       => $tempWritable ? 'pass' : 'warn',
    'temp_dir'     => $tempDir,
    'writable'     => $tempWritable,
    'upload_limit' => ini_get('upload_max_filesize'),
    'post_limit'   => ini_get('post_max_size'),
    'memory_limit' => ini_get('memory_limit'),
];

// ---- Razorpay Config ----
$razorpayKeyId = Env::get('RAZORPAY_KEY_ID', '');
$razorpayConfigured = !empty($razorpayKeyId) && $razorpayKeyId !== 'rzp_test_xxxxxxxxxxxxx';
$checks['razorpay'] = [
    'status'     => $razorpayConfigured ? 'pass' : 'warn',
    'configured' => $razorpayConfigured,
    'mode'       => Env::get('RAZORPAY_ENV', 'test'),
    'note'       => $razorpayConfigured ? 'API keys configured' : 'Using placeholder keys — set real keys in .env',
];

// ---- Environment ----
$checks['environment'] = [
    'status'   => 'pass',
    'app_env'  => Env::get('APP_ENV', 'development'),
    'debug'    => Env::bool('APP_DEBUG', true),
    'server'   => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
    'os'       => PHP_OS,
];

// ---- JWT Config ----
$jwtSecret = Env::get('JWT_SECRET', '');
$jwtDefault = $jwtSecret === 'spacex_trading_jwt_secret_key_change_in_production_2026';
$checks['jwt'] = [
    'status'        => $jwtDefault ? 'warn' : 'pass',
    'using_default' => $jwtDefault,
    'note'          => $jwtDefault ? 'Change JWT_SECRET in .env for production' : 'Custom secret configured',
];

// ---- Response ----
$overallStatus = $allPassed ? 'healthy' : 'degraded';
$httpCode = $allPassed ? 200 : 503;

http_response_code($httpCode);
echo json_encode([
    'status'    => $overallStatus,
    'timestamp' => date('c'),
    'version'   => '1.0.0',
    'checks'    => $checks,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit;
