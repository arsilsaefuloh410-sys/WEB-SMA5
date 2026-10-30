<?php
/**
 * Halaman Utama (Landing Page) Portal Bahan Ajar Informatika SMA
 * Terinspirasi dari Referensi Desain Modern & Palet Warna:
 * #2A4D88 (Deep Blue), #B1BBC8 (Glacial Salt), #D9D9D8 (Concerto)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Wajib login terlebih dahulu untuk mengakses portal bahan ajar
require_login();

$active_menu = 'beranda';
$page_title = 'Beranda - Portal Bahan Ajar Informatika';

// Ambil data jenjang kelas dan statistik materi
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

// Ambil total statistik sistem
$stats = get_app_stats($pdo);

// Ambil foto hero dari pengaturan admin (maksimal 3, bergantian fade)
$heroSlides = [];
try {
    $stmtHero = $pdo->query("SELECT nilai FROM pengaturan WHERE kunci LIKE 'hero_slide_%' AND nilai != '' ORDER BY kunci ASC");
    foreach ($stmtHero->fetchAll() as $rowHero) {
        $heroSlides[] = $rowHero['nilai'];
    }
} catch (Exception $e) {
    $heroSlides = [];
}

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ==========================================================
     * 1. HERO SECTION (Sesuai Referensi Gambar 2 & 3) — Full-bleed
     * ========================================================== -->
    <section class="hero-wrapper hero-fullbleed<?= !empty($heroSlides) ? ' hero-with-photo' : '' ?>">
        <?php if (!empty($heroSlides)): ?>
        <!-- Slideshow Foto Latar Belakang (bergantian fade, dikendalikan JS) -->
        <div class="hero-slideshow" id="heroSlideshow" aria-hidden="true">
            <?php foreach ($heroSlides as $i => $foto): ?>
                <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= $i ?>" style="background-image: url('<?= base_url($foto) ?>');"></div>
            <?php endforeach; ?>
        </div>
        <div class="hero-overlay" aria-hidden="true"></div>
        <?php endif; ?>

        <div class="container position-relative" style="z-index: 2;">
        <div class="row align-items-center gy-4">
            <div class="col-lg-7">
                <!-- Identitas Sekolah -->
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="brand-icon-box flex-shrink-0" style="width: 52px; height: 52px; background:#ffffff; border: 1px solid rgba(255,255,255,0.28); box-shadow: none;">
                        <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5" style="width:100%;height:100%;object-fit:contain;">
                    </div>
                    <div class="lh-1">
                        <span class="d-block welcome-school-name">SMA NEGERI 5</span>
                        <span class="d-block welcome-school-city">KOTA TERNATE</span>
                    </div>
                </div>
                <h1 class="hero-title">
                    Selamat Datang
                </h1>
                <div class="d-flex flex-wrap gap-3 align-items-center anim-reveal" style="--d: 0.2s;">
                    <?php if (is_logged_in()): ?>
                        <a href="<?= base_url('guru/index.php') ?>" class="btn-pill welcome-hero-btn">
                            <i class="bi bi-collection-play"></i> Buka Katalog Bahan Ajar
                        </a>
                        <a href="<?= base_url('guru/search.php') ?>" class="btn-pill" style="border: 1px solid rgba(255,255,255,0.55); background: transparent; color: #ffffff;">
                            <i class="bi bi-search"></i> Cari Topik Materi
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('auth/login.php') ?>" class="btn-pill welcome-hero-btn">
                            <i class="bi bi-box-arrow-in-right"></i> Masuk Akun Guru
                        </a>
                        <a href="#katalog-kelas" class="btn-pill" style="border: 1px solid rgba(255,255,255,0.55); background: transparent; color: #ffffff;">
                            <i class="bi bi-arrow-down-circle"></i> Lihat Jenjang Kelas
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </div>
    </section>

<div class="container pt-3 pb-4">

    <!-- ==========================================================
     * 2. KARTU FITUR MELAYANG (Sesuai Referensi Gambar 3)
     * ========================================================== -->
    <section class="mb-5">
        <div class="row g-4">
            <!-- Fitur 1 -->
            <div class="col-lg-3 col-md-6">
                <div class="feature-box-card card-primary-gradient anim-reveal" style="--d: 0s;">
                    <div class="feature-icon-pill icon-on-primary">
                        <i class="bi bi-easel-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Slide Presentasi</h5>
                    <p class="small mb-0">
                        Tayangan proyektor PPT/PPTX interaktif siap pakai untuk menjelaskan konsep abstrak di kelas secara visual.
                    </p>
                </div>
            </div>

            <!-- Fitur 2 -->
            <div class="col-lg-3 col-md-6">
                <div class="feature-box-card anim-reveal" style="--d: 0.12s;">
                    <div class="feature-icon-pill">
                        <i class="bi bi-file-earmark-text-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Modul Ajar PDF</h5>
                    <p class="text-muted small mb-0">
                        Panduan ajar ringkas berisi rangkuman bab, tujuan pembelajaran, dan alur mengajar tahap demi tahap.
                    </p>
                </div>
            </div>

            <!-- Fitur 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="feature-box-card anim-reveal" style="--d: 0.24s;">
                    <div class="feature-icon-pill">
                        <i class="bi bi-link-45deg"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Referensi Tepercaya</h5>
                    <p class="text-muted small mb-0">
                        Tautan langsung ke Buku Guru Kemdikbudristek, Bebras Indonesia, CS Unplugged, dan modul terverifikasi.
                    </p>
                </div>
            </div>

            <!-- Fitur 4 -->
            <div class="col-lg-3 col-md-6">
                <div class="feature-box-card anim-reveal" style="--d: 0.36s;">
                    <div class="feature-icon-pill">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Ramah Guru Non-IT</h5>
                    <p class="text-muted small mb-0">
                        Menggunakan analogi sehari-hari dan bebas istilah teknis yang rumit, memudahkan guru baru maupun senior.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
     * 3. TENTANG & KEUNGGULAN (Sesuai Referensi Gambar 2 & 3)
     * ========================================================== -->
    <section class="mb-5 py-3">
        <div class="row align-items-center gy-4">
            <div class="col-lg-6">
                <div class="section-eyebrow anim-reveal">
                    <i class="bi bi-stars me-1"></i> TENTANG BAHAN AJAR
                </div>
                <h2 class="section-title mb-3">
                    Membantu Guru Mengajar Informatika Lebih Mudah & Terarah
                </h2>
                <p class="text-muted mb-4" style="line-height: 1.7;">
                    Banyak guru SMA yang ditugaskan mengajar mata pelajaran Informatika berasal dari latar belakang non-IT. Portal ini hadir sebagai jembatan praktis agar proses persiapan mengajar menjadi ringan, terstruktur, dan tidak membingungkan.
                </p>

                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-start gap-3 anim-reveal" style="--d: 0.25s;">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="background: var(--color-ice); color: var(--color-primary); width: 36px; height: 36px; border: 1px solid var(--border-subtle);">
                            <i class="bi bi-check-lg fw-bold"></i>
                        </div>
                        <div>
                            <strong class="d-block text-dark">Sesuai Capaian Kurikulum Merdeka</strong>
                            <span class="text-muted small">Mencakup Berpikir Komputasional (BK), TIK, Sistem Komputer (SK), Jaringan & Internet (JKI), Analisis Data (AD), hingga Dampak Sosial Informatika (DSI).</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 anim-reveal" style="--d: 0.35s;">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="background: var(--color-ice); color: var(--color-primary); width: 36px; height: 36px; border: 1px solid var(--border-subtle);">
                            <i class="bi bi-check-lg fw-bold"></i>
                        </div>
                        <div>
                            <strong class="d-block text-dark">Unduh Langsung Tanpa Iklan / Batasan</strong>
                            <span class="text-muted small">Seluruh berkas modul dan presentasi dapat diunduh sekali klik dalam format PDF atau PPTX asli.</span>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-3 anim-reveal" style="--d: 0.45s;">
                        <div class="rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="background: var(--color-ice); color: var(--color-primary); width: 36px; height: 36px; border: 1px solid var(--border-subtle);">
                            <i class="bi bi-check-lg fw-bold"></i>
                        </div>
                        <div>
                            <strong class="d-block text-dark">Aktivitas Tanpa Komputer (Unplugged)</strong>
                            <span class="text-muted small">Menyediakan alternatif aktivitas pembelajaran yang dapat diterapkan di ruang kelas meskipun tanpa lab komputer.</span>
                        </div>
                    </div>
                </div>

                <a href="<?= is_logged_in() ? base_url('guru/index.php') : base_url('auth/login.php') ?>" class="btn-pill btn-pill-primary anim-reveal" style="--d: 0.45s;">
                    <i class="bi bi-arrow-right-circle"></i> Mulai Jelajahi Sekarang
                </a>
            </div>

            <!-- Kartu Highlight Kanan -->
            <div class="col-lg-6">
                <div class="p-4 rounded-4 anim-reveal anim-right" style="background: linear-gradient(135deg, rgba(217, 217, 216, 0.95) 0%, rgba(177, 187, 200, 0.3) 100%); border: 1px solid var(--border-subtle);">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="card card-modern p-4 h-100 border-0 shadow-sm text-center">
                                <div class="fs-1 fw-bold mb-1" style="color: var(--color-primary); font-family: 'Outfit';">Fase E</div>
                                <div class="fw-semibold text-dark">Informatika Kelas 10</div>
                                <p class="text-muted small mb-0 mt-1">Fondasi Berpikir Komputasional & Literasi Digital</p>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="card card-modern p-4 h-100 border-0 shadow-sm text-center">
                                <div class="fs-1 fw-bold mb-1" style="color: var(--color-primary); font-family: 'Outfit';">Fase F</div>
                                <div class="fw-semibold text-dark">Informatika Kelas 11 & 12</div>
                                <p class="text-muted small mb-0 mt-1">Algoritma Lanjut, Basis Data, & Rekayasa Perangkat Lunak</p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-modern p-4 border-0 shadow-sm" style="background: var(--color-primary); color: #ffffff;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="feature-icon-pill icon-on-primary m-0 flex-shrink-0">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-white mb-1">Dikelola oleh MGMP Sekolah</h5>
                                        <p class="small mb-0" style="color: rgba(217, 217, 216, 0.85);">
                                            Materi terus diperbarui sesuai dengan panduan resmi Kementerian Pendidikan dan kebiasaan mengajar di sekolah.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ==========================================================
     * 4. KATALOG JENJANG KELAS (Sidebar) + PENCARIAN CEPAT
     * ========================================================== -->
    <section id="katalog-kelas" class="mb-5 pt-3">
        <div class="row g-4 align-items-start">

            <!-- Konten Utama: Pencarian Cepat -->
            <div class="col-lg-8">
                <div class="mb-4">
                    <div class="section-eyebrow anim-reveal">
                        <i class="bi bi-folder2-open me-1"></i> KATALOG MATERI
                    </div>
                    <h2 class="section-title mb-2">Pilih Jenjang Kelas</h2>
                    <p class="text-muted mb-0" style="line-height: 1.7;">
                        Silakan pilih kelas yang Anda ampu untuk mengakses modul tayang, rangkuman bab, dan bahan ajar siap pakai. Daftar kelas menempel di samping agar mudah berpindah saat menelusuri halaman.
                    </p>
                </div>

                <div class="card card-modern p-4 p-md-5 text-center anim-reveal anim-zoom" style="background: linear-gradient(180deg, #ffffff 0%, var(--color-ice) 100%); border: 1px solid var(--border-subtle);">
                    <div class="mx-auto mb-3" style="max-width: 650px;">
                        <h3 class="fw-bold mb-2" style="color: var(--color-primary);">Butuh Topik Tertentu? Cari di Sini</h3>
                        <p class="text-muted small mb-0">Ketik judul materi seperti "Berpikir Komputasional", "Jaringan Komputer", atau "Algoritma" untuk pencarian cepat.</p>
                    </div>

                    <div class="mx-auto w-100 mt-1" style="max-width: 600px;">
                        <form action="<?= base_url('guru/search.php') ?>" method="GET">
                            <div class="input-group input-group-pill shadow-sm">
                                <span class="input-group-text bg-white border-end-0 ps-3 text-muted">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="q" class="form-control border-start-0 border-end-0 py-3" placeholder="Ketik topik materi yang ingin dicari..." required>
                                <button class="btn btn-pill-primary px-4" type="submit">
                                    Cari Materi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar Jenjang Kelas (Sticky) -->
            <div class="col-lg-4">
                <div class="sidebar-kelas anim-reveal anim-right" style="--d: 0.15s;">
                    <div class="p-4 border-bottom" style="border-color: var(--border-subtle) !important;">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="bi bi-collection-fill" style="color: var(--color-primary);"></i>
                            <h5 class="fw-bold mb-0 text-dark">Jenjang Kelas</h5>
                        </div>
                        <small class="text-muted">Daftar cepat kelas yang tersedia</small>
                    </div>

                    <div class="p-2">
                        <?php
                        $faseMap = [10 => 'E', 11 => 'F', 12 => 'F'];
                        $iconMap = [
                            10 => 'bi-laptop',
                            11 => 'bi-diagram-3',
                            12 => 'bi-cpu'
                        ];
                        $warnaMap = [
                            10 => ['#4893C6', '#ffffff'],
                            11 => ['#64A4CE', '#ffffff'],
                            12 => ['#8DBEDC', '#ffffff']
                        ];
                        foreach ($daftarKelas as $kls):
                            $tingkat = $kls['tingkat'];
                            $icon = $iconMap[$tingkat] ?? 'bi-book';
                            $w = $warnaMap[$tingkat] ?? ['var(--color-primary)', '#ffffff'];
                            $warna = $w[0];
                            $warnaTeks = $w[1];
                        ?>
                        <a href="<?= base_url('guru/kelas.php?tingkat=' . $tingkat) ?>" class="sidebar-kelas-item anim-reveal" style="--d: <?= 0.2 + ($tingkat - 10) * 0.08 ?>s;">
                            <div class="sidebar-kelas-angka" style="background-color: <?= $warna ?>; color: <?= $warnaTeks ?>;">
                                <i class="bi <?= $icon ?>"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold text-dark"><?= e($kls['nama_kelas']) ?></div>
                            </div>
                            <i class="bi bi-chevron-right text-muted sidebar-kelas-arrow"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-3 pt-1">
                        <a href="<?= base_url('guru/index.php') ?>" class="btn-pill btn-pill-outline w-100 justify-content-center">
                            <i class="bi bi-grid me-1"></i> Katalog Semua Kelas
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
