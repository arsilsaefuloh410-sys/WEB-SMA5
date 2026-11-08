<?php
/**
 * ==========================================================
 * Bootstrap API (JSON) untuk Aplikasi Flutter
 * ==========================================================
 * Menyiapkan koneksi DB, helper JSON, serta autentikasi Bearer token.
 * Halaman ini tidak menghasilkan HTML sama sekali.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Apache sering tidak menyalakan header Authorization ke $_SERVER.
// Salin manual dari apache_request_headers() bila belum ada.
if (empty($_SERVER['HTTP_AUTHORIZATION']) && function_exists('apache_request_headers')) {
    foreach (apache_request_headers() as $header_name => $header_value) {
        if (strcasecmp($header_name, 'Authorization') === 0) {
            $_SERVER['HTTP_AUTHORIZATION'] = $header_value;
            break;
        }
    }
}

/**
 * Mengirim respons JSON lalu menghentikan eksekusi
 * @param array $data Objek yang akan diserialisasi
 * @param int $code HTTP status code
 */
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Mengirim respons kesalahan JSON
 */
function json_err(string $message, int $code = 400): void {
    json_out(['ok' => false, 'message' => $message], $code);
}

/**
 * Membaca input request: JSON body bila Content-Type application/json,
 * selain itu pakai $_POST (untuk multipart/form-data).
 */
function api_input(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
    return $_POST;
}

/**
 * Mengambil token mentah dari header Authorization: Bearer <token>
 */
function api_raw_token(): string {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    return '';
}

/**
 * Memvalidasi token Bearer dan mengembalikan data pengguna aktif.
 * Mengembalikan null bila token tidak valid/kedaluwarsa/akun nonaktif.
 */
function api_user(PDO $pdo): ?array {
    $raw = api_raw_token();
    if ($raw === '') {
        return null;
    }
    $hash = hash('sha256', $raw);
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.nama, u.email, u.role, u.status
            FROM api_tokens t
            JOIN users u ON u.id = t.user_id
            WHERE t.token_hash = ? AND t.expires_at > NOW() AND u.status = 'aktif'
            LIMIT 1
        ");
        $stmt->execute([$hash]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Wajib: pengguna login yang valid. Menghentikan request dengan 401 bila tidak.
 */
function require_api_user(PDO $pdo): array {
    $user = api_user($pdo);
    if (!$user) {
        json_err('Sesi tidak valid atau akun tidak aktif. Silakan login kembali.', 401);
    }
    return $user;
}

/**
 * Wajib: pengguna dengan peran admin. Menghentikan request dengan 403 bila bukan admin.
 */
function require_api_admin(PDO $pdo): array {
    $user = require_api_user($pdo);
    if ($user['role'] !== 'admin') {
        json_err('Akses ditolak. Fitur ini khusus untuk Administrator.', 403);
    }
    return $user;
}

/**
 * Menolak request yang bukan metode HTTP tertentu
 */
function require_method(string $method): void {
    if (strtoupper($_SERVER['REQUEST_METHOD']) !== strtoupper($method)) {
        json_err('Metode request tidak diizinkan.', 405);
    }
}
