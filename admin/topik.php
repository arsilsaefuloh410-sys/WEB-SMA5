<?php
/**
 * Kelola Daftar Topik / Bab Pembelajaran Informatika
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Administrator yang sudah login
require_admin();

$active_admin_menu = 'topik';
$page_title = 'Kelola Topik & Materi';

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    // 1. Tambah Topik
    if ($action === 'add') {
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);
        $judul_topik = trim($_POST['judul_topik'] ?? '');
        $elemen = trim($_POST['elemen'] ?? 'BK');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);
        $user_id = current_user()['id'] ?? 1;

        if (empty($judul_topik) || $kelas_id <= 0) {
            set_flash('danger', 'Mohon isi judul topik dan pilih jenjang kelas.');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO topik (kelas_id, judul_topik, elemen, deskripsi, urutan, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($stmt->execute([$kelas_id, $judul_topik, $elemen, $deskripsi, $urutan, $user_id])) {
                $newId = $pdo->lastInsertId();
                set_flash('success', 'Topik materi "<strong>' . e($judul_topik) . '</strong>" berhasil ditambahkan! Silakan lengkapi berkas dan link referensinya.');
                header('Location: ' . base_url('admin/topik_detail.php?id=' . $newId));
                exit;
            } else {
                set_flash('danger', 'Gagal menyimpan topik baru.');
            }
        }
        header('Location: ' . base_url('admin/topik.php'));
        exit;
    }

    // 2. Edit Topik
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $kelas_id = (int)($_POST['kelas_id'] ?? 0);
        $judul_topik = trim($_POST['judul_topik'] ?? '');
        $elemen = trim($_POST['elemen'] ?? 'BK');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $urutan = (int)($_POST['urutan'] ?? 1);

        if (empty($judul_topik) || $kelas_id <= 0) {
            set_flash('danger', 'Mohon isi judul topik dan pilih jenjang kelas.');
        } else {
            $stmt = $pdo->prepare("
                UPDATE topik 
                SET kelas_id = ?, judul_topik = ?, elemen = ?, deskripsi = ?, urutan = ? 
                WHERE id = ?
            ");
            $stmt->execute([$kelas_id, $judul_topik, $elemen, $deskripsi, $urutan, $id]);
            if ($stmt->rowCount() > 0) {
                set_flash('success', 'Perubahan topik berhasil disimpan.');
            } else {
                set_flash('warning', 'Tidak ada perubahan yang disimpan (data sama atau topik tidak ditemukan).');
            }
        }
        header('Location: ' . base_url('admin/topik.php'));
        exit;
    }

    // 3. Hapus Topik
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        
        // Hapus fisik berkas-berkas yang ada dalam topik ini terlebih dahulu
        $stmtFiles = $pdo->prepare("SELECT path_file FROM file_materi WHERE topik_id = ?");
        $stmtFiles->execute([$id]);
        $files = $stmtFiles->fetchAll();
        foreach ($files as $f) {
            delete_materi_file($f['path_file']);
        }

        $stmtDel = $pdo->prepare("DELETE FROM topik WHERE id = ?");
        $stmtDel->execute([$id]);

        if ($stmtDel->rowCount() > 0) {
            set_flash('success', 'Topik beserta seluruh berkas dan referensinya berhasil dihapus.');
        } else {
            set_flash('danger', 'Topik tidak ditemukan — mungkin sudah dihapus sebelumnya.');
        }
        header('Location: ' . base_url('admin/topik.php'));
        exit;
    }
}

// Filter Kelas
$filter_kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;

// Ambil data semua kelas untuk dropdown filter
$daftarKelas = $pdo->query("SELECT * FROM kelas ORDER BY tingkat ASC")->fetchAll();

// Query topik
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
$stmtTopik = $pdo->prepare($sql);
$stmtTopik->execute($params);
$daftarTopik = $stmtTopik->fetchAll();

// Usulan "Nomor Urut Bab" berikutnya — dihitung per kelas terpilih (bukan total semua kelas)
$urutanKelasId = $filter_kelas_id > 0 ? $filter_kelas_id : (int)($daftarKelas[0]['id'] ?? 0);
$defaultUrutan = 1;
if ($urutanKelasId > 0) {
    try {
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM topik WHERE kelas_id = ?");
        $cnt->execute([$urutanKelasId]);
        $defaultUrutan = ((int)$cnt->fetchColumn()) + 1;
    } catch (Exception $e) {
        $defaultUrutan = count($daftarTopik) + 1;
    }
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h3 class="fw-bold text-dark mb-0">Kelola Topik & Materi</h3>
        <p class="text-muted small mb-0">Kelola materi ajar Informatika, berkas modul/slide, dan link referensi</p>
    </div>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary btn-sm fw-semibold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#addTopikModal">
            <i class="bi bi-plus-circle"></i> Tambah Bab / Topik Baru
        </button>
    </div>
</div>

<!-- Toolbar Filter Kelas -->
<div class="card card-modern p-3 mb-4 bg-white">
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <span class="small fw-semibold text-muted">Saring Jenjang:</span>
            <a href="<?= base_url('admin/topik.php') ?>" class="btn btn-sm <?= $filter_kelas_id == 0 ? 'btn-primary' : 'btn-outline-secondary' ?>">
                Semua Kelas
            </a>
            <?php foreach ($daftarKelas as $kls): ?>
                <a href="<?= base_url('admin/topik.php?kelas_id=' . $kls['id']) ?>" class="btn btn-sm <?= $filter_kelas_id == $kls['id'] ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    <?= e($kls['nama_kelas']) ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="text-muted small">
            Menampilkan <strong><?= count($daftarTopik) ?></strong> topik
        </div>
    </div>
</div>

<!-- Tabel Daftar Topik -->
<div class="card card-modern bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th style="width: 70px;">Urutan</th>
                        <th style="width: 110px;">Jenjang</th>
                        <th style="width: 140px;">Elemen</th>
                        <th>Judul Topik & Penjelasan</th>
                        <th style="width: 130px;" class="text-center">Berkas Materi</th>
                        <th style="width: 110px;" class="text-center">Link Web</th>
                        <th style="width: 240px;" class="text-end">Aksi Pengelolaan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarTopik)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-folder-x display-5 d-block mb-2 text-secondary"></i>
                                Belum ada topik yang terdaftar pada filter ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $elemenOptions = get_elemen_list();
                        foreach ($daftarTopik as $topik): 
                            $el_info = get_elemen_info($topik['elemen'] ?? 'BK');
                        ?>
                        <tr>
                            <td>
                                <span class="badge bg-light text-dark border">Bab <?= $topik['urutan'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                    <?= e($topik['nama_kelas']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1 small d-inline-flex align-items-center gap-1" style="background: <?= $el_info['bg'] ?>; color: <?= $el_info['warna'] ?>; border: 1px solid <?= $el_info['warna'] ?>35;">
                                    <i class="bi <?= $el_info['icon'] ?>"></i> <?= e($el_info['kode']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-1"><?= e($topik['judul_topik']) ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 420px;">
                                    <?= e($topik['deskripsi']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('admin/topik_detail.php?id=' . $topik['id']) ?>" class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10 text-decoration-none px-2 py-1">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i> <?= $topik['total_file'] ?> Berkas
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="<?= base_url('admin/topik_detail.php?id=' . $topik['id']) ?>" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-10 text-decoration-none px-2 py-1">
                                    <i class="bi bi-link-45deg me-1"></i> <?= $topik['total_referensi'] ?> Link
                                </a>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= base_url('admin/topik_detail.php?id=' . $topik['id']) ?>" class="btn btn-primary fw-semibold" title="Kelola berkas dan referensi untuk topik ini">
                                        <i class="bi bi-folder2-open me-1"></i> Kelola Materi
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal<?= $topik['id'] ?>" title="Edit Topik">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" data-item-name="topik '<?= e($topik['judul_topik']) ?>'" onclick="submitDelete(<?= $topik['id'] ?>)" title="Hapus Topik">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Modal Edit Topik -->
                        <div class="modal fade" id="editModal<?= $topik['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form action="" method="POST">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $topik['id'] ?>">

                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Bab / Topik Pembelajaran</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label small fw-semibold">Judul Topik / Bab</label>
                                                    <input type="text" name="judul_topik" class="form-control" value="<?= e($topik['judul_topik']) ?>" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Jenjang Kelas</label>
                                                    <select name="kelas_id" class="form-select" required>
                                                        <?php foreach ($daftarKelas as $k): ?>
                                                            <option value="<?= $k['id'] ?>" <?= $k['id'] == $topik['kelas_id'] ? 'selected' : '' ?>>
                                                                <?= e($k['nama_kelas']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Elemen Kurikulum</label>
                                                    <select name="elemen" class="form-select" required>
                                                        <?php foreach ($elemenOptions as $kode => $item): ?>
                                                            <option value="<?= $kode ?>" <?= ($topik['elemen'] ?? 'BK') === $kode ? 'selected' : '' ?>>
                                                                <?= $kode ?> - <?= e($item['nama']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Nomor Urut Bab</label>
                                                    <input type="number" name="urutan" class="form-control" value="<?= $topik['urutan'] ?>" required min="1">
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label small fw-semibold">Deskripsi / Penjelasan Konsep Singkat</label>
                                                    <textarea name="deskripsi" class="form-control" rows="4" placeholder="Tuliskan gambaran materi yang ramah untuk guru non-IT..."><?= e($topik['deskripsi']) ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary fw-semibold">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Topik Baru -->
<div class="modal fade" id="addTopikModal" tabindex="-1" aria-labelledby="addTopikModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" method="POST">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addTopikModalLabel"><i class="bi bi-plus-circle text-primary me-2"></i>Tambah Bab / Topik Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Judul Topik / Bab</label>
                            <input type="text" name="judul_topik" class="form-control" placeholder="Contoh: Berpikir Komputasional atau Jaringan Internet" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Jenjang Kelas</label>
                            <select name="kelas_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($daftarKelas as $k): ?>
                                    <option value="<?= $k['id'] ?>" <?= $filter_kelas_id == $k['id'] ? 'selected' : '' ?>>
                                        <?= e($k['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Elemen Kurikulum</label>
                            <select name="elemen" class="form-select" required>
                                <?php 
                                $elemenOptions = get_elemen_list();
                                foreach ($elemenOptions as $kode => $item): 
                                ?>
                                    <option value="<?= $kode ?>">
                                        <?= $kode ?> - <?= e($item['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Nomor Urut Bab</label>
                            <input type="number" name="urutan" class="form-control" value="<?= $defaultUrutan ?>" required min="1">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Deskripsi / Penjelasan Konsep Topik</label>
                            <textarea name="deskripsi" class="form-control" rows="4" placeholder="Tuliskan penjelasan materi dengan bahasa sederhana yang mudah dimengerti guru non-IT..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary fw-semibold">Simpan & Lanjutkan Unggah Berkas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form tersembunyi untuk delete -->
<form id="deleteForm" action="" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteTopikId" value="">
</form>

<script>
function submitDelete(id) {
    if (confirm('Apakah Bapak/Ibu yakin ingin menghapus bab topik ini?\nSeluruh berkas bahan ajar dan referensi di dalamnya akan ikut terhapus.')) {
        document.getElementById('deleteTopikId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
