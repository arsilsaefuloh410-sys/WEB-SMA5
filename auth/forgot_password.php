<?php
/**
 * Halaman Lupa Kata Sandi
 * Pengguna memasukkan email terdaftar, lalu link reset dikirim via email.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

// Jika sudah login, langsung alihkan sesuai peran
if (is_logged_in()) {
    header('Location: ' . (is_admin() ? base_url('admin/index.php') : base_url('index.php')));
    exit;
}

$error_message = '';
$success_message = '';
$debug_reset_url = '';
$email_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $email_input = $email;

    if ($email === '') {
        $error_message = 'Silakan masukkan alamat email Anda.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format alamat email tidak valid.';
    } else {
        // Cari pengguna berdasarkan email
        $stmt = $pdo->prepare("SELECT id, nama, email, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Buat token acak (hanya disimpan sebagai hash SHA-256)
            $token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $token);
            // Waktu dibuat/diambil dari PHP agar konsisten dengan validasi (jam DB bisa berbeda)
            $now = date('Y-m-d H:i:s');
            $expires_at = date('Y-m-d H:i:s', time() + PASSWORD_RESET_EXPIRY_MINUTES * 60);

            // Batalkan token lama untuk email ini (hanya 1 token aktif)
            $del = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->execute([$email]);

            // Simpan token baru
            $ins = $pdo->prepare("INSERT INTO password_resets (email, token_hash, expires_at, used, created_at) VALUES (?, ?, ?, 0, ?)");
            $ins->execute([$email, $token_hash, $expires_at, $now]);

            // Susun link reset absolut (untuk email)
            $reset_url = app_url('auth/reset_password.php?token=' . urlencode($token));

            // Kirim email
            $subject = 'Reset Kata Sandi - ' . APP_NAME;
            $html = password_reset_email_html($user['nama'], $reset_url, PASSWORD_RESET_EXPIRY_MINUTES);
            $text = "Halo {$user['nama']},\n\n"
                . "Kami menerima permintaan pengaturan ulang kata sandi untuk akun Anda.\n"
                . "Buat kata sandi baru melalui link berikut (berlaku " . PASSWORD_RESET_EXPIRY_MINUTES . " menit):\n"
                . $reset_url . "\n\n"
                . "Jika Anda tidak meminta ini, abaikan email ini.\n"
                . APP_NAME;

            $result = send_mail($email, $subject, $html, $text);

            if ($result['status']) {
                $success_message = 'Tautan pengaturan ulang kata sandi telah dikirim ke <strong>' . e($email) . '</strong>. '
                    . 'Silakan periksa kotak masuk (inbox) atau folder spam/email sampah Anda. '
                    . 'Tautan berlaku selama ' . PASSWORD_RESET_EXPIRY_MINUTES . ' menit.';
            } elseif (APP_DEBUG) {
                // Pengembangan lokal: tampilkan link langsung bila email gagal dikirim
                $debug_reset_url = $reset_url;
                $success_message = 'Pengiriman email gagal (konfigurasi SMTP belum diisi di berkas .env). '
                    . 'Karena mode debug aktif, tautan reset ditampilkan di bawah ini.';
            } else {
                $error_message = 'Tautan reset gagal dikirim karena kesalahan server. Silakan coba lagi nanti atau hubungi administrator.';
            }
        } else {
            // Pesan sukses generik agar tidak membocorkan status email (keamanan)
            $success_message = 'Jika alamat email tersebut terdaftar di sistem kami, tautan pengaturan ulang kata sandi '
                . 'sudah dikirim ke inbox Anda. Silakan periksa email (termasuk folder spam). '
                . 'Tautan berlaku selama ' . PASSWORD_RESET_EXPIRY_MINUTES . ' menit.';
        }
    }
}

$page_title = 'Lupa Kata Sandi';
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
    <meta name="description" content="Atur ulang kata sandi akun portal bahan ajar Informatika SMAN 5 Kota Ternate.">
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

    <!-- Panel Kanan: Form Lupa Kata Sandi -->
    <main class="login-panel">
        <div class="login-form-wrap">

            <h2 class="login-heading">Lupa Sandi</h2>
            <p class="login-sub">Masukkan email terdaftar, kami kirimkan tautan reset kata sandi Anda</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert login-alert d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert login-alert login-alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-envelope-check-fill me-2"></i>
                    <div><?= $success_message ?></div>
                </div>

                <?php if (!empty($debug_reset_url)): ?>
                    <div class="login-note mb-3">
                        <strong>Tautan Reset (Debug):</strong><br>
                        <a href="<?= e($debug_reset_url) ?>" style="word-break:break-all;"><?= e($debug_reset_url) ?></a>
                    </div>
                <?php endif; ?>

                <div class="login-back">
                    <a href="<?= base_url('auth/login.php') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke halaman Login</a>
                </div>
            <?php else: ?>
                <form action="" method="POST" autocomplete="off">
                    <?= csrf_field() ?>
                    <!-- Email -->
                    <div class="login-field">
                        <i class="bi bi-envelope field-icon"></i>
                        <input type="email" class="login-input" id="email" name="email" value="<?= e($email_input) ?>" placeholder="Alamat email terdaftar" aria-label="Alamat email" required autofocus>
                    </div>

                    <button type="submit" class="btn-signin">
                        <i class="bi bi-send me-1"></i> Kirim Tautan Reset
                    </button>
                </form>

                <div class="login-note">
                    <i class="bi bi-shield-lock me-1"></i>
                    Tautan reset hanya berlaku selama <strong><?= PASSWORD_RESET_EXPIRY_MINUTES ?> menit</strong> dan hanya dapat digunakan satu kali. Jika Anda tidak memiliki akun, silakan daftar terlebih dahulu.
                </div>

                <div class="login-back">
                    <a href="<?= base_url('auth/login.php') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke halaman Login</a>
                </div>
            <?php endif; ?>

        </div>
    </main>

</div>

</body>
</html>
