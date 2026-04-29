<?php
/**
 * Database Session Handler
 * Stores sessions in MySQL instead of files (required for Render free tier)
 */

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    private $maxLifetime;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->maxLifetime = (int)ini_get('session.gc_maxlifetime');
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($sessionId): string {
        try {
            $stmt = $this->pdo->prepare("SELECT session_data FROM sessions WHERE session_id = ? AND expires_at > NOW()");
            $stmt->execute([$sessionId]);
            $row = $stmt->fetch();
            return $row ? $row['session_data'] : '';
        } catch (Exception $e) {
            error_log('Session read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write($sessionId, $data): bool {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + $this->maxLifetime);
            $stmt = $this->pdo->prepare(
                "INSERT INTO sessions (session_id, session_data, expires_at) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE session_data = VALUES(session_data), expires_at = VALUES(expires_at)"
            );
            $stmt->execute([$sessionId, $data, $expiresAt]);
            return true;
        } catch (Exception $e) {
            error_log('Session write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy($sessionId): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE session_id = ?");
            $stmt->execute([$sessionId]);
            return true;
        } catch (Exception $e) {
            error_log('Session destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc($maxLifetime): int|false {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log('Session GC error: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * Initialize database-backed sessions
 */
function initializeSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Require database connection
        if (!isset($pdo)) {
            require_once __DIR__ . '/db.php';
        }

        // Set secure cookie parameters
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' 
                   || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
        
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.gc_maxlifetime', 86400);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);

        // Use database session handler
        $handler = new DatabaseSessionHandler($pdo);
        session_set_save_handler($handler, true);

        // Start the session
        session_start();

        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 3600) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}
