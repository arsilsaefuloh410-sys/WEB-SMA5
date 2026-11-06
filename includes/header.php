<?php
/**
 * Header Standar untuk Halaman Publik & Guru
 * Menggunakan Palet Warna Referensi:
 * #2A4D88 (Deep Blue), #B1BBC8 (Glacial Salt), #D9D9D8 (Concerto)
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Cek hak akses: Halaman auth (login/logout/register) tidak butuh login.
// Halaman lain yang memakai header ini wajib login — termasuk index.php
// (guard tambahan di tiap halaman tetap berlaku sebagai lapis kedua).
$current_script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (!preg_match('~/auth/~', $current_script)) {
    require_login();
}

// Penanda halaman login (untuk menyembunyikan navigasi saat tamu belum login)
$on_login_page = (bool) preg_match('~/auth/login\.php$~', $current_script);

// Penanda halaman beranda ROOT (gaya editorial) — bukan guru/index.php
$on_landing = (bool) preg_match('~/index\.php$~', $current_script)
    && !preg_match('~/(guru|admin|auth|includes)/~', $current_script);

$page_title = isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME;
$current_user = current_user();

/**
 * Render menu navigasi berbentuk pill (dipakai sidebar desktop & navbar mobile).
 * $suffix dipakai agar ID collapse unik saat menu dirender dua kali.
 */
function render_nav_menu(string $suffix): void
{
    global $active_menu;
    $tingkatAktif = (int) ($_GET['tingkat'] ?? 0);
    $kelasActive = isset($active_menu) && $active_menu === 'kelas';
    $onKatalog = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/guru/index.php') !== false;
    $warnaDot = [10 => '#4893C6', 11 => '#64A4CE', 12 => '#8DBEDC'];
    ?>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="side-link <?= (!isset($active_menu) || $active_menu === 'beranda') ? 'active' : '' ?>" href="<?= base_url('index.php') ?>">
                <i class="bi bi-house-door"></i> Beranda
            </a>
        </li>
        <li class="nav-item">
            <a class="side-link <?= $kelasActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="#sideKelas<?= $suffix ?>" role="button" aria-expanded="<?= $kelasActive ? 'true' : 'false' ?>" aria-controls="sideKelas<?= $suffix ?>">
                <i class="bi bi-collection"></i> Jenjang Kelas
                <i class="bi bi-chevron-down side-chevron"></i>
            </a>
            <ul class="collapse <?= $kelasActive ? 'show' : '' ?>" id="sideKelas<?= $suffix ?>">
                <?php foreach ([10 => 'Fase E', 11 => 'Fase F', 12 => 'Fase F'] as $t => $fase): ?>
                <li>
                    <a class="side-sublink <?= $tingkatAktif === $t ? 'active' : '' ?>" href="<?= base_url('guru/kelas.php?tingkat=' . $t) ?>">
                        <span class="side-dot" style="background-color: <?= $warnaDot[$t] ?>;"></span>
                        <span>Kelas <?= $t ?></span>
                        <span class="ms-auto small fw-semibold" style="color: var(--text-light);"><?= $fase ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
        <li class="nav-item">
            <a class="side-link <?= $onKatalog ? 'active' : '' ?>" href="<?= base_url('guru/index.php') ?>">
                <i class="bi bi-grid"></i> Semua Kelas
            </a>
        </li>
    </ul>
    <?php
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function () {
            var W = 1200;
            var w = window.innerWidth || document.documentElement.clientWidth;
            var s = w > 0 ? w / W : 0;
            if (s > 0 && s < 1) {
                document.querySelector('meta[name="viewport"]').setAttribute('content', 'width=' + W + ', initial-scale=' + Math.round(s * 10000) / 10000);
            }
        })();
    </script>
    <title><?= e($page_title) ?></title>
    
    <!-- Meta SEO Ramah Pendidik -->
    <meta name="description" content="Portal bahan ajar, modul praktis Kurikulum Merdeka, slide presentasi PPT, dan referensi materi Informatika SMA untuk guru.">
    <meta name="author" content="Tim Informatika <?= APP_SCHOOL ?>">

    <!-- Favicon Inline (menghindari request /favicon.ico 404) -->
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232A4D88'/%3E%3Cpath d='M16 8 5 13l11 5 11-5-11-5Zm-6 8.5V21c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5l-6 2.7-6-2.7Z' fill='%23fff'/%3E%3C/svg%3E">

    <!-- Google Fonts: dimuat non-blocking (1 request, tanpa menunda render) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" onload="this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

    <!-- Bootstrap 5.3 & Icons CDN (preconnect agar DNS+TLS sekali saja) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Gaya Kustom dengan Palet Warna #2A4D88, #B1BBC8, #D9D9D8 (versi=filemtime agar tidak terkena cache) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">

    <!-- Terapkan preferensi mode gelap/terang pengguna sesegera mungkin (anti kedip) -->
    <script>
    (function () {
        try {
            if (localStorage.getItem('theme') === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        } catch (e) {}
    })();
    </script>

    <!-- Penanda JavaScript aktif (agar animasi reveal tidak menyembunyikan konten jika JS gagal) -->
    <script>document.documentElement.classList.add('js-anim', 'js-tabs');</script>
</head>
<body class="<?= is_logged_in() ? 'has-sidebar sidebar-collapsed' : '' ?><?= $on_landing ? ' landing' : '' ?>">

    <?php if (is_logged_in()): ?>
    <!-- Sidebar Permanen di Kiri (Tersembunyi Secara Default) -->
    <aside class="app-sidebar">
        <a class="side-brand" href="<?= base_url('index.php') ?>">
            <div class="side-brand-icon" style="background:#ffffff;">
                <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5" style="width:100%;height:100%;object-fit:contain;">
            </div>
            <div class="min-width-0">
                <span class="side-brand-title">Portal Informatika</span>
                <small class="side-brand-sub">SMAN 5 Kota Ternate</small>
            </div>
        </a>

        <?php render_nav_menu('s'); ?>

        <?php if ($current_user): ?>
        <div class="side-user-card">
            <div class="d-flex align-items-center gap-2">
                <div class="side-user-avatar"><i class="bi bi-person-fill"></i></div>
                <div class="min-width-0">
                    <div class="fw-bold text-truncate"><?= e($current_user['nama']) ?></div>
                    <small class="side-user-role"><?= is_admin() ? 'Administrator MGMP' : 'Guru Pengajar' ?></small>
                </div>
            </div>
            <a href="<?= base_url('auth/logout.php') ?>" class="side-user-logout">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </a>
        </div>
        <?php endif; ?>
    </aside>
    <div class="sidebar-backdrop"></div>
    <?php endif; ?>

    <!-- Area Konten di Kanan Sidebar -->
    <div class="app-main-wrap">

    <!-- Navigasi Utama -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top">
        <div class="container-fluid px-xl-5 px-lg-4 px-3">
            <?php if (is_logged_in()): ?>
            <!-- Tombol Garis Tiga: Paling Kiri, di Samping Logo Portal -->
            <button class="sidebar-toggle me-2" type="button" aria-label="Buka atau tutup menu sidebar">
                <i class="bi bi-list"></i>
            </button>
            <?php endif; ?>

            <a class="navbar-brand navbar-brand-main d-flex align-items-center gap-2 me-lg-4 text-decoration-none" href="<?= base_url('index.php') ?>">
                <div class="brand-icon-box flex-shrink-0" style="background:#ffffff;">
                    <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5" style="width:100%;height:100%;object-fit:contain;">
                </div>
                <div class="d-flex flex-column lh-1 min-width-0">
                    <span class="fs-6 fw-bold text-dark text-nowrap" style="font-size: 0.95rem;">Portal Bahan Ajar Informatika</span>
                    <small class="fw-semibold text-nowrap mt-1 d-none d-sm-block" style="font-size: 0.8125rem; color: var(--color-primary);">
                        <i class="bi bi-geo-alt-fill me-1" style="color: var(--color-accent);"></i>SMAN 5 Kota Ternate
                    </small>
                </div>
            </a>

            <!-- Tombol Masuk / Status Akun Pengguna -->
            <div class="nav-actions d-flex align-items-center gap-2 ms-auto">
                    <!-- Tombol Mode Gelap / Terang -->
                    <button class="nav-icon-btn theme-toggle" type="button" aria-label="Aktifkan mode gelap" title="Mode Gelap / Terang">
                        <i class="bi bi-moon-stars"></i>
                    </button>
                    <?php if (is_logged_in()): ?>
                        <!-- Ikon Pencarian (Hanya Ikon) di Samping Akun -->
                        <a href="<?= base_url('guru/search.php') ?>" class="nav-icon-btn <?= (isset($active_menu) && $active_menu === 'pencarian') ? 'is-active' : '' ?>" title="Cari Materi" aria-label="Cari Materi">
                            <i class="bi bi-search"></i>
                        </a>

                        <?php if (is_admin()): ?>
                            <a href="<?= base_url('admin/index.php') ?>" class="btn-pill btn-pill-outline btn-sm py-1 px-2 px-sm-3 text-nowrap text-decoration-none" title="Panel Admin">
                                <i class="bi bi-shield-lock"></i><span class="d-none d-sm-inline"> Panel Admin</span>
                            </a>
                        <?php endif; ?>

                        <div class="dropdown flex-shrink-0">
                            <button class="btn btn-white btn-sm dropdown-toggle border d-flex align-items-center gap-2 py-1 px-3 text-nowrap" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border-radius: var(--radius-pill); border-color: var(--border-subtle) !important;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:28px; height:28px; background-color: var(--color-accent-soft); color: var(--color-primary);">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                                <span class="fw-semibold text-dark text-truncate nav-user-name" style="max-width: 150px;"><?= e($current_user['nama']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" data-ui style="border-radius: var(--radius-md); border: 1px solid var(--border-subtle); box-shadow: 0 12px 32px rgba(14, 29, 46, 0.16);">
                                <li class="px-3 py-2 border-bottom">
                                    <p class="mb-0 fw-bold text-dark"><?= e($current_user['nama']) ?></p>
                                    <small class="text-muted"><?= e($current_user['email']) ?></small>
                                    <div>
                                        <span class="badge mt-1" style="background-color: var(--color-primary);">
                                            <?= is_admin() ? 'Administrator MGMP' : 'Guru Pengajar' ?>
                                        </span>
                                    </div>
                                </li>
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item py-2" href="<?= base_url('admin/index.php') ?>"><i class="bi bi-speedometer2 me-2 text-primary"></i> Dashboard Admin</a></li>
                                    <li><a class="dropdown-item py-2" href="<?= base_url('admin/topik.php') ?>"><i class="bi bi-journal-plus me-2 text-primary"></i> Kelola Topik & Berkas</a></li>
                                    <li><a class="dropdown-item py-2" href="<?= base_url('admin/users.php') ?>"><i class="bi bi-people me-2 text-primary"></i> Kelola Akun Guru</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item py-2" href="<?= base_url('guru/index.php') ?>"><i class="bi bi-collection me-2 text-primary"></i> Katalog Bahan Ajar</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <?php if (!$on_login_page): ?>
                            <a href="<?= base_url('auth/login.php') ?>" class="btn-pill btn-pill-primary btn-sm">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk ke Portal
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
        </div>
    </nav>

    <!-- Shell Layout: Area Konten -->
    <div class="app-shell px-xl-5 px-lg-4 px-3">
        <div class="app-content">
            <!-- Kontainer Pesan Notifikasi Global (hanya jika ada pesan) -->
            <?php $flash_html = render_flash(); if ($flash_html): ?>
            <div class="container mt-3">
                <?= $flash_html ?>
            </div>
            <?php endif; ?>

            <!-- Konten Utama Halaman Dimulai -->
            <main>
