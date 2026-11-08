<?php
/**
 * POST /api/admin/berkas.php
 * Multipart (upload) : action=upload_file, topik_id, file_berkas
 * JSON               : { action: "delete_file", file_id, topik_id }
 * (mirror logika admin/topik_detail.php)
 */
require_once __DIR__ . '/../bootstrap.php';

$user = require_api_admin($pdo);
require_method('POST');

$input = api_input();
$action = $input['action'] ?? 'upload_file';
$topik_id = (int)($input['topik_id'] ?? 0);

if ($topik_id <= 0) {
    json_err('Topik tidak valid.');
}

// Pastikan topik ada
$stmt_topik = $pdo->prepare("SELECT id FROM topik WHERE id = ? LIMIT 1");
$stmt_topik->execute([$topik_id]);
if (!$stmt_topik->fetch()) {
    json_err('Topik materi tidak ditemukan.', 404);
}

// --- 1. Unggah berkas (pdf/ppt/pptx, maks 20 MB) ---
if ($action === 'upload_file') {
    if (!isset($_FILES['file_berkas']) || $_FILES['file_berkas']['error'] === UPLOAD_ERR_NO_FILE) {
        json_err('Silakan pilih berkas PPT atau PDF yang ingin diunggah.');
    }

    $upload_result = upload_materi_file($_FILES['file_berkas']);
    if (!$upload_result['status']) {
        json_err($upload_result['message']);
    }

    $file_data = $upload_result['data'];
    $stmt_insert = $pdo->prepare("
        INSERT INTO file_materi (topik_id, nama_file, path_file, tipe_file, ukuran_file, uploaded_by, uploaded_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt_insert->execute([
        $topik_id,
        $file_data['nama_asli'],
        $file_data['path_file'],
        $file_data['tipe_file'],
        $file_data['ukuran_file'],
        $user['id'],
    ]);

    json_out(['ok' => true, 'message' => 'Berkas "' . $file_data['nama_asli'] . '" berhasil diunggah.', 'data' => [
        'id' => (int)$pdo->lastInsertId(),
    ]]);
}

// --- 2. Hapus berkas ---
if ($action === 'delete_file') {
    $file_id = (int)($input['file_id'] ?? 0);
    if ($file_id <= 0) {
        json_err('Berkas tidak valid.');
    }

    $stmt_f = $pdo->prepare("SELECT * FROM file_materi WHERE id = ? AND topik_id = ?");
    $stmt_f->execute([$file_id, $topik_id]);
    $target = $stmt_f->fetch();

    if (!$target) {
        json_err('Berkas tidak ditemukan.', 404);
    }

    delete_materi_file($target['path_file']);

    $stmt_del = $pdo->prepare("DELETE FROM file_materi WHERE id = ?");
    $stmt_del->execute([$file_id]);

    json_out(['ok' => true, 'message' => 'Berkas berhasil dihapus.']);
}

json_err('Aksi tidak dikenali.');
