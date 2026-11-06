-- ==========================================================
-- MIGRASI: Tabel pengaturan (key-value) untuk fitur
-- Kelola Foto Beranda (hero slideshow) dan Foto & Keterangan
-- Beranda (3 foto + teks keterangan).
-- Jalankan sekali saja di phpMyAdmin.
-- ==========================================================

CREATE TABLE IF NOT EXISTS `pengaturan` (
  `kunci` VARCHAR(50) NOT NULL,
  `nilai` TEXT DEFAULT NULL,
  PRIMARY KEY (`kunci`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
