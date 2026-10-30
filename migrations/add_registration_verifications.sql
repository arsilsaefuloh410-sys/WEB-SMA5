-- ==========================================================
-- MIGRASI: TABEL VERIFIKASI PENDAFTARAN (registration_verifications)
-- Menyimpan data pendaftar guru yang sudah mengirim formulir
-- tetapi belum membuka tautan verifikasi dari emailnya.
-- Akun users BARU dibuat setelah token verifikasi dikonsumsi,
-- dengan status 'pending' menunggu persetujuan administrator.
-- Jalankan sekali saja pada database yang sudah berjalan,
-- misalnya melalui phpMyAdmin XAMPP atau baris perintah:
--   mysql -u root db_bahan_ajar_informatika < migrations/add_registration_verifications.sql
-- ==========================================================

CREATE TABLE IF NOT EXISTS `registration_verifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_regver_email_token` (`email`, `token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
