<?php
/**
 * POST /api/logout.php
 * Mencabut token yang sedang dipakai (header Authorization wajib).
 */
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$user = require_api_user($pdo);

$hash = hash('sha256', api_raw_token());
$pdo->prepare("DELETE FROM api_tokens WHERE token_hash = ? AND user_id = ?")
    ->execute([$hash, $user['id']]);

json_out(['ok' => true, 'message' => 'Berhasil keluar dari akun.']);
