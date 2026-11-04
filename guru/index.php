<?php
/**
 * Portal Guru - Daftar Jenjang Kelas & Bahan Ajar
 * Menggunakan Palet Warna Referensi: #2A4D88, #B1BBC8, #D9D9D8
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Wajib masuk (login) terlebih dahulu
require_login();

$active_menu = 'kelas';
$page_title = 'Pusat Bahan Ajar Guru';

// Mengambil daftar kelas dan statistik lengkap per kelas
$stmtKelas = $pdo->query("
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
$daftarKelas = $stmtKelas->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container pt-3 pb-4">
    <!-- Header Portal Guru -->
    <div class="card card-modern p-4 mb-4 bg-white anim-reveal" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">
        <div class="row align-items-center gy-3">
            <div class="col-md-7">
                <h3 class="fw-bold mb-1" style="color: var(--color-primary);">
                    Katalog Bahan Ajar & Modul Siap Tayang
                </h3>
                <p class="text-muted small mb-0">
                    Pilih jenjang kelas di bawah untuk menemukan modul ajar PDF, slide presentasi PPTX, dan referensi bacaan terstruktur.
                </p>
            </div>
            <div class="col-md-5">
                <form action="<?= base_url('guru/search.php') ?>" method="GET">
                    <div class="input-group input-group-pill">
                        <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0 border-end-0" placeholder="Cari topik modul..." required>
                        <button class="btn btn-pill-primary px-3" type="submit">
                            Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Pilihan 3 Kelas -->
    <div class="row g-4 mb-5">
        <?php 
        $header_classes = [
            10 => 'card-header-kelas-10',
            11 => 'card-header-kelas-11',
            12 => 'card-header-kelas-12'
        ];
            $icons = [
                10 => 'bi-laptop',
                11 => 'bi-diagram-3',
                12 => 'bi-cpu'
            ];
            $indexKartu = 0;
            foreach ($daftarKelas as $kls): 
                $tingkat = $kls['tingkat'];
                $header_class = $header_classes[$tingkat] ?? 'card-header-kelas-10';
                $icon = $icons[$tingkat] ?? 'bi-book';
                $delayKartu = round($indexKartu * 0.15, 2);
                $indexKartu++;
            ?>
        <div class="col-lg-4 col-md-6">
            <div class="card card-kelas h-100 anim-reveal" style="--d: <?= $delayKartu ?>s;">
                <div class="card-header-gradient <?= $header_class ?>">
                    <span class="kelas-number-watermark"><?= $tingkat ?></span>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi <?= $icon ?> fs-3 text-white"></i>
                        <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-1 text-white border border-white border-opacity-25 small">
                            Fase <?= $tingkat == 10 ? 'E' : 'F' ?> SMA
                        </span>
                    </div>
                    <h3 class="fw-bold mb-1 text-white"><?= e($kls['nama_kelas']) ?></h3>
                    <p class="small text-white text-opacity-90 mb-0">Kurikulum Merdeka Informatika</p>
                </div>
                
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div>
                        <p class="text-muted small mb-4" style="line-height: 1.6; min-height: 3.6em;">
                            <?= e($kls['deskripsi'] ?? 'Materi bahan ajar yang terstruktur dan siap digunakan.') ?>
                        </p>

                        <!-- Statistik Konten dalam Kelas -->
                        <div class="p-3 rounded-3 mb-4" style="background: var(--color-ice); border: 1px solid var(--border-subtle);">
                                <div class="row text-center g-2">
                                    <div class="col-4 border-end" style="border-color: var(--border-subtle) !important;">
                                        <div class="fw-bold fs-5 text-dark counter-value" data-count="<?= (int) $kls['total_topik'] ?>"><?= $kls['total_topik'] ?></div>
                                        <small class="text-muted" style="font-size: 0.75rem;">Bab Topik</small>
                                    </div>
                                    <div class="col-4 border-end" style="border-color: var(--border-subtle) !important;">
                                        <div class="fw-bold fs-5 counter-value" data-count="<?= (int) $kls['total_file'] ?>" style="color: #ea580c;"><?= $kls['total_file'] ?></div>
                                        <small class="text-muted" style="font-size: 0.75rem;">Berkas</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold fs-5 counter-value" data-count="<?= (int) $kls['total_referensi'] ?>" style="color: var(--color-primary);"><?= $kls['total_referensi'] ?></div>
                                        <small class="text-muted" style="font-size: 0.75rem;">Link Web</small>
                                    </div>
                                </div>
                        </div>
                    </div>

                    <a href="<?= base_url('guru/kelas.php?tingkat=' . $tingkat) ?>" class="btn-pill btn-pill-primary w-100 justify-content-between">
                        <span>Masuk ke <?= e($kls['nama_kelas']) ?></span>
                        <i class="bi bi-arrow-right-short fs-5"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Informasi Petunjuk Belajar untuk Guru -->
    <div class="p-4 d-flex align-items-start gap-3 anim-reveal" style="background: var(--color-ice); border-radius: var(--radius-lg);">
        <div class="p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; background: var(--color-primary); color: #ffffff; border-radius: var(--radius-sm);">
            <i class="bi bi-lightbulb-fill fs-5"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-1" style="color: var(--color-primary);">Tips Menggunakan Bahan Ajar Ini</h5>
            <p class="small text-muted mb-0" style="line-height: 1.6;">
                Setiap bab dilengkapi dengan <strong>Slide Presentasi (PPT)</strong> yang dapat Bapak/Ibu tayangkan di kelas, <strong>Modul Bacaan (PDF)</strong> untuk pegangan mengajar mandiri, serta <strong>Link Referensi Eksternal</strong> untuk memperkaya latihan soal dan wawasan praktis.
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
