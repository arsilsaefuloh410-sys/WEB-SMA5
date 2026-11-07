<?php
/**
 * ==========================================================
 * Konfigurasi Aplikasi Bahan Ajar Informatika SMA
 * ==========================================================
 * Mengatur parameter database, sesi pengguna, serta konstanta aplikasi.
 */

// Mulai sesi jika belum aktif (dengan cookie terlindungi)
if (session_status() === PHP_SESSION_NONE) {
    // Tolak ID sesi yang dipasang klien (anti-fixation) — harus sebelum session_start
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

// Set zona waktu Indonesia Barat
date_default_timezone_set('Asia/Jakarta');

// Muat variabel dari berkas .env di root proyek (jika ada)
require_once __DIR__ . '/env.php';
$env = load_env(dirname(__DIR__) . '/.env');

// Konfigurasi Database (nilai default dipakai jika .env tidak ditemukan)
define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_USER', $env['DB_USER'] ?? 'root');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('DB_NAME', $env['DB_NAME'] ?? 'db_bahan_ajar_informatika');
define('DB_PORT', $env['DB_PORT'] ?? '3306');

// Nama dan Pengaturan Aplikasi
define('APP_NAME', 'Portal Informatika SMAN 5 Kota Ternate');
define('APP_SUBTITLE', 'Portal Pembelajaran & Sumber Belajar Guru Informatika');
define('APP_SCHOOL', 'SMAN 5 Kota Ternate');
define('APP_VERSION', '1.0.0');

// Mode debug (true = pesan kesalahan detail + link reset ditampilkan bila email gagal)
define('APP_DEBUG', filter_var($env['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

// Set true hanya bila aplikasi berada di belakang proxy/CDN tepercaya
// (Cloudflare, load balancer) agar lockout login membaca IP asli dari
// X-Forwarded-For. Selain itu biarkan false/absen.
define('TRUSTED_PROXY', filter_var($env['TRUSTED_PROXY'] ?? false, FILTER_VALIDATE_BOOLEAN));

// Pengaturan Email (SMTP). Kosongkan MAIL_USERNAME untuk memakai mail() server.
define('MAIL_HOST', $env['MAIL_HOST'] ?? 'smtp.gmail.com');
define('MAIL_PORT', $env['MAIL_PORT'] ?? '587');
define('MAIL_USERNAME', $env['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $env['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $env['MAIL_ENCRYPTION'] ?? 'tls');
define('MAIL_FROM_ADDRESS', $env['MAIL_FROM_ADDRESS'] ?? 'no-reply@sma5.sch.id');
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME'] ?? APP_NAME);

// Berlaku (dalam menit) untuk link reset kata sandi yang dikirim via email
define('PASSWORD_RESET_EXPIRY_MINUTES', 60);

// Berlaku (dalam menit) untuk tautan verifikasi email pendaftaran akun guru
define('REGISTRATION_VERIFY_EXPIRY_MINUTES', 30);

// Pengaturan Berkas Unggahan
define('MAX_FILE_SIZE', 20 * 1024 * 1024); // Maksimal 20 Megabytes
define('ALLOWED_EXTENSIONS', ['pdf', 'ppt', 'pptx']);
define('UPLOAD_DIR', __DIR__ . '/../uploads/materi/');

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
