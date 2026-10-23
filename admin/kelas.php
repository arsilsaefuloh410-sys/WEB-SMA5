<?php
/**
 * Kelola Jenjang Kelas (Admin)
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Administrator yang sudah login
require_admin();

$active_admin_menu = 'kelas';
$page_title = 'Kelola Jenjang Kelas';

// Handle POST: Update deskripsi kelas
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'edit') {
        $id = (int)$_POST['id'];
        $nama_kelas = trim($_POST['nama_kelas'] ?? '');
        $tingkat = (int)($_POST['tingkat'] ?? 10);
        $deskripsi = trim($_POST['deskripsi'] ?? '');

        if (!empty($nama_kelas)) {
            $stmt = $pdo->prepare("UPDATE kelas SET nama_kelas = ?, tingkat = ?, deskripsi = ? WHERE id = ?");
            $stmt->execute([$nama_kelas, $tingkat, $deskripsi, $id]);
            set_flash('success', 'Data ' . e($nama_kelas) . ' berhasil diperbarui.');
        } else {
            set_flash('danger', 'Nama kelas tidak boleh kosong.');
        }
        header('Location: ' . base_url('admin/kelas.php'));
        exit;
    }
}

// Ambil data semua kelas beserta jumlah topiknya
$stmt = $pdo->query("
    SELECT k.*, COUNT(t.id) as total_topik 
    FROM kelas k 
    LEFT JOIN topik t ON k.id = t.kelas_id 
    GROUP BY k.id 
    ORDER BY k.tingkat ASC
");
$daftarKelas = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-3 border-bottom">
    <div>
        <h3 class="fw-bold text-dark mb-0">Kelola Jenjang Kelas</h3>
        <p class="text-muted small mb-0">Pengaturan tingkat kelas (Kelas 10, 11, 12) dan deskripsi kurikulum fase.</p>
    </div>
</div>

<div class="card card-modern bg-white">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th style="width: 80px;">Tingkat</th>
                        <th style="width: 180px;">Nama Kelas</th>
                        <th>Deskripsi / Fase Kurikulum</th>
                        <th style="width: 140px;" class="text-center">Jumlah Topik</th>
                        <th style="width: 180px;" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daftarKelas as $kls): ?>
                    <tr>
                        <td>
                            <span class="badge bg-secondary fs-6"><?= $kls['tingkat'] ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?= e($kls['nama_kelas']) ?></div>
                            <small class="text-muted">Fase <?= $kls['tingkat'] == 10 ? 'E' : 'F' ?> SMA</small>
                        </td>
                        <td>
                            <div class="text-muted small"><?= e($kls['deskripsi'] ?? '-') ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10 px-3 py-2">
                                <?= $kls['total_topik'] ?> Topik
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $kls['id'] ?>">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </button>
                            <a href="<?= base_url('admin/topik.php?kelas_id=' . $kls['id']) ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-list-task me-1"></i> Topik
                            </a>
                        </td>
                    </tr>

                    <!-- Modal Edit Kelas -->
                    <div class="modal fade" id="editModal<?= $kls['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $kls['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="id" value="<?= $kls['id'] ?>">

                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold" id="editModalLabel<?= $kls['id'] ?>">Edit <?= e($kls['nama_kelas']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Nama Kelas</label>
                                            <input type="text" name="nama_kelas" class="form-control" value="<?= e($kls['nama_kelas']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Tingkat Angka</label>
                                            <input type="number" name="tingkat" class="form-control" value="<?= e($kls['tingkat']) ?>" required min="10" max="12">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Deskripsi / Penjelasan Singkat</label>
                                            <textarea name="deskripsi" class="form-control" rows="3"><?= e($kls['deskripsi']) ?></textarea>
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
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
