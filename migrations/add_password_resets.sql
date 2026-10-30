-- ==========================================================
-- MIGRASI: TABEL RESET KATA SANDI (password_resets)
-- Jalankan sekali saja pada database yang sudah berjalan,
-- misalnya melalui phpMyAdmin XAMPP atau baris perintah:
--   mysql -u root db_bahan_ajar_informatika < migrations/add_password_resets.sql
-- ==========================================================

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(100) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_resets_email_token` (`email`, `token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
