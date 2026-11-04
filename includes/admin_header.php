<?php
/**
 * Header Khusus Panel Administrasi (Admin MGMP)
 * Menggunakan Palet Warna: #2A4D88, #B1BBC8, #D9D9D8
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// Pastikan hanya role admin yang dapat mengakses
require_admin();

$page_title = isset($page_title) ? $page_title . ' - Panel Admin' : 'Panel Pengelola - ' . APP_NAME;
$current_user = current_user();
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

    <!-- Favicon Inline (menghindari request /favicon.ico 404) -->
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232A4D88'/%3E%3Cpath d='M16 8 5 13l11 5 11-5-11-5Zm-6 8.5V21c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5l-6 2.7-6-2.7Z' fill='%23fff'/%3E%3C/svg%3E">

    <!-- Google Fonts: dimuat non-blocking (1 request, tanpa menunda render) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" onload="this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </noscript>

    <!-- Bootstrap 5.3 & Icons (preconnect agar DNS+TLS sekali saja) -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.5.13/dist/cropper.min.css">

    <!-- Gaya Kustom Palet #2A4D88, #B1BBC8, #D9D9D8 (versi=filemtime agar tidak terkena cache) -->
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
    <script>document.documentElement.classList.add('js-anim');</script>
</head>
<body style="background-color: var(--bg-body);">

    <!-- Navbar Atas Admin -->
    <header class="navbar navbar-expand-lg sticky-top px-3 py-2 shadow-sm" style="background-color: var(--color-primary); border-bottom: 1px solid rgba(177, 187, 200, 0.2);">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center gap-2 text-white" href="<?= base_url('admin/index.php') ?>">
                <div class="brand-icon-box" style="width:36px; height:36px; background:#ffffff; border: 1px solid rgba(175,203,233,0.3);">
                    <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5" style="width:100%;height:100%;object-fit:contain;padding:2px;box-sizing:border-box;">
                </div>
                <div class="d-flex flex-column min-width-0">
                    <span class="fs-6 fw-bold lh-1 text-white" style="font-size: 0.95rem;">Admin Bahan Ajar</span>
                    <small class="d-none d-sm-block" style="color: var(--color-accent); font-size: 0.72rem;">Pengelola Konten Informatika • <?= APP_SCHOOL ?></small>
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                <!-- Tombol Mode Gelap / Terang -->
                <button class="theme-toggle btn btn-sm d-inline-flex align-items-center justify-content-center" type="button" aria-label="Aktifkan mode gelap" title="Mode Gelap / Terang" style="width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.12); color: #ffffff; border: 1px solid rgba(183,215,234,0.3);">
                    <i class="bi bi-moon-stars"></i>
                </button>

                <a href="<?= base_url('guru/index.php') ?>" class="btn-pill btn-pill-outline btn-sm py-1 px-3 d-none d-md-inline-flex text-white border-white">
                    <i class="bi bi-eye"></i> Pratinjau Tampilan Guru
                </a>

                <div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle d-flex align-items-center gap-2 px-2 px-sm-3 py-1 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background: rgba(255,255,255,0.12); color: #ffffff; border: 1px solid rgba(175,203,233,0.3);">
                        <i class="bi bi-person-circle fs-6" style="color: var(--color-accent);"></i>
                        <span class="text-white nav-user-name"><?= e($current_user['nama']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="border-radius: 12px; border: 1px solid var(--border-subtle);">
                        <li class="px-3 py-2 border-bottom">
                            <span class="d-block fw-bold small text-dark"><?= e($current_user['nama']) ?></span>
                            <span class="d-block text-muted small"><?= e($current_user['email']) ?></span>
                            <span class="badge rounded-pill mt-1" style="background-color: var(--color-primary); color: #ffffff;">Administrator</span>
                        </li>
                        <li><a class="dropdown-item py-2" href="<?= base_url('guru/index.php') ?>"><i class="bi bi-book me-2" style="color: var(--color-primary);"></i> Halaman Bahan Ajar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="<?= base_url('auth/logout.php') ?>"><i class="bi bi-box-arrow-right me-2"></i> Keluar (Logout)</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigasi Admin -->
            <nav class="col-lg-2 col-md-3 d-none d-md-block admin-sidebar py-3">
                <div class="position-sticky" style="top: 80px;">
                    <div class="px-3 mb-3 text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.08em; color: var(--color-primary);">
                        Menu Pengelola
                    </div>
                    <ul class="nav flex-column mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?= (!isset($active_admin_menu) || $active_admin_menu === 'dashboard') ? 'active' : '' ?>" href="<?= base_url('admin/index.php') ?>">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (isset($active_admin_menu) && $active_admin_menu === 'kelas') ? 'active' : '' ?>" href="<?= base_url('admin/kelas.php') ?>">
                                <i class="bi bi-building"></i> Kelola Kelas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (isset($active_admin_menu) && $active_admin_menu === 'topik') ? 'active' : '' ?>" href="<?= base_url('admin/topik.php') ?>">
                                <i class="bi bi-journal-text"></i> Kelola Topik & Materi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (isset($active_admin_menu) && $active_admin_menu === 'hero') ? 'active' : '' ?>" href="<?= base_url('admin/hero.php') ?>">
                                <i class="bi bi-images"></i> Kelola Foto Beranda
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (isset($active_admin_menu) && $active_admin_menu === 'users') ? 'active' : '' ?>" href="<?= base_url('admin/users.php') ?>">
                                <i class="bi bi-people"></i> Kelola Akun Pengguna
                                <?php
                                $total_usr = 0;
                                try {
                                    $total_usr = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                                } catch (Exception $e) {}
                                if ($total_usr > 0): ?>
                                    <span class="badge rounded-pill ms-auto" style="background-color: var(--color-primary); color: #ffffff;"><?= $total_usr ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>

                    <div class="px-3 mb-2 text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 0.08em; color: var(--text-muted);">
                        Pintasan
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-muted" href="<?= base_url('guru/index.php') ?>">
                                <i class="bi bi-box-arrow-up-right"></i> Lihat Portal Guru
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="<?= base_url('auth/logout.php') ?>">
                                <i class="bi bi-box-arrow-right"></i> Keluar
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Area Konten Utama Admin -->
            <main class="col-lg-10 col-md-9 ms-sm-auto px-md-4 py-4">
                <!-- Flash Notification -->
                <?= render_flash() ?>
