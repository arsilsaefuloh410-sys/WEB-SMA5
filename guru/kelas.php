<?php
/**
 * Halaman Daftar Bab / Topik dalam Satu Jenjang Kelas
 * Menggunakan Palet Warna Referensi: #2A4D88, #B1BBC8, #D9D9D8
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib masuk (login) terlebih dahulu
require_login();

$tingkat = isset($_GET['tingkat']) ? (int)$_GET['tingkat'] : 0;
$kelas_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Cari data kelas berdasarkan tingkat atau ID
if ($tingkat > 0) {
    $stmt = $pdo->prepare("SELECT * FROM kelas WHERE tingkat = ? LIMIT 1");
    $stmt->execute([$tingkat]);
} elseif ($kelas_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ? LIMIT 1");
    $stmt->execute([$kelas_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM kelas ORDER BY tingkat ASC LIMIT 1");
    $stmt->execute();
}

$kelas = $stmt->fetch();

if (!$kelas) {
    set_flash('warning', 'Kelas yang Anda pilih tidak ditemukan.');
    header('Location: ' . base_url('guru/index.php'));
    exit;
}

$active_menu = 'kelas';
$page_title = 'Bahan Ajar ' . $kelas['nama_kelas'];

// Ambil seluruh topik dalam kelas ini beserta jumlah berkas dan linknya
$stmtTopik = $pdo->prepare("
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
$stmtTopik->execute([$kelas['id']]);
$daftarTopik = $stmtTopik->fetchAll();

$tingkat_val = $kelas['tingkat'];
$header_class = 'card-header-kelas-10';
if ($tingkat_val == 11) {
    $header_class = 'card-header-kelas-11';
} elseif ($tingkat_val == 12) {
    $header_class = 'card-header-kelas-12';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container pt-3 pb-4">
    <!-- Breadcrumb Navigasi -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= base_url('index.php') ?>" class="text-decoration-none" style="color: var(--color-primary);">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= base_url('guru/index.php') ?>" class="text-decoration-none" style="color: var(--color-primary);">Katalog Kelas</a></li>
            <li class="breadcrumb-item active text-muted" aria-current="page"><?= e($kelas['nama_kelas']) ?></li>
        </ol>
    </nav>

    <!-- Header Kelas Bergaya Modern -->
    <div class="card card-kelas mb-4">
        <div class="card-header-gradient <?= $header_class ?>">
            <span class="kelas-number-watermark"><?= $tingkat_val ?></span>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-1 mb-2 border border-white border-opacity-25 small">
                        Informatika Fase <?= $tingkat_val == 10 ? 'E' : 'F' ?> SMA
                    </span>
                    <h2 class="fw-bold mb-1 text-white"><?= e($kelas['nama_kelas']) ?></h2>
                    <p class="text-white text-opacity-90 mb-0 pe-md-5 small">
                        <?= e($kelas['deskripsi'] ?? 'Kumpulan materi pembelajaran dan alat bantu mengajar.') ?>
                    </p>
                </div>

                <div class="text-md-end">
                    <span class="badge px-3 py-2 rounded-pill small" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);">
                        <i class="bi bi-journal-text me-1"></i> <?= count($daftarTopik) ?> Bab Materi
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar Pencarian Bab Cepat -->
    <!-- Toolbar Pencarian Bab Cepat & Filter Elemen -->
    <div class="card card-modern p-3 mb-4 bg-white" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
        <div class="row align-items-center gy-3 mb-2">
            <div class="col-md-7">
                <div class="input-group input-group-pill">
                    <span class="input-group-text bg-white border-end-0 text-muted ps-3"><i class="bi bi-filter"></i></span>
                    <input type="text" id="quickTopicSearch" class="form-control border-start-0 ps-0" placeholder="Ketik kata kunci untuk menyaring bab materi di bawah...">
                </div>
            </div>
            <div class="col-md-5 text-md-end text-muted small">
                <span class="me-2">Pindah kelas:</span>
                <div class="btn-group" role="group">
                    <a href="<?= base_url('guru/kelas.php?tingkat=10') ?>" class="btn btn-sm <?= $tingkat_val == 10 ? 'btn-primary' : 'btn-outline-secondary' ?>">Kls 10</a>
                    <a href="<?= base_url('guru/kelas.php?tingkat=11') ?>" class="btn btn-sm <?= $tingkat_val == 11 ? 'btn-primary' : 'btn-outline-secondary' ?>">Kls 11</a>
                    <a href="<?= base_url('guru/kelas.php?tingkat=12') ?>" class="btn btn-sm <?= $tingkat_val == 12 ? 'btn-primary' : 'btn-outline-secondary' ?>">Kls 12</a>
                </div>
            </div>
        </div>

        <!-- Filter Elemen Kurikulum Merdeka -->
        <div class="pt-2 border-top d-flex align-items-center flex-wrap gap-1" style="border-color: var(--border-subtle) !important;">
            <span class="small fw-semibold text-muted me-2"><i class="bi bi-tags me-1"></i>Elemen:</span>
            <button type="button" class="btn btn-xs rounded-pill px-3 py-1 btn-filter-elemen active btn-primary" data-elemen="ALL">
                Semua
            </button>
            <?php 
            $elemenList = get_elemen_list();
            foreach ($elemenList as $k => $el): 
            ?>
                <button type="button" class="btn btn-xs rounded-pill px-2 py-1 btn-filter-elemen btn-outline-secondary" data-elemen="<?= $k ?>" title="<?= e($el['nama']) ?>">
                    <i class="bi <?= $el['icon'] ?> me-1"></i><?= $k ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pesan jika hasil pencarian kosong -->
    <div id="noTopicFoundMsg" class="card card-modern text-center py-4 mb-4" style="display: none; background: var(--color-ice); border: 1px dashed var(--border-subtle);">
        <i class="bi bi-search text-muted display-6 d-block mb-2"></i>
        <h6 class="fw-bold" style="color: var(--color-primary);">Bab materi tidak ditemukan</h6>
        <p class="text-muted small mb-0">Coba gunakan kata kunci atau elemen lain untuk mencari materi pada kelas ini.</p>
    </div>

    <!-- Daftar Topik / Bab -->
    <?php if (empty($daftarTopik)): ?>
        <div class="card card-modern text-center py-5" style="border: 1px dashed var(--border-subtle);">
            <div class="card-body">
                <i class="bi bi-folder-x text-muted display-4 d-block mb-3"></i>
                <h5 class="fw-bold text-dark">Belum Ada Topik untuk <?= e($kelas['nama_kelas']) ?></h5>
                <p class="text-muted small mb-3">Materi untuk jenjang ini sedang dalam proses penyusunan oleh koordinator materi.</p>
                <a href="<?= base_url('guru/index.php') ?>" class="btn-pill btn-pill-outline btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali ke Katalog Kelas
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3 mb-4" id="topicListContainer">
            <?php 
            $no = 1;
            foreach ($daftarTopik as $topik): 
                $el_info = get_elemen_info($topik['elemen'] ?? 'BK');
            ?>
            <div class="col-12 topic-item-card" data-elemen="<?= e($topik['elemen'] ?? 'BK') ?>">
                <div class="card card-modern p-4" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
                    <div class="row align-items-center gy-3">
                        <div class="col-lg-8 col-md-7">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                <span class="badge rounded-pill px-3 py-1 font-monospace" style="background-color: var(--color-accent-soft); color: var(--color-primary); border: 1px solid rgba(175,203,233,0.4);">
                                    Bab <?= $topik['urutan'] ?? $no ?>
                                </span>
                                <span class="badge rounded-pill px-2 py-1 small d-inline-flex align-items-center gap-1" style="background: <?= $el_info['bg'] ?>; color: <?= $el_info['warna'] ?>; border: 1px solid <?= $el_info['warna'] ?>35;">
                                    <i class="bi <?= $el_info['icon'] ?>"></i> <?= e($el_info['kode']) ?>
                                </span>
                                <span class="text-muted small">• <?= e($kelas['nama_kelas']) ?></span>
                            </div>
                            
                            <h4 class="fw-bold mb-2">
                                <a href="<?= base_url('guru/topik.php?id=' . $topik['id']) ?>" class="text-decoration-none" style="color: var(--color-primary);">
                                    <?= e($topik['judul_topik']) ?>
                                </a>
                            </h4>

                            <p class="text-muted small mb-3" style="line-height: 1.6;">
                                <?= e($topik['deskripsi'] ?? 'Deskripsi topik belum ditambahkan.') ?>
                            </p>

                            <!-- Indikator Berkas & Referensi -->
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge rounded-pill px-3 py-1 d-flex align-items-center gap-1" style="background: var(--color-ice); color: var(--text-main); border: 1px solid var(--border-subtle);">
                                    <i class="bi bi-file-earmark-arrow-down" style="color: #ea580c;"></i> <?= $topik['total_file'] ?> Berkas (PPT/PDF)
                                </span>
                                <span class="badge rounded-pill px-3 py-1 d-flex align-items-center gap-1" style="background: var(--color-ice); color: var(--text-main); border: 1px solid var(--border-subtle);">
                                    <i class="bi bi-link-45deg" style="color: var(--color-primary);"></i> <?= $topik['total_referensi'] ?> Link Belajar
                                </span>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-5 text-md-end">
                            <a href="<?= base_url('guru/topik.php?id=' . $topik['id']) ?>" class="btn-pill btn-pill-primary">
                                <span>Pelajari & Unduh</span>
                                <i class="bi bi-arrow-right-short fs-5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
            $no++;
            endforeach; 
            ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
