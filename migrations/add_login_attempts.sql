-- ==========================================================
-- MIGRASI: Tabel percobaan login (login_attempts)
-- Untuk database yang sudah diimpor sebelum fitur anti
-- brute-force ditambahkan. Jalankan sekali saja di phpMyAdmin.
-- ==========================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` DATETIME NOT NULL,
  KEY `idx_attempts_email_time` (`email`, `attempted_at`),
  KEY `idx_attempts_ip_time` (`ip_address`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
