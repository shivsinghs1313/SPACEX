<?php
/**
 * SPACEX Trading Academy — Standardized API Response Handler
 */

if (!defined('SPACEX_LOADED')) {
    define('SPACEX_LOADED', true);
}

class ApiResponse {
    /**
     * Send a success response
     */
    public static function success($data = null, $message = 'Success', $code = 200) {
        http_response_code($code);
        self::setHeaders();
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send an error response
     */
    public static function error($message = 'An error occurred', $code = 400, $errors = null) {
        http_response_code($code);
        self::setHeaders();
        $response = [
            'success' => false,
            'message' => $message,
            'data'    => null,
        ];
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send a 401 unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized. Please log in.') {
        self::error($message, 401);
    }

    /**
     * Send a 403 forbidden response
     */
    public static function forbidden($message = 'Access denied.') {
        self::error($message, 403);
    }

    /**
     * Send a 404 not found response
     */
    public static function notFound($message = 'Resource not found.') {
        self::error($message, 404);
    }

    /**
     * Send a 500 server error response
     */
    public static function serverError($message = 'Internal server error.') {
        self::error($message, 500);
    }

    /**
     * Send a 422 validation error response
     */
    public static function validationError($errors, $message = 'Validation failed.') {
        self::error($message, 422, $errors);
    }

    /**
     * Set common response headers
     */
    private static function setHeaders() {
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }
}
