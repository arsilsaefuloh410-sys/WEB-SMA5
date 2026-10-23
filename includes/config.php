<?php
/**
 * ==========================================================
 * Konfigurasi Aplikasi Bahan Ajar Informatika SMA
 * ==========================================================
 * Mengatur parameter database, sesi pengguna, serta konstanta aplikasi.
 */

// Mulai sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set zona waktu Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi Database MySQL (Default XAMPP)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_bahan_ajar_informatika');
define('DB_PORT', 3306);

// Nama dan Pengaturan Aplikasi
define('APP_NAME', 'Portal Informatika SMAN 5 Kota Ternate');
define('APP_SUBTITLE', 'Portal Pembelajaran & Sumber Belajar Guru Informatika');
define('APP_SCHOOL', 'SMAN 5 Kota Ternate');
define('APP_VERSION', '1.0.0');

// Pengaturan Berkas Unggahan
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // Maksimal 20 Megabytes
define('ALLOWED_EXTENSIONS', ['pdf', 'ppt', 'pptx']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/materi/');

/**
 * Mendeteksi base path web secara otomatis
 * Mendukung akses langsung (http://localhost:8000)
 * maupun via folder htdocs XAMPP (http://localhost/folder-anda/)
 */
/**
 * Mendeteksi base path web secara otomatis
 * Mendukung akses langsung (http://localhost:8000)
 * maupun via folder htdocs XAMPP (http://localhost/folder-anda/)
 */
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$dirPath = str_replace('\\', '/', dirname($scriptName));
$rootPath = preg_replace('~/(admin|guru|auth|includes)(/.*)?$~', '', $dirPath);
$rootPath = trim($rootPath, '/\\');

if ($rootPath === '' || $rootPath === '.') {
    define('BASE_PATH', '');
} else {
    define('BASE_PATH', '/' . $rootPath);
}

/**
 * Fungsi pembantu untuk membuat URL yang selalu valid
 * @param string $path Jalur relatif yang diinginkan
 * @return string URL relatif lengkap
 */
function base_url($path = '') {
    $p = ltrim(str_replace('\\', '/', $path), '/');
    if (BASE_PATH === '') {
        return '/' . $p;
    }
    return BASE_PATH . '/' . $p;
}
