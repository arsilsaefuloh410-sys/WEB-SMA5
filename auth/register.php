<?php
/**
 * Pendaftaran Akun Guru Baru
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Jika sudah login, alihkan
if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$error_message = '';
$nama = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($nama) || empty($email) || empty($password)) {
        $error_message = 'Mohon lengkapi semua data formulir.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format alamat email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Kata sandi minimal harus 6 karakter demi keamanan.';
    } elseif ($password !== $password_confirm) {
        $error_message = 'Konfirmasi kata sandi tidak cocok.';
    } else {
        // Periksa apakah email sudah terdaftar
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error_message = 'Alamat email ini sudah terdaftar di sistem. Silakan masuk menggunakan email tersebut.';
        } else {
            // Daftarkan sebagai akun Guru (status aktif siap pakai)
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $insertStmt = $pdo->prepare("
                INSERT INTO users (nama, email, password, role, status, created_at)
                VALUES (?, ?, ?, 'guru', 'aktif', NOW())
            ");
            
            if ($insertStmt->execute([$nama, $email, $hashed])) {
                set_flash('success', 'Pendaftaran akun Guru berhasil! Silakan masuk dengan email dan kata sandi baru Anda.');
                header('Location: ' . base_url('auth/login.php'));
                exit;
            } else {
                $error_message = 'Terjadi kesalahan saat menyimpan data akun. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Pendaftaran Akun Guru';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card card-modern shadow-sm">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <div class="brand-icon-box mx-auto mb-3 bg-success" style="width: 52px; height: 52px; font-size: 1.5rem; background: linear-gradient(135deg, #2A4D88, #7C94B8);">
                            <i class="bi bi-person-plus"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-1">Daftar Akun Guru</h4>
                        <p class="text-muted small mb-0">Lengkapi data di bawah ini untuk mengakses bahan ajar</p>
                    </div>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div class="small"><?= e($error_message) ?></div>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label for="nama" class="form-label fw-semibold small text-dark">Nama Lengkap & Gelar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" class="form-control" id="nama" name="nama" value="<?= e($nama) ?>" placeholder="Contoh: Rahmawati, S.Pd." required autofocus>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-dark">Alamat Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="nama@sekolah.sch.id atau email pribadi" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label fw-semibold small text-dark">Kata Sandi</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Minimal 6 karakter" required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label for="password_confirm" class="form-label fw-semibold small text-dark">Ulangi Kata Sandi</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Ketik ulang sandi" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Selesaikan Pendaftaran
                        </button>
                    </form>

                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="small text-muted mb-0">Sudah memiliki akun terdaftar? 
                            <a href="<?= base_url('auth/login.php') ?>" class="fw-semibold text-primary text-decoration-none">Masuk di sini</a>
                        </p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
