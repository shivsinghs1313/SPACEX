<?php
/**
 * SPACEX Trading Academy — Environment Loader
 * Reads .env file and provides typed access to configuration values.
 * Keeps secrets out of source code.
 */

if (!defined('SPACEX_LOADED')) {
    define('SPACEX_LOADED', true);
}

class Env {
    private static $vars = [];
    private static $loaded = false;

    /**
     * Load environment variables from .env file
     */
    public static function load($path = null) {
        if (self::$loaded) return;

        $envFile = $path ?? __DIR__ . '/.env';

        // Fall back to .env.example if .env doesn't exist
        if (!file_exists($envFile)) {
            $envFile = __DIR__ . '/.env.example';
        }

        if (!file_exists($envFile)) {
            // Use hardcoded defaults if no env file exists
            self::setDefaults();
            self::$loaded = true;
            return;
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            // Skip comments
            $line = trim($line);
            if (empty($line) || $line[0] === '#') continue;

            // Parse KEY=VALUE
            if (strpos($line, '=') === false) continue;

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove surrounding quotes
            if (preg_match('/^"(.*)"$/', $value, $m)) {
                $value = $m[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $m)) {
                $value = $m[1];
            }

            self::$vars[$key] = $value;

            // Also set as environment variable
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable
     */
    public static function get($key, $default = null) {
        if (!self::$loaded) self::load();
        return self::$vars[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Get a boolean environment variable
     */
    public static function bool($key, $default = false) {
        $value = self::get($key);
        if ($value === null) return $default;
        return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
    }

    /**
     * Get an integer environment variable
     */
    public static function int($key, $default = 0) {
        $value = self::get($key);
        return $value !== null ? intval($value) : $default;
    }

    /**
     * Check if we're in development mode
     */
    public static function isDev() {
        return self::get('APP_ENV', 'development') === 'development';
    }

    /**
     * Check if we're in production mode
     */
    public static function isProd() {
        return self::get('APP_ENV', 'development') === 'production';
    }

    /**
     * Set sensible defaults when no .env exists
     */
    private static function setDefaults() {
        self::$vars = [
            'APP_ENV'                => 'development',
            'APP_URL'                => 'http://localhost',
            'APP_DEBUG'              => 'true',
            'DB_HOST'                => 'localhost',
            'DB_PORT'                => '3306',
            'DB_NAME'                => 'spacex_trading',
            'DB_USER'                => 'root',
            'DB_PASS'                => '',
            'JWT_SECRET'             => 'spacex_trading_jwt_secret_key_change_in_production_2026',
            'JWT_EXPIRY'             => '604800',
            'RAZORPAY_KEY_ID'        => 'rzp_test_xxxxxxxxxxxxx',
            'RAZORPAY_KEY_SECRET'    => 'xxxxxxxxxxxxxxxxxxxx',
            'RAZORPAY_WEBHOOK_SECRET'=> 'xxxxxxxxxxxxxxxxxxxx',
            'RAZORPAY_ENV'           => 'test',
            'VIDEO_STORAGE_PATH'     => './videos',
            'VIDEO_TOKEN_EXPIRY'     => '7200',
            'VIDEO_SIGNED_SECRET'    => 'spacex_video_signing_secret_change_me',
        ];
    }
}

// Auto-load on include
Env::load();
