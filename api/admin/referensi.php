<?php
/**
 * POST /api/admin/referensi.php
 * Body JSON: { action: add|delete, topik_id, judul_link, url / ref_id }
 * (mirror logika admin/topik_detail.php)
 */
require_once __DIR__ . '/../bootstrap.php';

require_api_admin($pdo);
require_method('POST');

$input = api_input();
$action = $input['action'] ?? '';
$topik_id = (int)($input['topik_id'] ?? 0);

if ($topik_id <= 0) {
    json_err('Topik tidak valid.');
}

if ($action === 'add') {
    $judul_link = trim($input['judul_link'] ?? '');
    $url = trim($input['url'] ?? '');

    if ($judul_link === '' || $url === '') {
        json_err('Mohon isi judul link dan alamat URL referensi.');
    }

    // Pastikan diawali http/https
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = 'https://' . $url;
    }

    $stmt = $pdo->prepare("INSERT INTO referensi (topik_id, judul_link, url) VALUES (?, ?, ?)");
    $stmt->execute([$topik_id, $judul_link, $url]);

    json_out(['ok' => true, 'message' => 'Tautan referensi berhasil ditambahkan.', 'data' => [
        'id' => (int)$pdo->lastInsertId(),
    ]]);
}

if ($action === 'delete') {
    $ref_id = (int)($input['ref_id'] ?? 0);
    if ($ref_id <= 0) {
        json_err('Tautan referensi tidak valid.');
    }

    $stmt = $pdo->prepare("DELETE FROM referensi WHERE id = ? AND topik_id = ?");
    $stmt->execute([$ref_id, $topik_id]);

    if ($stmt->rowCount() > 0) {
        json_out(['ok' => true, 'message' => 'Tautan referensi berhasil dihapus.']);
    }
    json_err('Tautan referensi tidak ditemukan.', 404);
}

json_err('Aksi tidak dikenali.');
