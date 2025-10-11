<?php
declare(strict_types=1);

/**
 * Lightweight helper utilities used across the password reset flow.
 */

if (!function_exists('ensure_password_reset_table_exists')) {
    /**
     * Ensures the password reset tokens table exists. Safe to call multiple times.
     */
    function ensure_password_reset_table_exists(mysqli $conn): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    used_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_password_reset_user (user_id),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id)
        REFERENCES backend_users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

        $conn->query($sql);
    }
}

if (!function_exists('fetch_user_by_identifier')) {
    /**
     * Attempts to fetch a backend user using login or e-mail.
     *
     * @return array|null
     */
    function fetch_user_by_identifier(mysqli $conn, string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $sql = "SELECT id, login, username, email FROM backend_users WHERE login = ? OR email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            // Fallback for schemas without e-mail column.
            $sqlFallback = "SELECT id, login, username FROM backend_users WHERE login = ? LIMIT 1";
            $stmt = $conn->prepare($sqlFallback);
            if ($stmt === false) {
                return null;
            }
            $stmt->bind_param("s", $identifier);
        } else {
            $stmt->bind_param("ss", $identifier, $identifier);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $user ?: null;
    }
}

if (!function_exists('format_reset_response')) {
    /**
     * Helper for consistent JSON responses.
     *
     * @param bool   $success
     * @param string $message
     * @param array  $extra
     */
    function format_reset_response(bool $success, string $message, array $extra = []): void
    {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra));
    }
}
