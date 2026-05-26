-- Password Reset Tokens Table
-- For SMTP-only password reset functionality

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_token (token),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cleanup expired tokens (run daily via cron)
-- DELETE FROM password_reset_tokens WHERE expires_at < NOW();
