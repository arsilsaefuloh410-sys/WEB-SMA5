<?php
/**
 * Dashboard Administrator MGMP Informatika SMA
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib Administrator yang sudah login
require_admin();

$active_admin_menu = 'dashboard';
$page_title = 'Dashboard Pengelola';

// Ambil statistik ringkasan
$stats = get_app_stats($pdo);

// Ambil daftar bab/topik terbaru
$stmtTopik = $pdo->query("
    SELECT t.*, k.nama_kelas,
           (SELECT COUNT(*) FROM file_materi WHERE topik_id = t.id) as total_file,
           (SELECT COUNT(*) FROM referensi WHERE topik_id = t.id) as total_referensi
    FROM topik t
    JOIN kelas k ON t.kelas_id = k.id
    ORDER BY t.id DESC
    LIMIT 6
");
$topikTerbaru = $stmtTopik->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Hero Sambutan Dashboard (ala situs sekolah) -->
<div class="welcome-hero mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
        <div>
            <div class="d-flex align-items-center gap-3">
                <div class="brand-icon-box flex-shrink-0" style="width: 52px; height: 52px; font-size: 1.4rem; background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.28); box-shadow: none;">
                    <i class="bi bi-mortarboard-fill text-white"></i>
                </div>
                <div class="lh-1">
                    <span class="d-block welcome-school-name">SMA NEGERI 5</span>
                    <span class="d-block welcome-school-city">KOTA TERNATE</span>
                </div>
            </div>
            <h1 class="welcome-hero-title mb-2 mt-4">Selamat Datang</h1>
            <p class="welcome-hero-sub mb-0">
                <strong><?= e($current_user['nama']) ?></strong>
            </p>
        </div>
        <a href="<?= base_url('admin/topik.php?action=tambah') ?>" class="btn btn-sm px-3 py-2 welcome-hero-btn">
            <i class="bi bi-plus-circle me-1"></i> Tambah Topik Baru
        </a>
    </div>
</div>

<!-- Kartu Statistik Cepat -->
<div class="row g-3 mb-4">
    <!-- Total Topik -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-box anim-reveal" style="--d: 0s;">
            <div class="stat-icon-wrapper bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-journal-text"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark counter-value" data-count="<?= (int) $stats['total_topik'] ?>"><?= $stats['total_topik'] ?></div>
                <div class="text-muted small">Total Bab / Topik</div>
            </div>
        </div>
    </div>

    <!-- Total Berkas Bahan Ajar -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-box anim-reveal" style="--d: 0.1s;">
            <div class="stat-icon-wrapper bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-file-earmark-arrow-up"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark counter-value" data-count="<?= (int) $stats['total_file'] ?>"><?= $stats['total_file'] ?></div>
                <div class="text-muted small">Berkas (PPT/PDF)</div>
            </div>
        </div>
    </div>

    <!-- Total Link Referensi -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-box anim-reveal" style="--d: 0.2s;">
            <div class="stat-icon-wrapper bg-info bg-opacity-10 text-info">
                <i class="bi bi-link-45deg"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark counter-value" data-count="<?= (int) $stats['total_referensi'] ?>"><?= $stats['total_referensi'] ?></div>
                <div class="text-muted small">Tautan Referensi</div>
            </div>
        </div>
    </div>

    <!-- Total Akun Guru -->
    <div class="col-xl-3 col-sm-6">
        <div class="stat-box anim-reveal" style="--d: 0.3s;">
            <div class="stat-icon-wrapper bg-success bg-opacity-10 text-success">
                <i class="bi bi-people"></i>
            </div>
            <div>
                <div class="fs-4 fw-bold text-dark counter-value" data-count="<?= (int) $stats['total_guru'] ?>"><?= $stats['total_guru'] ?></div>
                <div class="text-muted small">
                    Guru Terdaftar
                    <?php if ($stats['guru_pending'] > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?= $stats['guru_pending'] ?> baru</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pemberitahuan jika ada akun guru yang menunggu approval -->
<?php if ($stats['guru_pending'] > 0): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center mb-4 border-0 shadow-sm">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-person-exclamation fs-4 text-warning"></i>
        <div>
            <strong>Terdapat <?= $stats['guru_pending'] ?> akun guru baru</strong> yang sedang menunggu persetujuan aktivasi.
        </div>
    </div>
    <a href="<?= base_url('admin/users.php') ?>" class="btn btn-sm btn-dark">Verifikasi Akun Guru</a>
</div>
<?php endif; ?>

<!-- Tabel Topik Pembelajaran Terbaru -->
<div class="card card-modern bg-white mb-4 anim-reveal" style="--d: 0.15s;">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-dark mb-1">Materi Pembelajaran Terkini</h5>
            <p class="text-muted small mb-0">Kelola berkas PPT/PDF dan tautan referensi untuk setiap bab</p>
        </div>
        <a href="<?= base_url('admin/topik.php') ?>" class="btn btn-outline-primary btn-sm">
            Kelola Semua Topik <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th style="width: 100px;">Jenjang</th>
                        <th>Judul Topik / Bab</th>
                        <th style="width: 140px;" class="text-center">Berkas Materi</th>
                        <th style="width: 140px;" class="text-center">Link Web</th>
                        <th style="width: 200px;" class="text-end">Aksi Pengelolaan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topikTerbaru)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada topik yang ditambahkan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topikTerbaru as $t): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">
                                    <?= e($t['nama_kelas']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= e($t['judul_topik']) ?></div>
                                <div class="text-muted small text-truncate" style="max-width: 420px;">
                                    <?= e($t['deskripsi']) ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-10">
                                    <i class="bi bi-file-earmark me-1"></i> <?= $t['total_file'] ?> Berkas
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-10">
                                    <i class="bi bi-link-45deg me-1"></i> <?= $t['total_referensi'] ?> Link
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="<?= base_url('admin/topik_detail.php?id=' . $t['id']) ?>" class="btn btn-sm btn-primary py-1 px-2 fw-semibold" title="Kelola berkas PPT/PDF dan tautan referensi">
                                    <i class="bi bi-folder2-open me-1"></i> Kelola Materi
                                </a>
                                <a href="<?= base_url('guru/topik.php?id=' . $t['id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1 px-2" title="Lihat pratinjau tampilan guru">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pintasan Cepat -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-modern p-3 bg-white h-100">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-layers text-primary me-2"></i>Jenjang Kelas</h6>
            <p class="text-muted small mb-3">Atur nama dan deskripsi pembagian kelas (Kelas 10, 11, 12).</p>
            <a href="<?= base_url('admin/kelas.php') ?>" class="btn btn-sm btn-outline-primary mt-auto">Kelola Kelas</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-modern p-3 bg-white h-100">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-file-earmark-plus text-danger me-2"></i>Unggah Modul & Slide</h6>
            <p class="text-muted small mb-3">Unggah berkas PPT presentasi dan PDF modul ajar hingga 20 MB per file.</p>
            <a href="<?= base_url('admin/topik.php') ?>" class="btn btn-sm btn-outline-danger mt-auto">Pilih Topik & Unggah</a>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-modern p-3 bg-white h-100">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-success me-2"></i>Verifikasi Akun Guru</h6>
            <p class="text-muted small mb-3">Persetujuan pendaftaran akun guru baru dari berbagai sekolah mitra.</p>
            <a href="<?= base_url('admin/users.php') ?>" class="btn btn-sm btn-outline-success mt-auto">Daftar Guru</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
