<?php
/**
 * Handler Unduh Berkas Bahan Ajar
 * Menghitung counter unduhan secara otomatis dan menyajikan berkas
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib login
require_login();

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($file_id <= 0) {
    set_flash('danger', 'Berkas tidak ditemukan.');
    header('Location: ' . base_url('guru/index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM file_materi WHERE id = ? LIMIT 1");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    set_flash('danger', 'Data berkas materi tidak ditemukan.');
    header('Location: ' . base_url('guru/index.php'));
    exit;
}

// Lokasi fisik berkas — pastikan berada di dalam UPLOAD_DIR (cegah path traversal)
$full_path = realpath(__DIR__ . '/../' . ltrim($file['path_file'], '/'));
$upload_root = realpath(UPLOAD_DIR);

if ($full_path === false || $upload_root === false
    || !str_starts_with($full_path, $upload_root . DIRECTORY_SEPARATOR)
    || !is_file($full_path)) {
    set_flash('warning', 'Berkas fisik materi belum tersedia di server.');
    header('Location: ' . base_url('guru/topik.php?id=' . $file['topik_id']));
    exit;
}

// Update jumlah unduhan — hanya setelah berkas dipastikan ada
$updateStmt = $pdo->prepare("UPDATE file_materi SET download_count = download_count + 1 WHERE id = ?");
$updateStmt->execute([$file_id]);

// Bersihkan buffer keluaran sebelum streaming berkas
if (ob_get_level()) {
    ob_end_clean();
}

$filename = preg_replace('/[\r\n"]+/', '_', basename($file['nama_file']));
$mime_types = [
    'pdf' => 'application/pdf',
    'ppt' => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];
$ext = strtolower($file['tipe_file']);
$content_type = $mime_types[$ext] ?? 'application/octet-stream';

header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($full_path));

readfile($full_path);
exit;
