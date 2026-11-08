<?php
/**
 * GET /api/kelas.php
 * Daftar jenjang kelas + jumlah bab/berkas/link (mirror guru/index.php).
 */
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
require_api_user($pdo);

$stmt = $pdo->query("
    SELECT k.*,
           COUNT(DISTINCT t.id) as total_topik,
           COUNT(DISTINCT f.id) as total_file,
           COUNT(DISTINCT r.id) as total_referensi
    FROM kelas k
    LEFT JOIN topik t ON k.id = t.kelas_id
    LEFT JOIN file_materi f ON t.id = f.topik_id
    LEFT JOIN referensi r ON t.id = r.topik_id
    GROUP BY k.id
    ORDER BY k.tingkat ASC
");

$daftar = [];
foreach ($stmt->fetchAll() as $row) {
    $daftar[] = [
        'id'              => (int)$row['id'],
        'nama_kelas'      => $row['nama_kelas'],
        'tingkat'         => (int)$row['tingkat'],
        'deskripsi'       => $row['deskripsi'],
        'total_topik'     => (int)$row['total_topik'],
        'total_file'      => (int)$row['total_file'],
        'total_referensi' => (int)$row['total_referensi'],
    ];
}

json_out(['ok' => true, 'data' => ['kelas' => $daftar]]);
