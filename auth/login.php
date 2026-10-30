<?php
/**
 * Halaman Masuk (Login)
 * Pilih peran (Guru / Admin) + akun demo, tampilan tetap simpel.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, langsung alihkan sesuai peran
if (is_logged_in()) {
    if (is_admin()) {
        header('Location: ' . base_url('admin/index.php'));
    } else {
        header('Location: ' . base_url('index.php'));
    }
    exit;
}

$error_message = '';
$email_input = '';
$selected_role = $_GET['role'] ?? 'guru';
if (!in_array($selected_role, ['guru', 'admin'])) {
    $selected_role = 'guru';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selected_role = $_POST['role'] ?? 'guru';
    if (!in_array($selected_role, ['guru', 'admin'])) {
        $selected_role = 'guru';
    }
    $email_input = $email;

    // Proteksi brute-force: kunci sementara bila terlalu banyak percobaan gagal
    $lock_seconds = get_login_lockout_seconds($pdo, $email);
    if ($lock_seconds > 0) {
        $mins = (int)ceil($lock_seconds / 60);
        $error_message = 'Terlalu banyak percobaan login gagal. Akun dikunci sementara selama ±' . $mins
            . ' menit. Silakan coba lagi nanti atau hubungi administrator.';
    } elseif (empty($email) || empty($password)) {
        $error_message = 'Silakan masukkan email dan kata sandi Anda.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error_message = 'Akun Anda masih menunggu persetujuan administrator. Silakan hubungi administrator sekolah untuk mengaktifkan akun Anda.';
            } elseif ($user['role'] !== $selected_role) {
                $error_message = 'Akun ini terdaftar sebagai ' . ($user['role'] === 'admin' ? 'Administrator' : 'Guru Pengajar') . '. Silakan pilih ' . ($user['role'] === 'admin' ? 'Administrator' : 'Guru Pengajar') . '.';
            } else {
                // Berhasil login
                clear_login_attempts($pdo, $email);
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user'] = [
                    'id'    => $user['id'],
                    'nama'  => $user['nama'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ];

                if ($user['role'] === 'admin') {
                    set_flash('success', 'Selamat datang kembali, <strong>' . e($user['nama']) . '</strong>.');
                    header('Location: ' . base_url('admin/index.php'));
                } else {
                    set_flash('success', 'Selamat datang, <strong>' . e($user['nama']) . '</strong>.');
                    header('Location: ' . base_url('index.php'));
                }
                exit;
            }
        } else {
            record_login_attempt($pdo, $email);
            $error_message = 'Email atau kata sandi tidak sesuai.';
        }
    }
}

$page_title = 'Masuk - Portal Bahan Ajar';
$is_admin_tab = $selected_role === 'admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="Masuk ke portal bahan ajar Informatika SMAN 5 Kota Ternate.">
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
        <span class="login-orb orb-1"></span>
        <span class="login-orb orb-2"></span>
        <span class="login-orb orb-3"></span>
        <span class="login-orb orb-4"></span>

        <div class="login-welcome-content">
            <div class="login-logo">
                <img src="<?= base_url('assets/img/logo-sman5.png') ?>" alt="Logo SMAN 5">
            </div>
            <h1>WELCOME</h1>
            <h2>Portal Bahan Ajar Informatika</h2>
        </div>
    </aside>

    <!-- Panel Kanan: Form Masuk -->
    <main class="login-panel">
        <div class="login-form-wrap">

            <h3 class="login-heading">Login</h3>
            <p class="login-sub">Silakan masuk menggunakan akun Anda</p>

            <?php if (!empty($error_message)): ?>
                <div class="alert login-alert d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <!-- Pilihan Peran -->
            <div class="d-flex gap-2 mb-3 p-1 rounded-pill role-tabs">
                <button type="button" id="tabGuru" class="btn rounded-pill flex-fill py-2 fw-semibold small border-0 role-tab <?= !$is_admin_tab ? 'text-white' : 'text-muted' ?>" style="background: <?= !$is_admin_tab ? '#2A4D88' : 'transparent' ?>;" onclick="selectRole('guru')">
                    <i class="bi bi-person-workspace me-1"></i> Guru
                </button>
                <button type="button" id="tabAdmin" class="btn rounded-pill flex-fill py-2 fw-semibold small border-0 role-tab <?= $is_admin_tab ? 'text-white' : 'text-muted' ?>" style="background: <?= $is_admin_tab ? '#2A4D88' : 'transparent' ?>;" onclick="selectRole('admin')">
                    <i class="bi bi-shield-lock me-1"></i> Admin
                </button>
            </div>

            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="role" id="selectedRoleInput" value="<?= e($selected_role) ?>">

                <!-- Email -->
                <div class="login-field">
                    <i class="bi bi-person field-icon"></i>
                    <input type="email" class="login-input" id="email" name="email" value="<?= e($email_input) ?>" placeholder="Alamat email" aria-label="Alamat email" required autofocus>
                </div>

                <!-- Kata Sandi -->
                <div class="login-field">
                    <i class="bi bi-lock field-icon"></i>
                    <input type="password" class="login-input has-toggle" id="password" name="password" placeholder="Kata sandi" aria-label="Kata sandi" required>
                    <button class="toggle-pw" type="button" id="togglePasswordBtn" title="Tampilkan/sembunyikan kata sandi">SHOW</button>
                </div>

                <div class="text-end mb-1" style="margin-top: -0.35rem;">
                    <a href="<?= base_url('auth/forgot_password.php') ?>" class="small fw-semibold text-decoration-none" style="color:#2A4D88;">Lupa kata sandi?</a>
                </div>

                <button type="submit" class="btn-signin">Login</button>
            </form>

            <!-- Akun Demo -->
            <div class="login-divider"><span>atau</span></div>
            <div class="demo-label"><i class="bi bi-lightning-charge-fill me-1" style="color:#2A4D88;"></i>Masuk cepat dengan akun demo</div>
            <div class="demo-grid">
                <button type="button" class="btn-demo" onclick="selectRole('guru'); fillDemo('guru@sma5.sch.id', 'guru123');">
                    <i class="bi bi-person-fill me-1"></i> Guru
                </button>
                <button type="button" class="btn-demo" onclick="selectRole('admin'); fillDemo('admin@sma5.sch.id', 'admin123');">
                    <i class="bi bi-shield-lock-fill me-1"></i> Admin
                </button>
            </div>

            <p class="login-register">
                Belum punya akun guru? <a href="<?= base_url('auth/register.php') ?>">Daftar di sini</a>
            </p>

        </div>
    </main>

</div>

<script>
function selectRole(role) {
    document.getElementById('selectedRoleInput').value = role;
    const tabGuru = document.getElementById('tabGuru');
    const tabAdmin = document.getElementById('tabAdmin');

    if (role === 'admin') {
        tabAdmin.style.background = '#2A4D88';
        tabAdmin.classList.add('text-white');
        tabAdmin.classList.remove('text-muted');
        tabGuru.style.background = 'transparent';
        tabGuru.classList.remove('text-white');
        tabGuru.classList.add('text-muted');
    } else {
        tabGuru.style.background = '#2A4D88';
        tabGuru.classList.add('text-white');
        tabGuru.classList.remove('text-muted');
        tabAdmin.style.background = 'transparent';
        tabAdmin.classList.remove('text-white');
        tabAdmin.classList.add('text-muted');
    }
}

function fillDemo(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
}

document.getElementById('togglePasswordBtn').addEventListener('click', function () {
    const input = document.getElementById('password');
    if (input.type === 'password') {
        input.type = 'text';
        this.textContent = 'HIDE';
    } else {
        input.type = 'password';
        this.textContent = 'SHOW';
    }
});
</script>

</body>
</html>
