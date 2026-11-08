<?php
/**
 * POST /api/login.php
 * Body JSON: { email, password, role }
 * Berhasil: { ok, data: { token, user } }
 */
require_once __DIR__ . '/bootstrap.php';

require_method('POST');

$input = api_input();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$role = ($input['role'] ?? 'guru') === 'admin' ? 'admin' : 'guru';

if ($email === '' || $password === '') {
    json_err('Silakan masukkan email dan kata sandi Anda.');
}

// Proteksi brute-force (sama dengan halaman login web)
$lock_seconds = get_login_lockout_seconds($pdo, $email);
if ($lock_seconds > 0) {
    $mins = (int)ceil($lock_seconds / 60);
    json_err('Terlalu banyak percobaan login gagal. Akun dikunci sementara selama ±' . $mins . ' menit. Silakan coba lagi nanti.', 429);
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    record_login_attempt($pdo, $email);
    json_err('Email atau kata sandi tidak sesuai.', 401);
}

if ($user['status'] === 'pending') {
    json_err('Akun Anda masih menunggu persetujuan administrator. Silakan hubungi administrator sekolah.', 403);
}

if ($user['role'] !== $role) {
    $label = $user['role'] === 'admin' ? 'Administrator' : 'Guru Pengajar';
    json_err('Akun ini terdaftar sebagai ' . $label . '. Silakan pilih ' . $label . '.', 403);
}

clear_login_attempts($pdo, $email);

// Buat token Bearer (hash SHA-256 yang disimpan di database)
$raw_token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $raw_token);
$expires_at = date('Y-m-d H:i:s', time() + 30 * 24 * 3600); // 30 hari

try {
    // Bersihkan token kedaluwarsa milik pengguna ini
    $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ? AND expires_at < NOW()")->execute([$user['id']]);
    $pdo->prepare("INSERT INTO api_tokens (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, NOW())")
        ->execute([$user['id'], $token_hash, $expires_at]);
} catch (Exception $e) {
    json_err('Gagal membuat sesi login. Pastikan migrasi add_api_tokens.sql sudah dijalankan.', 500);
}

json_out([
    'ok'   => true,
    'data' => [
        'token' => $raw_token,
        'user'  => [
            'id'    => (int)$user['id'],
            'nama'  => $user['nama'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ],
    ],
]);
