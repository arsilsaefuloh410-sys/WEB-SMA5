<?php
/**
 * Halaman Utama (Landing Page) Portal Bahan Ajar Informatika
 * Arah desain: "The printed annual" (editorial-report) — taste-skill.
 * Palet & huruf merek dipertahankan: #2A4D88, #B1BBC8, #D9D9D8, Outfit + Plus Jakarta Sans.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Wajib login terlebih dahulu untuk mengakses portal bahan ajar
require_login();

$active_menu = 'beranda';
$page_title = 'Beranda - Portal Bahan Ajar Informatika';

// Topik per tingkat kelas (dipakai daftar berjenjang di bagian katalog)
$stmtTopik = $pdo->query("
    SELECT t.id, t.judul_topik, t.elemen, k.tingkat,
           (SELECT COUNT(*) FROM file_materi f WHERE f.topik_id = t.id) AS jumlah_berkas,
           (SELECT COUNT(*) FROM referensi r WHERE r.topik_id = t.id) AS jumlah_referensi
    FROM topik t
    JOIN kelas k ON k.id = t.kelas_id
    ORDER BY k.tingkat ASC, t.urutan ASC, t.id ASC
");
$topikPerKelas = [10 => [], 11 => [], 12 => []];
foreach ($stmtTopik->fetchAll() as $rowTopik) {
    $tingkat = (int) $rowTopik['tingkat'];
    if (isset($topikPerKelas[$tingkat])) {
        $topikPerKelas[$tingkat][] = $rowTopik;
    }
}

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
if (empty($heroSlides)) {
    $heroSlides = ['assets/img/ternate-city.jpg'];
}

// Ambil foto & keterangan beranda dari pengaturan admin (3 foto + teks di bawahnya)
$galleryItems = [];
try {
    $stmtGal = $pdo->query("SELECT kunci, nilai FROM pengaturan WHERE kunci LIKE 'gallery_%'");
    $galRaw = [];
    foreach ($stmtGal->fetchAll() as $rowGal) {
        $galRaw[$rowGal['kunci']] = (string) $rowGal['nilai'];
    }
    foreach ([1, 2, 3] as $galNo) {
        $galImage = trim($galRaw['gallery_' . $galNo . '_image'] ?? '');
        if ($galImage === '') {
            continue; // slot kosong — lewati
        }
        $galleryItems[] = [
            'image' => $galImage,
            'caption' => trim($galRaw['gallery_' . $galNo . '_caption'] ?? ''),
        ];
    }
} catch (Exception $e) {
    $galleryItems = []; // tabel pengaturan belum ada — pakai fallback video
}

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ==========================================================
     * 1. HERO — "Narrow talk, wide headline" (direction sheet)
     * ========================================================== -->
    <section class="ed-hero">
        <div class="ed-hero-grid">

            <!-- Kolom kanan lebar: tautan, judul raksasa, foto terpotong -->
            <div class="ed-hero-main">
                <nav class="ed-hero-links anim-reveal" style="--d: 0.35s;" aria-label="Tautan cepat">
                    <a href="<?= base_url('guru/index.php') ?>">Katalog</a>
                    <a href="<?= base_url('guru/search.php') ?>">Cari Materi</a>
                    <a href="#katalog-kelas">Jenjang Kelas</a>
                    <a href="#tentang">Tentang</a>
                </nav>

                <!-- Foto hero gaya lama: latar penuh, sudut membulat, teks putih di atas foto -->
                <div class="hero-wrapper hero-fullbleed hero-with-photo">
                    <div class="hero-slideshow" id="heroSlideshow" aria-hidden="true">
                        <?php foreach ($heroSlides as $i => $foto): ?>
                            <div class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-slide="<?= $i ?>" style="background-image: url('<?= e(base_url($foto)) ?>');"></div>
                        <?php endforeach; ?>
                    </div>
                    <div class="hero-overlay" aria-hidden="true"></div>

                    <div class="hero-text-card">
                        <!-- Identitas Sekolah -->
                        <div class="d-flex align-items-center gap-3 mb-2 hero-school-row">
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
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ==========================================================
     * 2. YANG TERSEDIA — "Pinned heading, passing rows"
     * ========================================================== -->
    <section class="ed-features" id="fitur">
        <div class="ed-feat-inner">
            <h2 class="ed-feat-heading">Yang Tersedia</h2>

            <div class="ed-feat-rows">

                <article class="ed-feat-block anim-reveal" style="--d: 0s;">
                    <h3>Slide Presentasi</h3>
                    <div class="ed-feat-row">
                        <img src="<?= base_url('assets/img/school-dance.webp') ?>"
                             alt="Pagelaran tari siswa di halaman SMA Negeri 5 Kota Ternate"
                             width="1400" height="937" loading="lazy" decoding="async">
                        <ul class="anim-reveal" style="--d: 0.1s;">
                            <li>PPT dan PPTX siap dipakai proyektor</li>
                            <li>Konsep abstrak dijelaskan lewat tayangan visual</li>
                            <li>Diunduh langsung tanpa iklan atau batasan</li>
                            <li>Format asli, sekali klik</li>
                        </ul>
                    </div>
                </article>

                <article class="ed-feat-block anim-reveal" style="--d: 0.08s;">
                    <h3>Modul Ajar PDF</h3>
                    <div class="ed-feat-row ed-feat-row-flip">
                        <img src="<?= base_url('assets/img/school-visit.webp') ?>"
                             alt="Kunjungan ke pusat perpustakaan SMA Negeri 5 Kota Ternate"
                             width="1400" height="788" loading="lazy" decoding="async">
                        <ul class="anim-reveal" style="--d: 0.1s;">
                            <li>Rangkuman bab dan tujuan pembelajaran</li>
                            <li>Alur mengajar tahap demi tahap</li>
                            <li>Panduan ajar untuk Fase E dan Fase F</li>
                            <li>Dikelola MGMP Informatika sekolah</li>
                        </ul>
                    </div>
                </article>

                <article class="ed-feat-block anim-reveal" style="--d: 0.16s;">
                    <h3>Referensi Tepercaya</h3>
                    <div class="ed-feat-row">
                        <img src="<?= base_url('assets/img/maitara-tidore.jpg') ?>"
                             alt="Pulau Maitara dan Pulau Tidore dari Ternate"
                             width="1920" height="1440" loading="lazy" decoding="async">
                        <ul class="anim-reveal" style="--d: 0.1s;">
                            <li>Buku Guru Kemdikbudristek</li>
                            <li>Bebras Indonesia</li>
                            <li>CS Unplugged</li>
                            <li>Modul terverifikasi lainnya</li>
                        </ul>
                    </div>
                </article>

                <article class="ed-feat-block anim-reveal" style="--d: 0.24s;">
                    <h3>Ramah Guru Non-IT</h3>
                    <div class="ed-feat-row ed-feat-row-flip">
                        <img src="<?= base_url('assets/img/school-osis.webp') ?>"
                             alt="Pelatihan pengurus OSIS SMA Negeri 5 Kota Ternate"
                             width="1400" height="933" loading="lazy" decoding="async">
                        <ul class="anim-reveal" style="--d: 0.1s;">
                            <li>Analogi sehari-hari, bukan istilah rumit</li>
                            <li>Untuk guru baru maupun senior</li>
                            <li>Aktivitas tanpa komputer (unplugged)</li>
                            <li>Persiapan mengajar jadi ringan</li>
                        </ul>
                    </div>
                </article>

            </div>
        </div>
    </section>

    <!-- ==========================================================
     * 3. TENTANG — "Portrait as drop cap" (pernyataan)
     * ========================================================== -->
    <section class="ed-about" id="tentang">
        <div class="ed-about-grid">

            <aside class="ed-facts anim-reveal">
                <p>
                    <strong>Fase E &middot; Kelas 10</strong>
                    Berpikir Komputasional, TIK, Sistem Komputer, Jaringan &amp; Internet, Analisis Data, Algoritma.
                </p>
                <p>
                    <strong>Fase F &middot; Kelas 11 &amp; 12</strong>
                    Algoritma lanjut, basis data, jaringan, dan proyek kolaboratif lintas bidang.
                </p>
                <p>
                    <strong>Pengelola</strong>
                    MGMP Informatika, mengikuti panduan resmi Kementerian Pendidikan.
                </p>
            </aside>

            <div class="ed-statement-col">
                <h2 class="ed-about-heading anim-reveal">Mengajar Lebih Terarah</h2>
                <p class="ed-statement anim-reveal" style="--d: 0.08s;">
                    <img class="ed-dropcap" src="<?= base_url('assets/img/ternate-city.jpg') ?>"
                         alt="Kota Ternate dilihat dari ketinggian"
                         width="1920" height="1440" loading="lazy" decoding="async">
                    Banyak guru SMA yang mengajar Informatika berasal dari latar non-IT. Portal ini jembatan
                    praktis agar persiapan mengajar jadi ringan, terstruktur, dan tidak membingungkan.
                </p>
                <p class="ed-statement ed-statement-soft anim-reveal" style="--d: 0.16s;">
                    Materinya mengikuti Capaian Pembelajaran Kurikulum Merdeka untuk Fase E dan Fase F,
                    dikelola MGMP Informatika sekolah, dan diperbarui mengikuti panduan resmi Kementerian
                    Pendidikan.
                </p>
            </div>

        </div>
    </section>

    <!-- ==========================================================
     * FOTO & KETERANGAN BERANDA — slideshow bergantian dari admin.
     * Tiap foto punya teks keterangan yang ikut berganti (fade).
     * Jika semua kosong, tampilkan video bawaan (Benteng Kalamata).
     * ========================================================== -->
    <?php if (!empty($galleryItems)): ?>
    <section class="ed-gallery anim-reveal" aria-label="Foto beranda">
        <div class="ed-gal-slideshow" id="galSlideshow">
            <?php foreach ($galleryItems as $i => $galItem): ?>
            <div class="ed-gal-slide<?= $i === 0 ? ' is-active' : '' ?>"
                 style="background-image: url('<?= e(base_url($galItem['image'])) ?>');"
                 role="img"
                 aria-label="<?= e($galItem['caption'] !== '' ? $galItem['caption'] : 'Foto beranda SMA Negeri 5 Kota Ternate') ?>">
                <?php if ($galItem['caption'] !== ''): ?>
                <span class="ed-gal-caption"><?= e($galItem['caption']) ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php else: ?>
    <!-- Satu gambar bergerak: potongan video tempat sekolah ini berdiri (fallback) -->
    <figure class="ed-place anim-reveal">
        <video autoplay muted loop playsinline preload="metadata"
               poster="<?= base_url('assets/a-view-around-kalamata-fort-ternate-north-poster.jpg') ?>"
               width="854" height="480" aria-hidden="true">
            <source src="<?= base_url('assets/a-view-around-kalamata-fort-ternate-north.webm') ?>" type="video/webm">
        </video>
        <figcaption class="ed-caption">
            Benteng Kalamata, Ternate &mdash; kota tempat sekolah ini berdiri
        </figcaption>
    </figure>
    <?php endif; ?>

    <!-- ==========================================================
     * 4. KATALOG KELAS — "Day tabs, banded agenda"
     * ========================================================== -->
    <section class="ed-list" id="katalog-kelas">
        <div class="ed-list-head anim-reveal">
            <h2 class="ed-list-title">
                <span class="soft">Pilih jenjang</span><br>Kelas yang Anda Ampu
            </h2>
            <div class="ed-list-search">
                <form action="<?= base_url('guru/search.php') ?>" method="GET" class="ed-search">
                    <label class="visually-hidden" for="edSearchInput">Cari topik materi</label>
                    <input id="edSearchInput" type="text" name="q" placeholder="Cari topik materi&hellip;" required>
                    <button class="ed-btn" type="submit">Cari Materi</button>
                </form>
                <p class="ed-search-note">
                    Ketik judul materi seperti &ldquo;Berpikir Komputasional&rdquo;, &ldquo;Jaringan Komputer&rdquo;,
                    atau &ldquo;Algoritma&rdquo;.
                </p>
            </div>
        </div>

        <div class="ed-list-body">
            <div class="ed-tabs" role="tablist" aria-label="Jenjang kelas">
                <?php foreach ([10 => 'Fase E', 11 => 'Fase F', 12 => 'Fase F'] as $tingkat => $fase): ?>
                <button type="button" class="ed-tab<?= $tingkat === 10 ? ' is-active' : '' ?>"
                        role="tab" id="tab-<?= $tingkat ?>"
                        aria-selected="<?= $tingkat === 10 ? 'true' : 'false' ?>"
                        aria-controls="panel-<?= $tingkat ?>"
                        data-tab="<?= $tingkat ?>">
                    <span class="tab-name">Kelas <?= $tingkat ?></span>
                    <span class="tab-sub"><?= $fase ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="ed-panels">
                <?php foreach ([10, 11, 12] as $tingkat): ?>
                <div class="ed-panel<?= $tingkat === 10 ? ' is-active' : '' ?>"
                     role="tabpanel" id="panel-<?= $tingkat ?>" aria-labelledby="tab-<?= $tingkat ?>">
                    <?php if (empty($topikPerKelas[$tingkat])): ?>
                        <p class="ed-band-empty">Belum ada topik untuk kelas ini.</p>
                    <?php else: ?>
                        <?php foreach ($topikPerKelas[$tingkat] as $topik): ?>
                        <a class="ed-band" href="<?= base_url('guru/topik.php?id=' . (int) $topik['id']) ?>">
                            <span class="band-main">
                                <span class="band-title"><?= e($topik['judul_topik']) ?></span>
                                <span class="band-note">
                                    <?= e($topik['elemen']) ?>
                                    &middot; <?= (int) $topik['jumlah_berkas'] ?> berkas
                                    &middot; <?= (int) $topik['jumlah_referensi'] ?> referensi
                                </span>
                            </span>
                            <span class="band-go" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                        </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <a class="ed-panel-more" href="<?= base_url('guru/index.php') ?>">
                    Katalog semua kelas <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
