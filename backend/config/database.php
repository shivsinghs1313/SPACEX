<?php
/**
 * SPACEX Trading Academy — Database Configuration
 * WordPress-compatible PDO connection
 */

// Prevent direct access
if (!defined('SPACEX_LOADED')) {
    define('SPACEX_LOADED', true);
}

class Database {
    private static $instance = null;
    private $pdo;

    // Database configuration
    private $config = [
        'host'     => 'localhost',
        'dbname'   => 'spacex_trading',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'port'     => 3306,
    ];

    private function __construct() {
        try {
            // WordPress integration: use WP config if available
            if (defined('DB_HOST')) {
                $this->config['host']     = DB_HOST;
                $this->config['dbname']   = DB_NAME;
                $this->config['username'] = DB_USER;
                $this->config['password'] = DB_PASSWORD;
                $this->config['charset']  = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['dbname'],
                $this->config['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}",
            ];

            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);

        } catch (PDOException $e) {
            // In production, log error and show generic message
            error_log('Database connection failed: ' . $e->getMessage());
            $this->sendErrorResponse('Database connection failed', 500);
        }
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Execute a prepared query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Query error: ' . $e->getMessage() . ' | SQL: ' . $sql);
            throw $e;
        }
    }

    /**
     * Fetch all results
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch single row
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollBack();
    }

    /**
     * Send error response and exit
     */
    private function sendErrorResponse($message, $code = 500) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $message,
            'data'    => null,
        ]);
        exit;
    }

    // Prevent cloning
    private function __clone() {}

    // Prevent unserialization
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}
