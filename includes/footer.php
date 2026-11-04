    </main>
    <!-- Konten Utama Berakhir -->
        </div><!-- .app-content -->
    </div><!-- .app-shell -->

    <!-- Footer: nama kembali selebar halaman (editorial-report) -->
    <footer class="footer-custom">
        <h2 class="footer-name">Portal Informatika<br>SMAN 5 Kota Ternate</h2>

        <div class="row gy-4">
            <div class="col-lg-5 col-md-6">
                <p class="footer-lead mb-3">
                    Repositori modul ajar, slide presentasi, dan referensi belajar untuk
                    guru Informatika SMA &mdash; Kurikulum Merdeka Fase E dan Fase F,
                    dikelola MGMP Informatika sekolah.
                </p>
                <?php if (is_logged_in()): ?>
                    <span class="footer-session">
                        <i class="bi bi-person-check-fill"></i>
                        Sesi aktif: <?= e(current_user()['nama']) ?>
                    </span>
                <?php else: ?>
                    <a href="<?= base_url('auth/login.php') ?>" class="footer-session">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk Akun Guru
                    </a>
                <?php endif; ?>
            </div>

            <?php if (is_logged_in()): ?>
            <div class="col-lg-3 col-md-6">
                <h6>Jenjang Materi</h6>
                <ul class="footer-links">
                    <li><a href="<?= base_url('guru/kelas.php?tingkat=10') ?>"><i class="bi bi-arrow-right me-1"></i>Informatika Kelas 10 (Fase E)</a></li>
                    <li><a href="<?= base_url('guru/kelas.php?tingkat=11') ?>"><i class="bi bi-arrow-right me-1"></i>Informatika Kelas 11 (Fase F)</a></li>
                    <li><a href="<?= base_url('guru/kelas.php?tingkat=12') ?>"><i class="bi bi-arrow-right me-1"></i>Informatika Kelas 12 (Fase F)</a></li>
                    <li><a href="<?= base_url('guru/index.php') ?>"><i class="bi bi-arrow-right me-1"></i>Semua Katalog Pembelajaran</a></li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="<?= is_logged_in() ? 'col-lg-4' : 'col-lg-7' ?> col-md-12">
                <h6>Bantuan &amp; Koordinator MGMP</h6>
                <p class="mb-3">
                    Kendala saat mengunduh berkas atau mencari topik modul? Hubungi
                    koordinator tim Informatika <?= APP_SCHOOL ?> di
                    <a href="mailto:mgmp@sma5.sch.id">mgmp@sma5.sch.id</a>.
                </p>
                <ul class="footer-links">
                    <li><a href="<?= base_url('guru/search.php') ?>"><i class="bi bi-arrow-right me-1"></i>Cari Materi</a></li>
                    <li><a href="<?= base_url('index.php') ?>#katalog-kelas"><i class="bi bi-arrow-right me-1"></i>Jenjang Kelas</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-meta">
            <div>&copy; <?= date('Y') ?> <?= APP_SCHOOL ?>. Seluruh hak cipta dilindungi.</div>
            <div>Versi <?= APP_VERSION ?> &middot; Kurikulum Merdeka Terpadu</div>
        </div>

        <p class="footer-credits">
            Kredit foto: &ldquo;Ternate (City), Indonesia (2010)&rdquo; oleh U.S. Foreign Service
            Liaison Officer Tom Weinz (public domain, Wikimedia Commons); &ldquo;Pulau Maitara and
            Pulau Tidore from Ternate&rdquo; oleh Fabio Achilli (CC BY 2.0, Wikimedia Commons);
            video &ldquo;A view around Kalamata Fort, Ternate&rdquo; oleh Oceanmuse (CC BY 4.0,
            Wikimedia Commons). Foto kegiatan: dokumentasi SMA Negeri 5 Kota Ternate.
        </p>
    </footer>
    </div><!-- .app-main-wrap -->

    <!-- Bootstrap 5.3 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script Interaktif Aplikasi (versi=filemtime agar tidak terkena cache) -->
    <script src="<?= base_url('assets/js/main.js') ?>?v=<?= filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
</body>
</html>
