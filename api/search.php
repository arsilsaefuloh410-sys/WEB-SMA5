<?php
/**
 * GET /api/search.php?q=&elemen=&tingkat=
 * Pencarian topik (mirror guru/search.php).
 */
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
require_api_user($pdo);

$query = trim($_GET['q'] ?? '');
$filter_elemen = trim($_GET['elemen'] ?? '');
$filter_tingkat = trim($_GET['tingkat'] ?? '');
$hasil = [];

if ($query !== '' || $filter_elemen !== '' || $filter_tingkat !== '') {
    $sql = "
        SELECT t.*, k.nama_kelas, k.tingkat,
               (SELECT COUNT(*) FROM file_materi WHERE topik_id = t.id) as total_file,
               (SELECT COUNT(*) FROM referensi WHERE topik_id = t.id) as total_referensi
        FROM topik t
        JOIN kelas k ON t.kelas_id = k.id
        WHERE 1=1
    ";
    $params = [];

    if ($query !== '') {
        $search_term = '%' . $query . '%';
        $sql .= " AND (t.judul_topik LIKE ? OR t.deskripsi LIKE ? OR k.nama_kelas LIKE ?) ";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if ($filter_elemen !== '' && $filter_elemen !== 'ALL') {
        $sql .= " AND t.elemen = ? ";
        $params[] = $filter_elemen;
    }

    if ($filter_tingkat !== '' && $filter_tingkat !== 'ALL') {
        $sql .= " AND k.tingkat = ? ";
        $params[] = (int)$filter_tingkat;
    }

    $sql .= " ORDER BY k.tingkat ASC, t.urutan ASC ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll() as $row) {
        $hasil[] = [
            'id'              => (int)$row['id'],
            'judul_topik'     => $row['judul_topik'],
            'elemen'          => $row['elemen'],
            'deskripsi'       => $row['deskripsi'],
            'urutan'          => (int)$row['urutan'],
            'nama_kelas'      => $row['nama_kelas'],
            'tingkat'         => (int)$row['tingkat'],
            'total_file'      => (int)$row['total_file'],
            'total_referensi' => (int)$row['total_referensi'],
        ];
    }
}

json_out(['ok' => true, 'data' => ['hasil' => $hasil]]);
