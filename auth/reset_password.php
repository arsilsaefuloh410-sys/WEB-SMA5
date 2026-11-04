<?php
/**
 * Halaman Reset Kata Sandi
 * Dibuka melalui tautan yang dikirim ke email. Token divalidasi
 * sebelum pengguna dapat membuat kata sandi baru.
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
$user_email = '';

/**
 * Memvalidasi token reset: ada di database, belum kedaluwarsa, belum dipakai.
 * @return array|null Data baris token bila valid, selain itu null
 */
function find_valid_reset_token(PDO $pdo, string $token): ?array {
    if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
        return null;
    }
    $token_hash = hash('sha256', $token);
    // Bandingkan dengan waktu PHP (bukan NOW() database) agar konsisten
    // meskipun jam server database berbeda dengan aplikasi.
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > ? LIMIT 1");
    $stmt->execute([$token_hash, $now]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// --- Langkah 1: Validasi token dari tautan email ---
$reset_row = find_valid_reset_token($pdo, $token);

if (!$reset_row) {
    $error_message = 'Tautan reset kata sandi tidak valid, sudah kedaluwarsa, atau sudah digunakan. '
        . 'Silakan minta tautan baru melalui halaman Lupa Kata Sandi.';
} else {
    $token_valid = true;
    $user_email = $reset_row['email'];

    // --- Langkah 2: Proses penyimpanan kata sandi baru ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        if ($password === '' || $password_confirm === '') {
            $error_message = 'Silakan isi kata sandi baru dan konfirmasinya.';
        } elseif (!validate_password_strength($password)['valid']) {
            $error_message = validate_password_strength($password)['message'];
        } elseif ($password !== $password_confirm) {
            $error_message = 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            // Perbarui kata sandi pengguna
            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->execute([$hashed, $user_email]);

            // Tandai token sudah dipakai (hapus agar tidak bisa dipakai ulang)
            $del = $pdo->prepare("DELETE FROM password_resets WHERE id = ?");
            $del->execute([$reset_row['id']]);

            set_flash('success', 'Kata sandi Anda berhasil diperbarui. Silakan masuk menggunakan kata sandi baru.');
            header('Location: ' . base_url('auth/login.php'));
            exit;
        }
    }
}

$page_title = 'Reset Kata Sandi';
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
    <meta name="description" content="Buat kata sandi baru untuk akun portal bahan ajar Informatika SMAN 5 Kota Ternate.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232A4D88'/%3E%3Cpath d='M16 8 5 13l11 5 11-5-11-5Zm-6 8.5V21c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5l-6 2.7-6-2.7Z' fill='%23fff'/%3E%3C/svg%3E">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
</head>
<body class="login-body">

<div class="login-split">

    <!-- Panel Kiri: Sambutan -->
    <aside class="login-welcome">
        <div class="login-welcome-content">
            <div class="login-logo">
                <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5">
            </div>
            <h1>Portal Bahan Ajar Informatika</h1>
            <p>Modul, slide, dan referensi untuk guru Informatika SMA Negeri 5 Kota Ternate.</p>
        </div>
    </aside>

    <!-- Panel Kanan: Form Reset Kata Sandi -->
    <main class="login-panel">
        <div class="login-form-wrap">

            <h2 class="login-heading">Sandi Baru</h2>
            <p class="login-sub"><?= $token_valid ? 'Buat kata sandi baru untuk akun <strong>' . e($user_email) . '</strong>' : 'Tautan tidak dapat digunakan' ?></p>

            <?php if (!empty($error_message)): ?>
                <div class="alert login-alert d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= $error_message ?></div>
                </div>
            <?php endif; ?>

            <?php if ($token_valid): ?>
                <form action="" method="POST" autocomplete="off">
                    <input type="hidden" name="token" value="<?= e($token) ?>">

                    <!-- Kata Sandi Baru -->
                    <div class="login-field">
                        <i class="bi bi-lock field-icon"></i>
                        <input type="password" class="login-input has-toggle" id="password" name="password" placeholder="Kata sandi baru (min. 8 karakter)" aria-label="Kata sandi baru" minlength="8" maxlength="72" autocomplete="new-password" required autofocus>
                        <button class="toggle-pw" type="button" id="togglePasswordBtn" title="Tampilkan/sembunyikan kata sandi">SHOW</button>
                    </div>

                    <!-- Konfirmasi Kata Sandi -->
                    <div class="login-field">
                        <i class="bi bi-lock-fill field-icon"></i>
                        <input type="password" class="login-input" id="password_confirm" name="password_confirm" placeholder="Ulangi kata sandi baru" aria-label="Ulangi kata sandi baru" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn-signin">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Kata Sandi Baru
                    </button>
                </form>

                <div class="login-note">
                    <i class="bi bi-info-circle me-1"></i>
                    Kata sandi baru minimal 8 karakter dengan huruf &amp; angka. Setelah disimpan, gunakan kata sandi baru ini untuk masuk ke akun Anda.
                </div>
            <?php endif; ?>

            <div class="login-back">
                <a href="<?= base_url('auth/login.php') ?>"><i class="bi bi-arrow-left me-1"></i>Kembali ke halaman Login</a>
                <?php if (!$token_valid): ?>
                    &nbsp;&middot;&nbsp;
                    <a href="<?= base_url('auth/forgot_password.php') ?>">Minta tautan baru</a>
                <?php endif; ?>
            </div>

        </div>
    </main>

</div>

<?php if ($token_valid): ?>
<script>
document.querySelectorAll('.toggle-pw').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = document.getElementById('password');
        if (input.type === 'password') {
            input.type = 'text';
            this.textContent = 'HIDE';
        } else {
            input.type = 'password';
            this.textContent = 'SHOW';
        }
    });
});
</script>
<?php endif; ?>

</body>
</html>
