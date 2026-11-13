<?php
/**
 * ==========================================================
 * Koneksi Basis Data PDO (MySQL)
 * ==========================================================
 * Menghubungkan aplikasi ke MySQL dengan prepared statement aman.
 * Menampilkan panduan bila koneksi gagal — detail teknis hanya
 * saat APP_DEBUG=true agar kredensial tidak bocor di produksi.
 */

require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    $errorMsg = $e->getMessage();
    $isLocalHost = in_array(DB_HOST, ['localhost', '127.0.0.1'], true);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Perhatian: Koneksi Database Belum Terhubung - <?= APP_NAME ?></title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
            body { background: #D9D9D8; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            .error-card { max-width: 680px; margin: 60px auto; border-radius: var(--radius-lg); border: none; border-top: 4px solid #2A4D88; box-shadow: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="card error-card">
                <div class="card-header bg-warning bg-opacity-25 border-0 pt-4 pb-3 text-center">
                    <i class="bi bi-database-fill-exclamation text-warning display-4"></i>
                    <h4 class="mt-2 text-dark font-weight-bold">Basis Data Belum Terhubung</h4>
                    <p class="text-muted mb-0">Aplikasi belum dapat terhubung ke MySQL.</p>
                </div>
                <div class="card-body p-4">
                    <?php if (APP_DEBUG): ?>
                    <div class="alert alert-light border">
                        <small class="text-danger d-block font-monospace">Pesan Sistem: <?= htmlspecialchars($errorMsg) ?></small>
                    </div>
                    <?php endif; ?>

                    <?php if ($isLocalHost): ?>
                    <h6 class="fw-bold mb-3"><i class="bi bi-tools text-primary me-2"></i>Langkah Mudah (XAMPP Lokal):</h6>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item border-0 ps-0">
                            <strong>Buka XAMPP Control Panel:</strong> Pastikan tombol <span class="badge bg-success">Start</span> pada baris <strong>MySQL</strong> sudah aktif.
                        </li>
                        <li class="list-group-item border-0 ps-0">
                            <strong>Buka phpMyAdmin:</strong> Kunjungi <a href="http://localhost/phpmyadmin" target="_blank" class="fw-bold">http://localhost/phpmyadmin</a>.
                        </li>
                        <li class="list-group-item border-0 ps-0">
                            <strong>Impor Database:</strong> Tab <em>Import</em>, pilih <span class="badge bg-secondary">database.sql</span>, lalu klik <em>Go</em>.
                        </li>
                    </ol>
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="http://localhost/phpmyadmin" target="_blank" class="btn btn-outline-primary">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka phpMyAdmin
                        </a>
                        <button onclick="window.location.reload();" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang Halaman
                        </button>
                    </div>
                    <?php else: ?>
                    <h6 class="fw-bold mb-3"><i class="bi bi-tools text-primary me-2"></i>Langkah Mudah (Hosting):</h6>
                    <ol class="list-group list-group-numbered mb-4">
                        <li class="list-group-item border-0 ps-0">
                            <strong>Buat database</strong> melalui panel hosting (cPanel/Plesk), catat nama database, user, dan password-nya.
                        </li>
                        <li class="list-group-item border-0 ps-0">
                            <strong>Impor <span class="badge bg-secondary">database.sql</span></strong> lewat phpMyAdmin panel hosting.
                        </li>
                        <li class="list-group-item border-0 ps-0">
                            <strong>Salin <span class="badge bg-secondary">.env.example</span> menjadi <span class="badge bg-secondary">.env</span> di root aplikasi</strong>, lalu isi <code>DB_HOST</code>, <code>DB_USER</code>, <code>DB_PASS</code>, <code>DB_NAME</code> sesuai kredensial hosting.
                        </li>
                    </ol>
                    <div class="d-flex justify-content-end pt-2">
                        <button onclick="window.location.reload();" class="btn btn-primary">
                            <i class="bi bi-arrow-clockwise me-1"></i> Muat Ulang Halaman
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
