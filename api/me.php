<?php
/**
 * GET /api/me.php
 * Mengembalikan data pengguna yang sedang login (validasi token).
 */
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$user = require_api_user($pdo);

json_out(['ok' => true, 'data' => ['user' => [
    'id'    => (int)$user['id'],
    'nama'  => $user['nama'],
    'email' => $user['email'],
    'role'  => $user['role'],
    'status'=> $user['status'],
]]]);
