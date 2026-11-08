

<?php
/**
 * GET /api/unduh.php?id=1
 * Menyajikan berkas sebagai lampiran (attachment) dan menambah
 * download_count — mirror guru/unduh.php.
 */
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
require_api_user($pdo);

$file_id = (int)($_GET['id'] ?? 0);
if ($file_id <= 0) {
    json_err('Berkas tidak ditemukan.');
}

$stmt = $pdo->prepare("SELECT * FROM file_materi WHERE id = ? LIMIT 1");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    json_err('Data berkas materi tidak ditemukan.', 404);
}

// Cegah path traversal: berkas harus berada di dalam UPLOAD_DIR
$full_path = realpath(__DIR__ . '/../' . ltrim($file['path_file'], '/'));
$upload_root = realpath(UPLOAD_DIR);

if ($full_path === false || $upload_root === false
    || strpos($full_path, $upload_root . DIRECTORY_SEPARATOR) !== 0
    || !is_file($full_path)) {
    json_err('Berkas fisik materi belum tersedia di server.', 404);
}

// Tambah hitungan unduhan hanya setelah berkas dipastikan ada
$update = $pdo->prepare("UPDATE file_materi SET download_count = download_count + 1 WHERE id = ?");
$update->execute([$file_id]);

if (ob_get_level()) {
    ob_end_clean();
}

$filename = preg_replace('/[\r\n"]+/', '_', basename($file['nama_file']));
$mime_types = [
    'pdf'  => 'application/pdf',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
];
$ext = strtolower($file['tipe_file']);
$content_type = $mime_types[$ext] ?? 'application/octet-stream';

header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($full_path));

readfile($full_path);
exit;
