-- Create sessions table for database-backed session storage
CREATE TABLE IF NOT EXISTS sessions (
    session_id VARCHAR(128) NOT NULL PRIMARY KEY,
    session_data TEXT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
