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

    if (empty($email) || empty($password)) {
        $error_message = 'Silakan masukkan email dan kata sandi Anda.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error_message = 'Akun Anda berstatus nonaktif. Silakan hubungi Administrator untuk aktivasi.';
            } elseif ($user['role'] !== $selected_role) {
                $error_message = 'Akun ini terdaftar sebagai ' . ($user['role'] === 'admin' ? 'Administrator' : 'Guru Pengajar') . '. Silakan pilih ' . ($user['role'] === 'admin' ? 'Administrator' : 'Guru Pengajar') . '.';
            } else {
                // Berhasil login
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
            $error_message = 'Email atau kata sandi tidak sesuai.';
        }
    }
}

$page_title = 'Masuk - Portal Bahan Ajar';
require_once __DIR__ . '/../includes/header.php';
$is_admin_tab = $selected_role === 'admin';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">

            <div class="text-center mb-4">
                <div class="brand-icon-box mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="bi bi-mortarboard-fill text-white"></i>
                </div>
                <h4 class="fw-bold mb-1" style="color: var(--color-primary);">Masuk</h4>
                <p class="text-muted small mb-0">Portal Bahan Ajar Informatika SMA 5</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-3 rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div class="small"><?= e($error_message) ?></div>
                </div>
            <?php endif; ?>

            <div class="card card-modern shadow-sm border-0 bg-white p-4" style="border-radius: var(--radius-lg); border: 1px solid var(--border-subtle) !important;">

                <!-- Pilihan Peran -->
                <div class="d-flex gap-2 mb-4 p-1 rounded-pill mx-auto" style="background: var(--color-ice); max-width: 320px;">
                    <button type="button" id="tabGuru" class="btn rounded-pill flex-fill py-2 fw-semibold small border-0 <?= !$is_admin_tab ? 'text-white' : 'text-muted' ?>" style="background: <?= !$is_admin_tab ? 'var(--color-primary)' : 'transparent' ?>; transition: all 0.2s ease;" onclick="selectRole('guru')">
                        <i class="bi bi-person-workspace me-1"></i> Guru
                    </button>
                    <button type="button" id="tabAdmin" class="btn rounded-pill flex-fill py-2 fw-semibold small border-0 <?= $is_admin_tab ? 'text-white' : 'text-muted' ?>" style="background: <?= $is_admin_tab ? 'var(--color-primary)' : 'transparent' ?>; transition: all 0.2s ease;" onclick="selectRole('admin')">
                        <i class="bi bi-shield-lock me-1"></i> Admin
                    </button>
                </div>

                <form action="" method="POST" autocomplete="off">
                    <input type="hidden" name="role" id="selectedRoleInput" value="<?= e($selected_role) ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small" style="color: var(--color-primary);">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email_input) ?>" placeholder="nama@sma5.sch.id" required autofocus style="border-radius: 10px; padding: 0.65rem 0.9rem;">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label fw-semibold small" style="color: var(--color-primary);">Kata Sandi</label>
                        <div class="position-relative">
                            <input type="password" class="form-control pe-5" id="password" name="password" placeholder="Masukkan kata sandi" required style="border-radius: 10px; padding: 0.65rem 2.5rem 0.65rem 0.9rem;">
                            <button class="btn position-absolute top-50 end-0 translate-middle-y border-0 text-muted" type="button" id="togglePasswordBtn" title="Tampilkan/sembunyikan" style="background: transparent;">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-pill btn-pill-primary w-100 justify-content-center py-2">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>
                </form>

                <!-- Akun Demo -->
                <div class="mt-4 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                    <div class="small text-muted text-center mb-2"><i class="bi bi-lightning-charge-fill me-1" style="color: var(--color-accent-hover);"></i>Akun Demo</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light border flex-fill py-2 rounded-3 fw-semibold" onclick="selectRole('guru'); fillDemo('guru@sma5.sch.id', 'guru123');">
                            <i class="bi bi-person-fill text-primary me-1"></i> Guru
                        </button>
                        <button type="button" class="btn btn-sm btn-light border flex-fill py-2 rounded-3 fw-semibold" onclick="selectRole('admin'); fillDemo('admin@sma5.sch.id', 'admin123');">
                            <i class="bi bi-shield-lock-fill me-1" style="color: var(--color-primary);"></i> Admin
                        </button>
                    </div>
                </div>

            </div>

            <div class="text-center mt-4">
                <small class="text-muted">
                    Belum punya akun guru? <a href="<?= base_url('auth/register.php') ?>" class="text-decoration-none fw-semibold" style="color: var(--color-primary);">Daftar di sini</a>
                </small>
            </div>

        </div>
    </div>
</div>

<style>
#tabGuru:hover, #tabAdmin:hover { opacity: 0.85; }
</style>

<script>
function selectRole(role) {
    document.getElementById('selectedRoleInput').value = role;
    const tabGuru = document.getElementById('tabGuru');
    const tabAdmin = document.getElementById('tabAdmin');

    if (role === 'admin') {
        tabAdmin.style.background = 'var(--color-primary)';
        tabAdmin.classList.add('text-white');
        tabAdmin.classList.remove('text-muted');
        tabGuru.style.background = 'transparent';
        tabGuru.classList.remove('text-white');
        tabGuru.classList.add('text-muted');
    } else {
        tabGuru.style.background = 'var(--color-primary)';
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
    const icon = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
