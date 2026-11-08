<?php
/**
 * GET /api/topik.php?id=1
 *   → detail satu topik: data kelas + deskripsi + berkas + referensi + bab lain
 * GET /api/topik.php?kelas_id=1
 *   → daftar topik dalam satu kelas (dengan jumlah berkas/link)
 * (mirror guru/topik.php dan guru/kelas.php)
 */
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
require_api_user($pdo);

// --- Mode daftar (per kelas) ---
if (isset($_GET['kelas_id'])) {
    $kelas_id = (int)$_GET['kelas_id'];
    if ($kelas_id <= 0) {
        json_err('Kelas tidak valid.');
    }

    $stmt = $pdo->prepare("
        SELECT t.*,
               COUNT(DISTINCT f.id) as total_file,
               COUNT(DISTINCT r.id) as total_referensi
        FROM topik t
        LEFT JOIN file_materi f ON t.id = f.topik_id
        LEFT JOIN referensi r ON t.id = r.topik_id
        WHERE t.kelas_id = ?
        GROUP BY t.id
        ORDER BY t.urutan ASC, t.id ASC
    ");
    $stmt->execute([$kelas_id]);

    $daftar = [];
    foreach ($stmt->fetchAll() as $row) {
        $daftar[] = [
            'id'              => (int)$row['id'],
            'kelas_id'        => (int)$row['kelas_id'],
            'judul_topik'     => $row['judul_topik'],
            'elemen'          => $row['elemen'],
            'deskripsi'       => $row['deskripsi'],
            'urutan'          => (int)$row['urutan'],
            'total_file'      => (int)$row['total_file'],
            'total_referensi' => (int)$row['total_referensi'],
        ];
    }

    json_out(['ok' => true, 'data' => ['topik' => $daftar]]);
}

// --- Mode detail (satu topik) ---
$topik_id = (int)($_GET['id'] ?? 0);
if ($topik_id <= 0) {
    json_err('Topik materi tidak valid.');
}

$stmt = $pdo->prepare("
    SELECT t.*, k.nama_kelas, k.tingkat, u.nama as nama_pembuat
    FROM topik t
    JOIN kelas k ON t.kelas_id = k.id
    LEFT JOIN users u ON t.created_by = u.id
    WHERE t.id = ?
    LIMIT 1
");
$stmt->execute([$topik_id]);
$topik = $stmt->fetch();

if (!$topik) {
    json_err('Materi topik tidak ditemukan atau telah dihapus.', 404);
}

$stmtFile = $pdo->prepare("SELECT * FROM file_materi WHERE topik_id = ? ORDER BY id ASC");
$stmtFile->execute([$topik_id]);
$files = [];
foreach ($stmtFile->fetchAll() as $f) {
    $files[] = [
        'id'             => (int)$f['id'],
        'nama_file'      => $f['nama_file'],
        'tipe_file'      => $f['tipe_file'],
        'ukuran_file'    => (int)$f['ukuran_file'],
        'download_count' => (int)$f['download_count'],
        'uploaded_at'    => $f['uploaded_at'],
    ];
}

$stmtRef = $pdo->prepare("SELECT * FROM referensi WHERE topik_id = ? ORDER BY id ASC");
$stmtRef->execute([$topik_id]);
$refs = [];
foreach ($stmtRef->fetchAll() as $r) {
    $refs[] = [
        'id'         => (int)$r['id'],
        'judul_link' => $r['judul_link'],
        'url'        => $r['url'],
    ];
}

$stmtBabLain = $pdo->prepare("
    SELECT id, judul_topik, urutan
    FROM topik
    WHERE kelas_id = ? AND id != ?
    ORDER BY urutan ASC, id ASC
");
$stmtBabLain->execute([$topik['kelas_id'], $topik_id]);
$bab_lain = [];
foreach ($stmtBabLain->fetchAll() as $bl) {
    $bab_lain[] = [
        'id'          => (int)$bl['id'],
        'judul_topik' => $bl['judul_topik'],
        'urutan'      => (int)$bl['urutan'],
    ];
}

json_out(['ok' => true, 'data' => [
    'topik' => [
        'id'            => (int)$topik['id'],
        'kelas_id'      => (int)$topik['kelas_id'],
        'nama_kelas'    => $topik['nama_kelas'],
        'tingkat'       => (int)$topik['tingkat'],
        'judul_topik'   => $topik['judul_topik'],
        'elemen'        => $topik['elemen'],
        'deskripsi'     => $topik['deskripsi'],
        'urutan'        => (int)$topik['urutan'],
        'created_at'    => $topik['created_at'],
        'nama_pembuat'  => $topik['nama_pembuat'],
    ],
    'files'    => $files,
    'referensi'=> $refs,
    'bab_lain' => $bab_lain,
]]);
