    </main>
    <!-- Konten Utama Berakhir -->
        </div><!-- .app-content -->
    </div><!-- .app-shell -->

    <!-- Footer Modern dengan Palet #2A4D88 & #B1BBC8 -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-5 col-md-6">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="brand-icon-box" style="width: 38px; height: 38px; background: rgba(255,255,255,0.15); border: 1px solid rgba(175,203,233,0.3); color: #ffffff; display: flex; align-items: center; justify-content: center; border-radius: 10px; font-size: 1.1rem;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-white"><?= APP_NAME ?></h5>
                    </div>
                    <p class="small mb-3 pe-lg-4" style="color: rgba(217, 217, 216, 0.8); line-height: 1.6;">
                        Platform repositori bahan ajar, modul Kurikulum Merdeka, slide presentasi interaktif PPT, dan tautan referensi belajar untuk mendukung guru Informatika SMA dalam mengajar dengan mudah, terarah, dan percaya diri.
                    </p>
                    <span class="badge badge-footer px-3 py-2 rounded-pill font-monospace small">
                        Versi <?= APP_VERSION ?> • Kurikulum Merdeka Terpadu
                    </span>
                </div>

                <?php if (is_logged_in()): ?>
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-white mb-3">Jenjang Materi</h6>
                    <ul class="list-unstyled small mb-0 d-flex flex-column gap-2">
                        <li>
                            <a href="<?= base_url('guru/kelas.php?tingkat=10') ?>">
                                Informatika Kelas 10 (Fase E)
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('guru/kelas.php?tingkat=11') ?>">
                                Informatika Kelas 11 (Fase F)
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('guru/kelas.php?tingkat=12') ?>">
                                Informatika Kelas 12 (Fase F)
                            </a>
                        </li>
                        <li>
                            <a href="<?= base_url('guru/index.php') ?>">
                                Semua Katalog Pembelajaran
                            </a>
                        </li>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="<?= is_logged_in() ? 'col-lg-4' : 'col-lg-7' ?> col-md-12">
                    <h6 class="fw-bold text-white mb-3">Bantuan & Koordinator MGMP</h6>
                    <p class="small mb-3" style="color: rgba(217, 217, 216, 0.8); line-height: 1.6;">
                        Mengalami kendala saat mengunduh berkas atau mencari topik modul? Silakan hubungi koordinator tim Informatika di <?= APP_SCHOOL ?>.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <?php if (!is_logged_in()): ?>
                            <a href="<?= base_url('auth/login.php') ?>" class="btn-pill btn-pill-accent btn-sm">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk Akun Guru
                            </a>
                        <?php else: ?>
                            <span class="small px-3 py-2 rounded-pill d-inline-flex align-items-center gap-2" style="background: rgba(255, 255, 255, 0.12); color: var(--color-accent); border: 1px solid rgba(175,203,233,0.3);">
                                <i class="bi bi-person-check-fill"></i> Sesi Aktif: <?= e(current_user()['nama']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <hr class="my-4" style="border-color: rgba(177, 187, 200, 0.2);">

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small" style="color: rgba(217, 217, 216, 0.7);">
                <div>&copy; <?= date('Y') ?> <?= APP_SCHOOL ?>. Seluruh hak cipta dilindungi.</div>
                <div class="mt-2 mt-sm-0">Didesain ramah, modern, dan praktis untuk bapak/ibu guru Informatika SMA.</div>
            </div>
        </div>
    </footer>
    </div><!-- .app-main-wrap -->

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script Interaktif Aplikasi (versi=filemtime agar tidak terkena cache) -->
    <script src="<?= base_url('assets/js/main.js') ?>?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
