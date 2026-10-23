<?php
/**
 * Logout Pengguna
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Hapus seluruh data sesi
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Mulai sesi baru khusus untuk flash message
session_start();
set_flash('info', 'Anda telah berhasil keluar dari akun.');
header('Location: ' . base_url('auth/login.php'));
exit;
