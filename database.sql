-- ==========================================================
-- APLIKASI BAHAN AJAR INFORMATIKA SMA
-- Database: db_bahan_ajar_informatika
-- Dibuat untuk Guru SMA Mata Pelajaran Informatika
-- Siap diimpor langsung melalui phpMyAdmin XAMPP
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `db_bahan_ajar_informatika` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_bahan_ajar_informatika`;

-- --------------------------------------------------------
-- 1. TABEL PENGGUNA (users)
-- Menyimpan akun Admin (koordinator materi) dan Guru
-- --------------------------------------------------------
DROP TABLE IF EXISTS `file_materi`;
DROP TABLE IF EXISTS `referensi`;
DROP TABLE IF EXISTS `topik`;
DROP TABLE IF EXISTS `kelas`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','guru') NOT NULL DEFAULT 'guru',
  `status` ENUM('aktif','pending') NOT NULL DEFAULT 'aktif',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 2. TABEL KELAS (kelas)
-- Menyimpan jenjang kelas SMA (Kelas 10, 11, 12)
-- --------------------------------------------------------
CREATE TABLE `kelas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kelas` VARCHAR(20) NOT NULL,
  `tingkat` INT NOT NULL,
  `deskripsi` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 3. TABEL TOPIK / BAB (topik)
-- Menyimpan materi pembelajaran dalam tiap kelas
-- --------------------------------------------------------
CREATE TABLE `topik` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kelas_id` INT NOT NULL,
  `judul_topik` VARCHAR(255) NOT NULL,
  `elemen` VARCHAR(20) NOT NULL DEFAULT 'BK',
  `deskripsi` TEXT DEFAULT NULL,
  `urutan` INT NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_topik_kelas` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_topik_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 4. TABEL LINK REFERENSI (referensi)
-- Tautan eksternal ramah guru untuk eksplorasi materi
-- --------------------------------------------------------
CREATE TABLE `referensi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `topik_id` INT NOT NULL,
  `judul_link` VARCHAR(255) NOT NULL,
  `url` VARCHAR(500) NOT NULL,
  CONSTRAINT `fk_referensi_topik` FOREIGN KEY (`topik_id`) REFERENCES `topik` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 5. TABEL FILE BAHAN AJAR (file_materi)
-- Berkas modul / presentasi (PPT, PPTX, PDF)
-- --------------------------------------------------------
CREATE TABLE `file_materi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `topik_id` INT NOT NULL,
  `nama_file` VARCHAR(255) NOT NULL,
  `path_file` VARCHAR(255) NOT NULL,
  `tipe_file` VARCHAR(10) NOT NULL,
  `ukuran_file` INT NOT NULL,
  `download_count` INT NOT NULL DEFAULT 0,
  `uploaded_by` INT DEFAULT NULL,
  `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_file_topik` FOREIGN KEY (`topik_id`) REFERENCES `topik` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_file_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- DATA AWAL (SEED DATA)
-- Akun Login Bawaan:
-- 1. Admin: admin@sma5.sch.id / admin123
-- 2. Guru : guru@sma5.sch.id   / guru123
-- ==========================================================

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'Administrator MGMP', 'admin@sma5.sch.id', '$2y$10$T9DUaBiGA/.jagFl1xCX.ee9NQVTBsCpoKgwc4C2iq2zIuT2anVDa', 'admin', 'aktif', NOW()),
(2, 'Budi Prasetyo, S.Pd.', 'guru@sma5.sch.id', '$2y$10$Ae8mpdPcrsnO.YLMqGF8eOfGxoij69xPElymzuFqtVfG7WBMeknDS', 'guru', 'aktif', NOW()),
(3, 'Siti Nurhaliza, S.Pd.', 'siti.guru@sma5.sch.id', '$2y$10$Ae8mpdPcrsnO.YLMqGF8eOfGxoij69xPElymzuFqtVfG7WBMeknDS', 'guru', 'aktif', NOW());

-- --------------------------------------------------------
-- DATA KELAS (10, 11, 12)
-- --------------------------------------------------------
INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`, `deskripsi`) VALUES
(1, 'Kelas 10', 10, 'Fondasi dasar Informatika: Berpikir Komputasional, TIK, Sistem Komputer, Jaringan, dan Pemrograman Dasar.'),
(2, 'Kelas 11', 11, 'Pengembangan wawasan: Algoritma Lanjutan, Arsitektur Jaringan, Analisis Data, dan Dampak Sosial Informatika.'),
(3, 'Kelas 12', 12, 'Penerapan & Proyek: Pemrograman Web Praktis, Kecerdasan Artifisial (AI), dan Praktik Lintas Bidang.');

-- --------------------------------------------------------
-- DATA TOPIK INFORMATIKA (Kurikulum Merdeka)
-- --------------------------------------------------------
INSERT INTO `topik` (`id`, `kelas_id`, `judul_topik`, `elemen`, `deskripsi`, `urutan`, `created_by`, `created_at`) VALUES
-- Topik Kelas 10
(1, 1, 'Berpikir Komputasional (BK)', 'BK', 'Mengenal 4 pilar dasar Berpikir Komputasional (Dekomposisi, Pengenalan Pola, Abstraksi, dan Perancangan Algoritma) untuk memecahkan persoalan sehari-hari secara logis dan terstruktur tanpa memerlukan keahlian coding rumit.', 1, 1, NOW()),
(2, 1, 'Teknologi Informasi dan Komunikasi (TIK)', 'TIK', 'Pemanfaatan integrasi aplikasi perkantoran (pengolah kata, lembar kerja, presentasi), fitur Mail Merge untuk kemudahan administrasi guru, serta manajemen penyimpanan file Google Drive/Cloud secara aman.', 2, 1, NOW()),
(3, 1, 'Sistem Komputer (SK)', 'SK', 'Mengenal bagian utama perangkat komputer: perangkat keras (hardware seperti CPU, RAM, media simpan), perangkat lunak (software), interaksi antar perangkat, dan fungsi dasar sistem operasi.', 3, 1, NOW()),
(4, 1, 'Jaringan Komputer dan Internet (JKI)', 'JKI', 'Konsep jaringan lokal (LAN), internet, cara kerja Wi-Fi dan IP address, serta tips keamanan siber sederhana untuk melindungi data pribadi dan akun sekolah dari kejahatan siber.', 4, 1, NOW()),
(5, 1, 'Analisis Data (AD)', 'AD', 'Teknik mengumpulkan, mengelompokkan, mengolah, dan menampilkan data sederhana menggunakan aplikasi spreadsheet (Excel / Google Sheets) agar menjadi informasi yang mudah dipahami.', 5, 1, NOW()),
(6, 1, 'Algoritma dan Pemrograman (AP)', 'AP', 'Pengantar logika pemrograman komputer menggunakan diagram alur (flowchart), pseudocode sederhana, dan pengenalan blok visual interaktif seperti Blockly / Python dasar.', 6, 1, NOW()),

-- Topik Kelas 11
(7, 2, 'Berpikir Komputasional Tingkat Lanjut', 'BK', 'Eksplorasi struktur data Pohon (Tree) dan Graf (Graph) dalam kehidupan sehari-hari, seperti rute navigasi terdekat pada peta digital dan silsilah keluarga.', 1, 1, NOW()),
(8, 2, 'Strategi Algoritmik dan Pemecahan Masalah', 'AP', 'Mengenal strategi Greedy dan Divide & Conquer dengan analogi nyata (misal: mencari kembalian uang paling sedikit atau membagi tugas kelompok), mudah dipahami tanpa rumus rumit.', 2, 1, NOW()),
(9, 2, 'Arsitektur Jaringan dan Komunikasi Data', 'JKI', 'Memahami bagaimana pesan terkirim dari satu komputer ke komputer lain di dunia melalui model lapisan jaringan dan pengenalan protokol internet secara visual.', 3, 1, NOW()),
(10, 2, 'Dampak Sosial Informatika (DSI)', 'DSI', 'Diskusi etika digital, jejak digital, hak cipta karya digital (HAKI), lisensi terbuka (Creative Commons/Open Source), serta pemahaman dasar UU ITE bagi generasi muda.', 4, 1, NOW()),

-- Topik Kelas 12
(11, 3, 'Dasar Pemrograman Web Sederhana', 'AP', 'Mengenal struktur halaman web dengan HTML dan CSS dasar. Guru dan siswa dapat membuat halaman profil biodata sekolah secara mudah dan menyenangkan.', 1, 1, NOW()),
(12, 3, 'Pengantar Kecerdasan Artifisial (AI)', 'DSI', 'Memahami konsep dasar kecerdasan buatan, bagaimana AI mengenali gambar dan suara, tips membuat instruksi (prompt) yang baik, serta batasan etika penggunaan AI dalam pembelajaran.', 2, 1, NOW()),
(13, 3, 'Proyek Kolaborasi Lintas Bidang', 'PLB', 'Panduan membuat proyek karya digital berkelompok yang mengintegrasikan teknologi informatika untuk menyelesaikan masalah lingkungan atau kegiatan di sekolah.', 3, 1, NOW());

-- --------------------------------------------------------
-- DATA LINK REFERENSI PEMBELAJARAN
-- --------------------------------------------------------
INSERT INTO `referensi` (`id`, `topik_id`, `judul_link`, `url`) VALUES
-- Referensi Topik 1 (BK Kelas 10)
(1, 1, 'Video: Berpikir Komputasional Informatika SMA', 'https://www.youtube.com/watch?v=6UFH9roXFGQ'),
(2, 1, 'Video: Perkenalan Informatika Kelas X', 'https://www.youtube.com/watch?v=Bf1u_S6g38s'),
-- Referensi Topik 2 (TIK Kelas 10)
(3, 2, 'Video: Aplikasi Perkantoran (Office)', 'https://www.youtube.com/watch?v=hdSRzz7MZG8'),
(4, 2, 'Video: Tutorial Mail Merge di Microsoft Word', 'https://www.youtube.com/watch?v=do9ujnZLIC4'),
-- Referensi Topik 3 (SK Kelas 10)
(5, 3, 'Video: Sistem Komputer Kelas X', 'https://www.youtube.com/watch?v=Qd1_i9v4iGk'),
(6, 3, 'Video: Perangkat Keras Komputer (Hardware)', 'https://www.youtube.com/watch?v=w3DzHAKfC8Y'),
-- Referensi Topik 4 (JKI Kelas 10)
(7, 4, 'Video: Materi Jaringan Komputer Kelas 10', 'https://www.youtube.com/watch?v=ZhRvSpmtQjM'),
(8, 4, 'Video: Dasar Jaringan Komputer dari Nol', 'https://www.youtube.com/watch?v=2tWCf7AEe8c'),
-- Referensi Topik 5 (AD Kelas 10)
(9, 5, 'Video: Belajar Excel dari Nol (Analisis Data)', 'https://www.youtube.com/watch?v=6WgvzCU3TI8'),
(10, 5, 'Video: Excel Dasar Siap Kerja', 'https://www.youtube.com/watch?v=r9xxSvrLWwM'),
-- Referensi Topik 6 (AP Kelas 10)
(11, 6, 'Video: Belajar Python Dasar', 'https://www.youtube.com/watch?v=iA8lLwmtKQM'),
-- Referensi Topik 7 (BK Tingkat Lanjut Kelas 11)
(12, 7, 'Video: Struktur Data Tree dan Graph', 'https://www.youtube.com/watch?v=i2cYB_9R4jE'),
-- Referensi Topik 8 (Strategi Algoritmik Kelas 11)
(13, 8, 'Video: Strategi Algoritmik Kelas XI (Greedy & Divide Conquer)', 'https://www.youtube.com/watch?v=ytpY1Ozr7HA'),
-- Referensi Topik 9 (AJK Kelas 11)
(14, 9, 'Video: Jaringan Komputer dan Internet', 'https://www.youtube.com/watch?v=ACwCpGfU9kg'),
-- Referensi Topik 10 (DSI Kelas 11)
(15, 10, 'Video: Dampak Sosial Informatika (DSI)', 'https://www.youtube.com/watch?v=wMuIy8O6Hlw'),
-- Referensi Topik 11 (Web Kelas 12)
(16, 11, 'Video: Tutorial HTML Dasar', 'https://www.youtube.com/watch?v=hMDJyb7VkYw'),
(17, 11, 'Video: Tutorial CSS Dasar', 'https://www.youtube.com/watch?v=8FhmFuGLhaQ'),
-- Referensi Topik 12 (AI Kelas 12)
(18, 12, 'Video: Pengenalan Teachable Machine', 'https://www.youtube.com/watch?v=vPKNhB3msBQ'),
(19, 12, 'Video: Teachable Machine 2.0 (AI untuk Semua)', 'https://www.youtube.com/watch?v=T2qQGqZxkD0'),
-- Referensi Topik 13 (PLB Kelas 12)
(20, 13, 'Video: Praktik Lintas Bidang Informatika', 'https://www.youtube.com/watch?v=yWMQ541zCUo'),
(21, 13, 'Video: Contoh Proyek P5 Berekayasa & Berteknologi', 'https://www.youtube.com/watch?v=ISgaL92IYzI');

-- --------------------------------------------------------
-- DATA FILE BAHAN AJAR AWAL
-- Disimpan di folder uploads/materi/
-- --------------------------------------------------------
INSERT INTO `file_materi` (`id`, `topik_id`, `nama_file`, `path_file`, `tipe_file`, `ukuran_file`, `uploaded_by`, `uploaded_at`) VALUES
(1, 1, 'Modul_Ajar_Berpikir_Komputasional_Kelas10.pdf', 'uploads/materi/sample_modul_berpikir_komputasional.pdf', 'pdf', 542000, 1, NOW()),
(2, 1, 'Slide_Presentasi_Berpikir_Komputasional.pptx', 'uploads/materi/sample_slide_berpikir_komputasional.pptx', 'pptx', 1250000, 1, NOW()),
(3, 2, 'Panduan_Praktik_Integrasi_Office_MailMerge.pdf', 'uploads/materi/sample_integrasi_office.pdf', 'pdf', 430000, 1, NOW()),
(4, 3, 'Slide_Presentasi_Sistem_Komputer_Hardware.pptx', 'uploads/materi/sample_sistem_komputer.pptx', 'pptx', 1820000, 1, NOW()),
(5, 4, 'Bahan_Ajar_Jaringan_Komputer_Internet.pdf', 'uploads/materi/sample_jaringan_internet.pdf', 'pdf', 720000, 1, NOW()),
(6, 12, 'Panduan_Guru_Pengantar_Kecerdasan_Artifisial.pdf', 'uploads/materi/sample_pengantar_ai.pdf', 'pdf', 890000, 1, NOW());
