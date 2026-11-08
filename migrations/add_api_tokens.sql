-- ==========================================================
-- MIGRASI: Tabel token API mobile (api_tokens)
-- Untuk database yang sudah diimpor sebelum fitur API
-- aplikasi Flutter ditambahkan. Jalankan sekali saja di phpMyAdmin.
-- ==========================================================

CREATE TABLE IF NOT EXISTS `api_tokens` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  UNIQUE KEY `uniq_token_hash` (`token_hash`),
  KEY `idx_token_user` (`user_id`),
  CONSTRAINT `fk_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
