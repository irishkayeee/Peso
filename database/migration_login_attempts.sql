-- Track failed login attempts and temporary account lockouts.
-- Run this migration once on databases created before the lockout feature.

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `email_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  PRIMARY KEY (`email_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
