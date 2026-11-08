<?php
/**
 * GET  /api/admin/topik.php?kelas_id=  → daftar topik (+jumlah berkas/link)
 * POST /api/admin/topik.php            → action: add | edit | delete
 * (mirror logika admin/topik.php)
 */
require_once __DIR__ . '/../bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_api_admin($pdo);

    $filter_kelas_id = (int)($_GET['kelas_id'] ?? 0);

    $daftar_kelas = [];
    foreach ($pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC")->fetchAll() as $k) {
        $daftar_kelas[] = [
            'id'         => (int)$k['id'],
            'nama_kelas' => $k['nama_kelas'],
            'tingkat'    => (int)$k['tingkat'],
        ];
    }

    $sql = "
        SELECT t.*, k.nama_kelas, k.tingkat,
               (SELECT COUNT(*) FROM file_materi WHERE topik_id = t.id) as total_file,
               (SELECT COUNT(*) FROM referensi WHERE topik_id = t.id) as total_referensi
        FROM topik t
        JOIN kelas k ON t.kelas_id = k.id
    ";
    $params = [];
    if ($filter_kelas_id > 0) {
        $sql .= " WHERE t.kelas_id = ? ";
        $params[] = $filter_kelas_id;
    }
    $sql .= " ORDER BY k.tingkat ASC, t.urutan ASC, t.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $daftar = [];
    foreach ($stmt->fetchAll() as $row) {
        $daftar[] = [
            'id'              => (int)$row['id'],
            'kelas_id'        => (int)$row['kelas_id'],
            'nama_kelas'      => $row['nama_kelas'],
            'tingkat'         => (int)$row['tingkat'],
            'judul_topik'     => $row['judul_topik'],
            'elemen'          => $row['elemen'],
            'deskripsi'       => $row['deskripsi'],
            'urutan'          => (int)$row['urutan'],
            'total_file'      => (int)$row['total_file'],
            'total_referensi' => (int)$row['total_referensi'],
        ];
    }

    // Usulan nomor urut bab berikutnya (per kelas terpilih)
    $urutan_kelas_id = $filter_kelas_id > 0 ? $filter_kelas_id : (int)($daftar_kelas[0]['id'] ?? 0);
    $default_urutan = 1;
    if ($urutan_kelas_id > 0) {
        try {
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM topik WHERE kelas_id = ?");
            $cnt->execute([$urutan_kelas_id]);
            $default_urutan = ((int)$cnt->fetchColumn()) + 1;
        } catch (Exception $e) {
            $default_urutan = count($daftar) + 1;
        }
    }

    json_out(['ok' => true, 'data' => [
        'kelas'          => $daftar_kelas,
        'topik'          => $daftar,
        'default_urutan' => $default_urutan,
    ]]);
}

// --- POST: add / edit / delete ---
$user = require_api_admin($pdo);
require_method('POST');

$input = api_input();
$action = $input['action'] ?? '';

if ($action === 'add') {
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    $judul_topik = trim($input['judul_topik'] ?? '');
    $elemen = trim($input['elemen'] ?? 'BK');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $urutan = (int)($input['urutan'] ?? 1);
    $user_id = $user['id'] ?? 1;

    if ($judul_topik === '' || $kelas_id <= 0) {
        json_err('Mohon isi judul topik dan pilih jenjang kelas.');
    }

    $stmt = $pdo->prepare("
        INSERT INTO topik (kelas_id, judul_topik, elemen, deskripsi, urutan, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$kelas_id, $judul_topik, $elemen, $deskripsi, $urutan, $user_id]);

    json_out(['ok' => true, 'message' => 'Topik materi berhasil ditambahkan.', 'data' => [
        'id' => (int)$pdo->lastInsertId(),
    ]]);
}

if ($action === 'edit') {
    $id = (int)($input['id'] ?? 0);
    $kelas_id = (int)($input['kelas_id'] ?? 0);
    $judul_topik = trim($input['judul_topik'] ?? '');
    $elemen = trim($input['elemen'] ?? 'BK');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $urutan = (int)($input['urutan'] ?? 1);

    if ($judul_topik === '' || $kelas_id <= 0 || $id <= 0) {
        json_err('Mohon isi judul topik dan pilih jenjang kelas.');
    }

    $stmt = $pdo->prepare("
        UPDATE topik
        SET kelas_id = ?, judul_topik = ?, elemen = ?, deskripsi = ?, urutan = ?
        WHERE id = ?
    ");
    $stmt->execute([$kelas_id, $judul_topik, $elemen, $deskripsi, $urutan, $id]);

    json_out(['ok' => true, 'message' => 'Perubahan topik berhasil disimpan.']);
}

if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        json_err('Topik tidak valid.');
    }

    // Hapus fisik berkas topik terlebih dahulu (DB cascade menghapus sisanya)
    $stmt_files = $pdo->prepare("SELECT path_file FROM file_materi WHERE topik_id = ?");
    $stmt_files->execute([$id]);
    foreach ($stmt_files->fetchAll() as $f) {
        delete_materi_file($f['path_file']);
    }

    $stmt_del = $pdo->prepare("DELETE FROM topik WHERE id = ?");
    $stmt_del->execute([$id]);

    if ($stmt_del->rowCount() > 0) {
        json_out(['ok' => true, 'message' => 'Topik beserta seluruh berkas dan referensinya berhasil dihapus.']);
    }
    json_err('Topik tidak ditemukan — mungkin sudah dihapus sebelumnya.', 404);
}

json_err('Aksi tidak dikenali.');
