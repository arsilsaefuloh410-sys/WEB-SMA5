<?php
/**
 * Handler Pratinjau (inline) Berkas Materi — untuk iframe "Baca Modul"
 * Menyajikan berkas PDF secara inline setelah verifikasi login & validasi path.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib login — pratinjau tidak boleh terbuka untuk anonim
require_login();

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($file_id <= 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Berkas tidak ditemukan.');
}

$stmt = $pdo->prepare("SELECT * FROM file_materi WHERE id = ? LIMIT 1");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    header('HTTP/1.1 404 Not Found');
    exit('Data berkas materi tidak ditemukan.');
}

// Hanya PDF yang mendukung pratinjau inline
if (strtolower($file['tipe_file']) !== 'pdf') {
    header('HTTP/1.1 415 Unsupported Media Type');
    exit('Pratinjau hanya tersedia untuk berkas PDF.');
}

// Lokasi fisik — pastikan berada di dalam UPLOAD_DIR (cegah path traversal)
$full_path = realpath(__DIR__ . '/../' . ltrim($file['path_file'], '/'));
$upload_root = realpath(UPLOAD_DIR);

if ($full_path === false || $upload_root === false
    || strpos($full_path, $upload_root . DIRECTORY_SEPARATOR) !== 0
    || !is_file($full_path)) {
    header('HTTP/1.1 404 Not Found');
    exit('Berkas fisik materi belum tersedia di server.');
}

// Bersihkan buffer keluaran sebelum streaming
if (ob_get_level()) {
    ob_end_clean();
}

$filename = preg_replace('/[\r\n"]+/', '_', basename($file['nama_file']));

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($full_path));

readfile($full_path);
exit;
