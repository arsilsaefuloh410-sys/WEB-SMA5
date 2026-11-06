<?php
/**
 * Halaman Verifikasi Email Pendaftaran (Langkah 2)
 * Dibuka melalui tautan yang dikirim ke email pendaftar. Token divalidasi,
 * lalu akun guru dibuat berstatus 'pending' menunggu persetujuan administrator.
 * Konfirmasi dilakukan lewat tombol (POST) agar tautan tidak terkonsumsi
 * oleh pemuat tautan otomatis milik penyedia email.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, langsung alihkan sesuai peran
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? base_url('admin/index.php') : base_url('index.php')));
    exit;
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$error_message = '';
$success_message = '';
$token_valid = false;
$reg_email = '';
$reg_nama = '';

/**
 * Memvalidasi token verifikasi pendaftaran: ada di database,
 * belum kedaluwarsa, dan belum dipakai.
 * @return array|null Data baris token bila valid, selain itu null
 */
function find_valid_registration_token(PDO $pdo, string $token): ?array {
    if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
        return null;
    }
    $token_hash = hash('sha256', $token);
    // Bandingkan dengan waktu PHP (bukan NOW() database) agar konsisten
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM registration_verifications WHERE token_hash = ? AND used = 0 AND expires_at > ? LIMIT 1");
    $stmt->execute([$token_hash, $now]);
    $row = $stmt->fetch();
    return $row ?: null;
}

$reg_row = find_valid_registration_token($pdo, $token);

if (!$reg_row) {
    $error_message = 'Tautan verifikasi tidak valid, sudah kedaluwarsa, atau sudah digunakan. '
        . 'Silakan mendaftar ulang melalui halaman pendaftaran untuk mendapatkan tautan baru.';
} else {
    $token_valid = true;
    $reg_email = $reg_row['email'];
    $reg_nama = $reg_row['nama'];

    // --- Proses konfirmasi: buat akun berstatus pending ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        require_csrf();
        // Cek apakah email sudah menjadi akun (misal admin sudah membuat langsung)
        $cek = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1");
        $cek->execute([$reg_email]);
        if ($cek->fetch()) {
            $pdo->prepare("DELETE FROM registration_verifications WHERE id = ?")->execute([$reg_row['id']]);
            set_flash('warning', 'Email <strong>' . e($reg_email) . '</strong> sudah terdaftar sebagai akun. Silakan masuk atau gunakan Lupa Kata Sandi.');
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }

        try {
            // Akun dibuat dengan status 'pending': belum bisa masuk sebelum disetujui admin
            // Waktu diambil dari PHP (bukan NOW() database) agar konsisten dengan tabel lain
            $ins = $pdo->prepare("
                INSERT INTO users (nama, email, password, role, status, created_at)
                VALUES (?, ?, ?, 'guru', 'pending', ?)
            ");
            $ins->execute([$reg_row['nama'], $reg_row['email'], $reg_row['password_hash'], date('Y-m-d H:i:s')]);

            // Token dikonsumsi: hapus baris verifikasi
            $del = $pdo->prepare("DELETE FROM registration_verifications WHERE id = ?");
            $del->execute([$reg_row['id']]);

            $token_valid = false;
            $success_message = 'Email berhasil diverifikasi dan akun <strong>' . e($reg_email) . '</strong> telah dibuat. '
                . 'Akun Anda berstatus <strong>menunggu persetujuan administrator</strong> â€” '
                . 'Anda baru dapat masuk setelah akun diaktifkan. Silakan hubungi administrator sekolah.';
        } catch (PDOException $ex) {
            error_log('[REGISTER VERIFY ERROR] ' . $ex->getMessage());
            $error_message = 'Terjadi kesalahan saat menyimpan akun Anda. Silakan coba lagi atau hubungi administrator.';
        }
    }
}

$page_title = 'Verifikasi Email Pendaftaran';
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
    <title><?= e($page_title) ?> - Portal Bahan Ajar</title>
    <meta name="description" content="Verifikasi email pendaftaran akun guru portal bahan ajar Informatika SMAN 5 Kota Ternate.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232A4D88'/%3E%3Cpath d='M16 8 5 13l11 5 11-5-11-5Zm-6 8.5V21c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5l-6 2.7-6-2.7Z' fill='%23fff'/%3E%3C/svg%3E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>?v=<?= @filemtime(__DIR__ . '/../assets/css/auth.css') ?>">
</head>
<body class="login-body">

<div class="login-split">

    <!-- Panel Kiri: Sambutan -->
    <aside class="login-welcome">
        <div class="login-welcome-content">
            <div class="login-logo">
                <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5">
            </div>
            <h1>Welcome</h1>
            <p>Portal Bahan Ajar Informatika</p>
        </div>
    </aside>

    <!-- Panel Kanan: Verifikasi Email -->
    <main class="login-panel">
        <div class="login-form-wrap">

            <h2 class="login-heading">Verifikasi Email</h2>
            <p class="login-sub">Konfirmasi kepemilikan email untuk menyelesaikan pendaftaran</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert login-alert d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert login-alert login-alert-success d-flex align-items-start mb-3" role="alert">
                    <i class="bi bi-patch-check-fill me-2 mt-1"></i>
                    <div><?= $success_message ?></div>
                </div>
            <?php elseif ($token_valid): ?>
                <div class="alert alert-light border d-flex align-items-start mb-3" role="alert">
                    <i class="bi bi-envelope-paper me-2 mt-1 text-primary"></i>
                    <div class="small">
                        Anda akan mendaftarkan akun guru dengan:<br>
                        <strong><?= e($reg_nama) ?></strong><br>
                        <strong><?= e($reg_email) ?></strong>
                    </div>
                </div>

                <form action="" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <button type="submit" class="btn-signin">
                        <i class="bi bi-patch-check me-1"></i> Ya, Verifikasi Email Ini
                    </button>
                </form>

                <div class="login-note">
                    <i class="bi bi-info-circle me-1"></i>
                    Setelah diverifikasi, akun Anda dibuat dengan status <strong>menunggu persetujuan administrator</strong>.
                    Anda baru dapat masuk setelah administrator mengaktifkan akun Anda.
                </div>
            <?php endif; ?>

            <div class="login-back">
                <a href="<?= base_url('auth/login.php') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke halaman Login</a>
                <?php if (!$token_valid && empty($success_message)): ?>
                    &nbsp;&middot;&nbsp;
                    <a href="<?= base_url('auth/register.php') ?>">Daftar ulang</a>
                <?php endif; ?>
            </div>

        </div>
    </main>

</div>

</body>
</html>
